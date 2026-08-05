<?php
/**
 * CARDOXIS - Script de Limpeza
 * Remove arquivos desnecessários do sistema
 * 
 * ATENÇÃO: Faça backup antes de executar!
 * 
 * @version 1.0.0
 */

echo "<h1>🧹 CARDOXIS - Limpeza do Sistema</h1>";
echo "<pre>";

// Diretórios para verificar
$directories = [
    'public/assets/js/dashboard/',
    'public/assets/js/',
    'public/assets/css/',
    'public/assets/img/',
    'api/v1/controllers/',
    'api/v1/models/',
    'api/v1/config/',
    'api/v1/routes/',
    'resources/views/',
    'storage/logs/',
    'storage/uploads/',
    'storage/cache/'
];

// Arquivos para REMOVER (não essenciais)
$filesToRemove = [
    // Arquivos de avatar (não serão usados)
    'public/assets/js/dashboard/avatar.js',
    'public/assets/js/load-avatar.js',
    'public/assets/js/init.js',
    'public/assets/js/avatar-init.js',
    'public/assets/js/avatar.js',
    
    // Arquivos de teste e debug
    'test-session.php',
    'force-sync.php',
    'fix-session.php',
    'fix-session-all.php',
    'debug-session.php',
    'session-status.php',
    'sync-session.php',
    'test.html',
    'test_db.php',
    'test_login.php',
    'generate_hash.php',
    'fix_user.php',
    'debug.php',
    'info.php',
    'phpinfo.php',
    
    // Arquivos de exemplo
    'index.html',
    'index.htm',
    'default.html',
    'default.htm',
    'example.php',
    'sample.php',
    'demo.php',
    'placeholder.php',
    
    // Arquivos de backup
    '*.bak',
    '*.backup',
    '*.old',
    '*.tmp',
    
    // Arquivos de log desnecessários
    'storage/logs/*.log',
    'storage/logs/*.old',
    'api/v1/logs/*.log',
    'api/v1/logs/*.old',
    
    // Arquivos de cache
    'storage/cache/*',
    'api/v1/cache/*',
    
    // Arquivos de upload temporários
    'storage/uploads/temp/*',
    
    // Arquivos de exportação antigos
    'storage/exports/*.csv',
    'storage/exports/*.xlsx',
    
    // Arquivos de backup antigos
    'storage/backups/*.sql',
    'storage/backups/*.zip',
    'storage/backups/*.tar.gz',
    
    // Arquivos do composer (não usados em produção)
    'composer.json',
    'composer.lock',
    'package.json',
    'package-lock.json',
    'webpack.mix.js',
    'vite.config.js',
    
    // Arquivos de configuração desnecessários
    'docker-compose.yml',
    'Dockerfile',
    'nginx.conf',
    'supervisord.conf',
    'cronjobs',
    '.env.example',
    'phpunit.xml',
    'CHANGELOG.md',
    'LICENSE',
    
    // Arquivos de scripts
    'scripts/*.sh',
    'scripts/*.bat',
    'scripts/*.ps1',
    
    // Arquivos de migração (apenas os SQLs)
    'database/migrations/*.sql',
    'database/seeders/*.sql',
    'database/procedures/*.sql',
    
    // Arquivos de helpers duplicados
    'api/v1/helpers/functions.php',
    'api/v1/helpers/response.php',
    'api/v1/helpers/format.php',
    'api/v1/helpers/date.php',
    'api/v1/helpers/admin.php',
    'api/v1/helpers/permissions.php',
    
    // Traits não utilizadas
    'api/v1/traits/ApiResponseTrait.php',
    'api/v1/traits/ValidationTrait.php',
    'api/v1/traits/PaginationTrait.php',
    'api/v1/traits/AuthTrait.php',
    'api/v1/traits/AdminTrait.php',
    
    // Exceptions não utilizadas
    'api/v1/exceptions/Handler.php',
    'api/v1/exceptions/ApiException.php',
    'api/v1/exceptions/AuthException.php',
    'api/v1/exceptions/ValidationException.php',
    'api/v1/exceptions/NotFoundException.php',
    'api/v1/exceptions/ForbiddenException.php',
    'api/v1/exceptions/AdminException.php',
    
    // Services não utilizados
    'api/v1/services/JWTService.php',
    'api/v1/services/EmailService.php',
    'api/v1/services/FileService.php',
    'api/v1/services/PaymentService.php',
    'api/v1/services/ExportService.php',
    'api/v1/services/NotificationService.php',
    'api/v1/services/ValidationService.php',
    'api/v1/services/BackupService.php',
    'api/v1/services/AuditService.php',
    
    // Middleware não utilizados
    'api/v1/middleware/Cors.php',
    'api/v1/middleware/RateLimiter.php',
    'api/v1/middleware/Security.php',
    'api/v1/middleware/Validate.php',
    'api/v1/middleware/Subscription.php',
    'api/v1/middleware/Permission.php',
    'api/v1/middleware/Maintenance.php',
    
    // Configs não utilizadas
    'api/v1/config/app.php',
    'api/v1/config/jwt.php',
    'api/v1/config/cors.php',
    'api/v1/config/rate_limit.php',
    'api/v1/config/upload.php',
    'api/v1/config/mail.php',
    'api/v1/config/admin.php',
    'api/v1/config/constants.php',
    'api/v1/config/Connection.php',
    
    // Models não utilizados
    'api/v1/models/Company.php',
    'api/v1/models/Driver.php',
    'api/v1/models/Document.php',
    'api/v1/models/Maintenance.php',
    'api/v1/models/Alert.php',
    'api/v1/models/Report.php',
    'api/v1/models/Subscription.php',
    'api/v1/models/Payment.php',
    'api/v1/models/Notification.php',
    'api/v1/models/AuditLog.php',
    'api/v1/models/Setting.php',
    'api/v1/models/Plan.php',
    'api/v1/models/Invoice.php',
    'api/v1/models/LoginLog.php',
    'api/v1/models/ActivityLog.php',
    'api/v1/models/SystemHealth.php',
    'api/v1/models/Backup.php',
    
    // Controllers não utilizados
    'api/v1/controllers/ProfileController.php',
    'api/v1/controllers/CompanyController.php',
    'api/v1/controllers/DocumentController.php',
    'api/v1/controllers/MaintenanceController.php',
    'api/v1/controllers/AlertController.php',
    'api/v1/controllers/NotificationController.php',
    'api/v1/controllers/ReportController.php',
    'api/v1/controllers/SubscriptionController.php',
    'api/v1/controllers/PaymentController.php',
    'api/v1/controllers/FileController.php',
    'api/v1/controllers/AuditLogController.php',
    'api/v1/controllers/WebhookController.php',
    'api/v1/controllers/AdminAuthController.php',
    'api/v1/controllers/AdminDashboardController.php',
    'api/v1/controllers/AdminCompanyController.php',
    'api/v1/controllers/AdminUserController.php',
    'api/v1/controllers/AdminPlanController.php',
    'api/v1/controllers/AdminSubscriptionController.php',
    'api/v1/controllers/AdminPaymentController.php',
    'api/v1/controllers/AdminNotificationController.php',
    'api/v1/controllers/AdminLogController.php',
    'api/v1/controllers/AdminSystemController.php',
    'api/v1/controllers/AdminSettingController.php',
    'api/v1/controllers/AdminReportController.php',
    'api/v1/controllers/AdminSupportController.php',
    
    // Rotas não utilizadas
    'api/v1/routes/admin.php',
    'api/v1/routes/webhooks.php',
];

