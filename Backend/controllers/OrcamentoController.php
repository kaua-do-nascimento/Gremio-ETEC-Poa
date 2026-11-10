<?php

require_once __DIR__ . '/../models/Orcamento.php';
require_once __DIR__ . '/../models/Financeiro.php';
require_once __DIR__ . '/../services/AuditoriaService.php';

class OrcamentoController
{
    private Orcamento $orcamentoModel;
    private Financeiro $financeiroModel;

    private const STATUS_VALIDOS = ['planejado', 'aprovado', 'encerrado'];
    private const STATUS_PADRAO = 'planejado';

    // Limites vindos do próprio banco.
    private const ANO_MINIMO = 1901;              // tipo YEAR do MySQL/MariaDB
    private const ANO_MAXIMO = 2155;
    private const NOME_MAXIMO = 150;              // orcamentos.nome varchar(150)
    private const DESCRICAO_MAXIMO_BYTES = 65535; // orcamentos.descricao text
    private const ITEM_DESCRICAO_MAXIMO = 255;    // itens_orcamento.descricao varchar(255)

    private const LIMITE_PADRAO = 20;
    private const LIMITE_MAXIMO = 100;

    public function __construct()
    {
        $this->orcamentoModel = new Orcamento();
        // Módulo 7: a categoria de cada item é validada pelo mesmo model
        // que o Financeiro usa (mesma regra de "categoria existe e não
        // está arquivada").
        $this->financeiroModel = new Financeiro();
    }

    // ==========================================================
    // Formatação de saída
    // ==========================================================

    /**
     * Valores monetários permanecem string DECIMAL (nunca float em PHP).
     */
    private function formatarOrcamento(array $linha): array
    {
        $saida = [
            'id' => (int) $linha['id'],
            'nome' => $linha['nome'],
            'ano_referencia' => (int) $linha['ano_referencia'],
            'descricao' => $linha['descricao'],
            'valor_total' => $linha['valor_total'],
            'status' => $linha['status'],
            'criado_por' => (int) $linha['criado_por'],
            'criado_por_nome' => $linha['criado_por_nome'],
            'created_at' => $linha['created_at'],
            'updated_at' => $linha['updated_at'],
        ];

        if (isset($linha['total_itens'])) {
            $saida['total_itens'] = (int) $linha['total_itens'];
        }

        return $saida;
    }

    private function formatarSomas(array $somas): array
    {
        return [
            'total_itens' => (int) $somas['total_itens'],
            'soma_previsto' => $somas['soma_previsto'],
            'soma_realizado' => $somas['soma_realizado'],
            'consistente' => (int) $somas['consistente'] === 1,
        ];
    }

    private function formatarItem(array $linha): array
    {
        return [
            'id' => (int) $linha['id'],
            'orcamento_id' => (int) $linha['orcamento_id'],
            'categoria_id' => (int) $linha['categoria_id'],
            'categoria_nome' => $linha['categoria_nome'],
            'categoria_tipo' => $linha['categoria_tipo'],
            'descricao' => $linha['descricao'],
            'valor_previsto' => $linha['valor_previsto'],
            'valor_realizado' => $linha['valor_realizado'],
            'created_at' => $linha['created_at'],
            'updated_at' => $linha['updated_at'],
        ];
    }

    /**
     * Cabeçalho do orçamento + somas calculadas pelo banco. É o que as
     * respostas de item devolvem, para a tela atualizar os totais sem
     * precisar de outra chamada.
     */
    private function montarOrcamentoDetalhado(int $id): ?array
    {
        $linha = $this->orcamentoModel->buscarPorId($id);
        if ($linha === null) {
            return null;
        }

        $somas = $this->orcamentoModel->somas($id);
        if ($somas === null) {
            return null;
        }

        return array_merge($this->formatarOrcamento($linha), $this->formatarSomas($somas));
    }

    // ==========================================================
    // Validação
    // ==========================================================

    /**
     * Converte o valor recebido em string decimal canônica ("1234.50")
     * SEM aritmética de float. Aceita int, float (vindo do JSON) ou
     * string numérica com até 2 casas e no máximo 8 dígitos inteiros
     * (limite físico de decimal(10,2)). Retorna null se for inválido —
     * inclusive negativo, notação científica, vírgula e 3+ casas
     * decimais (não arredonda dinheiro em silêncio).
     */
    private function normalizarValor($valor): ?string
    {
        if (is_int($valor)) {
            $texto = (string) $valor;
        } elseif (is_float($valor)) {
            $texto = rtrim(rtrim(number_format($valor, 10, '.', ''), '0'), '.');
        } elseif (is_string($valor)) {
            $texto = trim($valor);
        } else {
            return null;
        }

        if (!preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/', $texto, $partes)) {
            return null;
        }

        $inteiro = ltrim($partes[1], '0');
        if ($inteiro === '') {
            $inteiro = '0';
        }
        $decimais = str_pad($partes[2] ?? '', 2, '0');

        return $inteiro . '.' . $decimais;
    }

