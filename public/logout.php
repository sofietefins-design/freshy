<?php
/**
 * Freshy — Encerramento Seguro de Sessão (Logout)
 */

require_once __DIR__ . '/../app/config/config.php';

// Limpa array de sessão
$_SESSION = [];

// Invalida o cookie de sessão no navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destrói a sessão
session_destroy();

// Inicia uma sessão temporária limpa apenas para exibir mensagem de despedida
session_start();
flash('info', 'Você saiu da sua conta com segurança. Até logo!');

redirect('login.php');
