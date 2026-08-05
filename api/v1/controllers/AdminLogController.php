<?php
/**
 * CARDOXIS - Admin Log Controller (PRODUÇÃO FINAL - CORRIGIDO)
 * Version: 17.0.0 - Enterprise Ready
 * 
 * @package CARDOXIS
 * @author CARDOXIS Team
 * @license Proprietary
 * @copyright 2025 CARDOXIS
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';

class AdminLogController extends BaseController {
    private $db;
    private $logFile;
    private $errorLogFile;
    private $securityLogFile;
    private $apiLogFile;
    
    // Diretórios de log
    private $logDirs = [
        'logs' => __DIR__ . '/../logs/',
        'php' => 'C:/xampp/php/logs/',
        'apache' => 'C:/xampp/apache/logs/'
    ];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logFile = __DIR__ . '/../logs/activity.log';
        $this->errorLogFile = __DIR__ . '/../logs/errors.log';
        $this->securityLogFile = __DIR__ . '/../logs/security.log';
        $this->apiLogFile = __DIR__ . '/../logs/api_errors.log';
        
        // Criar diretórios de log se não existirem
        foreach ($this->logDirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
        }
    }
    
    /**
     * Logs de auditoria - COM FILTROS
     * GET /api/v1/admin/logs/audit
     */
    public function getAuditLogs($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
            $limit = isset($params['limit']) ? min(100, max(1, (int)$params['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            $where = "1=1";
            $paramsDb = [];
            
            // Filtros
            if (isset($params['user_id']) && !empty($params['user_id'])) {
                $where .= " AND user_id = :user_id";
                $paramsDb[':user_id'] = (int)$params['user_id'];
            }
            
            if (isset($params['action']) && !empty($params['action'])) {
                $where .= " AND action = :action";
                $paramsDb[':action'] = $params['action'];
            }
            
            if (isset($params['entity_type']) && !empty($params['entity_type'])) {
                $where .= " AND entity_type = :entity_type";
                $paramsDb[':entity_type'] = $params['entity_type'];
            }
            
            if (isset($params['entity_id']) && !empty($params['entity_id'])) {
                $where .= " AND entity_id = :entity_id";
                $paramsDb[':entity_id'] = (int)$params['entity_id'];
            }
            
            if (isset($params['search']) && !empty($params['search'])) {
                $where .= " AND description LIKE :search";
                $paramsDb[':search'] = "%{$params['search']}%";
            }
            
            if (isset($params['date_from']) && !empty($params['date_from'])) {
                $where .= " AND created_at >= :date_from";
                $paramsDb[':date_from'] = $params['date_from'] . ' 00:00:00';
            }
            
            if (isset($params['date_to']) && !empty($params['date_to'])) {
                $where .= " AND created_at <= :date_to";
                $paramsDb[':date_to'] = $params['date_to'] . ' 23:59:59';
            }
            
            // Buscar logs
            $logs = $this->db->fetchAll("
                SELECT al.*, u.name as user_name, c.name as company_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN companies c ON al.company_id = c.id
                WHERE {$where}
                ORDER BY al.created_at DESC 
                LIMIT :limit OFFSET :offset
            ", array_merge($paramsDb, [':limit' => $limit, ':offset' => $offset]));
            
            // Total
            $totalResult = $this->db->fetchOne("SELECT COUNT(*) as total FROM audit_logs WHERE {$where}", $paramsDb);
            $total = $totalResult ? (int)$totalResult['total'] : 0;
            
            // Estatísticas rápidas
            $stats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN action = 'create' THEN 1 ELSE 0 END) as creates,
                    SUM(CASE WHEN action = 'update' THEN 1 ELSE 0 END) as updates,
                    SUM(CASE WHEN action = 'delete' THEN 1 ELSE 0 END) as deletes
                FROM audit_logs
            ");
            
            // Registrar atividade
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_view_audit_logs',
                'Visualizou logs de auditoria com filtros'
            );
            
            return $this->success([
                'data' => $logs ?: [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ],
                'stats' => [
                    'total' => (int)($stats['total'] ?? 0),
                    'creates' => (int)($stats['creates'] ?? 0),
                    'updates' => (int)($stats['updates'] ?? 0),
                    'deletes' => (int)($stats['deletes'] ?? 0)
                ]
            ]);
            
        } catch (Exception $e) {
            $this->logError('getAuditLogs', $e->getMessage());
            return $this->success([
                'data' => [],
                'pagination' => ['current_page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 1],
                'stats' => ['total' => 0, 'creates' => 0, 'updates' => 0, 'deletes' => 0]
            ]);
        }
    }
    
    /**
     * Logs de login - COM FILTROS
     * GET /api/v1/admin/logs/login
     */
    public function getLoginLogs($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
            $limit = isset($params['limit']) ? min(100, max(1, (int)$params['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            $where = "1=1";
            $paramsDb = [];
            
            if (isset($params['email']) && !empty($params['email'])) {
                $where .= " AND email LIKE :email";
                $paramsDb[':email'] = "%{$params['email']}%";
            }
            
            if (isset($params['status']) && !empty($params['status'])) {
                $where .= " AND status = :status";
                $paramsDb[':status'] = $params['status'];
            }
            
            if (isset($params['user_id']) && !empty($params['user_id'])) {
                $where .= " AND user_id = :user_id";
                $paramsDb[':user_id'] = (int)$params['user_id'];
            }
            
            if (isset($params['date_from']) && !empty($params['date_from'])) {
                $where .= " AND created_at >= :date_from";
                $paramsDb[':date_from'] = $params['date_from'] . ' 00:00:00';
            }
            
            if (isset($params['date_to']) && !empty($params['date_to'])) {
                $where .= " AND created_at <= :date_to";
                $paramsDb[':date_to'] = $params['date_to'] . ' 23:59:59';
            }
            
            $logs = $this->db->fetchAll("
                SELECT id, user_id, email, user_name, status, ip_address, created_at 
                FROM login_logs 
                WHERE {$where}
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset
            ", array_merge($paramsDb, [':limit' => $limit, ':offset' => $offset]));
            
            $totalResult = $this->db->fetchOne("SELECT COUNT(*) as total FROM login_logs WHERE {$where}", $paramsDb);
            $total = $totalResult ? (int)$totalResult['total'] : 0;
            
            // Estatísticas de login
            $stats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successes,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failures,
                    SUM(CASE WHEN status = 'logout' THEN 1 ELSE 0 END) as logouts
                FROM login_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_view_login_logs',
                'Visualizou logs de login'
            );
            
            return $this->success([
                'data' => $logs ?: [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ],
                'stats' => [
                    'total' => (int)($stats['total'] ?? 0),
                    'successes' => (int)($stats['successes'] ?? 0),
                    'failures' => (int)($stats['failures'] ?? 0),
                    'logouts' => (int)($stats['logouts'] ?? 0)
                ]
            ]);
            
        } catch (Exception $e) {
            $this->logError('getLoginLogs', $e->getMessage());
            return $this->success([
                'data' => [],
                'pagination' => ['current_page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 1],
                'stats' => ['total' => 0, 'successes' => 0, 'failures' => 0, 'logouts' => 0]
            ]);
        }
    }
    
    /**
     * Logs de atividade - COM FILTROS
     * GET /api/v1/admin/logs/activity
     */
    public function getActivityLogs($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
            $limit = isset($params['limit']) ? min(100, max(1, (int)$params['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            $where = "1=1";
            $paramsDb = [];
            
            if (isset($params['user_id']) && !empty($params['user_id'])) {
                $where .= " AND user_id = :user_id";
                $paramsDb[':user_id'] = (int)$params['user_id'];
            }
            
            if (isset($params['action']) && !empty($params['action'])) {
                $where .= " AND action = :action";
                $paramsDb[':action'] = $params['action'];
            }
            
            if (isset($params['search']) && !empty($params['search'])) {
                $where .= " AND description LIKE :search";
                $paramsDb[':search'] = "%{$params['search']}%";
            }
            
            if (isset($params['date_from']) && !empty($params['date_from'])) {
                $where .= " AND created_at >= :date_from";
                $paramsDb[':date_from'] = $params['date_from'] . ' 00:00:00';
            }
            
            if (isset($params['date_to']) && !empty($params['date_to'])) {
                $where .= " AND created_at <= :date_to";
                $paramsDb[':date_to'] = $params['date_to'] . ' 23:59:59';
            }
            
            $logs = $this->db->fetchAll("
                SELECT al.*, u.name as user_name
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE {$where}
                ORDER BY al.created_at DESC 
                LIMIT :limit OFFSET :offset
            ", array_merge($paramsDb, [':limit' => $limit, ':offset' => $offset]));
            
            $totalResult = $this->db->fetchOne("SELECT COUNT(*) as total FROM activity_logs WHERE {$where}", $paramsDb);
            $total = $totalResult ? (int)$totalResult['total'] : 0;
            
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_view_activity_logs',
                'Visualizou logs de atividade'
            );
            
            return $this->success([
                'data' => $logs ?: [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            $this->logError('getActivityLogs', $e->getMessage());
            return $this->success([
                'data' => [],
                'pagination' => ['current_page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 1]
            ]);
        }
    }
    
    /**
     * Logs de erro - SISTEMA COMPLETO
     * GET /api/v1/admin/logs/errors
     */
    public function getErrorLogs($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $type = isset($params['type']) ? $params['type'] : 'all';
            $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
            
            // Definir arquivos de log
            $logFiles = [
                'api_errors' => $this->apiLogFile,
                'errors' => $this->errorLogFile,
                'security' => $this->securityLogFile,
                'php_errors' => $this->logDirs['php'] . 'php_error_log',
                'apache_errors' => $this->logDirs['apache'] . 'error.log'
            ];
            
            $logs = [];
            $fileInfo = [];
            
            if ($type === 'all' || $type === 'api_errors') {
                $logs['api_errors'] = $this->readLogFile($logFiles['api_errors'], $limit);
                $fileInfo['api_errors'] = $this->getFileInfo($logFiles['api_errors']);
            }
            
            if ($type === 'all' || $type === 'errors') {
                $logs['errors'] = $this->readLogFile($logFiles['errors'], $limit);
                $fileInfo['errors'] = $this->getFileInfo($logFiles['errors']);
            }
            
            if ($type === 'all' || $type === 'security') {
                $logs['security'] = $this->readLogFile($logFiles['security'], $limit);
                $fileInfo['security'] = $this->getFileInfo($logFiles['security']);
            }
            
            if ($type === 'all' || $type === 'php') {
                $logs['php_errors'] = $this->readLogFile($logFiles['php_errors'], $limit);
                $fileInfo['php_errors'] = $this->getFileInfo($logFiles['php_errors']);
            }
            
            if ($type === 'all' || $type === 'apache') {
                $logs['apache_errors'] = $this->readLogFile($logFiles['apache_errors'], $limit);
                $fileInfo['apache_errors'] = $this->getFileInfo($logFiles['apache_errors']);
            }
            
            // Registrar atividade
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_view_error_logs',
                'Visualizou logs de erro - Tipo: ' . $type
            );
            
            return $this->success([
                'logs' => $logs,
                'files' => $fileInfo,
                'type' => $type,
                'limit' => $limit,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            $this->logError('getErrorLogs', $e->getMessage());
            return $this->success([
                'logs' => [],
                'files' => [],
                'type' => $type,
                'limit' => $limit,
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Logs da API
     * GET /api/v1/admin/logs/api
     */
    public function getApiLogs($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
            $logs = $this->readLogFile($this->apiLogFile, $limit);
            $fileInfo = $this->getFileInfo($this->apiLogFile);
            
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_view_api_logs',
                'Visualizou logs da API'
            );
            
            return $this->success([
                'logs' => $logs,
                'file' => $fileInfo,
                'limit' => $limit,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            $this->logError('getApiLogs', $e->getMessage());
            return $this->success([
                'logs' => [],
                'file' => ['exists' => false, 'size' => '0 KB'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Logs de segurança
     * GET /api/v1/admin/logs/security
     */
    public function getSecurityLogs($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
            $logs = $this->readLogFile($this->securityLogFile, $limit);
            $fileInfo = $this->getFileInfo($this->securityLogFile);
            
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_view_security_logs',
                'Visualizou logs de segurança'
            );
            
            return $this->success([
                'logs' => $logs,
                'file' => $fileInfo,
                'limit' => $limit,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            $this->logError('getSecurityLogs', $e->getMessage());
            return $this->success([
                'logs' => [],
                'file' => ['exists' => false, 'size' => '0 KB']
            ]);
        }
    }
    
    /**
     * Exportar logs - CORRIGIDO (Suporte a todos os tipos)
     * GET /api/v1/admin/logs/export
     */
    public function export($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $type = isset($params['type']) ? $params['type'] : 'audit';
            $format = isset($params['format']) ? $params['format'] : 'csv';
            
            $this->logError('export', "Tipo: $type, Formato: $format");
            
            // Buscar dados conforme tipo
            $data = [];
            $filename = '';
            $headers = [];
            
            switch ($type) {
                case 'audit':
                    $data = $this->db->fetchAll("
                        SELECT al.*, u.name as user_name, c.name as company_name
                        FROM audit_logs al
                        LEFT JOIN users u ON al.user_id = u.id
                        LEFT JOIN companies c ON al.company_id = c.id
                        ORDER BY al.created_at DESC
                        LIMIT 1000
                    ");
                    $filename = "logs_audit_" . date('Ymd_His');
                    $headers = ['ID', 'User ID', 'User Name', 'Company', 'Action', 'Entity Type', 'Entity ID', 'Description', 'IP', 'Created At'];
                    break;
                    
                case 'login':
                    $data = $this->db->fetchAll("
                        SELECT * FROM login_logs 
                        ORDER BY created_at DESC 
                        LIMIT 1000
                    ");
                    $filename = "logs_login_" . date('Ymd_His');
                    $headers = ['ID', 'User ID', 'Email', 'User Name', 'Status', 'IP', 'Created At'];
                    break;
                    
                case 'activity':
                    $data = $this->db->fetchAll("
                        SELECT al.*, u.name as user_name
                        FROM activity_logs al
                        LEFT JOIN users u ON al.user_id = u.id
                        ORDER BY al.created_at DESC
                        LIMIT 1000
                    ");
                    $filename = "logs_activity_" . date('Ymd_His');
                    $headers = ['ID', 'User ID', 'User Name', 'Action', 'Description', 'IP', 'Created At'];
                    break;
                    
                case 'api':
                    // Ler logs da API
                    $apiLogFile = __DIR__ . '/../logs/api_errors.log';
                    $logs = $this->readLogFile($apiLogFile, 500);
                    $data = [];
                    foreach ($logs as $log) {
                        $data[] = [
                            'timestamp' => date('Y-m-d H:i:s'),
                            'message' => $log
                        ];
                    }
                    $filename = "logs_api_" . date('Ymd_His');
                    $headers = ['Timestamp', 'Mensagem'];
                    break;
                    
                case 'errors':
                    // Ler logs de erros
                    $errorLogFile = __DIR__ . '/../logs/errors.log';
                    $logs = $this->readLogFile($errorLogFile, 500);
                    $data = [];
                    foreach ($logs as $log) {
                        $data[] = [
                            'timestamp' => date('Y-m-d H:i:s'),
                            'message' => $log
                        ];
                    }
                    $filename = "logs_errors_" . date('Ymd_His');
                    $headers = ['Timestamp', 'Mensagem'];
                    break;
                    
                case 'security':
                    // Ler logs de segurança
                    $securityLogFile = __DIR__ . '/../logs/security.log';
                    $logs = $this->readLogFile($securityLogFile, 500);
                    $data = [];
                    foreach ($logs as $log) {
                        $data[] = [
                            'timestamp' => date('Y-m-d H:i:s'),
                            'message' => $log
                        ];
                    }
                    $filename = "logs_security_" . date('Ymd_His');
                    $headers = ['Timestamp', 'Mensagem'];
                    break;
                    
                default:
                    return $this->error('Tipo de log inválido: ' . $type, null, 400);
            }
            
            if (empty($data)) {
                return $this->error('Nenhum dado para exportar', null, 404);
            }
            
            // ============================================
            // CAMINHO ABSOLUTO
            // ============================================
            $projectRoot = realpath(dirname(__DIR__, 3));
            $exportDir = $projectRoot . '/storage/exports/';
            
            $this->logError('export', "Project Root: $projectRoot");
            $this->logError('export', "Export Dir: $exportDir");
            
            // Criar diretório se não existir
            if (!is_dir($exportDir)) {
                if (!mkdir($exportDir, 0777, true)) {
                    $this->logError('export', "Falha ao criar diretório: $exportDir");
                    return $this->error('Não foi possível criar o diretório de exportação', null, 500);
                }
                $this->logError('export', "Diretório criado: $exportDir");
            }
            
            // Verificar permissões
            if (!is_writable($exportDir)) {
                $this->logError('export', "Diretório não é gravável: $exportDir");
                return $this->error('Diretório de exportação não tem permissão de escrita', null, 500);
            }
            
            // Nome completo do arquivo
            $fullFilename = $filename . '.csv';
            $filepath = $exportDir . $fullFilename;
            
            $this->logError('export', "Filepath: $filepath");
            
            // Criar arquivo CSV
            $file = @fopen($filepath, 'w');
            if (!$file) {
                $this->logError('export', "Erro ao abrir arquivo: $filepath");
                return $this->error('Não foi possível criar o arquivo de exportação', null, 500);
            }
            
            // Escrever BOM para UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Escrever cabeçalhos
            fputcsv($file, $headers, ';');
            
            // Escrever dados
            foreach ($data as $row) {
                $rowData = [];
                foreach ($headers as $header) {
                    $key = strtolower(str_replace(' ', '_', $header));
                    $rowData[] = $row[$key] ?? '';
                }
                fputcsv($file, $rowData, ';');
            }
            fclose($file);
            
            $this->logError('export', "Arquivo criado com sucesso: $filepath");
            $this->logError('export', "Tamanho: " . filesize($filepath) . " bytes");
            
            // Registrar atividade
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_export_logs',
                'Exportou logs: ' . $type . ' - ' . count($data) . ' registos'
            );
            
            // URL para download
            $downloadUrl = '/cardoxis/download.php?file=' . urlencode($fullFilename);
            
            return $this->success([
                'filename' => $fullFilename,
                'total' => count($data),
                'format' => $format,
                'type' => $type,
                'download_url' => $downloadUrl
            ]);
            
        } catch (Exception $e) {
            $this->logError('export', "ERRO: " . $e->getMessage());
            $this->logError('export', "TRACE: " . $e->getTraceAsString());
            return $this->error('Erro ao exportar logs: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Limpar logs
     * DELETE /api/v1/admin/logs/clear
     */
    public function clearLogs($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $type = isset($params['type']) ? $params['type'] : 'all';
            $olderThan = isset($params['older_than']) ? (int)$params['older_than'] : 30;
            
            $deleted = 0;
            $results = [];
            
            switch ($type) {
                case 'audit':
                    $deleted = $this->db->delete(
                        'audit_logs', 
                        'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', 
                        [':days' => $olderThan]
                    );
                    $results['audit'] = $deleted;
                    break;
                    
                case 'login':
                    $deleted = $this->db->delete(
                        'login_logs', 
                        'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', 
                        [':days' => $olderThan]
                    );
                    $results['login'] = $deleted;
                    break;
                    
                case 'activity':
                    $deleted = $this->db->delete(
                        'activity_logs', 
                        'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', 
                        [':days' => $olderThan]
                    );
                    $results['activity'] = $deleted;
                    break;
                    
                case 'files':
                    // Limpar arquivos de log
                    $logFiles = [
                        $this->apiLogFile,
                        $this->errorLogFile,
                        $this->securityLogFile,
                        $this->logFile
                    ];
                    foreach ($logFiles as $file) {
                        if (file_exists($file) && filesize($file) > 0) {
                            file_put_contents($file, '');
                            $deleted++;
                        }
                    }
                    $results['files'] = $deleted;
                    break;
                    
                default:
                    // Todos
                    $deleted += $this->db->delete(
                        'audit_logs', 
                        'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', 
                        [':days' => $olderThan]
                    );
                    $deleted += $this->db->delete(
                        'login_logs', 
                        'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', 
                        [':days' => $olderThan]
                    );
                    $deleted += $this->db->delete(
                        'activity_logs', 
                        'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', 
                        [':days' => $olderThan]
                    );
                    $results['audit'] = $deleted;
                    break;
            }
            
            // Registrar atividade
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_clear_logs',
                'Limpeza de logs: ' . $type . ' (mais de ' . $olderThan . ' dias) - ' . $deleted . ' registos removidos'
            );
            
            // Registrar em security log
            $this->logSecurity(
                $authUser['user_id'],
                'admin_clear_logs',
                'Limpeza de logs realizada por ' . ($authUser['email'] ?? 'unknown') . ': ' . $type . ' (mais de ' . $olderThan . ' dias)'
            );
            
            return $this->success([
                'type' => $type,
                'older_than' => $olderThan,
                'deleted' => $deleted,
                'results' => $results
            ], $deleted . ' registos de logs removidos com sucesso');
            
        } catch (Exception $e) {
            $this->logError('clearLogs', $e->getMessage());
            return $this->error('Erro ao limpar logs: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Estatísticas de logs
     * GET /api/v1/admin/logs/stats
     */
    public function getStats($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            // Estatísticas das tabelas
            $auditStats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as last_24h,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as last_7d
                FROM audit_logs
            ");
            
            $loginStats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM login_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            $activityStats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as last_24h
                FROM activity_logs
            ");
            
            // Tamanho dos arquivos de log
            $fileSizes = [];
            $logFiles = [
                'api_errors' => $this->apiLogFile,
                'errors' => $this->errorLogFile,
                'security' => $this->securityLogFile,
                'activity' => $this->logFile
            ];
            
            foreach ($logFiles as $name => $path) {
                $fileSizes[$name] = $this->getFileInfo($path);
            }
            
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_view_log_stats',
                'Visualizou estatísticas de logs'
            );
            
            return $this->success([
                'database' => [
                    'audit' => [
                        'total' => (int)($auditStats['total'] ?? 0),
                        'last_24h' => (int)($auditStats['last_24h'] ?? 0),
                        'last_7d' => (int)($auditStats['last_7d'] ?? 0)
                    ],
                    'login' => [
                        'total' => (int)($loginStats['total'] ?? 0),
                        'success' => (int)($loginStats['success'] ?? 0),
                        'failed' => (int)($loginStats['failed'] ?? 0)
                    ],
                    'activity' => [
                        'total' => (int)($activityStats['total'] ?? 0),
                        'last_24h' => (int)($activityStats['last_24h'] ?? 0)
                    ]
                ],
                'files' => $fileSizes,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            $this->logError('getStats', $e->getMessage());
            return $this->error('Erro ao carregar estatísticas', null, 500);
        }
    }
    
    // ============================================
    // MÉTODOS PRIVADOS
    // ============================================
    
    /**
     * Ler arquivo de log
     */
    private function readLogFile($filepath, $limit = 100) {
        $logs = [];
        if (file_exists($filepath) && filesize($filepath) > 0) {
            $content = file_get_contents($filepath);
            if ($content !== false) {
                $lines = explode("\n", $content);
                $lines = array_filter($lines);
                $logs = array_slice(array_reverse($lines), 0, $limit);
            }
        }
        return array_values($logs);
    }
    
    /**
     * Obter informações do arquivo
     */
    private function getFileInfo($filepath) {
        if (file_exists($filepath)) {
            $size = filesize($filepath);
            return [
                'exists' => true,
                'size' => $size > 0 ? round($size / 1024, 2) . ' KB' : '0 KB',
                'size_bytes' => $size,
                'modified' => date('Y-m-d H:i:s', filemtime($filepath)),
                'path' => $filepath
            ];
        }
        return [
            'exists' => false,
            'size' => '0 KB',
            'size_bytes' => 0,
            'modified' => null,
            'path' => $filepath
        ];
    }
    
    /**
     * Registrar atividade
     */
    private function logActivity($userId, $companyId, $action, $description) {
        try {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            
            $this->db->insert('activity_logs', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $ipAddress,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Não falhar se não conseguir logar
        }
    }
    
    /**
     * Registrar erro
     */
    private function logError($method, $message) {
        $timestamp = date('Y-m-d H:i:s');
        @file_put_contents(
            $this->errorLogFile, 
            "[$timestamp] AdminLogController::{$method} - {$message}" . PHP_EOL,
            FILE_APPEND
        );
    }
    
    /**
     * Registrar evento de segurança
     */
    private function logSecurity($userId, $event, $description) {
        try {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            
            $this->db->insert('security_logs', [
                'user_id' => $userId,
                'event' => $event,
                'description' => $description,
                'ip_address' => $ipAddress,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            @file_put_contents(
                $this->securityLogFile,
                "[" . date('Y-m-d H:i:s') . "] User: $userId | Event: $event | $description" . PHP_EOL,
                FILE_APPEND
            );
        } catch (Exception $e) {
            // Não falhar se não conseguir logar
        }
    }
}