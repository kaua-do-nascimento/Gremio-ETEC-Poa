<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grêmio</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo @filemtime(__DIR__ . '/css/style.css'); ?>">
    <link rel="icon" href="imgs/gremio favicon.png">
</head>

<body>
<div class="min-h-screen">

<header class="topbar">
    <div class="topbar-inner">

        <div class="brand">
            <span class="brand-mark">▦</span>
            <span class="brand-name font-serif">Grêmio</span>
        </div>

        <nav class="topbar-nav" id="topNav">
            <button class="nav-pill active">Início</button>
            <button class="nav-pill">Popular</button>
            <button class="nav-pill">Minhas comunidades</button>
        </nav>

        <div class="topbar-actions" id="topbarActions">

            <button id="searchTriggerDesktop" class="search-trigger">
                🔍 Buscar no Grêmio
            </button>

            <form id="searchForm" class="search-form" style="display:none;">
                <input id="searchInput" class="search-input"
                    type="text" placeholder="Buscar no Grêmio">
                <button type="button" id="closeSearchBtn"
                    class="btn btn-ghost btn-icon">✕</button>
            </form>

            <button id="searchTriggerMobile"
                class="btn btn-ghost btn-icon" style="display:none;">🔍</button>

            <button id="createBtn"
                class="btn btn-default btn-sm btn-rounded-full"
                style="display:none;">+ Criar</button>

            <div class="dropdown" style="position:relative;">
                <button class="btn btn-ghost btn-icon" id="btnNotificacoes" style="position:relative;">
                    🔔<span class="notif-badge" id="notificacoesBadge" style="display:none;"></span>
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

            <div class="dropdown" id="userDropdown">

                <button class="btn avatar-trigger" id="userDropdownTrigger">
                    <span class="avatar">
                        <img src="imgs/gremio icon.png" alt="Perfil">
                    </span>
                </button>

                <div class="dropdown-menu">
                    <div class="dropdown-label" id="nomeUsuarioLogado">Carregando...</div>
                    <div class="dropdown-sep"></div>

                    <button class="dropdown-item">👤 Meu perfil</button>
                    <button class="dropdown-item">🧭 Explorar</button>

                    <a href="configuracoes.php" class="dropdown-item">
                        ⚙ Configurações
                    </a>

                    <a href="dashboard.php" id="linkDashboardAdmin" class="dropdown-item" style="display:none;">
                        🛠 Painel administrativo
                    </a>

                  <a href="Home.php" id="linkSair" class="dropdown-item">
                        Sair
                    </a>

            </div>
        </div>
    </div>
</header>


<main class="app-main">
<div class="app-grid">

<section class="feed-section">
<div class="feed-stack">

    <div class="tabs-list" id="tabsList">
        <button class="tab-trigger active" data-tab="para-voce">Para você</button>
        <button class="tab-trigger" data-tab="seguindo">Seguindo</button>
        <button class="tab-trigger" data-tab="salvos">Salvos</button>
    </div>

    <div class="card" id="composerCard" style="display:none;">
        <div class="create-post-body">

            <div class="create-post-row">
                <span class="avatar avatar-md">
                    <img src="imgs/gremio icon.png" alt="Você">
                </span>

                <button id="createPostTrigger" class="create-post-trigger">
                    Compartilhe algo com a comunidade...
                </button>

                <button id="createPostChevron"
                    class="btn btn-ghost btn-icon chevron-toggle">▼</button>
            </div>

            <div id="createPostContent" class="create-post-content">

                <hr class="separator">

                <div id="createPostError" class="message error" style="display:none;"></div>

                <input id="postTitleInput" class="field field-title"
                    type="text" placeholder="Título do post">

                <textarea id="postContentInput" class="field field-textarea"
                    rows="4" placeholder="Escreva seu post aqui..."></textarea>

                <select id="postCategoriaSelect" class="field field-title">
                    <option value="">Selecione uma categoria...</option>
                </select>

                <div class="tags-row" id="tagsRow" style="display:none;">
                    <span>#</span>
                    <input id="tagInput" class="tag-input"
                        type="text" placeholder="Adicionar tag...">
                </div>

                <div class="create-post-actions">
                    <button class="btn btn-ghost btn-sm">🖼 Imagem</button>

                    <div class="create-post-actions-right">
                        <button id="cancelPostBtn" class="btn btn-ghost btn-sm">
                            Cancelar
                        </button>

                        <button id="publishBtn"
                            class="btn btn-default btn-sm" disabled>
                            Publicar
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="postsContainer" class="posts-list"></div>

    <div id="emptyState" class="empty-state" style="display:none;"></div>

