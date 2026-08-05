<?php
/**
 * CARDOXIS - Maintenance Controller  RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

class MaintenanceController extends BaseController {
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
     * Listar manutenções
     * GET /api/v1/maintenances
     */
    public function index($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->success(['data' => [], 'total' => 0]);
            }
            
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            $companyId = $this->getCompanyId($userId);
            
            if (!$companyId) {
                return $this->success(['data' => [], 'total' => 0]);
            }
            
            $limit = isset($params['limit']) ? (int)$params['limit'] : 15;
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $offset = ($page - 1) * $limit;
            $status = isset($params['status']) ? $params['status'] : '';
            $vehicleId = isset($params['vehicle_id']) ? (int)$params['vehicle_id'] : 0;
            
            $where = "m.company_id = ?";
            $paramsDb = [$companyId];
            
            if ($userRole === 'user') {
                $where .= " AND m.created_by = ?";
                $paramsDb[] = $userId;
            }
            
            if (!empty($status) && $status !== 'all') {
                $where .= " AND m.status = ?";
                $paramsDb[] = $status;
            }
            
            if ($vehicleId > 0) {
                $where .= " AND m.vehicle_id = ?";
                $paramsDb[] = $vehicleId;
            }
            
            $countSql = "SELECT COUNT(*) as total FROM maintenances m WHERE {$where}";
            $stmt = $this->db->getConnection()->prepare($countSql);
            $stmt->execute($paramsDb);
            $total = (int)$stmt->fetchColumn();
            
            $sql = "
                SELECT m.*, v.plate, v.brand, v.model
                FROM maintenances m
                JOIN vehicles v ON m.vehicle_id = v.id
                WHERE {$where}
                ORDER BY 
                    CASE 
                        WHEN m.status = 'overdue' THEN 0
                        WHEN m.status = 'in_progress' THEN 1
                        WHEN m.status = 'scheduled' THEN 2
                        ELSE 3
                    END,
                    m.scheduled_date ASC,
                    m.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $paramsDb[] = $limit;
            $paramsDb[] = $offset;
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($paramsDb);
            $maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success([
                'data' => $maintenances ?: [],
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]);
            
        } catch (Exception $e) {
            error_log("[MAINTENANCE] index error: " . $e->getMessage());
            return $this->success(['data' => [], 'total' => 0]);
        }
    }
    
    /**
     * Obter manutenção específica
     * GET /api/v1/maintenances/{id}
     */
    public function show($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$id || $id === 'null' || $id === 'undefined' || $id === '') {
                return $this->error('ID da manutenção inválido', null, 400);
            }
            
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            $companyId = $this->getCompanyId($userId);
            
            if (!$companyId) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            $sql = "
                SELECT m.*, v.plate, v.brand, v.model, v.odometer as current_odometer
                FROM maintenances m
                JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.id = ?
            ";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$id]);
            $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$maintenance) {
                return $this->error('Manutenção não encontrada', null, 404);
            }
            
            if ($maintenance['company_id'] != $companyId) {
                return $this->error('Sem permissão para aceder a esta manutenção', null, 403);
            }
            
            if ($userRole === 'user') {
                $check = $this->db->fetchOne(
                    "SELECT id FROM maintenances WHERE id = ? AND created_by = ?", 
                    [$id, $userId]
                );
                if (!$check) {
                    return $this->error('Sem permissão para aceder a esta manutenção', null, 403);
                }
            }
            
            return $this->success($maintenance);
            
        } catch (Exception $e) {
            error_log("[MAINTENANCE] show error: " . $e->getMessage());
            return $this->error('Erro ao buscar manutenção: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Criar manutenção
     * POST /api/v1/maintenances
     */
    public function create($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $userId = $authUser['user_id'];
            $companyId = $this->getCompanyId($userId);
            
            if (!$companyId) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            $required = ['vehicle_id', 'title', 'scheduled_date'];
            $validation = $this->validateRequired($input, $required);
            if ($validation !== true) {
                return $validation;
            }
            
            $vehicle = $this->db->fetchOne(
                "SELECT id FROM vehicles WHERE id = ? AND company_id = ?", 
                [$input['vehicle_id'], $companyId]
            );
            
            if (!$vehicle) {
                return $this->error('Veículo não encontrado', null, 404);
            }
            
            $scheduledDate = $input['scheduled_date'];
            $status = 'scheduled';
            
            if (strtotime($scheduledDate) < strtotime(date('Y-m-d'))) {
                $status = 'overdue';
            }
            
            $data = [
                'company_id' => $companyId,
                'vehicle_id' => $input['vehicle_id'],
                'type' => $input['type'] ?? 'preventive',
                'title' => $input['title'],
                'description' => $input['description'] ?? null,
                'scheduled_date' => $scheduledDate,
                'cost' => isset($input['cost']) && !empty($input['cost']) ? (float)$input['cost'] : null,
                'odometer_at_maintenance' => isset($input['odometer_at_maintenance']) && !empty($input['odometer_at_maintenance']) ? (int)$input['odometer_at_maintenance'] : null,
                'workshop_name' => $input['workshop_name'] ?? null,
                'priority' => $input['priority'] ?? 'medium',
                'status' => $status,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $maintenanceId = $this->db->insert('maintenances', $data);
            
            if (!$maintenanceId) {
                return $this->error('Erro ao inserir manutenção no banco de dados', null, 500);
            }
            
            // Registrar auditoria
            $this->logAudit(
                $userId,
                $companyId,
                'create',
                'maintenance',
                $maintenanceId,
                'Manutenção criada: ' . $data['title'],
                null,
                $data
            );
            
            $this->createMaintenanceAlert($companyId, $maintenanceId, $input['vehicle_id'], $input['title'], $scheduledDate, $userId);
            
            return $this->success(['id' => $maintenanceId], 'Manutenção agendada com sucesso', 201);
            
        } catch (Exception $e) {
            error_log("[MAINTENANCE] create error: " . $e->getMessage());
            return $this->error('Erro ao criar manutenção: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Atualizar manutenção
     * PUT /api/v1/maintenances/{id}
     */
    public function update($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$id || $id === 'null' || $id === 'undefined' || $id === '') {
                return $this->error('ID da manutenção inválido', null, 400);
            }
            
            $rawInput = file_get_contents('php://input');
            if (empty($rawInput)) {
                return $this->error('Nenhum dado enviado. Envie um JSON válido.', null, 400);
            }
            
            $data = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->error('JSON inválido: ' . json_last_error_msg(), null, 400);
            }
            
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            $companyId = $this->getCompanyId($userId);
            
            if (!$companyId) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            // Buscar valores antigos
            $oldMaintenance = $this->db->fetchOne("SELECT * FROM maintenances WHERE id = ? AND company_id = ?", [$id, $companyId]);
            
            if (!$oldMaintenance) {
                return $this->error('Manutenção não encontrada', null, 404);
            }
            
            if ($userRole === 'user') {
                $check = $this->db->fetchOne(
                    "SELECT id FROM maintenances WHERE id = ? AND created_by = ?", 
                    [$id, $userId]
                );
                if (!$check) {
                    return $this->error('Sem permissão para editar esta manutenção', null, 403);
                }
            }
            
            if ($oldMaintenance['status'] === 'completed') {
                return $this->error('Não é possível editar uma manutenção já concluída', null, 400);
            }
            
            $allowedFields = ['title', 'description', 'scheduled_date', 'cost', 'odometer_at_maintenance', 'workshop_name', 'priority'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field]) && $data[$field] !== null && $data[$field] !== '') {
                    if ($field === 'cost') {
                        $updateData[$field] = (float)$data[$field];
                    } elseif ($field === 'odometer_at_maintenance') {
                        $updateData[$field] = (int)$data[$field];
                    } else {
                        $updateData[$field] = $data[$field];
                    }
                }
            }
            
            if (empty($updateData)) {
                return $this->error('Nenhum dado para atualizar', null, 400);
            }
            
            if (isset($updateData['scheduled_date'])) {
                $scheduledDate = $updateData['scheduled_date'];
                if (strtotime($scheduledDate) < strtotime(date('Y-m-d'))) {
                    $updateData['status'] = 'overdue';
                } elseif ($oldMaintenance['status'] === 'overdue') {
                    $updateData['status'] = 'scheduled';
                }
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->update('maintenances', $updateData, 'id = :id', [':id' => $id]);
            
            // Registrar auditoria
            $this->logAudit(
                $userId,
                $companyId,
                'update',
                'maintenance',
                $id,
                'Manutenção atualizada: ' . ($updateData['title'] ?? $oldMaintenance['title']),
                $oldMaintenance,
                $updateData
            );
            
            return $this->success(null, 'Manutenção atualizada com sucesso');
            
        } catch (Exception $e) {
            error_log("[MAINTENANCE_UPDATE] ERRO: " . $e->getMessage());
            return $this->error('Erro ao atualizar manutenção: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Completar manutenção
     * POST /api/v1/maintenances/{id}/complete
     */
    public function complete($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$id || $id === 'null' || $id === 'undefined' || $id === '') {
                return $this->error('ID da manutenção inválido', null, 400);
            }
            
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            $companyId = $this->getCompanyId($userId);
            
            if (!$companyId) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            $oldMaintenance = $this->db->fetchOne(
                "SELECT * FROM maintenances WHERE id = ? AND company_id = ?", 
                [$id, $companyId]
            );
            
            if (!$oldMaintenance) {
                return $this->error('Manutenção não encontrada', null, 404);
            }
            
            if ($userRole === 'user') {
                $check = $this->db->fetchOne(
                    "SELECT id FROM maintenances WHERE id = ? AND created_by = ?", 
                    [$id, $userId]
                );
                if (!$check) {
                    return $this->error('Sem permissão para concluir esta manutenção', null, 403);
                }
            }
            
            if ($oldMaintenance['status'] === 'completed') {
                return $this->error('Manutenção já concluída', null, 400);
            }
            
            $updateData = [
                'status' => 'completed',
                'completion_date' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $data = json_decode($rawInput, true);
                if ($data) {
                    if (isset($data['cost']) && !empty($data['cost'])) {
                        $updateData['cost'] = (float)$data['cost'];
                    }
                    
                    if (isset($data['odometer_at_maintenance']) && !empty($data['odometer_at_maintenance'])) {
                        $odometer = (int)$data['odometer_at_maintenance'];
                        $updateData['odometer_at_maintenance'] = $odometer;
                        
                        $this->db->update('vehicles', [
                            'odometer' => $odometer,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = ?', [$oldMaintenance['vehicle_id']]);
                    }
                }
            }
            
            $this->db->update('maintenances', $updateData, 'id = :id', [':id' => $id]);
            
            // Registrar auditoria
            $this->logAudit(
                $userId,
                $companyId,
                'complete',
                'maintenance',
                $id,
                'Manutenção concluída: ' . $oldMaintenance['title'],
                $oldMaintenance,
                $updateData
            );
            
            return $this->success(null, 'Manutenção concluída com sucesso');
            
        } catch (Exception $e) {
            error_log("[MAINTENANCE_COMPLETE] ERRO: " . $e->getMessage());
            return $this->error('Erro ao concluir manutenção: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Deletar manutenção
     * DELETE /api/v1/maintenances/{id}
     */
    public function delete($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$id || $id === 'null' || $id === 'undefined' || $id === '' || !is_numeric($id)) {
                error_log("[MAINTENANCE_DELETE] ID inválido: " . var_export($id, true));
                return $this->error('ID da manutenção inválido', null, 400);
            }
            
            $id = (int)$id;
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            $companyId = $this->getCompanyId($userId);
            
            if (!$companyId) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            error_log("[MAINTENANCE_DELETE] ID: $id, User: $userId, Company: $companyId");
            
            $existing = $this->db->fetchOne(
                "SELECT * FROM maintenances WHERE id = ?", 
                [$id]
            );
            
            if (!$existing) {
                error_log("[MAINTENANCE_DELETE] Manutenção não encontrada: $id");
                return $this->error('Manutenção não encontrada', null, 404);
            }
            
            if ($existing['company_id'] != $companyId) {
                error_log("[MAINTENANCE_DELETE] Manutenção não pertence à empresa: " . $existing['company_id'] . " vs " . $companyId);
                return $this->error('Sem permissão para remover esta manutenção', null, 403);
            }
            
            if ($userRole === 'user') {
                if ($existing['created_by'] != $userId) {
                    error_log("[MAINTENANCE_DELETE] Usuário não é o criador: $userId vs " . $existing['created_by']);
                    return $this->error('Sem permissão para remover esta manutenção', null, 403);
                }
            }
            
            if ($existing['status'] === 'completed') {
                return $this->error('Não é possível remover uma manutenção já concluída', null, 400);
            }
            
            // Registrar auditoria ANTES do soft delete
            $this->logAudit(
                $userId,
                $companyId,
                'delete',
                'maintenance',
                $id,
                'Manutenção removida: ' . $existing['title'],
                $existing,
                ['deleted_at' => date('Y-m-d H:i:s'), 'deleted_by' => $userId]
            );
            
            try {
                $this->db->update('maintenances', [
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'deleted_by' => $userId
                ], 'id = ?', [$id]);
                error_log("[MAINTENANCE_DELETE] Soft delete realizado para ID: $id");
            } catch (Exception $e) {
                error_log("[MAINTENANCE_DELETE] Erro no soft delete, tentando hard delete: " . $e->getMessage());
                $this->db->delete('maintenances', 'id = ?', [$id]);
            }
            
            return $this->success(null, 'Manutenção removida com sucesso');
            
        } catch (Exception $e) {
            error_log("[MAINTENANCE_DELETE] ERRO: " . $e->getMessage());
            return $this->error('Erro ao remover manutenção: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Manutenções atrasadas
     * GET /api/v1/maintenances/overdue
     */
    public function getOverdue($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->success([], 'Nenhuma manutenção atrasada');
            }
            
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            $companyId = $this->getCompanyId($userId);
            
            if (!$companyId) {
                return $this->success([], 'Empresa não encontrada');
            }
            
            $where = "m.company_id = ?";
            $paramsDb = [$companyId];
            
            if ($userRole === 'user') {
                $where .= " AND m.created_by = ?";
                $paramsDb[] = $userId;
            }
            
            $where .= " AND m.scheduled_date < CURDATE() AND m.status IN ('scheduled', 'in_progress', 'overdue')";
            
            $sql = "
                SELECT m.*, v.plate, v.brand, v.model,
                       DATEDIFF(CURDATE(), m.scheduled_date) as days_overdue
                FROM maintenances m
                JOIN vehicles v ON m.vehicle_id = v.id
                WHERE {$where}
                ORDER BY m.scheduled_date ASC
                LIMIT 50
            ";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($paramsDb);
            $maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success($maintenances ?: []);
            
        } catch (Exception $e) {
            error_log("[MAINTENANCE] getOverdue error: " . $e->getMessage());
            return $this->success([], 'Nenhuma manutenção atrasada');
        }
    }
    
    /**
     * Manutenções próximas
     * GET /api/v1/maintenances/upcoming
     */
    public function getUpcoming($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->success([], 'Nenhuma manutenção encontrada');
            }
            
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            $companyId = $this->getCompanyId($userId);
            
            if (!$companyId) {
                return $this->success([], 'Empresa não encontrada');
            }
            
            $days = isset($params['days']) ? (int)$params['days'] : 30;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            
            $where = "m.company_id = ?";
            $paramsDb = [$companyId];
            
            if ($userRole === 'user') {
                $where .= " AND m.created_by = ?";
                $paramsDb[] = $userId;
            }
            
            $where .= " AND m.status IN ('scheduled', 'in_progress') AND m.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
            $paramsDb[] = $days;
            
            $sql = "
                SELECT m.*, v.plate, v.brand, v.model
                FROM maintenances m
                JOIN vehicles v ON m.vehicle_id = v.id
                WHERE {$where}
                ORDER BY 
                    CASE 
                        WHEN m.priority = 'critical' THEN 0
                        WHEN m.priority = 'high' THEN 1
                        WHEN m.priority = 'medium' THEN 2
                        ELSE 3
                    END,
                    m.scheduled_date ASC
                LIMIT ?
            ";
            $paramsDb[] = $limit;
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($paramsDb);
            $maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success($maintenances ?: []);
            
        } catch (Exception $e) {
            error_log("[MAINTENANCE] getUpcoming error: " . $e->getMessage());
            return $this->success([], 'Nenhuma manutenção encontrada');
        }
    }
    
    /**
     * Calendário de manutenções
     * GET /api/v1/maintenances/calendar
     */
    public function getCalendar($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->success([], 'Nenhuma manutenção encontrada');
            }
            
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            $companyId = $this->getCompanyId($userId);
            
            if (!$companyId) {
                return $this->success([], 'Empresa não encontrada');
            }
            
            $month = isset($params['month']) ? (int)$params['month'] : date('n');
            $year = isset($params['year']) ? (int)$params['year'] : date('Y');
            
            $where = "m.company_id = ?";
            $paramsDb = [$companyId];
            
            if ($userRole === 'user') {
                $where .= " AND m.created_by = ?";
                $paramsDb[] = $userId;
            }
            
            $where .= " AND MONTH(m.scheduled_date) = ? AND YEAR(m.scheduled_date) = ?";
            $paramsDb[] = $month;
            $paramsDb[] = $year;
            
            $sql = "
                SELECT m.*, v.plate, v.brand, v.model
                FROM maintenances m
                JOIN vehicles v ON m.vehicle_id = v.id
                WHERE {$where}
                ORDER BY m.scheduled_date ASC
            ";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($paramsDb);
            $maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $calendar = [];
            foreach ($maintenances as $m) {
                $date = date('Y-m-d', strtotime($m['scheduled_date']));
                if (!isset($calendar[$date])) {
                    $calendar[$date] = [];
                }
                $calendar[$date][] = $m;
            }
            
            return $this->success([
                'year' => $year,
                'month' => $month,
                'calendar' => $calendar
            ]);
            
        } catch (Exception $e) {
            error_log("[MAINTENANCE] getCalendar error: " . $e->getMessage());
            return $this->success([], 'Erro ao carregar calendário');
        }
    }
    
    /**
     * Criar alerta para manutenção
     */
    private function createMaintenanceAlert($companyId, $maintenanceId, $vehicleId, $title, $scheduledDate, $userId) {
        try {
            $this->db->insert('alerts', [
                'company_id' => $companyId,
                'title' => 'Manutenção Agendada',
                'message' => "Manutenção '{$title}' agendada para " . date('d/m/Y', strtotime($scheduledDate)),
                'type' => 'maintenance',
                'severity' => 'warning',
                'is_read' => 0,
                'source_type' => 'maintenance',
                'source_id' => $maintenanceId,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Erro ao criar alerta: " . $e->getMessage());
        }
    }
    
    /**
     * Obter company_id do usuário
     */
    private function getCompanyId($userId) {
        try {
            $result = $this->db->fetchOne("SELECT company_id FROM users WHERE id = ?", [$userId]);
            return $result ? $result['company_id'] : null;
        } catch (Exception $e) {
            error_log("[MAINTENANCE] getCompanyId error: " . $e->getMessage());
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