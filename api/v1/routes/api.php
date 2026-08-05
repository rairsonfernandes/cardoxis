<?php
/**
 * CARDOXIS - API Routes Configuration  RF
 */

return [
    // ============================================================
    // HEALTH
    // ============================================================
    'GET /' => [
        'method' => 'GET',
        'controller' => HealthController::class,
        'action' => 'index'
    ],
    'GET /health' => [
        'method' => 'GET',
        'controller' => HealthController::class,
        'action' => 'check'
    ],
    
    
    // ============================================================
    // AUTHENTICATION
    // ============================================================
    'POST /auth/register' => [
        'method' => 'POST',
        'controller' => AuthController::class,
        'action' => 'register'
    ],
    'POST /auth/login' => [
        'method' => 'POST',
        'controller' => AuthController::class,
        'action' => 'login'
    ],
    'POST /auth/logout' => [
        'method' => 'POST',
        'controller' => AuthController::class,
        'action' => 'logout'
    ],
    'POST /auth/refresh' => [
        'method' => 'POST',
        'controller' => AuthController::class,
        'action' => 'refresh'
    ],
    'POST /auth/forgot-password' => [
        'method' => 'POST',
        'controller' => AuthController::class,
        'action' => 'forgotPassword'
    ],
    'POST /auth/reset-password' => [
        'method' => 'POST',
        'controller' => AuthController::class,
        'action' => 'resetPassword'
    ],
    'POST /auth/verify-email' => [
        'method' => 'POST',
        'controller' => AuthController::class,
        'action' => 'verifyEmail'
    ],
    'POST /auth/resend-verification' => [
        'method' => 'POST',
        'controller' => AuthController::class,
        'action' => 'resendVerification'
    ],
    'GET /auth/me' => [
        'method' => 'GET',
        'controller' => AuthController::class,
        'action' => 'getMe'
    ],
    'GET /auth/check' => [
        'method' => 'GET',
        'controller' => AuthController::class,
        'action' => 'check'
    ],


        // ==================== AUTH ====================
    'GET /auth/check' => [
        'method' => 'GET',
        'controller' => AuthController::class,
        'action' => 'checkSession'
    ],
    
    // ============================================================
    // WELCOME SYSTEM
    // ============================================================
    'GET /welcome/status' => [
        'method' => 'GET',
        'controller' => WelcomeController::class,
        'action' => 'getStatus'
    ],
    'POST /welcome/mark-seen' => [
        'method' => 'POST',
        'controller' => WelcomeController::class,
        'action' => 'markSeen'
    ],
    
    // ============================================================
    // PROFILE
    // ============================================================
    'GET /profile' => [
        'method' => 'GET',
        'controller' => ProfileController::class,
        'action' => 'getProfile'
    ],
    'PUT /profile' => [
        'method' => 'PUT',
        'controller' => ProfileController::class,
        'action' => 'updateProfile'
    ],
    'PUT /profile/password' => [
        'method' => 'PUT',
        'controller' => ProfileController::class,
        'action' => 'changePassword'
    ],
    'POST /profile/avatar' => [
        'method' => 'POST',
        'controller' => ProfileController::class,
        'action' => 'uploadAvatar'
    ],
    'DELETE /profile/avatar' => [
        'method' => 'DELETE',
        'controller' => ProfileController::class,
        'action' => 'deleteAvatar'
    ],
    'GET /profile/activity' => [
        'method' => 'GET',
        'controller' => ProfileController::class,
        'action' => 'getActivity'
    ],
    'GET /profile/api-tokens' => [
        'method' => 'GET',
        'controller' => ProfileController::class,
        'action' => 'getApiTokens'
    ],
    'POST /profile/api-tokens' => [
        'method' => 'POST',
        'controller' => ProfileController::class,
        'action' => 'createApiToken'
    ],
    'DELETE /profile/api-tokens/{id}' => [
        'method' => 'DELETE',
        'controller' => ProfileController::class,
        'action' => 'revokeApiToken'
    ],
    'DELETE /profile/delete' => [
        'method' => 'DELETE',
        'controller' => ProfileController::class,
        'action' => 'deleteAccount'
    ],
    'GET /profile/export-data' => [
        'method' => 'GET',
        'controller' => ProfileController::class,
        'action' => 'exportData'
    ],
    'POST /profile/sessions/terminate-all' => [
        'method' => 'POST',
        'controller' => ProfileController::class,
        'action' => 'terminateAllSessions'
    ],
    
    // ============================================================
    // DASHBOARD
    // ============================================================
    'GET /dashboard' => [
        'method' => 'GET',
        'controller' => DashboardController::class,
        'action' => 'index'
    ],
    'GET /dashboard/stats' => [
        'method' => 'GET',
        'controller' => DashboardController::class,
        'action' => 'getStats'
    ],
    'GET /dashboard/vehicles-growth' => [
        'method' => 'GET',
        'controller' => DashboardController::class,
        'action' => 'getVehiclesGrowth'
    ],
    'GET /dashboard/fuel-distribution' => [
        'method' => 'GET',
        'controller' => DashboardController::class,
        'action' => 'getFuelDistribution'
    ],
    'GET /dashboard/notifications' => [
        'method' => 'GET',
        'controller' => DashboardController::class,
        'action' => 'getNotifications'
    ],
    'GET /dashboard/upcoming-maintenances' => [
        'method' => 'GET',
        'controller' => DashboardController::class,
        'action' => 'getUpcomingMaintenances'
    ],
    
    // ============================================================
    // COMPANY
    // ============================================================
    'GET /company' => [
        'method' => 'GET',
        'controller' => CompanyController::class,
        'action' => 'getCompany'
    ],
    'PUT /company' => [
        'method' => 'PUT',
        'controller' => CompanyController::class,
        'action' => 'updateCompany'
    ],
    'GET /company/settings' => [
        'method' => 'GET',
        'controller' => CompanyController::class,
        'action' => 'getSettings'
    ],
    'PUT /company/settings' => [
        'method' => 'PUT',
        'controller' => CompanyController::class,
        'action' => 'updateSettings'
    ],
    'GET /company/users' => [
        'method' => 'GET',
        'controller' => CompanyController::class,
        'action' => 'getUsers'
    ],
    'POST /company/users' => [
        'method' => 'POST',
        'controller' => CompanyController::class,
        'action' => 'addUser'
    ],
    'GET /company/users/{id}' => [
        'method' => 'GET',
        'controller' => CompanyController::class,
        'action' => 'getUser'
    ],
    'PUT /company/users/{id}' => [
        'method' => 'PUT',
        'controller' => CompanyController::class,
        'action' => 'updateUser'
    ],
    'DELETE /company/users/{id}' => [
        'method' => 'DELETE',
        'controller' => CompanyController::class,
        'action' => 'deleteUser'
    ],
    'PUT /company/users/{id}/role' => [
        'method' => 'PUT',
        'controller' => CompanyController::class,
        'action' => 'updateUserRole'
    ],
    
    // ============================================================
    // VEHICLES
    // ============================================================
    'GET /vehicles' => [
        'method' => 'GET',
        'controller' => VehicleController::class,
        'action' => 'index'
    ],
    'POST /vehicles' => [
        'method' => 'POST',
        'controller' => VehicleController::class,
        'action' => 'create'
    ],
    'GET /vehicles/{id}' => [
        'method' => 'GET',
        'controller' => VehicleController::class,
        'action' => 'show'
    ],
    'PUT /vehicles/{id}' => [
        'method' => 'PUT',
        'controller' => VehicleController::class,
        'action' => 'update'
    ],
    'DELETE /vehicles/{id}' => [
        'method' => 'DELETE',
        'controller' => VehicleController::class,
        'action' => 'delete'
    ],
    'POST /vehicles/{id}/image' => [
        'method' => 'POST',
        'controller' => VehicleController::class,
        'action' => 'uploadImage'
    ],
    'DELETE /vehicles/{id}/image' => [
        'method' => 'DELETE',
        'controller' => VehicleController::class,
        'action' => 'deleteImage'
    ],
    'PATCH /vehicles/{id}/color' => [
        'method' => 'PATCH',
        'controller' => VehicleController::class,
        'action' => 'updateColor'
    ],
    'PATCH /vehicles/{id}/odometer' => [
        'method' => 'PATCH',
        'controller' => VehicleController::class,
        'action' => 'updateOdometer'
    ],
    
    // ============================================================
// DRIVERS
// ============================================================
// IMPORTANTE: Rotas específicas PRIMEIRO, depois rotas com {id}

'GET /drivers/vehicles/available' => [
    'method' => 'GET',
    'controller' => DriverController::class,
    'action' => 'getAvailableVehicles'
],
'GET /drivers' => [
    'method' => 'GET',
    'controller' => DriverController::class,
    'action' => 'index'
],
'POST /drivers' => [
    'method' => 'POST',
    'controller' => DriverController::class,
    'action' => 'create'
],
'GET /drivers/{id}' => [
    'method' => 'GET',
    'controller' => DriverController::class,
    'action' => 'show'
],
'PUT /drivers/{id}' => [
    'method' => 'PUT',
    'controller' => DriverController::class,
    'action' => 'update'
],
'DELETE /drivers/{id}' => [
    'method' => 'DELETE',
    'controller' => DriverController::class,
    'action' => 'delete'
],
'GET /drivers/{id}/vehicles' => [
    'method' => 'GET',
    'controller' => DriverController::class,
    'action' => 'getVehicles'
],
'GET /drivers/{id}/documents' => [
    'method' => 'GET',
    'controller' => DriverController::class,
    'action' => 'getDocuments'
],
'GET /drivers/{id}/history' => [
    'method' => 'GET',
    'controller' => DriverController::class,
    'action' => 'getHistory'
],
'POST /drivers/{id}/license' => [
    'method' => 'POST',
    'controller' => DriverController::class,
    'action' => 'updateLicense'
],
    
    // ============================================================
    // MAINTENANCES
    // ============================================================
    'GET /maintenances' => [
        'method' => 'GET',
        'controller' => MaintenanceController::class,
        'action' => 'index'
    ],
    'GET /maintenances/upcoming' => [
        'method' => 'GET',
        'controller' => MaintenanceController::class,
        'action' => 'getUpcoming'
    ],
    'GET /maintenances/overdue' => [
        'method' => 'GET',
        'controller' => MaintenanceController::class,
        'action' => 'getOverdue'
    ],
    'GET /maintenances/{id}' => [
        'method' => 'GET',
        'controller' => MaintenanceController::class,
        'action' => 'show'
    ],
    'POST /maintenances' => [
        'method' => 'POST',
        'controller' => MaintenanceController::class,
        'action' => 'create'
    ],
    'PUT /maintenances/{id}' => [
        'method' => 'PUT',
        'controller' => MaintenanceController::class,
        'action' => 'update'
    ],
    'DELETE /maintenances/{id}' => [
        'method' => 'DELETE',
        'controller' => MaintenanceController::class,
        'action' => 'delete'
    ],
    'POST /maintenances/{id}/complete' => [
        'method' => 'POST',
        'controller' => MaintenanceController::class,
        'action' => 'complete'
    ],
    
    // ============================================================
    // DOCUMENTS
    // ============================================================
    'GET /documents' => [
        'method' => 'GET',
        'controller' => DocumentController::class,
        'action' => 'index'
    ],
    'POST /documents' => [
        'method' => 'POST',
        'controller' => DocumentController::class,
        'action' => 'upload'
    ],
    'GET /documents/{id}' => [
        'method' => 'GET',
        'controller' => DocumentController::class,
        'action' => 'show'
    ],
    'PUT /documents/{id}' => [
        'method' => 'PUT',
        'controller' => DocumentController::class,
        'action' => 'update'
    ],
    'DELETE /documents/{id}' => [
        'method' => 'DELETE',
        'controller' => DocumentController::class,
        'action' => 'delete'
    ],
    'GET /documents/{id}/download' => [
        'method' => 'GET',
        'controller' => DocumentController::class,
        'action' => 'download'
    ],
    'GET /documents/expired' => [
        'method' => 'GET',
        'controller' => DocumentController::class,
        'action' => 'getExpired'
    ],
    'GET /documents/expiring' => [
        'method' => 'GET',
        'controller' => DocumentController::class,
        'action' => 'getExpiring'
    ],
    'GET /documents/categories' => [
        'method' => 'GET',
        'controller' => DocumentController::class,
        'action' => 'getCategories'
    ],
    
    // ============================================================
    // ALERTS
    // ============================================================
    'GET /alerts' => [
        'method' => 'GET',
        'controller' => AlertController::class,
        'action' => 'index'
    ],
    'GET /alerts/{id}' => [
        'method' => 'GET',
        'controller' => AlertController::class,
        'action' => 'show'
    ],
    'POST /alerts/{id}/read' => [
        'method' => 'POST',
        'controller' => AlertController::class,
        'action' => 'markAsRead'
    ],
    'POST /alerts/read-all' => [
        'method' => 'POST',
        'controller' => AlertController::class,
        'action' => 'markAllAsRead'
    ],
    'DELETE /alerts/{id}/delete' => [
        'method' => 'DELETE',
        'controller' => AlertController::class,
        'action' => 'delete'
    ],
    'DELETE /alerts/delete-read' => [
        'method' => 'DELETE',
        'controller' => AlertController::class,
        'action' => 'deleteReadAlerts'
    ],
    'POST /alerts/check-documents' => [
        'method' => 'POST',
        'controller' => AlertController::class,
        'action' => 'checkDocuments'
    ],
    
    







// ==================== NOTIFICATIONS ====================
'GET /notifications' => [
    'method' => 'GET',
    'controller' => NotificationController::class,
    'action' => 'index'
],
'GET /notifications/{id}' => [
    'method' => 'GET',
    'controller' => NotificationController::class,
    'action' => 'show'
],
'POST /notifications/{id}/read' => [
    'method' => 'POST',
    'controller' => NotificationController::class,
    'action' => 'markAsRead'
],
'POST /notifications/read-all' => [
    'method' => 'POST',
    'controller' => NotificationController::class,
    'action' => 'markAllAsRead'
],
'DELETE /notifications/{id}' => [
    'method' => 'DELETE',
    'controller' => NotificationController::class,
    'action' => 'delete'
],
'DELETE /notifications/delete-read' => [
    'method' => 'DELETE',
    'controller' => NotificationController::class,
    'action' => 'deleteRead'
],








    // ============================================================
    // REPORTS
    // ============================================================
    'GET /reports/dashboard' => [
        'method' => 'GET',
        'controller' => ReportController::class,
        'action' => 'getDashboardData'
    ],
    'GET /reports/stats' => [
        'method' => 'GET',
        'controller' => ReportController::class,
        'action' => 'getStats'
    ],
    'GET /reports/maintenance' => [
        'method' => 'GET',
        'controller' => ReportController::class,
        'action' => 'getMaintenanceReport'
    ],
    'GET /reports/fuel' => [
        'method' => 'GET',
        'controller' => ReportController::class,
        'action' => 'getFuelReport'
    ],
    'GET /reports/documents' => [
        'method' => 'GET',
        'controller' => ReportController::class,
        'action' => 'getDocumentsReport'
    ],
    
    // ============================================================
    // FUEL MANAGEMENT
    // ============================================================
    'GET /fuel' => [
        'method' => 'GET',
        'controller' => FuelController::class,
        'action' => 'index'
    ],
    'GET /fuel/stats' => [
        'method' => 'GET',
        'controller' => FuelController::class,
        'action' => 'getStats'
    ],
    'GET /fuel/{id}' => [
        'method' => 'GET',
        'controller' => FuelController::class,
        'action' => 'show'
    ],
    'POST /fuel' => [
        'method' => 'POST',
        'controller' => FuelController::class,
        'action' => 'create'
    ],
    'PUT /fuel/{id}' => [
        'method' => 'PUT',
        'controller' => FuelController::class,
        'action' => 'update'
    ],
    'DELETE /fuel/{id}' => [
        'method' => 'DELETE',
        'controller' => FuelController::class,
        'action' => 'delete'
    ],
    
    // ============================================================
    // INSURANCE MANAGEMENT
    // ============================================================
    'GET /insurances' => [
        'method' => 'GET',
        'controller' => InsuranceController::class,
        'action' => 'index'
    ],
    'GET /insurances/stats' => [
        'method' => 'GET',
        'controller' => InsuranceController::class,
        'action' => 'getStats'
    ],
    'GET /insurances/{id}' => [
        'method' => 'GET',
        'controller' => InsuranceController::class,
        'action' => 'show'
    ],
    'POST /insurances' => [
        'method' => 'POST',
        'controller' => InsuranceController::class,
        'action' => 'create'
    ],
    'PUT /insurances/{id}' => [
        'method' => 'PUT',
        'controller' => InsuranceController::class,
        'action' => 'update'
    ],
    'DELETE /insurances/{id}' => [
        'method' => 'DELETE',
        'controller' => InsuranceController::class,
        'action' => 'delete'
    ],
    'POST /insurances/{id}/document' => [
        'method' => 'POST',
        'controller' => InsuranceController::class,
        'action' => 'uploadDocument'
    ],
    'GET /insurances/{id}/document' => [
        'method' => 'GET',
        'controller' => InsuranceController::class,
        'action' => 'downloadDocument'
    ],
    'DELETE /insurances/{id}/document' => [
        'method' => 'DELETE',
        'controller' => InsuranceController::class,
        'action' => 'deleteDocument'
    ],
    
    // ============================================================
    // FINES MANAGEMENT
    // ============================================================
    'GET /fines' => [
        'method' => 'GET',
        'controller' => FineController::class,
        'action' => 'index'
    ],
    'GET /fines/stats' => [
        'method' => 'GET',
        'controller' => FineController::class,
        'action' => 'getStats'
    ],
    'GET /fines/{id}' => [
        'method' => 'GET',
        'controller' => FineController::class,
        'action' => 'show'
    ],
    'POST /fines' => [
        'method' => 'POST',
        'controller' => FineController::class,
        'action' => 'create'
    ],
    'PUT /fines/{id}' => [
        'method' => 'PUT',
        'controller' => FineController::class,
        'action' => 'update'
    ],
    'DELETE /fines/{id}' => [
        'method' => 'DELETE',
        'controller' => FineController::class,
        'action' => 'delete'
    ],
    'POST /fines/{id}/pay' => [
        'method' => 'POST',
        'controller' => FineController::class,
        'action' => 'markAsPaid'
    ],
    'POST /fines/{id}/contest' => [
        'method' => 'POST',
        'controller' => FineController::class,
        'action' => 'markAsContested'
    ],
    'POST /fines/{id}/document' => [
        'method' => 'POST',
        'controller' => FineController::class,
        'action' => 'uploadDocument'
    ],
    'GET /fines/{id}/document' => [
        'method' => 'GET',
        'controller' => FineController::class,
        'action' => 'downloadDocument'
    ],
    'DELETE /fines/{id}/document' => [
        'method' => 'DELETE',
        'controller' => FineController::class,
        'action' => 'deleteDocument'
    ],
    
    // ============================================================
    // SUBSCRIPTION
    // ============================================================
    'GET /subscription' => [
        'method' => 'GET',
        'controller' => SubscriptionController::class,
        'action' => 'getSubscription'
    ],
    'GET /subscription/plans' => [
        'method' => 'GET',
        'controller' => SubscriptionController::class,
        'action' => 'getPlans'
    ],
    'POST /subscription/upgrade' => [
        'method' => 'POST',
        'controller' => SubscriptionController::class,
        'action' => 'upgrade'
    ],
    'POST /subscription/downgrade' => [
        'method' => 'POST',
        'controller' => SubscriptionController::class,
        'action' => 'downgrade'
    ],
    'POST /subscription/cancel' => [
        'method' => 'POST',
        'controller' => SubscriptionController::class,
        'action' => 'cancel'
    ],
    'POST /subscription/reactivate' => [
        'method' => 'POST',
        'controller' => SubscriptionController::class,
        'action' => 'reactivate'
    ],
    'GET /subscription/invoices' => [
        'method' => 'GET',
        'controller' => SubscriptionController::class,
        'action' => 'getInvoices'
    ],
    'GET /subscription/history' => [
        'method' => 'GET',
        'controller' => SubscriptionController::class,
        'action' => 'getHistory'
    ],
    
    // ============================================================
    // PAYMENTS
    // ============================================================
    'GET /payments' => [
        'method' => 'GET',
        'controller' => PaymentController::class,
        'action' => 'index'
    ],
    'GET /payments/{id}' => [
        'method' => 'GET',
        'controller' => PaymentController::class,
        'action' => 'show'
    ],
    'POST /payments' => [
        'method' => 'POST',
        'controller' => PaymentController::class,
        'action' => 'create'
    ],
    'POST /payments/webhook' => [
        'method' => 'POST',
        'controller' => PaymentController::class,
        'action' => 'webhook'
    ],
    'GET /payments/methods' => [
        'method' => 'GET',
        'controller' => PaymentController::class,
        'action' => 'getMethods'
    ],
    'POST /payments/methods' => [
        'method' => 'POST',
        'controller' => PaymentController::class,
        'action' => 'addMethod'
    ],
    'DELETE /payments/methods/{id}' => [
        'method' => 'DELETE',
        'controller' => PaymentController::class,
        'action' => 'deleteMethod'
    ],
    
    // ============================================================
    // AUDIT LOGS
    // ============================================================
    'GET /audit-logs' => [
        'method' => 'GET',
        'controller' => AuditLogController::class,
        'action' => 'index'
    ],
    'GET /audit-logs/{id}' => [
        'method' => 'GET',
        'controller' => AuditLogController::class,
        'action' => 'show'
    ],
    'GET /audit-logs/export' => [
        'method' => 'GET',
        'controller' => AuditLogController::class,
        'action' => 'export'
    ],
    
    // ============================================================
    // FILES
    // ============================================================
    'POST /files/upload' => [
        'method' => 'POST',
        'controller' => FileController::class,
        'action' => 'upload'
    ],
    'GET /files' => [
        'method' => 'GET',
        'controller' => FileController::class,
        'action' => 'index'
    ],
    'GET /files/{id}' => [
        'method' => 'GET',
        'controller' => FileController::class,
        'action' => 'show'
    ],
    'GET /files/{id}/download' => [
        'method' => 'GET',
        'controller' => FileController::class,
        'action' => 'download'
    ],
    'DELETE /files/{id}' => [
        'method' => 'DELETE',
        'controller' => FileController::class,
        'action' => 'delete'
    ],
    
    // ============================================================
    // WEBHOOKS
    // ============================================================
    'POST /webhooks/stripe' => [
        'method' => 'POST',
        'controller' => WebhookController::class,
        'action' => 'stripe'
    ],
    'POST /webhooks/pagarme' => [
        'method' => 'POST',
        'controller' => WebhookController::class,
        'action' => 'pagarme'
    ],
    'POST /webhooks/mercadopago' => [
        'method' => 'POST',
        'controller' => WebhookController::class,
        'action' => 'mercadopago'
    ],

    
    // ============================================================
    // DELIVERIES
    // ============================================================
    'GET /deliveries' => [
        'method' => 'GET',
        'controller' => DeliveryController::class,
        'action' => 'index'
    ],
    'GET /deliveries/{id}' => [
        'method' => 'GET',
        'controller' => DeliveryController::class,
        'action' => 'show'
    ],
    'POST /deliveries' => [
        'method' => 'POST',
        'controller' => DeliveryController::class,
        'action' => 'create'
    ],
    'PUT /deliveries/{id}' => [
        'method' => 'PUT',
        'controller' => DeliveryController::class,
        'action' => 'update'
    ],
    'DELETE /deliveries/{id}' => [
        'method' => 'DELETE',
        'controller' => DeliveryController::class,
        'action' => 'delete'
    ],
    'PATCH /deliveries/{id}/status' => [
        'method' => 'PATCH',
        'controller' => DeliveryController::class,
        'action' => 'updateStatus'
    ],
    'POST /deliveries/{id}/pod' => [
        'method' => 'POST',
        'controller' => DeliveryController::class,
        'action' => 'uploadPOD'
    ],
    'GET /deliveries/{id}/pod' => [
        'method' => 'GET',
        'controller' => DeliveryController::class,
        'action' => 'getPOD'
    ],
    
    
    // ============================================================
    // ANALYTICS
    // ============================================================
    'GET /analytics/deliveries' => [
        'method' => 'GET',
        'controller' => AnalyticsController::class,
        'action' => 'deliveries'
    ],
    'GET /analytics/routes' => [
        'method' => 'GET',
        'controller' => AnalyticsController::class,
        'action' => 'routes'
    ],
    'GET /analytics/drivers' => [
        'method' => 'GET',
        'controller' => AnalyticsController::class,
        'action' => 'drivers'
    ],
    'GET /analytics/fleet' => [
        'method' => 'GET',
        'controller' => AnalyticsController::class,
        'action' => 'fleet'
    ],
    
    // ============================================================
    // REPORTS (LAST MILE)
    // ============================================================
    'GET /reports/daily' => [
        'method' => 'GET',
        'controller' => ReportController::class,
        'action' => 'daily'
    ],
    'GET /reports/weekly' => [
        'method' => 'GET',
        'controller' => ReportController::class,
        'action' => 'weekly'
    ],
    'GET /reports/monthly' => [
        'method' => 'GET',
        'controller' => ReportController::class,
        'action' => 'monthly'
    ],
    'GET /reports/export/csv' => [
        'method' => 'GET',
        'controller' => ReportController::class,
        'action' => 'exportCSV'
    ],
    'GET /reports/export/excel' => [
        'method' => 'GET',
        'controller' => ReportController::class,
        'action' => 'exportExcel'
    ],
    'GET /reports/export/pdf' => [
        'method' => 'GET',
        'controller' => ReportController::class,
        'action' => 'exportPDF'
    ],








    
    // ==================== CHAT ====================
'GET /chat/status' => [
    'method' => 'GET',
    'controller' => ChatController::class,
    'action' => 'status',
    'auth' => true
],
'POST /chat/ask' => [
    'method' => 'POST',
    'controller' => ChatController::class,
    'action' => 'ask',
    'auth' => true
],
'GET /chat/history' => [
    'method' => 'GET',
    'controller' => ChatController::class,
    'action' => 'history',
    'auth' => true
],
'POST /chat/clear-history' => [
    'method' => 'POST',
    'controller' => ChatController::class,
    'action' => 'clearHistory',
    'auth' => true
],
'GET /chat/analytics' => [
    'method' => 'GET',
    'controller' => ChatController::class,
    'action' => 'analytics',
    'auth' => true
],







'POST /auth/forgot-password' => [
    'method' => 'POST',
    'controller' => AuthController::class,
    'action' => 'forgotPassword'
],
'POST /auth/verify-pin' => [
    'method' => 'POST',
    'controller' => AuthController::class,
    'action' => 'verifyPin'
],
'POST /auth/resend-pin' => [
    'method' => 'POST',
    'controller' => AuthController::class,
    'action' => 'resendPin'
],
'POST /auth/reset-password' => [
    'method' => 'POST',
    'controller' => AuthController::class,
    'action' => 'resetPassword'
],


];