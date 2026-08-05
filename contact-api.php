<?php
/**
 * CARDOXIS - Contact API Direct
 * Endpoint direto para contacto
 * Version: 2.0.0
 */

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuração do banco
$host = 'DB_HOST';
$dbname = 'DB_NAME';
$username = 'DB_USER';
$password = 'DB_PASSWORD';

// Obter dados
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input) {
    $input = $_POST;
}

// Verificar se é callback
$isCallback = isset($_GET['callback']) || isset($input['callback']);

if ($isCallback) {
    // ==================== CALLBACK ====================
    $errors = [];
    if (empty($input['name'])) $errors[] = 'Nome é obrigatório';
    if (empty($input['email'])) $errors[] = 'E-mail é obrigatório';
    if (empty($input['phone'])) $errors[] = 'Telefone é obrigatório';
    if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido';

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Verificar se a tabela contact_callbacks existe
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'contact_callbacks'");
        if ($tableCheck->rowCount() == 0) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `contact_callbacks` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `email` VARCHAR(255) NOT NULL,
                    `phone` VARCHAR(20) NOT NULL,
                    `time_preference` VARCHAR(50) NULL,
                    `status` ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
                    `ip_address` VARCHAR(45) NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `completed_at` DATETIME NULL,
                    `notes` TEXT NULL,
                    INDEX idx_status (`status`),
                    INDEX idx_created_at (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        $sql = "INSERT INTO contact_callbacks (name, email, phone, time_preference, status, ip_address, created_at) 
                VALUES (:name, :email, :phone, :time_preference, :status, :ip_address, :created_at)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':name' => trim($input['name']),
            ':email' => trim($input['email']),
            ':phone' => trim($input['phone']),
            ':time_preference' => isset($input['time']) ? trim($input['time']) : null,
            ':status' => 'pending',
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            $id = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Callback solicitado com sucesso!',
                'data' => [
                    'id' => $id
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao salvar callback',
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
    
} else {
    // ==================== CONTACTO ====================
    $errors = [];
    if (empty($input['name'])) $errors[] = 'Nome é obrigatório';
    if (empty($input['email'])) $errors[] = 'E-mail é obrigatório';
    if (empty($input['subject'])) $errors[] = 'Assunto é obrigatório';
    if (empty($input['message'])) $errors[] = 'Mensagem é obrigatória';
    if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido';
    if (!empty($input['message']) && strlen(trim($input['message'])) < 10) $errors[] = 'Mensagem deve ter no mínimo 10 caracteres';

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'contacts'");
        if ($tableCheck->rowCount() == 0) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `contacts` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `email` VARCHAR(255) NOT NULL,
                    `phone` VARCHAR(20) NULL,
                    `subject` VARCHAR(100) NOT NULL,
                    `message` TEXT NOT NULL,
                    `status` ENUM('pending', 'read', 'replied', 'archived') DEFAULT 'pending',
                    `ip_address` VARCHAR(45) NULL,
                    `user_agent` TEXT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `read_at` DATETIME NULL,
                    `replied_at` DATETIME NULL,
                    `replied_by` INT UNSIGNED NULL,
                    `notes` TEXT NULL,
                    INDEX idx_status (`status`),
                    INDEX idx_created_at (`created_at`),
                    INDEX idx_email (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        $sql = "INSERT INTO contacts (name, email, phone, subject, message, status, ip_address, user_agent, created_at) 
                VALUES (:name, :email, :phone, :subject, :message, :status, :ip_address, :user_agent, :created_at)";
        
        $stmt = $pdo->prepare($sql);
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
        
        if ($result) {
            $id = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso!',
                'data' => [
                    'id' => $id
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao salvar mensagem',
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
}