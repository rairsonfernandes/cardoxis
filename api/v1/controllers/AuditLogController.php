<?php
/**
 * CARDOXIS - Admin Log Controller RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';

class AdminLogController extends BaseController {
    private $db;
    private $logFile;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logFile = __DIR__ . '/../logs/activity.log';
        
        if (!is_dir(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0777, true);
        }
    }
    
    /**
     * Logs de auditoria
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
            
            if (isset($params['user_id']) && !empty($params['user_id'])) {
                $where .= " AND user_id = :user_id";
                $paramsDb[':user_id'] = $params['user_id'];
            }
            
            if (isset($params['action']) && !empty($params['action'])) {
                $where .= " AND action = :action";
                $paramsDb[':action'] = $params['action'];
            }
            
            if (isset($params['entity_type']) && !empty($params['entity_type'])) {
                $where .= " AND entity_type = :entity_type";
                $paramsDb[':entity_type'] = $params['entity_type'];
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
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE {$where}
                ORDER BY al.created_at DESC 
                LIMIT :limit OFFSET :offset
            ", array_merge($paramsDb, [':limit' => $limit, ':offset' => $offset]));
            
            $totalResult = $this->db->fetchOne("SELECT COUNT(*) as total FROM audit_logs WHERE {$where}", $paramsDb);
            $total = $totalResult ? (int)$totalResult['total'] : 0;
            
            // Registrar atividade
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_view_audit_logs',
                'Visualizou logs de auditoria'
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
            error_log("[AdminLog] getAuditLogs error: " . $e->getMessage());
            return $this->success(['data' => [], 'pagination' => ['current_page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 1]]);
        }
    }
    
    /**
     * Logs de login
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
            
            // Registrar atividade
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
                ]
            ]);
        } catch (Exception $e) {
            error_log("[AdminLog] getLoginLogs error: " . $e->getMessage());
            return $this->success(['data' => [], 'pagination' => ['current_page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 1]]);
        }
    }
    
    /**
     * Logs de atividade
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
                $paramsDb[':user_id'] = $params['user_id'];
            }
            
            if (isset($params['action']) && !empty($params['action'])) {
                $where .= " AND action = :action";
                $paramsDb[':action'] = $params['action'];
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
            
            // Registrar atividade
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
            error_log("[AdminLog] getActivityLogs error: " . $e->getMessage());
            return $this->success(['data' => [], 'pagination' => ['current_page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 1]]);
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
            
            $logFile = __DIR__ . '/../logs/api_errors.log';
            $logs = [];
            
            if (file_exists($logFile) && filesize($logFile) > 0) {
                $content = file_get_contents($logFile);
                $lines = explode("\n", $content);
                $logs = array_slice(array_reverse($lines), 0, 100);
                $logs = array_filter($logs);
            }
            
            // Registrar atividade
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_view_api_logs',
                'Visualizou logs da API'
            );
            
            return $this->success([
                'logs' => array_values($logs),
                'file' => $logFile,
                'size' => file_exists($logFile) ? round(filesize($logFile) / 1024, 2) . ' KB' : '0 KB'
            ]);
        } catch (Exception $e) {
            error_log("[AdminLog] getApiLogs error: " . $e->getMessage());
            return $this->success(['logs' => [], 'file' => '', 'size' => '0 KB']);
        }
    }
    
    /**
     * Logs de erro
     * GET /api/v1/admin/logs/errors
     */
    public function getErrorLogs($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $logFile = __DIR__ . '/../logs/errors.log';
            $logs = [];
            
            if (file_exists($logFile) && filesize($logFile) > 0) {
                $content = file_get_contents($logFile);
                $lines = explode("\n", $content);
                $logs = array_slice(array_reverse($lines), 0, 100);
                $logs = array_filter($logs);
            }
            
            // Registrar atividade
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_view_error_logs',
                'Visualizou logs de erro'
            );
            
            return $this->success([
                'logs' => array_values($logs),
                'file' => $logFile,
                'size' => file_exists($logFile) ? round(filesize($logFile) / 1024, 2) . ' KB' : '0 KB'
            ]);
        } catch (Exception $e) {
            error_log("[AdminLog] getErrorLogs error: " . $e->getMessage());
            return $this->success(['logs' => [], 'file' => '', 'size' => '0 KB']);
        }
    }
    
    /**
     * Exportar logs
     * GET /api/v1/admin/logs/export
     */
    public function export($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $type = isset($params['type']) ? $params['type'] : 'audit';
            
            switch ($type) {
                case 'audit':
                    $data = $this->db->fetchAll("
                        SELECT al.*, u.name as user_name 
                        FROM audit_logs al
                        LEFT JOIN users u ON al.user_id = u.id
                        ORDER BY al.created_at DESC
                    ");
                    $filename = "logs_audit_" . date('Ymd_His') . ".csv";
                    break;
                case 'login':
                    $data = $this->db->fetchAll("SELECT * FROM login_logs ORDER BY created_at DESC");
                    $filename = "logs_login_" . date('Ymd_His') . ".csv";
                    break;
                case 'activity':
                    $data = $this->db->fetchAll("
                        SELECT al.*, u.name as user_name 
                        FROM activity_logs al
                        LEFT JOIN users u ON al.user_id = u.id
                        ORDER BY al.created_at DESC
                    ");
                    $filename = "logs_activity_" . date('Ymd_His') . ".csv";
                    break;
                default:
                    $data = [];
                    $filename = "logs_export_" . date('Ymd_His') . ".csv";
            }
            
            if (empty($data)) {
                return $this->error('Nenhum dado para exportar', null, 404);
            }
            
            $projectRoot = dirname(__DIR__, 2);
            $exportDir = $projectRoot . '/storage/exports/';
            
            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0777, true);
            }
            
            $filepath = $exportDir . $filename;
            
            $file = fopen($filepath, 'w');
            if (!$file) {
                return $this->error('Não foi possível criar o arquivo de exportação', null, 500);
            }
            
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
            
            // Registrar atividade
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_export_logs',
                'Exportou logs: ' . $type
            );
            
            return $this->success([
                'filename' => $filename,
                'total' => count($data),
                'download_url' => '/cardoxis/download.php?file=' . urlencode($filename)
            ]);
        } catch (Exception $e) {
            error_log("[AdminLog] export error: " . $e->getMessage());
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
            
            switch ($type) {
                case 'audit':
                    $deleted = $this->db->delete('audit_logs', 'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', [':days' => $olderThan]);
                    break;
                case 'login':
                    $deleted = $this->db->delete('login_logs', 'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', [':days' => $olderThan]);
                    break;
                case 'activity':
                    $deleted = $this->db->delete('activity_logs', 'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', [':days' => $olderThan]);
                    break;
                default:
                    $deleted += $this->db->delete('audit_logs', 'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', [':days' => $olderThan]);
                    $deleted += $this->db->delete('login_logs', 'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', [':days' => $olderThan]);
                    $deleted += $this->db->delete('activity_logs', 'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', [':days' => $olderThan]);
            }
            
            // Registrar atividade
            $this->logActivity(
                $authUser['user_id'],
                null,
                'admin_clear_logs',
                'Limpeza de logs: ' . $type . ' (mais de ' . $olderThan . ' dias) - ' . $deleted . ' registos removidos'
            );
            
            return $this->success([
                'type' => $type,
                'older_than' => $olderThan,
                'deleted' => $deleted
            ], $deleted . ' registos de logs removidos com sucesso');
            
        } catch (Exception $e) {
            error_log("[AdminLog] clearLogs error: " . $e->getMessage());
            return $this->error('Erro ao limpar logs: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Registrar atividade
     */
    private function logActivity($userId, $companyId, $action, $description) {
        try {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $this->db->insert('activity_logs', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $ipAddress,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $logMessage = "[ACTIVITY] User: $userId | Action: $action | $description";
            $this->writeLog($logMessage);
            
        } catch (Exception $e) {
            error_log("Erro ao registrar atividade: " . $e->getMessage());
        }
    }
    
    private function writeLog($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }
}