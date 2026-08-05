<?php
/**
 * CARDOXIS - Fine Controller RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

class FineController extends BaseController {
    private $db;
    private $uploadDir;
    private $uploadUrl;
    
    // Constantes de configuração
    private const MAX_LIMIT = 100;
    private const DEFAULT_LIMIT = 15;
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        $basePath = dirname(__DIR__, 3);
        $this->uploadDir = $basePath . '/storage/uploads/fines/';
        $this->uploadUrl = '/cardoxis/storage/uploads/fines/';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Listar multas (com permissões)
     * GET /api/v1/fines
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
            $perm = PermissionMiddleware::scope($user, 'fines', 'f');
            
            $limit = isset($params['limit']) ? (int)$params['limit'] : self::DEFAULT_LIMIT;
            $limit = min(max($limit, 1), self::MAX_LIMIT);
            
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $page = max($page, 1);
            $offset = ($page - 1) * $limit;
            $status = isset($params['status']) ? $params['status'] : '';
            $type = isset($params['type']) ? $params['type'] : '';
            $search = isset($params['search']) ? $params['search'] : '';
            
            $extraWhere = "";
            $extraParams = [];
            
            if (!empty($status) && $status !== 'all') {
                if ($status === 'overdue') {
                    $extraWhere .= " AND f.due_date < CURDATE() AND f.status != 'paid'";
                } elseif ($status === 'pending') {
                    $extraWhere .= " AND f.status = 'pending' AND f.due_date >= CURDATE()";
                } else {
                    $extraWhere .= " AND f.status = :status";
                    $extraParams[':status'] = $status;
                }
            }
            
            if (!empty($type) && $type !== 'all') {
                $extraWhere .= " AND f.type = :type";
                $extraParams[':type'] = $type;
            }
            
            if (!empty($search)) {
                $extraWhere .= " AND (f.fine_number LIKE :search OR f.location LIKE :search OR v.plate LIKE :search)";
                $extraParams[':search'] = '%' . $search . '%';
            }
            
            // Buscar total
            $countSql = "
                SELECT COUNT(*) as total 
                FROM fines f
                LEFT JOIN vehicles v ON f.vehicle_id = v.id
                WHERE {$perm['where']} {$extraWhere}
            ";
            $countResult = $this->db->fetchOne($countSql, array_merge($perm['params'], $extraParams));
            $total = $countResult ? (int)$countResult['total'] : 0;
            
            // Buscar multas
            $sql = "
                SELECT f.*, 
                       v.plate as vehicle_plate, 
                       v.brand as vehicle_brand, 
                       v.model as vehicle_model,
                       d.name as driver_name,
                       DATEDIFF(f.due_date, CURDATE()) as days_remaining,
                       CASE 
                           WHEN f.status = 'paid' THEN 'paid'
                           WHEN f.due_date < CURDATE() THEN 'overdue'
                           WHEN f.status = 'contested' THEN 'contested'
                           ELSE 'pending'
                       END as status_calculated
                FROM fines f
                LEFT JOIN vehicles v ON f.vehicle_id = v.id
                LEFT JOIN drivers d ON f.driver_id = d.id
                WHERE {$perm['where']} {$extraWhere}
                ORDER BY f.due_date ASC, f.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $paramsDb = array_merge($perm['params'], $extraParams, [
                ':limit' => $limit,
                ':offset' => $offset
            ]);
            
            $fines = $this->db->fetchAll($sql, $paramsDb);
            
            return $this->success([
                'data' => $fines ?: [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("FineController index error: " . $e->getMessage());
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
     * Estatísticas de multas (com permissões)
     * GET /api/v1/fines/stats
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
                    'total_fines' => 0,
                    'total_amount' => 0,
                    'pending_amount' => 0,
                    'paid_amount' => 0,
                    'contested_amount' => 0,
                    'overdue_count' => 0,
                    'by_type' => [],
                    'by_status' => []
                ]);
            }
            
            $perm = PermissionMiddleware::scope($user, 'fines', 'f');
            
            // Estatísticas gerais
            $stats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total_fines,
                    COALESCE(SUM(f.amount), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN f.status = 'pending' OR (f.status != 'paid' AND f.status != 'contested') THEN f.amount ELSE 0 END), 0) as pending_amount,
                    COALESCE(SUM(CASE WHEN f.status = 'paid' THEN f.amount ELSE 0 END), 0) as paid_amount,
                    COALESCE(SUM(CASE WHEN f.status = 'contested' THEN f.amount ELSE 0 END), 0) as contested_amount,
                    COUNT(CASE WHEN f.status != 'paid' AND f.due_date < CURDATE() THEN 1 END) as overdue_count
                FROM fines f
                WHERE {$perm['where']}
            ", $perm['params']);
            
            // Estatísticas por tipo
            $byType = $this->db->fetchAll("
                SELECT 
                    f.type,
                    COUNT(*) as count,
                    COALESCE(SUM(f.amount), 0) as total_amount
                FROM fines f
                WHERE {$perm['where']}
                GROUP BY f.type
                ORDER BY count DESC
            ", $perm['params']);
            
            // Estatísticas por status
            $byStatus = $this->db->fetchAll("
                SELECT 
                    f.status,
                    COUNT(*) as count,
                    COALESCE(SUM(f.amount), 0) as total_amount
                FROM fines f
                WHERE {$perm['where']}
                GROUP BY f.status
                ORDER BY count DESC
            ", $perm['params']);
            
            return $this->success([
                'total_fines' => (int)($stats['total_fines'] ?? 0),
                'total_amount' => round($stats['total_amount'] ?? 0, 2),
                'pending_amount' => round($stats['pending_amount'] ?? 0, 2),
                'paid_amount' => round($stats['paid_amount'] ?? 0, 2),
                'contested_amount' => round($stats['contested_amount'] ?? 0, 2),
                'overdue_count' => (int)($stats['overdue_count'] ?? 0),
                'by_type' => $byType ?: [],
                'by_status' => $byStatus ?: []
            ]);
            
        } catch (Exception $e) {
            error_log("FineController getStats error: " . $e->getMessage());
            return $this->success([
                'total_fines' => 0,
                'total_amount' => 0,
                'pending_amount' => 0,
                'paid_amount' => 0,
                'contested_amount' => 0,
                'overdue_count' => 0,
                'by_type' => [],
                'by_status' => []
            ]);
        }
    }
    
    /**
     * Obter multa específica (com permissões)
     * GET /api/v1/fines/{id}
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
            
            if (!PermissionMiddleware::check($user, 'fines', $id)) {
                return $this->error('Sem permissão para visualizar esta multa', null, 403);
            }
            
            $fine = $this->db->fetchOne("
                SELECT f.*, 
                       v.plate as vehicle_plate, 
                       v.brand as vehicle_brand, 
                       v.model as vehicle_model,
                       d.name as driver_name,
                       d.email as driver_email,
                       d.phone as driver_phone,
                       DATEDIFF(f.due_date, CURDATE()) as days_remaining,
                       CASE 
                           WHEN f.status = 'paid' THEN 'paid'
                           WHEN f.due_date < CURDATE() THEN 'overdue'
                           WHEN f.status = 'contested' THEN 'contested'
                           ELSE 'pending'
                       END as status_calculated
                FROM fines f
                LEFT JOIN vehicles v ON f.vehicle_id = v.id
                LEFT JOIN drivers d ON f.driver_id = d.id
                WHERE f.id = :id
            ", [':id' => $id]);
            
            if (!$fine) {
                return $this->error('Multa não encontrada', null, 404);
            }
            
            return $this->success($fine);
            
        } catch (Exception $e) {
            error_log("FineController show error: " . $e->getMessage());
            return $this->error('Erro ao buscar multa', null, 500);
        }
    }
    
    /**
     * Criar multa (com permissões)
     * POST /api/v1/fines
     */
    public function create($input, $params) {
        $this->db->beginTransaction();
        
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                $this->db->rollBack();
                return $this->error('Não autenticado', null, 401);
            }
            
            $required = ['vehicle_id', 'fine_number', 'issue_date', 'due_date', 'amount'];
            $validation = $this->validateRequired($input, $required);
            if ($validation !== true) {
                $this->db->rollBack();
                return $validation;
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            if (!$user['company_id']) {
                $this->db->rollBack();
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            // Verificar se veículo pertence à empresa
            $vehicle = $this->db->fetchOne(
                "SELECT id FROM vehicles WHERE id = :id AND company_id = :company_id",
                [':id' => $input['vehicle_id'], ':company_id' => $user['company_id']]
            );
            
            if (!$vehicle) {
                $this->db->rollBack();
                return $this->error('Veículo não encontrado ou não pertence à empresa', null, 404);
            }
            
            // Validar datas
            $issueDate = strtotime($input['issue_date']);
            $dueDate = strtotime($input['due_date']);
            
            if ($issueDate === false || $dueDate === false) {
                $this->db->rollBack();
                return $this->error('Formato de data inválido', null, 400);
            }
            
            if ($issueDate > $dueDate) {
                $this->db->rollBack();
                return $this->error('A data de vencimento deve ser posterior à data de emissão', null, 400);
            }
            
            // Validar amount
            $amount = (float)$input['amount'];
            if ($amount <= 0) {
                $this->db->rollBack();
                return $this->error('O valor da multa deve ser maior que zero', null, 400);
            }
            
            // Verificar duplicidade de número de multa
            $exists = $this->db->fetchOne(
                "SELECT id FROM fines 
                 WHERE company_id = :company_id 
                 AND fine_number = :fine_number",
                [
                    ':company_id' => $user['company_id'],
                    ':fine_number' => $input['fine_number']
                ]
            );
            
            if ($exists) {
                $this->db->rollBack();
                return $this->error('Número de multa já registado para esta empresa', null, 409);
            }
            
            $fineId = $this->db->insert('fines', [
                'company_id' => $user['company_id'],
                'vehicle_id' => $input['vehicle_id'],
                'driver_id' => isset($input['driver_id']) && !empty($input['driver_id']) ? $input['driver_id'] : null,
                'fine_number' => $input['fine_number'],
                'issue_date' => $input['issue_date'],
                'due_date' => $input['due_date'],
                'amount' => $amount,
                'paid_amount' => isset($input['paid_amount']) ? (float)$input['paid_amount'] : 0,
                'type' => $input['type'] ?? 'speed',
                'severity' => $input['severity'] ?? 'mild',
                'location' => $input['location'] ?? null,
                'description' => $input['description'] ?? null,
                'status' => $input['status'] ?? 'pending',
                'payment_date' => isset($input['payment_date']) ? $input['payment_date'] : null,
                'notes' => $input['notes'] ?? null,
                'created_by' => $user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->commit();
            
            return $this->success(['id' => $fineId], 'Multa registada com sucesso', 201);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("FineController create error: " . $e->getMessage());
            
            if (strpos($e->getMessage(), '1452') !== false) {
                return $this->error('Veículo ou motorista inválido', null, 400);
            }
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false || 
                strpos($e->getMessage(), '1062') !== false) {
                return $this->error('Número de multa já registado', null, 409);
            }
            
            return $this->error('Erro ao criar multa: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Atualizar multa (com permissões)
     * PUT /api/v1/fines/{id}
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
            
            if (!PermissionMiddleware::check($user, 'fines', $id)) {
                return $this->error('Sem permissão para editar esta multa', null, 403);
            }
            
            $allowedFields = ['vehicle_id', 'driver_id', 'fine_number', 'issue_date', 'due_date', 
                              'payment_date', 'amount', 'paid_amount', 'type', 'severity', 
                              'location', 'description', 'status', 'notes'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field]) && $input[$field] !== null && $input[$field] !== '') {
                    if ($field === 'amount' || $field === 'paid_amount') {
                        $value = (float)$input[$field];
                        if ($field === 'amount' && $value <= 0) {
                            return $this->error('O valor da multa deve ser maior que zero', null, 400);
                        }
                        $updateData[$field] = $value;
                    } elseif ($field === 'vehicle_id') {
                        $vehicleId = (int)$input[$field];
                        
                        $vehicle = $this->db->fetchOne(
                            "SELECT id FROM vehicles WHERE id = :id AND company_id = :company_id",
                            [
                                ':id' => $vehicleId,
                                ':company_id' => $user['company_id']
                            ]
                        );
                        
                        if (!$vehicle) {
                            return $this->error('Veículo inválido ou não pertence à empresa', null, 400);
                        }
                        
                        $updateData[$field] = $vehicleId;
                    } elseif ($field === 'issue_date' || $field === 'due_date' || $field === 'payment_date') {
                        $updateData[$field] = $input[$field];
                    } else {
                        $updateData[$field] = $input[$field];
                    }
                }
            }
            
            if (empty($updateData)) {
                return $this->error('Nenhum dado para atualizar', null, 400);
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->update('fines', $updateData, 'id = :id AND company_id = :company_id', [
                ':id' => $id,
                ':company_id' => $user['company_id']
            ]);
            
            return $this->success(null, 'Multa atualizada com sucesso');
            
        } catch (Exception $e) {
            error_log("FineController update error: " . $e->getMessage());
            
            if (strpos($e->getMessage(), '1452') !== false) {
                return $this->error('Veículo ou motorista inválido', null, 400);
            }
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false || 
                strpos($e->getMessage(), '1062') !== false) {
                return $this->error('Número de multa já registado', null, 409);
            }
            
            return $this->error('Erro ao atualizar multa', null, 500);
        }
    }
    
    /**
     * Marcar multa como paga (com permissões)
     * POST /api/v1/fines/{id}/pay
     */
    public function markAsPaid($id, $input, $params) {
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
            
            if (!PermissionMiddleware::check($user, 'fines', $id)) {
                return $this->error('Sem permissão para esta multa', null, 403);
            }
            
            $fine = $this->db->fetchOne(
                "SELECT id, amount, status FROM fines WHERE id = :id AND company_id = :company_id",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$fine) {
                return $this->error('Multa não encontrada', null, 404);
            }
            
            if ($fine['status'] === 'paid') {
                return $this->error('Esta multa já está paga', null, 400);
            }
            
            $paidAmount = isset($input['paid_amount']) && !empty($input['paid_amount']) 
                ? (float)$input['paid_amount'] 
                : (float)$fine['amount'];
            
            $updateData = [
                'status' => 'paid',
                'payment_date' => date('Y-m-d'),
                'paid_amount' => $paidAmount,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->update('fines', $updateData, 'id = :id AND company_id = :company_id', [
                ':id' => $id,
                ':company_id' => $user['company_id']
            ]);
            
            return $this->success(null, 'Multa marcada como paga com sucesso');
            
        } catch (Exception $e) {
            error_log("FineController markAsPaid error: " . $e->getMessage());
            return $this->error('Erro ao marcar multa como paga', null, 500);
        }
    }
    
    /**
     * Marcar multa como contestada (com permissões)
     * POST /api/v1/fines/{id}/contest
     */
    public function markAsContested($id, $input, $params) {
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
            
            if (!PermissionMiddleware::check($user, 'fines', $id)) {
                return $this->error('Sem permissão para esta multa', null, 403);
            }
            
            $fine = $this->db->fetchOne(
                "SELECT id, status FROM fines WHERE id = :id AND company_id = :company_id",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$fine) {
                return $this->error('Multa não encontrada', null, 404);
            }
            
            if ($fine['status'] === 'paid') {
                return $this->error('Não é possível contestar uma multa já paga', null, 400);
            }
            
            $updateData = [
                'status' => 'contested',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->update('fines', $updateData, 'id = :id AND company_id = :company_id', [
                ':id' => $id,
                ':company_id' => $user['company_id']
            ]);
            
            return $this->success(null, 'Multa marcada como contestada com sucesso');
            
        } catch (Exception $e) {
            error_log("FineController markAsContested error: " . $e->getMessage());
            return $this->error('Erro ao marcar multa como contestada', null, 500);
        }
    }
    
    /**
     * Upload de documento (com permissões)
     * POST /api/v1/fines/{id}/document
     */
    public function uploadDocument($id, $input, $params) {
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
            
            if (!PermissionMiddleware::check($user, 'fines', $id)) {
                return $this->error('Sem permissão para esta multa', null, 403);
            }
            
            // Verificar se multa pertence à empresa
            $fine = $this->db->fetchOne(
                "SELECT id, document_file FROM fines WHERE id = :id AND company_id = :company_id",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$fine) {
                return $this->error('Multa não encontrada', null, 404);
            }
            
            // Verificar se arquivo foi enviado
            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = isset($_FILES['document']) ? $this->getUploadErrorMessage($_FILES['document']['error']) : 'Nenhum documento enviado';
                return $this->error($errorMessage, null, 400);
            }
            
            $file = $_FILES['document'];
            
            // Validar extensão
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
                return $this->error(
                    'Extensão não permitida. Use: ' . implode(', ', self::ALLOWED_EXTENSIONS),
                    null,
                    400
                );
            }
            
            // Validar MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
                return $this->error(
                    'Tipo de arquivo não permitido. Use: PDF, JPEG ou PNG',
                    null,
                    400
                );
            }
            
            // Validar tamanho
            if ($file['size'] > self::MAX_FILE_SIZE) {
                return $this->error(
                    'O arquivo deve ter no máximo ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB',
                    null,
                    400
                );
            }
            
            // Gerar nome único
            $filename = 'fine_' . $id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $filepath = $this->uploadDir . $filename;
            
            // Mover arquivo
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return $this->error('Erro ao fazer upload do documento', null, 500);
            }
            
            // Remover arquivo antigo se existir
            if (!empty($fine['document_file'])) {
                $oldFilePath = $this->uploadDir . $fine['document_file'];
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }
            
            // Atualizar banco
            $this->db->update('fines', [
                'document_file' => $filename,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id AND company_id = :company_id', [
                ':id' => $id,
                ':company_id' => $user['company_id']
            ]);
            
            $fileUrl = $this->uploadUrl . $filename;
            
            return $this->success([
                'document_url' => $fileUrl,
                'filename' => $filename,
                'size' => $file['size'],
                'mime_type' => $mimeType,
                'extension' => $extension
            ], 'Documento enviado com sucesso');
            
        } catch (Exception $e) {
            error_log("FineController uploadDocument error: " . $e->getMessage());
            return $this->error('Erro ao fazer upload do documento', null, 500);
        }
    }
    
    /**
     * Download de documento (com permissões)
     * GET /api/v1/fines/{id}/document
     */
    public function downloadDocument($id, $input, $params) {
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
            
            if (!PermissionMiddleware::check($user, 'fines', $id)) {
                return $this->error('Sem permissão para esta multa', null, 403);
            }
            
            // Buscar multa e verificar documento
            $fine = $this->db->fetchOne(
                "SELECT id, document_file, fine_number FROM fines WHERE id = :id AND company_id = :company_id",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$fine) {
                return $this->error('Multa não encontrada', null, 404);
            }
            
            if (empty($fine['document_file'])) {
                return $this->error('Nenhum documento associado a esta multa', null, 404);
            }
            
            $filePath = $this->uploadDir . $fine['document_file'];
            
            if (!file_exists($filePath)) {
                return $this->error('Arquivo não encontrado no servidor', null, 404);
            }
            
            // Forçar download
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            
            $extension = pathinfo($fine['document_file'], PATHINFO_EXTENSION);
            $filename = sprintf(
                'multa_%s_%s.%s',
                $fine['fine_number'],
                date('Y-m-d'),
                $extension
            );
            
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            readfile($filePath);
            exit;
            
        } catch (Exception $e) {
            error_log("FineController downloadDocument error: " . $e->getMessage());
            return $this->error('Erro ao baixar documento', null, 500);
        }
    }
    
    /**
     * Remover documento (com permissões)
     * DELETE /api/v1/fines/{id}/document
     */
    public function deleteDocument($id, $input, $params) {
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
            
            if (!PermissionMiddleware::check($user, 'fines', $id)) {
                return $this->error('Sem permissão para esta multa', null, 403);
            }
            
            // Verificar se multa pertence à empresa
            $fine = $this->db->fetchOne(
                "SELECT id, document_file FROM fines WHERE id = :id AND company_id = :company_id",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$fine) {
                return $this->error('Multa não encontrada', null, 404);
            }
            
            if (empty($fine['document_file'])) {
                return $this->error('Nenhum documento associado a esta multa', null, 404);
            }
            
            // Remover arquivo físico
            $filePath = $this->uploadDir . $fine['document_file'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Atualizar banco
            $this->db->update('fines', [
                'document_file' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id AND company_id = :company_id', [
                ':id' => $id,
                ':company_id' => $user['company_id']
            ]);
            
            return $this->success(null, 'Documento removido com sucesso');
            
        } catch (Exception $e) {
            error_log("FineController deleteDocument error: " . $e->getMessage());
            return $this->error('Erro ao remover documento', null, 500);
        }
    }
    
    /**
     * Deletar multa (com permissões)
     * DELETE /api/v1/fines/{id}
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
            
            if (!PermissionMiddleware::check($user, 'fines', $id)) {
                return $this->error('Sem permissão para eliminar esta multa', null, 403);
            }
            
            $fine = $this->db->fetchOne(
                "SELECT id, document_file FROM fines WHERE id = :id AND company_id = :company_id",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$fine) {
                return $this->error('Multa não encontrada', null, 404);
            }
            
            // Remover arquivo associado se existir
            if (!empty($fine['document_file'])) {
                $filePath = $this->uploadDir . $fine['document_file'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $this->db->delete('fines', 'id = :id AND company_id = :company_id', [
                ':id' => $id,
                ':company_id' => $user['company_id']
            ]);
            
            return $this->success(null, 'Multa removida com sucesso');
            
        } catch (Exception $e) {
            error_log("FineController delete error: " . $e->getMessage());
            return $this->error('Erro ao remover multa', null, 500);
        }
    }
    
    /**
     * Obter mensagem de erro de upload
     */
    private function getUploadErrorMessage($errorCode) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o tamanho máximo permitido pelo servidor',
            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo permitido pelo formulário',
            UPLOAD_ERR_PARTIAL => 'O upload foi parcialmente concluído',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever o arquivo no disco',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão PHP'
        ];
        
        return $messages[$errorCode] ?? 'Erro desconhecido no upload';
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
}