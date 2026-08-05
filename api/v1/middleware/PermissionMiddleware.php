<?php
/**
 * CARDOXIS - Permission Middleware RF
 */

class PermissionMiddleware {
    
    private static $allowedTables = [
        'vehicles', 'drivers', 'documents', 'maintenances', 
        'alerts', 'notifications', 'insurances', 'insurance_claims',
        'fines', 'fuel_entries', 'users', 'companies',
        'reports', 'report_schedules', 'subscriptions', 
        'payments', 'audit_logs', 'activity_logs', 'api_tokens',
        'routes', 'deliveries', 'customers'
    ];
    
    private static $allowedFields = ['created_by', 'user_id', 'company_id'];
    
    /**
     * Verificar se o usuário é administrador
     */
    public static function isAdmin($user) {
        if (!isset($user['role'])) {
            return false;
        }
        return in_array($user['role'], ['admin', 'super_admin']);
    }
    
    /**
     * Validar se a tabela é permitida
     */
    private static function isTableAllowed($table) {
        return in_array($table, self::$allowedTables);
    }
    
    /**
     * Método SCOPE - Retorna WHERE e params para consultas
     * CORRIGIDO: Apenas admin vê tudo, user normal vê apenas o que criou
     */
    public static function scope($user, $table, $alias = null, $field = 'created_by') {
        // Validar tabela
        if (!self::isTableAllowed($table)) {
            error_log("[PermissionMiddleware] Tabela não permitida: " . $table);
            return [
                'where' => '1=0',
                'params' => []
            ];
        }
        
        $prefix = $alias ? "{$alias}." : "";
        $conditions = [];
        $params = [];
        
        // ADMINS: Veem apenas dados da sua empresa (mas todos os usuários da empresa)
        // USERS NORMAIS: Veem apenas dados que criaram
        if (isset($user['company_id']) && !empty($user['company_id'])) {
            // Todos os usuários (admin ou não) só veem dados da sua empresa
            $conditions[] = "{$prefix}company_id = :company_id";
            $params[':company_id'] = $user['company_id'];
        }
        
        // Se NÃO for admin, filtrar por ownership (apenas o que criou)
        if (!self::isAdmin($user)) {
            if (isset($user['id']) && !empty($user['id'])) {
                $conditions[] = "{$prefix}{$field} = :user_id";
                $params[':user_id'] = $user['id'];
            }
        }
        // Se for ADMIN, NÃO adiciona filtro de ownership - vê tudo da empresa
        
        // Se não houver condições, retornar 1=0 (sem acesso)
        if (empty($conditions)) {
            return [
                'where' => '1=0',
                'params' => []
            ];
        }
        
        // Soft delete
        try {
            $db = Database::getInstance();
            $columns = $db->fetchAll("SHOW COLUMNS FROM {$table} LIKE 'deleted_at'");
            if (!empty($columns)) {
                $conditions[] = "({$prefix}deleted_at IS NULL OR {$prefix}deleted_at = '0000-00-00 00:00:00')";
            }
        } catch (Exception $e) {
            // Ignorar
        }
        
        return [
            'where' => implode(' AND ', $conditions),
            'params' => $params
        ];
    }
    
    /**
     * Verificar permissão para um recurso específico
     * CORRIGIDO: Admin vê tudo, user normal vê apenas o que criou
     */
    public static function check($user, $table, $id, $field = 'created_by') {
        // Admin tem acesso a tudo (da sua empresa)
        if (self::isAdmin($user)) {
            return true;
        }
        
        if (!self::isTableAllowed($table)) {
            return false;
        }
        
        try {
            $db = Database::getInstance();
            
            // Buscar o recurso
            $sql = "SELECT {$field}, company_id FROM {$table} WHERE id = ?";
            $result = $db->fetchOne($sql, [$id]);
            
            if (!$result) {
                return false;
            }
            
            // Verificar empresa (todos os usuários só veem da sua empresa)
            if (isset($user['company_id']) && !empty($user['company_id'])) {
                if ($result['company_id'] != $user['company_id']) {
                    return false;
                }
            }
            
            // Verificar ownership (se não for admin)
            if (!self::isAdmin($user)) {
                if (isset($result[$field]) && $result[$field] != $user['id']) {
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("[PermissionMiddleware] check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar se o usuário pode acessar um módulo
     */
    public static function canAccessModule($user, $module) {
        if (self::isAdmin($user)) {
            return true;
        }
        
        $allowedModules = [
            'dashboard', 'vehicles', 'drivers', 'fuel', 
            'insurance', 'fines', 'documents', 'maintenance',
            'alerts', 'profile', 'reports'
        ];
        
        return in_array($module, $allowedModules);
    }
    
    /**
     * Obter empresa do usuário
     */
    public static function getCompanyId($user) {
        return $user['company_id'] ?? null;
    }
    
    /**
     * Verificar se o usuário pode ver dados de outro usuário
     */
    public static function canViewUserData($user, $targetUserId) {
        if (self::isAdmin($user)) {
            return true;
        }
        return $user['id'] == $targetUserId;
    }
}