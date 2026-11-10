<?php

require_once __DIR__ . '/../models/Financeiro.php';
require_once __DIR__ . '/../services/AuditoriaService.php';

class FinanceiroController
{
    private Financeiro $financeiroModel;

    private const TIPOS_VALIDOS = ['entrada', 'saida'];
    private const ORIGENS_VALIDAS = ['doacao', 'patrocinio', 'evento', 'outro'];
    private const LIMITE_PADRAO = 20;
    private const LIMITE_MAXIMO = 100;
    private const VALOR_MAXIMO = 99999999.99; // limite físico de decimal(10,2)

    public function __construct()
    {
        $this->financeiroModel = new Financeiro();
    }

    private function formatarCategoria(array $linha): array
    {
        return [
            'id' => (int) $linha['id'],
            'nome' => $linha['nome'],
            'tipo' => $linha['tipo'],
            'descricao' => $linha['descricao'],
        ];
    }

    /**
     * 'valor' é mantido como string (o mesmo formato DECIMAL que o
     * MySQL/PDO devolve) em todas as respostas — nunca é convertido
     * para float em PHP, para não arriscar erro de arredondamento
     * monetário. O front-end formata para exibição (R$ 0,00).
     */
    private function formatarMovimentacao(array $linha): array
    {
        return [
            'id' => (int) $linha['id'],
            'categoria_id' => (int) $linha['categoria_id'],
            'categoria_nome' => $linha['categoria_nome'],
            'categoria_tipo' => $linha['categoria_tipo'],
            'usuario_id' => (int) $linha['usuario_id'],
            'usuario_nome' => $linha['usuario_nome'],
            'tipo' => $linha['tipo'],
            'origem' => $linha['origem'],
            'descricao' => $linha['descricao'],
            'valor' => $linha['valor'],
            'data_movimentacao' => $linha['data_movimentacao'],
            'created_at' => $linha['created_at'],
            'updated_at' => $linha['updated_at'],
        ];
    }

    private function dataValida($valor): bool
    {
        if ($valor === null || $valor === '') {
            return false;
        }
        $partes = DateTime::createFromFormat('Y-m-d', (string) $valor);
        return $partes && $partes->format('Y-m-d') === $valor;
    }

    private function valorValido($valor): bool
    {
        if ($valor === null || $valor === '' || !is_numeric($valor)) {
            return false;
        }
        $numero = (float) $valor;
        return $numero > 0 && $numero <= self::VALOR_MAXIMO;
    }

    /**
     * Regra de consistência (não é constraint do banco, mas evita
     * dado inconsistente): a categoria escolhida precisa ser do
     * mesmo tipo (entrada/saida) da movimentação.
     */
    private function validarDadosMovimentacao(array $dados): ?string
    {
        $tipo = (string) ($dados['tipo'] ?? '');
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            return 'Tipo é obrigatório e deve ser "entrada" ou "saida".';
        }

        $categoriaId = $dados['categoria_id'] ?? null;
        if (!is_numeric($categoriaId) || (int) $categoriaId < 1) {
            return 'Categoria é obrigatória.';
        }

        $categoria = $this->financeiroModel->buscarCategoriaPorId((int) $categoriaId);
        if (!$categoria) {
            return 'A categoria informada não existe.';
        }
        if ($categoria['tipo'] !== $tipo) {
            return "A categoria \"{$categoria['nome']}\" é do tipo \"{$categoria['tipo']}\" "
                 . "e não pode ser usada numa movimentação do tipo \"{$tipo}\".";
        }

        $origem = $dados['origem'] ?? null;
        if (!empty($origem) && !in_array($origem, self::ORIGENS_VALIDAS, true)) {
            return 'Origem inválida.';
        }

        $descricao = trim((string) ($dados['descricao'] ?? ''));
        if ($descricao === '' || mb_strlen($descricao) > 255) {
            return 'Descrição é obrigatória e deve ter no máximo 255 caracteres.';
        }

        if (!$this->valorValido($dados['valor'] ?? null)) {
            return 'Valor é obrigatório e deve ser um número maior que zero.';
        }

        if (!$this->dataValida($dados['data_movimentacao'] ?? null)) {
            return 'Data da movimentação é obrigatória. Use o formato AAAA-MM-DD.';
        }

