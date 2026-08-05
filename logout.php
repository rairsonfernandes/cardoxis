<?php
/**
 * CARDOXIS - Logout
 * @version 2.0.0
 */

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

// Destruir cookie customizado
setcookie('cardoxis_auth', '', time() - 42000, '/');
setcookie('PHPSESSID', '', time() - 42000, '/');

// Destruir a sessão
session_destroy();

// Para requisição AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['success' => true, 'message' => 'Logout realizado com sucesso']);
    exit();
}

// Para requisição GET (navegador)
header('Location: /cardoxis/login');
exit();