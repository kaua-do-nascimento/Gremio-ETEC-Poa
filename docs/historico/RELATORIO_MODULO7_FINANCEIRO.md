# MÓDULO 7 — FINANCEIRO
### Grêmio ETEC Poá

## Status

**COMPLETO** (escopo: categorias + movimentações financeiras + resumo — ver justificativa de escopo na seção 11).

---

## 1. Análise do banco

O schema (`database/gremio_etec_poa.sql`) já tinha **toda a estrutura financeira pronta antes desta implementação** — nenhuma tabela foi criada por este módulo. Tabelas encontradas:

| Tabela | Situação antes | Usada neste módulo? |
|---|---|---|
| `categorias_financeiras` (id, nome, tipo `entrada`/`saida`, descricao, deleted_at) | Existia, **vazia**, sem tela de cadastro | Sim — leitura |
| `movimentacoes_financeiras` (categoria_id FK, usuario_id FK, tipo, origem `doacao/patrocinio/evento/outro`, descricao, valor `decimal(10,2)` com CHECK `>= 0`, data_movimentacao, timestamps, deleted_at) | Existia, vazia | Sim — CRUD completo |
| `orcamentos` / `itens_orcamento` (planejamento anual, status planejado/aprovado/encerrado) | Existia, vazia, sem tela | Não (ver seção 11) |
| `comprovantes` (anexo de arquivo por movimentação: nome, caminho, mime_type, hash) | Existia, vazia, sem tela | Não (ver seção 11) |
| `prestacoes_contas` (relatório de transparência, status em_elaboracao/publicada) | Existia, vazia, sem tela | Não (ver seção 11) |

`Backend/controllers/FinanceiroController.php` já existia no projeto, mas como **arquivo vazio (stub, 0 bytes)** — nenhuma linha de código foi removida ou sobrescrita, só preenchido.

A coluna `modulo` de `logs` (auditoria) já documentava `'financeiro'` como valor esperado no seu próprio comentário de schema, confirmando que o módulo estava previsto desde a criação do banco.

---

## 2. Arquivos criados

| Arquivo | Descrição |
|---|---|
| `Backend/models/Financeiro.php` | Model: categorias (leitura) + CRUD de movimentações + resumo agregado |
| `database/seed_categorias_financeiras.sql` | Seed de **dados** (sem DDL) — 9 categorias padrão (4 entrada, 5 saída) |

## 3. Arquivos alterados

| Arquivo | Alteração |
|---|---|
| `Backend/controllers/FinanceiroController.php` | Estava vazio (stub) → implementado por completo |
| `Backend/routes/api.php` | + `require_once` do controller, + 7 rotas `/api/financeiro*` |
| `dashboard.php` | + item de navegação "Financeiro", + seção da página (stats + tabela + filtros + paginação), + 2 modais (criar/editar, arquivar) |
| `js/api.js` | + 7 funções (`apiListarFinanceiro`, `apiListarCategoriasFinanceiras`, `apiResumoFinanceiro`, `apiBuscarMovimentacaoFinanceira`, `apiCriarMovimentacaoFinanceira`, `apiAtualizarMovimentacaoFinanceira`, `apiExcluirMovimentacaoFinanceira`) |
| `js/dashboard.js` | + módulo completo (listagem/filtros/paginação/resumo/modais), + entrada no `pageTitles` e no dispatcher de navegação |

**Nenhum outro arquivo foi tocado.** CSS: zero classes novas — o módulo inteiro reaproveita `.stat-card`, `.table-card`, `.status-ativo`/`.status-suspenso`, `.btn-primary/secondary/danger`, `.edit-panel`, `.delete-panel`, `.field`, `.row-actions`, `.pagination-*` já existentes.

## 4. Rotas

Todas somente **administrador** (mesmo nível de restrição de `/api/auditoria`):

```
GET  /api/financeiro/categorias
GET  /api/financeiro/resumo?data_inicio=&data_fim=
GET  /api/financeiro?tipo=&categoria_id=&data_inicio=&data_fim=&pagina=&limite=
POST /api/financeiro
GET  /api/financeiro/{id}
POST /api/financeiro/{id}/atualizar
POST /api/financeiro/{id}/excluir
```

## 5. Funcionalidades

