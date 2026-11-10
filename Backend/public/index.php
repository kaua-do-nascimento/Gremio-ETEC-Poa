<?php

// ==============================================================
// CORS — necessário porque o front-end (arquivos .html) roda em
// uma porta/origem diferente da API (ex.: front em :5500, API em :8000).
// Sem isso, o navegador bloqueia as chamadas fetch() por segurança.
// ==============================================================
$origemPermitida = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: {$origemPermitida}");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Requisições OPTIONS são o "preflight" do navegador (checagem de CORS).
// Respondemos vazio, sem executar nenhuma rota.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../routes/api.php';

$method = $_SERVER['REQUEST_METHOD'];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /index.php caso ele apareça na URL
$uri = str_replace('/index.php', '', $uri);

// ==============================================================
// Torna a rota independente da subpasta onde o projeto está.
// No WampServer, a URL real é algo como:
//   /Gremio-ETEC-Poa/Backend/public/api/login
// Em vez de exigir que a URI comece exatamente com "/api", buscamos
// a partir da primeira ocorrência de "/api" na URL. Assim funciona
// tanto no servidor embutido do PHP (localhost:8000/api/login)
// quanto dentro do WampServer, não importa o nome da pasta do projeto.
// ==============================================================
$posicaoApi = strpos($uri, '/api');
if ($posicaoApi !== false) {
    $uri = substr($uri, $posicaoApi);
}

// Garante que a URI comece com /
if ($uri === '') {
    $uri = '/';
}

handleRoute($method, $uri);