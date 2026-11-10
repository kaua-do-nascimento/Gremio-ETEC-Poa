// ============================================================
// dashboard.js — Painel administrativo de usuários do Grêmio
//
// Conectado ao banco de dados real através de:
//   GET  /api/admin/usuarios              -> lista todos
//   POST /api/admin/usuarios/{id}/atualizar -> edita nome/e-mail
//   POST /api/admin/usuarios/{id}/status    -> ativa/suspende
//   POST /api/admin/usuarios/{id}/excluir   -> exclui (soft delete)
//
// OBS: os campos "Turma" e "Telefone" não existem no banco de dados
// atual (tabela `usuarios`), então são exibidos como "-" e não são
// salvos ao editar. Isso pode ser adicionado futuramente criando as
// colunas correspondentes no banco.
// ============================================================

const PER_PAGE = 8;

let users = [];

const state = {
    activeTab: "todos",
    search: "",
    page: 1,
    editUser: null,
    deleteTarget: null,
};

const iconsEmBreve = {
    dashboard: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="2.5" y="9" width="3" height="6.5" rx="0.5"></rect><rect x="7.5" y="5" width="3" height="10.5" rx="0.5"></rect><rect x="12.5" y="2.5" width="3" height="13" rx="0.5"></rect></svg>',
    eventos: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="2.5" y="3.5" width="13" height="12" rx="2"></rect><path d="M2.5 7.5h13M6 2v3M12 2v3"></path></svg>',
    configuracoes: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="9" cy="9" r="2.2"></circle><path d="M9 2v2M9 14v2M2 9h2M14 9h2M3.6 3.6l1.4 1.4M13 13l1.4 1.4M3.6 14.4l1.4-1.4M13 5l1.4-1.4"></path></svg>',
};

const pageTitles = {
    dashboard: "Dashboard",
    usuarios: "Gerenciar Usuários",
    eventos: "Eventos",
    projetos: "Projetos",
    votacoes: "Votações",
    auditoria: "Auditoria",
    financeiro: "Financeiro",
    orcamentos: "Orçamentos",
    configuracoes: "Configurações",
};

// ============================================================
// PROTEÇÃO DA PÁGINA (somente administrador logado)
// ============================================================
(async function protegerPagina() {
    const sessao = exigirLogin();
    if (!sessao) return; // exigirLogin() já redireciona

    if (!sessao.perfis || !sessao.perfis.includes("administrador")) {
        window.location.href = "index.php";
        return;
    }

    document.querySelector(".sidebar-profile-name").textContent = sessao.usuario.nome_completo;
    document.querySelector(".sidebar-profile-email").textContent = sessao.usuario.email;

    await loadUsers();
})();

document.getElementById("linkSairDashboard")?.addEventListener("click", async (evento) => {
    evento.preventDefault();
    await apiLogout();
    window.location.href = "login.php";
});

// ============================================================
// CARREGAMENTO DOS USUÁRIOS REAIS (banco de dados)
// ============================================================
function calcularIniciais(nome) {
    const partes = nome.trim().split(/\s+/);
    const primeira = partes[0]?.[0] || "";
    const segunda = partes.length > 1 ? partes[partes.length - 1][0] : "";
    return (primeira + segunda).toUpperCase();
}

function formatarData(dataIso) {
    if (!dataIso) return "-";
    const data = new Date(dataIso.replace(" ", "T"));
    if (isNaN(data.getTime())) return "-";
    return data.toLocaleDateString("pt-BR");
}

function mapearUsuario(u) {
    return {
        id: u.id,
        name: u.nome_completo,
        matricula: u.rm,
        turma: "-",
        email: u.email,
        status: u.status, // "ativo" | "pendente" | "suspenso" (calculado pelo backend)
        initials: calcularIniciais(u.nome_completo),
        joined: formatarData(u.created_at),
        phone: "",
        role: u.perfis || "Sem perfil",
    };
}

async function loadUsers() {
    const tbody = document.getElementById("usersTableBody");
    tbody.innerHTML = `<tr class="empty-row"><td colspan="8">Carregando usuários...</td></tr>`;

    const { ok, dados } = await apiListarUsuarios();

    if (!ok || !dados.success) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="8">Não foi possível carregar os usuários. Verifique se o backend está rodando.</td></tr>`;
        return;
    }

    users = dados.usuarios.map(mapearUsuario);
    render();
}

// ============================================================
// NAVEGAÇÃO LATERAL
// ============================================================
document.querySelectorAll(".nav-item[data-page]").forEach((btn) => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".nav-item[data-page]").forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");

        const page = btn.dataset.page;

        // "Configurações" abre a página de conta (perfil, senha e preferências)
        if (page === "configuracoes") {
            window.location.href = "configuracoes.php";
            return;
        }

        document.getElementById("pageTitle").textContent = pageTitles[page];

        const usuariosSection = document.getElementById("page-usuarios");
        const eventosSection = document.getElementById("page-eventos");
        const projetosSection = document.getElementById("page-projetos");
        const votacoesSection = document.getElementById("page-votacoes");
        const auditoriaSection = document.getElementById("page-auditoria");
        const financeiroSection = document.getElementById("page-financeiro");
        const orcamentosSection = document.getElementById("page-orcamentos");
        const emBreveSection = document.getElementById("page-em-breve");
        const dashboardSection = document.getElementById("page-dashboard");
        const subtitle = document.getElementById("pageSubtitle");

        usuariosSection.style.display = "none";
        eventosSection.style.display = "none";
        projetosSection.style.display = "none";
        votacoesSection.style.display = "none";
        auditoriaSection.style.display = "none";
        financeiroSection.style.display = "none";
        orcamentosSection.style.display = "none";
        emBreveSection.style.display = "none";
        dashboardSection.style.display = "none";

        if (page === "dashboard") {
            dashboardSection.style.display = "block";
            subtitle.textContent = "Visão geral do grêmio";
            carregarResumoDashboard();
        } else if (page === "usuarios") {
            usuariosSection.style.display = "block";
            subtitle.textContent = `${users.length} usuários cadastrados no sistema`;
        } else if (page === "eventos") {
            eventosSection.style.display = "block";
            subtitle.textContent = `${eventos.length} eventos cadastrados no sistema`;
            if (!eventosJaCarregados) {
                loadEventos();
            }
        } else if (page === "projetos") {
            projetosSection.style.display = "block";
            subtitle.textContent = `${projetos.length} projetos cadastrados no sistema`;
            if (!projetosJaCarregados) {
                loadProjetos();
            }
        } else if (page === "votacoes") {
            votacoesSection.style.display = "block";
            subtitle.textContent = `${votacoes.length} votações cadastradas no sistema`;
            if (!votacoesJaCarregadas) {
                loadVotacoes();
            }
        } else if (page === "auditoria") {
            auditoriaSection.style.display = "block";
            subtitle.textContent = "Trilha de auditoria do sistema";
            if (!auditoriaJaCarregada) {
                initAuditoria();
            }
        } else if (page === "financeiro") {
            financeiroSection.style.display = "block";
            subtitle.textContent = "Movimentações financeiras do grêmio";
            if (!financeiroJaCarregado) {
                initFinanceiro();
            }
        } else if (page === "orcamentos") {
            orcamentosSection.style.display = "block";
            subtitle.textContent = "Orçamentos anuais do grêmio";
            if (!orcamentosJaCarregado) {
                initOrcamentos();
            }
        } else {
            emBreveSection.style.display = "flex";
            subtitle.textContent = "Grêmio Estudantil · ETEC de Poá";
            document.getElementById("placeholderIcon").innerHTML = iconsEmBreve[page] || "";
        }
    });
});

// ============================================================
// ABAS DE STATUS
// ============================================================
document.querySelectorAll(".tab").forEach((tab) => {
    tab.addEventListener("click", () => {
        document.querySelectorAll(".tab").forEach((t) => t.classList.remove("active"));
        tab.classList.add("active");
        state.activeTab = tab.dataset.tab;
        state.page = 1;
        render();
    });
});

// ============================================================
// BUSCA
// ============================================================
document.getElementById("searchInput").addEventListener("input", (e) => {
    state.search = e.target.value;
    state.page = 1;
    render();
});

// ============================================================
// FILTRAGEM
// ============================================================
function getFiltered() {
    const q = state.search.toLowerCase().trim();
    return users.filter((u) => {
        const tabMatch = state.activeTab === "todos" || u.status === state.activeTab;
        const searchMatch =
            !q ||
            u.name.toLowerCase().includes(q) ||
            u.matricula.includes(q) ||
            u.email.toLowerCase().includes(q);
        return tabMatch && searchMatch;
    });
}

function statusLabel(status) {
    return { ativo: "Ativo", suspenso: "Suspenso", pendente: "Pendente" }[status] || status;
}

function escapeHtml(str) {
    const div = document.createElement("div");
    div.textContent = str ?? "";
    return div.innerHTML;
}

// ============================================================
// RENDERIZAÇÃO PRINCIPAL
// ============================================================
function render() {
    const filtered = getFiltered();
    const totalPages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
    if (state.page > totalPages) state.page = totalPages;
    const rows = filtered.slice((state.page - 1) * PER_PAGE, state.page * PER_PAGE);

    const stats = {
        total: users.length,
        ativos: users.filter((u) => u.status === "ativo").length,
        suspensos: users.filter((u) => u.status === "suspenso").length,
        pendentes: users.filter((u) => u.status === "pendente").length,
    };
    document.getElementById("statTotal").textContent = stats.total;
    document.getElementById("statAtivos").textContent = stats.ativos;
    document.getElementById("statSuspensos").textContent = stats.suspensos;
    document.getElementById("statPendentes").textContent = stats.pendentes;

    document.getElementById("countTodos").textContent = users.length;
    document.getElementById("countAtivo").textContent = stats.ativos;
    document.getElementById("countSuspenso").textContent = stats.suspensos;
    document.getElementById("countPendente").textContent = stats.pendentes;

    document.getElementById("resultsCount").textContent =
        `${filtered.length} resultado${filtered.length !== 1 ? "s" : ""}`;

    const tbody = document.getElementById("usersTableBody");
    if (rows.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="8">Nenhum usuário encontrado.</td></tr>`;
    } else {
        tbody.innerHTML = rows
            .map((u, i) => {
                const num = (state.page - 1) * PER_PAGE + i + 1;
                const ehAdmin = /administrador/i.test(u.role || "");
                return `
                <tr data-id="${u.id}">
                    <td class="td-index">${num}</td>
                    <td>
                        <div class="td-user">
                            <div class="avatar">${escapeHtml(u.initials)}</div>
                            <div>
                                <p class="td-user-name">${escapeHtml(u.name)}</p>
                                <p class="td-user-email">${escapeHtml(u.email)}</p>
                            </div>
                        </div>
                    </td>
                    <td class="td-matricula">${escapeHtml(u.matricula)}</td>
                    <td><span class="turma-badge">${escapeHtml(u.turma)}</span></td>
                    <td class="td-role">${escapeHtml(u.role)}</td>
                    <td class="td-joined">${escapeHtml(u.joined)}</td>
                    <td><span class="status-badge status-${u.status}">${statusLabel(u.status)}</span></td>
                    <td>
                        <div class="row-actions">
                            <button class="row-action-btn edit-btn" data-action="edit" data-id="${u.id}" title="Editar perfil">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M9.5 1.5L12.5 4.5l-8.5 8.5L1 13l.5-2.5 8.5-8.5z"></path><path d="M7.5 3.5l3 3"></path></svg>
                            </button>
                            ${ehAdmin
                                ? `<button class="row-action-btn" disabled data-action="protegido" title="Administrador protegido: não pode ser excluído" style="opacity:.5;cursor:not-allowed;">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="6.5" width="9" height="6" rx="1.2"></rect><path d="M4.5 6.5V4.5a2.5 2.5 0 015 0v2"></path></svg>
                            </button>`
                                : `<button class="row-action-btn delete-btn" data-action="delete" data-id="${u.id}" title="Excluir perfil">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M1.5 3.5h11M4.5 3.5V2h5v1.5M5 3.5l.7 8h2.6l.7-8"></path></svg>
                            </button>`}
                        </div>
                    </td>
                </tr>`;
            })
            .join("");
    }

    const info = document.getElementById("paginationInfo");
    if (filtered.length === 0) {
        info.textContent = "0 de 0";
    } else {
        const start = (state.page - 1) * PER_PAGE + 1;
        const end = Math.min(state.page * PER_PAGE, filtered.length);
        info.textContent = `${start}–${end} de ${filtered.length}`;
    }

    const btnsWrap = document.getElementById("paginationButtons");
    let html = `<button class="page-btn" data-page="prev" ${state.page === 1 ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11L5 7l4-4"></path></svg>
    </button>`;
    for (let p = 1; p <= totalPages; p++) {
        html += `<button class="page-btn ${p === state.page ? "active" : ""}" data-page="${p}">${p}</button>`;
    }
    html += `<button class="page-btn" data-page="next" ${state.page === totalPages ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 11l4-4-4-4"></path></svg>
    </button>`;
    btnsWrap.innerHTML = html;

    btnsWrap.querySelectorAll(".page-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const val = btn.dataset.page;
            if (val === "prev") state.page = Math.max(1, state.page - 1);
            else if (val === "next") state.page = Math.min(totalPages, state.page + 1);
            else state.page = parseInt(val, 10);
            render();
        });
    });

    tbody.querySelectorAll("[data-action='edit']").forEach((btn) => {
        btn.addEventListener("click", () => openEditModal(parseInt(btn.dataset.id, 10)));
    });
    tbody.querySelectorAll("[data-action='delete']").forEach((btn) => {
        btn.addEventListener("click", () => openDeleteModal(parseInt(btn.dataset.id, 10)));
    });
}

// ============================================================
// MODAL: EDITAR
// ============================================================
const editModalOverlay = document.getElementById("editModalOverlay");
const editStatusChoice = document.getElementById("editStatusChoice");

function openEditModal(id) {
    const user = users.find((u) => u.id === id);
    if (!user) return;
    state.editUser = { ...user };

    document.getElementById("editMatricula").textContent = `#${user.matricula}`;
    document.getElementById("editAvatarPreview").textContent = user.initials;
    document.getElementById("editName").value = user.name;
    document.getElementById("editEmail").value = user.email;
    document.getElementById("editPhone").value = user.phone;
    document.getElementById("editPhone").placeholder = "Não disponível no banco de dados";
    document.getElementById("editRm").value = user.matricula;
    document.getElementById("editRm").disabled = true; // RM não é editável (chave única)
    document.getElementById("editTurma").value = user.turma;
    document.getElementById("editTurma").placeholder = "Não disponível no banco de dados";

    editStatusChoice.querySelectorAll("button").forEach((b) => {
        b.classList.toggle("selected", b.dataset.status === user.status);
        // Não é possível voltar manualmente para "pendente" — esse estado
        // só existe automaticamente entre o cadastro e a análise do admin.
        b.disabled = b.dataset.status === "pendente" && user.status !== "pendente";
        // Administradores são protegidos: não podem ser suspensos
        if (/administrador/i.test(user.role || "") && b.dataset.status !== "ativo") {
            b.disabled = true;
            b.title = "Administrador protegido: não pode ser suspenso";
        } else if (b.title && b.title.startsWith("Administrador protegido")) {
            b.title = "";
        }
    });

    limparErroEdicao();
    editModalOverlay.classList.add("open");
}

