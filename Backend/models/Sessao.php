<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Model responsável pela tabela `sessoes`, usada para implementar
 * autenticação persistente baseada em token (semelhante a um "login token").
 *
 * Fluxo:
 * 1. No login, geramos um token aleatório (texto puro) e devolvemos ao cliente.
 * 2. Armazenamos no banco apenas o HASH (sha256) desse token — nunca o valor puro.
 * 3. Nas requisições seguintes, o cliente envia:
 *      Authorization: Bearer <token>
 *    e nós comparamos o hash do token recebido com o hash salvo.
 *
 * Isso evita que, mesmo em caso de vazamento do banco de dados, os tokens
 * de sessão dos usuários fiquem expostos em texto puro.
 */
class Sessao
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Cria uma nova sessão (login) para o usuário e retorna o ID da sessão criada.
     *
     * @param string $tokenHash Hash (sha256) do token — nunca o token em texto puro.
     */
    public function criar(
        int $usuarioId,
        string $tokenHash,
        ?string $ip,
        ?string $userAgent,
        int $duracaoSegundos = 7200
    ): int {
        $sql = "
            INSERT INTO sessoes
            (
                usuario_id,
                token_sessao,
                ip_address,
                user_agent,
                iniciada_em,
                expira_em,
                ativa
            )
            VALUES
            (
                :usuario_id,
                :token_sessao,
                :ip_address,
                :user_agent,
                NOW(),
                DATE_ADD(NOW(), INTERVAL :duracao SECOND),
                1
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':token_sessao', $tokenHash, PDO::PARAM_STR);
        $stmt->bindValue(':ip_address', $ip, PDO::PARAM_STR);
        $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
        $stmt->bindValue(':duracao', $duracaoSegundos, PDO::PARAM_INT);

        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    /**
     * Busca uma sessão ativa e não expirada pelo hash do token, já retornando
     * os dados básicos do usuário vinculado (evita uma segunda consulta).
     */
    public function buscarAtivaPorTokenHash(string $tokenHash): ?array
    {
        $sql = "
            SELECT
                s.id AS sessao_id,
                s.usuario_id,
                s.expira_em,

                u.rm,
                u.email,
                u.nome_completo,
                u.foto_url,
                u.ativo

            FROM sessoes s

            INNER JOIN usuarios u
                ON u.id = s.usuario_id

            WHERE s.token_sessao = :token_sessao
            AND s.ativa = 1
            AND s.expira_em > NOW()
            AND u.deleted_at IS NULL
            AND u.ativo = 1

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':token_sessao' => $tokenHash
        ]);

        $sessao = $stmt->fetch();

        return $sessao ?: null;
    }

    /**
     * Encerra (invalida) uma sessão a partir do hash do token. Usado no logout.
     */
    public function encerrar(string $tokenHash): bool
    {
        $sql = "
            UPDATE sessoes
            SET ativa = 0,
                encerrada_em = NOW()
            WHERE token_sessao = :token_sessao
            AND ativa = 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':token_sessao' => $tokenHash
        ]);

        return $stmt->rowCount() > 0;
    }
}
