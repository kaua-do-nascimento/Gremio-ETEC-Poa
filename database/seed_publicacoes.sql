-- ============================================================
-- Seed mínimo para o módulo de Publicações.
-- Apenas INSERT — nenhuma alteração de estrutura.
-- Necessário porque publicacoes.categoria_id é NOT NULL e a tabela
-- categorias_publicacao estava vazia (impossível criar qualquer
-- publicação sem pelo menos uma categoria existente).
-- Seguro executar mais de uma vez (usa WHERE NOT EXISTS).
-- ============================================================

INSERT INTO categorias_publicacao (nome, descricao)
SELECT 'Aviso', 'Avisos gerais da direção ou do grêmio'
WHERE NOT EXISTS (SELECT 1 FROM categorias_publicacao WHERE nome = 'Aviso');

INSERT INTO categorias_publicacao (nome, descricao)
SELECT 'Evento', 'Publicações relacionadas a eventos da escola'
WHERE NOT EXISTS (SELECT 1 FROM categorias_publicacao WHERE nome = 'Evento');

INSERT INTO categorias_publicacao (nome, descricao)
SELECT 'Projeto', 'Publicações sobre projetos dos alunos ou do grêmio'
WHERE NOT EXISTS (SELECT 1 FROM categorias_publicacao WHERE nome = 'Projeto');

INSERT INTO categorias_publicacao (nome, descricao)
SELECT 'Comunicado', 'Comunicados oficiais do Grêmio Estudantil'
WHERE NOT EXISTS (SELECT 1 FROM categorias_publicacao WHERE nome = 'Comunicado');

-- Tipo de reação único usado pelo botão de "curtir" (❤️) do feed.
INSERT INTO tipos_reacao (nome, emoji)
SELECT 'Curtida', '❤️'
WHERE NOT EXISTS (SELECT 1 FROM tipos_reacao WHERE nome = 'Curtida');
