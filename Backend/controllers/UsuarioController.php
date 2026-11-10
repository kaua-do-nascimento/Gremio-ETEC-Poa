<?php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../services/AuditoriaService.php';

class UsuarioController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    public function cadastrar(array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $rm = trim($dados['rm'] ?? '');
            $email = trim($dados['email'] ?? '');
            $nomeCompleto = trim($dados['nome_completo'] ?? '');
            $senha = $dados['senha'] ?? '';

            // ==============================
            // VALIDAÇÃO DOS CAMPOS
            // ==============================

            if (
                $rm === '' ||
                $email === '' ||
                $nomeCompleto === '' ||
                $senha === ''
            ) {
                $this->responder(400, [
                    'success' => false,
                    'message' => 'RM, e-mail, nome completo e senha são obrigatórios.'
                ]);

                return;
            }

            // RM deve possuir exatamente 5 caracteres
            if (strlen($rm) !== 5) {
                $this->responder(400, [
                    'success' => false,
                    'message' => 'O RM deve possuir exatamente 5 caracteres.'
                ]);

                return;
            }

            // Validação do e-mail
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->responder(400, [
                    'success' => false,
                    'message' => 'E-mail inválido.'
                ]);

                return;
            }

            // Nome completo
            if (strlen($nomeCompleto) < 3) {
                $this->responder(400, [
                    'success' => false,
                    'message' => 'O nome completo é inválido.'
                ]);

                return;
            }

            // Senha
            if (strlen($senha) < 8) {
                $this->responder(400, [
                    'success' => false,
                    'message' => 'A senha deve possuir pelo menos 8 caracteres.'
                ]);

                return;
            }

            // ==============================
            // VERIFICAÇÃO DE DUPLICIDADE
            // ==============================

            if ($this->usuarioModel->rmExiste($rm)) {
                $this->responder(409, [
                    'success' => false,
                    'message' => 'Este RM já está cadastrado.'
                ]);

                return;
            }

            if ($this->usuarioModel->emailExiste($email)) {
                $this->responder(409, [
                    'success' => false,
                    'message' => 'Este e-mail já está cadastrado.'
                ]);

                return;
            }

            // ==============================
            // GERAÇÃO DO HASH DA SENHA
            // ==============================

            $senhaHash = password_hash(
                $senha,
                PASSWORD_DEFAULT
            );

            // ==============================
            // TRANSAÇÃO
            // ==============================

            $db = $this->usuarioModel->getDatabase();

            try {
                $db->beginTransaction();

                // Cria o usuário INATIVO
                $usuarioId = $this->usuarioModel->criar(
                    $rm,
                    $email,
                    $senhaHash,
                    $nomeCompleto
                );

                // Cria a solicitação de cadastro
                $solicitacaoId = $this->usuarioModel->criarSolicitacaoCadastro(
                    $usuarioId,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                );

                // Confirma as duas operações
                $db->commit();

                AuditoriaService::log($usuarioId, 'cadastro_solicitado', 'usuarios', "Cadastro enviado para análise (RM {$rm}).");

                $this->responder(201, [
                    'success' => true,
                    'message' => 'Cadastro enviado para análise do administrador.',
                    'solicitacao_id' => $solicitacaoId
                ]);

            } catch (PDOException $e) {

                // Desfaz tudo se alguma operação falhar
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                $this->responder(500, [
                    'success' => false,
                    'message' => 'Não foi possível realizar o cadastro.'
                ]);
            }

        } catch (PDOException $e) {

            $this->responder(500, [
                'success' => false,
                'message' => 'Erro interno do servidor.'
            ]);
        }
    }

    /**
     * Lista todos os usuários (painel administrativo).
     */
    public function listarTodos(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $usuarios = $this->usuarioModel->listarTodos();

            $this->responder(200, [
                'success' => true,
                'usuarios' => $usuarios
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao listar usuários.'
            ]);
        }
    }

    /**
     * Muda o status de um usuário no painel administrativo.
     * Transições suportadas:
     *   - pendente -> ativo   (aprova a solicitação de cadastro)
     *   - pendente -> suspenso (rejeita a solicitação de cadastro)
     *   - ativo    -> suspenso (desativa o usuário)
     *   - suspenso -> ativo    (reativa o usuário)
     */
    public function mudarStatus(int $id, string $novoStatus, int $adminId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!in_array($novoStatus, ['ativo', 'suspenso'], true)) {
            $this->responder(400, [
                'success' => false,
                'message' => 'Status inválido. Use "ativo" ou "suspenso".'
            ]);
            return;
        }

        try {
            // Administradores não podem ser suspensos (nem por outro administrador)
            if ($novoStatus === 'suspenso' && $this->usuarioModel->ehAdministrador($id)) {
                AuditoriaService::log($adminId, 'suspensao', 'usuarios', "Tentativa bloqueada de suspender o administrador #{$id}.");
                $this->responder(403, [
                    'success' => false,
                    'message' => 'Administradores são protegidos e não podem ser suspensos.'
                ]);
                return;
            }

            require_once __DIR__ . '/../models/SolicitacaoCadastro.php';
            $solicitacaoModel = new SolicitacaoCadastro();

            $solicitacaoPendente = $solicitacaoModel->buscarPendentePorUsuario($id);

            if ($solicitacaoPendente) {
                // Usuário ainda está com cadastro pendente de análise
                if ($novoStatus === 'ativo') {
                    $solicitacaoModel->aprovar($solicitacaoPendente['id'], $adminId);
                    AuditoriaService::log($adminId, 'aprovacao_cadastro', 'usuarios', "Cadastro do usuário #{$id} aprovado.");
                } else {
                    $solicitacaoModel->rejeitar($solicitacaoPendente['id'], $adminId);
                    AuditoriaService::log($adminId, 'rejeicao_cadastro', 'usuarios', "Cadastro do usuário #{$id} rejeitado.");
                }
            } else {
                // Usuário já tinha sido analisado antes: só ativa/desativa
                if ($novoStatus === 'ativo') {
                    $this->usuarioModel->ativar($id);
                    AuditoriaService::log($adminId, 'ativacao', 'usuarios', "Usuário #{$id} reativado.");
                } else {
                    $this->usuarioModel->desativar($id);
                    AuditoriaService::log($adminId, 'suspensao', 'usuarios', "Usuário #{$id} suspenso.");
                }
            }

            $this->responder(200, [
                'success' => true,
                'message' => 'Status atualizado com sucesso.'
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao atualizar status do usuário.'
            ]);
        }
    }

    /**
     * Atualiza nome completo e e-mail de um usuário (painel administrativo).
     */
    public function atualizarDados(int $id, string $nomeCompleto, string $email, int $adminId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $nomeCompleto = trim($nomeCompleto);
        $email = trim($email);

        if (strlen($nomeCompleto) < 3) {
            $this->responder(400, [
                'success' => false,
                'message' => 'O nome completo é inválido.'
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->responder(400, [
                'success' => false,
                'message' => 'E-mail inválido.'
            ]);
            return;
        }

        try {
            $erro = $this->usuarioModel->atualizarDadosBasicos($id, $nomeCompleto, $email);

            if ($erro !== null) {
                $this->responder(409, [
                    'success' => false,
                    'message' => $erro
                ]);
                return;
            }

            AuditoriaService::log($adminId, 'atualizacao', 'usuarios', "Dados do usuário #{$id} atualizados.");

            $this->responder(200, [
                'success' => true,
                'message' => 'Dados atualizados com sucesso.'
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao atualizar dados do usuário.'
            ]);
        }
    }

    /**
     * Exclui (soft delete) um usuário. Um administrador não pode
     * excluir a própria conta por essa rota.
     */
    public function excluir(int $id, int $adminIdSolicitante): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($id === $adminIdSolicitante) {
            $this->responder(400, [
                'success' => false,
                'message' => 'Você não pode excluir a sua própria conta.'
            ]);
            return;
        }

        try {
            // Administradores não podem ser excluídos (nem por outro administrador)
            if ($this->usuarioModel->ehAdministrador($id)) {
                AuditoriaService::log($adminIdSolicitante, 'exclusao', 'usuarios', "Tentativa bloqueada de excluir o administrador #{$id}.");
                $this->responder(403, [
                    'success' => false,
                    'message' => 'Administradores são protegidos e não podem ser excluídos.'
                ]);
                return;
            }

            $excluiu = $this->usuarioModel->excluir($id);

            if (!$excluiu) {
                $this->responder(404, [
                    'success' => false,
                    'message' => 'Usuário não encontrado.'
                ]);
                return;
            }

            AuditoriaService::log($adminIdSolicitante, 'exclusao', 'usuarios', "Usuário #{$id} excluído (soft delete).");

            $this->responder(200, [
                'success' => true,
                'message' => 'Usuário excluído com sucesso.'
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao excluir usuário.'
            ]);
        }
    }

    private function responder(int $status, array $dados): void
    {
        http_response_code($status);

        echo json_encode(
            $dados,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }
}