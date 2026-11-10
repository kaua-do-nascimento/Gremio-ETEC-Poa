// =====================================================================
// api.js — Helper central de comunicação com o backend PHP.
//
// Todas as páginas (login, cadastro, index, configurações) usam este
// arquivo para não duplicar a lógica de fetch/token em cada uma.
// =====================================================================

// Endereço onde o backend PHP está rodando.
//
// Este projeto roda 100% dentro do WampServer/Apache: front-end e
// backend ficam sob o mesmo domínio e porta (ex.: http://localhost/
// Gremio-ETEC-Poa/...). Por isso a URL da API é calculada a partir
// da URL da própria página, não importando o nome da pasta do
// projeto nem a porta que o Apache estiver usando.
//
// A regra é: a pasta "Backend/public" deve estar sempre um nível
// dentro da pasta onde ficam os arquivos .php (login.php, index.php
// etc.) — ou seja, a estrutura de pastas do projeto não muda, só o
// "endereço base" onde ela está hospedada.
const API_BASE_URL = (function () {
  const pastaAtual = window.location.pathname.replace(/[^/]*$/, "");
  return `${window.location.origin}${pastaAtual}Backend/public/api`;
})();

const AUTH_STORAGE_KEY = "gremio_auth";

/**
 * Salva o token e os dados do usuário logado no localStorage.
 * localStorage é usado (não sessionStorage) para a sessão sobreviver
 * caso o aluno feche e reabra a aba durante a demonstração.
 */
function salvarSessao(token, usuario, perfis) {
  localStorage.setItem(
    AUTH_STORAGE_KEY,
    JSON.stringify({ token, usuario, perfis })
  );
}

/**
 * Recupera a sessão salva (token + usuário), ou null se não houver.
 */
function obterSessao() {
  const bruto = localStorage.getItem(AUTH_STORAGE_KEY);
  if (!bruto) return null;

  try {
    return JSON.parse(bruto);
  } catch (e) {
    localStorage.removeItem(AUTH_STORAGE_KEY);
    return null;
  }
}

/**
 * Remove a sessão salva (logout local).
 */
function limparSessao() {
  localStorage.removeItem(AUTH_STORAGE_KEY);
}

/**
 * Faz uma requisição para a API, incluindo automaticamente o token
 * de autenticação (se existir) no cabeçalho Authorization.
 *
 * @param {string} caminho Ex.: "/login", "/me", "/admin/solicitacoes"
 * @param {object} opcoes  Mesmas opções aceitas pelo fetch() nativo
 */
async function apiFetch(caminho, opcoes = {}) {
  const sessao = obterSessao();

  const headers = {
    "Content-Type": "application/json",
    ...(opcoes.headers || {}),
  };

  if (sessao && sessao.token) {
    headers["Authorization"] = `Bearer ${sessao.token}`;
  }

  const resposta = await fetch(`${API_BASE_URL}${caminho}`, {
    ...opcoes,
    headers,
  });

  let dados;
  try {
    dados = await resposta.json();
  } catch (e) {
    dados = { success: false, message: "Resposta inválida do servidor." };
  }

  // Sessão expirada/encerrada em outro lugar: volta para o login
  // (exceto nas rotas públicas, onde 401 significa credenciais erradas).
  const rotasPublicas = ["/login", "/cadastro", "/recuperar-senha", "/redefinir-senha"];
  const ehPublica = rotasPublicas.some((r) => caminho.startsWith(r));
  if (resposta.status === 401 && !ehPublica && sessao) {
    limparSessao();
    if (!/login\.php$/.test(window.location.pathname)) {
      window.location.href = "login.php";
    }
  }

  return { status: resposta.status, ok: resposta.ok, dados };
}

/**
 * Realiza login. Em caso de sucesso, já salva a sessão automaticamente.
 */
async function apiLogin(login, senha) {
  const { ok, dados } = await apiFetch("/login", {
    method: "POST",
    body: JSON.stringify({ login, senha }),
  });

  if (ok && dados.success) {
    salvarSessao(dados.token, dados.usuario, dados.perfis);
  }

  return dados;
}

