<?php

require_once __DIR__ . '/../models/Notificacao.php';

class NotificacaoController
{
    private Notificacao $notificacaoModel;

    private const LIMITE_PADRAO = 30;
    private const LIMITE_MAXIMO = 100;

    public function __construct()
    {
        $this->notificacaoModel = new Notificacao();
    }

    private function formatar(array $linha): array
    {
        return [
            'id' => (int) $linha['id'],
            'tipo' => $linha['tipo'],
            'titulo' => $linha['titulo'],
            'mensagem' => $linha['mensagem'],
            'referencia_tabela' => $linha['referencia_tabela'],
            'referencia_id' => $linha['referencia_id'] !== null ? (int) $linha['referencia_id'] : null,
            'lida' => (bool) $linha['lida'],
            'lida_em' => $linha['lida_em'],
            'created_at' => $linha['recebida_em'],
        ];
    }

    /**
     * Lista as notificações do usuário autenticado.
     * Aceita ?apenas_nao_lidas=1, ?limite= e ?pagina= opcionais.
     */
    public function listar(int $usuarioId, bool $apenasNaoLidas, int $pagina, int $limite): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $limite = max(1, min(self::LIMITE_MAXIMO, $limite ?: self::LIMITE_PADRAO));
        $pagina = max(1, $pagina);
        $offset = ($pagina - 1) * $limite;

        try {
            $linhas = $this->notificacaoModel->listarPorUsuario($usuarioId, $apenasNaoLidas, $limite, $offset);

            $this->responder(200, [
                'success' => true,
                'notificacoes' => array_map([$this, 'formatar'], $linhas),
                'pagina' => $pagina,
                'limite' => $limite,
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar notificações.']);
        }
    }

    /**
     * Busca uma notificação específica do usuário autenticado.
     * O isolamento por usuário é feito dentro do próprio model
     * (WHERE usuario_id = :usuario_id) — por isso o retorno é 404
     * (e não 403) quando o id pertence a outro usuário: do ponto de
     * vista de quem pediu, o recurso simplesmente não existe para ele.
     */
    public function buscarPorId(int $usuarioId, int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $linha = $this->notificacaoModel->buscarUmaDoUsuario($usuarioId, $id);

            if (!$linha) {
                $this->responder(404, ['success' => false, 'message' => 'Notificação não encontrada.']);
                return;
            }

            $this->responder(200, ['success' => true, 'notificacao' => $this->formatar($linha)]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao buscar notificação.']);
        }
    }

    public function contarNaoLidas(int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $total = $this->notificacaoModel->contarNaoLidas($usuarioId);
            $this->responder(200, ['success' => true, 'total' => $total]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao contar notificações não lidas.']);
        }
    }

    public function marcarComoLida(int $usuarioId, int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!$this->notificacaoModel->pertenceAoUsuario($usuarioId, $id)) {
                $this->responder(404, ['success' => false, 'message' => 'Notificação não encontrada.']);
                return;
            }

            $marcou = $this->notificacaoModel->marcarComoLida($usuarioId, $id);

            // Se pertence ao usuário mas não foi marcada agora, é
            // porque já estava lida antes — não é erro.
            $this->responder(200, [
                'success' => true,
                'message' => $marcou ? 'Notificação marcada como lida.' : 'Notificação já estava lida.',
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao marcar notificação como lida.']);
        }
    }

    public function marcarTodasComoLidas(int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $total = $this->notificacaoModel->marcarTodasComoLidas($usuarioId);

            $this->responder(200, [
                'success' => true,
                'message' => 'Notificações marcadas como lidas.',
                'total_marcadas' => $total,
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao marcar notificações como lidas.']);
        }
    }

    private function responder(int $status, array $dados): void
    {
        http_response_code($status);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
