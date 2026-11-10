-- ============================================================
-- Seed mínimo para habilitar autorização por perfil (RBAC)
-- ============================================================
-- Este script NÃO altera a estrutura do banco (nenhum CREATE/ALTER TABLE).
-- Apenas insere os perfis básicos necessários para a apresentação do TCC.
-- É seguro executar mais de uma vez (usa NOT EXISTS).
--
-- Execute uma única vez, com o banco gremio_etec_poa selecionado:
--   mysql -u root gremio_etec_poa < database/seed_perfis.sql
-- ou cole o conteúdo no phpMyAdmin / MySQL Workbench.
-- ============================================================

INSERT INTO perfis (nome, descricao)
SELECT 'administrador', 'Administrador do sistema - aprova/rejeita cadastros'
WHERE NOT EXISTS (SELECT 1 FROM perfis WHERE nome = 'administrador');

INSERT INTO perfis (nome, descricao)
SELECT 'aluno', 'Aluno da instituição - perfil padrão atribuído após aprovação do cadastro'
WHERE NOT EXISTS (SELECT 1 FROM perfis WHERE nome = 'aluno');