/**
 * Realiza o cadastro de um novo usuário (fica pendente de aprovação).
 */
async function apiCadastro(dadosCadastro) {
  const { dados } = await apiFetch("/cadastro", {
    method: "POST",
    body: JSON.stringify(dadosCadastro),
  });

  return dados;
}

/**
 * Busca os dados do usuário autenticado (GET /api/me).
 */
async function apiMe() {
  const { ok, dados } = await apiFetch("/me");
  return { ok, dados };
}

/**
 * Realiza logout: avisa o backend (encerra a sessão no banco) e limpa
 * os dados salvos localmente, independentemente da resposta do servidor.
 */
async function apiLogout() {
  try {
    await apiFetch("/logout", { method: "POST" });
  } catch (e) {
    // Mesmo se a chamada falhar (ex.: backend fora do ar), seguimos
    // limpando a sessão local para não deixar o usuário travado.
  } finally {
    limparSessao();
  }
}

/**
 * Lista todos os usuários (painel administrativo).
 */
async function apiListarUsuarios() {
  const { ok, dados } = await apiFetch("/admin/usuarios");
  return { ok, dados };
}

/**
 * Atualiza nome/e-mail de um usuário (painel administrativo).
 */
async function apiAtualizarUsuario(id, nomeCompleto, email) {
  const { dados } = await apiFetch(`/admin/usuarios/${id}/atualizar`, {
    method: "POST",
    body: JSON.stringify({ nome_completo: nomeCompleto, email }),
  });
  return dados;
}

/**
 * Muda o status (ativo/suspenso) de um usuário (painel administrativo).
 */
async function apiMudarStatusUsuario(id, status) {
  const { dados } = await apiFetch(`/admin/usuarios/${id}/status`, {
    method: "POST",
    body: JSON.stringify({ status }),
  });
  return dados;
}

/**
 * Exclui um usuário (painel administrativo).
 */
async function apiExcluirUsuario(id) {
  const { dados } = await apiFetch(`/admin/usuarios/${id}/excluir`, {
    method: "POST",
  });
  return dados;
}

/**
 * Protege uma página: se não houver sessão salva, redireciona para o
 * login. Deve ser chamada no topo do <script> de páginas privadas
 * (ex.: index.html, configuracoes.html).
 */
/**
 * Lista o feed de publicações.
 */
async function apiListarPublicacoes() {
  const { ok, dados } = await apiFetch("/publicacoes");
  return { ok, dados };
}

/**
 * Lista as categorias de publicação (para o seletor do formulário).
 */
async function apiListarCategoriasPublicacao() {
  const { ok, dados } = await apiFetch("/categorias-publicacao");
  return { ok, dados };
}

/**
 * Cria uma nova publicação (somente administrador).
 */
async function apiCriarPublicacao(titulo, conteudo, categoriaId) {
  const { dados } = await apiFetch("/publicacoes", {
    method: "POST",
    body: JSON.stringify({ titulo, conteudo, categoria_id: categoriaId }),
  });
  return dados;
}

/**
 * Alterna a curtida do usuário autenticado numa publicação.
 */
async function apiReagirPublicacao(id) {
  const { dados } = await apiFetch(`/publicacoes/${id}/reagir`, {
    method: "POST",
  });
  return dados;
}

/**
 * Exclui uma publicação (somente administrador).
 */
async function apiExcluirPublicacao(id) {
  const { dados } = await apiFetch(`/publicacoes/${id}/excluir`, {
    method: "POST",
  });
  return dados;
}

/**
 * Lista eventos (opcionalmente filtrados por status).
 */
async function apiListarEventos(status = null) {
  const caminho = status ? `/eventos?status=${encodeURIComponent(status)}` : "/eventos";
  const { ok, dados } = await apiFetch(caminho);
  return { ok, dados };
}

/**
 * Lista os próximos eventos (widget de agenda do feed).
 */
async function apiListarEventosProximos() {
  const { ok, dados } = await apiFetch("/eventos/proximos");
  return { ok, dados };
}

