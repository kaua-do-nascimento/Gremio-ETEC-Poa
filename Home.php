<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Grêmio Estudantil - ETEC de Poá</title>

    <meta
        name="description"
        content="Este é o espaço oficial do Grêmio Estudantil da Etec de Poá.">

    <link rel="icon" href="imgs/gremio favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Caveat:wght@700&family=Nunito:wght@400;600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="css/Home.css?v=<?php echo @filemtime(__DIR__ . '/css/Home.css'); ?>">

</head>


<body>


    <!-- MENU -->

    <header class="menu" id="menu">

        <div class="logo">

            <img
                src="imgs/gremio icon.png"
                alt="Logo do Grêmio">

            <span>Grêmio</span>

        </div>


        <nav class="links-menu">

            <a href="#sobre">Sobre</a>

            <a href="#recursos">Recursos</a>

            <a href="#como-funciona">
                Como Funciona
            </a>

        </nav>


        <div class="botoes-menu">

            <a
                href="login.php"
                class="entrar">

                Entrar

            </a>


            <a
                href="login.php?cadastro=1"
                class="cadastrar">

                Cadastrar

            </a>

        </div>


        <!-- BOTÃO MOBILE -->

        <button
            class="menu-mobile"
            id="menuMobile"
            aria-label="Abrir menu">

            <span></span>
            <span></span>
            <span></span>

        </button>

    </header>


    <!-- MENU MOBILE -->

    <div
        class="mobile"
        id="mobile">

        <nav>

            <a href="#sobre">
                Sobre
            </a>

            <a href="#recursos">
                Recursos
            </a>

            <a href="#como-funciona">
                Como Funciona
            </a>

        </nav>


        <div class="botoes-mobile">

            <a
                href="login.php"
                class="entrar">

                Entrar

            </a>


            <a
                href="login.php?cadastro=1"
                class="cadastrar">

                Cadastrar

            </a>

        </div>

    </div>


    <!-- INÍCIO -->

    <section
        class="inicio"
        id="sobre">


        <!-- LADO ESQUERDO -->

        <div class="lado-esquerdo">


            <h1>

                <span class="titulo-branco">
                    Grêmio
                </span>

                <br>

                <span class="titulo-verde">
                    Estudantil
                </span>

            </h1>


            <p class="texto-inicio">

                Este é o espaço oficial do Grêmio Estudantil
                da Etec de Poá.

                <br><br>

                Aqui, os estudantes podem acompanhar informações
                sobre eventos, projetos e atividades realizadas
                na escola.

                <br><br>

                A plataforma foi criada para facilitar a comunicação
                e organizar as informações do Grêmio em um único ambiente.

            </p>


            <a
                href="login.php?cadastro=1"
                class="botao-principal">

                Fazer parte agora

            </a>


        </div>


        <!-- LADO DIREITO -->

        <div class="lado-direito">


            <!-- ONDA -->

            <div class="onda">

                <svg
                    viewBox="0 0 120 900"
                    preserveAspectRatio="none">

                    <path
                        d="M120 0 C80 80,110 160,70 260 C30 360,10 420,30 520 C50 620,90 700,60 800 C40 860,10 880,0 900 L120 900 Z"
                        fill="#0f0f0f">

                    </path>

                </svg>

            </div>


            <p class="bem-vindo">

                Bem vindo!

            </p>


            <!-- LOGO CENTRAL -->

            <img
                src="imgs/gremio favicon.png"
                alt="Logo do Grêmio"
                class="logo-central">


        </div>


    </section>


    <!-- RECURSOS -->

    <section
        class="recursos"
        id="recursos">


        <div class="conteudo-recursos">


            <div class="titulo-recursos">


                <div>

                    <p class="texto-pequeno">
                        O que você encontra aqui
                    </p>


                    <h2>

                        Tudo que o
                        <br>

                        grêmio precisa

                    </h2>

                </div>


                <p class="descricao-recursos">

                    Uma plataforma pensada para a realidade
                    estudantil, simples e direta.

                </p>


            </div>


            <!-- CARDS -->

            <div class="cards">


                <div class="card">

                    <div class="icone">
                        📢
                    </div>

                    <h3>
                        Mural de Avisos
                    </h3>

                    <p>

                        Receba comunicados da diretoria
                        e do grêmio em tempo real.

                    </p>

                </div>



                <div class="card">

                    <div class="icone">
                        🗳️
                    </div>

                    <h3>
                        Votações Digitais
                    </h3>

                    <p>

                        Participe de eleições, enquetes
                        e decisões coletivas.

                    </p>

                </div>



                <div class="card">

                    <div class="icone">
                        📅
                    </div>

                    <h3>
                        Agenda de Eventos
                    </h3>

                    <p>

                        Eventos, reuniões e atividades
                        organizadas em um só lugar.

                    </p>

                </div>



                <div class="card">

                    <div class="icone">
                        🏆
                    </div>

                    <h3>
                        Projetos

                    </h3>

                    <p>

                        Acompanhe os projetos e conquistas
                        realizadas pelo grêmio.

                    </p>

                </div>


            </div>


        </div>


    </section>


    <!-- COMO FUNCIONA -->

    <section
        class="como-funciona"
        id="como-funciona">


        <div class="conteudo-como-funciona">


            <p class="titulo-como-funciona">

                Como funciona?

            </p>


            <div class="passos">


                <!-- PASSO 1 -->

                <div class="passo">


                    <span class="numero">

                        01

                    </span>


                    <h3>

                        Crie sua conta

                    </h3>


                    <p>

                        Cadastre-se com o e-mail da escola
                        em menos de um minuto.

                    </p>


                </div>



                <!-- PASSO 2 -->

                <div class="passo">


                    <span class="numero">

                        02

                    </span>


                    <h3>

                        Conecte-se

                    </h3>


                    <p>

                        Encontre colegas e fique por dentro
                        das novidades.

                    </p>


                </div>



                <!-- PASSO 3 -->

                <div class="passo">


                    <span class="numero">

                        03

                    </span>


                    <h3>

                        Participe!

                    </h3>


                    <p>

                        Comente, compartilhe ideias e
                        participe das atividades.

                    </p>


                </div>


            </div>


        </div>


    </section>


    <!-- PARTE FINAL -->

    <section class="final">


        <div class="card-final">


            <p class="titulo-final">

                Pronto para participar?

            </p>


            <p class="texto-final">

                Cadastre-se agora e faça parte da comunidade
                do Grêmio Estudantil da ETEC de Poá.

            </p>


            <div class="botoes-final">


                <a
                    href="login.php?cadastro=1"
                    class="criar-conta">

                    Criar minha conta

                </a>


                <a
                    href="login.php"
                    class="ja-tenho-conta">

                    Já tenho conta →

                </a>


            </div>


        </div>


    </section>


    <!-- RODAPÉ -->

    <footer class="rodape">


        <div class="conteudo-rodape">


            <div class="logo-rodape">

               

            </div>


            <p>

               © 2026 Plataforma exclusiva para estudantes
                · Todos os direitos reservados 

            </p>


        </div>


    </footer>


    <!-- JAVASCRIPT -->

    <script>

        const botaoMenu =
            document.getElementById("menuMobile");

        const menuMobile =
            document.getElementById("mobile");


        botaoMenu.addEventListener(
            "click",

            () => {

                botaoMenu.classList.toggle("ativo");

                menuMobile.classList.toggle("ativo");

            }

        );


        document
            .querySelectorAll(".mobile a")

            .forEach(link => {

                link.addEventListener(
                    "click",

                    () => {

                        botaoMenu.classList.remove("ativo");

                        menuMobile.classList.remove("ativo");

                    }

                );

            });

    </script>


</body>

</html>
