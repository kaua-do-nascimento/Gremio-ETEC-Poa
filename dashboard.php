<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários · Grêmio</title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="icon" href="imgs/gremio icon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;700;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo @filemtime(__DIR__ . '/css/dashboard.css'); ?>">
</head>
<body>

<div class="app">

    <!-- ===================== SIDEBAR ===================== -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-inner">
                <span class="ico ico-lime">
                    <svg width="22" height="22" viewBox="0 0 22 22" fill="currentColor">
                        <rect x="2" y="2" width="8" height="8" rx="1.5"></rect>
                        <rect x="12" y="2" width="8" height="8" rx="1.5"></rect>
                        <rect x="2" y="12" width="8" height="8" rx="1.5"></rect>
                        <rect x="12" y="12" width="8" height="8" rx="1.5"></rect>
                    </svg>
                </span>
                <div>
                    <p class="sidebar-title">Grêmio</p>
                    <p class="sidebar-subtitle">ETEC de Poá</p>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <p class="sidebar-nav-label">Menu</p>

            <button class="nav-item" data-page="dashboard">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="2.5" y="9" width="3" height="6.5" rx="0.5"></rect><rect x="7.5" y="5" width="3" height="10.5" rx="0.5"></rect><rect x="12.5" y="2.5" width="3" height="13" rx="0.5"></rect></svg>
                <span>Dashboard</span>
            </button>

            <button class="nav-item active" data-page="usuarios">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="9" cy="6" r="2.8"></circle><path d="M3.5 15.5c0-3 2.5-5 5.5-5s5.5 2 5.5 5"></path><path d="M14 9.5a2.5 2.5 0 100-5"></path><path d="M16.5 14.5c0-2-1-3.2-2.5-3.8"></path></svg>
                <span>Usuários</span>
            </button>

            <button class="nav-item" data-page="eventos">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="2.5" y="3.5" width="13" height="12" rx="2"></rect><path d="M2.5 7.5h13M6 2v3M12 2v3"></path></svg>
                <span>Eventos</span>
            </button>

            <button class="nav-item" data-page="projetos">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M2.5 5.5a1.5 1.5 0 011.5-1.5h3l1.5 2h5.5a1.5 1.5 0 011.5 1.5v6a1.5 1.5 0 01-1.5 1.5H4a1.5 1.5 0 01-1.5-1.5v-8z"></path></svg>
                <span>Projetos</span>
            </button>

            <button class="nav-item" data-page="votacoes">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M3 9l4 4 8-8"></path><path d="M3 14.5h12"></path></svg>
                <span>Votações</span>
            </button>

            <button class="nav-item" data-page="auditoria">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="2.5" width="12" height="13" rx="1.5"></rect><path d="M6 6.5h6M6 9h6M6 11.5h3.5"></path></svg>
                <span>Auditoria</span>
            </button>

            <button class="nav-item" data-page="financeiro">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="2" y="5" width="14" height="8" rx="1.5"></rect><circle cx="9" cy="9" r="1.8"></circle><path d="M4.5 5v8M13.5 5v8"></path></svg>
                <span>Financeiro</span>
            </button>

            <button class="nav-item" data-page="orcamentos">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="2.5" width="12" height="13" rx="1.5"></rect><path d="M6 6.5h6M6 9h6M6 11.5h3.5"></path></svg>
                <span>Orçamentos</span>
            </button>

            <button class="nav-item" data-page="configuracoes">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="9" cy="9" r="2.2"></circle><path d="M9 2v2M9 14v2M2 9h2M14 9h2M3.6 3.6l1.4 1.4M13 13l1.4 1.4M3.6 14.4l1.4-1.4M13 5l1.4-1.4"></path></svg>
                <span>Configurações</span>
            </button>

            <button class="nav-item" id="linkSairDashboard">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 3H3.5A1.5 1.5 0 002 4.5v9A1.5 1.5 0 003.5 15H7M12 12.5L15.5 9 12 5.5M15.5 9h-9"></path></svg>
                <span>Sair</span>
            </button>
        </nav>

        <div class="sidebar-profile">
            <div class="sidebar-profile-inner">
                <div class="avatar avatar-lime avatar-sm">AD</div>
                <div class="sidebar-profile-info">
                    <p class="sidebar-profile-name">Administrador</p>
                    <p class="sidebar-profile-email">admin@gremio.etec</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- ===================== MAIN ===================== -->
    <div class="main">

        <header class="topbar">
            <div>
                <h1 id="pageTitle" class="page-title">Gerenciar Usuários</h1>
                <p id="pageSubtitle" class="page-subtitle">— usuários cadastrados no sistema</p>
            </div>
            <div class="topbar-actions">
                <div class="search-box">
                    <span class="search-ico">
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="6.5" cy="6.5" r="4"></circle><path d="M10 10L13 13"></path></svg>
                    </span>
                    <input type="text" id="searchInput" placeholder="Buscar usuário...">
                </div>
                <div class="dropdown" style="position:relative;">
                    <button class="icon-btn" id="btnNotificacoes" title="Notificações" style="position:relative;">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M9 2a5 5 0 015 5v3l1.5 2.5H2.5L4 10V7a5 5 0 015-5z"></path><path d="M7 14.5a2 2 0 004 0"></path></svg>
                        <span class="notif-badge" id="notificacoesBadge" style="display:none;"></span>
                    </button>
                    <div class="notif-dropdown" id="notificacoesDropdown">
                        <div class="notif-dropdown-header">
                            <span>Notificações</span>
                            <button type="button" id="btnMarcarTodasLidas">Marcar todas como lidas</button>
                        </div>
                        <div class="notif-lista" id="notificacoesLista">
                            <div class="notif-vazio">Carregando...</div>
                        </div>
                    </div>
                </div>
                <div class="avatar avatar-lime">AD</div>
            </div>
        </header>

        <main class="content">

            <!-- ===================== PÁGINA: USUÁRIOS ===================== -->
            <section id="page-usuarios" class="page-section">

                <div class="stats-row">
                    <div class="stat-card stat-accent">
                        <p class="stat-label">Total de Alunos</p>
                        <p class="stat-value" id="statTotal">0</p>
                        <p class="stat-sub">cadastrados no sistema</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Ativos</p>
                        <p class="stat-value" id="statAtivos">0</p>
                        <p class="stat-sub">com acesso liberado</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Suspensos</p>
                        <p class="stat-value" id="statSuspensos">0</p>
                        <p class="stat-sub">acesso bloqueado</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Pendentes</p>
                        <p class="stat-value" id="statPendentes">0</p>
                        <p class="stat-sub">aguardando aprovação</p>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-toolbar">
                        <div class="tabs" id="statusTabs">
                            <button class="tab active" data-tab="todos">Todos <span class="tab-count" id="countTodos">0</span></button>
                            <button class="tab" data-tab="ativo">Ativos <span class="tab-count" id="countAtivo">0</span></button>
                            <button class="tab" data-tab="suspenso">Suspensos <span class="tab-count" id="countSuspenso">0</span></button>
                            <button class="tab" data-tab="pendente">Pendentes <span class="tab-count" id="countPendente">0</span></button>
                        </div>
                        <p class="results-count" id="resultsCount">0 resultados</p>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nº</th>
                                    <th>Nome</th>
                                    <th>Rm</th>
                                    <th>Turma</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                            </tbody>
                        </table>
                    </div>

                    <div class="table-pagination">
                        <p class="pagination-info" id="paginationInfo">0 de 0</p>
                        <div class="pagination-buttons" id="paginationButtons"></div>
                    </div>
                </div>

            </section>

            <!-- ===================== PÁGINA: EVENTOS ===================== -->
            <section id="page-eventos" class="page-section" style="display:none;">

                <div class="stats-row">
                    <div class="stat-card stat-accent">
                        <p class="stat-label">Total de Eventos</p>
                        <p class="stat-value" id="statEventosTotal">0</p>
                        <p class="stat-sub">cadastrados no sistema</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Planejados</p>
                        <p class="stat-value" id="statEventosPlanejados">0</p>
                        <p class="stat-sub">ainda vão acontecer</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Em andamento</p>
                        <p class="stat-value" id="statEventosAndamento">0</p>
                        <p class="stat-sub">acontecendo agora</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Realizados</p>
                        <p class="stat-value" id="statEventosRealizados">0</p>
                        <p class="stat-sub">já concluídos</p>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-toolbar">
                        <div class="tabs" id="eventosStatusTabs">
                            <button class="tab active" data-tab="todos">Todos <span class="tab-count" id="countEventosTodos">0</span></button>
                            <button class="tab" data-tab="planejado">Planejados <span class="tab-count" id="countEventosPlanejado">0</span></button>
                            <button class="tab" data-tab="em_andamento">Em andamento <span class="tab-count" id="countEventosAndamento">0</span></button>
                            <button class="tab" data-tab="realizado">Realizados <span class="tab-count" id="countEventosRealizado">0</span></button>
                            <button class="tab" data-tab="cancelado">Cancelados <span class="tab-count" id="countEventosCancelado">0</span></button>
                        </div>
                        <button class="btn-primary" id="novoEventoBtn" type="button">+ Novo evento</button>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nº</th>
                                    <th>Título</th>
                                    <th>Data / Horário</th>
                                    <th>Local</th>
                                    <th>Participantes</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="eventosTableBody">
                            </tbody>
                        </table>
                    </div>

                    <div class="table-pagination">
                        <p class="pagination-info" id="eventosPaginationInfo">0 de 0</p>
                        <div class="pagination-buttons" id="eventosPaginationButtons"></div>
                    </div>
                </div>

            </section>

            <!-- ===================== PÁGINA: PROJETOS ===================== -->
            <section id="page-projetos" class="page-section" style="display:none;">

                <div class="stats-row">
                    <div class="stat-card stat-accent">
                        <p class="stat-label">Total de Projetos</p>
                        <p class="stat-value" id="statProjetosTotal">0</p>
                        <p class="stat-sub">cadastrados no sistema</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Planejados</p>
                        <p class="stat-value" id="statProjetosPlanejados">0</p>
                        <p class="stat-sub">ainda vão começar</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Em andamento</p>
                        <p class="stat-value" id="statProjetosAndamento">0</p>
                        <p class="stat-sub">acontecendo agora</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Concluídos</p>
                        <p class="stat-value" id="statProjetosConcluidos">0</p>
                        <p class="stat-sub">já finalizados</p>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-toolbar">
                        <div class="tabs" id="projetosStatusTabs">
                            <button class="tab active" data-tab="todos">Todos <span class="tab-count" id="countProjetosTodos">0</span></button>
                            <button class="tab" data-tab="planejado">Planejados <span class="tab-count" id="countProjetosPlanejado">0</span></button>
                            <button class="tab" data-tab="em_andamento">Em andamento <span class="tab-count" id="countProjetosAndamento">0</span></button>
                            <button class="tab" data-tab="concluido">Concluídos <span class="tab-count" id="countProjetosConcluido">0</span></button>
                            <button class="tab" data-tab="cancelado">Cancelados <span class="tab-count" id="countProjetosCancelado">0</span></button>
                        </div>
                        <button class="btn-primary" id="novoProjetoBtn" type="button">+ Novo projeto</button>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nº</th>
                                    <th>Nome</th>
                                    <th>Responsável</th>
                                    <th>Prazo previsto</th>
                                    <th>Membros</th>
                                    <th>Tarefas</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="projetosTableBody">
                            </tbody>
                        </table>
                    </div>

                    <div class="table-pagination">
                        <p class="pagination-info" id="projetosPaginationInfo">0 de 0</p>
                        <div class="pagination-buttons" id="projetosPaginationButtons"></div>
                    </div>
                </div>

            </section>

            <!-- ===================== PÁGINA: VOTAÇÕES ===================== -->
            <section id="page-votacoes" class="page-section" style="display:none;">

                <div class="stats-row">
                    <div class="stat-card stat-accent">
                        <p class="stat-label">Total de Votações</p>
                        <p class="stat-value" id="statVotacoesTotal">0</p>
                        <p class="stat-sub">cadastradas no sistema</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Agendadas</p>
                        <p class="stat-value" id="statVotacoesAgendadas">0</p>
                        <p class="stat-sub">ainda vão abrir</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Abertas</p>
                        <p class="stat-value" id="statVotacoesAbertas">0</p>
                        <p class="stat-sub">recebendo votos agora</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Encerradas</p>
                        <p class="stat-value" id="statVotacoesEncerradas">0</p>
                        <p class="stat-sub">já concluídas</p>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-toolbar">
                        <div class="tabs" id="votacoesStatusTabs">
                            <button class="tab active" data-tab="todos">Todas <span class="tab-count" id="countVotacoesTodos">0</span></button>
                            <button class="tab" data-tab="agendada">Agendadas <span class="tab-count" id="countVotacoesAgendada">0</span></button>
                            <button class="tab" data-tab="aberta">Abertas <span class="tab-count" id="countVotacoesAberta">0</span></button>
                            <button class="tab" data-tab="encerrada">Encerradas <span class="tab-count" id="countVotacoesEncerrada">0</span></button>
                            <button class="tab" data-tab="cancelada">Canceladas <span class="tab-count" id="countVotacoesCancelada">0</span></button>
                        </div>
                        <button class="btn-primary" id="novaVotacaoBtn" type="button">+ Nova votação</button>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nº</th>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Período</th>
                                    <th>Status</th>
                                    <th>Total de votos</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="votacoesTableBody">
                            </tbody>
                        </table>
                    </div>

                    <div class="table-pagination">
                        <p class="pagination-info" id="votacoesPaginationInfo">0 de 0</p>
                        <div class="pagination-buttons" id="votacoesPaginationButtons"></div>
                    </div>
                </div>

            </section>

            <!-- ===================== PÁGINA: AUDITORIA ===================== -->
            <section id="page-auditoria" class="page-section" style="display:none;">

                <div class="table-card">
                    <div class="table-toolbar" style="flex-wrap:wrap; gap:10px;">
                        <div class="filtros-auditoria" style="display:flex; gap:8px; flex-wrap:wrap;">
                            <select id="auditoriaFiltroModulo" class="filtro-select">
                                <option value="">Todos os módulos</option>
                            </select>
                            <select id="auditoriaFiltroAcao" class="filtro-select">
                                <option value="">Todas as ações</option>
                            </select>
                            <input type="date" id="auditoriaFiltroDataInicio" class="filtro-select" title="De">
                            <input type="date" id="auditoriaFiltroDataFim" class="filtro-select" title="Até">
                            <button class="btn-secondary" id="btnLimparFiltrosAuditoria">Limpar filtros</button>
                        </div>
                        <p class="results-count" id="auditoriaResultsCount">0 resultados</p>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Usuário</th>
                                    <th>Ação</th>
                                    <th>Módulo</th>
                                    <th>Descrição</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody id="auditoriaTableBody">
                            </tbody>
                        </table>
                    </div>

                    <div class="table-pagination">
                        <p class="pagination-info" id="auditoriaPaginationInfo">0 de 0</p>
                        <div class="pagination-buttons" id="auditoriaPaginationButtons"></div>
                    </div>
                </div>

            </section>

            <!-- ===================== PÁGINA: FINANCEIRO ===================== -->
            <section id="page-financeiro" class="page-section" style="display:none;">

                <div class="stats-row">
                    <div class="stat-card stat-accent">
                        <p class="stat-label">Total de Entradas</p>
                        <p class="stat-value" id="statFinanceiroEntradas">R$ 0,00</p>
                        <p class="stat-sub">no período filtrado</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Total de Saídas</p>
                        <p class="stat-value" id="statFinanceiroSaidas">R$ 0,00</p>
                        <p class="stat-sub">no período filtrado</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Saldo</p>
                        <p class="stat-value" id="statFinanceiroSaldo">R$ 0,00</p>
                        <p class="stat-sub">entradas − saídas</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Movimentações</p>
                        <p class="stat-value" id="statFinanceiroQuantidade">0</p>
                        <p class="stat-sub">lançamentos no período filtrado</p>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-toolbar" style="flex-wrap:wrap; gap:10px;">
                        <div class="filtros-auditoria" style="display:flex; gap:8px; flex-wrap:wrap;">
                            <select id="financeiroFiltroTipo" class="filtro-select">
                                <option value="">Todos os tipos</option>
                                <option value="entrada">Entrada</option>
                                <option value="saida">Saída</option>
                            </select>
                            <select id="financeiroFiltroCategoria" class="filtro-select">
                                <option value="">Todas as categorias</option>
                            </select>
                            <input type="date" id="financeiroFiltroDataInicio" class="filtro-select" title="De">
                            <input type="date" id="financeiroFiltroDataFim" class="filtro-select" title="Até">
                            <button class="btn-secondary" id="btnLimparFiltrosFinanceiro">Limpar filtros</button>
                        </div>
                        <button class="btn-primary" id="novaMovimentacaoBtn" type="button">+ Nova movimentação</button>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Categoria</th>
                                    <th>Descrição</th>
                                    <th>Valor</th>
                                    <th>Lançado por</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="financeiroTableBody">
                            </tbody>
                        </table>
                    </div>

                    <div class="table-pagination">
                        <p class="pagination-info" id="financeiroPaginationInfo">0 de 0</p>
                        <div class="pagination-buttons" id="financeiroPaginationButtons"></div>
                    </div>
                </div>

            </section>

            <!-- ===================== PÁGINA: ORÇAMENTOS ===================== -->
            <section id="page-orcamentos" class="page-section" style="display:none;">

                <div class="stats-row">
                    <div class="stat-card stat-accent">
                        <p class="stat-label">Orçamentos</p>
                        <p class="stat-value" id="statOrcamentosQuantidade">0</p>
                        <p class="stat-sub">no filtro atual</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Valor total previsto</p>
                        <p class="stat-value" id="statOrcamentosValor">R$ 0,00</p>
                        <p class="stat-sub">soma dos orçamentos filtrados</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Aprovados</p>
                        <p class="stat-value" id="statOrcamentosAprovados">0</p>
                        <p class="stat-sub">no filtro atual</p>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-toolbar" style="flex-wrap:wrap; gap:10px;">
                        <div class="filtros-auditoria" style="display:flex; gap:8px; flex-wrap:wrap;">
                            <input type="number" id="orcamentoFiltroAno" class="filtro-select" placeholder="Ano" min="1901" max="2155" style="width:110px;">
                            <select id="orcamentoFiltroStatus" class="filtro-select">
                                <option value="">Todos os status</option>
                                <option value="planejado">Planejado</option>
                                <option value="aprovado">Aprovado</option>
                                <option value="encerrado">Encerrado</option>
                            </select>
                            <button class="btn-secondary" id="btnLimparFiltrosOrcamento">Limpar filtros</button>
                        </div>
                        <button class="btn-primary" id="novoOrcamentoBtn" type="button">+ Novo orçamento</button>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Ano</th>
                                    <th>Status</th>
                                    <th>Itens</th>
                                    <th>Valor total</th>
                                    <th>Criado por</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="orcamentosTableBody">
                            </tbody>
                        </table>
                    </div>

                    <div class="table-pagination">
                        <p class="pagination-info" id="orcamentosPaginationInfo">0 de 0</p>
                        <div class="pagination-buttons" id="orcamentosPaginationButtons"></div>
                    </div>
                </div>

            </section>

            <!-- ===================== PÁGINA: DASHBOARD (visão geral) ===================== -->
            <section id="page-dashboard" class="page-section" style="display:none;">

                <div class="stats-row">
                    <div class="stat-card stat-accent">
                        <p class="stat-label">Saldo do grêmio</p>
                        <p class="stat-value" id="resumoSaldo">R$ 0,00</p>
                        <p class="stat-sub" id="resumoSaldoSub">entradas − saídas</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Usuários ativos</p>
                        <p class="stat-value" id="resumoUsuarios">0</p>
                        <p class="stat-sub" id="resumoUsuariosSub">cadastros pendentes: 0</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Eventos</p>
                        <p class="stat-value" id="resumoEventos">0</p>
                        <p class="stat-sub" id="resumoEventosSub">planejados: 0</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Projetos</p>
                        <p class="stat-value" id="resumoProjetos">0</p>
                        <p class="stat-sub" id="resumoProjetosSub">em andamento: 0</p>
                    </div>
                </div>

                <div class="stats-row">
                    <div class="stat-card">
                        <p class="stat-label">Votações</p>
                        <p class="stat-value" id="resumoVotacoes">0</p>
                        <p class="stat-sub" id="resumoVotacoesSub">abertas: 0</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Publicações</p>
                        <p class="stat-value" id="resumoPublicacoes">0</p>
                        <p class="stat-sub">no mural</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Orçamentos</p>
                        <p class="stat-value" id="resumoOrcamentos">0</p>
                        <p class="stat-sub" id="resumoOrcamentosSub">aprovados: 0</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Entradas / Saídas</p>
                        <p class="stat-value" id="resumoEntradas" style="font-size:20px;">R$ 0,00</p>
                        <p class="stat-sub" id="resumoSaidas">saídas: R$ 0,00</p>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-toolbar"><strong>Atividade recente</strong></div>
                    <table>
                        <thead>
                            <tr><th>Quando</th><th>Usuário</th><th>Módulo</th><th>Descrição</th></tr>
                        </thead>
                        <tbody id="resumoAtividadeBody">
                            <tr class="empty-row"><td colspan="4">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ===================== PÁGINAS EM DESENVOLVIMENTO ===================== -->
            <section id="page-em-breve" class="page-section placeholder-page" style="display:none;">
                <div class="placeholder-icon" id="placeholderIcon"></div>
                <p class="placeholder-title">Em breve</p>
                <p class="placeholder-desc">Esta seção está em desenvolvimento.</p>
            </section>

        </main>
    </div>
