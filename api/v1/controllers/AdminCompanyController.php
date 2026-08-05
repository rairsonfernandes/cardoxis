<?php
/**
 * CARDOXIS - Admin Company Controller  RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';

class AdminCompanyController extends BaseController {
    private $db;
    private $exportDir;
    private $basePath;
    private $logFile;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
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
     * Listar empresas
     * GET /api/v1/admin/companies
     */
    public function index($input, $params) {
        try {
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 15;
            $offset = ($page - 1) * $limit;
            $search = isset($params['search']) ? $params['search'] : '';
            $status = isset($params['status']) ? $params['status'] : '';
            
            $where = "deleted_at IS NULL";
            $whereParams = [];
            
            if (!empty($search)) {
                $where .= " AND (name LIKE :search OR document LIKE :search OR email LIKE :search)";
                $whereParams[':search'] = "%{$search}%";
            }
            
            if (!empty($status)) {
                $where .= " AND status = :status";
                $whereParams[':status'] = $status;
            }
            
            $totalResult = $this->db->fetchOne("SELECT COUNT(*) as total FROM companies WHERE {$where}", $whereParams);
            $total = $totalResult ? (int)$totalResult['total'] : 0;
            
            $companies = $this->db->fetchAll("
                SELECT 
                    id, 
                    name, 
                    document, 
                    email, 
                    phone, 
                    address, 
                    city, 
                    state, 
                    zip_code, 
                    status, 
                    created_at,
                    (SELECT COUNT(*) FROM users WHERE company_id = companies.id AND deleted_at IS NULL) as total_users,
                    (SELECT COUNT(*) FROM vehicles WHERE company_id = companies.id AND deleted_at IS NULL) as total_vehicles
                FROM companies
                WHERE {$where}
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ", array_merge($whereParams, [':limit' => $limit, ':offset' => $offset]));
            
            return $this->success([
                'data' => $companies ?: [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            error_log("AdminCompanyController index error: " . $e->getMessage());
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
     * Criar empresa
     * POST /api/v1/admin/companies
     */
    public function create($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser['user_id'] : null;
            
            $required = ['name', 'document'];
            $validation = $this->validateRequired($input, $required);
            if ($validation !== true) return $validation;
            
            $existing = $this->db->fetchOne("SELECT id FROM companies WHERE document = :document AND deleted_at IS NULL", [':document' => $input['document']]);
            if ($existing) {
                return $this->error('NIF já cadastrado', null, 409);
            }
            
            $data = [
                'name' => $input['name'],
                'document' => preg_replace('/[^0-9]/', '', $input['document']),
                'email' => $input['email'] ?? null,
                'phone' => $input['phone'] ?? null,
                'address' => $input['address'] ?? null,
                'city' => $input['city'] ?? null,
                'state' => $input['state'] ?? null,
                'zip_code' => $input['zip_code'] ?? null,
                'status' => $input['status'] ?? 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $companyId = $this->db->insert('companies', $data);
            
            // Registrar auditoria
            $this->logAudit(
                $userId,
                null,
                'create',
                'company',
                $companyId,
                'Empresa criada: ' . $data['name'],
                null,
                $data
            );
            
            return $this->success(['id' => $companyId], 'Empresa criada com sucesso', 201);
        } catch (Exception $e) {
            error_log("AdminCompanyController create error: " . $e->getMessage());
            return $this->error('Erro ao criar empresa: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Obter empresa
     * GET /api/v1/admin/companies/{id}
     */
    public function show($id, $input, $params) {
        try {
            $company = $this->db->fetchOne("
                SELECT c.*, 
                       (SELECT COUNT(*) FROM users WHERE company_id = c.id AND deleted_at IS NULL) as total_users,
                       (SELECT COUNT(*) FROM vehicles WHERE company_id = c.id AND deleted_at IS NULL) as total_vehicles
                FROM companies c
                WHERE c.id = :id AND c.deleted_at IS NULL
            ", [':id' => $id]);
            
            if (!$company) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            return $this->success($company);
        } catch (Exception $e) {
            error_log("AdminCompanyController show error: " . $e->getMessage());
            return $this->error('Erro ao buscar empresa', null, 500);
        }
    }
    
    /**
     * Atualizar empresa
     * PUT /api/v1/admin/companies/{id}
     */
    public function update($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser['user_id'] : null;
            
            $existing = $this->db->fetchOne("SELECT * FROM companies WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
            if (!$existing) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            $allowedFields = ['name', 'document', 'email', 'phone', 'address', 'city', 'state', 'zip_code', 'status'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field]) && $input[$field] !== null && $input[$field] !== '') {
                    if ($field === 'document') {
                        $updateData[$field] = preg_replace('/[^0-9]/', '', $input[$field]);
                    } else {
                        $updateData[$field] = $input[$field];
                    }
                }
            }
            
            if (empty($updateData)) {
                return $this->error('Nenhum dado para atualizar', null, 400);
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('companies', $updateData, 'id = :id', [':id' => $id]);
            
            // Registrar auditoria
            $this->logAudit(
                $userId,
                $existing['id'],
                'update',
                'company',
                $id,
                'Empresa atualizada: ' . $existing['name'],
                $existing,
                $updateData
            );
            
            return $this->success(null, 'Empresa atualizada com sucesso');
        } catch (Exception $e) {
            error_log("AdminCompanyController update error: " . $e->getMessage());
            return $this->error('Erro ao atualizar empresa: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Deletar empresa - REMOVE FISICAMENTE DA BD
     * DELETE /api/v1/admin/companies/{id}
     */
    public function delete($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser['user_id'] : null;
            
            $existing = $this->db->fetchOne("SELECT * FROM companies WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
            if (!$existing) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            $usersCount = $this->db->fetchOne("SELECT COUNT(*) as total FROM users WHERE company_id = :id AND deleted_at IS NULL", [':id' => $id]);
            if ($usersCount && $usersCount['total'] > 0) {
                return $this->error(
                    'Não é possível eliminar esta empresa pois tem ' . $usersCount['total'] . ' utilizador(es) associado(s).',
                    null, 
                    400
                );
            }
            
            $vehiclesCount = $this->db->fetchOne("SELECT COUNT(*) as total FROM vehicles WHERE company_id = :id AND deleted_at IS NULL", [':id' => $id]);
            if ($vehiclesCount && $vehiclesCount['total'] > 0) {
                return $this->error(
                    'Não é possível eliminar esta empresa pois tem ' . $vehiclesCount['total'] . ' veículo(s) associado(s).',
                    null, 
                    400
                );
            }
            
            // Registrar auditoria ANTES de deletar
            $this->logAudit(
                $userId,
                $existing['id'],
                'delete',
                'company',
                $id,
                'Empresa removida permanentemente: ' . $existing['name'],
                $existing,
                null
            );
            
            $this->db->delete('companies', 'id = :id', [':id' => $id]);
            
            error_log("Empresa ID {$id} ('{$existing['name']}') eliminada por utilizador " . ($userId ?? 'desconhecido'));
            
            return $this->success(null, 'Empresa removida permanentemente com sucesso');
        } catch (Exception $e) {
            error_log("AdminCompanyController delete error: " . $e->getMessage());
            return $this->error('Erro ao remover empresa: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Ativar empresa
     * POST /api/v1/admin/companies/{id}/activate
     */
    public function activate($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser['user_id'] : null;
            
            $existing = $this->db->fetchOne("SELECT * FROM companies WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
            if (!$existing) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            $this->db->update('companies', [
                'status' => 'active', 
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', [':id' => $id]);
            
            $this->logAudit(
                $userId,
                $existing['id'],
                'update',
                'company',
                $id,
                'Empresa ativada: ' . $existing['name'],
                ['status' => $existing['status']],
                ['status' => 'active']
            );
            
            return $this->success(null, 'Empresa ativada com sucesso');
        } catch (Exception $e) {
            error_log("AdminCompanyController activate error: " . $e->getMessage());
            return $this->error('Erro ao ativar empresa: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Suspender empresa
     * POST /api/v1/admin/companies/{id}/suspend
     */
    public function suspend($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser['user_id'] : null;
            
            $existing = $this->db->fetchOne("SELECT * FROM companies WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
            if (!$existing) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            $this->db->update('companies', [
                'status' => 'suspended', 
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', [':id' => $id]);
            
            $this->logAudit(
                $userId,
                $existing['id'],
                'update',
                'company',
                $id,
                'Empresa suspensa: ' . $existing['name'],
                ['status' => $existing['status']],
                ['status' => 'suspended']
            );
            
            return $this->success(null, 'Empresa suspensa com sucesso');
        } catch (Exception $e) {
            error_log("AdminCompanyController suspend error: " . $e->getMessage());
            return $this->error('Erro ao suspender empresa: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * EXPORTAR EMPRESAS
     * GET /api/v1/admin/companies/export
     */
    public function export($input, $params) {
        try {
            $search = $params['search'] ?? '';
            $status = $params['status'] ?? '';
            
            $where = "deleted_at IS NULL";
            $whereParams = [];
            
            if (!empty($search)) {
                $where .= " AND (name LIKE :search OR document LIKE :search OR email LIKE :search)";
                $whereParams[':search'] = "%{$search}%";
            }
            
            if (!empty($status)) {
                $where .= " AND status = :status";
                $whereParams[':status'] = $status;
            }
            
            $companies = $this->db->fetchAll("
                SELECT 
                    id,
                    name, 
                    document, 
                    email, 
                    phone, 
                    address, 
                    city, 
                    state, 
                    zip_code, 
                    status, 
                    created_at,
                    (SELECT COUNT(*) FROM users WHERE company_id = companies.id AND deleted_at IS NULL) as total_users,
                    (SELECT COUNT(*) FROM vehicles WHERE company_id = companies.id AND deleted_at IS NULL) as total_vehicles
                FROM companies
                WHERE {$where}
                ORDER BY created_at DESC
            ", $whereParams);
            
            $headers = ['ID', 'Nome', 'NIF', 'Email', 'Telefone', 'Morada', 'Cidade', 'Distrito', 'Código Postal', 'Estado', 'Utilizadores', 'Veículos', 'Data Registo'];
            
            $data = [];
            foreach ($companies as $c) {
                $data[] = [
                    $c['id'],
                    $c['name'],
                    $c['document'],
                    $c['email'] ?? '-',
                    $c['phone'] ?? '-',
                    $c['address'] ?? '-',
                    $c['city'] ?? '-',
                    $c['state'] ?? '-',
                    $c['zip_code'] ?? '-',
                    $this->translateStatus($c['status']),
                    $c['total_users'] ?? 0,
                    $c['total_vehicles'] ?? 0,
                    date('d/m/Y H:i', strtotime($c['created_at']))
                ];
            }
            
            $timestamp = date('Ymd_His');
            $filename = "empresas_{$timestamp}.csv";
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
            error_log("AdminCompanyController export error: " . $e->getMessage());
            return $this->error('Erro ao exportar empresas: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Traduzir status
     */
    private function translateStatus($status) {
        $statuses = [
            'active' => 'Activa',
            'inactive' => 'Inactiva',
            'suspended' => 'Suspensa'
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