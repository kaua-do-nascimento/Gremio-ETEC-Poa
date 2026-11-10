# Guia de Contribuição

Este guia explica como a equipe **Arch&Sec WebSolutions** trabalha no repositório do Grêmio ETEC Poá. A regra de ouro: **ninguém trabalha direto na `main`** — todo código chega por Pull Request, revisado por outro integrante.

Os comandos abaixo são para o **PowerShell** do Windows. Substitua o que estiver entre `< >` pelos seus valores.

## Sumário

1. [Antes de começar (configuração única)](#1-antes-de-começar-configuração-única)
2. [Clonar o repositório](#2-clonar-o-repositório)
3. [Estratégia de branches](#3-estratégia-de-branches)
4. [Rotina de trabalho do dia a dia](#4-rotina-de-trabalho-do-dia-a-dia)
5. [Padrão de commits](#5-padrão-de-commits)
6. [Abrir um Pull Request](#6-abrir-um-pull-request)
7. [Revisar o trabalho de outro integrante](#7-revisar-o-trabalho-de-outro-integrante)
8. [Resolver conflitos](#8-resolver-conflitos)
9. [Comunicar problemas](#9-comunicar-problemas)
10. [Regras de segurança](#10-regras-de-segurança)
11. [Padrões de código](#11-padrões-de-código)

---

## 1. Antes de começar (configuração única)

Diga ao Git quem é você (use o nome e o e-mail **da sua conta do GitHub**):

```powershell
git config --global user.name "Seu Nome"
git config --global user.email "seu-email@exemplo.com"
git config --global init.defaultBranch main
```

Confira:

```powershell
git config --global --list
```

## 2. Clonar o repositório

Aceite o convite de colaborador (chega por e-mail e em <https://github.com/notifications>) e clone **dentro da pasta `www` do Wamp**:

```powershell
cd C:\wamp64\www
git clone https://github.com/<USUARIO-OU-ORGANIZACAO>/<NOME-DO-REPOSITORIO>.git Gremio-ETEC-Poa
cd Gremio-ETEC-Poa
git checkout develop
```

Depois siga a [instalação do README](README.md#instalação-no-windows-com-wampserver) (banco, `.env`, administrador).

## 3. Estratégia de branches

| Branch | Para que serve | Quem escreve nela | Vem de → vai para |
|---|---|---|---|
| `main` | Versão **estável**, a que seria apresentada/publicada | Ninguém diretamente. Só recebe Pull Request de `develop`, já testado | `develop` → `main` |
| `develop` | **Integração**: junta o que cada um fez | Ninguém diretamente. Só recebe Pull Requests | `feature/*` e `fix/*` → `develop` |
| `feature/*` | Uma **nova funcionalidade** | Quem pegou a tarefa | sai de `develop`, volta para `develop` |
| `fix/*` | Uma **correção de bug** | Quem pegou a correção | sai de `develop`, volta para `develop` |

Exemplos de nomes (minúsculas, palavras separadas por hífen):

```text
feature/perfil-usuario        fix/login
feature/configuracoes         fix/notificacoes
feature/orcamentos            fix/financeiro
feature/frontend-dashboard
```

Quando usar cada uma:

- Vai **acrescentar** algo que não existia (tela, endpoint, tabela)? → `feature/…`
- Vai **consertar** algo que existe e está errado? → `fix/…`
- Só mexe em texto/documentação? Pode usar `feature/` ou `fix/` conforme o caso (ex.: `feature/docs-instalacao`).
- Cada branch cuida de **um assunto só** e deve durar poucos dias. Branches grandes geram conflitos.

## 4. Rotina de trabalho do dia a dia

**4.1. Atualize a `develop` antes de começar qualquer coisa:**

```powershell
git checkout develop
git pull origin develop
```

**4.2. Crie sua branch a partir da `develop`:**

```powershell
git checkout -b feature/perfil-usuario
```

**4.3. Trabalhe e veja o que mudou:**

```powershell
git status
git diff
```

**4.4. Faça commits pequenos e frequentes:**

```powershell
git add Backend/controllers/UsuarioController.php js/dashboard.js
git commit -m "feat: adiciona edição de perfil do usuário"
```

Prefira adicionar arquivos pelo nome (como acima). Use `git add .` só depois de conferir o `git status`, para não incluir arquivos indevidos.

**4.5. Mantenha sua branch em dia com a `develop`** (faça isso ao menos uma vez por dia e antes de abrir o PR):

```powershell
git fetch origin
git merge origin/develop
```

**4.6. Envie sua branch ao GitHub:**

```powershell
git push -u origin feature/perfil-usuario
```

(Nos envios seguintes basta `git push`.)

**4.7. Abra o Pull Request** (veja a seção 6).

**4.8. Depois que o PR for aprovado e mesclado**, limpe e volte para a `develop`:

```powershell
git checkout develop
git pull origin develop
git branch -d feature/perfil-usuario
```

## 5. Padrão de commits

Formato: `tipo: descrição curta no presente, em português`

| Tipo | Quando usar |
|---|---|
| `feat` | Nova funcionalidade |
| `fix` | Correção de bug |
| `docs` | Documentação |
| `style` | Alterações visuais/formatação, sem mudar a lógica |
| `refactor` | Reorganização de código sem mudar o comportamento |
| `test` | Testes |
| `chore` | Manutenção e configuração (ex.: `.gitignore`, estrutura do GitHub) |

Exemplos:

```text
feat: adiciona gerenciamento de orçamentos
fix: corrige autenticação de usuários
docs: atualiza instruções de instalação
style: ajusta espaçamento da tabela de usuários
refactor: separa validação de eventos em método próprio
chore: configura estrutura do GitHub
```

Boas práticas: um commit = uma ideia; mensagem que explique **o quê** mudou; nada de "arrumei", "teste", "aaa".

## 6. Abrir um Pull Request

1. Faça o `git push` da sua branch (passo 4.6).
2. No GitHub, clique em **Compare & pull request** (ou *Pull requests → New pull request*).
3. Confira a direção: **base:** `develop` ← **compare:** `feature/sua-branch`.
4. Preencha o modelo que aparece (descrição, arquivos alterados, testes realizados, impactos, checklist).
5. Vincule a tarefa escrevendo `Closes #12` (número da Issue) na descrição: a Issue fecha sozinha quando o PR for mesclado.
6. Peça revisão de **pelo menos 1** colega (campo *Reviewers*).
7. Corrija o que for pedido fazendo novos commits na mesma branch — o PR atualiza sozinho.

**Antes de pedir revisão, teste:** o fluxo que você mexeu funciona no navegador? O `php Backend/tests/diagnostico.php` continua sem falhas? Nada de senha, `.env` ou dado pessoal nos arquivos alterados (`git diff --staged`)?

**Quem faz o merge:** o autor do PR, **depois** de aprovado. Quem revisou não mescla o próprio código.

Para levar `develop` à `main` (entrega estável): PR de `develop` → `main`, aberto pelo responsável do projeto, só depois de testar o sistema inteiro.

## 7. Revisar o trabalho de outro integrante

Revisar é parte do trabalho, não opcional.

1. Abra o PR no GitHub e leia a descrição.
2. Aba **Files changed**: leia o código. Comente linhas específicas (clique no `+` ao lado da linha).
3. Para testar na sua máquina:

```powershell
git fetch origin
git checkout feature/branch-do-colega
```

Teste no navegador e, ao terminar, volte com `git checkout develop`.

4. Use o checklist do modelo de PR. Perguntas úteis: funciona? quebra algo existente? há senha/dado pessoal no código? o nome da branch e os commits seguem o padrão? tem consulta SQL sem *prepared statement*?
5. Finalize em **Review changes**: *Approve* (aprovado), *Request changes* (precisa de ajustes) ou *Comment*.
6. Seja objetivo e gentil: critique o código, não a pessoa.

## 8. Resolver conflitos

Conflito acontece quando duas pessoas mudam as mesmas linhas. Resolva **na sua branch**, nunca na `develop`:

```powershell
git fetch origin
git merge origin/develop
```

Se o Git avisar `CONFLICT`:

```powershell
git status
```

Abra cada arquivo listado em *both modified*. Você verá:

```text
<<<<<<< HEAD
(o que está na sua branch)
=======
(o que veio da develop)
>>>>>>> origin/develop
```

Edite deixando o resultado correto (pode ser um, outro ou a mistura), **apague os marcadores** `<<<<<<<`, `=======` e `>>>>>>>`, salve e finalize:

```powershell
git add <arquivo-resolvido>
git commit -m "chore: resolve conflitos com develop"
git push
```

Se a situação ficar confusa, **cancele o merge e peça ajuda** — nada foi perdido:

```powershell
git merge --abort
```

Dica para evitar conflitos: avise no grupo quando for mexer em arquivos grandes e compartilhados (`js/dashboard.js`, `dashboard.php`, `Backend/routes/api.php`).

## 9. Comunicar problemas

- **Bug:** abra uma Issue com o modelo *Relatar um bug* (passos para reproduzir, resultado esperado e obtido, prints).
- **Ideia ou melhoria:** Issue com o modelo *Solicitar uma funcionalidade*.
- **Tarefa técnica** (refatoração, configuração, documentação): modelo *Registrar uma tarefa técnica*.
- Use as *labels* (`backend`, `frontend`, `database`, `bug`, `priority-high`…) e atribua a Issue a quem vai resolver.
- **Falha de segurança:** não escreva detalhes em Issue pública; fale direto com o responsável pelo projeto.
- Dúvidas rápidas: grupo da equipe. Decisões importantes: registre na Issue/PR para ficar documentado.

Guia de Issues, labels e Projects: [`docs/github/issues-e-projects.md`](docs/github/issues-e-projects.md).

## 10. Regras de segurança

**Nunca** envie ao repositório:

- o arquivo `.env` (já está no `.gitignore`, mas confira com `git status`);
- senhas, tokens, chaves, RMs/e-mails/nomes reais de alunos;
- backups ou *dumps* com dados (o único SQL versionado é a **estrutura** e os *seeds* oficiais);
- scripts temporários com senha fixa.

Antes de cada commit, confira o que vai ser enviado:

```powershell
git status
git diff --staged
```

**Se um segredo foi enviado por engano:** avise o responsável **imediatamente** e **troque a senha/chave** — apagar o arquivo num commit novo não remove o segredo do histórico.

## 11. Padrões de código

- Mantenha a arquitetura atual: PHP puro, MVC simplificado, JavaScript puro. Não introduza frameworks sem decisão da equipe.
- Consultas ao banco **sempre** com PDO e *prepared statements* (nunca concatenar dados do usuário no SQL).
- Novas rotas vão em `Backend/routes/api.php`. Rotas que não sejam públicas **precisam** de `autenticarRequisicao()` e, se forem administrativas, de `exigirPerfil(...)`.
- Ações administrativas devem registrar auditoria (`AuditoriaService::log`).
- Nada de credenciais no código: use `.env` (veja `Backend/config/env.php`).
- Mudou rotas, tabelas ou fluxos? Atualize a documentação em `docs/` no mesmo PR.
