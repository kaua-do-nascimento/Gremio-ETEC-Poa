<?php

require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../services/NotificacaoService.php';
require_once __DIR__ . '/../services/AuditoriaService.php';

class EventoController
{
    private Evento $eventoModel;

    public function __construct()
    {
        $this->eventoModel = new Evento();
    }

    private const STATUS_VALIDOS = ['planejado', 'em_andamento', 'realizado', 'cancelado'];

    /**
     * Formata os campos agregados de uma linha do banco para o
     * formato de resposta da API.
     */
    private function formatarEvento(array $linha): array
    {
        return [
            'id' => (int) $linha['id'],
            'titulo' => $linha['titulo'],
            'descricao' => $linha['descricao'],
            'imagem_url' => $linha['imagem_url'],
            'local' => $linha['local'],
            'data_evento' => $linha['data_evento'],
            'hora_inicio' => substr($linha['hora_inicio'], 0, 5),
            'hora_fim' => $linha['hora_fim'] ? substr($linha['hora_fim'], 0, 5) : null,
            'limite_participantes' => $linha['limite_participantes'] !== null ? (int) $linha['limite_participantes'] : null,
            'status' => $linha['status'],
            'criador_nome' => $linha['criador_nome'],
            'total_inscritos' => (int) $linha['total_inscritos'],
            'inscrito_por_mim' => (bool) $linha['inscrito_por_mim'],
        ];
    }

