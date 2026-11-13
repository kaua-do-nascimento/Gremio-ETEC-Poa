
const authContainer =
    document.getElementById("authContainer");

const abrirCadastro =
    document.getElementById("abrirCadastro");

const voltarLogin =
    document.getElementById("voltarLogin");


abrirCadastro.addEventListener("click", () => {

    authContainer.classList.add("cadastro-ativo");

});


voltarLogin.addEventListener("click", () => {

    authContainer.classList.remove("cadastro-ativo");

});


// Permite abrir a página diretamente no cadastro
if (new URLSearchParams(window.location.search).get("cadastro") === "1") {
    authContainer.classList.add("cadastro-ativo");
}


/* LOGIN */

const formLogin =
    document.getElementById("formLogin");

const mensagemErroLogin =
    document.getElementById("mensagemErroLogin");

const botaoEntrar =
    document.getElementById("botaoEntrar");


formLogin.addEventListener(
    "submit",

    async (evento) => {

        evento.preventDefault();

        mensagemErroLogin.style.display = "none";

        botaoEntrar.disabled = true;
        botaoEntrar.textContent = "Entrando...";


        const login =
            document.getElementById("loginInput").value.trim();

        const senha =
            document.getElementById("senhaLoginInput").value;


        try {

            const resultado =
                await apiLogin(login, senha);


            if (resultado.success) {

                window.location.href = "index.php";

            } else {

                mensagemErroLogin.textContent =
                    resultado.message || "Não foi possível entrar.";

                mensagemErroLogin.style.display = "block";

            }

        } catch (erro) {

            mensagemErroLogin.textContent =
                "Não foi possível conectar ao servidor (" + erro.message + "). " +
                "Confira se o WAMP está com o ícone verde e se o mod_rewrite está ativo.";

            mensagemErroLogin.style.display = "block";

        } finally {

            botaoEntrar.disabled = false;
            botaoEntrar.textContent = "Entrar";

        }

    }
);


/* CADASTRO */

const formCadastro =
    document.getElementById("formCadastro");

const mensagemErroCadastro =
    document.getElementById("mensagemErroCadastro");

const mensagemSucesso =
    document.getElementById("mensagemSucesso");

const botaoCriar =
    document.getElementById("botaoCriar");

const aceitarTermos =
    document.getElementById("aceitarTermos");


// Botão começa desabilitado
botaoCriar.disabled = !aceitarTermos.checked;


// Habilita ou desabilita conforme a aceitação
aceitarTermos.addEventListener("change", () => {

    botaoCriar.disabled = !aceitarTermos.checked;

});


formCadastro.addEventListener(
    "submit",

    async (evento) => {

        evento.preventDefault();

        mensagemErroCadastro.style.display = "none";
        mensagemSucesso.style.display = "none";


        // Verifica se os termos foram aceitos
        if (!aceitarTermos.checked) {

            mensagemErroCadastro.textContent =
                "Você precisa aceitar os Termos de Uso e a Política de Privacidade.";

            mensagemErroCadastro.style.display = "block";

            return;
        }


        const nome =
            document.getElementById("nomeInput").value.trim();

        const email =
            document.getElementById("emailInput").value.trim();

        const rm =
            document.getElementById("rmInput").value.trim();

        const senha =
            document.getElementById("senhaCadastroInput").value;

        const confirmarSenha =
            document.getElementById("confirmarSenhaInput").value;


        if (!/^\d{5}$/.test(rm)) {

            mensagemErroCadastro.textContent =
                "O RM deve possuir exatamente 5 dígitos.";

            mensagemErroCadastro.style.display = "block";

            return;
        }


        if (senha !== confirmarSenha) {

            mensagemErroCadastro.textContent =
                "As senhas não coincidem.";

            mensagemErroCadastro.style.display = "block";

            return;
        }


        botaoCriar.disabled = true;
        botaoCriar.textContent = "Criando...";


        const dadosCadastro = {

            nome_completo: nome,
            email: email,
            rm: rm,
            senha: senha

        };


        try {

            const resultado =
                await apiCadastro(dadosCadastro);


            if (resultado.success) {

                mensagemSucesso.textContent =
                    resultado.message ||
                    "Cadastro realizado com sucesso. Aguarde a aprovação.";

                mensagemSucesso.style.display = "block";

                formCadastro.reset();

            } else {

                mensagemErroCadastro.textContent =
                    resultado.message ||
                    "Não foi possível realizar o cadastro.";

                mensagemErroCadastro.style.display = "block";

            }

        } catch (erro) {

            mensagemErroCadastro.textContent =
                "Não foi possível conectar ao servidor (" + erro.message + "). " +
                "Confira se o WAMP está com o ícone verde e se o mod_rewrite está ativo.";

            mensagemErroCadastro.style.display = "block";

        } finally {

            botaoCriar.disabled = !aceitarTermos.checked;
            botaoCriar.textContent = "Criar minha conta";

        }

    }

);
