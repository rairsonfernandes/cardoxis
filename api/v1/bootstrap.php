<?php
/**
 * API Bootstrap - Initialize all services
 */

// Error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Exception handler
set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal Server Error',
        'message' => APP_DEBUG ? $exception->getMessage() : 'An error occurred',
        'code' => $exception->getCode()
    ]);
    exit;
});

// CORS headers
header('Access-Control-Allow-Origin: ' . CORS_ALLOWED_ORIGINS);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Content type
header('Content-Type: application/json');

// Load database connection
require_once __DIR__ . '/database/Connection.php';

// Load all helpers
foreach (glob(__DIR__ . '/helpers/*.php') as $file) {
    require_once $file;
}

// Load all traits
foreach (glob(__DIR__ . '/traits/*.php') as $file) {
    require_once $file;
}

// Load all models
foreach (glob(__DIR__ . '/models/*.php') as $file) {
    require_once $file;
}

// Load all services
foreach (glob(__DIR__ . '/services/*.php') as $file) {
    require_once $file;
}

// Load middleware
foreach (glob(__DIR__ . '/middleware/*.php') as $file) {
    require_once $file;
}

// Initialize database connection
$db = DatabaseConnection::getInstance()->getConnection();

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}