</div>

<!-- ===================== MODAL: EDITAR PERFIL ===================== -->
<div class="modal-overlay" id="editModalOverlay">
    <div class="modal-backdrop" data-close="edit"></div>
    <aside class="edit-panel">
        <div class="edit-panel-header">
            <div>
                <h2>Editar Perfil</h2>
                <p id="editMatricula">#0000</p>
            </div>
            <button class="icon-btn" data-close="edit">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l10 10M13 3L3 13"></path></svg>
            </button>
        </div>

        <div class="edit-panel-body">
            <div class="edit-avatar-preview">
                <div class="avatar avatar-lg" id="editAvatarPreview">AA</div>
            </div>

            <div class="field">
                <label>Nome completo</label>
                <input type="text" id="editName">
            </div>
            <div class="field">
                <label>E-mail institucional</label>
                <input type="email" id="editEmail">
            </div>
            <div class="field">
                <label>Telefone</label>
                <input type="text" id="editPhone">
            </div>
            <div class="field">
                <label>Rm</label>
                <input type="text" id="editRm">
            </div>
            <div class="field">
                <label>Turma</label>
                <input type="text" id="editTurma">
            </div>
            <div class="field">
                <label>Status</label>
                <div class="status-choice" id="editStatusChoice">
                    <button type="button" data-status="ativo">ativo</button>
                    <button type="button" data-status="suspenso">suspenso</button>
                    <button type="button" data-status="pendente">pendente</button>
                </div>
            </div>
        </div>

        <div class="edit-panel-footer">
            <button class="btn-secondary" data-close="edit">Cancelar</button>
            <button class="btn-primary" id="saveEditBtn">Salvar alterações</button>
        </div>
    </aside>
