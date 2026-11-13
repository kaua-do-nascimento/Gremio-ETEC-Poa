# Grêmio ETEC Poá

Plataforma web do **Grêmio Estudantil da ETEC de Poá**, desenvolvida como Trabalho de Conclusão de Curso (TCC) pela equipe **Arch&Sec WebSolutions**.

O sistema aproxima o grêmio dos alunos: publicações, eventos, votações e projetos ficam em um só lugar, com cadastro aprovado por um administrador, controle de acesso por perfil e registro de auditoria das ações. A equipe administrativa também gerencia o financeiro e os orçamentos do grêmio.

> **Status:** em desenvolvimento. Veja o [estado de cada módulo](#funcionalidades-e-estado-atual) antes de assumir que algo está pronto.

---

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8 puro (sem framework), arquitetura MVC simplificada, API REST própria em JSON |
| Banco de dados | MySQL / MariaDB via **PDO** (consultas preparadas) |
| Frontend | HTML5, CSS3 e JavaScript puro (**Fetch API**), páginas servidas em `.php` |
| Autenticação | Token Bearer com sessão no banco (token guardado como hash SHA-256) |
| Autorização | Controle de acesso por perfis (RBAC): `administrador` e `aluno` |
| Servidor local | WampServer (Apache 2.4 + `mod_rewrite`) no Windows |
| Versionamento | Git e GitHub |

Não há dependências a instalar: **não existe** `composer.json` nem `package.json`. O frontend carrega apenas Font Awesome e Google Fonts por CDN.

---

## Funcionalidades e estado atual

Legenda: ✅ implementado e utilizável · 🟡 parcial · ⛔ não implementado

| Módulo | Estado | O que existe hoje |
|---|---|---|
| Cadastro, login e aprovação | ✅ | Cadastro cria uma solicitação; o administrador aprova ou rejeita; login com token; logout |
| Recuperação e troca de senha | ✅ | "Esqueci minha senha" por link com validade e uso único; troca de senha em *Configurações* |
| Usuários (painel admin) | ✅ | Listar, editar, suspender/reativar e excluir (exclusão lógica); administradores são **protegidos** contra exclusão e suspensão |
| Publicações | 🟡 | API completa (criar, listar, buscar, editar, excluir, reagir). No feed (`index.php`) a interface permite **criar, listar e reagir**; editar e excluir ainda não têm tela. Os cartões laterais "tendências" e "estatísticas" são **dados fixos** |
| Eventos | 🟡 | CRUD no painel admin, "próximos eventos", participar/cancelar e presença. Anexos de evento: ⛔ (tabela existe, sem funcionalidade) |
| Votações | ✅ | Criar com opções, votar (feed do aluno), ver resultado, editar e excluir |
| Projetos e tarefas | 🟡 | CRUD de projetos, membros, tarefas e histórico. Anexos de projeto: ⛔ (tabela existe, sem funcionalidade) |
| Notificações | ✅ | Sino com lista, contador de não lidas e marcar como lida |
| Auditoria e logs | ✅ | Registro das ações administrativas, com filtros e detalhe (somente administrador) |
| Financeiro | ✅ | Entradas e saídas por categoria, resumo/saldo, edição e arquivamento |
| Orçamentos | ✅ | Orçamentos anuais com itens (previsto × realizado) |
| Dashboard administrativo | ✅ | Visão geral com indicadores de todos os módulos |
| Configurações da conta | ✅ | Editar nome/e-mail, trocar senha, acessibilidade (salva no navegador) e encerrar outras sessões |
| Comprovantes | ⛔ | Apenas a tabela `comprovantes` existe no banco; não há API nem tela |
| Prestação de contas | ⛔ | Apenas a tabela `prestacoes_contas` existe no banco; não há API nem tela |
| Permissões granulares | ⛔ | Tabelas `permissoes` e `perfil_permissao` existem, mas o acesso hoje é decidido só pelo perfil |

Detalhes técnicos: [endpoints da API](docs/backend/endpoints.md) · [banco de dados](docs/banco-de-dados/estrutura.md).

---

## Estrutura do projeto

```text
Gremio-ETEC-Poa/
├── index.php                 # Feed do aluno (publicações, eventos, votações, notificações)
├── Home.php                  # Página de apresentação
├── login.php                 # Login e cadastro
├── esqueci-minha-senha.php   # Pedido de recuperação de senha
├── redefinir-senha.php       # Definição da nova senha (link recebido)
├── configuracoes.php         # Configurações da conta do usuário logado
├── dashboard.php            # Painel administrativo (somente administrador)
├── termos.php                # Termos de Uso da plataforma
├── privacidade.php           # Política de Privacidade e tratamento de dados dos usuários
├── css/                      # Estilos de cada página
├── js/                       # Scripts: api.js (cliente da API), dashboard.js, script.js, ...
├── imgs/                     # Imagens e ícones
├── uploads/                  # Arquivos enviados (pasta mantida com .gitkeep; conteúdo não versionado)
├── Backend/
│   ├── public/               # Ponto de entrada da API (index.php + .htaccess)
│   ├── routes/api.php        # Todas as rotas da API
│   ├── controllers/          # Regras de cada módulo
│   ├── models/               # Acesso ao banco (PDO)
│   ├── middleware/           # Autenticação por token
│   ├── services/             # Auditoria e notificações
│   ├── utils/                # Utilitários (respostas JSON)
│   ├── config/               # Banco (database.php), variáveis de ambiente (env.php) e app.php
│   └── tests/                # Scripts de manutenção via terminal (criar admin, diagnóstico, ...)
├── database/                 # Script do banco, seeds e proteção do administrador
├── docs/                     # Documentação técnica e guias da equipe
├── .github/                  # Modelos de Issue e de Pull Request
├── .env.example              # Modelo de configuração local (copie para .env)
└── CONTRIBUTING.md           # Como colaborar
```

---

## Instalação no Windows com WampServer

### Requisitos

- [WampServer](https://www.wampserver.com/) com Apache e MySQL **ou** MariaDB. O projeto foi desenvolvido para PHP 8.x (a equipe utiliza o PHP 8.5 do Wamp).
- Extensões PHP `pdo_mysql`, `mbstring`, `json` e `openssl` (vêm ativas no Wamp padrão).
- Módulo `rewrite_module` do Apache ativo (ícone do Wamp → *Apache* → *Módulos Apache* → `rewrite_module`).
- [Git para Windows](https://git-scm.com/download/win).

### Passo a passo

**1. Clone o repositório dentro da pasta `www` do Wamp** (PowerShell):

```powershell
cd C:\wamp64\www
git clone https://github.com/<USUARIO-OU-ORGANIZACAO>/<NOME-DO-REPOSITORIO>.git Gremio-ETEC-Poa
cd Gremio-ETEC-Poa
```

**2. Ligue o Wamp** (ícone verde) e abra o phpMyAdmin (`http://localhost/phpmyadmin`).

**3. Importe o banco, nesta ordem** (aba *Importar*; o primeiro arquivo já cria o banco `gremio_etec_poa`):

1. `database/gremio_etec_poa.sql`
2. `database/seed_perfis.sql`
3. `database/seed_tipos_notificacao.sql`
4. `database/seed_status_tarefa.sql`
5. `database/seed_publicacoes.sql`
6. `database/seed_categorias_financeiras.sql` (opcional: as categorias são criadas automaticamente se faltarem)

> Os arquivos `limpar_usuarios.sql` e `protecao_administrador.sql` **não** fazem parte da importação inicial. Veja os detalhes em [`docs/banco-de-dados/estrutura.md`](docs/banco-de-dados/estrutura.md).

**4. Configure a conexão com o banco.** Copie o modelo e ajuste:

```powershell
Copy-Item .env.example .env
notepad .env
```

O ponto principal é `DB_PORT`: normalmente **3306** para o MySQL e **3307** para o MariaDB do Wamp (confira no phpMyAdmin ou no menu do Wamp). Sem arquivo `.env`, o sistema usa `localhost`, porta `3307`, usuário `root` e senha vazia. O `.env` **nunca** é enviado ao GitHub.

**5. Crie o administrador** (use o `php.exe` do Wamp; se o comando `php` não for reconhecido, use o caminho completo, ex.: `C:\wamp64\bin\php\php8.x.x\php.exe`):

```powershell
php Backend/tests/criar-admin.php 00001 admin@etecpoa.sp.gov.br "Administrador Geral" SuaSenhaForte@123
```

O RM tem 5 caracteres e a senha, no mínimo 8. **Escolha uma senha sua e não a compartilhe nem a registre em arquivos do projeto.**

**6. Instale a proteção do administrador** (impede excluí-lo ou suspendê-lo, inclusive direto no banco):

```powershell
php Backend/tests/proteger-admin.php
```

**7. Confira se está tudo certo:**

```powershell
php Backend/tests/diagnostico.php
```

O diagnóstico verifica PHP, extensões, conexão, tabelas, dados iniciais e administrador, e diz o que corrigir.

**8. Acesse o sistema:**

- Login: `http://localhost/Gremio-ETEC-Poa/login.php`
- Painel administrativo: `http://localhost/Gremio-ETEC-Poa/dashboard.php` (somente administrador)

Se o navegador mostrar telas antigas após atualizar o código, use `Ctrl + F5`.

### Problemas comuns

| Sintoma | O que verificar |
|---|---|
| "Não foi possível conectar ao banco" | MySQL/MariaDB ligado? `DB_PORT` correto no `.env`? Rode `php Backend/tests/diagnostico.php` |
| API retorna 404 / "Resposta inválida" | `rewrite_module` ativo no Apache e pasta com o nome `Gremio-ETEC-Poa` em `www` |
| Erro 500 em todas as páginas | O Apache não aceitou os arquivos `.htaccess` de proteção: apague os `.htaccess` das pastas `Backend/*` (exceto `Backend/public`), `database/` e o da raiz — são proteções extras, não afetam o funcionamento |
| Financeiro/Orçamentos sem categorias | Importe `seed_categorias_financeiras.sql` (ou abra o Financeiro uma vez: o sistema as recria) |

---

## Desenvolvimento em equipe

O repositório usa duas branches permanentes (`main` e `develop`) e branches curtas por tarefa (`feature/*`, `fix/*`). Resumo:

```powershell
git checkout develop
git pull origin develop
git checkout -b feature/nome-da-funcionalidade
# ... trabalhe, depois:
git add .
git commit -m "feat: descreve o que foi feito"
git push -u origin feature/nome-da-funcionalidade
# abra o Pull Request no GitHub: feature/nome-da-funcionalidade -> develop
```

Guia completo: **[CONTRIBUTING.md](CONTRIBUTING.md)**. Guias de GitHub (colaboradores, Issues, Projects, primeiro envio): [`docs/github/`](docs/github/).

---

## Documentação

| Documento | Conteúdo |
|---|---|
| [Visão geral da arquitetura](docs/arquitetura/visao-geral.md) | Como uma requisição percorre o sistema |
| [Autenticação e RBAC](docs/backend/autenticacao-rbac.md) | Token, sessões, perfis, cadastro e aprovação |
| [Endpoints da API](docs/backend/endpoints.md) | Todas as rotas, com acesso exigido |
| [Estrutura do banco](docs/banco-de-dados/estrutura.md) | Tabelas, relacionamentos e tabelas ainda sem uso |
| [Frontend](docs/frontend/estrutura.md) | Páginas e scripts |
| [Fluxogramas](docs/fluxogramas/fluxos.md) | Cadastro/aprovação, login e recuperação de senha |
| [Histórico](docs/historico/README.md) | Relatórios de entrega dos módulos |

---

## Equipe

**Arch&Sec WebSolutions** — TCC, ETEC de Poá.

| Integrante | Responsabilidade | GitHub |
|---|---|---|
| _a preencher_ | _a preencher_ | _@usuario_ |
| _a preencher_ | _a preencher_ | _@usuario_ |
| _a preencher_ | _a preencher_ | _@usuario_ |

> Preencha esta tabela com os dados reais da equipe.

---

## Segurança

- Nunca envie ao GitHub o arquivo `.env`, senhas, tokens, backups do banco ou dados pessoais de alunos.
- Para relatar uma vulnerabilidade, **não** abra uma Issue pública com detalhes: avise o responsável pelo projeto diretamente.
- Antes de publicar em um servidor real, leia a seção "Riscos conhecidos" em [Visão geral da arquitetura](docs/arquitetura/visao-geral.md#riscos-conhecidos-e-pendências-de-segurança).