/**
 * Busca um evento específico.
 */
async function apiBuscarEvento(id) {
  const { ok, dados } = await apiFetch(`/eventos/${id}`);
  return { ok, dados };
}

/**
 * Cria um novo evento (somente administrador).
 */
async function apiCriarEvento(dadosEvento) {
  const { dados } = await apiFetch("/eventos", {
    method: "POST",
    body: JSON.stringify(dadosEvento),
  });
  return dados;
}

/**
 * Atualiza um evento existente (somente administrador).
 */
async function apiAtualizarEvento(id, dadosEvento) {
  const { dados } = await apiFetch(`/eventos/${id}/atualizar`, {
    method: "POST",
    body: JSON.stringify(dadosEvento),
  });
  return dados;
}

/**
 * Exclui um evento (somente administrador).
 */
async function apiExcluirEvento(id) {
  const { dados } = await apiFetch(`/eventos/${id}/excluir`, {
    method: "POST",
  });
  return dados;
}

/**
 * Participa de um evento (qualquer usuário autenticado).
 */
async function apiParticiparEvento(id) {
  const { dados } = await apiFetch(`/eventos/${id}/participar`, {
    method: "POST",
  });
  return dados;
}

/**
 * Cancela a participação num evento.
 */
async function apiCancelarParticipacaoEvento(id) {
  const { dados } = await apiFetch(`/eventos/${id}/cancelar-participacao`, {
    method: "POST",
  });
  return dados;
}

/**
 * Lista os participantes de um evento (somente administrador).
 */
async function apiListarParticipantesEvento(id) {
  const { ok, dados } = await apiFetch(`/eventos/${id}/participantes`);
  return { ok, dados };
}

/**
 * Registra/altera a presença de um participante (somente administrador).
 */
async function apiRegistrarPresenca(eventoId, usuarioId, presente) {
  const { dados } = await apiFetch(`/eventos/${eventoId}/presenca`, {
    method: "POST",
    body: JSON.stringify({ usuario_id: usuarioId, presente }),
  });
  return dados;
}

/**
 * Lista votações (opcionalmente filtradas por status).
 */
async function apiListarVotacoes(status = null) {
  const caminho = status ? `/votacoes?status=${encodeURIComponent(status)}` : "/votacoes";
  const { ok, dados } = await apiFetch(caminho);
  return { ok, dados };
}

/**
 * Busca uma votação específica (com opções e resultado ao vivo).
 */
async function apiBuscarVotacao(id) {
  const { ok, dados } = await apiFetch(`/votacoes/${id}`);
  return { ok, dados };
}

/**
 * Busca somente o resultado de uma votação.
 */
async function apiResultadoVotacao(id) {
  const { ok, dados } = await apiFetch(`/votacoes/${id}/resultado`);
  return { ok, dados };
}

/**
 * Cria uma nova votação (somente administrador).
 */
async function apiCriarVotacao(dadosVotacao) {
  const { dados } = await apiFetch("/votacoes", {
    method: "POST",
    body: JSON.stringify(dadosVotacao),
  });
  return dados;
}

/**
 * Atualiza uma votação existente (somente administrador).
 */
async function apiAtualizarVotacao(id, dadosVotacao) {
  const { dados } = await apiFetch(`/votacoes/${id}/atualizar`, {
    method: "POST",
    body: JSON.stringify(dadosVotacao),
  });
  return dados;
}

/**
 * Exclui uma votação (somente administrador).
 */
async function apiExcluirVotacao(id) {
  const { dados } = await apiFetch(`/votacoes/${id}/excluir`, {
    method: "POST",
  });
  return dados;
}

/**
 * Vota numa opção de uma votação.
 */
async function apiVotar(votacaoId, opcaoId) {
  const { dados } = await apiFetch(`/votacoes/${votacaoId}/votar`, {
    method: "POST",
    body: JSON.stringify({ opcao_id: opcaoId }),
  });
  return dados;
}

// ============================================================
// PROJETOS
// ============================================================

/**
 * Lista os status de tarefa disponíveis (para os seletores dos formulários).
 */
