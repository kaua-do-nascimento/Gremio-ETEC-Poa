<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Model responsável pelas tabelas `eventos`, `participantes_evento`
 * e `presenca_evento`.
 *
 * OBSERVAÇÃO (auditoria do módulo de Eventos):
 * - A tabela `anexos_evento` existe no banco, mas o front-end não tem
 *   nenhuma tela de upload de arquivo (nem para eventos, nem para
 *   publicações). Por instrução explícita de não inventar um sistema
 *   de upload complexo que não existe, este model NÃO implementa
 *   anexos. Fica registrado como limitação no relatório final.
 * - `participantes_evento` tem UNIQUE KEY (evento_id, usuario_id):
 *   o próprio banco já impede inscrição duplicada. "Cancelar" não
 *   apaga a linha, apenas muda `status_inscricao` para 'cancelado'
 *   (histórico é preservado, e permite reinscrição depois).
 */
class Evento
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Lista eventos não excluídos, mais próximos primeiro, com a
     * contagem de participantes inscritos e se o usuário atual está
     * inscrito.
     */
    public function listar(int $usuarioId, ?string $status = null): array
    {
        $sql = "
            SELECT
                e.id,
                e.titulo,
                e.descricao,
                e.imagem_url,
                e.local,
                e.data_evento,
                e.hora_inicio,
                e.hora_fim,
                e.limite_participantes,
                e.status,
                e.criado_por,
                u.nome_completo AS criador_nome,

                (
                    SELECT COUNT(*) FROM participantes_evento pe
                    WHERE pe.evento_id = e.id AND pe.status_inscricao = 'inscrito'
                ) AS total_inscritos,

                EXISTS(
                    SELECT 1 FROM participantes_evento pe2
                    WHERE pe2.evento_id = e.id
                    AND pe2.usuario_id = :usuario_id
                    AND pe2.status_inscricao = 'inscrito'
                ) AS inscrito_por_mim

            FROM eventos e
            INNER JOIN usuarios u ON u.id = e.criado_por

            WHERE e.deleted_at IS NULL
            " . ($status ? "AND e.status = :status" : "") . "

            ORDER BY e.data_evento ASC, e.hora_inicio ASC
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
     * Lista os próximos eventos planejados (data >= hoje), para o
     * widget de agenda do feed principal.
     */
    public function listarProximos(int $usuarioId, int $limite = 5): array
    {
        $sql = "
            SELECT
                e.id,
                e.titulo,
                e.local,
                e.data_evento,
                e.hora_inicio,

                EXISTS(
                    SELECT 1 FROM participantes_evento pe2
                    WHERE pe2.evento_id = e.id
                    AND pe2.usuario_id = :usuario_id
                    AND pe2.status_inscricao = 'inscrito'
                ) AS inscrito_por_mim

            FROM eventos e

            WHERE e.deleted_at IS NULL
            AND e.status IN ('planejado', 'em_andamento')
            AND e.data_evento >= CURDATE()

            ORDER BY e.data_evento ASC, e.hora_inicio ASC
            LIMIT :limite
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Busca um evento específico com os mesmos dados agregados.
     */
    public function buscarPorId(int $id, int $usuarioId): ?array
    {
        $sql = "
            SELECT
                e.id,
                e.titulo,
                e.descricao,
                e.imagem_url,
                e.local,
                e.data_evento,
                e.hora_inicio,
                e.hora_fim,
                e.limite_participantes,
                e.status,
                e.criado_por,
                u.nome_completo AS criador_nome,

                (
                    SELECT COUNT(*) FROM participantes_evento pe
                    WHERE pe.evento_id = e.id AND pe.status_inscricao = 'inscrito'
                ) AS total_inscritos,

                EXISTS(
                    SELECT 1 FROM participantes_evento pe2
                    WHERE pe2.evento_id = e.id
                    AND pe2.usuario_id = :usuario_id
                    AND pe2.status_inscricao = 'inscrito'
                ) AS inscrito_por_mim

            FROM eventos e
            INNER JOIN usuarios u ON u.id = e.criado_por

            WHERE e.id = :id
            AND e.deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':usuario_id' => $usuarioId
        ]);

        $evento = $stmt->fetch();

        return $evento ?: null;
    }

    /**
     * Verifica se um evento existe (não excluído).
     */
    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM eventos WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Cria um novo evento.
     */
    public function criar(int $criadoPor, array $dados): int
    {
        $sql = "
            INSERT INTO eventos
                (titulo, descricao, imagem_url, local, data_evento, hora_inicio, hora_fim, limite_participantes, status, criado_por)
            VALUES
                (:titulo, :descricao, :imagem_url, :local, :data_evento, :hora_inicio, :hora_fim, :limite_participantes, :status, :criado_por)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':titulo' => $dados['titulo'],
            ':descricao' => $dados['descricao'],
            ':imagem_url' => $dados['imagem_url'],
            ':local' => $dados['local'],
            ':data_evento' => $dados['data_evento'],
            ':hora_inicio' => $dados['hora_inicio'],
            ':hora_fim' => $dados['hora_fim'],
            ':limite_participantes' => $dados['limite_participantes'],
            ':status' => $dados['status'],
            ':criado_por' => $criadoPor
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza um evento existente.
     */
    public function atualizar(int $id, array $dados): bool
    {
        $sql = "
            UPDATE eventos
            SET titulo = :titulo,
                descricao = :descricao,
                local = :local,
                data_evento = :data_evento,
                hora_inicio = :hora_inicio,
                hora_fim = :hora_fim,
                limite_participantes = :limite_participantes,
                status = :status
            WHERE id = :id
            AND deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':titulo' => $dados['titulo'],
            ':descricao' => $dados['descricao'],
            ':local' => $dados['local'],
            ':data_evento' => $dados['data_evento'],
            ':hora_inicio' => $dados['hora_inicio'],
            ':hora_fim' => $dados['hora_fim'],
            ':limite_participantes' => $dados['limite_participantes'],
            ':status' => $dados['status'],
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Exclui (soft delete) um evento.
     */
    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE eventos SET deleted_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Conta quantos participantes ativos (status 'inscrito') um
     * evento já tem. Usado para checar o limite antes de inscrever.
     */
    public function contarInscritos(int $eventoId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM participantes_evento
            WHERE evento_id = :evento_id AND status_inscricao = 'inscrito'
        ");
        $stmt->execute([':evento_id' => $eventoId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Busca a inscrição (de qualquer status) de um usuário num evento.
     */
    public function buscarInscricao(int $eventoId, int $usuarioId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, status_inscricao FROM participantes_evento
            WHERE evento_id = :evento_id AND usuario_id = :usuario_id
        ");
        $stmt->execute([':evento_id' => $eventoId, ':usuario_id' => $usuarioId]);

        $inscricao = $stmt->fetch();
        return $inscricao ?: null;
    }

    /**
     * Inscreve um usuário num evento (ou reativa uma inscrição
     * cancelada anteriormente, já que a UNIQUE KEY do banco impede
     * duas linhas para o mesmo par evento+usuário).
     */
    public function inscrever(int $eventoId, int $usuarioId): void
    {
        $inscricaoExistente = $this->buscarInscricao($eventoId, $usuarioId);

        if ($inscricaoExistente) {
            $stmt = $this->db->prepare("
                UPDATE participantes_evento
                SET status_inscricao = 'inscrito', inscrito_em = NOW()
                WHERE id = :id
            ");
            $stmt->execute([':id' => $inscricaoExistente['id']]);
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO participantes_evento (evento_id, usuario_id, status_inscricao)
            VALUES (:evento_id, :usuario_id, 'inscrito')
        ");
        $stmt->execute([':evento_id' => $eventoId, ':usuario_id' => $usuarioId]);
    }

    /**
     * Cancela a participação de um usuário num evento.
     */
    public function cancelarParticipacao(int $eventoId, int $usuarioId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE participantes_evento
            SET status_inscricao = 'cancelado'
            WHERE evento_id = :evento_id
            AND usuario_id = :usuario_id
            AND status_inscricao = 'inscrito'
        ");
        $stmt->execute([':evento_id' => $eventoId, ':usuario_id' => $usuarioId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Lista os participantes de um evento (inscritos e cancelados),
     * já incluindo o nome do aluno e se está presente (para o
     * administrador marcar presença).
     */
    public function listarParticipantes(int $eventoId): array
    {
        $sql = "
            SELECT
                pe.id AS participacao_id,
                pe.status_inscricao,
                pe.inscrito_em,

                u.id AS usuario_id,
                u.nome_completo,
                u.rm,
                u.email,

                pr.presente

            FROM participantes_evento pe
            INNER JOIN usuarios u ON u.id = pe.usuario_id
            LEFT JOIN presenca_evento pr ON pr.evento_id = pe.evento_id AND pr.usuario_id = pe.usuario_id

            WHERE pe.evento_id = :evento_id

            ORDER BY pe.status_inscricao ASC, u.nome_completo ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Registra ou altera a presença de um participante num evento
     * (INSERT ... ON DUPLICATE KEY UPDATE, aproveitando a UNIQUE KEY
     * já existente na tabela presenca_evento).
     */
    public function registrarPresenca(int $eventoId, int $usuarioId, bool $presente, int $marcadoPor): void
    {
        $sql = "
            INSERT INTO presenca_evento (evento_id, usuario_id, presente, marcado_por, marcado_em)
            VALUES (:evento_id, :usuario_id, :presente, :marcado_por, NOW())
            ON DUPLICATE KEY UPDATE
                presente = VALUES(presente),
                marcado_por = VALUES(marcado_por),
                marcado_em = VALUES(marcado_em)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':evento_id' => $eventoId,
            ':usuario_id' => $usuarioId,
            ':presente' => $presente ? 1 : 0,
            ':marcado_por' => $marcadoPor
        ]);
    }
}
