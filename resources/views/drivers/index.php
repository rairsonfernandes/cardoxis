<?php
/**
 * CARDOXIS - Drivers Page RF
 */

if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: /cardoxis/login');
    exit;
}

$pageTitle = 'Motoristas | CARDOXIS';
$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userRole = $_SESSION['user_role'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0052CC">
    <meta name="description" content="Gestão de motoristas da frota - CARDOXIS">
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
    <link rel="stylesheet" href="/cardoxis/public/assets/css/dashboard/dashboard.css">
    <link rel="stylesheet" href="/cardoxis/public/assets/css/dashboard/drivers.css">
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
    <div class="dashboard-container drivers-container">
        
        <!-- HEADER -->
        <div class="dashboard-header">
            <div class="header-top">
                <div class="header-title-group">
                    <h1 class="header-title">
                        <i class="fas fa-users"></i>
                        Motoristas
                    </h1>
                </div>
                
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="globalSearch" placeholder="Pesquisar motoristas por nome, NIF ou carta...">
                </div>
                
                <div class="header-actions">
                    <button class="btn-secondary" onclick="window.location.reload()" title="Atualizar">
                        <i class="fas fa-sync-alt"></i>
                        <span>Atualizar</span>
                    </button>
                    <button class="btn-primary" onclick="openAddDriverModal()">
                        <i class="fas fa-user-plus"></i>
                        <span>Adicionar Motorista</span>
                    </button>
                </div>
            </div>
            
            <div class="header-bottom">
                <div>
                    <p class="welcome-subtitle">Gerencie todos os motoristas da sua frota de forma eficiente</p>
                </div>
                <div class="last-update">
                    <i class="fas fa-clock"></i>
                    <span>Última atualização: <span id="lastUpdateTime">-</span></span>
                </div>
            </div>
        </div>
        
        <!-- STATS CARDS -->
        <div class="stats-grid" id="driverStats">
            <div class="stat-card" onclick="filterDrivers('all')">
                <div class="stat-header">
                    <span class="stat-title">Total</span>
                    <div class="stat-icon primary"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-value" id="totalDrivers">-</div>
                <div class="stat-change">Motoristas cadastrados</div>
            </div>
            <div class="stat-card" onclick="filterDrivers('active')">
                <div class="stat-header">
                    <span class="stat-title">Ativos</span>
                    <div class="stat-icon success"><i class="fas fa-user-check"></i></div>
                </div>
                <div class="stat-value" id="activeDrivers">-</div>
                <div class="stat-change">Em operação</div>
            </div>
            <div class="stat-card" onclick="filterDrivers('inactive')">
                <div class="stat-header">
                    <span class="stat-title">Inativos</span>
                    <div class="stat-icon danger"><i class="fas fa-user-slash"></i></div>
                </div>
                <div class="stat-value" id="inactiveDrivers">-</div>
                <div class="stat-change">Inativos</div>
            </div>
            <div class="stat-card" onclick="filterDrivers('expiring')">
                <div class="stat-header">
                    <span class="stat-title">Licenças a Expirar</span>
                    <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
                </div>
                <div class="stat-value" id="expiringLicenses">-</div>
                <div class="stat-change">Próximos 30 dias</div>
            </div>
        </div>
        
        <!-- QUICK ACTIONS & FILTERS -->
        <div class="quick-actions">
            <div class="section-header">
                <div class="filter-group">
                    <button class="filter-status-btn active" data-status="all" onclick="filterDrivers('all')">
                        <i class="fas fa-list"></i> Todos
                        <span class="count" id="filterAllCount">0</span>
                    </button>
                    <button class="filter-status-btn" data-status="active" onclick="filterDrivers('active')">
                        <i class="fas fa-check-circle"></i> Ativos
                        <span class="count" id="filterActiveCount">0</span>
                    </button>
                    <button class="filter-status-btn" data-status="inactive" onclick="filterDrivers('inactive')">
                        <i class="fas fa-user-slash"></i> Inativos
                        <span class="count" id="filterInactiveCount">0</span>
                    </button>
                </div>
                <div class="view-toggle">
                    <button class="view-btn active" id="viewGridBtn" onclick="setView('grid')">
                        <i class="fas fa-th"></i>
                    </button>
                    <button class="view-btn" id="viewListBtn" onclick="setView('list')">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- GRID VIEW -->
        <div id="driversGridContainer" class="drivers-grid view-grid"></div>
        
        <!-- LIST VIEW -->
        <div id="driversListContainer" class="drivers-list-container view-list" style="display: none;"></div>
        
        <!-- PAGINATION -->
        <div id="pagination" class="pagination"></div>
        
        <!-- FOOTER NOTE -->
        <div class="footer-note">
            <i class="fas fa-info-circle"></i>
            Clique em um motorista para ver mais detalhes. Use os filtros para organizar a visualização.
        </div>
        
    </div>
</main>

<!-- MODAL ADICIONAR MOTORISTA -->

<div id="addDriverModal" class="modal">
    <div class="modal-content" style="max-width: 750px;">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Adicionar Motorista</h3>
            <button class="modal-close" onclick="closeModal('addDriverModal')">&times;</button>
        </div>
        <form id="addDriverForm">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nome Completo *</label>
                        <input type="text" id="addName" placeholder="Nome do motorista" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> NIF / Documento</label>
                        <input type="text" id="addDocument" placeholder="NIF do motorista">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone-alt"></i> Telefone</label>
                        <input type="tel" id="addPhone" placeholder="Telefone">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="addEmail" placeholder="Email">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Morada</label>
                    <input type="text" id="addAddress" placeholder="Morada completa">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> Carta de Condução *</label>
                        <input type="text" id="addLicenseNumber" placeholder="Número da carta" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Categoria *</label>
                        <select id="addLicenseCategory" required>
                            <option value="AM">AM - Ciclomotores</option>
                            <option value="A1">A1 - Motociclos até 125cc</option>
                            <option value="A2">A2 - Motociclos até 35kW</option>
                            <option value="A">A - Motociclos</option>
                            <option value="B" selected>B - Ligeiros</option>
                            <option value="B+E">B+E - Ligeiros c/ reboque</option>
                            <option value="C1">C1 - Pesados até 7.5t</option>
                            <option value="C1+E">C1+E - Pesados até 7.5t c/ reboque</option>
                            <option value="C">C - Pesados</option>
                            <option value="C+E">C+E - Pesados c/ reboque</option>
                            <option value="D1">D1 - Passageiros até 16 lugares</option>
                            <option value="D1+E">D1+E - Passageiros até 16 lugares c/ reboque</option>
                            <option value="D">D - Passageiros</option>
                            <option value="D+E">D+E - Passageiros c/ reboque</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Data de Emissão</label>
                        <input type="date" id="addLicenseIssueDate">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Data de Validade *</label>
                        <input type="date" id="addLicenseExpiryDate" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Data de Admissão</label>
                        <input type="date" id="addHireDate">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-chart-line"></i> Status</label>
                        <select id="addStatus">
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>
                </div>
                
                <!-- ASSOCIAR VEÍCULOS -->
                <div class="form-group">
                    <label><i class="fas fa-truck"></i> Associar Veículos</label>
                    <div class="vehicle-select-container">
                        <select id="addVehicleId" multiple style="width: 100%; min-height: 120px;">
                            <option value="">Carregando veículos...</option>
                        </select>
                        <small class="help-text">Segure Ctrl (ou Cmd) para selecionar múltiplos veículos</small>
                    </div>
                    <div id="addSelectedVehicles" class="selected-vehicles-tags">
                        <span class="no-vehicles">Nenhum veículo selecionado</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addDriverModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Adicionar Motorista</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR MOTORISTA -->

<div id="editDriverModal" class="modal">
    <div class="modal-content" style="max-width: 750px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Motorista</h3>
            <button class="modal-close" onclick="closeModal('editDriverModal')">&times;</button>
        </div>
        <form id="editDriverForm">
            <input type="hidden" id="editDriverId">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nome Completo *</label>
                        <input type="text" id="editName" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> NIF / Documento</label>
                        <input type="text" id="editDocument">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone-alt"></i> Telefone</label>
                        <input type="tel" id="editPhone">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="editEmail">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Morada</label>
                    <input type="text" id="editAddress">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> Carta de Condução *</label>
                        <input type="text" id="editLicenseNumber" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Categoria *</label>
                        <select id="editLicenseCategory" required>
                            <option value="AM">AM - Ciclomotores</option>
                            <option value="A1">A1 - Motociclos até 125cc</option>
                            <option value="A2">A2 - Motociclos até 35kW</option>
                            <option value="A">A - Motociclos</option>
                            <option value="B">B - Ligeiros</option>
                            <option value="B+E">B+E - Ligeiros c/ reboque</option>
                            <option value="C1">C1 - Pesados até 7.5t</option>
                            <option value="C1+E">C1+E - Pesados até 7.5t c/ reboque</option>
                            <option value="C">C - Pesados</option>
                            <option value="C+E">C+E - Pesados c/ reboque</option>
                            <option value="D1">D1 - Passageiros até 16 lugares</option>
                            <option value="D1+E">D1+E - Passageiros até 16 lugares c/ reboque</option>
                            <option value="D">D - Passageiros</option>
                            <option value="D+E">D+E - Passageiros c/ reboque</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Data de Emissão</label>
                        <input type="date" id="editLicenseIssueDate">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Data de Validade *</label>
                        <input type="date" id="editLicenseExpiryDate" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Data de Admissão</label>
                        <input type="date" id="editHireDate">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-chart-line"></i> Status</label>
                        <select id="editStatus">
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>
                </div>
                
                <!-- ASSOCIAR VEÍCULOS -->
                <div class="form-group">
                    <label><i class="fas fa-truck"></i> Associar Veículos</label>
                    <div class="vehicle-select-container">
                        <select id="editVehicleId" multiple style="width: 100%; min-height: 120px;">
                            <option value="">Carregando veículos...</option>
                        </select>
                        <small class="help-text">Segure Ctrl (ou Cmd) para selecionar múltiplos veículos</small>
                    </div>
                    <div id="editSelectedVehicles" class="selected-vehicles-tags">
                        <span class="no-vehicles">Nenhum veículo selecionado</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editDriverModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DETALHES MOTORISTA -->

<div id="viewDriverModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3><i class="fas fa-user"></i> Detalhes do Motorista</h3>
            <button class="modal-close" onclick="closeModal('viewDriverModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailModalContent">
            <div class="loading-spinner"><div class="spinner"></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('viewDriverModal')">Fechar</button>
            <button class="btn-primary" id="editFromDetailsBtn">
                <i class="fas fa-edit"></i> Editar
            </button>
        </div>
    </div>
</div>

<!-- MODAL CONFIRMAÇÃO DELETE -->

<div id="deleteConfirmModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3><i class="fas fa-trash-alt"></i> Confirmar Eliminação</h3>
            <button class="modal-close" onclick="closeModal('deleteConfirmModal')">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--danger); margin-bottom: 16px;"></i>
            <p>Tem certeza que deseja eliminar este motorista?</p>
            <p style="font-size: 0.85rem; color: var(--gray);">Esta ação não pode ser desfeita.</p>
        </div>
        <div class="modal-footer" style="justify-content: center;">
            <button class="btn-secondary" onclick="closeModal('deleteConfirmModal')">Cancelar</button>
            <button class="btn-danger" id="confirmDeleteBtn">Eliminar</button>
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

<script src="/cardoxis/public/assets/js/dashboard/drivers.js"></script>

</body>
</html>