<?php
// Verificar autenticação
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Logs do Sistema | CARDOXIS';

$userName = $_SESSION['user_name'] ?? 'Administrator';

$userEmail = $_SESSION['user_email'] ?? 'admin@example.com';

$userRole = $_SESSION['user_role'] ?? 'administrator';
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0052CC">
    <meta name="description" content="Logs do sistema - CARDOXIS">
    <meta name="author" content="CARDOXIS Team">
    <meta name="robots" content="noindex, nofollow">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?= asset('img/logo/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/logo/apple-touch-icon.png') ?>">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?= asset('css/admin/admin.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin/logs.css') ?>">
</head>
<body>

<!-- MOBILE HEADER -->
<div class="mobile-header">
    <button class="menu-hamburger" id="menuToggle" aria-label="Menu">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-logo">
        <div class="mobile-logo-icon">
            <img src="<?= asset('img/logo/logo.png') ?>" alt="CARDOXIS">
        </div>
        <div class="mobile-logo-text">CARDOXIS<span>.io</span></div>
    </div>
    <div class="mobile-actions">
        <button class="btn-icon-refresh" onclick="refreshLogs()">
            <i class="fas fa-sync-alt"></i>
            <span>Atualizar</span>
        </button>
    </div>
</div>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<?php include_once ROOT_PATH . '/resources/views/layouts/partials/sidebar-admin.php'; ?>

<!-- MAIN CONTENT -->
<main class="main-content">
    <div class="dashboard-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Logs do Sistema</h1>
                <p class="page-subtitle">Registo de todas as atividades do sistema</p>
            </div>
            <div class="page-actions">
                <button class="btn-secondary" onclick="exportLogs()">
                    <i class="fas fa-download"></i> Exportar
                </button>
                <button class="btn-primary" onclick="refreshLogs()">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </button>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Logs de Auditoria</span>
                    <div class="stat-icon primary"><i class="fas fa-clipboard-list"></i></div>
                </div>
                <div class="stat-value" id="totalAuditLogs">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Logs de Login</span>
                    <div class="stat-icon success"><i class="fas fa-sign-in-alt"></i></div>
                </div>
                <div class="stat-value" id="totalLoginLogs">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Logs de Atividade</span>
                    <div class="stat-icon warning"><i class="fas fa-chart-line"></i></div>
                </div>
                <div class="stat-value" id="totalActivityLogs">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Logs da API</span>
                    <div class="stat-icon danger"><i class="fas fa-code"></i></div>
                </div>
                <div class="stat-value" id="totalApiLogs">-</div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="logs-tabs">
            <button class="logs-tab active" id="tabAudit" onclick="switchTab('audit')">
                <i class="fas fa-clipboard-list"></i> Auditoria
            </button>
            <button class="logs-tab" id="tabLogin" onclick="switchTab('login')">
                <i class="fas fa-sign-in-alt"></i> Logins
            </button>
            <button class="logs-tab" id="tabActivity" onclick="switchTab('activity')">
                <i class="fas fa-chart-line"></i> Atividades
            </button>
            <button class="logs-tab" id="tabApi" onclick="switchTab('api')">
                <i class="fas fa-code"></i> API
            </button>
            <button class="logs-tab" id="tabErrors" onclick="switchTab('errors')">
                <i class="fas fa-exclamation-triangle"></i> Erros
            </button>
        </div>
        
        <!-- Filters Bar -->
        <div class="filters-bar" id="filtersBar">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar logs..." autocomplete="off">
            </div>
            <select id="actionFilter" class="filter-select" onchange="filterByAction()">
                <option value="">Todas as ações</option>
                <option value="create">Criar</option>
                <option value="update">Atualizar</option>
                <option value="delete">Eliminar</option>
                <option value="login">Login</option>
                <option value="logout">Logout</option>
                <option value="export">Exportar</option>
            </select>
            <div class="date-range">
                <input type="date" id="startDate" class="date-input" placeholder="Data inicial" onchange="filterByDate()">
                <span>até</span>
                <input type="date" id="endDate" class="date-input" placeholder="Data final" onchange="filterByDate()">
            </div>
            <button class="btn-secondary" onclick="resetFilters()">
                <i class="fas fa-undo-alt"></i> Limpar Filtros
            </button>
            <button class="btn-primary" onclick="searchLogs()">
                <i class="fas fa-search"></i> Buscar
            </button>
        </div>
        
        <!-- Logs Container -->
        <div id="logsContainer" class="logs-container">
            <div class="loading-spinner">
                <div class="spinner"></div>
            </div>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <div class="pagination-info" id="pageInfo"></div>
            <div id="pagination"></div>
        </div>
    </div>
</main>

<!-- MODAL DE DETALHES -->
<div id="detailModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Detalhes do Log</h3>
            <button class="modal-close" onclick="closeDetailModal()">&times;</button>
        </div>
        <div class="modal-body" id="detailContent">
            <!-- Conteúdo dinâmico -->
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeDetailModal()">Fechar</button>
        </div>
    </div>
</div>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner-large"></div>
</div>

<!-- Admin JS -->
<script src="<?= asset('js/admin.js') ?>"></script>
<script src="<?= asset('js/logs.js') ?>"></script>

<script>
    // Atualizar menu ativo
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-item').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '/cardoxis/admin/logs') {
                link.classList.add('active');
            }
        });
    });
</script>
</body>
</html>