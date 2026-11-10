-- ============================================================
-- Script de limpeza: mantém SOMENTE o administrador no banco.
-- Útil para deixar o ambiente limpo antes da apresentação do TCC.
-- ============================================================
-- Troque o valor abaixo se o ID do seu administrador for outro.
-- No seu banco atual, o admin é o id = 5 (RM 00001).
-- ============================================================

SET @ID_ADMIN_A_MANTER = 5;

-- 1. Remove sessões (tokens) de todos os outros usuários
DELETE FROM sessoes
WHERE usuario_id <> @ID_ADMIN_A_MANTER;

-- 2. Remove solicitações de cadastro de todos os outros usuários
DELETE FROM solicitacoes_cadastro
WHERE usuario_id <> @ID_ADMIN_A_MANTER;

-- 3. Remove vínculos de perfil de todos os outros usuários
DELETE FROM usuario_perfil
WHERE usuario_id <> @ID_ADMIN_A_MANTER;

-- 4. Por fim, remove os próprios usuários (exceto o administrador)
DELETE FROM usuarios
WHERE id <> @ID_ADMIN_A_MANTER;

-- Confirma o resultado
SELECT id, rm, email, nome_completo, ativo FROM usuarios;