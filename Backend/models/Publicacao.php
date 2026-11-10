<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Model responsável pelas tabelas `publicacoes`, `reacoes`,
 * `categorias_publicacao` e `tipos_reacao`.
 *
 * OBSERVAÇÃO IMPORTANTE (limitações reais do banco atual):
 * - Não existe tabela de comentários no banco de 38 tabelas, então
 *   este model não implementa comentários.
 * - Não existe tabela de "favoritos"/bookmarks, então não é
 *   implementado aqui.
 * - "Tempo de leitura" não é uma coluna do banco: é calculado a
 *   partir da contagem de palavras do conteúdo (~200 palavras/min),
 *   igual à fórmula que já existia no front-end mockado.
 * - O schema permite apenas 1 categoria por publicação (categoria_id
 *   é NOT NULL e único por linha), por isso não há múltiplas tags.
 */
class Publicacao
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Lista publicações com status 'publicado', mais recentes primeiro,
     * já incluindo autor, categoria, contagem de curtidas e se o
     * usuário atual curtiu.
     */
    public function listarPublicadas(int $usuarioId, int $limite = 50): array
    {
        $sql = "
            SELECT
                p.id,
                p.titulo,
                p.conteudo,
                p.imagem_url,
                p.publicado_em,
                p.status,
                p.created_at,

                u.id AS autor_id,
                u.nome_completo AS autor_nome,
                u.foto_url AS autor_foto,

                c.nome AS categoria_nome,

                (
                    SELECT COUNT(*) FROM reacoes r
                    WHERE r.publicacao_id = p.id
                ) AS total_curtidas,

                EXISTS(
                    SELECT 1 FROM reacoes r2
                    WHERE r2.publicacao_id = p.id
                    AND r2.usuario_id = :usuario_id
                ) AS curtido_por_mim

            FROM publicacoes p
            INNER JOIN usuarios u ON u.id = p.usuario_id
            INNER JOIN categorias_publicacao c ON c.id = p.categoria_id

            WHERE p.status = 'publicado'
            AND p.deleted_at IS NULL

            ORDER BY p.publicado_em DESC, p.id DESC
            LIMIT :limite
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Busca uma publicação específica pelo ID (com os mesmos dados
     * agregados de listarPublicadas). Retorna null se não existir,
     * estiver excluída, ou ainda não estiver publicada.
     */
    public function buscarPorId(int $id, int $usuarioId): ?array
    {
        $sql = "
            SELECT
                p.id,
                p.titulo,
                p.conteudo,
                p.imagem_url,
                p.publicado_em,
                p.status,
                p.created_at,

                u.id AS autor_id,
                u.nome_completo AS autor_nome,
                u.foto_url AS autor_foto,

                c.id AS categoria_id,
                c.nome AS categoria_nome,

                (
                    SELECT COUNT(*) FROM reacoes r
                    WHERE r.publicacao_id = p.id
                ) AS total_curtidas,

                EXISTS(
                    SELECT 1 FROM reacoes r2
                    WHERE r2.publicacao_id = p.id
                    AND r2.usuario_id = :usuario_id
                ) AS curtido_por_mim

            FROM publicacoes p
            INNER JOIN usuarios u ON u.id = p.usuario_id
            INNER JOIN categorias_publicacao c ON c.id = p.categoria_id

            WHERE p.id = :id
            AND p.deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':usuario_id' => $usuarioId
        ]);

        $publicacao = $stmt->fetch();

        return $publicacao ?: null;
    }

    /**
     * Cria uma nova publicação já com status 'publicado' (o composer
     * do front-end publica direto, sem fluxo de rascunho por enquanto).
     */
    public function criar(int $usuarioId, int $categoriaId, string $titulo, string $conteudo, ?string $imagemUrl = null): int
    {
        $sql = "
            INSERT INTO publicacoes
                (usuario_id, categoria_id, titulo, conteudo, imagem_url, publicado_em, status)
            VALUES
                (:usuario_id, :categoria_id, :titulo, :conteudo, :imagem_url, NOW(), 'publicado')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':categoria_id' => $categoriaId,
            ':titulo' => $titulo,
            ':conteudo' => $conteudo,
            ':imagem_url' => $imagemUrl
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza título/conteúdo/categoria de uma publicação existente.
     */
    public function atualizar(int $id, int $categoriaId, string $titulo, string $conteudo): bool
    {
        $sql = "
            UPDATE publicacoes
            SET categoria_id = :categoria_id,
                titulo = :titulo,
                conteudo = :conteudo
            WHERE id = :id
            AND deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':categoria_id' => $categoriaId,
            ':titulo' => $titulo,
            ':conteudo' => $conteudo,
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Exclui (soft delete) uma publicação.
     */
    public function excluir(int $id): bool
    {
        $sql = "
            UPDATE publicacoes
            SET deleted_at = NOW()
            WHERE id = :id
            AND deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Verifica se uma publicação existe (não excluída). Usado para
     * validar antes de permitir uma reação.
     */
    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM publicacoes WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Alterna a curtida do usuário numa publicação (curte se ainda não
     * curtiu, remove a curtida se já tinha curtido).
     *
     * @return bool true se passou a estar curtido, false se foi removido.
     */
    public function alternarCurtida(int $usuarioId, int $publicacaoId, int $tipoReacaoId): bool
    {
        $stmtBusca = $this->db->prepare("
            SELECT id FROM reacoes
            WHERE usuario_id = :usuario_id AND publicacao_id = :publicacao_id
        ");
        $stmtBusca->execute([
            ':usuario_id' => $usuarioId,
            ':publicacao_id' => $publicacaoId
        ]);
        $reacaoExistente = $stmtBusca->fetch();

        if ($reacaoExistente) {
            $this->db->prepare("DELETE FROM reacoes WHERE id = :id")
                ->execute([':id' => $reacaoExistente['id']]);
            return false;
        }

        $this->db->prepare("
            INSERT INTO reacoes (usuario_id, publicacao_id, tipo_reacao_id)
            VALUES (:usuario_id, :publicacao_id, :tipo_reacao_id)
        ")->execute([
            ':usuario_id' => $usuarioId,
            ':publicacao_id' => $publicacaoId,
            ':tipo_reacao_id' => $tipoReacaoId
        ]);

        return true;
    }

    /**
     * Retorna o ID do tipo de reação padrão ("Curtida"), usado pelo
     * botão de curtir do feed. Lança PDOException se não existir
     * (indicaria que o seed de categorias/reações não foi executado).
     */
    public function idTipoReacaoCurtida(): int
    {
        $stmt = $this->db->query("SELECT id FROM tipos_reacao WHERE nome = 'Curtida' LIMIT 1");
        $id = $stmt->fetchColumn();

        if (!$id) {
            throw new RuntimeException(
                'Tipo de reação "Curtida" não encontrado. Execute database/seed_publicacoes.sql.'
            );
        }

        return (int) $id;
    }

    /**
     * Lista as categorias de publicação disponíveis (para o seletor
     * do formulário de criar/editar post).
     */
    public function listarCategorias(): array
    {
        $stmt = $this->db->query("
            SELECT id, nome FROM categorias_publicacao
            WHERE deleted_at IS NULL
            ORDER BY nome
        ");

        return $stmt->fetchAll();
    }

    /**
     * Verifica se uma categoria existe (usado para validar entrada
     * do usuário antes de criar/editar uma publicação).
     */
    public function categoriaExiste(int $categoriaId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM categorias_publicacao
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $categoriaId]);

        return (bool) $stmt->fetchColumn();
    }
}
