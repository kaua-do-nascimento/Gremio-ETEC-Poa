<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Model responsável pelo módulo Financeiro.
 *
 * AUDITORIA DA ESTRUTURA (feita antes de implementar este módulo):
 * O banco já possui 6 tabelas financeiras, todas vazias, nenhuma
 * criada por este módulo:
 *
 *   - `categorias_financeiras` ... categorias de entrada/saída
 *     (id, nome, tipo enum('entrada','saida'), descricao). Usada
 *     como FK obrigatória em `movimentacoes_financeiras`.
 *
 *   - `movimentacoes_financeiras` ... o "livro-caixa" do grêmio.
 *     Colunas: id, categoria_id (FK), usuario_id (FK, quem lançou),
 *     tipo enum('entrada','saida'), origem enum('doacao','patrocinio',
 *     'evento','outro') nullable, descricao, valor decimal(10,2)
 *     com CHECK >= 0, data_movimentacao, created_at, updated_at,
 *     deleted_at (soft delete). É a tabela que este módulo usa
 *     como fonte principal.
 *
 *   - `orcamentos` / `itens_orcamento` ... planejamento orçamentário
 *     anual (status planejado/aprovado/encerrado). Estrutura pronta,
 *     mas SEM NENHUMA tela ou fluxo de orçamento no front-end atual.
 *
 *   - `comprovantes` ... anexo de arquivo por movimentação (nome,
 *     caminho, mime_type, hash). Mesma situação já documentada em
 *     `anexos_evento` (módulo de Eventos): não existe nenhuma tela
 *     de upload de arquivo em nenhum módulo do sistema hoje, e criar
 *     um fluxo de upload novo do zero foge do escopo deste módulo.
 *
 *   - `prestacoes_contas` ... relatório de prestação de contas
 *     (status em_elaboracao/publicada), pensado para transparência
 *     pública, independente do lançamento diário de movimentações.
 *
 * Por instrução explícita de não inventar funcionalidade sem tela/
 * fluxo já previsto, este model implementa CATEGORIAS (leitura) e
 * MOVIMENTAÇÕES (CRUD completo + resumo). Orçamentos, comprovantes
 * e prestação de contas ficam documentados como próximo passo no
 * relatório final — a estrutura já suporta, sem qualquer alteração,
 * quando o front-end para eles for definido.
 *
 * Nenhuma tabela nova foi criada e nenhuma coluna foi alterada.
 * Foi criado apenas um seed de DADOS (não de estrutura) em
 * database/seed_categorias_financeiras.sql, pois a tabela
 * `categorias_financeiras` existia vazia e `categoria_id` é
 * NOT NULL em `movimentacoes_financeiras` (impossível lançar
 * qualquer movimentação sem pelo menos uma categoria existente).
 */
