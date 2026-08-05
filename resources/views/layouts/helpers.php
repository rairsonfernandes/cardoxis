<?php
/**
 * CARDOXIS - Funções Auxiliares RF
 */

// FUNÇÕES DE URL E ASSETS

if (!function_exists('asset')) {
    /**
     * Gera o caminho completo para um asset (CSS, JS, imagens, etc.)
     * asset('css/landing/landing.css')
     * // Retorna: /cardoxis/public/assets/css/landing/landing.css
     */
    function asset($path) {
        return '/cardoxis/public/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Gera uma URL completa para a aplicação
     * url('login')
     * // Retorna: /cardoxis/login
     */
    function url($path = '') {
        return '/cardoxis/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    /**
     * Redireciona o utilizador para uma URL específica
     */
    function redirect($path, $status = 302) {
        header('Location: ' . url($path), true, $status);
        exit;
    }
}

if (!function_exists('back')) {
    /**
     * Redireciona o utilizador para a página anterior
     */
    function back() {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('');
        header('Location: ' . $referer);
        exit;
    }
}

// FUNÇÕES DE NAVEGAÇÃO

if (!function_exists('isActive')) {
    /**
     * Verifica se um caminho está ativo e retorna a classe CSS
     * isActive('dashboard')
     * // Retorna: 'active' se estiver na página do dashboard
     */
    function isActive($path, $class = 'active') {
        $currentPath = trim($_SERVER['REQUEST_URI'], '/');
        $targetPath = trim($path, '/');
        
        // Para o caminho raiz
        if ($targetPath === '') {
            return $currentPath === '' ? $class : '';
        }
        
        // Para caminhos específicos
        return strpos($currentPath, $targetPath) === 0 ? $class : '';
    }
}

if (!function_exists('isExactActive')) {
    /**
     * Verifica se o caminho atual corresponde exatamente
     */
    function isExactActive($path, $class = 'active') {
        $currentPath = trim($_SERVER['REQUEST_URI'], '/');
        $targetPath = trim($path, '/');
        return $currentPath === $targetPath ? $class : '';
    }
}

// FUNÇÕES DE SESSÃO E AUTENTICAÇÃO

if (!function_exists('isLoggedIn')) {
    /**
     * Verifica se o utilizador está autenticado
     * 
     * @return bool True se autenticado, False caso contrário
     */
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['authenticated']);
    }
}

if (!function_exists('isAdmin')) {
    /**
     * Verifica se o utilizador tem permissões de administrador
     * 
     * @return bool True se for administrador, False caso contrário
     */
    function isAdmin() {
        return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['super_admin', 'admin']);
    }
}

if (!function_exists('isSuperAdmin')) {
    /**
     * Verifica se o utilizador é Super Administrador
     * 
     * @return bool True se for Super Admin, False caso contrário
     */
    function isSuperAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
    }
}

if (!function_exists('getCurrentUser')) {
    /**
     * Obtém os dados do utilizador atualmente autenticado
     * 
     * @param string|null $key Chave específica do array (opcional)
     * @return mixed Dados do utilizador ou null se não autenticado
     */
    function getCurrentUser($key = null) {
        if (!isLoggedIn()) {
            return null;
        }
        
        if (isset($_SESSION['user'])) {
            return $key ? ($_SESSION['user'][$key] ?? null) : $_SESSION['user'];
        }
        
        return null;
    }
}

if (!function_exists('hasPermission')) {
    /**
     * Verifica se o utilizador tem uma permissão específica
     * 
     * @param string $permission Nome da permissão
     * @return bool True se tem permissão, False caso contrário
     */
    function hasPermission($permission) {
        if (!isLoggedIn()) {
            return false;
        }
        
        // Administradores têm todas as permissões
        if (isAdmin()) {
            return true;
        }
        
        // Verificar permissões na sessão
        if (isset($_SESSION['permissions']) && in_array($permission, $_SESSION['permissions'])) {
            return true;
        }
        
        return false;
    }
}

// ============================================================
// FUNÇÕES DE FORMATAÇÃO DE DATA
// ============================================================

if (!function_exists('formatDate')) {
    /**
     * Formata uma data para o formato especificado
     * 
     * @param string|int|null $date Data a formatar (timestamp ou string)
     * @param string $format Formato de saída (padrão: 'd/m/Y H:i:s')
     * @return string Data formatada ou '-' se inválida
     * 
     * @example
     * formatDate('2024-12-25', 'd/m/Y')
     * // Retorna: '25/12/2024'
     */
    function formatDate($date, $format = 'd/m/Y H:i:s') {
        if (!$date) {
            return '-';
        }
        
        // Se for timestamp numérico
        if (is_numeric($date)) {
            return date($format, (int) $date);
        }
        
        return date($format, strtotime($date));
    }
}

