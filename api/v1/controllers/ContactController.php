<?php
/**
 * CARDOXIS - Contact Controller RF
 */

require_once __DIR__ . '/BaseController.php';

class ContactController extends BaseController {
    private $db;
    
    public function __construct() {
        // Conectar diretamente ao banco
        $host = 'localhost';
        $dbname = 'cardoxis_db';
        $username = 'root';
        $password = '';
        
        try {
            $this->db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("[CONTACT] Erro ao conectar: " . $e->getMessage());
        }
    }
    
    /**
     * Enviar mensagem de contacto - POST /api/v1/contact/send
     */
    public function send($input, $params) {
        // Headers
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            return null;
        }
        
        try {
            // Log
            error_log("[CONTACT] Input: " . print_r($input, true));
            
            // Validar campos obrigatórios
            if (empty($input['name'])) {
                return $this->jsonResponse(false, 'O nome é obrigatório', null, 400);
            }
            
            if (empty($input['email'])) {
                return $this->jsonResponse(false, 'O e-mail é obrigatório', null, 400);
            }
            
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->jsonResponse(false, 'E-mail inválido', null, 400);
            }
            
            if (empty($input['subject'])) {
                return $this->jsonResponse(false, 'O assunto é obrigatório', null, 400);
            }
            
            if (empty($input['message']) || strlen(trim($input['message'])) < 10) {
                return $this->jsonResponse(false, 'A mensagem deve ter no mínimo 10 caracteres', null, 400);
            }
            
            // Verificar conexão
            if (!$this->db) {
                return $this->jsonResponse(false, 'Erro de conexão com banco de dados', null, 500);
            }
            
            // Inserir no banco
            $sql = "INSERT INTO contacts (name, email, phone, subject, message, status, ip_address, user_agent, created_at) 
                    VALUES (:name, :email, :phone, :subject, :message, :status, :ip_address, :user_agent, :created_at)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':name' => trim($input['name']),
                ':email' => trim($input['email']),
                ':phone' => isset($input['phone']) ? trim($input['phone']) : null,
                ':subject' => trim($input['subject']),
                ':message' => trim($input['message']),
                ':status' => 'pending',
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                ':created_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$result) {
                return $this->jsonResponse(false, 'Erro ao salvar mensagem', null, 500);
            }
            
            $contactId = $this->db->lastInsertId();
            error_log("[CONTACT] Mensagem salva com sucesso! ID: " . $contactId);
            
            return $this->jsonResponse(true, 'Mensagem enviada com sucesso!', [
                'id' => $contactId
            ], 201);
            
        } catch (PDOException $e) {
            error_log("[CONTACT] PDO Exception: " . $e->getMessage());
            return $this->jsonResponse(false, 'Erro de banco de dados: ' . $e->getMessage(), null, 500);
        } catch (Exception $e) {
            error_log("[CONTACT] Exception: " . $e->getMessage());
            return $this->jsonResponse(false, 'Erro interno: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Solicitar callback - POST /api/v1/contact/callback
     */
    public function callback($input, $params) {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            return null;
        }
        
        try {
            if (empty($input['name'])) {
                return $this->jsonResponse(false, 'O nome é obrigatório', null, 400);
            }
            
            if (empty($input['phone'])) {
                return $this->jsonResponse(false, 'O telefone é obrigatório', null, 400);
            }
            
            if (!$this->db) {
                return $this->jsonResponse(false, 'Erro de conexão com banco de dados', null, 500);
            }
            
            $sql = "INSERT INTO contact_callbacks (name, phone, time_preference, status, ip_address, created_at) 
                    VALUES (:name, :phone, :time_preference, :status, :ip_address, :created_at)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':name' => trim($input['name']),
                ':phone' => trim($input['phone']),
                ':time_preference' => isset($input['time']) ? trim($input['time']) : null,
                ':status' => 'pending',
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':created_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$result) {
                return $this->jsonResponse(false, 'Erro ao salvar callback', null, 500);
            }
            
            $callbackId = $this->db->lastInsertId();
            
            return $this->jsonResponse(true, 'Callback solicitado com sucesso!', [
                'id' => $callbackId
            ], 201);
            
        } catch (Exception $e) {
            error_log("[CONTACT] Callback error: " . $e->getMessage());
            return $this->jsonResponse(false, 'Erro ao solicitar callback', null, 500);
        }
    }
    
    /**
     * Resposta JSON
     */
    private function jsonResponse($success, $message, $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        
        $response = [
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return null;
    }
}