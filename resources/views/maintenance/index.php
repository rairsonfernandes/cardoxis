<?php
/**
 * CARDOXIS - Página de Gestão de Manutenções RF 
 */

// Verificar autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Manutenções | CARDOXIS';
$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userRole = $_SESSION['user_role'] ?? 'user';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0052CC">
    <meta name="description" content="Gestão de manutenções - CARDOXIS">
    <meta name="author" content="CARDOXIS Team">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
	
	
	<link rel="icon" type="image/x-icon" href="<?= asset('img/logo/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/logo/apple-touch-icon.png') ?>">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= asset('css/Dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/Dashboard/maintenance.css') ?>">
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
    <div class="dashboard-container">
        <!-- HEADER -->
        <div class="dashboard-header">
            <div class="header-top">
                    <h1 class="header-title"><i class="fas fa-wrench"></i>Manutenções</h1>
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="globalSearch" placeholder="Pesquisar manutenções...">
                </div>
                <div class="header-actions">
                    <button class="btn-icon-refresh" onclick="location.reload()" title="Atualizar">
                        <i class="fas fa-sync-alt"></i>
                        <span>Atualizar</span>
                    </button>
                    <button class="btn-primary" onclick="openAddMaintenanceModal()">
                        <i class="fas fa-plus"></i>
                        <span>Agendar Manutenção</span>
                    </button>
                </div>
            </div>
            <div class="header-bottom">
                <div class="welcome-section">
                    <p class="welcome-subtitle">Gerencie todas as manutenções da sua frota</p>
                </div>
                <div class="last-update">
                    <i class="fas fa-clock"></i>
                    <span>Última atualização: <span id="lastUpdateTime">-</span></span>
                </div>
            </div>
        </div>
        
        <!-- STATS CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Manutenções</span>
                    <div class="stat-icon primary"><i class="fas fa-clipboard-list"></i></div>
                </div>
                <div class="stat-value" id="totalMaintenances">-</div>
                <div class="stat-change">Registadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Pendentes</span>
                    <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
                </div>
                <div class="stat-value" id="scheduledCount">-</div>
                <div class="stat-change">Agendadas / Em andamento</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Concluídas</span>
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-value" id="completedCount">-</div>
                <div class="stat-change">Finalizadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Custo Total</span>
                    <div class="stat-icon primary"><i class="fas fa-euro-sign"></i></div>
                </div>
                <div class="stat-value" id="totalCost">-</div>
                <div class="stat-change">Manutenções concluídas</div>
            </div>
        </div>
        
        <!-- QUICK ACTIONS & FILTERS -->
        <div class="quick-actions">
            <div class="section-header">
                <div class="filter-group">
                    <button class="filter-status-btn active" data-status="all" onclick="filterMaintenances('all')">
                        <i class="fas fa-list"></i> Todas
                        <span class="count" id="filterAllCount">0</span>
                    </button>
                    <button class="filter-status-btn" data-status="scheduled" onclick="filterMaintenances('scheduled')">
                        <i class="fas fa-calendar"></i> Agendadas
                        <span class="count" id="filterScheduledCount">0</span>
                    </button>
                    <button class="filter-status-btn" data-status="in_progress" onclick="filterMaintenances('in_progress')">
                        <i class="fas fa-spinner"></i> Em Andamento
                        <span class="count" id="filterInProgressCount">0</span>
                    </button>
                    <button class="filter-status-btn" data-status="completed" onclick="filterMaintenances('completed')">
                        <i class="fas fa-check-circle"></i> Concluídas
                        <span class="count" id="filterCompletedCount">0</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- MAINTENANCE TABLE -->
        <div class="maintenance-table-container">
            <table class="maintenance-table">
                <thead>
                    <tr>
                        <th>Manutenção</th>
                        <th>Veículo</th>
                        <th>Prioridade</th>
                        <th>Status</th>
                        <th>Data Agendada</th>
                        <th>Custo</th>
                        <th>Oficina</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="maintenancesTableBody">
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 60px;">
                            <div class="spinner"></div>
                            <p style="margin-top: 16px;">Carregando manutenções...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- PAGINATION -->
        <div id="pagination" class="pagination"></div>
        
        <div class="footer-note">
            <i class="fas fa-info-circle"></i> Clique em uma manutenção para ver mais detalhes
        </div>
    </div>
</main>

