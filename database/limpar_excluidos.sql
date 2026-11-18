-- ============================================================
-- Limpeza: apaga DE VERDADE os registros que foram "excluídos"
-- antes da mudança para DELETE real (os que têm deleted_at).
-- ============================================================
-- Rode UMA vez no phpMyAdmin (aba SQL) depois de subir o código novo.
-- Faça um backup do banco antes (Exportar > SQL).
-- Os filhos (itens, tarefas, reações, votos...) saem junto por
-- ON DELETE CASCADE.
-- ============================================================

-- Projetos e filhos
DELETE FROM tarefas   WHERE deleted_at IS NOT NULL;
DELETE FROM projetos  WHERE deleted_at IS NOT NULL;

-- Votações (opções, votos e resultados saem em cascata)
DELETE FROM votacoes  WHERE deleted_at IS NOT NULL;

-- Eventos (participantes, presenças e anexos saem em cascata)
DELETE FROM eventos   WHERE deleted_at IS NOT NULL;

-- Financeiro
DELETE FROM movimentacoes_financeiras WHERE deleted_at IS NOT NULL;
DELETE FROM orcamentos                WHERE deleted_at IS NOT NULL;

-- Publicações (reações saem em cascata)
DELETE FROM publicacoes WHERE deleted_at IS NOT NULL;

-- Usuários já excluídos: primeiro o que bloqueia (RESTRICT), depois o usuário.
DELETE FROM votos
WHERE usuario_id IN (SELECT id FROM usuarios WHERE deleted_at IS NOT NULL);

DELETE FROM publicacoes
WHERE usuario_id IN (SELECT id FROM usuarios WHERE deleted_at IS NOT NULL);

-- IGNORE: se algum usuário ainda for dono de evento/projeto/etc., ele é
-- pulado em vez de interromper o script.
DELETE IGNORE FROM usuarios WHERE deleted_at IS NOT NULL;

-- Conferência: tudo abaixo deve retornar 0 (ou só usuários pulados).
SELECT 'usuarios' AS tabela, COUNT(*) AS restantes FROM usuarios WHERE deleted_at IS NOT NULL
UNION ALL SELECT 'eventos', COUNT(*) FROM eventos WHERE deleted_at IS NOT NULL
UNION ALL SELECT 'projetos', COUNT(*) FROM projetos WHERE deleted_at IS NOT NULL
UNION ALL SELECT 'votacoes', COUNT(*) FROM votacoes WHERE deleted_at IS NOT NULL
UNION ALL SELECT 'publicacoes', COUNT(*) FROM publicacoes WHERE deleted_at IS NOT NULL
UNION ALL SELECT 'movimentacoes', COUNT(*) FROM movimentacoes_financeiras WHERE deleted_at IS NOT NULL
UNION ALL SELECT 'orcamentos', COUNT(*) FROM orcamentos WHERE deleted_at IS NOT NULL;