// Arquivos para MANTER (essenciais)
$filesToKeep = [
    // Public
    'public/index.php',
    'public/.htaccess',
    
    // CSS
    'public/assets/css/dashboard.css',
    'public/assets/css/vehicles.css',
    'public/assets/css/admin.css',
    'public/assets/css/landing.css',
    'public/assets/css/auth.css',
    
    // JS
    'public/assets/js/dashboard/dashboard.js',
    'public/assets/js/vehicles.js',
    'public/assets/js/admin.js',
    'public/assets/js/auth/login.js',
    'public/assets/js/auth/register.js',
    
    // Controllers essenciais
    'api/v1/controllers/BaseController.php',
    'api/v1/controllers/AuthController.php',
    'api/v1/controllers/DashboardController.php',
    'api/v1/controllers/VehicleController.php',
    'api/v1/controllers/DriverController.php',
    'api/v1/controllers/HealthController.php',
    
    // Models essenciais
    'api/v1/models/BaseModel.php',
    'api/v1/models/UserModel.php',
    'api/v1/models/VehicleModel.php',
    'api/v1/models/DriverModel.php',
    
    // Config essenciais
    'api/v1/config/database.php',
    
    // Rotas essenciais
    'api/v1/routes/api.php',
    
    // Helpers essenciais
    'api/v1/helpers/functions.php',
    
    // Views essenciais
    'resources/views/landing.php',
    'resources/views/auth/login.php',
    'resources/views/auth/register.php',
    'resources/views/dashboard/index.php',
    'resources/views/vehicles/index.php',
    'resources/views/layouts/partials/sidebar.php',
    'resources/views/layouts/partials/sidebar-admin.php',
    'resources/views/admin/dashboard.php',
    'resources/views/errors/404.php',
    
    // Storage
    'storage/uploads/vehicles/.gitkeep',
    'storage/logs/.gitkeep',
    'storage/cache/.gitkeep',
    'storage/exports/.gitkeep',
    'storage/backups/.gitkeep',
    
    // Raiz
    'public/index.php',
    '.htaccess',
    'index.php',
    'logout.php',
    'cleanup.php',
];

