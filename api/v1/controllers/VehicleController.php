<?php
/**
 * CARDOXIS - Vehicle Controller  RF 
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 3));
}

class VehicleController extends BaseController {
    private $db;
    private $uploadDir;
    private $uploadUrl;
    private $logFile;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        $basePath = dirname(__DIR__, 3);
        $this->uploadDir = $basePath . '/storage/uploads/vehicles/';
        $this->uploadUrl = '/cardoxis/storage/uploads/vehicles/';
        $this->logFile = __DIR__ . '/../logs/audit.log';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
        
        if (!is_dir(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0777, true);
        }
    }
    
    /**
     * Listar veículos
     * GET /api/v1/vehicles
     */
    public function index($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->success([], 'Nenhum veículo encontrado');
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            if (!$user['company_id']) {
                return $this->success([], 'Empresa não encontrada');
            }
            
            $perm = PermissionMiddleware::scope($user, 'vehicles', 'v');
            
            $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            $extraWhere = '';
            $extraParams = [];
            
            if (isset($params['status']) && $params['status']) {
                $extraWhere .= " AND v.status = :status";
                $extraParams[':status'] = $params['status'];
            }
            
            if (isset($params['search']) && $params['search']) {
                $extraWhere .= " AND (v.brand LIKE :search OR v.model LIKE :search OR v.plate LIKE :search)";
                $extraParams[':search'] = "%{$params['search']}%";
            }
            
            $sql = "
                SELECT v.id, v.brand, v.model, v.plate, v.year, v.color, v.fuel_type, 
                       v.status, v.odometer, v.image, v.chassis, v.created_by, v.created_at,
                       (SELECT name FROM users WHERE id = v.created_by) as created_by_name
                FROM vehicles v
                WHERE {$perm['where']} {$extraWhere}
                ORDER BY v.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $paramsDb = array_merge($perm['params'], $extraParams, [
                ':limit' => $limit,
                ':offset' => $offset
            ]);
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($paramsDb);
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($vehicles as &$vehicle) {
                if (!empty($vehicle['image'])) {
                    $imagePath = $this->uploadDir . $vehicle['image'];
                    if (file_exists($imagePath)) {
                        $vehicle['image_url'] = $this->uploadUrl . $vehicle['image'];
                    } else {
                        $vehicle['image_url'] = null;
                        $vehicle['image'] = null;
                    }
                } else {
                    $vehicle['image_url'] = null;
                }
            }
            
            $countSql = "SELECT COUNT(*) as total FROM vehicles v WHERE {$perm['where']} {$extraWhere}";
            $stmt = $this->db->getConnection()->prepare($countSql);
            $stmt->execute(array_merge($perm['params'], $extraParams));
            $total = (int)$stmt->fetchColumn();
            
            return [
                'success' => true,
                'message' => 'Veículos carregados com sucesso',
                'data' => $vehicles ?: [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ],
                'user_role' => $user['role'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("VehicleController index error: " . $e->getMessage());
            return $this->success([], 'Nenhum veículo encontrado');
        }
    }
    
    /**
     * Obter veículo específico
     * GET /api/v1/vehicles/{id}
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
            
            if (!PermissionMiddleware::check($user, 'vehicles', $id)) {
                return $this->error('Sem permissão para aceder a este veículo', null, 403);
            }
            
            $vehicle = $this->db->fetchOne("
                SELECT v.*, 
                       YEAR(v.created_at) as ano_cadastro,
                       DATE_FORMAT(v.created_at, '%d/%m/%Y') as data_cadastro,
                       (SELECT name FROM users WHERE id = v.created_by) as created_by_name
                FROM vehicles v
                WHERE v.id = :id
            ", [':id' => $id]);
            
            if (!$vehicle) {
                return $this->error('Veículo não encontrado', null, 404);
            }
            
            if (!empty($vehicle['image'])) {
                $imagePath = $this->uploadDir . $vehicle['image'];
                if (file_exists($imagePath)) {
                    $vehicle['image_url'] = $this->uploadUrl . $vehicle['image'];
                } else {
                    $vehicle['image_url'] = null;
                }
            }
            
            return $this->success($vehicle);
            
        } catch (Exception $e) {
            error_log("VehicleController show error: " . $e->getMessage());
            return $this->error('Erro ao buscar veículo', null, 500);
        }
    }
    
    /**
     * Criar veículo
     * POST /api/v1/vehicles
     */
    public function create($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $required = ['brand', 'model', 'plate'];
            $validation = $this->validateRequired($input, $required);
            if ($validation !== true) {
                return $validation;
            }
            
            $userId = $authUser['user_id'];
            $companyId = $this->getCompanyId($userId);
            
            if (!$companyId) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            $existing = $this->db->fetchOne("
                SELECT id FROM vehicles 
                WHERE plate = :plate AND company_id = :company_id
            ", [
                ':plate' => strtoupper($input['plate']),
                ':company_id' => $companyId
            ]);
            
            if ($existing) {
                return $this->error('Placa já cadastrada', null, 409);
            }
            
            $chassis = isset($input['chassis']) && !empty($input['chassis']) ? $input['chassis'] : null;
            
            $data = [
                'company_id' => $companyId,
                'brand' => $input['brand'],
                'model' => $input['model'],
                'plate' => strtoupper($input['plate']),
                'year' => $input['year'] ?? null,
                'color' => $input['color'] ?? null,
                'fuel_type' => $input['fuel_type'] ?? 'diesel',
                'status' => $input['status'] ?? 'active',
                'odometer' => isset($input['odometer']) ? (int)$input['odometer'] : 0,
                'chassis' => $chassis,
                'image' => null,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $vehicleId = $this->db->insert('vehicles', $data);
            
            // Registrar auditoria
            $this->logAudit(
                $userId,
                $companyId,
                'create',
                'vehicle',
                $vehicleId,
                'Veículo criado',
                null,
                $data
            );
            
            return $this->success(['id' => $vehicleId], 'Veículo criado com sucesso', 201);
            
        } catch (Exception $e) {
            error_log("VehicleController create error: " . $e->getMessage());
            return $this->error('Erro ao criar veículo: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Atualizar veículo
     * PUT /api/v1/vehicles/{id}
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
            
            if (!PermissionMiddleware::check($user, 'vehicles', $id)) {
                return $this->error('Sem permissão para atualizar este veículo', null, 403);
            }
            
            // Buscar valores antigos
            $oldVehicle = $this->db->fetchOne("SELECT * FROM vehicles WHERE id = :id", [':id' => $id]);
            if (!$oldVehicle) {
                return $this->error('Veículo não encontrado', null, 404);
            }
            
            $allowedFields = ['brand', 'model', 'plate', 'year', 'color', 'fuel_type', 'status', 'odometer', 'chassis'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field]) && $input[$field] !== null && $input[$field] !== '') {
                    if ($field === 'plate') {
                        $updateData[$field] = strtoupper(trim($input[$field]));
                    } elseif ($field === 'odometer') {
                        $updateData[$field] = (int)$input[$field];
                    } else {
                        $updateData[$field] = $input[$field];
                    }
                }
            }
            
            if (empty($updateData)) {
                return $this->error('Nenhum dado para atualizar', null, 400);
            }
            
            $this->db->update('vehicles', $updateData, 'id = :id', [':id' => $id]);
            
            // Registrar auditoria
            $this->logAudit(
                $user['id'],
                $user['company_id'],
                'update',
                'vehicle',
                $id,
                'Veículo atualizado',
                $oldVehicle,
                $updateData
            );
            
            return $this->success(null, 'Veículo atualizado com sucesso');
            
        } catch (Exception $e) {
            error_log("VehicleController update error: " . $e->getMessage());
            return $this->error('Erro ao atualizar veículo', null, 500);
        }
    }
    
    /**
     * Deletar veículo
     * DELETE /api/v1/vehicles/{id}
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
            
            if (!PermissionMiddleware::check($user, 'vehicles', $id)) {
                return $this->error('Sem permissão para remover este veículo', null, 403);
            }
            
            $vehicle = $this->db->fetchOne("SELECT id, image FROM vehicles WHERE id = :id", [':id' => $id]);
            
            if (!$vehicle) {
                return $this->error('Veículo não encontrado', null, 404);
            }
            
            if ($vehicle && !empty($vehicle['image'])) {
                $imagePath = $this->uploadDir . $vehicle['image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            // Registrar auditoria ANTES do soft delete
            $this->logAudit(
                $user['id'],
                $user['company_id'],
                'delete',
                'vehicle',
                $id,
                'Veículo removido (soft delete)',
                $vehicle,
                ['deleted_at' => date('Y-m-d H:i:s'), 'deleted_by' => $user['id']]
            );
            
            $this->db->update('vehicles', [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $user['id']
            ], 'id = :id', [':id' => $id]);
            
            return $this->success(null, 'Veículo removido com sucesso');
            
        } catch (Exception $e) {
            error_log("VehicleController delete error: " . $e->getMessage());
            return $this->error('Erro ao remover veículo', null, 500);
        }
    }
    
    /**
     * Upload de imagem do veículo
     * POST /api/v1/vehicles/{id}/image
     */
    public function uploadImage($id, $input, $params) {
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
            
            if (!PermissionMiddleware::check($user, 'vehicles', $id)) {
                return $this->error('Sem permissão para este veículo', null, 403);
            }
            
            $vehicle = $this->db->fetchOne("SELECT id, image FROM vehicles WHERE id = :id", [':id' => $id]);
            
            if (!$vehicle) {
                return $this->error('Veículo não encontrado', null, 404);
            }
            
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                return $this->error('Nenhuma imagem enviada', null, 400);
            }
            
            $file = $_FILES['image'];
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                return $this->error('Tipo de arquivo não permitido. Use JPEG, PNG ou WEBP', null, 400);
            }
            
            if ($file['size'] > 5 * 1024 * 1024) {
                return $this->error('A imagem deve ter no máximo 5MB', null, 400);
            }
            
            $oldImage = $vehicle['image'];
            
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'vehicle_' . $id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $filepath = $this->uploadDir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return $this->error('Erro ao fazer upload da imagem', null, 500);
            }
            
            if (!empty($oldImage)) {
                $oldImagePath = $this->uploadDir . $oldImage;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            $this->db->update('vehicles', ['image' => $filename], 'id = :id', [':id' => $id]);
            
            // Registrar auditoria
            $this->logAudit(
                $user['id'],
                $user['company_id'],
                'update',
                'vehicle',
                $id,
                'Imagem do veículo atualizada',
                ['image' => $oldImage],
                ['image' => $filename]
            );
            
            $imageUrl = $this->uploadUrl . $filename;
            
            return $this->success([
                'image_url' => $imageUrl,
                'filename' => $filename
            ], 'Imagem enviada com sucesso');
            
        } catch (Exception $e) {
            error_log("VehicleController uploadImage error: " . $e->getMessage());
            return $this->error('Erro ao fazer upload da imagem: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Remover imagem do veículo
     * DELETE /api/v1/vehicles/{id}/image
     */
    public function deleteImage($id, $input, $params) {
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
            
            if (!PermissionMiddleware::check($user, 'vehicles', $id)) {
                return $this->error('Sem permissão para este veículo', null, 403);
            }
            
            $vehicle = $this->db->fetchOne("SELECT id, image FROM vehicles WHERE id = :id", [':id' => $id]);
            
            if (!$vehicle) {
                return $this->error('Veículo não encontrado', null, 404);
            }
            
            $oldImage = $vehicle['image'];
            
            if ($vehicle && !empty($oldImage)) {
                $imagePath = $this->uploadDir . $oldImage;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $this->db->update('vehicles', ['image' => null], 'id = :id', [':id' => $id]);
                
                // Registrar auditoria
                $this->logAudit(
                    $user['id'],
                    $user['company_id'],
                    'update',
                    'vehicle',
                    $id,
                    'Imagem do veículo removida',
                    ['image' => $oldImage],
                    ['image' => null]
                );
            }
            
            return $this->success(null, 'Imagem removida com sucesso');
            
        } catch (Exception $e) {
            error_log("VehicleController deleteImage error: " . $e->getMessage());
            return $this->error('Erro ao remover imagem', null, 500);
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
            
            // Log em arquivo também
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