async function apiListarStatusTarefa() {
  const { ok, dados } = await apiFetch("/status-tarefa");
  return { ok, dados };
}

/**
 * Lista todos os projetos (qualquer usuário autenticado).
 */
async function apiListarProjetos() {
  const { ok, dados } = await apiFetch("/projetos");
  return { ok, dados };
}

/**
 * Busca um projeto específico.
 */
async function apiBuscarProjeto(id) {
  const { ok, dados } = await apiFetch(`/projetos/${id}`);
  return { ok, dados };
}

/**
 * Cria um novo projeto (somente administrador).
 */
async function apiCriarProjeto(dadosProjeto) {
  const { dados } = await apiFetch("/projetos", {
    method: "POST",
    body: JSON.stringify(dadosProjeto),
  });
  return dados;
}

/**
 * Atualiza um projeto existente (somente administrador).
 */
async function apiAtualizarProjeto(id, dadosProjeto) {
  const { dados } = await apiFetch(`/projetos/${id}/atualizar`, {
    method: "POST",
    body: JSON.stringify(dadosProjeto),
  });
  return dados;
}

/**
 * Arquiva/exclui um projeto (somente administrador).
 */
async function apiExcluirProjeto(id) {
  const { dados } = await apiFetch(`/projetos/${id}/excluir`, {
    method: "POST",
  });
  return dados;
}

/**
 * Lista o histórico de um projeto.
 */
async function apiListarHistoricoProjeto(id) {
  const { ok, dados } = await apiFetch(`/projetos/${id}/historico`);
  return { ok, dados };
}

/**
 * Lista os membros de um projeto.
 */
async function apiListarMembrosProjeto(projetoId) {
  const { ok, dados } = await apiFetch(`/projetos/${projetoId}/membros`);
  return { ok, dados };
}

/**
 * Adiciona um membro ao projeto (somente administrador).
 */
async function apiAdicionarMembroProjeto(projetoId, usuarioId, papel) {
  const { dados } = await apiFetch(`/projetos/${projetoId}/membros`, {
    method: "POST",
    body: JSON.stringify({ usuario_id: usuarioId, papel }),
  });
  return dados;
}

/**
 * Remove um membro do projeto (somente administrador).
 */
async function apiRemoverMembroProjeto(projetoId, usuarioId) {
  const { dados } = await apiFetch(`/projetos/${projetoId}/membros/remover`, {
    method: "POST",
    body: JSON.stringify({ usuario_id: usuarioId }),
  });
  return dados;
}

/**
 * Lista as tarefas de um projeto.
 */
async function apiListarTarefasProjeto(projetoId) {
  const { ok, dados } = await apiFetch(`/projetos/${projetoId}/tarefas`);
  return { ok, dados };
}

/**
 * Cria uma nova tarefa no projeto (somente administrador).
 */
async function apiCriarTarefa(projetoId, dadosTarefa) {
  const { dados } = await apiFetch(`/projetos/${projetoId}/tarefas`, {
    method: "POST",
    body: JSON.stringify(dadosTarefa),
  });
  return dados;
}

/**
 * Atualiza uma tarefa do projeto (somente administrador).
 */
async function apiAtualizarTarefa(projetoId, tarefaId, dadosTarefa) {
  const { dados } = await apiFetch(`/projetos/${projetoId}/tarefas/${tarefaId}/atualizar`, {
    method: "POST",
    body: JSON.stringify(dadosTarefa),
  });
  return dados;
}

/**
 * Exclui uma tarefa do projeto (somente administrador).
 */
async function apiExcluirTarefa(projetoId, tarefaId) {
  const { dados } = await apiFetch(`/projetos/${projetoId}/tarefas/${tarefaId}/excluir`, {
    method: "POST",
  });
  return dados;
}


// ============================================================
// NOTIFICAÇÕES
// ============================================================

/**
 * Lista as notificações do usuário autenticado.
 * @param {boolean} apenasNaoLidas Se true, retorna só as não lidas.
 */