// ============================================
// EXECUTAR LIMPEZA
// ============================================

$totalRemoved = 0;
$totalSkipped = 0;

echo "\n📋 Iniciando limpeza...\n";
echo str_repeat('-', 60) . "\n";

// Função para remover arquivos
function removeFile($file) {
    global $totalRemoved;
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "🗑️  Removido: $file\n";
            $totalRemoved++;
            return true;
        } else {
            echo "❌ Erro ao remover: $file\n";
            return false;
        }
    }
    return false;
}

// Função para remover diretórios recursivamente
function removeDirectory($dir) {
    global $totalRemoved;
    if (!is_dir($dir)) return false;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            removeDirectory($path);
        } else {
            if (unlink($path)) {
                echo "🗑️  Removido: $path\n";
                $totalRemoved++;
            }
        }
    }
    if (rmdir($dir)) {
        echo "🗑️  Removido diretório: $dir\n";
        return true;
    }
    return false;
}

// Remover arquivos
echo "\n📁 Removendo arquivos desnecessários...\n";
foreach ($filesToRemove as $pattern) {
    if (strpos($pattern, '*') !== false) {
        // Padrão com wildcard
        $files = glob($pattern);
        foreach ($files as $file) {
            if (is_file($file)) {
                removeFile($file);
            }
        }
    } else {
        // Arquivo específico
        if (file_exists($pattern)) {
            removeFile($pattern);
        }
    }
}

// Limpar diretórios de logs
echo "\n📁 Limpando logs...\n";
$logDirs = [
    'storage/logs/',
    'api/v1/logs/'
];

foreach ($logDirs as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file) && !strpos($file, '.gitkeep')) {
                removeFile($file);
            }
        }
    }
}

// Limpar cache
echo "\n📁 Limpando cache...\n";
$cacheDirs = [
    'storage/cache/',
    'api/v1/cache/'
];

foreach ($cacheDirs as $dir) {
    if (is_dir($dir)) {
        removeDirectory($dir);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '.gitkeep', '');
    }
}

// Limpar uploads temporários
echo "\n📁 Limpando uploads temporários...\n";
$tempDirs = [
    'storage/uploads/temp/',
    'storage/uploads/documents/',
    'storage/uploads/profiles/',
    'storage/uploads/vehicles/',
    'storage/uploads/drivers/',
    'storage/uploads/invoices/'
];

foreach ($tempDirs as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file) && !strpos($file, '.gitkeep')) {
                removeFile($file);
            }
        }
    }
}

// Limpar exports antigos
echo "\n📁 Limpando exports antigos...\n";
$exportDirs = [
    'storage/exports/'
];

foreach ($exportDirs as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file) && !strpos($file, '.gitkeep')) {
                removeFile($file);
            }
        }
    }
}

// Limpar backups antigos
echo "\n📁 Limpando backups antigos...\n";
$backupDirs = [
    'storage/backups/'
];

foreach ($backupDirs as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file) && !strpos($file, '.gitkeep')) {
                removeFile($file);
            }
        }
    }
}

// ============================================
// RELATÓRIO FINAL
// ============================================

echo "\n" . str_repeat('=', 60) . "\n";
echo "📊 RELATÓRIO DE LIMPEZA\n";
echo str_repeat('=', 60) . "\n";
echo "🗑️  Arquivos removidos: $totalRemoved\n";
echo "📁 Diretórios limpos: " . count($logDirs) + count($cacheDirs) + count($tempDirs) + count($exportDirs) + count($backupDirs) . "\n";
echo "\n✅ Limpeza concluída!\n";
echo "📌 O sistema está mais leve e otimizado.\n";
echo str_repeat('=', 60) . "\n";

// Verificar arquivos essenciais
echo "\n🔍 Verificando arquivos essenciais...\n";
$essentialFiles = [
    'public/index.php',
    'api/v1/index.php',
    'api/v1/config/database.php',
    'api/v1/routes/api.php',
    'api/v1/controllers/AuthController.php',
    'api/v1/controllers/DashboardController.php',
    'api/v1/controllers/VehicleController.php',
    'api/v1/models/UserModel.php',
    'resources/views/landing.php',
    'resources/views/auth/login.php',
    'resources/views/dashboard/index.php',
    '.htaccess'
];

$allExist = true;
foreach ($essentialFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file\n";
    } else {
        echo "❌ $file - NÃO ENCONTRADO!\n";
        $allExist = false;
    }
}

if ($allExist) {
    echo "\n🎉 Todos os arquivos essenciais estão presentes!\n";
} else {
    echo "\n⚠️  Alguns arquivos essenciais estão faltando!\n";
}

echo "\n💡 Recomendação: Faça backup antes de executar novamente.\n";
echo "</pre>";