</div>

<!-- ===================== MODAL: EXCLUIR PERFIL ===================== -->
<div class="modal-overlay" id="deleteModalOverlay">
    <div class="modal-backdrop" data-close="delete"></div>
    <div class="delete-panel">
        <div class="delete-icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M14 10v6M14 19.5v.5"></path><path d="M12.3 3.8L2.6 20a2 2 0 001.7 3h19.4a2 2 0 001.7-3L15.7 3.8a2 2 0 00-3.4 0z"></path></svg>
        </div>
        <h3>Excluir perfil?</h3>
        <p>O perfil de <strong id="deleteUserName"></strong> será removido permanentemente. Esta ação não pode ser desfeita.</p>
        <div class="delete-buttons">
            <button class="btn-secondary" data-close="delete">Cancelar</button>
            <button class="btn-danger" id="confirmDeleteBtn">Sim, excluir</button>
        </div>
    </div>
</div>

<!-- ===================== MODAL: CRIAR/EDITAR EVENTO ===================== -->
<div class="modal-overlay" id="eventoModalOverlay">
    <div class="modal-backdrop" data-close="evento"></div>
    <aside class="edit-panel">
        <div class="edit-panel-header">
            <div>
                <h2 id="eventoModalTitulo">Novo evento</h2>
                <p id="eventoModalSubtitulo">Preencha os dados do evento</p>
            </div>
            <button class="icon-btn" data-close="evento">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l10 10M13 3L3 13"></path></svg>
            </button>
        </div>

        <div class="edit-panel-body">
            <div id="eventoFormError" class="message error" style="display:none;"></div>

            <div class="field">
                <label>Título</label>
                <input type="text" id="eventoTitulo" maxlength="150">
            </div>
            <div class="field">
                <label>Descrição</label>
                <textarea id="eventoDescricao" rows="3"></textarea>
            </div>
            <div class="field">
                <label>Local</label>
                <input type="text" id="eventoLocal">
            </div>
            <div class="field">
                <label>Data do evento</label>
                <input type="date" id="eventoData">
            </div>
            <div class="field">
                <label>Horário de início</label>
                <input type="time" id="eventoHoraInicio">
            </div>
            <div class="field">
                <label>Horário de término (opcional)</label>
                <input type="time" id="eventoHoraFim">
            </div>
            <div class="field">
                <label>Limite de participantes (opcional)</label>
                <input type="number" id="eventoLimite" min="1">
            </div>
            <div class="field">
                <label>Status</label>
                <select id="eventoStatus">
                    <option value="planejado">Planejado</option>
                    <option value="em_andamento">Em andamento</option>
                    <option value="realizado">Realizado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
        </div>

        <div class="edit-panel-footer">
            <button class="btn-secondary" data-close="evento">Cancelar</button>
            <button class="btn-primary" id="salvarEventoBtn">Salvar evento</button>
        </div>
    </aside>
