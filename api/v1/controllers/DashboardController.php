<?php
/**
 * CARDOXIS - Dashboard Controller  RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

class DashboardController extends BaseController {
    private $db;
    private $logFile;
    
    private const LIMIT_RECENT = 5;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logFile = __DIR__ . '/../logs/activity.log';
        
        if (!is_dir(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0777, true);
        }
    }
    
    /**
     * Renderizar dashboard
     * GET /dashboard
     */
    public function index($input, $params) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
            header('Location: /cardoxis/login');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'] ?? 'user';
        $companyId = $this->getCompanyId($userId);
        
        if (!$companyId) {
            header('Location: /cardoxis/login');
            exit;
        }
        
        $user = [
            'id' => $userId,
            'role' => $userRole,
            'company_id' => $companyId
        ];
        
        $data = $this->getDashboardData($user);
        
        $_SESSION['alert_count'] = $data['activeAlerts'] ?? 0;
        $_SESSION['maintenance_count'] = $data['activeMaintenances'] ?? 0;
        $_SESSION['total_vehicles'] = $data['totalVehicles'] ?? 0;
        
        // Registrar atividade de acesso ao dashboard
        $this->logActivity(
            $userId,
            $companyId,
            'dashboard_access',
            'Acedeu ao dashboard'
        );
        
        extract($data);
        
        $viewPath = ROOT_PATH . '/resources/views/dashboard/index.php';
        
        if (file_exists($viewPath)) {
            include_once $viewPath;
        } else {
            echo "<h1>Dashboard</h1>";
            echo "<p>Bem-vindo, " . htmlspecialchars($userName ?? 'Utilizador') . "!</p>";
        }
        exit;
    }
    
    /**
     * GET /api/v1/dashboard/stats
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
            
            $stats = $this->getStatsData($user);
            
            // Registrar atividade
            $this->logActivity(
                $user['id'],
                $user['company_id'],
                'dashboard_stats',
                'Visualizou estatísticas do dashboard'
            );
            
            return $this->success($stats);
        } catch (Exception $e) {
            error_log("[Dashboard] getStats error: " . $e->getMessage());
            return $this->success([
                'total_vehicles' => 0,
                'active_vehicles' => 0,
                'total_drivers' => 0,
                'pending_maintenance' => 0,
                'maintenance_cost' => 0,
                'active_alerts' => 0,
                'total_km' => 0
            ]);
        }
    }
    
    /**
     * GET /api/v1/dashboard/vehicles-growth
     */
    public function getVehiclesGrowth($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->success([]);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            $perm = PermissionMiddleware::scope($user, 'vehicles', 'v');
            
            $growth = $this->db->fetchAll("
                SELECT 
                    DATE_FORMAT(v.created_at, '%b') as month,
                    COUNT(*) as count
                FROM vehicles v
                WHERE {$perm['where']}
                    AND v.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(v.created_at, '%Y-%m')
                ORDER BY v.created_at ASC
            ", $perm['params']);
            
            if (empty($growth)) {
                $months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                $growth = [];
                foreach ($months as $month) {
                    $growth[] = ['month' => $month, 'count' => 0];
                }
            }
            
            return $this->success($growth);
        } catch (Exception $e) {
            error_log("[Dashboard] getVehiclesGrowth error: " . $e->getMessage());
            return $this->success([]);
        }
    }
    
    /**
     * GET /api/v1/dashboard/fuel-distribution
     */
    public function getFuelDistribution($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->success([]);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            $perm = PermissionMiddleware::scope($user, 'vehicles', 'v');
            
            $distribution = $this->db->fetchAll("
                SELECT 
                    CASE 
                        WHEN LOWER(v.fuel_type) IN ('diesel', 'gasóleo') THEN 'diesel'
                        WHEN LOWER(v.fuel_type) IN ('gasoline', 'petrol', 'gasolina') THEN 'gasoline'
                        WHEN LOWER(v.fuel_type) IN ('electric', 'elétrico', 'eletrico') THEN 'electric'
                        WHEN LOWER(v.fuel_type) IN ('hybrid', 'híbrido', 'hibrido') THEN 'hybrid'
                        ELSE 'other'
                    END as fuel_type,
                    COUNT(*) as count
                FROM vehicles v
                WHERE {$perm['where']}
                GROUP BY fuel_type
                ORDER BY count DESC
            ", $perm['params']);
            
            if (empty($distribution) || $distribution[0]['count'] == 0) {
                $distribution = [
                    ['fuel_type' => 'diesel', 'count' => 0],
                    ['fuel_type' => 'gasoline', 'count' => 0],
                    ['fuel_type' => 'electric', 'count' => 0],
                    ['fuel_type' => 'hybrid', 'count' => 0]
                ];
            }
            
            return $this->success($distribution);
        } catch (Exception $e) {
            error_log("[Dashboard] getFuelDistribution error: " . $e->getMessage());
            return $this->success([]);
        }
    }
    
    /**
     * GET /api/v1/dashboard/notifications
     */
    public function getNotifications($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->success([
                    'notifications' => [],
                    'unread_count' => 0,
                    'has_more' => false
                ]);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            $perm = PermissionMiddleware::scope($user, 'alerts', 'a');
            
            $notifications = $this->db->fetchAll("
                SELECT a.* FROM alerts a 
                WHERE {$perm['where']} AND a.is_read = 0
                ORDER BY a.created_at DESC LIMIT 5
            ", $perm['params']);
            
            $unreadCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM alerts a WHERE {$perm['where']} AND a.is_read = 0",
                $perm['params']
            )['count'] ?? 0;
            
            return $this->success([
                'notifications' => $notifications ?? [],
                'unread_count' => (int)$unreadCount,
                'has_more' => (int)$unreadCount > 5
            ]);
        } catch (Exception $e) {
            error_log("[Dashboard] getNotifications error: " . $e->getMessage());
            return $this->success([
                'notifications' => [],
                'unread_count' => 0,
                'has_more' => false
            ]);
        }
    }
    
    /**
     * GET /api/v1/dashboard/upcoming-maintenances
     */
    public function getUpcomingMaintenances($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->success([]);
            }
            
            $user = [
                'id' => $authUser['user_id'],
                'role' => $authUser['role'] ?? 'user',
                'company_id' => $this->getCompanyId($authUser['user_id'])
            ];
            
            $perm = PermissionMiddleware::scope($user, 'maintenances', 'm');
            
            $maintenances = $this->db->fetchAll("
                SELECT m.*, v.brand, v.model, v.plate 
                FROM maintenances m 
                JOIN vehicles v ON m.vehicle_id = v.id 
                WHERE {$perm['where']} AND m.status IN ('scheduled', 'in_progress')
                ORDER BY m.scheduled_date ASC LIMIT 5
            ", $perm['params']);
            
            return $this->success($maintenances ?? []);
        } catch (Exception $e) {
            error_log("[Dashboard] getUpcomingMaintenances error: " . $e->getMessage());
            return $this->success([]);
        }
    }
    
    /**
     * GET /api/v1/dashboard/last-mile-kpis
     */
    public function getLastMileKPIs($input, $params) {
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
            
            // Registrar atividade
            $this->logActivity(
                $user['id'],
                $user['company_id'],
                'dashboard_last_mile',
                'Visualizou KPIs da última milha'
            );
            
            try {
                $this->db->fetchOne("SELECT 1 FROM deliveries LIMIT 1");
            } catch (Exception $e) {
                return $this->success([
                    'total_deliveries' => 0,
                    'completed_deliveries' => 0,
                    'pending_deliveries' => 0,
                    'failed_deliveries' => 0,
                    'in_route_deliveries' => 0,
                    'deliveries_today' => 0,
                    'total_revenue' => 0,
                    'avg_order_value' => 0,
                    'sla_rate' => 0,
                    'avg_delivery_time' => 0,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
            
            $perm = PermissionMiddleware::scope($user, 'deliveries', 'd');
            
            $kpis = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total_deliveries,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_deliveries,
                    SUM(CASE WHEN status IN ('pending', 'assigned') THEN 1 ELSE 0 END) as pending_deliveries,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_deliveries,
                    SUM(CASE WHEN status = 'in_route' THEN 1 ELSE 0 END) as in_route_deliveries,
                    SUM(CASE WHEN status = 'delivered' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as deliveries_today,
                    COALESCE(SUM(amount), 0) as total_revenue,
                    COALESCE(AVG(CASE WHEN status = 'delivered' THEN amount END), 0) as avg_order_value
                FROM deliveries d
                WHERE {$perm['where']}
            ", $perm['params']);
            
            $sla = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE 
                        WHEN status = 'delivered' 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        AND updated_at <= DATE_ADD(created_at, INTERVAL 2 HOUR)
                        THEN 1 ELSE 0 
                    END) as on_time
                FROM deliveries d
                WHERE {$perm['where']}
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ", $perm['params']);
            
            $total = (int)($sla['total'] ?? 0);
            $onTime = (int)($sla['on_time'] ?? 0);
            $slaRate = $total > 0 ? round(($onTime / $total) * 100, 1) : 0;
            
            $avgDeliveryTime = $this->db->fetchOne("
                SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_time
                FROM deliveries d
                WHERE {$perm['where']}
                    AND status = 'delivered'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ", $perm['params']);
            
            return $this->success([
                'total_deliveries' => (int)($kpis['total_deliveries'] ?? 0),
                'completed_deliveries' => (int)($kpis['completed_deliveries'] ?? 0),
                'pending_deliveries' => (int)($kpis['pending_deliveries'] ?? 0),
                'failed_deliveries' => (int)($kpis['failed_deliveries'] ?? 0),
                'in_route_deliveries' => (int)($kpis['in_route_deliveries'] ?? 0),
                'deliveries_today' => (int)($kpis['deliveries_today'] ?? 0),
                'total_revenue' => (float)($kpis['total_revenue'] ?? 0),
                'avg_order_value' => (float)($kpis['avg_order_value'] ?? 0),
                'sla_rate' => $slaRate,
                'avg_delivery_time' => round($avgDeliveryTime['avg_time'] ?? 0, 0),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("[Dashboard] getLastMileKPIs error: " . $e->getMessage());
            return $this->success([
                'total_deliveries' => 0,
                'completed_deliveries' => 0,
                'pending_deliveries' => 0,
                'failed_deliveries' => 0,
                'in_route_deliveries' => 0,
                'deliveries_today' => 0,
                'total_revenue' => 0,
                'avg_order_value' => 0,
                'sla_rate' => 0,
                'avg_delivery_time' => 0,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * GET /api/v1/dashboard/last-mile-summary
     */
    public function getLastMileSummary($input, $params) {
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
            
            $kpis = $this->getLastMileKPIs($input, $params);
            $kpisData = json_decode(json_encode($kpis), true);
            
            $vehicleStatus = $this->getVehicleStatus($input, $params);
            $vehicleData = json_decode(json_encode($vehicleStatus), true);
            
            $topDrivers = $this->getTopDrivers($input, $params);
            $driversData = json_decode(json_encode($topDrivers), true);
            
            $financial = $this->getFinancialSummary($input, $params);
            $financialData = json_decode(json_encode($financial), true);
            
            return $this->success([
                'kpis' => $kpisData['data'] ?? [],
                'vehicle_status' => $vehicleData['data'] ?? [],
                'top_drivers' => $driversData['data'] ?? [],
                'financial' => $financialData['data'] ?? [],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("[Dashboard] getLastMileSummary error: " . $e->getMessage());
            return $this->success([
                'kpis' => [],
                'vehicle_status' => [],
                'top_drivers' => [],
                'financial' => [],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * GET /api/v1/dashboard/delivery-performance
     */
    public function getDeliveryPerformance($input, $params) {
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
                return $this->success([]);
            }
            
            try {
                $this->db->fetchOne("SELECT 1 FROM deliveries LIMIT 1");
            } catch (Exception $e) {
                return $this->success([]);
            }
            
            $period = isset($params['period']) ? $params['period'] : 'week';
            $days = $period === 'week' ? 7 : ($period === 'month' ? 30 : 365);
            
            $perm = PermissionMiddleware::scope($user, 'deliveries', 'd');
            
            $performance = $this->db->fetchAll("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'in_route' THEN 1 ELSE 0 END) as in_route,
                    SUM(amount) as revenue
                FROM deliveries d
                WHERE {$perm['where']}
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ", array_merge($perm['params'], [$days]));
            
            return $this->success($performance);
            
        } catch (Exception $e) {
            error_log("[Dashboard] getDeliveryPerformance error: " . $e->getMessage());
            return $this->success([]);
        }
    }
    
    /**
     * GET /api/v1/dashboard/top-drivers
     */
    public function getTopDrivers($input, $params) {
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
                return $this->success([]);
            }
            
            try {
                $this->db->fetchOne("SELECT 1 FROM deliveries LIMIT 1");
            } catch (Exception $e) {
                return $this->success([]);
            }
            
            $perm = PermissionMiddleware::scope($user, 'deliveries', 'd');
            
            $topDrivers = $this->db->fetchAll("
                SELECT 
                    dr.name as driver_name,
                    COUNT(d.id) as total_deliveries,
                    SUM(CASE WHEN d.status = 'delivered' THEN 1 ELSE 0 END) as completed,
                    ROUND(AVG(CASE WHEN d.status = 'delivered' THEN d.amount END), 2) as avg_value,
                    ROUND(SUM(CASE WHEN d.status = 'delivered' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(d.id), 0), 1) as success_rate
                FROM deliveries d
                JOIN drivers dr ON d.driver_id = dr.id
                WHERE {$perm['where']}
                    AND d.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND d.driver_id IS NOT NULL
                GROUP BY d.driver_id, dr.name
                HAVING COUNT(d.id) >= 3
                ORDER BY completed DESC
                LIMIT 10
            ", $perm['params']);
            
            return $this->success($topDrivers);
            
        } catch (Exception $e) {
            error_log("[Dashboard] getTopDrivers error: " . $e->getMessage());
            return $this->success([]);
        }
    }
    
    /**
     * GET /api/v1/dashboard/vehicle-status
     */
    public function getVehicleStatus($input, $params) {
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
                return $this->success([]);
            }
            
            $perm = PermissionMiddleware::scope($user, 'vehicles', 'v');
            
            $status = $this->db->fetchAll("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM vehicles v
                WHERE {$perm['where']}
                GROUP BY status
                ORDER BY count DESC
            ", $perm['params']);
            
            $allStatus = ['active', 'in_route', 'idle', 'offline', 'maintenance'];
            $existingStatus = array_column($status, 'status');
            foreach ($allStatus as $s) {
                if (!in_array($s, $existingStatus)) {
                    $status[] = ['status' => $s, 'count' => 0];
                }
            }
            
            return $this->success($status);
            
        } catch (Exception $e) {
            error_log("[Dashboard] getVehicleStatus error: " . $e->getMessage());
            return $this->success([]);
        }
    }
    
    /**
     * GET /api/v1/dashboard/realtime-activity
     */
    public function getRealtimeActivity($input, $params) {
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
                return $this->success([]);
            }
            
            // Buscar atividades recentes da tabela activity_logs
            $activities = $this->db->fetchAll("
                SELECT 
                    al.id,
                    al.action,
                    al.description,
                    al.ip_address,
                    al.created_at,
                    u.name as user_name
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.company_id = :company_id
                ORDER BY al.created_at DESC
                LIMIT 20
            ", [':company_id' => $user['company_id']]);
            
            return $this->success($activities);
            
        } catch (Exception $e) {
            error_log("[Dashboard] getRealtimeActivity error: " . $e->getMessage());
            return $this->success([]);
        }
    }
    
    /**
     * GET /api/v1/dashboard/financial-summary
     */
    public function getFinancialSummary($input, $params) {
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
                    'total_revenue' => 0,
                    'revenue_last_30_days' => 0,
                    'revenue_last_7_days' => 0,
                    'revenue_today' => 0,
                    'delivery_revenue' => 0,
                    'avg_delivery_value' => 0,
                    'paid_deliveries' => 0
                ]);
            }
            
            $perm = PermissionMiddleware::scope($user, 'payments', 'p');
            
            $revenue = $this->db->fetchOne("
                SELECT 
                    COALESCE(SUM(amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN DATE(payment_date) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END), 0) as last_30_days,
                    COALESCE(SUM(CASE WHEN DATE(payment_date) >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN amount ELSE 0 END), 0) as last_7_days,
                    COALESCE(SUM(CASE WHEN DATE(payment_date) = CURDATE() THEN amount ELSE 0 END), 0) as today
                FROM payments p
                WHERE {$perm['where']}
                    AND status = 'paid'
            ", $perm['params']);
            
            $deliveryRevenue = $this->db->fetchOne("
                SELECT 
                    COALESCE(SUM(amount), 0) as total,
                    COALESCE(AVG(amount), 0) as avg,
                    COUNT(*) as count
                FROM deliveries d
                WHERE {$perm['where']}
                    AND status = 'delivered'
            ", $perm['params']);
            
            return $this->success([
                'total_revenue' => (float)($revenue['total_revenue'] ?? 0),
                'revenue_last_30_days' => (float)($revenue['last_30_days'] ?? 0),
                'revenue_last_7_days' => (float)($revenue['last_7_days'] ?? 0),
                'revenue_today' => (float)($revenue['today'] ?? 0),
                'delivery_revenue' => (float)($deliveryRevenue['total'] ?? 0),
                'avg_delivery_value' => (float)($deliveryRevenue['avg'] ?? 0),
                'paid_deliveries' => (int)($deliveryRevenue['count'] ?? 0)
            ]);
            
        } catch (Exception $e) {
            error_log("[Dashboard] getFinancialSummary error: " . $e->getMessage());
            return $this->success([
                'total_revenue' => 0,
                'revenue_last_30_days' => 0,
                'revenue_last_7_days' => 0,
                'revenue_today' => 0,
                'delivery_revenue' => 0,
                'avg_delivery_value' => 0,
                'paid_deliveries' => 0
            ]);
        }
    }
    
    /**
     * GET /api/v1/dashboard/delivery-map
     */
    public function getDeliveryMap($input, $params) {
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
                return $this->success([]);
            }
            
            try {
                $this->db->fetchOne("SELECT 1 FROM deliveries LIMIT 1");
            } catch (Exception $e) {
                return $this->success([]);
            }
            
            $perm = PermissionMiddleware::scope($user, 'deliveries', 'd');
            
            $deliveries = $this->db->fetchAll("
                SELECT 
                    id,
                    customer_name,
                    customer_address,
                    customer_phone,
                    status,
                    amount,
                    latitude,
                    longitude,
                    created_at,
                    updated_at
                FROM deliveries d
                WHERE {$perm['where']}
                    AND latitude IS NOT NULL
                    AND longitude IS NOT NULL
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY created_at DESC
                LIMIT 100
            ", $perm['params']);
            
            return $this->success($deliveries);
            
        } catch (Exception $e) {
            error_log("[Dashboard] getDeliveryMap error: " . $e->getMessage());
            return $this->success([]);
        }
    }
    
    // ============================================
    // MÉTODOS PRIVADOS
    // ============================================
    
    private function getCompanyId($userId) {
        try {
            $result = $this->db->fetchOne("SELECT company_id FROM users WHERE id = :user_id", [':user_id' => $userId]);
            return $result ? $result['company_id'] : null;
        } catch (Exception $e) {
            error_log("[Dashboard] getCompanyId error: " . $e->getMessage());
            return null;
        }
    }
    
    private function getStatsData($user) {
        try {
            $vehiclePerm = PermissionMiddleware::scope($user, 'vehicles', 'v');
            $maintenancePerm = PermissionMiddleware::scope($user, 'maintenances', 'm');
            $alertPerm = PermissionMiddleware::scope($user, 'alerts', 'a');
            $driverPerm = PermissionMiddleware::scope($user, 'drivers', 'd');
            
            $totalVehicles = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM vehicles v WHERE {$vehiclePerm['where']}",
                $vehiclePerm['params']
            )['total'] ?? 0;
            
            $activeVehicles = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM vehicles v WHERE {$vehiclePerm['where']} AND v.status = 'active'",
                $vehiclePerm['params']
            )['total'] ?? 0;
            
            $totalKm = $this->db->fetchOne(
                "SELECT SUM(v.odometer) as total_km FROM vehicles v WHERE {$vehiclePerm['where']}",
                $vehiclePerm['params']
            )['total_km'] ?? 0;
            
            $pendingMaintenance = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM maintenances m WHERE {$maintenancePerm['where']} AND m.status IN ('scheduled', 'in_progress')",
                $maintenancePerm['params']
            )['total'] ?? 0;
            
            $maintenanceCost = $this->db->fetchOne(
                "SELECT SUM(m.cost) as total_cost FROM maintenances m WHERE {$maintenancePerm['where']} AND YEAR(m.created_at) = YEAR(NOW())",
                $maintenancePerm['params']
            )['total_cost'] ?? 0;
            
            $activeAlerts = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM alerts a WHERE {$alertPerm['where']} AND a.is_read = 0",
                $alertPerm['params']
            )['total'] ?? 0;
            
            $totalDrivers = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM drivers d WHERE {$driverPerm['where']}",
                $driverPerm['params']
            )['total'] ?? 0;
            
            return [
                'total_vehicles' => (int)$totalVehicles,
                'active_vehicles' => (int)$activeVehicles,
                'total_drivers' => (int)$totalDrivers,
                'pending_maintenance' => (int)$pendingMaintenance,
                'maintenance_cost' => (float)$maintenanceCost,
                'active_alerts' => (int)$activeAlerts,
                'total_km' => (int)$totalKm
            ];
        } catch (Exception $e) {
            error_log("[Dashboard] getStatsData error: " . $e->getMessage());
            return [
                'total_vehicles' => 0,
                'active_vehicles' => 0,
                'total_drivers' => 0,
                'pending_maintenance' => 0,
                'maintenance_cost' => 0,
                'active_alerts' => 0,
                'total_km' => 0
            ];
        }
    }
    
    private function getDashboardData($user) {
        try {
            $stats = $this->getStatsData($user);
            $vehiclePerm = PermissionMiddleware::scope($user, 'vehicles', 'v');
            $maintenancePerm = PermissionMiddleware::scope($user, 'maintenances', 'm');
            $alertPerm = PermissionMiddleware::scope($user, 'alerts', 'a');
            
            $recentVehicles = $this->db->fetchAll(
                "SELECT v.* FROM vehicles v WHERE {$vehiclePerm['where']} ORDER BY v.created_at DESC LIMIT 5",
                $vehiclePerm['params']
            );
            
            $upcomingMaintenances = $this->db->fetchAll("
                SELECT m.*, v.brand, v.model, v.plate 
                FROM maintenances m 
                JOIN vehicles v ON m.vehicle_id = v.id 
                WHERE {$maintenancePerm['where']} AND m.status IN ('scheduled', 'in_progress')
                ORDER BY m.scheduled_date ASC LIMIT 5
            ", $maintenancePerm['params']);
            
            $notifications = $this->db->fetchAll("
                SELECT a.* FROM alerts a 
                WHERE {$alertPerm['where']} AND a.is_read = 0
                ORDER BY a.created_at DESC LIMIT 5
            ", $alertPerm['params']);
            
            $unreadCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM alerts a WHERE {$alertPerm['where']} AND a.is_read = 0",
                $alertPerm['params']
            )['count'] ?? 0;
            
            return [
                'stats' => $stats,
                'totalVehicles' => $stats['total_vehicles'],
                'activeVehicles' => $stats['active_vehicles'],
                'totalDrivers' => $stats['total_drivers'],
                'totalKm' => $stats['total_km'],
                'maintenanceCost' => $stats['maintenance_cost'],
                'activeMaintenances' => $stats['pending_maintenance'],
                'activeAlerts' => $stats['active_alerts'],
                'recentVehicles' => $recentVehicles ?? [],
                'upcomingMaintenances' => $upcomingMaintenances ?? [],
                'notifications' => $notifications ?? [],
                'unreadCount' => (int)$unreadCount,
                'userName' => $_SESSION['user_name'] ?? 'Utilizador',
                'userRole' => $user['role'],
                'userEmail' => $_SESSION['user_email'] ?? '',
                'userId' => $user['id'],
                'companyId' => $user['company_id'],
                'isAdmin' => PermissionMiddleware::isAdmin($user),
                'vehicleGrowth' => [],
                'fuelDistribution' => [],
                'hasMoreAlerts' => (int)$unreadCount > 5
            ];
        } catch (Exception $e) {
            error_log("[Dashboard] getDashboardData error: " . $e->getMessage());
            return [
                'stats' => [],
                'totalVehicles' => 0,
                'activeVehicles' => 0,
                'totalDrivers' => 0,
                'totalKm' => 0,
                'maintenanceCost' => 0,
                'activeMaintenances' => 0,
                'activeAlerts' => 0,
                'recentVehicles' => [],
                'upcomingMaintenances' => [],
                'notifications' => [],
                'unreadCount' => 0,
                'userName' => $_SESSION['user_name'] ?? 'Utilizador',
                'userRole' => $user['role'],
                'userEmail' => $_SESSION['user_email'] ?? '',
                'userId' => $user['id'],
                'companyId' => $user['company_id'],
                'isAdmin' => false,
                'vehicleGrowth' => [],
                'fuelDistribution' => [],
                'hasMoreAlerts' => false
            ];
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