<?php
/**
 * Admin API Routes Configuration RF
 */

return [
    // ==================== ADMIN AUTH ====================
    'POST /admin/auth/login' => [
        'method' => 'POST',
        'controller' => AdminAuthController::class,
        'action' => 'login',
        'middleware' => []
    ],
    'POST /admin/auth/logout' => [
        'method' => 'POST',
        'controller' => AdminAuthController::class,
        'action' => 'logout',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/auth/refresh' => [
        'method' => 'POST',
        'controller' => AdminAuthController::class,
        'action' => 'refresh',
        'middleware' => []
    ],
    'GET /admin/auth/me' => [
        'method' => 'GET',
        'controller' => AdminAuthController::class,
        'action' => 'getMe',
        'middleware' => ['AdminAuth']
    ],
    
    // ==================== ADMIN DASHBOARD ====================
    'GET /admin/dashboard' => [
        'method' => 'GET',
        'controller' => AdminDashboardController::class,
        'action' => 'index',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/dashboard/stats' => [
        'method' => 'GET',
        'controller' => AdminDashboardController::class,
        'action' => 'getStats',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/dashboard/revenue' => [
        'method' => 'GET',
        'controller' => AdminDashboardController::class,
        'action' => 'getRevenue',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/dashboard/users-growth' => [
        'method' => 'GET',
        'controller' => AdminDashboardController::class,
        'action' => 'getUsersGrowth',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/dashboard/system-health' => [
        'method' => 'GET',
        'controller' => AdminDashboardController::class,
        'action' => 'getSystemHealth',
        'middleware' => ['AdminAuth']
    ],
    
    // ==================== ADMIN COMPANIES ====================
    // IMPORTANTE: Rotas fixas ANTES das rotas com {id}
    'GET /admin/companies/export' => [
        'method' => 'GET',
        'controller' => AdminCompanyController::class,
        'action' => 'export',
        'middleware' => ['AdminAuth']
    ],
    
    'GET /admin/companies' => [
        'method' => 'GET',
        'controller' => AdminCompanyController::class,
        'action' => 'index',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/companies' => [
        'method' => 'POST',
        'controller' => AdminCompanyController::class,
        'action' => 'create',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/companies/{id}' => [
        'method' => 'GET',
        'controller' => AdminCompanyController::class,
        'action' => 'show',
        'middleware' => ['AdminAuth']
    ],
    'PUT /admin/companies/{id}' => [
        'method' => 'PUT',
        'controller' => AdminCompanyController::class,
        'action' => 'update',
        'middleware' => ['AdminAuth']
    ],
    'DELETE /admin/companies/{id}' => [
        'method' => 'DELETE',
        'controller' => AdminCompanyController::class,
        'action' => 'delete',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/companies/{id}/activate' => [
        'method' => 'POST',
        'controller' => AdminCompanyController::class,
        'action' => 'activate',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/companies/{id}/suspend' => [
        'method' => 'POST',
        'controller' => AdminCompanyController::class,
        'action' => 'suspend',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/companies/{id}/users' => [
        'method' => 'GET',
        'controller' => AdminCompanyController::class,
        'action' => 'getUsers',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/companies/{id}/subscriptions' => [
        'method' => 'GET',
        'controller' => AdminCompanyController::class,
        'action' => 'getSubscriptions',
        'middleware' => ['AdminAuth']
    ],
    
    // ==================== ADMIN USERS ====================
    // IMPORTANTE: Rotas fixas ANTES das rotas com {id}
    'GET /admin/users/export' => [
        'method' => 'GET',
        'controller' => AdminUserController::class,
        'action' => 'export',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/users/import' => [
        'method' => 'POST',
        'controller' => AdminUserController::class,
        'action' => 'import',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/users/roles' => [
        'method' => 'GET',
        'controller' => AdminUserController::class,
        'action' => 'getRoles',
        'middleware' => ['AdminAuth']
    ],
    
    'GET /admin/users' => [
        'method' => 'GET',
        'controller' => AdminUserController::class,
        'action' => 'index',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/users' => [
        'method' => 'POST',
        'controller' => AdminUserController::class,
        'action' => 'create',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/users/{id}' => [
        'method' => 'GET',
        'controller' => AdminUserController::class,
        'action' => 'show',
        'middleware' => ['AdminAuth']
    ],
    'PUT /admin/users/{id}' => [
        'method' => 'PUT',
        'controller' => AdminUserController::class,
        'action' => 'update',
        'middleware' => ['AdminAuth']
    ],
    'DELETE /admin/users/{id}' => [
        'method' => 'DELETE',
        'controller' => AdminUserController::class,
        'action' => 'delete',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/users/{id}/activate' => [
        'method' => 'POST',
        'controller' => AdminUserController::class,
        'action' => 'activate',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/users/{id}/suspend' => [
        'method' => 'POST',
        'controller' => AdminUserController::class,
        'action' => 'suspend',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/users/{id}/reset-password' => [
        'method' => 'POST',
        'controller' => AdminUserController::class,
        'action' => 'resetPassword',
        'middleware' => ['AdminAuth']
    ],
    'PUT /admin/users/{id}/role' => [
        'method' => 'PUT',
        'controller' => AdminUserController::class,
        'action' => 'updateRole',
        'middleware' => ['AdminAuth']
    ],


    // ==================== ADMIN USERS ====================
    'POST /admin/users/import' => [
        'method' => 'POST',
        'controller' => AdminUserController::class,
        'action' => 'import',
        'middleware' => ['AdminAuth']
    ],
        
    // ==================== ADMIN PLANS ====================
    'GET /admin/plans' => [
        'method' => 'GET',
        'controller' => AdminPlanController::class,
        'action' => 'index',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/plans' => [
        'method' => 'POST',
        'controller' => AdminPlanController::class,
        'action' => 'create',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/plans/{id}' => [
        'method' => 'GET',
        'controller' => AdminPlanController::class,
        'action' => 'show',
        'middleware' => ['AdminAuth']
    ],
    'PUT /admin/plans/{id}' => [
        'method' => 'PUT',
        'controller' => AdminPlanController::class,
        'action' => 'update',
        'middleware' => ['AdminAuth']
    ],
    'DELETE /admin/plans/{id}' => [
        'method' => 'DELETE',
        'controller' => AdminPlanController::class,
        'action' => 'delete',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/plans/{id}/features' => [
        'method' => 'POST',
        'controller' => AdminPlanController::class,
        'action' => 'addFeatures',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/plans/features' => [
        'method' => 'GET',
        'controller' => AdminPlanController::class,
        'action' => 'getFeatures',
        'middleware' => ['AdminAuth']
    ],
    
    // ==================== ADMIN SUBSCRIPTIONS ====================
    'GET /admin/subscriptions' => [
        'method' => 'GET',
        'controller' => AdminSubscriptionController::class,
        'action' => 'index',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/subscriptions/{id}' => [
        'method' => 'GET',
        'controller' => AdminSubscriptionController::class,
        'action' => 'show',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/subscriptions/{id}/cancel' => [
        'method' => 'POST',
        'controller' => AdminSubscriptionController::class,
        'action' => 'cancel',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/subscriptions/{id}/reactivate' => [
        'method' => 'POST',
        'controller' => AdminSubscriptionController::class,
        'action' => 'reactivate',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/subscriptions/{id}/change-plan' => [
        'method' => 'POST',
        'controller' => AdminSubscriptionController::class,
        'action' => 'changePlan',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/subscriptions/export' => [
        'method' => 'GET',
        'controller' => AdminSubscriptionController::class,
        'action' => 'export',
        'middleware' => ['AdminAuth']
    ],
    
    // ==================== ADMIN PAYMENTS ====================
    'GET /admin/payments' => [
        'method' => 'GET',
        'controller' => AdminPaymentController::class,
        'action' => 'index',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/payments/{id}' => [
        'method' => 'GET',
        'controller' => AdminPaymentController::class,
        'action' => 'show',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/payments/{id}/refund' => [
        'method' => 'POST',
        'controller' => AdminPaymentController::class,
        'action' => 'refund',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/payments/export' => [
        'method' => 'GET',
        'controller' => AdminPaymentController::class,
        'action' => 'export',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/payments/revenue' => [
        'method' => 'GET',
        'controller' => AdminPaymentController::class,
        'action' => 'getRevenue',
        'middleware' => ['AdminAuth']
    ],
    
    // ==================== ADMIN NOTIFICATIONS ====================
    'GET /admin/notifications' => [
        'method' => 'GET',
        'controller' => AdminNotificationController::class,
        'action' => 'index',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/notifications/send' => [
        'method' => 'POST',
        'controller' => AdminNotificationController::class,
        'action' => 'send',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/notifications/broadcast' => [
        'method' => 'POST',
        'controller' => AdminNotificationController::class,
        'action' => 'broadcast',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/notifications/templates' => [
        'method' => 'GET',
        'controller' => AdminNotificationController::class,
        'action' => 'getTemplates',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/notifications/templates' => [
        'method' => 'POST',
        'controller' => AdminNotificationController::class,
        'action' => 'createTemplate',
        'middleware' => ['AdminAuth']
    ],
    
    // ==================== ADMIN LOGS ====================
'GET /admin/logs/audit' => [
    'method' => 'GET',
    'controller' => AdminLogController::class,
    'action' => 'getAuditLogs',
    'middleware' => ['AdminAuth']
],
'GET /admin/logs/login' => [
    'method' => 'GET',
    'controller' => AdminLogController::class,
    'action' => 'getLoginLogs',
    'middleware' => ['AdminAuth']
],
'GET /admin/logs/activity' => [
    'method' => 'GET',
    'controller' => AdminLogController::class,
    'action' => 'getActivityLogs',
    'middleware' => ['AdminAuth']
],
'GET /admin/logs/api' => [
    'method' => 'GET',
    'controller' => AdminLogController::class,
    'action' => 'getApiLogs',
    'middleware' => ['AdminAuth']
],
'GET /admin/logs/errors' => [
    'method' => 'GET',
    'controller' => AdminLogController::class,
    'action' => 'getErrorLogs',
    'middleware' => ['AdminAuth']
],
'GET /admin/logs/security' => [
    'method' => 'GET',
    'controller' => AdminLogController::class,
    'action' => 'getSecurityLogs',
    'middleware' => ['AdminAuth']
],
'GET /admin/logs/export' => [
    'method' => 'GET',
    'controller' => AdminLogController::class,
    'action' => 'export',
    'middleware' => ['AdminAuth']
],
'DELETE /admin/logs/clear' => [
    'method' => 'DELETE',
    'controller' => AdminLogController::class,
    'action' => 'clearLogs',
    'middleware' => ['AdminAuth']
],
'GET /admin/logs/stats' => [
    'method' => 'GET',
    'controller' => AdminLogController::class,
    'action' => 'getStats',
    'middleware' => ['AdminAuth']
],
    
   // ==================== ADMIN SYSTEM ====================
    'GET /admin/system/health' => [
        'method' => 'GET',
        'controller' => AdminSystemController::class,
        'action' => 'getHealth',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/system/stats' => [
        'method' => 'GET',
        'controller' => AdminSystemController::class,
        'action' => 'getStats',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/system/info' => [
        'method' => 'GET',
        'controller' => AdminSystemController::class,
        'action' => 'getInfo',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/system/cache-clear' => [
        'method' => 'POST',
        'controller' => AdminSystemController::class,
        'action' => 'clearCache',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/system/maintenance/enable' => [
        'method' => 'POST',
        'controller' => AdminSystemController::class,
        'action' => 'enableMaintenance',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/system/maintenance/disable' => [
        'method' => 'POST',
        'controller' => AdminSystemController::class,
        'action' => 'disableMaintenance',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/system/backups' => [
        'method' => 'GET',
        'controller' => AdminSystemController::class,
        'action' => 'getBackups',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/system/backup' => [
        'method' => 'POST',
        'controller' => AdminSystemController::class,
        'action' => 'createBackup',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/system/backup/restore' => [
        'method' => 'POST',
        'controller' => AdminSystemController::class,
        'action' => 'restoreBackup',
        'middleware' => ['AdminAuth']
    ],
    'DELETE /admin/system/backup/{filename}' => [
        'method' => 'DELETE',
        'controller' => AdminSystemController::class,
        'action' => 'deleteBackup',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/system/backup/download/{filename}' => [
        'method' => 'GET',
        'controller' => AdminSystemController::class,
        'action' => 'downloadBackup',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/system/queues' => [
        'method' => 'GET',
        'controller' => AdminSystemController::class,
        'action' => 'getQueues',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/system/queues/retry' => [
        'method' => 'POST',
        'controller' => AdminSystemController::class,
        'action' => 'retryFailed',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/system/migrations' => [
        'method' => 'GET',
        'controller' => AdminSystemController::class,
        'action' => 'getMigrations',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/system/migrations/run' => [
        'method' => 'POST',
        'controller' => AdminSystemController::class,
        'action' => 'runMigrations',
        'middleware' => ['AdminAuth']
    ],

    // ==================== ADMIN SETTINGS ====================
    'GET /admin/settings' => [
        'method' => 'GET',
        'controller' => AdminSettingController::class,
        'action' => 'getSettings'
    ],
    'PUT /admin/settings' => [
        'method' => 'PUT',
        'controller' => AdminSettingController::class,
        'action' => 'updateSettings'
    ],
    'GET /admin/settings/general' => [
        'method' => 'GET',
        'controller' => AdminSettingController::class,
        'action' => 'getGeneralSettings'
    ],
    'PUT /admin/settings/general' => [
        'method' => 'PUT',
        'controller' => AdminSettingController::class,
        'action' => 'updateGeneralSettings'
    ],
    'GET /admin/settings/email' => [
        'method' => 'GET',
        'controller' => AdminSettingController::class,
        'action' => 'getEmailSettings'
    ],
    'PUT /admin/settings/email' => [
        'method' => 'PUT',
        'controller' => AdminSettingController::class,
        'action' => 'updateEmailSettings'
    ],
    'GET /admin/settings/payment' => [
        'method' => 'GET',
        'controller' => AdminSettingController::class,
        'action' => 'getPaymentSettings'
    ],
    'PUT /admin/settings/payment' => [
        'method' => 'PUT',
        'controller' => AdminSettingController::class,
        'action' => 'updatePaymentSettings'
    ],
    'GET /admin/settings/security' => [
        'method' => 'GET',
        'controller' => AdminSettingController::class,
        'action' => 'getSecuritySettings'
    ],
    'PUT /admin/settings/security' => [
        'method' => 'PUT',
        'controller' => AdminSettingController::class,
        'action' => 'updateSecuritySettings'
    ],
    'GET /admin/settings/integrations' => [
        'method' => 'GET',
        'controller' => AdminSettingController::class,
        'action' => 'getIntegrationSettings'
    ],
    'PUT /admin/settings/integrations' => [
        'method' => 'PUT',
        'controller' => AdminSettingController::class,
        'action' => 'updateIntegrationSettings'
    ],

    // ==================== ADMIN REPORTS ====================
    'GET /admin/reports/revenue' => [
        'method' => 'GET',
        'controller' => AdminReportController::class,
        'action' => 'getRevenueReport',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/reports/users' => [
        'method' => 'GET',
        'controller' => AdminReportController::class,
        'action' => 'getUsersReport',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/reports/subscriptions' => [
        'method' => 'GET',
        'controller' => AdminReportController::class,
        'action' => 'getSubscriptionsReport',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/reports/retention' => [
        'method' => 'GET',
        'controller' => AdminReportController::class,
        'action' => 'getRetentionReport',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/reports/system' => [
        'method' => 'GET',
        'controller' => AdminReportController::class,
        'action' => 'getSystemReport',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/reports/generate' => [
        'method' => 'POST',
        'controller' => AdminReportController::class,
        'action' => 'generate',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/reports/download/{id}' => [
        'method' => 'GET',
        'controller' => AdminReportController::class,
        'action' => 'download',
        'middleware' => ['AdminAuth']
    ],
    
    // ==================== ADMIN SUPPORT ====================
    'GET /admin/support/tickets' => [
        'method' => 'GET',
        'controller' => AdminSupportController::class,
        'action' => 'getTickets',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/support/tickets/{id}' => [
        'method' => 'GET',
        'controller' => AdminSupportController::class,
        'action' => 'getTicket',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/support/tickets/{id}/reply' => [
        'method' => 'POST',
        'controller' => AdminSupportController::class,
        'action' => 'replyTicket',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/support/tickets/{id}/close' => [
        'method' => 'POST',
        'controller' => AdminSupportController::class,
        'action' => 'closeTicket',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/support/faq' => [
        'method' => 'GET',
        'controller' => AdminSupportController::class,
        'action' => 'getFaq',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/support/faq' => [
        'method' => 'POST',
        'controller' => AdminSupportController::class,
        'action' => 'createFaq',
        'middleware' => ['AdminAuth']
    ],
    'GET /admin/support/knowledge-base' => [
        'method' => 'GET',
        'controller' => AdminSupportController::class,
        'action' => 'getKnowledgeBase',
        'middleware' => ['AdminAuth']
    ],
    'POST /admin/support/knowledge-base' => [
        'method' => 'POST',
        'controller' => AdminSupportController::class,
        'action' => 'createKnowledgeBase',
        'middleware' => ['AdminAuth']
    ],
    
    // ==================== ADMIN PROFILE ====================
    'GET /admin/profile' => [
        'method' => 'GET',
        'controller' => AdminProfileController::class,
        'action' => 'getProfile',
        'middleware' => ['AdminAuth']
    ],
    'PUT /admin/profile' => [
        'method' => 'PUT',
        'controller' => AdminProfileController::class,
        'action' => 'updateProfile',
        'middleware' => ['AdminAuth']
    ],
    'PUT /admin/profile/password' => [
        'method' => 'PUT',
        'controller' => AdminProfileController::class,
        'action' => 'changePassword',
        'middleware' => ['AdminAuth']
    ],

];