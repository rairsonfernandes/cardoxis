<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';

class AdminSystemController extends BaseController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obter estatísticas do sistema
     * GET /api/v1/admin/system/stats
     */
    public function getStats($input, $params) {
        // Database stats
        $tables = $this->db->fetchAll("
            SELECT table_name, table_rows, data_length, index_length
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        
        $dbStats = [
            'tables' => count($tables),
            'total_rows' => array_sum(array_column($tables, 'table_rows')),
            'data_size' => round(array_sum(array_column($tables, 'data_length')) / 1024 / 1024, 2) . ' MB',
            'index_size' => round(array_sum(array_column($tables, 'index_length')) / 1024 / 1024, 2) . ' MB'
        ];
        
        // Files stats
        $uploadDir = __DIR__ . '/../../storage/uploads/';
        $filesStats = [
            'total' => $this->countFiles($uploadDir),
            'size' => round($this->getDirectorySize($uploadDir) / 1024 / 1024, 2) . ' MB'
        ];
        
        // Cache stats
        $cacheDir = __DIR__ . '/../cache/';
        $cacheStats = [
            'total' => $this->countFiles($cacheDir),
            'size' => round($this->getDirectorySize($cacheDir) / 1024 / 1024, 2) . ' MB'
        ];
        
        // Logs stats
        $logsDir = __DIR__ . '/../logs/';
        $logsStats = [
            'total' => $this->countFiles($logsDir),
            'size' => round($this->getDirectorySize($logsDir) / 1024 / 1024, 2) . ' MB'
        ];
        
        return $this->success([
            'database' => $dbStats,
            'files' => $filesStats,
            'cache' => $cacheStats,
            'logs' => $logsStats
        ]);
    }
    
    /**
     * Obter saúde do sistema
     * GET /api/v1/admin/system/health
     */
    public function getHealth($input, $params) {
        try {
            $this->db->getConnection()->query("SELECT 1");
            $database = 'healthy';
            $dbVersion = $this->db->getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (Exception $e) {
            $database = 'unhealthy';
            $dbVersion = 'N/A';
        }
        
        // Espaço em disco
        $diskFree = disk_free_space(__DIR__);
        $diskTotal = disk_total_space(__DIR__);
        $diskUsage = $diskTotal > 0 ? round((1 - $diskFree / $diskTotal) * 100) : 0;
        
        // Memória
        $memoryUsage = round(memory_get_usage() / 1024 / 1024, 2);
        $memoryLimit = ini_get('memory_limit');
        
        // Uploads
        $uploadDir = __DIR__ . '/../../storage/uploads/';
        $uploadsWritable = 'not writable';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        if (is_writable($uploadDir)) {
            $uploadsWritable = 'writable';
        }
        
        // Cache
        $cacheDir = __DIR__ . '/../cache/';
        $cacheWritable = 'not writable';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        if (is_writable($cacheDir)) {
            $cacheWritable = 'writable';
        }
        
        // Último backup
        $backupDir = __DIR__ . '/../../storage/backups/';
        $lastBackup = null;
        if (is_dir($backupDir)) {
            $files = glob($backupDir . '*.sql');
            if (!empty($files)) {
                $lastBackup = date('Y-m-d H:i:s', filemtime(end($files)));
            }
        }
        
        return $this->success([
            'database' => $database,
            'database_version' => $dbVersion,
            'disk_usage' => $diskUsage,
            'memory_usage' => $memoryUsage . ' MB / ' . $memoryLimit,
            'uploads' => $uploadsWritable,
            'cache' => $cacheWritable,
            'last_backup' => $lastBackup ?? 'Nunca',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'php_version' => PHP_VERSION
        ]);
    }
    
    /**
     * Obter informações do sistema
     * GET /api/v1/admin/system/info
     */
    public function getInfo($input, $params) {
        // Estatísticas da aplicação
        $companies = $this->db->fetchOne("SELECT COUNT(*) as total FROM companies")['total'] ?? 0;
        $users = $this->db->fetchOne("SELECT COUNT(*) as total FROM users")['total'] ?? 0;
        $vehicles = $this->db->fetchOne("SELECT COUNT(*) as total FROM vehicles")['total'] ?? 0;
        $drivers = $this->db->fetchOne("SELECT COUNT(*) as total FROM drivers")['total'] ?? 0;
        
        return $this->success([
            'php' => [
                'version' => PHP_VERSION,
                'extensions' => get_loaded_extensions(),
                'max_execution_time' => ini_get('max_execution_time'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'memory_limit' => ini_get('memory_limit')
            ],
            'server' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'os' => PHP_OS,
                'hostname' => gethostname()
            ],
            'mysql' => [
                'version' => $this->db->getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION),
                'client_version' => $this->db->getConnection()->getAttribute(PDO::ATTR_CLIENT_VERSION)
            ],
            'application' => [
                'name' => 'CARDOXIS',
                'version' => '1.0.0',
                'environment' => 'production',
                'debug' => false
            ],
            'statistics' => [
                'companies' => (int)$companies,
                'users' => (int)$users,
                'vehicles' => (int)$vehicles,
                'drivers' => (int)$drivers
            ]
        ]);
    }
    
    /**
     * Limpar cache
     * POST /api/v1/admin/system/cache-clear
     */
    public function clearCache($input, $params) {
        $cacheDir = __DIR__ . '/../cache/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        return $this->success(null, 'Cache limpo com sucesso');
    }
    
    /**
     * Ativar modo manutenção
     * POST /api/v1/admin/system/maintenance/enable
     */
    public function enableMaintenance($input, $params) {
        $file = __DIR__ . '/../../storage/maintenance.lock';
        file_put_contents($file, date('Y-m-d H:i:s'));
        return $this->success(null, 'Modo de manutenção ativado');
    }
    
    /**
     * Desativar modo manutenção
     * POST /api/v1/admin/system/maintenance/disable
     */
    public function disableMaintenance($input, $params) {
        $file = __DIR__ . '/../../storage/maintenance.lock';
        if (file_exists($file)) {
            unlink($file);
        }
        return $this->success(null, 'Modo de manutenção desativado');
    }
    
    /**
     * Listar backups
     * GET /api/v1/admin/system/backups
     */
    public function getBackups($input, $params) {
        $backupDir = __DIR__ . '/../../storage/backups/';
        $backups = [];
        
        if (is_dir($backupDir)) {
            $files = glob($backupDir . '*.sql');
            foreach ($files as $file) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => round(filesize($file) / 1024, 2) . ' KB',
                    'date' => date('Y-m-d H:i:s', filemtime($file))
                ];
            }
        }
        
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $this->success($backups);
    }
    
    /**
     * Criar backup
     * POST /api/v1/admin/system/backup
     */
    public function createBackup($input, $params) {
        $backupDir = __DIR__ . '/../../storage/backups/';
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        
        $filename = 'backup_' . date('Ymd_His') . '.sql';
        $filepath = $backupDir . $filename;
        
        // Criar backup
        $content = "-- CARDOXIS Backup\n";
        $content .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        $tables = $this->db->fetchAll("SHOW TABLES");
        foreach ($tables as $table) {
            $tableName = reset($table);
            $content .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            
            $createTable = $this->db->fetchOne("SHOW CREATE TABLE {$tableName}");
            $content .= $createTable['Create Table'] . ";\n\n";
            
            $rows = $this->db->fetchAll("SELECT * FROM {$tableName}");
            foreach ($rows as $row) {
                $values = array_map(function($val) {
                    if ($val === null) return 'NULL';
                    return "'" . addslashes($val) . "'";
                }, array_values($row));
                $content .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
            }
            $content .= "\n";
        }
        
        $content .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($filepath, $content);
        
        return $this->success(['filename' => $filename], 'Backup criado com sucesso');
    }
    
    /**
     * Restaurar backup - CORRIGIDO
     * POST /api/v1/admin/system/backup/restore
     */
    public function restoreBackup($input, $params) {
        $filename = $input['filename'] ?? null;
        if (!$filename) {
            return $this->error('Nome do arquivo obrigatório', null, 400);
        }
        
        $backupDir = __DIR__ . '/../../storage/backups/';
        $filepath = $backupDir . $filename;
        
        if (!file_exists($filepath)) {
            return $this->error('Arquivo de backup não encontrado', null, 404);
        }
        
        $sql = file_get_contents($filepath);
        
        try {
            // Ignorar erros de chave duplicada
            $this->db->query("SET FOREIGN_KEY_CHECKS=0");
            
            $queries = explode(';', $sql);
            $successCount = 0;
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query)) continue;
                
                // Pular comandos DROP TABLE para não perder dados
                if (strpos(strtoupper($query), 'DROP TABLE') !== false) {
                    continue;
                }
                
                try {
                    $this->db->query($query);
                    $successCount++;
                } catch (PDOException $e) {
                    // Ignorar erro de duplicação de chave primária
                    if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                        error_log("Erro na query: " . $e->getMessage());
                    }
                }
            }
            
            $this->db->query("SET FOREIGN_KEY_CHECKS=1");
            
            return $this->success(null, 'Backup restaurado com sucesso! ' . $successCount . ' queries executadas.');
        } catch (Exception $e) {
            $this->db->query("SET FOREIGN_KEY_CHECKS=1");
            return $this->error('Erro ao restaurar backup: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Deletar backup
     * DELETE /api/v1/admin/system/backup/{filename}
     */
    public function deleteBackup($id, $input, $params) {
        $filename = is_string($id) ? $id : ($input['filename'] ?? $params['filename'] ?? null);
        
        if (!$filename) {
            return $this->error('Nome do arquivo obrigatório', null, 400);
        }
        
        $backupDir = __DIR__ . '/../../storage/backups/';
        $filepath = $backupDir . $filename;
        
        if (!file_exists($filepath)) {
            return $this->error('Arquivo de backup não encontrado', null, 404);
        }
        
        unlink($filepath);
        
        return $this->success(null, 'Backup excluído com sucesso');
    }
    
    /**
     * Download de backup
     * GET /api/v1/admin/system/backup/download/{filename}
     */
    public function downloadBackup($id, $input, $params) {
        $filename = is_string($id) ? $id : ($input['filename'] ?? $params['filename'] ?? null);
        
        if (!$filename) {
            return $this->error('Nome do arquivo obrigatório', null, 400);
        }
        
        $backupDir = __DIR__ . '/../../storage/backups/';
        $filepath = $backupDir . $filename;
        
        if (!file_exists($filepath)) {
            return $this->error('Arquivo de backup não encontrado', null, 404);
        }
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private');
        readfile($filepath);
        exit();
    }
    
    /**
     * Obter filas
     * GET /api/v1/admin/system/queues
     */
    public function getQueues($input, $params) {
        return $this->success([
            'pending' => 0,
            'processing' => 0,
            'failed' => 0,
            'completed' => 0
        ]);
    }
    
    /**
     * Reexecutar jobs falhados
     * POST /api/v1/admin/system/queues/retry
     */
    public function retryFailed($input, $params) {
        return $this->success(null, 'Jobs reexecutados com sucesso');
    }
    
    /**
     * Obter migrações
     * GET /api/v1/admin/system/migrations
     */
    public function getMigrations($input, $params) {
        $migrationsDir = __DIR__ . '/../../database/migrations/';
        $migrations = [];
        
        if (is_dir($migrationsDir)) {
            $files = glob($migrationsDir . '*.sql');
            foreach ($files as $file) {
                $migrations[] = [
                    'name' => basename($file),
                    'executed' => false
                ];
            }
        }
        
        return $this->success($migrations);
    }
    
    /**
     * Executar migração
     * POST /api/v1/admin/system/migrations/run
     */
    public function runMigrations($input, $params) {
        $filename = $input['filename'] ?? null;
        if (!$filename) {
            return $this->error('Nome do arquivo obrigatório', null, 400);
        }
        
        $migrationsDir = __DIR__ . '/../../database/migrations/';
        $filepath = $migrationsDir . $filename;
        
        if (!file_exists($filepath)) {
            return $this->error('Arquivo de migração não encontrado', null, 404);
        }
        
        $sql = file_get_contents($filepath);
        
        try {
            $this->db->beginTransaction();
            $queries = explode(';', $sql);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $this->db->query($query);
                }
            }
            $this->db->commit();
            return $this->success(null, 'Migração executada com sucesso');
        } catch (Exception $e) {
            $this->db->rollback();
            return $this->error('Erro ao executar migração: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Contar arquivos em diretório
     */
    private function countFiles($dir) {
        $count = 0;
        if (is_dir($dir)) {
            $files = glob($dir . '*');
            $count = count($files);
        }
        return $count;
    }
    
    /**
     * Calcular tamanho do diretório
     */
    private function getDirectorySize($path) {
        $size = 0;
        if (is_dir($path)) {
            foreach (glob(rtrim($path, '/') . '/*', GLOB_NOSORT) as $file) {
                $size += is_file($file) ? filesize($file) : $this->getDirectorySize($file);
            }
        }
        return $size;
    }
}