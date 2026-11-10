<?php

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/UsuarioController.php';
require_once __DIR__ . '/../controllers/SolicitacaoController.php';
require_once __DIR__ . '/../controllers/PublicacaoController.php';
require_once __DIR__ . '/../controllers/EventoController.php';
require_once __DIR__ . '/../controllers/VotacaoController.php';
require_once __DIR__ . '/../controllers/ProjetoController.php';
require_once __DIR__ . '/../controllers/NotificacaoController.php';
require_once __DIR__ . '/../controllers/AuditoriaController.php';
require_once __DIR__ . '/../controllers/FinanceiroController.php';
require_once __DIR__ . '/../controllers/OrcamentoController.php';
require_once __DIR__ . '/../controllers/ContaController.php';
require_once __DIR__ . '/../controllers/ResumoController.php';

function handleRoute(string $method, string $uri): void
{
    // Rota de teste da API
    if ($method === 'GET' && $uri === '/api') {
        responder(200, [
            'success' => true,
            'message' => 'API do Grêmio ETEC Poá funcionando!'
        ]);

        return;
    }

    // Login
    if ($method === 'POST' && $uri === '/api/login') {
        $dados = json_decode(
            file_get_contents('php://input'),
            true
        );

        $login = trim($dados['login'] ?? '');
        $senha = $dados['senha'] ?? '';

        if ($login === '' || $senha === '') {
            responder(400, [
                'success' => false,
                'message' => 'Login e senha são obrigatórios.'
            ]);

            return;
        }

        $authController = new AuthController();
        $authController->login($login, $senha);

        return;
    }

    // Logout (requer autenticação)
    if ($method === 'POST' && $uri === '/api/logout') {
        $usuario = autenticarRequisicao();

        $authController = new AuthController();
        $authController->logout($usuario['token_hash'], $usuario['id']);

        return;
    }

    // Dados do usuário autenticado (requer autenticação) - Tela "Meu Perfil"
    if ($method === 'GET' && $uri === '/api/me') {
        $usuario = autenticarRequisicao();

        $authController = new AuthController();
        $authController->me($usuario);

        return;
    }

    // Cadastro de usuário
    if ($method === 'POST' && $uri === '/api/cadastro') {
        $dados = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($dados)) {
            responder(400, [
                'success' => false,
                'message' => 'JSON inválido.'
            ]);

            return;
        }

        $usuarioController = new UsuarioController();
        $usuarioController->cadastrar($dados);

        return;
    }

    // Lista solicitações pendentes (somente administrador) - Tela Admin
    if ($method === 'GET' && $uri === '/api/admin/solicitacoes') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $solicitacaoController = new SolicitacaoController();
        $solicitacaoController->listarPendentes();

        return;
    }

    // Busca uma solicitação específica (somente administrador)
    if (
        $method === 'GET' &&
        preg_match('#^/api/admin/solicitacoes/([0-9]+)$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $id = (int) $matches[1];

        $solicitacaoController = new SolicitacaoController();
        $solicitacaoController->buscarPorId($id);

        return;
    }

    // Aprova uma solicitação de cadastro (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/admin/solicitacoes/([0-9]+)/aprovar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];
        $observacao = isset($dados['observacao']) ? trim((string) $dados['observacao']) : null;

        $id = (int) $matches[1];

        $solicitacaoController = new SolicitacaoController();
        $solicitacaoController->aprovar($id, $usuario['id'], $observacao !== '' ? $observacao : null);

        return;
    }

    // Rejeita uma solicitação de cadastro (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/admin/solicitacoes/([0-9]+)/rejeitar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];
        $observacao = isset($dados['observacao']) ? trim((string) $dados['observacao']) : null;

        $id = (int) $matches[1];

        $solicitacaoController = new SolicitacaoController();
        $solicitacaoController->rejeitar($id, $usuario['id'], $observacao !== '' ? $observacao : null);

        return;
    }

    // Lista TODOS os usuários (somente administrador) - Tela do Dashboard
    if ($method === 'GET' && $uri === '/api/admin/usuarios') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $usuarioController = new UsuarioController();
        $usuarioController->listarTodos();

        return;
    }

    // Atualiza nome/e-mail de um usuário (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/admin/usuarios/([0-9]+)/atualizar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $id = (int) $matches[1];

        $usuarioController = new UsuarioController();
        $usuarioController->atualizarDados(
            $id,
            (string) ($dados['nome_completo'] ?? ''),
            (string) ($dados['email'] ?? ''),
            $usuario['id']
        );

        return;
    }

    // Muda o status (ativo/suspenso) de um usuário (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/admin/usuarios/([0-9]+)/status$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $id = (int) $matches[1];

        $usuarioController = new UsuarioController();
        $usuarioController->mudarStatus(
            $id,
            (string) ($dados['status'] ?? ''),
            $usuario['id']
        );

        return;
    }

    // Exclui (soft delete) um usuário (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/admin/usuarios/([0-9]+)/excluir$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $id = (int) $matches[1];

        $usuarioController = new UsuarioController();
        $usuarioController->excluir($id, $usuario['id']);

        return;
    }

    // ==============================================================
    // PUBLICAÇÕES
    // ==============================================================

    // Lista o feed de publicações (qualquer usuário autenticado)
    if ($method === 'GET' && $uri === '/api/publicacoes') {
        $usuario = autenticarRequisicao();

        $publicacaoController = new PublicacaoController();
        $publicacaoController->listar($usuario['id']);

        return;
    }

    // Lista categorias de publicação (para o seletor do formulário)
    if ($method === 'GET' && $uri === '/api/categorias-publicacao') {
        $usuario = autenticarRequisicao();

        $publicacaoController = new PublicacaoController();
        $publicacaoController->listarCategorias();

        return;
    }

    // Cria uma nova publicação (somente administrador)
    if ($method === 'POST' && $uri === '/api/publicacoes') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $publicacaoController = new PublicacaoController();
        $publicacaoController->criar($usuario['id'], $dados);

        return;
    }

    // Busca uma publicação específica (qualquer usuário autenticado)
    if (
        $method === 'GET' &&
        preg_match('#^/api/publicacoes/([0-9]+)$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();

        $publicacaoController = new PublicacaoController();
        $publicacaoController->buscarPorId((int) $matches[1], $usuario['id']);

        return;
    }

    // Atualiza uma publicação (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/publicacoes/([0-9]+)/atualizar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $publicacaoController = new PublicacaoController();
        $publicacaoController->atualizar((int) $matches[1], $dados, $usuario['id']);

        return;
    }

    // Exclui uma publicação (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/publicacoes/([0-9]+)/excluir$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $publicacaoController = new PublicacaoController();
        $publicacaoController->excluir((int) $matches[1], $usuario['id']);

        return;
    }

    // Curtir/descurtir uma publicação (qualquer usuário autenticado)
    if (
        $method === 'POST' &&
        preg_match('#^/api/publicacoes/([0-9]+)/reagir$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();

        $publicacaoController = new PublicacaoController();
        $publicacaoController->reagir((int) $matches[1], $usuario['id']);

        return;
    }

    // ==============================================================
    // EVENTOS
    // ==============================================================

    // Lista os próximos eventos (widget de agenda do feed) - qualquer autenticado
    if ($method === 'GET' && $uri === '/api/eventos/proximos') {
        $usuario = autenticarRequisicao();

        $eventoController = new EventoController();
        $eventoController->listarProximos($usuario['id']);

        return;
    }

    // Lista eventos (qualquer usuário autenticado). Filtro opcional ?status=
    if ($method === 'GET' && $uri === '/api/eventos') {
        $usuario = autenticarRequisicao();

        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;

        $eventoController = new EventoController();
        $eventoController->listar($usuario['id'], $status);

        return;
    }

    // Cria um novo evento (somente administrador)
    if ($method === 'POST' && $uri === '/api/eventos') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $eventoController = new EventoController();
        $eventoController->criar($usuario['id'], $dados);

        return;
    }

    // Busca um evento específico (qualquer usuário autenticado)
    if (
        $method === 'GET' &&
        preg_match('#^/api/eventos/([0-9]+)$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();

        $eventoController = new EventoController();
        $eventoController->buscarPorId((int) $matches[1], $usuario['id']);

        return;
    }

    // Atualiza um evento (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/eventos/([0-9]+)/atualizar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $eventoController = new EventoController();
        $eventoController->atualizar((int) $matches[1], $dados, $usuario['id']);

        return;
    }

    // Exclui um evento (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/eventos/([0-9]+)/excluir$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $eventoController = new EventoController();
        $eventoController->excluir((int) $matches[1], $usuario['id']);

        return;
    }

    // Participar de um evento (qualquer usuário autenticado)
    if (
        $method === 'POST' &&
        preg_match('#^/api/eventos/([0-9]+)/participar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();

        $eventoController = new EventoController();
        $eventoController->participar((int) $matches[1], $usuario['id']);

        return;
    }

    // Cancelar participação num evento (qualquer usuário autenticado)
    if (
        $method === 'POST' &&
        preg_match('#^/api/eventos/([0-9]+)/cancelar-participacao$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();

        $eventoController = new EventoController();
        $eventoController->cancelarParticipacao((int) $matches[1], $usuario['id']);

        return;
    }

    // Lista os participantes de um evento (somente administrador)
    if (
        $method === 'GET' &&
        preg_match('#^/api/eventos/([0-9]+)/participantes$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $eventoController = new EventoController();
        $eventoController->listarParticipantes((int) $matches[1]);

        return;
    }

    // Registra/altera a presença de um participante (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/eventos/([0-9]+)/presenca$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $eventoController = new EventoController();
        $eventoController->registrarPresenca(
            (int) $matches[1],
            (int) ($dados['usuario_id'] ?? 0),
            (bool) ($dados['presente'] ?? false),
            $usuario['id']
        );

        return;
    }

    // ==============================================================
    // VOTAÇÕES
    // ==============================================================

    // Lista votações (qualquer usuário autenticado). Filtro opcional ?status=
    if ($method === 'GET' && $uri === '/api/votacoes') {
        $usuario = autenticarRequisicao();

        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;

        $votacaoController = new VotacaoController();
        $votacaoController->listar($usuario['id'], $status);

        return;
    }

    // Cria uma nova votação (somente administrador)
    if ($method === 'POST' && $uri === '/api/votacoes') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $votacaoController = new VotacaoController();
        $votacaoController->criar($usuario['id'], $dados);

        return;
    }

    // Busca uma votação específica, com opções e resultado ao vivo
    if (
        $method === 'GET' &&
        preg_match('#^/api/votacoes/([0-9]+)$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();

        $votacaoController = new VotacaoController();
        $votacaoController->buscarPorId((int) $matches[1], $usuario['id']);

        return;
    }

    // Resultado de uma votação (opções + votos + percentual)
    if (
        $method === 'GET' &&
        preg_match('#^/api/votacoes/([0-9]+)/resultado$#', $uri, $matches)
    ) {
        autenticarRequisicao();

        $votacaoController = new VotacaoController();
        $votacaoController->resultado((int) $matches[1]);

        return;
    }

    // Atualiza uma votação (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/votacoes/([0-9]+)/atualizar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $votacaoController = new VotacaoController();
        $votacaoController->atualizar((int) $matches[1], $usuario['id'], $dados);

        return;
    }

    // Exclui uma votação (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/votacoes/([0-9]+)/excluir$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $votacaoController = new VotacaoController();
        $votacaoController->excluir((int) $matches[1], $usuario['id']);

        return;
    }

    // Vota numa opção (qualquer usuário autenticado)
    if (
        $method === 'POST' &&
        preg_match('#^/api/votacoes/([0-9]+)/votar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $votacaoController = new VotacaoController();
        $votacaoController->votar((int) $matches[1], $usuario['id'], $dados);

        return;
    }

    // ==============================================================
    // PROJETOS
    // ==============================================================

    // Lista status de tarefa disponíveis (para o seletor do formulário)
    if ($method === 'GET' && $uri === '/api/status-tarefa') {
        autenticarRequisicao();

        $projetoController = new ProjetoController();
        $projetoController->listarStatusTarefa();

        return;
    }

    // Lista projetos (qualquer usuário autenticado)
    if ($method === 'GET' && $uri === '/api/projetos') {
        autenticarRequisicao();

        $projetoController = new ProjetoController();
        $projetoController->listar();

        return;
    }

    // Cria um novo projeto (somente administrador)
    if ($method === 'POST' && $uri === '/api/projetos') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $projetoController = new ProjetoController();
        $projetoController->criar($usuario['id'], $dados);

        return;
    }

    // Busca um projeto específico (qualquer usuário autenticado)
    if (
        $method === 'GET' &&
        preg_match('#^/api/projetos/([0-9]+)$#', $uri, $matches)
    ) {
        autenticarRequisicao();

        $projetoController = new ProjetoController();
        $projetoController->buscarPorId((int) $matches[1]);

        return;
    }

    // Atualiza um projeto (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/projetos/([0-9]+)/atualizar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $projetoController = new ProjetoController();
        $projetoController->atualizar((int) $matches[1], $usuario['id'], $dados);

        return;
    }

    // Arquiva/exclui um projeto (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/projetos/([0-9]+)/excluir$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $projetoController = new ProjetoController();
        $projetoController->excluir((int) $matches[1], $usuario['id']);

        return;
    }

    // Histórico do projeto (qualquer usuário autenticado)
    if (
        $method === 'GET' &&
        preg_match('#^/api/projetos/([0-9]+)/historico$#', $uri, $matches)
    ) {
        autenticarRequisicao();

        $projetoController = new ProjetoController();
        $projetoController->listarHistorico((int) $matches[1]);

        return;
    }

    // ---------------- Membros do projeto ----------------

    // Lista os membros de um projeto (qualquer usuário autenticado)
    if (
        $method === 'GET' &&
        preg_match('#^/api/projetos/([0-9]+)/membros$#', $uri, $matches)
    ) {
        autenticarRequisicao();

        $projetoController = new ProjetoController();
        $projetoController->listarMembros((int) $matches[1]);

        return;
    }

    // Adiciona um membro ao projeto (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/projetos/([0-9]+)/membros$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $projetoController = new ProjetoController();
        $projetoController->adicionarMembro(
            (int) $matches[1],
            (int) ($dados['usuario_id'] ?? 0),
            (string) ($dados['papel'] ?? 'membro'),
            $usuario['id']
        );

        return;
    }

    // Remove um membro do projeto (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/projetos/([0-9]+)/membros/remover$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $projetoController = new ProjetoController();
        $projetoController->removerMembro(
            (int) $matches[1],
            (int) ($dados['usuario_id'] ?? 0),
            $usuario['id']
        );

        return;
    }

    // ---------------- Tarefas do projeto ----------------

    // Lista as tarefas de um projeto (qualquer usuário autenticado)
    if (
        $method === 'GET' &&
        preg_match('#^/api/projetos/([0-9]+)/tarefas$#', $uri, $matches)
    ) {
        autenticarRequisicao();

        $projetoController = new ProjetoController();
        $projetoController->listarTarefas((int) $matches[1]);

        return;
    }

    // Cria uma nova tarefa no projeto (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/projetos/([0-9]+)/tarefas$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $projetoController = new ProjetoController();
        $projetoController->criarTarefa((int) $matches[1], $usuario['id'], $dados);

        return;
    }

    // Atualiza uma tarefa do projeto (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/projetos/([0-9]+)/tarefas/([0-9]+)/atualizar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $projetoController = new ProjetoController();
        $projetoController->atualizarTarefa(
            (int) $matches[1],
            (int) $matches[2],
            $usuario['id'],
            $dados
        );

        return;
    }

    // Exclui uma tarefa do projeto (somente administrador)
    if (
        $method === 'POST' &&
        preg_match('#^/api/projetos/([0-9]+)/tarefas/([0-9]+)/excluir$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $projetoController = new ProjetoController();
        $projetoController->excluirTarefa(
            (int) $matches[1],
            (int) $matches[2],
            $usuario['id']
        );

        return;
    }

    // ================= NOTIFICAÇÕES =================
    // Dados privados do usuário: todas as rotas exigem autenticação e
    // o isolamento por usuário é reforçado dentro do próprio Model
    // (WHERE usuario_id = :usuario_logado em toda consulta).

    // Contagem de não lidas (checada antes da rota genérica /{id},
    // mas como o regex do id exige dígitos, "nao-lidas" nunca bateria
    // nela mesmo fora de ordem — mantido aqui por clareza)
    if ($method === 'GET' && $uri === '/api/notificacoes/nao-lidas') {
        $usuario = autenticarRequisicao();

        $notificacaoController = new NotificacaoController();
        $notificacaoController->contarNaoLidas($usuario['id']);

        return;
    }

    // Lista as notificações do usuário autenticado
    // Filtros opcionais via query string: ?apenas_nao_lidas=1&pagina=1&limite=30
    if ($method === 'GET' && $uri === '/api/notificacoes') {
        $usuario = autenticarRequisicao();

        $apenasNaoLidas = isset($_GET['apenas_nao_lidas']) && $_GET['apenas_nao_lidas'] === '1';
        $pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
        $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 30;

        $notificacaoController = new NotificacaoController();
        $notificacaoController->listar($usuario['id'], $apenasNaoLidas, $pagina, $limite);

        return;
    }

    // Marca todas as notificações do usuário autenticado como lidas
    if ($method === 'POST' && $uri === '/api/notificacoes/ler-todas') {
        $usuario = autenticarRequisicao();

        $notificacaoController = new NotificacaoController();
        $notificacaoController->marcarTodasComoLidas($usuario['id']);

        return;
    }

    // Marca uma notificação específica como lida
    if (
        $method === 'POST' &&
        preg_match('#^/api/notificacoes/([0-9]+)/ler$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();

        $notificacaoController = new NotificacaoController();
        $notificacaoController->marcarComoLida($usuario['id'], (int) $matches[1]);

        return;
    }

    // Busca uma notificação específica do usuário autenticado
    if (
        $method === 'GET' &&
        preg_match('#^/api/notificacoes/([0-9]+)$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();

        $notificacaoController = new NotificacaoController();
        $notificacaoController->buscarPorId($usuario['id'], (int) $matches[1]);

        return;
    }

    // ================= AUDITORIA (somente administrador) =================

    // Ações/módulos já usados nos logs (para os filtros em dropdown)
    if ($method === 'GET' && $uri === '/api/auditoria/filtros') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $auditoriaController = new AuditoriaController();
        $auditoriaController->listarFiltrosDisponiveis();

        return;
    }

    // Lista os logs de auditoria, com filtros e paginação
    // Query string opcional: ?usuario_id=&acao=&modulo=&data_inicio=&data_fim=&pagina=&limite=
    if ($method === 'GET' && $uri === '/api/auditoria') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $filtros = [
            'usuario_id' => isset($_GET['usuario_id']) ? (int) $_GET['usuario_id'] : null,
            'acao' => $_GET['acao'] ?? null,
            'modulo' => $_GET['modulo'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? null,
            'data_fim' => $_GET['data_fim'] ?? null,
        ];
        $pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
        $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 30;

        $auditoriaController = new AuditoriaController();
        $auditoriaController->listar($filtros, $pagina, $limite);

        return;
    }

    // Detalhe de um log específico
    if (
        $method === 'GET' &&
        preg_match('#^/api/auditoria/([0-9]+)$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $auditoriaController = new AuditoriaController();
        $auditoriaController->buscarPorId((int) $matches[1]);

        return;
    }

    // ================= FINANCEIRO (somente administrador) =================

    // Lista as categorias financeiras (para os seletores do formulário)
    if ($method === 'GET' && $uri === '/api/financeiro/categorias') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $financeiroController = new FinanceiroController();
        $financeiroController->listarCategorias();

        return;
    }

    // Resumo financeiro (totais de entradas/saídas/saldo). Filtro
    // opcional de período: ?data_inicio=&data_fim=
    if ($method === 'GET' && $uri === '/api/financeiro/resumo') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $filtros = [
            'data_inicio' => $_GET['data_inicio'] ?? null,
            'data_fim' => $_GET['data_fim'] ?? null,
        ];

        $financeiroController = new FinanceiroController();
        $financeiroController->resumo($filtros);

        return;
    }

    // Lista movimentações com filtros e paginação.
    // Query string opcional: ?tipo=&categoria_id=&data_inicio=&data_fim=&pagina=&limite=
    if ($method === 'GET' && $uri === '/api/financeiro') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $filtros = [
            'tipo' => $_GET['tipo'] ?? null,
            'categoria_id' => isset($_GET['categoria_id']) ? (int) $_GET['categoria_id'] : null,
            'data_inicio' => $_GET['data_inicio'] ?? null,
            'data_fim' => $_GET['data_fim'] ?? null,
        ];
        $pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
        $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 20;

        $financeiroController = new FinanceiroController();
        $financeiroController->listar($filtros, $pagina, $limite);

        return;
    }

    // Cria uma nova movimentação (receita ou despesa)
    if ($method === 'POST' && $uri === '/api/financeiro') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $financeiroController = new FinanceiroController();
        $financeiroController->criar($usuario['id'], $dados);

        return;
    }

    // Busca uma movimentação específica
    if (
        $method === 'GET' &&
        preg_match('#^/api/financeiro/([0-9]+)$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $financeiroController = new FinanceiroController();
        $financeiroController->buscarPorId((int) $matches[1]);

        return;
    }

    // Atualiza uma movimentação existente
    if (
        $method === 'POST' &&
        preg_match('#^/api/financeiro/([0-9]+)/atualizar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $financeiroController = new FinanceiroController();
        $financeiroController->atualizar((int) $matches[1], $usuario['id'], $dados);

        return;
    }

    // Arquiva (soft delete) uma movimentação
    if (
        $method === 'POST' &&
        preg_match('#^/api/financeiro/([0-9]+)/excluir$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $financeiroController = new FinanceiroController();
        $financeiroController->excluir((int) $matches[1], $usuario['id']);

        return;
    }

    // ================= ORÇAMENTOS (somente administrador) =================

    // Lista orçamentos com filtros e paginação.
    // Query string opcional: ?ano_referencia=&status=&pagina=&limite=
    if ($method === 'GET' && $uri === '/api/orcamentos') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $filtros = [
            'ano_referencia' => $_GET['ano_referencia'] ?? null,
            'status' => $_GET['status'] ?? null,
        ];
        $pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
        $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 20;

        $orcamentoController = new OrcamentoController();
        $orcamentoController->listar($filtros, $pagina, $limite);

        return;
    }

    // Cria um orçamento (valor_total nunca é lido do corpo da requisição)
    if ($method === 'POST' && $uri === '/api/orcamentos') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $orcamentoController = new OrcamentoController();
        $orcamentoController->criar($usuario['id'], $dados);

        return;
    }

    // Itens: listar
    if (
        $method === 'GET' &&
        preg_match('#^/api/orcamentos/([0-9]+)/itens$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $orcamentoController = new OrcamentoController();
        $orcamentoController->listarItens((int) $matches[1]);

        return;
    }

    // Itens: adicionar
    if (
        $method === 'POST' &&
        preg_match('#^/api/orcamentos/([0-9]+)/itens$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $orcamentoController = new OrcamentoController();
        $orcamentoController->criarItem((int) $matches[1], $usuario['id'], $dados);

        return;
    }

    // Itens: atualizar (o item precisa pertencer ao orçamento da URL)
    if (
        $method === 'POST' &&
        preg_match('#^/api/orcamentos/([0-9]+)/itens/([0-9]+)/atualizar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $orcamentoController = new OrcamentoController();
        $orcamentoController->atualizarItem((int) $matches[1], (int) $matches[2], $usuario['id'], $dados);

        return;
    }

    // Itens: remover (o item precisa pertencer ao orçamento da URL)
    if (
        $method === 'POST' &&
        preg_match('#^/api/orcamentos/([0-9]+)/itens/([0-9]+)/excluir$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $orcamentoController = new OrcamentoController();
        $orcamentoController->excluirItem((int) $matches[1], (int) $matches[2], $usuario['id']);

        return;
    }

    // Busca um orçamento (cabeçalho + totais calculados)
    if (
        $method === 'GET' &&
        preg_match('#^/api/orcamentos/([0-9]+)$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $orcamentoController = new OrcamentoController();
        $orcamentoController->buscarPorId((int) $matches[1]);

        return;
    }

    // Atualiza um orçamento
    if (
        $method === 'POST' &&
        preg_match('#^/api/orcamentos/([0-9]+)/atualizar$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $orcamentoController = new OrcamentoController();
        $orcamentoController->atualizar((int) $matches[1], $usuario['id'], $dados);

        return;
    }

    // Arquiva (soft delete) um orçamento
    if (
        $method === 'POST' &&
        preg_match('#^/api/orcamentos/([0-9]+)/excluir$#', $uri, $matches)
    ) {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $orcamentoController = new OrcamentoController();
        $orcamentoController->excluir((int) $matches[1], $usuario['id']);

        return;
    }

    // ================= CONTA DO USUÁRIO / RECUPERAÇÃO DE SENHA =================

    // Atualiza nome e e-mail do próprio usuário logado
    if ($method === 'POST' && $uri === '/api/me/perfil') {
        $usuario = autenticarRequisicao();

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $contaController = new ContaController();
        $contaController->atualizarPerfil($usuario['id'], $dados);

        return;
    }

    // Troca a senha do próprio usuário (exige a senha atual)
    if ($method === 'POST' && $uri === '/api/me/senha') {
        $usuario = autenticarRequisicao();

        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $contaController = new ContaController();
        $contaController->alterarSenha($usuario['id'], $usuario['token_hash'], $dados);

        return;
    }

    // Encerra as outras sessões abertas do usuário (outros navegadores/dispositivos)
    if ($method === 'POST' && $uri === '/api/me/sessoes/encerrar-outras') {
        $usuario = autenticarRequisicao();

        $contaController = new ContaController();
        $contaController->encerrarOutrasSessoes($usuario['id'], $usuario['token_hash']);

        return;
    }

    // "Esqueci minha senha" - pública (não exige login)
    if ($method === 'POST' && $uri === '/api/recuperar-senha') {
        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        // Endereço do front-end (pasta que contém os .php), deduzido da URL da requisição
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
        $posBackend = strpos($caminho, '/Backend/');
        $pastaFront = $posBackend !== false ? substr($caminho, 0, $posBackend) : '';
        $urlBase = $https . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $pastaFront;

        $contaController = new ContaController();
        $contaController->solicitarRecuperacao($dados, $urlBase);

        return;
    }

    // Confere se o link de redefinição ainda é válido (pública)
    if ($method === 'POST' && $uri === '/api/recuperar-senha/validar') {
        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $contaController = new ContaController();
        $contaController->validarTokenRecuperacao((string) ($dados['token'] ?? ''));

        return;
    }

    // Define a nova senha a partir do link de recuperação (pública)
    if ($method === 'POST' && $uri === '/api/redefinir-senha') {
        $dados = json_decode(file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];

        $contaController = new ContaController();
        $contaController->redefinirSenha($dados);

        return;
    }

    // ================= RESUMO DO PAINEL ADMINISTRATIVO =================

    if ($method === 'GET' && $uri === '/api/admin/resumo') {
        $usuario = autenticarRequisicao();
        exigirPerfil($usuario, ['administrador']);

        $resumoController = new ResumoController();
        $resumoController->resumo();

        return;
    }

    // Rota não encontrada
    responder(404, [
        'success' => false,
        'message' => 'Rota não encontrada.'
    ]);
}

function responder(int $status, array $dados): void
{
    enviarResposta($status, $dados);
}
