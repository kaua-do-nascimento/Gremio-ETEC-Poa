# Guia da equipe no GitHub

Configuração do repositório e do ambiente de cada integrante. O repositório é criado **manualmente** pelo responsável do projeto.

## 1. Quem faz o quê

| Papel | Quem | Responsabilidades |
|---|---|---|
| Responsável pelo repositório (dono) | _a definir_ | Cria o repositório, convida a equipe, configura proteção de branches, faz os PRs `develop` → `main` |
| Desenvolvedores | _a definir_ | Trabalham em `feature/*` e `fix/*`, abrem PRs para `develop`, revisam PRs dos colegas |
| Apoio (design/documentação/orientação) | _a definir_ | Acompanham pelo Projects e Issues; podem comentar e revisar |

## 2. Criar o repositório (responsável)

1. No GitHub: **New repository**. Nome sugerido: `Gremio-ETEC-Poa`.
2. **Não** marque *Add a README*, *.gitignore* nem *license* (o projeto já tem os seus; evitar histórico divergente).
3. Visibilidade: o projeto **não contém segredos**, então pode ser público ou privado. Atenção: em conta pessoal gratuita, a **proteção de branches só funciona em repositório público** (em privado exige plano Pro/Team). Alunos podem solicitar o plano Pro pelo [GitHub Student Developer Pack](https://education.github.com/pack).
4. Opcional: criar uma **Organization** gratuita para a equipe (ex.: *Arch-Sec-WebSolutions*), o que habilita papéis mais granulares (Triage, Maintain).
5. Publique o código seguindo [`primeiro-envio.md`](primeiro-envio.md).

## 3. Convidar colaboradores (responsável)

1. Repositório → **Settings** → **Collaborators** (em organização: **Collaborators and teams**) → **Add people**.
2. Digite o usuário ou e-mail de cada integrante (confirme o usuário com a pessoa; não presuma).
3. Escolha a permissão e envie o convite.

Permissões recomendadas:

| Quem | Em repositório de organização | Em repositório pessoal |
|---|---|---|
| Responsável | Admin | Dono |
| Desenvolvedores | **Write** | Colaborador (equivale a Write) |
| Orientador / quem só acompanha | **Read** (ou Triage, para gerenciar Issues) | Colaborador (Write) — convide só se precisar editar |

## 4. Aceitar o convite e clonar (integrante)

1. Abra o e-mail do GitHub ou <https://github.com/notifications> e clique em **Accept invitation**.
2. Configure o Git (uma vez) — veja [CONTRIBUTING.md, seção 1](../../CONTRIBUTING.md#1-antes-de-começar-configuração-única).
3. Clone em `C:\wamp64\www\Gremio-ETEC-Poa` — veja [CONTRIBUTING.md, seção 2](../../CONTRIBUTING.md#2-clonar-o-repositório).

Autenticação no `git push`: o Git para Windows abre uma janela de login no navegador (Git Credential Manager). **Não use a senha da conta**; se pedir token, gere um *Personal Access Token* em *Settings → Developer settings* e **nunca o salve em arquivos do projeto**.

## 5. Ambiente PHP/MySQL de cada integrante

Cada pessoa tem seu **próprio banco local** (os dados não vão ao GitHub):

1. Instale o WampServer e ligue-o.
2. Importe os SQLs na ordem indicada no [README](../../README.md#instalação-no-windows-com-wampserver).
3. `Copy-Item .env.example .env` e ajuste `DB_PORT` para a porta do **seu** MySQL/MariaDB.
4. `php Backend/tests/criar-admin.php ...` com uma senha **sua** (não compartilhar).
5. `php Backend/tests/proteger-admin.php` e `php Backend/tests/diagnostico.php`.

Mudanças de estrutura do banco devem ser feitas por **script SQL versionado** em `database/` e descritas no PR, para todos aplicarem o mesmo.

## 6. Proteção de branches (responsável)

Em **Settings → Branches → Add branch ruleset / protection rule**, para `main` **e** `develop`:

- **Require a pull request before merging** com **1 aprovação**.
- **Dismiss stale approvals** quando houver novos commits.
- **Require conversation resolution before merging**.
- **Do not allow bypassing** (inclusive para admins, se possível).
- Bloquear **force push** e **exclusão** da branch.

Para a `main`, restrinja ainda quem pode fazer o merge (apenas o responsável).

Marque também **Settings → General → Pull Requests → Automatically delete head branches** para apagar as branches depois do merge.

## 7. Rotina combinada da equipe

- Toda tarefa vira uma **Issue** (modelos em `.github/ISSUE_TEMPLATE`) e entra no **Project**.
- Cada pessoa pega **uma** Issue por vez, cria a branch (`feature/…` ou `fix/…`) e abre PR para `develop`.
- Nenhum integrante aprova o próprio PR.
- `develop` → `main` só em marcos testados (entregas do TCC), por PR do responsável.
