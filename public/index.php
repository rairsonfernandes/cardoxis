<?php
/**
 * CARDOXIS - Front Controller RF
 */

// 1. CONFIGURAÇÕES INICIAIS

// Configurar ambiente
$environment = getenv('APP_ENV') ?: 'production';
$isDevelopment = $environment === 'development';

// Configurar relatório de erros
if ($isDevelopment) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configurar logs
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php-errors.log');

// Definir constantes globais
define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', '/cardoxis');
define('ENVIRONMENT', $environment);
define('IS_DEVELOPMENT', $isDevelopment);

// ============================================
// 2. SESSÃO - CONFIGURAÇÕES DE SEGURANÇA
// ============================================

// Configurar segurança da sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', IS_DEVELOPMENT ? 0 : 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 86400); // 24 horas

// Iniciar sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerar ID da sessão periodicamente
if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
} elseif (time() - $_SESSION['created_at'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created_at'] = time();
}

// ============================================
// 3. FUNÇÕES HELPER
// ============================================

if (!function_exists('asset')) {
    function asset($path) {
        return BASE_URL . '/public/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('isAuthenticated')) {
    function isAuthenticated() {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['authenticated']) && 
               $_SESSION['authenticated'] === true;
    }
}

if (!function_exists('requireAuth')) {
    function requireAuth() {
        if (!isAuthenticated()) {
            header('Location: ' . url('login?expired=1'));
            exit;
        }
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user_role']) && 
               in_array($_SESSION['user_role'], ['super_admin', 'admin']);
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        if (!isAuthenticated() || !isAdmin()) {
            http_response_code(403);
            include ROOT_PATH . '/resources/views/errors/403.php';
            exit;
        }
    }
}

if (!function_exists('renderError')) {
    function renderError($code) {
        http_response_code($code);
        $errorFile = ROOT_PATH . '/resources/views/errors/' . $code . '.php';
        
        // Verificar se existe página de erro específica
        if (file_exists($errorFile)) {
            include $errorFile;
        } else {
            // Fallback para erro genérico
            echo "<h1>Erro " . $code . "</h1>";
            echo "<p>Ocorreu um erro inesperado.</p>";
        }
        exit;
    }
}

// ============================================
// 4. PROCESSAMENTO DA ROTA
// ============================================

// Limpar e normalizar a URL
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$path = str_replace(dirname($scriptName), '', $requestUri);
$path = trim($path, '/');

// Remover parâmetros da URL (tudo após ?)
if (strpos($path, '?') !== false) {
    $path = substr($path, 0, strpos($path, '?'));
}

// Remover base do caminho
$base = 'cardoxis';
if (strpos($path, $base) === 0) {
    $path = substr($path, strlen($base));
    $path = trim($path, '/');
}

// Rota padrão
if (empty($path)) {
    $path = 'landing';
}

$view = null;
$viewFound = false;

// ============================================
// 5. ROTAS DA APLICAÇÃO
// ============================================

// ============================================
// 5.1 ROTAS DE API
// ============================================
if (strpos($path, 'api/') === 0) {
    $apiFile = ROOT_PATH . '/api/v1/index.php';
    if (file_exists($apiFile)) {
        include $apiFile;
    } else {
        renderError(404);
    }
    exit;
}

// ============================================
// 5.2 ROTAS DE SESSÃO / AUTENTICAÇÃO
// ============================================
$sessionRoutes = [
    'logout', 'sync-session', 'session-status', 
    'test-session', 'debug-session', 'force-sync', 'fix-session'
];

if (in_array($path, $sessionRoutes)) {
    $file = ROOT_PATH . '/' . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
    } else {
        // Fallback: logout básico
        session_destroy();
        header('Location: ' . url('login?logout=1'));
    }
    exit;
}

// ============================================
// 5.3 ROTAS PÚBLICAS (Sem autenticação)
// ============================================
$publicRoutes = [
    'landing' => 'landing.php',
    'home' => 'landing.php',
    'login' => 'auth/login.php',
    'register' => 'auth/register.php',
    'forgot-password' => 'auth/forgot-password.php',
    'reset-password' => 'auth/reset-password.php',
    'verify-pin' => 'auth/verify-pin.php',
    'about' => 'about/index.php',
    'contact' => 'contact/index.php',
    'faq' => 'faq/index.php',
    'privacy' => 'privacy/index.php',
    'terms' => 'terms/index.php',
    'cookies' => 'cookies/index.php',
    'careers' => 'careers/index.php',
    'help' => 'help/index.php',
    'support' => 'support/index.php',
    'changelog' => 'changelog/index.php',
    'docs' => 'docs/index.php'
    
];

if (isset($publicRoutes[$path])) {
    $view = $publicRoutes[$path];
    $viewFound = true;
}

// ============================================
// 5.4 ROTAS PROTEGIDAS - DASHBOARD
// ============================================
if ($path === 'dashboard') {
    requireAuth();
    $view = 'dashboard/index.php';
    $viewFound = true;
}

// ============================================
// 5.5 ROTAS PROTEGIDAS - VEÍCULOS
// ============================================
$vehicleRoutes = ['vehicles', 'vehicles/index', 'vehicles/create', 'vehicles/edit', 'vehicles/show'];
if (in_array($path, $vehicleRoutes)) {
    requireAuth();
    $viewMap = [
        'vehicles' => 'vehicles/index.php',
        'vehicles/index' => 'vehicles/index.php',
        'vehicles/create' => 'vehicles/create.php',
        'vehicles/edit' => 'vehicles/edit.php',
        'vehicles/show' => 'vehicles/show.php'
    ];
    $view = $viewMap[$path] ?? 'vehicles/index.php';
    $viewFound = true;
}

