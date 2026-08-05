<?php
/**
 * CARDOXIS - Settings Controller RF 
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

class SettingsController extends BaseController {
    private $db;
    private $userModel;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->userModel = new UserModel();
    }
    
    /**
     * Obter todas as configurações
     * GET /api/v1/settings
     */
    public function getSettings($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $userId = (int)$authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            $companyId = $this->getCompanyId($userId);
            
            // Verificar permissão - apenas admin pode ver configurações
            if (!PermissionMiddleware::isAdmin(['id' => $userId, 'role' => $userRole])) {
                return $this->error('Sem permissão para aceder às configurações', null, 403);
            }
            
            // Buscar configurações do sistema
            $settings = $this->getAllSettings($companyId);
            
            return $this->success($settings);
            
        } catch (Exception $e) {
            error_log("[SETTINGS] getSettings error: " . $e->getMessage());
            return $this->error('Erro ao carregar configurações', null, 500);
        }
    }
    
    /**
     * Atualizar configurações
     * PUT /api/v1/settings
     */
    public function updateSettings($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $userId = (int)$authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            $companyId = $this->getCompanyId($userId);
            
            // Verificar permissão - apenas admin pode atualizar configurações
            if (!PermissionMiddleware::isAdmin(['id' => $userId, 'role' => $userRole])) {
                return $this->error('Sem permissão para atualizar configurações', null, 403);
            }
            
            $allowedSections = ['general', 'security', 'notifications', 'appearance', 'system'];
            $updated = [];
            
            foreach ($allowedSections as $section) {
                if (isset($input[$section]) && is_array($input[$section])) {
                    foreach ($input[$section] as $key => $value) {
                        $settingKey = $section . '_' . $key;
                        $this->updateSetting($companyId, $settingKey, $value);
                        $updated[$section][$key] = $value;
                    }
                }
            }
            
            return $this->success([
                'updated' => $updated,
                'message' => 'Configurações atualizadas com sucesso'
            ]);
            
        } catch (Exception $e) {
            error_log("[SETTINGS] updateSettings error: " . $e->getMessage());
            return $this->error('Erro ao atualizar configurações', null, 500);
        }
    }
    
    /**
     * Obter configurações do sistema
     */
    private function getAllSettings($companyId) {
        $settings = [
            'general' => [
                'company_name' => 'CARDOXIS',
                'company_email' => 'contato@cardoxis.com',
                'company_phone' => '+351 300 123 456',
                'company_address' => 'Lisboa, Portugal',
                'timezone' => 'Europe/Lisbon',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'currency' => 'EUR',
                'currency_symbol' => '€',
                'language' => 'pt-PT'
            ],
            'security' => [
                'two_factor_auth' => false,
                'session_timeout' => 120,
                'max_login_attempts' => 5,
                'password_min_length' => 8,
                'require_special_char' => true,
                'require_uppercase' => true,
                'require_numbers' => true
            ],
            'notifications' => [
                'email_notifications' => true,
                'system_alerts' => true,
                'maintenance_reminders' => true,
                'document_expiry' => true,
                'insurance_expiry' => true,
                'fuel_alerts' => true,
                'fine_alerts' => true,
                'report_generation' => true,
                'marketing_emails' => false
            ],
            'appearance' => [
                'theme' => 'light',
                'sidebar_collapsed' => false,
                'compact_mode' => false,
                'primary_color' => '#2563eb',
                'accent_color' => '#0ea5e9'
            ],
            'system' => [
                'maintenance_mode' => false,
                'backup_enabled' => true,
                'backup_frequency' => 'daily',
                'backup_retention' => 30,
                'log_level' => 'info',
                'api_rate_limit' => 100,
                'upload_max_size' => 10,
                'allowed_file_types' => ['pdf', 'jpg', 'png', 'doc', 'docx', 'xls', 'xlsx']
            ]
        ];
        
        // Buscar configurações do banco de dados
        try {
            $dbSettings = $this->db->fetchAll(
                "SELECT setting_key, setting_value FROM system_settings WHERE company_id = :company_id OR company_id IS NULL",
                [':company_id' => $companyId]
            );
            
            foreach ($dbSettings as $row) {
                $key = $row['setting_key'];
                $value = $row['setting_value'];
                
                // Parse JSON se for JSON
                if (strpos($value, '{') === 0 || strpos($value, '[') === 0) {
                    $value = json_decode($value, true);
                }
                
                // Tentar encontrar a seção e atualizar
                foreach ($settings as $section => &$sectionSettings) {
                    if (strpos($key, $section . '_') === 0) {
                        $settingName = substr($key, strlen($section) + 1);
                        if (isset($sectionSettings[$settingName])) {
                            $sectionSettings[$settingName] = $value;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("[SETTINGS] getAllSettings DB error: " . $e->getMessage());
        }
        
        return $settings;
    }
    
    /**
     * Atualizar uma configuração
     */
    private function updateSetting($companyId, $key, $value) {
        try {
            // Converter arrays para JSON
            if (is_array($value)) {
                $value = json_encode($value);
            }
            
            // Verificar se a configuração já existe
            $existing = $this->db->fetchOne(
                "SELECT id FROM system_settings WHERE company_id = :company_id AND setting_key = :key",
                [':company_id' => $companyId, ':key' => $key]
            );
            
            if ($existing) {
                $this->db->update(
                    'system_settings',
                    ['setting_value' => $value],
                    'company_id = :company_id AND setting_key = :key',
                    [':company_id' => $companyId, ':key' => $key]
                );
            } else {
                $this->db->insert('system_settings', [
                    'company_id' => $companyId,
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("[SETTINGS] updateSetting error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter company_id do usuário
     */
    private function getCompanyId($userId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT company_id FROM users WHERE id = :user_id",
                [':user_id' => $userId]
            );
            return $result ? (int)$result['company_id'] : null;
        } catch (Exception $e) {
            error_log("[SETTINGS] getCompanyId error: " . $e->getMessage());
            return null;
        }
    }
}