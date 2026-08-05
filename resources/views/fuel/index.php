<?php
/**
 * CARDOXIS - Fuel RF
 */

if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Gestão de Combustível | CARDOXIS';

$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'user';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));

// Funções auxiliares
function getFuelTypeText($type) {
    $texts = [
        'diesel' => 'Gasóleo',
        'gasoline' => 'Gasolina',
        'electric' => 'Elétrico',
        'hybrid' => 'Híbrido',
        'gpl' => 'GPL'
    ];
    return $texts[$type] ?? $type;
}

function getFuelTypeClass($type) {
    $classes = [
        'diesel' => 'diesel',
        'gasoline' => 'gasoline',
        'electric' => 'electric',
        'hybrid' => 'hybrid',
        'gpl' => 'gpl'
    ];
    return $classes[$type] ?? 'diesel';
}

function getStatusText($status) {
    $texts = [
        'pending' => 'Pendente',
        'approved' => 'Aprovado',
        'rejected' => 'Rejeitado'
    ];
    return $texts[$status] ?? $status;
}

function getStatusClass($status) {
    $classes = [
        'pending' => 'pending',
        'approved' => 'approved',
        'rejected' => 'rejected'
    ];
    return $classes[$status] ?? 'pending';
}

function formatMoney($value) {
    if (!$value || $value == 0) return '€ 0,00';
    return '€ ' . number_format($value, 2, ',', '.');
}

