<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Model responsável pelas tabelas `votacoes`, `opcoes_votacao`,
 * `votos` e `resultado_votacao`.
 *
 * OBSERVAÇÃO (auditoria do módulo de Votações):
 * - `votos` tem UNIQUE KEY (votacao_id, usuario_id): o próprio banco
 *   já impede voto duplicado, além da checagem feita na aplicação.
 * - `votacoes` tem CHECK (data_fim > data_inicio) e um enum de status
 *   (agendada/aberta/encerrada/cancelada). Uma votação só aceita voto
 *   quando status='aberta' E a data atual está dentro do período —
 *   usamos os dois mecanismos que já existem, sem inventar um terceiro.
 * - `opcoes_votacao` não tem soft delete, e tem ON DELETE CASCADE para
 *   `votos`. Por isso, só permitimos alterar as opções enquanto a
 *   votação ainda está 'agendada' (antes de abrir), evitando apagar
 *   votos reais sem querer.
 * - `resultado_votacao` parece ser uma tabela de apuração congelada
 *   (tem `apurado_em`). Os resultados exibidos são SEMPRE calculados
 *   ao vivo a partir de `votos` (nunca mockados); usamos
 *   `resultado_votacao` apenas como um snapshot histórico gravado no
 *   momento em que a votação é encerrada.
 */