- Listar movimentações com filtro por tipo, categoria, período (data_inicio/data_fim) e paginação server-side (mesmo padrão de `/api/auditoria`)
- Ver detalhe de uma movimentação
- Criar receita / criar despesa
- Editar movimentação
- Arquivar (soft delete via `deleted_at`, nunca `DELETE` físico)
- Listar categorias (para popular os `<select>` do formulário)
- Resumo financeiro: total de entradas, total de saídas, saldo, quantidade — com filtro opcional de período
- Front-end: 4 cards (entradas/saídas/saldo/quantidade), tabela com filtros + paginação, modal de criar/editar, modal de confirmação de arquivamento — tudo integrado ao `dashboard.php` existente, no mesmo padrão visual de Eventos/Projetos/Auditoria

## 6. Segurança

| Item | Implementação |
|---|---|
| Autenticação | `autenticarRequisicao()` (Bearer Token, mesmo middleware de todo o sistema) em toda rota |
| RBAC | `exigirPerfil($usuario, ['administrador'])` em **todas** as 7 rotas, inclusive leitura — é o módulo com o RBAC mais restritivo do sistema, no mesmo nível de Auditoria |
| IDOR | Modelo de autorização é por **perfil**, não por "dono do registro" (movimentação financeira é um livro-caixa organizacional, não um dado pessoal — mesmo modelo já usado em Eventos/Projetos/Auditoria: qualquer administrador pode ver/editar qualquer lançamento). Testado: usuário sem perfil administrador recebe 403 **independente do ID** tentado; ID inexistente retorna 404 mesmo para administrador |
| Prepared statements | 100% das queries usam PDO com bind nomeado (`:parametro`), zero concatenação de SQL com input do usuário |
| Validação de entrada | Backend valida tudo, independente do front: tipo (enum), categoria (existe + `categoria.tipo` precisa bater com `movimentacao.tipo` — regra de consistência checada mesmo não sendo constraint do banco), descrição (obrigatória, máx. 255), valor (numérico, `> 0`, máx. `99999999.99` = limite físico do `decimal(10,2)`), data (formato `AAAA-MM-DD` válido), origem (enum quando informada) |
| Auditoria | `AuditoriaService::log()` chamado em criação/atualização/arquivamento — **decisão deliberada**: o log registra só tipo + id (ex.: `Movimentação financeira do tipo "entrada" criada (#7).`), **nunca o valor ou a descrição**, seguindo à risca a instrução de não logar informação financeira sensível mesmo em módulo já restrito a administrador |
| Valores monetários | `valor` nunca é convertido para `float` em PHP — trafega como string decimal do MySQL do banco até a resposta JSON. Toda soma (resumo) é feita em SQL (`SUM` sobre `DECIMAL` nativo), nunca somada em PHP. O front-end só formata para exibição (`R$ 0,00`) na hora de renderizar |

## 7. Banco

- **Nenhuma tabela criada. Nenhuma coluna alterada. Nenhum `ALTER`/`CREATE TABLE` executado.**
- Único script SQL novo: `database/seed_categorias_financeiras.sql` — **dados**, não estrutura. Necessário porque `categorias_financeiras` existia vazia e `categoria_id` é `NOT NULL` em `movimentacoes_financeiras` (seria impossível lançar qualquer receita/despesa sem isso). Usa o mesmo padrão de `INSERT ... WHERE NOT EXISTS` de `seed_perfis.sql` — seguro rodar mais de uma vez.
- Para aplicar no seu ambiente (WampServer; PowerShell ou CMD, a partir da pasta do projeto):
  ```powershell
  mysql -u root -P 3307 --default-character-set=utf8mb4 gremio_etec_poa -e "source database/seed_categorias_financeiras.sql"
  ```
  **Atualização (Módulo 8):** a versão anterior desta instrução usava `Get-Content ... | mysql`. Como o seed tem acentos e o Windows PowerShell 5.1 lê o arquivo como ANSI e envia o pipe em ASCII, os nomes podem ter ido ao banco corrompidos (`Doa??o`). Confira com `SELECT id, nome FROM categorias_financeiras;` — detalhes de correção no cabeçalho do próprio seed.

## 8. Testes reais

