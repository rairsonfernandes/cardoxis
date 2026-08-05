<?php
/**
 * CARDOXIS - Company Controller RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UserModel.php';

class CompanyController extends BaseController {
    private $db;
    private $userModel;
    private $logFile;
    private $auditLogFile;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->userModel = new UserModel();
        $this->logFile = __DIR__ . '/../logs/activity.log';
        $this->auditLogFile = __DIR__ . '/../logs/audit.log';
        
        if (!is_dir(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0777, true);
        }
    }
    
    /**
     * Obter dados da empresa
     * GET /api/v1/company
     */
    public function getCompany($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $company = $this->db->fetchOne("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM users WHERE company_id = c.id) as total_users,
                   (SELECT COUNT(*) FROM vehicles WHERE company_id = c.id) as total_vehicles,
                   (SELECT COUNT(*) FROM drivers WHERE company_id = c.id) as total_drivers
            FROM companies c
            WHERE c.id = (SELECT company_id FROM users WHERE id = :user_id)
        ", [':user_id' => $authUser['user_id']]);
        
        if (!$company) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        // Registrar atividade
        $this->logActivity(
            $authUser['user_id'],
            $company['id'],
            'company_view',
            'Visualizou dados da empresa: ' . $company['name']
        );
        
        return $this->success($company);
    }
    
    /**
     * Atualizar empresa
     * PUT /api/v1/company
     */
    public function updateCompany($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $userId = $authUser['user_id'];
        $companyId = $this->getCompanyId($userId);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        // Buscar valores antigos
        $oldCompany = $this->db->fetchOne("SELECT * FROM companies WHERE id = :id", [':id' => $companyId]);
        
        if (!$oldCompany) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        $allowedFields = ['name', 'document', 'phone', 'email', 'address', 'city', 'state', 'zip_code', 'website'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field]) && $input[$field] !== null && $input[$field] !== '') {
                $updateData[$field] = $input[$field];
            }
        }
        
        if (empty($updateData)) {
            return $this->error('Nenhum dado para atualizar', null, 400);
        }
        
        $this->db->update('companies', $updateData, 'id = :id', [':id' => $companyId]);
        
        // Registrar auditoria
        $this->logAudit(
            $userId,
            $companyId,
            'update',
            'company',
            $companyId,
            'Dados da empresa atualizados: ' . $oldCompany['name'],
            $oldCompany,
            $updateData
        );
        
        // Registrar atividade
        $this->logActivity(
            $userId,
            $companyId,
            'company_update',
            'Atualizou dados da empresa: ' . $oldCompany['name']
        );
        
        return $this->success(null, 'Empresa atualizada com sucesso');
    }
    
    /**
     * Obter configurações
     * GET /api/v1/company/settings
     */
    public function getSettings($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $settings = $this->db->fetchOne("
            SELECT settings 
            FROM companies 
            WHERE id = (SELECT company_id FROM users WHERE id = :user_id)
        ", [':user_id' => $authUser['user_id']]);
        
        $settingsData = $settings ? json_decode($settings['settings'], true) : [];
        
        // Registrar atividade
        $this->logActivity(
            $authUser['user_id'],
            $this->getCompanyId($authUser['user_id']),
            'settings_view',
            'Visualizou configurações da empresa'
        );
        
        return $this->success($settingsData);
    }
    
    /**
     * Atualizar configurações
     * PUT /api/v1/company/settings
     */
    public function updateSettings($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $userId = $authUser['user_id'];
        $companyId = $this->getCompanyId($userId);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        $oldSettings = $this->db->fetchOne("SELECT settings FROM companies WHERE id = :id", [':id' => $companyId]);
        
        $this->db->update('companies', ['settings' => json_encode($input)], 'id = :id', [':id' => $companyId]);
        
        // Registrar auditoria
        $this->logAudit(
            $userId,
            $companyId,
            'update',
            'company',
            $companyId,
            'Configurações da empresa atualizadas',
            ['settings' => $oldSettings ? $oldSettings['settings'] : null],
            ['settings' => json_encode($input)]
        );
        
        // Registrar atividade
        $this->logActivity(
            $userId,
            $companyId,
            'settings_update',
            'Atualizou configurações da empresa'
        );
        
        return $this->success(null, 'Configurações atualizadas com sucesso');
    }
    
    /**
     * Listar usuários da empresa
     * GET /api/v1/company/users
     */
    public function getUsers($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $users = $this->db->fetchAll("
            SELECT id, name, email, role, phone, status, created_at, last_login_at
            FROM users 
            WHERE company_id = (SELECT company_id FROM users WHERE id = :user_id)
            ORDER BY created_at DESC
        ", [':user_id' => $authUser['user_id']]);
        
        // Registrar atividade
        $this->logActivity(
            $authUser['user_id'],
            $this->getCompanyId($authUser['user_id']),
            'users_list',
            'Listou usuários da empresa'
        );
        
        return $this->success($users);
    }
    
    /**
     * Adicionar usuário
     * POST /api/v1/company/users
     */
    public function addUser($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $required = ['name', 'email', 'password', 'role'];
        $validation = $this->validateRequired($input, $required);
        if ($validation !== true) return $validation;
        
        if (!$this->validateEmail($input['email'])) {
            return $this->error('Email inválido', null, 400);
        }
        
        $existing = $this->userModel->findByEmail($input['email']);
        if ($existing) {
            return $this->error('Email já cadastrado', null, 409);
        }
        
        $userId = $authUser['user_id'];
        $companyId = $this->getCompanyId($userId);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        $newUserId = $this->userModel->createUser([
            'company_id' => $companyId,
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => $input['role']
        ]);
        
        // Registrar auditoria
        $this->logAudit(
            $userId,
            $companyId,
            'create',
            'user',
            $newUserId,
            'Usuário adicionado à empresa: ' . $input['name'],
            null,
            ['name' => $input['name'], 'email' => $input['email'], 'role' => $input['role']]
        );
        
        // Registrar atividade
        $this->logActivity(
            $userId,
            $companyId,
            'user_create',
            'Adicionou novo usuário: ' . $input['name']
        );
        
        return $this->success(['id' => $newUserId], 'Usuário adicionado com sucesso', 201);
    }
    
    /**
     * Obter usuário específico
     * GET /api/v1/company/users/{id}
     */
    public function getUser($id, $input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $user = $this->db->fetchOne("
            SELECT id, name, email, role, phone, status, created_at, last_login_at
            FROM users 
            WHERE id = :id AND company_id = (SELECT company_id FROM users WHERE id = :user_id)
        ", [':id' => $id, ':user_id' => $authUser['user_id']]);
        
        if (!$user) {
            return $this->error('Usuário não encontrado', null, 404);
        }
        
        return $this->success($user);
    }
    
    /**
     * Atualizar usuário
     * PUT /api/v1/company/users/{id}
     */
    public function updateUser($id, $input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $userId = $authUser['user_id'];
        $companyId = $this->getCompanyId($userId);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        // Buscar valores antigos
        $oldUser = $this->db->fetchOne("SELECT * FROM users WHERE id = :id AND company_id = :company_id", [
            ':id' => $id,
            ':company_id' => $companyId
        ]);
        
        if (!$oldUser) {
            return $this->error('Usuário não encontrado', null, 404);
        }
        
        $allowedFields = ['name', 'phone', 'role', 'status'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field]) && $input[$field] !== null && $input[$field] !== '') {
                $updateData[$field] = $input[$field];
            }
        }
        
        if (empty($updateData)) {
            return $this->error('Nenhum dado para atualizar', null, 400);
        }
        
        $this->db->update('users', $updateData, 'id = :id AND company_id = :company_id', [
            ':id' => $id,
            ':company_id' => $companyId
        ]);
        
        // Registrar auditoria
        $this->logAudit(
            $userId,
            $companyId,
            'update',
            'user',
            $id,
            'Usuário atualizado: ' . $oldUser['name'],
            $oldUser,
            $updateData
        );
        
        // Registrar atividade
        $this->logActivity(
            $userId,
            $companyId,
            'user_update',
            'Atualizou usuário: ' . $oldUser['name']
        );
        
        return $this->success(null, 'Usuário atualizado com sucesso');
    }
    
    /**
     * Remover usuário
     * DELETE /api/v1/company/users/{id}
     */
    public function deleteUser($id, $input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        if ($id == $authUser['user_id']) {
            return $this->error('Não é possível remover seu próprio usuário', null, 400);
        }
        
        $userId = $authUser['user_id'];
        $companyId = $this->getCompanyId($userId);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        // Buscar usuário antes de deletar
        $oldUser = $this->db->fetchOne("SELECT * FROM users WHERE id = :id AND company_id = :company_id", [
            ':id' => $id,
            ':company_id' => $companyId
        ]);
        
        if (!$oldUser) {
            return $this->error('Usuário não encontrado', null, 404);
        }
        
        // Registrar auditoria ANTES de deletar
        $this->logAudit(
            $userId,
            $companyId,
            'delete',
            'user',
            $id,
            'Usuário removido da empresa: ' . $oldUser['name'],
            $oldUser,
            null
        );
        
        // Registrar atividade
        $this->logActivity(
            $userId,
            $companyId,
            'user_delete',
            'Removeu usuário: ' . $oldUser['name']
        );
        
        $this->db->delete('users', 'id = :id AND company_id = :company_id', [
            ':id' => $id,
            ':company_id' => $companyId
        ]);
        
        return $this->success(null, 'Usuário removido com sucesso');
    }
    
    /**
     * Atualizar papel do usuário
     * PUT /api/v1/company/users/{id}/role
     */
    public function updateUserRole($id, $input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $role = $input['role'] ?? null;
        
        if (!$role) {
            return $this->error('Role obrigatória', null, 400);
        }
        
        $allowedRoles = ['admin', 'manager', 'user'];
        if (!in_array($role, $allowedRoles)) {
            return $this->error('Role inválida', null, 400);
        }
        
        $userId = $authUser['user_id'];
        $companyId = $this->getCompanyId($userId);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        // Buscar valores antigos
        $oldUser = $this->db->fetchOne("SELECT * FROM users WHERE id = :id AND company_id = :company_id", [
            ':id' => $id,
            ':company_id' => $companyId
        ]);
        
        if (!$oldUser) {
            return $this->error('Usuário não encontrado', null, 404);
        }
        
        $this->db->update('users', ['role' => $role], 'id = :id AND company_id = :company_id', [
            ':id' => $id,
            ':company_id' => $companyId
        ]);
        
        // Registrar auditoria
        $this->logAudit(
            $userId,
            $companyId,
            'update',
            'user',
            $id,
            'Papel do usuário atualizado: ' . $oldUser['name'] . ' (' . $oldUser['role'] . ' → ' . $role . ')',
            ['role' => $oldUser['role']],
            ['role' => $role]
        );
        
        // Registrar atividade
        $this->logActivity(
            $userId,
            $companyId,
            'user_role_update',
            'Atualizou papel do usuário: ' . $oldUser['name'] . ' (' . $oldUser['role'] . ' → ' . $role . ')'
        );
        
        return $this->success(null, 'Papel do usuário atualizado com sucesso');
    }
    
    /**
     * Obter company_id do usuário
     */
    private function getCompanyId($userId) {
        try {
            $result = $this->db->fetchOne("SELECT company_id FROM users WHERE id = :user_id", [':user_id' => $userId]);
            return $result ? $result['company_id'] : null;
        } catch (Exception $e) {
            error_log("Erro ao buscar company_id: " . $e->getMessage());
            return null;
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
            
            $logMessage = "[AUDIT] User: $userId | Action: $action | Entity: $entityType:$entityId | $description";
            $this->writeAuditLog($logMessage);
            
        } catch (Exception $e) {
            error_log("Erro ao registrar auditoria: " . $e->getMessage());
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
            
            $logMessage = "[ACTIVITY] User: $userId | Action: $action | $description";
            $this->writeActivityLog($logMessage);
            
        } catch (Exception $e) {
            error_log("Erro ao registrar atividade: " . $e->getMessage());
        }
    }
    
    private function writeActivityLog($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }
    
    private function writeAuditLog($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->auditLogFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }
}