if (!function_exists('formatDateShort')) {
    /**
     * Formata uma data no formato curto (ex: 25/12/2024)
     * 
     * @param string|int|null $date Data a formatar
     * @return string Data formatada no formato curto
     */
    function formatDateShort($date) {
        return formatDate($date, 'd/m/Y');
    }
}

if (!function_exists('formatDateTime')) {
    /**
     * Formata uma data e hora no formato PT (ex: 25 de Dezembro de 2024 às 14:30)
     * 
     * @param string|int|null $date Data a formatar
     * @return string Data e hora formatada
     */
    function formatDateTime($date) {
        if (!$date) {
            return '-';
        }
        
        $timestamp = is_numeric($date) ? (int) $date : strtotime($date);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        
        return date('d', $timestamp) . ' de ' . $months[(int) date('m', $timestamp) - 1] . ' de ' . date('Y', $timestamp) . ' às ' . date('H:i', $timestamp);
    }
}

if (!function_exists('timeAgo')) {
    /**
     * Converte uma data para formato "há X tempo"
     * 
     * @param string|int $date Data a converter
     * @return string Texto com o tempo decorrido
     * 
     * @example
     * timeAgo('2024-12-25 10:00:00')
     * // Retorna: 'há 2 horas' (se passaram 2 horas)
     */
    function timeAgo($date) {
        $timestamp = is_numeric($date) ? (int) $date : strtotime($date);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'há ' . $diff . ' segundos';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return 'há ' . $minutes . ' minutos';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return 'há ' . $hours . ' horas';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return 'há ' . $days . ' dias';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return 'há ' . $weeks . ' semanas';
        } else {
            $months = floor($diff / 2592000);
            return 'há ' . $months . ' meses';
        }
    }
}

// ============================================================
// FUNÇÕES DE FORMATAÇÃO DE NÚMEROS
// ============================================================

if (!function_exists('formatNumber')) {
    /**
     * Formata um número com separadores de milhares
     * 
     * @param mixed $number Número a formatar
     * @param int $decimals Número de casas decimais (padrão: 0)
     * @return string Número formatado
     * 
     * @example
     * formatNumber(1500)
     * // Retorna: '1.500'
     */
    function formatNumber($number, $decimals = 0) {
        if ($number === null || $number === '') {
            return '0';
        }
        return number_format((float) $number, $decimals, ',', '.');
    }
}

if (!function_exists('formatMoney')) {
    /**
     * Formata um valor monetário em euros (€)
     * 
     * @param float|int|null $value Valor a formatar
     * @param string $currency Símbolo da moeda (padrão: '€')
     * @return string Valor formatado com símbolo da moeda
     * 
     * @example
     * formatMoney(1250.50)
     * // Retorna: '€ 1.250,50'
     */
    function formatMoney($value, $currency = '€') {
        if ($value === null || $value === '') {
            return $currency . ' 0,00';
        }
        return $currency . ' ' . number_format((float) $value, 2, ',', '.');
    }
}

if (!function_exists('formatMoneyWithoutSymbol')) {
    /**
     * Formata um valor monetário sem símbolo da moeda
     * 
     * @param float|int|null $value Valor a formatar
     * @return string Valor formatado sem símbolo
     */
    function formatMoneyWithoutSymbol($value) {
        if ($value === null || $value === '') {
            return '0,00';
        }
        return number_format((float) $value, 2, ',', '.');
    }
}

if (!function_exists('formatPercent')) {
    /**
     * Formata um valor como percentagem
     * 
     * @param float $value Valor a formatar
     * @param int $decimals Número de casas decimais (padrão: 1)
     * @return string Valor formatado como percentagem
     */
    function formatPercent($value, $decimals = 1) {
        return number_format((float) $value, $decimals, ',', '.') . '%';
    }
}

// ============================================================
// FUNÇÕES DE TEXTOS E STRINGS
// ============================================================