function formatDate($date) {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($date) {
    if (!$date) return '-';
    return date('d/m/Y H:i', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0052CC">
    <meta name="description" content="Gestão de combustível CARDOXIS">
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
    
	
    <!-- CSS -->
    <link rel="stylesheet" href="<?= asset('css/Dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/fuel.css') ?>">
    
    <script>
        window.userName = '<?= htmlspecialchars($userName) ?>';
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
    <div class="fuel-container">
        
        <!-- HEADER -->
        <div class="fuel-header">
            <div class="header-top">
                <h1 class="header-title"><i class="fas fa-gas-pump"></i>Combustível</h1>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearch" placeholder="Pesquisar veículos, postos...">
                </div>
                <div class="header-actions">
                    <button class="btn-icon-refresh" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Atualizar</button>
                    <button class="btn-primary" onclick="openAddFuelModal()"><i class="fas fa-plus"></i> Novo Abastecimento</button>
                </div>
            </div>
            <div class="header-bottom">
                <div>
                    <h2 class="welcome-title">Olá, <span class="user-highlight"><?= htmlspecialchars($userName) ?></span> <span class="welcome-emoji">👋</span></h2>
                    <p class="welcome-subtitle">Gerencie todos os abastecimentos da sua frota de forma eficiente.</p>
                </div>
                <div class="last-update">
                    <i class="fas fa-clock"></i>
                    <span>Última atualização: <span id="lastUpdateTime">-</span></span>
                </div>
            </div>
        </div>

        <!-- STATS GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Abastecimentos</span>
                    <div class="stat-icon primary"><i class="fas fa-gas-pump"></i></div>
                </div>
                <div class="stat-value" id="totalEntries">0</div>
                <div class="stat-change">Registados</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Litros</span>
                    <div class="stat-icon success"><i class="fas fa-flask"></i></div>
                </div>
                <div class="stat-value" id="totalLiters">0 L</div>
                <div class="stat-change">Consumidos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Custo Total</span>
                    <div class="stat-icon warning"><i class="fas fa-euro-sign"></i></div>
                </div>
                <div class="stat-value" id="totalCost">€ 0,00</div>
                <div class="stat-change">Gastos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Preço Médio</span>
                    <div class="stat-icon info"><i class="fas fa-chart-line"></i></div>
                </div>
                <div class="stat-value" id="avgPrice">€ 0,000</div>
                <div class="stat-change">Por litro</div>
            </div>
        </div>

        <!-- FILTERS -->
        <div class="filters-bar">
            <div class="filter-group">
                <button class="filter-btn active" data-status="all" onclick="filterFuel('all')">Todos</button>
                <button class="filter-btn" data-status="approved" onclick="filterFuel('approved')">Aprovados</button>
                <button class="filter-btn" data-status="pending" onclick="filterFuel('pending')">Pendentes</button>
                <button class="filter-btn" data-status="rejected" onclick="filterFuel('rejected')">Rejeitados</button>
            </div>
            <div class="filter-group">
                <button class="filter-btn active" data-type="all" onclick="filterFuelByType('all')">Todos</button>
                <button class="filter-btn" data-type="diesel" onclick="filterFuelByType('diesel')">Gasóleo</button>
                <button class="filter-btn" data-type="gasoline" onclick="filterFuelByType('gasoline')">Gasolina</button>
                <button class="filter-btn" data-type="electric" onclick="filterFuelByType('electric')">Elétrico</button>
                <button class="filter-btn" data-type="hybrid" onclick="filterFuelByType('hybrid')">Híbrido</button>
                <button class="filter-btn" data-type="gpl" onclick="filterFuelByType('gpl')">GPL</button>
            </div>
        </div>

        <!-- TABLE -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-list"></i> Lista de Abastecimentos</div>
                <div class="table-info">
                    <span>Total: <strong id="totalCount">0</strong></span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="fuel-table">
                        <thead>
                            <tr>
                                <th>Veículo</th>
                                <th>Motorista</th>
                                <th>Data</th>
                                <th>Litros</th>
                                <th>Preço/L</th>
                                <th>Total</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="fuelTableBody">
                            <!-- Renderizado via JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div class="pagination" id="pagination">
                    <!-- Renderizado via JavaScript -->
                </div>
            </div>
        </div>

        <!-- TOP VEHICLE -->
        <div class="card" id="topVehicleCard" style="display:none;">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-trophy" style="color:#FF8B00;"></i> Veículo com Mais Abastecimentos</div>
            </div>
            <div class="card-body" id="topVehicleContent">
                <!-- Renderizado via JavaScript -->
            </div>
        </div>

    </div>
</main>

<!-- MODAL: ADICIONAR ABASTECIMENTO -->
<div id="addFuelModal" class="modal">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Novo Abastecimento</h3>
            <button class="modal-close" onclick="closeModal('addFuelModal')">&times;</button>
        </div>
        <form id="addFuelForm">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-car"></i> Veículo *</label>
                        <select id="addVehicleId" required>
                            <option value="">Selecione um veículo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Motorista</label>
                        <select id="addDriverId">
                            <option value="">Selecione um motorista</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Data *</label>
                        <input type="date" id="addDate" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Tipo *</label>
                        <select id="addFuelType" required>
                            <option value="diesel">Gasóleo</option>
                            <option value="gasoline">Gasolina</option>
                            <option value="electric">Elétrico</option>
                            <option value="hybrid">Híbrido</option>
                            <option value="gpl">GPL</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-flask"></i> Litros *</label>
                        <input type="number" id="addLiters" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-euro-sign"></i> Preço/L *</label>
                        <input type="number" id="addPricePerLiter" step="0.001" placeholder="0.000" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-road"></i> Odômetro *</label>
                        <input type="number" id="addOdometer" placeholder="0" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-store"></i> Posto</label>
                        <input type="text" id="addStationName" placeholder="Nome do posto">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-receipt"></i> Fatura Nº</label>
                        <input type="text" id="addInvoiceNumber" placeholder="Número da fatura">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-chart-line"></i> Status</label>
                        <select id="addStatus">
                            <option value="approved">Aprovado</option>
                            <option value="pending">Pendente</option>
                            <option value="rejected">Rejeitado</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label><i class="fas fa-sticky-note"></i> Observações</label>
                        <textarea id="addNotes" rows="2" placeholder="Observações adicionais..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addFuelModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Registrar Abastecimento</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDITAR ABASTECIMENTO -->
<div id="editFuelModal" class="modal">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Abastecimento</h3>
            <button class="modal-close" onclick="closeModal('editFuelModal')">&times;</button>
        </div>
        <form id="editFuelForm">
            <input type="hidden" id="editFuelId">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-car"></i> Veículo *</label>
                        <select id="editVehicleId" required>
                            <option value="">Selecione um veículo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Motorista</label>
                        <select id="editDriverId">
                            <option value="">Selecione um motorista</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Data *</label>
                        <input type="date" id="editDate" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Tipo *</label>
                        <select id="editFuelType" required>
                            <option value="diesel">Gasóleo</option>
                            <option value="gasoline">Gasolina</option>
                            <option value="electric">Elétrico</option>
                            <option value="hybrid">Híbrido</option>
                            <option value="gpl">GPL</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-flask"></i> Litros *</label>
                        <input type="number" id="editLiters" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-euro-sign"></i> Preço/L *</label>
                        <input type="number" id="editPricePerLiter" step="0.001" placeholder="0.000" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-road"></i> Odômetro *</label>
                        <input type="number" id="editOdometer" placeholder="0" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-store"></i> Posto</label>
                        <input type="text" id="editStationName" placeholder="Nome do posto">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-receipt"></i> Fatura Nº</label>
                        <input type="text" id="editInvoiceNumber" placeholder="Número da fatura">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-chart-line"></i> Status</label>
                        <select id="editStatus">
                            <option value="approved">Aprovado</option>
                            <option value="pending">Pendente</option>
                            <option value="rejected">Rejeitado</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label><i class="fas fa-sticky-note"></i> Observações</label>
                        <textarea id="editNotes" rows="2" placeholder="Observações adicionais..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editFuelModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: VISUALIZAR DETALHES -->
<div id="viewFuelModal" class="modal">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3><i class="fas fa-gas-pump"></i> Detalhes do Abastecimento</h3>
            <button class="modal-close" onclick="closeModal('viewFuelModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailModalContent">
            <div class="loading-spinner"><div class="spinner"></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn-danger" id="deleteFromDetailsBtn"><i class="fas fa-trash-alt"></i> Eliminar</button>
            <button class="btn-secondary" onclick="closeModal('viewFuelModal')">Fechar</button>
            <button class="btn-primary" id="editFromDetailsBtn"><i class="fas fa-edit"></i> Editar</button>
        </div>
    </div>
</div>

<!-- MODAL: CONFIRMAÇÃO DELETE -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content" style="max-width:400px;">
        <div class="modal-header">
            <h3><i class="fas fa-trash-alt"></i> Confirmar Eliminação</h3>
            <button class="modal-close" onclick="closeModal('deleteConfirmModal')">&times;</button>
        </div>
        <div class="modal-body" style="text-align:center;">
            <i class="fas fa-exclamation-triangle" style="font-size:3rem;color:var(--danger);margin-bottom:16px;"></i>
            <p style="font-weight:600;font-size:1.1rem;" id="confirmMessage">Tem certeza que deseja eliminar este registo?</p>
            <p style="font-size:0.85rem;color:var(--gray);">Esta ação não pode ser desfeita.</p>
        </div>
        <div class="modal-footer" style="justify-content:center;">
            <button class="btn-cancel" onclick="closeModal('deleteConfirmModal')">Cancelar</button>
            <button class="btn-danger" id="confirmDeleteBtn">Eliminar</button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div id="toastContainer" class="toast-container"></div>

<!-- FUEL JS -->
<script src="<?= asset('js/dashboard/fuel.js') ?>"></script>

</body>
</html>