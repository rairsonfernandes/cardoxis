<?php
class HealthController {
    
    public function index() {
        return [
            'success' => true,
            'message' => 'CARDOXIS API v1.0.0',
            'endpoints' => [
                'GET /health' => 'Verificar status da API',
                'POST /auth/login' => 'Login de usuário',
                'POST /auth/register' => 'Registro de usuário',
                'GET /vehicles' => 'Listar veículos',
                'GET /drivers' => 'Listar motoristas',
                'GET /dashboard/stats' => 'Estatísticas do dashboard',
                'GET /subscription/plans' => 'Planos de assinatura'
            ],
            'version' => '1.0.0',
            'status' => 'operational'
        ];
    }



   /**
 * Documentação da API
 * GET /api/v1/docs
 */
public function docs() {
    return [
        'success' => true,
        'message' => 'CARDOXIS API Documentation',
        'version' => '1.0.0',
        'base_url' => 'http://localhost/cardoxis/api/v1',
        'endpoints' => [
            'authentication' => [
                'POST /auth/register' => 'Registrar novo usuário',
                'POST /auth/login' => 'Login de usuário',
                'POST /auth/logout' => 'Logout',
                'POST /auth/refresh' => 'Refresh token',
                'POST /auth/forgot-password' => 'Esqueci a senha',
                'POST /auth/reset-password' => 'Resetar senha',
                'POST /auth/verify-email' => 'Verificar email',
                'POST /auth/resend-verification' => 'Reenviar verificação',
                'GET /auth/me' => 'Dados do usuário logado'
            ],
            'profile' => [
                'GET /profile' => 'Obter perfil',
                'PUT /profile' => 'Atualizar perfil',
                'PUT /profile/password' => 'Alterar senha',
                'POST /profile/avatar' => 'Upload avatar',
                'DELETE /profile/avatar' => 'Remover avatar',
                'GET /profile/activity' => 'Atividades',
                'GET /profile/api-tokens' => 'Listar tokens',
                'POST /profile/api-tokens' => 'Criar token',
                'DELETE /profile/api-tokens/{id}' => 'Revogar token'
            ],
            'vehicles' => [
                'GET /vehicles' => 'Listar veículos',
                'POST /vehicles' => 'Criar veículo',
                'GET /vehicles/{id}' => 'Obter veículo',
                'PUT /vehicles/{id}' => 'Atualizar veículo',
                'DELETE /vehicles/{id}' => 'Remover veículo',
                'PATCH /vehicles/{id}/color' => 'Atualizar cor',
                'PATCH /vehicles/{id}/odometer' => 'Atualizar odômetro'
            ],
            'drivers' => [
                'GET /drivers' => 'Listar motoristas',
                'POST /drivers' => 'Criar motorista',
                'GET /drivers/{id}' => 'Obter motorista',
                'PUT /drivers/{id}' => 'Atualizar motorista',
                'DELETE /drivers/{id}' => 'Remover motorista'
            ],
            'dashboard' => [
                'GET /dashboard/stats' => 'Estatísticas do dashboard',
                'GET /dashboard/recent-activities' => 'Atividades recentes'
            ]
        ]
    ];
}



    
    public function check() {
        $db = Database::getInstance();
        
        try {
            $db->getConnection()->query("SELECT 1");
            $dbStatus = 'connected';
        } catch (Exception $e) {
            $dbStatus = 'disconnected';
        }
        
        return [
            'success' => true,
            'message' => 'API is healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => [
                'status' => $dbStatus,
                'version' => $dbStatus === 'connected' ? $db->getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION) : null
            ],
            'php_version' => PHP_VERSION,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_usage' => memory_get_usage(),
            'peak_memory' => memory_get_peak_usage()
        ];
    }
}