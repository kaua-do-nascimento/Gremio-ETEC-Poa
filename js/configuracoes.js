// ============================================================
// configuracoes.js — Configurações da conta do usuário logado
//
// Painéis (todos funcionais):
//   conta ........ nome e e-mail (POST /api/me/perfil)
//   senha ........ troca de senha (POST /api/me/senha)
//   acessibilidade preferências aplicadas em todas as páginas
//                  (salvas neste navegador, ver api.js)
//   seguranca .... encerrar outras sessões e sair
// ============================================================

const navItems = {
  conta: { label: "Sua conta" },
  senha: { label: "Senha e autenticação" },
  acessibilidade: { label: "Acessibilidade" },
  seguranca: { label: "Segurança" },
};

let activePanel = "conta";
let usuarioAtual = null;

function esc(str) {
  const div = document.createElement("div");
  div.textContent = str ?? "";
  return div.innerHTML;
}

function mensagemHTML(id) {
  return `<p id="${id}" role="status" style="display:none;margin:0.75rem 0 0;font-size:0.875rem;"></p>`;
}

function mostrarMensagem(id, texto, tipo) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = texto;
  el.style.display = texto ? "block" : "none";
  el.style.color = tipo === "erro" ? "#b3261e" : "#1b7a3d";
}

const campoStyle = "width:100%;padding:0.625rem 0.75rem;border:1px solid var(--border,#ddd);border-radius:0.5rem;font:inherit;background:transparent;color:inherit;box-sizing:border-box;";
const labelStyle = "display:block;font-size:0.8125rem;margin:0.75rem 0 0.25rem;color:var(--muted-foreground,#666);";

function toggleSwitchHTML(key, checked) {
  return `<button type="button" class="settings-toggle${checked ? " on" : ""}" data-toggle="${key}" aria-pressed="${checked}" aria-label="${key}"></button>`;
}

function renderContaPanel() {
  const u = usuarioAtual;
  const perfis = (u.perfis || []).join(", ") || "-";
  return `
    <div class="settings-panel">
      <div class="card settings-card">
        <div class="settings-card-body">
          <p class="settings-kicker">Identificação</p>
          <h2 class="font-serif" style="font-size:1.75rem;margin:0.5rem 0 0;">Sua conta</h2>
          <p class="settings-panel-desc">Informações que identificam você no Grêmio.</p>

          <div class="settings-row">
            <div><p class="settings-row-title">RM</p><p class="settings-row-value">${esc(u.rm)}</p></div>
          </div>
          <div class="settings-row">
            <div><p class="settings-row-title">Perfil de acesso</p><p class="settings-row-value">${esc(perfis)}</p></div>
          </div>

          <form id="formPerfil" novalidate>
            <label for="perfilNome" style="${labelStyle}">Nome completo</label>
            <input id="perfilNome" type="text" maxlength="150" value="${esc(u.nome_completo)}" style="${campoStyle}" autocomplete="name" />
            <label for="perfilEmail" style="${labelStyle}">E-mail</label>
            <input id="perfilEmail" type="email" maxlength="150" value="${esc(u.email)}" style="${campoStyle}" autocomplete="email" />
            ${mensagemHTML("perfilMsg")}
            <div style="margin-top:1rem;"><button type="submit" class="settings-row-action" id="btnSalvarPerfil">Salvar alterações</button></div>
          </form>
        </div>
      </div>
    </div>`;
}

function renderSenhaPanel() {
  return `
    <div class="settings-panel">
      <div class="card settings-card">
        <div class="settings-card-body">
          <p class="settings-kicker">Acesso</p>
          <h2 class="font-serif" style="font-size:1.75rem;margin:0.5rem 0 0;">Senha e autenticação</h2>
          <p class="settings-panel-desc">Troque sua senha. Por segurança, seus outros dispositivos serão desconectados.</p>

          <form id="formSenha" novalidate>
            <label for="senhaAtual" style="${labelStyle}">Senha atual</label>
            <input id="senhaAtual" type="password" style="${campoStyle}" autocomplete="current-password" />
            <label for="senhaNova" style="${labelStyle}">Nova senha (mínimo 8 caracteres)</label>
            <input id="senhaNova" type="password" style="${campoStyle}" autocomplete="new-password" />
            <label for="senhaConfirma" style="${labelStyle}">Confirmar nova senha</label>
            <input id="senhaConfirma" type="password" style="${campoStyle}" autocomplete="new-password" />
            ${mensagemHTML("senhaMsg")}
            <div style="margin-top:1rem;"><button type="submit" class="settings-row-action" id="btnSalvarSenha">Alterar senha</button></div>
          </form>
        </div>
      </div>
    </div>`;
}