if (!function_exists('truncate')) {
    /**
     * Trunca um texto para um comprimento específico
     * 
     * @param string $text Texto a truncar
     * @param int $length Comprimento máximo (padrão: 100)
     * @param string $suffix Sufixo a adicionar (padrão: '...')
     * @param bool $preserveWords Preservar palavras completas (padrão: true)
     * @return string Texto truncado
     * 
     * @example
     * truncate('Um texto muito longo...', 10)
     * // Retorna: 'Um texto...'
     */
    function truncate($text, $length = 100, $suffix = '...', $preserveWords = true) {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        if ($preserveWords) {
            $text = substr($text, 0, $length);
            $lastSpace = strrpos($text, ' ');
            if ($lastSpace !== false) {
                $text = substr($text, 0, $lastSpace);
            }
            return $text . $suffix;
        }
        
        return substr($text, 0, $length) . $suffix;
    }
}

if (!function_exists('slugify')) {
    /**
     * Converte uma string para formato slug (URL amigável)
     * 
     * @param string $text Texto a converter
     * @return string Slug gerado
     * 
     * @example
     * slugify('Gestão de Frotas CARDOXIS')
     * // Retorna: 'gestao-de-frotas-cardoxis'
     */
    function slugify($text) {
        // Remover acentos
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        
        // Substituir caracteres especiais por hífen
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        
        // Remover caracteres não permitidos
        $text = preg_replace('~[^-\w]+~', '', $text);
        
        // Remover hífens duplicados
        $text = preg_replace('~-+~', '-', $text);
        
        // Remover hífens no início e fim
        $text = trim($text, '-');
        
        // Converter para minúsculas
        $text = strtolower($text);
        
        // Se vazio, retornar 'n-a'
        if (empty($text)) {
            return 'n-a';
        }
        
        return $text;
    }
}

if (!function_exists('sanitizeInput')) {
    /**
     * Sanitiza uma string para output HTML seguro
     * 
     * @param string $text Texto a sanitizar
     * @return string Texto sanitizado
     */
    function sanitizeInput($text) {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('sanitizeArray')) {
    /**
     * Sanitiza todos os elementos de um array
     * 
     * @param array $array Array a sanitizar
     * @return array Array sanitizado
     */
    function sanitizeArray($array) {
        if (!is_array($array)) {
            return $array;
        }
        
        return array_map(function($item) {
            if (is_array($item)) {
                return sanitizeArray($item);
            }
            return sanitizeInput($item);
        }, $array);
    }
}

if (!function_exists('generateRandomString')) {
    /**
     * Gera uma string aleatória de um comprimento específico
     * 
     * @param int $length Comprimento da string (padrão: 10)
     * @param bool $includeSpecial Incluir caracteres especiais (padrão: false)
     * @return string String aleatória
     */
    function generateRandomString($length = 10, $includeSpecial = false) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        
        if ($includeSpecial) {
            $chars .= '!@#$%^&*()_+-=';
        }
        
        return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
    }
}

// ============================================================
// FUNÇÕES DE STATUS E BADGES
// ============================================================

if (!function_exists('getStatusBadge')) {
    /**
     * Retorna o HTML de um badge de status
     * 
     * @param string $status Código do status
     * @param string $extraClass Classes CSS adicionais
     * @return string HTML do badge
     * 
     * @example
     * getStatusBadge('active')
     * // Retorna: '<span class="status-badge status-success">Ativo</span>'
     */
    function getStatusBadge($status, $extraClass = '') {
        $badges = [
            'active' => ['class' => 'status-success', 'icon' => 'fa-check-circle', 'label' => 'Ativo'],
            'inactive' => ['class' => 'status-danger', 'icon' => 'fa-minus-circle', 'label' => 'Inativo'],
            'maintenance' => ['class' => 'status-warning', 'icon' => 'fa-tools', 'label' => 'Manutenção'],
            'pending' => ['class' => 'status-warning', 'icon' => 'fa-clock', 'label' => 'Pendente'],
            'completed' => ['class' => 'status-success', 'icon' => 'fa-check-circle', 'label' => 'Concluído'],
            'cancelled' => ['class' => 'status-danger', 'icon' => 'fa-times-circle', 'label' => 'Cancelado'],
            'processing' => ['class' => 'status-info', 'icon' => 'fa-spinner fa-spin', 'label' => 'Em Processamento'],
            'approved' => ['class' => 'status-success', 'icon' => 'fa-thumbs-up', 'label' => 'Aprovado'],
            'rejected' => ['class' => 'status-danger', 'icon' => 'fa-thumbs-down', 'label' => 'Rejeitado'],
            'on_hold' => ['class' => 'status-warning', 'icon' => 'fa-pause-circle', 'label' => 'Em Espera'],
            'new' => ['class' => 'status-info', 'icon' => 'fa-plus-circle', 'label' => 'Novo'],
            'paid' => ['class' => 'status-success', 'icon' => 'fa-money-bill', 'label' => 'Pago'],
            'unpaid' => ['class' => 'status-danger', 'icon' => 'fa-credit-card', 'label' => 'Não Pago'],
            'expired' => ['class' => 'status-danger', 'icon' => 'fa-clock', 'label' => 'Expirado'],
            'overdue' => ['class' => 'status-danger', 'icon' => 'fa-exclamation-triangle', 'label' => 'Vencido'],
            'draft' => ['class' => 'status-secondary', 'icon' => 'fa-pen', 'label' => 'Rascunho'],
            'published' => ['class' => 'status-success', 'icon' => 'fa-globe', 'label' => 'Publicado'],
            'archived' => ['class' => 'status-secondary', 'icon' => 'fa-archive', 'label' => 'Arquivado']
        ];
        
        $badge = $badges[$status] ?? ['class' => 'status-info', 'icon' => 'fa-info-circle', 'label' => ucfirst($status)];
        $extraClass = $extraClass ? ' ' . trim($extraClass) : '';
        
        return sprintf(
            '<span class="status-badge %s%s"><i class="fas %s"></i> %s</span>',
            $badge['class'],
            $extraClass,
            $badge['icon'],
            $badge['label']
        );
    }
}

