// ============================================================
// notificacoes.js — Módulo compartilhado do sino de notificações.
//
// Usado tanto no dashboard (administrador) quanto no feed (index.php).
// Depende de js/api.js já estar carregado antes deste arquivo, e
// espera encontrar no HTML da página:
//
//   #btnNotificacoes      -> botão do sino (já existente no topbar)
//   #notificacoesBadge    -> bolinha/contador de não lidas (dentro do botão)
//   #notificacoesDropdown -> painel dropdown (lista)
//   #notificacoesLista    -> container onde os itens são renderizados
//   #btnMarcarTodasLidas  -> botão "marcar todas como lidas"
//
// Não reimplementa autenticação nem uma segunda camada de API: usa
// apenas as funções já expostas por js/api.js (apiListarNotificacoes,
// apiContarNotificacoesNaoLidas, apiMarcarNotificacaoLida,
// apiMarcarTodasNotificacoesLidas).
// ============================================================

(function () {
    const POLL_INTERVAL_MS = 30000;

    const TIPO_ICONE = {
        sistema: "⚙️",
        publicacao: "📰",
        evento: "📅",
        votacao: "🗳️",
        projeto: "📁",
    };

    let carregando = false;
    let abas = null;

    function formatarDataRelativa(dataIso) {
        if (!dataIso) return "";
        const data = new Date(dataIso.replace(" ", "T"));
        if (isNaN(data.getTime())) return "";

        const diffMs = Date.now() - data.getTime();
        const diffMin = Math.floor(diffMs / 60000);

        if (diffMin < 1) return "agora";
        if (diffMin < 60) return `há ${diffMin} min`;
        const diffHoras = Math.floor(diffMin / 60);
        if (diffHoras < 24) return `há ${diffHoras}h`;
        const diffDias = Math.floor(diffHoras / 24);
        if (diffDias < 7) return `há ${diffDias}d`;
        return data.toLocaleDateString("pt-BR");
    }

    function escaparHtml(str) {
        const div = document.createElement("div");
        div.textContent = str ?? "";
        return div.innerHTML;
    }

    function atualizarBadge(total) {
        const badge = document.getElementById("notificacoesBadge");
        if (!badge) return;

        if (total > 0) {
            badge.style.display = "";
            badge.textContent = total > 9 ? "9+" : String(total);
            badge.setAttribute("data-tem-nao-lidas", "1");
        } else {
            badge.style.display = "none";
            badge.removeAttribute("data-tem-nao-lidas");
        }
    }

    async function atualizarContador() {
        const { ok, dados } = await apiContarNotificacoesNaoLidas();
        if (ok && dados.success) {
            atualizarBadge(dados.total);
        }
    }

    function renderLista(notificacoes) {
        const lista = document.getElementById("notificacoesLista");
        if (!lista) return;

        if (!notificacoes.length) {
            lista.innerHTML = `<div class="notif-vazio">Nenhuma notificação por aqui.</div>`;
            return;
        }

        lista.innerHTML = notificacoes
            .map((n) => {
                const icone = TIPO_ICONE[n.tipo] || "🔔";
                const classeNaoLida = n.lida ? "" : "notif-item-nao-lida";
                return `
                <div class="notif-item ${classeNaoLida}" data-id="${n.id}" data-lida="${n.lida ? 1 : 0}">
                    <span class="notif-item-icone">${icone}</span>
                    <div class="notif-item-corpo">
                        <p class="notif-item-titulo">${escaparHtml(n.titulo)}</p>
                        <p class="notif-item-mensagem">${escaparHtml(n.mensagem)}</p>
                        <span class="notif-item-data">${formatarDataRelativa(n.created_at)}</span>
                    </div>
                    ${n.lida ? "" : '<span class="notif-item-dot"></span>'}
                </div>`;
            })
            .join("");
    }

    async function carregarLista() {
        if (carregando) return;
        carregando = true;

        const lista = document.getElementById("notificacoesLista");
        if (lista) {
            lista.innerHTML = `<div class="notif-vazio">Carregando...</div>`;
        }

        try {
            const { ok, dados } = await apiListarNotificacoes(false);
            if (ok && dados.success) {
                renderLista(dados.notificacoes);
            } else if (lista) {
                lista.innerHTML = `<div class="notif-vazio">Não foi possível carregar as notificações.</div>`;
            }
        } catch (e) {
            if (lista) {
                lista.innerHTML = `<div class="notif-vazio">Não foi possível conectar ao servidor.</div>`;
            }
        } finally {
            carregando = false;
        }
    }

    function alternarDropdown(forcarFechar = false) {
        const dropdown = document.getElementById("notificacoesDropdown");
        if (!dropdown) return;

        const estaAberto = dropdown.classList.contains("open");

        if (forcarFechar || estaAberto) {
            dropdown.classList.remove("open");
            return;
        }

        dropdown.classList.add("open");
        carregarLista();
    }

    async function marcarItemComoLido(itemEl) {
        const id = itemEl.getAttribute("data-id");
        const jaLida = itemEl.getAttribute("data-lida") === "1";
        if (!id || jaLida) return;

        // Otimista: já atualiza a UI, sem esperar a rede.
        itemEl.classList.remove("notif-item-nao-lida");
        itemEl.setAttribute("data-lida", "1");
        const dot = itemEl.querySelector(".notif-item-dot");
        if (dot) dot.remove();

        const dados = await apiMarcarNotificacaoLida(id);
        if (dados && dados.success) {
            atualizarContador();
        }
    }

    async function marcarTodasComoLidas() {
        const dados = await apiMarcarTodasNotificacoesLidas();
        if (dados && dados.success) {
            document.querySelectorAll(".notif-item-nao-lida").forEach((el) => {
                el.classList.remove("notif-item-nao-lida");
                el.setAttribute("data-lida", "1");
                const dot = el.querySelector(".notif-item-dot");
                if (dot) dot.remove();
            });
            atualizarBadge(0);
        }
    }

    function initNotificacoes() {
        const sessao = typeof obterSessao === "function" ? obterSessao() : null;
        if (!sessao) return; // página não está logada; nada a fazer aqui

        const btnSino = document.getElementById("btnNotificacoes");
        const dropdown = document.getElementById("notificacoesDropdown");
        const btnMarcarTodas = document.getElementById("btnMarcarTodasLidas");
        const lista = document.getElementById("notificacoesLista");

        if (!btnSino || !dropdown) return; // página não tem o componente

        btnSino.addEventListener("click", (evento) => {
            evento.stopPropagation();
            alternarDropdown();
        });

        dropdown.addEventListener("click", (evento) => evento.stopPropagation());

        document.addEventListener("click", () => alternarDropdown(true));

        if (btnMarcarTodas) {
            btnMarcarTodas.addEventListener("click", (evento) => {
                evento.stopPropagation();
                marcarTodasComoLidas();
            });
        }

        if (lista) {
            lista.addEventListener("click", (evento) => {
                const item = evento.target.closest(".notif-item");
                if (item) marcarItemComoLido(item);
            });
        }

        atualizarContador();
        setInterval(atualizarContador, POLL_INTERVAL_MS);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initNotificacoes);
    } else {
        initNotificacoes();
    }
})();
