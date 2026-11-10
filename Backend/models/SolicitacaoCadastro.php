<?php

require_once __DIR__ . '/../config/database.php';

class SolicitacaoCadastro
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Lista todas as solicitações pendentes.
     */
    public function listarPendentes(): array
    {
        $sql = "
            SELECT
                sc.id,
                sc.usuario_id,
                sc.tipo_solicitacao,
                sc.status,
                sc.observacao,
                sc.ip_solicitacao,
                sc.created_at,

                u.rm,
                u.email,
                u.nome_completo,
                u.foto_url

            FROM solicitacoes_cadastro sc

            INNER JOIN usuarios u
                ON u.id = sc.usuario_id

            WHERE sc.status = 'pendente'

            ORDER BY sc.created_at ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Busca uma solicitação pelo ID.
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                sc.id,
                sc.usuario_id,
                sc.tipo_solicitacao,
                sc.status,
                sc.observacao,
                sc.created_at,

                u.rm,
                u.email,
                u.nome_completo,
                u.foto_url,
                u.ativo

            FROM solicitacoes_cadastro sc

            INNER JOIN usuarios u
                ON u.id = sc.usuario_id

            WHERE sc.id = :id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $solicitacao = $stmt->fetch();

        return $solicitacao ?: null;
    }

    /**
     * Aprova uma solicitação de cadastro pendente:
     * - marca a solicitação como aprovada;
     * - ativa o usuário vinculado (ativo = 1);
     * - vincula o usuário ao perfil padrão "aluno" (se o perfil existir
     *   e o usuário ainda não tiver nenhum perfil vinculado).
     *
     * Tudo é feito em uma única transação para garantir consistência.
     *
     * @return int|null ID do usuário aprovado, ou null se a solicitação
     *                   não existir ou já tiver sido analisada anteriormente.
     */
    public function aprovar(int $id, int $adminId, ?string $observacao = null): ?int
    {
        $this->db->beginTransaction();

        try {
            // Busca e trava a linha para evitar aprovação duplicada em requisições concorrentes
            $stmtBusca = $this->db->prepare("
                SELECT usuario_id, status
                FROM solicitacoes_cadastro
                WHERE id = :id
                FOR UPDATE
            ");
            $stmtBusca->execute([':id' => $id]);
            $solicitacao = $stmtBusca->fetch();

            if (!$solicitacao || $solicitacao['status'] !== 'pendente') {
                $this->db->rollBack();
                return null;
            }

            $usuarioId = (int) $solicitacao['usuario_id'];

            // Atualiza a solicitação
            $stmtUpdate = $this->db->prepare("
                UPDATE solicitacoes_cadastro
                SET status = 'aprovado',
                    analisado_por = :admin_id,
                    observacao = :observacao,
                    analisado_em = NOW()
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':admin_id' => $adminId,
                ':observacao' => $observacao,
                ':id' => $id
            ]);

            // Ativa o usuário
            $stmtAtiva = $this->db->prepare("
                UPDATE usuarios
                SET ativo = 1
                WHERE id = :usuario_id
            ");
            $stmtAtiva->execute([':usuario_id' => $usuarioId]);

            // Vincula o usuário ao perfil padrão "aluno", caso o perfil exista
            // e o usuário ainda não possua nenhum perfil vinculado
            $stmtPerfil = $this->db->prepare("
                INSERT INTO usuario_perfil (usuario_id, perfil_id)
                SELECT :usuario_id_1, p.id
                FROM perfis p
                WHERE p.nome = 'aluno'
                AND p.deleted_at IS NULL
                AND NOT EXISTS (
                    SELECT 1 FROM usuario_perfil up
                    WHERE up.usuario_id = :usuario_id_2
                )
                LIMIT 1
            ");
            $stmtPerfil->execute([
                ':usuario_id_1' => $usuarioId,
                ':usuario_id_2' => $usuarioId
            ]);

            $this->db->commit();

            return $usuarioId;

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Busca a solicitação PENDENTE mais recente de um usuário específico.
     * Usado pelo painel administrativo, que trabalha com o ID do usuário
     * (não com o ID da solicitação).
     */
    public function buscarPendentePorUsuario(int $usuarioId): ?array
    {
        $sql = "
            SELECT id, usuario_id, status
            FROM solicitacoes_cadastro
            WHERE usuario_id = :usuario_id
            AND status = 'pendente'
            ORDER BY id DESC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        $solicitacao = $stmt->fetch();

        return $solicitacao ?: null;
    }

    /**
     * Rejeita uma solicitação de cadastro pendente.
     * O usuário permanece inativo (ativo = 0).
     *
     * @return bool true se a solicitação foi rejeitada, false se não
     *              existir ou já tiver sido analisada anteriormente.
     */
    public function rejeitar(int $id, int $adminId, ?string $observacao = null): bool
    {
        $sql = "
            UPDATE solicitacoes_cadastro
            SET status = 'rejeitado',
                analisado_por = :admin_id,
                observacao = :observacao,
                analisado_em = NOW()
            WHERE id = :id
            AND status = 'pendente'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':admin_id' => $adminId,
            ':observacao' => $observacao,
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }
}