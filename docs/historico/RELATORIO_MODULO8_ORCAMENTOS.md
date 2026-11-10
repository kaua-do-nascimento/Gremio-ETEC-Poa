# MÓDULO 8 — ORÇAMENTOS
### Grêmio ETEC Poá

## 1. Status

**COMPLETO** no escopo pedido (orçamentos + itens, total calculado pelo backend, RBAC, auditoria, transações, frontend integrado). Decisões em aberto na seção 13; checklist para o seu WAMP na seção 16.

- **Base usada:** o zip do Módulo 7 que eu entreguei (o sandbox foi resetado entre as conversas). Se o seu projeto local divergiu dele, compare os arquivos da seção 3 antes de sobrescrever.
- **Ambiente dos testes — uma réplica, não o seu WAMP:** Ubuntu 24.04, **PHP 8.3.6** (não 8.5), **MariaDB 10.11.14** (não sei qual engine/versão está na sua porta 3307), servidor embutido do PHP com 8 workers (sem Apache, sem `.htaccess`). Node 22.22 + jsdom 30.1 só para o teste de frontend, fora do projeto (nada de Node entrou no projeto).
- Tudo que está marcado PASS foi **executado**. O que não foi executado está em "NÃO EXECUTADO" abaixo.

| NÃO EXECUTADO | Motivo |
|---|---|
| Teste em navegador real (layout, CSS, largura do painel de itens, cores dos badges) | sem navegador no sandbox; o teste de UI usou jsdom (DOM simulado) |
| PHP 8.5 | só 8.3 disponível; sem sintaxe de 8.4+/8.5 no código novo, mas não é o mesmo binário |
| MySQL Oracle 8/9 | só MariaDB 10.11 |
| Apache + `.htaccess` do WAMP | usei `php -S` com `index.php` como roteador |

---

## 2. Arquivos criados

| Arquivo | Descrição |
|---|---|
| `Backend/models/Orcamento.php` | Model + `OrcamentoException`: CRUD de orçamentos e itens, soma/consistência em SQL, transação com lock |
| `Backend/controllers/OrcamentoController.php` | Validação, mapeamento de erros → HTTP, auditoria, formatação |
| `RELATORIO_MODULO8_ORCAMENTOS.md` | Este relatório |

`OrcamentoController.php` não existia no projeto (ao contrário do `FinanceiroController.php` no Módulo 7, que era um stub vazio).

## 3. Arquivos alterados

| Arquivo | O que mudou |
|---|---|
| `Backend/routes/api.php` | `require_once` do controller + 9 rotas `/api/orcamentos*`, todas com `autenticarRequisicao()` + `exigirPerfil(['administrador'])` |
| `dashboard.php` | item de menu "Orçamentos", seção `page-orcamentos` (3 cards, filtros, tabela, paginação) e 4 modais (criar/editar, itens, arquivar, remover item) |
| `js/api.js` | 9 funções `api*Orcamento*` (mesmo formato das do Financeiro) |
| `js/dashboard.js` | módulo Orçamentos anexado ao fim; `pageTitles`/dispatcher/`closeModal`; rótulos de exibição da Auditoria; **fix de paginação do Financeiro (ver 11/12)** |
| `css/dashboard.css` | bloco novo no fim, todo prefixado/escopado (`.edit-panel-wide`, `.orcamento-*`, `#orcamentoModalOverlay .field textarea`). Nenhuma regra existente foi alterada — o `textarea` ficou escopado de propósito para não mudar os modais dos outros módulos |
| `database/seed_categorias_financeiras.sql` | cabeçalho + `SET NAMES utf8mb4;` (os dados inseridos são idênticos). Ver 11/12 e 15 |
| `RELATORIO_MODULO7_FINANCEIRO.md` | instrução do comando do seed corrigida |

Conferido por `diff -rq` contra o zip do Módulo 7: **nenhum outro arquivo mudou**.

## 4. Endpoints criados

Todos exigem Bearer Token e perfil `administrador`.

| Método | Rota | Função |
|---|---|---|
| GET | `/api/orcamentos?ano_referencia=&status=&pagina=&limite=` | lista paginada + `resumo` (quantidade, soma dos totais, aprovados) |
| POST | `/api/orcamentos` | cria (201) |
| GET | `/api/orcamentos/{id}` | cabeçalho + totais calculados + flag `consistente` |
| POST | `/api/orcamentos/{id}/atualizar` | edita nome/ano/status/descrição |
| POST | `/api/orcamentos/{id}/excluir` | arquiva (soft delete) |
| GET | `/api/orcamentos/{id}/itens` | itens (com categoria) + cabeçalho + totais |
| POST | `/api/orcamentos/{id}/itens` | adiciona item (201) |
| POST | `/api/orcamentos/{id}/itens/{item_id}/atualizar` | edita item (escopo: item pertence ao orçamento da URL) |
| POST | `/api/orcamentos/{id}/itens/{item_id}/excluir` | remove item (idem) |

