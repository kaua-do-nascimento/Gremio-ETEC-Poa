document.addEventListener("DOMContentLoaded", () => {
    carregarPerfil();
});

async function carregarPerfil() {

    // Verifica se o usuário está logado
    const sessao = exigirLogin();

    if (!sessao) {
        return;
    }

    try {

        // Busca os dados do usuário pela API
        const { ok, dados } = await apiMe();

        if (!ok || !dados.success) {
            limparSessao();
            window.location.href = "login.php";
            return;
        }

        const usuario = dados.usuario;

        // ELEMENTOS DO PERFIL
        const foto = document.querySelector(".foto-perfil");
        const nome = document.querySelector(".informacoes-perfil h2");
        const curso = document.querySelector(".informacoes-perfil p");

        // INFORMAÇÕES PESSOAIS
        const campos = document.querySelectorAll(".item-informacao p");

        // FOTO
        if (foto) {
            foto.src = usuario.foto_url || "imgs/gremio icon.png";

            foto.onerror = () => {
                foto.src = "imgs/gremio icon.png";
            };
        }

        // NOME E CURSO DO CABEÇALHO
        if (nome) {
            nome.textContent = usuario.nome_completo || "Usuário";
        }

        if (curso) {
            curso.textContent = usuario.curso || "Curso não informado";
        }

        // DADOS PESSOAIS
        if (campos.length >= 4) {
            campos[0].textContent = usuario.nome_completo || "Não informado";
            campos[1].textContent = usuario.rm || "Não informado";
            campos[2].textContent = usuario.email || "Não informado";
            campos[3].textContent = usuario.curso || "Não informado";
        }

    } catch (erro) {
        console.error("Erro ao carregar perfil:", erro);
    }
}