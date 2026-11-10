<?php

require_once __DIR__ . '/../models/Log.php';

class AuditoriaController
{
    private Log $logModel;

    private const LIMITE_PADRAO = 30;
    private const LIMITE_MAXIMO = 100;

    public function __construct()
    {
        $this->logModel = new Log();
    }

    private function formatar(array $linha): array
    {
        return [
            'id' => (int) $linha['id'],
            'usuario_id' => $linha['usuario_id'] !== null ? (int) $linha['usuario_id'] : null,
            'usuario_nome' => $linha['usuario_nome'],
            'usuario_rm' => $linha['usuario_rm'],
            'acao' => $linha['acao'],
            'modulo' => $linha['modulo'],
            'descricao' => $linha['descricao'],
            'ip_address' => $linha['ip_address'],
            'created_at' => $linha['created_at'],
        ];
    }

    /**
     * Lista os logs de auditoria (somente administrador — RBAC já
     * verificado na rota antes de chegar aqui).
     * Filtros aceitos: usuario_id, acao, modulo, data_inicio, data_fim.
     */
    public function listar(array $filtros, int $pagina, int $limite): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $limite = max(1, min(self::LIMITE_MAXIMO, $limite ?: self::LIMITE_PADRAO));
        $pagina = max(1, $pagina);

        try {
            $linhas = $this->logModel->listar($filtros, $pagina, $limite);
            $total = $this->logModel->contar($filtros);

            $this->responder(200, [
                'success' => true,
                'logs' => array_map([$this, 'formatar'], $linhas),
                'pagina' => $pagina,
                'limite' => $limite,
                'total' => $total,
                'total_paginas' => (int) ceil($total / $limite),
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar logs de auditoria.']);
        }
    }

    public function buscarPorId(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $linha = $this->logModel->buscarPorId($id);

            if (!$linha) {
                $this->responder(404, ['success' => false, 'message' => 'Log não encontrado.']);
                return;
            }

            $detalhe = $this->formatar($linha);
            $detalhe['usuario_email'] = $linha['usuario_email'];
            $detalhe['user_agent'] = $linha['user_agent'];

            $this->responder(200, ['success' => true, 'log' => $detalhe]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao buscar log.']);
        }
    }

    /**
     * Retorna as ações e módulos já usados nos logs, para alimentar
     * os filtros em dropdown no frontend (sem lista fixa hardcoded).
     */
    public function listarFiltrosDisponiveis(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->responder(200, [
                'success' => true,
                'acoes' => $this->logModel->listarAcoesDistintas(),
                'modulos' => $this->logModel->listarModulosDistintos(),
            ]);
        } catch (PDOException $e) {
            $this->responder(500, ['success' => false, 'message' => 'Erro ao listar filtros disponíveis.']);
        }
    }

    private function responder(int $status, array $dados): void
    {
        http_response_code($status);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
