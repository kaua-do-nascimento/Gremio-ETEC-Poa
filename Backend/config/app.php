<?php

require_once __DIR__ . '/env.php';

/**
 * Configurações gerais da aplicação.
 *
 * exibir_link_recuperacao
 *   O servidor local (WAMP) normalmente NÃO envia e-mail. Com este valor em
 *   true, a API devolve o link de redefinição de senha na própria resposta
 *   (modo demonstração/apresentação). Em produção, com envio de e-mail
 *   configurado no PHP (mail()/SMTP), mude para false: o link passa a ser
 *   enviado apenas por e-mail.
 *
 * recuperacao_validade_minutos
 *   Tempo de validade do link de redefinição.
 */
return [
    // Em produção (APP_ENV=production) o link NUNCA é exibido na resposta da API.
    'exibir_link_recuperacao' => gremio_env('APP_ENV', 'local') !== 'production'
        && gremio_env_bool('APP_EXIBIR_LINK_RECUPERACAO', true),
    'recuperacao_validade_minutos' => 60,
    'recuperacao_max_pedidos_por_hora' => 5,
    'email_remetente' => gremio_env('MAIL_REMETENTE', 'no-reply@gremio-etec-poa.local'),
];
