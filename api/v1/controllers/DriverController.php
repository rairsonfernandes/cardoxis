<?php
/**
 * CARDOXIS - Driver Controller  RF
 * 
 * 
 * REGRAS DE NEGÓCIO:
 * 1. Ao CADASTRAR motorista: mostrar APENAS veículos SEM motorista
 * 2. Ao EDITAR motorista: mostrar veículos SEM motorista + veículos do motorista atual
 * 3. Veículos de OUTROS motoristas NUNCA aparecem
 * 4. Ao EXCLUIR motorista: desassociar veículos automaticamente
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

class DriverController extends BaseController {
    private $db;
    private $logFile;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logFile = __DIR__ . '/../logs/audit.log';
        
        if (!is_dir(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0777, true);
        }
    }
    
    /**
     * Listar motoristas
     * GET /api/v1/drivers
     */
    public function index($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            if (!$user['company_id']) {
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
            
            $perm = PermissionMiddleware::scope($user, 'drivers', 'd');
            
            $limit = isset($params['limit']) ? (int)$params['limit'] : 15;
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $offset = ($page - 1) * $limit;
            $status = isset($params['status']) ? $params['status'] : '';
            $search = isset($params['search']) ? trim($params['search']) : '';
            
            $where = $perm['where'];
            $paramsDb = $perm['params'];
            
            if (!empty($status) && $status !== 'all') {
                $where .= " AND d.status = :status";
                $paramsDb[':status'] = $status;
            }
            
            if (!empty($search)) {
                $where .= " AND (d.name LIKE :search OR d.document LIKE :search OR d.license_number LIKE :search)";
                $paramsDb[':search'] = "%{$search}%";
            }
            
            $totalResult = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM drivers d WHERE {$where}",
                $paramsDb
            );
            $total = $totalResult ? (int)$totalResult['total'] : 0;
            
            $drivers = $this->db->fetchAll("
                SELECT d.*, 
                       COUNT(v.id) as vehicles_count,
                       GROUP_CONCAT(v.plate SEPARATOR ', ') as vehicles_plates,
                       u.name as created_by_name
                FROM drivers d
                LEFT JOIN vehicles v ON d.id = v.driver_id AND (v.deleted_at IS NULL OR v.deleted_at = '0000-00-00 00:00:00')
                LEFT JOIN users u ON d.created_by = u.id
                WHERE {$where}
                GROUP BY d.id
                ORDER BY d.created_at DESC
                LIMIT :limit OFFSET :offset
            ", array_merge($paramsDb, [':limit' => $limit, ':offset' => $offset]));
            
            return $this->success([
                'data' => $drivers ?: [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ],
                'user_role' => $user['role']
            ]);
            
        } catch (Exception $e) {
            error_log("DriverController index error: " . $e->getMessage());
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
     * Criar motorista
     * POST /api/v1/drivers
     * 
     * REGRA: Ao cadastrar, mostrar APENAS veículos SEM motorista
     */
    public function create($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $required = ['name', 'license_number', 'license_category', 'license_expiry_date'];
            $validation = $this->validateRequired($input, $required);
            if ($validation !== true) {
                return $validation;
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            if (!$user['company_id']) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            if (isset($input['document']) && !empty($input['document'])) {
                $existing = $this->db->fetchOne(
                    "SELECT id FROM drivers WHERE document = :document AND company_id = :company_id AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')", 
                    [':document' => $input['document'], ':company_id' => $user['company_id']]
                );
                
                if ($existing) {
                    return $this->error('NIF já cadastrado na empresa', null, 409);
                }
            }
            
            $data = [
                'company_id' => $user['company_id'],
                'name' => $input['name'],
                'document' => $input['document'] ?? null,
                'phone' => $input['phone'] ?? null,
                'email' => $input['email'] ?? null,
                'address' => $input['address'] ?? null,
                'license_number' => $input['license_number'],
                'license_category' => strtoupper($input['license_category']),
                'license_expiry_date' => $input['license_expiry_date'],
                'license_issue_date' => $input['license_issue_date'] ?? null,
                'hire_date' => $input['hire_date'] ?? null,
                'status' => $input['status'] ?? 'active',
                'created_by' => $user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $driverId = $this->db->insert('drivers', $data);
            
            // REGRA: Associar veículos SOMENTE se estiverem disponíveis (sem motorista)
            if (isset($input['vehicle_ids']) && is_array($input['vehicle_ids']) && !empty($input['vehicle_ids'])) {
                foreach ($input['vehicle_ids'] as $vehicleId) {
                    // Verificar se o veículo está disponível (sem motorista)
                    $vehicleCheck = $this->db->fetchOne(
                        "SELECT id, driver_id FROM vehicles WHERE id = :id AND company_id = :company_id AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')",
                        [':id' => $vehicleId, ':company_id' => $user['company_id']]
                    );
                    
                    if ($vehicleCheck && $vehicleCheck['driver_id'] === null) {
                        $this->db->update('vehicles', 
                            ['driver_id' => $driverId], 
                            'id = :id', 
                            [':id' => $vehicleId]
                        );
                    }
                }
            }
            
            // Registrar auditoria
            $this->logAudit(
                $user['id'],
                $user['company_id'],
                'create',
                'driver',
                $driverId,
                'Motorista criado',
                null,
                $data
            );
            
            return $this->success(['id' => $driverId], 'Motorista criado com sucesso', 201);
            
        } catch (Exception $e) {
            error_log("DriverController create error: " . $e->getMessage());
            return $this->error('Erro ao criar motorista: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Obter motorista específico
     * GET /api/v1/drivers/{id}
     */
    public function show($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            if (!$user['company_id']) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            if (!PermissionMiddleware::check($user, 'drivers', $id)) {
                return $this->error('Sem permissão para visualizar este motorista', null, 403);
            }
            
            $driver = $this->db->fetchOne("
                SELECT d.*, 
                       GROUP_CONCAT(v.plate SEPARATOR ', ') as vehicles_plates,
                       COUNT(v.id) as vehicles_count,
                       u.name as created_by_name
                FROM drivers d
                LEFT JOIN vehicles v ON d.id = v.driver_id AND (v.deleted_at IS NULL OR v.deleted_at = '0000-00-00 00:00:00')
                LEFT JOIN users u ON d.created_by = u.id
                WHERE d.id = :id AND d.company_id = :company_id AND (d.deleted_at IS NULL OR d.deleted_at = '0000-00-00 00:00:00')
                GROUP BY d.id
            ", [':id' => $id, ':company_id' => $user['company_id']]);
            
            if (!$driver) {
                return $this->error('Motorista não encontrado', null, 404);
            }
            
            return $this->success($driver);
            
        } catch (Exception $e) {
            error_log("DriverController show error: " . $e->getMessage());
            return $this->error('Erro ao buscar motorista', null, 500);
        }
    }
    
    /**
     * Atualizar motorista
     * PUT /api/v1/drivers/{id}
     * 
     * REGRA: Ao editar, mostrar veículos SEM motorista + veículos do motorista atual
     */
    public function update($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            if (!$user['company_id']) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            if (!PermissionMiddleware::check($user, 'drivers', $id)) {
                return $this->error('Sem permissão para editar este motorista', null, 403);
            }
            
            // Buscar valores antigos
            $oldDriver = $this->db->fetchOne("SELECT * FROM drivers WHERE id = :id", [':id' => $id]);
            if (!$oldDriver) {
                return $this->error('Motorista não encontrado', null, 404);
            }
            
            $allowedFields = ['name', 'document', 'phone', 'email', 'address', 
                              'license_number', 'license_category', 'license_expiry_date', 
                              'license_issue_date', 'hire_date', 'status'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field]) && $input[$field] !== null && $input[$field] !== '') {
                    if ($field === 'license_category') {
                        $updateData[$field] = strtoupper($input[$field]);
                    } else {
                        $updateData[$field] = $input[$field];
                    }
                }
            }
            
            if (empty($updateData)) {
                return $this->error('Nenhum dado para atualizar', null, 400);
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->update('drivers', $updateData, 'id = :id', [':id' => $id]);
            
            // ============================================================
            // REGRA DE NEGÓCIO: Atualizar associação de veículos
            // 1. Desassociar TODOS os veículos deste motorista
            // 2. Associar APENAS os veículos selecionados que estão disponíveis
            // ============================================================
            
            // PASSO 1: Remover todas as associações deste motorista
            $this->db->update('vehicles', 
                ['driver_id' => null], 
                'driver_id = :driver_id AND company_id = :company_id', 
                [':driver_id' => $id, ':company_id' => $user['company_id']]
            );
            
            // PASSO 2: Associar novos veículos (apenas se estiverem disponíveis)
            if (isset($input['vehicle_ids']) && is_array($input['vehicle_ids']) && !empty($input['vehicle_ids'])) {
                foreach ($input['vehicle_ids'] as $vehicleId) {
                    // Verificar se o veículo está disponível (sem motorista)
                    $vehicleCheck = $this->db->fetchOne(
                        "SELECT id, driver_id FROM vehicles WHERE id = :id AND company_id = :company_id AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')",
                        [':id' => $vehicleId, ':company_id' => $user['company_id']]
                    );
                    
                    // Só associar se o veículo estiver disponível (driver_id = null)
                    if ($vehicleCheck && $vehicleCheck['driver_id'] === null) {
                        $this->db->update('vehicles', 
                            ['driver_id' => $id], 
                            'id = :id', 
                            [':id' => $vehicleId]
                        );
                    }
                }
            }
            
            // Registrar auditoria
            $this->logAudit(
                $user['id'],
                $user['company_id'],
                'update',
                'driver',
                $id,
                'Motorista atualizado',
                $oldDriver,
                $updateData
            );
            
            return $this->success(null, 'Motorista atualizado com sucesso');
            
        } catch (Exception $e) {
            error_log("DriverController update error: " . $e->getMessage());
            return $this->error('Erro ao atualizar motorista', null, 500);
        }
    }
    
    /**
     * Remover motorista - SOFT DELETE
     * DELETE /api/v1/drivers/{id}
     * 
     * REGRA: Desassociar veículos automaticamente ao excluir o motorista
     */
    public function delete($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            if (!$user['company_id']) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            if (!PermissionMiddleware::check($user, 'drivers', $id)) {
                return $this->error('Sem permissão para eliminar este motorista', null, 403);
            }
            
            $existing = $this->db->fetchOne(
                "SELECT * FROM drivers WHERE id = :id AND company_id = :company_id AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')", 
                [':id' => $id, ':company_id' => $user['company_id']]
            );
            
            if (!$existing) {
                return $this->error('Motorista não encontrado', null, 404);
            }
            
            // REGRA: Desassociar veículos automaticamente
            $this->db->update('vehicles', 
                ['driver_id' => null], 
                'driver_id = :driver_id AND company_id = :company_id', 
                [':driver_id' => $id, ':company_id' => $user['company_id']]
            );
            
            // Registrar auditoria ANTES do soft delete
            $this->logAudit(
                $user['id'],
                $user['company_id'],
                'delete',
                'driver',
                $id,
                'Motorista removido (soft delete) - Veículos desassociados',
                $existing,
                ['deleted_at' => date('Y-m-d H:i:s'), 'deleted_by' => $user['id']]
            );
            
            $this->db->update('drivers', [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $user['id']
            ], 'id = :id', [':id' => $id]);
            
            return $this->success(null, 'Motorista removido com sucesso');
            
        } catch (Exception $e) {
            error_log("DriverController delete error: " . $e->getMessage());
            return $this->error('Erro ao remover motorista: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Atualizar licença
     * POST /api/v1/drivers/{id}/license
     */
    public function updateLicense($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            if (!PermissionMiddleware::check($user, 'drivers', $id)) {
                return $this->error('Sem permissão', null, 403);
            }
            
            $required = ['license_number', 'license_category', 'license_expiry_date'];
            $validation = $this->validateRequired($input, $required);
            if ($validation !== true) {
                return $validation;
            }
            
            // Buscar valores antigos
            $oldDriver = $this->db->fetchOne("SELECT license_number, license_category, license_expiry_date, license_issue_date FROM drivers WHERE id = :id", [':id' => $id]);
            
            $updateData = [
                'license_number' => $input['license_number'],
                'license_category' => strtoupper($input['license_category']),
                'license_expiry_date' => $input['license_expiry_date'],
                'license_issue_date' => $input['license_issue_date'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->update('drivers', $updateData, 'id = :id', [':id' => $id]);
            
            // Registrar auditoria
            $this->logAudit(
                $user['id'],
                $user['company_id'],
                'update',
                'driver',
                $id,
                'Licença do motorista atualizada',
                $oldDriver,
                $updateData
            );
            
            return $this->success(null, 'Licença atualizada com sucesso');
            
        } catch (Exception $e) {
            error_log("DriverController updateLicense error: " . $e->getMessage());
            return $this->error('Erro ao atualizar licença', null, 500);
        }
    }
    
    /**
     * Veículos do motorista
     * GET /api/v1/drivers/{id}/vehicles
     */
    public function getVehicles($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            if (!PermissionMiddleware::check($user, 'drivers', $id)) {
                return $this->error('Sem permissão', null, 403);
            }
            
            $vehicles = $this->db->fetchAll("
                SELECT id, plate, brand, model, year_manufacture, color, status
                FROM vehicles 
                WHERE driver_id = :driver_id AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
                ORDER BY created_at DESC
            ", [':driver_id' => $id]);
            
            return $this->success($vehicles ?: []);
            
        } catch (Exception $e) {
            error_log("DriverController getVehicles error: " . $e->getMessage());
            return $this->success([], 'Nenhum veículo encontrado');
        }
    }
    
    /**
     * Listar veículos disponíveis para associar
     * GET /api/v1/drivers/vehicles/available
     * 
     * REGRAS DE NEGÓCIO:
     * ┌─────────────────────────────────────────────────────────────┐
     * │ 1. CADASTRAR (driver_id = null):                            │
     * │    → Retorna APENAS veículos com driver_id IS NULL          │
     * │                                                             │
     * │ 2. EDITAR (driver_id = valor):                              │
     * │    → Retorna veículos com driver_id IS NULL                 │
     * │    → Retorna veículos com driver_id = :driver_id            │
     * │    → EXCLUI veículos com driver_id de OUTROS motoristas     │
     * │                                                             │
     * │ 3. Veículos INACTIVE ou MAINTENANCE não são retornados      │
     * │ 4. Veículos deletados (soft delete) não são retornados      │
     * └─────────────────────────────────────────────────────────────┘
     */
    public function getAvailableVehicles($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            if (!$user['company_id']) {
                return $this->success([
                    'data' => [],
                    'message' => 'Empresa não encontrada'
                ]);
            }
            
            // ID do motorista que está sendo editado (opcional)
            $driverId = isset($params['driver_id']) ? (int)$params['driver_id'] : null;
            
            // REGRA: Construir SQL com base no modo (ADD ou EDIT)
            $sql = "
                SELECT 
                    v.id, 
                    v.plate, 
                    v.brand, 
                    v.model, 
                    v.year_manufacture, 
                    v.color, 
                    v.status, 
                    v.driver_id,
                    d.name as current_driver_name,
                    d.id as current_driver_id,
                    CASE 
                        WHEN v.driver_id IS NULL THEN 'Disponível'
                        WHEN v.driver_id = ? THEN 'Associado a este motorista ✓'
                        ELSE CONCAT('Associado a: ', d.name)
                    END as availability_label,
                    CASE 
                        WHEN v.driver_id IS NULL THEN 'available'
                        WHEN v.driver_id = ? THEN 'assigned_to_this_driver'
                        ELSE 'assigned_to_other'
                    END as availability_status,
                    CASE 
                        WHEN v.driver_id IS NULL THEN 1
                        WHEN v.driver_id = ? THEN 1
                        ELSE 0
                    END as can_select
                FROM vehicles v
                LEFT JOIN drivers d ON v.driver_id = d.id AND (d.deleted_at IS NULL OR d.deleted_at = '0000-00-00 00:00:00')
                WHERE v.company_id = ? 
                AND (v.deleted_at IS NULL OR v.deleted_at = '0000-00-00 00:00:00')
                AND v.status = 'active'
            ";
            
            // REGRA: Filtrar veículos permitidos
            if ($driverId) {
                // MODO EDIT: veículos sem motorista OU veículos do motorista atual
                $sql .= " AND (v.driver_id IS NULL OR v.driver_id = ?)";
                $paramsDb = [$driverId, $driverId, $driverId, $user['company_id'], $driverId];
            } else {
                // MODO ADD: apenas veículos sem motorista
                $sql .= " AND v.driver_id IS NULL";
                $paramsDb = [null, null, null, $user['company_id']];
            }
            
            $sql .= " ORDER BY v.brand, v.model, v.plate";
            
            // Executar query com PDO
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute($paramsDb);
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success([
                'data' => $vehicles ?: [],
                'total' => count($vehicles),
                'driver_id' => $driverId,
                'company_id' => $user['company_id'],
                'mode' => $driverId ? 'edit' : 'add',
                'message' => $driverId 
                    ? 'Veículos disponíveis + veículos já associados a este motorista' 
                    : 'Veículos disponíveis para associação (sem motorista)'
            ]);
            
        } catch (Exception $e) {
            error_log("DriverController getAvailableVehicles error: " . $e->getMessage());
            return $this->success([
                'data' => [],
                'total' => 0,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Documentos do motorista
     * GET /api/v1/drivers/{id}/documents
     */
    public function getDocuments($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            if (!PermissionMiddleware::check($user, 'drivers', $id)) {
                return $this->error('Sem permissão', null, 403);
            }
            
            $documents = $this->db->fetchAll("
                SELECT * FROM documents 
                WHERE entity_type = 'driver' AND entity_id = :driver_id AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
                ORDER BY created_at DESC
            ", [':driver_id' => $id]);
            
            return $this->success($documents ?: []);
            
        } catch (Exception $e) {
            error_log("DriverController getDocuments error: " . $e->getMessage());
            return $this->success([], 'Nenhum documento encontrado');
        }
    }
    
    /**
     * Histórico do motorista
     * GET /api/v1/drivers/{id}/history
     */
    public function getHistory($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            if (!PermissionMiddleware::check($user, 'drivers', $id)) {
                return $this->error('Sem permissão', null, 403);
            }
            
            $history = $this->db->fetchAll("
                SELECT * FROM audit_logs 
                WHERE entity_type = 'driver' AND entity_id = :driver_id
                ORDER BY created_at DESC
                LIMIT 50
            ", [':driver_id' => $id]);
            
            return $this->success($history ?: []);
            
        } catch (Exception $e) {
            error_log("DriverController getHistory error: " . $e->getMessage());
            return $this->success([], 'Nenhum histórico encontrado');
        }
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