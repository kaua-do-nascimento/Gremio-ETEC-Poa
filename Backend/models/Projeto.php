<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AuditoriaService.php';

/**
 * Model responsável pelas tabelas `projetos`, `membros_projeto`,
 * `tarefas`, `status_tarefa` e `historico_projeto`.
 *
 * OBSERVAÇÃO (auditoria do módulo de Projetos):
 * - A tabela `anexos_projeto` existe no banco, mas — assim como em
 *   Eventos e Publicações — não existe nenhuma infraestrutura de
 *   upload de arquivo no projeto (sem $_FILES, sem move_uploaded_file,
 *   sem endpoint multipart em nenhum outro módulo). Seguindo a mesma
 *   decisão já tomada no módulo de Eventos, NÃO foi implementado um
 *   sistema de upload improvisado. Fica registrado como limitação no
 *   relatório final.
 * - `status_tarefa` é uma tabela de apoio (lookup) sem nenhuma linha
 *   inserida pelo dump original. Sem pelo menos uma linha nela, é
 *   impossível criar uma tarefa (FK obrigatória). Foi criado um seed
 *   idempotente em database/seed_status_tarefa.sql — no mesmo padrão
 *   do já existente database/seed_perfis.sql — sem alterar a
 *   estrutura do banco.
 */