editStatusChoice.querySelectorAll("button").forEach((b) => {
    b.addEventListener("click", () => {
        if (b.disabled) return;
        editStatusChoice.querySelectorAll("button").forEach((x) => x.classList.remove("selected"));
        b.classList.add("selected");
        state.editUser.status = b.dataset.status;
    });
});

function mostrarErroEdicao(mensagem) {
    let el = document.getElementById("editErrorMessage");
    if (!el) {
        el = document.createElement("p");
        el.id = "editErrorMessage";
        el.style.color = "#ff6b60";
        el.style.fontSize = "13px";
        el.style.marginTop = "8px";
        document.querySelector(".edit-panel-body").appendChild(el);
    }
    el.textContent = mensagem;
}

function limparErroEdicao() {
    const el = document.getElementById("editErrorMessage");
    if (el) el.textContent = "";
}

document.getElementById("saveEditBtn").addEventListener("click", async () => {
    if (!state.editUser) return;

    limparErroEdicao();

    const nome = document.getElementById("editName").value.trim();
    const email = document.getElementById("editEmail").value.trim();
    const novoStatus = state.editUser.status;
    const statusMudou = novoStatus !== users.find((u) => u.id === state.editUser.id)?.status;

    const saveBtn = document.getElementById("saveEditBtn");
    saveBtn.disabled = true;
    saveBtn.textContent = "Salvando...";

    try {
        const resultadoDados = await apiAtualizarUsuario(state.editUser.id, nome, email);

        if (!resultadoDados.success) {
            mostrarErroEdicao(resultadoDados.message || "Não foi possível salvar os dados.");
            return;
        }

        if (statusMudou && novoStatus !== "pendente") {
            const resultadoStatus = await apiMudarStatusUsuario(state.editUser.id, novoStatus);
            if (!resultadoStatus.success) {
                mostrarErroEdicao(resultadoStatus.message || "Não foi possível mudar o status.");
                return;
            }
        }

        closeModal("edit");
        await loadUsers();

    } catch (erro) {
        mostrarErroEdicao("Não foi possível conectar ao servidor.");
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = "Salvar alterações";
    }
});

// ============================================================
// MODAL: EXCLUIR
// ============================================================
const deleteModalOverlay = document.getElementById("deleteModalOverlay");

function openDeleteModal(id) {
    const user = users.find((u) => u.id === id);
    if (!user) return;
    state.deleteTarget = user;
    document.getElementById("deleteUserName").textContent = user.name;
    deleteModalOverlay.classList.add("open");
}