function renderAcessibilidadePanel() {
  const p = obterPreferencias();
  const sel = (v) => (p.tamanhoTexto === v ? "selected" : "");
  return `
    <div class="settings-panel">
      <div class="card settings-card">
        <div class="settings-card-body">
          <p class="settings-kicker">Navegue do seu jeito</p>
          <h2 class="font-serif" style="font-size:1.75rem;margin:0.5rem 0 0;">Acessibilidade</h2>
          <p class="settings-panel-desc">Estas preferências valem para todas as páginas e ficam salvas neste navegador.</p>
          <div style="margin-top:1rem;">
            <div class="settings-row">
              <div><p class="settings-row-title">Alto contraste</p><p class="settings-row-detail">Aumenta a diferença entre textos, fundos e controles.</p></div>
              ${toggleSwitchHTML("altoContraste", p.altoContraste)}
            </div>
            <div class="settings-row">
              <div><p class="settings-row-title">Reduzir animações</p><p class="settings-row-detail">Desativa transições e movimentos da interface.</p></div>
              ${toggleSwitchHTML("reduzirAnimacoes", p.reduzirAnimacoes)}
            </div>
            <div class="settings-row">
              <div><p class="settings-row-title">Tamanho do texto</p><p class="settings-row-detail">Ajuste a leitura em toda a plataforma.</p></div>
              <select class="settings-select" id="selTamanhoTexto" aria-label="Tamanho do texto">
                <option value="padrao" ${sel("padrao")}>Padrão</option>
                <option value="grande" ${sel("grande")}>Grande</option>
                <option value="muito-grande" ${sel("muito-grande")}>Muito grande</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>`;
}

function renderSegurancaPanel() {
  return `
    <div class="settings-panel">
      <div class="card settings-card">
        <div class="settings-card-body">
          <p class="settings-kicker">Proteção</p>
          <h2 class="font-serif" style="font-size:1.75rem;margin:0.5rem 0 0;">Segurança</h2>
          <p class="settings-panel-desc">Se você entrou em um computador compartilhado, encerre as outras sessões abertas.</p>
          <div class="settings-row">
            <div><p class="settings-row-title">Outras sessões</p><p class="settings-row-detail">Desconecta todos os outros navegadores e dispositivos. Esta sessão continua ativa.</p></div>
            <button type="button" class="settings-row-action" id="btnEncerrarOutras">Encerrar outras sessões</button>
          </div>
          <div class="settings-row">
            <div><p class="settings-row-title">Esta sessão</p><p class="settings-row-detail">Sair da sua conta neste navegador.</p></div>
            <button type="button" class="settings-row-action" id="btnSairSeguranca">Sair da conta</button>
          </div>
          ${mensagemHTML("segMsg")}
        </div>
      </div>
    </div>`;
}

// ===================== RENDER =====================

function renderPanel() {
  const container = document.getElementById("panelsContainer");
  const renderers = {
    conta: renderContaPanel,
    senha: renderSenhaPanel,
    acessibilidade: renderAcessibilidadePanel,
    seguranca: renderSegurancaPanel,
  };
  container.innerHTML = renderers[activePanel]();
  document.getElementById("breadcrumbActive").textContent = navItems[activePanel].label;
  document.querySelectorAll(".settings-nav-item").forEach((b) =>
    b.classList.toggle("active", b.dataset.panel === activePanel)
  );
  ligarEventos();
}

