-- ============================================================
-- Seed mínimo para o Módulo 7 (Financeiro): categorias de
-- entrada/saída para o grêmio.
-- ============================================================
-- Este script NÃO altera a estrutura do banco (nenhum CREATE/ALTER
-- TABLE). A tabela `categorias_financeiras` já existe no schema
-- (database/gremio_etec_poa.sql) mas está vazia — e `categoria_id`
-- é NOT NULL em `movimentacoes_financeiras`, ou seja, é IMPOSSÍVEL
-- lançar qualquer receita/despesa sem pelo menos uma categoria
-- cadastrada. Sem tela de cadastro de categoria prevista no
-- Módulo 7 (não está na lista de funcionalidades), este seed cobre
-- o conjunto mínimo para o módulo funcionar de ponta a ponta.
--
-- É seguro executar mais de uma vez (usa NOT EXISTS, mesmo padrão
-- de database/seed_perfis.sql).
--
-- COMO EXECUTAR (Windows/WAMP): a partir da pasta do projeto
-- (C:\wamp64\www\Gremio-ETEC-Poa), no PowerShell OU no CMD:
--
--   mysql -u root -P 3307 --default-character-set=utf8mb4 gremio_etec_poa -e "source database/seed_categorias_financeiras.sql"
--
-- Por que assim, e NÃO "Get-Content arquivo.sql | mysql ...": este arquivo tem
-- acentos (UTF-8). O Windows PowerShell 5.1 lê arquivo sem BOM como ANSI e envia
-- o pipe para programas externos em ASCII ($OutputEncoding), então "Doação"
-- chegaria ao MySQL como "Doa??o". Com "source", quem lê o arquivo é o próprio
-- cliente mysql, sem o PowerShell no meio. O "SET NAMES" abaixo garante que o
-- servidor leia os bytes como UTF-8 mesmo se o cliente estiver em outro charset.
--
-- CONFERIR depois:  SELECT id, nome FROM categorias_financeiras;  (9 linhas, acentos corretos)
-- Se você já rodou o seed antes e os nomes ficaram com "?" ou "Ã§":
--   * se AINDA NÃO existem movimentações/itens de orçamento usando essas categorias:
--     DELETE FROM categorias_financeiras;  e rode o comando acima de novo;
--   * se já existem: corrija com UPDATE ... WHERE id = X (também via "source", pelo mesmo motivo).
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO categorias_financeiras (nome, tipo, descricao)
SELECT 'Doação', 'entrada', 'Doações recebidas de alunos, pais ou terceiros'
WHERE NOT EXISTS (SELECT 1 FROM categorias_financeiras WHERE nome = 'Doação' AND tipo = 'entrada');

INSERT INTO categorias_financeiras (nome, tipo, descricao)
SELECT 'Patrocínio', 'entrada', 'Apoio financeiro de empresas ou patrocinadores'
WHERE NOT EXISTS (SELECT 1 FROM categorias_financeiras WHERE nome = 'Patrocínio' AND tipo = 'entrada');

INSERT INTO categorias_financeiras (nome, tipo, descricao)
SELECT 'Receita de Evento', 'entrada', 'Venda de ingressos, produtos ou arrecadação em eventos do grêmio'
WHERE NOT EXISTS (SELECT 1 FROM categorias_financeiras WHERE nome = 'Receita de Evento' AND tipo = 'entrada');

INSERT INTO categorias_financeiras (nome, tipo, descricao)
SELECT 'Outras Entradas', 'entrada', 'Entradas que não se enquadram nas demais categorias'
WHERE NOT EXISTS (SELECT 1 FROM categorias_financeiras WHERE nome = 'Outras Entradas' AND tipo = 'entrada');

INSERT INTO categorias_financeiras (nome, tipo, descricao)
SELECT 'Material de Consumo', 'saida', 'Materiais de escritório, papelaria e afins'
WHERE NOT EXISTS (SELECT 1 FROM categorias_financeiras WHERE nome = 'Material de Consumo' AND tipo = 'saida');

INSERT INTO categorias_financeiras (nome, tipo, descricao)
SELECT 'Eventos', 'saida', 'Custos de organização de eventos do grêmio'
WHERE NOT EXISTS (SELECT 1 FROM categorias_financeiras WHERE nome = 'Eventos' AND tipo = 'saida');

INSERT INTO categorias_financeiras (nome, tipo, descricao)
SELECT 'Manutenção', 'saida', 'Manutenção de equipamentos e espaços do grêmio'
WHERE NOT EXISTS (SELECT 1 FROM categorias_financeiras WHERE nome = 'Manutenção' AND tipo = 'saida');

INSERT INTO categorias_financeiras (nome, tipo, descricao)
SELECT 'Serviços', 'saida', 'Serviços contratados de terceiros'
WHERE NOT EXISTS (SELECT 1 FROM categorias_financeiras WHERE nome = 'Serviços' AND tipo = 'saida');

INSERT INTO categorias_financeiras (nome, tipo, descricao)
SELECT 'Outras Saídas', 'saida', 'Saídas que não se enquadram nas demais categorias'
WHERE NOT EXISTS (SELECT 1 FROM categorias_financeiras WHERE nome = 'Outras Saídas' AND tipo = 'saida');