<!-- ============================================ -->
<!-- MODAL ADICIONAR MANUTENÇÃO -->
<!-- ============================================ -->
<div id="addMaintenanceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Agendar Manutenção</h3>
            <button class="modal-close" onclick="closeModal('addMaintenanceModal')">&times;</button>
        </div>
        <form id="addMaintenanceForm">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group full-width">
                        <label><i class="fas fa-car"></i> Veículo *</label>
                        <select id="addVehicleId" required>
                            <option value="">Selecione um veículo</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-wrench"></i> Título *</label>
                        <input type="text" id="addTitle" placeholder="Ex: Revisão de 10.000km" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-flag"></i> Prioridade</label>
                        <select id="addPriority">
                            <option value="low">Baixa</option>
                            <option value="medium" selected>Média</option>
                            <option value="high">Alta</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Data Agendada *</label>
                        <input type="date" id="addScheduledDate" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Oficina</label>
                        <input type="text" id="addWorkshopName" placeholder="Nome da oficina">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-euro-sign"></i> Custo Estimado</label>
                        <input type="number" id="addCost" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-road"></i> KM Atual</label>
                        <input type="number" id="addOdometer" placeholder="Quilómetros atuais">
                    </div>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-align-left"></i> Descrição</label>
                    <textarea id="addDescription" placeholder="Descreva os serviços a serem realizados..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addMaintenanceModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Agendar Manutenção</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL EDITAR MANUTENÇÃO -->
<!-- ============================================ -->
<div id="editMaintenanceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Manutenção</h3>
            <button class="modal-close" onclick="closeModal('editMaintenanceModal')">&times;</button>
        </div>
        <form id="editMaintenanceForm">
            <input type="hidden" id="editMaintenanceId">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group full-width">
                        <label><i class="fas fa-wrench"></i> Título *</label>
                        <input type="text" id="editTitle" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Data Agendada *</label>
                        <input type="date" id="editScheduledDate" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-flag"></i> Prioridade</label>
                        <select id="editPriority">
                            <option value="low">Baixa</option>
                            <option value="medium">Média</option>
                            <option value="high">Alta</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Oficina</label>
                        <input type="text" id="editWorkshopName">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-euro-sign"></i> Custo</label>
                        <input type="number" id="editCost" step="0.01">
                    </div>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-align-left"></i> Descrição</label>
                    <textarea id="editDescription" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editMaintenanceModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL DETALHES MANUTENÇÃO -->
<!-- ============================================ -->
<div id="viewMaintenanceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Detalhes da Manutenção</h3>
            <button class="modal-close" onclick="closeModal('viewMaintenanceModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailModalContent">
            <div class="loading-spinner"><div class="spinner"></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('viewMaintenanceModal')">Fechar</button>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL CONCLUIR MANUTENÇÃO -->
<!-- ============================================ -->
<div id="completeMaintenanceModal" class="modal">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h3><i class="fas fa-check-circle"></i> Concluir Manutenção</h3>
            <button class="modal-close" onclick="closeModal('completeMaintenanceModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 20px; color: var(--gray);">Registe as informações finais da manutenção:</p>
            <div class="form-group">
                <label><i class="fas fa-euro-sign"></i> Custo Final</label>
                <input type="number" id="completeCost" step="0.01" placeholder="0.00">
            </div>
            <div class="form-group">
                <label><i class="fas fa-road"></i> KM no momento da manutenção</label>
                <input type="number" id="completeOdometer" placeholder="Quilómetros">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeModal('completeMaintenanceModal')">Cancelar</button>
            <button type="button" class="btn-primary" onclick="confirmCompleteMaintenance()">Concluir</button>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL CONFIRMAÇÃO DELETE -->
<!-- ============================================ -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3><i class="fas fa-trash-alt"></i> Confirmar Eliminação</h3>
            <button class="modal-close" onclick="closeModal('deleteConfirmModal')">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--danger); margin-bottom: 16px;"></i>
            <p class="modal-message">Tem certeza que deseja eliminar esta manutenção?</p>
            <p style="font-size: 0.85rem; color: var(--gray);">Esta ação não pode ser desfeita.</p>
        </div>
        <div class="modal-footer" style="justify-content: center;">
            <button type="button" class="btn-cancel" onclick="closeModal('deleteConfirmModal')">Cancelar</button>
            <button type="button" class="btn-danger" onclick="confirmDeleteMaintenance()">Eliminar</button>
        </div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div id="toastContainer" class="toast-container"></div>

<!-- JavaScript -->
<script src="<?= asset('js/dashboard/maintenance.js') ?>"></script>
<script src="<?= asset('js/dashboard/avatar.js') ?>"></script>

<script>
// Variável global para uso nos modais
let currentMaintenanceId = null;
</script>

</body>
</html>