// ============================================
// 5.6 ROTAS PROTEGIDAS - MOTORISTAS
// ============================================
if (in_array($path, ['drivers', 'drivers/index'])) {
    requireAuth();
    $view = 'drivers/index.php';
    $viewFound = true;
}

// ============================================
// 5.7 ROTAS PROTEGIDAS - COMBUSTÍVEL
// ============================================
if (in_array($path, ['fuel', 'fuel/index'])) {
    requireAuth();
    $view = 'fuel/index.php';
    $viewFound = true;
}

// ============================================
// 5.8 ROTAS PROTEGIDAS - SEGUROS
// ============================================
if (in_array($path, ['insurance', 'insurance/index'])) {
    requireAuth();
    $view = 'insurance/index.php';
    $viewFound = true;
}

// ============================================
// 5.9 ROTAS PROTEGIDAS - MULTAS
// ============================================
if (in_array($path, ['fines', 'fines/index'])) {
    requireAuth();
    $view = 'fines/index.php';
    $viewFound = true;
}

// ============================================
// 5.10 ROTAS PROTEGIDAS - DOCUMENTOS
// ============================================
if ($path === 'documents') {
    requireAuth();
    $view = 'documents/index.php';
    $viewFound = true;
}

if ($path === 'documents/upload') {
    requireAuth();
    $view = 'documents/upload.php';
    $viewFound = true;
}

// ============================================
// 5.11 ROTAS PROTEGIDAS - MANUTENÇÕES
// ============================================
if (in_array($path, ['maintenance', 'maintenance/index'])) {
    requireAuth();
    $view = 'maintenance/index.php';
    $viewFound = true;
}

if ($path === 'maintenance/create') {
    requireAuth();
    $view = 'maintenance/create.php';
    $viewFound = true;
}

// ============================================
// 5.12 ROTAS PROTEGIDAS - ALERTAS
// ============================================
if ($path === 'alerts') {
    requireAuth();
    $view = 'alerts/index.php';
    $viewFound = true;
}

if ($path === 'alerts/settings') {
    requireAuth();
    $view = 'alerts/settings.php';
    $viewFound = true;
}

// ============================================
// 5.13 ROTAS PROTEGIDAS - NOTIFICAÇÕES
// ============================================
if ($path === 'notifications') {
    requireAuth();
    $view = 'notifications/index.php';
    $viewFound = true;
}

// ============================================
// 5.14 ROTAS PROTEGIDAS - RELATÓRIOS
// ============================================
if (in_array($path, ['reports', 'reports/index'])) {
    requireAuth();
    $view = 'reports/index.php';
    $viewFound = true;
}

if ($path === 'reports/generate') {
    requireAuth();
    $view = 'reports/generate.php';
    $viewFound = true;
}

// ============================================
// 5.15 ROTAS PROTEGIDAS - PERFIL
// ============================================
if ($path === 'profile') {
    requireAuth();
    $view = 'profile/index.php';
    $viewFound = true;
}

if ($path === 'profile/edit') {
    requireAuth();
    $view = 'profile/edit.php';
    $viewFound = true;
}

if ($path === 'profile/security') {
    requireAuth();
    $view = 'profile/security.php';
    $viewFound = true;
}


// ============================================
// 5.15 ROTAS PROTEGIDAS - chat
// ============================================
if (in_array($path, ['chat', 'chat/index'])) {
    requireAuth();
    $view = 'chat/index.php';
    $viewFound = true;
}

// ============================================
// 5.16 ROTAS PROTEGIDAS - ADMIN
// ============================================
$adminRoutes = [
    'admin', 'admin/dashboard', 'admin/companies', 'admin/users',
    'admin/plans', 'admin/subscriptions', 'admin/payments',
    'admin/logs', 'admin/system', 'admin/settings'
];

if (in_array($path, $adminRoutes)) {
    requireAdmin();
    $page = str_replace('admin/', '', $path);
    if ($page === 'admin' || $page === 'dashboard') {
        $view = 'admin/dashboard.php';
    } else {
        $view = 'admin/' . $page . '/index.php';
    }
    $viewFound = true;
}

// ============================================
// 5.17 ROTA DE CONTATO (API)
// ============================================
if ($path === 'contact/send') {
    header('Location: ' . url('api/v1/contact/send'));
    exit;
}

// ============================================
// 6. FALLBACK - 404
// ============================================

if (!$viewFound) {
    $viewFile = ROOT_PATH . '/resources/views/' . $path . '.php';
    if (file_exists($viewFile)) {
        $view = $path . '.php';
        $viewFound = true;
    }
}

// ============================================
// 7. INCLUIR A VIEW
// ============================================

if ($viewFound && isset($view)) {
    $fullViewPath = ROOT_PATH . '/resources/views/' . $view;
    if (file_exists($fullViewPath)) {
        include $fullViewPath;
    } else {
        renderError(404);
    }
} else {
    renderError(404);
}

// ============================================
// 8. LOG DE ACESSO (OPCIONAL)
// ============================================

// Registrar acesso em ambiente de desenvolvimento
if (IS_DEVELOPMENT) {
    $log = date('Y-m-d H:i:s') . ' - ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . "\n";
    @file_put_contents(ROOT_PATH . '/storage/logs/access.log', $log, FILE_APPEND);
}