document.getElementById("confirmDeleteBtn").addEventListener("click", async () => {
    if (!state.deleteTarget) return;

    const btn = document.getElementById("confirmDeleteBtn");
    btn.disabled = true;
    btn.textContent = "Excluindo...";

    try {
        const resultado = await apiExcluirUsuario(state.deleteTarget.id);

        if (!resultado.success) {
            alert(resultado.message || "Não foi possível excluir o usuário.");
            return;
        }

        state.deleteTarget = null;
        closeModal("delete");
        await loadUsers();

    } catch (erro) {
        alert("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Sim, excluir";
    }
});

// ============================================================
// FECHAR MODAIS
// ============================================================
function closeModal(which) {
    if (which === "edit") editModalOverlay.classList.remove("open");
    if (which === "delete") deleteModalOverlay.classList.remove("open");
    if (which === "evento") document.getElementById("eventoModalOverlay").classList.remove("open");
    if (which === "delete-evento") document.getElementById("deleteEventoModalOverlay").classList.remove("open");
    if (which === "participantes") document.getElementById("participantesModalOverlay").classList.remove("open");
    if (which === "projeto") document.getElementById("projetoModalOverlay").classList.remove("open");
    if (which === "delete-projeto") document.getElementById("deleteProjetoModalOverlay").classList.remove("open");
    if (which === "projeto-detalhe") document.getElementById("projetoDetalheModalOverlay").classList.remove("open");
    if (which === "votacao") document.getElementById("votacaoModalOverlay").classList.remove("open");
    if (which === "delete-votacao") document.getElementById("deleteVotacaoModalOverlay").classList.remove("open");
    if (which === "resultado") document.getElementById("resultadoModalOverlay").classList.remove("open");
    if (which === "log-detalhe") document.getElementById("logDetalheModalOverlay").classList.remove("open");
    if (which === "financeiro") document.getElementById("financeiroModalOverlay").classList.remove("open");
    if (which === "delete-financeiro") document.getElementById("deleteFinanceiroModalOverlay").classList.remove("open");
    if (which === "orcamento") document.getElementById("orcamentoModalOverlay").classList.remove("open");
    if (which === "orcamento-itens") document.getElementById("orcamentoItensModalOverlay").classList.remove("open");
    if (which === "delete-orcamento") document.getElementById("deleteOrcamentoModalOverlay").classList.remove("open");
    if (which === "delete-orcamento-item") document.getElementById("deleteOrcamentoItemModalOverlay").classList.remove("open");
}

document.querySelectorAll("[data-close]").forEach((el) => {
    el.addEventListener("click", () => closeModal(el.dataset.close));
});

// ============================================================
// MÓDULO DE EVENTOS — gerenciamento pelo administrador
// ============================================================

const EVENTOS_PER_PAGE = 8;

let eventos = [];
let eventosJaCarregados = false;

const eventosState = {
    activeTab: "todos",
    page: 1,
    editId: null,   // null = criando um evento novo; número = editando
    deleteId: null,
    participantesEventoId: null,
};

const STATUS_EVENTO_LABEL = {
    planejado: "Planejado",
    em_andamento: "Em andamento",
    realizado: "Realizado",
    cancelado: "Cancelado",
};

/**
 * Formata "HH:MM" + data ISO (YYYY-MM-DD) para exibição em pt-BR.
 */
function formatarDataHoraEvento(dataIso, horaInicio, horaFim) {
    const [ano, mes, dia] = dataIso.split("-");
    const dataFormatada = `${dia}/${mes}/${ano}`;
    const horario = horaFim ? `${horaInicio} - ${horaFim}` : horaInicio;
    return `${dataFormatada} · ${horario}`;
}

/**
 * Carrega os eventos reais do banco de dados.
 */
async function loadEventos() {
    const tbody = document.getElementById("eventosTableBody");
    tbody.innerHTML = `<tr class="empty-row"><td colspan="7">Carregando eventos...</td></tr>`;

    const { ok, dados } = await apiListarEventos();

    if (!ok || !dados.success) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="7">Não foi possível carregar os eventos.</td></tr>`;
        return;
    }

    eventos = dados.eventos;
    eventosJaCarregados = true;
    renderEventos();
}

function getEventosFiltrados() {
    if (eventosState.activeTab === "todos") return eventos;
    return eventos.filter((e) => e.status === eventosState.activeTab);
}

function renderEventos() {
    const filtrados = getEventosFiltrados();
    const totalPages = Math.max(1, Math.ceil(filtrados.length / EVENTOS_PER_PAGE));
    if (eventosState.page > totalPages) eventosState.page = totalPages;
    const linhas = filtrados.slice(
        (eventosState.page - 1) * EVENTOS_PER_PAGE,
        eventosState.page * EVENTOS_PER_PAGE
    );

    // Stats
    document.getElementById("statEventosTotal").textContent = eventos.length;
    document.getElementById("statEventosPlanejados").textContent = eventos.filter((e) => e.status === "planejado").length;
    document.getElementById("statEventosAndamento").textContent = eventos.filter((e) => e.status === "em_andamento").length;
    document.getElementById("statEventosRealizados").textContent = eventos.filter((e) => e.status === "realizado").length;

    // Contadores das abas
    document.getElementById("countEventosTodos").textContent = eventos.length;
    document.getElementById("countEventosPlanejado").textContent = eventos.filter((e) => e.status === "planejado").length;
    document.getElementById("countEventosAndamento").textContent = eventos.filter((e) => e.status === "em_andamento").length;
    document.getElementById("countEventosRealizado").textContent = eventos.filter((e) => e.status === "realizado").length;
    document.getElementById("countEventosCancelado").textContent = eventos.filter((e) => e.status === "cancelado").length;

    const tbody = document.getElementById("eventosTableBody");
    if (linhas.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="7">Nenhum evento encontrado.</td></tr>`;
    } else {
        tbody.innerHTML = linhas
            .map((ev, i) => {
                const num = (eventosState.page - 1) * EVENTOS_PER_PAGE + i + 1;
                const vagas = ev.limite_participantes
                    ? `${ev.total_inscritos}/${ev.limite_participantes}`
                    : `${ev.total_inscritos}`;
                return `
                <tr data-id="${ev.id}">
                    <td class="td-index">${num}</td>
                    <td class="td-user-name">${escapeHtml(ev.titulo)}</td>
                    <td class="td-joined">${formatarDataHoraEvento(ev.data_evento, ev.hora_inicio, ev.hora_fim)}</td>
                    <td>${escapeHtml(ev.local || "-")}</td>
                    <td>${vagas}</td>
                    <td><span class="status-badge status-${ev.status === 'planejado' || ev.status === 'em_andamento' ? 'ativo' : (ev.status === 'cancelado' ? 'suspenso' : 'pendente')}">${STATUS_EVENTO_LABEL[ev.status]}</span></td>
                    <td>
                        <div class="row-actions">
                            <button class="row-action-btn" data-action="participantes-evento" data-id="${ev.id}" title="Ver participantes">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="5" cy="4.5" r="2"></circle><path d="M1.5 12v-1a3 3 0 013-3h1a3 3 0 013 3v1"></path><circle cx="10.5" cy="5.5" r="1.5"></circle><path d="M8.8 8h1.4a2.3 2.3 0 012.3 2.3V12"></path></svg>
                            </button>
                            <button class="row-action-btn edit-btn" data-action="edit-evento" data-id="${ev.id}" title="Editar evento">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M9.5 1.5L12.5 4.5l-8.5 8.5L1 13l.5-2.5 8.5-8.5z"></path><path d="M7.5 3.5l3 3"></path></svg>
                            </button>
                            <button class="row-action-btn delete-btn" data-action="delete-evento" data-id="${ev.id}" title="Excluir evento">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M1.5 3.5h11M4.5 3.5V2h5v1.5M5 3.5l.7 8h2.6l.7-8"></path></svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
            })
            .join("");
    }

    const info = document.getElementById("eventosPaginationInfo");
    if (filtrados.length === 0) {
        info.textContent = "0 de 0";
    } else {
        const start = (eventosState.page - 1) * EVENTOS_PER_PAGE + 1;
        const end = Math.min(eventosState.page * EVENTOS_PER_PAGE, filtrados.length);
        info.textContent = `${start}–${end} de ${filtrados.length}`;
    }

    const btnsWrap = document.getElementById("eventosPaginationButtons");
    let html = `<button class="page-btn" data-page="prev" ${eventosState.page === 1 ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11L5 7l4-4"></path></svg>
    </button>`;
    for (let p = 1; p <= totalPages; p++) {
        html += `<button class="page-btn ${p === eventosState.page ? "active" : ""}" data-page="${p}">${p}</button>`;
    }
    html += `<button class="page-btn" data-page="next" ${eventosState.page === totalPages ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 11l4-4-4-4"></path></svg>
    </button>`;
    btnsWrap.innerHTML = html;

    btnsWrap.querySelectorAll(".page-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const val = btn.dataset.page;
            if (val === "prev") eventosState.page = Math.max(1, eventosState.page - 1);
            else if (val === "next") eventosState.page = Math.min(totalPages, eventosState.page + 1);
            else eventosState.page = parseInt(val, 10);
            renderEventos();
        });
    });

    tbody.querySelectorAll("[data-action='edit-evento']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalEvento(parseInt(btn.dataset.id, 10)));
    });
    tbody.querySelectorAll("[data-action='delete-evento']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalExcluirEvento(parseInt(btn.dataset.id, 10)));
    });
    tbody.querySelectorAll("[data-action='participantes-evento']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalParticipantes(parseInt(btn.dataset.id, 10)));
    });
}

document.querySelectorAll("#eventosStatusTabs .tab").forEach((tab) => {
    tab.addEventListener("click", () => {
        document.querySelectorAll("#eventosStatusTabs .tab").forEach((t) => t.classList.remove("active"));
        tab.classList.add("active");
        eventosState.activeTab = tab.dataset.tab;
        eventosState.page = 1;
        renderEventos();
    });
});

// ============================================================
// MODAL: CRIAR/EDITAR EVENTO
// ============================================================
function limparErroEvento() {
    const el = document.getElementById("eventoFormError");
    el.style.display = "none";
    el.textContent = "";
}

function mostrarErroEvento(mensagem) {
    const el = document.getElementById("eventoFormError");
    el.textContent = mensagem;
    el.style.display = "block";
}

function abrirModalEvento(id = null) {
    eventosState.editId = id;
    limparErroEvento();

    const titulo = document.getElementById("eventoModalTitulo");
    const subtitulo = document.getElementById("eventoModalSubtitulo");

    if (id === null) {
        titulo.textContent = "Novo evento";
        subtitulo.textContent = "Preencha os dados do evento";
        document.getElementById("eventoTitulo").value = "";
        document.getElementById("eventoDescricao").value = "";
        document.getElementById("eventoLocal").value = "";
        document.getElementById("eventoData").value = "";
        document.getElementById("eventoHoraInicio").value = "";
        document.getElementById("eventoHoraFim").value = "";
        document.getElementById("eventoLimite").value = "";
        document.getElementById("eventoStatus").value = "planejado";
    } else {
        const evento = eventos.find((e) => e.id === id);
        if (!evento) return;
        titulo.textContent = "Editar evento";
        subtitulo.textContent = `#${evento.id}`;
        document.getElementById("eventoTitulo").value = evento.titulo;
        document.getElementById("eventoDescricao").value = evento.descricao || "";
        document.getElementById("eventoLocal").value = evento.local || "";
        document.getElementById("eventoData").value = evento.data_evento;
        document.getElementById("eventoHoraInicio").value = evento.hora_inicio;
        document.getElementById("eventoHoraFim").value = evento.hora_fim || "";
        document.getElementById("eventoLimite").value = evento.limite_participantes || "";
        document.getElementById("eventoStatus").value = evento.status;
    }

    document.getElementById("eventoModalOverlay").classList.add("open");
}

document.getElementById("novoEventoBtn").addEventListener("click", () => abrirModalEvento(null));

document.getElementById("salvarEventoBtn").addEventListener("click", async () => {
    limparErroEvento();

    const dadosEvento = {
        titulo: document.getElementById("eventoTitulo").value.trim(),
        descricao: document.getElementById("eventoDescricao").value.trim(),
        local: document.getElementById("eventoLocal").value.trim(),
        data_evento: document.getElementById("eventoData").value,
        hora_inicio: document.getElementById("eventoHoraInicio").value,
        hora_fim: document.getElementById("eventoHoraFim").value || null,
        limite_participantes: document.getElementById("eventoLimite").value || null,
        status: document.getElementById("eventoStatus").value,
    };

    const btn = document.getElementById("salvarEventoBtn");
    btn.disabled = true;
    btn.textContent = "Salvando...";

    try {
        const resultado = eventosState.editId === null
            ? await apiCriarEvento(dadosEvento)
            : await apiAtualizarEvento(eventosState.editId, dadosEvento);

        if (!resultado.success) {
            mostrarErroEvento(resultado.message || "Não foi possível salvar o evento.");
            return;
        }

        closeModal("evento");
        await loadEventos();

    } catch (erro) {
        mostrarErroEvento("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Salvar evento";
    }
});

// ============================================================
// MODAL: EXCLUIR EVENTO
// ============================================================
function abrirModalExcluirEvento(id) {
    const evento = eventos.find((e) => e.id === id);
    if (!evento) return;
    eventosState.deleteId = id;
    document.getElementById("deleteEventoTitulo").textContent = evento.titulo;
    document.getElementById("deleteEventoModalOverlay").classList.add("open");
}

document.getElementById("confirmDeleteEventoBtn").addEventListener("click", async () => {
    if (eventosState.deleteId === null) return;

    const btn = document.getElementById("confirmDeleteEventoBtn");
    btn.disabled = true;
    btn.textContent = "Excluindo...";

    try {
        const resultado = await apiExcluirEvento(eventosState.deleteId);

        if (!resultado.success) {
            alert(resultado.message || "Não foi possível excluir o evento.");
            return;
        }

        eventosState.deleteId = null;
        closeModal("delete-evento");
        await loadEventos();

    } catch (erro) {
        alert("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Sim, excluir";
    }
});

// ============================================================
// MODAL: PARTICIPANTES E PRESENÇA
// ============================================================
async function abrirModalParticipantes(eventoId) {
    const evento = eventos.find((e) => e.id === eventoId);
    if (!evento) return;

    eventosState.participantesEventoId = eventoId;
    document.getElementById("participantesEventoTitulo").textContent = evento.titulo;

    const lista = document.getElementById("participantesLista");
    lista.innerHTML = `<p class="post-loading">Carregando participantes...</p>`;
    document.getElementById("participantesModalOverlay").classList.add("open");

    const { ok, dados } = await apiListarParticipantesEvento(eventoId);

    if (!ok || !dados.success) {
        lista.innerHTML = `<p class="post-loading">Não foi possível carregar os participantes.</p>`;
        return;
    }

    if (dados.participantes.length === 0) {
        lista.innerHTML = `<p class="post-loading">Nenhum participante inscrito ainda.</p>`;
        return;
    }

    lista.innerHTML = dados.participantes
        .map((p) => {
            const canceladoTag = p.status_inscricao === "cancelado"
                ? '<span class="status-badge status-suspenso">Cancelado</span>'
                : '<span class="status-badge status-ativo">Inscrito</span>';

            const presencaBtn = p.status_inscricao === "cancelado"
                ? ""
                : `<button class="btn-secondary presenca-btn" data-usuario-id="${p.usuario_id}" data-presente="${p.presente ? '1' : '0'}">
                     ${p.presente ? "✓ Presente" : "Marcar presença"}
                   </button>`;

            return `
                <div class="field" style="border-bottom:1px solid var(--border-color, #eee); padding-bottom:12px; margin-bottom:12px;">
                    <p class="td-user-name">${escapeHtml(p.nome_completo)} <span class="td-matricula">#${escapeHtml(p.rm)}</span></p>
                    <p class="td-user-email">${escapeHtml(p.email)}</p>
                    <div style="display:flex; align-items:center; gap:10px; margin-top:6px;">
                        ${canceladoTag}
                        ${presencaBtn}
                    </div>
                </div>`;
        })
        .join("");

    lista.querySelectorAll(".presenca-btn").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const usuarioId = parseInt(btn.dataset.usuarioId, 10);
            const presenteAtual = btn.dataset.presente === "1";

            btn.disabled = true;
            const resultado = await apiRegistrarPresenca(eventosState.participantesEventoId, usuarioId, !presenteAtual);

            if (resultado.success) {
                btn.dataset.presente = presenteAtual ? "0" : "1";
                btn.textContent = presenteAtual ? "Marcar presença" : "✓ Presente";
            } else {
                alert(resultado.message || "Não foi possível registrar a presença.");
            }
            btn.disabled = false;
        });
    });
}

// ============================================================
// MÓDULO DE PROJETOS — gerenciamento pelo administrador
//
// Conectado ao banco de dados real através de:
//   GET  /api/projetos                                  -> lista
//   GET  /api/projetos/{id}                              -> detalhe
//   POST /api/projetos                                   -> cria (admin)
//   POST /api/projetos/{id}/atualizar                    -> edita (admin)
//   POST /api/projetos/{id}/excluir                      -> arquiva (admin)
//   GET  /api/projetos/{id}/membros                       -> lista membros
//   POST /api/projetos/{id}/membros                       -> adiciona membro (admin)
//   POST /api/projetos/{id}/membros/remover                -> remove membro (admin)
//   GET  /api/projetos/{id}/tarefas                        -> lista tarefas
//   POST /api/projetos/{id}/tarefas                        -> cria tarefa (admin)
//   POST /api/projetos/{id}/tarefas/{id}/atualizar          -> edita tarefa (admin)
//   POST /api/projetos/{id}/tarefas/{id}/excluir            -> exclui tarefa (admin)
//   GET  /api/projetos/{id}/historico                      -> histórico
//   GET  /api/status-tarefa                               -> status disponíveis
//
// OBS: anexos de projeto (tabela `anexos_projeto`) NÃO foram
// implementados — não existe infraestrutura de upload em nenhum
// módulo do sistema (mesma decisão já tomada em Eventos).
// ============================================================

const PROJETOS_PER_PAGE = 8;

let projetos = [];
let projetosJaCarregados = false;
let statusTarefaCache = [];
let statusTarefaJaCarregado = false;

const projetosState = {
    activeTab: "todos",
    page: 1,
    editId: null,
    deleteId: null,
};

let projetoDetalheAtualId = null;
let projetoDetalheMembrosAtuais = [];
let projetoDetalheTarefasAtuais = [];

const STATUS_PROJETO_LABEL = {
    planejado: "Planejado",
    em_andamento: "Em andamento",
    concluido: "Concluído",
    cancelado: "Cancelado",
};

function statusProjetoClasse(status) {
    if (status === "em_andamento" || status === "concluido") return "ativo";
    if (status === "cancelado") return "suspenso";
    return "pendente";
}

function getProjetosFiltrados() {
    if (projetosState.activeTab === "todos") return projetos;
    return projetos.filter((p) => p.status === projetosState.activeTab);
}

async function loadProjetos() {
    const tbody = document.getElementById("projetosTableBody");
    tbody.innerHTML = `<tr class="empty-row"><td colspan="8">Carregando projetos...</td></tr>`;

    const { ok, dados } = await apiListarProjetos();

    if (!ok || !dados.success) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="8">Não foi possível carregar os projetos.</td></tr>`;
        return;
    }

    projetos = dados.projetos;
    projetosJaCarregados = true;
    renderProjetos();
}

function renderProjetos() {
    const filtrados = getProjetosFiltrados();
    const totalPages = Math.max(1, Math.ceil(filtrados.length / PROJETOS_PER_PAGE));
    if (projetosState.page > totalPages) projetosState.page = totalPages;
    const linhas = filtrados.slice(
        (projetosState.page - 1) * PROJETOS_PER_PAGE,
        projetosState.page * PROJETOS_PER_PAGE
    );

    document.getElementById("statProjetosTotal").textContent = projetos.length;
    document.getElementById("statProjetosPlanejados").textContent = projetos.filter((p) => p.status === "planejado").length;
    document.getElementById("statProjetosAndamento").textContent = projetos.filter((p) => p.status === "em_andamento").length;
    document.getElementById("statProjetosConcluidos").textContent = projetos.filter((p) => p.status === "concluido").length;

    document.getElementById("countProjetosTodos").textContent = projetos.length;
    document.getElementById("countProjetosPlanejado").textContent = projetos.filter((p) => p.status === "planejado").length;
    document.getElementById("countProjetosAndamento").textContent = projetos.filter((p) => p.status === "em_andamento").length;
    document.getElementById("countProjetosConcluido").textContent = projetos.filter((p) => p.status === "concluido").length;
    document.getElementById("countProjetosCancelado").textContent = projetos.filter((p) => p.status === "cancelado").length;

    const tbody = document.getElementById("projetosTableBody");
    if (linhas.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="8">Nenhum projeto encontrado.</td></tr>`;
    } else {
        tbody.innerHTML = linhas
            .map((p, i) => {
                const num = (projetosState.page - 1) * PROJETOS_PER_PAGE + i + 1;
                const tarefasResumo = `${p.total_tarefas_concluidas}/${p.total_tarefas}`;
                return `
                <tr data-id="${p.id}">
                    <td class="td-index">${num}</td>
                    <td class="td-user-name">${escapeHtml(p.nome)}</td>
                    <td>${escapeHtml(p.responsavel_nome)}</td>
                    <td class="td-joined">${formatarData(p.data_fim_prevista)}</td>
                    <td>${p.total_membros}</td>
                    <td>${tarefasResumo}</td>
                    <td><span class="status-badge status-${statusProjetoClasse(p.status)}">${STATUS_PROJETO_LABEL[p.status]}</span></td>
                    <td>
                        <div class="row-actions">
                            <button class="row-action-btn" data-action="detalhe-projeto" data-id="${p.id}" title="Ver detalhes (membros, tarefas, histórico)">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="5" cy="4.5" r="2"></circle><path d="M1.5 12v-1a3 3 0 013-3h1a3 3 0 013 3v1"></path><circle cx="10.5" cy="5.5" r="1.5"></circle><path d="M8.8 8h1.4a2.3 2.3 0 012.3 2.3V12"></path></svg>
                            </button>
                            <button class="row-action-btn edit-btn" data-action="edit-projeto" data-id="${p.id}" title="Editar projeto">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M9.5 1.5L12.5 4.5l-8.5 8.5L1 13l.5-2.5 8.5-8.5z"></path><path d="M7.5 3.5l3 3"></path></svg>
                            </button>
                            <button class="row-action-btn delete-btn" data-action="delete-projeto" data-id="${p.id}" title="Arquivar projeto">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M1.5 3.5h11M4.5 3.5V2h5v1.5M5 3.5l.7 8h2.6l.7-8"></path></svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
            })
            .join("");
    }

    const info = document.getElementById("projetosPaginationInfo");
    if (filtrados.length === 0) {
        info.textContent = "0 de 0";
    } else {
        const start = (projetosState.page - 1) * PROJETOS_PER_PAGE + 1;
        const end = Math.min(projetosState.page * PROJETOS_PER_PAGE, filtrados.length);
        info.textContent = `${start}–${end} de ${filtrados.length}`;
    }

    const btnsWrap = document.getElementById("projetosPaginationButtons");
    let html = `<button class="page-btn" data-page="prev" ${projetosState.page === 1 ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11L5 7l4-4"></path></svg>
    </button>`;
    for (let p = 1; p <= totalPages; p++) {
        html += `<button class="page-btn ${p === projetosState.page ? "active" : ""}" data-page="${p}">${p}</button>`;
    }
    html += `<button class="page-btn" data-page="next" ${projetosState.page === totalPages ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 11l4-4-4-4"></path></svg>
    </button>`;
    btnsWrap.innerHTML = html;

    btnsWrap.querySelectorAll(".page-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const val = btn.dataset.page;
            if (val === "prev") projetosState.page = Math.max(1, projetosState.page - 1);
            else if (val === "next") projetosState.page = Math.min(totalPages, projetosState.page + 1);
            else projetosState.page = parseInt(val, 10);
            renderProjetos();
        });
    });

    tbody.querySelectorAll("[data-action='edit-projeto']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalProjeto(parseInt(btn.dataset.id, 10)));
    });
    tbody.querySelectorAll("[data-action='delete-projeto']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalExcluirProjeto(parseInt(btn.dataset.id, 10)));
    });
    tbody.querySelectorAll("[data-action='detalhe-projeto']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalDetalheProjeto(parseInt(btn.dataset.id, 10)));
    });
}

document.querySelectorAll("#projetosStatusTabs .tab").forEach((tab) => {
    tab.addEventListener("click", () => {
        document.querySelectorAll("#projetosStatusTabs .tab").forEach((t) => t.classList.remove("active"));
        tab.classList.add("active");
        projetosState.activeTab = tab.dataset.tab;
        projetosState.page = 1;
        renderProjetos();
    });
});

// ============================================================
// MODAL: CRIAR/EDITAR PROJETO
// ============================================================
function limparErroProjeto() {
    const el = document.getElementById("projetoFormError");
    el.style.display = "none";
    el.textContent = "";
}

function mostrarErroProjeto(msg) {
    const el = document.getElementById("projetoFormError");
    el.textContent = msg;
    el.style.display = "block";
}

/**
 * Preenche o <select> de usuários (usado tanto para "Responsável"
 * quanto para o "Responsável" de tarefa). Reaproveita o array global
 * `users`, que já é carregado no início do painel (GET /api/admin/usuarios).
 */
function popularSelectUsuarios(selectEl, { opcaoVazia = null } = {}) {
    let html = "";
    if (opcaoVazia !== null) {
        html += `<option value="">${escapeHtml(opcaoVazia)}</option>`;
    }
    html += users
        .map((u) => `<option value="${u.id}">${escapeHtml(u.name)} (#${escapeHtml(u.matricula)})</option>`)
        .join("");
    selectEl.innerHTML = html;
}

function abrirModalProjeto(id = null) {
    projetosState.editId = id;
    limparErroProjeto();

    const titulo = document.getElementById("projetoModalTitulo");
    const subtitulo = document.getElementById("projetoModalSubtitulo");

    popularSelectUsuarios(document.getElementById("projetoResponsavel"));

    if (id === null) {
        titulo.textContent = "Novo projeto";
        subtitulo.textContent = "Preencha os dados do projeto";
        document.getElementById("projetoNome").value = "";
        document.getElementById("projetoDescricao").value = "";
        document.getElementById("projetoDataInicio").value = "";
        document.getElementById("projetoDataFimPrevista").value = "";
        document.getElementById("projetoDataFimReal").value = "";
        document.getElementById("projetoStatus").value = "planejado";
    } else {
        const projeto = projetos.find((p) => p.id === id);
        if (!projeto) return;
        titulo.textContent = "Editar projeto";
        subtitulo.textContent = `#${projeto.id}`;
        document.getElementById("projetoNome").value = projeto.nome;
        document.getElementById("projetoDescricao").value = projeto.descricao || "";
        document.getElementById("projetoDataInicio").value = projeto.data_inicio || "";
        document.getElementById("projetoDataFimPrevista").value = projeto.data_fim_prevista || "";
        document.getElementById("projetoDataFimReal").value = projeto.data_fim_real || "";
        document.getElementById("projetoStatus").value = projeto.status;
        document.getElementById("projetoResponsavel").value = projeto.responsavel_id;
    }

    document.getElementById("projetoModalOverlay").classList.add("open");
}

document.getElementById("novoProjetoBtn").addEventListener("click", () => abrirModalProjeto(null));

document.getElementById("salvarProjetoBtn").addEventListener("click", async () => {
    limparErroProjeto();

    const dadosProjeto = {
        nome: document.getElementById("projetoNome").value.trim(),
        descricao: document.getElementById("projetoDescricao").value.trim(),
        data_inicio: document.getElementById("projetoDataInicio").value || null,
        data_fim_prevista: document.getElementById("projetoDataFimPrevista").value || null,
        data_fim_real: document.getElementById("projetoDataFimReal").value || null,
        status: document.getElementById("projetoStatus").value,
        responsavel_id: document.getElementById("projetoResponsavel").value,
    };

    const btn = document.getElementById("salvarProjetoBtn");
    btn.disabled = true;
    btn.textContent = "Salvando...";

    try {
        const resultado = projetosState.editId === null
            ? await apiCriarProjeto(dadosProjeto)
            : await apiAtualizarProjeto(projetosState.editId, dadosProjeto);

        if (!resultado.success) {
            mostrarErroProjeto(resultado.message || "Não foi possível salvar o projeto.");
            return;
        }

        closeModal("projeto");
        await loadProjetos();

    } catch (erro) {
        mostrarErroProjeto("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Salvar projeto";
    }
});

// ============================================================
// MODAL: ARQUIVAR PROJETO
// ============================================================
function abrirModalExcluirProjeto(id) {
    const projeto = projetos.find((p) => p.id === id);
    if (!projeto) return;
    projetosState.deleteId = id;
    document.getElementById("deleteProjetoNome").textContent = projeto.nome;
    document.getElementById("deleteProjetoModalOverlay").classList.add("open");
}

document.getElementById("confirmDeleteProjetoBtn").addEventListener("click", async () => {
    if (projetosState.deleteId === null) return;

    const btn = document.getElementById("confirmDeleteProjetoBtn");
    btn.disabled = true;
    btn.textContent = "Arquivando...";

    try {
        const resultado = await apiExcluirProjeto(projetosState.deleteId);

        if (!resultado.success) {
            alert(resultado.message || "Não foi possível arquivar o projeto.");
            return;
        }

        projetosState.deleteId = null;
        closeModal("delete-projeto");
        await loadProjetos();

    } catch (erro) {
        alert("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Sim, arquivar";
    }
});

// ============================================================
// MODAL: DETALHES DO PROJETO (MEMBROS / TAREFAS / HISTÓRICO)
// ============================================================
async function garantirStatusTarefaCarregado() {
    if (statusTarefaJaCarregado) return;
    const { ok, dados } = await apiListarStatusTarefa();
    if (ok && dados.success) {
        statusTarefaCache = dados.status;
        statusTarefaJaCarregado = true;
    }
}

function mostrarPainelDetalheProjeto(aba) {
    const botoes = {
        membros: document.getElementById("projetoDetalheTabMembrosBtn"),
        tarefas: document.getElementById("projetoDetalheTabTarefasBtn"),
        historico: document.getElementById("projetoDetalheTabHistoricoBtn"),
    };
    const paineis = {
        membros: document.getElementById("projetoDetalhePainelMembros"),
        tarefas: document.getElementById("projetoDetalhePainelTarefas"),
        historico: document.getElementById("projetoDetalhePainelHistorico"),
    };

    Object.keys(botoes).forEach((chave) => {
        botoes[chave].className = chave === aba ? "btn-primary" : "btn-secondary";
        paineis[chave].style.display = chave === aba ? "block" : "none";
    });

    if (aba === "membros") carregarMembrosDetalhe();
    if (aba === "tarefas") carregarTarefasDetalhe();
    if (aba === "historico") carregarHistoricoDetalhe();
}

document.getElementById("projetoDetalheTabMembrosBtn").addEventListener("click", () => mostrarPainelDetalheProjeto("membros"));
document.getElementById("projetoDetalheTabTarefasBtn").addEventListener("click", () => mostrarPainelDetalheProjeto("tarefas"));
document.getElementById("projetoDetalheTabHistoricoBtn").addEventListener("click", () => mostrarPainelDetalheProjeto("historico"));

async function abrirModalDetalheProjeto(id) {
    const projeto = projetos.find((p) => p.id === id);
    if (!projeto) return;

    projetoDetalheAtualId = id;
    document.getElementById("projetoDetalheTitulo").textContent = projeto.nome;
    document.getElementById("projetoDetalheSubtitulo").textContent = `#${projeto.id} · Responsável: ${projeto.responsavel_nome}`;

    document.getElementById("projetoDetalheModalOverlay").classList.add("open");
    mostrarPainelDetalheProjeto("membros");
}

// ---------------- Membros ----------------

function limparErroMembro() {
    const el = document.getElementById("membroFormError");
    el.style.display = "none";
    el.textContent = "";
}

function mostrarErroMembro(msg) {
    const el = document.getElementById("membroFormError");
    el.textContent = msg;
    el.style.display = "block";
}

async function carregarMembrosDetalhe() {
    if (projetoDetalheAtualId === null) return;
    const lista = document.getElementById("projetoMembrosLista");
    lista.innerHTML = `<p class="post-loading">Carregando membros...</p>`;

    const { ok, dados } = await apiListarMembrosProjeto(projetoDetalheAtualId);

    if (!ok || !dados.success) {
        lista.innerHTML = `<p class="post-loading">Não foi possível carregar os membros.</p>`;
        return;
    }

    projetoDetalheMembrosAtuais = dados.membros;
    renderMembrosDetalhe();
}

function renderMembrosDetalhe() {
    const selectUsuario = document.getElementById("membroUsuarioSelect");
    const idsJaMembros = projetoDetalheMembrosAtuais.map((m) => m.usuario_id);
    const usuariosDisponiveis = users.filter((u) => !idsJaMembros.includes(u.id));
    selectUsuario.innerHTML = usuariosDisponiveis
        .map((u) => `<option value="${u.id}">${escapeHtml(u.name)} (#${escapeHtml(u.matricula)})</option>`)
        .join("") || `<option value="">Nenhum usuário disponível</option>`;

    const lista = document.getElementById("projetoMembrosLista");

    if (projetoDetalheMembrosAtuais.length === 0) {
        lista.innerHTML = `<p class="post-loading">Nenhum membro adicionado ainda.</p>`;
        return;
    }

    lista.innerHTML = projetoDetalheMembrosAtuais
        .map((m) => {
            const papelLabel = m.papel === "coordenador" ? "Coordenador" : "Membro";
            return `
            <div class="field" style="border-bottom:1px solid var(--border-color, #eee); padding-bottom:12px; margin-bottom:12px;">
                <p class="td-user-name">${escapeHtml(m.nome_completo)} <span class="td-matricula">#${escapeHtml(m.rm)}</span></p>
                <p class="td-user-email">${escapeHtml(m.email)}</p>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-top:6px;">
                    <span class="status-badge status-${m.papel === 'coordenador' ? 'ativo' : 'pendente'}">${papelLabel}</span>
                    <button class="btn-secondary remover-membro-btn" data-usuario-id="${m.usuario_id}">Remover</button>
                </div>
            </div>`;
        })
        .join("");

    lista.querySelectorAll(".remover-membro-btn").forEach((btn) => {
        btn.addEventListener("click", async () => {
            btn.disabled = true;
            const resultado = await apiRemoverMembroProjeto(projetoDetalheAtualId, parseInt(btn.dataset.usuarioId, 10));
            if (!resultado.success) {
                alert(resultado.message || "Não foi possível remover o membro.");
                btn.disabled = false;
                return;
            }
            await carregarMembrosDetalhe();
            await loadProjetos();
        });
    });
}

document.getElementById("adicionarMembroBtn").addEventListener("click", async () => {
    limparErroMembro();
    const usuarioId = document.getElementById("membroUsuarioSelect").value;
    const papel = document.getElementById("membroPapelSelect").value;

    if (!usuarioId) {
        mostrarErroMembro("Selecione um usuário para adicionar.");
        return;
    }

    const btn = document.getElementById("adicionarMembroBtn");
    btn.disabled = true;
    btn.textContent = "Adicionando...";

    try {
        const resultado = await apiAdicionarMembroProjeto(projetoDetalheAtualId, parseInt(usuarioId, 10), papel);
        if (!resultado.success) {
            mostrarErroMembro(resultado.message || "Não foi possível adicionar o membro.");
            return;
        }
        await carregarMembrosDetalhe();
        await loadProjetos();
    } catch (erro) {
        mostrarErroMembro("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Adicionar";
    }
});

// ---------------- Tarefas ----------------

function limparErroTarefa() {
    const el = document.getElementById("tarefaFormError");
    el.style.display = "none";
    el.textContent = "";
}

function mostrarErroTarefa(msg) {
    const el = document.getElementById("tarefaFormError");
    el.textContent = msg;
    el.style.display = "block";
}

async function carregarTarefasDetalhe() {
    if (projetoDetalheAtualId === null) return;
    const lista = document.getElementById("projetoTarefasLista");
    lista.innerHTML = `<p class="post-loading">Carregando tarefas...</p>`;

    await garantirStatusTarefaCarregado();
    popularSelectUsuarios(document.getElementById("tarefaResponsavelSelect"), { opcaoVazia: "Sem responsável" });

    const { ok, dados } = await apiListarTarefasProjeto(projetoDetalheAtualId);

    if (!ok || !dados.success) {
        lista.innerHTML = `<p class="post-loading">Não foi possível carregar as tarefas.</p>`;
        return;
    }

    projetoDetalheTarefasAtuais = dados.tarefas;
    renderTarefasDetalhe();
}

function renderTarefasDetalhe() {
    const lista = document.getElementById("projetoTarefasLista");

    if (projetoDetalheTarefasAtuais.length === 0) {
        lista.innerHTML = `<p class="post-loading">Nenhuma tarefa cadastrada ainda.</p>`;
        return;
    }

    const opcoesStatus = statusTarefaCache
        .map((s) => `<option value="${s.id}">${s.nome.replace("_", " ")}</option>`)
        .join("");

    lista.innerHTML = projetoDetalheTarefasAtuais
        .map((t) => {
            return `
            <div class="field" style="border-bottom:1px solid var(--border-color, #eee); padding-bottom:12px; margin-bottom:12px;">
                <p class="td-user-name">${escapeHtml(t.titulo)}</p>
                <p class="td-user-email">Responsável: ${t.responsavel_nome ? escapeHtml(t.responsavel_nome) : "-"} · Prazo: ${formatarData(t.data_prazo)}</p>
                <div style="display:flex; align-items:center; gap:10px; margin-top:6px;">
                    <select class="tarefa-status-select" data-tarefa-id="${t.id}">${opcoesStatus}</select>
                    <button class="btn-secondary excluir-tarefa-btn" data-tarefa-id="${t.id}">Excluir</button>
                </div>
            </div>`;
        })
        .join("");

    lista.querySelectorAll(".tarefa-status-select").forEach((sel) => {
        const tarefaId = parseInt(sel.dataset.tarefaId, 10);
        const tarefa = projetoDetalheTarefasAtuais.find((t) => t.id === tarefaId);
        if (tarefa) sel.value = tarefa.status_tarefa_id;

        sel.addEventListener("change", async () => {
            sel.disabled = true;
            const resultado = await apiAtualizarTarefa(projetoDetalheAtualId, tarefaId, {
                titulo: tarefa.titulo,
                descricao: tarefa.descricao,
                status_tarefa_id: parseInt(sel.value, 10),
                responsavel_id: tarefa.responsavel_id,
                data_inicio: tarefa.data_inicio,
                data_prazo: tarefa.data_prazo,
                data_conclusao: tarefa.data_conclusao,
            });
            if (!resultado.success) {
                alert(resultado.message || "Não foi possível atualizar o status da tarefa.");
            }
            await carregarTarefasDetalhe();
            await loadProjetos();
        });
    });

    lista.querySelectorAll(".excluir-tarefa-btn").forEach((btn) => {
        btn.addEventListener("click", async () => {
            if (!confirm("Excluir esta tarefa?")) return;
            btn.disabled = true;
            const resultado = await apiExcluirTarefa(projetoDetalheAtualId, parseInt(btn.dataset.tarefaId, 10));
            if (!resultado.success) {
                alert(resultado.message || "Não foi possível excluir a tarefa.");
                btn.disabled = false;
                return;
            }
            await carregarTarefasDetalhe();
            await loadProjetos();
        });
    });
}

document.getElementById("criarTarefaBtn").addEventListener("click", async () => {
    limparErroTarefa();

    const titulo = document.getElementById("tarefaTitulo").value.trim();
    const responsavelId = document.getElementById("tarefaResponsavelSelect").value;
    const dataPrazo = document.getElementById("tarefaDataPrazo").value;

    if (!titulo) {
        mostrarErroTarefa("Informe o título da tarefa.");
        return;
    }

    if (!statusTarefaCache.length) {
        mostrarErroTarefa("Nenhum status de tarefa cadastrado no banco (veja database/seed_status_tarefa.sql).");
        return;
    }

    const btn = document.getElementById("criarTarefaBtn");
    btn.disabled = true;
    btn.textContent = "Adicionando...";

    try {
        const statusPendente = statusTarefaCache.find((s) => s.nome === "pendente") || statusTarefaCache[0];

        const resultado = await apiCriarTarefa(projetoDetalheAtualId, {
            titulo,
            status_tarefa_id: statusPendente.id,
            responsavel_id: responsavelId || null,
            data_prazo: dataPrazo || null,
        });

        if (!resultado.success) {
            mostrarErroTarefa(resultado.message || "Não foi possível criar a tarefa.");
            return;
        }

        document.getElementById("tarefaTitulo").value = "";
        document.getElementById("tarefaDataPrazo").value = "";

        await carregarTarefasDetalhe();
        await loadProjetos();
    } catch (erro) {
        mostrarErroTarefa("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "+ Adicionar tarefa";
    }
});

// ---------------- Histórico ----------------

async function carregarHistoricoDetalhe() {
    if (projetoDetalheAtualId === null) return;
    const lista = document.getElementById("projetoHistoricoLista");
    lista.innerHTML = `<p class="post-loading">Carregando histórico...</p>`;

    const { ok, dados } = await apiListarHistoricoProjeto(projetoDetalheAtualId);

    if (!ok || !dados.success) {
        lista.innerHTML = `<p class="post-loading">Não foi possível carregar o histórico.</p>`;
        return;
    }

    if (dados.historico.length === 0) {
        lista.innerHTML = `<p class="post-loading">Nenhum registro de histórico ainda.</p>`;
        return;
    }

    lista.innerHTML = dados.historico
        .map((h) => `
            <div class="field" style="border-bottom:1px solid var(--border-color, #eee); padding-bottom:12px; margin-bottom:12px;">
                <p class="td-user-name">${escapeHtml(h.descricao || h.acao)}</p>
                <p class="td-user-email">${h.usuario_nome ? escapeHtml(h.usuario_nome) : "Sistema"} · ${formatarData(h.created_at)}</p>
            </div>`)
        .join("");
}

// ============================================================
// MÓDULO DE VOTAÇÕES — gerenciamento pelo administrador
// ============================================================

const VOTACOES_PER_PAGE = 8;

let votacoes = [];
let votacoesJaCarregadas = false;

const votacoesState = {
    activeTab: "todos",
    page: 1,
    editId: null,
    editStatusAtual: null, // status que a votacao tinha ANTES de abrir o modal (trava edicao de opcoes)
    deleteId: null,
};

const TIPO_VOTACAO_LABEL = {
    enquete: "Enquete",
    eleicao: "Eleição",
    votacao_gremio: "Votação do Grêmio",
};

const STATUS_VOTACAO_LABEL = {
    agendada: "Agendada",
    aberta: "Aberta",
    encerrada: "Encerrada",
    cancelada: "Cancelada",
};

function formatarPeriodoVotacao(dataInicio, dataFim) {
    const formatar = (iso) => {
        const [data, hora] = iso.split(" ");
        const [ano, mes, dia] = data.split("-");
        return `${dia}/${mes}/${ano} ${hora ? hora.slice(0, 5) : ""}`;
    };
    return `${formatar(dataInicio)} até ${formatar(dataFim)}`;
}

/**
 * Converte "YYYY-MM-DD HH:MM:SS" (formato do banco) para o formato
 * que o <input type="datetime-local"> espera ("YYYY-MM-DDTHH:MM").
 */
function paraDatetimeLocal(dataBanco) {
    if (!dataBanco) return "";
    return dataBanco.replace(" ", "T").slice(0, 16);
}

async function loadVotacoes() {
    const tbody = document.getElementById("votacoesTableBody");
    tbody.innerHTML = `<tr class="empty-row"><td colspan="7">Carregando votações...</td></tr>`;

    const { ok, dados } = await apiListarVotacoes();

    if (!ok || !dados.success) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="7">Não foi possível carregar as votações.</td></tr>`;
        return;
    }

    votacoes = dados.votacoes;
    votacoesJaCarregadas = true;
    renderVotacoes();
}

function getVotacoesFiltradas() {
    if (votacoesState.activeTab === "todos") return votacoes;
    return votacoes.filter((v) => v.status === votacoesState.activeTab);
}

function renderVotacoes() {
    const filtradas = getVotacoesFiltradas();
    const totalPages = Math.max(1, Math.ceil(filtradas.length / VOTACOES_PER_PAGE));
    if (votacoesState.page > totalPages) votacoesState.page = totalPages;
    const linhas = filtradas.slice(
        (votacoesState.page - 1) * VOTACOES_PER_PAGE,
        votacoesState.page * VOTACOES_PER_PAGE
    );

    document.getElementById("statVotacoesTotal").textContent = votacoes.length;
    document.getElementById("statVotacoesAgendadas").textContent = votacoes.filter((v) => v.status === "agendada").length;
    document.getElementById("statVotacoesAbertas").textContent = votacoes.filter((v) => v.status === "aberta").length;
    document.getElementById("statVotacoesEncerradas").textContent = votacoes.filter((v) => v.status === "encerrada").length;

    document.getElementById("countVotacoesTodos").textContent = votacoes.length;
    document.getElementById("countVotacoesAgendada").textContent = votacoes.filter((v) => v.status === "agendada").length;
    document.getElementById("countVotacoesAberta").textContent = votacoes.filter((v) => v.status === "aberta").length;
    document.getElementById("countVotacoesEncerrada").textContent = votacoes.filter((v) => v.status === "encerrada").length;
    document.getElementById("countVotacoesCancelada").textContent = votacoes.filter((v) => v.status === "cancelada").length;

    const tbody = document.getElementById("votacoesTableBody");
    if (linhas.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="7">Nenhuma votação encontrada.</td></tr>`;
    } else {
        tbody.innerHTML = linhas
            .map((v, i) => {
                const num = (votacoesState.page - 1) * VOTACOES_PER_PAGE + i + 1;
                const statusClasse = v.status === "aberta" ? "ativo" : (v.status === "cancelada" ? "suspenso" : (v.status === "encerrada" ? "suspenso" : "pendente"));
                return `
                <tr data-id="${v.id}">
                    <td class="td-index">${num}</td>
                    <td class="td-user-name">${escapeHtml(v.titulo)}</td>
                    <td>${TIPO_VOTACAO_LABEL[v.tipo] || v.tipo}</td>
                    <td class="td-joined">${formatarPeriodoVotacao(v.data_inicio, v.data_fim)}</td>
                    <td><span class="status-badge status-${statusClasse}">${STATUS_VOTACAO_LABEL[v.status]}</span></td>
                    <td>${v.total_votos}</td>
                    <td>
                        <div class="row-actions">
                            <button class="row-action-btn" data-action="resultado-votacao" data-id="${v.id}" title="Ver resultado">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M2 12V7M6 12V2M10 12V5"></path></svg>
                            </button>
                            <button class="row-action-btn edit-btn" data-action="edit-votacao" data-id="${v.id}" title="Editar votação">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M9.5 1.5L12.5 4.5l-8.5 8.5L1 13l.5-2.5 8.5-8.5z"></path><path d="M7.5 3.5l3 3"></path></svg>
                            </button>
                            <button class="row-action-btn delete-btn" data-action="delete-votacao" data-id="${v.id}" title="Excluir votação">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M1.5 3.5h11M4.5 3.5V2h5v1.5M5 3.5l.7 8h2.6l.7-8"></path></svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
            })
            .join("");
    }

    const info = document.getElementById("votacoesPaginationInfo");
    if (filtradas.length === 0) {
        info.textContent = "0 de 0";
    } else {
        const start = (votacoesState.page - 1) * VOTACOES_PER_PAGE + 1;
        const end = Math.min(votacoesState.page * VOTACOES_PER_PAGE, filtradas.length);
        info.textContent = `${start}–${end} de ${filtradas.length}`;
    }

    const btnsWrap = document.getElementById("votacoesPaginationButtons");
    let html = `<button class="page-btn" data-page="prev" ${votacoesState.page === 1 ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11L5 7l4-4"></path></svg>
    </button>`;
    for (let p = 1; p <= totalPages; p++) {
        html += `<button class="page-btn ${p === votacoesState.page ? "active" : ""}" data-page="${p}">${p}</button>`;
    }
    html += `<button class="page-btn" data-page="next" ${votacoesState.page === totalPages ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 11l4-4-4-4"></path></svg>
    </button>`;
    btnsWrap.innerHTML = html;

    btnsWrap.querySelectorAll(".page-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const val = btn.dataset.page;
            if (val === "prev") votacoesState.page = Math.max(1, votacoesState.page - 1);
            else if (val === "next") votacoesState.page = Math.min(totalPages, votacoesState.page + 1);
            else votacoesState.page = parseInt(val, 10);
            renderVotacoes();
        });
    });

    tbody.querySelectorAll("[data-action='edit-votacao']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalVotacao(parseInt(btn.dataset.id, 10)));
    });
    tbody.querySelectorAll("[data-action='delete-votacao']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalExcluirVotacao(parseInt(btn.dataset.id, 10)));
    });
    tbody.querySelectorAll("[data-action='resultado-votacao']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalResultado(parseInt(btn.dataset.id, 10)));
    });
}

document.querySelectorAll("#votacoesStatusTabs .tab").forEach((tab) => {
    tab.addEventListener("click", () => {
        document.querySelectorAll("#votacoesStatusTabs .tab").forEach((t) => t.classList.remove("active"));
        tab.classList.add("active");
        votacoesState.activeTab = tab.dataset.tab;
        votacoesState.page = 1;
        renderVotacoes();
    });
});

// ============================================================
// MODAL: CRIAR/EDITAR VOTAÇÃO (com opções dinâmicas)
// ============================================================
function limparErroVotacao() {
    const el = document.getElementById("votacaoFormError");
    el.style.display = "none";
    el.textContent = "";
}

function mostrarErroVotacao(mensagem) {
    const el = document.getElementById("votacaoFormError");
    el.textContent = mensagem;
    el.style.display = "block";
}

function renderOpcoesVotacao(opcoes, opcoesEditaveis) {
    const container = document.getElementById("votacaoOpcoesLista");
    document.getElementById("votacaoOpcoesAviso").style.display = opcoesEditaveis ? "none" : "block";

    container.innerHTML = opcoes
        .map(
            (texto, i) => `
        <div class="field" style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
            <input type="text" class="opcao-votacao-input" value="${escapeHtml(texto)}" maxlength="150" ${opcoesEditaveis ? "" : "disabled"} style="flex:1;">
            ${opcoesEditaveis ? `<button type="button" class="row-action-btn delete-btn remover-opcao-btn" data-index="${i}">✕</button>` : ""}
        </div>`
        )
        .join("");

    if (opcoesEditaveis) {
        container.querySelectorAll(".remover-opcao-btn").forEach((btn) => {
            btn.addEventListener("click", () => {
                const inputs = [...container.querySelectorAll(".opcao-votacao-input")].map((i) => i.value);
                inputs.splice(parseInt(btn.dataset.index, 10), 1);
                renderOpcoesVotacao(inputs, true);
            });
        });
    }

    document.getElementById("adicionarOpcaoBtn").style.display = opcoesEditaveis ? "inline-block" : "none";
}

document.getElementById("adicionarOpcaoBtn").addEventListener("click", () => {
    const container = document.getElementById("votacaoOpcoesLista");
    const atuais = [...container.querySelectorAll(".opcao-votacao-input")].map((i) => i.value);
    atuais.push("");
    renderOpcoesVotacao(atuais, true);
});

async function abrirModalVotacao(id = null) {
    votacoesState.editId = id;
    limparErroVotacao();

    const titulo = document.getElementById("votacaoModalTitulo");
    const subtitulo = document.getElementById("votacaoModalSubtitulo");

    if (id === null) {
        votacoesState.editStatusAtual = "agendada";
        titulo.textContent = "Nova votação";
        subtitulo.textContent = "Preencha os dados da votação";
        document.getElementById("votacaoTitulo").value = "";
        document.getElementById("votacaoDescricao").value = "";
        document.getElementById("votacaoTipo").value = "enquete";
        document.getElementById("votacaoDataInicio").value = "";
        document.getElementById("votacaoDataFim").value = "";
        document.getElementById("votacaoStatus").value = "agendada";
        renderOpcoesVotacao(["", ""], true);
    } else {
        const votacaoResumo = votacoes.find((v) => v.id === id);
        if (!votacaoResumo) return;

        titulo.textContent = "Editar votação";
        subtitulo.textContent = `#${votacaoResumo.id}`;
        document.getElementById("votacaoTitulo").value = votacaoResumo.titulo;
        document.getElementById("votacaoDescricao").value = votacaoResumo.descricao || "";
        document.getElementById("votacaoTipo").value = votacaoResumo.tipo;
        document.getElementById("votacaoDataInicio").value = paraDatetimeLocal(votacaoResumo.data_inicio);
        document.getElementById("votacaoDataFim").value = paraDatetimeLocal(votacaoResumo.data_fim);
        document.getElementById("votacaoStatus").value = votacaoResumo.status;

        votacoesState.editStatusAtual = votacaoResumo.status;

        // Busca as opções reais (a listagem resumida não traz elas)
        const { ok, dados } = await apiBuscarVotacao(id);
        if (ok && dados.success) {
            const textos = dados.votacao.opcoes.map((o) => o.descricao_opcao);
            renderOpcoesVotacao(textos, votacaoResumo.status === "agendada");
        }
    }

    document.getElementById("votacaoModalOverlay").classList.add("open");
}

document.getElementById("novaVotacaoBtn").addEventListener("click", () => abrirModalVotacao(null));

document.getElementById("salvarVotacaoBtn").addEventListener("click", async () => {
    limparErroVotacao();

    const dadosVotacao = {
        titulo: document.getElementById("votacaoTitulo").value.trim(),
        descricao: document.getElementById("votacaoDescricao").value.trim(),
        tipo: document.getElementById("votacaoTipo").value,
        data_inicio: document.getElementById("votacaoDataInicio").value,
        data_fim: document.getElementById("votacaoDataFim").value,
        status: document.getElementById("votacaoStatus").value,
    };

    // Opções só são enviadas se ainda eram editáveis (votação ainda 'agendada'
    // no momento em que o modal foi aberto). O backend também garante isso.
    if (votacoesState.editStatusAtual === "agendada") {
        dadosVotacao.opcoes = [...document.querySelectorAll(".opcao-votacao-input")].map((i) => i.value);
    }

    const btn = document.getElementById("salvarVotacaoBtn");
    btn.disabled = true;
    btn.textContent = "Salvando...";

    try {
        const resultado = votacoesState.editId === null
            ? await apiCriarVotacao(dadosVotacao)
            : await apiAtualizarVotacao(votacoesState.editId, dadosVotacao);

        if (!resultado.success) {
            mostrarErroVotacao(resultado.message || "Não foi possível salvar a votação.");
            return;
        }

        closeModal("votacao");
        await loadVotacoes();

    } catch (erro) {
        mostrarErroVotacao("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Salvar votação";
    }
});

// ============================================================
// MODAL: EXCLUIR VOTAÇÃO
// ============================================================
function abrirModalExcluirVotacao(id) {
    const votacao = votacoes.find((v) => v.id === id);
    if (!votacao) return;
    votacoesState.deleteId = id;
    document.getElementById("deleteVotacaoTitulo").textContent = votacao.titulo;
    document.getElementById("deleteVotacaoModalOverlay").classList.add("open");
}

document.getElementById("confirmDeleteVotacaoBtn").addEventListener("click", async () => {
    if (votacoesState.deleteId === null) return;

    const btn = document.getElementById("confirmDeleteVotacaoBtn");
    btn.disabled = true;
    btn.textContent = "Excluindo...";

    try {
        const resultado = await apiExcluirVotacao(votacoesState.deleteId);

        if (!resultado.success) {
            alert(resultado.message || "Não foi possível excluir a votação.");
            return;
        }

        votacoesState.deleteId = null;
        closeModal("delete-votacao");
        await loadVotacoes();

    } catch (erro) {
        alert("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Sim, excluir";
    }
});

// ============================================================
// MODAL: RESULTADO
// ============================================================
async function abrirModalResultado(votacaoId) {
    const votacao = votacoes.find((v) => v.id === votacaoId);
    if (!votacao) return;

    document.getElementById("resultadoVotacaoTitulo").textContent = votacao.titulo;

    const lista = document.getElementById("resultadoLista");
    lista.innerHTML = `<p class="post-loading">Carregando resultado...</p>`;
    document.getElementById("resultadoModalOverlay").classList.add("open");

    const { ok, dados } = await apiResultadoVotacao(votacaoId);

    if (!ok || !dados.success) {
        lista.innerHTML = `<p class="post-loading">Não foi possível carregar o resultado.</p>`;
        return;
    }

    if (dados.total_votos === 0) {
        lista.innerHTML = `<p class="post-loading">Ainda não há votos registrados nesta votação.</p>`;
        return;
    }

    lista.innerHTML = dados.opcoes
        .map(
            (o) => `
        <div style="margin-bottom:16px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <span class="td-user-name">${escapeHtml(o.descricao_opcao)}</span>
                <span class="td-user-email">${o.total_votos} voto(s) · ${o.percentual}%</span>
            </div>
            <div style="background:#eee; border-radius:8px; height:10px; overflow:hidden;">
                <div style="background:var(--accent-color, #4f46e5); height:100%; width:${o.percentual}%;"></div>
            </div>
        </div>`
        )
        .join("") + `<p class="td-user-email" style="margin-top:8px;">Total geral: ${dados.total_votos} voto(s)</p>`;
}

// ============================================================
// MÓDULO DE AUDITORIA — consulta de logs pelo administrador
// ============================================================

const AUDITORIA_LIMITE = 15;

let auditoriaJaCarregada = false;

const auditoriaState = {
    pagina: 1,
    totalPaginas: 1,
    total: 0,
    filtros: {
        modulo: "",
        acao: "",
        data_inicio: "",
        data_fim: "",
    },
};

const AUDITORIA_ACAO_LABEL = {
    login: "Login",
    logout: "Logout",
    cadastro_solicitado: "Cadastro solicitado",
    aprovacao_cadastro: "Aprovação de cadastro",
    rejeicao_cadastro: "Rejeição de cadastro",
    ativacao: "Ativação",
    suspensao: "Suspensão",
    atualizacao: "Atualização",
    exclusao: "Exclusão",
    criacao: "Criação",
    encerramento: "Encerramento",
    voto: "Voto",
    participacao: "Participação",
    cancelamento_participacao: "Cancelamento de participação",
    membro_adicionado: "Membro adicionado",
    membro_removido: "Membro removido",
    criacao_tarefa: "Criação de tarefa",
    alteracao_status_tarefa: "Alteração de status de tarefa",
    alteracao_tarefa: "Alteração de tarefa",
    exclusao_tarefa: "Exclusão de tarefa",
    alteracao_status: "Alteração de status",
    alteracao: "Alteração",
    arquivamento: "Arquivamento",
    criacao_item: "Criação de item",
    alteracao_item: "Alteração de item",
    exclusao_item: "Remoção de item",
};

const AUDITORIA_MODULO_LABEL = {
    autenticacao: "Autenticação",
    usuarios: "Usuários",
    publicacoes: "Publicações",
    eventos: "Eventos",
    votacoes: "Votações",
    projetos: "Projetos",
    financeiro: "Financeiro",
    orcamentos: "Orçamentos",
};

function formatarAcaoAuditoria(acao) {
    return AUDITORIA_ACAO_LABEL[acao] || acao.replace(/_/g, " ");
}

function formatarModuloAuditoria(modulo) {
    return AUDITORIA_MODULO_LABEL[modulo] || modulo;
}

function formatarDataHoraAuditoria(dataBanco) {
    if (!dataBanco) return "—";
    const [data, hora] = dataBanco.split(" ");
    const [ano, mes, dia] = data.split("-");
    return `${dia}/${mes}/${ano} ${hora ? hora.slice(0, 5) : ""}`;
}

async function initAuditoria() {
    auditoriaJaCarregada = true;

    const { ok, dados } = await apiListarFiltrosAuditoria();
    if (ok && dados.success) {
        const selectModulo = document.getElementById("auditoriaFiltroModulo");
        dados.modulos.forEach((modulo) => {
            const opt = document.createElement("option");
            opt.value = modulo;
            opt.textContent = formatarModuloAuditoria(modulo);
            selectModulo.appendChild(opt);
        });

        const selectAcao = document.getElementById("auditoriaFiltroAcao");
        dados.acoes.forEach((acao) => {
            const opt = document.createElement("option");
            opt.value = acao;
            opt.textContent = formatarAcaoAuditoria(acao);
            selectAcao.appendChild(opt);
        });
    }

    document.getElementById("auditoriaFiltroModulo").addEventListener("change", (e) => {
        auditoriaState.filtros.modulo = e.target.value;
        auditoriaState.pagina = 1;
        loadAuditoria();
    });
    document.getElementById("auditoriaFiltroAcao").addEventListener("change", (e) => {
        auditoriaState.filtros.acao = e.target.value;
        auditoriaState.pagina = 1;
        loadAuditoria();
    });
    document.getElementById("auditoriaFiltroDataInicio").addEventListener("change", (e) => {
        auditoriaState.filtros.data_inicio = e.target.value;
        auditoriaState.pagina = 1;
        loadAuditoria();
    });
    document.getElementById("auditoriaFiltroDataFim").addEventListener("change", (e) => {
        auditoriaState.filtros.data_fim = e.target.value;
        auditoriaState.pagina = 1;
        loadAuditoria();
    });
    document.getElementById("btnLimparFiltrosAuditoria").addEventListener("click", () => {
        auditoriaState.filtros = { modulo: "", acao: "", data_inicio: "", data_fim: "" };
        auditoriaState.pagina = 1;
        document.getElementById("auditoriaFiltroModulo").value = "";
        document.getElementById("auditoriaFiltroAcao").value = "";
        document.getElementById("auditoriaFiltroDataInicio").value = "";
        document.getElementById("auditoriaFiltroDataFim").value = "";
        loadAuditoria();
    });

    document.getElementById("auditoriaTableBody").addEventListener("click", (evento) => {
        const btn = evento.target.closest("[data-action='ver-log']");
        if (btn) abrirDetalheLog(parseInt(btn.dataset.id, 10));
    });

    loadAuditoria();
}

async function loadAuditoria() {
    const tbody = document.getElementById("auditoriaTableBody");
    tbody.innerHTML = `<tr class="empty-row"><td colspan="6">Carregando logs...</td></tr>`;

    const { ok, dados } = await apiListarAuditoria({
        ...auditoriaState.filtros,
        pagina: auditoriaState.pagina,
        limite: AUDITORIA_LIMITE,
    });

    if (!ok || !dados.success) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="6">Não foi possível carregar os logs.</td></tr>`;
        return;
    }

    auditoriaState.totalPaginas = dados.total_paginas || 1;
    auditoriaState.total = dados.total || 0;

    document.getElementById("auditoriaResultsCount").textContent = `${dados.total} resultado${dados.total === 1 ? "" : "s"}`;

    if (dados.logs.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="6">Nenhum log encontrado com esses filtros.</td></tr>`;
    } else {
        tbody.innerHTML = dados.logs
            .map((log) => {
                const usuario = log.usuario_nome
                    ? `${escapeHtml(log.usuario_nome)} ${log.usuario_rm ? `<span class="td-user-email">RM ${escapeHtml(log.usuario_rm)}</span>` : ""}`
                    : `<span class="td-user-email">—</span>`;
                return `
                <tr data-id="${log.id}">
                    <td class="td-joined">${formatarDataHoraAuditoria(log.created_at)}</td>
                    <td>${usuario}</td>
                    <td><span class="status-badge status-pendente">${escapeHtml(formatarAcaoAuditoria(log.acao))}</span></td>
                    <td>${escapeHtml(formatarModuloAuditoria(log.modulo))}</td>
                    <td class="td-user-email">${escapeHtml(log.descricao || "—")}</td>
                    <td>
                        <div class="row-actions">
                            <button class="row-action-btn" data-action="ver-log" data-id="${log.id}" title="Ver detalhes">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="7" cy="7" r="5.5"></circle><path d="M7 4.5v3l2 1.5"></path></svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
            })
            .join("");
    }

    renderPaginacaoAuditoria();
}

function renderPaginacaoAuditoria() {
    const info = document.getElementById("auditoriaPaginationInfo");
    if (auditoriaState.total === 0) {
        info.textContent = "0 de 0";
    } else {
        const start = (auditoriaState.pagina - 1) * AUDITORIA_LIMITE + 1;
        const end = Math.min(auditoriaState.pagina * AUDITORIA_LIMITE, auditoriaState.total);
        info.textContent = `${start}–${end} de ${auditoriaState.total}`;
    }

    const btnsWrap = document.getElementById("auditoriaPaginationButtons");
    const totalPaginas = auditoriaState.totalPaginas;
    let html = `<button class="page-btn" data-page="prev" ${auditoriaState.pagina === 1 ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11L5 7l4-4"></path></svg>
    </button>`;
    for (let p = 1; p <= totalPaginas; p++) {
        html += `<button class="page-btn ${p === auditoriaState.pagina ? "active" : ""}" data-page="${p}">${p}</button>`;
    }
    html += `<button class="page-btn" data-page="next" ${auditoriaState.pagina === totalPaginas ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 11l4-4-4-4"></path></svg>
    </button>`;
    btnsWrap.innerHTML = html;

    btnsWrap.querySelectorAll(".page-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const val = btn.dataset.page;
            if (val === "prev") auditoriaState.pagina = Math.max(1, auditoriaState.pagina - 1);
            else if (val === "next") auditoriaState.pagina = Math.min(totalPaginas, auditoriaState.pagina + 1);
            else auditoriaState.pagina = parseInt(val, 10);
            loadAuditoria();
        });
    });
}

async function abrirDetalheLog(id) {
    document.getElementById("logDetalheId").textContent = `#${id}`;
    document.getElementById("logDetalheCorpo").innerHTML = `<p class="td-user-email">Carregando...</p>`;
    document.getElementById("logDetalheModalOverlay").classList.add("open");

    const { ok, dados } = await apiBuscarLogAuditoria(id);

    if (!ok || !dados.success) {
        document.getElementById("logDetalheCorpo").innerHTML = `<p class="td-user-email">Não foi possível carregar este log.</p>`;
        return;
    }

    const log = dados.log;
    const linhas = [
        ["Data/Hora", formatarDataHoraAuditoria(log.created_at)],
        ["Usuário", log.usuario_nome ? `${log.usuario_nome} (RM ${log.usuario_rm || "—"})` : "—"],
        ["E-mail", log.usuario_email || "—"],
        ["Ação", formatarAcaoAuditoria(log.acao)],
        ["Módulo", formatarModuloAuditoria(log.modulo)],
        ["Descrição", log.descricao || "—"],
        ["Endereço IP", log.ip_address || "—"],
        ["Navegador", log.user_agent || "—"],
    ];

    document.getElementById("logDetalheCorpo").innerHTML = linhas
        .map(([rotulo, valor]) => `
            <div class="log-detalhe-linha">
                <span>${escapeHtml(rotulo)}</span>
                <span>${escapeHtml(String(valor))}</span>
            </div>`)
        .join("");
}

// ============================================================
// MÓDULO FINANCEIRO — movimentações (somente administrador)
// ============================================================

const FINANCEIRO_LIMITE = 10;

let financeiroJaCarregado = false;
let financeiroCategorias = [];

const financeiroState = {
    filtros: { tipo: "", categoria_id: "", data_inicio: "", data_fim: "" },
    pagina: 1,
    totalPaginas: 1,
    total: 0,
    editId: null,
    deleteId: null,
    movimentacoes: [],
};

const TIPO_FINANCEIRO_LABEL = {
    entrada: "Entrada",
    saida: "Saída",
};

/**
 * Formata um valor decimal vindo da API (string, ex.: "1234.56") como
 * moeda brasileira. A API nunca recebe/devolve valor já formatado —
 * só o front-end formata para R$ 0,00, e só para exibição.
 */
function formatarMoeda(valorDecimalString) {
    const numero = Number(valorDecimalString);
    if (Number.isNaN(numero)) return "R$ 0,00";
    return numero.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

function formatarDataFinanceiro(dataIso) {
    if (!dataIso) return "-";
    const [ano, mes, dia] = dataIso.split("-");
    return `${dia}/${mes}/${ano}`;
}

async function carregarCategoriasFinanceiras() {
    const { ok, dados } = await apiListarCategoriasFinanceiras();
    if (ok && dados.success) {
        financeiroCategorias = dados.categorias;

        const select = document.getElementById("financeiroFiltroCategoria");
        select.querySelectorAll("option:not(:first-child)").forEach((o) => o.remove());
        financeiroCategorias.forEach((cat) => {
            const opt = document.createElement("option");
            opt.value = cat.id;
            opt.textContent = `${cat.nome} (${TIPO_FINANCEIRO_LABEL[cat.tipo]})`;
            select.appendChild(opt);
        });
    }
}

/**
 * Preenche o <select> de categoria do modal com apenas as categorias
 * do tipo (entrada/saida) selecionado no momento — o backend também
 * valida essa correspondência, isso aqui só evita erro óbvio na hora
 * de preencher o formulário.
 */
function popularSelectCategoriaFinanceiro(tipoSelecionado, categoriaIdAtual = null) {
    const select = document.getElementById("financeiroCategoria");
    select.innerHTML = "";
    financeiroCategorias
        .filter((cat) => cat.tipo === tipoSelecionado)
        .forEach((cat) => {
            const opt = document.createElement("option");
            opt.value = cat.id;
            opt.textContent = cat.nome;
            select.appendChild(opt);
        });
    if (categoriaIdAtual !== null) {
        select.value = categoriaIdAtual;
    }
}

async function loadResumoFinanceiro() {
    const { ok, dados } = await apiResumoFinanceiro({
        data_inicio: financeiroState.filtros.data_inicio,
        data_fim: financeiroState.filtros.data_fim,
    });

    if (!ok || !dados.success) return;

    document.getElementById("statFinanceiroEntradas").textContent = formatarMoeda(dados.total_entradas);
    document.getElementById("statFinanceiroSaidas").textContent = formatarMoeda(dados.total_saidas);
    document.getElementById("statFinanceiroSaldo").textContent = formatarMoeda(dados.saldo);
    document.getElementById("statFinanceiroQuantidade").textContent = dados.quantidade;
}

async function loadFinanceiro() {
    const tbody = document.getElementById("financeiroTableBody");
    tbody.innerHTML = `<tr class="empty-row"><td colspan="7">Carregando movimentações...</td></tr>`;

    const { ok, dados } = await apiListarFinanceiro({
        ...financeiroState.filtros,
        pagina: financeiroState.pagina,
        limite: FINANCEIRO_LIMITE,
    });

    if (!ok || !dados.success) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="7">Não foi possível carregar as movimentações.</td></tr>`;
        return;
    }

    // A página atual ficou vazia (ex.: arquivou o único item da última página): volta para a última existente.
    if (dados.movimentacoes.length === 0 && financeiroState.pagina > 1 && dados.total > 0) {
        financeiroState.pagina = dados.total_paginas;
        return loadFinanceiro();
    }

    financeiroState.movimentacoes = dados.movimentacoes;
    financeiroState.totalPaginas = dados.total_paginas || 1;
    financeiroState.total = dados.total || 0;

    if (dados.movimentacoes.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="7">Nenhuma movimentação encontrada com esses filtros.</td></tr>`;
    } else {
        tbody.innerHTML = dados.movimentacoes
            .map((mov) => {
                const corTipo = mov.tipo === "entrada" ? "status-ativo" : "status-suspenso";
                const sinal = mov.tipo === "entrada" ? "+" : "−";
                return `
                <tr data-id="${mov.id}">
                    <td class="td-joined">${formatarDataFinanceiro(mov.data_movimentacao)}</td>
                    <td><span class="status-badge ${corTipo}">${TIPO_FINANCEIRO_LABEL[mov.tipo]}</span></td>
                    <td>${escapeHtml(mov.categoria_nome)}</td>
                    <td class="td-user-email">${escapeHtml(mov.descricao)}</td>
                    <td>${sinal} ${formatarMoeda(mov.valor)}</td>
                    <td>${escapeHtml(mov.usuario_nome)}</td>
                    <td>
                        <div class="row-actions">
                            <button class="row-action-btn edit-btn" data-action="edit-financeiro" data-id="${mov.id}" title="Editar movimentação">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M9.5 1.5L12.5 4.5l-8.5 8.5L1 13l.5-2.5 8.5-8.5z"></path><path d="M7.5 3.5l3 3"></path></svg>
                            </button>
                            <button class="row-action-btn delete-btn" data-action="delete-financeiro" data-id="${mov.id}" title="Arquivar movimentação">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M1.5 3.5h11M4.5 3.5V2h5v1.5M5 3.5l.7 8h2.6l.7-8"></path></svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
            })
            .join("");
    }

    renderPaginacaoFinanceiro();
    loadResumoFinanceiro();

    tbody.querySelectorAll("[data-action='edit-financeiro']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalFinanceiro(parseInt(btn.dataset.id, 10)));
    });
    tbody.querySelectorAll("[data-action='delete-financeiro']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalExcluirFinanceiro(parseInt(btn.dataset.id, 10)));
    });
}

function renderPaginacaoFinanceiro() {
    const info = document.getElementById("financeiroPaginationInfo");
    if (financeiroState.total === 0) {
        info.textContent = "0 de 0";
    } else {
        const start = (financeiroState.pagina - 1) * FINANCEIRO_LIMITE + 1;
        const end = Math.min(financeiroState.pagina * FINANCEIRO_LIMITE, financeiroState.total);
        info.textContent = `${start}–${end} de ${financeiroState.total}`;
    }

    const btnsWrap = document.getElementById("financeiroPaginationButtons");
    const totalPaginas = financeiroState.totalPaginas;
    let html = `<button class="page-btn" data-page="prev" ${financeiroState.pagina === 1 ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11L5 7l4-4"></path></svg>
    </button>`;
    for (let p = 1; p <= totalPaginas; p++) {
        html += `<button class="page-btn ${p === financeiroState.pagina ? "active" : ""}" data-page="${p}">${p}</button>`;
    }
    html += `<button class="page-btn" data-page="next" ${financeiroState.pagina === totalPaginas ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 11l4-4-4-4"></path></svg>
    </button>`;
    btnsWrap.innerHTML = html;

    btnsWrap.querySelectorAll(".page-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const val = btn.dataset.page;
            if (val === "prev") financeiroState.pagina = Math.max(1, financeiroState.pagina - 1);
            else if (val === "next") financeiroState.pagina = Math.min(totalPaginas, financeiroState.pagina + 1);
            else financeiroState.pagina = parseInt(val, 10);
            loadFinanceiro();
        });
    });
}

async function initFinanceiro() {
    financeiroJaCarregado = true;

    await carregarCategoriasFinanceiras();

    document.getElementById("financeiroFiltroTipo").addEventListener("change", (e) => {
        financeiroState.filtros.tipo = e.target.value;
        financeiroState.pagina = 1;
        loadFinanceiro();
    });
    document.getElementById("financeiroFiltroCategoria").addEventListener("change", (e) => {
        financeiroState.filtros.categoria_id = e.target.value;
        financeiroState.pagina = 1;
        loadFinanceiro();
    });
    document.getElementById("financeiroFiltroDataInicio").addEventListener("change", (e) => {
        financeiroState.filtros.data_inicio = e.target.value;
        financeiroState.pagina = 1;
        loadFinanceiro();
    });
    document.getElementById("financeiroFiltroDataFim").addEventListener("change", (e) => {
        financeiroState.filtros.data_fim = e.target.value;
        financeiroState.pagina = 1;
        loadFinanceiro();
    });
    document.getElementById("btnLimparFiltrosFinanceiro").addEventListener("click", () => {
        financeiroState.filtros = { tipo: "", categoria_id: "", data_inicio: "", data_fim: "" };
        financeiroState.pagina = 1;
        document.getElementById("financeiroFiltroTipo").value = "";
        document.getElementById("financeiroFiltroCategoria").value = "";
        document.getElementById("financeiroFiltroDataInicio").value = "";
        document.getElementById("financeiroFiltroDataFim").value = "";
        loadFinanceiro();
    });

    loadFinanceiro();
}

// ============================================================
// MODAL: CRIAR/EDITAR MOVIMENTAÇÃO FINANCEIRA
// ============================================================
function limparErroFinanceiro() {
    const el = document.getElementById("financeiroFormError");
    el.style.display = "none";
    el.textContent = "";
}

function mostrarErroFinanceiro(mensagem) {
    const el = document.getElementById("financeiroFormError");
    el.textContent = mensagem;
    el.style.display = "block";
}

function abrirModalFinanceiro(id = null) {
    financeiroState.editId = id;
    limparErroFinanceiro();

    const titulo = document.getElementById("financeiroModalTitulo");
    const subtitulo = document.getElementById("financeiroModalSubtitulo");

    if (id === null) {
        titulo.textContent = "Nova movimentação";
        subtitulo.textContent = "Preencha os dados da movimentação";
        document.getElementById("financeiroTipo").value = "entrada";
        popularSelectCategoriaFinanceiro("entrada");
        document.getElementById("financeiroDescricao").value = "";
        document.getElementById("financeiroValor").value = "";
        document.getElementById("financeiroData").value = "";
        document.getElementById("financeiroOrigem").value = "";
    } else {
        const mov = financeiroState.movimentacoes.find((m) => m.id === id);
        if (!mov) return;
        titulo.textContent = "Editar movimentação";
        subtitulo.textContent = `#${mov.id}`;
        document.getElementById("financeiroTipo").value = mov.tipo;
        popularSelectCategoriaFinanceiro(mov.tipo, mov.categoria_id);
        document.getElementById("financeiroDescricao").value = mov.descricao;
        document.getElementById("financeiroValor").value = mov.valor;
        document.getElementById("financeiroData").value = mov.data_movimentacao;
        document.getElementById("financeiroOrigem").value = mov.origem || "";
    }

    document.getElementById("financeiroModalOverlay").classList.add("open");
}

document.getElementById("novaMovimentacaoBtn").addEventListener("click", () => abrirModalFinanceiro(null));

document.getElementById("financeiroTipo").addEventListener("change", (e) => {
    popularSelectCategoriaFinanceiro(e.target.value);
});

document.getElementById("salvarFinanceiroBtn").addEventListener("click", async () => {
    limparErroFinanceiro();

    const dadosMovimentacao = {
        tipo: document.getElementById("financeiroTipo").value,
        categoria_id: document.getElementById("financeiroCategoria").value,
        descricao: document.getElementById("financeiroDescricao").value.trim(),
        valor: document.getElementById("financeiroValor").value,
        data_movimentacao: document.getElementById("financeiroData").value,
        origem: document.getElementById("financeiroOrigem").value || null,
    };

    const btn = document.getElementById("salvarFinanceiroBtn");
    btn.disabled = true;
    btn.textContent = "Salvando...";

    try {
        const resultado = financeiroState.editId === null
            ? await apiCriarMovimentacaoFinanceira(dadosMovimentacao)
            : await apiAtualizarMovimentacaoFinanceira(financeiroState.editId, dadosMovimentacao);

        if (!resultado.success) {
            mostrarErroFinanceiro(resultado.message || "Não foi possível salvar a movimentação.");
            return;
        }

        closeModal("financeiro");
        await loadFinanceiro();

    } catch (erro) {
        mostrarErroFinanceiro("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Salvar movimentação";
    }
});

// ============================================================
// MODAL: ARQUIVAR MOVIMENTAÇÃO FINANCEIRA
// ============================================================
function abrirModalExcluirFinanceiro(id) {
    const mov = financeiroState.movimentacoes.find((m) => m.id === id);
    if (!mov) return;
    financeiroState.deleteId = id;
    document.getElementById("deleteFinanceiroDescricao").textContent = mov.descricao;
    document.getElementById("deleteFinanceiroModalOverlay").classList.add("open");
}

document.getElementById("confirmDeleteFinanceiroBtn").addEventListener("click", async () => {
    if (financeiroState.deleteId === null) return;

    const btn = document.getElementById("confirmDeleteFinanceiroBtn");
    btn.disabled = true;
    btn.textContent = "Arquivando...";

    try {
        const resultado = await apiExcluirMovimentacaoFinanceira(financeiroState.deleteId);

        if (!resultado.success) {
            alert(resultado.message || "Não foi possível arquivar a movimentação.");
            return;
        }

        financeiroState.deleteId = null;
        closeModal("delete-financeiro");
        await loadFinanceiro();

    } catch (erro) {
        alert("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Sim, arquivar";
    }
});

// ============================================================
// MÓDULO ORÇAMENTOS — orçamentos anuais e itens (somente administrador)
// ============================================================

const ORCAMENTOS_LIMITE = 10;

let orcamentosJaCarregado = false;
let orcamentoCategorias = [];

const orcamentosState = {
    filtros: { ano_referencia: "", status: "" },
    pagina: 1,
    totalPaginas: 1,
    total: 0,
    orcamentos: [],
    editId: null,
    deleteId: null,
    detalheId: null,
    itens: [],
    itemEditId: null,
    itemDeleteId: null,
};

const STATUS_ORCAMENTO_LABEL = {
    planejado: "Planejado",
    aprovado: "Aprovado",
    encerrado: "Encerrado",
};

const STATUS_ORCAMENTO_CLASSE = {
    planejado: "status-pendente",
    aprovado: "status-ativo",
    encerrado: "",
};

async function carregarCategoriasOrcamento() {
    const { ok, dados } = await apiListarCategoriasFinanceiras();
    if (!ok || !dados.success) return;

    orcamentoCategorias = dados.categorias;
    document.getElementById("orcamentoItemCategoria").innerHTML = orcamentoCategorias
        .map((cat) => `<option value="${cat.id}">${escapeHtml(cat.nome)} (${TIPO_FINANCEIRO_LABEL[cat.tipo]})</option>`)
        .join("");
}

async function loadOrcamentos() {
    const tbody = document.getElementById("orcamentosTableBody");
    tbody.innerHTML = `<tr class="empty-row"><td colspan="7">Carregando orçamentos...</td></tr>`;

    const { ok, dados } = await apiListarOrcamentos({
        ...orcamentosState.filtros,
        pagina: orcamentosState.pagina,
        limite: ORCAMENTOS_LIMITE,
    });

    if (!ok || !dados.success) {
        const msg = (dados && dados.message) || "Não foi possível carregar os orçamentos.";
        tbody.innerHTML = `<tr class="empty-row"><td colspan="7">${escapeHtml(msg)}</td></tr>`;
        return;
    }

    // A página atual ficou vazia (ex.: arquivou o último orçamento da última página)
    if (dados.orcamentos.length === 0 && orcamentosState.pagina > 1 && dados.total > 0) {
        orcamentosState.pagina = dados.total_paginas;
        return loadOrcamentos();
    }

    orcamentosState.orcamentos = dados.orcamentos;
    orcamentosState.totalPaginas = dados.total_paginas || 1;
    orcamentosState.total = dados.total || 0;

    document.getElementById("statOrcamentosQuantidade").textContent = dados.resumo.quantidade;
    document.getElementById("statOrcamentosValor").textContent = formatarMoeda(dados.resumo.soma_valor_total);
    document.getElementById("statOrcamentosAprovados").textContent = dados.resumo.aprovados;

    if (dados.orcamentos.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="7">Nenhum orçamento encontrado com esses filtros.</td></tr>`;
    } else {
        tbody.innerHTML = dados.orcamentos
            .map((orc) => `
                <tr data-id="${orc.id}">
                    <td class="td-user-email">${escapeHtml(orc.nome)}</td>
                    <td>${orc.ano_referencia}</td>
                    <td><span class="status-badge ${STATUS_ORCAMENTO_CLASSE[orc.status] || ""}">${STATUS_ORCAMENTO_LABEL[orc.status] || escapeHtml(orc.status)}</span></td>
                    <td>${orc.total_itens}</td>
                    <td>${formatarMoeda(orc.valor_total)}</td>
                    <td>${escapeHtml(orc.criado_por_nome)}</td>
                    <td>
                        <div class="row-actions">
                            <button class="row-action-btn" data-action="itens-orcamento" data-id="${orc.id}" title="Ver itens">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M1 7s2.2-4 6-4 6 4 6 4-2.2 4-6 4-6-4-6-4z"></path><circle cx="7" cy="7" r="1.6"></circle></svg>
                            </button>
                            <button class="row-action-btn edit-btn" data-action="edit-orcamento" data-id="${orc.id}" title="Editar orçamento">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M9.5 1.5L12.5 4.5l-8.5 8.5L1 13l.5-2.5 8.5-8.5z"></path><path d="M7.5 3.5l3 3"></path></svg>
                            </button>
                            <button class="row-action-btn delete-btn" data-action="delete-orcamento" data-id="${orc.id}" title="Arquivar orçamento">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M1.5 3.5h11M4.5 3.5V2h5v1.5M5 3.5l.7 8h2.6l.7-8"></path></svg>
                            </button>
                        </div>
                    </td>
                </tr>`)
            .join("");
    }

    renderPaginacaoOrcamentos();

    tbody.querySelectorAll("[data-action='itens-orcamento']").forEach((btn) => {
        btn.addEventListener("click", () => abrirDetalheOrcamento(parseInt(btn.dataset.id, 10)));
    });
    tbody.querySelectorAll("[data-action='edit-orcamento']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalOrcamento(parseInt(btn.dataset.id, 10)));
    });
    tbody.querySelectorAll("[data-action='delete-orcamento']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalExcluirOrcamento(parseInt(btn.dataset.id, 10)));
    });
}

function renderPaginacaoOrcamentos() {
    const info = document.getElementById("orcamentosPaginationInfo");
    if (orcamentosState.total === 0) {
        info.textContent = "0 de 0";
    } else {
        const start = (orcamentosState.pagina - 1) * ORCAMENTOS_LIMITE + 1;
        const end = Math.min(orcamentosState.pagina * ORCAMENTOS_LIMITE, orcamentosState.total);
        info.textContent = `${start}–${end} de ${orcamentosState.total}`;
    }

    const btnsWrap = document.getElementById("orcamentosPaginationButtons");
    const totalPaginas = orcamentosState.totalPaginas;
    let html = `<button class="page-btn" data-page="prev" ${orcamentosState.pagina === 1 ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11L5 7l4-4"></path></svg>
    </button>`;
    for (let p = 1; p <= totalPaginas; p++) {
        html += `<button class="page-btn ${p === orcamentosState.pagina ? "active" : ""}" data-page="${p}">${p}</button>`;
    }
    html += `<button class="page-btn" data-page="next" ${orcamentosState.pagina === totalPaginas ? "disabled" : ""}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 11l4-4-4-4"></path></svg>
    </button>`;
    btnsWrap.innerHTML = html;

    btnsWrap.querySelectorAll(".page-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const val = btn.dataset.page;
            if (val === "prev") orcamentosState.pagina = Math.max(1, orcamentosState.pagina - 1);
            else if (val === "next") orcamentosState.pagina = Math.min(totalPaginas, orcamentosState.pagina + 1);
            else orcamentosState.pagina = parseInt(val, 10);
            loadOrcamentos();
        });
    });
}

async function initOrcamentos() {
    orcamentosJaCarregado = true;

    await carregarCategoriasOrcamento();

    document.getElementById("orcamentoFiltroAno").addEventListener("change", (e) => {
        orcamentosState.filtros.ano_referencia = e.target.value;
        orcamentosState.pagina = 1;
        loadOrcamentos();
    });
    document.getElementById("orcamentoFiltroStatus").addEventListener("change", (e) => {
        orcamentosState.filtros.status = e.target.value;
        orcamentosState.pagina = 1;
        loadOrcamentos();
    });
    document.getElementById("btnLimparFiltrosOrcamento").addEventListener("click", () => {
        orcamentosState.filtros = { ano_referencia: "", status: "" };
        orcamentosState.pagina = 1;
        document.getElementById("orcamentoFiltroAno").value = "";
        document.getElementById("orcamentoFiltroStatus").value = "";
        loadOrcamentos();
    });

    loadOrcamentos();
}

// ============================================================
// MODAL: CRIAR/EDITAR ORÇAMENTO
// ============================================================
function limparErroOrcamento() {
    const el = document.getElementById("orcamentoFormError");
    el.style.display = "none";
    el.textContent = "";
}

function mostrarErroOrcamento(mensagem) {
    const el = document.getElementById("orcamentoFormError");
    el.textContent = mensagem;
    el.style.display = "block";
}

function abrirModalOrcamento(id = null) {
    orcamentosState.editId = id;
    limparErroOrcamento();

    const titulo = document.getElementById("orcamentoModalTitulo");
    const subtitulo = document.getElementById("orcamentoModalSubtitulo");

    if (id === null) {
        titulo.textContent = "Novo orçamento";
        subtitulo.textContent = "Preencha os dados do orçamento";
        document.getElementById("orcamentoNome").value = "";
        document.getElementById("orcamentoAno").value = new Date().getFullYear();
        document.getElementById("orcamentoStatus").value = "planejado";
        document.getElementById("orcamentoDescricao").value = "";
    } else {
        const orc = orcamentosState.orcamentos.find((o) => o.id === id);
        if (!orc) return;
        titulo.textContent = "Editar orçamento";
        subtitulo.textContent = `#${orc.id}`;
        document.getElementById("orcamentoNome").value = orc.nome;
        document.getElementById("orcamentoAno").value = orc.ano_referencia;
        document.getElementById("orcamentoStatus").value = orc.status;
        document.getElementById("orcamentoDescricao").value = orc.descricao || "";
    }

    document.getElementById("orcamentoModalOverlay").classList.add("open");
}

document.getElementById("novoOrcamentoBtn").addEventListener("click", () => abrirModalOrcamento(null));

document.getElementById("salvarOrcamentoBtn").addEventListener("click", async () => {
    limparErroOrcamento();

    // valor_total não é enviado: o backend calcula a partir dos itens.
    const dadosOrcamento = {
        nome: document.getElementById("orcamentoNome").value.trim(),
        ano_referencia: document.getElementById("orcamentoAno").value,
        status: document.getElementById("orcamentoStatus").value,
        descricao: document.getElementById("orcamentoDescricao").value.trim(),
    };

    const btn = document.getElementById("salvarOrcamentoBtn");
    btn.disabled = true;
    btn.textContent = "Salvando...";

    try {
        const eraNovo = orcamentosState.editId === null;
        const resultado = eraNovo
            ? await apiCriarOrcamento(dadosOrcamento)
            : await apiAtualizarOrcamento(orcamentosState.editId, dadosOrcamento);

        if (!resultado.success) {
            mostrarErroOrcamento(resultado.message || "Não foi possível salvar o orçamento.");
            return;
        }

        closeModal("orcamento");
        await loadOrcamentos();

        // Orçamento novo nasce sem itens: já abre a tela de itens para preencher.
        if (eraNovo && resultado.id) {
            abrirDetalheOrcamento(resultado.id);
        }

    } catch (erro) {
        mostrarErroOrcamento("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Salvar orçamento";
    }
});

// ============================================================
// MODAL: ARQUIVAR ORÇAMENTO
// ============================================================
function abrirModalExcluirOrcamento(id) {
    const orc = orcamentosState.orcamentos.find((o) => o.id === id);
    if (!orc) return;
    orcamentosState.deleteId = id;
    document.getElementById("deleteOrcamentoNome").textContent = orc.nome;
    document.getElementById("deleteOrcamentoModalOverlay").classList.add("open");
}

document.getElementById("confirmDeleteOrcamentoBtn").addEventListener("click", async () => {
    if (orcamentosState.deleteId === null) return;

    const btn = document.getElementById("confirmDeleteOrcamentoBtn");
    btn.disabled = true;
    btn.textContent = "Arquivando...";

    try {
        const resultado = await apiExcluirOrcamento(orcamentosState.deleteId);

        if (!resultado.success) {
            alert(resultado.message || "Não foi possível arquivar o orçamento.");
            return;
        }

        orcamentosState.deleteId = null;
        closeModal("delete-orcamento");
        await loadOrcamentos();

    } catch (erro) {
        alert("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Sim, arquivar";
    }
});

// ============================================================
// MODAL: ITENS DO ORÇAMENTO (listar / adicionar / editar / remover)
// ============================================================
function limparErroItemOrcamento() {
    const el = document.getElementById("orcamentoItensErro");
    el.style.display = "none";
    el.textContent = "";
}

function mostrarErroItemOrcamento(mensagem) {
    const el = document.getElementById("orcamentoItensErro");
    el.textContent = mensagem;
    el.style.display = "block";
}

function resetarFormItemOrcamento() {
    orcamentosState.itemEditId = null;
    document.getElementById("orcamentoItemDescricao").value = "";
    document.getElementById("orcamentoItemPrevisto").value = "";
    document.getElementById("orcamentoItemRealizado").value = "";
    const select = document.getElementById("orcamentoItemCategoria");
    if (select.options.length > 0) select.selectedIndex = 0;
    document.getElementById("orcamentoItemFormTitulo").textContent = "Adicionar item";
    document.getElementById("salvarOrcamentoItemBtn").textContent = "Adicionar item";
    document.getElementById("cancelarEdicaoItemBtn").style.display = "none";
}

async function abrirDetalheOrcamento(id) {
    orcamentosState.detalheId = id;
    orcamentosState.itens = [];
    limparErroItemOrcamento();

    if (orcamentoCategorias.length === 0) {
        await carregarCategoriasOrcamento();
    }
    resetarFormItemOrcamento();

    document.getElementById("orcamentoItensModalOverlay").classList.add("open");
    await recarregarItensOrcamento();
}

async function recarregarItensOrcamento() {
    const { ok, dados } = await apiListarItensOrcamento(orcamentosState.detalheId);

    if (!ok || !dados.success) {
        mostrarErroItemOrcamento((dados && dados.message) || "Não foi possível carregar os itens do orçamento.");
        return;
    }

    const orc = dados.orcamento;
    orcamentosState.itens = dados.itens;

    document.getElementById("orcamentoItensTitulo").textContent = orc.nome;
    document.getElementById("orcamentoItensSubtitulo").textContent =
        `#${orc.id} · ${orc.ano_referencia} · ${STATUS_ORCAMENTO_LABEL[orc.status] || orc.status}`;
    document.getElementById("orcResumoTotal").textContent = formatarMoeda(orc.valor_total);
    document.getElementById("orcResumoSoma").textContent = formatarMoeda(orc.soma_previsto);
    document.getElementById("orcResumoRealizado").textContent = formatarMoeda(orc.soma_realizado);
    document.getElementById("orcResumoQtd").textContent = orc.total_itens;

    renderItensOrcamento();
}

function renderItensOrcamento() {
    const tbody = document.getElementById("orcamentoItensBody");
    const itens = orcamentosState.itens;

    if (itens.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="5">Nenhum item neste orçamento ainda.</td></tr>`;
        return;
    }

    tbody.innerHTML = itens
        .map((item) => `
            <tr data-item-id="${item.id}">
                <td>${escapeHtml(item.categoria_nome)} <span class="status-badge ${item.categoria_tipo === "entrada" ? "status-ativo" : "status-suspenso"}">${TIPO_FINANCEIRO_LABEL[item.categoria_tipo]}</span></td>
                <td class="td-user-email">${escapeHtml(item.descricao)}</td>
                <td>${formatarMoeda(item.valor_previsto)}</td>
                <td>${formatarMoeda(item.valor_realizado)}</td>
                <td>
                    <div class="row-actions">
                        <button class="row-action-btn edit-btn" data-action="edit-item-orcamento" data-id="${item.id}" title="Editar item">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M9.5 1.5L12.5 4.5l-8.5 8.5L1 13l.5-2.5 8.5-8.5z"></path><path d="M7.5 3.5l3 3"></path></svg>
                        </button>
                        <button class="row-action-btn delete-btn" data-action="delete-item-orcamento" data-id="${item.id}" title="Remover item">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M1.5 3.5h11M4.5 3.5V2h5v1.5M5 3.5l.7 8h2.6l.7-8"></path></svg>
                        </button>
                    </div>
                </td>
            </tr>`)
        .join("");

    tbody.querySelectorAll("[data-action='edit-item-orcamento']").forEach((btn) => {
        btn.addEventListener("click", () => iniciarEdicaoItemOrcamento(parseInt(btn.dataset.id, 10)));
    });
    tbody.querySelectorAll("[data-action='delete-item-orcamento']").forEach((btn) => {
        btn.addEventListener("click", () => abrirModalExcluirItemOrcamento(parseInt(btn.dataset.id, 10)));
    });
}

function iniciarEdicaoItemOrcamento(itemId) {
    const item = orcamentosState.itens.find((i) => i.id === itemId);
    if (!item) return;

    limparErroItemOrcamento();
    orcamentosState.itemEditId = itemId;

    document.getElementById("orcamentoItemCategoria").value = item.categoria_id;
    document.getElementById("orcamentoItemDescricao").value = item.descricao;
    document.getElementById("orcamentoItemPrevisto").value = item.valor_previsto;
    document.getElementById("orcamentoItemRealizado").value = item.valor_realizado;
    document.getElementById("orcamentoItemFormTitulo").textContent = `Editar item #${item.id}`;
    document.getElementById("salvarOrcamentoItemBtn").textContent = "Salvar item";
    document.getElementById("cancelarEdicaoItemBtn").style.display = "";
}

document.getElementById("cancelarEdicaoItemBtn").addEventListener("click", () => {
    limparErroItemOrcamento();
    resetarFormItemOrcamento();
});

document.getElementById("salvarOrcamentoItemBtn").addEventListener("click", async () => {
    limparErroItemOrcamento();

    const orcamentoId = orcamentosState.detalheId;
    if (orcamentoId === null) return;

    const dadosItem = {
        categoria_id: document.getElementById("orcamentoItemCategoria").value,
        descricao: document.getElementById("orcamentoItemDescricao").value.trim(),
        valor_previsto: document.getElementById("orcamentoItemPrevisto").value,
        valor_realizado: document.getElementById("orcamentoItemRealizado").value,
    };

    const btn = document.getElementById("salvarOrcamentoItemBtn");
    btn.disabled = true;
    btn.textContent = "Salvando...";

    try {
        const resultado = orcamentosState.itemEditId === null
            ? await apiCriarItemOrcamento(orcamentoId, dadosItem)
            : await apiAtualizarItemOrcamento(orcamentoId, orcamentosState.itemEditId, dadosItem);

        if (!resultado.success) {
            mostrarErroItemOrcamento(resultado.message || "Não foi possível salvar o item.");
            return;
        }

        resetarFormItemOrcamento();
        await recarregarItensOrcamento();
        loadOrcamentos(); // atualiza total e quantidade de itens na listagem

    } catch (erro) {
        mostrarErroItemOrcamento("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = orcamentosState.itemEditId === null ? "Adicionar item" : "Salvar item";
    }
});

function abrirModalExcluirItemOrcamento(itemId) {
    const item = orcamentosState.itens.find((i) => i.id === itemId);
    if (!item) return;
    orcamentosState.itemDeleteId = itemId;
    document.getElementById("deleteOrcamentoItemDescricao").textContent = item.descricao;
    document.getElementById("deleteOrcamentoItemModalOverlay").classList.add("open");
}

document.getElementById("confirmDeleteOrcamentoItemBtn").addEventListener("click", async () => {
    const itemId = orcamentosState.itemDeleteId;
    const orcamentoId = orcamentosState.detalheId;
    if (itemId === null || orcamentoId === null) return;

    const btn = document.getElementById("confirmDeleteOrcamentoItemBtn");
    btn.disabled = true;
    btn.textContent = "Removendo...";

    try {
        const resultado = await apiExcluirItemOrcamento(orcamentoId, itemId);

        orcamentosState.itemDeleteId = null;
        closeModal("delete-orcamento-item");

        if (!resultado.success) {
            mostrarErroItemOrcamento(resultado.message || "Não foi possível remover o item.");
            return;
        }

        if (orcamentosState.itemEditId === itemId) {
            resetarFormItemOrcamento();
        }
        await recarregarItensOrcamento();
        loadOrcamentos();

    } catch (erro) {
        closeModal("delete-orcamento-item");
        mostrarErroItemOrcamento("Não foi possível conectar ao servidor.");
    } finally {
        btn.disabled = false;
        btn.textContent = "Sim, remover";
    }
});


// ============================================================
// DASHBOARD — visão geral (GET /api/admin/resumo)
// ============================================================
async function carregarResumoDashboard() {
    const corpo = document.getElementById("resumoAtividadeBody");
    const { ok, dados } = await apiFetch("/admin/resumo");

    if (!ok || !dados.success) {
        corpo.innerHTML = `<tr class="empty-row"><td colspan="4">Não foi possível carregar o resumo.</td></tr>`;
        return;
    }

    const set = (id, texto) => { document.getElementById(id).textContent = texto; };

    set("resumoSaldo", formatarMoeda(dados.financeiro.saldo));
    set("resumoEntradas", formatarMoeda(dados.financeiro.entradas));
    set("resumoSaidas", `saídas: ${formatarMoeda(dados.financeiro.saidas)}`);
    set("resumoUsuarios", dados.usuarios.ativos);
    set("resumoUsuariosSub", `cadastros pendentes: ${dados.usuarios.pendentes}`);
    set("resumoEventos", dados.eventos.total);
    set("resumoEventosSub", `planejados: ${dados.eventos.planejados}`);
    set("resumoProjetos", dados.projetos.total);
    set("resumoProjetosSub", `em andamento: ${dados.projetos.em_andamento}`);
    set("resumoVotacoes", dados.votacoes.total);
    set("resumoVotacoesSub", `abertas: ${dados.votacoes.abertas}`);
    set("resumoPublicacoes", dados.publicacoes.total);
    set("resumoOrcamentos", dados.orcamentos.total);
    set("resumoOrcamentosSub", `aprovados: ${dados.orcamentos.aprovados}`);

    if (!dados.atividade_recente.length) {
        corpo.innerHTML = `<tr class="empty-row"><td colspan="4">Nenhuma atividade registrada ainda.</td></tr>`;
        return;
    }

    corpo.innerHTML = dados.atividade_recente.map((l) => `
        <tr>
            <td>${escapeHtml(formatarDataHoraAuditoria(l.created_at))}</td>
            <td>${escapeHtml(l.usuario_nome || "Sistema")}</td>
            <td>${escapeHtml(l.modulo || "-")}</td>
            <td>${escapeHtml(l.descricao || "-")}</td>
        </tr>
    `).join("");
}
