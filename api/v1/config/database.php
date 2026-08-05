<?php
/**
 * Configuração do Banco de Dados RF
 */

class Database {
    private static $instance = null;
    private $connection;
    private $logFile;
    
    private $host = 'DB_HOST';
    private $dbname = 'DB_NAME';
    private $username = 'DB_USER';
    private $password = 'DB_PASSWORD';
    
    private function __construct() {
        $this->logFile = __DIR__ . '/../logs/database.log';
        
        // Criar diretório de logs se não existir
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 30
                ]
            );
            
            // Registrar conexão bem-sucedida (apenas em debug)
            // $this->log("Database connection established successfully");
            
        } catch(PDOException $e) {
            // Log do erro
            $this->log("Database Error: " . $e->getMessage());
            $this->log("Error Code: " . $e->getCode());
            
            die(json_encode([
                'success' => false,
                'error' => 'Database Connection Failed',
                'message' => 'Erro ao conectar ao banco de dados. Tente novamente mais tarde.',
                'timestamp' => date('Y-m-d H:i:s')
            ]));
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($sql) {
        try {
            return $this->connection->prepare($sql);
        } catch (PDOException $e) {
            $this->log("Prepare Error: " . $e->getMessage());
            $this->log("SQL: " . $sql);
            throw $e;
        }
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->log("Query Error: " . $e->getMessage());
            $this->log("SQL: " . $sql);
            $this->log("Params: " . json_encode($params));
            throw $e;
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function insert($table, $data) {
        try {
            $fields = array_keys($data);
            $placeholders = ':' . implode(', :', $fields);
            
            $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
            $stmt = $this->prepare($sql);
            
            $execData = [];
            foreach ($data as $key => $value) {
                $execData[":{$key}"] = is_array($value) ? json_encode($value) : $value;
            }
            
            $stmt->execute($execData);
            return $this->connection->lastInsertId();
            
        } catch (PDOException $e) {
            $this->log("Insert Error: " . $e->getMessage());
            $this->log("Table: " . $table);
            $this->log("Data: " . json_encode($data));
            throw $e;
        }
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        try {
            if (empty($data)) {
                return 0;
            }
            
            $set = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                $set[] = "{$key} = :set_{$key}";
                $params[":set_{$key}"] = is_array($value) ? json_encode($value) : $value;
            }
            
            foreach ($whereParams as $key => $value) {
                $paramKey = (strpos($key, ':') === 0) ? $key : ":{$key}";
                $params[$paramKey] = is_array($value) ? json_encode($value) : $value;
            }
            
            $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            $this->log("Update Error: " . $e->getMessage());
            $this->log("Table: " . $table);
            $this->log("Data: " . json_encode($data));
            throw $e;
        }
    }
    
    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->prepare($sql);
            
            $execParams = [];
            foreach ($params as $key => $value) {
                $execParams[$key] = is_array($value) ? json_encode($value) : $value;
            }
            
            $stmt->execute($execParams);
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            $this->log("Delete Error: " . $e->getMessage());
            $this->log("Table: " . $table);
            $this->log("Where: " . $where);
            throw $e;
        }
    }
    
    public function beginTransaction() {
        $this->connection->beginTransaction();
    }
    
    public function commit() {
        $this->connection->commit();
    }
    
    public function rollback() {
        $this->connection->rollBack();
    }
    
    /**
     * Registrar log do banco de dados
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }
}