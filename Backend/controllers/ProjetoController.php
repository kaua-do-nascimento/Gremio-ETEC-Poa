<?php

require_once __DIR__ . '/../models/Projeto.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../services/NotificacaoService.php';
require_once __DIR__ . '/../services/AuditoriaService.php';

class ProjetoController
{
    private Projeto $projetoModel;
    private Usuario $usuarioModel;

    private const STATUS_PROJETO_VALIDOS = ['planejado', 'em_andamento', 'concluido', 'cancelado'];
    private const PAPEIS_MEMBRO_VALIDOS = ['coordenador', 'membro'];

    public function __construct()
    {
        $this->projetoModel = new Projeto();
        $this->usuarioModel = new Usuario();
    }

    private function formatarProjeto(array $linha): array
    {
        return [
            'id' => (int) $linha['id'],
            'nome' => $linha['nome'],
            'descricao' => $linha['descricao'],
            'data_inicio' => $linha['data_inicio'],
            'data_fim_prevista' => $linha['data_fim_prevista'],
            'data_fim_real' => $linha['data_fim_real'],
            'status' => $linha['status'],
            'responsavel_id' => (int) $linha['responsavel_id'],
            'responsavel_nome' => $linha['responsavel_nome'],
            'total_membros' => (int) $linha['total_membros'],
            'total_tarefas' => (int) $linha['total_tarefas'],
            'total_tarefas_concluidas' => (int) $linha['total_tarefas_concluidas'],
            'created_at' => $linha['created_at'],
            'updated_at' => $linha['updated_at'],
        ];
    }

    private function formatarTarefa(array $linha): array
    {
        return [
            'id' => (int) $linha['id'],
            'projeto_id' => (int) $linha['projeto_id'],
            'status_tarefa_id' => (int) $linha['status_tarefa_id'],
            'status_nome' => $linha['status_nome'],
            'responsavel_id' => $linha['responsavel_id'] !== null ? (int) $linha['responsavel_id'] : null,
            'responsavel_nome' => $linha['responsavel_nome'],
            'titulo' => $linha['titulo'],
            'descricao' => $linha['descricao'],
            'data_inicio' => $linha['data_inicio'],
            'data_prazo' => $linha['data_prazo'],
            'data_conclusao' => $linha['data_conclusao'],
            'created_at' => $linha['created_at'],
            'updated_at' => $linha['updated_at'],
        ];
    }

    private function dataValida($valor): bool
    {
        if ($valor === null || $valor === '') {
            return true;
        }
        $partes = DateTime::createFromFormat('Y-m-d', (string) $valor);
        return $partes && $partes->format('Y-m-d') === $valor;
    }

    private function validarDadosProjeto(array $dados): ?string
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        if ($nome === '' || mb_strlen($nome) > 150) {
            return 'Nome do projeto é obrigatório e deve ter no máximo 150 caracteres.';
        }

        if (!$this->dataValida($dados['data_inicio'] ?? null)) {
            return 'Data de início inválida. Use o formato AAAA-MM-DD.';
        }
        if (!$this->dataValida($dados['data_fim_prevista'] ?? null)) {
            return 'Data de fim prevista inválida. Use o formato AAAA-MM-DD.';
        }
        if (!$this->dataValida($dados['data_fim_real'] ?? null)) {
            return 'Data de fim real inválida. Use o formato AAAA-MM-DD.';
        }

        $status = (string) ($dados['status'] ?? 'planejado');
        if (!in_array($status, self::STATUS_PROJETO_VALIDOS, true)) {
            return 'Status do projeto inválido.';
        }

        $responsavelId = $dados['responsavel_id'] ?? null;
        if (!is_numeric($responsavelId) || (int) $responsavelId < 1) {
            return 'Responsável pelo projeto é obrigatório.';
        }

        if (!$this->usuarioModel->buscarPorId((int) $responsavelId)) {
            return 'O usuário informado como responsável não existe.';
        }

