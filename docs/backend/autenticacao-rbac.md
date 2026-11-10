# Autenticação, perfis (RBAC) e cadastro

## Autenticação por token (Bearer)

1. `POST /api/login` recebe `login` (RM **ou** e-mail) e `senha`.
2. O servidor só autentica usuários com `ativo = 1` e senha correta (`password_verify`, hash gerado por `password_hash`).
3. Gera um token aleatório (`random_bytes(32)`, 64 caracteres hexadecimais) e devolve **uma única vez** ao cliente.
4. No banco (`sessoes`) fica apenas o **hash SHA-256** do token, com IP, *user agent* e data de expiração. Duração da sessão: **2 horas** (`expira_em_segundos = 7200`).
5. O cliente guarda o token e envia `Authorization: Bearer <token>` nas demais chamadas. O `AuthMiddleware` calcula o hash, procura uma sessão ativa e não expirada e carrega o usuário e seus perfis.
6. `POST /api/logout` encerra a sessão. A sessão só é aceita para usuários ativos e não excluídos; por isso suspender ou excluir um usuário invalida o acesso dele imediatamente (a exclusão também encerra as sessões). Redefinir a senha encerra todas as sessões; trocar a senha encerra as outras e preserva a atual.

Tentativas de login (sucesso e falha) são registradas em `logs_login`, via `AuditoriaService`.

## Perfis e controle de acesso (RBAC)

Perfis iniciais (`database/seed_perfis.sql`):

| Perfil | Descrição |
|---|---|
| `administrador` | Aprova/rejeita cadastros e acessa o painel administrativo |
| `aluno` | Perfil padrão atribuído após a aprovação do cadastro |

O controle é feito **no servidor**, em `Backend/routes/api.php`:

- `autenticarRequisicao()` → exige token válido (401 se inválido/expirado).
- `exigirPerfil($usuario, ['administrador'])` → exige um dos perfis informados (403 caso contrário).

A coluna **Acesso** em [`endpoints.md`](endpoints.md) mostra a exigência de cada rota. As tabelas `permissoes` e `perfil_permissao` existem, mas **não são usadas**: hoje a decisão depende só do perfil.

## Proteção do administrador

- **Na API:** administradores não podem ser excluídos nem suspensos (nem por outro administrador); tentativas bloqueadas são auditadas.
- **No banco:** `database/protecao_administrador.sql` cria gatilhos que barram `DELETE`, exclusão lógica, desativação e remoção do perfil de um administrador — vale também para o phpMyAdmin. Instalação: `php Backend/tests/proteger-admin.php`. Para remover a proteção, use os `DROP TRIGGER` descritos no cabeçalho do arquivo SQL.

## Cadastro e aprovação

1. `POST /api/cadastro` valida os dados e cria o usuário **inativo** (`ativo = 0`) junto com uma solicitação em `solicitacoes_cadastro` (status `pendente`).
2. O administrador lista as pendências (`GET /api/admin/solicitacoes`) e **aprova** ou **rejeita**.
3. Ao aprovar: o usuário passa a `ativo = 1`, recebe o perfil `aluno` (se ainda não tiver perfil) e uma notificação é gerada. Ao rejeitar, permanece inativo.
4. Só então o login é permitido.

## Recuperação e troca de senha

- `POST /api/recuperar-senha` (e-mail + RM): resposta **sempre igual**, exista ou não a conta. Se existir, gera um token (hash SHA-256 em `tokens_recuperacao`), válido por **60 minutos**, uso único, máximo de **5 pedidos por hora** por usuário.
- `POST /api/recuperar-senha/validar` e `POST /api/redefinir-senha` concluem o processo e encerram todas as sessões do usuário.
- `POST /api/me/senha` (usuário logado) exige a senha atual e encerra as outras sessões.
- O servidor local **não envia e-mail**: com `APP_EXIBIR_LINK_RECUPERACAO=true` o link aparece na tela (modo demonstração). Veja os riscos em [visão geral](../arquitetura/visao-geral.md#riscos-conhecidos-e-pendências-de-segurança).
- Senhas e tokens **nunca** são gravados nos logs.