        return null;
    }

    private function normalizarDadosMovimentacao(array $dados): array
    {
        return [
            'categoria_id' => (int) $dados['categoria_id'],
            'tipo' => $dados['tipo'],
            'origem' => !empty($dados['origem']) ? $dados['origem'] : null,
            'descricao' => trim((string) $dados['descricao']),
            'valor' => number_format((float) $dados['valor'], 2, '.', ''),
            'data_movimentacao' => $dados['data_movimentacao'],
        ];
    }

    public function listarCategorias(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $linhas = $this->financeiroModel->listarCategorias();
            $this->responder(200, [
                'success' => true,
                'categorias' => array_map([$this, 'formatarCategoria'], $linhas),
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar categorias financeiras.']);
        }
    }

    /**
     * Lista movimentações com filtros e paginação (somente administrador).
     * Filtros aceitos: tipo, categoria_id, data_inicio, data_fim.
     */
    public function listar(array $filtros, int $pagina, int $limite): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!empty($filtros['tipo']) && !in_array($filtros['tipo'], self::TIPOS_VALIDOS, true)) {
            $this->responder(400, ['success' => false, 'message' => 'Filtro de tipo inválido.']);
            return;
        }

        $limite = max(1, min(self::LIMITE_MAXIMO, $limite ?: self::LIMITE_PADRAO));
        $pagina = max(1, $pagina);

        try {
            $linhas = $this->financeiroModel->listar($filtros, $pagina, $limite);
            $total = $this->financeiroModel->contar($filtros);

            $this->responder(200, [
                'success' => true,
                'movimentacoes' => array_map([$this, 'formatarMovimentacao'], $linhas),
                'pagina' => $pagina,
                'limite' => $limite,
                'total' => $total,
                'total_paginas' => (int) ceil($total / $limite),
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar movimentações.']);
        }
    }

    /**
     * Totais de entradas/saídas/saldo (somente administrador).
     * Aceita filtro opcional de período (data_inicio/data_fim).
     */
    public function resumo(array $filtros): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $linha = $this->financeiroModel->resumo($filtros);

            $this->responder(200, [
                'success' => true,
                'total_entradas' => $linha['total_entradas'],
                'total_saidas' => $linha['total_saidas'],
                'saldo' => $linha['saldo'],
                'quantidade' => (int) $linha['quantidade'],
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao calcular resumo financeiro.']);
        }
    }

    public function buscarPorId(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $linha = $this->financeiroModel->buscarPorId($id);
            if (!$linha) {
                $this->responder(404, ['success' => false, 'message' => 'Movimentação não encontrada.']);
                return;
            }
            $this->responder(200, ['success' => true, 'movimentacao' => $this->formatarMovimentacao($linha)]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao buscar movimentação.']);
        }
    }

    public function criar(int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $erro = $this->validarDadosMovimentacao($dados);
        if ($erro !== null) {
            $this->responder(400, ['success' => false, 'message' => $erro]);
            return;
        }

        try {
            $dadosNormalizados = $this->normalizarDadosMovimentacao($dados);
            $id = $this->financeiroModel->criar($usuarioId, $dadosNormalizados);

            // Instrução explícita: NUNCA registrar valor/descrição no
            // log de auditoria geral (informação financeira sensível),
            // mesmo o módulo já sendo restrito a administrador. Só o
            // tipo e o id vão para a trilha.
            AuditoriaService::log(
                $usuarioId,
                'criacao',
                'financeiro',
                "Movimentação financeira do tipo \"{$dadosNormalizados['tipo']}\" criada (#{$id})."
            );

            $this->responder(201, [
                'success' => true,
                'message' => 'Movimentação criada com sucesso.',
                'id' => $id,
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao criar movimentação.']);
        }
    }

    public function atualizar(int $id, int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->financeiroModel->existe($id)) {
            $this->responder(404, ['success' => false, 'message' => 'Movimentação não encontrada.']);
            return;
        }

        $erro = $this->validarDadosMovimentacao($dados);
        if ($erro !== null) {
            $this->responder(400, ['success' => false, 'message' => $erro]);
            return;
        }

        try {
            $dadosNormalizados = $this->normalizarDadosMovimentacao($dados);
            $this->financeiroModel->atualizar($id, $dadosNormalizados);

            AuditoriaService::log(
                $usuarioId,
                'atualizacao',
                'financeiro',
                "Movimentação financeira #{$id} atualizada."
            );

            $this->responder(200, ['success' => true, 'message' => 'Movimentação atualizada com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao atualizar movimentação.']);
        }
    }

    /**
     * Arquiva (soft delete) uma movimentação.
     */
    public function excluir(int $id, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $arquivou = $this->financeiroModel->arquivar($id);
            if (!$arquivou) {
                $this->responder(404, ['success' => false, 'message' => 'Movimentação não encontrada.']);
                return;
            }

            AuditoriaService::log(
                $usuarioId,
                'arquivamento',
                'financeiro',
                "Movimentação financeira #{$id} arquivada."
            );

            $this->responder(200, ['success' => true, 'message' => 'Movimentação arquivada com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao arquivar movimentação.']);
        }
    }

    private function responder(int $status, array $dados): void
    {
        http_response_code($status);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