</div>

<!-- ===================== MODAL: EXCLUIR EVENTO ===================== -->
<div class="modal-overlay" id="deleteEventoModalOverlay">
    <div class="modal-backdrop" data-close="delete-evento"></div>
    <div class="delete-panel">
        <div class="delete-icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M14 10v6M14 19.5v.5"></path><path d="M12.3 3.8L2.6 20a2 2 0 001.7 3h19.4a2 2 0 001.7-3L15.7 3.8a2 2 0 00-3.4 0z"></path></svg>
        </div>
        <h3>Excluir evento?</h3>
        <p>O evento <strong id="deleteEventoTitulo"></strong> será removido permanentemente. Esta ação não pode ser desfeita.</p>
        <div class="delete-buttons">
            <button class="btn-secondary" data-close="delete-evento">Cancelar</button>
            <button class="btn-danger" id="confirmDeleteEventoBtn">Sim, excluir</button>
        </div>
    </div>
</div>

<!-- ===================== MODAL: PARTICIPANTES E PRESENÇA ===================== -->
<div class="modal-overlay" id="participantesModalOverlay">
    <div class="modal-backdrop" data-close="participantes"></div>
    <aside class="edit-panel">
        <div class="edit-panel-header">
            <div>
                <h2>Participantes</h2>
                <p id="participantesEventoTitulo">—</p>
            </div>
            <button class="icon-btn" data-close="participantes">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l10 10M13 3L3 13"></path></svg>
            </button>
        </div>

        <div class="edit-panel-body">
            <div id="participantesLista"></div>
        </div>

        <div class="edit-panel-footer">
            <button class="btn-secondary" data-close="participantes">Fechar</button>
        </div>
    </aside>