function ligarEventos() {
  const formPerfil = document.getElementById("formPerfil");
  if (formPerfil) {
    formPerfil.addEventListener("submit", async (e) => {
      e.preventDefault();
      const nome = document.getElementById("perfilNome").value.trim();
      const email = document.getElementById("perfilEmail").value.trim();
      const btn = document.getElementById("btnSalvarPerfil");
      btn.disabled = true;
      mostrarMensagem("perfilMsg", "", "ok");
      try {
        const dados = await apiAtualizarPerfil(nome, email);
        if (dados.success) {
          usuarioAtual = { ...usuarioAtual, ...dados.usuario };
          const sessao = obterSessao();
          if (sessao) salvarSessao(sessao.token, { ...sessao.usuario, ...dados.usuario }, sessao.perfis);
          document.getElementById("nomeUsuarioLogado").textContent = usuarioAtual.nome_completo;
          mostrarMensagem("perfilMsg", "Perfil atualizado com sucesso.", "ok");
        } else {
          mostrarMensagem("perfilMsg", dados.message || "Não foi possível salvar.", "erro");
        }
      } catch (err) {
        mostrarMensagem("perfilMsg", "Não foi possível conectar ao servidor.", "erro");
      }
      btn.disabled = false;
    });
  }

  const formSenha = document.getElementById("formSenha");
  if (formSenha) {
    formSenha.addEventListener("submit", async (e) => {
      e.preventDefault();
      const atual = document.getElementById("senhaAtual").value;
      const nova = document.getElementById("senhaNova").value;
      const conf = document.getElementById("senhaConfirma").value;
      if (!atual || !nova) return mostrarMensagem("senhaMsg", "Preencha todos os campos.", "erro");
      if (nova.length < 8) return mostrarMensagem("senhaMsg", "A nova senha deve ter pelo menos 8 caracteres.", "erro");
      if (nova !== conf) return mostrarMensagem("senhaMsg", "A confirmação não confere com a nova senha.", "erro");
      const btn = document.getElementById("btnSalvarSenha");
      btn.disabled = true;
      try {
        const dados = await apiAlterarSenha(atual, nova);
        if (dados.success) {
          formSenha.reset();
          mostrarMensagem("senhaMsg", "Senha alterada com sucesso. Os outros dispositivos foram desconectados.", "ok");
        } else {
          mostrarMensagem("senhaMsg", dados.message || "Não foi possível alterar a senha.", "erro");
        }
      } catch (err) {
        mostrarMensagem("senhaMsg", "Não foi possível conectar ao servidor.", "erro");
      }
      btn.disabled = false;
    });
  }

  document.querySelectorAll("[data-toggle]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const prefs = obterPreferencias();
      prefs[btn.dataset.toggle] = !prefs[btn.dataset.toggle];
      salvarPreferencias(prefs);
      btn.classList.toggle("on", prefs[btn.dataset.toggle]);
      btn.setAttribute("aria-pressed", String(prefs[btn.dataset.toggle]));
    });
  });

  const sel = document.getElementById("selTamanhoTexto");
  if (sel) {
    sel.addEventListener("change", () => {
      const prefs = obterPreferencias();
      prefs.tamanhoTexto = sel.value;
      salvarPreferencias(prefs);
    });
  }

  const btnOutras = document.getElementById("btnEncerrarOutras");
  if (btnOutras) {
    btnOutras.addEventListener("click", async () => {
      btnOutras.disabled = true;
      const dados = await apiEncerrarOutrasSessoes();
      mostrarMensagem("segMsg", dados.message || "Não foi possível encerrar as sessões.", dados.success ? "ok" : "erro");
      btnOutras.disabled = false;
    });
  }

  const btnSair = document.getElementById("btnSairSeguranca");
  if (btnSair) btnSair.addEventListener("click", sairDaConta);
}

async function sairDaConta() {
  await apiLogout();
  window.location.href = "login.php";
}

// ===================== INICIALIZAÇÃO =====================

document.getElementById("settingsNav").addEventListener("click", (e) => {
  const btn = e.target.closest(".settings-nav-item");
  if (!btn || !usuarioAtual) return;
  activePanel = btn.dataset.panel;
  renderPanel();
});



(async function iniciar() {
  const sessao = obterSessao();
  if (!sessao) return; // a página principal já redireciona ao login
  usuarioAtual = { ...sessao.usuario, perfis: sessao.perfis || [] };

  try {
    const { ok, dados } = await apiMe();
    if (ok && dados.success) {
      usuarioAtual = { ...usuarioAtual, ...dados.usuario };
    }
  } catch (e) { /* mantém os dados da sessão salva */ }

  renderPanel();
})();