Códigos: 200/201 · 400 validação · 401 sem token ou token inválido · 403 sem perfil · 404 inexistente/arquivado/item de outro orçamento · 500 genérico (sem vazar SQL).

## 5. Tabelas utilizadas

Estrutura lida do dump real (`database/gremio_etec_poa.sql`), nada presumido:

| Tabela | Uso | Estrutura relevante |
|---|---|---|
| `orcamentos` | leitura/escrita | `nome varchar(150)`, `ano_referencia YEAR`, `descricao text NULL`, `valor_total decimal(10,2) DEFAULT 0 CHECK >= 0`, `status enum(planejado,aprovado,encerrado)`, `criado_por` FK `usuarios`, `deleted_at` |
| `itens_orcamento` | leitura/escrita | `orcamento_id` FK (CASCADE), `categoria_id` FK `categorias_financeiras`, `descricao varchar(255)`, `valor_previsto`/`valor_realizado decimal(10,2)`. **Sem `deleted_at`, sem `quantidade`, sem CHECK nos valores** |
| `categorias_financeiras` | só leitura (via `Financeiro::buscarCategoriaPorId` e `/api/financeiro/categorias`) | FK dos itens |
| `usuarios` | JOIN (`criado_por_nome`) | |
| `logs` | via `AuditoriaService` | |
| `sessoes`, `perfis`, `usuario_perfil` | autenticação/RBAC (middleware existente) | |
| `movimentacoes_financeiras` | **não usada** | não há FK com orçamentos/itens (ver 8) |
| `prestacoes_contas` | **não usada** | tem FK `orcamento_id` (SET NULL); arquivar orçamento é soft delete, então não dispara a FK (ver 13) |

## 6. Funcionalidades implementadas

- Listar orçamentos com filtros (ano, status), paginação server-side (10 por página na UI) e 3 cards (quantidade, valor total previsto, aprovados) calculados em SQL sobre o mesmo filtro.
- Ver detalhes; criar; editar; arquivar (soft delete) orçamento.
- Itens: listar, adicionar, editar, remover — com categoria (badge entrada/saída), previsto e realizado.
- **Totais calculados pelo backend:** `valor_total = SUM(valor_previsto)` dos itens, recalculado em SQL a cada mudança; a API devolve também `soma_previsto`, `soma_realizado`, `total_itens` e `consistente` (comparação feita pelo MySQL).
- Frontend: menu, tabela, filtros, paginação (inclui fallback quando a página fica vazia), modal criar/editar, tela de itens com formulário inline (adicionar/editar), confirmação antes de arquivar orçamento e de remover item. Criar um orçamento já abre a tela de itens.

## 7. Regras de segurança

| Regra | Como está implementada | Evidência |
|---|---|---|
| Autenticação | `autenticarRequisicao()` (Bearer) nas 9 rotas | testes 1, 2 |
| RBAC | `exigirPerfil($usuario, ['administrador'])` nas 9 rotas (verificado por grep: 9 ocorrências no bloco) | 3, 4, R19 |
| IDOR | toda query de item filtra `id = :item AND orcamento_id = :orc` (SELECT FOR UPDATE, UPDATE e DELETE); orçamento arquivado ⇒ itens inacessíveis | 16, **18**, mutante B |
| Total nunca vem do cliente | `valor_total` não é lido do corpo em lugar nenhum; criação grava o DEFAULT 0.00 | E1 |
| Consistência + atomicidade | `BEGIN → SELECT … FOR UPDATE no orçamento → altera → recalcula → confere total == soma → COMMIT`; qualquer erro ⇒ `ROLLBACK` | E3, E5, E6, E7, mutantes A e C |
| Dinheiro sem float | valores validados por regex em string (até 8 inteiros + 2 casas), somas em `DECIMAL` no SQL; **não arredonda em silêncio** (3+ casas é erro); rejeita negativo, zero (previsto), `1e3`, vírgula, bool, array, null | 19 |
| Limites do banco | nome ≤150, descrição item ≤255, ano 1901–2155 (tipo `YEAR`), valor ≤ 99.999.999,99, **soma dos itens** ≤ limite do `decimal(10,2)` (pré-checada em SQL: erro 400, não 500) | 19, E3 |
| Prepared statements | 100% das queries; a única interpolação é `{$where}`, montado só com fragmentos fixos + parâmetros nomeados; `->query/->exec` não aparecem nos arquivos novos | checagem estática |
| Auditoria sem dado sensível | só ids e ano (ex.: `Item #12 adicionado ao orçamento #5.`) — sem nome, descrição, valores, token ou senha | 21, 22 |
| Erros | 500 genéricos; nenhuma mensagem de SQL/PDO sai na resposta | revisão de código |

