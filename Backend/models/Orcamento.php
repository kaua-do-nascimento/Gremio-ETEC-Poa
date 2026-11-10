<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Erro de regra de negócio do módulo Orçamentos (orçamento inexistente,
 * item que não pertence ao orçamento, soma acima do limite da coluna...).
 * O model só sinaliza; quem traduz o código em resposta HTTP é o controller.
 */
class OrcamentoException extends RuntimeException
{
    public const NAO_ENCONTRADO = 'nao_encontrado';
    public const ITEM_NAO_ENCONTRADO = 'item_nao_encontrado';
    public const TOTAL_EXCEDIDO = 'total_excedido';
    public const TOTAL_INCONSISTENTE = 'total_inconsistente';

    private string $codigoErro;

    public function __construct(string $codigoErro, string $mensagem = '')
    {
        parent::__construct($mensagem !== '' ? $mensagem : $codigoErro);
        $this->codigoErro = $codigoErro;
    }

    public function getCodigoErro(): string
    {
        return $this->codigoErro;
    }
}

/**
 * Model do módulo Orçamentos (Módulo 8).
 *
 * ESTRUTURA REAL USADA (nenhuma tabela/coluna criada ou alterada):
 *
 *   orcamentos ........ id, nome varchar(150), ano_referencia YEAR,
 *                       descricao text NULL, valor_total decimal(10,2)
 *                       DEFAULT 0 (CHECK >= 0), status enum('planejado',
 *                       'aprovado','encerrado'), criado_por (FK usuarios),
 *                       created_at, updated_at, deleted_at (soft delete).
 *
 *   itens_orcamento ... id, orcamento_id (FK, ON DELETE CASCADE),
 *                       categoria_id (FK categorias_financeiras),
 *                       descricao varchar(255), valor_previsto e
 *                       valor_realizado decimal(10,2), created_at,
 *                       updated_at. NÃO tem deleted_at (remover item é um
 *                       DELETE de verdade), NÃO tem coluna `quantidade` e
 *                       NÃO tem CHECK nos valores — a recusa de valor
 *                       negativo é toda feita no backend.
 *
 * REGRA CENTRAL: orcamentos.valor_total é SEMPRE calculado aqui, em SQL,
 * como SUM(itens_orcamento.valor_previsto). Nunca vem do cliente. Toda
 * operação que mexe em item (ou edita o orçamento) roda numa transação:
 *
 *   BEGIN → trava a linha do orçamento (SELECT ... FOR UPDATE, serializa
 *   edições concorrentes) → altera → recalcula o total → confere
 *   total == soma dos itens → COMMIT.   Qualquer erro: ROLLBACK.
 *
 * ANTI-IDOR: toda query de item filtra pelo id do item E pelo id do
 * orçamento da URL. Item de outro orçamento nunca é lido nem alterado.
 *
 * INTEGRAÇÃO COM O FINANCEIRO (Módulo 7): o único vínculo real no banco
 * é a FK itens_orcamento.categoria_id → categorias_financeiras (o
 * controller valida a categoria com Financeiro::buscarCategoriaPorId).
 * Não existe FK entre orçamentos/itens e movimentacoes_financeiras,
 * então valor_realizado é informado manualmente.
 */
class Orcamento
{
    private PDO $db;

    // Maior valor que cabe em decimal(10,2): limite da soma dos itens.
    private const VALOR_MAXIMO = '99999999.99';

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    // ==========================================================
    // ORÇAMENTOS — leitura
    // ==========================================================

    private function montarFiltros(array $filtros): array
    {
        $condicoes = ['o.deleted_at IS NULL'];
        $params = [];

        if (!empty($filtros['ano_referencia'])) {
            $condicoes[] = 'o.ano_referencia = :ano_referencia';
            $params[':ano_referencia'] = (int) $filtros['ano_referencia'];
        }

        if (!empty($filtros['status'])) {
            $condicoes[] = 'o.status = :status';
            $params[':status'] = $filtros['status'];
        }

        return ['WHERE ' . implode(' AND ', $condicoes), $params];
    }

