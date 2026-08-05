<?php
/**
 * CARDOXIS - API Router Professional  RF
 */

// ============================================
// CONFIGURAÇÃO DE ERROS - NÍVEL EMPRESARIAL
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Definir arquivo de log específico para a API
$apiLogFile = __DIR__ . '/logs/api_errors.log';
ini_set('error_log', $apiLogFile);

// ============================================
// CRIAÇÃO DO DIRETÓRIO DE LOGS
// ============================================

if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}

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
    file_put_contents(__DIR__ . '/logs/errors.log', $logEntry . PHP_EOL, FILE_APPEND);
    
    // Não interromper a execução para erros não-fatais
    return false;
});

// ============================================
// HANDLER DE EXCEÇÕES PERSONALIZADO
// ============================================

set_exception_handler(function($exception) {
    $logEntry = sprintf(
        "[%s] Exception: %s in %s on line %d\nStack trace:\n%s",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    file_put_contents(__DIR__ . '/logs/errors.log', $logEntry . PHP_EOL, FILE_APPEND);
    
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
        file_put_contents(__DIR__ . '/logs/errors.log', $logEntry . PHP_EOL, FILE_APPEND);
        
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
// HEADERS CORS
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// ============================================
// RESPOSTA PARA PREFLIGHT (OPTIONS)
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// AUTOLOADER
// ============================================

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/controllers/',
        __DIR__ . '/models/',
        __DIR__ . '/config/',
        __DIR__ . '/middleware/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once __DIR__ . '/config/Database.php';

// ============================================
// CARREGAR ROTAS
// ============================================

$routes = require_once __DIR__ . '/routes/api.php';
if (file_exists(__DIR__ . '/routes/admin.php')) {
    $adminRoutes = require_once __DIR__ . '/routes/admin.php';
    $routes = array_merge($routes, $adminRoutes);
}

// ============================================
// PREPARAR REQUISIÇÃO
// ============================================

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$uri = str_replace(['/cardoxis/api/v1', '/api/v1'], '', $uri);
$uri = parse_url($uri, PHP_URL_PATH);
$uri = trim($uri, '/');

$routeKey = $method . ' /' . ($uri === '' ? '' : $uri);

// ============================================
// OBTER CORPO DA REQUISIÇÃO
// ============================================

$rawInput = file_get_contents('php://input');
$input = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $input = $decoded;
    }
}

// ============================================
// LOG DA REQUISIÇÃO (APENAS EM DESENVOLVIMENTO)
// ============================================

if (isset($_SERVER['HTTP_X_DEBUG']) && $_SERVER['HTTP_X_DEBUG'] === 'true') {
    error_log("[API] Request: $method $uri");
    error_log("[API] Input: " . json_encode($input));
}

// ============================================
// BUSCAR E EXECUTAR ROTA
// ============================================

$response = null;
$routeFound = false;

foreach ($routes as $pattern => $config) {
    if ($config['method'] !== $method) {
        continue;
    }
    
    $regexPattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
    $regexPattern = '#^' . $regexPattern . '$#';
    
    if (preg_match($regexPattern, $routeKey, $matches)) {
        array_shift($matches);
        $routeFound = true;
        
        $controllerName = $config['controller'];
        $actionName = $config['action'];
        
        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            
            if (method_exists($controller, $actionName)) {
                $callParams = array_merge($matches, [$input, $_GET]);
                
                // Log da execução
                if (isset($_SERVER['HTTP_X_DEBUG']) && $_SERVER['HTTP_X_DEBUG'] === 'true') {
                    error_log("[API] Controller: $controllerName, Action: $actionName");
                    error_log("[API] Matches: " . json_encode($matches));
                }
                
                $response = call_user_func_array([$controller, $actionName], $callParams);
            } else {
                http_response_code(500);
                $response = [
                    'success' => false,
                    'error' => 'Method Not Found',
                    'message' => "Action '{$actionName}' not found in '{$controllerName}'"
                ];
            }
        } else {
            http_response_code(500);
            $response = [
                'success' => false,
                'error' => 'Controller Not Found',
                'message' => "Controller '{$controllerName}' not found"
            ];
        }
        break;
    }
}

if (!$routeFound) {
    http_response_code(404);
    $response = [
        'success' => false,
        'error' => 'Route Not Found',
        'message' => "No route found for {$routeKey}"
    ];
}

// ============================================
// GARANTIR TIMESTAMP NA RESPOSTA
// ============================================

if (!isset($response['timestamp'])) {
    $response['timestamp'] = date('Y-m-d H:i:s');
}

// ============================================
// LOG DE ERROS NA RESPOSTA
// ============================================

if (isset($response['success']) && $response['success'] === false) {
    error_log("[API] Error Response: " . json_encode($response));
}

// ============================================
// ENVIAR RESPOSTA
// ============================================

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);