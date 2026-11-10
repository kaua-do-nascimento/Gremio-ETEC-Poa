<?php

/**
 * Envia uma resposta HTTP em formato JSON e centraliza o formato de
 * resposta usado pelas rotas e pelo middleware de autenticação.
 *
 * Os controllers existentes mantêm seus próprios métodos responder()
 * privados (não foram alterados para não duplicar/instabilizar código
 * já funcionando). Este helper é usado apenas pelo código novo
 * (routes/api.php e middleware/AuthMiddleware.php).
 */
function enviarResposta(int $status, array $dados): void
{
    http_response_code($status);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
}