## 8. Integrações com Financeiro e Auditoria

**Financeiro (Módulo 7).** O único vínculo real no banco é a FK `itens_orcamento.categoria_id → categorias_financeiras`, e ela é usada de ponta a ponta: o controller valida a categoria com o mesmo `Financeiro::buscarCategoriaPorId` do Módulo 7 (existe e não está arquivada); a UI popula o seletor com o mesmo `/api/financeiro/categorias`; a tela de itens mostra nome + tipo (entrada/saída) da categoria. O RBAC é idêntico (administrador-only) e as mesmas decisões de dinheiro (string decimal, sem float) foram mantidas.

**Não existe FK entre orçamentos/itens e `movimentacoes_financeiras`** (nem de um lado nem do outro). Por isso `valor_realizado` é informado manualmente pelo administrador e **não** é calculado a partir das movimentações. Dá para derivar um "previsto × realizado" de verdade sem alterar o banco (casando `categoria_id` + ano da `data_movimentacao` com `ano_referencia`), mas é uma inferência — não um relacionamento gravado — e fica ambígua quando dois itens do mesmo orçamento usam a mesma categoria. Não implementei; está como decisão sua na seção 13.

**Auditoria.** `AuditoriaService::log()` com `modulo = 'orcamentos'` e as ações: `criacao`, `atualizacao`, `arquivamento`, `criacao_item`, `alteracao_item`, `exclusao_item`. A tela de Auditoria já filtra por módulo/ação a partir do banco (`SELECT DISTINCT`), então o módulo novo aparece sozinho; só adicionei **rótulos de exibição** (`Orçamentos`, `Financeiro`, `Criação/Alteração/Remoção de item`). Nenhuma lógica do Módulo 6 foi alterada.

---

## 9. Testes executados

**Totais da rodada final (banco limpo, código final):** API **57 PASS / 0 FAIL** (37 do Módulo 8 + 20 de regressão) · UI jsdom **23 PASS / 0 FAIL** · **0** Warning/Notice/Deprecated/Fatal no log do PHP (`error_reporting=-1`) durante toda a bateria.

Formato: número · descrição · esperado · obtido · PASS/FAIL. Os testes 1–23 são os da sua lista (o 23 foi dividido em 23.1–23.6); E1–E8 e S1 são extras que eu adicionei (segurança/integridade, concorrência, seed, log de erros do PHP).

### 9.1 Backend — API real + MariaDB real