</div>

<!-- ===================== MODAL: CRIAR/EDITAR PROJETO ===================== -->
<div class="modal-overlay" id="projetoModalOverlay">
    <div class="modal-backdrop" data-close="projeto"></div>
    <aside class="edit-panel">
        <div class="edit-panel-header">
            <div>
                <h2 id="projetoModalTitulo">Novo projeto</h2>
                <p id="projetoModalSubtitulo">Preencha os dados do projeto</p>
            </div>
            <button class="icon-btn" data-close="projeto">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l10 10M13 3L3 13"></path></svg>
            </button>
        </div>

        <div class="edit-panel-body">
            <div id="projetoFormError" class="message error" style="display:none;"></div>

            <div class="field">
                <label>Nome do projeto</label>
                <input type="text" id="projetoNome" maxlength="150">
            </div>
            <div class="field">
                <label>Descrição</label>
                <textarea id="projetoDescricao" rows="3"></textarea>
            </div>
            <div class="field">
                <label>Responsável</label>
                <select id="projetoResponsavel"></select>
            </div>
            <div class="field">
                <label>Data de início (opcional)</label>
                <input type="date" id="projetoDataInicio">
            </div>
            <div class="field">
                <label>Prazo previsto (opcional)</label>
                <input type="date" id="projetoDataFimPrevista">
            </div>
            <div class="field">
                <label>Data de conclusão real (opcional)</label>
                <input type="date" id="projetoDataFimReal">
            </div>
            <div class="field">
                <label>Status</label>
                <select id="projetoStatus">
                    <option value="planejado">Planejado</option>
                    <option value="em_andamento">Em andamento</option>
                    <option value="concluido">Concluído</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
        </div>

        <div class="edit-panel-footer">
            <button class="btn-secondary" data-close="projeto">Cancelar</button>
            <button class="btn-primary" id="salvarProjetoBtn">Salvar projeto</button>
        </div>
    </aside>
</div>

<!-- ===================== MODAL: ARQUIVAR/EXCLUIR PROJETO ===================== -->
<div class="modal-overlay" id="deleteProjetoModalOverlay">
    <div class="modal-backdrop" data-close="delete-projeto"></div>
    <div class="delete-panel">
        <div class="delete-icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M14 10v6M14 19.5v.5"></path><path d="M12.3 3.8L2.6 20a2 2 0 001.7 3h19.4a2 2 0 001.7-3L15.7 3.8a2 2 0 00-3.4 0z"></path></svg>
        </div>
        <h3>Arquivar projeto?</h3>
        <p>O projeto <strong id="deleteProjetoNome"></strong> será arquivado e deixará de aparecer nas listagens. Esta ação não pode ser desfeita.</p>
        <div class="delete-buttons">
            <button class="btn-secondary" data-close="delete-projeto">Cancelar</button>
            <button class="btn-danger" id="confirmDeleteProjetoBtn">Sim, arquivar</button>
        </div>
    </div>