class Votacao
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Lista votações não excluídas, mais recentes primeiro.
     */
    public function listar(int $usuarioId, ?string $status = null): array
    {
        $sql = "
            SELECT
                v.id,
                v.titulo,
                v.descricao,
                v.tipo,
                v.data_inicio,
                v.data_fim,
                v.status,
                v.criado_por,
                u.nome_completo AS criador_nome,

                (SELECT COUNT(*) FROM votos vo WHERE vo.votacao_id = v.id) AS total_votos,

                EXISTS(
                    SELECT 1 FROM votos vo2
                    WHERE vo2.votacao_id = v.id AND vo2.usuario_id = :usuario_id
                ) AS ja_votei

            FROM votacoes v
            INNER JOIN usuarios u ON u.id = v.criado_por

            WHERE v.deleted_at IS NULL
            " . ($status ? "AND v.status = :status" : "") . "

            ORDER BY v.data_inicio DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        if ($status) {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Busca uma votação específica (sem as opções — ver listarOpcoes).
     */
    public function buscarPorId(int $id, int $usuarioId): ?array
    {
        $sql = "
            SELECT
                v.id,
                v.titulo,
                v.descricao,
                v.tipo,
                v.data_inicio,
                v.data_fim,
                v.status,
                v.criado_por,
                u.nome_completo AS criador_nome,

                (SELECT COUNT(*) FROM votos vo WHERE vo.votacao_id = v.id) AS total_votos,

                EXISTS(
                    SELECT 1 FROM votos vo2
                    WHERE vo2.votacao_id = v.id AND vo2.usuario_id = :usuario_id
                ) AS ja_votei,

                (
                    SELECT vo3.opcao_id FROM votos vo3
                    WHERE vo3.votacao_id = v.id AND vo3.usuario_id = :usuario_id_2
                    LIMIT 1
                ) AS opcao_votada_id

            FROM votacoes v
            INNER JOIN usuarios u ON u.id = v.criado_por

            WHERE v.id = :id
            AND v.deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':usuario_id' => $usuarioId,
            ':usuario_id_2' => $usuarioId
        ]);

        $votacao = $stmt->fetch();

        return $votacao ?: null;
    }

    /**
     * Verifica se uma votação existe (não excluída).
     */
    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM votacoes WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Lista as opções de uma votação, já com total de votos e
     * percentual calculados ao vivo a partir da tabela `votos`.
     */
    public function listarOpcoesComResultado(int $votacaoId): array
    {
        $sql = "
            SELECT
                o.id,
                o.descricao_opcao,
                (SELECT COUNT(*) FROM votos vo WHERE vo.opcao_id = o.id) AS total_votos
            FROM opcoes_votacao o
            WHERE o.votacao_id = :votacao_id
            ORDER BY o.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':votacao_id' => $votacaoId]);
        $opcoes = $stmt->fetchAll();

        $totalGeral = array_sum(array_column($opcoes, 'total_votos'));

        foreach ($opcoes as &$opcao) {
            $opcao['total_votos'] = (int) $opcao['total_votos'];
            $opcao['percentual'] = $totalGeral > 0
                ? round(($opcao['total_votos'] / $totalGeral) * 100, 1)
                : 0.0;
        }
        unset($opcao);

        return $opcoes;
    }

    /**
     * Lista só as opções simples (id + texto), sem cálculo de
     * resultado. Usado para popular o formulário de edição.
     */
    public function listarOpcoesSimples(int $votacaoId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, descricao_opcao FROM opcoes_votacao
            WHERE votacao_id = :votacao_id
            ORDER BY id ASC
        ");
        $stmt->execute([':votacao_id' => $votacaoId]);

        return $stmt->fetchAll();
    }

    /**
     * Busca uma opção específica (usado para validar se ela pertence
     * mesmo à votação informada, antes de registrar um voto).
     */
    public function buscarOpcao(int $opcaoId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, votacao_id, descricao_opcao FROM opcoes_votacao
            WHERE id = :id
        ");
        $stmt->execute([':id' => $opcaoId]);

        $opcao = $stmt->fetch();
        return $opcao ?: null;
    }

    /**
     * Cria uma nova votação com suas opções, em uma única transação.
     *
     * @param string[] $opcoesTextos Textos das opções (mínimo 2).
     */
    public function criar(int $criadoPor, array $dados, array $opcoesTextos): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO votacoes
                    (titulo, descricao, tipo, data_inicio, data_fim, status, criado_por)
                VALUES
                    (:titulo, :descricao, :tipo, :data_inicio, :data_fim, :status, :criado_por)
            ");
            $stmt->execute([
                ':titulo' => $dados['titulo'],
                ':descricao' => $dados['descricao'],
                ':tipo' => $dados['tipo'],
                ':data_inicio' => $dados['data_inicio'],
                ':data_fim' => $dados['data_fim'],
                ':status' => $dados['status'],
                ':criado_por' => $criadoPor
            ]);

            $votacaoId = (int) $this->db->lastInsertId();

            $stmtOpcao = $this->db->prepare("
                INSERT INTO opcoes_votacao (votacao_id, descricao_opcao)
                VALUES (:votacao_id, :descricao_opcao)
            ");
            foreach ($opcoesTextos as $texto) {
                $stmtOpcao->execute([
                    ':votacao_id' => $votacaoId,
                    ':descricao_opcao' => $texto
                ]);
            }

            $this->db->commit();
            return $votacaoId;

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Atualiza uma votação. Se $opcoesTextos for informado, substitui
     * as opções (só deve ser chamado pelo controller quando o status
     * atual salvo no banco ainda for 'agendada' — ver EventoController
     * para o mesmo padrão de checagem prévia).
     */
    public function atualizar(int $id, array $dados, ?array $opcoesTextos = null): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                UPDATE votacoes
                SET titulo = :titulo,
                    descricao = :descricao,
                    tipo = :tipo,
                    data_inicio = :data_inicio,
                    data_fim = :data_fim,
                    status = :status
                WHERE id = :id
                AND deleted_at IS NULL
            ");
            $stmt->execute([
                ':titulo' => $dados['titulo'],
                ':descricao' => $dados['descricao'],
                ':tipo' => $dados['tipo'],
                ':data_inicio' => $dados['data_inicio'],
                ':data_fim' => $dados['data_fim'],
                ':status' => $dados['status'],
                ':id' => $id
            ]);
            $atualizou = $stmt->rowCount() > 0;

            if ($opcoesTextos !== null) {
                $this->db->prepare("DELETE FROM opcoes_votacao WHERE votacao_id = :id")
                    ->execute([':id' => $id]);

                $stmtOpcao = $this->db->prepare("
                    INSERT INTO opcoes_votacao (votacao_id, descricao_opcao)
                    VALUES (:votacao_id, :descricao_opcao)
                ");
                foreach ($opcoesTextos as $texto) {
                    $stmtOpcao->execute([
                        ':votacao_id' => $id,
                        ':descricao_opcao' => $texto
                    ]);
                }
            }

            // Se a votação está sendo encerrada agora, congela o
            // resultado atual na tabela resultado_votacao (apuração).
            if (($dados['status'] ?? null) === 'encerrada') {
                $this->congelarResultado($id);
            }

            $this->db->commit();
            return $atualizou;

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Grava um snapshot dos resultados atuais na tabela
     * `resultado_votacao` (apuração), chamado ao encerrar a votação.
     */
    private function congelarResultado(int $votacaoId): void
    {
        $opcoes = $this->listarOpcoesComResultado($votacaoId);

        $stmt = $this->db->prepare("
            INSERT INTO resultado_votacao (votacao_id, opcao_id, total_votos, apurado_em)
            VALUES (:votacao_id, :opcao_id, :total_votos, NOW())
            ON DUPLICATE KEY UPDATE
                total_votos = VALUES(total_votos),
                apurado_em = VALUES(apurado_em)
        ");

        foreach ($opcoes as $opcao) {
            $stmt->execute([
                ':votacao_id' => $votacaoId,
                ':opcao_id' => $opcao['id'],
                ':total_votos' => $opcao['total_votos']
            ]);
        }
    }

    /**
     * Exclui (soft delete) uma votação.
     */
    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE votacoes SET deleted_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Verifica se o usuário já votou nessa votação.
     */
    public function jaVotou(int $votacaoId, int $usuarioId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM votos WHERE votacao_id = :votacao_id AND usuario_id = :usuario_id
        ");
        $stmt->execute([':votacao_id' => $votacaoId, ':usuario_id' => $usuarioId]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Registra o voto de um usuário. Se por alguma condição de corrida
     * o usuário já tiver votado (a UNIQUE KEY do banco garante isso),
     * uma PDOException com SQLSTATE 23000 é lançada — o controller
     * trata isso como voto duplicado.
     */
    public function registrarVoto(int $votacaoId, int $opcaoId, int $usuarioId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO votos (votacao_id, opcao_id, usuario_id)
            VALUES (:votacao_id, :opcao_id, :usuario_id)
        ");
        $stmt->execute([
            ':votacao_id' => $votacaoId,
            ':opcao_id' => $opcaoId,
            ':usuario_id' => $usuarioId
        ]);
    }
}
