<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Login - Grêmio ETEC de Poá</title>

    <link rel="icon"
        href="imgs/gremio icon.png">

    <link rel="stylesheet"
        href="css/login.css?v=<?php echo @filemtime(__DIR__ . '/css/login.css'); ?>">

    

    <link rel="preconnect"
        href="https://fonts.googleapis.com">

    <link rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Outfit:wght@300;400;500;600;700&family=Permanent+Marker&display=swap"
        rel="stylesheet">

</head>

<body>

    <div class="auth-container"
        id="authContainer">

        <div class="auth-track">


            <!-- LOGIN -->

            <section class="panel form-panel login-panel">

                <div class="form-box">

                    <h1>GRÊMIO</h1>


                    <div id="mensagemErroLogin"
                        class="message error">
                    </div>


                    <form id="formLogin">


                        <div class="field">

                            <label>
                                E-MAIL OU RM
                            </label>


                            <div class="input-box">

                                <i class="fa-regular fa-id-card"></i>

                                <input
                                    type="text"
                                    id="loginInput"
                                    placeholder="RM ou e-mail institucional"
                                    required>

                            </div>

                        </div>


                        <div class="field">

                            <label>
                                SENHA
                            </label>


                            <div class="input-box">

                                <i class="fa-solid fa-lock"></i>

                                <input
                                    type="password"
                                    id="senhaLoginInput"
                                    placeholder="Senha"
                                    required>

                            </div>

                        </div>


                        <a class="forgot"
                            href="esqueci-minha-senha.php">

                            Esqueceu a senha?

                        </a>


                        <button
                            type="submit"
                            class="primary-button"
                            id="botaoEntrar">

                            Entrar

                        </button>

                    </form>


                    <div class="divider"></div>


                    <p class="switch-text">

                        Ainda não tem uma conta?

                    </p>


                    <button
                        type="button"
                        class="secondary-button"
                        id="abrirCadastro">

                        Cadastrar

                    </button>

                </div>

            </section>


            <!-- PAINEL VERDE LOGIN -->

            <section class="panel green-panel">

                <div class="green-shape right-shape"></div>


                <div class="green-content">

                    <div class="welcome">

                        BEM-VINDO!

                    </div>


                    <img
                        src="imgs/gremio favicon.png"
                        class="logo-image"
                        alt="Logo do Grêmio">

                </div>

            </section>


            <!-- PAINEL VERDE CADASTRO -->

            <section class="panel green-panel">

                <div class="green-shape left-shape"></div>


                <div class="green-content">

                    <div class="welcome">

                        FAÇA PARTE!

                    </div>


                    <img
                        src="imgs/gremio favicon.png"
                        class="logo-image"
                        alt="Logo do Grêmio">

                </div>

            </section>


            <!-- CADASTRO -->

            <section class="panel form-panel cadastro-panel">

                <div class="form-box">

                    <h1>GRÊMIO</h1>


                    <div id="mensagemErroCadastro"
                        class="message error">
                    </div>


                    <div id="mensagemSucesso"
                        class="message success">
                    </div>


                    <form id="formCadastro">


                        <div class="field">

                            <label>
                                NOME COMPLETO
                            </label>


                            <div class="input-box">

                                <i class="fa-regular fa-user"></i>

                                <input
                                    type="text"
                                    id="nomeInput"
                                    placeholder="Nome completo"
                                    required>

                            </div>

                        </div>


                        <div class="field">

                            <label>
                                E-MAIL INSTITUCIONAL
                            </label>


                            <div class="input-box">

                                <i class="fa-regular fa-envelope"></i>

                                <input
                                    type="email"
                                    id="emailInput"
                                    placeholder="E-mail institucional"
                                    required>

                            </div>

                        </div>


                        <div class="field">

                            <label>
                                RM
                            </label>


                            <div class="input-box">

                                <i class="fa-regular fa-id-card"></i>

                                <input
                                    type="text"
                                    id="rmInput"
                                    placeholder="RM (5 dígitos)"
                                    maxlength="5"
                                    inputmode="numeric"
                                    required>

                            </div>

                        </div>


                        <div class="field">

                            <label>
                                SENHA
                            </label>


                            <div class="input-box">

                                <i class="fa-solid fa-lock"></i>

                                <input
                                    type="password"
                                    id="senhaCadastroInput"
                                    placeholder="Mínimo 8 caracteres"
                                    minlength="8"
                                    required>

                            </div>

                        </div>


                        <div class="field">

                            <label>
                                CONFIRMAR SENHA
                            </label>


                            <div class="input-box">

                                <i class="fa-solid fa-lock"></i>

                                <input
                                    type="password"
                                    id="confirmarSenhaInput"
                                    placeholder="Confirme sua senha"
                                    required>

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="primary-button"
                            id="botaoCriar">

                            Criar minha conta

                        </button>

                    </form>


                    <div class="divider"></div>


                    <p class="switch-text">

                        Já possui uma conta?

                    </p>


                    <button
                        type="button"
                        class="secondary-button"
                        id="voltarLogin">

                        Fazer Login

                    </button>

                </div>

            </section>


        </div>

    </div>


    <script src="js/api.js?v=<?php echo @filemtime(__DIR__ . '/js/api.js'); ?>"></script>

    <script src="js/login-cadastro.js?v=<?php echo @filemtime(__DIR__ . '/js/login-cadastro.js'); ?>"></script>

</body>

</html>