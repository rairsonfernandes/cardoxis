<?php
// NÃO iniciar sessão novamente - a sessão já está ativa no index.php

// Verificar autenticação
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Dashboard Administrativo | CARDOXIS';

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
    <meta name="description" content="Dashboard de gestão de frotas CARDOXIS - Solução empresarial completa">
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?= asset('css/admin/admin.css') ?>">
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
        <button class="btn-icon-refresh" onclick="location.reload()">
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
        <div class="page-header">
            <h1 class="page-title">Dashboard Administrativo</h1>
            <p class="page-subtitle">Visão geral da plataforma CARDOXIS</p>
        </div>
        
        <!-- KPI CARDS - MÉTRICAS PRINCIPAIS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Empresas Ativas</span>
                    <div class="stat-icon primary"><i class="fas fa-building"></i></div>
                </div>
                <div class="stat-value" id="totalCompanies">-</div>
                <div class="stat-change">Total registadas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Utilizadores Totais</span>
                    <div class="stat-icon success"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-value" id="totalUsers">-</div>
                <div class="stat-change">+12% este mês</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Veículos</span>
                    <div class="stat-icon warning"><i class="fas fa-truck"></i></div>
                </div>
                <div class="stat-value" id="totalVehicles">-</div>
                <div class="stat-change">Em circulação</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Motoristas</span>
                    <div class="stat-icon danger"><i class="fas fa-id-card"></i></div>
                </div>
                <div class="stat-value" id="totalDrivers">-</div>
                <div class="stat-change">Ativos</div>
            </div>
        </div>
        
        <!-- SEGUNDA LINHA DE KPIs -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Assinaturas Ativas</span>
                    <div class="stat-icon primary"><i class="fas fa-credit-card"></i></div>
                </div>
                <div class="stat-value" id="activeSubscriptions">-</div>
                <div class="stat-change">Planos pagos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Manutenções Pendentes</span>
                    <div class="stat-icon warning"><i class="fas fa-tools"></i></div>
                </div>
                <div class="stat-value" id="pendingMaintenance">-</div>
                <div class="stat-change">Agendadas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Documentos a Expirar</span>
                    <div class="stat-icon danger"><i class="fas fa-file-alt"></i></div>
                </div>
                <div class="stat-value" id="expiringDocuments">-</div>
                <div class="stat-change">Próximos 30 dias</div>
            </div>
        </div>
        
        <!-- GRÁFICOS -->
        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-title">
                    <i class="fas fa-chart-line" style="color: #0052CC;"></i> Evolução de Receita
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-title">
                    <i class="fas fa-chart-bar" style="color: #36B37E;"></i> Crescimento de Utilizadores
                </div>
                <div class="chart-container">
                    <canvas id="usersChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- ALERTAS SISTEMA | ATIVIDADES RECENTES -->
        <div class="two-columns">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-bell" style="color: #FF8B00;"></i>
                        <span>Alertas do Sistema</span>
                    </div>
                    <button class="btn-icon-refresh" onclick="loadSystemAlerts()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div id="systemAlertsList">
                        <div class="loading-spinner"><div class="spinner"></div></div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-clock" style="color: #6B778C;"></i>
                        <span>Atividades Recentes</span>
                    </div>
                    <button class="btn-icon-refresh" onclick="loadRecentActivities()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="activity-list" id="activityList">
                        <div class="loading-spinner"><div class="spinner"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SAÚDE SISTEMA | BACKUPS -->
        <div class="two-columns">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-heartbeat" style="color: #00A3BF;"></i>
                        <span>Saúde do Sistema</span>
                    </div>
                    <button class="btn-icon-refresh" onclick="loadSystemHealth()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div id="systemHealthList">
                        <div class="loading-spinner"><div class="spinner"></div></div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-database" style="color: #0052CC;"></i>
                        <span>Backups</span>
                    </div>
                    <button class="btn-primary" style="padding:6px 12px; font-size:0.75rem;" onclick="createBackup()">
                        <i class="fas fa-plus"></i> Novo Backup
                    </button>
                </div>
                <div class="card-body">
                    <div id="backupsList">
                        <div class="loading-spinner"><div class="spinner"></div></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- AÇÕES RÁPIDAS -->
        <div class="quick-actions-section">
            <div class="quick-actions-card">
                <div class="quick-actions-header">
                    <div class="card-title">
                        <i class="fas fa-bolt" style="color: #FF8B00;"></i>
                        <span>Ações Rápidas</span>
                    </div>
                </div>
                <div class="quick-actions-body">
                    <div class="quick-actions-grid">
                        <button class="quick-action-btn quick-action-btn-primary" onclick="clearCache()" data-tooltip="Limpar cache do sistema">
                            <i class="fas fa-trash-alt"></i>
                            <span>Limpar Cache</span>
                        </button>
                        
                        <button class="quick-action-btn quick-action-btn-success" onclick="window.location.href='<?= url('reports') ?>'" data-tooltip="Gerar relatório personalizado">
                            <i class="fas fa-chart-bar"></i>
                            <span>Gerar Relatório</span>
                        </button>
                        
                        <button class="quick-action-btn quick-action-btn-warning" onclick="window.location.href='<?= url('admin/settings') ?>'" data-tooltip="Configurações do sistema">
                            <i class="fas fa-cog"></i>
                            <span>Configurações</span>
                        </button>
                        
                        <button class="quick-action-btn quick-action-btn-danger" onclick="createBackup()" data-tooltip="Criar backup do banco de dados">
                            <i class="fas fa-database"></i>
                            <span>Criar Backup</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODAL DE ERRO -->
