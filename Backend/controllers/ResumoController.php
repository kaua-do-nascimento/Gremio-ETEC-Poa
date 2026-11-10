<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Visão geral do painel administrativo (GET /api/admin/resumo).
 * Somente leitura: contagens e somas calculadas em SQL.
 */
class ResumoController
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    private function um(string $sql): string
    {
        return (string) $this->db->query($sql)->fetchColumn();
    }

    public function resumo(): void
    {
        try {
            $dados = [
                'usuarios' => [
                    'ativos' => (int) $this->um("SELECT COUNT(*) FROM usuarios WHERE deleted_at IS NULL AND ativo = 1"),
                    'pendentes' => (int) $this->um("SELECT COUNT(*) FROM solicitacoes_cadastro WHERE status = 'pendente'"),
                ],
                'eventos' => [
                    'total' => (int) $this->um("SELECT COUNT(*) FROM eventos WHERE deleted_at IS NULL"),
                    'planejados' => (int) $this->um("SELECT COUNT(*) FROM eventos WHERE deleted_at IS NULL AND status = 'planejado'"),
                ],
                'projetos' => [
                    'total' => (int) $this->um("SELECT COUNT(*) FROM projetos WHERE deleted_at IS NULL"),
                    'em_andamento' => (int) $this->um("SELECT COUNT(*) FROM projetos WHERE deleted_at IS NULL AND status = 'em_andamento'"),
                ],
                'votacoes' => [
                    'total' => (int) $this->um("SELECT COUNT(*) FROM votacoes WHERE deleted_at IS NULL"),
                    'abertas' => (int) $this->um("SELECT COUNT(*) FROM votacoes WHERE deleted_at IS NULL AND status = 'aberta'"),
                ],
                'publicacoes' => [
                    'total' => (int) $this->um("SELECT COUNT(*) FROM publicacoes WHERE deleted_at IS NULL"),
                ],
                'financeiro' => [
                    'entradas' => $this->um("SELECT CAST(COALESCE(SUM(valor),0) AS DECIMAL(12,2)) FROM movimentacoes_financeiras WHERE deleted_at IS NULL AND tipo = 'entrada'"),
                    'saidas' => $this->um("SELECT CAST(COALESCE(SUM(valor),0) AS DECIMAL(12,2)) FROM movimentacoes_financeiras WHERE deleted_at IS NULL AND tipo = 'saida'"),
                    'saldo' => $this->um("SELECT CAST(COALESCE(SUM(CASE WHEN tipo='entrada' THEN valor ELSE -valor END),0) AS DECIMAL(12,2)) FROM movimentacoes_financeiras WHERE deleted_at IS NULL"),
                ],
                'orcamentos' => [
                    'total' => (int) $this->um("SELECT COUNT(*) FROM orcamentos WHERE deleted_at IS NULL"),
                    'aprovados' => (int) $this->um("SELECT COUNT(*) FROM orcamentos WHERE deleted_at IS NULL AND status = 'aprovado'"),
                ],
            ];

            $stmt = $this->db->query("
                SELECT l.acao, l.modulo, l.descricao, l.created_at, u.nome_completo AS usuario_nome
                FROM logs l LEFT JOIN usuarios u ON u.id = l.usuario_id
                ORDER BY l.id DESC LIMIT 8
            ");
            $dados['atividade_recente'] = $stmt->fetchAll();

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true] + $dados, JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            error_log('[ResumoController] ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.'], JSON_UNESCAPED_UNICODE);
        }
    }
}
