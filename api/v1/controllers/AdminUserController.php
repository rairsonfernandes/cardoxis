<?php
/**
 * CARDOXIS - Admin User Controller  RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UserModel.php';

class AdminUserController extends BaseController {
    private $db;
    private $userModel;
    private $exportDir;
    private $basePath;
    private $logFile;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->userModel = new UserModel();
        
        $this->basePath = dirname(__DIR__, 3);
        $this->exportDir = $this->basePath . '/storage/exports/';
        $this->logFile = __DIR__ . '/../logs/audit.log';
        
        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir, 0777, true);
        }
        
        if (!is_dir(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0777, true);
        }
    }
    
    /**
     * Listar utilizadores
     * GET /api/v1/admin/users
     */
    public function index($input, $params) {
        try {
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 15;
            $offset = ($page - 1) * $limit;
            $search = $params['search'] ?? '';
            $role = $params['role'] ?? '';
            $status = $params['status'] ?? '';
            
            $where = "1=1";
            $whereParams = [];
            
            if (!empty($search)) {
                $where .= " AND (u.name LIKE :search OR u.email LIKE :search)";
                $whereParams[':search'] = "%{$search}%";
            }
            
            if (!empty($role)) {
                $where .= " AND u.role = :role";
                $whereParams[':role'] = $role;
            }
            
            if (!empty($status)) {
                $where .= " AND u.status = :status";
                $whereParams[':status'] = $status;
            }
            
            $totalResult = $this->db->fetchOne("SELECT COUNT(*) as total FROM users u WHERE {$where}", $whereParams);
            $total = $totalResult ? (int)$totalResult['total'] : 0;
            
            $users = $this->db->fetchAll("
                SELECT u.id, u.name, u.email, u.role, u.phone, u.status, u.created_at, u.last_login_at, c.name as company_name
                FROM users u
                LEFT JOIN companies c ON u.company_id = c.id
                WHERE {$where}
                ORDER BY u.created_at DESC
                LIMIT :limit OFFSET :offset
            ", array_merge($whereParams, [':limit' => $limit, ':offset' => $offset]));
            
            return $this->success([
                'data' => $users ?: [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            error_log("AdminUserController index error: " . $e->getMessage());
            return $this->success([
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                    'last_page' => 1
                ]
            ]);
        }
    }
    
    /**
     * Criar utilizador
     * POST /api/v1/admin/users
     */
    public function create($input, $params) {
        try {
            error_log("AdminUserController create called with input: " . json_encode($input));
            
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser['user_id'] : null;
            
            $required = ['name', 'email', 'password', 'role'];
            $validation = $this->validateRequired($input, $required);
            if ($validation !== true) return $validation;
            
            if (!$this->validateEmail($input['email'])) {
                return $this->error('Email inválido', null, 400);
            }
            
            if (strlen($input['password']) < 6) {
                return $this->error('A senha deve ter no mínimo 6 caracteres', null, 400);
            }
            
            $existing = $this->userModel->findByEmail($input['email']);
            if ($existing) {
                return $this->error('Email já cadastrado', null, 409);
            }
            
            $companyId = null;
            if (isset($input['company_id']) && !empty($input['company_id'])) {
                $companyId = $input['company_id'];
            } else {
                $company = $this->db->fetchOne("SELECT id FROM companies LIMIT 1");
                $companyId = $company ? $company['id'] : null;
            }
            
            $data = [
                'company_id' => $companyId,
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'role' => $input['role'],
                'phone' => $input['phone'] ?? null,
                'status' => $input['status'] ?? 'active'
            ];
            
            $newUserId = $this->userModel->createUser($data);
            
            if (!$newUserId) {
                return $this->error('Erro ao criar utilizador', null, 500);
            }
            
            // Registrar auditoria
            $this->logAudit(
                $userId,
                $companyId,
                'create',
                'user',
                $newUserId,
                'Utilizador criado: ' . $data['name'],
                null,
                $data
            );
            
            return $this->success(['id' => $newUserId], 'Utilizador criado com sucesso', 201);
        } catch (Exception $e) {
            error_log("AdminUserController create error: " . $e->getMessage());
            return $this->error('Erro ao criar utilizador: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Obter utilizador
     * GET /api/v1/admin/users/{id}
     */
    public function show($id, $input, $params) {
        try {
            $user = $this->db->fetchOne("
                SELECT u.id, u.name, u.email, u.role, u.phone, u.status, u.created_at, u.last_login_at, u.company_id, c.name as company_name
                FROM users u
                LEFT JOIN companies c ON u.company_id = c.id
                WHERE u.id = :id
            ", [':id' => $id]);
            
            if (!$user) {
                return $this->error('Utilizador não encontrado', null, 404);
            }
            
            unset($user['password']);
            
            return $this->success($user);
        } catch (Exception $e) {
            error_log("AdminUserController show error: " . $e->getMessage());
            return $this->error('Erro ao buscar utilizador', null, 500);
        }
    }
    
    /**
     * Atualizar utilizador
     * PUT /api/v1/admin/users/{id}
     */
    public function update($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser['user_id'] : null;
            
            $oldUser = $this->db->fetchOne("SELECT * FROM users WHERE id = :id", [':id' => $id]);
            if (!$oldUser) {
                return $this->error('Utilizador não encontrado', null, 404);
            }
            
            $allowedFields = ['name', 'email', 'phone', 'role', 'status', 'company_id'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field]) && $input[$field] !== null && $input[$field] !== '') {
                    $updateData[$field] = $input[$field];
                }
            }
            
            if (empty($updateData) && (!isset($input['password']) || empty($input['password']))) {
                return $this->error('Nenhum dado para atualizar', null, 400);
            }
            
            if (isset($updateData['email'])) {
                $existingEmail = $this->db->fetchOne("SELECT id FROM users WHERE email = :email AND id != :id", [
                    ':email' => $updateData['email'],
                    ':id' => $id
                ]);
                if ($existingEmail) {
                    return $this->error('Email já cadastrado para outro utilizador', null, 409);
                }
            }
            
            if (isset($input['password']) && !empty($input['password'])) {
                if (strlen($input['password']) < 6) {
                    return $this->error('A senha deve ter no mínimo 6 caracteres', null, 400);
                }
                $updateData['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            
            if (!empty($updateData)) {
                $this->db->update('users', $updateData, 'id = :id', [':id' => $id]);
            }
            
            // Registrar auditoria
            $this->logAudit(
                $userId,
                $oldUser['company_id'],
                'update',
                'user',
                $id,
                'Utilizador atualizado: ' . $oldUser['name'],
                $oldUser,
                $updateData
            );
            
            return $this->success(null, 'Utilizador actualizado com sucesso');
        } catch (Exception $e) {
            error_log("AdminUserController update error: " . $e->getMessage());
            return $this->error('Erro ao actualizar utilizador', null, 500);
        }
    }
    
    /**
     * Remover utilizador
     * DELETE /api/v1/admin/users/{id}
     */
    public function delete($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser['user_id'] : null;
            
            $oldUser = $this->db->fetchOne("SELECT * FROM users WHERE id = :id", [':id' => $id]);
            if (!$oldUser) {
                return $this->error('Utilizador não encontrado', null, 404);
            }
            
            if ($authUser && isset($authUser['user_id']) && $authUser['user_id'] == $id) {
                return $this->error('Não é possível eliminar o seu próprio utilizador', null, 400);
            }
            
            // Registrar auditoria ANTES de deletar
            $this->logAudit(
                $userId,
                $oldUser['company_id'],
                'delete',
                'user',
                $id,
                'Utilizador removido: ' . $oldUser['name'],
                $oldUser,
                null
            );
            
            $this->db->delete('users', 'id = :id', [':id' => $id]);
            
            return $this->success(null, 'Utilizador removido com sucesso');
        } catch (Exception $e) {
            error_log("AdminUserController delete error: " . $e->getMessage());
            return $this->error('Erro ao remover utilizador', null, 500);
        }
    }
    
    /**
     * Ativar utilizador
     * POST /api/v1/admin/users/{id}/activate
     */
    public function activate($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser['user_id'] : null;
            
            $oldUser = $this->db->fetchOne("SELECT * FROM users WHERE id = :id", [':id' => $id]);
            if (!$oldUser) {
                return $this->error('Utilizador não encontrado', null, 404);
            }
            
            $this->db->update('users', ['status' => 'active'], 'id = :id', [':id' => $id]);
            
            $this->logAudit(
                $userId,
                $oldUser['company_id'],
                'update',
                'user',
                $id,
                'Utilizador ativado: ' . $oldUser['name'],
                ['status' => $oldUser['status']],
                ['status' => 'active']
            );
            
            return $this->success(null, 'Utilizador activado com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao activar utilizador', null, 500);
        }
    }
    
    /**
     * Suspender utilizador
     * POST /api/v1/admin/users/{id}/suspend
     */
    public function suspend($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser['user_id'] : null;
            
            $oldUser = $this->db->fetchOne("SELECT * FROM users WHERE id = :id", [':id' => $id]);
            if (!$oldUser) {
                return $this->error('Utilizador não encontrado', null, 404);
            }
            
            $this->db->update('users', ['status' => 'suspended'], 'id = :id', [':id' => $id]);
            
            $this->logAudit(
                $userId,
                $oldUser['company_id'],
                'update',
                'user',
                $id,
                'Utilizador suspenso: ' . $oldUser['name'],
                ['status' => $oldUser['status']],
                ['status' => 'suspended']
            );
            
            return $this->success(null, 'Utilizador suspenso com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao suspender utilizador', null, 500);
        }
    }
    
    /**
     * Resetar senha
     * POST /api/v1/admin/users/{id}/reset-password
     */
    public function resetPassword($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser['user_id'] : null;
            
            $oldUser = $this->db->fetchOne("SELECT * FROM users WHERE id = :id", [':id' => $id]);
            if (!$oldUser) {
                return $this->error('Utilizador não encontrado', null, 404);
            }
            
            $newPassword = $this->generateRandomPassword();
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $this->db->update('users', ['password' => $hashedPassword], 'id = :id', [':id' => $id]);
            
            $this->logAudit(
                $userId,
                $oldUser['company_id'],
                'update',
                'user',
                $id,
                'Senha redefinida para: ' . $oldUser['name'],
                null,
                ['password_reset' => true]
            );
            
            return $this->success(['new_password' => $newPassword], 'Senha redefinida com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao redefinir senha', null, 500);
        }
    }
    
    /**
     * Atualizar papel
     * PUT /api/v1/admin/users/{id}/role
     */
    public function updateRole($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser['user_id'] : null;
            
            $role = $input['role'] ?? null;
            
            if (!$role) {
                return $this->error('Papel obrigatório', null, 400);
            }
            
            $allowedRoles = ['super_admin', 'admin', 'manager', 'user'];
            if (!in_array($role, $allowedRoles)) {
                return $this->error('Papel inválido', null, 400);
            }
            
            $oldUser = $this->db->fetchOne("SELECT * FROM users WHERE id = :id", [':id' => $id]);
            if (!$oldUser) {
                return $this->error('Utilizador não encontrado', null, 404);
            }
            
            $this->db->update('users', ['role' => $role], 'id = :id', [':id' => $id]);
            
            $this->logAudit(
                $userId,
                $oldUser['company_id'],
                'update',
                'user',
                $id,
                'Papel do utilizador atualizado: ' . $oldUser['name'] . ' (' . $oldUser['role'] . ' → ' . $role . ')',
                ['role' => $oldUser['role']],
                ['role' => $role]
            );
            
            return $this->success(null, 'Papel do utilizador actualizado com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao actualizar papel', null, 500);
        }
    }
    
    /**
     * Listar papéis disponíveis
     * GET /api/v1/admin/users/roles
     */
    public function getRoles($input, $params) {
        $roles = [
            ['value' => 'super_admin', 'label' => 'Super Administrador', 'description' => 'Acesso total ao sistema'],
            ['value' => 'admin', 'label' => 'Administrador', 'description' => 'Acesso administrativo completo'],
            ['value' => 'manager', 'label' => 'Gestor', 'description' => 'Gerencia utilizadores da empresa'],
            ['value' => 'user', 'label' => 'Utilizador', 'description' => 'Acesso básico ao sistema']
        ];
        
        return $this->success($roles);
    }
    
    /**
     * EXPORTAR UTILIZADORES
     * GET /api/v1/admin/users/export
     */
    public function export($input, $params) {
        try {
            $search = $params['search'] ?? '';
            $role = $params['role'] ?? '';
            $status = $params['status'] ?? '';
            
            $where = "1=1";
            $whereParams = [];
            
            if (!empty($search)) {
                $where .= " AND (u.name LIKE :search OR u.email LIKE :search)";
                $whereParams[':search'] = "%{$search}%";
            }
            
            if (!empty($role)) {
                $where .= " AND u.role = :role";
                $whereParams[':role'] = $role;
            }
            
            if (!empty($status)) {
                $where .= " AND u.status = :status";
                $whereParams[':status'] = $status;
            }
            
            $users = $this->db->fetchAll("
                SELECT u.id, u.name, u.email, u.role, u.phone, u.status, u.created_at, u.last_login_at, c.name as company_name
                FROM users u
                LEFT JOIN companies c ON u.company_id = c.id
                WHERE {$where}
                ORDER BY u.created_at DESC
            ", $whereParams);
            
            $headers = ['ID', 'Nome', 'Email', 'Papel', 'Telefone', 'Estado', 'Empresa', 'Data Registo', 'Último Acesso'];
            
            $data = [];
            foreach ($users as $u) {
                $data[] = [
                    $u['id'],
                    $u['name'],
                    $u['email'],
                    $this->translateRole($u['role']),
                    $u['phone'] ?? '-',
                    $this->translateStatus($u['status']),
                    $u['company_name'] ?? '-',
                    date('d/m/Y H:i', strtotime($u['created_at'])),
                    $u['last_login_at'] ? date('d/m/Y H:i', strtotime($u['last_login_at'])) : 'Nunca'
                ];
            }
            
            $timestamp = date('Ymd_His');
            $filename = "utilizadores_{$timestamp}.csv";
            $filepath = $this->exportDir . $filename;
            
            $file = fopen($filepath, 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers, ';');
            foreach ($data as $row) {
                fputcsv($file, $row, ';');
            }
            fclose($file);
            
            return $this->success([
                'filename' => $filename,
                'download_url' => '/cardoxis/storage/exports/' . $filename,
                'total' => count($data),
                'format' => 'csv'
            ], 'Exportação gerada com sucesso');
            
        } catch (Exception $e) {
            error_log("AdminUserController export error: " . $e->getMessage());
            return $this->error('Erro ao exportar utilizadores: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * IMPORTAR UTILIZADORES
     * POST /api/v1/admin/users/import
     */
    public function import($input, $params) {
        try {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                return $this->error('Nenhum ficheiro enviado', null, 400);
            }
            
            $file = $_FILES['file'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($extension !== 'csv') {
                return $this->error('Formato de ficheiro não suportado. Use apenas CSV.', null, 400);
            }
            
            $data = $this->processCSV($file['tmp_name']);
            
            if (empty($data)) {
                return $this->error('Ficheiro vazio ou formato inválido', null, 400);
            }
            
            $created = 0;
            $updated = 0;
            $failed = 0;
            $errors = [];
            
            $companies = $this->db->fetchAll("SELECT id, name FROM companies ORDER BY name");
            $companyMap = [];
            foreach ($companies as $c) {
                $companyMap[strtolower(trim($c['name']))] = $c['id'];
            }
            
            foreach ($data as $row) {
                try {
                    if (empty($row['nome']) || empty($row['email']) || empty($row['papel'])) {
                        $failed++;
                        $errors[] = "Dados incompletos para: " . ($row['email'] ?? 'sem email');
                        continue;
                    }
                    
                    $existing = $this->userModel->findByEmail($row['email']);
                    
                    $role = strtolower(trim($row['papel']));
                    $validRoles = ['super_admin', 'admin', 'manager', 'user'];
                    if (!in_array($role, $validRoles)) {
                        $role = 'user';
                    }
                    
                    $status = strtolower(trim($row['estado'] ?? 'active'));
                    if (!in_array($status, ['active', 'inactive', 'suspended'])) {
                        $status = 'active';
                    }
                    
                    $companyId = null;
                    if (!empty($row['empresa'])) {
                        $companyName = strtolower(trim($row['empresa']));
                        if (isset($companyMap[$companyName])) {
                            $companyId = $companyMap[$companyName];
                        }
                    }
                    
                    $password = $row['senha'] ?? $this->generateRandomPassword(8);
                    
                    if ($existing) {
                        $updateData = [
                            'name' => $row['nome'],
                            'role' => $role,
                            'phone' => $row['telefone'] ?? null,
                            'status' => $status,
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        if ($companyId) {
                            $updateData['company_id'] = $companyId;
                        }
                        $this->db->update('users', $updateData, 'id = :id', [':id' => $existing['id']]);
                        $updated++;
                    } else {
                        $createData = [
                            'company_id' => $companyId,
                            'name' => $row['nome'],
                            'email' => $row['email'],
                            'password' => $password,
                            'role' => $role,
                            'phone' => $row['telefone'] ?? null,
                            'status' => $status
                        ];
                        $this->userModel->createUser($createData);
                        $created++;
                    }
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Erro ao processar " . ($row['email'] ?? 'linha') . ": " . $e->getMessage();
                }
            }
            
            return $this->success([
                'total' => count($data),
                'created' => $created,
                'updated' => $updated,
                'failed' => $failed,
                'errors' => $errors
            ], "Importação concluída: {$created} criados, {$updated} actualizados, {$failed} falhas");
            
        } catch (Exception $e) {
            error_log("AdminUserController import error: " . $e->getMessage());
            return $this->error('Erro ao importar ficheiro: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Processar ficheiro CSV
     */
    private function processCSV($filepath) {
        $data = [];
        $handle = fopen($filepath, 'r');
        
        if (!$handle) {
            return $data;
        }
        
        $firstLine = fgets($handle);
        rewind($handle);
        
        $separator = ';';
        if (strpos($firstLine, ',') !== false && strpos($firstLine, ';') === false) {
            $separator = ',';
        }
        
        $headers = fgetcsv($handle, 0, $separator);
        
        if (!$headers) {
            fclose($handle);
            return $data;
        }
        
        $headers = array_map(function($h) {
            $h = trim($h);
            $h = strtolower($h);
            $h = preg_replace('/[^a-z0-9]/', '', $h);
            return $h;
        }, $headers);
        
        $map = [];
        $fieldMap = [
            'nome' => ['nome', 'name', 'nomes', 'utilizador', 'usuario'],
            'email' => ['email', 'mail', 'e-mail'],
            'papel' => ['papel', 'role', 'perfil', 'funcao', 'função'],
            'telefone' => ['telefone', 'phone', 'telefono', 'contacto', 'contato'],
            'estado' => ['estado', 'status', 'situacao', 'situação'],
            'senha' => ['senha', 'password', 'pass', 'palavrapasse'],
            'empresa' => ['empresa', 'company', 'companhia']
        ];
        
        foreach ($headers as $index => $header) {
            foreach ($fieldMap as $field => $aliases) {
                if (in_array($header, $aliases)) {
                    $map[$field] = $index;
                    break;
                }
            }
        }
        
        if (!isset($map['nome']) && isset($headers[0])) {
            $map['nome'] = 0;
        }
        if (!isset($map['email']) && isset($headers[1])) {
            $map['email'] = 1;
        }
        if (!isset($map['papel']) && isset($headers[2])) {
            $map['papel'] = 2;
        }
        
        while (($row = fgetcsv($handle, 0, $separator)) !== false) {
            if (count($row) < 2) continue;
            
            $entry = [];
            if (isset($map['nome'])) $entry['nome'] = trim($row[$map['nome']] ?? '');
            if (isset($map['email'])) $entry['email'] = trim($row[$map['email']] ?? '');
            if (isset($map['papel'])) $entry['papel'] = trim($row[$map['papel']] ?? '');
            if (isset($map['telefone'])) $entry['telefone'] = trim($row[$map['telefone']] ?? '');
            if (isset($map['estado'])) $entry['estado'] = trim($row[$map['estado']] ?? '');
            if (isset($map['senha'])) $entry['senha'] = trim($row[$map['senha']] ?? '');
            if (isset($map['empresa'])) $entry['empresa'] = trim($row[$map['empresa']] ?? '');
            
            if (!empty($entry['nome']) && !empty($entry['email'])) {
                $data[] = $entry;
            }
        }
        
        fclose($handle);
        return $data;
    }
    
    /**
     * Gerar senha aleatória
     */
    private function generateRandomPassword($length = 10) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
    
    /**
     * Traduzir papel para exibição
     */
    private function translateRole($role) {
        $roles = [
            'super_admin' => 'Super Administrador',
            'admin' => 'Administrador',
            'manager' => 'Gestor',
            'user' => 'Utilizador'
        ];
        return $roles[$role] ?? $role;
    }
    
    /**
     * Traduzir status para exibição
     */
    private function translateStatus($status) {
        $statuses = [
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            'suspended' => 'Suspenso'
        ];
        return $statuses[$status] ?? $status;
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
            $this->writeLog($logMessage);
            
        } catch (Exception $e) {
            error_log("Erro ao registrar auditoria: " . $e->getMessage());
        }
    }
    
    private function writeLog($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }
}