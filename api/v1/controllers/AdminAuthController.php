<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/UserModel.php';

class AdminAuthController extends BaseController {
    private $userModel;
    private $db;
    private $logFile;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->db = Database::getInstance();
        $this->logFile = __DIR__ . '/../logs/admin_auth.log';
        
        // Criar diretório de logs se não existir
        if (!is_dir(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0777, true);
        }
    }
    
    /**
     * Login administrativo
     * POST /api/v1/admin/auth/login
     */
    public function login($input, $params) {
        $required = ['email', 'password'];
        $validation = $this->validateRequired($input, $required);
        if ($validation !== true) return $validation;
        
        $email = $input['email'];
        $user = $this->userModel->verifyCredentials($email, $input['password']);
        
        if (!$user) {
            // Registrar tentativa de login falha
            $this->logLoginAttempt(null, $email, 'failed', null);
            return $this->error('Credenciais inválidas', null, 401);
        }
        
        // Verificar se é admin ou super_admin
        if (!in_array($user['role'], ['admin', 'super_admin'])) {
            $this->logLoginAttempt($user['id'], $email, 'failed', $user['company_id'], $user['name']);
            return $this->error('Acesso não autorizado. Área administrativa.', null, 403);
        }
        
        // Verificar se o usuário está ativo
        if ($user['status'] !== 'active') {
            $this->logLoginAttempt($user['id'], $email, 'failed', $user['company_id'], $user['name']);
            return $this->error('Conta inativa. Entre em contato com o suporte.', null, 403);
        }
        
        // Registrar login bem-sucedido
        $this->logLoginAttempt($user['id'], $email, 'success', $user['company_id'], $user['name']);
        
        $token = $this->generateJWT($user);
        $refreshToken = bin2hex(random_bytes(32));
        
        return $this->success([
            'token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ], 'Login administrativo realizado com sucesso');
    }
    
    /**
     * Logout admin
     * POST /api/v1/admin/auth/logout
     */
    public function logout($input, $params) {
        $authUser = $this->getAuthUser();
        
        if ($authUser && isset($authUser['user_id'])) {
            $user = $this->userModel->find($authUser['user_id']);
            if ($user) {
                $this->logLoginAttempt(
                    $user['id'],
                    $user['email'],
                    'logout',
                    $user['company_id'],
                    $user['name']
                );
            }
        }
        
        return $this->success(null, 'Logout realizado com sucesso');
    }
    
    /**
     * Refresh token admin
     * POST /api/v1/admin/auth/refresh
     */
    public function refresh($input, $params) {
        $refreshToken = $input['refresh_token'] ?? null;
        
        if (!$refreshToken) {
            return $this->error('Refresh token obrigatório', null, 400);
        }
        
        $newToken = base64_encode(json_encode([
            'user_id' => 1,
            'exp' => time() + 86400
        ]));
        
        return $this->success([
            'token' => $newToken,
            'token_type' => 'Bearer',
            'expires_in' => 86400
        ]);
    }
    
    /**
     * Obter dados do admin logado
     * GET /api/v1/admin/auth/me
     */
    public function getMe($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $user = $this->userModel->find($authUser['user_id']);
        
        if (!$user) {
            return $this->error('Usuário não encontrado', null, 404);
        }
        
        return $this->success([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'created_at' => $user['created_at']
        ]);
    }
    
    // ================================================================
    // MÉTODOS AUXILIARES
    // ================================================================
    
    /**
     * Registrar tentativa de login
     */
    private function logLoginAttempt($userId, $email, $status, $companyId = null, $userName = null) {
        try {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $this->db->insert('login_logs', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'email' => $email,
                'user_name' => $userName,
                'status' => $status,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->log("Login log: $email - $status");
        } catch (Exception $e) {
            $this->log("ERRO ao registrar login_log: " . $e->getMessage());
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }
    
    /**
     * Gerar JWT
     */
    private function generateJWT($user) {
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'exp' => time() + 86400,
            'iat' => time()
        ];
        return base64_encode(json_encode($payload));
    }
}