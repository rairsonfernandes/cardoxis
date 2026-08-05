<?php
/**
 * CARDOXIS - Auth Controller RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController extends BaseController {
    private $userModel;
    private $logFile;
    private $db;
    private $activityLogFile;
    
    // ============================================================
    // CONFIGURAÇÃO GMAIL
    // ============================================================
    private $smtpConfig = [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@example.com',
        'password' => 'your-app-password',
        'from_email' => 'your-email@example.com',
        'from_name' => 'Example App',
    ];
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->db = Database::getInstance();
        $this->logFile = __DIR__ . '/../logs/auth.log';
        $this->activityLogFile = __DIR__ . '/../logs/activity.log';
        
        // Criar diretório de logs
        if (!is_dir(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0777, true);
        }
        
        // Iniciar sessão se não estiver ativa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Garantir que o CSRF token existe
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * Registro de usuário
     * POST /api/v1/auth/register
     */
    public function register($input, $params) {
        $required = ['name', 'email', 'password'];
        $validation = $this->validateRequired($input, $required);
        if ($validation !== true) {
            return $validation;
        }
        
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->error('Email inválido', null, 400);
        }
        
        if (strlen($input['password']) < 6) {
            return $this->error('A senha deve ter no mínimo 6 caracteres', null, 400);
        }
        
        $db = Database::getInstance();
        
        $existing = $db->fetchOne("SELECT id FROM users WHERE email = :email", [':email' => $input['email']]);
        if ($existing) {
            return $this->error('Email já cadastrado', null, 409);
        }
        
        $company = $db->fetchOne("SELECT id FROM companies LIMIT 1");
        
        if (!$company) {
            $companyId = $db->insert('companies', [
                'name' => $input['name'] . ' - Empresa',
                'document' => '000000000',
                'email' => $input['email'],
                'status' => 'active',
                'max_vehicles' => 10,
                'max_drivers' => 5,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $companyId = $company['id'];
        }
        
        $userId = $db->insert('users', [
            'company_id' => $companyId,
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => password_hash($input['password'], PASSWORD_DEFAULT),
            'role' => 'user',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if (!$userId) {
            return $this->error('Erro ao criar usuário', null, 500);
        }
        
        // Registrar atividade de registro
        $this->logActivity($userId, $companyId, 'register', 'Novo usuário registrado: ' . $input['email']);
        $this->logAudit($userId, $companyId, 'create', 'user', $userId, 'Usuário criado via registro', null, $input);
        
        return $this->success([
            'user_id' => $userId,
            'name' => $input['name'],
            'email' => $input['email']
        ], 'Usuário criado com sucesso!', 201);
    }
    
    /**
     * Login de usuário
     * POST /api/v1/auth/login
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
            $this->logActivity(null, null, 'login_failed', 'Tentativa de login falha: ' . $email);
            return $this->error('Email ou senha inválidos', null, 401);
        }
        
        // Verificar se o usuário está ativo
        if ($user['status'] !== 'active') {
            $this->logLoginAttempt($user['id'], $email, 'failed', $user['company_id']);
            $this->logActivity($user['id'], $user['company_id'], 'login_failed', 'Tentativa de login em conta inativa: ' . $email);
            return $this->error('Conta inativa. Entre em contato com o suporte.', null, 403);
        }
        
        // Registrar login bem-sucedido
        $this->logLoginAttempt($user['id'], $email, 'success', $user['company_id'], $user['name']);
        $this->logActivity($user['id'], $user['company_id'], 'login_success', 'Login realizado: ' . $email);
        
        $token = $this->generateJWT($user);
        $refreshToken = bin2hex(random_bytes(32));
        
        // Atualizar último login
        $this->db->update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $user['id']]);
        
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
        ], 'Login realizado com sucesso');
    }
    
    /**
     * Logout
     * POST /api/v1/auth/logout
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
                $this->logActivity($user['id'], $user['company_id'], 'logout', 'Logout realizado: ' . $user['email']);
            }
        }
        
        return $this->success(null, 'Logout realizado com sucesso');
    }
    
    /**
     * Refresh token
     * POST /api/v1/auth/refresh
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
    
    // ================================================================
    // SISTEMA DE RECUPERAÇÃO DE SENHA
    // ================================================================
    
    /**
     * Solicitar recuperação de senha
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword($input, $params) {
        $email = $input['email'] ?? null;
        $csrfToken = $input['csrf_token'] ?? null;
        
        if (!isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
            return $this->error('Token de segurança inválido', null, 403);
        }
        
        if (!$email) {
            return $this->error('Email obrigatório', null, 400);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Email inválido', null, 400);
        }
        
        $db = Database::getInstance();
        
        $user = $db->fetchOne(
            "SELECT id, name, email, company_id FROM users WHERE email = :email AND status = 'active'", 
            [':email' => $email]
        );
        
        if (!$user) {
            return $this->success(null, 'Se o email existir, um código será enviado');
        }
        
        $attempts = $db->fetchOne(
            "SELECT COUNT(*) as count FROM password_resets 
             WHERE email = :email AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [':email' => $email]
        );
        
        if ($attempts && $attempts['count'] >= 5) {
            return $this->error('Muitas tentativas. Aguarde 15 minutos.', null, 429);
        }
        
        $pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hashedPin = password_hash($pin, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $db->insert('password_resets', [
            'email' => $email,
            'token' => $hashedPin,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_token'] = $hashedPin;
        $_SESSION['reset_expires'] = time() + 900;
        
        $emailSent = $this->sendPasswordResetEmail($user['name'], $email, $pin);
        
        $this->log("Email enviado para $email: " . ($emailSent ? 'SUCESSO' : 'FALHA'));
        
        // Registrar atividade
        $this->logActivity($user['id'], $user['company_id'], 'password_reset_request', 'Solicitação de recuperação de senha: ' . $email);
        
        return $this->success([
            'email' => $email,
            'expires_in' => 900,
            'email_sent' => $emailSent
        ], $emailSent ? 'Código enviado com sucesso' : 'Código gerado, mas falha no envio do email');
    }
    
    /**
     * Verificar PIN
     * POST /api/v1/auth/verify-pin
     */
    public function verifyPin($input, $params) {
        $this->log("=== VERIFY PIN REQUEST ===");
        $this->log("Input: " . json_encode($input));
        
        try {
            $pin = $input['pin'] ?? null;
            $csrfToken = $input['csrf_token'] ?? null;
            
            if (!isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
                $this->log("CSRF inválido");
                return $this->error('Token de segurança inválido. Recarregue a página.', null, 403);
            }
            
            if (!$pin) {
                $this->log("PIN não fornecido");
                return $this->error('Código obrigatório', null, 400);
            }
            
            if (!preg_match('/^[0-9]{6}$/', $pin)) {
                $this->log("PIN inválido: $pin");
                return $this->error('Código inválido (deve ter 6 dígitos)', null, 400);
            }
            
            if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_token'])) {
                $this->log("Sessão inválida");
                return $this->error('Sessão inválida. Solicite um novo código.', null, 400);
            }
            
            if (isset($_SESSION['reset_expires']) && time() > $_SESSION['reset_expires']) {
                $this->log("Código expirado");
                $this->clearResetSession();
                return $this->error('Código expirado. Solicite um novo código.', null, 400);
            }
            
            $db = Database::getInstance();
            $email = $_SESSION['reset_email'];
            
            $reset = $db->fetchOne(
                "SELECT * FROM password_resets WHERE email = :email ORDER BY created_at DESC LIMIT 1",
                [':email' => $email]
            );
            
            if (!$reset) {
                $this->log("Reset não encontrado para: " . $email);
                return $this->error('Código inválido. Solicite um novo código.', null, 400);
            }
            
            if (strtotime($reset['expires_at']) < time()) {
                $this->log("Reset expirado no banco");
                return $this->error('Código expirado. Solicite um novo código.', null, 400);
            }
            
            if (!password_verify($pin, $reset['token'])) {
                $newAttempts = ($reset['attempts'] ?? 0) + 1;
                
                $db->update(
                    'password_resets', 
                    ['attempts' => $newAttempts], 
                    'id = :id',
                    [':id' => $reset['id']]
                );
                
                $this->log("PIN incorreto - Tentativa: $newAttempts");
                
                if ($newAttempts >= 5) {
                    $this->log("Muitas tentativas - Bloqueando");
                    return $this->error('Muitas tentativas. Solicite um novo código.', null, 400);
                }
                
                return $this->error('Código inválido. Tente novamente.', null, 400);
            }
            
            $_SESSION['reset_verified'] = true;
            $_SESSION['reset_expires'] = time() + 900;
            
            $db->update(
                'password_resets', 
                [
                    'used' => 1, 
                    'used_at' => date('Y-m-d H:i:s')
                ], 
                'id = :id',
                [':id' => $reset['id']]
            );
            
            $this->log("PIN verificado com sucesso para: " . $email);
            
            // Registrar atividade
            $user = $db->fetchOne("SELECT id, company_id FROM users WHERE email = :email", [':email' => $email]);
            if ($user) {
                $this->logActivity($user['id'], $user['company_id'], 'password_reset_verify', 'PIN verificado para recuperação de senha: ' . $email);
            }
            
            return $this->success(null, 'Código verificado com sucesso');
            
        } catch (Exception $e) {
            $this->log("ERRO EXCEÇÃO: " . $e->getMessage());
            $this->log("ERRO TRACE: " . $e->getTraceAsString());
            return $this->error('Erro interno do servidor: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Reenviar PIN
     * POST /api/v1/auth/resend-pin
     */
    public function resendPin($input, $params) {
        $csrfToken = $input['csrf_token'] ?? null;
        
        if (!isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
            return $this->error('Token de segurança inválido', null, 403);
        }
        
        if (!isset($_SESSION['reset_email'])) {
            return $this->error('Sessão inválida', null, 400);
        }
        
        $email = $_SESSION['reset_email'];
        
        $db = Database::getInstance();
        
        $attempts = $db->fetchOne(
            "SELECT COUNT(*) as count FROM password_resets 
             WHERE email = :email AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [':email' => $email]
        );
        
        if ($attempts && $attempts['count'] >= 5) {
            return $this->error('Muitas tentativas. Aguarde 15 minutos.', null, 429);
        }
        
        $user = $db->fetchOne("SELECT id, name, email, company_id FROM users WHERE email = :email", [':email' => $email]);
        
        if (!$user) {
            return $this->error('Usuário não encontrado', null, 404);
        }
        
        $pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hashedPin = password_hash($pin, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $db->insert('password_resets', [
            'email' => $email,
            'token' => $hashedPin,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $_SESSION['reset_token'] = $hashedPin;
        $_SESSION['reset_expires'] = time() + 900;
        unset($_SESSION['reset_verified']);
        
        $this->sendPasswordResetEmail($user['name'], $email, $pin);
        
        // Registrar atividade
        $this->logActivity($user['id'], $user['company_id'], 'password_reset_resend', 'Reenvio de PIN para recuperação de senha: ' . $email);
        
        return $this->success(null, 'Código reenviado com sucesso');
    }
    
    /**
     * Redefinir senha
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword($input, $params) {
        $this->log("=== RESET PASSWORD REQUEST ===");
        $this->log("Input: " . json_encode($input));
        $this->log("Session: " . json_encode([
            'reset_verified' => $_SESSION['reset_verified'] ?? 'not set',
            'reset_email' => $_SESSION['reset_email'] ?? 'not set',
            'reset_expires' => $_SESSION['reset_expires'] ?? 'not set'
        ]));
        
        try {
            $password = $input['password'] ?? null;
            $passwordConfirm = $input['password_confirm'] ?? null;
            $csrfToken = $input['csrf_token'] ?? null;
            
            $this->log("1. Validando CSRF...");
            
            if (!isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
                $this->log("CSRF inválido");
                return $this->error('Token de segurança inválido. Recarregue a página.', null, 403);
            }
            
            $this->log("2. Verificando se a recuperação foi verificada...");
            
            if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
                $this->log("Sessão não verificada - reset_verified: " . ($_SESSION['reset_verified'] ?? 'not set'));
                return $this->error('Sessão inválida. Solicite um novo código.', null, 400);
            }
            
            $this->log("3. Verificando expiração...");
            
            if (isset($_SESSION['reset_expires']) && time() > $_SESSION['reset_expires']) {
                $this->log("Sessão expirada");
                $this->clearResetSession();
                return $this->error('Sessão expirada. Solicite um novo código.', null, 400);
            }
            
            $this->log("4. Validando senha...");
            
            if (!$password || !$passwordConfirm) {
                $this->log("Senha ou confirmação vazias");
                return $this->error('Senha e confirmação são obrigatórias', null, 400);
            }
            
            if ($password !== $passwordConfirm) {
                $this->log("Senhas não coincidem");
                return $this->error('As senhas não coincidem', null, 400);
            }
            
            if (strlen($password) < 6) {
                $this->log("Senha muito curta: " . strlen($password));
                return $this->error('Senha deve ter no mínimo 6 caracteres', null, 400);
            }
            
            $this->log("5. Buscando email da sessão...");
            
            $email = $_SESSION['reset_email'] ?? null;
            
            if (!$email) {
                $this->log("Email não encontrado na sessão");
                return $this->error('Sessão inválida. Solicite um novo código.', null, 400);
            }
            
            $this->log("6. Buscando usuário no banco...");
            
            $db = Database::getInstance();
            
            $user = $db->fetchOne(
                "SELECT id, email, company_id, name FROM users WHERE email = :email",
                [':email' => $email]
            );
            
            if (!$user) {
                $this->log("Usuário não encontrado: " . $email);
                return $this->error('Usuário não encontrado', null, 404);
            }
            
            $this->log("7. Atualizando senha do usuário ID: " . $user['id']);
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $db->update(
                'users', 
                ['password' => $hashedPassword],
                'id = :id',
                [':id' => $user['id']]
            );
            
            $this->log("8. Invalidando tokens de recuperação...");
            
            $db->delete('password_resets', 'email = :email', [':email' => $email]);
            
            $this->log("9. Limpando sessão de recuperação...");
            
            $this->clearResetSession();
            
            $this->log("10. Registrando logs...");
            
            $this->logSecurityEvent($user['id'], 'password_reset', 'Password reset completed');
            
            // Registrar atividade
            $this->logActivity($user['id'], $user['company_id'], 'password_reset_complete', 'Senha redefinida com sucesso: ' . $email);
            
            // Registrar auditoria
            $this->logAudit($user['id'], $user['company_id'], 'update', 'user', $user['id'], 'Senha redefinida via recuperação', null, ['password_reset' => true]);
            
            $this->log("=== RESET PASSWORD SUCESSO ===");
            
            return $this->success(null, 'Senha redefinida com sucesso');
            
        } catch (Exception $e) {
            $this->log("ERRO EXCEÇÃO: " . $e->getMessage());
            $this->log("ERRO TRACE: " . $e->getTraceAsString());
            return $this->error('Erro interno do servidor: ' . $e->getMessage(), null, 500);
        }
    }
    
    // ================================================================
    // MÉTODOS DE EMAIL - GMAIL SMTP
    // ================================================================
    
    /**
     * Enviar email de recuperação via Gmail SMTP
     */
    private function sendPasswordResetEmail($name, $email, $pin) {
        try {
            $subject = 'Código de Recuperação - CARDOXIS';
            $message = $this->buildResetEmailHTML($name, $pin);
            
            $result = $this->sendSMTPMail(
                $this->smtpConfig['host'],
                $this->smtpConfig['port'],
                $this->smtpConfig['username'],
                $this->smtpConfig['password'],
                $email,
                $subject,
                $message,
                $this->smtpConfig['from_email'],
                $this->smtpConfig['from_name']
            );
            
            if ($result) {
                $this->log("Email enviado com sucesso para: $email (Gmail)");
            } else {
                $this->log("Falha ao enviar email para: $email (Gmail)");
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->log("ERRO ao enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Construir HTML do email de recuperação
     */
    private function buildResetEmailHTML($name, $pin) {
        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; background: #f4f5f7; padding: 20px; margin: 0; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { font-size: 28px; font-weight: 800; color: #172B4D; }
                .logo span { color: #0052CC; }
                .subtitle { color: #6B778C; font-size: 14px; margin-top: 4px; }
                .divider { height: 2px; background: linear-gradient(to right, #DFE1E6, #0052CC, #DFE1E6); margin: 20px 0; }
                .greeting { font-size: 18px; font-weight: 600; color: #172B4D; }
                .message { color: #42526E; line-height: 1.7; font-size: 15px; }
                .pin-code { font-size: 42px; font-weight: 700; letter-spacing: 14px; text-align: center; padding: 24px; background: #f0f5ff; border-radius: 10px; margin: 24px 0; color: #0052CC; font-family: 'Courier New', monospace; }
                .info-box { background: #f4f5f7; padding: 14px 18px; border-radius: 8px; margin: 16px 0; font-size: 14px; color: #42526E; }
                .warning-box { background: #FFF4E5; padding: 14px 18px; border-radius: 8px; margin: 16px 0; font-size: 14px; color: #CC7000; border-left: 4px solid #FF8B00; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #DFE1E6; font-size: 12px; color: #6B778C; }
                .footer a { color: #0052CC; text-decoration: none; }
                .footer a:hover { text-decoration: underline; }
                @media (max-width: 480px) {
                    .container { padding: 24px 16px; }
                    .pin-code { font-size: 32px; letter-spacing: 10px; padding: 16px; }
                    .logo { font-size: 24px; }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>CARDOXIS<span>.io</span></div>
                    <div class='subtitle'>Sistema de Gestão de Frotas</div>
                </div>
                
                <div class='divider'></div>
                
                <p class='greeting'>Olá, " . htmlspecialchars($name) . "!</p>
                
                <p class='message'>Recebemos um pedido para redefinir a senha da sua conta no <strong>CARDOXIS</strong>.</p>
                
                <p class='message'>Utilize o código abaixo para continuar com o processo de recuperação:</p>
                
                <div class='pin-code'>" . htmlspecialchars($pin) . "</div>
                
                <div class='info-box'>
                    <strong>⏱️ Prazo:</strong> Este código é válido por <strong>15 minutos</strong>.
                </div>
                
                <div class='warning-box'>
                    <strong>⚠️ Atenção:</strong> Se não solicitou esta recuperação, <strong>ignore este email</strong>. A sua conta permanece segura.
                </div>
                
                <p class='message'>
                    Precisa de ajuda? 
                    <a href='mailto:suporte@cardoxis.io' style='color: #0052CC; font-weight: 600;'>Contacte o suporte</a>
                </p>
                
                <div class='footer'>
                    <p>&copy; " . date('Y') . " CARDOXIS. Todos os direitos reservados.</p>
                    <p style='margin-top: 4px;'>
                        Rua da Tecnologia, 123 - 1000-001 Lisboa, Portugal<br>
                        <a href='mailto:suporte@cardoxis.io'>suporte@cardoxis.io</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Enviar email via Socket SMTP
     */
    private function sendSMTPMail($host, $port, $username, $password, $to, $subject, $message, $fromEmail, $fromName) {
        $socket = @fsockopen($host, $port, $errno, $errstr, 30);
        
        if (!$socket) {
            $this->log("Erro ao conectar SMTP: $errstr ($errno)");
            return false;
        }
        
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            $this->log("Resposta inesperada: $response");
            return false;
        }
        
        fputs($socket, "EHLO " . gethostname() . "\r\n");
        $this->readSMTPResponse($socket);
        
        fputs($socket, "STARTTLS\r\n");
        $response = $this->readSMTPResponse($socket);
        
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            $this->log("STARTTLS falhou");
            return false;
        }
        
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
            fclose($socket);
            $this->log("Falha ao ativar TLS");
            return false;
        }
        
        fputs($socket, "EHLO " . gethostname() . "\r\n");
        $this->readSMTPResponse($socket);
        
        fputs($socket, "AUTH LOGIN\r\n");
        $response = $this->readSMTPResponse($socket);
        
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            $this->log("AUTH LOGIN falhou");
            return false;
        }
        
        fputs($socket, base64_encode($username) . "\r\n");
        $response = $this->readSMTPResponse($socket);
        
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            $this->log("Username inválido");
            return false;
        }
        
        fputs($socket, base64_encode($password) . "\r\n");
        $response = $this->readSMTPResponse($socket);
        
        if (substr($response, 0, 3) != '235') {
            fclose($socket);
            $this->log("Autenticação falhou - Verifique email e senha de aplicação");
            return false;
        }
        
        fputs($socket, "MAIL FROM:<$fromEmail>\r\n");
        $response = $this->readSMTPResponse($socket);
        
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            $this->log("MAIL FROM falhou");
            return false;
        }
        
        fputs($socket, "RCPT TO:<$to>\r\n");
        $response = $this->readSMTPResponse($socket);
        
        if (substr($response, 0, 3) != '250' && substr($response, 0, 3) != '251') {
            fclose($socket);
            $this->log("RCPT TO falhou");
            return false;
        }
        
        fputs($socket, "DATA\r\n");
        $response = $this->readSMTPResponse($socket);
        
        if (substr($response, 0, 3) != '354') {
            fclose($socket);
            $this->log("DATA falhou");
            return false;
        }
        
        $fullMessage = "Subject: $subject\r\n";
        $fullMessage .= "From: $fromName <$fromEmail>\r\n";
        $fullMessage .= "To: $to\r\n";
        $fullMessage .= "MIME-Version: 1.0\r\n";
        $fullMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
        $fullMessage .= "\r\n";
        $fullMessage .= $message;
        $fullMessage .= "\r\n.\r\n";
        
        fputs($socket, $fullMessage);
        $response = $this->readSMTPResponse($socket);
        
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        if (substr($response, 0, 3) != '250') {
            $this->log("Erro ao enviar mensagem: $response");
            return false;
        }
        
        return true;
    }
    
    /**
     * Ler resposta SMTP
     */
    private function readSMTPResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 1024)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }
    
    // ================================================================
    // MÉTODOS DE LOG    // ================================================================
    
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
    
    /**
     * Registrar atividade
     */
    private function logActivity($userId, $companyId, $action, $description) {
        try {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $this->db->insert('activity_logs', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $ipAddress,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->log("[ACTIVITY] User: $userId | Action: $action | $description");
            
        } catch (Exception $e) {
            $this->log("Erro ao registrar atividade: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar auditoria
     */
    private function logAudit($userId, $companyId, $action, $entityType, $entityId, $description, $oldValues = null, $newValues = null) {
        try {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $this->db->insert('audit_logs', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'description' => $description,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->log("[AUDIT] User: $userId | Action: $action | Entity: $entityType:$entityId | $description");
            
        } catch (Exception $e) {
            $this->log("Erro ao registrar auditoria: " . $e->getMessage());
        }
    }
    
    private function clearResetSession() {
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_token']);
        unset($_SESSION['reset_verified']);
        unset($_SESSION['reset_expires']);
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }
    
    public function testEmail($input, $params) {
        $email = $input['email'] ?? null;
        
        if (!$email) {
            return $this->error('Email obrigatório para teste', null, 400);
        }
        
        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT name, email FROM users WHERE email = :email", [':email' => $email]);
        
        if (!$user) {
            return $this->error('Usuário não encontrado', null, 404);
        }
        
        $pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $result = $this->sendPasswordResetEmail($user['name'], $email, $pin);
        
        return $this->success([
            'sent' => $result,
            'email' => $email,
            'pin' => $result ? null : $pin,
            'message' => $result ? 'Email enviado com sucesso' : 'Falha ao enviar email',
            'debug' => [
                'log_file' => $this->logFile
            ]
        ]);
    }
    
    private function logSecurityEvent($userId, $event, $description) {
        $db = Database::getInstance();
        $db->insert('security_logs', [
            'user_id' => $userId,
            'event' => $event,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // ================================================================
    // MÉTODOS ADICIONAIS
    // ================================================================
    
    public function verifyEmail($input, $params) {
        $token = $input['token'] ?? null;
        
        if (!$token) {
            return $this->error('Token obrigatório', null, 400);
        }
        
        return $this->success(null, 'Email verificado com sucesso');
    }
    
    public function resendVerification($input, $params) {
        $userId = $this->getAuthUser()['user_id'] ?? null;
        
        if (!$userId) {
            return $this->error('Usuário não autenticado', null, 401);
        }
        
        return $this->success(null, 'Email de verificação reenviado');
    }
    
    public function check($input, $params) {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        
        if (empty($token)) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $decoded = json_decode(base64_decode($token), true);
        
        if (!$decoded || !isset($decoded['user_id'])) {
            return $this->error('Token inválido', null, 401);
        }
        
        if (isset($decoded['exp']) && $decoded['exp'] < time()) {
            return $this->error('Token expirado', null, 401);
        }
        
        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT id, name, email, role, company_id FROM users WHERE id = :id", [':id' => $decoded['user_id']]);
        
        if (!$user) {
            return $this->error('Usuário não encontrado', null, 401);
        }
        
        return $this->success($user, 'Usuário autenticado');
    }
    
    public function getMe($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $user = $this->userModel->find($authUser['user_id']);
        
        if (!$user) {
            return $this->error('Usuário não encontrado', null, 404);
        }
        
        return $this->success($user);
    }
    
    public function checkSession($input, $params) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            return $this->success([
                'authenticated' => true,
                'user_id' => $_SESSION['user_id'],
                'user_name' => $_SESSION['user_name'] ?? null,
                'user_email' => $_SESSION['user_email'] ?? null,
                'user_role' => $_SESSION['user_role'] ?? 'user'
            ]);
        }
        
        return $this->error('Não autenticado', null, 401);
    }
    
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