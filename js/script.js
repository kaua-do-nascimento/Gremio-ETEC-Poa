// ===================== DATA =====================

// Os posts agora vêm do banco de dados real via GET /api/publicacoes.
// Começa vazio e é populado por loadPosts() no carregamento da página.
let posts = [];

const trendingTags = [
  { name: "Etec", posts: 142 },
  { name: "Gremio", posts: 98 },
  { name: "Semana Paulo Freire", posts: 87 },
  { name: "Reforma da quadra", posts: 65 },
  { name: "ENEM", posts: 54 },
  { name: "Provão Paulista", posts: 41 },
];

const activityStats = [
  { label: "Posts", value: "12" },
  { label: "Seguidores", value: "84" },
  { label: "Curtidas", value: "231" },
];

let searchQuery = "";
let activeTab = "para-voce";
let newPostTags = [];

// ===================== INTEGRAÇÃO COM O BACKEND =====================

/**
 * Calcula as iniciais de um nome (ex.: "Administrador Geral" -> "AG").
 */
function calcularIniciaisAutor(nome) {
  const partes = (nome || "").trim().split(/\s+/);
  const primeira = partes[0]?.[0] || "";
  const segunda = partes.length > 1 ? partes[partes.length - 1][0] : "";
  return (primeira + segunda).toUpperCase();
}

/**
 * Converte uma data do banco (ex.: "2026-09-15 10:30:00") em texto
 * relativo (ex.: "Há 2 horas", "Ontem", "12/09/2026").
 */
function formatarTempoRelativo(dataIso) {
  if (!dataIso) return "";

  const data = new Date(dataIso.replace(" ", "T"));
  if (isNaN(data.getTime())) return "";

  const agora = new Date();
  const diffMs = agora - data;
  const diffMin = Math.floor(diffMs / 60000);
  const diffHoras = Math.floor(diffMin / 60);
  const diffDias = Math.floor(diffHoras / 24);

  if (diffMin < 1) return "Agora mesmo";
  if (diffMin < 60) return `Há ${diffMin} min`;
  if (diffHoras < 24) return `Há ${diffHoras} h`;
  if (diffDias === 1) return "Ontem";
  if (diffDias < 7) return `Há ${diffDias} dias`;

  return data.toLocaleDateString("pt-BR");
}

/**
 * Converte o formato do backend (GET /api/publicacoes) para o
 * formato que renderPosts() já sabia desenhar.
 *
 * OBS: "comentários" e "salvos" não têm tabela no banco de dados
 * atual, então ficam sempre com valor zero/false — são apenas
 * visuais por enquanto (ver auditoria do módulo de Publicações).
 */
function mapearPublicacaoDoBackend(p) {
  return {
    id: String(p.id),
    title: p.titulo,
    excerpt: p.conteudo,
    author: {
      name: p.autor.nome,
      avatar: p.autor.foto_url || "imgs/gremio icon.png",
      initials: calcularIniciaisAutor(p.autor.nome),
    },
    publishedAt: formatarTempoRelativo(p.publicado_em),
    readTime: p.tempo_leitura,
    tags: p.categoria ? [p.categoria] : [],
    coverImage: p.imagem_url || null,
    likes: p.total_curtidas,
    comments: 0,
    bookmarked: false,
    liked: p.curtido_por_mim,
  };
}

/**
 * Busca as publicações reais do banco e renderiza o feed.
 */
async function loadPosts() {
  const container = document.getElementById("postsContainer");
  container.innerHTML = `<p class="post-loading">Carregando publicações...</p>`;

  const { ok, dados } = await apiListarPublicacoes();

  if (!ok || !dados.success) {
    container.innerHTML = `<p class="post-loading">Não foi possível carregar as publicações.</p>`;
    return;
  }

  posts = dados.publicacoes.map(mapearPublicacaoDoBackend);
  renderPosts();
}

/**
 * Preenche o seletor de categorias do formulário de criar post e
 * mostra o compositor apenas para administradores (RBAC: somente
 * administrador pode criar publicações).
 */