if (!function_exists('getPriorityBadge')) {
    /**
     * Retorna o HTML de um badge de prioridade
     * 
     * @param string $priority Nível de prioridade
     * @return string HTML do badge
     */
    function getPriorityBadge($priority) {
        $badges = [
            'high' => ['class' => 'priority-high', 'label' => 'Alta'],
            'medium' => ['class' => 'priority-medium', 'label' => 'Média'],
            'low' => ['class' => 'priority-low', 'label' => 'Baixa'],
            'urgent' => ['class' => 'priority-urgent', 'label' => 'Urgente']
        ];
        
        $badge = $badges[$priority] ?? ['class' => 'priority-medium', 'label' => 'Média'];
        
        return '<span class="priority-badge ' . $badge['class'] . '">' . $badge['label'] . '</span>';
    }
}

// ============================================================
// FUNÇÕES DE VALIDAÇÃO
// ============================================================

if (!function_exists('isValidEmail')) {
    /**
     * Verifica se um email é válido
     * 
     * @param string $email Email a validar
     * @return bool True se válido, False caso contrário
     */
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('isValidPhone')) {
    /**
     * Verifica se um número de telefone é válido (formato PT)
     * 
     * @param string $phone Telefone a validar
     * @return bool True se válido, False caso contrário
     */
    function isValidPhone($phone) {
        // Remove espaços e caracteres especiais
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Formato PT: 9 dígitos, começando por 9
        if (preg_match('/^9[0-9]{8}$/', $phone)) {
            return true;
        }
        
        // Formato com indicativo: +351 9...
        if (preg_match('/^\+3519[0-9]{8}$/', $phone)) {
            return true;
        }
        
        return false;
    }
}

if (!function_exists('isValidNIF')) {
    /**
     * Verifica se um NIF (Número de Identificação Fiscal) é válido
     * 
     * @param string $nif NIF a validar
     * @return bool True se válido, False caso contrário
     */
    function isValidNIF($nif) {
        // Remove espaços
        $nif = preg_replace('/[^0-9]/', '', $nif);
        
        // Deve ter 9 dígitos
        if (strlen($nif) !== 9) {
            return false;
        }
        
        // Algoritmo de validação do NIF português
        $checkSum = 0;
        for ($i = 0; $i < 8; $i++) {
            $checkSum += (int) $nif[$i] * (10 - $i - 1);
        }
        
        $checkDigit = 11 - ($checkSum % 11);
        if ($checkDigit >= 10) {
            $checkDigit = 0;
        }
        
        return (int) $nif[8] === $checkDigit;
    }
}

// ============================================================
// FUNÇÕES DE FICHEIROS E UPLOAD
// ============================================================

