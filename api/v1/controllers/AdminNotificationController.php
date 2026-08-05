<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';

class AdminNotificationController extends BaseController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Listar notificações admin
     * GET /api/v1/admin/notifications
     */
    public function index($input, $params) {
        $pagination = $this->getPaginationParams($params);
        $offset = ($pagination['page'] - 1) * $pagination['limit'];
        
        $notifications = $this->db->fetchAll("
            SELECT n.*, u.name as user_name, c.name as company_name
            FROM notifications n
            LEFT JOIN users u ON n.user_id = u.id
            LEFT JOIN companies c ON n.company_id = c.id
            ORDER BY n.created_at DESC
            LIMIT :limit OFFSET :offset
        ", [
            ':limit' => $pagination['limit'],
            ':offset' => $offset
        ]);
        
        $total = $this->db->fetchOne("SELECT COUNT(*) as total FROM notifications")['total'];
        
        return $this->success([
            'data' => $notifications,
            'pagination' => [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['limit'],
                'total' => (int)$total,
                'last_page' => ceil($total / $pagination['limit'])
            ]
        ]);
    }
    
    /**
     * Enviar notificação para usuário
     * POST /api/v1/admin/notifications/send
     */
    public function send($input, $params) {
        $required = ['user_id', 'title', 'message'];
        $validation = $this->validateRequired($input, $required);
        if ($validation !== true) return $validation;
        
        // Verificar se usuário existe
        $user = $this->db->fetchOne("SELECT id, company_id FROM users WHERE id = :id", [':id' => $input['user_id']]);
        if (!$user) {
            return $this->error('Usuário não encontrado', null, 404);
        }
        
        $notificationId = $this->db->insert('notifications', [
            'company_id' => $user['company_id'],
            'user_id' => $input['user_id'],
            'type' => $input['type'] ?? 'admin',
            'title' => $input['title'],
            'message' => $input['message'],
            'data' => isset($input['data']) ? json_encode($input['data']) : null,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->success(['id' => $notificationId], 'Notificação enviada com sucesso', 201);
    }
    
    /**
     * Enviar notificação para todos os usuários da empresa
     * POST /api/v1/admin/notifications/broadcast
     */
    public function broadcast($input, $params) {
        $required = ['title', 'message'];
        $validation = $this->validateRequired($input, $required);
        if ($validation !== true) return $validation;
        
        $companyId = $input['company_id'] ?? null;
        
        $where = "1=1";
        $whereParams = [];
        
        if ($companyId) {
            $where .= " AND company_id = :company_id";
            $whereParams[':company_id'] = $companyId;
        }
        
        $users = $this->db->fetchAll("SELECT id, company_id FROM users WHERE {$where}", $whereParams);
        
        $sent = 0;
        foreach ($users as $user) {
            $this->db->insert('notifications', [
                'company_id' => $user['company_id'],
                'user_id' => $user['id'],
                'type' => 'broadcast',
                'title' => $input['title'],
                'message' => $input['message'],
                'data' => isset($input['data']) ? json_encode($input['data']) : null,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $sent++;
        }
        
        return $this->success(['sent' => $sent], 'Notificações enviadas com sucesso');
    }
    
    /**
     * Templates de notificações
     * GET /api/v1/admin/notifications/templates
     */
    public function getTemplates($input, $params) {
        $templates = [
            [
                'id' => 'welcome',
                'title' => 'Bem-vindo ao sistema',
                'message' => 'Seja bem-vindo ao CARDOXIS! Estamos felizes em ter você conosco.',
                'type' => 'welcome'
            ],
            [
                'id' => 'maintenance_reminder',
                'title' => 'Manutenção programada',
                'message' => 'O veículo {plate} está com manutenção programada para {date}.',
                'type' => 'maintenance'
            ],
            [
                'id' => 'document_expiring',
                'title' => 'Documento próximo ao vencimento',
                'message' => 'O documento {document} vencerá em {days} dias.',
                'type' => 'document'
            ],
            [
                'id' => 'subscription_expiring',
                'title' => 'Assinatura próxima ao vencimento',
                'message' => 'Sua assinatura do plano {plan} vencerá em {days} dias.',
                'type' => 'subscription'
            ]
        ];
        
        return $this->success($templates);
    }
    
    /**
     * Criar template
     * POST /api/v1/admin/notifications/templates
     */
    public function createTemplate($input, $params) {
        $required = ['id', 'title', 'message', 'type'];
        $validation = $this->validateRequired($input, $required);
        if ($validation !== true) return $validation;
        
        // TODO: Salvar template no banco ou arquivo
        
        return $this->success(null, 'Template criado com sucesso', 201);
    }
}