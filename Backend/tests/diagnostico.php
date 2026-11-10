<?php

/**
 * Diagnóstico da instalação do Grêmio ETEC Poá.
 *
 * Uso (no terminal, dentro da pasta do projeto):
 *     php Backend/tests/diagnostico.php
 *
 * Verifica versão do PHP, extensões, conexão com o MySQL, tabelas, dados
 * iniciais (seeds), administrador e pasta de uploads, e diz o que fazer
 * quando algo estiver faltando. Não altera nada no banco.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Execute pelo terminal: php Backend/tests/diagnostico.php');
}

$falhas = 0;
function ok(string $msg): void { echo "  [ OK ] $msg\n"; }
function falha(string $msg, string $dica = ''): void {
    global $falhas; $falhas++;
    echo "  [FALHA] $msg\n";
    if ($dica !== '') echo "          -> $dica\n";
}
function aviso(string $msg): void { echo "  [AVISO] $msg\n"; }

echo "\n== PHP ==\n";
version_compare(PHP_VERSION, '8.0.0', '>=') ? ok('PHP ' . PHP_VERSION) : falha('PHP ' . PHP_VERSION . ' (necessário 8.0+)');
foreach (['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl'] as $ext) {
    extension_loaded($ext) ? ok("extensão $ext") : falha("extensão $ext ausente", 'Ative no php.ini (extension=' . $ext . ') e reinicie o Apache.');
}

echo "\n== Banco de dados ==\n";
define('GREMIO_LANCAR_EXCECAO_BANCO', true);
require_once __DIR__ . '/../config/database.php';
$pdo = null;
try {
    $pdo = (new Database())->connect();
    ok('conexão com o MySQL');
} catch (Throwable $e) {
    falha('não conectou ao MySQL', 'Ligue o MySQL e confira host/porta/usuário/senha em Backend/config/database.php.');
}

if ($pdo) {
    $esperadas = [
        'usuarios', 'perfis', 'usuario_perfil', 'sessoes', 'solicitacoes_cadastro', 'tokens_recuperacao',
        'publicacoes', 'categorias_publicacao', 'tipos_reacao', 'reacoes',
        'eventos', 'participantes_evento', 'presenca_evento',
        'votacoes', 'opcoes_votacao', 'votos',
        'projetos', 'membros_projeto', 'tarefas', 'status_tarefa', 'historico_projeto',
        'notificacoes', 'tipos_notificacao', 'usuario_notificacao',
        'logs', 'categorias_financeiras', 'movimentacoes_financeiras', 'orcamentos', 'itens_orcamento',
    ];
    $existentes = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $faltam = array_diff($esperadas, $existentes);
    $faltam ? falha('tabelas ausentes: ' . implode(', ', $faltam), 'Importe database/gremio_etec_poa.sql no banco.')
            : ok(count($esperadas) . ' tabelas principais presentes');

    $seeds = [
        ['perfis', 'seed_perfis.sql', 2],
        ['tipos_notificacao', 'seed_tipos_notificacao.sql', 1],
        ['status_tarefa', 'seed_status_tarefa.sql', 1],
        ['categorias_publicacao', 'seed_publicacoes.sql', 1],
        ['tipos_reacao', 'seed_publicacoes.sql', 1],
    ];
    foreach ($seeds as [$tabela, $arquivo, $min]) {
        if (in_array($tabela, $existentes, true)) {
            $n = (int) $pdo->query("SELECT COUNT(*) FROM `$tabela`")->fetchColumn();
            $n >= $min ? ok("dados iniciais em $tabela ($n)") : falha("tabela $tabela vazia", "Importe database/$arquivo.");
        }
    }

    if (in_array('categorias_financeiras', $existentes, true)) {
        $n = (int) $pdo->query('SELECT COUNT(*) FROM categorias_financeiras')->fetchColumn();
        $n > 0 ? ok("categorias financeiras ($n)")
               : aviso('categorias financeiras vazias: serão criadas automaticamente no primeiro acesso ao Financeiro.');
        $ruins = (int) $pdo->query("SELECT COUNT(*) FROM categorias_financeiras WHERE nome COLLATE utf8mb4_bin LIKE '%Ã%' OR nome COLLATE utf8mb4_bin LIKE '%?%' OR nome COLLATE utf8mb4_bin LIKE '%�%'")->fetchColumn();
        if ($ruins > 0) aviso("$ruins categoria(s) com acentos corrompidos: serão corrigidas automaticamente ao abrir o Financeiro.");
    }

    if (in_array('usuario_perfil', $existentes, true)) {
        $admins = (int) $pdo->query("
            SELECT COUNT(*) FROM usuario_perfil up JOIN perfis p ON p.id = up.perfil_id
            JOIN usuarios u ON u.id = up.usuario_id
            WHERE p.nome = 'administrador' AND u.deleted_at IS NULL AND u.ativo = 1
        ")->fetchColumn();
        $admins > 0 ? ok("administrador ativo ($admins)")
                    : falha('nenhum administrador ativo', 'php Backend/tests/criar-admin.php 00001 admin@etecpoa.sp.gov.br "Administrador Geral" SuaSenha@123');
    }

    $gatilhos = (int) $pdo->query("
        SELECT COUNT(*) FROM information_schema.TRIGGERS
        WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME LIKE 'trg\\_%admin\\_no\\_%'
    ")->fetchColumn();
    $gatilhos >= 4 ? ok("proteção do administrador instalada no banco ($gatilhos gatilhos)")
                   : aviso('proteção do administrador NÃO instalada no banco. Rode: php Backend/tests/proteger-admin.php');

    $charset = $pdo->query("SELECT @@character_set_database")->fetchColumn();
    $charset === 'utf8mb4' || $charset === 'utf8' ? ok("codificação do banco: $charset")
        : aviso("codificação do banco é $charset (recomendado utf8mb4)");
}

echo "\n== Arquivos ==\n";
$uploads = __DIR__ . '/../../uploads';
is_dir($uploads) && is_writable($uploads) ? ok('pasta uploads gravável') : aviso('pasta uploads ausente ou sem permissão de escrita');
file_exists(__DIR__ . '/../public/.htaccess') ? ok('.htaccess da API presente (precisa de mod_rewrite ativo no Apache)') : falha('Backend/public/.htaccess ausente');

echo "\n" . ($falhas === 0 ? "Tudo certo! Nenhum problema encontrado.\n" : "$falhas problema(s) encontrado(s). Corrija os itens [FALHA] acima.\n") . "\n";
exit($falhas === 0 ? 0 : 1);
