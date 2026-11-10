<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../services/AuditoriaService.php';

/**
 * Conta do próprio usuário logado e recuperação de senha:
 *
 *   - atualizarPerfil ... edita nome completo e e-mail (POST /api/me/perfil)
 *   - alterarSenha ...... troca a senha exigindo a senha atual (POST /api/me/senha)
 *   - solicitarRecuperacao / redefinirSenha ... fluxo "esqueci minha senha"
 *     usando a tabela já existente `tokens_recuperacao` (nenhuma tabela nova).
 *
 * Segurança:
 *   - o token é gerado com random_bytes e só o HASH (sha256) é guardado;
 *   - a resposta de "solicitar recuperação" é sempre a mesma, exista ou não
 *     a conta (não permite descobrir quais e-mails/RMs estão cadastrados);
 *   - trocar/redefinir a senha encerra as outras sessões do usuário;
 *   - senha e token nunca são gravados em log.
 */
class ContaController
{
    private PDO $db;
    private Usuario $usuarioModel;
    private array $config;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->usuarioModel = new Usuario();
        $this->config = require __DIR__ . '/../config/app.php';
    }

    private function responder(int $status, array $dados): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function senhaValida($senha): ?string
    {
        if (!is_string($senha) || $senha === '') {
            return 'A senha é obrigatória.';
        }
        if (strlen($senha) < 8) {
            return 'A senha deve possuir pelo menos 8 caracteres.';
        }
        if (strlen($senha) > 72) {
            return 'A senha deve possuir no máximo 72 caracteres.';
        }
        return null;
    }

    // ==========================================================
    // PERFIL
    // ==========================================================

    public function atualizarPerfil(int $usuarioId, array $dados): void
    {
        try {
            $nome = trim((string) ($dados['nome_completo'] ?? ''));
            $email = trim((string) ($dados['email'] ?? ''));

            if (mb_strlen($nome) < 3 || mb_strlen($nome) > 150) {
                $this->responder(400, ['success' => false, 'message' => 'O nome completo deve ter entre 3 e 150 caracteres.']);
                return;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
                $this->responder(400, ['success' => false, 'message' => 'E-mail inválido.']);
                return;
            }

            $erro = $this->usuarioModel->atualizarDadosBasicos($usuarioId, $nome, $email);
            if ($erro !== null) {
                $this->responder(409, ['success' => false, 'message' => $erro]);
                return;
            }

            AuditoriaService::log($usuarioId, 'atualizacao', 'usuarios', 'Usuário atualizou o próprio perfil.');

            $usuario = $this->usuarioModel->buscarPorId($usuarioId);
            $this->responder(200, [
                'success' => true,
                'message' => 'Perfil atualizado com sucesso.',
                'usuario' => $usuario,
            ]);
        } catch (PDOException $e) {
            error_log('[ContaController] atualizarPerfil: ' . $e->getMessage());
            $this->responder(500, ['success' => false, 'message' => 'Erro interno do servidor.']);
        }
    }

    // ==========================================================
    // TROCA DE SENHA (usuário logado)
    // ==========================================================

    public function alterarSenha(int $usuarioId, string $tokenHashAtual, array $dados): void
    {
        try {
            $atual = $dados['senha_atual'] ?? '';
            $nova = $dados['nova_senha'] ?? '';

            if (!is_string($atual) || $atual === '') {
                $this->responder(400, ['success' => false, 'message' => 'Informe a senha atual.']);
                return;
            }
            if (($erro = $this->senhaValida($nova)) !== null) {
                $this->responder(400, ['success' => false, 'message' => $erro]);
                return;
            }
            if ($atual === $nova) {
                $this->responder(400, ['success' => false, 'message' => 'A nova senha deve ser diferente da atual.']);
                return;
            }

            $stmt = $this->db->prepare("SELECT senha_hash FROM usuarios WHERE id = :id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([':id' => $usuarioId]);
            $linha = $stmt->fetch();

            if (!$linha || !password_verify($atual, $linha['senha_hash'])) {
                AuditoriaService::log($usuarioId, 'atualizacao', 'autenticacao', 'Tentativa de troca de senha com senha atual incorreta.');
                $this->responder(403, ['success' => false, 'message' => 'A senha atual está incorreta.']);
                return;
            }

            $this->db->beginTransaction();
            $this->db->prepare("UPDATE usuarios SET senha_hash = :h WHERE id = :id")
                ->execute([':h' => password_hash($nova, PASSWORD_DEFAULT), ':id' => $usuarioId]);
            // Encerra as OUTRAS sessões (a atual continua válida)
            $this->db->prepare("
                UPDATE sessoes SET ativa = 0, encerrada_em = NOW()
                WHERE usuario_id = :id AND ativa = 1 AND token_sessao <> :atual
            ")->execute([':id' => $usuarioId, ':atual' => $tokenHashAtual]);
            $this->db->commit();

            AuditoriaService::log($usuarioId, 'atualizacao', 'autenticacao', 'Usuário alterou a própria senha.');

            $this->responder(200, ['success' => true, 'message' => 'Senha alterada com sucesso.']);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[ContaController] alterarSenha: ' . $e->getMessage());
            $this->responder(500, ['success' => false, 'message' => 'Erro interno do servidor.']);
        }
    }

    // ==========================================================
    // SESSÕES
    // ==========================================================

    /** Encerra todas as sessões do usuário, exceto a atual. */
    public function encerrarOutrasSessoes(int $usuarioId, string $tokenHashAtual): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE sessoes SET ativa = 0, encerrada_em = NOW()
                WHERE usuario_id = :id AND ativa = 1 AND token_sessao <> :atual
            ");
            $stmt->execute([':id' => $usuarioId, ':atual' => $tokenHashAtual]);
            $qtd = $stmt->rowCount();

            AuditoriaService::log($usuarioId, 'atualizacao', 'autenticacao', "Usuário encerrou {$qtd} outra(s) sessão(ões).");

            $this->responder(200, [
                'success' => true,
                'message' => $qtd > 0 ? "{$qtd} sessão(ões) encerrada(s)." : 'Não havia outras sessões ativas.',
                'encerradas' => $qtd,
            ]);
        } catch (PDOException $e) {
            error_log('[ContaController] encerrarOutrasSessoes: ' . $e->getMessage());
            $this->responder(500, ['success' => false, 'message' => 'Erro interno do servidor.']);
        }
    }

    // ==========================================================
    // RECUPERAÇÃO DE SENHA ("esqueci minha senha")
    // ==========================================================

    public function solicitarRecuperacao(array $dados, string $urlBaseFront): void
    {
        $mensagemPadrao = 'Se os dados informados estiverem corretos, enviamos as instruções para redefinir a senha.';

        try {
            $email = trim((string) ($dados['email'] ?? ''));
            $rm = trim((string) ($dados['rm'] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($rm) !== 5) {
                $this->responder(400, ['success' => false, 'message' => 'Informe um e-mail válido e o RM com 5 caracteres.']);
                return;
            }

            $stmt = $this->db->prepare("
                SELECT id, ativo FROM usuarios
                WHERE email = :email AND rm = :rm AND deleted_at IS NULL LIMIT 1
            ");
            $stmt->execute([':email' => $email, ':rm' => $rm]);
            $usuario = $stmt->fetch();

            $resposta = ['success' => true, 'message' => $mensagemPadrao];

            if ($usuario && (int) $usuario['ativo'] === 1) {
                $usuarioId = (int) $usuario['id'];

                // Limite de pedidos por hora
                $stmtLim = $this->db->prepare("
                    SELECT COUNT(*) FROM tokens_recuperacao
                    WHERE usuario_id = :id AND created_at > (NOW() - INTERVAL 1 HOUR)
                ");
                $stmtLim->execute([':id' => $usuarioId]);
                if ((int) $stmtLim->fetchColumn() >= (int) $this->config['recuperacao_max_pedidos_por_hora']) {
                    $this->responder(200, $resposta);
                    return;
                }

                // Invalida pedidos anteriores ainda não usados
                $this->db->prepare("
                    UPDATE tokens_recuperacao SET utilizado = 1, utilizado_em = NOW()
                    WHERE usuario_id = :id AND utilizado = 0
                ")->execute([':id' => $usuarioId]);

                $tokenPuro = bin2hex(random_bytes(32));
                $minutos = (int) $this->config['recuperacao_validade_minutos'];

                $this->db->prepare("
                    INSERT INTO tokens_recuperacao (usuario_id, token, ip_solicitacao, user_agent, expira_em)
                    VALUES (:id, :token, :ip, :ua, DATE_ADD(NOW(), INTERVAL {$minutos} MINUTE))
                ")->execute([
                    ':id' => $usuarioId,
                    ':token' => hash('sha256', $tokenPuro),
                    ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    ':ua' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
                ]);

                AuditoriaService::log($usuarioId, 'criacao', 'autenticacao', 'Solicitação de recuperação de senha registrada.');

                $link = rtrim($urlBaseFront, '/') . '/redefinir-senha.php?token=' . $tokenPuro;

                // Tentativa de envio por e-mail (silenciosa: no WAMP costuma não haver SMTP)
                $assunto = 'Recuperação de senha - Grêmio ETEC Poá';
                $corpo = "Olá!\r\n\r\nRecebemos um pedido para redefinir a sua senha.\r\n"
                    . "Acesse o link abaixo (válido por {$minutos} minutos):\r\n\r\n{$link}\r\n\r\n"
                    . "Se não foi você, ignore esta mensagem.";
                @mail($email, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $corpo,
                    "From: {$this->config['email_remetente']}\r\nContent-Type: text/plain; charset=UTF-8");

                if (!empty($this->config['exibir_link_recuperacao'])) {
                    $resposta['link_redefinicao'] = $link;
                    $resposta['aviso_demonstracao'] = 'Modo demonstração: o link é exibido aqui porque o servidor local não envia e-mails.';
                }
            }

            $this->responder(200, $resposta);
        } catch (Throwable $e) {
            error_log('[ContaController] solicitarRecuperacao: ' . $e->getMessage());
            $this->responder(500, ['success' => false, 'message' => 'Erro interno do servidor.']);
        }
    }

    /** Valida um token sem consumi-lo (usado pela página para checar o link). */
    public function validarTokenRecuperacao(string $token): void
    {
        $linha = $this->buscarTokenValido($token);
        if (!$linha) {
            $this->responder(400, ['success' => false, 'message' => 'Link inválido ou expirado. Solicite uma nova recuperação.']);
            return;
        }
        $this->responder(200, ['success' => true, 'message' => 'Link válido.']);
    }

    private function buscarTokenValido(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $stmt = $this->db->prepare("
            SELECT id, usuario_id FROM tokens_recuperacao
            WHERE token = :t AND utilizado = 0 AND expira_em > NOW() LIMIT 1
        ");
        $stmt->execute([':t' => hash('sha256', $token)]);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    public function redefinirSenha(array $dados): void
    {
        try {
            $token = (string) ($dados['token'] ?? '');
            $nova = $dados['nova_senha'] ?? '';

            if (($erro = $this->senhaValida($nova)) !== null) {
                $this->responder(400, ['success' => false, 'message' => $erro]);
                return;
            }

            $this->db->beginTransaction();

            $linha = $this->buscarTokenValido($token);
            if (!$linha) {
                $this->db->rollBack();
                $this->responder(400, ['success' => false, 'message' => 'Link inválido ou expirado. Solicite uma nova recuperação.']);
                return;
            }

            // Consome o token de forma atômica (evita uso duplo em requisições simultâneas)
            $consumo = $this->db->prepare("
                UPDATE tokens_recuperacao SET utilizado = 1, utilizado_em = NOW()
                WHERE id = :id AND utilizado = 0
            ");
            $consumo->execute([':id' => $linha['id']]);
            if ($consumo->rowCount() !== 1) {
                $this->db->rollBack();
                $this->responder(400, ['success' => false, 'message' => 'Link inválido ou expirado. Solicite uma nova recuperação.']);
                return;
            }

            $usuarioId = (int) $linha['usuario_id'];
            $this->db->prepare("UPDATE usuarios SET senha_hash = :h WHERE id = :id AND deleted_at IS NULL")
                ->execute([':h' => password_hash($nova, PASSWORD_DEFAULT), ':id' => $usuarioId]);
            $this->db->prepare("UPDATE sessoes SET ativa = 0, encerrada_em = NOW() WHERE usuario_id = :id AND ativa = 1")
                ->execute([':id' => $usuarioId]);

            $this->db->commit();

            AuditoriaService::log($usuarioId, 'atualizacao', 'autenticacao', 'Senha redefinida por link de recuperação.');

            $this->responder(200, ['success' => true, 'message' => 'Senha redefinida com sucesso. Faça login com a nova senha.']);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[ContaController] redefinirSenha: ' . $e->getMessage());
            $this->responder(500, ['success' => false, 'message' => 'Erro interno do servidor.']);
        }
    }
}
