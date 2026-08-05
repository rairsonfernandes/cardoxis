<?php
/**
 * CARDOXIS - Configuração de Permissões (RBAC)
 * 
 * Níveis de acesso:
 * - 'all'      → Acesso total (super admin)
 * - 'company'  → Acesso a tudo da empresa
 * - 'own'      → Apenas recursos que criou
 */

return [
    /**
     * PERMISSÕES DO ADMIN
     * O admin tem acesso a tudo
     */
    'admin' => [
        // Dashboard
        'dashboard.view' => PermissionMiddleware::LEVEL_ALL,
        'dashboard.stats.view' => PermissionMiddleware::LEVEL_ALL,
        'dashboard.notifications.view' => PermissionMiddleware::LEVEL_ALL,
        'dashboard.admin' => PermissionMiddleware::LEVEL_ALL,
        
        // Veículos
        'vehicles.view' => PermissionMiddleware::LEVEL_ALL,
        'vehicles.create' => PermissionMiddleware::LEVEL_ALL,
        'vehicles.edit' => PermissionMiddleware::LEVEL_ALL,
        'vehicles.delete' => PermissionMiddleware::LEVEL_ALL,
        'vehicles.export' => PermissionMiddleware::LEVEL_ALL,
        
        // Motoristas
        'drivers.view' => PermissionMiddleware::LEVEL_ALL,
        'drivers.create' => PermissionMiddleware::LEVEL_ALL,
        'drivers.edit' => PermissionMiddleware::LEVEL_ALL,
        'drivers.delete' => PermissionMiddleware::LEVEL_ALL,
        
        // Manutenções
        'maintenances.view' => PermissionMiddleware::LEVEL_ALL,
        'maintenances.create' => PermissionMiddleware::LEVEL_ALL,
        'maintenances.edit' => PermissionMiddleware::LEVEL_ALL,
        'maintenances.delete' => PermissionMiddleware::LEVEL_ALL,
        
        // Documentos
        'documents.view' => PermissionMiddleware::LEVEL_ALL,
        'documents.create' => PermissionMiddleware::LEVEL_ALL,
        'documents.edit' => PermissionMiddleware::LEVEL_ALL,
        'documents.delete' => PermissionMiddleware::LEVEL_ALL,
        
        // Alertas
        'alerts.view' => PermissionMiddleware::LEVEL_ALL,
        'alerts.create' => PermissionMiddleware::LEVEL_ALL,
        'alerts.edit' => PermissionMiddleware::LEVEL_ALL,
        'alerts.delete' => PermissionMiddleware::LEVEL_ALL,
        
        // Empresa
        'company.view' => PermissionMiddleware::LEVEL_ALL,
        'company.edit' => PermissionMiddleware::LEVEL_ALL,
        'company.users.view' => PermissionMiddleware::LEVEL_ALL,
        'company.users.manage' => PermissionMiddleware::LEVEL_ALL,
        
        // Configurações
        'settings.view' => PermissionMiddleware::LEVEL_ALL,
        'settings.edit' => PermissionMiddleware::LEVEL_ALL,
    ],
    
    /**
     * PERMISSÕES DO MANAGER (Gestor)
     * Vê tudo da empresa, mas não pode gerenciar usuários
     */
    'manager' => [
        // Dashboard
        'dashboard.view' => PermissionMiddleware::LEVEL_COMPANY,
        'dashboard.stats.view' => PermissionMiddleware::LEVEL_COMPANY,
        'dashboard.notifications.view' => PermissionMiddleware::LEVEL_COMPANY,
        'dashboard.admin' => PermissionMiddleware::LEVEL_OWN,
        
        // Veículos
        'vehicles.view' => PermissionMiddleware::LEVEL_COMPANY,
        'vehicles.create' => PermissionMiddleware::LEVEL_COMPANY,
        'vehicles.edit' => PermissionMiddleware::LEVEL_COMPANY,
        'vehicles.delete' => PermissionMiddleware::LEVEL_OWN,
        'vehicles.export' => PermissionMiddleware::LEVEL_COMPANY,
        
        // Motoristas
        'drivers.view' => PermissionMiddleware::LEVEL_COMPANY,
        'drivers.create' => PermissionMiddleware::LEVEL_COMPANY,
        'drivers.edit' => PermissionMiddleware::LEVEL_COMPANY,
        'drivers.delete' => PermissionMiddleware::LEVEL_OWN,
        
        // Manutenções
        'maintenances.view' => PermissionMiddleware::LEVEL_COMPANY,
        'maintenances.create' => PermissionMiddleware::LEVEL_COMPANY,
        'maintenances.edit' => PermissionMiddleware::LEVEL_COMPANY,
        'maintenances.delete' => PermissionMiddleware::LEVEL_OWN,
        
        // Documentos
        'documents.view' => PermissionMiddleware::LEVEL_COMPANY,
        'documents.create' => PermissionMiddleware::LEVEL_COMPANY,
        'documents.edit' => PermissionMiddleware::LEVEL_COMPANY,
        'documents.delete' => PermissionMiddleware::LEVEL_OWN,
        
        // Alertas
        'alerts.view' => PermissionMiddleware::LEVEL_COMPANY,
        'alerts.create' => PermissionMiddleware::LEVEL_COMPANY,
        'alerts.edit' => PermissionMiddleware::LEVEL_COMPANY,
        'alerts.delete' => PermissionMiddleware::LEVEL_OWN,
        
        // Empresa (limitado)
        'company.view' => PermissionMiddleware::LEVEL_COMPANY,
        'company.edit' => PermissionMiddleware::LEVEL_OWN,
        'company.users.view' => PermissionMiddleware::LEVEL_COMPANY,
        'company.users.manage' => PermissionMiddleware::LEVEL_OWN,
        
        // Configurações
        'settings.view' => PermissionMiddleware::LEVEL_COMPANY,
        'settings.edit' => PermissionMiddleware::LEVEL_OWN,
    ],
    
    /**
     * PERMISSÕES DO USER (Utilizador Comum)
     * Vê apenas o que criou
     */
    'user' => [
        // Dashboard
        'dashboard.view' => PermissionMiddleware::LEVEL_OWN,
        'dashboard.stats.view' => PermissionMiddleware::LEVEL_OWN,
        'dashboard.notifications.view' => PermissionMiddleware::LEVEL_OWN,
        'dashboard.admin' => PermissionMiddleware::LEVEL_OWN,
        
        // Veículos
        'vehicles.view' => PermissionMiddleware::LEVEL_OWN,
        'vehicles.create' => PermissionMiddleware::LEVEL_OWN,
        'vehicles.edit' => PermissionMiddleware::LEVEL_OWN,
        'vehicles.delete' => PermissionMiddleware::LEVEL_OWN,
        'vehicles.export' => PermissionMiddleware::LEVEL_OWN,
        
        // Motoristas
        'drivers.view' => PermissionMiddleware::LEVEL_OWN,
        'drivers.create' => PermissionMiddleware::LEVEL_OWN,
        'drivers.edit' => PermissionMiddleware::LEVEL_OWN,
        'drivers.delete' => PermissionMiddleware::LEVEL_OWN,
        
        // Manutenções
        'maintenances.view' => PermissionMiddleware::LEVEL_OWN,
        'maintenances.create' => PermissionMiddleware::LEVEL_OWN,
        'maintenances.edit' => PermissionMiddleware::LEVEL_OWN,
        'maintenances.delete' => PermissionMiddleware::LEVEL_OWN,
        
        // Documentos
        'documents.view' => PermissionMiddleware::LEVEL_OWN,
        'documents.create' => PermissionMiddleware::LEVEL_OWN,
        'documents.edit' => PermissionMiddleware::LEVEL_OWN,
        'documents.delete' => PermissionMiddleware::LEVEL_OWN,
        
        // Alertas
        'alerts.view' => PermissionMiddleware::LEVEL_OWN,
        'alerts.create' => PermissionMiddleware::LEVEL_OWN,
        'alerts.edit' => PermissionMiddleware::LEVEL_OWN,
        'alerts.delete' => PermissionMiddleware::LEVEL_OWN,
        
        // Empresa (apenas visualização básica)
        'company.view' => PermissionMiddleware::LEVEL_OWN,
        'company.edit' => PermissionMiddleware::LEVEL_OWN,
        'company.users.view' => PermissionMiddleware::LEVEL_OWN,
        'company.users.manage' => PermissionMiddleware::LEVEL_OWN,
        
        // Configurações (nenhuma)
        'settings.view' => PermissionMiddleware::LEVEL_OWN,
        'settings.edit' => PermissionMiddleware::LEVEL_OWN,
    ],
];