    public function listar(array $filtros, int $pagina, int $limite): array
    {
        [$where, $params] = $this->montarFiltros($filtros);
        $offset = ($pagina - 1) * $limite;

        $stmt = $this->db->prepare("
            SELECT
                o.id, o.nome, o.ano_referencia, o.descricao, o.valor_total,
                o.status, o.criado_por, o.created_at, o.updated_at,
                u.nome_completo AS criado_por_nome,
                (SELECT COUNT(*) FROM itens_orcamento i WHERE i.orcamento_id = o.id) AS total_itens
            FROM orcamentos o
            INNER JOIN usuarios u ON u.id = o.criado_por
            {$where}
            ORDER BY o.ano_referencia DESC, o.id DESC
            LIMIT :limite OFFSET :offset
        ");
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Contagem + soma dos valor_total + quantidade de aprovados, para o
     * mesmo conjunto filtrado da listagem (usado também como total da
     * paginação). A soma é feita em SQL (DECIMAL), nunca em PHP.
     */
    public function resumo(array $filtros): array
    {
        [$where, $params] = $this->montarFiltros($filtros);

        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS quantidade,
                COALESCE(SUM(o.valor_total), 0) AS soma_valor_total,
                COALESCE(SUM(o.status = 'aprovado'), 0) AS aprovados
            FROM orcamentos o
            {$where}
        ");
        $stmt->execute($params);

        return $stmt->fetch();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                o.id, o.nome, o.ano_referencia, o.descricao, o.valor_total,
                o.status, o.criado_por, o.created_at, o.updated_at,
                u.nome_completo AS criado_por_nome
            FROM orcamentos o
            INNER JOIN usuarios u ON u.id = o.criado_por
            WHERE o.id = :id AND o.deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();

        return $linha ?: null;
    }

    /**
     * Quantidade de itens, soma dos previstos, soma dos realizados e se o
     * valor_total gravado bate com a soma dos previstos (comparação feita
     * pelo próprio MySQL, em DECIMAL).
     */
    public function somas(int $orcamentoId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(i.id) AS total_itens,
                COALESCE(SUM(i.valor_previsto), 0) AS soma_previsto,
                COALESCE(SUM(i.valor_realizado), 0) AS soma_realizado,
                (o.valor_total = COALESCE(SUM(i.valor_previsto), 0)) AS consistente
            FROM orcamentos o
            LEFT JOIN itens_orcamento i ON i.orcamento_id = o.id
            WHERE o.id = :id
            GROUP BY o.id, o.valor_total
        ");
        $stmt->execute([':id' => $orcamentoId]);
        $linha = $stmt->fetch();

        return $linha ?: null;
    }

    public function listarItens(int $orcamentoId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                i.id, i.orcamento_id, i.categoria_id, i.descricao,
                i.valor_previsto, i.valor_realizado, i.created_at, i.updated_at,
                cf.nome AS categoria_nome, cf.tipo AS categoria_tipo
            FROM itens_orcamento i
            INNER JOIN categorias_financeiras cf ON cf.id = i.categoria_id
            WHERE i.orcamento_id = :orcamento_id
            ORDER BY i.id ASC
        ");
        $stmt->execute([':orcamento_id' => $orcamentoId]);

        return $stmt->fetchAll();
    }

    // ==========================================================
    // ORÇAMENTOS — escrita
    // ==========================================================

    /**
     * valor_total NÃO é inserido: nasce com o DEFAULT 0.00 do banco (um
     * orçamento novo ainda não tem itens). Quem altera o total é só
     * recalcularTotal().
     */
    public function criar(int $usuarioId, array $dados): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO orcamentos (nome, ano_referencia, descricao, status, criado_por)
            VALUES (:nome, :ano_referencia, :descricao, :status, :criado_por)
        ");
        $stmt->execute([
            ':nome' => $dados['nome'],
            ':ano_referencia' => $dados['ano_referencia'],
            ':descricao' => $dados['descricao'],
            ':status' => $dados['status'],
            ':criado_por' => $usuarioId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza os dados do orçamento. valor_total nunca é aceito de fora;
     * no fim da transação ele é recalculado a partir dos itens (se algum
     * dia o banco ficar fora de sincronia, a próxima edição corrige).
     *
     * @throws OrcamentoException NAO_ENCONTRADO se não existir/arquivado
     */
    public function atualizar(int $id, array $dados): void
    {
        $this->emTransacao($id, function () use ($id, $dados) {
            $stmt = $this->db->prepare("
                UPDATE orcamentos
                SET nome = :nome,
                    ano_referencia = :ano_referencia,
                    descricao = :descricao,
                    status = :status
                WHERE id = :id AND deleted_at IS NULL
            ");
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':ano_referencia' => $dados['ano_referencia'],
                ':descricao' => $dados['descricao'],
                ':status' => $dados['status'],
                ':id' => $id,
            ]);

            return null;
        });
    }

    /**
     * Arquiva (soft delete). Os itens ficam intactos no banco, apenas
     * inacessíveis, pois toda rota de item exige orçamento não arquivado.
     */
    public function arquivar(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE orcamentos SET deleted_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    // ==========================================================
    // ITENS — escrita (sempre transacional + total recalculado)
    // ==========================================================

    public function criarItem(int $orcamentoId, array $dados): int
    {
        return $this->emTransacao($orcamentoId, function () use ($orcamentoId, $dados) {
            $stmt = $this->db->prepare("
                INSERT INTO itens_orcamento
                    (orcamento_id, categoria_id, descricao, valor_previsto, valor_realizado)
                VALUES
                    (:orcamento_id, :categoria_id, :descricao, :valor_previsto, :valor_realizado)
            ");
            $stmt->execute([
                ':orcamento_id' => $orcamentoId,
                ':categoria_id' => $dados['categoria_id'],
                ':descricao' => $dados['descricao'],
                ':valor_previsto' => $dados['valor_previsto'],
                ':valor_realizado' => $dados['valor_realizado'],
            ]);

            return (int) $this->db->lastInsertId();
        });
    }

    /**
     * ANTI-IDOR: o item só é tocado se pertencer ao orçamento da URL.
     *
     * @throws OrcamentoException ITEM_NAO_ENCONTRADO se for de outro orçamento
     */
    public function atualizarItem(int $orcamentoId, int $itemId, array $dados): void
    {
        $this->emTransacao($orcamentoId, function () use ($orcamentoId, $itemId, $dados) {
            $stmt = $this->db->prepare("
                SELECT id FROM itens_orcamento
                WHERE id = :item_id AND orcamento_id = :orcamento_id
                FOR UPDATE
            ");
            $stmt->execute([':item_id' => $itemId, ':orcamento_id' => $orcamentoId]);
            if (!$stmt->fetchColumn()) {
                throw new OrcamentoException(OrcamentoException::ITEM_NAO_ENCONTRADO);
            }

            $stmt = $this->db->prepare("
                UPDATE itens_orcamento
                SET categoria_id = :categoria_id,
                    descricao = :descricao,
                    valor_previsto = :valor_previsto,
                    valor_realizado = :valor_realizado
                WHERE id = :item_id AND orcamento_id = :orcamento_id
            ");
            $stmt->execute([
                ':categoria_id' => $dados['categoria_id'],
                ':descricao' => $dados['descricao'],
                ':valor_previsto' => $dados['valor_previsto'],
                ':valor_realizado' => $dados['valor_realizado'],
                ':item_id' => $itemId,
                ':orcamento_id' => $orcamentoId,
            ]);

            return null;
        });
    }

    /**
     * ANTI-IDOR: só remove o item se ele pertencer ao orçamento da URL.
     * A tabela não tem deleted_at, então é um DELETE físico (a ação fica
     * registrada na auditoria).
     *
     * @throws OrcamentoException ITEM_NAO_ENCONTRADO se for de outro orçamento
     */
    public function removerItem(int $orcamentoId, int $itemId): void
    {
        $this->emTransacao($orcamentoId, function () use ($orcamentoId, $itemId) {
            $stmt = $this->db->prepare("
                DELETE FROM itens_orcamento
                WHERE id = :item_id AND orcamento_id = :orcamento_id
            ");
            $stmt->execute([':item_id' => $itemId, ':orcamento_id' => $orcamentoId]);

            if ($stmt->rowCount() === 0) {
                throw new OrcamentoException(OrcamentoException::ITEM_NAO_ENCONTRADO);
            }

            return null;
        });
    }

    // ==========================================================
    // Transação + consistência do total
    // ==========================================================

    /**
     * BEGIN → trava o orçamento → executa a operação → recalcula o total →
     * valida a consistência → COMMIT. Qualquer falha desfaz tudo (ROLLBACK)
     * e a exceção sobe para o controller.
     *
     * O FOR UPDATE no orçamento faz duas edições simultâneas na mesma
     * planilha esperarem uma pela outra — sem isso, duas transações poderiam
     * calcular o total sem enxergar o item da outra e gravar valor errado.
     */
    private function emTransacao(int $orcamentoId, callable $operacao)
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT id FROM orcamentos
                WHERE id = :id AND deleted_at IS NULL
                FOR UPDATE
            ");
            $stmt->execute([':id' => $orcamentoId]);
            if (!$stmt->fetchColumn()) {
                throw new OrcamentoException(OrcamentoException::NAO_ENCONTRADO);
            }

            $resultado = $operacao();

            $this->recalcularTotal($orcamentoId);
            $this->garantirConsistencia($orcamentoId);

            $this->db->commit();

            return $resultado;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * valor_total = SUM(valor_previsto) dos itens, tudo em SQL. Se a soma
     * não couber em decimal(10,2), recusa ANTES de gravar (em vez de deixar
     * o MySQL estourar com erro genérico).
     */
    private function recalcularTotal(int $orcamentoId): void
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(valor_previsto), 0) AS soma,
                (COALESCE(SUM(valor_previsto), 0) > CAST(:maximo AS DECIMAL(10,2))) AS estourou
            FROM itens_orcamento
            WHERE orcamento_id = :orcamento_id
        ");
        $stmt->execute([
            ':maximo' => self::VALOR_MAXIMO,
            ':orcamento_id' => $orcamentoId,
        ]);
        $linha = $stmt->fetch();

        if ((int) $linha['estourou'] === 1) {
            throw new OrcamentoException(
                OrcamentoException::TOTAL_EXCEDIDO,
                'A soma dos valores previstos ultrapassa o limite de R$ 99.999.999,99 por orçamento.'
            );
        }

        $stmt = $this->db->prepare("UPDATE orcamentos SET valor_total = :soma WHERE id = :id");
        $stmt->execute([':soma' => $linha['soma'], ':id' => $orcamentoId]);
    }

    private function garantirConsistencia(int $orcamentoId): void
    {
        $somas = $this->somas($orcamentoId);

        if ($somas === null || (int) $somas['consistente'] !== 1) {
            throw new OrcamentoException(OrcamentoException::TOTAL_INCONSISTENTE);
        }
    }
}
