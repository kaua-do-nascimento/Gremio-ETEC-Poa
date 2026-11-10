<?php

require_once __DIR__ . '/../models/Votacao.php';
require_once __DIR__ . '/../services/NotificacaoService.php';
require_once __DIR__ . '/../services/AuditoriaService.php';

class VotacaoController
{
    private Votacao $votacaoModel;

    private const TIPOS_VALIDOS = ['enquete', 'eleicao', 'votacao_gremio'];
    private const STATUS_VALIDOS = ['agendada', 'aberta', 'encerrada', 'cancelada'];

    public function __construct()
    {
        $this->votacaoModel = new Votacao();
    }

    /**
     * Formata os campos agregados de uma linha do banco (listagem/detalhe).
     */
    private function formatarVotacao(array $linha): array
    {
        return [
            'id' => (int) $linha['id'],
            'titulo' => $linha['titulo'],
            'descricao' => $linha['descricao'],
            'tipo' => $linha['tipo'],
            'data_inicio' => $linha['data_inicio'],
            'data_fim' => $linha['data_fim'],
            'status' => $linha['status'],
            'criador_nome' => $linha['criador_nome'],
            'total_votos' => (int) $linha['total_votos'],
            'ja_votei' => (bool) $linha['ja_votei'],
            'opcao_votada_id' => isset($linha['opcao_votada_id']) && $linha['opcao_votada_id'] !== null
                ? (int) $linha['opcao_votada_id']
                : null,
        ];
    }

    /**
     * Valida os campos de entrada para criação/edição de votação.
     * Retorna uma mensagem de erro (string) ou null se estiver tudo ok.
     */
    private function validarDados(array $dados): ?string
    {
        $titulo = trim((string) ($dados['titulo'] ?? ''));
        if ($titulo === '' || mb_strlen($titulo) > 150) {
            return 'Título é obrigatório e deve ter no máximo 150 caracteres.';
        }

        $tipo = (string) ($dados['tipo'] ?? '');
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            return 'Tipo inválido. Use enquete, eleicao ou votacao_gremio.';
        }

        $dataInicio = (string) ($dados['data_inicio'] ?? '');
        $dataFim = (string) ($dados['data_fim'] ?? '');

        $inicio = DateTime::createFromFormat('Y-m-d\TH:i', $dataInicio) ?: DateTime::createFromFormat('Y-m-d H:i:s', $dataInicio);
        $fim = DateTime::createFromFormat('Y-m-d\TH:i', $dataFim) ?: DateTime::createFromFormat('Y-m-d H:i:s', $dataFim);

        if (!$inicio) {
            return 'Data/hora de início inválida.';
        }
        if (!$fim) {
            return 'Data/hora de término inválida.';
        }
        if ($fim <= $inicio) {
            return 'A data de término deve ser depois da data de início.';
        }