    /**
     * Valida os campos de entrada para criação/edição de evento.
     * Retorna uma mensagem de erro (string) ou null se estiver tudo ok.
     */
    private function validarDados(array $dados): ?string
    {
        $titulo = trim((string) ($dados['titulo'] ?? ''));
        if ($titulo === '' || mb_strlen($titulo) > 150) {
            return 'Título é obrigatório e deve ter no máximo 150 caracteres.';
        }

        $dataEvento = (string) ($dados['data_evento'] ?? '');
        $partesData = DateTime::createFromFormat('Y-m-d', $dataEvento);
        if (!$partesData || $partesData->format('Y-m-d') !== $dataEvento) {
            return 'Data do evento inválida. Use o formato AAAA-MM-DD.';
        }

        $horaInicio = (string) ($dados['hora_inicio'] ?? '');
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horaInicio)) {
            return 'Horário de início inválido. Use o formato HH:MM.';
        }

        $horaFim = $dados['hora_fim'] ?? null;
        if ($horaFim !== null && $horaFim !== '') {
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horaFim)) {
                return 'Horário de término inválido. Use o formato HH:MM.';
            }
            if ($horaFim <= $horaInicio) {
                return 'O horário de término deve ser depois do horário de início.';
            }
        }

        $limite = $dados['limite_participantes'] ?? null;
        if ($limite !== null && $limite !== '') {
            if (!is_numeric($limite) || (int) $limite < 1) {
                return 'Limite de participantes deve ser um número inteiro maior que zero.';
            }
        }

        $status = (string) ($dados['status'] ?? 'planejado');
        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            return 'Status inválido.';
        }

        return null;
    }

    /**
     * Monta o array de dados normalizado para passar ao model.
     */
    private function normalizarDados(array $dados): array
    {
        $limite = $dados['limite_participantes'] ?? null;

        return [
            'titulo' => trim((string) $dados['titulo']),
            'descricao' => isset($dados['descricao']) ? trim((string) $dados['descricao']) : null,
            'imagem_url' => isset($dados['imagem_url']) && $dados['imagem_url'] !== '' ? (string) $dados['imagem_url'] : null,
            'local' => isset($dados['local']) && $dados['local'] !== '' ? trim((string) $dados['local']) : null,
            'data_evento' => $dados['data_evento'],
            'hora_inicio' => $dados['hora_inicio'] . ':00',
            'hora_fim' => (!empty($dados['hora_fim'])) ? $dados['hora_fim'] . ':00' : null,
            'limite_participantes' => ($limite !== null && $limite !== '') ? (int) $limite : null,
            'status' => $dados['status'] ?? 'planejado',
        ];
    }

    /**
     * Lista eventos (qualquer usuário autenticado). Aceita filtro
     * opcional por status via query string (?status=planejado).
     */
    public function listar(int $usuarioId, ?string $status): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($status !== null && !in_array($status, self::STATUS_VALIDOS, true)) {
            $this->responder(400, [
                'success' => false,
                'message' => 'Status de filtro inválido.'
            ]);
            return;
        }

        try {
            $linhas = $this->eventoModel->listar($usuarioId, $status);
            $eventos = array_map([$this, 'formatarEvento'], $linhas);

            $this->responder(200, [
                'success' => true,
                'eventos' => $eventos
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao listar eventos.'
            ]);
        }
    }

    /**
     * Lista os próximos eventos (para o widget de agenda do feed).
     */
    public function listarProximos(int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $linhas = $this->eventoModel->listarProximos($usuarioId);

            $eventos = array_map(function ($linha) {
                return [
                    'id' => (int) $linha['id'],
                    'titulo' => $linha['titulo'],
                    'local' => $linha['local'],
                    'data_evento' => $linha['data_evento'],
                    'hora_inicio' => substr($linha['hora_inicio'], 0, 5),
                    'inscrito_por_mim' => (bool) $linha['inscrito_por_mim'],
                ];
            }, $linhas);

            $this->responder(200, [
                'success' => true,
                'eventos' => $eventos
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao listar próximos eventos.'
            ]);
        }
    }

    /**
     * Busca um evento específico.
     */
    public function buscarPorId(int $id, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $linha = $this->eventoModel->buscarPorId($id, $usuarioId);

            if (!$linha) {
                $this->responder(404, [
                    'success' => false,
                    'message' => 'Evento não encontrado.'
                ]);
                return;
            }

            $this->responder(200, [
                'success' => true,
                'evento' => $this->formatarEvento($linha)
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao buscar evento.'
            ]);
        }
    }

    /**
     * Cria um novo evento (somente administrador).
     */
    public function criar(int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $erro = $this->validarDados($dados);
        if ($erro !== null) {
            $this->responder(400, ['success' => false, 'message' => $erro]);
            return;
        }

        try {
            $dadosNormalizados = $this->normalizarDados($dados);
            $id = $this->eventoModel->criar($usuarioId, $dadosNormalizados);

            NotificacaoService::notificarNovoEvento($usuarioId, $id, $dadosNormalizados['titulo']);
            AuditoriaService::log($usuarioId, 'criacao', 'eventos', "Evento \"{$dadosNormalizados['titulo']}\" criado (#{$id}).");

            $this->responder(201, [
                'success' => true,
                'message' => 'Evento criado com sucesso.',
                'id' => $id
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao criar evento.'
            ]);
        }
    }

    /**
     * Atualiza um evento existente (somente administrador).
     */
    public function atualizar(int $id, array $dados, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Também serve de snapshot "antes" para detectar mudança
        // relevante depois (data/hora, local, cancelamento).
        $eventoAntes = $this->eventoModel->buscarPorId($id, 0);
        if (!$eventoAntes) {
            $this->responder(404, ['success' => false, 'message' => 'Evento não encontrado.']);
            return;
        }

        $erro = $this->validarDados($dados);
        if ($erro !== null) {
            $this->responder(400, ['success' => false, 'message' => $erro]);
            return;
        }

        try {
            $dadosNormalizados = $this->normalizarDados($dados);
            $this->eventoModel->atualizar($id, $dadosNormalizados);

            $this->notificarSeRelevante($id, $eventoAntes, $dadosNormalizados);
            AuditoriaService::log($usuarioId, 'atualizacao', 'eventos', "Evento \"{$dadosNormalizados['titulo']}\" atualizado (#{$id}).");

            $this->responder(200, [
                'success' => true,
                'message' => 'Evento atualizado com sucesso.'
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao atualizar evento.'
            ]);
        }
    }

    /**
     * Compara o estado do evento antes/depois de uma atualização e,
     * se algo relevante para quem já está inscrito mudou (data/hora,
     * local ou cancelamento), notifica os inscritos.
     */
    private function notificarSeRelevante(int $eventoId, array $antes, array $depois): void
    {
        $motivos = [];

        if ($antes['data_evento'] !== $depois['data_evento'] || substr($antes['hora_inicio'], 0, 5) !== substr($depois['hora_inicio'], 0, 5)) {
            $motivos[] = 'data/horário alterado';
        }
        if (($antes['local'] ?? null) !== ($depois['local'] ?? null)) {
            $motivos[] = 'local alterado';
        }
        if ($antes['status'] !== $depois['status'] && $depois['status'] === 'cancelado') {
            $motivos[] = 'evento cancelado';
        }

        if (empty($motivos)) {
            return;
        }

        NotificacaoService::notificarEventoAlterado(
            $eventoId,
            $depois['titulo'],
            implode(', ', $motivos),
            null
        );
    }

    /**
     * Exclui (soft delete) um evento (somente administrador).
     */
    public function excluir(int $id, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $excluiu = $this->eventoModel->excluir($id);

            if (!$excluiu) {
                $this->responder(404, ['success' => false, 'message' => 'Evento não encontrado.']);
                return;
            }

            AuditoriaService::log($usuarioId, 'exclusao', 'eventos', "Evento #{$id} excluído.");

            $this->responder(200, [
                'success' => true,
                'message' => 'Evento excluído com sucesso.'
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao excluir evento.'
            ]);
        }
    }

    /**
     * Inscreve o usuário autenticado num evento (qualquer usuário
     * autenticado pode participar).
     */
    public function participar(int $eventoId, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $evento = $this->eventoModel->buscarPorId($eventoId, $usuarioId);

            if (!$evento) {
                $this->responder(404, ['success' => false, 'message' => 'Evento não encontrado.']);
                return;
            }

            if (in_array($evento['status'], ['realizado', 'cancelado'], true)) {
                $this->responder(400, [
                    'success' => false,
                    'message' => 'Não é possível se inscrever em um evento já realizado ou cancelado.'
                ]);
                return;
            }

            $inscricaoExistente = $this->eventoModel->buscarInscricao($eventoId, $usuarioId);
            if ($inscricaoExistente && $inscricaoExistente['status_inscricao'] === 'inscrito') {
                $this->responder(409, [
                    'success' => false,
                    'message' => 'Você já está inscrito neste evento.'
                ]);
                return;
            }

            if ($evento['limite_participantes'] !== null) {
                $totalInscritos = $this->eventoModel->contarInscritos($eventoId);
                if ($totalInscritos >= $evento['limite_participantes']) {
                    $this->responder(409, [
                        'success' => false,
                        'message' => 'Este evento já atingiu o limite de participantes.'
                    ]);
                    return;
                }
            }

            $this->eventoModel->inscrever($eventoId, $usuarioId);

            NotificacaoService::notificarParticipacaoEvento(
                (int) $evento['criado_por'],
                $usuarioId,
                $eventoId,
                $evento['titulo']
            );
            AuditoriaService::log($usuarioId, 'participacao', 'eventos', "Inscrição no evento \"{$evento['titulo']}\" (#{$eventoId}).");

            $this->responder(200, [
                'success' => true,
                'message' => 'Inscrição realizada com sucesso.'
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao registrar participação.'
            ]);
        }
    }

    /**
     * Cancela a participação do usuário autenticado num evento.
     */
    public function cancelarParticipacao(int $eventoId, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $cancelou = $this->eventoModel->cancelarParticipacao($eventoId, $usuarioId);

            if (!$cancelou) {
                $this->responder(409, [
                    'success' => false,
                    'message' => 'Você não está inscrito neste evento.'
                ]);
                return;
            }

            AuditoriaService::log($usuarioId, 'cancelamento_participacao', 'eventos', "Participação cancelada no evento #{$eventoId}.");

            $this->responder(200, [
                'success' => true,
                'message' => 'Participação cancelada com sucesso.'
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao cancelar participação.'
            ]);
        }
    }

    /**
     * Lista os participantes de um evento (somente administrador).
     */
    public function listarParticipantes(int $eventoId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!$this->eventoModel->existe($eventoId)) {
                $this->responder(404, ['success' => false, 'message' => 'Evento não encontrado.']);
                return;
            }

            $linhas = $this->eventoModel->listarParticipantes($eventoId);

            $participantes = array_map(function ($linha) {
                return [
                    'participacao_id' => (int) $linha['participacao_id'],
                    'usuario_id' => (int) $linha['usuario_id'],
                    'nome_completo' => $linha['nome_completo'],
                    'rm' => $linha['rm'],
                    'email' => $linha['email'],
                    'status_inscricao' => $linha['status_inscricao'],
                    'inscrito_em' => $linha['inscrito_em'],
                    'presente' => $linha['presente'] !== null ? (bool) $linha['presente'] : null,
                ];
            }, $linhas);

            $this->responder(200, [
                'success' => true,
                'participantes' => $participantes
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao listar participantes.'
            ]);
        }
    }

    /**
     * Registra/altera a presença de um participante (somente administrador).
     */
    public function registrarPresenca(int $eventoId, int $usuarioAlvoId, bool $presente, int $marcadoPor): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!$this->eventoModel->existe($eventoId)) {
                $this->responder(404, ['success' => false, 'message' => 'Evento não encontrado.']);
                return;
            }

            $inscricao = $this->eventoModel->buscarInscricao($eventoId, $usuarioAlvoId);
            if (!$inscricao) {
                $this->responder(404, [
                    'success' => false,
                    'message' => 'Este usuário não está inscrito neste evento.'
                ]);
                return;
            }

            $this->eventoModel->registrarPresenca($eventoId, $usuarioAlvoId, $presente, $marcadoPor);

            $this->responder(200, [
                'success' => true,
                'message' => 'Presença registrada com sucesso.'
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao registrar presença.'
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
