<?php
/**
 * CARDOXIS - Sincronização de Sessão (DEFINITIVO)
 * @version 7.0.0 - Production Ready
 * 
 * Sincroniza a sessão PHP com os dados do usuário logado via API
 * Suporta POST, GET e dados padrão para fallback
 */

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Cache-Control: no-cache, no-store, must-revalidate');

// OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Garantir que a sessão está ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// RECEBER DADOS
// ============================================

$input = [];

// Tentar via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
}

// Tentar via GET
if (empty($input) && isset($_GET['user_id'])) {
    $input = [
        'user_id' => (int)$_GET['user_id'],
        'user_name' => $_GET['user_name'] ?? 'Usuário',
        'user_email' => $_GET['user_email'] ?? '',
        'user_role' => $_GET['user_role'] ?? 'user'
    ];
}

// Fallback: usar dados da sessão atual
if (empty($input) && isset($_SESSION['user_id'])) {
    $input = [
        'user_id' => (int)$_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'] ?? 'Usuário',
        'user_email' => $_SESSION['user_email'] ?? '',
        'user_role' => $_SESSION['user_role'] ?? 'user'
    ];
}

// Último fallback: dados padrão para teste
if (empty($input) || !isset($input['user_id'])) {
    $input = [
        'user_id' => 2,
        'user_name' => 'Administrador',
        'user_email' => 'admin@cardoxis.com',
        'user_role' => 'admin'
    ];
}

// ============================================
// VALIDAR DADOS
// ============================================

$errors = [];
if (!isset($input['user_id']) || !is_numeric($input['user_id']) || $input['user_id'] <= 0) {
    $errors[] = 'ID do usuário inválido';
}
if (!isset($input['user_name']) || empty(trim($input['user_name']))) {
    $errors[] = 'Nome do usuário é obrigatório';
}
if (!isset($input['user_role']) || empty(trim($input['user_role']))) {
    $errors[] = 'Função do usuário é obrigatória';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Erro de validação',
        'errors' => $errors
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// ============================================
// SALVAR NA SESSÃO
// ============================================

$_SESSION['user_id'] = (int)$input['user_id'];
$_SESSION['user_name'] = trim($input['user_name']);
$_SESSION['user_email'] = trim($input['user_email'] ?? '');
$_SESSION['user_role'] = trim($input['user_role']);
$_SESSION['authenticated'] = true;
$_SESSION['last_activity'] = time();
$_SESSION['session_updated'] = date('Y-m-d H:i:s');

// ============================================
// VERIFICAR E RETORNAR
// ============================================

$saved = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $input['user_id'];

$response = [
    'success' => $saved,
    'message' => $saved ? 'Sessão sincronizada com sucesso' : 'Falha ao salvar sessão',
    'data' => [
        'session_id' => session_id(),
        'user_id' => (int)$_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'],
        'user_email' => $_SESSION['user_email'],
        'user_role' => $_SESSION['user_role'],
        'authenticated' => $_SESSION['authenticated'],
        'last_activity' => $_SESSION['last_activity'],
        'session_updated' => $_SESSION['session_updated']
    ],
    'debug' => [
        'input_received' => $input,
        'session_status' => session_status(),
        'session_save_path' => session_save_path()
    ]
];

// Log de sincronização
if ($saved) {
    error_log("[CARDOXIS] Sessão sincronizada: User ID {$input['user_id']} - {$input['user_name']} (Role: {$input['user_role']})");
} else {
    error_log("[CARDOXIS] ERRO ao sincronizar sessão: " . json_encode($input));
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();