    private function textoLimpo($valor): ?string
    {
        if ($valor === null) {
            return '';
        }
        if (!is_scalar($valor)) {
            return null;
        }
        return trim((string) $valor);
    }

    private function anoValido($ano): ?int
    {
        if (is_string($ano)) {
            $ano = trim($ano);
        }
        $inteiro = filter_var($ano, FILTER_VALIDATE_INT);

        if ($inteiro === false || $inteiro < self::ANO_MINIMO || $inteiro > self::ANO_MAXIMO) {
            return null;
        }

        return $inteiro;
    }

    /**
     * @return array [?string $erro, ?array $dadosNormalizados]
     */
    private function prepararDadosOrcamento(array $dados): array
    {
        $nome = $this->textoLimpo($dados['nome'] ?? null);
        if ($nome === null || $nome === '' || mb_strlen($nome) > self::NOME_MAXIMO) {
            return ['Nome é obrigatório e deve ter no máximo ' . self::NOME_MAXIMO . ' caracteres.', null];
        }

        $ano = $this->anoValido($dados['ano_referencia'] ?? null);
        if ($ano === null) {
            return ['Ano de referência é obrigatório e deve estar entre ' . self::ANO_MINIMO . ' e ' . self::ANO_MAXIMO . '.', null];
        }

        $statusBruto = $dados['status'] ?? null;
        $status = ($statusBruto === null || $statusBruto === '') ? self::STATUS_PADRAO : $statusBruto;
        if (!is_string($status) || !in_array($status, self::STATUS_VALIDOS, true)) {
            return ['Status inválido. Use: ' . implode(', ', self::STATUS_VALIDOS) . '.', null];
        }

        $descricao = $this->textoLimpo($dados['descricao'] ?? null);
        if ($descricao === null || strlen($descricao) > self::DESCRICAO_MAXIMO_BYTES) {
            return ['Descrição inválida ou grande demais.', null];
        }

        // valor_total NÃO é lido de $dados em lugar nenhum: é calculado
        // pelo banco a partir dos itens (qualquer valor enviado é ignorado).
        return [null, [
            'nome' => $nome,
            'ano_referencia' => $ano,
            'descricao' => $descricao === '' ? null : $descricao,
            'status' => $status,
        ]];
    }

    /**
     * @return array [?string $erro, ?array $dadosNormalizados]
     */
    private function prepararDadosItem(array $dados): array
    {
        $categoriaBruta = $dados['categoria_id'] ?? null;
        if (is_string($categoriaBruta)) {
            $categoriaBruta = trim($categoriaBruta);
        }
        $categoriaId = filter_var($categoriaBruta, FILTER_VALIDATE_INT);
        if ($categoriaId === false || $categoriaId < 1) {
            return ['Categoria é obrigatória.', null];
        }
        if ($this->financeiroModel->buscarCategoriaPorId($categoriaId) === null) {
            return ['A categoria informada não existe.', null];
        }

        $descricao = $this->textoLimpo($dados['descricao'] ?? null);
        if ($descricao === null || $descricao === '' || mb_strlen($descricao) > self::ITEM_DESCRICAO_MAXIMO) {
            return ['Descrição é obrigatória e deve ter no máximo ' . self::ITEM_DESCRICAO_MAXIMO . ' caracteres.', null];
        }

        $previsto = $this->normalizarValor($dados['valor_previsto'] ?? null);
        if ($previsto === null || $previsto === '0.00') {
            return ['Valor previsto é obrigatório e deve ser maior que zero (até 2 casas decimais, máximo 99999999.99).', null];
        }

        $realizadoBruto = $dados['valor_realizado'] ?? null;
        if ($realizadoBruto === null || $realizadoBruto === '') {
            $realizado = '0.00';
        } else {
            $realizado = $this->normalizarValor($realizadoBruto);
            if ($realizado === null) {
                return ['Valor realizado deve ser um número maior ou igual a zero (até 2 casas decimais, máximo 99999999.99).', null];
            }
        }

        return [null, [
            'categoria_id' => $categoriaId,
            'descricao' => $descricao,
            'valor_previsto' => $previsto,
            'valor_realizado' => $realizado,
        ]];
    }

