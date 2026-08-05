<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';

class AdminSettingController extends BaseController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obter todas as configurações
     * GET /api/v1/admin/settings
     */
    public function getSettings($input, $params) {
        $this->ensureSettingsTable();
        
        $settings = $this->db->fetchAll("SELECT * FROM system_settings ORDER BY setting_group, setting_key");
        
        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting['setting_key']] = $this->formatValue($setting['setting_value'], $setting['value_type']);
        }
        
        return $this->success($formattedSettings);
    }
    
    /**
     * Atualizar configurações
     * PUT /api/v1/admin/settings
     */
    public function updateSettings($input, $params) {
        if (empty($input)) {
            return $this->error('Nenhum dado para atualizar', null, 400);
        }
        
        foreach ($input as $key => $value) {
            $this->db->update('system_settings', 
                ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')], 
                'setting_key = :key', 
                [':key' => $key]
            );
        }
        
        return $this->success(null, 'Configurações atualizadas com sucesso');
    }
    
    /**
     * Obter configurações gerais
     * GET /api/v1/admin/settings/general
     */
    public function getGeneralSettings($input, $params) {
        $this->ensureSettingsTable();
        
        $settings = $this->db->fetchAll("SELECT * FROM system_settings WHERE setting_group = 'general'");
        
        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting['setting_key']] = $this->formatValue($setting['setting_value'], $setting['value_type']);
        }
        
        // Se não houver configurações, criar as padrão
        if (empty($formattedSettings)) {
            $formattedSettings = $this->createDefaultSettings();
        }
        
        return $this->success($formattedSettings);
    }
    
    /**
     * Atualizar configurações gerais
     * PUT /api/v1/admin/settings/general
     */
    public function updateGeneralSettings($input, $params) {
        if (empty($input)) {
            return $this->error('Nenhum dado para atualizar', null, 400);
        }
        
        foreach ($input as $key => $value) {
            $existing = $this->db->fetchOne("SELECT id FROM system_settings WHERE setting_key = :key AND setting_group = 'general'", [':key' => $key]);
            
            if ($existing) {
                $this->db->update('system_settings', 
                    ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')], 
                    'setting_key = :key', 
                    [':key' => $key]
                );
            } else {
                $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');
                $this->db->insert('system_settings', [
                    'setting_key' => $key,
                    'setting_value' => (string)$value,
                    'value_type' => $type,
                    'setting_group' => 'general',
                    'description' => '',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        return $this->success(null, 'Configurações gerais atualizadas com sucesso');
    }
    
    /**
     * Obter configurações de email
     * GET /api/v1/admin/settings/email
     */
    public function getEmailSettings($input, $params) {
        $this->ensureSettingsTable();
        
        $settings = $this->db->fetchAll("SELECT * FROM system_settings WHERE setting_group = 'email'");
        
        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting['setting_key']] = $this->formatValue($setting['setting_value'], $setting['value_type']);
        }
        
        // Configurações padrão se não existirem
        if (empty($formattedSettings)) {
            $formattedSettings = [
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'smtp_username' => '',
                'smtp_password' => '',
                'mail_from' => 'noreply@cardoxis.com',
                'mail_from_name' => 'CARDOXIS'
            ];
        }
        
        // Ocultar senha
        if (isset($formattedSettings['smtp_password']) && !empty($formattedSettings['smtp_password'])) {
            $formattedSettings['smtp_password'] = '********';
        }
        
        return $this->success($formattedSettings);
    }
    
    /**
     * Atualizar configurações de email
     * PUT /api/v1/admin/settings/email
     */
    public function updateEmailSettings($input, $params) {
        if (empty($input)) {
            return $this->error('Nenhum dado para atualizar', null, 400);
        }
        
        foreach ($input as $key => $value) {
            // Não salvar senha mascarada
            if ($key === 'smtp_password' && $value === '********') {
                continue;
            }
            
            $existing = $this->db->fetchOne("SELECT id FROM system_settings WHERE setting_key = :key AND setting_group = 'email'", [':key' => $key]);
            
            if ($existing) {
                $this->db->update('system_settings', 
                    ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')], 
                    'setting_key = :key', 
                    [':key' => $key]
                );
            } else {
                $this->db->insert('system_settings', [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'value_type' => 'string',
                    'setting_group' => 'email',
                    'description' => '',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        return $this->success(null, 'Configurações de email atualizadas com sucesso');
    }
    
    /**
     * Obter configurações de pagamento
     * GET /api/v1/admin/settings/payment
     */
    public function getPaymentSettings($input, $params) {
        $this->ensureSettingsTable();
        
        $settings = $this->db->fetchAll("SELECT * FROM system_settings WHERE setting_group = 'payment'");
        
        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting['setting_key']] = $this->formatValue($setting['setting_value'], $setting['value_type']);
        }
        
        // Configurações padrão
        if (empty($formattedSettings)) {
            $formattedSettings = [
                'stripe_key' => '',
                'stripe_secret' => '',
                'pagarme_key' => '',
                'mercadopago_key' => '',
                'currency' => 'EUR'
            ];
        }
        
        // Ocultar chaves secretas
        foreach (['stripe_secret', 'pagarme_key'] as $key) {
            if (isset($formattedSettings[$key]) && !empty($formattedSettings[$key])) {
                $formattedSettings[$key] = '********';
            }
        }
        
        return $this->success($formattedSettings);
    }
    
    /**
     * Atualizar configurações de pagamento
     * PUT /api/v1/admin/settings/payment
     */
    public function updatePaymentSettings($input, $params) {
        if (empty($input)) {
            return $this->error('Nenhum dado para atualizar', null, 400);
        }
        
        foreach ($input as $key => $value) {
            // Não salvar chaves mascaradas
            if (in_array($key, ['stripe_secret', 'pagarme_key']) && $value === '********') {
                continue;
            }
            
            $existing = $this->db->fetchOne("SELECT id FROM system_settings WHERE setting_key = :key AND setting_group = 'payment'", [':key' => $key]);
            
            if ($existing) {
                $this->db->update('system_settings', 
                    ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')], 
                    'setting_key = :key', 
                    [':key' => $key]
                );
            } else {
                $this->db->insert('system_settings', [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'value_type' => 'string',
                    'setting_group' => 'payment',
                    'description' => '',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        return $this->success(null, 'Configurações de pagamento atualizadas com sucesso');
    }
    
    /**
     * Obter configurações de segurança
     * GET /api/v1/admin/settings/security
     */
    public function getSecuritySettings($input, $params) {
        $this->ensureSettingsTable();
        
        $settings = $this->db->fetchAll("SELECT * FROM system_settings WHERE setting_group = 'security'");
        
        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting['setting_key']] = $this->formatValue($setting['setting_value'], $setting['value_type']);
        }
        
        // Configurações padrão
        if (empty($formattedSettings)) {
            $formattedSettings = [
                'max_login_attempts' => 5,
                'session_timeout' => 120,
                'two_factor_auth' => false,
                'password_expiry_days' => 90,
                'password_min_length' => 8
            ];
        }
        
        return $this->success($formattedSettings);
    }
    
    /**
     * Atualizar configurações de segurança
     * PUT /api/v1/admin/settings/security
     */
    public function updateSecuritySettings($input, $params) {
        if (empty($input)) {
            return $this->error('Nenhum dado para atualizar', null, 400);
        }
        
        foreach ($input as $key => $value) {
            $existing = $this->db->fetchOne("SELECT id FROM system_settings WHERE setting_key = :key AND setting_group = 'security'", [':key' => $key]);
            
            if ($existing) {
                $this->db->update('system_settings', 
                    ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')], 
                    'setting_key = :key', 
                    [':key' => $key]
                );
            } else {
                $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');
                $this->db->insert('system_settings', [
                    'setting_key' => $key,
                    'setting_value' => (string)$value,
                    'value_type' => $type,
                    'setting_group' => 'security',
                    'description' => '',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        return $this->success(null, 'Configurações de segurança atualizadas com sucesso');
    }
    
    /**
     * Obter configurações de integrações
     * GET /api/v1/admin/settings/integrations
     */
    public function getIntegrationSettings($input, $params) {
        $this->ensureSettingsTable();
        
        $settings = $this->db->fetchAll("SELECT * FROM system_settings WHERE setting_group = 'integration'");
        
        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting['setting_key']] = $this->formatValue($setting['setting_value'], $setting['value_type']);
        }
        
        // Configurações padrão
        if (empty($formattedSettings)) {
            $formattedSettings = [
                'google_maps_api' => '',
                'here_api_key' => '',
                'webhook_url' => '',
                'slack_webhook' => ''
            ];
        }
        
        return $this->success($formattedSettings);
    }
    
    /**
     * Atualizar configurações de integrações
     * PUT /api/v1/admin/settings/integrations
     */
    public function updateIntegrationSettings($input, $params) {
        if (empty($input)) {
            return $this->error('Nenhum dado para atualizar', null, 400);
        }
        
        foreach ($input as $key => $value) {
            $existing = $this->db->fetchOne("SELECT id FROM system_settings WHERE setting_key = :key AND setting_group = 'integration'", [':key' => $key]);
            
            if ($existing) {
                $this->db->update('system_settings', 
                    ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')], 
                    'setting_key = :key', 
                    [':key' => $key]
                );
            } else {
                $this->db->insert('system_settings', [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'value_type' => 'string',
                    'setting_group' => 'integration',
                    'description' => '',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        return $this->success(null, 'Configurações de integrações atualizadas com sucesso');
    }
    
    /**
     * Formatar valor conforme tipo
     */
    private function formatValue($value, $type) {
        if ($value === null) return null;
        
        switch ($type) {
            case 'integer':
                return (int)$value;
            case 'boolean':
                return $value === '1' || $value === 1 || $value === 'true' || $value === true;
            case 'json':
            case 'array':
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : [];
            default:
                return (string)$value;
        }
    }
    
    /**
     * Garantir que a tabela system_settings existe
     */
    private function ensureSettingsTable() {
        $tableExists = $this->db->fetchOne("SHOW TABLES LIKE 'system_settings'");
        if (!$tableExists) {
            $this->createSettingsTable();
        }
    }
    
    /**
     * Criar tabela system_settings se não existir
     */
    private function createSettingsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS system_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT NULL,
                value_type ENUM('string', 'integer', 'boolean', 'json', 'array') DEFAULT 'string',
                setting_group VARCHAR(50) DEFAULT 'general',
                description TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_setting_key (setting_key),
                INDEX idx_setting_group (setting_group)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $this->db->query($sql);
    }
    
    /**
     * Criar configurações padrão
     */
    private function createDefaultSettings() {
        $defaultSettings = [
            ['key' => 'app_name', 'value' => 'CARDOXIS', 'type' => 'string', 'group' => 'general', 'desc' => 'Nome da aplicação'],
            ['key' => 'app_version', 'value' => '1.0.0', 'type' => 'string', 'group' => 'general', 'desc' => 'Versão da aplicação'],
            ['key' => 'company_name', 'value' => 'CARDOXIS Sistemas', 'type' => 'string', 'group' => 'general', 'desc' => 'Nome da empresa'],
            ['key' => 'support_email', 'value' => 'suporte@cardoxis.com', 'type' => 'string', 'group' => 'general', 'desc' => 'Email de suporte'],
            ['key' => 'support_phone', 'value' => '+351 300 123 456', 'type' => 'string', 'group' => 'general', 'desc' => 'Telefone de suporte'],
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'system', 'desc' => 'Modo de manutenção'],
            ['key' => 'upload_max_size', 'value' => '10', 'type' => 'integer', 'group' => 'storage', 'desc' => 'Tamanho máximo de upload em MB'],
        ];
        
        foreach ($defaultSettings as $setting) {
            $existing = $this->db->fetchOne("SELECT id FROM system_settings WHERE setting_key = :key", [':key' => $setting['key']]);
            if (!$existing) {
                $this->db->insert('system_settings', [
                    'setting_key' => $setting['key'],
                    'setting_value' => (string)$setting['value'],
                    'value_type' => $setting['type'],
                    'setting_group' => $setting['group'],
                    'description' => $setting['desc']
                ]);
            }
        }
        
        $formatted = [];
        foreach ($defaultSettings as $setting) {
            $formatted[$setting['key']] = $this->formatValue($setting['value'], $setting['type']);
        }
        
        return $formatted;
    }
}