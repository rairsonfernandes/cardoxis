<?php
/**
 * CARDOXIS - Notificações RF 
 */

if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Notificações | CARDOXIS';

$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'user';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));
$isAdmin = in_array($userRole, ['admin', 'super_admin']);
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <meta name="description" content="Notificações do sistema CARDOXIS">
    <meta name="author" content="CARDOXIS Team">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <link rel="icon" type="image/x-icon" href="<?= asset('img/logo/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/logo/apple-touch-icon.png') ?>">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/notifications.css') ?>">
    
    <script>
        window.userName = '<?= htmlspecialchars($userName) ?>';
        window.userEmail = '<?= htmlspecialchars($userEmail) ?>';
        window.userRole = '<?= htmlspecialchars($userRole) ?>';
        window.isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
        window.API_URL = '<?= url('api/v1') ?>';
    </script>
</head>
<body>





<!-- MOBILE HEADER -->
<div class="mobile-header">
    <button class="menu-hamburger" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="mobile-logo">
        <img src="<?= asset('img/logo/logo.png') ?>" alt="Logo" class="mobile-logo-img">
        <span class="mobile-logo-text">CARDOXIS<span>.io</span></span>
    </div>

    <button class="btn-icon-refresh" onclick="location.reload()">
        <i class="fas fa-sync-alt"></i>
    </button>
</div>



<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<?php include_once ROOT_PATH . '/resources/views/layouts/partials/sidebar.php'; ?>

<!-- MAIN CONTENT -->
<main class="main-content">
    <div class="notifications-container">
        
        <!-- Header -->
        <div class="notif-header-card">
            <div class="notif-header-top">
                <div class="notif-header-title">
                    <i class="fas fa-bell"></i>
                    Notificações
                    <span class="badge" id="unreadBadge">0</span>
                </div>
                <div class="notif-actions">
                    <button class="btn btn-secondary btn-sm" id="markAllRead">
                        <i class="fas fa-check-double"></i> Marcar todas como lidas
                    </button>
                    <button class="btn btn-danger btn-sm" id="deleteAllRead">
                        <i class="fas fa-trash-alt"></i> Limpar lidas
                    </button>
                </div>
            </div>
            <div class="notif-header-bottom">
                <div class="notif-welcome">
                    <h2>Olá, <span class="highlight"><?= htmlspecialchars($userName) ?></span> <span class="emoji">👋</span></h2>
                    <span style="font-size:0.85rem;color:var(--notif-text-secondary);">
                        Acompanhe todas as notificações do sistema
                    </span>
                </div>
                <div class="notif-last-update">
                    <i class="fas fa-clock"></i>
                    Última atualização: <span id="lastUpdateTime">-</span>
                </div>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="notif-stats-grid">
            <div class="notif-stat-card" onclick="filterNotifications('all')">
                <div class="stat-header">
                    <span class="stat-title">Total</span>
                    <div class="stat-icon primary"><i class="fas fa-bell"></i></div>
                </div>
                <div class="stat-value" id="totalCount">0</div>
                <div class="stat-change">Notificações</div>
            </div>
            <div class="notif-stat-card" onclick="filterNotifications('unread')">
                <div class="stat-header">
                    <span class="stat-title">Não Lidas</span>
                    <div class="stat-icon warning"><i class="fas fa-envelope"></i></div>
                </div>
                <div class="stat-value" id="unreadCount">0</div>
                <div class="stat-change">Pendentes</div>
            </div>
            <div class="notif-stat-card" onclick="filterNotifications('read')">
                <div class="stat-header">
                    <span class="stat-title">Lidas</span>
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-value" id="readCount">0</div>
                <div class="stat-change">Visualizadas</div>
            </div>
            <div class="notif-stat-card" onclick="filterNotifications('system')">
                <div class="stat-header">
                    <span class="stat-title">Sistema</span>
                    <div class="stat-icon info"><i class="fas fa-server"></i></div>
                </div>
                <div class="stat-value" id="systemCount">0</div>
                <div class="stat-change">Alertas do sistema</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="notif-filters" id="filterContainer">
            <button class="notif-filter-btn active" data-filter="all" onclick="filterNotifications('all')">
                <i class="fas fa-list"></i> Todos
                <span class="count" id="filterAllCount">0</span>
            </button>
            <button class="notif-filter-btn" data-filter="unread" onclick="filterNotifications('unread')">
                <i class="fas fa-envelope"></i> Não Lidos
                <span class="count" id="filterUnreadCount">0</span>
            </button>
            <button class="notif-filter-btn" data-filter="read" onclick="filterNotifications('read')">
                <i class="fas fa-check-circle"></i> Lidos
                <span class="count" id="filterReadCount">0</span>
            </button>
            <button class="notif-filter-btn" data-filter="system" onclick="filterNotifications('system')">
                <i class="fas fa-server"></i> Sistema
                <span class="count" id="filterSystemCount">0</span>
            </button>
            <button class="notif-filter-btn" data-filter="document" onclick="filterNotifications('document')">
                <i class="fas fa-file-alt"></i> Documentos
                <span class="count" id="filterDocumentCount">0</span>
            </button>
            <button class="notif-filter-btn" data-filter="maintenance" onclick="filterNotifications('maintenance')">
                <i class="fas fa-tools"></i> Manutenção
                <span class="count" id="filterMaintenanceCount">0</span>
            </button>
        </div>
        
        <!-- Notifications List -->
        <div id="notificationsList">
            <div class="notif-loading">
                <div class="spinner"></div>
                <p style="margin-top:12px;color:var(--notif-text-muted);">Carregando notificações...</p>
            </div>
        </div>
        
        <!-- Pagination -->
        <div class="notif-pagination" id="pagination"></div>
        
    </div>
</main>

<!-- TOAST CONTAINER -->
<div id="toastContainer" class="toast-container"></div>

<!-- NOTIFICATIONS JS -->
<script src="<?= asset('js/dashboard/notifications.js') ?>"></script>

</body>
</html>