</div>

<!-- ===================== MODAL: DETALHES DO PROJETO (MEMBROS / TAREFAS / HISTÓRICO) ===================== -->
<div class="modal-overlay" id="projetoDetalheModalOverlay">
    <div class="modal-backdrop" data-close="projeto-detalhe"></div>
    <aside class="edit-panel">
        <div class="edit-panel-header">
            <div>
                <h2 id="projetoDetalheTitulo">Projeto</h2>
                <p id="projetoDetalheSubtitulo">—</p>
            </div>
            <button class="icon-btn" data-close="projeto-detalhe">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l10 10M13 3L3 13"></path></svg>
            </button>
        </div>

        <div class="edit-panel-body">
            <div style="display:flex; gap:8px; margin-bottom:16px;">
                <button class="btn-primary" id="projetoDetalheTabMembrosBtn" type="button" style="flex:1;">Membros</button>
                <button class="btn-secondary" id="projetoDetalheTabTarefasBtn" type="button" style="flex:1;">Tarefas</button>
                <button class="btn-secondary" id="projetoDetalheTabHistoricoBtn" type="button" style="flex:1;">Histórico</button>
            </div>

            <!-- ---- PAINEL: MEMBROS ---- -->
            <div id="projetoDetalhePainelMembros">
                <div id="membroFormError" class="message error" style="display:none;"></div>
                <div class="field" style="display:flex; gap:8px; align-items:flex-end;">
                    <div style="flex:2;">
                        <label>Adicionar membro</label>
                        <select id="membroUsuarioSelect"></select>
                    </div>
                    <div style="flex:1;">
                        <label>Papel</label>
                        <select id="membroPapelSelect">
                            <option value="membro">Membro</option>
                            <option value="coordenador">Coordenador</option>
                        </select>
                    </div>
                    <button class="btn-primary" id="adicionarMembroBtn" type="button">Adicionar</button>
                </div>
                <div id="projetoMembrosLista" style="margin-top:12px;"></div>
            </div>

            <!-- ---- PAINEL: TAREFAS ---- -->
            <div id="projetoDetalhePainelTarefas" style="display:none;">
                <div id="tarefaFormError" class="message error" style="display:none;"></div>
                <div class="field">
                    <label>Título da nova tarefa</label>
                    <input type="text" id="tarefaTitulo" maxlength="150">
                </div>
                <div class="field" style="display:flex; gap:8px;">
                    <div style="flex:1;">
                        <label>Responsável (opcional)</label>
                        <select id="tarefaResponsavelSelect"></select>
                    </div>
                    <div style="flex:1;">
                        <label>Prazo (opcional)</label>
                        <input type="date" id="tarefaDataPrazo">
                    </div>
                </div>
                <button class="btn-primary" id="criarTarefaBtn" type="button">+ Adicionar tarefa</button>
                <div id="projetoTarefasLista" style="margin-top:16px;"></div>
            </div>

            <!-- ---- PAINEL: HISTÓRICO ---- -->
            <div id="projetoDetalhePainelHistorico" style="display:none;">
                <div id="projetoHistoricoLista"></div>
            </div>
        </div>

        <div class="edit-panel-footer">
            <button class="btn-secondary" data-close="projeto-detalhe">Fechar</button>
        </div>
    </aside>
</div>

<!-- ===================== MODAL: CRIAR/EDITAR VOTAÇÃO ===================== -->
<div class="modal-overlay" id="votacaoModalOverlay">
    <div class="modal-backdrop" data-close="votacao"></div>
    <aside class="edit-panel">
        <div class="edit-panel-header">
            <div>
                <h2 id="votacaoModalTitulo">Nova votação</h2>
                <p id="votacaoModalSubtitulo">Preencha os dados da votação</p>
            </div>
            <button class="icon-btn" data-close="votacao">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l10 10M13 3L3 13"></path></svg>
            </button>
        </div>

        <div class="edit-panel-body">
            <div id="votacaoFormError" class="message error" style="display:none;"></div>

            <div class="field">
                <label>Título</label>
                <input type="text" id="votacaoTitulo" maxlength="150">
            </div>
            <div class="field">
                <label>Descrição (opcional)</label>
                <textarea id="votacaoDescricao" rows="3"></textarea>
            </div>
            <div class="field">
                <label>Tipo</label>
                <select id="votacaoTipo">
                    <option value="enquete">Enquete</option>
                    <option value="eleicao">Eleição</option>
                    <option value="votacao_gremio">Votação do Grêmio</option>
                </select>
            </div>
            <div class="field">
                <label>Início</label>
                <input type="datetime-local" id="votacaoDataInicio">
            </div>
            <div class="field">
                <label>Término</label>
                <input type="datetime-local" id="votacaoDataFim">
            </div>
            <div class="field">
                <label>Status</label>
                <select id="votacaoStatus">
                    <option value="agendada">Agendada</option>
                    <option value="aberta">Aberta</option>
                    <option value="encerrada">Encerrada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>

            <div class="field">
                <label>Opções de voto</label>
                <p id="votacaoOpcoesAviso" style="display:none; font-size:12px; color:#888; margin-bottom:8px;">
                    As opções só podem ser alteradas enquanto a votação ainda está "Agendada".
                </p>
                <div id="votacaoOpcoesLista"></div>
                <button type="button" class="btn-secondary" id="adicionarOpcaoBtn" style="margin-top:8px;">+ Adicionar opção</button>
            </div>
        </div>

        <div class="edit-panel-footer">
            <button class="btn-secondary" data-close="votacao">Cancelar</button>
            <button class="btn-primary" id="salvarVotacaoBtn">Salvar votação</button>
        </div>
    </aside>
</div>

