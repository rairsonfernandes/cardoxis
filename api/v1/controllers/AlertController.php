<?php
/**
 * CARDOXIS - Alert Controller RF
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

class AlertController extends BaseController {
    private $db;
    
    // Constantes
    private const ALERT_TYPES = ['document', 'maintenance', 'insurance', 'fine', 'license', 'payment', 'system'];
    private const SEVERITY_LEVELS = ['info', 'warning', 'critical'];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Listar alertas com permissões
     * GET /api/v1/alerts
     */
    public function index($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = $this->buildUser($authUser);
            
            if (!$user['company_id']) {
                return $this->emptyResponse($user);
            }
            
            // Verificar e criar alertas automaticamente
            $this->processAutomaticAlerts($user['company_id']);
            
            // Obter scope de permissões
            $perm = PermissionMiddleware::scope($user, 'alerts', 'a');
            
            $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
            $limit = min(max($limit, 1), 100);
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $offset = ($page - 1) * $limit;
            $status = isset($params['status']) ? $params['status'] : '';
            
            $extraWhere = "";
            $extraParams = [];
            
            // Filtros
            if ($status === 'unread') {
                $extraWhere .= " AND a.is_read = 0";
            } elseif ($status === 'read') {
                $extraWhere .= " AND a.is_read = 1";
            } elseif ($status === 'critical') {
                $extraWhere .= " AND a.severity = 'critical' AND a.is_read = 0";
            } elseif ($status === 'document') {
                $extraWhere .= " AND a.type = 'document'";
            }
            
            // Contagem total
            $countSql = "SELECT COUNT(*) as total FROM alerts a WHERE {$perm['where']} {$extraWhere}";
            $countResult = $this->db->fetchOne($countSql, array_merge($perm['params'], $extraParams));
            $total = $countResult ? (int)$countResult['total'] : 0;
            
            // Buscar alertas
            $sql = "
                SELECT a.*, u.name as created_by_name
                FROM alerts a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE {$perm['where']} {$extraWhere}
                ORDER BY 
                    CASE 
                        WHEN a.severity = 'critical' AND a.is_read = 0 THEN 0
                        WHEN a.severity = 'warning' AND a.is_read = 0 THEN 1
                        WHEN a.is_read = 0 THEN 2
                        ELSE 3
                    END,
                    a.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $paramsDb = array_merge($perm['params'], $extraParams, [
                ':limit' => $limit,
                ':offset' => $offset
            ]);
            
            $alerts = $this->db->fetchAll($sql, $paramsDb);
            
            // Formatar dados
            $alerts = array_map(function($alert) use ($user) {
                $alert['is_read'] = (int)$alert['is_read'];
                $alert['created_at_formatted'] = date('d/m/Y H:i', strtotime($alert['created_at']));
                if ($alert['read_at']) {
                    $alert['read_at_formatted'] = date('d/m/Y H:i', strtotime($alert['read_at']));
                }
                $alert['is_owner'] = ($alert['created_by'] == $user['id']);
                $alert['can_delete'] = (PermissionMiddleware::isAdmin($user) || $alert['created_by'] == $user['id']);
                return $alert;
            }, $alerts);
            
            return $this->success([
                'data' => $alerts,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit),
                'user_role' => $user['role'],
                'is_admin' => PermissionMiddleware::isAdmin($user),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("[ALERTS] index error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Obter alerta específico
     * GET /api/v1/alerts/{id}
     */
    public function show($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$this->isValidId($id)) {
                return $this->error('ID do alerta inválido', null, 400);
            }
            
            $user = $this->buildUser($authUser);
            
            if (!$user['company_id']) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            if (!PermissionMiddleware::check($user, 'alerts', $id)) {
                return $this->error('Sem permissão', null, 403);
            }
            
            $alert = $this->db->fetchOne("
                SELECT a.*, u.name as created_by_name
                FROM alerts a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.id = :id AND a.company_id = :company_id
                AND (a.deleted_at IS NULL OR a.deleted_at = '0000-00-00 00:00:00')
            ", [
                ':id' => $id,
                ':company_id' => $user['company_id']
            ]);
            
            if (!$alert) {
                return $this->error('Alerta não encontrado', null, 404);
            }
            
            $alert['is_read'] = (int)$alert['is_read'];
            $alert['can_delete'] = (PermissionMiddleware::isAdmin($user) || $alert['created_by'] == $user['id']);
            
            return $this->success($alert);
            
        } catch (Exception $e) {
            error_log("[ALERTS] show error: " . $e->getMessage());
            return $this->error('Erro ao buscar alerta', null, 500);
        }
    }
    
    /**
     * Marcar alerta como lido
     * POST /api/v1/alerts/{id}/read
     */
    public function markAsRead($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$this->isValidId($id)) {
                return $this->error('ID do alerta inválido', null, 400);
            }
            
            $user = $this->buildUser($authUser);
            
            if (!$user['company_id']) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            if (!PermissionMiddleware::check($user, 'alerts', $id)) {
                return $this->error('Sem permissão', null, 403);
            }
            
            $existing = $this->db->fetchOne(
                "SELECT id, is_read FROM alerts WHERE id = :id AND company_id = :company_id AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$existing) {
                return $this->error('Alerta não encontrado', null, 404);
            }
            
            if ($existing['is_read'] == 1) {
                return $this->success(null, 'Alerta já estava marcado como lido');
            }
            
            $this->db->update('alerts', [
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s'),
                'read_by' => $user['id']
            ], 'id = :id', [':id' => $id]);
            
            return $this->success(null, 'Alerta marcado como lido');
            
        } catch (Exception $e) {
            error_log("[ALERTS] markAsRead error: " . $e->getMessage());
            return $this->error('Erro ao marcar alerta', null, 500);
        }
    }
    
    /**
     * Marcar todos como lidos
     * POST /api/v1/alerts/read-all
     */
    public function markAllAsRead($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = $this->buildUser($authUser);
            
            if (!$user['company_id']) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            $perm = PermissionMiddleware::scope($user, 'alerts', 'a');
            
            $sql = "UPDATE alerts a SET is_read = 1, read_at = :read_at, read_by = :read_by WHERE {$perm['where']} AND is_read = 0";
            $paramsDb = array_merge($perm['params'], [
                ':read_at' => date('Y-m-d H:i:s'),
                ':read_by' => $user['id']
            ]);
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($paramsDb);
            $affected = $stmt->rowCount();
            
            return $this->success(['affected' => $affected], "{$affected} alertas marcados como lidos");
            
        } catch (Exception $e) {
            error_log("[ALERTS] markAllAsRead error: " . $e->getMessage());
            return $this->error('Erro ao marcar alertas', null, 500);
        }
    }
    
    /**
     * Eliminar alerta (soft delete)
     * DELETE /api/v1/alerts/{id}/delete
     */
    public function delete($id, $input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            if (!$this->isValidId($id)) {
                return $this->error('ID do alerta inválido', null, 400);
            }
            
            $user = $this->buildUser($authUser);
            
            if (!$user['company_id']) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            if (!PermissionMiddleware::check($user, 'alerts', $id)) {
                return $this->error('Sem permissão', null, 403);
            }
            
            $alert = $this->db->fetchOne(
                "SELECT created_by FROM alerts WHERE id = :id AND company_id = :company_id",
                [
                    ':id' => $id,
                    ':company_id' => $user['company_id']
                ]
            );
            
            if (!$alert) {
                return $this->error('Alerta não encontrado', null, 404);
            }
            
            if (!PermissionMiddleware::isAdmin($user) && $alert['created_by'] != $user['id']) {
                return $this->error('Sem permissão para eliminar este alerta', null, 403);
            }
            
            $this->db->update('alerts', [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $user['id']
            ], 'id = :id', [':id' => $id]);
            
            return $this->success(null, 'Alerta eliminado com sucesso');
            
        } catch (Exception $e) {
            error_log("[ALERTS] delete error: " . $e->getMessage());
            return $this->error('Erro ao eliminar alerta', null, 500);
        }
    }
    
    /**
     * Eliminar alertas lidos
     * DELETE /api/v1/alerts/delete-read
     */
    public function deleteReadAlerts($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = $this->buildUser($authUser);
            
            if (!$user['company_id']) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            $perm = PermissionMiddleware::scope($user, 'alerts', 'a');
            
            $sql = "UPDATE alerts a SET deleted_at = :deleted_at, deleted_by = :deleted_by WHERE {$perm['where']} AND is_read = 1 AND deleted_at IS NULL";
            $paramsDb = array_merge($perm['params'], [
                ':deleted_at' => date('Y-m-d H:i:s'),
                ':deleted_by' => $user['id']
            ]);
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($paramsDb);
            $affected = $stmt->rowCount();
            
            return $this->success(['affected' => $affected], "{$affected} alertas lidos eliminados");
            
        } catch (Exception $e) {
            error_log("[ALERTS] deleteReadAlerts error: " . $e->getMessage());
            return $this->error('Erro ao eliminar alertas', null, 500);
        }
    }
    
    /**
     * Verificar documentos e criar alertas automáticos
     * POST /api/v1/alerts/check-documents
     */
    public function checkDocuments($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $user = $this->buildUser($authUser);
            
            if (!$user['company_id']) {
                return $this->error('Empresa não encontrada', null, 404);
            }
            
            if (!PermissionMiddleware::isAdmin($user)) {
                return $this->error('Sem permissão para executar esta ação', null, 403);
            }
            
            $created = $this->processDocumentAlerts($user['company_id']);
            
            return $this->success([
                'created' => $created,
                'total' => $created,
                'timestamp' => date('Y-m-d H:i:s')
            ], "{$created} alertas de documentos criados");
            
        } catch (Exception $e) {
            error_log("[ALERTS] checkDocuments error: " . $e->getMessage());
            return $this->error('Erro ao verificar documentos', null, 500);
        }
    }
    
    /**
     * Processar alertas automáticos
     */
    private function processAutomaticAlerts($companyId) {
        try {
            $this->processDocumentAlerts($companyId);
            // Adicionar outros tipos de alerta aqui
        } catch (Exception $e) {
            error_log("[ALERTS] processAutomaticAlerts error: " . $e->getMessage());
        }
    }
    
    /**
     * Processar alertas de documentos
     */
    private function processDocumentAlerts($companyId) {
        try {
            // Buscar documentos que vencem nos próximos 30 dias
            $documents = $this->db->fetchAll("
                SELECT d.id, d.company_id, d.title, d.expiry_date, d.uploaded_by, d.entity_type, d.entity_id,
                       CASE 
                           WHEN d.entity_type = 'vehicle' THEN CONCAT(v.brand, ' ', v.model, ' (', v.plate, ')')
                           WHEN d.entity_type = 'driver' THEN dr.name
                           WHEN d.entity_type = 'company' THEN c.name
                           ELSE NULL
                       END as entity_name
                FROM documents d
                LEFT JOIN vehicles v ON d.entity_type = 'vehicle' AND d.entity_id = v.id
                LEFT JOIN drivers dr ON d.entity_type = 'driver' AND d.entity_id = dr.id
                LEFT JOIN companies c ON d.entity_type = 'company' AND d.entity_id = c.id
                WHERE d.company_id = ?
                  AND d.expiry_date IS NOT NULL 
                  AND d.expiry_date >= CURDATE() 
                  AND d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                  AND d.status = 'valid'
                ORDER BY d.expiry_date ASC
            ", [$companyId]);
            
            $created = 0;
            foreach ($documents as $doc) {
                // Verificar se já existe alerta para este documento
                $existing = $this->db->fetchOne(
                    "SELECT id FROM alerts 
                     WHERE company_id = ? 
                       AND source_type = 'document' 
                       AND source_id = ? 
                       AND type = 'document' 
                       AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                       AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')",
                    [$companyId, $doc['id']]
                );
                
                if (!$existing) {
                    $daysRemaining = (int)ceil((strtotime($doc['expiry_date']) - time()) / 86400);
                    $entityInfo = !empty($doc['entity_name']) ? " ({$doc['entity_name']})" : "";
                    $severity = $daysRemaining <= 7 ? 'critical' : 'warning';
                    $daysText = $daysRemaining . ($daysRemaining == 1 ? ' dia' : ' dias');
                    
                    $title = "📄 Documento: {$doc['title']}{$entityInfo}";
                    $message = "O documento '{$doc['title']}' vence em {$daysText} (" . date('d/m/Y', strtotime($doc['expiry_date'])) . "){$entityInfo}";
                    
                    $alertId = $this->createAlert(
                        $doc['company_id'],
                        $title,
                        $message,
                        'document',
                        $severity,
                        $doc['uploaded_by'],
                        null,
                        'document',
                        $doc['id']
                    );
                    
                    if ($alertId) {
                        $created++;
                        error_log("[ALERTS] Alerta criado: {$doc['title']} (ID: {$alertId})");
                    }
                }
            }
            
            return $created;
        } catch (Exception $e) {
            error_log("[ALERTS] processDocumentAlerts error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Criar alerta
     */
    private function createAlert($companyId, $title, $message, $type, $severity, $createdBy = null, $userId = null, $sourceType = null, $sourceId = null) {
        try {
            $data = [
                'company_id' => $companyId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'severity' => $severity,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($createdBy) {
                $data['created_by'] = $createdBy;
            }
            
            return $this->db->insert('alerts', $data);
        } catch (Exception $e) {
            error_log("[ALERTS] createAlert error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Métodos auxiliares
     */
    private function buildUser($authUser) {
        return [
            'id' => $authUser['user_id'],
            'role' => $authUser['role'] ?? 'user',
            'company_id' => $this->getCompanyId($authUser['user_id'])
        ];
    }
    
    private function isValidId($id) {
        return !empty($id) && $id !== 'null' && $id !== 'undefined' && $id !== '';
    }
    
    private function emptyResponse($user) {
        return $this->success([
            'data' => [],
            'total' => 0,
            'page' => 1,
            'limit' => 50,
            'total_pages' => 0,
            'user_role' => $user['role'],
            'is_admin' => false,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function errorResponse($message) {
        return $this->success([
            'data' => [],
            'total' => 0,
            'page' => 1,
            'limit' => 50,
            'total_pages' => 0,
            'user_role' => 'user',
            'is_admin' => false,
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => $message
        ]);
    }
    
    private function getCompanyId($userId) {
        try {
            if (!$userId) {
                return null;
            }
            $result = $this->db->fetchOne("SELECT company_id FROM users WHERE id = ?", [$userId]);
            return $result ? (int)$result['company_id'] : null;
        } catch (Exception $e) {
            error_log("[ALERTS] getCompanyId error: " . $e->getMessage());
            return null;
        }
    }
}