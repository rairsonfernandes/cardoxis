<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';

class SubscriptionController extends BaseController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obter assinatura atual
     * GET /api/v1/subscription
     */
    public function getSubscription($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $companyId = $this->getCompanyId($authUser['user_id']);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        // Buscar assinatura ativa
        $subscription = $this->db->fetchOne("
            SELECT 
                s.id,
                s.company_id,
                s.plan_id,
                s.status,
                s.starts_at,
                s.ends_at,
                s.canceled_at,
                s.trial_ends_at,
                s.payment_method,
                s.auto_renew,
                s.created_at,
                s.updated_at,
                p.name as plan_name,
                p.price,
                p.annual_price,
                p.currency,
                p.billing_interval,
                p.max_vehicles,
                p.max_drivers,
                p.max_storage_mb,
                p.features,
                p.description as plan_description
            FROM subscriptions s
            LEFT JOIN subscription_plans p ON s.plan_id = p.id
            WHERE s.company_id = :company_id 
                AND s.status IN ('active', 'trialing')
            ORDER BY s.id DESC 
            LIMIT 1
        ", [':company_id' => $companyId]);
        
        if (!$subscription) {
            // Verificar se existe assinatura expirada ou cancelada
            $lastSubscription = $this->db->fetchOne("
                SELECT 
                    s.*,
                    p.name as plan_name,
                    p.price,
                    p.features
                FROM subscriptions s
                LEFT JOIN subscription_plans p ON s.plan_id = p.id
                WHERE s.company_id = :company_id
                ORDER BY s.id DESC 
                LIMIT 1
            ", [':company_id' => $companyId]);
            
            if ($lastSubscription) {
                return $this->success([
                    'has_subscription' => false,
                    'last_subscription' => [
                        'plan_name' => $lastSubscription['plan_name'],
                        'status' => $lastSubscription['status'],
                        'ended_at' => $lastSubscription['ends_at'] ?? $lastSubscription['canceled_at'],
                        'message' => $this->getStatusMessage($lastSubscription['status'])
                    ]
                ]);
            }
            
            return $this->success([
                'has_subscription' => false,
                'message' => 'Nenhuma assinatura encontrada'
            ]);
        }
        
        // Processar features
        if ($subscription['features']) {
            $features = json_decode($subscription['features'], true);
            $subscription['features'] = is_array($features) ? $features : [];
        } else {
            $subscription['features'] = [];
        }
        
        // Calcular dias restantes
        if ($subscription['ends_at']) {
            $endDate = new DateTime($subscription['ends_at']);
            $now = new DateTime();
            $subscription['days_remaining'] = $now->diff($endDate)->days;
        } else {
            $subscription['days_remaining'] = null;
        }
        
        // Verificar se está em período de teste
        if ($subscription['trial_ends_at']) {
            $trialEnd = new DateTime($subscription['trial_ends_at']);
            $now = new DateTime();
            $subscription['is_trial'] = $now < $trialEnd;
            $subscription['trial_days_remaining'] = $now->diff($trialEnd)->days;
        } else {
            $subscription['is_trial'] = false;
            $subscription['trial_days_remaining'] = 0;
        }
        
        // Buscar uso atual
        $usage = $this->getCurrentUsage($companyId);
        $subscription['current_usage'] = $usage;
        
        return $this->success([
            'has_subscription' => true,
            'subscription' => $subscription
        ]);
    }
    
    /**
     * Listar planos disponíveis
     * GET /api/v1/subscription/plans
     */
    public function getPlans($input, $params) {
        // Buscar planos do banco de dados
        $plans = $this->db->fetchAll("
            SELECT 
                id,
                name,
                description,
                price,
                annual_price,
                currency,
                billing_interval,
                trial_days,
                max_vehicles,
                max_drivers,
                max_storage_mb,
                features,
                is_active,
                sort_order,
                created_at,
                updated_at
            FROM subscription_plans 
            WHERE is_active = 1 
            ORDER BY sort_order ASC, price ASC
        ");
        
        // Se não houver planos cadastrados, retornar array vazio
        if (!$plans || empty($plans)) {
            return $this->success([], 'Nenhum plano disponível no momento');
        }
        
        // Processar features de cada plano
        foreach ($plans as &$plan) {
            if (isset($plan['features']) && $plan['features']) {
                $decoded = json_decode($plan['features'], true);
                $plan['features'] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) 
                    ? $decoded 
                    : [];
            } else {
                $plan['features'] = [];
            }
            
            // Adicionar informações adicionais
            $plan['is_popular'] = $plan['sort_order'] == 2; // Plano do meio é popular
            $plan['price_formatted'] = number_format($plan['price'], 2, ',', '.') . ' ' . ($plan['currency'] ?? '€');
            
            if ($plan['annual_price']) {
                $plan['annual_price_formatted'] = number_format($plan['annual_price'], 2, ',', '.') . ' ' . ($plan['currency'] ?? '€');
                $plan['annual_savings'] = ($plan['price'] * 12) - $plan['annual_price'];
                $plan['annual_savings_percent'] = round((($plan['price'] * 12 - $plan['annual_price']) / ($plan['price'] * 12)) * 100);
            } else {
                $plan['annual_price_formatted'] = null;
                $plan['annual_savings'] = 0;
                $plan['annual_savings_percent'] = 0;
            }
        }
        
        return $this->success($plans);
    }
    
    /**
     * Fazer upgrade de plano
     * POST /api/v1/subscription/upgrade
     */
    public function upgrade($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $planId = $input['plan_id'] ?? null;
        $billingCycle = $input['billing_cycle'] ?? 'monthly'; // monthly, annual
        
        if (!$planId) {
            return $this->error('ID do plano obrigatório', null, 400);
        }
        
        // Buscar plano no banco de dados
        $plan = $this->db->fetchOne("
            SELECT 
                id,
                name,
                description,
                price,
                annual_price,
                currency,
                billing_interval,
                trial_days,
                max_vehicles,
                max_drivers,
                max_storage_mb,
                features
            FROM subscription_plans 
            WHERE id = :id AND is_active = 1
        ", [':id' => $planId]);
        
        if (!$plan) {
            return $this->error('Plano não encontrado ou inativo', null, 404);
        }
        
        $companyId = $this->getCompanyId($authUser['user_id']);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        // Verificar se já existe assinatura ativa
        $existingSubscription = $this->db->fetchOne("
            SELECT id, plan_id, status 
            FROM subscriptions 
            WHERE company_id = :company_id 
                AND status IN ('active', 'trialing')
            ORDER BY id DESC 
            LIMIT 1
        ", [':company_id' => $companyId]);
        
        // Calcular valor a ser cobrado
        $amount = ($billingCycle === 'annual' && $plan['annual_price']) 
            ? $plan['annual_price'] 
            : $plan['price'];
        
        $startsAt = date('Y-m-d H:i:s');
        $trialEndsAt = null;
        
        // Se for primeira assinatura ou upgrade, pode ter período de teste
        if (!$existingSubscription && $plan['trial_days'] > 0) {
            $trialEndsAt = date('Y-m-d H:i:s', strtotime("+{$plan['trial_days']} days"));
        }
        
        // Iniciar transação
        $this->db->beginTransaction();
        
        try {
            // Cancelar assinatura atual se existir
            if ($existingSubscription) {
                $this->db->update('subscriptions', 
                    [
                        'status' => 'canceled', 
                        'ends_at' => date('Y-m-d H:i:s'),
                        'canceled_at' => date('Y-m-d H:i:s')
                    ], 
                    'id = :id', 
                    [':id' => $existingSubscription['id']]
                );
            }
            
            // Criar nova assinatura
            $subscriptionId = $this->db->insert('subscriptions', [
                'company_id' => $companyId,
                'plan_id' => $planId,
                'status' => $trialEndsAt ? 'trialing' : 'active',
                'starts_at' => $startsAt,
                'trial_ends_at' => $trialEndsAt,
                'payment_method' => $input['payment_method'] ?? null,
                'auto_renew' => $input['auto_renew'] ?? true,
                'created_by' => $authUser['user_id']
            ]);
            
            // Registrar pagamento
            if ($trialEndsAt === null) {
                $paymentId = $this->db->insert('payments', [
                    'company_id' => $companyId,
                    'subscription_id' => $subscriptionId,
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'amount' => $amount,
                    'status' => 'pending',
                    'payment_method' => $input['payment_method'] ?? 'credit_card',
                    'gateway' => $input['gateway'] ?? null,
                    'created_by' => $authUser['user_id']
                ]);
            }
            
            // Atualizar limites da empresa
            $this->db->update('companies', [
                'max_vehicles' => $plan['max_vehicles'],
                'max_drivers' => $plan['max_drivers'],
                'max_storage_mb' => $plan['max_storage_mb']
            ], 'id = :id', [':id' => $companyId]);
            
            // Registrar log de atividade
            $this->db->insert('activity_logs', [
                'user_id' => $authUser['user_id'],
                'company_id' => $companyId,
                'action' => 'subscription_upgrade',
                'description' => "Upgrade para plano '{$plan['name']}'",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->commit();
            
            return $this->success([
                'subscription_id' => $subscriptionId,
                'plan' => [
                    'id' => $plan['id'],
                    'name' => $plan['name'],
                    'price' => $amount
                ],
                'status' => $trialEndsAt ? 'trialing' : 'active',
                'trial_ends_at' => $trialEndsAt,
                'starts_at' => $startsAt,
                'is_trial' => $trialEndsAt !== null,
                'payment_id' => $paymentId ?? null
            ], 'Plano atualizado com sucesso');
            
        } catch (Exception $e) {
            $this->db->rollback();
            return $this->error('Erro ao processar upgrade: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Fazer downgrade de plano
     * POST /api/v1/subscription/downgrade
     */
    public function downgrade($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $planId = $input['plan_id'] ?? null;
        
        if (!$planId) {
            return $this->error('ID do plano obrigatório', null, 400);
        }
        
        // Buscar plano no banco de dados
        $plan = $this->db->fetchOne("
            SELECT id, name, price, max_vehicles, max_drivers, max_storage_mb
            FROM subscription_plans 
            WHERE id = :id AND is_active = 1
        ", [':id' => $planId]);
        
        if (!$plan) {
            return $this->error('Plano não encontrado', null, 404);
        }
        
        $companyId = $this->getCompanyId($authUser['user_id']);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        // Verificar assinatura atual
        $currentSubscription = $this->db->fetchOne("
            SELECT id, plan_id, status 
            FROM subscriptions 
            WHERE company_id = :company_id 
                AND status IN ('active', 'trialing')
            ORDER BY id DESC 
            LIMIT 1
        ", [':company_id' => $companyId]);
        
        if (!$currentSubscription) {
            return $this->error('Nenhuma assinatura ativa encontrada', null, 404);
        }
        
        // Verificar se o plano é realmente downgrade
        $currentPlan = $this->db->fetchOne("
            SELECT price, max_vehicles, max_drivers 
            FROM subscription_plans 
            WHERE id = :id
        ", [':id' => $currentSubscription['plan_id']]);
        
        if ($currentPlan && $plan['price'] >= $currentPlan['price']) {
            return $this->error('Este não é um downgrade. O plano é igual ou mais caro.', null, 400);
        }
        
        // Iniciar transação
        $this->db->beginTransaction();
        
        try {
            // Agendar downgrade (manter assinatura atual até o fim do ciclo)
            $this->db->update('subscriptions', [
                'downgrade_to_plan_id' => $planId,
                'downgrade_scheduled_at' => date('Y-m-d H:i:s')
            ], 'id = :id', [':id' => $currentSubscription['id']]);
            
            // Registrar log
            $this->db->insert('activity_logs', [
                'user_id' => $authUser['user_id'],
                'company_id' => $companyId,
                'action' => 'subscription_downgrade_scheduled',
                'description' => "Downgrade para plano '{$plan['name']}' agendado",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->commit();
            
            return $this->success([
                'message' => 'Downgrade agendado para o próximo ciclo de cobrança',
                'current_plan' => $currentSubscription['plan_id'],
                'new_plan' => $planId,
                'effective_date' => date('Y-m-d H:i:s', strtotime('+1 month'))
            ], 'Downgrade agendado com sucesso');
            
        } catch (Exception $e) {
            $this->db->rollback();
            return $this->error('Erro ao processar downgrade: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Cancelar assinatura
     * POST /api/v1/subscription/cancel
     */
    public function cancel($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $companyId = $this->getCompanyId($authUser['user_id']);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        // Buscar assinatura ativa
        $subscription = $this->db->fetchOne("
            SELECT id, plan_id, status, ends_at
            FROM subscriptions 
            WHERE company_id = :company_id 
                AND status IN ('active', 'trialing')
            ORDER BY id DESC 
            LIMIT 1
        ", [':company_id' => $companyId]);
        
        if (!$subscription) {
            return $this->error('Nenhuma assinatura ativa encontrada', null, 404);
        }
        
        // Cancelar assinatura
        $this->db->update('subscriptions', [
            'status' => 'canceled',
            'canceled_at' => date('Y-m-d H:i:s'),
            'ends_at' => date('Y-m-d H:i:s')
        ], 'id = :id', [':id' => $subscription['id']]);
        
        // Atualizar limites da empresa para valor padrão
        $this->db->update('companies', [
            'max_vehicles' => 5,
            'max_drivers' => 3,
            'max_storage_mb' => 1024
        ], 'id = :id', [':id' => $companyId]);
        
        // Registrar log
        $this->db->insert('activity_logs', [
            'user_id' => $authUser['user_id'],
            'company_id' => $companyId,
            'action' => 'subscription_canceled',
            'description' => 'Assinatura cancelada',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->success([
            'subscription_id' => $subscription['id'],
            'canceled_at' => date('Y-m-d H:i:s'),
            'message' => 'Sua assinatura foi cancelada. Você terá acesso até o final do período atual.'
        ], 'Assinatura cancelada com sucesso');
    }
    
    /**
     * Reativar assinatura
     * POST /api/v1/subscription/reactivate
     */
    public function reactivate($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $companyId = $this->getCompanyId($authUser['user_id']);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        // Buscar última assinatura cancelada
        $lastSubscription = $this->db->fetchOne("
            SELECT id, plan_id, status, canceled_at
            FROM subscriptions 
            WHERE company_id = :company_id 
                AND status = 'canceled'
            ORDER BY id DESC 
            LIMIT 1
        ", [':company_id' => $companyId]);
        
        if (!$lastSubscription) {
            return $this->error('Nenhuma assinatura cancelada encontrada para reativar', null, 404);
        }
        
        // Buscar plano
        $plan = $this->db->fetchOne("
            SELECT id, name, max_vehicles, max_drivers, max_storage_mb
            FROM subscription_plans 
            WHERE id = :id AND is_active = 1
        ", [':id' => $lastSubscription['plan_id']]);
        
        if (!$plan) {
            return $this->error('Plano não está mais disponível', null, 400);
        }
        
        // Reativar assinatura
        $this->db->update('subscriptions', [
            'status' => 'active',
            'canceled_at' => null,
            'ends_at' => date('Y-m-d H:i:s', strtotime('+1 month'))
        ], 'id = :id', [':id' => $lastSubscription['id']]);
        
        // Atualizar limites da empresa
        $this->db->update('companies', [
            'max_vehicles' => $plan['max_vehicles'],
            'max_drivers' => $plan['max_drivers'],
            'max_storage_mb' => $plan['max_storage_mb']
        ], 'id = :id', [':id' => $companyId]);
        
        // Registrar log
        $this->db->insert('activity_logs', [
            'user_id' => $authUser['user_id'],
            'company_id' => $companyId,
            'action' => 'subscription_reactivated',
            'description' => "Assinatura reativada - Plano '{$plan['name']}'",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->success([
            'subscription_id' => $lastSubscription['id'],
            'plan' => $plan['name'],
            'status' => 'active',
            'reactivated_at' => date('Y-m-d H:i:s')
        ], 'Assinatura reativada com sucesso');
    }
    
    /**
     * Listar faturas
     * GET /api/v1/subscription/invoices
     */
    public function getInvoices($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $companyId = $this->getCompanyId($authUser['user_id']);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        // Verificar se tabela payments existe
        $tableExists = $this->db->fetchOne("
            SELECT COUNT(*) as count 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = 'payments'
        ");
        
        if (!$tableExists || $tableExists['count'] == 0) {
            return $this->success([], 'Nenhuma fatura encontrada');
        }
        
        // Buscar faturas com paginação
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 20;
        $offset = ($page - 1) * $limit;
        
        $invoices = $this->db->fetchAll("
            SELECT 
                p.id,
                p.invoice_number,
                p.amount,
                p.status,
                p.payment_method,
                p.payment_date,
                p.transaction_id,
                p.gateway,
                p.created_at,
                s.plan_id,
                sp.name as plan_name
            FROM payments p
            LEFT JOIN subscriptions s ON p.subscription_id = s.id
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE p.company_id = :company_id
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ", [
            ':company_id' => $companyId,
            ':limit' => $limit,
            ':offset' => $offset
        ]);
        
        // Total de registros
        $total = $this->db->fetchOne("
            SELECT COUNT(*) as total 
            FROM payments 
            WHERE company_id = :company_id
        ", [':company_id' => $companyId]);
        
        return $this->success([
            'data' => $invoices,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total['total'] ?? 0,
                'total_pages' => ceil(($total['total'] ?? 0) / $limit)
            ]
        ]);
    }
    
    /**
     * Histórico de assinaturas
     * GET /api/v1/subscription/history
     */
    public function getHistory($input, $params) {
        $authUser = $this->getAuthUser();
        
        if (!$authUser) {
            return $this->error('Não autenticado', null, 401);
        }
        
        $companyId = $this->getCompanyId($authUser['user_id']);
        
        if (!$companyId) {
            return $this->error('Empresa não encontrada', null, 404);
        }
        
        $history = $this->db->fetchAll("
            SELECT 
                s.id,
                s.status,
                s.starts_at,
                s.ends_at,
                s.canceled_at,
                s.trial_ends_at,
                s.created_at,
                p.id as plan_id,
                p.name as plan_name,
                p.price,
                p.currency
            FROM subscriptions s
            JOIN subscription_plans p ON s.plan_id = p.id
            WHERE s.company_id = :company_id
            ORDER BY s.created_at DESC
        ", [':company_id' => $companyId]);
        
        return $this->success($history);
    }
    
    /**
     * Obter company_id do usuário
     */
    private function getCompanyId($userId) {
        $result = $this->db->fetchOne(
            "SELECT company_id FROM users WHERE id = :user_id", 
            [':user_id' => $userId]
        );
        return $result ? $result['company_id'] : null;
    }
    
    /**
     * Obter uso atual da empresa
     */
    private function getCurrentUsage($companyId) {
        $vehicles = $this->db->fetchOne("
            SELECT COUNT(*) as count 
            FROM vehicles 
            WHERE company_id = :company_id AND deleted_at IS NULL
        ", [':company_id' => $companyId]);
        
        $drivers = $this->db->fetchOne("
            SELECT COUNT(*) as count 
            FROM drivers 
            WHERE company_id = :company_id AND deleted_at IS NULL
        ", [':company_id' => $companyId]);
        
        $storage = $this->db->fetchOne("
            SELECT IFNULL(SUM(file_size), 0) as total 
            FROM documents 
            WHERE company_id = :company_id AND deleted_at IS NULL
        ", [':company_id' => $companyId]);
        
        return [
            'vehicles' => $vehicles['count'] ?? 0,
            'drivers' => $drivers['count'] ?? 0,
            'storage_mb' => round(($storage['total'] ?? 0) / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Gerar número de fatura
     */
    private function generateInvoiceNumber() {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        
        $last = $this->db->fetchOne("
            SELECT MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) as last_num
            FROM payments 
            WHERE invoice_number LIKE :pattern
        ", [':pattern' => "{$prefix}-{$year}{$month}-%"]);
        
        $next = ($last['last_num'] ?? 0) + 1;
        return "{$prefix}-{$year}{$month}-" . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Obter mensagem de status
     */
    private function getStatusMessage($status) {
        $messages = [
            'active' => 'Assinatura ativa',
            'trialing' => 'Período de teste',
            'past_due' => 'Pagamento em atraso',
            'canceled' => 'Assinatura cancelada',
            'incomplete' => 'Assinatura incompleta',
            'expired' => 'Assinatura expirada'
        ];
        
        return $messages[$status] ?? 'Status desconhecido';
    }
}