async function initComposerRBAC() {
  const sessao = obterSessao();
  if (!sessao || !sessao.perfis || !sessao.perfis.includes("administrador")) {
    return; // composer fica oculto (display:none já é o padrão no HTML)
  }

  const { ok, dados } = await apiListarCategoriasPublicacao();
  if (ok && dados.success) {
    const select = document.getElementById("postCategoriaSelect");
    dados.categorias.forEach((categoria) => {
      const option = document.createElement("option");
      option.value = categoria.id;
      option.textContent = categoria.nome;
      select.appendChild(option);
    });
  }

  document.getElementById("composerCard").style.display = "block";
}

// ===================== ICONS =====================

const icons = {
  clock: `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
  heart: (filled) => `<svg width="14" height="14" viewBox="0 0 24 24" fill="${filled ? "currentColor" : "none"}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>`,
  message: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>`,
  bookmark: (filled) => `<svg width="14" height="14" viewBox="0 0 24 24" fill="${filled ? "currentColor" : "none"}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>`,
  more: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>`,
  hash: `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="9" y2="9"/><line x1="4" x2="20" y1="15" y2="15"/><line x1="10" x2="8" y1="3" y2="21"/><line x1="16" x2="14" y1="3" y2="21"/></svg>`,
};

function escapeHtml(str) {
  const div = document.createElement("div");
  div.textContent = str;
  return div.innerHTML;
}

// ===================== RENDER: POSTS =====================

function renderPosts() {
  const container = document.getElementById("postsContainer");
  const emptyState = document.getElementById("emptyState");

  const filtered = posts.filter((p) => {
    if (!searchQuery) return true;
    const q = searchQuery.toLowerCase();
    return (
      p.title.toLowerCase().includes(q) ||
      p.excerpt.toLowerCase().includes(q) ||
      p.tags.some((t) => t.toLowerCase().includes(q))
    );
  });

  const tabFiltered = activeTab === "salvos" ? filtered.filter((p) => p.bookmarked) : filtered;

  if (tabFiltered.length === 0) {
    container.style.display = "none";
    emptyState.style.display = "block";
    emptyState.textContent = searchQuery ? `Nenhum resultado para "${searchQuery}"` : "Nenhum post ainda.";
    return;
  }

  container.style.display = "flex";
  emptyState.style.display = "none";

  container.innerHTML = tabFiltered
    .map((post) => {
      const tagsHtml = post.tags
        .map((tag) => `<span class="badge badge-secondary badge-tag">${escapeHtml(tag)}</span>`)
        .join("");

      const coverHtml = post.coverImage
        ? `<div class="post-cover"><img src="${post.coverImage}" alt="${escapeHtml(post.title)}" /></div>`
        : "";

      return `
      <div class="card post-card">
        <div class="post-card-inner">
          <div class="post-card-content">
            <div class="post-meta">
              <span class="avatar avatar-sm">
                <img src="${post.author.avatar}" alt="${escapeHtml(post.author.name)}" />
              </span>
              <span class="post-author-name">${escapeHtml(post.author.name)}</span>
              <span class="post-dot">·</span>
              <span class="post-time">${escapeHtml(post.publishedAt)}</span>
              <span class="post-readtime">${icons.clock}${post.readTime} min</span>
            </div>

            <h2 class="post-title">${escapeHtml(post.title)}</h2>
            <p class="post-excerpt">${escapeHtml(post.excerpt)}</p>

            <div class="post-footer">
              ${tagsHtml}
              <div class="post-actions">
                <button class="post-action-btn ${post.liked ? "liked" : ""}" data-action="like" data-id="${post.id}">
                  ${icons.heart(post.liked)} ${post.likes}
                </button>
                <button class="post-action-btn" data-action="comment">
                  ${icons.message} ${post.comments}
                </button>
                <button class="post-action-btn ${post.bookmarked ? "bookmarked" : ""}" data-action="bookmark" data-id="${post.id}">
                  ${icons.bookmark(post.bookmarked)}
                </button>
                <div class="dropdown" data-post-menu="${post.id}">
                  <button class="post-action-btn" data-action="menu-toggle">${icons.more}</button>
                  <div class="dropdown-menu">
                    <button class="dropdown-item">Copiar link</button>
                    <button class="dropdown-item">Compartilhar</button>
                    <button class="dropdown-item destructive">Denunciar</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          ${coverHtml}
        </div>
      </div>`;
    })
    .join("");
}

// ===================== RENDER: SIDEBAR =====================

function renderTrending() {
  const el = document.getElementById("trendingList");
  el.innerHTML = trendingTags
    .map(
      (tag, index) => `
    <button class="trend-row">
      <span class="trend-left">
        <span class="trend-index">0${index + 1}</span>
        ${icons.hash}
        <span class="trend-name">${escapeHtml(tag.name)}</span>
      </span>
      <span class="trend-count">${tag.posts}</span>
    </button>`
    )
    .join("");
}

function renderActivity() {
  const el = document.getElementById("activityGrid");
  el.innerHTML = activityStats
    .map(
      (s) => `
    <div class="activity-stat">
      <p class="activity-value">${s.value}</p>
      <p class="activity-label">${s.label}</p>
    </div>`
    )
    .join("");
}

const monthNames = [
  "janeiro", "fevereiro", "março", "abril", "maio", "junho",
  "julho", "agosto", "setembro", "outubro", "novembro", "dezembro",
];

// Real "today" for highlighting + the fixed community event days (Aug 2026)
const REAL_TODAY = new Date();
const EVENT_MONTH = 7; // August (0-indexed)
const EVENT_YEAR = 2026;
const EVENT_DAYS = [18, 22];

let calViewYear = EVENT_YEAR;
let calViewMonth = EVENT_MONTH;
let selectedDays = new Set(EVENT_DAYS); // toggled by clicking a day

function buildMonthWeeks(year, month) {
  const firstDay = new Date(year, month, 1);
  const startWeekday = firstDay.getDay(); // 0 = Sunday
  const daysInMonth = new Date(year, month + 1, 0).getDate();

  const cells = [];
  for (let i = 0; i < startWeekday; i++) cells.push(0);
  for (let d = 1; d <= daysInMonth; d++) cells.push(d);
  while (cells.length % 7 !== 0) cells.push(0);

  const weeks = [];
  for (let i = 0; i < cells.length; i += 7) weeks.push(cells.slice(i, i + 7));
  return weeks;
}

function renderCalendar() {
  const headEl = document.getElementById("calHead");
  const daysEl = document.getElementById("calDays");
  const captionEl = document.getElementById("calCaption");
  const weekLabels = ["do", "se", "te", "qu", "qu", "se", "sa"];
  headEl.innerHTML = weekLabels.map((d) => `<span class="cal-head">${d}</span>`).join("");

  captionEl.textContent = `${monthNames[calViewMonth]} ${calViewYear}`;

  const isRealTodayMonth =
    REAL_TODAY.getFullYear() === calViewYear && REAL_TODAY.getMonth() === calViewMonth;
  const todayDate = isRealTodayMonth ? REAL_TODAY.getDate() : null;

  const weeks = buildMonthWeeks(calViewYear, calViewMonth);

  let html = "";
  weeks.forEach((week) => {
    week.forEach((day) => {
      if (day === 0) {
        html += `<span class="cal-day outside"></span>`;
      } else {
        const classes = ["cal-day"];
        if (day === todayDate) classes.push("today");
        if (selectedDays.has(day)) classes.push("selected");
        html += `<button type="button" class="${classes.join(" ")}" data-day="${day}">${day}</button>`;
      }
    });
  });
  daysEl.innerHTML = html;
}

function initCalendar() {
  document.getElementById("calPrevBtn").addEventListener("click", () => {
    calViewMonth -= 1;
    if (calViewMonth < 0) {
      calViewMonth = 11;
      calViewYear -= 1;
    }
    renderCalendar();
  });

  document.getElementById("calNextBtn").addEventListener("click", () => {
    calViewMonth += 1;
    if (calViewMonth > 11) {
      calViewMonth = 0;
      calViewYear += 1;
    }
    renderCalendar();
  });

  document.getElementById("calDays").addEventListener("click", (e) => {
    const dayBtn = e.target.closest(".cal-day[data-day]");
    if (!dayBtn) return;
    const day = Number(dayBtn.dataset.day);
    if (selectedDays.has(day)) {
      selectedDays.delete(day);
    } else {
      selectedDays.add(day);
    }
    renderCalendar();
  });
}

// ===================== TOPBAR: SEARCH =====================

function initTopbar() {
  const actions = document.getElementById("topbarActions");
  const triggerDesktop = document.getElementById("searchTriggerDesktop");
  const triggerMobile = document.getElementById("searchTriggerMobile");
  const createBtn = document.getElementById("createBtn");
  const searchForm = document.getElementById("searchForm");
  const searchInput = document.getElementById("searchInput");
  const closeBtn = document.getElementById("closeSearchBtn");

  function openSearch() {
    triggerDesktop.style.display = "none";
    triggerMobile.style.display = "none";
    createBtn.style.display = "none";
    searchForm.style.display = "flex";
    searchInput.value = "";
    searchInput.focus();
  }

  function closeSearch() {
    searchForm.style.display = "none";
    syncResponsiveButtons();
    searchQuery = "";
    renderPosts();
  }

  function syncResponsiveButtons() {
    triggerDesktop.style.display = "";
    createBtn.style.display = "";
    triggerMobile.style.display = "";
  }

  triggerDesktop.addEventListener("click", openSearch);
  triggerMobile.addEventListener("click", openSearch);
  closeBtn.addEventListener("click", closeSearch);
  searchForm.addEventListener("submit", (e) => {
    e.preventDefault();
    searchQuery = searchInput.value;
    renderPosts();
  });
}

// ===================== DROPDOWNS =====================

function closeAllDropdowns(except) {
  document.querySelectorAll(".dropdown.open").forEach((d) => {
    if (d !== except) d.classList.remove("open");
  });
}

document.addEventListener("click", (e) => {
  const trigger = e.target.closest("#userDropdownTrigger");
  if (trigger) {
    const dropdown = document.getElementById("userDropdown");
    const wasOpen = dropdown.classList.contains("open");
    closeAllDropdowns();
    dropdown.classList.toggle("open", !wasOpen);
    return;
  }

  const menuToggle = e.target.closest('[data-action="menu-toggle"]');
  if (menuToggle) {
    const dropdown = menuToggle.closest(".dropdown");
    const wasOpen = dropdown.classList.contains("open");
    closeAllDropdowns();
    dropdown.classList.toggle("open", !wasOpen);
    return;
  }

  if (!e.target.closest(".dropdown-menu")) {
    closeAllDropdowns();
  }
});

// ===================== TOP NAV (Início / Popular / Minhas comunidades) =====================

function initTopNav() {
  const nav = document.getElementById("topNav");
  nav.addEventListener("click", (e) => {
    const btn = e.target.closest(".nav-pill");
    if (!btn) return;
    nav.querySelectorAll(".nav-pill").forEach((p) => p.classList.remove("active"));
    btn.classList.add("active");
  });
}

// ===================== TABS =====================

function initTabs() {
  const tabsList = document.getElementById("tabsList");
  tabsList.addEventListener("click", (e) => {
    const btn = e.target.closest(".tab-trigger");
    if (!btn) return;
    activeTab = btn.dataset.tab;
    tabsList.querySelectorAll(".tab-trigger").forEach((t) => t.classList.remove("active"));
    btn.classList.add("active");
    renderPosts();
  });
}

// ===================== POST ACTIONS (like / bookmark) =====================

document.getElementById("postsContainer").addEventListener("click", (e) => {
  const likeBtn = e.target.closest('[data-action="like"]');
  if (likeBtn) {
    const id = likeBtn.dataset.id;

    // Atualização otimista: já muda a tela, e confirma com o backend depois.
    posts = posts.map((p) =>
      p.id === id ? { ...p, liked: !p.liked, likes: p.liked ? p.likes - 1 : p.likes + 1 } : p
    );
    renderPosts();

    apiReagirPublicacao(id).then((resultado) => {
      if (!resultado.success) {
        // Reverte se o backend rejeitou (ex.: sessão expirada).
        posts = posts.map((p) =>
          p.id === id ? { ...p, liked: !p.liked, likes: p.liked ? p.likes - 1 : p.likes + 1 } : p
        );
        renderPosts();
      }
    });

    return;
  }
  const bookmarkBtn = e.target.closest('[data-action="bookmark"]');
  if (bookmarkBtn) {
    const id = bookmarkBtn.dataset.id;
    posts = posts.map((p) => (p.id === id ? { ...p, bookmarked: !p.bookmarked } : p));
    renderPosts();
    return;
  }
});

// ===================== CREATE POST =====================

function initCreatePost() {
  const trigger = document.getElementById("createPostTrigger");
  const chevron = document.getElementById("createPostChevron");
  const content = document.getElementById("createPostContent");
  const titleInput = document.getElementById("postTitleInput");
  const contentInput = document.getElementById("postContentInput");
  const tagsRow = document.getElementById("tagsRow");
  const tagInput = document.getElementById("tagInput");
  const publishBtn = document.getElementById("publishBtn");
  const cancelBtn = document.getElementById("cancelPostBtn");

  let expanded = false;

  function setExpanded(value) {
    expanded = value;
    content.classList.toggle("open", expanded);
    chevron.classList.toggle("expanded", expanded);
    trigger.textContent = expanded ? "Novo post..." : "Compartilhe algo com a comunidade...";
  }

  function toggleExpanded() {
    setExpanded(!expanded);
  }

  trigger.addEventListener("click", toggleExpanded);
  chevron.addEventListener("click", toggleExpanded);

  function updatePublishState() {
    publishBtn.disabled = !titleInput.value.trim() || !contentInput.value.trim();
  }

  titleInput.addEventListener("input", updatePublishState);
  contentInput.addEventListener("input", updatePublishState);

  function renderTagsRow() {
    tagsRow.querySelectorAll(".badge-removable").forEach((el) => el.remove());
    newPostTags.forEach((tag) => {
      const badge = document.createElement("span");
      badge.className = "badge badge-secondary badge-tag badge-removable";
      badge.style.gap = "0.25rem";
      badge.style.cursor = "pointer";
      badge.textContent = tag + " ×";
      badge.addEventListener("click", () => {
        newPostTags = newPostTags.filter((t) => t !== tag);
        renderTagsRow();
      });
      tagsRow.insertBefore(badge, tagInput);
    });
    tagInput.style.display = newPostTags.length < 5 ? "" : "none";
  }

  tagInput.addEventListener("keydown", (e) => {
    if ((e.key === "Enter" || e.key === ",") && tagInput.value.trim()) {
      e.preventDefault();
      const newTag = tagInput.value.trim().replace(/,$/, "");
      if (newTag && !newPostTags.includes(newTag) && newPostTags.length < 5) {
        newPostTags.push(newTag);
      }
      tagInput.value = "";
      renderTagsRow();
    }
  });

  function resetForm() {
    titleInput.value = "";
    contentInput.value = "";
    document.getElementById("postCategoriaSelect").value = "";
    newPostTags = [];
    tagInput.value = "";
    renderTagsRow();
    updatePublishState();
  }

  cancelBtn.addEventListener("click", () => {
    resetForm();
    setExpanded(false);
  });

  publishBtn.addEventListener("click", async () => {
    const title = titleInput.value.trim();
    const content = contentInput.value.trim();
    const categoriaId = document.getElementById("postCategoriaSelect").value;
    const errorBox = document.getElementById("createPostError");

    errorBox.style.display = "none";

    if (!title || !content) return;

    if (!categoriaId) {
      errorBox.textContent = "Selecione uma categoria antes de publicar.";
      errorBox.style.display = "block";
      return;
    }

    publishBtn.disabled = true;
    publishBtn.textContent = "Publicando...";

    try {
      const resultado = await apiCriarPublicacao(title, content, categoriaId);

      if (!resultado.success) {
        errorBox.textContent = resultado.message || "Não foi possível publicar.";
        errorBox.style.display = "block";
        return;
      }

      await loadPosts();
      resetForm();
      setExpanded(false);
    } catch (erro) {
      errorBox.textContent = "Não foi possível conectar ao servidor.";
      errorBox.style.display = "block";
    } finally {
      publishBtn.disabled = false;
      publishBtn.textContent = "Publicar";
    }
  });
}

// ===================== INIT =====================

/**
 * Formata data ISO (YYYY-MM-DD) para "DD/MM" (usado no widget de agenda).
 */
function formatarDataCurta(dataIso) {
  const [ano, mes, dia] = dataIso.split("-");
  return `${dia}/${mes}`;
}

let todosEventosProximos = [];
let mostrandoTodosEventos = false;

function renderAgendaEventos() {
  const container = document.getElementById("agendaEventosContainer");

  const lista = mostrandoTodosEventos ? todosEventosProximos : todosEventosProximos.slice(0, 3);

  if (lista.length === 0) {
    container.innerHTML = `<p class="agenda-event-desc" style="padding:8px 0;">Nenhum evento agendado no momento.</p>`;
    return;
  }

  container.innerHTML = lista
    .map(
      (ev) => `
        <div class="agenda-event">
            <span class="agenda-dot"></span>
            <div style="flex:1;">
                <p class="agenda-event-time">${formatarDataCurta(ev.data_evento)} · ${ev.hora_inicio}</p>
                <p class="agenda-event-desc">${escapeHtml(ev.titulo)}${ev.local ? " — " + escapeHtml(ev.local) : ""}</p>
            </div>
            <button class="btn btn-ghost btn-sm" data-action="toggle-participacao-evento" data-id="${ev.id}" data-inscrito="${ev.inscrito_por_mim}">
                ${ev.inscrito_por_mim ? "Cancelar" : "Participar"}
            </button>
        </div>`
    )
    .join("");
}

async function loadAgendaEventos() {
  const { ok, dados } = await apiListarEventosProximos();

  if (!ok || !dados.success) {
    document.getElementById("agendaEventosContainer").innerHTML =
      `<p class="agenda-event-desc" style="padding:8px 0;">Não foi possível carregar os eventos.</p>`;
    return;
  }

  todosEventosProximos = dados.eventos;
  renderAgendaEventos();
}

document.getElementById("verTodosEventosBtn").addEventListener("click", () => {
  mostrandoTodosEventos = !mostrandoTodosEventos;
  document.getElementById("verTodosEventosBtn").textContent =
    mostrandoTodosEventos ? "Ver menos ←" : "Ver todos os eventos →";
  renderAgendaEventos();
});

document.getElementById("agendaEventosContainer").addEventListener("click", async (e) => {
  const btn = e.target.closest('[data-action="toggle-participacao-evento"]');
  if (!btn) return;

  const id = btn.dataset.id;
  const inscrito = btn.dataset.inscrito === "true";

  btn.disabled = true;

  const resultado = inscrito
    ? await apiCancelarParticipacaoEvento(id)
    : await apiParticiparEvento(id);

  if (resultado.success) {
    todosEventosProximos = todosEventosProximos.map((ev) =>
      String(ev.id) === String(id) ? { ...ev, inscrito_por_mim: !inscrito } : ev
    );
    renderAgendaEventos();
  } else {
    alert(resultado.message || "Não foi possível completar a ação.");
  }

  btn.disabled = false;
});

/**
 * Card de "Votações ativas" na barra lateral do feed.
 */
async function loadVotacoesAtivas() {
  const container = document.getElementById("votacoesAtivasContainer");

  const { ok, dados } = await apiListarVotacoes("aberta");

  if (!ok || !dados.success) {
    container.innerHTML = `<p class="post-loading">Não foi possível carregar as votações.</p>`;
    return;
  }

  if (dados.votacoes.length === 0) {
    container.innerHTML = `<p class="post-loading">Nenhuma votação ativa no momento.</p>`;
    return;
  }

  container.innerHTML = dados.votacoes
    .map((v) => `<div class="votacao-widget-item" data-id="${v.id}"><p class="post-loading">Carregando...</p></div>`)
    .join("");

  for (const v of dados.votacoes) {
    const { ok: okDetalhe, dados: detalhe } = await apiBuscarVotacao(v.id);
    const item = container.querySelector(`.votacao-widget-item[data-id="${v.id}"]`);
    if (!item) continue;

    if (!okDetalhe || !detalhe.success) {
      item.innerHTML = `<p class="post-loading">Erro ao carregar esta votação.</p>`;
      continue;
    }

    renderVotacaoWidget(item, detalhe.votacao);
  }
}

function renderVotacaoWidget(container, votacao) {
  if (votacao.ja_votei) {
    // Já votou: mostra o resultado (dados reais, nunca mockados)
    const totalGeral = votacao.opcoes.reduce((soma, o) => soma + o.total_votos, 0);
    container.innerHTML = `
      <p class="agenda-event-desc" style="font-weight:600; margin-bottom:6px;">${escapeHtml(votacao.titulo)}</p>
      ${votacao.opcoes
        .map(
          (o) => `
        <div style="margin-bottom:8px;">
          <div style="display:flex; justify-content:space-between; font-size:12px; color:#666;">
            <span>${escapeHtml(o.descricao_opcao)}${o.id === votacao.opcao_votada_id ? " ✓" : ""}</span>
            <span>${o.percentual}%</span>
          </div>
          <div style="background:#eee; border-radius:6px; height:6px; overflow:hidden;">
            <div style="background:#4f46e5; height:100%; width:${o.percentual}%;"></div>
          </div>
        </div>`
        )
        .join("")}
      <p class="post-loading" style="text-align:left; padding:0;">Total: ${totalGeral} voto(s)</p>
    `;
    return;
  }

  // Ainda não votou: mostra as opções para escolher
  container.innerHTML = `
    <p class="agenda-event-desc" style="font-weight:600; margin-bottom:6px;">${escapeHtml(votacao.titulo)}</p>
    <div class="votacao-opcoes-radio">
      ${votacao.opcoes
        .map(
          (o) => `
        <label style="display:flex; align-items:center; gap:6px; font-size:13px; margin-bottom:4px; cursor:pointer;">
          <input type="radio" name="votacao-${votacao.id}" value="${o.id}">
          ${escapeHtml(o.descricao_opcao)}
        </label>`
        )
        .join("")}
    </div>
    <button class="btn btn-default btn-sm" data-action="confirmar-voto" data-votacao-id="${votacao.id}" disabled style="margin-top:6px;">
      Votar
    </button>
  `;

  const radios = container.querySelectorAll(`input[name="votacao-${votacao.id}"]`);
  const botaoVotar = container.querySelector('[data-action="confirmar-voto"]');

  radios.forEach((radio) => {
    radio.addEventListener("change", () => {
      botaoVotar.disabled = false;
    });
  });

  botaoVotar.addEventListener("click", async () => {
    const selecionado = container.querySelector(`input[name="votacao-${votacao.id}"]:checked`);
    if (!selecionado) return;

    botaoVotar.disabled = true;
    botaoVotar.textContent = "Enviando...";

    const resultado = await apiVotar(votacao.id, selecionado.value);

    if (resultado.success) {
      const { ok, dados } = await apiBuscarVotacao(votacao.id);
      if (ok && dados.success) {
        renderVotacaoWidget(container, dados.votacao);
      }
    } else {
      alert(resultado.message || "Não foi possível registrar o voto.");
      botaoVotar.disabled = false;
      botaoVotar.textContent = "Votar";
    }
  });
}

document.addEventListener("DOMContentLoaded", () => {
  initTopbar();
  initTopNav();
  initTabs();
  initCreatePost();
  initCalendar();
  renderTrending();
  renderActivity();
  renderCalendar();
  loadPosts();
  initComposerRBAC();
  loadAgendaEventos();
  loadVotacoesAtivas();
});