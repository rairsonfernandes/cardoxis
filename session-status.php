<?php
/**
 * CARDOXIS - Status da Sessão
 * @version 2.0.0
 * 
 * Verifica o status atual da sessão PHP
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = [
    'success' => true,
    'session_status' => session_status(),
    'session_id' => session_id(),
    'session_name' => session_name(),
    'session_save_path' => session_save_path(),
    'session_data' => [
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_name' => $_SESSION['user_name'] ?? null,
        'user_email' => $_SESSION['user_email'] ?? null,
        'user_role' => $_SESSION['user_role'] ?? null,
        'authenticated' => $_SESSION['authenticated'] ?? false,
        'last_activity' => $_SESSION['last_activity'] ?? null,
        'session_updated' => $_SESSION['session_updated'] ?? null
    ],
    'cookies' => $_COOKIE,
    'server' => [
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();