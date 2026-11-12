<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" href="imgs/gremio favicon.png">
  <title>Configurações · Grêmio</title>
  <meta name="description" content="Gerencie sua conta, senha, acessibilidade e segurança no Grêmio." />
  <meta name="robots" content="noindex, nofollow" />
  <link rel="stylesheet" href="css/style.css?v=<?php echo @filemtime(__DIR__ . '/css/style.css'); ?>" />
  <link rel="stylesheet" href="css/configuracoes.css?v=<?php echo @filemtime(__DIR__ . '/css/configuracoes.css'); ?>" />
</head>
<body>
  <div class="min-h-screen">

    <!-- ===================== TOPBAR (igual às demais páginas) ===================== -->
    <header class="topbar">
      <div class="topbar-inner">
        <div class="brand">
          <a href="index.php" style="display:flex;align-items:center;gap:0.625rem;">
            <span class="brand-mark">
              <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </span>
            <span class="brand-name font-serif">Grêmio</span>
          </a>
        </div>

        <nav class="topbar-nav">
          <a class="nav-pill" href="index.php">Início</a>
          <a class="nav-pill" href="index.php">Popular</a>
          <a class="nav-pill" href="index.php">Minhas comunidades</a>
        </nav>

        <div class="topbar-actions">
          <div class="dropdown" id="userDropdown">
            <button class="btn avatar-trigger" id="userDropdownTrigger">
              <span class="avatar" id="avatarIniciais" style="display:flex;align-items:center;justify-content:center;font-weight:600;">GR</span>
            </button>
            <div class="dropdown-menu">
              <div class="dropdown-label" id="nomeUsuarioLogado">Marcos Alves</div>
              <div class="dropdown-sep"></div>
              <a class="dropdown-item" href="index.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Meu perfil
              </a>
              <a class="dropdown-item" href="index.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
                Explorar
              </a>
              <a class="dropdown-item active" href="configuracoes.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Configurações
              </a>
              <div class="dropdown-sep"></div>
              <a class="dropdown-item destructive" href="Home.php" id="linkSair">Sair</a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- ===================== MAIN ===================== -->
    <main class="settings-main">
      <div class="settings-heading">
        <div>
          <p class="settings-eyebrow">Conta · Configurações</p>
          <h1 class="font-serif settings-title">Seu espaço, suas regras.</h1>
        </div>
        
      </div>

      <div class="settings-layout">

        <!-- ===================== SIDEBAR DE CONFIGURAÇÕES ===================== -->
        <aside class="settings-sidebar card">
          <div class="settings-sidebar-head">
            <p class="settings-sidebar-title">Configurações</p>
            <p class="settings-sidebar-sub">Controle sua experiência</p>
          </div>
          <nav class="settings-nav" id="settingsNav" aria-label="Opções de configuração">
            <button class="settings-nav-item active" data-panel="conta">
              <span class="settings-nav-icon">◉</span>
              <span>Sua conta</span>
            </button>
            <button class="settings-nav-item" data-panel="senha">
              <span class="settings-nav-icon">⌁</span>
              <span>Senha e autenticação</span>
            </button>
            <button class="settings-nav-item" data-panel="acessibilidade">
              <span class="settings-nav-icon">◈</span>
              <span>Acessibilidade</span>
            </button>
            <button class="settings-nav-item" data-panel="seguranca">
              <span class="settings-nav-icon">◇</span>
              <span>Segurança</span>
            </button>
          </nav>
          <button class="settings-logout">
            <span class="settings-nav-icon">↪</span> Sair da conta
          </button>
        </aside>

        <!-- ===================== CONTEÚDO ===================== -->
        <section class="settings-content">
          <div class="settings-breadcrumb">
            <span>Configurações</span><span>›</span><span class="settings-breadcrumb-active" id="breadcrumbActive">Sua conta</span>
          </div>

          <div id="panelsContainer"></div>

        </section>

      </div>
    </main>
  </div>

  <script src="js/api.js?v=<?php echo @filemtime(__DIR__ . '/js/api.js'); ?>"></script>
  <script>
    // Protege a página e carrega o nome real do usuário logado (banco de dados)
    function definirIniciais(nome) {
        const partes = String(nome || '').trim().split(/\s+/).filter(Boolean);
        const ini = ((partes[0] || 'G')[0] + (partes.length > 1 ? partes[partes.length - 1][0] : '')).toUpperCase();
        const el = document.getElementById('avatarIniciais');
        if (el) el.textContent = ini;
    }

    (async function () {
        const sessao = exigirLogin();
        if (!sessao) return;

        const nomeUsuarioLogado = document.getElementById('nomeUsuarioLogado');

        if (sessao.usuario && sessao.usuario.nome_completo) {
            nomeUsuarioLogado.textContent = sessao.usuario.nome_completo;
            definirIniciais(sessao.usuario.nome_completo);
        }

        const { ok, dados } = await apiMe();

        if (ok && dados.success) {
            nomeUsuarioLogado.textContent = dados.usuario.nome_completo;
            definirIniciais(dados.usuario.nome_completo);
        } else {
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
  <script src="js/configuracoes.js?v=<?php echo @filemtime(__DIR__ . '/js/configuracoes.js'); ?>"></script>
</body>
</html>
