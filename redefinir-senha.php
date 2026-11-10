<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Redefinir Senha - Grêmio ETEC de Poá</title>

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

            <h1>Defina uma<br><strong>nova senha</strong></h1>

            <p id="intro">Verificando o link...</p>

            <form id="formRedefinir" novalidate style="display:none;">

                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="novaSenha" placeholder="Nova senha (mín. 8 caracteres)"
                        required autocomplete="new-password">
                </div>

                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="confirmaSenha" placeholder="Confirmar nova senha"
                        required autocomplete="new-password">
                </div>

                <button type="submit" id="btnRedefinir">Redefinir senha</button>
            </form>

            <p id="msg" class="form-msg" role="status"></p>

            <div class="bottom-link">
                <a href="esqueci-minha-senha.php">Pedir um novo link</a> ·
                <a href="login.php">Voltar para Login</a>
            </div>

        </div>

    </main>

    <footer>
        © 2026 Grêmio Estudantil da ETEC de Poá
    </footer>

    <script src="js/api.js?v=<?php echo @filemtime(__DIR__ . '/js/api.js'); ?>"></script>
    <script>
        const params = new URLSearchParams(window.location.search);
        const token = params.get('token') || '';
        const intro = document.getElementById('intro');
        const form = document.getElementById('formRedefinir');
        const msg = document.getElementById('msg');
        const btn = document.getElementById('btnRedefinir');

        function mostrar(texto, tipo) {
            msg.textContent = texto;
            msg.className = 'form-msg ' + tipo;
            msg.style.display = 'block';
        }

        (async function validar() {
            try {
                const dados = await apiValidarTokenRecuperacao(token);
                if (dados.success) {
                    intro.textContent = 'Escolha uma nova senha para a sua conta.';
                    form.style.display = 'block';
                } else {
                    intro.textContent = '';
                    mostrar(dados.message || 'Link inválido ou expirado.', 'erro');
                }
            } catch (err) {
                intro.textContent = '';
                mostrar('Não foi possível conectar ao servidor.', 'erro');
            }
        })();

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const nova = document.getElementById('novaSenha').value;
            const conf = document.getElementById('confirmaSenha').value;

            if (nova.length < 8) { mostrar('A senha deve ter pelo menos 8 caracteres.', 'erro'); return; }
            if (nova !== conf) { mostrar('A confirmação não confere com a nova senha.', 'erro'); return; }

            btn.disabled = true;
            try {
                const dados = await apiRedefinirSenha(token, nova);
                if (dados.success) {
                    form.style.display = 'none';
                    intro.textContent = '';
                    mostrar(dados.message, 'ok');
                    setTimeout(() => { window.location.href = 'login.php'; }, 2500);
                    return;
                }
                mostrar(dados.message || 'Não foi possível redefinir a senha.', 'erro');
            } catch (err) {
                mostrar('Não foi possível conectar ao servidor.', 'erro');
            }
            btn.disabled = false;
        });
    </script>
</body>
</html>
