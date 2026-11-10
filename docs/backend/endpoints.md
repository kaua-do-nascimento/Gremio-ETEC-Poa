# Endpoints da API

> Gerado a partir de `Backend/routes/api.php` (fonte da verdade). Total de rotas mapeadas: **82**.
> Se alterar rotas, atualize este arquivo.

**Base da URL:** `http://localhost/Gremio-ETEC-Poa/Backend/public` + o caminho abaixo (ex.: `/api/login`).
**Autenticação:** cabeçalho `Authorization: Bearer <token>` nas rotas que não são públicas.
**Formato:** requisições e respostas em JSON (`Content-Type: application/json`).

Observação: as rotas de alteração usam `POST` (ex.: `/atualizar`, `/excluir`) em vez de `PUT`/`DELETE`.

## Geral

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api` | Pública | - |

## Autenticação

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| POST | `/api/login` | Pública | AuthController::login |
| POST | `/api/logout` | Qualquer usuário logado | AuthController::logout |
| POST | `/api/cadastro` | Pública | UsuarioController::cadastrar |

## Conta do usuário logado

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api/me` | Qualquer usuário logado | AuthController::me |
| POST | `/api/me/perfil` | Qualquer usuário logado | ContaController::atualizarPerfil |
| POST | `/api/me/senha` | Qualquer usuário logado | ContaController::alterarSenha |
| POST | `/api/me/sessoes/encerrar-outras` | Qualquer usuário logado | ContaController::encerrarOutrasSessoes |

## Administração: solicitações de cadastro

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api/admin/solicitacoes` | Perfil: administrador | SolicitacaoController::listarPendentes |
| GET | `/api/admin/solicitacoes/{id}` | Perfil: administrador | SolicitacaoController::buscarPorId |
| POST | `/api/admin/solicitacoes/{id}/aprovar` | Perfil: administrador | SolicitacaoController::aprovar |
| POST | `/api/admin/solicitacoes/{id}/rejeitar` | Perfil: administrador | SolicitacaoController::rejeitar |

## Administração: usuários

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api/admin/usuarios` | Perfil: administrador | UsuarioController::listarTodos |
| POST | `/api/admin/usuarios/{id}/atualizar` | Perfil: administrador | UsuarioController::atualizarDados |
| POST | `/api/admin/usuarios/{id}/status` | Perfil: administrador | UsuarioController::mudarStatus |
| POST | `/api/admin/usuarios/{id}/excluir` | Perfil: administrador | UsuarioController::excluir |

## Publicações

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api/publicacoes` | Qualquer usuário logado | PublicacaoController::listar |
| GET | `/api/categorias-publicacao` | Qualquer usuário logado | PublicacaoController::listarCategorias |
| POST | `/api/publicacoes` | Perfil: administrador | PublicacaoController::criar |
| GET | `/api/publicacoes/{id}` | Qualquer usuário logado | PublicacaoController::buscarPorId |
| POST | `/api/publicacoes/{id}/atualizar` | Perfil: administrador | PublicacaoController::atualizar |
| POST | `/api/publicacoes/{id}/excluir` | Perfil: administrador | PublicacaoController::excluir |
| POST | `/api/publicacoes/{id}/reagir` | Qualquer usuário logado | PublicacaoController::reagir |

## Eventos

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api/eventos/proximos` | Qualquer usuário logado | EventoController::listarProximos |
| GET | `/api/eventos` | Qualquer usuário logado | EventoController::listar |
| POST | `/api/eventos` | Perfil: administrador | EventoController::criar |
| GET | `/api/eventos/{id}` | Qualquer usuário logado | EventoController::buscarPorId |
| POST | `/api/eventos/{id}/atualizar` | Perfil: administrador | EventoController::atualizar |
| POST | `/api/eventos/{id}/excluir` | Perfil: administrador | EventoController::excluir |
| POST | `/api/eventos/{id}/participar` | Qualquer usuário logado | EventoController::participar |
| POST | `/api/eventos/{id}/cancelar-participacao` | Qualquer usuário logado | EventoController::cancelarParticipacao |
| GET | `/api/eventos/{id}/participantes` | Perfil: administrador | EventoController::listarParticipantes |
| POST | `/api/eventos/{id}/presenca` | Perfil: administrador | EventoController::registrarPresenca |