| # | Descrição | Esperado | Obtido | Resultado |
|---|---|---|---|---|
| 1 | Acesso sem token | HTTP 401 | HTTP 401 | **PASS** |
| 2 | Token inválido | HTTP 401 | HTTP 401 | **PASS** |
| 3 | Aluno tentando listar | HTTP 403 | HTTP 403 | **PASS** |
| 4 | Aluno tentando criar | HTTP 403 e nada gravado | HTTP 403, orcamentos no banco=0 | **PASS** |
| 5 | Admin lista orçamentos | HTTP 200 + success | HTTP 200 success=true | **PASS** |
| 6 | Admin cria orçamento | HTTP 201 + id | HTTP 201 id=1 | **PASS** |
| 7 | Persistência no banco (nome\|ano\|status\|total\|criado_por\|deleted_at) | Orçamento Anual 2026\|2026\|planejado\|0.00\|1\|NULL | Orçamento Anual 2026\|2026\|planejado\|0.00\|1\|NULL | **PASS** |
| 8 | Visualizar orçamento | 200, nome correto, 0 itens, consistente | HTTP 200 nome=Orçamento Anual 2026 itens=0 consistente=true | **PASS** |
| 9 | Adicionar 2 itens (string 1000.50 / número JSON 250.25; categorias saída e entrada) | 201 e 201; 2 itens no banco | HTTP 201/201, itens no banco=2 | **PASS** |
| 10 | Cálculo do total (1000.50 + 250.25) | banco=1250.75, banco==SUM(itens), API total=soma=1250.75, consistente | banco=1250.75 eq=1 API total=1250.75 soma=1250.75 consistente=true | **PASS** |
| 11 | Editar item (1000.50 → 2000.00) | HTTP 200; banco 'Camisetas (editado)\|2000.00' | HTTP 200; banco 'Camisetas (editado)\|2000.00' | **PASS** |
| 12 | Novo total após edição (2000.00 + 250.25) | banco=2250.25; API total=soma=2250.25; realizado=100.10 | banco=2250.25 API total=2250.25 soma=2250.25 realizado=100.10 | **PASS** |
| 13 | Editar orçamento (nome+status; descrição omitida é preservada; total intacto) | HTTP 200; banco 'Orçamento Anual 2026 (revisado)\|aprovado\|2250.25\|Teste E2E' | HTTP 200; banco 'Orçamento Anual 2026 (revisado)\|aprovado\|2250.25\|Teste E2E' | **PASS** |
| 14 | Remover item (patrocínio 250.25) e recalcular | HTTP 200; item sumiu; total=2000.00 | HTTP 200; item no banco=0; total=2000.00 | **PASS** |
| 15 | Arquivar orçamento (com 1 item) | HTTP 200 | HTTP 200 Orçamento arquivado com sucesso. | **PASS** |
| 16 | Soft delete: linha e itens preservados, mas inacessíveis | deleted_at preenchido + 1 item intacto; GET/itens/POST item/re-arquivar = 404; fora da lista | banco '1\|1' (deletado\|itens); GET=404 itens=404 postItem=404 reArquivar=404 naLista='0' | **PASS** |
| 17 | Orçamento inexistente (GET, itens, atualizar, excluir, excluir item) | 404 em todos | 404/404/404/404/404 | **PASS** |
| 18 | IDOR: alterar/remover item que pertence a OUTRO orçamento (A→item de B e B→item de A) | 404 nos 4 ataques; item B e item A intactos; totais A e B inalterados | HTTP 404/404/404/404; itemB igual=sim; itemA='1\|Camisetas (editado)\|2000.00'; totalA 2000.00→2000.00; totalB 500.00→500.00 | **PASS** |
| 19 | Valores/campos inválidos (23 casos: negativo, zero, texto, 3 casas, estouro, 1e3, vírgula, bool, array, null, categoria, descrição, ano, status, nome, update) | HTTP 400 em todos e NADA gravado | falhas: nenhuma; orcamentos 3→3; itens 3→3 | **PASS** |
| 20 | Campos obrigatórios ausentes (orçamento: vazio/sem ano; item: vazio/sem descrição/sem categoria/sem valor) | 400 em todos | 400/400/400/400/400/400 | **PASS** |
| 21 | Auditoria registrou criação/atualização/arquivamento e criação/alteração/remoção de item | 6 ações presentes (modulo=orcamentos) | ações: alteracao_item,arquivamento,atualizacao,criacao,criacao_item,exclusao_item; faltando: nenhuma; 11 logs | **PASS** |
| 22 | Nenhum token/senha (e nenhum valor/nome/descrição) nas tabelas logs e logs_login | nada encontrado | nada encontrado (logs lidos: 17 linhas) | **PASS** |
| 23.1 | Paginação limite=2: página 1 tem 2, página 2 não repete ids, total e total_paginas corretos | total=5, total_paginas=3, sem ids repetidos | HTTP 200 total=5 total_paginas=3 limite=2 idsOk=1 | **PASS** |
| 23.2 | Filtro ano_referencia=2025 | só 2025; quantidade = banco (2) | HTTP 200 total=2 todos2025=1 | **PASS** |
| 23.3 | Filtro status=aprovado | só aprovados; quantidade = banco (2); resumo.aprovados=2 | HTTP 200 total=2 todosAprov=1 resumo.aprovados=2 | **PASS** |
| 23.4 | Filtros combinados ano=2026 + status=planejado | quantidade = banco (1) | HTTP 200 total=1 | **PASS** |
| 23.5 | Cards (resumo): quantidade e soma dos valor_total = banco | quantidade=5 soma=2500.00 | quantidade=5 soma=2500.00 | **PASS** |
| 23.6 | Filtros inválidos → 400; limite=1000 limitado a 100; página fora do intervalo → 200 vazia | 400/400/400/400; limite=100; vazia | 400/400/400/400; limite=100; HTTP 200 vazia=1 | **PASS** |
| E1 | valor_total enviado pelo cliente é ignorado (criação e edição) | criação grava 0.00; edição mantém 2000.00 | criação=0.00; edição=2000.00 | **PASS** |
| E2 | Edição: status omitido preserva 'aprovado'; descricao "" limpa para NULL | aprovado\|NULL | aprovado\|NULL (status antes: aprovado) | **PASS** |
| E3 | Soma dos itens acima de decimal(10,2): recusa com 400, ROLLBACK (item não fica) e orçamento segue operável | 400; banco '99999999.99\|1'; depois remover → 200 e total 0.00 | HTTP 400 (A soma dos valores previstos ultrapassa o limite de R$ 99.999.999,99 por orçamento.); banco '99999999.99\|1'; remover=200 total=0.00 | **PASS** |
| E4 | Invariante global: nenhum orçamento (inclusive arquivados) com valor_total ≠ soma dos itens | 0 divergentes | 0 divergentes | **PASS** |
| E5 | Concorrência: 12 inserções de item simultâneas (8 workers PHP) no mesmo orçamento | 12×201; itens=12; total=soma=120.00 |  12 201; banco '120.00\|12\|120.00' (total\|itens\|soma) | **PASS** |
| E6 | Concorrência mista: 6 remoções + 6 inserções simultâneas no mesmo orçamento | 6×200 e 6×201; itens=12; total=soma=120.00 | remoções: 6 200; inserções: 6 201; banco '120.00\|12\|120.00' | **PASS** |
| E7 | Invariante global de novo, depois da bateria de concorrência | 0 divergentes | 0 divergentes | **PASS** |
| S1 | Seed de categorias (pré-requisito do M8) rodado por 'source' E por pipe com cliente em latin1: acentos corretos e idempotente | Doação,Patrocínio,Receita de Evento,Outras Entradas,Material de Consumo,Eventos,Manutenção,Serviços,Outras Saídas; 9 linhas após 2 execuções | Doação,Patrocínio,Receita de Evento,Outras Entradas,Material de Consumo,Eventos,Manutenção,Serviços,Outras Saídas; 9 linhas | **PASS** |
| E8 | Log do PHP (error_reporting=-1) sem Warning/Notice/Deprecated/Fatal durante toda a bateria | 0 ocorrências | 0 ocorrências  | **PASS** |

