<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';

class FileController extends BaseController {
    private $db;
    private $uploadDir;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../../storage/uploads/files/';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }
    
    /**
     * Upload de arquivo
     * POST /api/v1/files/upload
     */
    public function upload($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return $this->error('Arquivo não enviado', null, 400);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                         'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                         'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        
        if (!in_array($_FILES['file']['type'], $allowedTypes)) {
            return $this->error('Tipo de arquivo não permitido', null, 400);
        }
        
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($_FILES['file']['size'] > $maxSize) {
            return $this->error('Arquivo muito grande. Máximo 10MB', null, 400);
        }
        
        $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $filename = 'file_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $this->uploadDir . $filename;
        
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
            return $this->error('Erro ao fazer upload', null, 500);
        }
        
        $fileId = $this->db->insert('files', [
            'user_id' => $authUser['user_id'],
            'original_name' => $_FILES['file']['name'],
            'file_name' => $filename,
            'file_path' => '/storage/uploads/files/' . $filename,
            'file_size' => $_FILES['file']['size'],
            'file_type' => $_FILES['file']['type'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->success([
            'id' => $fileId,
            'name' => $_FILES['file']['name'],
            'url' => '/storage/uploads/files/' . $filename,
            'size' => round($_FILES['file']['size'] / 1024, 2) . ' KB'
        ], 'Upload realizado com sucesso', 201);
    }
    
    /**
     * Listar arquivos
     * GET /api/v1/files
     */
    public function index($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $companyId = $this->getCompanyId($authUser['user_id']);
        
        $files = $this->db->fetchAll("
            SELECT f.*, u.name as user_name
            FROM files f
            LEFT JOIN users u ON f.user_id = u.id
            WHERE u.company_id = :company_id
            ORDER BY f.created_at DESC
        ", [':company_id' => $companyId]);
        
        return $this->success($files);
    }
    
    /**
     * Obter arquivo
     * GET /api/v1/files/{id}
     */
    public function show($id, $input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $file = $this->db->fetchOne("SELECT * FROM files WHERE id = :id", [':id' => $id]);
        
        if (!$file) {
            return $this->error('Arquivo não encontrado', null, 404);
        }
        
        return $this->success($file);
    }
    
    /**
     * Download de arquivo
     * GET /api/v1/files/{id}/download
     */
    public function download($id, $input, $params) {
        $file = $this->db->fetchOne("SELECT * FROM files WHERE id = :id", [':id' => $id]);
        
        if (!$file) {
            return $this->error('Arquivo não encontrado', null, 404);
        }
        
        $filepath = __DIR__ . '/../..' . $file['file_path'];
        
        if (!file_exists($filepath)) {
            return $this->error('Arquivo não encontrado no servidor', null, 404);
        }
        
        header('Content-Type: ' . $file['file_type']);
        header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    }
    
    /**
     * Remover arquivo
     * DELETE /api/v1/files/{id}
     */
    public function delete($id, $input, $params) {
        $file = $this->db->fetchOne("SELECT * FROM files WHERE id = :id", [':id' => $id]);
        
        if ($file) {
            $filepath = __DIR__ . '/../..' . $file['file_path'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            $this->db->delete('files', 'id = :id', [':id' => $id]);
        }
        
        return $this->success(null, 'Arquivo removido com sucesso');
    }
    
    /**
     * Obter company_id do usuário
     */
    private function getCompanyId($userId) {
        $result = $this->db->fetchOne("SELECT company_id FROM users WHERE id = :user_id", [':user_id' => $userId]);
        return $result['company_id'];
    }
}