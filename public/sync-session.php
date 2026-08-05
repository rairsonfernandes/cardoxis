<?php
/**
 * CARDOXIS - Sincronização de Sessão
 * @version 5.0.0
 */

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Iniciar sessão com configurações explícitas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se a sessão está ativa
if (!session_id()) {
    session_start();
}

// Receber dados
$input = [];
$raw = file_get_contents('php://input');
if (!empty($raw)) {
    $input = json_decode($raw, true) ?? [];
}

// Fallback para GET
if (empty($input) && isset($_GET['user_id'])) {
    $input = [
        'user_id' => (int)$_GET['user_id'],
        'user_name' => $_GET['user_name'] ?? 'Usuário',
        'user_email' => $_GET['user_email'] ?? '',
        'user_role' => $_GET['user_role'] ?? 'user'
    ];
}

// Validar dados
if (empty($input) || !isset($input['user_id']) || !isset($input['user_name']) || !isset($input['user_role'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Dados incompletos. Envie: user_id, user_name, user_role',
        'received' => $input
    ]);
    exit();
}

// SALVAR DIRETAMENTE NA SESSÃO (SEM session_regenerate_id)
$_SESSION['user_id'] = (int)$input['user_id'];
$_SESSION['user_name'] = $input['user_name'];
$_SESSION['user_email'] = $input['user_email'] ?? '';
$_SESSION['user_role'] = $input['user_role'];
$_SESSION['authenticated'] = true;
$_SESSION['last_activity'] = time();

// Forçar gravação da sessão
session_write_close();

// Reabrir para verificar
session_start();

// Verificar se foi salvo
$saved = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $input['user_id'];

// Resposta
echo json_encode([
    'success' => $saved,
    'message' => $saved ? 'Sessão sincronizada com sucesso' : 'Falha ao salvar sessão',
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? null,
    'user_name' => $_SESSION['user_name'] ?? null,
    'user_role' => $_SESSION['user_role'] ?? null,
    'authenticated' => $_SESSION['authenticated'] ?? false,
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);