if (!function_exists('getFileSize')) {
    /**
     * Converte o tamanho de um ficheiro para formato legível
     * 
     * @param int $bytes Tamanho em bytes
     * @param int $precision Número de casas decimais (padrão: 2)
     * @return string Tamanho formatado
     */
    function getFileSize($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('getFileExtension')) {
    /**
     * Obtém a extensão de um ficheiro
     * 
     * @param string $filename Nome do ficheiro
     * @return string Extensão do ficheiro (em minúsculas)
     */
    function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}

if (!function_exists('isAllowedFileType')) {
    /**
     * Verifica se a extensão de um ficheiro é permitida
     * 
     * @param string $filename Nome do ficheiro
     * @param array $allowedExtensions Lista de extensões permitidas
     * @return bool True se permitido, False caso contrário
     */
    function isAllowedFileType($filename, $allowedExtensions = []) {
        $ext = getFileExtension($filename);
        return in_array($ext, $allowedExtensions);
    }
}

// ============================================================
// FUNÇÕES DE SEGURANÇA
// ============================================================

if (!function_exists('csrfToken')) {
    /**
     * Gera e armazena um token CSRF na sessão
     * 
     * @return string Token CSRF
     */
    function csrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrfField')) {
    /**
     * Retorna um campo hidden com o token CSRF
     * 
     * @return string HTML do campo hidden
     */
    function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
    }
}

if (!function_exists('verifyCsrfToken')) {
    /**
     * Verifica se o token CSRF é válido
     * 
     * @param string $token Token a verificar
     * @return bool True se válido, False caso contrário
     */
    function verifyCsrfToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('hashPassword')) {
    /**
     * Gera um hash seguro para uma password
     * 
     * @param string $password Password em texto claro
     * @return string Hash da password
     */
    function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('verifyPassword')) {
    /**
     * Verifica se uma password corresponde ao hash
     * 
     * @param string $password Password em texto claro
     * @param string $hash Hash armazenado
     * @return bool True se corresponder, False caso contrário
     */
    function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

// ============================================================
// FUNÇÕES DE RESPONSE
// ============================================================

if (!function_exists('jsonResponse')) {
    /**
     * Retorna uma resposta JSON com headers apropriados
     * 
     * @param array $data Dados a retornar
     * @param int $status Código de status HTTP (padrão: 200)
     * @return void
     */
    function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('jsonSuccess')) {
    /**
     * Retorna uma resposta JSON de sucesso
     * 
     * @param array $data Dados adicionais
     * @param string $message Mensagem de sucesso
     * @param int $status Código de status HTTP
     * @return void
     */
    function jsonSuccess($data = [], $message = 'Operação realizada com sucesso', $status = 200) {
        jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }
}

if (!function_exists('jsonError')) {
    /**
     * Retorna uma resposta JSON de erro
     * 
     * @param string $message Mensagem de erro
     * @param int $status Código de status HTTP
     * @param array $errors Erros adicionais
     * @return void
     */
    function jsonError($message = 'Ocorreu um erro', $status = 400, $errors = []) {
        jsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}

// ============================================================
// FUNÇÕES DE LOG
// ============================================================

if (!function_exists('logMessage')) {
    /**
     * Regista uma mensagem no ficheiro de log
     * 
     * @param string $message Mensagem a registar
     * @param string $level Nível de log (info, warning, error)
     * @param string $file Ficheiro de log (opcional)
     * @return bool True se registado, False caso contrário
     */
    function logMessage($message, $level = 'info', $file = null) {
        $logDir = __DIR__ . '/../logs/';
        
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                return false;
            }
        }
        
        $file = $file ?? date('Y-m-d') . '.log';
        $logFile = $logDir . $file;
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        return file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) !== false;
    }
}

if (!function_exists('logError')) {
    /**
     * Regista uma mensagem de erro no log
     * 
     * @param string $message Mensagem de erro
     * @param mixed $context Contexto adicional (opcional)
     * @return bool True se registado, False caso contrário
     */
    function logError($message, $context = null) {
        $logMessage = $message;
        if ($context !== null) {
            $logMessage .= ' - ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        return logMessage($logMessage, 'error');
    }
}

// ============================================================
// FUNÇÕES DE CONFIGURAÇÃO
// ============================================================

if (!function_exists('env')) {
    /**
     * Obtém uma variável de ambiente
     * 
     * @param string $key Nome da variável
     * @param mixed $default Valor padrão
     * @return mixed Valor da variável ou valor padrão
     */
    function env($key, $default = null) {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * Obtém um valor de configuração
     * 
     * @param string $key Chave de configuração
     * @param mixed $default Valor padrão
     * @return mixed Valor da configuração ou valor padrão
     */
    function config($key, $default = null) {
        global $config;
        
        if (!isset($config)) {
            return $default;
        }
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}