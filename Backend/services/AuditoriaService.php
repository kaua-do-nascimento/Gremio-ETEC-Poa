<?php

require_once __DIR__ . '/../models/Log.php';

/**
 * Camada fina entre os Controllers existentes e o model Log.
 *
 * Mesmo princípio já usado no NotificacaoService (Módulo 5): registrar
 * um log NUNCA pode derrubar a operação principal que já foi validada
 * e persistida. Qualquer falha aqui é apenas registrada via
 * error_log(), nunca propagada.
 */
class AuditoriaService
{
    private static function model(): Log
    {
        static $instancia = null;
        if ($instancia === null) {
            $instancia = new Log();
        }
        return $instancia;
    }

    private static function seguro(callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            error_log('[AuditoriaService] Falha ao registrar log: ' . $e->getMessage());
        }
    }

    /**
     * Registro genérico de auditoria.
     *
     * @param int|null $usuarioId Quem realizou a ação (null quando não aplicável/anônimo).
     * @param string $acao Ex.: 'login', 'criacao', 'atualizacao', 'exclusao'.
     * @param string $modulo Ex.: 'autenticacao', 'usuarios', 'publicacoes', 'eventos',
     *                        'votacoes', 'projetos'.
     * @param string|null $descricao Resumo legível. NUNCA deve conter senha, hash de
     *                        senha, token de sessão ou a opção escolhida num voto.
     */
    public static function log(?int $usuarioId, string $acao, string $modulo, ?string $descricao = null): void
    {
        self::seguro(function () use ($usuarioId, $acao, $modulo, $descricao) {
            self::model()->registrar($usuarioId, $acao, $modulo, $descricao);
        });
    }

    public static function loginSucesso(int $usuarioId): void
    {
        self::seguro(function () use ($usuarioId) {
            self::model()->registrarLoginSucesso($usuarioId);
        });
    }

    public static function loginFalha(?int $usuarioId, string $motivo): void
    {
        self::seguro(function () use ($usuarioId, $motivo) {
            self::model()->registrarLoginFalha($usuarioId, $motivo);
        });
    }
}
