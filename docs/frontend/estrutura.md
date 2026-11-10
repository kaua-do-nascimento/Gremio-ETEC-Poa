# Frontend

Páginas `.php` (HTML com pequenos trechos PHP para versionar CSS/JS), CSS por página e JavaScript puro. Toda comunicação com o servidor passa por `js/api.js`.

## Páginas

| Arquivo | Quem acessa | Função |
|---|---|---|
| `Home.php` | Público | Página de apresentação do Grêmio |
| `login.php` | Público | Login e cadastro (`js/login-cadastro.js`) |
| `esqueci-minha-senha.php` | Público | Pedido de recuperação de senha |
| `redefinir-senha.php` | Público (com link válido) | Definição da nova senha |
| `index.php` | Usuário logado | Feed: publicações, próximos eventos, votações e notificações (`js/script.js`, `js/notificacoes.js`) |
| `configuracoes.php` | Usuário logado | Conta, senha, acessibilidade e segurança (`js/configuracoes.js`) |
| `dashboard.php` | Administrador | Painel: dashboard, usuários, eventos, projetos, votações, auditoria, financeiro, orçamentos (`js/dashboard.js`) |

## Scripts (`js/`)

| Arquivo | Responsabilidade |
|---|---|
| `api.js` | Cliente da API: monta a URL base, anexa o token, trata sessão expirada, guarda a sessão no `localStorage`, aplica preferências de acessibilidade e expõe as funções `api*` usadas pelas páginas |
| `login-cadastro.js` | Formulários de login e cadastro |
| `script.js` | Feed do aluno (publicações, eventos, votação) |
| `notificacoes.js` | Sino de notificações |
| `dashboard.js` | Todo o painel administrativo (um arquivo grande; candidato a ser dividido) |
| `configuracoes.js` | Página de configurações da conta |

## Observações

- A URL da API é calculada a partir do endereço da página (`API_BASE_URL` em `js/api.js`), então não é preciso configurar caminhos.
- Os `<script>`/`<link>` recebem `?v=<data do arquivo>` para evitar que o navegador use versões antigas em cache.
- Preferências de acessibilidade ficam no `localStorage` (`gremio_preferencias`) e valem para todas as páginas que carregam `api.js`.
- Dados ainda fixos no feed (`js/script.js`): cartões "tendências" e "estatísticas de atividade".
- Fontes e ícones vêm de CDN (Google Fonts e Font Awesome): sem internet, a aparência muda.