<div id="errorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header modal-header-error">
            <h3>
                <i class="fas fa-times-circle"></i>
                <span id="errorModalTitle">Erro</span>
            </h3>
            <button class="modal-close" onclick="closeModal('errorModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-icon modal-icon-error">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="modal-title modal-title-error" id="errorModalTitle2">Erro</div>
            <div class="modal-message" id="errorModalMessage">Ocorreu um erro inesperado.</div>
            <div class="modal-detail" id="errorModalDetail" style="display: none;"></div>
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-primary" onclick="closeModal('errorModal')">OK</button>
        </div>
    </div>
</div>

<!-- MODAL DE SUCESSO -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <div class="modal-header modal-header-success">
            <h3>
                <i class="fas fa-check-circle"></i>
                <span id="successModalTitle">Sucesso</span>
            </h3>
            <button class="modal-close" onclick="closeModal('successModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-icon modal-icon-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="modal-title modal-title-success" id="successModalTitle2">Sucesso</div>
            <div class="modal-message" id="successModalMessage">Operação realizada com sucesso.</div>
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-success" id="successModalConfirm" onclick="closeModal('successModal')">OK</button>
        </div>
    </div>
</div>

<!-- MODAL DE AVISO / CONFIRMAÇÃO -->
<div id="warningModal" class="modal">
    <div class="modal-content">
        <div class="modal-header modal-header-warning">
            <h3>
                <i class="fas fa-exclamation-triangle"></i>
                <span id="warningModalTitle">Atenção</span>
            </h3>
            <button class="modal-close" onclick="closeModal('warningModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-icon modal-icon-warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="modal-title modal-title-warning" id="warningModalTitle2">Confirmar Ação</div>
            <div class="modal-message" id="warningModalMessage">Tem certeza que deseja realizar esta ação?</div>
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-secondary" id="warningModalCancel" onclick="closeModal('warningModal')">Cancelar</button>
            <button class="modal-btn modal-btn-warning" id="warningModalConfirm">Confirmar</button>
        </div>
    </div>
</div>

<!-- MODAL DE INFORMAÇÃO -->
<div id="infoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header modal-header-info">
            <h3>
                <i class="fas fa-info-circle"></i>
                <span id="infoModalTitle">Informação</span>
            </h3>
            <button class="modal-close" onclick="closeModal('infoModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-icon modal-icon-info">
                <i id="infoModalIcon" class="fas fa-info-circle"></i>
            </div>
            <div class="modal-title modal-title-info" id="infoModalTitle2">Informação</div>
            <div class="modal-message" id="infoModalMessage"></div>
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-primary" onclick="closeModal('infoModal')">OK</button>
        </div>
    </div>
</div>

<!-- Admin JS -->
<script src="<?= asset('js/admin.js') ?>"></script>

<!-- Funções adicionais para recarregamento -->
<script>
    // Funções globais para recarregar seções específicas
    window.loadRecentCompanies = function() { location.reload(); };
    window.loadRecentPayments = function() { location.reload(); };
    window.loadSystemAlerts = function() { location.reload(); };
    window.loadSystemHealth = function() { location.reload(); };
    window.loadBackups = function() { location.reload(); };
    window.loadRecentActivities = function() { location.reload(); };
</script>
</body>
</html>