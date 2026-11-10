<?php

require_once __DIR__ . '/../models/Notificacao.php';

/**
 * Camada fina entre os Controllers já validados (Publicações, Eventos,
 * Votações, Projetos, Solicitações de cadastro) e o model Notificacao.
 *
 * DECISÃO DE DESIGN (item 7 do escopo): a criação de notificação NUNCA
 * pode derrubar a operação principal que já foi validada e persistida
 * (ex.: se notificar falhar, o evento/publicação/projeto continua
 * criado normalmente). Por isso todo método aqui captura qualquer
 * Throwable internamente e apenas registra em error_log — o
 * controller que chama nunca precisa de try/catch para isso.
 */
class NotificacaoService
{
    private static function model(): Notificacao
    {
        static $instancia = null;
        if ($instancia === null) {
            $instancia = new Notificacao();
        }
        return $instancia;
    }

    private static function seguro(callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            error_log('[NotificacaoService] Falha ao gerar notificação: ' . $e->getMessage());
        }
    }

    // ==========================================================
    // CADASTRO
    // ==========================================================

    public static function notificarCadastroAprovado(int $usuarioId): void
    {
        self::seguro(function () use ($usuarioId) {
            self::model()->criarENotificar(
                'sistema',
                'Cadastro aprovado',
                'Seu cadastro foi aprovado. Agora você já pode acessar todas as áreas do sistema.',
                'usuarios',
                $usuarioId,
                null,
                [$usuarioId]
            );
        });
    }

    // ==========================================================
    // PUBLICAÇÕES
    // ==========================================================

    public static function notificarNovaPublicacao(int $autorId, int $publicacaoId, string $titulo): void
    {
        self::seguro(function () use ($autorId, $publicacaoId, $titulo) {
            $model = self::model();
            $destinatarios = $model->listarIdsUsuariosAtivos($autorId);

            $model->criarENotificar(
                'publicacao',
                'Nova publicação',
                "Nova publicação: \"{$titulo}\".",
                'publicacoes',
                $publicacaoId,
                $autorId,
                $destinatarios
            );
        });
    }

    // ==========================================================
    // EVENTOS
    // ==========================================================

    public static function notificarNovoEvento(int $criadorId, int $eventoId, string $titulo): void
    {
        self::seguro(function () use ($criadorId, $eventoId, $titulo) {
            $model = self::model();
            $destinatarios = $model->listarIdsUsuariosAtivos($criadorId);

            $model->criarENotificar(
                'evento',
                'Novo evento',
                "Novo evento criado: \"{$titulo}\".",
                'eventos',
                $eventoId,
                $criadorId,
                $destinatarios
            );
        });
    }

    /**
     * Avisa o organizador do evento (criado_por) que alguém novo se
     * inscreveu. Não notifica se o próprio organizador se inscrever
     * no próprio evento.
     */
    public static function notificarParticipacaoEvento(int $organizadorId, int $participanteId, int $eventoId, string $titulo): void
    {
        if ($organizadorId === $participanteId) {
            return;
        }

        self::seguro(function () use ($organizadorId, $participanteId, $eventoId, $titulo) {
            self::model()->criarENotificar(
                'evento',
                'Nova inscrição',
                "Uma nova pessoa se inscreveu no evento \"{$titulo}\".",
                'eventos',
                $eventoId,
                $participanteId,
                [$organizadorId]
            );
        });
    }

    /**
     * Avisa os inscritos quando um evento sofre alteração relevante
     * (data/hora, local ou cancelamento).
     */
    public static function notificarEventoAlterado(int $eventoId, string $titulo, string $motivo, ?int $atorId): void
    {
        self::seguro(function () use ($eventoId, $titulo, $motivo, $atorId) {
            $model = self::model();
            $destinatarios = $model->listarIdsInscritosEvento($eventoId, $atorId);

            if (empty($destinatarios)) {
                return;
            }

            $model->criarENotificar(
                'evento',
                'Evento atualizado',
                "O evento \"{$titulo}\" foi atualizado: {$motivo}.",
                'eventos',
                $eventoId,
                $atorId,
                $destinatarios
            );
        });
    }

    // ==========================================================
    // VOTAÇÕES
    // ==========================================================

    public static function notificarNovaVotacao(int $criadorId, int $votacaoId, string $titulo): void
    {
        self::seguro(function () use ($criadorId, $votacaoId, $titulo) {
            $model = self::model();
            $destinatarios = $model->listarIdsUsuariosAtivos($criadorId);

            $model->criarENotificar(
                'votacao',
                'Nova votação',
                "Nova votação aberta: \"{$titulo}\". Participe!",
                'votacoes',
                $votacaoId,
                $criadorId,
                $destinatarios
            );
        });
    }

    /**
     * Avisa quem votou que a votação foi encerrada (para ver o
     * resultado). Se ninguém votou, não gera notificação.
     */
    public static function notificarVotacaoEncerrada(int $votacaoId, string $titulo, ?int $atorId): void
    {
        self::seguro(function () use ($votacaoId, $titulo, $atorId) {
            $model = self::model();
            $destinatarios = $model->listarIdsVotantes($votacaoId);

            if (empty($destinatarios)) {
                return;
            }

            $model->criarENotificar(
                'votacao',
                'Votação encerrada',
                "A votação \"{$titulo}\" foi encerrada. Confira o resultado.",
                'votacoes',
                $votacaoId,
                $atorId,
                $destinatarios
            );
        });
    }

    // ==========================================================
    // PROJETOS
    // ==========================================================

    public static function notificarNovoProjeto(int $criadorId, int $projetoId, string $nome): void
    {
        self::seguro(function () use ($criadorId, $projetoId, $nome) {
            $model = self::model();
            $destinatarios = $model->listarIdsUsuariosAtivos($criadorId);

            $model->criarENotificar(
                'projeto',
                'Novo projeto',
                "Novo projeto criado: \"{$nome}\".",
                'projetos',
                $projetoId,
                $criadorId,
                $destinatarios
            );
        });
    }

    public static function notificarMembroAdicionadoProjeto(int $usuarioId, int $projetoId, string $nomeProjeto, ?int $atorId): void
    {
        self::seguro(function () use ($usuarioId, $projetoId, $nomeProjeto, $atorId) {
            self::model()->criarENotificar(
                'projeto',
                'Adicionado a um projeto',
                "Você foi adicionado ao projeto \"{$nomeProjeto}\".",
                'projetos',
                $projetoId,
                $atorId,
                [$usuarioId]
            );
        });
    }

    /**
     * Avisa os membros do projeto sobre alteração relevante (ex.:
     * mudança de status).
     */
    public static function notificarProjetoAlterado(int $projetoId, string $nome, string $motivo, ?int $atorId): void
    {
        self::seguro(function () use ($projetoId, $nome, $motivo, $atorId) {
            $model = self::model();
            $destinatarios = $model->listarIdsMembrosProjeto($projetoId, $atorId);

            if (empty($destinatarios)) {
                return;
            }

            $model->criarENotificar(
                'projeto',
                'Projeto atualizado',
                "O projeto \"{$nome}\" foi atualizado: {$motivo}.",
                'projetos',
                $projetoId,
                $atorId,
                $destinatarios
            );
        });
    }
}