async function apiListarNotificacoes(apenasNaoLidas = false) {
  const caminho = apenasNaoLidas ? "/notificacoes?apenas_nao_lidas=1" : "/notificacoes";
  const { ok, dados } = await apiFetch(caminho);
  return { ok, dados };
}

/**
 * Conta quantas notificações não lidas o usuário autenticado tem
 * (usado para o "ponto" vermelho/contador no sino).
 */
async function apiContarNotificacoesNaoLidas() {
  const { ok, dados } = await apiFetch("/notificacoes/nao-lidas");
  return { ok, dados };
}

/**
 * Marca uma notificação específica como lida.
 */
async function apiMarcarNotificacaoLida(id) {
  const { dados } = await apiFetch(`/notificacoes/${id}/ler`, {
    method: "POST",
  });
  return dados;
}

/**
 * Marca todas as notificações do usuário autenticado como lidas.
 */
async function apiMarcarTodasNotificacoesLidas() {
  const { dados } = await apiFetch("/notificacoes/ler-todas", {
    method: "POST",
  });
  return dados;
}

// ============================================================
// AUDITORIA (somente administrador)
// ============================================================

/**
 * Lista os logs de auditoria com filtros e paginação.
 * @param {object} filtros { usuario_id, acao, modulo, data_inicio, data_fim, pagina, limite }
 */
async function apiListarAuditoria(filtros = {}) {
  const params = new URLSearchParams();
  Object.entries(filtros).forEach(([chave, valor]) => {
    if (valor !== null && valor !== undefined && valor !== "") {
      params.set(chave, valor);
    }
  });

  const query = params.toString();
  const { ok, dados } = await apiFetch(`/auditoria${query ? `?${query}` : ""}`);
  return { ok, dados };
}

/**
 * Busca o detalhe de um log específico.
 */
async function apiBuscarLogAuditoria(id) {
  const { ok, dados } = await apiFetch(`/auditoria/${id}`);
  return { ok, dados };
}

/**
 * Retorna as ações e módulos já usados nos logs (para os filtros).
 */
async function apiListarFiltrosAuditoria() {
  const { ok, dados } = await apiFetch("/auditoria/filtros");
  return { ok, dados };
}

// ============================================================
// FINANCEIRO (somente administrador)
// ============================================================

/**
 * Lista as categorias financeiras (para os seletores do formulário).
 */
async function apiListarCategoriasFinanceiras() {
  const { ok, dados } = await apiFetch("/financeiro/categorias");
  return { ok, dados };
}

/**
 * Lista movimentações financeiras com filtros e paginação.
 * @param {object} filtros { tipo, categoria_id, data_inicio, data_fim, pagina, limite }
 */
async function apiListarFinanceiro(filtros = {}) {
  const params = new URLSearchParams();
  Object.entries(filtros).forEach(([chave, valor]) => {
    if (valor !== null && valor !== undefined && valor !== "") {
      params.set(chave, valor);
    }
  });

  const query = params.toString();
  const { ok, dados } = await apiFetch(`/financeiro${query ? `?${query}` : ""}`);
  return { ok, dados };
}

/**
 * Busca uma movimentação financeira específica.
 */
async function apiBuscarMovimentacaoFinanceira(id) {
  const { ok, dados } = await apiFetch(`/financeiro/${id}`);
  return { ok, dados };
}

/**
 * Totais de entradas/saídas/saldo. Aceita filtro opcional de período.
 * @param {object} filtros { data_inicio, data_fim }
 */
async function apiResumoFinanceiro(filtros = {}) {
  const params = new URLSearchParams();
  Object.entries(filtros).forEach(([chave, valor]) => {
    if (valor !== null && valor !== undefined && valor !== "") {
      params.set(chave, valor);
    }
  });

  const query = params.toString();
  const { ok, dados } = await apiFetch(`/financeiro/resumo${query ? `?${query}` : ""}`);
  return { ok, dados };
}

/**
 * Cria uma nova movimentação financeira (receita ou despesa).
 */
async function apiCriarMovimentacaoFinanceira(dadosMovimentacao) {
  const { dados } = await apiFetch("/financeiro", {
    method: "POST",
    body: JSON.stringify(dadosMovimentacao),
  });
  return dados;
}

