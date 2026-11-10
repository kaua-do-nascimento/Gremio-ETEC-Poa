# Issues, labels e GitHub Projects

## Labels

Crie em **Issues → Labels → New label** (ou edite as padrão). `bug`, `documentation` e `enhancement` já existem no GitHub.

| Label | Uso | Cor sugerida |
|---|---|---|
| `backend` | PHP, API, controllers, models | `#1d76db` |
| `frontend` | HTML, CSS, JavaScript, telas | `#0e8a16` |
| `database` | Tabelas, SQL, seeds | `#5319e7` |
| `design` | Identidade visual, protótipos, UX | `#d93f0b` |
| `documentation` | README, `docs/`, guias | `#0075ca` |
| `bug` | Algo que existe e está errado | `#d73a4a` |
| `enhancement` | Melhoria ou funcionalidade nova | `#a2eeef` |
| `priority-high` | Bloqueia o uso ou a entrega | `#b60205` |
| `priority-medium` | Importante, sem urgência | `#fbca04` |
| `priority-low` | Pode esperar | `#c2e0c6` |

Opcional, se tiver o [GitHub CLI](https://cli.github.com/) instalado e logado (`gh auth login`) dentro da pasta do repositório:

```powershell
gh label create backend --color 1d76db --description "PHP, API, controllers, models"
gh label create frontend --color 0e8a16 --description "HTML, CSS, JavaScript, telas"
gh label create database --color 5319e7 --description "Tabelas, SQL, seeds"
gh label create design --color d93f0b --description "Identidade visual, prototipos, UX"
gh label create priority-high --color b60205
gh label create priority-medium --color fbca04
gh label create priority-low --color c2e0c6
```

## Modelos de Issue

Ao clicar em **New issue**, a equipe escolhe entre:

1. **Relatar um bug** — passos para reproduzir, esperado × obtido, ambiente.
2. **Solicitar uma funcionalidade** — problema, proposta, critérios de aceitação.
3. **Registrar uma tarefa técnica** — objetivo e lista de passos.

Arquivos: `.github/ISSUE_TEMPLATE/`. Cada modelo já aplica uma label inicial; **adicione as de área e prioridade**.

## GitHub Projects (quadro de tarefas)

1. Aba **Projects** → **New project** → modelo **Board**. Nome: `TCC Grêmio ETEC Poá`.
2. Em **Status**, deixe estas colunas, nesta ordem:

| Coluna | Significado |
|---|---|
| **A fazer** | Tarefa definida, ninguém trabalhando |
| **Em desenvolvimento** | Alguém atribuído e com branch aberta |
| **Em revisão** | Pull Request aberto aguardando revisão |
| **Concluído** | PR mesclado em `develop` e Issue fechada |

3. **Workflows** (engrenagem do Project): ative *Item added to project → Status: A fazer*, *Pull request merged → Status: Concluído* e *Item closed → Status: Concluído*.
4. Em **Settings → Projects** do repositório, vincule o Project, e use **Add item → Add from repository** para trazer as Issues.
5. Sugestão de campos extras no Project: **Prioridade** (Alta/Média/Baixa), **Módulo** (Publicações, Eventos, ...) e **Responsável**.

## Fluxo de uma tarefa

```text
Issue criada ─► A fazer ─► atribuída a alguém ─► Em desenvolvimento (branch feature/… ou fix/…)
   ─► PR aberto para develop ─► Em revisão ─► aprovado e mesclado ─► Concluído (Issue fecha com "Closes #n")
```

## Sugestão de primeiras Issues (a partir do estado real do sistema)

Estas pendências aparecem no código e na documentação; a equipe decide quais abrir:

| Título sugerido | Labels |
|---|---|
| Implementar comprovantes de movimentações financeiras | `backend` `frontend` `database` `enhancement` |
| Implementar prestação de contas | `backend` `frontend` `database` `enhancement` |
| Tela para editar e excluir publicações no feed | `frontend` `enhancement` |
| Substituir "tendências" e "estatísticas" fixas do feed por dados reais | `frontend` `backend` `enhancement` |
| Anexos de eventos e de projetos | `backend` `frontend` `enhancement` |
| Limitar tentativas de login | `backend` `priority-high` |
| Restringir CORS às origens do sistema | `backend` `priority-medium` |
| Diagrama ER e dicionário de dados | `documentation` `database` |
| Exemplos de requisição/resposta por endpoint | `documentation` `backend` |