### 9.2 Frontend — `dashboard.php` + `api.js` + `dashboard.js` reais no jsdom, contra a API/banco reais

Isto **não é um navegador**: valida fluxo, DOM, handlers, chamadas à API e renderização de dados/valores; não valida CSS/layout. Inclui regressão de UI do Financeiro (M7), da Auditoria (M6) e das demais páginas.

| # | Descrição | Esperado | Obtido | Resultado |
|---|---|---|---|---|
| U1 | Dashboard real carrega api.js + dashboard.js sem erro de JavaScript | 0 erros; formatarMoeda e item de menu 'Orçamentos' existem | erros=0  | **PASS** |
| U2 | Menu Orçamentos: abre a seção e carrega a lista real (10 por página) | seção visível, usuários oculto, 10 linhas | linhas=10 subtítulo='Orçamentos anuais do grêmio' | **PASS** |
| U3 | Cards: quantidade, valor total previsto e aprovados batem com os dados | 14 / R$ 500,00 / 1 | 14 / R$ 500,00 / 1 | **PASS** |
| U4 | Linha mostra nome, ano, status, nº de itens, total formatado em R$ e autor | UI Seed 2026 | 2026 | Aprovado | 1 | R$ 500,00 | Admin Teste | UI Seed 2026 2026 Aprovado 1 R$ 500,00 Admin Teste | **PASS** |
| U5 | Paginação: 14 itens → página 1 (1–10), página 2 (11–14) | info '1–10 de 14'; página 2 com 4 linhas e '11–14 de 14' | 1–10 de 14 → 11–14 de 14 | **PASS** |
| U6 | Filtro por ano (2025) e botão Limpar filtros | 1 linha 'UI Seed 2025', cards=1; limpar volta a 14 | UI Seed 2025 2025 Planejado 0 R$ 0,00 Admin Teste \| card=1 | **PASS** |
| U7 | Filtro por status (aprovado) | 1 linha 'UI Seed 2026' | UI Seed 2026 2026 Aprovado 1 R$ 500,00 Admin Teste | **PASS** |
| U8 | Criar orçamento pela UI: nome vazio é recusado com mensagem; depois salva e abre a tela de itens | erro exibido e modal aberto; depois modal fecha, itens abrem com o nome, 0.00 no banco | modalAbriu=true erroNome=true id=15 total=0.00 | **PASS** |
| U9 | Adicionar item pela UI (1234.56 previsto / 100 realizado) | 1 linha; total, soma e realizado formatados em R$; banco 1234.56 | R$ 1.234,56 \| soma R$ 1.234,56 \| realizado R$ 100,00 \| banco 1234.56 | **PASS** |
| U10 | Segundo item (entrada, 765.44): total recalculado pelo backend | 2 linhas; total R$ 2.000,00; banco 2000.00 | R$ 2.000,00 \| banco 2000.00 | **PASS** |
| U11 | Editar item pela UI (1234.56 → 1500.00) | form vira 'Editar item', salva, total R$ 2.265,44, form volta ao modo adicionar | prefill=true total=R$ 2.265,44 banco=2265.44 | **PASS** |
| U12 | Erros do backend aparecem na tela (descrição vazia; valor negativo) e nada é gravado | mensagens exibidas; continua com 2 itens | e1='Descrição é obrigatória e deve ter no máximo 255 caracteres.' e2='Valor previsto é obrigatório e deve ser maior que zero (até 2 casas decimais, máximo 99999999.99).' itens=2 | **PASS** |
| U13 | Remover item: Cancelar não remove; Confirmar remove e recalcula | 2 linhas após cancelar; 1 linha e R$ 1.500,00 após confirmar | abriu=true cancelou=true total=R$ 1.500,00 banco=1500.00 | **PASS** |
| U14 | Fechar a tela de itens: a listagem já mostra 1 item e R$ 1.500,00 | linha atualizada | UI Criado 2026 Planejado 1 R$ 1.500,00 Admin Teste | **PASS** |
| U15 | Editar orçamento pela UI (nome + status → Aprovado); total preservado | modal pré-preenchido; linha 'UI Criado (editado)' Aprovado; banco 1500.00 | pre=true status=aprovado total=1500.00 | **PASS** |
| U16 | Arquivar orçamento pela UI (com confirmação) | modal mostra o nome; linha some; API devolve 404; cards 14 | abriu=true api=404 card=14 | **PASS** |
| U16b | Orçamentos: arquivar o ÚNICO item da última página volta para uma página válida | 10 linhas e '1–10 de 10' (sem tabela vazia) | linhas=10 info='1–10 de 10' | **PASS** |
| U17 | Financeiro (M7): menu abre e a tabela/cards vêm do banco | 10 linhas; cards = resumo da API | R$ 150,50 / R$ 78,90 / R$ 71,60 | **PASS** |
| U18 | Financeiro (M7): criar movimentação pela UI | modal fecha; total 11 no banco; cards atualizam | info='1–10 de 11' entradas=R$ 250,40 | **PASS** |
| U19 | Financeiro (M7): arquivar o ÚNICO item da última página volta para uma página válida | tabela com 10 linhas e '1–10 de 10' (sem 'Nenhuma movimentação') | linhas=10 info='1–10 de 10' corpo='12/10/2026 Entrada Doação Receita via UI + R$ 99,90 Admin Te' | **PASS** |
| U20 | Auditoria (M6): carrega e exibe os rótulos 'Orçamentos', 'Financeiro' e 'Remoção de item' | 3 rótulos presentes na tabela | linhas=15 orcamentos=true financeiro=true remocao=true | **PASS** |
| U21 | Demais páginas (Usuários, Eventos, Projetos, Votações) abrem sem erro de JS | nenhum erro novo | erros novos=0 | **PASS** |
| U22 | Sessão inteira: nenhum erro de JS, console.error, rejeição não tratada nem alert() inesperado | 0 | 0 ocorrências | **PASS** |