</div>
</section>


<aside class="sidebar-aside">
<div class="sidebar-sticky">
<div class="sidebar-stack">

    <div class="card">
        <div class="side-card-header">
            <div class="side-card-title">
                📈 <span class="font-serif">Em alta</span>
            </div>
        </div>

        <div class="side-card-body" id="trendingList"></div>
    </div>

    <hr class="sep-strong">

    <div class="card" id="votacoesCard">
        <div class="side-card-header">
            <div class="side-card-title">
                🗳️ <span class="font-serif">Votações ativas</span>
            </div>
        </div>

        <div class="side-card-body" id="votacoesAtivasContainer">
            <p class="post-loading">Carregando votações...</p>
        </div>
    </div>

    <hr class="sep-strong">

    <div class="card agenda-card">

        <div class="agenda-header">
            <div class="agenda-title-row">
                📅 <span class="font-serif">Agenda da comunidade</span>
            </div>

            <p class="agenda-subtitle">Agosto de 2026</p>
        </div>

        <div class="calendar-wrap">

            <div class="cal-nav">
                <button id="calPrevBtn" type="button" class="cal-nav-btn">←</button>

                <span id="calCaption" class="cal-caption">
                    agosto 2026
                </span>

                <button id="calNextBtn" type="button" class="cal-nav-btn">→</button>
            </div>

            <div class="cal-grid" id="calHead"></div>
            <div class="cal-grid" id="calDays"></div>

        </div>

        <div class="agenda-footer">

            <div id="agendaEventosContainer">
                <p class="post-loading">Carregando eventos...</p>
            </div>

            <button class="agenda-link" id="verTodosEventosBtn">
                Ver todos os eventos →
            </button>

        </div>
    </div>


    <div class="card">
        <div class="activity-card-body">

            <div class="activity-title">
                ⭐ <span class="font-serif">Sua atividade</span>
            </div>

            <div id="activityGrid" class="activity-grid"></div>

        </div>
    </div>

</div>
</div>
</aside>

</div>
</main>

</div>

<script src="js/api.js?v=<?php echo @filemtime(__DIR__ . '/js/api.js'); ?>"></script>
<script>
    // ==============================================================
    // Conexão real com o backend: protege a página e carrega os
    // dados do usuário autenticado a partir do banco (GET /api/me).
    // ==============================================================
    (async function () {
        const sessao = exigirLogin();
        if (!sessao) return; // exigirLogin() já redireciona para login.html

        const nomeUsuarioLogado = document.getElementById('nomeUsuarioLogado');

        // Mostra imediatamente o nome salvo no login, sem esperar a rede
        if (sessao.usuario && sessao.usuario.nome_completo) {
            nomeUsuarioLogado.textContent = sessao.usuario.nome_completo;
        }

        // Confirma/atualiza os dados direto do banco de dados
        const { ok, dados } = await apiMe();

        if (ok && dados.success) {
            nomeUsuarioLogado.textContent = dados.usuario.nome_completo;

            if (dados.usuario.perfis && dados.usuario.perfis.includes('administrador')) {
                document.getElementById('linkDashboardAdmin').style.display = 'block';
            }
        } else {
            // Token inválido/expirado: volta para o login
            limparSessao();
            window.location.href = 'login.php';
        }
    })();

    document.getElementById('linkSair').addEventListener('click', async (evento) => {
        evento.preventDefault();
        await apiLogout();
        window.location.href = 'login.php';
    });
</script>

<script src="js/notificacoes.js?v=<?php echo @filemtime(__DIR__ . '/js/notificacoes.js'); ?>"></script>
<script src="js/script.js?v=<?php echo @filemtime(__DIR__ . '/js/script.js'); ?>"></script>
</body>
</html>