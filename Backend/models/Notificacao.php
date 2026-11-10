<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Model responsável pelas tabelas `notificacoes`, `tipos_notificacao`
 * e `usuario_notificacao`.
 *
 * AUDITORIA (módulo de Notificações):
 * - As 3 tabelas já existiam no banco (dump original), vazias.
 * - Desenho: uma notificação (`notificacoes`) é criada UMA vez e pode
 *   ser distribuída para vários destinatários através da tabela de
 *   junção `usuario_notificacao` (que também guarda o estado
 *   lida/não lida POR usuário). Isso é o mesmo padrão já usado em
 *   `membros_projeto`, `participantes_evento`, etc.
 * - Decisão de segurança (IDOR): o "id" exposto pela API para o
 *   front-end é sempre o id da linha em `usuario_notificacao` (nunca
 *   o id da notificação em si). Toda consulta feita por esse id já
 *   filtra por `usuario_id = :usuario_logado` na mesma query, então
 *   é estruturalmente impossível um usuário ler/marcar como lida uma
 *   notificação de outro usuário alterando o id na URL.
 */
class Notificacao
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    // ==========================================================
    // TIPOS DE NOTIFICAÇÃO
    // ==========================================================

    /**
     * Busca o id de um tipo de notificação pelo nome. Se ainda não
     * existir, cria (idempotente) — mesma lógica já usada no seed de
     * `status_tarefa`/`perfis`, só que também disponível em tempo de
     * execução como rede de segurança.
     */
    public function buscarOuCriarTipoId(string $nome, ?string $descricao = null): int
    {
        $stmt = $this->db->prepare("SELECT id FROM tipos_notificacao WHERE nome = :nome LIMIT 1");
        $stmt->execute([':nome' => $nome]);
        $tipo = $stmt->fetch();

        if ($tipo) {
            return (int) $tipo['id'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO tipos_notificacao (nome, descricao)
            VALUES (:nome, :descricao)
        ");
        $stmt->execute([':nome' => $nome, ':descricao' => $descricao]);

        return (int) $this->db->lastInsertId();
    }

    // ==========================================================
    // CRIAÇÃO / DISTRIBUIÇÃO
    // ==========================================================

    /**
     * Cria uma notificação e já a distribui para a lista de
     * destinatários informada, tudo em uma única transação.
     *
     * @param string $tipoNome         Nome do tipo (ex.: 'evento').
     * @param string $titulo
     * @param string $mensagem
     * @param string|null $referenciaTabela Ex.: 'eventos', 'projetos'.
     * @param int|null $referenciaId
     * @param int|null $criadoPor      Usuário que originou a ação (pode ser null).
     * @param int[] $destinatarios     IDs dos usuários que devem receber.
     * @return int|null ID da notificação criada, ou null se não havia
     *                   nenhum destinatário válido (nada é criado).
     */
    public function criarENotificar(
        string $tipoNome,
        string $titulo,
        string $mensagem,
        ?string $referenciaTabela,
        ?int $referenciaId,
        ?int $criadoPor,
        array $destinatarios
    ): ?int {
        // Remove duplicados e valores inválidos/vazios.
        $destinatarios = array_values(array_unique(array_filter(
            array_map('intval', $destinatarios),
            fn($id) => $id > 0
        )));

        if (empty($destinatarios)) {
            return null;
        }

        $this->db->beginTransaction();

        try {
            $tipoId = $this->buscarOuCriarTipoId($tipoNome);

            $stmt = $this->db->prepare("
                INSERT INTO notificacoes
                    (tipo_notificacao_id, titulo, mensagem, referencia_tabela, referencia_id, criado_por)
                VALUES
                    (:tipo_id, :titulo, :mensagem, :referencia_tabela, :referencia_id, :criado_por)
            ");
            $stmt->execute([
                ':tipo_id' => $tipoId,
                ':titulo' => $titulo,
                ':mensagem' => $mensagem,
                ':referencia_tabela' => $referenciaTabela,
                ':referencia_id' => $referenciaId,
                ':criado_por' => $criadoPor,
            ]);

            $notificacaoId = (int) $this->db->lastInsertId();

            // INSERT IGNORE: a UNIQUE KEY (notificacao_id, usuario_id)
            // já impede duplicidade; IGNORE apenas evita que uma
            // eventual colisão derrube a transação inteira.
            $stmtDestino = $this->db->prepare("
                INSERT IGNORE INTO usuario_notificacao (notificacao_id, usuario_id)
                VALUES (:notificacao_id, :usuario_id)
            ");
            foreach ($destinatarios as $usuarioId) {
                $stmtDestino->execute([
                    ':notificacao_id' => $notificacaoId,
                    ':usuario_id' => $usuarioId,
                ]);
            }

            $this->db->commit();

            return $notificacaoId;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // ==========================================================
    // AUDIÊNCIAS (quem deve receber cada tipo de notificação)
    // ==========================================================

    /**
     * Todos os usuários ativos (para notificações "de novidade":
     * nova publicação, novo evento, nova votação, novo projeto),
     * opcionalmente excluindo quem originou a ação.
     */
    public function listarIdsUsuariosAtivos(?int $exceto = null): array
    {
        $sql = "SELECT id FROM usuarios WHERE ativo = 1 AND deleted_at IS NULL";
        $params = [];

        if ($exceto !== null) {
            $sql .= " AND id != :exceto";
            $params[':exceto'] = $exceto;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * IDs dos usuários atualmente inscritos ('inscrito') em um evento.
     */
    public function listarIdsInscritosEvento(int $eventoId, ?int $exceto = null): array
    {
        $sql = "
            SELECT usuario_id FROM participantes_evento
            WHERE evento_id = :evento_id AND status_inscricao = 'inscrito'
        ";
        $params = [':evento_id' => $eventoId];

        if ($exceto !== null) {
            $sql .= " AND usuario_id != :exceto";
            $params[':exceto'] = $exceto;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * IDs dos usuários que já votaram em uma votação.
     */
    public function listarIdsVotantes(int $votacaoId): array
    {
        $stmt = $this->db->prepare("SELECT DISTINCT usuario_id FROM votos WHERE votacao_id = :votacao_id");
        $stmt->execute([':votacao_id' => $votacaoId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * IDs dos membros de um projeto.
     */
    public function listarIdsMembrosProjeto(int $projetoId, ?int $exceto = null): array
    {
        $sql = "SELECT usuario_id FROM membros_projeto WHERE projeto_id = :projeto_id";
        $params = [':projeto_id' => $projetoId];

        if ($exceto !== null) {
            $sql .= " AND usuario_id != :exceto";
            $params[':exceto'] = $exceto;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // ==========================================================
    // LEITURA (sempre isolada por usuario_id — proteção IDOR)
    // ==========================================================

    /**
     * Lista as notificações do usuário autenticado, mais recentes
     * primeiro. `usuario_notificacao.id` é o "id" público.
     */
    public function listarPorUsuario(int $usuarioId, bool $apenasNaoLidas, int $limite, int $offset): array
    {
        $sql = "
            SELECT
                un.id,
                un.lida,
                un.lida_em,
                un.created_at AS recebida_em,
                n.id AS notificacao_id,
                n.titulo,
                n.mensagem,
                n.referencia_tabela,
                n.referencia_id,
                tn.nome AS tipo
            FROM usuario_notificacao un
            INNER JOIN notificacoes n ON n.id = un.notificacao_id
            INNER JOIN tipos_notificacao tn ON tn.id = n.tipo_notificacao_id
            WHERE un.usuario_id = :usuario_id
        ";

        if ($apenasNaoLidas) {
            $sql .= " AND un.lida = 0";
        }

        $sql .= " ORDER BY n.created_at DESC, un.id DESC LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Busca UMA notificação do usuário autenticado pelo id de
     * `usuario_notificacao`. O WHERE usuario_id = :usuario_id é a
     * proteção contra IDOR: se o id pertencer a outro usuário, a
     * consulta simplesmente não retorna nada (404 no controller),
     * nunca vaza o dado de outro usuário.
     */
    public function buscarUmaDoUsuario(int $usuarioId, int $id): ?array
    {
        $sql = "
            SELECT
                un.id,
                un.lida,
                un.lida_em,
                un.created_at AS recebida_em,
                n.id AS notificacao_id,
                n.titulo,
                n.mensagem,
                n.referencia_tabela,
                n.referencia_id,
                tn.nome AS tipo
            FROM usuario_notificacao un
            INNER JOIN notificacoes n ON n.id = un.notificacao_id
            INNER JOIN tipos_notificacao tn ON tn.id = n.tipo_notificacao_id
            WHERE un.id = :id
            AND un.usuario_id = :usuario_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':usuario_id' => $usuarioId]);

        $linha = $stmt->fetch();

        return $linha ?: null;
    }

    public function contarNaoLidas(int $usuarioId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM usuario_notificacao
            WHERE usuario_id = :usuario_id AND lida = 0
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca uma notificação como lida. Filtra por usuario_id (IDOR) e
     * por lida = 0 (evita sobrescrever lida_em de algo já lido, e
     * também serve de teste de "marcar já lida novamente": retorna
     * false na segunda tentativa, sem erro).
     */
    public function marcarComoLida(int $usuarioId, int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE usuario_notificacao
            SET lida = 1, lida_em = NOW()
            WHERE id = :id AND usuario_id = :usuario_id AND lida = 0
        ");
        $stmt->execute([':id' => $id, ':usuario_id' => $usuarioId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Verifica se a notificação existe e pertence ao usuário (usado
     * para diferenciar 404 de "já estava lida" no controller).
     */
    public function pertenceAoUsuario(int $usuarioId, int $id): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM usuario_notificacao WHERE id = :id AND usuario_id = :usuario_id
        ");
        $stmt->execute([':id' => $id, ':usuario_id' => $usuarioId]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Marca todas as notificações não lidas do usuário como lidas.
     * @return int Quantidade de notificações afetadas.
     */
    public function marcarTodasComoLidas(int $usuarioId): int
    {
        $stmt = $this->db->prepare("
            UPDATE usuario_notificacao
            SET lida = 1, lida_em = NOW()
            WHERE usuario_id = :usuario_id AND lida = 0
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->rowCount();
    }
}