class Financeiro
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    // ==========================================================
    // CATEGORIAS (somente leitura — sem tela de cadastro prevista)
    // ==========================================================

    /**
     * Categorias padrão do grêmio. Garantidas automaticamente (idempotente),
     * para o Financeiro e os Orçamentos nunca ficarem sem categoria mesmo
     * que o seed SQL não tenha sido executado (ou tenha sido executado
     * com a codificação de caracteres errada).
     */
    private const CATEGORIAS_PADRAO = [
        ['Doação', 'entrada', 'Doações recebidas de alunos, pais ou terceiros'],
        ['Patrocínio', 'entrada', 'Apoio financeiro de empresas ou patrocinadores'],
        ['Receita de Evento', 'entrada', 'Venda de ingressos, produtos ou arrecadação em eventos do grêmio'],
        ['Outras Entradas', 'entrada', 'Entradas que não se enquadram nas demais categorias'],
        ['Material de Consumo', 'saida', 'Materiais de escritório, papelaria e afins'],
        ['Eventos', 'saida', 'Custos de organização de eventos do grêmio'],
        ['Manutenção', 'saida', 'Manutenção de equipamentos e espaços do grêmio'],
        ['Serviços', 'saida', 'Serviços contratados de terceiros'],
        ['Outras Saídas', 'saida', 'Saídas que não se enquadram nas demais categorias'],
    ];

    /**
     * Cria as categorias padrão que faltam e corrige nomes gravados com
     * acentos corrompidos (ex.: "DoaÃ§Ã£o" ou "Doa??o"). Nunca lança exceção.
     */
    public function garantirCategoriasPadrao(): void
    {
        try {
            $this->db->exec("SET NAMES utf8mb4");

            $total = (int) $this->db->query("SELECT COUNT(*) FROM categorias_financeiras")->fetchColumn();

            foreach (self::CATEGORIAS_PADRAO as [$nome, $tipo, $descricao]) {
                // Corrige nome corrompido (mesmo tipo, mesmo prefixo ASCII)
                if ($total > 0 && preg_match('/[^\x00-\x7F]/', $nome)) {
                    $prefixo = preg_split('/[^\x00-\x7F]/', $nome)[0];
                    if ($prefixo !== '') {
                        $stmtFix = $this->db->prepare("
                            UPDATE categorias_financeiras
                            SET nome = :nome, descricao = :descricao
                            WHERE tipo = :tipo
                              AND nome <> :nome2
                              AND nome COLLATE utf8mb4_bin LIKE :prefixo
                              AND (nome COLLATE utf8mb4_bin LIKE '%Ã%' OR nome COLLATE utf8mb4_bin LIKE '%?%' OR nome COLLATE utf8mb4_bin LIKE '%�%')
                              AND NOT EXISTS (
                                  SELECT 1 FROM (SELECT nome, tipo FROM categorias_financeiras) c
                                  WHERE c.nome = :nome3 AND c.tipo = :tipo2
                              )
                        ");
                        $stmtFix->execute([
                            ':nome' => $nome, ':descricao' => $descricao, ':tipo' => $tipo,
                            ':nome2' => $nome, ':prefixo' => $prefixo . '%',
                            ':nome3' => $nome, ':tipo2' => $tipo,
                        ]);
                    }
                }

                $stmt = $this->db->prepare("
                    INSERT INTO categorias_financeiras (nome, tipo, descricao)
                    SELECT :nome, :tipo, :descricao
                    WHERE NOT EXISTS (
                        SELECT 1 FROM categorias_financeiras WHERE nome = :nome2 AND tipo = :tipo2
                    )
                ");
                // Só insere as padrão quando a tabela está totalmente vazia
                // (não recria categorias que o admin removeu de propósito).
                if ($total === 0) {
                    $stmt->execute([
                        ':nome' => $nome, ':tipo' => $tipo, ':descricao' => $descricao,
                        ':nome2' => $nome, ':tipo2' => $tipo,
                    ]);
                }
            }
        } catch (Throwable $e) {
            error_log('[Financeiro] Falha ao garantir categorias padrão: ' . $e->getMessage());
        }
    }

    public function listarCategorias(): array
    {
        $this->garantirCategoriasPadrao();

        $stmt = $this->db->query("
            SELECT id, nome, tipo, descricao
            FROM categorias_financeiras
            WHERE deleted_at IS NULL
            ORDER BY tipo, nome
        ");
        return $stmt->fetchAll();
    }

    public function buscarCategoriaPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, nome, tipo, descricao
            FROM categorias_financeiras
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    // ==========================================================
    // MOVIMENTAÇÕES
    // ==========================================================

    /**
     * Monta a cláusula WHERE + parâmetros a partir dos filtros
     * aceitos: tipo, categoria_id, data_inicio (YYYY-MM-DD),
     * data_fim (YYYY-MM-DD). Todos opcionais. Mesmo princípio já
     * usado em Log::montarFiltros() (módulo de Auditoria).
     */
    private function montarFiltros(array $filtros): array
    {
        $condicoes = ['mf.deleted_at IS NULL'];
        $params = [];

        if (!empty($filtros['tipo'])) {
            $condicoes[] = 'mf.tipo = :tipo';
            $params[':tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['categoria_id'])) {
            $condicoes[] = 'mf.categoria_id = :categoria_id';
            $params[':categoria_id'] = (int) $filtros['categoria_id'];
        }

        if (!empty($filtros['data_inicio'])) {
            $condicoes[] = 'mf.data_movimentacao >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $condicoes[] = 'mf.data_movimentacao <= :data_fim';
            $params[':data_fim'] = $filtros['data_fim'];
        }

        $where = 'WHERE ' . implode(' AND ', $condicoes);

        return [$where, $params];
    }

    public function listar(array $filtros, int $pagina, int $limite): array
    {
        [$where, $params] = $this->montarFiltros($filtros);
        $offset = ($pagina - 1) * $limite;

        $sql = "
            SELECT
                mf.id, mf.categoria_id, mf.usuario_id, mf.tipo, mf.origem,
                mf.descricao, mf.valor, mf.data_movimentacao,
                mf.created_at, mf.updated_at,
                cf.nome AS categoria_nome, cf.tipo AS categoria_tipo,
                u.nome_completo AS usuario_nome
            FROM movimentacoes_financeiras mf
            INNER JOIN categorias_financeiras cf ON cf.id = mf.categoria_id
            INNER JOIN usuarios u ON u.id = mf.usuario_id
            {$where}
            ORDER BY mf.data_movimentacao DESC, mf.id DESC
            LIMIT :limite OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function contar(array $filtros): int
    {
        [$where, $params] = $this->montarFiltros($filtros);

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM movimentacoes_financeiras mf {$where}
        ");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Totais de entradas/saídas/saldo/quantidade. Toda a soma é
     * feita em SQL (DECIMAL nativo do MySQL) — o PHP nunca soma
     * valores monetários, evitando erro de arredondamento de float
     * (ver aviso na tarefa: "evite cálculos monetários com float").
     * Aceita os mesmos filtros de período/categoria/tipo da listagem.
     */
    public function resumo(array $filtros): array
    {
        [$where, $params] = $this->montarFiltros($filtros);

        $sql = "
            SELECT
                COALESCE(SUM(CASE WHEN mf.tipo = 'entrada' THEN mf.valor ELSE 0 END), 0) AS total_entradas,
                COALESCE(SUM(CASE WHEN mf.tipo = 'saida'   THEN mf.valor ELSE 0 END), 0) AS total_saidas,
                COALESCE(SUM(CASE WHEN mf.tipo = 'entrada' THEN mf.valor ELSE -mf.valor END), 0) AS saldo,
                COUNT(*) AS quantidade
            FROM movimentacoes_financeiras mf
            {$where}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch();
    }

    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM movimentacoes_financeiras WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                mf.id, mf.categoria_id, mf.usuario_id, mf.tipo, mf.origem,
                mf.descricao, mf.valor, mf.data_movimentacao,
                mf.created_at, mf.updated_at,
                cf.nome AS categoria_nome, cf.tipo AS categoria_tipo,
                u.nome_completo AS usuario_nome
            FROM movimentacoes_financeiras mf
            INNER JOIN categorias_financeiras cf ON cf.id = mf.categoria_id
            INNER JOIN usuarios u ON u.id = mf.usuario_id
            WHERE mf.id = :id AND mf.deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    public function criar(int $usuarioId, array $dados): int
    {
        $sql = "
            INSERT INTO movimentacoes_financeiras
                (categoria_id, usuario_id, tipo, origem, descricao, valor, data_movimentacao)
            VALUES
                (:categoria_id, :usuario_id, :tipo, :origem, :descricao, :valor, :data_movimentacao)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':categoria_id' => $dados['categoria_id'],
            ':usuario_id' => $usuarioId,
            ':tipo' => $dados['tipo'],
            ':origem' => $dados['origem'],
            ':descricao' => $dados['descricao'],
            ':valor' => $dados['valor'],
            ':data_movimentacao' => $dados['data_movimentacao'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sql = "
            UPDATE movimentacoes_financeiras
            SET categoria_id = :categoria_id,
                tipo = :tipo,
                origem = :origem,
                descricao = :descricao,
                valor = :valor,
                data_movimentacao = :data_movimentacao
            WHERE id = :id
            AND deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':categoria_id' => $dados['categoria_id'],
            ':tipo' => $dados['tipo'],
            ':origem' => $dados['origem'],
            ':descricao' => $dados['descricao'],
            ':valor' => $dados['valor'],
            ':data_movimentacao' => $dados['data_movimentacao'],
            ':id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Arquiva (soft delete) uma movimentação. Mesmo padrão de
     * Projeto::arquivar() — a linha nunca é apagada fisicamente,
     * só marcada com deleted_at.
     */
    public function arquivar(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE movimentacoes_financeiras SET deleted_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
