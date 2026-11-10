<?php

// Utilitário de manutenção: somente via terminal (nunca pelo navegador).
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Execute pelo terminal (CLI).');
}

require_once __DIR__ . '/../config/database.php';
$pdo = (new Database())->connect();

$info = $pdo->query("SELECT @@port AS porta, @@version AS versao, DATABASE() AS banco, @@datadir AS pasta_dados")->fetch();
print_r($info);

$usuarios = $pdo->query("SELECT id, rm, email, nome_completo, ativo, deleted_at FROM usuarios")->fetchAll();
print_r($usuarios);