<!-- ===================== MODAL: EXCLUIR VOTAÇÃO ===================== -->
<div class="modal-overlay" id="deleteVotacaoModalOverlay">
    <div class="modal-backdrop" data-close="delete-votacao"></div>
    <div class="delete-panel">
        <div class="delete-icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M14 10v6M14 19.5v.5"></path><path d="M12.3 3.8L2.6 20a2 2 0 001.7 3h19.4a2 2 0 001.7-3L15.7 3.8a2 2 0 00-3.4 0z"></path></svg>
        </div>
        <h3>Excluir votação?</h3>
        <p>A votação <strong id="deleteVotacaoTitulo"></strong> será removida permanentemente. Esta ação não pode ser desfeita.</p>
        <div class="delete-buttons">
            <button class="btn-secondary" data-close="delete-votacao">Cancelar</button>
            <button class="btn-danger" id="confirmDeleteVotacaoBtn">Sim, excluir</button>
        </div>
    </div>
</div>

<!-- ===================== MODAL: RESULTADO DA VOTAÇÃO ===================== -->
<div class="modal-overlay" id="resultadoModalOverlay">
    <div class="modal-backdrop" data-close="resultado"></div>
    <aside class="edit-panel">
        <div class="edit-panel-header">
            <div>
                <h2>Resultado</h2>
                <p id="resultadoVotacaoTitulo">—</p>
            </div>
            <button class="icon-btn" data-close="resultado">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l10 10M13 3L3 13"></path></svg>
            </button>
        </div>

        <div class="edit-panel-body">
            <div id="resultadoLista"></div>
        </div>

        <div class="edit-panel-footer">
            <button class="btn-secondary" data-close="resultado">Fechar</button>
        </div>
    </aside>
</div>

<!-- ===================== MODAL: DETALHE DO LOG DE AUDITORIA ===================== -->
<div class="modal-overlay" id="logDetalheModalOverlay">
    <div class="modal-backdrop" data-close="log-detalhe"></div>
    <aside class="edit-panel">
        <div class="edit-panel-header">
            <div>
                <h2>Detalhes do log</h2>
                <p id="logDetalheId">#0</p>
            </div>
            <button class="icon-btn" data-close="log-detalhe">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l10 10M13 3L3 13"></path></svg>
            </button>
        </div>

        <div class="edit-panel-body">
            <div id="logDetalheCorpo"></div>
        </div>

        <div class="edit-panel-footer">
            <button class="btn-secondary" data-close="log-detalhe">Fechar</button>
        </div>
    </aside>
</div>

<!-- ===================== MODAL: CRIAR/EDITAR MOVIMENTAÇÃO FINANCEIRA ===================== -->
<div class="modal-overlay" id="financeiroModalOverlay">
    <div class="modal-backdrop" data-close="financeiro"></div>
    <aside class="edit-panel">
        <div class="edit-panel-header">
            <div>
                <h2 id="financeiroModalTitulo">Nova movimentação</h2>
                <p id="financeiroModalSubtitulo">Preencha os dados da movimentação</p>
            </div>
            <button class="icon-btn" data-close="financeiro">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l10 10M13 3L3 13"></path></svg>
            </button>
        </div>

        <div class="edit-panel-body">
            <div id="financeiroFormError" class="message error" style="display:none;"></div>

            <div class="field">
                <label>Tipo</label>
                <select id="financeiroTipo">
                    <option value="entrada">Entrada</option>
                    <option value="saida">Saída</option>
                </select>
            </div>
            <div class="field">
                <label>Categoria</label>
                <select id="financeiroCategoria"></select>
            </div>
            <div class="field">
                <label>Descrição</label>
                <input type="text" id="financeiroDescricao" maxlength="255">
            </div>
            <div class="field">
                <label>Valor (R$)</label>
                <input type="number" id="financeiroValor" min="0.01" step="0.01">
            </div>
            <div class="field">
                <label>Data da movimentação</label>
                <input type="date" id="financeiroData">
            </div>
            <div class="field">
                <label>Origem (opcional)</label>
                <select id="financeiroOrigem">
                    <option value="">—</option>
                    <option value="doacao">Doação</option>
                    <option value="patrocinio">Patrocínio</option>
                    <option value="evento">Evento</option>
                    <option value="outro">Outro</option>
                </select>
            </div>
        </div>

        <div class="edit-panel-footer">
            <button class="btn-secondary" data-close="financeiro">Cancelar</button>
            <button class="btn-primary" id="salvarFinanceiroBtn">Salvar movimentação</button>
        </div>
    </aside>
</div>

<!-- ===================== MODAL: ARQUIVAR MOVIMENTAÇÃO FINANCEIRA ===================== -->
<div class="modal-overlay" id="deleteFinanceiroModalOverlay">
    <div class="modal-backdrop" data-close="delete-financeiro"></div>
    <div class="delete-panel">
        <div class="delete-icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M14 10v6M14 19.5v.5"></path><path d="M12.3 3.8L2.6 20a2 2 0 001.7 3h19.4a2 2 0 001.7-3L15.7 3.8a2 2 0 00-3.4 0z"></path></svg>
        </div>
        <h3>Arquivar movimentação?</h3>
        <p>A movimentação <strong id="deleteFinanceiroDescricao"></strong> será arquivada e não aparecerá mais nas listagens.</p>
        <div class="delete-buttons">
            <button class="btn-secondary" data-close="delete-financeiro">Cancelar</button>
            <button class="btn-danger" id="confirmDeleteFinanceiroBtn">Sim, arquivar</button>
        </div>
    </div>
</div>

