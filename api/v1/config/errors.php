<?php
/**
 * CARDOXIS - Configuração Centralizada de Erros RF 
 */

// ============================================
// CONFIGURAÇÃO DE ERROS
// ============================================

// Ativar relatório de erros
error_reporting(E_ALL);

// Desativar exibição de erros (produção)
ini_set('display_errors', 0);

// Ativar log de erros
ini_set('log_errors', 1);

// Definir arquivo de log específico para a API
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

// ============================================
// DIRETÓRIO DE LOGS
// ============================================

$logDir = __DIR__ . '/../logs/';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// ============================================
// ARQUIVOS DE LOG
// ============================================

define('LOG_API_ERRORS', $logDir . 'api_errors.log');
define('LOG_ERRORS', $logDir . 'errors.log');
define('LOG_SECURITY', $logDir . 'security.log');
define('LOG_DATABASE', $logDir . 'database.log');
define('LOG_ACTIVITY', $logDir . 'activity.log');
define('LOG_AUDIT', $logDir . 'audit.log');

// ============================================
// HANDLER DE ERROS PERSONALIZADO
// ============================================

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $logEntry = sprintf(
        "[%s] Error [%d]: %s in %s on line %d",
        date('Y-m-d H:i:s'),
        $errno,
        $errstr,
        $errfile,
        $errline
    );
    file_put_contents(LOG_ERRORS, $logEntry . PHP_EOL, FILE_APPEND);
    
    // Para erros fatais, registrar também no log da API
    if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        file_put_contents(LOG_API_ERRORS, $logEntry . PHP_EOL, FILE_APPEND);
    }
    
    return false;
});

// ============================================
// HANDLER DE EXCEÇÕES PERSONALIZADO
// ============================================

set_exception_handler(function($exception) {
    $logEntry = sprintf(
        "[%s] Exception: %s in %s on line %d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    file_put_contents(LOG_ERRORS, $logEntry . PHP_EOL, FILE_APPEND);
    file_put_contents(LOG_API_ERRORS, $logEntry . PHP_EOL, FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal Server Error',
        'message' => 'Ocorreu um erro no servidor',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
});

// ============================================
// HANDLER DE SHUTDOWN (ERROS FATAIS)
// ============================================

register_shutdown_function(function() {
    $lastError = error_get_last();
    if ($lastError && in_array($lastError['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $logEntry = sprintf(
            "[%s] Fatal Error: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $lastError['message'],
            $lastError['file'],
            $lastError['line']
        );
        file_put_contents(LOG_ERRORS, $logEntry . PHP_EOL, FILE_APPEND);
        file_put_contents(LOG_API_ERRORS, $logEntry . PHP_EOL, FILE_APPEND);
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Fatal Error',
            'message' => 'Ocorreu um erro fatal no servidor',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
});

// ============================================
// FUNÇÃO DE LOG PERSONALIZADA
// ============================================

function logSystem($message, $type = 'info', $file = null) {
    $logFile = $file ?: LOG_ERRORS;
    $logEntry = sprintf(
        "[%s] [%s] %s",
        date('Y-m-d H:i:s'),
        strtoupper($type),
        $message
    );
    file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);
}

// ============================================
// FUNÇÃO DE LOG DE SEGURANÇA
// ============================================

function logSecurity($userId, $event, $description) {
    $logEntry = sprintf(
        "[%s] User: %s | Event: %s | %s | IP: %s",
        date('Y-m-d H:i:s'),
        $userId ?: 'anonymous',
        $event,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    );
    file_put_contents(LOG_SECURITY, $logEntry . PHP_EOL, FILE_APPEND);
}