class Projeto
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    // ==========================================================
    // PROJETOS
    // ==========================================================

    public function listar(): array
    {
        $sql = "
            SELECT
                p.id,
                p.nome,
                p.descricao,
                p.data_inicio,
                p.data_fim_prevista,
                p.data_fim_real,
                p.status,
                p.responsavel_id,
                u.nome_completo AS responsavel_nome,
                p.created_at,
                p.updated_at,

                (SELECT COUNT(*) FROM membros_projeto mp WHERE mp.projeto_id = p.id) AS total_membros,
                (SELECT COUNT(*) FROM tarefas t WHERE t.projeto_id = p.id AND t.deleted_at IS NULL) AS total_tarefas,
                (
                    SELECT COUNT(*) FROM tarefas t
                    INNER JOIN status_tarefa st ON st.id = t.status_tarefa_id
                    WHERE t.projeto_id = p.id AND t.deleted_at IS NULL AND st.nome = 'concluida'
                ) AS total_tarefas_concluidas

            FROM projetos p
            INNER JOIN usuarios u ON u.id = p.responsavel_id

            WHERE p.deleted_at IS NULL

            ORDER BY p.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                p.id,
                p.nome,
                p.descricao,
                p.data_inicio,
                p.data_fim_prevista,
                p.data_fim_real,
                p.status,
                p.responsavel_id,
                u.nome_completo AS responsavel_nome,
                p.created_at,
                p.updated_at,

                (SELECT COUNT(*) FROM membros_projeto mp WHERE mp.projeto_id = p.id) AS total_membros,
                (SELECT COUNT(*) FROM tarefas t WHERE t.projeto_id = p.id AND t.deleted_at IS NULL) AS total_tarefas,
                (
                    SELECT COUNT(*) FROM tarefas t
                    INNER JOIN status_tarefa st ON st.id = t.status_tarefa_id
                    WHERE t.projeto_id = p.id AND t.deleted_at IS NULL AND st.nome = 'concluida'
                ) AS total_tarefas_concluidas

            FROM projetos p
            INNER JOIN usuarios u ON u.id = p.responsavel_id

            WHERE p.id = :id
            AND p.deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $projeto = $stmt->fetch();

        return $projeto ?: null;
    }

    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM projetos WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    public function criar(array $dados): int
    {
        $sql = "
            INSERT INTO projetos
                (nome, descricao, data_inicio, data_fim_prevista, data_fim_real, status, responsavel_id)
            VALUES
                (:nome, :descricao, :data_inicio, :data_fim_prevista, :data_fim_real, :status, :responsavel_id)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nome' => $dados['nome'],
            ':descricao' => $dados['descricao'],
            ':data_inicio' => $dados['data_inicio'],
            ':data_fim_prevista' => $dados['data_fim_prevista'],
            ':data_fim_real' => $dados['data_fim_real'],
            ':status' => $dados['status'],
            ':responsavel_id' => $dados['responsavel_id'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sql = "
            UPDATE projetos
            SET nome = :nome,
                descricao = :descricao,
                data_inicio = :data_inicio,
                data_fim_prevista = :data_fim_prevista,
                data_fim_real = :data_fim_real,
                status = :status,
                responsavel_id = :responsavel_id
            WHERE id = :id
            AND deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nome' => $dados['nome'],
            ':descricao' => $dados['descricao'],
            ':data_inicio' => $dados['data_inicio'],
            ':data_fim_prevista' => $dados['data_fim_prevista'],
            ':data_fim_real' => $dados['data_fim_real'],
            ':status' => $dados['status'],
            ':responsavel_id' => $dados['responsavel_id'],
            ':id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Arquiva (soft delete) um projeto. O padrão já usado em todo o
     * sistema (usuarios, eventos, publicacoes, tarefas...) é
     * `deleted_at`, então "excluir" e "arquivar" são a mesma operação
     * aqui, seguindo a convenção existente.
     */
    public function arquivar(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE projetos SET deleted_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    // ==========================================================
    // MEMBROS DO PROJETO
    // ==========================================================

    public function listarMembros(int $projetoId): array
    {
        $sql = "
            SELECT
                mp.id AS membro_id,
                mp.papel,
                mp.created_at AS adicionado_em,
                u.id AS usuario_id,
                u.nome_completo,
                u.rm,
                u.email,
                u.foto_url
            FROM membros_projeto mp
            INNER JOIN usuarios u ON u.id = mp.usuario_id
            WHERE mp.projeto_id = :projeto_id
            ORDER BY mp.papel ASC, u.nome_completo ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':projeto_id' => $projetoId]);

        return $stmt->fetchAll();
    }

    public function membroExiste(int $projetoId, int $usuarioId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM membros_projeto
            WHERE projeto_id = :projeto_id AND usuario_id = :usuario_id
        ");
        $stmt->execute([':projeto_id' => $projetoId, ':usuario_id' => $usuarioId]);

        return (bool) $stmt->fetchColumn();
    }

    public function adicionarMembro(int $projetoId, int $usuarioId, string $papel): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO membros_projeto (projeto_id, usuario_id, papel)
            VALUES (:projeto_id, :usuario_id, :papel)
        ");
        $stmt->execute([
            ':projeto_id' => $projetoId,
            ':usuario_id' => $usuarioId,
            ':papel' => $papel,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function removerMembro(int $projetoId, int $usuarioId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM membros_projeto
            WHERE projeto_id = :projeto_id AND usuario_id = :usuario_id
        ");
        $stmt->execute([':projeto_id' => $projetoId, ':usuario_id' => $usuarioId]);

        return $stmt->rowCount() > 0;
    }

    // ==========================================================
    // TAREFAS
    // ==========================================================

    public function listarStatusTarefa(): array
    {
        $stmt = $this->db->prepare("SELECT id, nome, descricao FROM status_tarefa ORDER BY id ASC");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function statusTarefaExiste(int $statusId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM status_tarefa WHERE id = :id");
        $stmt->execute([':id' => $statusId]);

        return (bool) $stmt->fetchColumn();
    }

    public function listarTarefas(int $projetoId): array
    {
        $sql = "
            SELECT
                t.id,
                t.projeto_id,
                t.status_tarefa_id,
                st.nome AS status_nome,
                t.responsavel_id,
                u.nome_completo AS responsavel_nome,
                t.titulo,
                t.descricao,
                t.data_inicio,
                t.data_prazo,
                t.data_conclusao,
                t.created_at,
                t.updated_at
            FROM tarefas t
            INNER JOIN status_tarefa st ON st.id = t.status_tarefa_id
            LEFT JOIN usuarios u ON u.id = t.responsavel_id
            WHERE t.projeto_id = :projeto_id
            AND t.deleted_at IS NULL
            ORDER BY t.data_prazo IS NULL, t.data_prazo ASC, t.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':projeto_id' => $projetoId]);

        return $stmt->fetchAll();
    }

    /**
     * Busca uma tarefa pelo id, já validando (via WHERE) que ela
     * pertence ao projeto informado. Usado para impedir IDOR: uma
     * tarefa de um projeto não pode ser lida/alterada informando o
     * id de outro projeto na URL.
     */
    public function buscarTarefaDoProjeto(int $tarefaId, int $projetoId): ?array
    {
        $sql = "
            SELECT
                t.id, t.projeto_id, t.status_tarefa_id, t.responsavel_id,
                t.titulo, t.descricao, t.data_inicio, t.data_prazo, t.data_conclusao
            FROM tarefas t
            WHERE t.id = :id
            AND t.projeto_id = :projeto_id
            AND t.deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $tarefaId, ':projeto_id' => $projetoId]);

        $tarefa = $stmt->fetch();

        return $tarefa ?: null;
    }

    public function criarTarefa(int $projetoId, array $dados): int
    {
        $sql = "
            INSERT INTO tarefas
                (projeto_id, status_tarefa_id, responsavel_id, titulo, descricao, data_inicio, data_prazo, data_conclusao)
            VALUES
                (:projeto_id, :status_tarefa_id, :responsavel_id, :titulo, :descricao, :data_inicio, :data_prazo, :data_conclusao)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':projeto_id' => $projetoId,
            ':status_tarefa_id' => $dados['status_tarefa_id'],
            ':responsavel_id' => $dados['responsavel_id'],
            ':titulo' => $dados['titulo'],
            ':descricao' => $dados['descricao'],
            ':data_inicio' => $dados['data_inicio'],
            ':data_prazo' => $dados['data_prazo'],
            ':data_conclusao' => $dados['data_conclusao'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function atualizarTarefa(int $tarefaId, array $dados): bool
    {
        $sql = "
            UPDATE tarefas
            SET status_tarefa_id = :status_tarefa_id,
                responsavel_id = :responsavel_id,
                titulo = :titulo,
                descricao = :descricao,
                data_inicio = :data_inicio,
                data_prazo = :data_prazo,
                data_conclusao = :data_conclusao
            WHERE id = :id
            AND deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':status_tarefa_id' => $dados['status_tarefa_id'],
            ':responsavel_id' => $dados['responsavel_id'],
            ':titulo' => $dados['titulo'],
            ':descricao' => $dados['descricao'],
            ':data_inicio' => $dados['data_inicio'],
            ':data_prazo' => $dados['data_prazo'],
            ':data_conclusao' => $dados['data_conclusao'],
            ':id' => $tarefaId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function excluirTarefa(int $tarefaId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE tarefas SET deleted_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $tarefaId]);

        return $stmt->rowCount() > 0;
    }

    // ==========================================================
    // HISTÓRICO
    // ==========================================================

    public function registrarHistorico(int $projetoId, ?int $usuarioId, string $acao, ?string $descricao = null): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO historico_projeto (projeto_id, usuario_id, acao, descricao)
            VALUES (:projeto_id, :usuario_id, :acao, :descricao)
        ");
        $stmt->execute([
            ':projeto_id' => $projetoId,
            ':usuario_id' => $usuarioId,
            ':acao' => $acao,
            ':descricao' => $descricao,
        ]);

        // Também alimenta a trilha de auditoria geral (tabela `logs`),
        // reaproveitando a mesma ação/descrição já registrada aqui no
        // histórico específico do projeto — evita duplicar chamadas em
        // cada método do ProjetoController.
        AuditoriaService::log(
            $usuarioId,
            $acao,
            'projetos',
            ($descricao ?? '') . " (projeto #{$projetoId})"
        );
    }

    public function listarHistorico(int $projetoId): array
    {
        $sql = "
            SELECT
                h.id,
                h.acao,
                h.descricao,
                h.created_at,
                h.usuario_id,
                u.nome_completo AS usuario_nome
            FROM historico_projeto h
            LEFT JOIN usuarios u ON u.id = h.usuario_id
            WHERE h.projeto_id = :projeto_id
            ORDER BY h.created_at DESC, h.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':projeto_id' => $projetoId]);

        return $stmt->fetchAll();
    }
}