<!-- ===================== MODAL: CRIAR/EDITAR ORÇAMENTO ===================== -->
<div class="modal-overlay" id="orcamentoModalOverlay">
    <div class="modal-backdrop" data-close="orcamento"></div>
    <aside class="edit-panel">
        <div class="edit-panel-header">
            <div>
                <h2 id="orcamentoModalTitulo">Novo orçamento</h2>
                <p id="orcamentoModalSubtitulo">Preencha os dados do orçamento</p>
            </div>
            <button class="icon-btn" data-close="orcamento">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l10 10M13 3L3 13"></path></svg>
            </button>
        </div>

        <div class="edit-panel-body">
            <div id="orcamentoFormError" class="message error" style="display:none;"></div>

            <div class="field">
                <label>Nome</label>
                <input type="text" id="orcamentoNome" maxlength="150">
            </div>
            <div class="field">
                <label>Ano de referência</label>
                <input type="number" id="orcamentoAno" min="1901" max="2155">
            </div>
            <div class="field">
                <label>Status</label>
                <select id="orcamentoStatus">
                    <option value="planejado">Planejado</option>
                    <option value="aprovado">Aprovado</option>
                    <option value="encerrado">Encerrado</option>
                </select>
            </div>
            <div class="field">
                <label>Descrição (opcional)</label>
                <textarea id="orcamentoDescricao" rows="3"></textarea>
            </div>
            <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.5rem;">O valor total é calculado automaticamente pela soma dos itens.</p>
        </div>

        <div class="edit-panel-footer">
            <button class="btn-secondary" data-close="orcamento">Cancelar</button>
            <button class="btn-primary" id="salvarOrcamentoBtn">Salvar orçamento</button>
        </div>
    </aside>
</div>

<!-- ===================== MODAL: ITENS DO ORÇAMENTO ===================== -->
<div class="modal-overlay" id="orcamentoItensModalOverlay">
    <div class="modal-backdrop" data-close="orcamento-itens"></div>
    <aside class="edit-panel edit-panel-wide">
        <div class="edit-panel-header">
            <div>
                <h2 id="orcamentoItensTitulo">Orçamento</h2>
                <p id="orcamentoItensSubtitulo"></p>
            </div>
            <button class="icon-btn" data-close="orcamento-itens">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l10 10M13 3L3 13"></path></svg>
            </button>
        </div>

        <div class="edit-panel-body">
            <div id="orcamentoItensErro" class="message error" style="display:none;"></div>

            <div class="orcamento-resumo">
                <div class="orcamento-resumo-item"><span>Total do orçamento</span><strong id="orcResumoTotal">R$ 0,00</strong></div>
                <div class="orcamento-resumo-item"><span>Soma dos itens</span><strong id="orcResumoSoma">R$ 0,00</strong></div>
                <div class="orcamento-resumo-item"><span>Realizado</span><strong id="orcResumoRealizado">R$ 0,00</strong></div>
                <div class="orcamento-resumo-item"><span>Itens</span><strong id="orcResumoQtd">0</strong></div>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Descrição</th>
                            <th>Previsto</th>
                            <th>Realizado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="orcamentoItensBody">
                    </tbody>
                </table>
            </div>

            <h3 class="orcamento-item-form-titulo" id="orcamentoItemFormTitulo">Adicionar item</h3>
            <div class="orcamento-item-form">
                <div class="field">
                    <label>Categoria</label>
                    <select id="orcamentoItemCategoria"></select>
                </div>
                <div class="field">
                    <label>Descrição</label>
                    <input type="text" id="orcamentoItemDescricao" maxlength="255">
                </div>
                <div class="field">
                    <label>Previsto (R$)</label>
                    <input type="number" id="orcamentoItemPrevisto" min="0.01" step="0.01">
                </div>
                <div class="field">
                    <label>Realizado (R$)</label>
                    <input type="number" id="orcamentoItemRealizado" min="0" step="0.01">
                </div>
            </div>
            <div class="orcamento-item-form-actions">
                <button class="btn-primary" id="salvarOrcamentoItemBtn" type="button">Adicionar item</button>
                <button class="btn-secondary" id="cancelarEdicaoItemBtn" type="button" style="display:none;">Cancelar edição</button>
            </div>
        </div>

        <div class="edit-panel-footer">
            <button class="btn-secondary" data-close="orcamento-itens">Fechar</button>
        </div>
    </aside>
</div>

<!-- ===================== MODAL: ARQUIVAR ORÇAMENTO ===================== -->
<div class="modal-overlay" id="deleteOrcamentoModalOverlay">
    <div class="modal-backdrop" data-close="delete-orcamento"></div>
    <div class="delete-panel">
        <div class="delete-icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M14 10v6M14 19.5v.5"></path><path d="M12.3 3.8L2.6 20a2 2 0 001.7 3h19.4a2 2 0 001.7-3L15.7 3.8a2 2 0 00-3.4 0z"></path></svg>
        </div>
        <h3>Arquivar orçamento?</h3>
        <p>O orçamento <strong id="deleteOrcamentoNome"></strong> será arquivado e não aparecerá mais na listagem.</p>
        <div class="delete-buttons">
            <button class="btn-secondary" data-close="delete-orcamento">Cancelar</button>
            <button class="btn-danger" id="confirmDeleteOrcamentoBtn">Sim, arquivar</button>
        </div>
    </div>
</div>

<!-- ===================== MODAL: REMOVER ITEM DO ORÇAMENTO ===================== -->
<div class="modal-overlay" id="deleteOrcamentoItemModalOverlay">
    <div class="modal-backdrop" data-close="delete-orcamento-item"></div>
    <div class="delete-panel">
        <div class="delete-icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M14 10v6M14 19.5v.5"></path><path d="M12.3 3.8L2.6 20a2 2 0 001.7 3h19.4a2 2 0 001.7-3L15.7 3.8a2 2 0 00-3.4 0z"></path></svg>
        </div>
        <h3>Remover item?</h3>
        <p>O item <strong id="deleteOrcamentoItemDescricao"></strong> será removido do orçamento e o total será recalculado. Esta ação não pode ser desfeita.</p>
        <div class="delete-buttons">
            <button class="btn-secondary" data-close="delete-orcamento-item">Cancelar</button>
            <button class="btn-danger" id="confirmDeleteOrcamentoItemBtn">Sim, remover</button>
        </div>
    </div>
</div>

<script src="js/api.js?v=<?php echo @filemtime(__DIR__ . '/js/api.js'); ?>"></script>
<script src="js/notificacoes.js?v=<?php echo @filemtime(__DIR__ . '/js/notificacoes.js'); ?>"></script>
<script src="js/dashboard.js?v=<?php echo @filemtime(__DIR__ . '/js/dashboard.js'); ?>"></script>
</body>
</html>