### 9.3 Os testes pegam defeito de verdade? (mutation testing)

Para não confiar num "56/56 passou" na primeira rodada, sabotei o código em cópias descartáveis e rodei a mesma bateria:

| Sabotagem | Esperado | Obtido |
|---|---|---|
| A — remover o `FOR UPDATE` do orçamento | E5/E6 (concorrência) falham | **FAIL=2.** E5: 9×201 + 3×500, banco `90.00 / 9 itens / soma 90.00`. E6: remoções 3×200 + 3×500, inserções 5×201 + 1×500, banco `110.00 / 11 / 110.00`. A contagem de 500 varia de rodada para rodada (numa rodada anterior: 5×201 + 7×500). Achado: o dado **não** ficou inconsistente (rollback + checagem seguraram); o que quebra são as requisições (deadlock → 500). O lock é o que faz edições simultâneas terminarem com sucesso. Neste mutante limitei `innodb_lock_wait_timeout` a 5s só para a bateria não demorar |
| B — remover o escopo `orcamento_id` das queries de item | teste 18 (IDOR) falha | **FAIL=2.** 18: HTTP 200 ×4; item de B alterado/removido e item de A removido pela URL do outro orçamento; total de B 500.00→0.00 e total de A ficou **desatualizado** (2000.00 sem itens, porque a checagem de consistência só olha o orçamento da URL). E1 falha por efeito colateral |
| C — remover o recálculo do total (mantendo a checagem) | testes de total falham | **FAIL=14.** Adicionar item devolve **500 e nada é gravado** (`itens no banco = 0`): checagem de consistência + rollback fazem o sistema falhar fechado em vez de gravar total errado. As outras 13 falhas (10–14, 16, 18, 19, 21, E1, E3, E5, E6) são cascata disso |
| Sonda — Warning provocado no `index.php` de uma cópia | o log do PHP captura | capturou o Warning provocado (o E8 não é vazio) |

---

## 10. Testes de regressão — módulos 1 a 7 (API)

