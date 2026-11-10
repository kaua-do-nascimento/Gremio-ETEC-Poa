<?php

/**
 * Script de linha de comando para criar o usuário administrador inicial.
 *
 * Por que um script CLI e não um endpoint da API?
 * Porque um endpoint público de criação de administrador seria uma falha
 * grave de segurança. Esta é a forma correta de provisionar o primeiro
 * administrador do sistema.
 *
 * Uso (PowerShell, a partir da pasta Backend):
 *   php tests/criar-admin.php <rm> <email> "<nome completo>" <senha>
 *
 * Exemplo:
 *   php tests/criar-admin.php 00001 admin@etecpoa.sp.gov.br "Administrador Geral" SuaSenhaForte@123
 */

require_once __DIR__ . '/../config/database.php';

if (php_sapi_name() !== 'cli') {
    die("Este script deve ser executado via linha de comando (CLI).\n");
}

$rm = $argv[1] ?? null;
$email = $argv[2] ?? null;
$nomeCompleto = $argv[3] ?? null;
$senha = $argv[4] ?? null;

if (!$rm || !$email || !$nomeCompleto || !$senha) {
    die("Uso: php tests/criar-admin.php <rm> <email> \"<nome completo>\" <senha>\n");
}

if (strlen($rm) !== 5) {
    die("Erro: o RM deve possuir exatamente 5 caracteres.\n");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Erro: e-mail inválido.\n");
}

if (strlen($senha) < 8) {
    die("Erro: a senha deve possuir pelo menos 8 caracteres.\n");
}

$database = new Database();
$db = $database->connect();

try {
    $db->beginTransaction();

    // Garante que o perfil "administrador" existe
    $stmtPerfil = $db->prepare("SELECT id FROM perfis WHERE nome = 'administrador' LIMIT 1");
    $stmtPerfil->execute();
    $perfil = $stmtPerfil->fetch();

    if (!$perfil) {
        $db->prepare("
            INSERT INTO perfis (nome, descricao)
            VALUES ('administrador', 'Administrador do sistema')
        ")->execute();

        $perfilId = (int) $db->lastInsertId();
    } else {
        $perfilId = (int) $perfil['id'];
    }

    // Verifica se já existe usuário com este RM ou e-mail
    $stmtExiste = $db->prepare("
        SELECT id FROM usuarios WHERE rm = :rm OR email = :email LIMIT 1
    ");
    $stmtExiste->execute([':rm' => $rm, ':email' => $email]);
    $existente = $stmtExiste->fetch();

    if ($existente) {
        $db->rollBack();
        die("Já existe um usuário com este RM ou e-mail (id={$existente['id']}).\n");
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $stmtInsere = $db->prepare("
        INSERT INTO usuarios (rm, email, senha_hash, nome_completo, foto_url, ativo)
        VALUES (:rm, :email, :senha_hash, :nome_completo, 'nsa/default.png', 1)
    ");
    $stmtInsere->execute([
        ':rm' => $rm,
        ':email' => $email,
        ':senha_hash' => $senhaHash,
        ':nome_completo' => $nomeCompleto
    ]);

    $usuarioId = (int) $db->lastInsertId();

    $db->prepare("
        INSERT INTO usuario_perfil (usuario_id, perfil_id)
        VALUES (:usuario_id, :perfil_id)
    ")->execute([
        ':usuario_id' => $usuarioId,
        ':perfil_id' => $perfilId
    ]);

    $db->commit();

    echo "Administrador criado com sucesso!\n";
    echo "ID: {$usuarioId}\n";
    echo "RM: {$rm}\n";
    echo "E-mail: {$email}\n";

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    die("Erro ao criar administrador: " . $e->getMessage() . "\n");
}
