<?php
/**
 * CARDOXIS - Logout RF
 */

// Headers para evitar cache
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruir todos os cookies relacionados
setcookie('cardoxis_auth', '', time() - 42000, '/');
setcookie('PHPSESSID', '', time() - 42000, '/');
setcookie('user_data', '', time() - 42000, '/');
setcookie('auth_token', '', time() - 42000, '/');
setcookie('remember_token', '', time() - 42000, '/');

// Destruir a sessão
session_destroy();

// Redirecionar para login com parâmetro de logout
header('Location: /cardoxis/login?logout=1');
exit();