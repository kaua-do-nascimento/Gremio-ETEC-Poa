<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" href="imgs/gremio favicon.png">

    <title>Configurações · Grêmio</title>

    <link rel="stylesheet" href="css/style.css?v=<?php echo @filemtime(__DIR__ . '/css/style.css'); ?>">
    <link rel="stylesheet" href="css/configuracoes.css?v=<?php echo @filemtime(__DIR__ . '/css/configuracoes.css'); ?>">
</head>

<body>

    <header class="topbar">
        <div class="topbar-inner">

            <a href="index.php" class="brand">
                <span class="brand-name">Grêmio</span>
            </a>
        </div>
</header>
    <main class="settings-main">

        <div class="settings-heading">
            <p class="settings-eyebrow">CONTA · CONFIGURAÇÕES</p>
            <h1 class="settings-title">Seu espaço, suas regras.</h1>
        </div>

        <div class="settings-layout">

            <aside class="settings-sidebar">

                <div class="settings-sidebar-head">
                    <h2>Configurações</h2>
                    <p>Controle sua experiência</p>
                </div>

                <nav class="settings-nav" id="settingsNav">

                    <button class="settings-nav-item active" data-panel="conta">
                        <span class="settings-nav-icon">◉</span>
                        Sua conta
                    </button>

                    <button class="settings-nav-item" data-panel="senha">
                        <span class="settings-nav-icon">⌁</span>
                        Senha e autenticação
                    </button>

                    <button class="settings-nav-item" data-panel="acessibilidade">
                        <span class="settings-nav-icon">◈</span>
                        Acessibilidade
                    </button>

                    <button class="settings-nav-item" data-panel="seguranca">
                        <span class="settings-nav-icon">◇</span>
                        Segurança
                    </button>

                </nav>

               <br><a href="index.php" class="settings-logout">
    <span class="settings-nav-icon">↪</span>
    Sair
</a>
            </aside>

            <section class="settings-content">

                <div class="settings-breadcrumb">
                    <span>Configurações</span>
                    <span>›</span>
                    <span id="breadcrumbActive">Sua conta</span>
                </div>

                <div id="panelsContainer"></div>

            </section>

        </div>

    </main>

    <script src="js/api.js?v=<?php echo @filemtime(__DIR__ . '/js/api.js'); ?>"></script>

    <script>
        function definirIniciais(nome) {
            const partes = String(nome || '').trim().split(/\s+/).filter(Boolean);
            const iniciais = (
                (partes[0] || 'G')[0] +
                (partes.length > 1 ? partes[partes.length - 1][0] : '')
            ).toUpperCase();

            const avatar = document.getElementById('avatarIniciais');

            if (avatar) avatar.textContent = iniciais;
        }

        (async function () {
            const sessao = exigirLogin();

            if (!sessao) return;

            const nome = document.getElementById('nomeUsuarioLogado');

            if (sessao.usuario?.nome_completo) {
                nome.textContent = sessao.usuario.nome_completo;
                definirIniciais(sessao.usuario.nome_completo);
            }

            const { ok, dados } = await apiMe();

            if (ok && dados.success) {
                nome.textContent = dados.usuario.nome_completo;
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