        $status = (string) ($dados['status'] ?? 'agendada');
        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            return 'Status inválido.';
        }

        return null;
    }

    /**
     * Valida a lista de opções recebida (mínimo 2, textos não vazios).
     * Retorna a lista normalizada, ou null se inválida (mensagem em $erro).
     */
    private function validarOpcoes($opcoesEntrada, ?string &$erro): ?array
    {
        if (!is_array($opcoesEntrada)) {
            $erro = 'É necessário informar as opções de voto.';
            return null;
        }

        $opcoes = [];
        foreach ($opcoesEntrada as $texto) {
            $textoLimpo = trim((string) $texto);
            if ($textoLimpo !== '') {
                $opcoes[] = $textoLimpo;
            }
        }

        if (count($opcoes) < 2) {
            $erro = 'Informe pelo menos 2 opções de voto.';
            return null;
        }

        foreach ($opcoes as $texto) {
            if (mb_strlen($texto) > 150) {
                $erro = 'Cada opção deve ter no máximo 150 caracteres.';
                return null;
            }
        }

        return $opcoes;
    }

    private function normalizarDados(array $dados): array
    {
        $dataInicio = str_replace('T', ' ', (string) $dados['data_inicio']);
        $dataFim = str_replace('T', ' ', (string) $dados['data_fim']);

        return [
            'titulo' => trim((string) $dados['titulo']),
            'descricao' => isset($dados['descricao']) && $dados['descricao'] !== '' ? trim((string) $dados['descricao']) : null,
            'tipo' => $dados['tipo'],
            'data_inicio' => strlen($dataInicio) === 16 ? $dataInicio . ':00' : $dataInicio,
            'data_fim' => strlen($dataFim) === 16 ? $dataFim . ':00' : $dataFim,
            'status' => $dados['status'] ?? 'agendada',
        ];
    }

    /**
     * Lista votações (qualquer usuário autenticado).
     */
    public function listar(int $usuarioId, ?string $status): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($status !== null && !in_array($status, self::STATUS_VALIDOS, true)) {
            $this->responder(400, ['success' => false, 'message' => 'Status de filtro inválido.']);
            return;
        }

        try {
            $linhas = $this->votacaoModel->listar($usuarioId, $status);
            $votacoes = array_map([$this, 'formatarVotacao'], $linhas);

            $this->responder(200, ['success' => true, 'votacoes' => $votacoes]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar votações.']);
        }
    }

    /**
     * Busca uma votação específica, já com as opções e resultado ao vivo.
     */
    public function buscarPorId(int $id, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $linha = $this->votacaoModel->buscarPorId($id, $usuarioId);

            if (!$linha) {
                $this->responder(404, ['success' => false, 'message' => 'Votação não encontrada.']);
                return;
            }

            $votacao = $this->formatarVotacao($linha);
            $votacao['opcoes'] = $this->votacaoModel->listarOpcoesComResultado($id);

            $this->responder(200, ['success' => true, 'votacao' => $votacao]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao buscar votação.']);
        }
    }

    /**
     * Retorna somente o resultado (opções + votos + percentual) de
     * uma votação.
     */
    public function resultado(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!$this->votacaoModel->existe($id)) {
                $this->responder(404, ['success' => false, 'message' => 'Votação não encontrada.']);
                return;
            }

            $opcoes = $this->votacaoModel->listarOpcoesComResultado($id);
            $totalGeral = array_sum(array_column($opcoes, 'total_votos'));

            $this->responder(200, [
                'success' => true,
                'total_votos' => $totalGeral,
                'opcoes' => $opcoes
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao calcular resultado.']);
        }
    }

    /**
     * Cria uma nova votação com suas opções (somente administrador).
     */
    public function criar(int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $erro = $this->validarDados($dados);
        if ($erro !== null) {
            $this->responder(400, ['success' => false, 'message' => $erro]);
            return;
        }

        $erroOpcoes = null;
        $opcoes = $this->validarOpcoes($dados['opcoes'] ?? null, $erroOpcoes);
        if ($opcoes === null) {
            $this->responder(400, ['success' => false, 'message' => $erroOpcoes]);
            return;
        }

        try {
            $dadosNormalizados = $this->normalizarDados($dados);
            $id = $this->votacaoModel->criar($usuarioId, $dadosNormalizados, $opcoes);

            NotificacaoService::notificarNovaVotacao($usuarioId, $id, $dadosNormalizados['titulo']);
            AuditoriaService::log($usuarioId, 'criacao', 'votacoes', "Votação \"{$dadosNormalizados['titulo']}\" criada (#{$id}).");

            $this->responder(201, [
                'success' => true,
                'message' => 'Votação criada com sucesso.',
                'id' => $id
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao criar votação.']);
        }
    }

    /**
     * Atualiza uma votação existente (somente administrador). As
     * opções só podem ser alteradas enquanto a votação ainda estiver
     * 'agendada' no banco (checagem feita ANTES da atualização, usando
     * o status atual salvo — não o status novo enviado na requisição),
     * para nunca apagar votos reais por engano.
     */
    public function atualizar(int $id, int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $votacaoAtual = $this->votacaoModel->buscarPorId($id, $usuarioId);

            if (!$votacaoAtual) {
                $this->responder(404, ['success' => false, 'message' => 'Votação não encontrada.']);
                return;
            }

            $erro = $this->validarDados($dados);
            if ($erro !== null) {
                $this->responder(400, ['success' => false, 'message' => $erro]);
                return;
            }

            $opcoesParaAtualizar = null;

            if (array_key_exists('opcoes', $dados)) {
                if ($votacaoAtual['status'] !== 'agendada') {
                    $this->responder(400, [
                        'success' => false,
                        'message' => 'As opções só podem ser alteradas enquanto a votação ainda não foi aberta.'
                    ]);
                    return;
                }

                $erroOpcoes = null;
                $opcoesParaAtualizar = $this->validarOpcoes($dados['opcoes'], $erroOpcoes);
                if ($opcoesParaAtualizar === null) {
                    $this->responder(400, ['success' => false, 'message' => $erroOpcoes]);
                    return;
                }
            }

            $dadosNormalizados = $this->normalizarDados($dados);
            $this->votacaoModel->atualizar($id, $dadosNormalizados, $opcoesParaAtualizar);

            if ($votacaoAtual['status'] !== 'encerrada' && $dadosNormalizados['status'] === 'encerrada') {
                NotificacaoService::notificarVotacaoEncerrada($id, $dadosNormalizados['titulo'], $usuarioId);
                AuditoriaService::log($usuarioId, 'encerramento', 'votacoes', "Votação \"{$dadosNormalizados['titulo']}\" encerrada (#{$id}).");
            } else {
                AuditoriaService::log($usuarioId, 'atualizacao', 'votacoes', "Votação \"{$dadosNormalizados['titulo']}\" atualizada (#{$id}).");
            }

            $this->responder(200, ['success' => true, 'message' => 'Votação atualizada com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao atualizar votação.']);
        }
    }

    /**
     * Exclui (soft delete) uma votação (somente administrador).
     */
    public function excluir(int $id, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $excluiu = $this->votacaoModel->excluir($id);

            if (!$excluiu) {
                $this->responder(404, ['success' => false, 'message' => 'Votação não encontrada.']);
                return;
            }

            AuditoriaService::log($usuarioId, 'exclusao', 'votacoes', "Votação #{$id} excluída.");

            $this->responder(200, ['success' => true, 'message' => 'Votação excluída com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao excluir votação.']);
        }
    }

    /**
     * Registra o voto do usuário autenticado numa opção de uma votação.
     */
    public function votar(int $votacaoId, int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $opcaoId = (int) ($dados['opcao_id'] ?? 0);
        if ($opcaoId <= 0) {
            $this->responder(400, ['success' => false, 'message' => 'Informe a opção escolhida.']);
            return;
        }

        try {
            $votacao = $this->votacaoModel->buscarPorId($votacaoId, $usuarioId);

            if (!$votacao) {
                $this->responder(404, ['success' => false, 'message' => 'Votação não encontrada.']);
                return;
            }

            if ($votacao['status'] !== 'aberta') {
                $this->responder(400, [
                    'success' => false,
                    'message' => 'Esta votação não está aberta no momento.'
                ]);
                return;
            }

            $agora = new DateTime();
            $inicio = new DateTime($votacao['data_inicio']);
            $fim = new DateTime($votacao['data_fim']);

            if ($agora < $inicio) {
                $this->responder(400, ['success' => false, 'message' => 'Esta votação ainda não começou.']);
                return;
            }
            if ($agora > $fim) {
                $this->responder(400, ['success' => false, 'message' => 'Esta votação já foi encerrada.']);
                return;
            }

            $opcao = $this->votacaoModel->buscarOpcao($opcaoId);
            if (!$opcao) {
                $this->responder(404, ['success' => false, 'message' => 'Opção não encontrada.']);
                return;
            }
            if ((int) $opcao['votacao_id'] !== $votacaoId) {
                $this->responder(400, [
                    'success' => false,
                    'message' => 'Esta opção não pertence a esta votação.'
                ]);
                return;
            }

            if ($this->votacaoModel->jaVotou($votacaoId, $usuarioId)) {
                $this->responder(409, ['success' => false, 'message' => 'Você já votou nesta votação.']);
                return;
            }

            try {
                $this->votacaoModel->registrarVoto($votacaoId, $opcaoId, $usuarioId);

                // IMPORTANTE (sigilo do voto): registramos apenas o FATO de que
                // um voto foi realizado nesta votação — nunca a opção escolhida.
                AuditoriaService::log($usuarioId, 'voto', 'votacoes', "Voto registrado na votação #{$votacaoId}.");
            } catch (PDOException $e) {
                // SQLSTATE 23000 = violação de UNIQUE KEY (condição de corrida:
                // dois votos quase simultâneos do mesmo usuário).
                if ($e->getCode() === '23000') {
                    $this->responder(409, ['success' => false, 'message' => 'Você já votou nesta votação.']);
                    return;
                }
                throw $e;
            }

            $this->responder(200, ['success' => true, 'message' => 'Voto registrado com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao registrar voto.']);
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
