# Estrutura do banco de dados

Banco: **`gremio_etec_poa`** (MySQL/MariaDB, `utf8mb4`, InnoDB). Total de tabelas no script oficial: **38**.

Script oficial de criação: [`database/gremio_etec_poa.sql`](../../database/gremio_etec_poa.sql) (somente estrutura, **sem dados**).
Dados iniciais obrigatórios: `database/seed_*.sql` (veja a ordem em [Instalação](../../README.md#instalação-no-windows-com-wampserver)).

A coluna **Em uso** indica se algum arquivo de `Backend/` executa comandos SQL nessa tabela. Tabelas marcadas como *sem uso* existem no banco mas ainda não têm funcionalidade implementada.

## Usuários, acesso e segurança

| Tabela | Colunas | Relacionamentos (FK) | Em uso |
|---|---|---|---|
| `usuarios` | 11 | - | sim |
| `perfis` | 6 | - | sim |
| `usuario_perfil` | 5 | `perfil_id` → `perfis.id`, `usuario_id` → `usuarios.id` | sim |
| `permissoes` | 6 | - | **sem uso** |
| `perfil_permissao` | 5 | `perfil_id` → `perfis.id`, `permissao_id` → `permissoes.id` | **sem uso** |
| `solicitacoes_cadastro` | 12 | `analisado_por` → `usuarios.id`, `usuario_id` → `usuarios.id` | sim |
| `sessoes` | 11 | `usuario_id` → `usuarios.id` | sim |
| `tokens_recuperacao` | 10 | `usuario_id` → `usuarios.id` | sim |

## Publicações

| Tabela | Colunas | Relacionamentos (FK) | Em uso |
|---|---|---|---|
| `publicacoes` | 11 | `categoria_id` → `categorias_publicacao.id`, `usuario_id` → `usuarios.id` | sim |
| `categorias_publicacao` | 6 | - | sim |
| `tipos_reacao` | 5 | - | sim |
| `reacoes` | 6 | `publicacao_id` → `publicacoes.id`, `tipo_reacao_id` → `tipos_reacao.id`, `usuario_id` → `usuarios.id` | sim |

## Eventos

| Tabela | Colunas | Relacionamentos (FK) | Em uso |
|---|---|---|---|
| `eventos` | 14 | `criado_por` → `usuarios.id` | sim |
| `participantes_evento` | 7 | `evento_id` → `eventos.id`, `usuario_id` → `usuarios.id` | sim |
| `presenca_evento` | 8 | `evento_id` → `eventos.id`, `marcado_por` → `usuarios.id`, `usuario_id` → `usuarios.id` | sim |
| `anexos_evento` | 12 | `evento_id` → `eventos.id`, `usuario_id` → `usuarios.id` | **sem uso** |

## Votações

| Tabela | Colunas | Relacionamentos (FK) | Em uso |
|---|---|---|---|
| `votacoes` | 11 | `criado_por` → `usuarios.id` | sim |
| `opcoes_votacao` | 5 | `votacao_id` → `votacoes.id` | sim |
| `votos` | 7 | `opcao_id` → `opcoes_votacao.id`, `usuario_id` → `usuarios.id`, `votacao_id` → `votacoes.id` | sim |
| `resultado_votacao` | 7 | `opcao_id` → `opcoes_votacao.id`, `votacao_id` → `votacoes.id` | sim |

## Projetos

| Tabela | Colunas | Relacionamentos (FK) | Em uso |
|---|---|---|---|
| `projetos` | 11 | `responsavel_id` → `usuarios.id` | sim |
| `membros_projeto` | 6 | `projeto_id` → `projetos.id`, `usuario_id` → `usuarios.id` | sim |
| `tarefas` | 12 | `projeto_id` → `projetos.id`, `responsavel_id` → `usuarios.id`, `status_tarefa_id` → `status_tarefa.id` | sim |
| `status_tarefa` | 5 | - | sim |
| `historico_projeto` | 7 | `projeto_id` → `projetos.id`, `usuario_id` → `usuarios.id` | sim |
| `anexos_projeto` | 12 | `projeto_id` → `projetos.id`, `usuario_id` → `usuarios.id` | **sem uso** |

## Notificações

| Tabela | Colunas | Relacionamentos (FK) | Em uso |
|---|---|---|---|
| `notificacoes` | 9 | `tipo_notificacao_id` → `tipos_notificacao.id`, `criado_por` → `usuarios.id` | sim |
| `tipos_notificacao` | 5 | - | sim |
| `usuario_notificacao` | 7 | `notificacao_id` → `notificacoes.id`, `usuario_id` → `usuarios.id` | sim |

## Auditoria e logs

| Tabela | Colunas | Relacionamentos (FK) | Em uso |
|---|---|---|---|
| `logs` | 8 | `usuario_id` → `usuarios.id` | sim |
| `logs_alteracoes` | 10 | `usuario_id` → `usuarios.id` | **sem uso** |
| `logs_login` | 7 | `usuario_id` → `usuarios.id` | sim |

## Financeiro e orçamentos

| Tabela | Colunas | Relacionamentos (FK) | Em uso |
|---|---|---|---|
| `categorias_financeiras` | 7 | - | sim |
| `movimentacoes_financeiras` | 11 | `categoria_id` → `categorias_financeiras.id`, `usuario_id` → `usuarios.id` | sim |
| `orcamentos` | 10 | `criado_por` → `usuarios.id` | sim |
| `itens_orcamento` | 8 | `categoria_id` → `categorias_financeiras.id`, `orcamento_id` → `orcamentos.id` | sim |
| `comprovantes` | 11 | `movimentacao_id` → `movimentacoes_financeiras.id` | **sem uso** |
| `prestacoes_contas` | 13 | `orcamento_id` → `orcamentos.id`, `responsavel_id` → `usuarios.id` | **sem uso** |

## Observações

- **Exclusão lógica:** várias tabelas têm `deleted_at`; registros "excluídos" continuam no banco e são ignorados pelas consultas.
- **Proteção do administrador:** `database/protecao_administrador.sql` cria gatilhos (triggers) que impedem excluir, suspender ou remover o perfil de um administrador. Instalação: `php Backend/tests/proteger-admin.php`.
- **Perfis iniciais** (`seed_perfis.sql`): `administrador` e `aluno`. As tabelas `permissoes` e `perfil_permissao` existem, mas o controle de acesso atual é feito por perfil (veja [Autenticação e RBAC](../backend/autenticacao-rbac.md)).
- **A complementar pela equipe:** diagrama ER visual (ex.: exportar do MySQL Workbench para `docs/banco-de-dados/`) e dicionário de dados coluna a coluna.
