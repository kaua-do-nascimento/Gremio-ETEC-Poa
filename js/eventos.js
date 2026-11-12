
// ===================== CONFIGURAÇÕES =====================

const nomesMeses = [
    "Janeiro", "Fevereiro", "Março",
    "Abril", "Maio", "Junho",
    "Julho", "Agosto", "Setembro",
    "Outubro", "Novembro", "Dezembro"
];

const diasSemana = ["D", "S", "T", "Q", "Q", "S", "S"];

const hoje = new Date();

let anoAtual = hoje.getFullYear();
let dataSelecionada = new Date(
    hoje.getFullYear(),
    hoje.getMonth(),
    hoje.getDate()
);

let filtroAtual = "todos";

// ===================== DADOS DE DEMONSTRAÇÃO =====================

// Exemplos temporários. Depois serão substituídos pelos dados da API.

const atividades = [
    {
        id: 1,
        tipo: "evento",
        titulo: "Semana Paulo Freire",
        descricao: "Atividades e apresentações organizadas pela comunidade escolar.",
        inicio: "2026-11-18",
        fim: "2026-11-18",
        horario: "09:00",
        local: "Pátio da escola"
    },
    {
        id: 2,
        tipo: "evento",
        titulo: "Encontro da comunidade",
        descricao: "Momento de integração entre os alunos.",
        inicio: "2026-11-22",
        fim: "2026-11-22",
        horario: "10:00",
        local: "Auditório"
    },
    {
        id: 3,
        tipo: "projeto",
        titulo: "Reforma da quadra",
        descricao: "Iniciativa para melhorias no espaço esportivo da escola.",
        inicio: "2026-11-10",
        fim: "2026-11-30",
        horario: "",
        local: "Quadra da escola"
    },
    {
        id: 4,
        tipo: "projeto",
        titulo: "Campanha de arrecadação",
        descricao: "Projeto de arrecadação e organização de doações.",
        inicio: "2026-12-01",
        fim: "2026-12-15",
        horario: "",
        local: "Comunidade escolar"
    }
];

// ===================== FUNÇÕES DE DATA =====================

function formatarData(data) {
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, "0");
    const dia = String(data.getDate()).padStart(2, "0");

    return `${ano}-${mes}-${dia}`;
}

function dataBrasileira(data) {
    return new Date(`${data}T12:00:00`).toLocaleDateString("pt-BR");
}

function atividadeAconteceNoDia(atividade, data) {
    return data >= atividade.inicio && data <= atividade.fim;
}

// ===================== CALENDÁRIO =====================

function criarMes(mes, ano) {
    const primeiroDia = new Date(ano, mes, 1).getDay();
    const totalDias = new Date(ano, mes + 1, 0).getDate();

    const card = document.createElement("div");
    card.className = "month-card";

    let html = `
        <h3 class="month-title">${nomesMeses[mes]}</h3>
        <div class="month-grid">
    `;

    diasSemana.forEach(dia => {
        html += `<span class="weekday">${dia}</span>`;
    });

    for (let i = 0; i < primeiroDia; i++) {
        html += `<span class="calendar-day empty"></span>`;
    }

    for (let dia = 1; dia <= totalDias; dia++) {
        const data = formatarData(new Date(ano, mes, dia));

        const temEvento = atividades.some(a =>
            a.tipo === "evento" && atividadeAconteceNoDia(a, data)
        );

        const temProjeto = atividades.some(a =>
            a.tipo === "projeto" && atividadeAconteceNoDia(a, data)
        );

        const classes = ["calendar-day"];

        if (data === formatarData(hoje)) {
            classes.push("today");
        }

        if (data === formatarData(dataSelecionada)) {
            classes.push("selected");
        }

        if (temEvento) classes.push("has-event");
        if (temProjeto) classes.push("has-project");

        html += `
            <button
                type="button"
                class="${classes.join(" ")}"
                data-date="${data}"
                aria-label="${dia} de ${nomesMeses[mes]}"
            >
                ${dia}
            </button>
        `;
    }

    html += `</div>`;

    card.innerHTML = html;

    return card;
}

function renderCalendario() {
    const container = document.getElementById("calendarContainer");

    container.innerHTML = "";

    document.getElementById("currentYear").textContent = anoAtual;

    for (let mes = 0; mes < 12; mes++) {
        container.appendChild(criarMes(mes, anoAtual));
    }

    atualizarAtividades();
}

// ===================== ATIVIDADES =====================

function atualizarAtividades() {
    const data = formatarData(dataSelecionada);

    let lista = atividades.filter(atividade =>
        atividadeAconteceNoDia(atividade, data)
    );

    if (filtroAtual !== "todos") {
        lista = lista.filter(a => a.tipo === filtroAtual);
    }

    const container = document.getElementById("activitiesList");

    document.getElementById("activitiesTitle").textContent =
        "Atividades do dia";

    document.getElementById("activitiesSubtitle").textContent =
        dataSelecionada.toLocaleDateString("pt-BR", {
            day: "numeric",
            month: "long",
            year: "numeric"
        });

    if (lista.length === 0) {
        container.innerHTML = `
            <div class="empty-activities">
                Nenhuma atividade encontrada para esta data.
            </div>
        `;

        return;
    }

    container.innerHTML = lista.map(atividade => {
        const tipo = atividade.tipo === "evento" ? "EVENTO" : "PROJETO";

        let periodo = dataBrasileira(atividade.inicio);

        if (atividade.inicio !== atividade.fim) {
            periodo += ` até ${dataBrasileira(atividade.fim)}`;
        }

        const horario = atividade.horario
            ? ` · ${atividade.horario}`
            : "";

        return `
            <article class="activity-card">

                <div class="activity-info">

                    <span class="activity-type ${atividade.tipo}">
                        ${tipo}
                    </span>

                    <h3>${atividade.titulo}</h3>

                    <p>${atividade.descricao}</p>

                    <p>${atividade.local || "Local a definir"}</p>

                </div>

                <span class="activity-date">
                    ${periodo}${horario}
                </span>

            </article>
        `;
    }).join("");
}

// ===================== CLIQUE NO CALENDÁRIO =====================

document.getElementById("calendarContainer").addEventListener("click", (e) => {
    const botao = e.target.closest(".calendar-day[data-date]");

    if (!botao) return;

    const [ano, mes, dia] = botao.dataset.date.split("-").map(Number);

    dataSelecionada = new Date(ano, mes - 1, dia);

    renderCalendario();
});

// ===================== FILTROS =====================

document.querySelectorAll(".filter-button").forEach(botao => {
    botao.addEventListener("click", () => {
        filtroAtual = botao.dataset.filter;

        document.querySelectorAll(".filter-button").forEach(b => {
            b.classList.remove("active");
        });

        botao.classList.add("active");

        atualizarAtividades();
    });
});

// ===================== TROCA DE ANO =====================

document.getElementById("prevYear").addEventListener("click", () => {
    anoAtual--;

    dataSelecionada = new Date(anoAtual, 0, 1);

    renderCalendario();
});

document.getElementById("nextYear").addEventListener("click", () => {
    anoAtual++;

    dataSelecionada = new Date(anoAtual, 0, 1);

    renderCalendario();
});

// ===================== INICIALIZAÇÃO =====================

renderCalendario();
