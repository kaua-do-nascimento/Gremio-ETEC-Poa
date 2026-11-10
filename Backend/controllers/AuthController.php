<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Sessao.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../services/AuditoriaService.php';

class AuthController
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function login(string $login, string $senha): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $sql = "
                SELECT
                    id,
                    rm,
                    email,
                    senha_hash,
                    nome_completo,
                    foto_url,
                    ativo
                FROM usuarios
                WHERE (rm = :login OR email = :login)
                AND deleted_at IS NULL
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':login' => $login
            ]);

            $usuario = $stmt->fetch();

            if (!$usuario) {
                AuditoriaService::loginFalha(null, 'Login não encontrado (RM/e-mail incorreto).');

                $this->responder(401, [
                    'success' => false,
                    'message' => 'Credenciais inválidas.'
                ]);
                return;
            }

            if ((int) $usuario['ativo'] !== 1) {
                AuditoriaService::loginFalha((int) $usuario['id'], 'Usuário inativo.');

                $this->responder(403, [
                    'success' => false,
                    'message' => 'Usuário inativo. Aguarde a aprovação do administrador.'
                ]);
                return;
            }

            if (!password_verify($senha, $usuario['senha_hash'])) {
                AuditoriaService::loginFalha((int) $usuario['id'], 'Senha incorreta.');

                $this->responder(401, [
                    'success' => false,
                    'message' => 'Credenciais inválidas.'
                ]);
                return;
            }

            unset($usuario['senha_hash']);

            // ==============================
            // AUTENTICAÇÃO PERSISTENTE (criação de sessão/token)
            // ==============================

            $tokenTextoPuro = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $tokenTextoPuro);
            $duracaoSegundos = 7200; // 2 horas

            $sessaoModel = new Sessao();
            $sessaoModel->criar(
                (int) $usuario['id'],
                $tokenHash,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $duracaoSegundos
            );

            // Atualiza o último login (não interrompe o fluxo em caso de falha)
            try {
                $stmtUltimoLogin = $this->db->prepare("
                    UPDATE usuarios SET ultimo_login_em = NOW() WHERE id = :id
                ");
                $stmtUltimoLogin->execute([':id' => $usuario['id']]);
            } catch (PDOException $e) {
                // não crítico para o login
            }

            $usuarioModel = new Usuario();
            $perfis = $usuarioModel->buscarPerfis((int) $usuario['id']);

            AuditoriaService::loginSucesso((int) $usuario['id']);

            $this->responder(200, [
                'success' => true,
                'message' => 'Login realizado com sucesso.',
                'token' => $tokenTextoPuro,
                'expira_em_segundos' => $duracaoSegundos,
                'usuario' => $usuario,
                'perfis' => $perfis
            ]);

        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro interno do servidor.'
            ]);
        }
    }

    /**
     * Encerra a sessão atual (logout). Requer que o token já tenha
     * sido validado pelo middleware de autenticação.
     */
    public function logout(string $tokenHash, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $sessaoModel = new Sessao();
            $sessaoModel->encerrar($tokenHash);

            AuditoriaService::log($usuarioId, 'logout', 'autenticacao', 'Logout realizado.');

            $this->responder(200, [
                'success' => true,
                'message' => 'Logout realizado com sucesso.'
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao encerrar a sessão.'
            ]);
        }
    }

    /**
     * Retorna os dados do usuário autenticado (rota protegida GET /api/me).
     * Recebe o array já resolvido pelo middleware autenticarRequisicao().
     */
    public function me(array $usuarioAutenticado): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $this->responder(200, [
            'success' => true,
            'usuario' => [
                'id' => $usuarioAutenticado['id'],
                'rm' => $usuarioAutenticado['rm'],
                'email' => $usuarioAutenticado['email'],
                'nome_completo' => $usuarioAutenticado['nome_completo'],
                'foto_url' => $usuarioAutenticado['foto_url'],
                'perfis' => $usuarioAutenticado['perfis']
            ]
        ]);
    }

    private function responder(int $status, array $dados): void
    {
        http_response_code($status);
        echo json_encode(
            $dados,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
