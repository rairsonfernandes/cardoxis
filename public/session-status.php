<?php
/**
 * CARDOXIS - Status da Sessão
 * @version 2.0.0
 */

// Garantir que a sessão está ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar se a sessão tem dados
$response = [
    'success' => true,
    'session_id' => session_id(),
    'session_status' => session_status(),
    'has_session' => isset($_SESSION),
    'authenticated' => false,
    'user_id' => null,
    'user_name' => null,
    'user_email' => null,
    'user_role' => null,
    'last_activity' => null,
    'session_data' => [],
    'cookies' => $_COOKIE
];

// Verificar se o usuário está autenticado
if (isset($_SESSION['user_id']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $response['authenticated'] = true;
    $response['user_id'] = $_SESSION['user_id'];
    $response['user_name'] = $_SESSION['user_name'] ?? null;
    $response['user_email'] = $_SESSION['user_email'] ?? null;
    $response['user_role'] = $_SESSION['user_role'] ?? null;
    $response['last_activity'] = $_SESSION['last_activity'] ?? null;
    
    // Remover dados sensíveis para o response
    $sessionData = $_SESSION;
    unset($sessionData['password']);
    $response['session_data'] = $sessionData;
}

// Verificar arquivo de sessão
$sessionFile = session_save_path() . '/sess_' . session_id();
if (file_exists($sessionFile)) {
    $response['session_file_exists'] = true;
    $response['session_file_size'] = filesize($sessionFile);
    $response['session_file_path'] = $sessionFile;
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);