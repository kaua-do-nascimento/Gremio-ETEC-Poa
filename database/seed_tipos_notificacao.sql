-- ============================================================
-- Seed dos tipos de notificação usados pelo módulo de Notificações
-- ============================================================
-- Este script NÃO altera a estrutura do banco (nenhum CREATE/ALTER TABLE).
-- Apenas insere os tipos básicos de notificação (tabela `tipos_notificacao`).
-- É seguro executar mais de uma vez (usa NOT EXISTS).
--
-- Observação: o próprio NotificacaoModel também cria um tipo em tempo
-- de execução caso ele ainda não exista (buscarOuCriarTipoId), então
-- rodar este seed é opcional — serve para deixar os tipos e suas
-- descrições já prontos e documentados desde o início.
--
-- Execute uma única vez, com o banco gremio_etec_poa selecionado:
--   mysql -u root gremio_etec_poa < database/seed_tipos_notificacao.sql
-- ou cole o conteúdo no phpMyAdmin / MySQL Workbench.
-- ============================================================

INSERT INTO tipos_notificacao (nome, descricao)
SELECT 'sistema', 'Avisos do sistema (ex.: aprovação de cadastro)'
WHERE NOT EXISTS (SELECT 1 FROM tipos_notificacao WHERE nome = 'sistema');

INSERT INTO tipos_notificacao (nome, descricao)
SELECT 'publicacao', 'Novas publicações no mural'
WHERE NOT EXISTS (SELECT 1 FROM tipos_notificacao WHERE nome = 'publicacao');

INSERT INTO tipos_notificacao (nome, descricao)
SELECT 'evento', 'Eventos criados, alterados ou com novas inscrições'
WHERE NOT EXISTS (SELECT 1 FROM tipos_notificacao WHERE nome = 'evento');

INSERT INTO tipos_notificacao (nome, descricao)
SELECT 'votacao', 'Votações abertas ou encerradas'
WHERE NOT EXISTS (SELECT 1 FROM tipos_notificacao WHERE nome = 'votacao');

INSERT INTO tipos_notificacao (nome, descricao)
SELECT 'projeto', 'Projetos criados, alterados ou com novos membros'
WHERE NOT EXISTS (SELECT 1 FROM tipos_notificacao WHERE nome = 'projeto');
