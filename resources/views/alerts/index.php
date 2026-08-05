<?php
/**
 * CARDOXIS - Página de Gestão de Alertas RF 
 */

if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: /cardoxis/login');
    exit;
}

$pageTitle = 'Alertas | CARDOXIS';
$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userRole = $_SESSION['user_role'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0052CC">
    <meta name="description" content="Gestão de alertas e notificações - CARDOXIS">
    <meta name="author" content="CARDOXIS Team">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <link rel="icon" type="image/x-icon" href="/cardoxis/public/assets/img/logo/favicon.png">
    <link rel="apple-touch-icon" href="/cardoxis/public/assets/img/logo/apple-touch-icon.png">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/cardoxis/public/assets/css/Dashboard/dashboard.css">
    <link rel="stylesheet" href="/cardoxis/public/assets/css/Dashboard/alerts.css">
    
    <script>
        window.CARDOXIS = window.CARDOXIS || {};
        window.CARDOXIS.API_URL = '/cardoxis/api/v1';
        window.CARDOXIS.CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?? '' ?>';
        window.CARDOXIS.USER = {
            id: <?= $_SESSION['user_id'] ?? 0 ?>,
            name: '<?= htmlspecialchars($userName) ?>',
            role: '<?= htmlspecialchars($userRole) ?>'
        };
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
    <div class="dashboard-container alerts-container">
        
        <!-- HEADER -->
        <div class="dashboard-header">
            <div class="header-top">
                <div class="header-title-group">
                    <h1 class="header-title">
                        <i class="fas fa-bell"></i>
                        Alertas
                    </h1>
                    <span class="badge" id="unreadBadge" style="display:none;"></span>
                </div>
                
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="globalSearch" placeholder="Pesquisar alertas...">
                </div>
                
                <div class="header-actions">
                    <button class="btn-secondary" onclick="markAllAsRead()" title="Marcar todos como lidos">
                        <i class="fas fa-check-double"></i>
                        <span>Marcar todos</span>
                    </button>
                    <button class="btn-secondary danger" onclick="deleteReadAlerts()" title="Eliminar alertas lidos">
                        <i class="fas fa-trash-alt"></i>
                        <span>Limpar lidos</span>
                    </button>
                    <button class="btn-primary" onclick="runDocumentCheck()" title="Verificar documentos">
                        <i class="fas fa-sync-alt"></i>
                        <span>Verificar Documentos</span>
                    </button>
                </div>
            </div>
            
            <div class="header-bottom">
                <div class="welcome-section">
                    <p class="welcome-subtitle">Acompanhe todas as notificações e alertas do sistema, incluindo documentos a vencer</p>
                </div>
                <div class="last-update">
                    <i class="fas fa-clock"></i>
                    <span>Última atualização: <span id="lastUpdateTime">-</span></span>
                </div>
            </div>
        </div>
        
        <!-- STATS CARDS -->
        <div class="stats-grid">
            <div class="stat-card" onclick="filterAlerts('all')">
                <div class="stat-header">
                    <span class="stat-title">Total</span>
                    <div class="stat-icon primary"><i class="fas fa-bell"></i></div>
                </div>
                <div class="stat-value" id="totalAlerts">-</div>
            </div>
            
            <div class="stat-card" onclick="filterAlerts('unread')">
                <div class="stat-header">
                    <span class="stat-title">Não Lidos</span>
                    <div class="stat-icon warning"><i class="fas fa-envelope"></i></div>
                </div>
                <div class="stat-value" id="unreadCount">-</div>
            </div>
            
            <div class="stat-card" onclick="filterAlerts('critical')">
                <div class="stat-header">
                    <span class="stat-title">Críticos</span>
                    <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div class="stat-value" id="criticalCount">-</div>
            </div>
            
            <div class="stat-card" onclick="filterAlerts('info')">
                <div class="stat-header">
                    <span class="stat-title">Documentos</span>
                    <div class="stat-icon info"><i class="fas fa-file-alt"></i></div>
                </div>
                <div class="stat-value" id="documentCount">-</div>
            </div>
        </div>
        
        <!-- FILTERS -->
        <div class="quick-actions">
            <div class="section-header">
                <div class="filter-group">
                    <button class="filter-status-btn active" data-status="all" onclick="filterAlerts('all')">
                        <i class="fas fa-list"></i> Todos
                        <span class="count" id="filterAllCount">0</span>
                    </button>
                    <button class="filter-status-btn" data-status="unread" onclick="filterAlerts('unread')">
                        <i class="fas fa-envelope"></i> Não Lidos
                        <span class="count" id="filterUnreadCount">0</span>
                    </button>
                    <button class="filter-status-btn" data-status="read" onclick="filterAlerts('read')">
                        <i class="fas fa-check-circle"></i> Lidos
                        <span class="count" id="filterReadCount">0</span>
                    </button>
                    <button class="filter-status-btn" data-status="critical" onclick="filterAlerts('critical')">
                        <i class="fas fa-exclamation-triangle"></i> Críticos
                        <span class="count" id="filterCriticalCount">0</span>
                    </button>
                    <button class="filter-status-btn" data-status="document" onclick="filterAlerts('document')">
                        <i class="fas fa-file-alt"></i> Documentos
                        <span class="count" id="filterDocumentCount">0</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- TABLE -->
        <div class="alerts-table-container" id="tableContainer">
            <table class="alerts-table">
                <thead>
                    <tr>
                        <th class="alert-icon-col"></th>
                        <th>Alerta</th>
                        <th>Tipo</th>
                        <th>Severidade</th>
                        <th>Data</th>
                        <th class="actions-col">Ações</th>
                    </tr>
                </thead>
                <tbody id="alertsTableBody">
                    <tr>
                        <td colspan="6" style="text-align:center; padding:60px;">
                            <div class="spinner"></div>
                            <p style="margin-top:16px; color:#5a6b7c;">Carregando alertas...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- PAGINATION -->
        <div id="pagination" class="pagination"></div>
        
        <!-- FOOTER NOTE -->
        <div class="footer-note">
            <i class="fas fa-info-circle"></i> 
            Clique em um alerta para ver mais detalhes. Os alertas de documentos são gerados automaticamente quando um documento está próximo do vencimento.
        </div>
        
    </div>
</main>
 
<!-- MODAL DETALHES DO ALERTA --> 
<div id="viewAlertModal" class="modal">
    <div class="modal-content" style="max-width: 560px;">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Detalhes do Alerta</h3>
            <button class="modal-close" onclick="closeModal('viewAlertModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailModalContent">
            <div class="loading-spinner"><div class="spinner"></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('viewAlertModal')">Fechar</button>
            <button class="btn-primary" id="markReadFromModal" onclick="markCurrentAsRead()">
                <i class="fas fa-check"></i> Marcar como lido
            </button>
        </div>
    </div>
</div>
        
<!-- MODAL CONFIRMAÇÃO -->    
<div id="confirmModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3><i class="fas fa-question-circle"></i> Confirmar Ação</h3>
            <button class="modal-close" onclick="closeModal('confirmModal')">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--warning); margin-bottom: 16px;"></i>
            <p id="confirmMessage">Tem certeza que deseja realizar esta ação?</p>
            <p style="font-size: 0.85rem; color: var(--gray);">Esta ação não pode ser desfeita.</p>
        </div>
        <div class="modal-footer" style="justify-content: center;">
            <button class="btn-cancel" onclick="closeModal('confirmModal')">Cancelar</button>
            <button class="btn-danger" id="confirmActionBtn">Confirmar</button>
        </div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div id="toastContainer" class="toast-container"></div>
 
<!-- JAVASCRIPT -->
<script src="/cardoxis/public/assets/js/dashboard/alerts.js"></script>

</body>
</html>