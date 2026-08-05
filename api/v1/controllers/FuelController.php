<?php
/**
 * CARDOXIS - Fuel Controller RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

class FuelController extends BaseController {
    private $db;
    
    // Constantes de configuração
    private const MAX_LIMIT = 100;
    private const DEFAULT_LIMIT = 15;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Listar registros de combustível
     * GET /api/v1/fuel
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
            
            // Usar PermissionMiddleware para obter scope
            $perm = PermissionMiddleware::scope($user, 'fuel_entries', 'f');
            
            $limit = isset($params['limit']) ? (int)$params['limit'] : self::DEFAULT_LIMIT;
            $limit = min(max($limit, 1), self::MAX_LIMIT);
            
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $page = max($page, 1);
            $offset = ($page - 1) * $limit;
            
            $fuelType = isset($params['fuel_type']) && $params['fuel_type'] !== 'all' ? $params['fuel_type'] : '';
            $search = isset($params['search']) ? trim($params['search']) : '';
            $status = isset($params['status']) ? $params['status'] : '';
            
            $extraWhere = "";
            $extraParams = [];
            
            if (!empty($fuelType)) {
                $extraWhere .= " AND f.fuel_type = :fuel_type";
                $extraParams[':fuel_type'] = $fuelType;
            }
            
            if (!empty($search)) {
                $extraWhere .= " AND (v.plate LIKE :search OR v.brand LIKE :search OR v.model LIKE :search OR f.station_name LIKE :search)";
                $extraParams[':search'] = '%' . $search . '%';
            }
            
            if (!empty($status) && $status !== 'all') {
                $extraWhere .= " AND f.status = :status";
                $extraParams[':status'] = $status;
            }
            
            // Buscar total
            $countSql = "
                SELECT COUNT(*) as total 
                FROM fuel_entries f
                JOIN vehicles v ON f.vehicle_id = v.id
                WHERE {$perm['where']} {$extraWhere}
            ";
            $countResult = $this->db->fetchOne($countSql, array_merge($perm['params'], $extraParams));
            $total = $countResult ? (int)$countResult['total'] : 0;
            
            // Buscar registros
            $sql = "
                SELECT f.*,
                       v.plate as vehicle_plate,
                       v.brand as vehicle_brand,
                       v.model as vehicle_model,
                       d.name as driver_name
                FROM fuel_entries f
                JOIN vehicles v ON f.vehicle_id = v.id
                LEFT JOIN drivers d ON f.driver_id = d.id
                WHERE {$perm['where']} {$extraWhere}
                ORDER BY f.date DESC, f.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $paramsDb = array_merge($perm['params'], $extraParams, [
                ':limit' => $limit,
                ':offset' => $offset
            ]);
            
            $entries = $this->db->fetchAll($sql, $paramsDb);
            
            return $this->success([
                'data' => $entries ?: [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("[FUEL] index error: " . $e->getMessage());
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
     * Estatísticas de combustível
     * GET /api/v1/fuel/stats
     */
    public function getStats($input, $params) {
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
                    'total_entries' => 0,
                    'total_liters' => 0,
                    'total_cost' => 0,
                    'avg_price' => 0,
                    'by_fuel_type' => [],
                    'top_vehicle' => null
                ]);
            }
            
            $perm = PermissionMiddleware::scope($user, 'fuel_entries', 'f');
            
            // Estatísticas gerais
            $stats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total_entries,
                    COALESCE(SUM(f.liters), 0) as total_liters,
                    COALESCE(SUM(f.liters * f.price_per_liter), 0) as total_cost,
                    COALESCE(AVG(f.price_per_liter), 0) as avg_price
                FROM fuel_entries f
                WHERE {$perm['where']}
            ", $perm['params']);
            
            // Por tipo de combustível
            $byFuelType = $this->db->fetchAll("
                SELECT 
                    f.fuel_type,
                    COUNT(*) as total_entries,
                    COALESCE(SUM(f.liters), 0) as total_liters,
                    COALESCE(SUM(f.liters * f.price_per_liter), 0) as total_cost,
                    COALESCE(AVG(f.price_per_liter), 0) as avg_price
                FROM fuel_entries f
                WHERE {$perm['where']}
                GROUP BY f.fuel_type
                ORDER BY total_entries DESC
            ", $perm['params']);
            
            // Veículo com mais abastecimentos
            $topVehicle = $this->db->fetchOne("
                SELECT 
                    v.id,
                    v.plate,
                    v.brand,
                    v.model,
                    COUNT(*) as total_entries,
                    COALESCE(SUM(f.liters), 0) as total_liters,
                    COALESCE(SUM(f.liters * f.price_per_liter), 0) as total_cost
                FROM fuel_entries f
                JOIN vehicles v ON f.vehicle_id = v.id
                WHERE {$perm['where']}
                GROUP BY v.id, v.plate, v.brand, v.model
                ORDER BY total_entries DESC
                LIMIT 1
            ", $perm['params']);
            
            return $this->success([
                'total_entries' => (int)($stats['total_entries'] ?? 0),
                'total_liters' => round($stats['total_liters'] ?? 0, 2),
                'total_cost' => round($stats['total_cost'] ?? 0, 2),
                'avg_price' => round($stats['avg_price'] ?? 0, 3),
                'by_fuel_type' => $byFuelType ?: [],
                'top_vehicle' => $topVehicle ?: null
            ]);
            
        } catch (Exception $e) {
            error_log("[FUEL] getStats error: " . $e->getMessage());
            return $this->success([
                'total_entries' => 0,
                'total_liters' => 0,
                'total_cost' => 0,
                'avg_price' => 0,
                'by_fuel_type' => [],
                'top_vehicle' => null
            ]);
        }
    }
    
    /**
     * Obter registro específico
     * GET /api/v1/fuel/{id}
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
            
            if (!PermissionMiddleware::check($user, 'fuel_entries', $id)) {
                return $this->error('Sem permissão', null, 403);
            }
            
            $entry = $this->db->fetchOne("
                SELECT f.*,
                       v.plate as vehicle_plate,
                       v.brand as vehicle_brand,
                       v.model as vehicle_model,
                       d.name as driver_name
                FROM fuel_entries f
                JOIN vehicles v ON f.vehicle_id = v.id
                LEFT JOIN drivers d ON f.driver_id = d.id
                WHERE f.id = :id
            ", [':id' => $id]);
            
            if (!$entry) {
                return $this->error('Registo não encontrado', null, 404);
            }
            
            return $this->success($entry);
            
        } catch (Exception $e) {
            error_log("[FUEL] show error: " . $e->getMessage());
            return $this->error('Erro ao buscar registo', null, 500);
        }
    }
    
    /**
     * Criar registro de combustível
     * POST /api/v1/fuel
     */
    public function create($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $required = ['vehicle_id', 'date', 'liters', 'price_per_liter', 'odometer'];
            $validation = $this->validateRequired($input, $required);
            if ($validation !== true) {
                return $validation;
            }
            
            $companyId = $this->getCompanyId($authUser['user_id']);
            
            if (!$companyId) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            // Verificar se veículo pertence à empresa
            $vehicle = $this->db->fetchOne(
                "SELECT id FROM vehicles WHERE id = :id AND company_id = :company_id",
                [':id' => $input['vehicle_id'], ':company_id' => $companyId]
            );
            
            if (!$vehicle) {
                return $this->error('Veículo não encontrado', null, 404);
            }
            
            // Validar dados
            $liters = (float)$input['liters'];
            $pricePerLiter = (float)$input['price_per_liter'];
            $odometer = (int)$input['odometer'];
            
            if ($liters <= 0) {
                return $this->error('A quantidade de litros deve ser maior que zero', null, 400);
            }
            
            if ($pricePerLiter <= 0) {
                return $this->error('O preço por litro deve ser maior que zero', null, 400);
            }
            
            if ($odometer < 0) {
                return $this->error('O odômetro não pode ser negativo', null, 400);
            }
            
            $entryId = $this->db->insert('fuel_entries', [
                'company_id' => $companyId,
                'vehicle_id' => $input['vehicle_id'],
                'driver_id' => isset($input['driver_id']) && $input['driver_id'] ? (int)$input['driver_id'] : null,
                'date' => $input['date'],
                'liters' => $liters,
                'price_per_liter' => $pricePerLiter,
                'odometer' => $odometer,
                'fuel_type' => $input['fuel_type'] ?? 'diesel',
                'station_name' => $input['station_name'] ?? null,
                'invoice_number' => $input['invoice_number'] ?? null,
                'notes' => $input['notes'] ?? null,
                'status' => $input['status'] ?? 'approved',
                'created_by' => $authUser['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return $this->success(['id' => $entryId], 'Registo criado com sucesso', 201);
            
        } catch (Exception $e) {
            error_log("[FUEL] create error: " . $e->getMessage());
            return $this->error('Erro ao criar registo: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Atualizar registro de combustível
     * PUT /api/v1/fuel/{id}
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
            
            if (!PermissionMiddleware::check($user, 'fuel_entries', $id)) {
                return $this->error('Sem permissão', null, 403);
            }
            
            $allowedFields = ['date', 'liters', 'price_per_liter', 'odometer', 'fuel_type', 
                             'station_name', 'invoice_number', 'driver_id', 'notes', 'status'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field]) && $input[$field] !== null && $input[$field] !== '') {
                    if ($field === 'liters' || $field === 'price_per_liter') {
                        $updateData[$field] = (float)$input[$field];
                    } elseif ($field === 'odometer') {
                        $updateData[$field] = (int)$input[$field];
                    } elseif ($field === 'driver_id') {
                        $updateData[$field] = $input[$field] ? (int)$input[$field] : null;
                    } else {
                        $updateData[$field] = $input[$field];
                    }
                }
            }
            
            if (empty($updateData)) {
                return $this->error('Nenhum dado para atualizar', null, 400);
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->update('fuel_entries', $updateData, 'id = :id', [':id' => $id]);
            
            return $this->success(null, 'Registo atualizado com sucesso');
            
        } catch (Exception $e) {
            error_log("[FUEL] update error: " . $e->getMessage());
            return $this->error('Erro ao atualizar registo', null, 500);
        }
    }
    
    /**
     * Remover registro de combustível
     * DELETE /api/v1/fuel/{id}
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
            
            if (!PermissionMiddleware::check($user, 'fuel_entries', $id)) {
                return $this->error('Sem permissão', null, 403);
            }
            
            $this->db->delete('fuel_entries', 'id = :id', [':id' => $id]);
            
            return $this->success(null, 'Registo removido com sucesso');
            
        } catch (Exception $e) {
            error_log("[FUEL] delete error: " . $e->getMessage());
            return $this->error('Erro ao remover registo', null, 500);
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
            error_log("[FUEL] getCompanyId error: " . $e->getMessage());
            return null;
        }
    }
}