| # | Descrição | Esperado | Obtido | Resultado |
|---|---|---|---|---|
| R1 | AUTH: login admin | HTTP 200 + token | HTTP 200 | **PASS** |
| R2 | AUTH: GET /api/me | HTTP 200 | HTTP 200 | **PASS** |
| R3 | PUBLICAÇÕES: listar | HTTP 200 | HTTP 200 | **PASS** |
| R4 | PUBLICAÇÕES: criar | HTTP 2xx + id | HTTP 201 id=1 | **PASS** |
| R5 | PUBLICAÇÕES: reagir | HTTP 200 | HTTP 200 | **PASS** |
| R6 | EVENTOS: listar | HTTP 200 | HTTP 200 | **PASS** |
| R7 | EVENTOS: criar | HTTP 2xx + id | HTTP 201 id=1 | **PASS** |
| R8 | EVENTOS: participar (aluno) | success | HTTP 200 success=true | **PASS** |
| R9 | VOTAÇÕES: listar | HTTP 200 | HTTP 200 | **PASS** |
| R10 | VOTAÇÕES: criar (status aberta) | HTTP 2xx + id | HTTP 201 id=1 | **PASS** |
| R11 | VOTAÇÕES: votar (aluno) | success | HTTP 200 Voto registrado com sucesso. | **PASS** |
| R12 | PROJETOS: listar | HTTP 200 | HTTP 200 | **PASS** |
| R13 | NOTIFICAÇÕES: listar / não lidas / marcar todas como lidas (aluno) | 200/200/200 | 200/200/200 | **PASS** |
| R14 | AUDITORIA: listar como admin | HTTP 200 + success | HTTP 200 success=true | **PASS** |
| R15 | AUDITORIA: aluno bloqueado | HTTP 403 | HTTP 403 | **PASS** |
| R16 | FINANCEIRO: categorias + criar receita + criar despesa | 200 (>=9 categorias); 201; 201 | 200 (9 categorias); 201 id=1; 201 id=2 | **PASS** |
| R17 | FINANCEIRO: resumo (entradas − saídas = saldo) | coerente | entradas=150.50 saidas=42.90 saldo=107.60 | **PASS** |
| R18 | FINANCEIRO: editar + arquivar (soft delete) | 200; 200; deleted_at preenchido | 200; 200; deleted=1 | **PASS** |
| R19 | FINANCEIRO/ORÇAMENTOS: aluno bloqueado nos dois módulos | 403/403 | 403/403 | **PASS** |
| R20 | AUTH: logout invalida o token | logout success; depois /me = 401 | logout HTTP 200 success=true; /me depois=401 | **PASS** |

Regressão de UI (acima, 9.2): **U17–U19** Financeiro (menu, cards, criar pela UI, paginação), **U20** Auditoria (carrega e mostra os rótulos), **U21** Usuários/Eventos/Projetos/Votações abrem sem erro de JS, **U22** a sessão inteira sem nenhum erro de JS/console/`alert()`.

---

## 11. Problemas encontrados

