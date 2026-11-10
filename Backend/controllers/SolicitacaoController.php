<?php

require_once __DIR__ . '/../models/SolicitacaoCadastro.php';
require_once __DIR__ . '/../services/NotificacaoService.php';
require_once __DIR__ . '/../services/AuditoriaService.php';

class SolicitacaoController
{
    private SolicitacaoCadastro $solicitacaoModel;

    public function __construct()
    {
        $this->solicitacaoModel = new SolicitacaoCadastro();
    }

    /**
     * Lista as solicitações de cadastro pendentes.
     */
    public function listarPendentes(): void
    {
        try {
            $solicitacoes = $this->solicitacaoModel->listarPendentes();

            $this->responder(200, [
                'success' => true,
                'solicitacoes' => $solicitacoes
            ]);

        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao buscar solicitações.'
            ]);
        }
    }

    /**
     * Busca uma solicitação específica.
     */
    public function buscarPorId(int $id): void
    {
        try {
            $solicitacao = $this->solicitacaoModel->buscarPorId($id);

            if (!$solicitacao) {
                $this->responder(404, [
                    'success' => false,
                    'message' => 'Solicitação não encontrada.'
                ]);

                return;
            }

            $this->responder(200, [
                'success' => true,
                'solicitacao' => $solicitacao
            ]);

        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao buscar solicitação.'
            ]);
        }
    }

    /**
     * Aprova uma solicitação de cadastro pendente (somente administrador).
     */
    public function aprovar(int $id, int $adminId, ?string $observacao = null): void
    {
        try {
            $usuarioId = $this->solicitacaoModel->aprovar($id, $adminId, $observacao);

            if ($usuarioId === null) {
                $this->responder(409, [
                    'success' => false,
                    'message' => 'Solicitação não encontrada ou já analisada.'
                ]);

                return;
            }

            NotificacaoService::notificarCadastroAprovado($usuarioId);
            AuditoriaService::log($adminId, 'aprovacao_cadastro', 'usuarios', "Cadastro do usuário #{$usuarioId} aprovado (solicitação #{$id}).");

            $this->responder(200, [
                'success' => true,
                'message' => 'Solicitação aprovada. Usuário ativado com sucesso.',
                'usuario_id' => $usuarioId
            ]);

        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao aprovar solicitação.'
            ]);
        }
    }

    /**
     * Rejeita uma solicitação de cadastro pendente (somente administrador).
     */
    public function rejeitar(int $id, int $adminId, ?string $observacao = null): void
    {
        try {
            $rejeitada = $this->solicitacaoModel->rejeitar($id, $adminId, $observacao);

            if (!$rejeitada) {
                $this->responder(409, [
                    'success' => false,
                    'message' => 'Solicitação não encontrada ou já analisada.'
                ]);

                return;
            }

            AuditoriaService::log($adminId, 'rejeicao_cadastro', 'usuarios', "Solicitação de cadastro #{$id} rejeitada.");

            $this->responder(200, [
                'success' => true,
                'message' => 'Solicitação rejeitada.'
            ]);

        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao rejeitar solicitação.'
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