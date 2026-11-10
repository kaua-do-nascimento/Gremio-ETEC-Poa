<?php

require_once __DIR__ . '/../models/Sessao.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../utils/Response.php';

/**
 * Extrai o token Bearer do cabeçalho Authorization.
 * Retorna null se o cabeçalho não existir ou estiver em formato inválido.
 */
function extrairTokenBearer(): ?string
{
    $headers = [];

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    }

    // Fallback para configurações onde getallheaders() não está disponível
    // (ex.: alguns setups de PHP-FPM/nginx sem passthrough de headers)
    $authHeader = $headers['Authorization']
        ?? $headers['authorization']
        ?? $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? null;

    if (!$authHeader) {
        return null;
    }

    if (!preg_match('/^Bearer\s+(\S+)$/i', trim($authHeader), $matches)) {
        return null;
    }

    return $matches[1];
}

/**
 * Autentica a requisição atual a partir do token enviado no cabeçalho
 * "Authorization: Bearer <token>".
 *
 * Em caso de sucesso, retorna um array com os dados do usuário autenticado
 * (incluindo a lista de nomes de perfis). Em caso de falha, já envia a
 * resposta HTTP 401 e encerra a execução do script (exit).
 *
 * @return array{id:int, rm:string, email:string, nome_completo:string,
 *               foto_url:string, perfis:string[], token_hash:string}
 */
function autenticarRequisicao(): array
{
    $token = extrairTokenBearer();

    if (!$token) {
        enviarResposta(401, [
            'success' => false,
            'message' => 'Token de autenticação não informado. Envie o cabeçalho Authorization: Bearer <token>.'
        ]);
        exit;
    }

    $tokenHash = hash('sha256', $token);

    $sessaoModel = new Sessao();
    $sessao = $sessaoModel->buscarAtivaPorTokenHash($tokenHash);

    if (!$sessao) {
        enviarResposta(401, [
            'success' => false,
            'message' => 'Sessão inválida ou expirada. Faça login novamente.'
        ]);
        exit;
    }

    $usuarioModel = new Usuario();
    $perfis = $usuarioModel->buscarPerfis((int) $sessao['usuario_id']);

    return [
        'id' => (int) $sessao['usuario_id'],
        'rm' => $sessao['rm'],
        'email' => $sessao['email'],
        'nome_completo' => $sessao['nome_completo'],
        'foto_url' => $sessao['foto_url'],
        'perfis' => $perfis,
        'token_hash' => $tokenHash
    ];
}

/**
 * Garante que o usuário autenticado possua pelo menos um dos perfis
 * informados. Caso contrário, já envia a resposta HTTP 403 e encerra
 * a execução do script (exit).
 *
 * @param array $usuarioAutenticado Retorno de autenticarRequisicao().
 * @param string[] $perfisPermitidos Ex.: ['administrador']
 */
function exigirPerfil(array $usuarioAutenticado, array $perfisPermitidos): void
{
    $perfisDoUsuario = $usuarioAutenticado['perfis'] ?? [];

    if (empty(array_intersect($perfisDoUsuario, $perfisPermitidos))) {
        enviarResposta(403, [
            'success' => false,
            'message' => 'Você não tem permissão para acessar este recurso.'
        ]);
        exit;
    }
}
