# Grêmio ETEC Poá — Versão final (base: Módulo 8)

## Como atualizar a sua instalação (WAMP)

1. Ligue Apache e MySQL (o projeto espera o MySQL na porta **3307**; veja `Backend/config/database.php`).
2. Copie esta pasta por cima da pasta atual do projeto (substituindo os arquivos).
3. **Não é necessário alterar o banco** — nenhuma tabela nova. Se o banco ainda não existir, importe `database/gremio_etec_poa.sql` e depois os `database/seed_*.sql`.
4. Rode o diagnóstico, que diz exatamente o que está faltando (se algo estiver):
   ```
   php Backend/tests/diagnostico.php
   ```
5. Se não houver administrador:
   ```
   php Backend/tests/criar-admin.php 00001 admin@etecpoa.sp.gov.br "Administrador Geral" SuaSenha@123
   ```
6. Abra o site com **Ctrl+F5** uma vez. (Daqui para frente o navegador nunca mais usa JS/CSS antigo: os arquivos agora levam a data de modificação na URL.)

## O que foi corrigido / completado

### Robustez (prováveis causas de "a v8 não funciona" no seu computador)
- **Categorias financeiras automáticas.** Financeiro e Orçamentos dependem da tabela `categorias_financeiras`. Se ela estiver vazia (seed não rodado), as categorias padrão são criadas sozinhas. Se estiverem com acentos corrompidos (`DoaÃ§Ã£o`, `Manuten??o`), são reparadas sozinhas. Nada que o administrador criou é sobrescrito.
- **Cache do navegador.** `api.js`, `dashboard.js` etc. antigos misturados com o `dashboard.php` novo quebravam telas. Todos os includes agora têm `?v=<data do arquivo>`.
- **Erro de banco em JSON.** Quando o MySQL estava desligado/na porta errada a API devolvia texto puro e a tela mostrava "Resposta inválida do servidor". Agora devolve uma mensagem clara em JSON.
- **Sessão expirada.** Um 401 em página protegida agora leva ao login em vez de deixar a tela quebrada.
- **Diagnóstico** (`Backend/tests/diagnostico.php`): PHP, extensões, conexão, tabelas, seeds, administrador, pasta uploads.

### Funcionalidades que eram só "Em breve" ou mockup
- **Dashboard (painel admin):** visão geral real (saldo, usuários, eventos, projetos, votações, publicações, orçamentos e atividade recente) — `GET /api/admin/resumo`.
- **Configurações:** a tela era um mockup com dados fixos. Agora usa os dados do usuário logado:
  - Sua conta: editar nome e e-mail (`POST /api/me/perfil`);
  - Senha: trocar a senha exigindo a atual; encerra as outras sessões (`POST /api/me/senha`);
  - Acessibilidade: alto contraste, reduzir animações e tamanho do texto, aplicados em todas as páginas (salvos no navegador);
  - Segurança: encerrar outras sessões e sair.
  O menu "Configurações" do painel admin abre essa página.
- **Recuperação de senha completa** ("Esqueci minha senha" não tinha backend): `esqueci-minha-senha.php` → link → `redefinir-senha.php`.
  - O token é guardado apenas como hash (SHA-256), vale 60 min, só pode ser usado uma vez e há limite de 5 pedidos por hora por usuário.
  - A resposta é a mesma exista ou não a conta (não revela quem está cadastrado).
  - Ao redefinir, todas as sessões antigas do usuário são encerradas. Nada disso é registrado em log com senha/token.
  - **Importante:** o servidor local não envia e-mail. Em `Backend/config/app.php`, `exibir_link_recuperacao => true` mostra o link na própria tela (modo demonstração). Em produção, configure o envio de e-mail do PHP e mude para `false`.
- `index.php` apontava para `home.php` (o arquivo é `Home.php`) — corrigido.

### Removido por não ter nada por trás
Na tela de Configurações saíram os painéis **Privacidade, Notificações e Conteúdo** (eram apenas botões de enfeite, sem backend), o botão "Alterar foto" e a barra "salvas automaticamente".

## Testes executados (ambiente: PHP 8.3 + MariaDB 10.11)
- API: 68 verificações (todos os módulos, incluindo permissões 401/403 e logout) — 68/68.
- Conta/recuperação/resumo/categorias: 31 verificações — 31/31.
- Interface (jsdom, com o dashboard real): todas as páginas, CRUD de Financeiro e Orçamentos (com itens), Auditoria (detalhe e filtro), Dashboard, Configurações e fluxo completo de recuperação de senha — sem erros de JavaScript.

## Limites do que foi verificado
- Não foi testado em navegador real, nem em Apache/WAMP, nem em PHP 8.5; o ambiente de teste usou o servidor embutido do PHP.
- Não foi possível reproduzir o defeito original da v8 numa instalação limpa; as correções de robustez acima atacam as causas mais prováveis do lado do ambiente.
- Prestação de contas / comprovantes: as tabelas existem no banco, mas nunca tiveram tela nem API — não foram implementadas.