**Importante sobre o ambiente de teste:** este assistente não tem acesso ao seu WampServer/Windows local. Os testes abaixo são **reais** (servidor PHP embutido + MariaDB de verdade, schema idêntico ao seu, rodando de ponta a ponta), mas executados numa réplica no sandbox Linux deste ambiente — não no seu `C:\wamp64`. Diferenças de ambiente encontradas e como foram tratadas:
- PHP disponível aqui: **8.3** (o `apt` do Ubuntu 24.04 não oferece 8.5). O código não usa nenhuma sintaxe exclusiva de 8.4/8.5 (sem enums nativos, sem `readonly`, sem novidades de 8.4+), então a expectativa é compatibilidade total com seu PHP 8.5 — mas isso não foi validado literalmente na versão 8.5.
- `php-mbstring` precisou ser instalado manualmente (ausente por padrão neste sandbox; o WampServer já vem com mbstring habilitado por padrão, então isso não deve ser um problema no seu ambiente).
- Usuário administrador de teste criado com o mecanismo **oficial já existente** no projeto: `Backend/tests/criar-admin.php`. Usuário "aluno" de teste criado pelo fluxo real (`POST /api/cadastro` → aprovação via `POST /api/admin/solicitacoes/{id}/aprovar`), exercitando de brinde o fluxo de cadastro. Nenhum desses usuários existe no seu banco real — só na réplica descartável usada para o teste.

| Teste | Resultado | Observação |
|---|---|---|
| 1. Sem token | PASSOU — teste real | HTTP 401 |
| 2. Token inválido | PASSOU — teste real | HTTP 401 |
| 3. Login admin | PASSOU — teste real | token obtido |
| 4. Listar financeiro (admin) | PASSOU — teste real | HTTP 200 |
| 5. Criar receita válida | PASSOU — teste real | id=1, valor=150.50 |
| 6. Receita persistida no banco | PASSOU — teste real | `SELECT valor` = 150.50 |
| 7. Criar despesa válida | PASSOU — teste real | id=2, valor=42.90 |
| 8. Despesa persistida no banco | PASSOU — teste real | `SELECT valor` = 42.90 |
| 9. Resumo financeiro | PASSOU — teste real | entradas=150.50, saídas=42.90, saldo=107.60 (bate: entradas−saídas=saldo) |
| 10. Editar movimentação | PASSOU — teste real | valor 150.50→200.00, descrição atualizada |
| 11. Alteração persistida | PASSOU — teste real | confirmado via `SELECT` direto no banco |
| 12. Excluir/arquivar | PASSOU — teste real | `deleted_at` preenchido no banco (soft delete) |
| 13. Valor inválido (negativo) | PASSOU — teste real | HTTP 400 |
| 14. Campos obrigatórios faltando | PASSOU — teste real | HTTP 400 |
| 15. ID inexistente | PASSOU — teste real | HTTP 404 |
| 16. IDOR (aluno acessando ID real) | PASSOU — teste real | HTTP 403 — bloqueado por RBAC, independente do ID (ver seção 6) |
| 17. RBAC sem permissão | PASSOU — teste real | listar/criar/resumo = 403 para usuário "aluno" |
| 18. Auditoria registrou ações | PASSOU — teste real | 4 logs com `modulo=financeiro` (criação/atualização/arquivamento) |
| 19. Log sem dado sensível | PASSOU — teste real | nenhum `senha`/`token`/`hash`; valor/descrição também não aparecem (decisão deliberada, seção 6) |
| 20. Filtros | PASSOU — teste real | `?tipo=entrada` retornou só linhas `entrada` |
| 21. Paginação | PASSOU — teste real | `?pagina=1&limite=1` retornou 1 item, `total_paginas` correto |
| 22. Frontend | PASSOU — análise estática | sem navegador real neste sandbox (ver detalhe abaixo) |

**Detalhe do teste 22:** `node --check` em `api.js` e `dashboard.js` (sintaxe OK) + verificação cruzada automatizada: toda função `apiXxxFinanceiro` chamada em `dashboard.js` existe em `api.js`; todo `id` de elemento HTML referenciado via `getElementById` no JS do módulo existe em `dashboard.php`; toda rota chamada pelo front (`/financeiro`, `/financeiro/categorias`, `/financeiro/resumo`, `/financeiro/{id}/atualizar`, `/financeiro/{id}/excluir`) está registrada em `routes/api.php`. As três checagens passaram. Não abri navegador real — não afirmo ter testado clique-a-clique na interface.