/**
 * Atualiza uma movimentação financeira existente.
 */
async function apiAtualizarMovimentacaoFinanceira(id, dadosMovimentacao) {
  const { dados } = await apiFetch(`/financeiro/${id}/atualizar`, {
    method: "POST",
    body: JSON.stringify(dadosMovimentacao),
  });
  return dados;
}

/**
 * Arquiva (soft delete) uma movimentação financeira.
 */
async function apiExcluirMovimentacaoFinanceira(id) {
  const { dados } = await apiFetch(`/financeiro/${id}/excluir`, {
    method: "POST",
  });
  return dados;
}

// ============================================================
// ORÇAMENTOS (somente administrador)
// ============================================================

/**
 * Lista orçamentos com filtros e paginação.
 * @param {object} filtros { ano_referencia, status, pagina, limite }
 */
async function apiListarOrcamentos(filtros = {}) {
  const params = new URLSearchParams();
  Object.entries(filtros).forEach(([chave, valor]) => {
    if (valor !== null && valor !== undefined && valor !== "") {
      params.set(chave, valor);
    }
  });

  const query = params.toString();
  const { ok, dados } = await apiFetch(`/orcamentos${query ? `?${query}` : ""}`);
  return { ok, dados };
}

/**
 * Busca um orçamento (cabeçalho + totais calculados pelo backend).
 */
async function apiBuscarOrcamento(id) {
  const { ok, dados } = await apiFetch(`/orcamentos/${id}`);
  return { ok, dados };
}

/**
 * Cria um orçamento. O total (valor_total) é calculado pelo backend.
 */
async function apiCriarOrcamento(dadosOrcamento) {
  const { dados } = await apiFetch("/orcamentos", {
    method: "POST",
    body: JSON.stringify(dadosOrcamento),
  });
  return dados;
}

/**
 * Atualiza os dados de um orçamento.
 */
async function apiAtualizarOrcamento(id, dadosOrcamento) {
  const { dados } = await apiFetch(`/orcamentos/${id}/atualizar`, {
    method: "POST",
    body: JSON.stringify(dadosOrcamento),
  });
  return dados;
}

/**
 * Arquiva (soft delete) um orçamento.
 */
async function apiExcluirOrcamento(id) {
  const { dados } = await apiFetch(`/orcamentos/${id}/excluir`, {
    method: "POST",
  });
  return dados;
}

/**
 * Lista os itens de um orçamento (junto com o cabeçalho e os totais).
 */
async function apiListarItensOrcamento(orcamentoId) {
  const { ok, dados } = await apiFetch(`/orcamentos/${orcamentoId}/itens`);
  return { ok, dados };
}

/**
 * Adiciona um item ao orçamento.
 */
async function apiCriarItemOrcamento(orcamentoId, dadosItem) {
  const { dados } = await apiFetch(`/orcamentos/${orcamentoId}/itens`, {
    method: "POST",
    body: JSON.stringify(dadosItem),
  });
  return dados;
}

/**
 * Atualiza um item (o backend só aceita se o item pertencer ao orçamento).
 */
async function apiAtualizarItemOrcamento(orcamentoId, itemId, dadosItem) {
  const { dados } = await apiFetch(`/orcamentos/${orcamentoId}/itens/${itemId}/atualizar`, {
    method: "POST",
    body: JSON.stringify(dadosItem),
  });
  return dados;
}

/**
 * Remove um item (o backend só aceita se o item pertencer ao orçamento).
 */
async function apiExcluirItemOrcamento(orcamentoId, itemId) {
  const { dados } = await apiFetch(`/orcamentos/${orcamentoId}/itens/${itemId}/excluir`, {
    method: "POST",
  });
  return dados;
}

// ============================================================
// CONTA DO USUÁRIO, RECUPERAÇÃO DE SENHA E RESUMO DO PAINEL
// ============================================================