## Votações

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api/votacoes` | Qualquer usuário logado | VotacaoController::listar |
| POST | `/api/votacoes` | Perfil: administrador | VotacaoController::criar |
| GET | `/api/votacoes/{id}` | Qualquer usuário logado | VotacaoController::buscarPorId |
| GET | `/api/votacoes/{id}/resultado` | Qualquer usuário logado | VotacaoController::resultado |
| POST | `/api/votacoes/{id}/atualizar` | Perfil: administrador | VotacaoController::atualizar |
| POST | `/api/votacoes/{id}/excluir` | Perfil: administrador | VotacaoController::excluir |
| POST | `/api/votacoes/{id}/votar` | Qualquer usuário logado | VotacaoController::votar |

## Projetos e tarefas

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api/status-tarefa` | Qualquer usuário logado | ProjetoController::listarStatusTarefa |
| GET | `/api/projetos` | Qualquer usuário logado | ProjetoController::listar |
| POST | `/api/projetos` | Perfil: administrador | ProjetoController::criar |
| GET | `/api/projetos/{id}` | Qualquer usuário logado | ProjetoController::buscarPorId |
| POST | `/api/projetos/{id}/atualizar` | Perfil: administrador | ProjetoController::atualizar |
| POST | `/api/projetos/{id}/excluir` | Perfil: administrador | ProjetoController::excluir |
| GET | `/api/projetos/{id}/historico` | Qualquer usuário logado | ProjetoController::listarHistorico |
| GET | `/api/projetos/{id}/membros` | Qualquer usuário logado | ProjetoController::listarMembros |
| POST | `/api/projetos/{id}/membros` | Perfil: administrador | ProjetoController::adicionarMembro |
| POST | `/api/projetos/{id}/membros/remover` | Perfil: administrador | ProjetoController::removerMembro |
| GET | `/api/projetos/{id}/tarefas` | Qualquer usuário logado | ProjetoController::listarTarefas |
| POST | `/api/projetos/{id}/tarefas` | Perfil: administrador | ProjetoController::criarTarefa |
| POST | `/api/projetos/{id}/tarefas/{id}/atualizar` | Perfil: administrador | ProjetoController::atualizarTarefa |
| POST | `/api/projetos/{id}/tarefas/{id}/excluir` | Perfil: administrador | ProjetoController::excluirTarefa |

## Notificações

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api/notificacoes/nao-lidas` | Qualquer usuário logado | NotificacaoController::contarNaoLidas |
| GET | `/api/notificacoes` | Qualquer usuário logado | NotificacaoController::listar |
| POST | `/api/notificacoes/ler-todas` | Qualquer usuário logado | NotificacaoController::marcarTodasComoLidas |
| POST | `/api/notificacoes/{id}/ler` | Qualquer usuário logado | NotificacaoController::marcarComoLida |
| GET | `/api/notificacoes/{id}` | Qualquer usuário logado | NotificacaoController::buscarPorId |

## Auditoria e logs

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api/auditoria/filtros` | Perfil: administrador | AuditoriaController::listarFiltrosDisponiveis |
| GET | `/api/auditoria` | Perfil: administrador | AuditoriaController::listar |
| GET | `/api/auditoria/{id}` | Perfil: administrador | AuditoriaController::buscarPorId |

## Financeiro

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api/financeiro/categorias` | Perfil: administrador | FinanceiroController::listarCategorias |
| GET | `/api/financeiro/resumo` | Perfil: administrador | FinanceiroController::resumo |
| GET | `/api/financeiro` | Perfil: administrador | FinanceiroController::listar |
| POST | `/api/financeiro` | Perfil: administrador | FinanceiroController::criar |
| GET | `/api/financeiro/{id}` | Perfil: administrador | FinanceiroController::buscarPorId |
| POST | `/api/financeiro/{id}/atualizar` | Perfil: administrador | FinanceiroController::atualizar |
| POST | `/api/financeiro/{id}/excluir` | Perfil: administrador | FinanceiroController::excluir |

## Orçamentos

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api/orcamentos` | Perfil: administrador | OrcamentoController::listar |
| POST | `/api/orcamentos` | Perfil: administrador | OrcamentoController::criar |
| GET | `/api/orcamentos/{id}/itens` | Perfil: administrador | OrcamentoController::listarItens |
| POST | `/api/orcamentos/{id}/itens` | Perfil: administrador | OrcamentoController::criarItem |
| POST | `/api/orcamentos/{id}/itens/{id}/atualizar` | Perfil: administrador | OrcamentoController::atualizarItem |
| POST | `/api/orcamentos/{id}/itens/{id}/excluir` | Perfil: administrador | OrcamentoController::excluirItem |
| GET | `/api/orcamentos/{id}` | Perfil: administrador | OrcamentoController::buscarPorId |
| POST | `/api/orcamentos/{id}/atualizar` | Perfil: administrador | OrcamentoController::atualizar |
| POST | `/api/orcamentos/{id}/excluir` | Perfil: administrador | OrcamentoController::excluir |

## Recuperação de senha

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| POST | `/api/recuperar-senha` | Pública | ContaController::solicitarRecuperacao |
| POST | `/api/recuperar-senha/validar` | Pública | ContaController::validarTokenRecuperacao |
| POST | `/api/redefinir-senha` | Pública | ContaController::redefinirSenha |

## Administração: resumo do painel

| Método | Rota | Acesso | Controller::método |
|---|---|---|---|
| GET | `/api/admin/resumo` | Perfil: administrador | ResumoController::resumo |

---
`{id}` representa um identificador numérico na URL.

Os campos esperados em cada corpo de requisição estão nas validações dos respectivos controllers em `Backend/controllers/`.
**A complementar pela equipe:** exemplos de requisição/resposta por endpoint.
