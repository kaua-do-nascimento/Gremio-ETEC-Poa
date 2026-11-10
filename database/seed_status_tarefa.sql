-- ============================================================
-- Seed mínimo para a tabela de apoio `status_tarefa`
-- ============================================================
-- Este script NÃO altera a estrutura do banco (nenhum CREATE/ALTER TABLE).
-- A tabela `status_tarefa` não vem com nenhuma linha no dump original,
-- mas `tarefas.status_tarefa_id` é uma FK obrigatória — sem estas
-- linhas não é possível criar nenhuma tarefa. Os nomes usados aqui
-- seguem exatamente o exemplo já deixado no comentário da coluna
-- `status_tarefa.nome` no dump: "Ex.: pendente, em_andamento,
-- concluida, atrasada".
-- É seguro executar mais de uma vez (usa NOT EXISTS).
--
-- Execute uma única vez, com o banco gremio_etec_poa selecionado:
--   mysql -u root gremio_etec_poa < database/seed_status_tarefa.sql
-- ou cole o conteúdo no phpMyAdmin / MySQL Workbench.
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO status_tarefa (nome, descricao)
SELECT 'pendente', 'Tarefa ainda não iniciada'
WHERE NOT EXISTS (SELECT 1 FROM status_tarefa WHERE nome = 'pendente');

INSERT INTO status_tarefa (nome, descricao)
SELECT 'em_andamento', 'Tarefa em execução'
WHERE NOT EXISTS (SELECT 1 FROM status_tarefa WHERE nome = 'em_andamento');

INSERT INTO status_tarefa (nome, descricao)
SELECT 'concluida', 'Tarefa finalizada'
WHERE NOT EXISTS (SELECT 1 FROM status_tarefa WHERE nome = 'concluida');

INSERT INTO status_tarefa (nome, descricao)
SELECT 'atrasada', 'Tarefa que passou do prazo sem ser concluída'
WHERE NOT EXISTS (SELECT 1 FROM status_tarefa WHERE nome = 'atrasada');
