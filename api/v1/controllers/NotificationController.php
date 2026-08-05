<?php
/**
 * CARDOXIS - Notification Controller RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

class NotificationController extends BaseController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Listar notificações do usuário
     * GET /api/v1/notifications
     * Permissão: Admin vê tudo, User vê apenas as suas
     */
    public function index($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            
            // Construir objeto do usuário para permissões
            $userObj = [
                'id' => $userId,
                'role' => $userRole,
                'company_id' => $this->getCompanyId($userId)
            ];
            
            $isAdmin = PermissionMiddleware::isAdmin($userObj);
            
            // Buscar notificações - Admin vê todas da empresa, User vê apenas as suas
            if ($isAdmin) {
                // Admin: Vê todas as notificações da empresa
                $notifications = $this->db->fetchAll("
                    SELECT n.*, u.name as created_by_name, 
                           u2.name as user_name
                    FROM notifications n
                    LEFT JOIN users u ON n.created_by = u.id
                    LEFT JOIN users u2 ON n.user_id = u2.id
                    WHERE n.company_id = :company_id
                    ORDER BY n.created_at DESC
                    LIMIT 50
                ", [':company_id' => $userObj['company_id']]);
            } else {
                // User: Vê apenas as suas notificações
                $notifications = $this->db->fetchAll("
                    SELECT n.*, u.name as created_by_name
                    FROM notifications n
                    LEFT JOIN users u ON n.created_by = u.id
                    WHERE n.user_id = :user_id
                    ORDER BY n.created_at DESC
                    LIMIT 50
                ", [':user_id' => $userId]);
            }
            
            // Se não houver notificações, gerar notificações automáticas do sistema
            if (empty($notifications)) {
                $this->generateSystemNotifications($userObj['company_id'], $userId);
                
                // Recarregar notificações
                if ($isAdmin) {
                    $notifications = $this->db->fetchAll("
                        SELECT n.*, u.name as created_by_name, 
                               u2.name as user_name
                        FROM notifications n
                        LEFT JOIN users u ON n.created_by = u.id
                        LEFT JOIN users u2 ON n.user_id = u2.id
                        WHERE n.company_id = :company_id
                        ORDER BY n.created_at DESC
                        LIMIT 50
                    ", [':company_id' => $userObj['company_id']]);
                } else {
                    $notifications = $this->db->fetchAll("
                        SELECT n.*, u.name as created_by_name
                        FROM notifications n
                        LEFT JOIN users u ON n.created_by = u.id
                        WHERE n.user_id = :user_id
                        ORDER BY n.created_at DESC
                        LIMIT 50
                    ", [':user_id' => $userId]);
                }
            }
            
            // Formatar dados
            foreach ($notifications as &$notif) {
                $notif['is_read'] = (int)$notif['is_read'];
                $notif['created_at_formatted'] = date('d/m/Y H:i', strtotime($notif['created_at']));
                $notif['type'] = $notif['type'] ?? 'system';
                $notif['title'] = $notif['title'] ?? 'Notificação';
                $notif['message'] = $notif['message'] ?? 'Sem mensagem';
                $notif['can_delete'] = $isAdmin || ($notif['created_by'] == $userId);
            }
            
            return $this->success([
                'data' => $notifications,
                'total' => count($notifications),
                'is_admin' => $isAdmin
            ]);
            
        } catch (Exception $e) {
            error_log("[NOTIFICATIONS] index error: " . $e->getMessage());
            return $this->error('Erro ao carregar notificações', null, 500);
        }
    }
    
    /**
     * Obter notificação específica
     * GET /api/v1/notifications/{id}
     */
    public function show($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$id || $id === 'null' || $id === 'undefined') {
                return $this->error('ID inválido', null, 400);
            }
            
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            
            $userObj = [
                'id' => $userId,
                'role' => $userRole,
                'company_id' => $this->getCompanyId($userId)
            ];
            
            $isAdmin = PermissionMiddleware::isAdmin($userObj);
            
            // Buscar notificação
            $sql = "SELECT n.*, u.name as created_by_name 
                    FROM notifications n
                    LEFT JOIN users u ON n.created_by = u.id
                    WHERE n.id = :id";
            
            $params = [':id' => $id];
            
            if (!$isAdmin) {
                $sql .= " AND n.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $notification = $this->db->fetchOne($sql, $params);
            
            if (!$notification) {
                return $this->error('Notificação não encontrada', null, 404);
            }
            
            $notification['is_read'] = (int)$notification['is_read'];
            
            return $this->success($notification);
            
        } catch (Exception $e) {
            error_log("[NOTIFICATIONS] show error: " . $e->getMessage());
            return $this->error('Erro ao buscar notificação', null, 500);
        }
    }
    
    /**
     * Marcar notificação como lida
     * POST /api/v1/notifications/{id}/read
     */
    public function markAsRead($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$id || $id === 'null' || $id === 'undefined') {
                return $this->error('ID inválido', null, 400);
            }
            
            $userId = $authUser['user_id'];
            
            // Verificar se a notificação existe e pertence ao usuário
            $existing = $this->db->fetchOne("
                SELECT id, is_read FROM notifications 
                WHERE id = :id AND user_id = :user_id
            ", [
                ':id' => $id,
                ':user_id' => $userId
            ]);
            
            if (!$existing) {
                return $this->error('Notificação não encontrada', null, 404);
            }
            
            if ($existing['is_read'] == 1) {
                return $this->success(null, 'Notificação já estava marcada como lida');
            }
            
            $this->db->update('notifications', [
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ], 'id = :id', [':id' => $id]);
            
            return $this->success(null, 'Notificação marcada como lida');
            
        } catch (Exception $e) {
            error_log("[NOTIFICATIONS] markAsRead error: " . $e->getMessage());
            return $this->error('Erro ao marcar notificação', null, 500);
        }
    }
    
    /**
     * Marcar todas as notificações como lidas
     * POST /api/v1/notifications/read-all
     */
    public function markAllAsRead($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $userId = $authUser['user_id'];
            
            $sql = "UPDATE notifications SET is_read = 1, read_at = :read_at WHERE user_id = :user_id AND is_read = 0";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([
                ':read_at' => date('Y-m-d H:i:s'),
                ':user_id' => $userId
            ]);
            
            $affected = $stmt->rowCount();
            
            return $this->success(['affected' => $affected], "{$affected} notificações marcadas como lidas");
            
        } catch (Exception $e) {
            error_log("[NOTIFICATIONS] markAllAsRead error: " . $e->getMessage());
            return $this->error('Erro ao marcar notificações', null, 500);
        }
    }
    
    /**
     * Eliminar notificação
     * DELETE /api/v1/notifications/{id}
     */
    public function delete($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$id || $id === 'null' || $id === 'undefined') {
                return $this->error('ID inválido', null, 400);
            }
            
            $userId = $authUser['user_id'];
            $userRole = $authUser['role'] ?? 'user';
            
            $userObj = [
                'id' => $userId,
                'role' => $userRole,
                'company_id' => $this->getCompanyId($userId)
            ];
            
            $isAdmin = PermissionMiddleware::isAdmin($userObj);
            
            // Verificar permissão
            $sql = "SELECT id, created_by FROM notifications WHERE id = :id";
            $params = [':id' => $id];
            
            if (!$isAdmin) {
                $sql .= " AND user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $notification = $this->db->fetchOne($sql, $params);
            
            if (!$notification) {
                return $this->error('Notificação não encontrada', null, 404);
            }
            
            // Apenas admin ou criador pode eliminar
            if (!$isAdmin && $notification['created_by'] != $userId) {
                return $this->error('Sem permissão para eliminar', null, 403);
            }
            
            $this->db->delete('notifications', 'id = :id', [':id' => $id]);
            
            return $this->success(null, 'Notificação eliminada com sucesso');
            
        } catch (Exception $e) {
            error_log("[NOTIFICATIONS] delete error: " . $e->getMessage());
            return $this->error('Erro ao eliminar notificação', null, 500);
        }
    }
    
    /**
     * Eliminar notificações lidas
     * DELETE /api/v1/notifications/delete-read
     */
    public function deleteRead($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $userId = $authUser['user_id'];
            
            $sql = "DELETE FROM notifications WHERE user_id = :user_id AND is_read = 1";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            
            $affected = $stmt->rowCount();
            
            return $this->success(['affected' => $affected], "{$affected} notificações lidas eliminadas");
            
        } catch (Exception $e) {
            error_log("[NOTIFICATIONS] deleteRead error: " . $e->getMessage());
            return $this->error('Erro ao eliminar notificações', null, 500);
        }
    }
    
    /**
     * Gerar notificações automáticas do sistema
     */
    private function generateSystemNotifications($companyId, $userId) {
        try {
            // Buscar documentos a vencer
            $documents = $this->db->fetchAll("
                SELECT id, title, expiry_date 
                FROM documents 
                WHERE company_id = :company_id 
                  AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                  AND status = 'valid'
                LIMIT 5
            ", [':company_id' => $companyId]);
            
            foreach ($documents as $doc) {
                $daysLeft = (int)ceil((strtotime($doc['expiry_date']) - time()) / 86400);
                $this->db->insert('notifications', [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'type' => 'document',
                    'title' => '📄 Documento próximo do vencimento',
                    'message' => "O documento '{$doc['title']}' vence em {$daysLeft} dias (" . date('d/m/Y', strtotime($doc['expiry_date'])) . ")",
                    'created_by' => $userId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Buscar manutenções agendadas
            $maintenances = $this->db->fetchAll("
                SELECT m.id, m.title, m.scheduled_date, v.plate
                FROM maintenances m
                JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.company_id = :company_id
                  AND m.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                  AND m.status = 'scheduled'
                LIMIT 5
            ", [':company_id' => $companyId]);
            
            foreach ($maintenances as $m) {
                $daysLeft = (int)ceil((strtotime($m['scheduled_date']) - time()) / 86400);
                $this->db->insert('notifications', [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'type' => 'maintenance',
                    'title' => '🔧 Manutenção agendada',
                    'message' => "Manutenção '{$m['title']}' agendada para o veículo {$m['plate']} em {$daysLeft} dias (" . date('d/m/Y', strtotime($m['scheduled_date'])) . ")",
                    'created_by' => $userId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Buscar seguros a expirar
            $insurances = $this->db->fetchAll("
                SELECT i.id, i.policy_number, i.end_date, v.plate
                FROM insurances i
                JOIN vehicles v ON i.vehicle_id = v.id
                WHERE i.company_id = :company_id
                  AND i.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                  AND i.status = 'active'
                LIMIT 5
            ", [':company_id' => $companyId]);
            
            foreach ($insurances as $ins) {
                $daysLeft = (int)ceil((strtotime($ins['end_date']) - time()) / 86400);
                $this->db->insert('notifications', [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'type' => 'insurance',
                    'title' => '🛡️ Seguro a expirar',
                    'message' => "Seguro do veículo {$ins['plate']} (apólice: {$ins['policy_number']}) vence em {$daysLeft} dias (" . date('d/m/Y', strtotime($ins['end_date'])) . ")",
                    'created_by' => $userId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Buscar multas a vencer
            $fines = $this->db->fetchAll("
                SELECT f.id, f.fine_number, f.due_date, f.amount, v.plate
                FROM fines f
                JOIN vehicles v ON f.vehicle_id = v.id
                WHERE f.company_id = :company_id
                  AND f.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                  AND f.status = 'pending'
                LIMIT 5
            ", [':company_id' => $companyId]);
            
            foreach ($fines as $fine) {
                $daysLeft = (int)ceil((strtotime($fine['due_date']) - time()) / 86400);
                $this->db->insert('notifications', [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'type' => 'fine',
                    'title' => '💰 Multa a vencer',
                    'message' => "Multa {$fine['fine_number']} do veículo {$fine['plate']} vence em {$daysLeft} dias (valor: € " . number_format($fine['amount'], 2, ',', '.') . ")",
                    'created_by' => $userId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Notificação de boas-vindas (se não houver outras)
            $existing = $this->db->fetchOne(
                "SELECT id FROM notifications WHERE user_id = :user_id AND type = 'system'",
                [':user_id' => $userId]
            );
            
            if (!$existing) {
                $this->db->insert('notifications', [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'type' => 'system',
                    'title' => '👋 Bem-vindo ao CARDOXIS',
                    'message' => 'O seu sistema de gestão de frotas está pronto para usar. Configure a sua empresa e comece a adicionar veículos.',
                    'created_by' => $userId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
        } catch (Exception $e) {
            error_log("[NOTIFICATIONS] generateSystemNotifications error: " . $e->getMessage());
        }
    }
    
    /**
     * Obter company_id do usuário
     */
    private function getCompanyId($userId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT company_id FROM users WHERE id = :user_id",
                [':user_id' => $userId]
            );
            return $result ? $result['company_id'] : null;
        } catch (Exception $e) {
            error_log("[NOTIFICATIONS] getCompanyId error: " . $e->getMessage());
            return null;
        }
    }
}