    // ==========================================================
    // Endpoints — orçamentos
    // ==========================================================

    /**
     * Lista com filtros (ano_referencia, status) e paginação server-side.
     */
    public function listar(array $filtros, int $pagina, int $limite): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $filtrosLimpos = [];

        if (isset($filtros['status']) && $filtros['status'] !== '') {
            if (!is_string($filtros['status']) || !in_array($filtros['status'], self::STATUS_VALIDOS, true)) {
                $this->responder(400, ['success' => false, 'message' => 'Filtro de status inválido.']);
                return;
            }
            $filtrosLimpos['status'] = $filtros['status'];
        }

        if (isset($filtros['ano_referencia']) && $filtros['ano_referencia'] !== '') {
            $ano = $this->anoValido($filtros['ano_referencia']);
            if ($ano === null) {
                $this->responder(400, ['success' => false, 'message' => 'Filtro de ano inválido.']);
                return;
            }
            $filtrosLimpos['ano_referencia'] = $ano;
        }

        $limite = max(1, min(self::LIMITE_MAXIMO, $limite ?: self::LIMITE_PADRAO));
        $pagina = max(1, $pagina);

        try {
            $linhas = $this->orcamentoModel->listar($filtrosLimpos, $pagina, $limite);
            $resumo = $this->orcamentoModel->resumo($filtrosLimpos);
            $total = (int) $resumo['quantidade'];

            $this->responder(200, [
                'success' => true,
                'orcamentos' => array_map([$this, 'formatarOrcamento'], $linhas),
                'pagina' => $pagina,
                'limite' => $limite,
                'total' => $total,
                'total_paginas' => (int) ceil($total / $limite),
                'resumo' => [
                    'quantidade' => $total,
                    'soma_valor_total' => $resumo['soma_valor_total'],
                    'aprovados' => (int) $resumo['aprovados'],
                ],
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar orçamentos.']);
        }
    }

    public function buscarPorId(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $orcamento = $this->montarOrcamentoDetalhado($id);
            if ($orcamento === null) {
                $this->responder(404, ['success' => false, 'message' => 'Orçamento não encontrado.']);
                return;
            }

            $this->responder(200, ['success' => true, 'orcamento' => $orcamento]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao buscar orçamento.']);
        }
    }

    public function criar(int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        [$erro, $normalizados] = $this->prepararDadosOrcamento($dados);
        if ($erro !== null) {
            $this->responder(400, ['success' => false, 'message' => $erro]);
            return;
        }

        try {
            $id = $this->orcamentoModel->criar($usuarioId, $normalizados);

            // Auditoria só com ids/ano: sem nome, descrição ou valores.
            AuditoriaService::log(
                $usuarioId,
                'criacao',
                'orcamentos',
                "Orçamento #{$id} (ano {$normalizados['ano_referencia']}) criado."
            );

            $this->responder(201, [
                'success' => true,
                'message' => 'Orçamento criado com sucesso.',
                'id' => $id,
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao criar orçamento.']);
        }
    }

    public function atualizar(int $id, int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $atual = $this->orcamentoModel->buscarPorId($id);
            if ($atual === null) {
                $this->responder(404, ['success' => false, 'message' => 'Orçamento não encontrado.']);
                return;
            }

            // Campos opcionais omitidos mantêm o valor atual (assim uma
            // edição que só muda o nome não reabre um orçamento aprovado
            // nem apaga a descrição sem querer).
            if (!isset($dados['status']) || $dados['status'] === '') {
                $dados['status'] = $atual['status'];
            }
            if (!array_key_exists('descricao', $dados)) {
                $dados['descricao'] = $atual['descricao'];
            }

            [$erro, $normalizados] = $this->prepararDadosOrcamento($dados);
            if ($erro !== null) {
                $this->responder(400, ['success' => false, 'message' => $erro]);
                return;
            }

            $this->orcamentoModel->atualizar($id, $normalizados);

            AuditoriaService::log(
                $usuarioId,
                'atualizacao',
                'orcamentos',
                "Orçamento #{$id} atualizado."
            );

            $this->responder(200, ['success' => true, 'message' => 'Orçamento atualizado com sucesso.']);
        } catch (OrcamentoException $e) {
            $this->responderErroOrcamento($e);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao atualizar orçamento.']);
        }
    }

    /**
     * Arquiva (soft delete) o orçamento.
     */
    public function excluir(int $id, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!$this->orcamentoModel->arquivar($id)) {
                $this->responder(404, ['success' => false, 'message' => 'Orçamento não encontrado.']);
                return;
            }

            AuditoriaService::log(
                $usuarioId,
                'arquivamento',
                'orcamentos',
                "Orçamento #{$id} arquivado."
            );

            $this->responder(200, ['success' => true, 'message' => 'Orçamento arquivado com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao arquivar orçamento.']);
        }
    }

