<?php

require_once __DIR__ . '/../config/database.php';

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Busca um usuário pelo ID.
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                id,
                rm,
                email,
                nome_completo,
                foto_url,
                ativo,
                ultimo_login_em,
                created_at,
                updated_at
            FROM usuarios
            WHERE id = :id
            AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    /**
     * Busca um usuário pelo RM.
     */
    public function buscarPorRm(string $rm): ?array
    {
        $sql = "
            SELECT
                id,
                rm,
                email,
                nome_completo,
                foto_url,
                ativo
            FROM usuarios
            WHERE rm = :rm
            AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':rm' => $rm
        ]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    /**
     * Busca um usuário pelo e-mail.
     */
    public function buscarPorEmail(string $email): ?array
    {
        $sql = "
            SELECT
                id,
                rm,
                email,
                nome_completo,
                foto_url,
                ativo
            FROM usuarios
            WHERE email = :email
            AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email
        ]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    /**
     * Verifica se RM já está cadastrado.
     */
    public function rmExiste(string $rm): bool
    {
        $sql = "
            SELECT id
            FROM usuarios
            WHERE rm = :rm
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':rm' => $rm
        ]);

        return (bool) $stmt->fetch();
    }

    /**
     * Verifica se e-mail já está cadastrado.
     */
    public function emailExiste(string $email): bool
    {
        $sql = "
            SELECT id
            FROM usuarios
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email
        ]);

        return (bool) $stmt->fetch();
    }
    public function criar(
    string $rm,
    string $email,
    string $senhaHash,
    string $nomeCompleto
): int {
    $sql = "
        INSERT INTO usuarios
        (
            rm,
            email,
            senha_hash,
            nome_completo,
            foto_url,
            ativo
        )
        VALUES
        (
            :rm,
            :email,
            :senha_hash,
            :nome_completo,
            'nsa/default.png',
            0
        )
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        ':rm' => $rm,
        ':email' => $email,
        ':senha_hash' => $senhaHash,
        ':nome_completo' => $nomeCompleto
    ]);

    return (int) $this->db->lastInsertId();
}
public function criarSolicitacaoCadastro(
    int $usuarioId,
    ?string $ip,
    ?string $userAgent
): int {
    $sql = "
        INSERT INTO solicitacoes_cadastro
        (
            usuario_id,
            tipo_solicitacao,
            status,
            ip_solicitacao,
            user_agent
        )
        VALUES
        (
            :usuario_id,
            'cadastro',
            'pendente',
            :ip_solicitacao,
            :user_agent
        )
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':ip_solicitacao' => $ip,
        ':user_agent' => $userAgent
    ]);

    return (int) $this->db->lastInsertId();
}
public function getDatabase(): PDO
{
    return $this->db;
}

/**
 * Retorna os nomes dos perfis (papéis) vinculados ao usuário.
 * Usado pela autorização por perfil (RBAC) e pelo login.
 */
public function buscarPerfis(int $usuarioId): array
{
    $sql = "
        SELECT p.nome
        FROM usuario_perfil up
        INNER JOIN perfis p
            ON p.id = up.perfil_id
        WHERE up.usuario_id = :usuario_id
        AND p.deleted_at IS NULL
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        ':usuario_id' => $usuarioId
    ]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Informa se o usuário possui o perfil "administrador".
 * Usado para proteger administradores contra exclusão e suspensão.
 */
public function ehAdministrador(int $id): bool
{
    $stmt = $this->db->prepare("
        SELECT 1
        FROM usuario_perfil up
        INNER JOIN perfis p ON p.id = up.perfil_id
        WHERE up.usuario_id = :id
          AND p.nome = 'administrador'
          AND p.deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);

    return (bool) $stmt->fetchColumn();
}

/**
 * Ativa um usuário (usado após aprovação de solicitação de cadastro).
 */
public function ativar(int $id): bool
{
    $sql = "
        UPDATE usuarios
        SET ativo = 1
        WHERE id = :id
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        ':id' => $id
    ]);

    return $stmt->rowCount() > 0;
}

/**
 * Desativa (suspende) um usuário. Usado pelo painel administrativo.
 */
public function desativar(int $id): bool
{
    $sql = "
        UPDATE usuarios
        SET ativo = 0
        WHERE id = :id
        AND deleted_at IS NULL
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        ':id' => $id
    ]);

    return $stmt->rowCount() > 0;
}

/**
 * Lista todos os usuários não excluídos, com status calculado
 * (ativo / pendente / suspenso) e o nome do perfil vinculado.
 *
 * Regra de negócio para o status exibido no painel administrativo:
 *   - "pendente": existe uma solicitação de cadastro com status
 *     'pendente' ainda não analisada para esse usuário;
 *   - "ativo": usuarios.ativo = 1;
 *   - "suspenso": usuarios.ativo = 0 e não há solicitação pendente
 *     (ou seja, já foi aprovado/rejeitado antes e depois desativado).
 */
public function listarTodos(): array
{
    $sql = "
        SELECT
            u.id,
            u.rm,
            u.email,
            u.nome_completo,
            u.ativo,
            u.created_at,
            (
                SELECT sc.status
                FROM solicitacoes_cadastro sc
                WHERE sc.usuario_id = u.id
                ORDER BY sc.id DESC
                LIMIT 1
            ) AS ultima_solicitacao_status,
            (
                SELECT GROUP_CONCAT(p.nome SEPARATOR ', ')
                FROM usuario_perfil up
                INNER JOIN perfis p ON p.id = up.perfil_id
                WHERE up.usuario_id = u.id
                AND p.deleted_at IS NULL
            ) AS perfis
        FROM usuarios u
        WHERE u.deleted_at IS NULL
        ORDER BY u.created_at DESC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();

    $usuarios = $stmt->fetchAll();

    foreach ($usuarios as &$usuario) {
        if ($usuario['ultima_solicitacao_status'] === 'pendente') {
            $usuario['status'] = 'pendente';
        } elseif ((int) $usuario['ativo'] === 1) {
            $usuario['status'] = 'ativo';
        } else {
            $usuario['status'] = 'suspenso';
        }
    }
    unset($usuario);

    return $usuarios;
}

/**
 * Atualiza nome completo e e-mail de um usuário (painel administrativo).
 * Retorna null em caso de sucesso, ou uma string com o motivo da falha.
 */
public function atualizarDadosBasicos(int $id, string $nomeCompleto, string $email): ?string
{
    $existente = $this->buscarPorEmail($email);
    if ($existente && (int) $existente['id'] !== $id) {
        return 'Este e-mail já está em uso por outro usuário.';
    }

    $sql = "
        UPDATE usuarios
        SET nome_completo = :nome_completo,
            email = :email
        WHERE id = :id
        AND deleted_at IS NULL
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        ':nome_completo' => $nomeCompleto,
        ':email' => $email,
        ':id' => $id
    ]);

    return null;
}

/**
 * Exclui (soft delete) um usuário e encerra todas as suas sessões ativas.
 */
public function excluir(int $id): bool
{
    $this->db->beginTransaction();

    try {
        $stmt = $this->db->prepare("
            UPDATE usuarios
            SET deleted_at = NOW(),
                ativo = 0
            WHERE id = :id
            AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
        $excluiu = $stmt->rowCount() > 0;

        if ($excluiu) {
            $this->db->prepare("
                UPDATE sessoes SET ativa = 0, encerrada_em = NOW()
                WHERE usuario_id = :id AND ativa = 1
            ")->execute([':id' => $id]);
        }

        $this->db->commit();
        return $excluiu;

    } catch (PDOException $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        throw $e;
    }
}
}