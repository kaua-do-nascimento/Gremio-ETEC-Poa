<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Model responsável pela auditoria do sistema.
 *
 * AUDITORIA DA ESTRUTURA (feita antes de implementar este módulo):
 * O banco já possui 3 tabelas de log, todas vazias, nenhuma criada
 * por este módulo:
 *
 *   - `logs` .............. log geral de auditoria (append-only).
 *     Colunas: id, usuario_id (nullable, FK -> usuarios ON DELETE SET NULL),
 *     acao, modulo, descricao, ip_address, user_agent, created_at.
 *     É a tabela que este módulo usa como trilha de auditoria
 *     unificada (a que o painel administrativo consulta).
 *
 *   - `logs_login` ......... histórico dedicado de tentativas de
 *     login (sucesso e falha), com colunas próprias `sucesso` e
 *     `motivo_falha` que a tabela `logs` não tem. Usada em paralelo
 *     à `logs` apenas para logins: todo login/tentativa de login
 *     grava aqui (com detalhe de sucesso/falha); um resumo do LOGIN
 *     COM SUCESSO também é gravado em `logs` (acao='login') para
 *     aparecer na trilha unificada. Tentativas malsucedidas ficam
 *     somente em `logs_login`, para não poluir a trilha geral com
 *     tentativas de senha errada.
 *
 *   - `logs_alteracoes` .... existe e seria adequada para guardar o
 *     diff completo (JSON antes/depois) de qualquer UPDATE. NÃO foi
 *     usada nesta primeira versão do módulo para manter o escopo
 *     controlado — a tabela `logs` já registra "o quê" mudou de
 *     forma legível em `descricao` (reaproveitando os textos que
 *     vários controllers já geravam, ex.: registrarHistorico() de
 *     Projeto). Fica documentado aqui como possível evolução futura,
 *     sem necessidade de nenhuma alteração estrutural para isso.
 *
 * Nenhuma tabela nova foi criada e nenhuma coluna foi alterada.
 */
class Log
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    // ==========================================================
    // REGISTRO
    // ==========================================================

    /**
     * Registra uma entrada na trilha de auditoria geral.
     */
    public function registrar(?int $usuarioId, string $acao, string $modulo, ?string $descricao): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO logs (usuario_id, acao, modulo, descricao, ip_address, user_agent)
            VALUES (:usuario_id, :acao, :modulo, :descricao, :ip, :ua)
        ");
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':acao' => $acao,
            ':modulo' => $modulo,
            ':descricao' => $descricao,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Registra uma tentativa de login bem-sucedida (logs_login) e
     * também um resumo na trilha geral (logs).
     */
    public function registrarLoginSucesso(int $usuarioId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO logs_login (usuario_id, ip_address, user_agent, sucesso, motivo_falha)
            VALUES (:usuario_id, :ip, :ua, 1, NULL)
        ");
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
        ]);

        $this->registrar($usuarioId, 'login', 'autenticacao', 'Login realizado com sucesso.');
    }

    /**
     * Registra uma tentativa de login malsucedida. Fica somente em
     * `logs_login` (não polui a trilha geral). $usuarioId pode ser
     * null quando o "login" informado não corresponde a nenhum
     * usuário. Nunca recebe/grava a senha em lugar nenhum.
     */
    public function registrarLoginFalha(?int $usuarioId, string $motivo): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO logs_login (usuario_id, ip_address, user_agent, sucesso, motivo_falha)
            VALUES (:usuario_id, :ip, :ua, 0, :motivo)
        ");
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
            ':motivo' => $motivo,
        ]);
    }

    // ==========================================================
    // CONSULTA (painel administrativo)
    // ==========================================================

    /**
     * Monta a cláusula WHERE + parâmetros a partir dos filtros
     * aceitos: usuario_id, acao, modulo, data_inicio (YYYY-MM-DD),
     * data_fim (YYYY-MM-DD). Todos opcionais.
     */
    private function montarFiltros(array $filtros): array
    {
        $condicoes = [];
        $params = [];

        if (!empty($filtros['usuario_id'])) {
            $condicoes[] = 'l.usuario_id = :usuario_id';
            $params[':usuario_id'] = (int) $filtros['usuario_id'];
        }

        if (!empty($filtros['acao'])) {
            $condicoes[] = 'l.acao = :acao';
            $params[':acao'] = $filtros['acao'];
        }

        if (!empty($filtros['modulo'])) {
            $condicoes[] = 'l.modulo = :modulo';
            $params[':modulo'] = $filtros['modulo'];
        }

        if (!empty($filtros['data_inicio'])) {
            $condicoes[] = 'l.created_at >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
        }

        if (!empty($filtros['data_fim'])) {
            $condicoes[] = 'l.created_at <= :data_fim';
            $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
        }

        $where = $condicoes ? ('WHERE ' . implode(' AND ', $condicoes)) : '';

        return [$where, $params];
    }

    public function listar(array $filtros, int $pagina, int $limite): array
    {
        [$where, $params] = $this->montarFiltros($filtros);
        $offset = ($pagina - 1) * $limite;

        $sql = "
            SELECT
                l.id, l.usuario_id, l.acao, l.modulo, l.descricao,
                l.ip_address, l.created_at,
                u.nome_completo AS usuario_nome, u.rm AS usuario_rm
            FROM logs l
            LEFT JOIN usuarios u ON u.id = l.usuario_id
            {$where}
            ORDER BY l.created_at DESC, l.id DESC
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

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM logs l {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                l.id, l.usuario_id, l.acao, l.modulo, l.descricao,
                l.ip_address, l.user_agent, l.created_at,
                u.nome_completo AS usuario_nome, u.rm AS usuario_rm, u.email AS usuario_email
            FROM logs l
            LEFT JOIN usuarios u ON u.id = l.usuario_id
            WHERE l.id = :id
        ");
        $stmt->execute([':id' => $id]);

        $linha = $stmt->fetch();

        return $linha ?: null;
    }

    /**
     * Ações e módulos realmente já usados na tabela (para alimentar
     * os filtros em dropdown no frontend, sem precisar de uma lista
     * fixa hardcoded).
     */
    public function listarAcoesDistintas(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT acao FROM logs ORDER BY acao");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listarModulosDistintos(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT modulo FROM logs ORDER BY modulo");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
