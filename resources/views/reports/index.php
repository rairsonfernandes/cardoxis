<?php
/**
 * CARDOXIS - Reports Page RF
 */

// Verificar autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: /cardoxis/login');
    exit;
}

$pageTitle = 'Relatórios | CARDOXIS';
$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userRole = $_SESSION['user_role'] ?? 'user';

// As funções url() e asset() já estão declaradas no public/index.php
// Não as redeclare aqui!
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0052CC">
    <meta name="description" content="Relatórios e análises da frota - CARDOXIS">
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/cardoxis/public/assets/css/dashboard/dashboard.css">
    <link rel="stylesheet" href="/cardoxis/public/assets/css/dashboard/reports.css">
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
    <div class="dashboard-container reports-container">
        
        <!-- HEADER -->
        <div class="dashboard-header">
            <div class="header-top">
                <div class="header-title-group">
                    <h1 class="header-title"><i class="fas fa-chart-line"></i>Relatórios</h1>
                </div>
                
                <div class="header-actions">
                    <button class="btn-secondary" onclick="window.location.reload()" title="Atualizar">
                        <i class="fas fa-sync-alt"></i>
                        <span>Atualizar</span>
                    </button>
                    <button class="btn-primary" onclick="exportReport('pdf')" title="Exportar PDF">
                        <i class="fas fa-file-pdf"></i>
                        <span>Exportar PDF</span>
                    </button>
                    <button class="btn-secondary" onclick="exportReport('csv')" title="Exportar CSV">
                        <i class="fas fa-file-csv"></i>
                        <span>Exportar CSV</span>
                    </button>
                </div>
            </div>
            
            <div class="header-bottom">
                <div>
                    <p class="welcome-subtitle">Visualize e analise todos os dados da sua frota em tempo real</p>
                </div>
                <div class="last-update">
                    <i class="fas fa-clock"></i>
                    <span>Última atualização: <span id="lastUpdateTime">-</span></span>
                </div>
            </div>
        </div>
        
        <!-- FILTERS BAR -->
        <div class="filters-bar">
            <div class="filter-group">
                <label><i class="fas fa-calendar-alt"></i> Período</label>
                <select id="periodFilter">
                    <option value="7">Últimos 7 dias</option>
                    <option value="30" selected>Últimos 30 dias</option>
                    <option value="60">Últimos 60 dias</option>
                    <option value="90">Últimos 90 dias</option>
                    <option value="180">Últimos 6 meses</option>
                    <option value="365">Último ano</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-chart-pie"></i> Tipo de Relatório</label>
                <select id="reportTypeFilter">
                    <option value="fleet">Frota</option>
                    <option value="maintenance">Manutenções</option>
                    <option value="fuel">Combustível</option>
                    <option value="drivers">Motoristas</option>
                    <option value="documents">Documentos</option>
                    <option value="costs">Custos</option>
                </select>
            </div>
            
            <div class="filter-group" style="min-width: 120px;">
                <label><i class="fas fa-chart-bar"></i> Visualização</label>
                <select id="viewTypeFilter">
                    <option value="charts" selected>Gráficos</option>
                    <option value="tables">Tabelas</option>
                    <option value="both">Ambos</option>
                </select>
            </div>
            
            <button class="btn-primary filter-btn" onclick="applyFilters()">
                <i class="fas fa-filter"></i> Aplicar Filtros
            </button>
        </div>
        
        <!-- STATS CARDS -->
        <div class="stats-grid" id="reportStats">
            <div class="stat-card" onclick="filterReport('vehicles')">
                <div class="stat-header">
                    <span class="stat-title">Total Veículos</span>
                    <div class="stat-icon primary"><i class="fas fa-truck"></i></div>
                </div>
                <div class="stat-value" id="totalVehicles">-</div>
                <div class="stat-change">Na frota</div>
            </div>
            <div class="stat-card" onclick="filterReport('maintenance')">
                <div class="stat-header">
                    <span class="stat-title">Custo Manutenções</span>
                    <div class="stat-icon warning"><i class="fas fa-tools"></i></div>
                </div>
                <div class="stat-value" id="totalMaintenanceCost">-</div>
                <div class="stat-change">Este ano</div>
            </div>
            <div class="stat-card" onclick="filterReport('fuel')">
                <div class="stat-header">
                    <span class="stat-title">Combustível</span>
                    <div class="stat-icon success"><i class="fas fa-gas-pump"></i></div>
                </div>
                <div class="stat-value" id="totalFuelLiters">-</div>
                <div class="stat-change">Litros consumidos</div>
            </div>
            <div class="stat-card" onclick="filterReport('documents')">
                <div class="stat-header">
                    <span class="stat-title">Documentos a Expirar</span>
                    <div class="stat-icon danger"><i class="fas fa-file-alt"></i></div>
                </div>
                <div class="stat-value" id="expiringDocuments">-</div>
                <div class="stat-change">Próximos 30 dias</div>
            </div>
        </div>
        
        <!-- CHARTS SECTION -->
        <div id="chartsSection">
            <div class="two-columns">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-chart-pie" style="color: #0052CC;"></i>
                            <span>Distribuição por Status</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-chart-pie" style="color: #36B37E;"></i>
                            <span>Distribuição por Combustível</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="fuelChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="two-columns">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-chart-line" style="color: #FF8B00;"></i>
                            <span>Custo de Manutenções</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="maintenanceChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-chart-bar" style="color: #6554C0;"></i>
                            <span>Consumo de Combustível</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="fuelConsumptionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- TABLES SECTION -->
        <div id="tablesSection">
            <div class="reports-grid">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-tools" style="color: #FF8B00;"></i>
                            <span>Manutenções por Tipo</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="maintenanceByType">
                            <div class="loading-spinner"><div class="spinner"></div></div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-gas-pump" style="color: #36B37E;"></i>
                            <span>Consumo por Veículo</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="fuelByVehicle">
                            <div class="loading-spinner"><div class="spinner"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- DOCUMENTS EXPIRING -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-file-alt" style="color: #DE350B;"></i>
                    <span>Documentos a Expirar</span>
                </div>
                <span class="nav-badge danger" id="expiringCount">0</span>
            </div>
            <div class="card-body">
                <div id="expiringDocumentsList">
                    <div class="loading-spinner"><div class="spinner"></div></div>
                </div>
            </div>
        </div>
        
        <!-- FOOTER -->
        <div class="footer-note">
            <i class="fas fa-info-circle"></i>
            Os dados são atualizados em tempo real. Clique em "Atualizar" para recarregar os dados.
        </div>
        
    </div>
