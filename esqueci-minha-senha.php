<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Recuperar Senha - Grêmio ETEC de Poá</title>

    <link rel="icon" href="imgs/gremio icon.png">
    <link rel="stylesheet" href="css/esqueci-minha-senha.css?v=<?php echo @filemtime(__DIR__ . '/css/esqueci-minha-senha.css'); ?>">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .form-msg { margin: 12px 0 0; font-size: 14px; line-height: 1.4; display: none; word-break: break-word; }
        .form-msg.erro { color: #b3261e; }
        .form-msg.ok { color: #1b7a3d; }
        .link-demo { display: block; margin-top: 10px; padding: 10px 12px; border: 1px dashed #999; border-radius: 8px; font-size: 13px; word-break: break-all; }
        button[disabled] { opacity: .6; cursor: not-allowed; }
    </style>
</head>

<body>

    <header>
        <div class="logo-topo">▦</div>
        <span>Grêmio Estudantil </span>
    </header>

    <main class="auth-page">

        <div class="auth-card">

            <h1>Recupere sua<br><strong>senha</strong></h1>

            <p>
                Informe seu e-mail e RM para recuperar o acesso à sua conta.
            </p>

            <form id="formRecuperar" novalidate>

                <div class="input-box">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" id="email" name="email"
                        placeholder="E-mail institucional" required autocomplete="email">
                </div>

                <div class="input-box">
                    <i class="fa-regular fa-id-card"></i>
                    <input type="text" id="rm" name="rm" maxlength="5"
                        placeholder="RM" required autocomplete="username">
                </div>

                <button type="submit" id="btnRecuperar">
                    Recuperar senha
                </button>

                <p id="msg" class="form-msg" role="status"></p>
                <a id="linkDemo" class="link-demo" style="display:none;"></a>

            </form>

            <div class="bottom-link">
                Lembrou sua senha?
                <a href="login.php">Voltar para Login</a>
            </div>

        </div>

    </main>

    <footer>
        © 2026 Grêmio Estudantil da ETEC de Poá
    </footer>

    <script src="js/api.js?v=<?php echo @filemtime(__DIR__ . '/js/api.js'); ?>"></script>
    <script>
        const form = document.getElementById('formRecuperar');
        const msg = document.getElementById('msg');
        const btn = document.getElementById('btnRecuperar');
        const linkDemo = document.getElementById('linkDemo');

        function mostrar(texto, tipo) {
            msg.textContent = texto;
            msg.className = 'form-msg ' + tipo;
            msg.style.display = 'block';
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            linkDemo.style.display = 'none';
            const email = document.getElementById('email').value.trim();
            const rm = document.getElementById('rm').value.trim();

            if (!email || !rm) {
                mostrar('Preencha o e-mail e o RM.', 'erro');
                return;
            }

            btn.disabled = true;
            try {
                const dados = await apiSolicitarRecuperacao(email, rm);
                if (dados.success) {
                    mostrar(dados.message, 'ok');
                    if (dados.link_redefinicao) {
                        linkDemo.href = dados.link_redefinicao;
                        linkDemo.textContent = 'Abrir link de redefinição (modo demonstração): ' + dados.link_redefinicao;
                        linkDemo.style.display = 'block';
                    }
                } else {
                    mostrar(dados.message || 'Não foi possível processar o pedido.', 'erro');
                }
            } catch (err) {
                mostrar('Não foi possível conectar ao servidor. Verifique se o Apache e o MySQL estão ligados.', 'erro');
            }
            btn.disabled = false;
        });
    </script>
</body>
</html>
