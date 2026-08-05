<?php
/**
 * CARDOXIS - Document Controller  RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

class DocumentController extends BaseController {
    private $db;
    private $uploadDir;
    private $uploadUrl;
    private $logFile;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        $basePath = dirname(__DIR__, 3);
        $this->uploadDir = $basePath . '/storage/uploads/documents/';
        $this->uploadUrl = '/cardoxis/storage/uploads/documents/';
        $this->logFile = __DIR__ . '/../logs/audit.log';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
        
        if (!is_dir(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0777, true);
        }
    }
    
    /**
     * Listar documentos
     * GET /api/v1/documents
     */
    public function index($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
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
            
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            $companyId = $this->getCompanyId($userId);
            
            if (!$companyId) {
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
            
            $limit = isset($params['limit']) ? (int)$params['limit'] : 15;
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            $where = "d.company_id = ?";
            $paramsDb = [$companyId];
            
            if ($userRole === 'user') {
                $where .= " AND d.created_by = ?";
                $paramsDb[] = $userId;
            }
            
            if (isset($params['entity_type']) && $params['entity_type'] && $params['entity_type'] !== 'all') {
                $where .= " AND d.entity_type = ?";
                $paramsDb[] = $params['entity_type'];
            }
            
            if (isset($params['type']) && $params['type'] && $params['type'] !== 'all') {
                $where .= " AND d.type = ?";
                $paramsDb[] = $params['type'];
            }
            
            if (isset($params['status']) && $params['status'] && $params['status'] !== 'all') {
                if ($params['status'] === 'expired') {
                    $where .= " AND d.expiry_date < CURDATE() AND d.expiry_date IS NOT NULL";
                } elseif ($params['status'] === 'expiring') {
                    $where .= " AND d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
                }
            }
            
            $sql = "
                SELECT 
                    d.*,
                    u.name as uploaded_by_name,
                    CASE 
                        WHEN d.entity_type = 'vehicle' THEN CONCAT(v.brand, ' ', v.model, ' (', v.plate, ')')
                        WHEN d.entity_type = 'driver' THEN dr.name
                        WHEN d.entity_type = 'company' THEN c.name
                        ELSE '-'
                    END as entity_name
                FROM documents d
                LEFT JOIN companies c ON d.entity_type = 'company' AND d.entity_id = c.id
                LEFT JOIN vehicles v ON d.entity_type = 'vehicle' AND d.entity_id = v.id
                LEFT JOIN drivers dr ON d.entity_type = 'driver' AND d.entity_id = dr.id
                LEFT JOIN users u ON d.uploaded_by = u.id
                WHERE {$where}
                ORDER BY d.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $paramsDb[] = $limit;
            $paramsDb[] = $offset;
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($paramsDb);
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countSql = "SELECT COUNT(*) as total FROM documents d WHERE {$where}";
            $countParams = array_slice($paramsDb, 0, -2);
            $stmt = $this->db->getConnection()->prepare($countSql);
            $stmt->execute($countParams);
            $totalCount = (int)$stmt->fetchColumn();
            
            return $this->success([
                'data' => $documents ?: [],
                'pagination' => [
                    'current_page' => (int)$page,
                    'per_page' => (int)$limit,
                    'total' => (int)$totalCount,
                    'last_page' => (int)ceil($totalCount / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("[DOCUMENTOS] ERRO: " . $e->getMessage());
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
     * Upload de documento
     * POST /api/v1/documents
     */
    public function upload($input, $params) {
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
            
            $entity_type = isset($_POST['entity_type']) ? $_POST['entity_type'] : null;
            $entity_id = isset($_POST['entity_id']) ? (int)$_POST['entity_id'] : null;
            $type = isset($_POST['type']) ? $_POST['type'] : null;
            $title = isset($_POST['title']) ? $_POST['title'] : null;
            
            if (!$entity_type || !$entity_id || !$type || !$title) {
                return $this->error('Campos obrigatórios faltando', null, 400);
            }
            
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                return $this->error('Arquivo não enviado', null, 400);
            }
            
            $allowedTypes = [
                'application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'image/webp',
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];
            
            $fileType = $_FILES['file']['type'];
            if (!in_array($fileType, $allowedTypes)) {
                return $this->error('Tipo de arquivo não permitido', null, 400);
            }
            
            $maxSize = 10 * 1024 * 1024;
            if ($_FILES['file']['size'] > $maxSize) {
                return $this->error('Arquivo muito grande. Máximo 10MB', null, 400);
            }
            
            $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $filename = 'doc_' . $entity_type . '_' . $entity_id . '_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $this->uploadDir . $filename;
            
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
                return $this->error('Erro ao fazer upload do arquivo', null, 500);
            }
            
            $expiryDate = isset($_POST['expiry_date']) && !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
            
            $data = [
                'company_id' => $companyId,
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'type' => $type,
                'title' => $title,
                'description' => isset($_POST['description']) ? $_POST['description'] : null,
                'file_path' => $this->uploadUrl . $filename,
                'file_name' => $_FILES['file']['name'],
                'file_size' => $_FILES['file']['size'],
                'file_type' => $fileType,
                'expiry_date' => $expiryDate,
                'reminder_days' => isset($_POST['reminder_days']) ? (int)$_POST['reminder_days'] : 30,
                'status' => 'valid',
                'uploaded_by' => $userId,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $documentId = $this->db->insert('documents', $data);
            
            if (!$documentId) {
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                return $this->error('Erro ao salvar documento', null, 500);
            }
            
            // Registrar auditoria
            $this->logAudit(
                $userId,
                $companyId,
                'create',
                'document',
                $documentId,
                'Documento enviado: ' . $title,
                null,
                $data
            );
            
            return $this->success([
                'id' => $documentId,
                'file_path' => $this->uploadUrl . $filename,
                'file_name' => $_FILES['file']['name']
            ], 'Documento enviado com sucesso', 201);
            
        } catch (Exception $e) {
            error_log("DocumentController upload error: " . $e->getMessage());
            return $this->error('Erro ao enviar documento: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Obter documento específico
     * GET /api/v1/documents/{id}
     */
    public function show($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$id || $id === 'null' || $id === 'undefined' || $id === '') {
                return $this->error('ID do documento inválido', null, 400);
            }
            
            $document = $this->db->fetchOne("
                SELECT d.*, u.name as uploaded_by_name
                FROM documents d
                LEFT JOIN users u ON d.uploaded_by = u.id
                WHERE d.id = ?
            ", [$id]);
            
            if (!$document) {
                return $this->error('Documento não encontrado', null, 404);
            }
            
            $document['entity_name'] = $this->getEntityName($document['entity_type'], $document['entity_id']);
            
            return $this->success($document);
            
        } catch (Exception $e) {
            error_log("DocumentController show error: " . $e->getMessage());
            return $this->error('Erro ao buscar documento: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Atualizar documento
     * PUT /api/v1/documents/{id}
     */
    public function update($id, $input, $params) {
        try {
            error_log("=== DOCUMENT UPDATE ===");
            error_log("ID: " . $id);
            
            $rawInput = file_get_contents('php://input');
            error_log("Raw input: " . $rawInput);
            
            if (empty($rawInput)) {
                return $this->error('Nenhum dado enviado. Envie um JSON válido.', null, 400);
            }
            
            $data = json_decode($rawInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON Error: " . json_last_error_msg());
                return $this->error('JSON inválido: ' . json_last_error_msg(), null, 400);
            }
            
            error_log("Decoded data: " . json_encode($data));
            
            if (empty($data) || !is_array($data)) {
                return $this->error('Nenhum dado válido no JSON.', null, 400);
            }
            
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$id || $id === 'null' || $id === 'undefined' || $id === '') {
                return $this->error('ID do documento inválido', null, 400);
            }
            
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            $companyId = $this->getCompanyId($userId);
            
            $oldDocument = $this->db->fetchOne(
                "SELECT * FROM documents WHERE id = ?",
                [$id]
            );
            
            if (!$oldDocument) {
                error_log("Documento não encontrado: $id");
                return $this->error('Documento não encontrado', null, 404);
            }
            
            if ($oldDocument['company_id'] != $companyId) {
                return $this->error('Sem permissão para editar este documento', null, 403);
            }
            
            if ($userRole === 'user' && $oldDocument['created_by'] != $userId) {
                return $this->error('Sem permissão para editar este documento', null, 403);
            }
            
            $allowedFields = ['title', 'description', 'expiry_date', 'reminder_days', 'status'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $value = $data[$field];
                    
                    if ($field === 'title' && (empty($value) || trim($value) === '')) {
                        return $this->error('O título é obrigatório', null, 400);
                    }
                    
                    if ($field === 'reminder_days') {
                        $updateData[$field] = (int)$value;
                    } elseif ($field === 'expiry_date') {
                        $updateData[$field] = (empty($value) || $value === '') ? null : $value;
                    } else {
                        $updateData[$field] = $value;
                    }
                }
            }
            
            if (empty($updateData)) {
                return $this->error('Nenhum dado válido para atualizar', null, 400);
            }
            
            error_log("Update Data: " . json_encode($updateData));
            
            $this->db->update('documents', $updateData, 'id = :id', [':id' => $id]);
            
            // Registrar auditoria
            $this->logAudit(
                $userId,
                $companyId,
                'update',
                'document',
                $id,
                'Documento atualizado: ' . ($updateData['title'] ?? $oldDocument['title']),
                $oldDocument,
                $updateData
            );
            
            return $this->success(null, 'Documento atualizado com sucesso');
            
        } catch (Exception $e) {
            error_log("[DOCUMENT_UPDATE] ERRO: " . $e->getMessage());
            return $this->error('Erro ao atualizar documento: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Remover documento
     * DELETE /api/v1/documents/{id}
     */
    public function delete($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$id || $id === 'null' || $id === 'undefined' || $id === '') {
                return $this->error('ID do documento inválido', null, 400);
            }
            
            $userId = $authUser['user_id'];
            $companyId = $this->getCompanyId($userId);
            
            $doc = $this->db->fetchOne("SELECT * FROM documents WHERE id = ?", [$id]);
            
            if (!$doc) {
                return $this->error('Documento não encontrado', null, 404);
            }
            
            // Registrar auditoria ANTES de deletar
            $this->logAudit(
                $userId,
                $companyId,
                'delete',
                'document',
                $id,
                'Documento removido: ' . $doc['title'],
                $doc,
                null
            );
            
            if ($doc && $doc['file_path']) {
                $filename = basename($doc['file_path']);
                $filepath = $this->uploadDir . $filename;
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
            
            $this->db->delete('documents', 'id = ?', [$id]);
            
            return $this->success(null, 'Documento removido com sucesso');
            
        } catch (Exception $e) {
            error_log("DocumentController delete error: " . $e->getMessage());
            return $this->error('Erro ao remover documento: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Download de documento
     * GET /api/v1/documents/{id}/download
     */
    public function download($id, $input, $params) {
        try {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);
                exit;
            }
            
            if (!$id || $id === 'null' || $id === 'undefined' || $id === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID do documento inválido']);
                exit;
            }
            
            $doc = $this->db->fetchOne("SELECT file_path, file_name FROM documents WHERE id = ?", [$id]);
            
            if (!$doc || !$doc['file_path']) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Documento não encontrado']);
                exit;
            }
            
            $filename = basename($doc['file_path']);
            $filepath = $this->uploadDir . $filename;
            
            if (!file_exists($filepath)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Arquivo não encontrado']);
                exit;
            }
            
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filepath);
            finfo_close($finfo);
            
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . $doc['file_name'] . '"');
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            header('Expires: 0');
            
            readfile($filepath);
            exit;
            
        } catch (Exception $e) {
            error_log("DocumentController download error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao fazer download: ' . $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Documentos vencidos
     * GET /api/v1/documents/expired
     */
    public function getExpired($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $companyId = $this->getCompanyId($authUser['user_id']);
            
            $documents = $this->db->fetchAll(
                "SELECT * FROM documents WHERE company_id = ? AND expiry_date < CURDATE() AND expiry_date IS NOT NULL ORDER BY expiry_date ASC",
                [$companyId]
            );
            
            foreach ($documents as &$doc) {
                $doc['entity_name'] = $this->getEntityName($doc['entity_type'], $doc['entity_id']);
            }
            
            return $this->success($documents ?: []);
            
        } catch (Exception $e) {
            error_log("DocumentController getExpired error: " . $e->getMessage());
            return $this->success([], 'Erro ao buscar documentos vencidos');
        }
    }
    
    /**
     * Documentos a vencer
     * GET /api/v1/documents/expiring
     */
    public function getExpiring($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $companyId = $this->getCompanyId($authUser['user_id']);
            $days = isset($params['days']) ? (int)$params['days'] : 30;
            
            $documents = $this->db->fetchAll(
                "SELECT * FROM documents WHERE company_id = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY) AND expiry_date IS NOT NULL ORDER BY expiry_date ASC",
                [$companyId, $days]
            );
            
            foreach ($documents as &$doc) {
                $doc['entity_name'] = $this->getEntityName($doc['entity_type'], $doc['entity_id']);
            }
            
            return $this->success($documents ?: []);
            
        } catch (Exception $e) {
            error_log("DocumentController getExpiring error: " . $e->getMessage());
            return $this->success([], 'Erro ao buscar documentos a vencer');
        }
    }
    
    /**
     * Categorias de documentos
     * GET /api/v1/documents/categories
     */
    public function getCategories($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $companyId = $this->getCompanyId($authUser['user_id']);
            
            $categories = $this->db->fetchAll(
                "SELECT type, COUNT(*) as count FROM documents WHERE company_id = ? GROUP BY type ORDER BY type ASC",
                [$companyId]
            );
            
            return $this->success($categories ?: []);
            
        } catch (Exception $e) {
            error_log("DocumentController getCategories error: " . $e->getMessage());
            return $this->success([], 'Erro ao buscar categorias');
        }
    }
    
    /**
     * Obter nome da entidade
     */
    private function getEntityName($entityType, $entityId) {
        try {
            if ($entityType === 'vehicle') {
                $result = $this->db->fetchOne("SELECT CONCAT(brand, ' ', model, ' (', plate, ')') as name FROM vehicles WHERE id = ?", [$entityId]);
                return $result ? $result['name'] : '-';
            } elseif ($entityType === 'driver') {
                $result = $this->db->fetchOne("SELECT name FROM drivers WHERE id = ?", [$entityId]);
                return $result ? $result['name'] : '-';
            } elseif ($entityType === 'company') {
                $result = $this->db->fetchOne("SELECT name FROM companies WHERE id = ?", [$entityId]);
                return $result ? $result['name'] : '-';
            }
            return '-';
        } catch (Exception $e) {
            return '-';
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
            error_log("[DOCUMENT] Erro ao buscar company_id: " . $e->getMessage());
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