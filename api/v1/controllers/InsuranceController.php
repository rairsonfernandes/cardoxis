<?php
/**
 * CARDOXIS - Insurance Controller RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

class InsuranceController extends BaseController {
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
        $this->uploadDir = $basePath . '/storage/uploads/insurances/';
        $this->uploadUrl = '/cardoxis/storage/uploads/insurances/';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Listar seguros (com permissões)
     * GET /api/v1/insurances
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
            $perm = PermissionMiddleware::scope($user, 'insurances', 'i');
            
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
                if ($status === 'expired') {
                    $extraWhere .= " AND i.end_date < CURDATE()";
                } elseif ($status === 'expiring_soon') {
                    $extraWhere .= " AND i.end_date >= CURDATE() AND DATEDIFF(i.end_date, CURDATE()) <= 30";
                } else {
                    $extraWhere .= " AND i.status = :status";
                    $extraParams[':status'] = $status;
                }
            }
            
            if (!empty($type) && $type !== 'all') {
                $extraWhere .= " AND i.type = :type";
                $extraParams[':type'] = $type;
            }
            
            if (!empty($search)) {
                $extraWhere .= " AND (i.policy_number LIKE :search OR i.insurance_company LIKE :search OR v.plate LIKE :search)";
                $extraParams[':search'] = '%' . $search . '%';
            }
            
            // Buscar total
            $countSql = "
                SELECT COUNT(*) as total 
                FROM insurances i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                WHERE {$perm['where']} {$extraWhere}
            ";
            $countResult = $this->db->fetchOne($countSql, array_merge($perm['params'], $extraParams));
            $total = $countResult ? (int)$countResult['total'] : 0;
            
            // Buscar seguros
            $sql = "
                SELECT i.*, 
                       v.plate as vehicle_plate, 
                       v.brand as vehicle_brand, 
                       v.model as vehicle_model,
                       DATEDIFF(i.end_date, CURDATE()) as days_remaining,
                       CASE 
                           WHEN i.end_date < CURDATE() THEN 'expired'
                           WHEN DATEDIFF(i.end_date, CURDATE()) <= 30 THEN 'expiring_soon'
                           ELSE 'valid'
                       END as validity_status
                FROM insurances i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                WHERE {$perm['where']} {$extraWhere}
                ORDER BY i.end_date ASC, i.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $paramsDb = array_merge($perm['params'], $extraParams, [
                ':limit' => $limit,
                ':offset' => $offset
            ]);
            
            $insurances = $this->db->fetchAll($sql, $paramsDb);
            
            return $this->success([
                'data' => $insurances ?: [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("InsuranceController index error: " . $e->getMessage());
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
     * Estatísticas de seguros (com permissões)
     * GET /api/v1/insurances/stats
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
                    'total_policies' => 0,
                    'active_policies' => 0,
                    'expired_policies' => 0,
                    'expiring_soon' => 0,
                    'total_premium' => 0,
                    'by_type' => []
                ]);
            }
            
            $perm = PermissionMiddleware::scope($user, 'insurances', 'i');
            
            $stats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total_policies,
                    SUM(CASE WHEN i.status = 'active' AND i.end_date >= CURDATE() THEN 1 ELSE 0 END) as active_policies,
                    SUM(CASE WHEN i.end_date < CURDATE() THEN 1 ELSE 0 END) as expired_policies,
                    SUM(CASE WHEN i.status = 'active' AND i.end_date >= CURDATE() AND DATEDIFF(i.end_date, CURDATE()) <= 30 THEN 1 ELSE 0 END) as expiring_soon,
                    SUM(i.premium_amount) as total_premium
                FROM insurances i
                WHERE {$perm['where']}
            ", $perm['params']);
            
            $byType = $this->db->fetchAll("
                SELECT 
                    i.type,
                    COUNT(*) as count,
                    SUM(i.premium_amount) as total_premium
                FROM insurances i
                WHERE {$perm['where']} AND i.status = 'active'
                GROUP BY i.type
            ", $perm['params']);
            
            return $this->success([
                'total_policies' => (int)($stats['total_policies'] ?? 0),
                'active_policies' => (int)($stats['active_policies'] ?? 0),
                'expired_policies' => (int)($stats['expired_policies'] ?? 0),
                'expiring_soon' => (int)($stats['expiring_soon'] ?? 0),
                'total_premium' => round($stats['total_premium'] ?? 0, 2),
                'by_type' => $byType ?: []
            ]);
            
        } catch (Exception $e) {
            error_log("InsuranceController getStats error: " . $e->getMessage());
            return $this->success([
                'total_policies' => 0,
                'active_policies' => 0,
                'expired_policies' => 0,
                'expiring_soon' => 0,
                'total_premium' => 0,
                'by_type' => []
            ]);
        }
    }
    
    /**
     * Obter seguro específico (com permissões)
     * GET /api/v1/insurances/{id}
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
            
            if (!PermissionMiddleware::check($user, 'insurances', $id)) {
                return $this->error('Sem permissão para visualizar este seguro', null, 403);
            }
            
            $insurance = $this->db->fetchOne("
                SELECT i.*, 
                       v.plate as vehicle_plate, 
                       v.brand as vehicle_brand, 
                       v.model as vehicle_model,
                       DATEDIFF(i.end_date, CURDATE()) as days_remaining
                FROM insurances i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                WHERE i.id = :id
            ", [':id' => $id]);
            
            if (!$insurance) {
                return $this->error('Seguro não encontrado', null, 404);
            }
            
            // Buscar sinistros associados
            $claims = $this->db->fetchAll("
                SELECT * FROM insurance_claims 
                WHERE insurance_id = :insurance_id
                ORDER BY date DESC
            ", [':insurance_id' => $id]);
            
            $insurance['claims'] = $claims ?: [];
            
            return $this->success($insurance);
            
        } catch (Exception $e) {
            error_log("InsuranceController show error: " . $e->getMessage());
            return $this->error('Erro ao buscar seguro', null, 500);
        }
    }
    
    /**
     * Criar seguro (com permissões)
     * POST /api/v1/insurances
     */
    public function create($input, $params) {
        $this->db->beginTransaction();
        
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                $this->db->rollBack();
                return $this->error('Não autenticado', null, 401);
            }
            
            $required = ['vehicle_id', 'insurance_company', 'policy_number', 'start_date', 'end_date', 'premium_amount'];
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
            
            // Validar datas
            $startDate = strtotime($input['start_date']);
            $endDate = strtotime($input['end_date']);
            
            if ($startDate === false || $endDate === false) {
                $this->db->rollBack();
                return $this->error('Formato de data inválido', null, 400);
            }
            
            if ($startDate >= $endDate) {
                $this->db->rollBack();
                return $this->error('A data final deve ser maior que a data inicial', null, 400);
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
            
            // Verificar duplicidade de apólice
            $exists = $this->db->fetchOne(
                "SELECT id FROM insurances 
                 WHERE company_id = :company_id 
                 AND policy_number = :policy_number",
                [
                    ':company_id' => $user['company_id'],
                    ':policy_number' => $input['policy_number']
                ]
            );
            
            if ($exists) {
                $this->db->rollBack();
                return $this->error(
                    'Número da apólice já cadastrado para esta empresa',
                    null,
                    409
                );
            }
            
            // Validar premium_amount
            $premiumAmount = (float)$input['premium_amount'];
            if ($premiumAmount <= 0) {
                $this->db->rollBack();
                return $this->error('O valor do prémio deve ser maior que zero', null, 400);
            }
            
            $insuranceId = $this->db->insert('insurances', [
                'company_id' => $user['company_id'],
                'vehicle_id' => $input['vehicle_id'],
                'insurance_company' => $input['insurance_company'],
                'policy_number' => $input['policy_number'],
                'type' => $input['type'] ?? 'comprehensive',
                'start_date' => $input['start_date'],
                'end_date' => $input['end_date'],
                'premium_amount' => $premiumAmount,
                'deductible' => isset($input['deductible']) ? (float)$input['deductible'] : 0,
                'coverage_details' => $input['coverage_details'] ?? null,
                'beneficiary' => $input['beneficiary'] ?? null,
                'broker_name' => $input['broker_name'] ?? null,
                'broker_phone' => $input['broker_phone'] ?? null,
                'broker_email' => $input['broker_email'] ?? null,
                'status' => $input['status'] ?? 'active',
                'reminder_days' => $input['reminder_days'] ?? 30,
                'notes' => $input['notes'] ?? null,
                'created_by' => $user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->commit();
            
            return $this->success(['id' => $insuranceId], 'Seguro criado com sucesso', 201);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("InsuranceController create error: " . $e->getMessage());
            
            if (strpos($e->getMessage(), '1452') !== false) {
                return $this->error('Veículo inválido ou não existe', null, 400);
            }
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false || 
                strpos($e->getMessage(), '1062') !== false) {
                return $this->error(
                    'Número da apólice já cadastrado para esta empresa',
                    null,
                    409
                );
            }
            
            return $this->error('Erro ao criar seguro: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Atualizar seguro (com permissões)
     * PUT /api/v1/insurances/{id}
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
            
            if (!PermissionMiddleware::check($user, 'insurances', $id)) {
                return $this->error('Sem permissão para editar este seguro', null, 403);
            }
            
            $currentInsurance = $this->db->fetchOne(
                "SELECT start_date, end_date, policy_number 
                 FROM insurances 
                 WHERE id = :id AND company_id = :company_id",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$currentInsurance) {
                return $this->error('Seguro não encontrado', null, 404);
            }
            
            // Validar datas
            $newStartDate = isset($input['start_date']) 
                ? $input['start_date'] 
                : $currentInsurance['start_date'];
            
            $newEndDate = isset($input['end_date']) 
                ? $input['end_date'] 
                : $currentInsurance['end_date'];
            
            $startTimestamp = strtotime($newStartDate);
            $endTimestamp = strtotime($newEndDate);
            
            if ($startTimestamp === false || $endTimestamp === false) {
                return $this->error('Formato de data inválido', null, 400);
            }
            
            if ($startTimestamp >= $endTimestamp) {
                return $this->error(
                    'A data final deve ser maior que a data inicial',
                    null,
                    400
                );
            }
            
            // Verificar duplicidade de apólice no UPDATE
            if (isset($input['policy_number'])) {
                $exists = $this->db->fetchOne(
                    "SELECT id FROM insurances 
                     WHERE company_id = :company_id 
                     AND policy_number = :policy_number 
                     AND id <> :id",
                    [
                        ':company_id' => $user['company_id'],
                        ':policy_number' => $input['policy_number'],
                        ':id' => $id
                    ]
                );
                
                if ($exists) {
                    return $this->error(
                        'Número da apólice já cadastrado para esta empresa',
                        null,
                        409
                    );
                }
            }
            
            $allowedFields = ['vehicle_id', 'insurance_company', 'policy_number', 'type', 'start_date', 'end_date', 
                              'premium_amount', 'deductible', 'coverage_details', 'beneficiary', 'broker_name', 
                              'broker_phone', 'broker_email', 'status', 'reminder_days', 'notes'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field]) && $input[$field] !== null && $input[$field] !== '') {
                    if ($field === 'premium_amount' || $field === 'deductible') {
                        $value = (float)$input[$field];
                        if ($field === 'premium_amount' && $value <= 0) {
                            return $this->error('O valor do prémio deve ser maior que zero', null, 400);
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
                    } elseif ($field === 'start_date' || $field === 'end_date') {
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
            
            $this->db->update(
                'insurances', 
                $updateData, 
                'id = :id AND company_id = :company_id', 
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            return $this->success(null, 'Seguro atualizado com sucesso');
            
        } catch (Exception $e) {
            error_log("InsuranceController update error: " . $e->getMessage());
            
            if (strpos($e->getMessage(), '1452') !== false) {
                return $this->error('Veículo inválido ou não existe', null, 400);
            }
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false || 
                strpos($e->getMessage(), '1062') !== false) {
                return $this->error(
                    'Número da apólice já cadastrado para esta empresa',
                    null,
                    409
                );
            }
            
            return $this->error('Erro ao atualizar seguro: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Deletar seguro (com permissões)
     * DELETE /api/v1/insurances/{id}
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
            
            if (!PermissionMiddleware::check($user, 'insurances', $id)) {
                return $this->error('Sem permissão para eliminar este seguro', null, 403);
            }
            
            $insurance = $this->db->fetchOne(
                "SELECT id, document_file FROM insurances WHERE id = :id AND company_id = :company_id",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$insurance) {
                return $this->error('Seguro não encontrado', null, 404);
            }
            
            // Remover arquivo associado se existir
            if (!empty($insurance['document_file'])) {
                $filePath = $this->uploadDir . $insurance['document_file'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $this->db->delete(
                'insurances', 
                'id = :id AND company_id = :company_id', 
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            return $this->success(null, 'Seguro removido com sucesso');
            
        } catch (Exception $e) {
            error_log("InsuranceController delete error: " . $e->getMessage());
            return $this->error('Erro ao remover seguro', null, 500);
        }
    }
    
    /**
     * Upload de documento (com permissões)
     * POST /api/v1/insurances/{id}/document
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
            
            if (!PermissionMiddleware::check($user, 'insurances', $id)) {
                return $this->error('Sem permissão para este seguro', null, 403);
            }
            
            // Verificar se seguro pertence à empresa
            $insurance = $this->db->fetchOne(
                "SELECT id, document_file FROM insurances WHERE id = :id AND company_id = :company_id",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$insurance) {
                return $this->error('Seguro não encontrado', null, 404);
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
            
            // Validar correspondência MIME x Extensão
            $mimeToExtension = [
                'application/pdf' => ['pdf'],
                'image/jpeg' => ['jpg', 'jpeg'],
                'image/png' => ['png']
            ];
            
            if (isset($mimeToExtension[$mimeType]) && 
                !in_array($extension, $mimeToExtension[$mimeType])) {
                return $this->error(
                    'A extensão do arquivo não corresponde ao tipo MIME',
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
            $filename = 'insurance_' . $id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $filepath = $this->uploadDir . $filename;
            
            // Mover arquivo
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return $this->error('Erro ao fazer upload do documento', null, 500);
            }
            
            // Remover arquivo antigo se existir
            if (!empty($insurance['document_file'])) {
                $oldFilePath = $this->uploadDir . $insurance['document_file'];
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }
            
            // Atualizar banco
            $this->db->update(
                'insurances', 
                [
                    'document_file' => $filename, 
                    'updated_at' => date('Y-m-d H:i:s')
                ], 
                'id = :id AND company_id = :company_id', 
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            $fileUrl = $this->uploadUrl . $filename;
            
            return $this->success([
                'document_url' => $fileUrl,
                'filename' => $filename,
                'size' => $file['size'],
                'mime_type' => $mimeType,
                'extension' => $extension
            ], 'Documento enviado com sucesso');
            
        } catch (Exception $e) {
            error_log("InsuranceController uploadDocument error: " . $e->getMessage());
            return $this->error('Erro ao fazer upload do documento', null, 500);
        }
    }
    
    /**
     * Download de documento (com permissões)
     * GET /api/v1/insurances/{id}/document
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
            
            if (!PermissionMiddleware::check($user, 'insurances', $id)) {
                return $this->error('Sem permissão para este seguro', null, 403);
            }
            
            // Buscar seguro e verificar documento
            $insurance = $this->db->fetchOne(
                "SELECT id, document_file, policy_number, insurance_company 
                 FROM insurances 
                 WHERE id = :id AND company_id = :company_id",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$insurance) {
                return $this->error('Seguro não encontrado', null, 404);
            }
            
            if (empty($insurance['document_file'])) {
                return $this->error('Nenhum documento associado a este seguro', null, 404);
            }
            
            $filePath = $this->uploadDir . $insurance['document_file'];
            
            if (!file_exists($filePath)) {
                return $this->error('Arquivo não encontrado no servidor', null, 404);
            }
            
            // Forçar download
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            
            $extension = pathinfo($insurance['document_file'], PATHINFO_EXTENSION);
            $filename = sprintf(
                'apolice_%s_%s.%s',
                $insurance['policy_number'],
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
            error_log("InsuranceController downloadDocument error: " . $e->getMessage());
            return $this->error('Erro ao baixar documento', null, 500);
        }
    }
    
    /**
     * Remover documento (com permissões)
     * DELETE /api/v1/insurances/{id}/document
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
            
            if (!PermissionMiddleware::check($user, 'insurances', $id)) {
                return $this->error('Sem permissão para este seguro', null, 403);
            }
            
            // Verificar se seguro pertence à empresa
            $insurance = $this->db->fetchOne(
                "SELECT id, document_file FROM insurances WHERE id = :id AND company_id = :company_id",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$insurance) {
                return $this->error('Seguro não encontrado', null, 404);
            }
            
            if (empty($insurance['document_file'])) {
                return $this->error('Nenhum documento associado a este seguro', null, 404);
            }
            
            // Remover arquivo físico
            $filePath = $this->uploadDir . $insurance['document_file'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Atualizar banco
            $this->db->update(
                'insurances', 
                [
                    'document_file' => null, 
                    'updated_at' => date('Y-m-d H:i:s')
                ], 
                'id = :id AND company_id = :company_id', 
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            return $this->success(null, 'Documento removido com sucesso');
            
        } catch (Exception $e) {
            error_log("InsuranceController deleteDocument error: " . $e->getMessage());
            return $this->error('Erro ao remover documento', null, 500);
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