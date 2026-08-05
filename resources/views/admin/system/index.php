<?php
// Verificar autenticação
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = ' Sistema | CARDOXIS';

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
    <meta name="description" content="Sistema - CARDOXIS">
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
    <link rel="stylesheet" href="<?= asset('css/admin/system.css') ?>">
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
            <div>
                <h1 class="page-title">Sistema</h1>
                <p class="page-subtitle">Monitorize e gerencie o sistema</p>
            </div>
        </div>
        
        <!-- System Container -->
        <div class="system-container">
            <!-- Sidebar -->
            <div class="system-sidebar">
                <nav class="system-nav">
                    <div class="system-nav-item active" data-pane="dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </div>
                    <div class="system-nav-item" data-pane="health">
                        <i class="fas fa-heartbeat"></i>
                        <span>Saúde</span>
                    </div>
                    <div class="system-nav-item" data-pane="info">
                        <i class="fas fa-info-circle"></i>
                        <span>Informação</span>
                    </div>
                    <div class="system-nav-item" data-pane="backups">
                        <i class="fas fa-database"></i>
                        <span>Backups</span>
                    </div>
                    <div class="system-nav-item" data-pane="migrations">
                        <i class="fas fa-code-branch"></i>
                        <span>Migrações</span>
                    </div>
                    <div class="system-nav-item" data-pane="queues">
                        <i class="fas fa-tasks"></i>
                        <span>Filas</span>
                    </div>
                </nav>
            </div>
            
            <!-- Content -->
            <div class="system-content">
                
                <!-- PAINEL: DASHBOARD -->

                <div id="pane-dashboard" class="system-pane active">
                    <div class="system-header">
                        <h2>Dashboard do Sistema</h2>
                        <p>Visão geral do estado do sistema</p>
                    </div>
                    <div class="system-body">
                        <!-- Stats Cards -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title">Tabelas</span>
                                    <div class="stat-icon primary"><i class="fas fa-table"></i></div>
                                </div>
                                <div class="stat-value" id="totalTables">-</div>
                                <div class="stat-change">No banco de dados</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title">Registos</span>
                                    <div class="stat-icon success"><i class="fas fa-database"></i></div>
                                </div>
                                <div class="stat-value" id="totalRows">-</div>
                                <div class="stat-change">Total de registos</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title">Tamanho Dados</span>
                                    <div class="stat-icon warning"><i class="fas fa-chart-line"></i></div>
                                </div>
                                <div class="stat-value" id="dataSize">-</div>
                                <div class="stat-change">Tamanho dos dados</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title">Índices</span>
                                    <div class="stat-icon danger"><i class="fas fa-chart-bar"></i></div>
                                </div>
                                <div class="stat-value" id="indexSize">-</div>
                                <div class="stat-change">Tamanho dos índices</div>
                            </div>
                        </div>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title">Ficheiros</span>
                                    <div class="stat-icon primary"><i class="fas fa-file"></i></div>
                                </div>
                                <div class="stat-value" id="totalFiles">-</div>
                                <div class="stat-change">Total de ficheiros</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title">Espaço Ficheiros</span>
                                    <div class="stat-icon success"><i class="fas fa-hdd"></i></div>
                                </div>
                                <div class="stat-value" id="filesSize">-</div>
                                <div class="stat-change">Espaço utilizado</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title">Cache</span>
                                    <div class="stat-icon warning"><i class="fas fa-memory"></i></div>
                                </div>
                                <div class="stat-value" id="cacheSize">-</div>
                                <div class="stat-change">Tamanho do cache</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title">Logs</span>
                                    <div class="stat-icon danger"><i class="fas fa-history"></i></div>
                                </div>
                                <div class="stat-value" id="logsSize">-</div>
                                <div class="stat-change">Tamanho dos logs</div>
                            </div>
                        </div>
                        
                        <!-- System Health Summary -->
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-heartbeat" style="color: #00875A;"></i>
                                <span>Saúde do Sistema</span>
                            </div>
                            <div id="systemHealthList">
                                <div class="loading-spinner"><div class="spinner"></div></div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-bolt" style="color: #0052CC;"></i>
                                <span>Ações Rápidas</span>
                            </div>
                            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <button class="btn-secondary" onclick="clearCache()">
                                    <i class="fas fa-trash-alt"></i> Limpar Cache
                                </button>
                                <button class="btn-secondary" onclick="enableMaintenance()">
                                    <i class="fas fa-wrench"></i> Modo Manutenção
                                </button>
                                <button class="btn-primary" onclick="createBackup()">
                                    <i class="fas fa-database"></i> Criar Backup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- PAINEL: SAÚDE -->
                <div id="pane-health" class="system-pane">
                    <div class="system-header">
                        <h2>Saúde do Sistema</h2>
                        <p>Monitorize o estado de todos os componentes</p>
                    </div>
                    <div class="system-body">
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-database"></i>
                                <span>Base de Dados</span>
                            </div>
                            <div class="health-item">
                                <div class="health-label">Status</div>
                                <div class="health-status" id="dbStatus">-</div>
                            </div>
                            <div class="health-item">
                                <div class="health-label">Versão</div>
                                <div class="health-status" id="dbVersion">-</div>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-server"></i>
                                <span>Servidor</span>
                            </div>
                            <div class="health-item">
                                <div class="health-label">Software</div>
                                <div class="health-status" id="serverSoftware">-</div>
                            </div>
                            <div class="health-item">
                                <div class="health-label">PHP Version</div>
                                <div class="health-status" id="phpVersion">-</div>
                            </div>
                            <div class="health-item">
                                <div class="health-label">Memória</div>
                                <div class="health-status" id="memoryUsage">-</div>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-hdd"></i>
                                <span>Armazenamento</span>
                            </div>
                            <div class="health-item">
                                <div class="health-label">Disco</div>
                                <div class="health-status" id="diskUsage">-</div>
                            </div>
                            <div class="progress-bar" id="diskProgressBar"></div>
                            <div class="health-item">
                                <div class="health-label">Uploads</div>
                                <div class="health-status" id="uploadsStatus">-</div>
                            </div>
                            <div class="health-item">
                                <div class="health-label">Cache</div>
                                <div class="health-status" id="cacheStatus">-</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- PAINEL: INFORMAÇÃO -->
                <div id="pane-info" class="system-pane">
                    <div class="system-header">
                        <h2>Informação do Sistema</h2>
                        <p>Detalhes técnicos da plataforma</p>
                    </div>
                    <div class="system-body">
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-code"></i>
                                <span>Aplicação</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Nome</span>
                                <span class="info-value">CARDOXIS</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Versão</span>
                                <span class="info-value">1.0.0</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Ambiente</span>
                                <span class="info-value">Produção</span>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-chart-line"></i>
                                <span>Estatísticas</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Empresas</span>
                                <span class="info-value" id="statCompanies">-</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Utilizadores</span>
                                <span class="info-value" id="statUsers">-</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Veículos</span>
                                <span class="info-value" id="statVehicles">-</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Motoristas</span>
                                <span class="info-value" id="statDrivers">-</span>
                            </div>
                        </div>
                        
                        <div id="systemInfoList">
                            <div class="loading-spinner"><div class="spinner"></div></div>
                        </div>
                    </div>
                </div>
                
                <!-- PAINEL: BACKUPS -->
                <div id="pane-backups" class="system-pane">
                    <div class="system-header">
                        <h2>Backups</h2>
                        <p>Gerencie os backups do sistema</p>
                    </div>
                    <div class="system-body">
                        <div style="margin-bottom: 20px; display: flex; justify-content: flex-end;">
                            <button class="btn-primary" onclick="createBackup()">
                                <i class="fas fa-plus"></i> Novo Backup
                            </button>
                        </div>
                        
                        <div class="data-table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nome do Arquivo</th>
                                        <th>Tamanho</th>
                                        <th>Data</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="backupsTableBody">
                                    <tr>
                                        <td colspan="4">
                                            <div class="loading-spinner"><div class="spinner"></div></div>
                                         </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- PAINEL: MIGRAÇÕES -->
                <div id="pane-migrations" class="system-pane">
                    <div class="system-header">
                        <h2>Migrações</h2>
                        <p>Gerencie as migrações do banco de dados</p>
                    </div>
                    <div class="system-body">
                        <div class="data-table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Arquivo</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="migrationsTableBody">
                                    <tr>
                                        <td colspan="3">
                                            <div class="loading-spinner"><div class="spinner"></div></div>
                                         </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- PAINEL: FILAS -->
                <div id="pane-queues" class="system-pane">
                    <div class="system-header">
                        <h2>Filas</h2>
                        <p>Monitorize as filas de processamento</p>
                    </div>
                    <div class="system-body">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title">Pendentes</span>
                                    <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
                                </div>
                                <div class="stat-value" id="pendingJobs">-</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title">Processando</span>
                                    <div class="stat-icon primary"><i class="fas fa-spinner"></i></div>
                                </div>
                                <div class="stat-value" id="processingJobs">-</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title">Falhados</span>
                                    <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                                </div>
                                <div class="stat-value" id="failedJobs">-</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title">Concluídos</span>
                                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                                </div>
                                <div class="stat-value" id="completedJobs">-</div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                            <button class="btn-secondary" onclick="retryFailedJobs()">
                                <i class="fas fa-redo-alt"></i> Reexecutar Falhados
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODAL DE CONFIRMAÇÃO -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="confirmModalTitle">Confirmar Ação</h3>
            <button class="modal-close" onclick="closeModal('confirmModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="modal-title" id="confirmModalTitle2">Confirmar</div>
            <div class="modal-message" id="confirmModalMessage">Tem certeza que deseja realizar esta ação?</div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('confirmModal')">Cancelar</button>
            <button class="btn-danger" id="confirmModalConfirm">Confirmar</button>
        </div>
    </div>
</div>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner-large"></div>
</div>

<!-- Admin JS -->
<script src="<?= asset('js/admin.js') ?>"></script>
<script src="<?= asset('js/system.js') ?>"></script>
</body>
</html>