    // ==========================================================
    // Endpoints — itens
    // ==========================================================

    public function listarItens(int $orcamentoId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $orcamento = $this->montarOrcamentoDetalhado($orcamentoId);
            if ($orcamento === null) {
                $this->responder(404, ['success' => false, 'message' => 'Orçamento não encontrado.']);
                return;
            }

            $itens = $this->orcamentoModel->listarItens($orcamentoId);

            $this->responder(200, [
                'success' => true,
                'orcamento' => $orcamento,
                'itens' => array_map([$this, 'formatarItem'], $itens),
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar itens do orçamento.']);
        }
    }

    public function criarItem(int $orcamentoId, int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($this->orcamentoModel->buscarPorId($orcamentoId) === null) {
                $this->responder(404, ['success' => false, 'message' => 'Orçamento não encontrado.']);
                return;
            }

            [$erro, $normalizados] = $this->prepararDadosItem($dados);
            if ($erro !== null) {
                $this->responder(400, ['success' => false, 'message' => $erro]);
                return;
            }

            $itemId = $this->orcamentoModel->criarItem($orcamentoId, $normalizados);

            AuditoriaService::log(
                $usuarioId,
                'criacao_item',
                'orcamentos',
                "Item #{$itemId} adicionado ao orçamento #{$orcamentoId}."
            );

            $this->responder(201, [
                'success' => true,
                'message' => 'Item adicionado com sucesso.',
                'id' => $itemId,
                'orcamento' => $this->montarOrcamentoDetalhado($orcamentoId),
            ]);
        } catch (OrcamentoException $e) {
            $this->responderErroOrcamento($e);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao adicionar item.']);
        }
    }

    public function atualizarItem(int $orcamentoId, int $itemId, int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($this->orcamentoModel->buscarPorId($orcamentoId) === null) {
                $this->responder(404, ['success' => false, 'message' => 'Orçamento não encontrado.']);
                return;
            }

            [$erro, $normalizados] = $this->prepararDadosItem($dados);
            if ($erro !== null) {
                $this->responder(400, ['success' => false, 'message' => $erro]);
                return;
            }

            // O model só altera o item se ele pertencer a ESTE orçamento.
            $this->orcamentoModel->atualizarItem($orcamentoId, $itemId, $normalizados);

            AuditoriaService::log(
                $usuarioId,
                'alteracao_item',
                'orcamentos',
                "Item #{$itemId} do orçamento #{$orcamentoId} alterado."
            );

            $this->responder(200, [
                'success' => true,
                'message' => 'Item atualizado com sucesso.',
                'orcamento' => $this->montarOrcamentoDetalhado($orcamentoId),
            ]);
        } catch (OrcamentoException $e) {
            $this->responderErroOrcamento($e);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao atualizar item.']);
        }
    }

    public function excluirItem(int $orcamentoId, int $itemId, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->orcamentoModel->removerItem($orcamentoId, $itemId);

            AuditoriaService::log(
                $usuarioId,
                'exclusao_item',
                'orcamentos',
                "Item #{$itemId} removido do orçamento #{$orcamentoId}."
            );

            $this->responder(200, [
                'success' => true,
                'message' => 'Item removido com sucesso.',
                'orcamento' => $this->montarOrcamentoDetalhado($orcamentoId),
            ]);
        } catch (OrcamentoException $e) {
            $this->responderErroOrcamento($e);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao remover item.']);
        }
    }

    // ==========================================================
    // Respostas
    // ==========================================================

    private function responderErroOrcamento(OrcamentoException $e): void
    {
        switch ($e->getCodigoErro()) {
            case OrcamentoException::NAO_ENCONTRADO:
                $this->responder(404, ['success' => false, 'message' => 'Orçamento não encontrado.']);
                break;
            case OrcamentoException::ITEM_NAO_ENCONTRADO:
                $this->responder(404, ['success' => false, 'message' => 'Item não encontrado neste orçamento.']);
                break;
            case OrcamentoException::TOTAL_EXCEDIDO:
                $this->responder(400, ['success' => false, 'message' => $e->getMessage()]);
                break;
            default:
                $this->responder(500, ['success' => false, 'message' => 'Não foi possível manter a consistência do orçamento.']);
        }
    }

    private function responder(int $status, array $dados): void
    {
        http_response_code($status);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