1. **Bug real no Módulo 7 (UI do Financeiro) — achado pelo teste de UI (U19).** Arquivar o único item da última página deixava a tabela em "Nenhuma movimentação encontrada…" com paginação `11–10 de 10`. Eu tinha desconfiado lendo o código; o teste confirmou.
2. **Risco real no seed do Módulo 7 (acentos).** Reproduzido: com o cliente `mysql` num charset que não é UTF-8, o seed gravava `Doação` como `DoaÃ§Ã£o` (bytes `C383C2A7…`). No **Windows PowerShell 5.1** o comando que eu te passei no Módulo 7 (`Get-Content … | mysql`) tem o problema extra de que o `Get-Content` lê arquivo sem BOM como ANSI e o `$OutputEncoding` (ponte para programas externos) é ASCII por padrão ([Microsoft Learn — about_Character_Encoding](https://learn.microsoft.com/powershell/module/microsoft.powershell.core/about/about_character_encoding)) — então `Doa??o` pode ter ido para o seu banco. **Não consegui rodar PowerShell aqui:** reproduzi a metade do cliente com charset errado; a parte do PowerShell vem da documentação.
3. **Omissão minha no Módulo 7:** a tela de Auditoria mostrava `financeiro` cru (sem rótulo no mapa de exibição).
4. Achados que **não** eram bug do produto: o check estático apontou `editErrorMessage` (é criado dinamicamente em `dashboard.js`; já estava no baseline do M7); no harness de UI, o `Intl` usa espaço sem quebra entre `R$` e o número (meu comparador estava errado — o `obtido` e o `esperado` apareciam idênticos); o jsdom 30.1 mudou a API de carregamento de recursos.
5. O sandbox foi resetado entre as conversas (projeto, PHP e MariaDB sumiram); reconstruí tudo a partir do zip do Módulo 7.

## 12. Problemas corrigidos

| # | Correção | Verificação |
|---|---|---|
| 1 | `loadFinanceiro()`: se a página atual vier vazia e `pagina > 1`, volta para `total_paginas` (5 linhas; **única mudança de lógica no Módulo 7**) | U19 PASS (era FAIL) |
| 2 | Seed: `SET NAMES utf8mb4;` no topo + comando novo baseado em `source` (sem PowerShell no meio) no cabeçalho do seed e no relatório do M7. **O seu banco não foi (nem pode ser) conferido por mim** — ver seção 16 | S1 PASS: seed rodado por `source` **e** por pipe, com cliente em `latin1`, duas vezes: acentos corretos e 9 linhas (idempotente) |
| 3 | Rótulos `financeiro`, `orcamentos`, `criacao_item`, `alteracao_item`, `exclusao_item` nos mapas de exibição da Auditoria | U20 PASS |

---

## 13. Limitações restantes e decisões em aberto

Decisões que eu tomei e você pode querer reverter (cada uma é pequena):

1. **`valor_total` = soma bruta de `valor_previsto` de todos os itens**, misturando categorias de entrada e saída. O schema não define netting e o `CHECK >= 0` impede um total líquido negativo. A tela mostra o tipo de cada item para deixar isso visível. Se a ideia for "orçamento de despesas", o certo é restringir itens a categorias `saida` — é uma validação no controller.
2. **`status` é só um rótulo.** Orçamento `encerrado` continua aceitando edição de itens. A spec não define o que cada status bloqueia e eu não quis inventar regra. Travar itens em `encerrado` é uma condição no controller.
3. **`valor_realizado` é manual** (sem vínculo com movimentações — seção 8).
4. **Remover item é `DELETE` físico**: `itens_orcamento` não tem `deleted_at`. A ação fica na auditoria, mas o item não é recuperável.
5. **Não há `quantidade`**: a sua spec cita "quantidade" na validação, mas a tabela não tem a coluna, então não implementei (e não alterei o banco para isso).
6. **Arquivar orçamento não olha `prestacoes_contas`** (FK `orcamento_id`, `ON DELETE SET NULL`): como é soft delete, a FK não dispara e o módulo de prestação de contas ainda não existe. Quando existir, decidir se orçamento com prestação ativa pode ser arquivado.
7. Categoria arquivada depois continua aparecendo nos itens que já a usavam (não filtrei `deleted_at` no JOIN, de propósito), mas não pode ser escolhida para itens novos.
8. `apiBuscarOrcamento` (`GET /api/orcamentos/{id}`) está coberto por teste de API, mas a UI usa o `GET …/itens`, que já devolve o cabeçalho.
9. Limitações de ambiente: ver a tabela "NÃO EXECUTADO" na seção 1.

## 14. Alterações estruturais no banco

**Nenhuma.** Nenhum `CREATE`/`ALTER`/`DROP`/`TRUNCATE`. Nenhum SQL foi executado no seu banco (só na réplica descartável). O único arquivo de banco tocado foi o seed do Módulo 7 (cabeçalho + `SET NAMES`; os dados são os mesmos).

## 15. Seed

O Módulo 8 **não tem seed próprio** (não precisou). Pré-requisito: as categorias do Módulo 7 (`categoria_id` é obrigatório nos itens). Comando correto no Windows/WAMP, a partir de `C:\wamp64\www\Gremio-ETEC-Poa`, PowerShell ou CMD:

```powershell
mysql -u root -P 3307 --default-character-set=utf8mb4 gremio_etec_poa -e "source database/seed_categorias_financeiras.sql"
```

Conferir: `SELECT id, nome FROM categorias_financeiras;` — 9 linhas, sem `?` nem `Ã§`. Se já rodou antes e ficou errado: se ainda não há movimentações/itens usando essas categorias, `DELETE FROM categorias_financeiras;` e rode o comando acima; se já há, corrija com `UPDATE … WHERE id = X` (também via `source`, pelo mesmo motivo). Não existe um script de reparo automático porque ele teria que adivinhar o estado do seu banco.

## 16. Testes que ainda precisam ser realizados no seu WAMP

1. `php -v` (8.5) e `php -m | findstr /i "mbstring pdo_mysql"` — o código novo usa `mb_strlen`.
2. Conferir os acentos das categorias (seção 15) **antes** de abrir a tela de itens.
3. Abrir `dashboard.php` logado como administrador → **Orçamentos**: criar orçamento → (abre a tela de itens) adicionar 2 itens → editar um → remover um → conferir o total na tela **e** no phpMyAdmin (`valor_total` = soma dos `valor_previsto` do orçamento).
4. Olhar o visual, que eu não consegui verificar: largura do painel de itens (780px), textarea do modal, badges (Planejado = amarelo, Aprovado = verde, Encerrado = neutro), 3 cards, responsividade.
5. Rotas aninhadas via Apache (`/api/orcamentos/{id}/itens/{item_id}/atualizar` e `/excluir`) — o roteamento do `.htaccess` é genérico e as rotas aninhadas dos outros módulos já funcionam, mas eu só testei com `php -S`.
6. Com um usuário "aluno" real: `/api/orcamentos` deve dar 403 (ou conferir pela UI que o menu Orçamentos nem é acessível, já que o dashboard exige administrador).
7. Se o WAMP usa MySQL (não MariaDB): repetir o passo 3 — as queries usam `CAST(… AS DECIMAL)`, `GROUP BY` com colunas completas e `FOR UPDATE`, todos padrão, mas só testei MariaDB 10.11.

