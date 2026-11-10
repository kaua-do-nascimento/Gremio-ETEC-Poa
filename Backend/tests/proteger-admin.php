<?php

/**
 * Instala (ou reinstala) a proteção do administrador no banco.
 *
 * Uso:   php Backend/tests/proteger-admin.php
 *
 * Cria 4 gatilhos (triggers) que impedem, no próprio banco, que um usuário
 * com o perfil "administrador" seja excluído, suspenso ou perca o perfil.
 * É seguro rodar mais de uma vez. O mesmo conteúdo está em
 * database/protecao_administrador.sql (para importar pelo phpMyAdmin).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Execute pelo terminal: php Backend/tests/proteger-admin.php');
}

define('GREMIO_LANCAR_EXCECAO_BANCO', true);
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = (new Database())->connect();
} catch (Throwable $e) {
    exit("Não foi possível conectar ao banco: " . $e->getMessage() . PHP_EOL);
}

$sqlArquivo = file_get_contents(__DIR__ . '/../../database/protecao_administrador.sql');
if ($sqlArquivo === false) {
    exit("Arquivo database/protecao_administrador.sql não encontrado." . PHP_EOL);
}

// Remove comentários e a diretiva DELIMITER (só existe para clientes SQL)
$sqlArquivo = preg_replace('/^--.*$/m', '', $sqlArquivo);
$sqlArquivo = str_replace(['DELIMITER $$', 'DELIMITER ;'], '', $sqlArquivo);

$comandos = array_filter(array_map('trim', explode('$$', $sqlArquivo)));
$instalados = 0;

try {
    foreach ($comandos as $bloco) {
        // Os DROP TRIGGER vêm todos juntos no primeiro bloco, separados por ";"
        if (stripos($bloco, 'CREATE TRIGGER') === false) {
            foreach (array_filter(array_map('trim', explode(';', $bloco))) as $drop) {
                $pdo->exec($drop);
            }
            continue;
        }
        $pdo->exec($bloco);
        $instalados++;
    }
} catch (Throwable $e) {
    exit("Falha ao instalar a proteção: " . $e->getMessage() . PHP_EOL
        . "O usuário do banco precisa da permissão TRIGGER (o root do WAMP já tem)." . PHP_EOL);
}

$admins = (int) $pdo->query("
    SELECT COUNT(*) FROM usuario_perfil up JOIN perfis p ON p.id = up.perfil_id
    WHERE p.nome = 'administrador'
")->fetchColumn();

echo "Proteção instalada: {$instalados} gatilhos ativos." . PHP_EOL;
echo "Administradores protegidos agora: {$admins}." . PHP_EOL;
