<?php

// Utilitário de manutenção: somente via terminal (nunca pelo navegador).
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Execute pelo terminal (CLI).');
}

require_once __DIR__ . '/../config/database.php';

$rm = $argv[1] ?? '';
$senha = $argv[2] ?? '';
if ($rm === '' || strlen($senha) < 8) {
    exit("Uso: php Backend/tests/resetar-senha.php RM NovaSenha (mínimo 8 caracteres)\n");
}

$pdo = (new Database())->connect();
$stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = :h WHERE rm = :rm AND deleted_at IS NULL");
$stmt->execute([':h' => password_hash($senha, PASSWORD_DEFAULT), ':rm' => $rm]);

echo $stmt->rowCount() > 0 ? "Senha alterada.\n" : "Nenhum usuário encontrado com esse RM.\n";