        return null;
    }

    private function normalizarDadosProjeto(array $dados): array
    {
        return [
            'nome' => trim((string) $dados['nome']),
            'descricao' => isset($dados['descricao']) && trim((string) $dados['descricao']) !== ''
                ? trim((string) $dados['descricao'])
                : null,
            'data_inicio' => (!empty($dados['data_inicio'])) ? $dados['data_inicio'] : null,
            'data_fim_prevista' => (!empty($dados['data_fim_prevista'])) ? $dados['data_fim_prevista'] : null,
            'data_fim_real' => (!empty($dados['data_fim_real'])) ? $dados['data_fim_real'] : null,
            'status' => $dados['status'] ?? 'planejado',
            'responsavel_id' => (int) $dados['responsavel_id'],
        ];
    }

    private function validarDadosTarefa(array $dados): ?string
    {
        $titulo = trim((string) ($dados['titulo'] ?? ''));
        if ($titulo === '' || mb_strlen($titulo) > 150) {
            return 'Título da tarefa é obrigatório e deve ter no máximo 150 caracteres.';
        }

        $statusId = $dados['status_tarefa_id'] ?? null;
        if (!is_numeric($statusId) || (int) $statusId < 1) {
            return 'Status da tarefa é obrigatório.';
        }
        if (!$this->projetoModel->statusTarefaExiste((int) $statusId)) {
            return 'Status de tarefa inválido.';
        }

        if (!empty($dados['responsavel_id'])) {
            if (!is_numeric($dados['responsavel_id'])) {
                return 'Responsável pela tarefa inválido.';
            }
            if (!$this->usuarioModel->buscarPorId((int) $dados['responsavel_id'])) {
                return 'O usuário informado como responsável pela tarefa não existe.';
            }
        }

        if (!$this->dataValida($dados['data_inicio'] ?? null)) {
            return 'Data de início da tarefa inválida. Use o formato AAAA-MM-DD.';
        }
        if (!$this->dataValida($dados['data_prazo'] ?? null)) {
            return 'Prazo da tarefa inválido. Use o formato AAAA-MM-DD.';
        }
        if (!$this->dataValida($dados['data_conclusao'] ?? null)) {
            return 'Data de conclusão da tarefa inválida. Use o formato AAAA-MM-DD.';
        }

        return null;
    }

    private function normalizarDadosTarefa(array $dados): array
    {
        return [
            'status_tarefa_id' => (int) $dados['status_tarefa_id'],
            'responsavel_id' => !empty($dados['responsavel_id']) ? (int) $dados['responsavel_id'] : null,
            'titulo' => trim((string) $dados['titulo']),
            'descricao' => isset($dados['descricao']) && trim((string) $dados['descricao']) !== ''
                ? trim((string) $dados['descricao'])
                : null,
            'data_inicio' => (!empty($dados['data_inicio'])) ? $dados['data_inicio'] : null,
            'data_prazo' => (!empty($dados['data_prazo'])) ? $dados['data_prazo'] : null,
            'data_conclusao' => (!empty($dados['data_conclusao'])) ? $dados['data_conclusao'] : null,
        ];
    }

    public function listar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $linhas = $this->projetoModel->listar();
            $this->responder(200, [
                'success' => true,
                'projetos' => array_map([$this, 'formatarProjeto'], $linhas),
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar projetos.']);
        }
    }

    public function buscarPorId(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $linha = $this->projetoModel->buscarPorId($id);
            if (!$linha) {
                $this->responder(404, ['success' => false, 'message' => 'Projeto não encontrado.']);
                return;
            }
            $this->responder(200, ['success' => true, 'projeto' => $this->formatarProjeto($linha)]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao buscar projeto.']);
        }
    }

    public function criar(int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $erro = $this->validarDadosProjeto($dados);
        if ($erro !== null) {
            $this->responder(400, ['success' => false, 'message' => $erro]);
            return;
        }

        try {
            $dadosNormalizados = $this->normalizarDadosProjeto($dados);
            $id = $this->projetoModel->criar($dadosNormalizados);

            $this->projetoModel->registrarHistorico($id, $usuarioId, 'criacao', 'Projeto criado.');

            NotificacaoService::notificarNovoProjeto($usuarioId, $id, $dadosNormalizados['nome']);

            $this->responder(201, [
                'success' => true,
                'message' => 'Projeto criado com sucesso.',
                'id' => $id,
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao criar projeto.']);
        }
    }

    public function atualizar(int $id, int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $projetoAtual = $this->projetoModel->buscarPorId($id);
        if (!$projetoAtual) {
            $this->responder(404, ['success' => false, 'message' => 'Projeto não encontrado.']);
            return;
        }

        $erro = $this->validarDadosProjeto($dados);
        if ($erro !== null) {
            $this->responder(400, ['success' => false, 'message' => $erro]);
            return;
        }

        try {
            $dadosNormalizados = $this->normalizarDadosProjeto($dados);
            $this->projetoModel->atualizar($id, $dadosNormalizados);

            if ($projetoAtual['status'] !== $dadosNormalizados['status']) {
                $this->projetoModel->registrarHistorico(
                    $id,
                    $usuarioId,
                    'alteracao_status',
                    "Status alterado de '{$projetoAtual['status']}' para '{$dadosNormalizados['status']}'."
                );

                NotificacaoService::notificarProjetoAlterado(
                    $id,
                    $dadosNormalizados['nome'],
                    "status alterado para \"{$dadosNormalizados['status']}\"",
                    $usuarioId
                );
            } else {
                $this->projetoModel->registrarHistorico($id, $usuarioId, 'alteracao', 'Dados do projeto atualizados.');
            }

            $this->responder(200, ['success' => true, 'message' => 'Projeto atualizado com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao atualizar projeto.']);
        }
    }

    public function excluir(int $id, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $arquivou = $this->projetoModel->arquivar($id);
            if (!$arquivou) {
                $this->responder(404, ['success' => false, 'message' => 'Projeto não encontrado.']);
                return;
            }
            $this->projetoModel->registrarHistorico($id, $usuarioId, 'arquivamento', 'Projeto arquivado/excluído.');
            $this->responder(200, ['success' => true, 'message' => 'Projeto arquivado com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao arquivar projeto.']);
        }
    }

    public function listarMembros(int $projetoId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if (!$this->projetoModel->existe($projetoId)) {
                $this->responder(404, ['success' => false, 'message' => 'Projeto não encontrado.']);
                return;
            }

            $linhas = $this->projetoModel->listarMembros($projetoId);
            $membros = array_map(function ($linha) {
                return [
                    'membro_id' => (int) $linha['membro_id'],
                    'usuario_id' => (int) $linha['usuario_id'],
                    'nome_completo' => $linha['nome_completo'],
                    'rm' => $linha['rm'],
                    'email' => $linha['email'],
                    'foto_url' => $linha['foto_url'],
                    'papel' => $linha['papel'],
                    'adicionado_em' => $linha['adicionado_em'],
                ];
            }, $linhas);

            $this->responder(200, ['success' => true, 'membros' => $membros]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar membros do projeto.']);
        }
    }

    public function adicionarMembro(int $projetoId, int $usuarioIdAlvo, string $papel, int $adminId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->projetoModel->existe($projetoId)) {
            $this->responder(404, ['success' => false, 'message' => 'Projeto não encontrado.']);
            return;
        }

        if ($usuarioIdAlvo < 1) {
            $this->responder(400, ['success' => false, 'message' => 'Usuário inválido.']);
            return;
        }

        if (!$this->usuarioModel->buscarPorId($usuarioIdAlvo)) {
            $this->responder(404, ['success' => false, 'message' => 'Usuário não encontrado.']);
            return;
        }

        if (!in_array($papel, self::PAPEIS_MEMBRO_VALIDOS, true)) {
            $this->responder(400, ['success' => false, 'message' => 'Papel do membro inválido.']);
            return;
        }

        if ($this->projetoModel->membroExiste($projetoId, $usuarioIdAlvo)) {
            $this->responder(409, ['success' => false, 'message' => 'Este usuário já é membro do projeto.']);
            return;
        }

        try {
            $this->projetoModel->adicionarMembro($projetoId, $usuarioIdAlvo, $papel);

            $projeto = $this->projetoModel->buscarPorId($projetoId);
            NotificacaoService::notificarMembroAdicionadoProjeto(
                $usuarioIdAlvo,
                $projetoId,
                $projeto['nome'] ?? 'projeto',
                null
            );
            AuditoriaService::log($adminId, 'membro_adicionado', 'projetos', "Usuário #{$usuarioIdAlvo} adicionado ao projeto #{$projetoId} como {$papel}.");

            $this->responder(201, ['success' => true, 'message' => 'Membro adicionado com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao adicionar membro.']);
        }
    }

    public function removerMembro(int $projetoId, int $usuarioIdAlvo, int $adminId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->projetoModel->existe($projetoId)) {
            $this->responder(404, ['success' => false, 'message' => 'Projeto não encontrado.']);
            return;
        }

        try {
            $removeu = $this->projetoModel->removerMembro($projetoId, $usuarioIdAlvo);
            if (!$removeu) {
                $this->responder(404, ['success' => false, 'message' => 'Este usuário não é membro do projeto.']);
                return;
            }

            AuditoriaService::log($adminId, 'membro_removido', 'projetos', "Usuário #{$usuarioIdAlvo} removido do projeto #{$projetoId}.");

            $this->responder(200, ['success' => true, 'message' => 'Membro removido com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao remover membro.']);
        }
    }

    public function listarStatusTarefa(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->responder(200, ['success' => true, 'status' => $this->projetoModel->listarStatusTarefa()]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar status de tarefa.']);
        }
    }

    public function listarTarefas(int $projetoId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if (!$this->projetoModel->existe($projetoId)) {
                $this->responder(404, ['success' => false, 'message' => 'Projeto não encontrado.']);
                return;
            }

            $linhas = $this->projetoModel->listarTarefas($projetoId);
            $this->responder(200, [
                'success' => true,
                'tarefas' => array_map([$this, 'formatarTarefa'], $linhas),
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar tarefas.']);
        }
    }

    public function criarTarefa(int $projetoId, int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->projetoModel->existe($projetoId)) {
            $this->responder(404, ['success' => false, 'message' => 'Projeto não encontrado.']);
            return;
        }

        $erro = $this->validarDadosTarefa($dados);
        if ($erro !== null) {
            $this->responder(400, ['success' => false, 'message' => $erro]);
            return;
        }

        try {
            $dadosNormalizados = $this->normalizarDadosTarefa($dados);
            $id = $this->projetoModel->criarTarefa($projetoId, $dadosNormalizados);

            $this->projetoModel->registrarHistorico(
                $projetoId,
                $usuarioId,
                'criacao_tarefa',
                "Tarefa \"{$dadosNormalizados['titulo']}\" criada."
            );

            $this->responder(201, ['success' => true, 'message' => 'Tarefa criada com sucesso.', 'id' => $id]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao criar tarefa.']);
        }
    }

    public function atualizarTarefa(int $projetoId, int $tarefaId, int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->projetoModel->existe($projetoId)) {
            $this->responder(404, ['success' => false, 'message' => 'Projeto não encontrado.']);
            return;
        }

        $tarefaAtual = $this->projetoModel->buscarTarefaDoProjeto($tarefaId, $projetoId);
        if (!$tarefaAtual) {
            $this->responder(404, ['success' => false, 'message' => 'Tarefa não encontrada neste projeto.']);
            return;
        }

        $erro = $this->validarDadosTarefa($dados);
        if ($erro !== null) {
            $this->responder(400, ['success' => false, 'message' => $erro]);
            return;
        }

        try {
            $dadosNormalizados = $this->normalizarDadosTarefa($dados);
            $this->projetoModel->atualizarTarefa($tarefaId, $dadosNormalizados);

            if ($tarefaAtual['status_tarefa_id'] !== $dadosNormalizados['status_tarefa_id']) {
                $this->projetoModel->registrarHistorico(
                    $projetoId,
                    $usuarioId,
                    'alteracao_status_tarefa',
                    "Status da tarefa \"{$dadosNormalizados['titulo']}\" alterado."
                );
            } else {
                $this->projetoModel->registrarHistorico(
                    $projetoId,
                    $usuarioId,
                    'alteracao_tarefa',
                    "Tarefa \"{$dadosNormalizados['titulo']}\" atualizada."
                );
            }

            $this->responder(200, ['success' => true, 'message' => 'Tarefa atualizada com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao atualizar tarefa.']);
        }
    }

    public function excluirTarefa(int $projetoId, int $tarefaId, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->projetoModel->existe($projetoId)) {
            $this->responder(404, ['success' => false, 'message' => 'Projeto não encontrado.']);
            return;
        }

        $tarefaAtual = $this->projetoModel->buscarTarefaDoProjeto($tarefaId, $projetoId);
        if (!$tarefaAtual) {
            $this->responder(404, ['success' => false, 'message' => 'Tarefa não encontrada neste projeto.']);
            return;
        }

        try {
            $this->projetoModel->excluirTarefa($tarefaId);
            $this->projetoModel->registrarHistorico(
                $projetoId,
                $usuarioId,
                'exclusao_tarefa',
                "Tarefa \"{$tarefaAtual['titulo']}\" excluída."
            );

            $this->responder(200, ['success' => true, 'message' => 'Tarefa excluída com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao excluir tarefa.']);
        }
    }

    public function listarHistorico(int $projetoId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if (!$this->projetoModel->existe($projetoId)) {
                $this->responder(404, ['success' => false, 'message' => 'Projeto não encontrado.']);
                return;
            }

            $linhas = $this->projetoModel->listarHistorico($projetoId);
            $historico = array_map(function ($linha) {
                return [
                    'id' => (int) $linha['id'],
                    'acao' => $linha['acao'],
                    'descricao' => $linha['descricao'],
                    'usuario_id' => $linha['usuario_id'] !== null ? (int) $linha['usuario_id'] : null,
                    'usuario_nome' => $linha['usuario_nome'],
                    'created_at' => $linha['created_at'],
                ];
            }, $linhas);

            $this->responder(200, ['success' => true, 'historico' => $historico]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar histórico do projeto.']);
        }
    }

    private function responder(int $status, array $dados): void
    {
        http_response_code($status);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
