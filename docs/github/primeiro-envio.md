# Primeiro envio ao GitHub (responsável pelo projeto)

Todos os comandos são para o **PowerShell**, dentro de `C:\wamp64\www\Gremio-ETEC-Poa`. Os comandos de Git desta página foram conferidos numa cópia do projeto; **nenhum deles envia algo ao GitHub até o passo 9**.

Substitua os marcadores:

- `<USUARIO-OU-ORGANIZACAO>` → seu usuário (ou organização) do GitHub
- `<NOME-DO-REPOSITORIO>` → nome do repositório criado no GitHub
- `<SEU NOME>` e `<seu-email>` → os da sua conta do GitHub

## Antes de tudo

1. Crie o repositório **vazio** no GitHub (sem README, sem `.gitignore`, sem licença) — veja [guia-equipe.md](guia-equipe.md#2-criar-o-repositório-responsável).
2. **Apague** o arquivo temporário `Backend\tests\criar-admin-web.php` (continha uma senha fixa). Ele já está no `.gitignore`, mas não deve continuar no seu computador:

```powershell
Remove-Item Backend\tests\criar-admin-web.php
```

3. Se alguma senha que apareceu nesse arquivo ou em comentários antigos ainda estiver em uso, **troque-a** (`php Backend/tests/resetar-senha.php 00001 NovaSenhaForte@123`).

## 1. Verificar se já existe um repositório Git na pasta

```powershell
cd C:\wamp64\www\Gremio-ETEC-Poa
Test-Path .git
```

- `False` → siga para o passo 2.
- `True` → **pare e analise antes de continuar** (não rode `git init`, `reset` nem `clean`):

```powershell
git status
git log --oneline -n 10
git remote -v
git branch -a
```

Se houver histórico, confira se ele contém segredos antes de publicá-lo: `git log -p --all -S"senha" -- . ` e `git log --all --diff-filter=A --name-only | Select-String "\.env|backup|\.sql"`. Se encontrar algum, fale com quem manteve o repositório; **não publique esse histórico**.

## 2. Inicializar o Git e configurar a branch `main`

```powershell
git init -b main
```

(Se seu Git for antigo e der erro no `-b`: `git init` seguido de `git branch -M main`.)

Identificação **somente deste repositório**:

```powershell
git config user.name "<SEU NOME>"
git config user.email "<seu-email>"
```

## 3. Conferir o que será ignorado

Confirme que os arquivos sensíveis **estão** ignorados (cada linha impressa mostra a regra que o ignora):

```powershell
git check-ignore -v .env Backend/tests/criar-admin-web.php uploads/qualquer.png backup_teste.sql
```

Confirme que os arquivos essenciais **não** estão ignorados (não deve imprimir nada):

```powershell
git check-ignore -v .env.example uploads/.gitkeep database/gremio_etec_poa.sql database/seed_perfis.sql "imgs/gremio icon.png" Backend/config/database.php
```

Veja também tudo o que o Git está ignorando de fato:

```powershell
git status --short --ignored
```

## 4. Revisar os arquivos que serão enviados

Simulação (não adiciona nada):

```powershell
git add -n .
```

Adicione e liste:

```powershell
git add .
git status
git ls-files
```

O esperado são cerca de 100 arquivos. **Não** devem aparecer: `.env`, `criar-admin-web.php`, backups `.sql`, arquivos em `uploads/` (exceto `.gitkeep`).

Avisos `CRLF will be replaced by LF` são normais: o `.gitattributes` padroniza os finais de linha e não altera o funcionamento do sistema.

## 5. Varredura final de segredos (nada deve ser listado)

```powershell
git grep --cached -nIiE "Admin@123|C:\\Users" 
git grep --cached -nIE "\$2y\$[0-9]{2}\$[./A-Za-z0-9]{50,}"
```

Se algo aparecer, remova o trecho, rode `git add .` de novo e repita. Para tirar um arquivo da área de envio sem apagá-lo: `git restore --staged <arquivo>`.

## 6. Criar o primeiro commit

```powershell
git commit -m "chore: estrutura inicial do repositório Grêmio ETEC Poá"
git log --oneline
```

## 7. Configurar o remote

```powershell
git remote add origin https://github.com/<USUARIO-OU-ORGANIZACAO>/<NOME-DO-REPOSITORIO>.git
git remote -v
```

## 8. Criar a branch `develop`

```powershell
git branch develop
git branch
```

## 9. Publicar as branches (**só quando você decidir publicar**)

```powershell
git push -u origin main
git push -u origin develop
```

O Git abrirá uma janela para você entrar na conta do GitHub. Depois do envio, confira no navegador que o repositório mostra o README e **não** contém `.env`.

## 10. Depois da publicação

1. No GitHub: **Settings → General → Default branch** → escolha `develop` (para que novos PRs apontem para ela por padrão). Opcional.
2. Configure a proteção de `main` e `develop` — [guia-equipe.md](guia-equipe.md#6-proteção-de-branches-responsável).
3. Convide a equipe — [guia-equipe.md](guia-equipe.md#3-convidar-colaboradores-responsável).
4. Crie as labels, o Project e as primeiras Issues — [issues-e-projects.md](issues-e-projects.md).
5. Peça para cada integrante seguir o [CONTRIBUTING.md](../../CONTRIBUTING.md).

## Se algo der errado

| Situação | O que fazer |
|---|---|
| Commitou um arquivo que não devia, **antes** do push | `git rm --cached <arquivo>` e `git commit --amend --no-edit` (ou novo commit). Confira o `.gitignore` |
| Já fez push de um segredo | **Troque a senha/chave imediatamente.** Depois, fale com o responsável para limpar o histórico — apagar o arquivo num novo commit **não** remove o segredo do histórico |
| `git push` rejeitado (`non-fast-forward`) | O repositório remoto já tem commits. **Não use `--force`.** Rode `git pull origin main --allow-unrelated-histories`, resolva e tente de novo — ou peça ajuda |
| Esqueceu de criar a branch `develop` | `git checkout -b develop` e `git push -u origin develop` |
