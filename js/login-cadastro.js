
/* ABRIR E FECHAR CADASTRO */

const authContainer = document.getElementById("authContainer");

const abrirCadastro = document.getElementById("abrirCadastro");

const voltarLogin = document.getElementById("voltarLogin");

abrirCadastro.addEventListener("click", () => {
    authContainer.classList.add("cadastro-ativo");
});

voltarLogin.addEventListener("click", () => {
    authContainer.classList.remove("cadastro-ativo");
});


/* ABRIR CADASTRO PELA URL */

if (new URLSearchParams(window.location.search).get("cadastro") === "1") {
    authContainer.classList.add("cadastro-ativo");
}


/* ACEITE DOS TERMOS */

const aceitarTermos = document.getElementById("aceitarTermos");

const botaoCriar = document.getElementById("botaoCriar");

aceitarTermos.addEventListener("change", function () {
    botaoCriar.disabled = !this.checked;
});


/* LOGIN */

const formLogin = document.getElementById("formLogin");

const mensagemErroLogin = document.getElementById("mensagemErroLogin");

const botaoEntrar = document.getElementById("botaoEntrar");

formLogin.addEventListener("submit", async (evento) => {

    evento.preventDefault();

    mensagemErroLogin.style.display = "none";

    botaoEntrar.disabled = true;
    botaoEntrar.textContent = "Entrando...";

    const login = document.getElementById("loginInput").value.trim();

    const senha = document.getElementById("senhaLoginInput").value;

    try {

        const resultado = await apiLogin(login, senha);

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

});


/* CADASTRO */

const formCadastro = document.getElementById("formCadastro");

const mensagemErroCadastro = document.getElementById("mensagemErroCadastro");

const mensagemSucesso = document.getElementById("mensagemSucesso");


formCadastro.addEventListener("submit", async (evento) => {

    evento.preventDefault();

    mensagemErroCadastro.style.display = "none";

    mensagemSucesso.style.display = "none";


    const nome = document.getElementById("nomeInput").value.trim();

    const email = document.getElementById("emailInput").value.trim();

    const rm = document.getElementById("rmInput").value.trim();

    const curso = document.getElementById("cursoInput").value;

    const senha = document.getElementById("senhaCadastroInput").value;

    const confirmarSenha = document.getElementById("confirmarSenhaInput").value;


    /* VERIFICAR ACEITE DOS TERMOS */

    if (!aceitarTermos.checked) {

        mensagemErroCadastro.textContent =
            "Você precisa aceitar os termos para continuar.";

        mensagemErroCadastro.style.display = "block";

        return;

    }


    /* VERIFICAR RM */

    if (!/^\d{5}$/.test(rm)) {

        mensagemErroCadastro.textContent =
            "O RM deve possuir exatamente 5 dígitos.";

        mensagemErroCadastro.style.display = "block";

        return;

    }


    /* VERIFICAR CURSO */

    if (!curso) {

        mensagemErroCadastro.textContent =
            "Selecione seu curso para continuar.";

        mensagemErroCadastro.style.display = "block";

        return;

    }


    /* VERIFICAR SENHAS */

    if (senha !== confirmarSenha) {

        mensagemErroCadastro.textContent =
            "As senhas não coincidem.";

        mensagemErroCadastro.style.display = "block";

        return;

    }


    /* ENVIAR CADASTRO */

    botaoCriar.disabled = true;

    botaoCriar.textContent = "Criando...";


    const dadosCadastro = {

        nome_completo: nome,

        email: email,

        rm: rm,

        curso: curso,

        senha: senha

    };


    try {

        const resultado = await apiCadastro(dadosCadastro);


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

        botaoCriar.textContent = "Criar minha conta";

        botaoCriar.disabled = !aceitarTermos.checked;

    }

});