</main>

<!-- MODAL DE EXPORTAÇÃO -->

<div id="exportModal" class="modal">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
            <h3><i class="fas fa-file-export"></i> Exportar Relatório</h3>
            <button class="modal-close" onclick="closeModal('exportModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group" style="margin-bottom: 16px;">
                <label><i class="fas fa-file-format"></i> Formato</label>
                <select id="exportFormat" style="width:100%; padding:10px; border:1px solid var(--gray-ultra-light); border-radius: var(--radius-md);">
                    <option value="pdf">PDF</option>
                    <option value="csv">CSV</option>
                    <option value="excel">Excel</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label><i class="fas fa-calendar-alt"></i> Período</label>
                <select id="exportPeriod" style="width:100%; padding:10px; border:1px solid var(--gray-ultra-light); border-radius: var(--radius-md);">
                    <option value="7">Últimos 7 dias</option>
                    <option value="30" selected>Últimos 30 dias</option>
                    <option value="90">Últimos 90 dias</option>
                    <option value="365">Último ano</option>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-file-alt"></i> Incluir</label>
                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 8px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                        <input type="checkbox" checked> Gráficos
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                        <input type="checkbox" checked> Tabelas
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                        <input type="checkbox" checked> Estatísticas
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('exportModal')">Cancelar</button>
            <button class="btn-primary" onclick="confirmExport()">
                <i class="fas fa-download"></i> Exportar
            </button>
        </div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div id="toastContainer" class="toast-container"></div>

<!-- JAVASCRIPT -->
 
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

<script src="/cardoxis/public/assets/js/dashboard/reports.js"></script>

</body>
</html>