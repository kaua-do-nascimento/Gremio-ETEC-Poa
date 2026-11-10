<?php

require_once __DIR__ . '/env.php';

class Database
{
    private string $host;
    private string $port;
    private string $db_name;
    private string $username;
    private string $password;

    public function __construct()
    {
        // Valores lidos de variáveis de ambiente / arquivo .env (veja .env.example).
        // Os padrões abaixo reproduzem a configuração de desenvolvimento original.
        $this->host = (string) gremio_env('DB_HOST', 'localhost');
        $this->port = (string) gremio_env('DB_PORT', '3307');
        $this->db_name = (string) gremio_env('DB_NAME', 'gremio_etec_poa');
        $this->username = (string) gremio_env('DB_USER', 'root');
        $this->password = (string) gremio_env('DB_PASS', '');
    }

    public function connect(): PDO
    {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";

            $pdo = new PDO($dsn, $this->username, $this->password);

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // IMPORTANTE: sem isso, fetch() retorna PDO::FETCH_BOTH (chaves
            // numéricas E nomeadas). Isso causava vazamento de colunas
            // sensíveis (ex.: senha_hash) mesmo após unset($usuario['senha_hash']),
            // pois a chave numérica correspondente permanecia no array.
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $pdo;

        } catch (PDOException $e) {
            error_log('[Database] Falha na conexão: ' . $e->getMessage());

            // O script de diagnóstico trata a exceção por conta própria
            if (defined('GREMIO_LANCAR_EXCECAO_BANCO')) {
                throw $e;
            }

            // Responde em JSON (o front-end sempre espera JSON da API) em vez de
            // texto puro, que aparecia como "Resposta inválida do servidor".
            if (PHP_SAPI !== 'cli') {
                if (!headers_sent()) {
                    http_response_code(500);
                    header('Content-Type: application/json; charset=utf-8');
                }
                die(json_encode([
                    'success' => false,
                    'message' => 'Não foi possível conectar ao banco de dados. Verifique se o MySQL está ligado '
                        . '(porta ' . $this->port . ') e se o banco "' . $this->db_name . '" foi criado. '
                        . 'Rode "php Backend/tests/diagnostico.php" para ver o que está faltando.',
                ], JSON_UNESCAPED_UNICODE));
            }

            die("Erro na conexão com o banco: " . $e->getMessage() . PHP_EOL);
        }
    }
}