/** Atualiza nome e e-mail do usuário logado (POST /api/me/perfil). */
async function apiAtualizarPerfil(nomeCompleto, email) {
  const { dados } = await apiFetch("/me/perfil", {
    method: "POST",
    body: JSON.stringify({ nome_completo: nomeCompleto, email }),
  });
  return dados;
}

/** Troca a senha do usuário logado (POST /api/me/senha). */
async function apiAlterarSenha(senhaAtual, novaSenha) {
  const { dados } = await apiFetch("/me/senha", {
    method: "POST",
    body: JSON.stringify({ senha_atual: senhaAtual, nova_senha: novaSenha }),
  });
  return dados;
}

/** "Esqueci minha senha" (POST /api/recuperar-senha) - pública. */
async function apiSolicitarRecuperacao(email, rm) {
  const { dados } = await apiFetch("/recuperar-senha", {
    method: "POST",
    body: JSON.stringify({ email, rm }),
  });
  return dados;
}

/** Confere se o link de redefinição ainda é válido - pública. */
async function apiValidarTokenRecuperacao(token) {
  const { dados } = await apiFetch("/recuperar-senha/validar", {
    method: "POST",
    body: JSON.stringify({ token }),
  });
  return dados;
}

/** Define a nova senha a partir do link de recuperação - pública. */
async function apiRedefinirSenha(token, novaSenha) {
  const { dados } = await apiFetch("/redefinir-senha", {
    method: "POST",
    body: JSON.stringify({ token, nova_senha: novaSenha }),
  });
  return dados;
}

/** Encerra as outras sessões abertas do usuário (POST /api/me/sessoes/encerrar-outras). */
async function apiEncerrarOutrasSessoes() {
  const { dados } = await apiFetch("/me/sessoes/encerrar-outras", { method: "POST" });
  return dados;
}

// ============================================================
// PREFERÊNCIAS DE ACESSIBILIDADE (salvas neste navegador)
// Aplicadas em todas as páginas que carregam api.js.
// ============================================================
const PREFS_STORAGE_KEY = "gremio_preferencias";

function obterPreferencias() {
  try {
    const p = JSON.parse(localStorage.getItem(PREFS_STORAGE_KEY) || "{}");
    return {
      altoContraste: !!p.altoContraste,
      reduzirAnimacoes: !!p.reduzirAnimacoes,
      tamanhoTexto: ["padrao", "grande", "muito-grande"].includes(p.tamanhoTexto) ? p.tamanhoTexto : "padrao",
    };
  } catch (e) {
    return { altoContraste: false, reduzirAnimacoes: false, tamanhoTexto: "padrao" };
  }
}

function salvarPreferencias(prefs) {
  try {
    localStorage.setItem(PREFS_STORAGE_KEY, JSON.stringify(prefs));
  } catch (e) { /* armazenamento indisponível: ignora */ }
  aplicarPreferencias();
}

function aplicarPreferencias() {
  const prefs = obterPreferencias();
  const raiz = document.documentElement;
  raiz.style.fontSize = { padrao: "", grande: "112.5%", "muito-grande": "125%" }[prefs.tamanhoTexto];
  raiz.classList.toggle("pref-alto-contraste", prefs.altoContraste);
  raiz.classList.toggle("pref-reduzir-animacoes", prefs.reduzirAnimacoes);

  let estilo = document.getElementById("gremio-prefs-style");
  if (!estilo) {
    estilo = document.createElement("style");
    estilo.id = "gremio-prefs-style";
    estilo.textContent =
      "html.pref-alto-contraste{filter:contrast(1.25)}" +
      "html.pref-alto-contraste a,html.pref-alto-contraste button{text-decoration-thickness:2px}" +
      "html.pref-reduzir-animacoes *,html.pref-reduzir-animacoes *::before,html.pref-reduzir-animacoes *::after" +
      "{animation-duration:0.001ms!important;animation-iteration-count:1!important;transition-duration:0.001ms!important;scroll-behavior:auto!important}";
    document.head.appendChild(estilo);
  }
}
aplicarPreferencias();

function exigirLogin() {
  const sessao = obterSessao();
  if (!sessao) {
    window.location.href = "login.php";
  }
  return sessao;
}