## 9. Testes de regressão (módulos 1–6)

| Módulo | Teste | Resultado |
|---|---|---|
| AUTH | `GET /api/me` | PASSOU — teste real |
| AUTH | logout | PASSOU — teste real |
| PUBLICAÇÕES | listar | PASSOU — teste real |
| PUBLICAÇÕES | criar | PASSOU — teste real |
| PUBLICAÇÕES | reagir | PASSOU — teste real |
| EVENTOS | listar | PASSOU — teste real |
| EVENTOS | criar | PASSOU — teste real |
| EVENTOS | participar | PASSOU — teste real |
| VOTAÇÕES | listar | PASSOU — teste real |
| VOTAÇÕES | criar | PASSOU — teste real |
| VOTAÇÕES | votar | PASSOU — teste real* |
| PROJETOS | listar | PASSOU — teste real |
| NOTIFICAÇÕES | listar / não lidas / marcar lidas | PASSOU — teste real (3/3) |
| AUDITORIA | listar como admin | PASSOU — teste real |

\* Na primeira rodada, "votar" retornou erro de negócio ("Esta votação não está aberta no momento") porque meu script de teste criou a votação sem definir `status`, que por padrão nasce `agendada` — comportamento correto do módulo, não regressão. Refiz o teste passando `status: "aberta"` explicitamente e o voto foi registrado com sucesso, confirmando **zero regressão** em Votações.

**Nenhum módulo 1–6 quebrou.**

## 10. Problemas encontrados

| Problema | Causa | Correção |
|---|---|---|
| `PHP Fatal error: Call to undefined function mb_strlen()` ao criar/editar movimentação | `php-mbstring` não vem instalado por padrão neste sandbox Linux (diferente do WampServer, que já vem com mbstring habilitado) | Extensão instalada no ambiente de teste. Não deve afetar seu WampServer, mas vale conferir `php -m \| findstr mbstring` (ou `php_info()`) se algo parecido acontecer no seu ambiente |
| Falso negativo no teste de "votar" (regressão) | Script de teste não passou `status: "aberta"` ao criar a votação de teste | Não é um bug do código — é lacuna do meu script de teste, corrigida e reverificada (seção 9) |

Nenhum outro erro foi encontrado nos 22 testes do Financeiro nem nos 13 testes de regressão.

## 11. Limitações

- **Orçamentos (`orcamentos`/`itens_orcamento`), comprovantes (`comprovantes`) e prestação de contas (`prestacoes_contas`) não foram implementados.** As tabelas existem prontas no schema, mas não há nenhuma tela ou fluxo previsto para elas em nenhum lugar do sistema hoje, e a lista de funcionalidades do Módulo 7 (listar/criar/editar/arquivar movimentação, categorias, filtros, período, resumo, saldo, histórico) mapeia integralmente para `categorias_financeiras` + `movimentacoes_financeiras` — nenhuma dessas 3 tabelas extras é mencionada na lista. `comprovantes` especificamente exigiria um fluxo de upload de arquivo que não existe em nenhum módulo do sistema hoje — mesma situação (e mesma decisão) já documentada no código de `anexos_evento`, no módulo de Eventos. Implementar as 3 rasas, sem tela real por trás, teria menos valor do que documentar o que falta e deixar pronto pra próxima rodada.
- Testes reais rodaram em PHP 8.3 (ambiente disponível neste sandbox), não 8.5 (sua versão local). Sem incompatibilidade identificada no código, mas não é o mesmo binário.
- Categorias financeiras não têm tela de cadastro/edição (só leitura) — não estava na lista de funcionalidades pedida, e criar uma teria exigido decidir uma UI nova sem pedido explícito.

## 12. Próximos passos

- Rodar `database/seed_categorias_financeiras.sql` no banco real antes de usar o módulo em produção (sem isso, `categoria_id` não tem o que referenciar).
- Se fizer sentido pro projeto: Módulo 7.1 (orçamento anual, usando `orcamentos`/`itens_orcamento`, já com schema pronto) e Módulo 7.2 (comprovante anexado à movimentação, decidindo primeiro o fluxo de upload de arquivo — hoje inexistente em qualquer módulo).
- `prestacoes_contas` sugere uma tela pública de transparência (fora do dashboard administrativo) — vale definir se isso é uma página nova ou uma seção do site institucional antes de implementar.
