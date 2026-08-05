<?php
/**
 * CARDOXIS - Report Controller  RF 
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

class ReportController extends BaseController {
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
     * Obter estatísticas para relatórios
     * GET /api/v1/reports/stats
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
            
            $vehiclePerm = PermissionMiddleware::scope($user, 'vehicles', 'v');
            $maintenancePerm = PermissionMiddleware::scope($user, 'maintenances', 'm');
            $driverPerm = PermissionMiddleware::scope($user, 'drivers', 'd');
            $documentPerm = PermissionMiddleware::scope($user, 'documents', 'doc');
            
            // Estatísticas de veículos
            $totalVehicles = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM vehicles v WHERE {$vehiclePerm['where']}",
                $vehiclePerm['params']
            )['total'] ?? 0;
            
            $activeVehicles = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM vehicles v WHERE {$vehiclePerm['where']} AND v.status = 'active'",
                $vehiclePerm['params']
            )['total'] ?? 0;
            
            $maintenanceVehicles = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM vehicles v WHERE {$vehiclePerm['where']} AND v.status = 'maintenance'",
                $vehiclePerm['params']
            )['total'] ?? 0;
            
            $inactiveVehicles = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM vehicles v WHERE {$vehiclePerm['where']} AND v.status = 'inactive'",
                $vehiclePerm['params']
            )['total'] ?? 0;
            
            // Estatísticas de manutenções
            $totalMaintenances = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM maintenances m WHERE {$maintenancePerm['where']}",
                $maintenancePerm['params']
            )['total'] ?? 0;
            
            $maintenanceCost = $this->db->fetchOne(
                "SELECT SUM(m.cost) as total_cost FROM maintenances m WHERE {$maintenancePerm['where']} AND YEAR(m.created_at) = YEAR(NOW())",
                $maintenancePerm['params']
            )['total_cost'] ?? 0;
            
            $pendingMaintenances = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM maintenances m WHERE {$maintenancePerm['where']} AND m.status IN ('scheduled', 'in_progress')",
                $maintenancePerm['params']
            )['total'] ?? 0;
            
            // Estatísticas de motoristas
            $totalDrivers = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM drivers d WHERE {$driverPerm['where']}",
                $driverPerm['params']
            )['total'] ?? 0;
            
            $activeDrivers = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM drivers d WHERE {$driverPerm['where']} AND d.status = 'active'",
                $driverPerm['params']
            )['total'] ?? 0;
            
            // Estatísticas de documentos
            $totalDocuments = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM documents doc WHERE {$documentPerm['where']}",
                $documentPerm['params']
            )['total'] ?? 0;
            
            $expiringDocuments = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM documents doc WHERE {$documentPerm['where']} AND doc.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)",
                $documentPerm['params']
            )['total'] ?? 0;
            
            $expiredDocuments = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM documents doc WHERE {$documentPerm['where']} AND doc.expiry_date < CURDATE()",
                $documentPerm['params']
            )['total'] ?? 0;
            
            // Estatísticas de combustível (se tiver tabela)
            $totalFuelLiters = 0;
            $totalFuelCost = 0;
            $fuelTableExists = $this->db->fetchOne("SHOW TABLES LIKE 'fuel_entries'");
            if ($fuelTableExists) {
                $fuelPerm = PermissionMiddleware::scope($user, 'fuel_entries', 'f');
                $totalFuelLiters = $this->db->fetchOne(
                    "SELECT SUM(f.liters) as total FROM fuel_entries f WHERE {$fuelPerm['where']}",
                    $fuelPerm['params']
                )['total'] ?? 0;
                
                $totalFuelCost = $this->db->fetchOne(
                    "SELECT SUM(f.total_cost) as total FROM fuel_entries f WHERE {$fuelPerm['where']}",
                    $fuelPerm['params']
                )['total'] ?? 0;
            }
            
            // Registrar atividade
            $this->logActivity(
                $user['id'],
                $user['company_id'],
                'report_stats',
                'Visualizou estatísticas de relatórios'
            );
            
            return $this->success([
                'total_vehicles' => (int)$totalVehicles,
                'active_vehicles' => (int)$activeVehicles,
                'maintenance_vehicles' => (int)$maintenanceVehicles,
                'inactive_vehicles' => (int)$inactiveVehicles,
                'total_maintenances' => (int)$totalMaintenances,
                'maintenance_cost' => (float)$maintenanceCost,
                'pending_maintenances' => (int)$pendingMaintenances,
                'total_drivers' => (int)$totalDrivers,
                'active_drivers' => (int)$activeDrivers,
                'total_documents' => (int)$totalDocuments,
                'expiring_documents' => (int)$expiringDocuments,
                'expired_documents' => (int)$expiredDocuments,
                'total_fuel_liters' => (float)$totalFuelLiters,
                'total_fuel_cost' => (float)$totalFuelCost
            ]);
            
        } catch (Exception $e) {
            error_log("[REPORTS] stats error: " . $e->getMessage());
            return $this->error('Erro ao carregar estatísticas', null, 500);
        }
    }
    
    /**
     * Obter relatório de manutenções
     * GET /api/v1/reports/maintenance
     */
    public function getMaintenanceReport($input, $params) {
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
            
            $period = isset($params['period']) ? (int)$params['period'] : 30;
            $perm = PermissionMiddleware::scope($user, 'maintenances', 'm');
            
            // Manutenções por tipo
            $byType = $this->db->fetchAll("
                SELECT 
                    m.type,
                    COUNT(*) as count,
                    SUM(m.cost) as total_cost,
                    AVG(m.cost) as avg_cost
                FROM maintenances m
                WHERE {$perm['where']}
                    AND m.created_at >= DATE_SUB(NOW(), INTERVAL :period DAY)
                GROUP BY m.type
                ORDER BY total_cost DESC
            ", array_merge($perm['params'], [':period' => $period]));
            
            // Manutenções por mês
            $byMonth = $this->db->fetchAll("
                SELECT 
                    DATE_FORMAT(m.created_at, '%Y-%m') as month,
                    COUNT(*) as count,
                    SUM(m.cost) as total_cost
                FROM maintenances m
                WHERE {$perm['where']}
                    AND m.created_at >= DATE_SUB(NOW(), INTERVAL :period DAY)
                GROUP BY DATE_FORMAT(m.created_at, '%Y-%m')
                ORDER BY month ASC
            ", array_merge($perm['params'], [':period' => $period]));
            
            // Manutenções recentes
            $recent = $this->db->fetchAll("
                SELECT m.*, v.plate, v.brand, v.model
                FROM maintenances m
                JOIN vehicles v ON m.vehicle_id = v.id
                WHERE {$perm['where']}
                ORDER BY m.created_at DESC
                LIMIT 10
            ", $perm['params']);
            
            // Registrar atividade
            $this->logActivity(
                $user['id'],
                $user['company_id'],
                'report_maintenance',
                'Visualizou relatório de manutenções'
            );
            
            return $this->success([
                'by_type' => $byType,
                'by_month' => $byMonth,
                'recent' => $recent
            ]);
            
        } catch (Exception $e) {
            error_log("[REPORTS] maintenance report error: " . $e->getMessage());
            return $this->error('Erro ao carregar relatório de manutenções', null, 500);
        }
    }
    
    /**
     * Obter relatório de combustível
     * GET /api/v1/reports/fuel
     */
    public function getFuelReport($input, $params) {
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
            
            $period = isset($params['period']) ? (int)$params['period'] : 30;
            
            // Registrar atividade
            $this->logActivity(
                $user['id'],
                $user['company_id'],
                'report_fuel',
                'Visualizou relatório de combustível'
            );
            
            // Verificar se tabela fuel_entries existe
            $fuelTableExists = $this->db->fetchOne("SHOW TABLES LIKE 'fuel_entries'");
            
            if (!$fuelTableExists) {
                return $this->success([
                    'by_vehicle' => [],
                    'by_month' => [],
                    'total_liters' => 0,
                    'total_cost' => 0,
                    'message' => 'Tabela de combustível não encontrada'
                ]);
            }
            
            $fuelPerm = PermissionMiddleware::scope($user, 'fuel_entries', 'f');
            
            // Consumo por veículo
            $byVehicle = $this->db->fetchAll("
                SELECT 
                    v.plate,
                    v.brand,
                    v.model,
                    SUM(f.liters) as total_liters,
                    SUM(f.total_cost) as total_cost,
                    COUNT(*) as entries
                FROM fuel_entries f
                JOIN vehicles v ON f.vehicle_id = v.id
                WHERE {$fuelPerm['where']}
                    AND f.created_at >= DATE_SUB(NOW(), INTERVAL :period DAY)
                GROUP BY f.vehicle_id
                ORDER BY total_liters DESC
                LIMIT 10
            ", array_merge($fuelPerm['params'], [':period' => $period]));
            
            // Consumo por mês
            $byMonth = $this->db->fetchAll("
                SELECT 
                    DATE_FORMAT(f.created_at, '%Y-%m') as month,
                    SUM(f.liters) as total_liters,
                    SUM(f.total_cost) as total_cost,
                    COUNT(*) as entries
                FROM fuel_entries f
                WHERE {$fuelPerm['where']}
                    AND f.created_at >= DATE_SUB(NOW(), INTERVAL :period DAY)
                GROUP BY DATE_FORMAT(f.created_at, '%Y-%m')
                ORDER BY month ASC
            ", array_merge($fuelPerm['params'], [':period' => $period]));
            
            // Totais
            $totals = $this->db->fetchOne("
                SELECT 
                    SUM(f.liters) as total_liters,
                    SUM(f.total_cost) as total_cost,
                    COUNT(*) as entries
                FROM fuel_entries f
                WHERE {$fuelPerm['where']}
                    AND f.created_at >= DATE_SUB(NOW(), INTERVAL :period DAY)
            ", $fuelPerm['params']);
            
            return $this->success([
                'by_vehicle' => $byVehicle,
                'by_month' => $byMonth,
                'total_liters' => (float)($totals['total_liters'] ?? 0),
                'total_cost' => (float)($totals['total_cost'] ?? 0),
                'entries' => (int)($totals['entries'] ?? 0)
            ]);
            
        } catch (Exception $e) {
            error_log("[REPORTS] fuel report error: " . $e->getMessage());
            return $this->error('Erro ao carregar relatório de combustível', null, 500);
        }
    }
    
    /**
     * Obter relatório de documentos
     * GET /api/v1/reports/documents
     */
    public function getDocumentsReport($input, $params) {
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
            
            $perm = PermissionMiddleware::scope($user, 'documents', 'doc');
            
            // Documentos a expirar
            $expiring = $this->db->fetchAll("
                SELECT doc.*, 
                       CASE 
                           WHEN doc.entity_type = 'vehicle' THEN v.plate
                           WHEN doc.entity_type = 'driver' THEN d.name
                           ELSE c.name
                       END as entity_name,
                       DATEDIFF(doc.expiry_date, CURDATE()) as days_remaining
                FROM documents doc
                LEFT JOIN companies c ON doc.entity_type = 'company' AND doc.entity_id = c.id
                LEFT JOIN vehicles v ON doc.entity_type = 'vehicle' AND doc.entity_id = v.id
                LEFT JOIN drivers d ON doc.entity_type = 'driver' AND doc.entity_id = d.id
                WHERE {$perm['where']}
                  AND doc.expiry_date IS NOT NULL
                ORDER BY doc.expiry_date ASC
            ", $perm['params']);
            
            // Documentos por tipo
            $byType = $this->db->fetchAll("
                SELECT 
                    doc.type,
                    COUNT(*) as count,
                    SUM(CASE WHEN doc.expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired,
                    SUM(CASE WHEN doc.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring
                FROM documents doc
                WHERE {$perm['where']}
                GROUP BY doc.type
            ", $perm['params']);
            
            // Registrar atividade
            $this->logActivity(
                $user['id'],
                $user['company_id'],
                'report_documents',
                'Visualizou relatório de documentos'
            );
            
            return $this->success([
                'expiring' => $expiring,
                'by_type' => $byType,
                'total' => count($expiring)
            ]);
            
        } catch (Exception $e) {
            error_log("[REPORTS] documents report error: " . $e->getMessage());
            return $this->error('Erro ao carregar relatório de documentos', null, 500);
        }
    }
    
    /**
     * Obter todos os dados para o dashboard de relatórios
     * GET /api/v1/reports/dashboard
     */
    public function getDashboardData($input, $params) {
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
            
            $period = isset($params['period']) ? (int)$params['period'] : 30;
            
            // Buscar todos os dados necessários
            $stats = $this->getStats($input, $params);
            $maintenanceReport = $this->getMaintenanceReport($input, $params);
            $fuelReport = $this->getFuelReport($input, $params);
            $documentsReport = $this->getDocumentsReport($input, $params);
            
            // Registrar atividade
            $this->logActivity(
                $user['id'],
                $user['company_id'],
                'report_dashboard',
                'Visualizou dashboard de relatórios'
            );
            
            // Combinar todos os dados
            $data = [
                'stats' => $stats['data'] ?? [],
                'maintenance' => $maintenanceReport['data'] ?? [],
                'fuel' => $fuelReport['data'] ?? [],
                'documents' => $documentsReport['data'] ?? [],
                'period' => $period
            ];
            
            return $this->success($data);
            
        } catch (Exception $e) {
            error_log("[REPORTS] dashboard data error: " . $e->getMessage());
            return $this->error('Erro ao carregar dados do dashboard', null, 500);
        }
    }
    
    /**
     * Obter company_id do usuário
     */
    private function getCompanyId($userId) {
        try {
            $result = $this->db->fetchOne("SELECT company_id FROM users WHERE id = ?", [$userId]);
            return $result ? (int)$result['company_id'] : null;
        } catch (Exception $e) {
            error_log("[REPORTS] getCompanyId error: " . $e->getMessage());
            return null;
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