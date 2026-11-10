<?php

/**
 * Leitura de configurações locais (variáveis de ambiente).
 *
 * Ordem de prioridade para cada chave:
 *   1. variável de ambiente real do sistema/Apache;
 *   2. arquivo ".env" na raiz do projeto (NÃO vai para o Git);
 *   3. valor padrão informado no código (mantém o projeto funcionando
 *      sem .env, com as configurações padrão do ambiente de desenvolvimento).
 *
 * Para configurar o seu ambiente: copie ".env.example" para ".env" e ajuste.
 */

if (!function_exists('gremio_env')) {
    function gremio_env(string $chave, ?string $padrao = null): ?string
    {
        static $arquivo = null;

        if ($arquivo === null) {
            $arquivo = [];
            $caminho = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';

            if (is_file($caminho) && is_readable($caminho)) {
                foreach (file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linha) {
                    $linha = trim($linha);
                    if ($linha === '' || $linha[0] === '#' || strpos($linha, '=') === false) {
                        continue;
                    }
                    [$k, $v] = explode('=', $linha, 2);
                    $v = trim($v);
                    if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
                        $v = substr($v, 1, -1);
                    }
                    $arquivo[trim($k)] = $v;
                }
            }
        }

        $real = getenv($chave);
        if ($real !== false) {
            return $real;
        }

        return $arquivo[$chave] ?? $padrao;
    }
}

if (!function_exists('gremio_env_bool')) {
    function gremio_env_bool(string $chave, bool $padrao): bool
    {
        $v = gremio_env($chave);
        if ($v === null) {
            return $padrao;
        }
        return in_array(strtolower($v), ['1', 'true', 'sim', 'yes', 'on'], true);
    }
}
