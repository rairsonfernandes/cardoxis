<?php
/**
 * CARDOXIS - Página de Gestão de Veículos RF 
 */

// Verificar autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Veículos | CARDOXIS';
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
    <meta name="description" content="Gestão de veículos - CARDOXIS">
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
    <link rel="stylesheet" href="<?= asset('css/Dashboard/vehicles.css') ?>">
    
    <!-- Configurações Globais -->
    <script>
        window.CARDOXIS = {
            API_URL: 'http://localhost/cardoxis/api/v1',
            ASSETS_URL: '<?= asset('') ?>',
            CSRF_TOKEN: '<?= $_SESSION['csrf_token'] ?? '' ?>',
            USER: {
                id: <?= $_SESSION['user_id'] ?? 0 ?>,
                name: '<?= htmlspecialchars($userName) ?>',
                role: '<?= htmlspecialchars($userRole) ?>'
            }
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
    <div class="dashboard-container">
        <!-- HEADER -->
        <div class="dashboard-header">
            <div class="header-top">
               <h1 class="header-title"><i class="fas fa-car"></i>Veículos</h1>
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Pesquisar por marca, modelo ou matrícula...">
                </div>
                <div class="header-actions">
                    <button class="btn-icon-refresh" onclick="loadVehicles()">
                        <i class="fas fa-sync-alt"></i>
                        <span>Atualizar</span>
                    </button>
                    <button class="btn-primary" onclick="openAddVehicleModal()">
                        <i class="fas fa-plus"></i>
                        <span>Novo Veículo</span>
                    </button>
                </div>
            </div>
            <div class="header-bottom">
                <div class="welcome-section">

                    <p class="welcome-subtitle">Gerencie todos os veículos da sua frota</p>
                </div>
                <div class="last-update">
                    <i class="fas fa-clock"></i>
                    <span>Última atualização: <span id="lastUpdateTime">-</span></span>
                </div>
            </div>
        </div>
        
        <!-- STATS CARDS -->
        <div class="stats-grid">
            <div class="stat-card" data-status="all" onclick="filterVehicles('all')">
                <div class="stat-header">
                    <span class="stat-title">Total de Veículos</span>
                    <div class="stat-icon primary"><i class="fas fa-car"></i></div>
                </div>
                <div class="stat-value" id="totalVehicles">-</div>
                <div class="stat-change">Todos os veículos</div>
            </div>
            <div class="stat-card" data-status="active" onclick="filterVehicles('active')">
                <div class="stat-header">
                    <span class="stat-title">Veículos Ativos</span>
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-value" id="activeVehicles">-</div>
                <div class="stat-change">Em circulação</div>
            </div>
            <div class="stat-card" data-status="maintenance" onclick="filterVehicles('maintenance')">
                <div class="stat-header">
                    <span class="stat-title">Em Manutenção</span>
                    <div class="stat-icon warning"><i class="fas fa-tools"></i></div>
                </div>
                <div class="stat-value" id="maintenanceVehicles">-</div>
                <div class="stat-change">Em reparação</div>
            </div>
            <div class="stat-card" data-status="inactive" onclick="filterVehicles('inactive')">
                <div class="stat-header">
                    <span class="stat-title">Veículos Inativos</span>
                    <div class="stat-icon danger"><i class="fas fa-minus-circle"></i></div>
                </div>
                <div class="stat-value" id="inactiveVehicles">-</div>
                <div class="stat-change">Fora de serviço</div>
            </div>
        </div>
        
        <!-- QUICK ACTIONS & FILTERS -->
        <div class="quick-actions">
            <div class="section-header">
                <div class="filter-section">
                    <div class="filter-group">
                        <button class="filter-status-btn active" data-status="all" onclick="filterVehicles('all')">
                            <i class="fas fa-list"></i> Todos
                            <span class="count" id="filterAllCount">0</span>
                        </button>
                        <button class="filter-status-btn" data-status="active" onclick="filterVehicles('active')">
                            <i class="fas fa-check-circle"></i> Ativos
                            <span class="count" id="filterActiveCount">0</span>
                        </button>
                        <button class="filter-status-btn" data-status="maintenance" onclick="filterVehicles('maintenance')">
                            <i class="fas fa-tools"></i> Manutenção
                            <span class="count" id="filterMaintenanceCount">0</span>
                        </button>
                        <button class="filter-status-btn" data-status="inactive" onclick="filterVehicles('inactive')">
                            <i class="fas fa-minus-circle"></i> Inativos
                            <span class="count" id="filterInactiveCount">0</span>
                        </button>
                    </div>
                </div>
                <div class="view-toggle">
                    <button class="view-btn active" data-view="grid" onclick="setView('grid')">
                        <i class="fas fa-th-large"></i> Grid
                    </button>
                    <button class="view-btn" data-view="list" onclick="setView('list')">
                        <i class="fas fa-list"></i> Lista
                    </button>
                </div>
            </div>
        </div>
        
        <!-- VEHICLES CONTAINER -->
        <div id="vehiclesContainer">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Carregando veículos...</p>
            </div>
        </div>
        
        <!-- PAGINATION -->
        <div class="pagination" id="pagination"></div>
        
        <div class="footer-note">
            <i class="fas fa-info-circle"></i> Clique em um veículo para ver mais detalhes
        </div>
    </div>
</main>

<!-- ============================================ -->
<!-- MODAL ADICIONAR VEÍCULO -->
<!-- ============================================ -->
<div id="addVehicleModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-car"></i> Adicionar Veículo</h3>
            <button class="modal-close" onclick="closeModal('addVehicleModal')">&times;</button>
        </div>
        <form id="addVehicleForm" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-car"></i> Marca *</label>
                    <input type="text" name="brand" id="addVehicleBrand" placeholder="Ex: Toyota" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-car-side"></i> Modelo *</label>
                    <input type="text" name="model" id="addVehicleModel" placeholder="Ex: Hilux" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> Matrícula *</label>
                    <input type="text" name="plate" id="addVehiclePlate" placeholder="Ex: AB-12-CD" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Ano</label>
                    <input type="number" name="year" id="addVehicleYear" placeholder="2024" min="1900" max="2025">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-palette"></i> Cor</label>
                    <input type="text" name="color" id="addVehicleColor" placeholder="Ex: Branco">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-gas-pump"></i> Combustível</label>
                    <select name="fuel_type" id="addVehicleFuel">
                        <option value="diesel">Diesel</option>
                        <option value="gasoline">Gasolina</option>
                        <option value="electric">Elétrico</option>
                        <option value="hybrid">Híbrido</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-chart-line"></i> Status</label>
                    <select name="status" id="addVehicleStatus">
                        <option value="active">Em Operação</option>
                        <option value="maintenance">Manutenção</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-road"></i> KM Atual</label>
                    <input type="number" name="odometer" id="addVehicleKm" placeholder="0" value="0" min="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group full-width">
                    <label><i class="fas fa-qrcode"></i> Número de Chassis (VIN)</label>
                    <input type="text" name="chassis" id="addVehicleChassis" placeholder="Número de chassis do veículo">
                </div>
            </div>
            <div class="form-group full-width">
                <label><i class="fas fa-image"></i> Imagem do Veículo</label>
                <div class="image-upload-area" onclick="document.getElementById('addVehicleImage').click()">
                    <input type="file" name="image" id="addVehicleImage" accept="image/jpeg,image/png,image/jpg,image/webp" style="display: none;">
                    <div class="upload-placeholder" id="addUploadPlaceholder">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Clique para selecionar uma imagem</p>
                        <span>PNG, JPG, WEBP até 5MB</span>
                    </div>
                    <div class="image-preview-wrapper" id="addImagePreviewWrapper" style="display: none;">
                        <img id="addImagePreview" src="">
                        <button type="button" class="remove-image" onclick="removeAddImage()"><i class="fas fa-trash-alt"></i> Remover</button>
                    </div>
                </div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-secondary" onclick="closeModal('addVehicleModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Adicionar Veículo</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL EDIÇÃO VEÍCULO -->
<!-- ============================================ -->
<div id="editVehicleModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Veículo</h3>
            <button class="modal-close" onclick="closeModal('editVehicleModal')">&times;</button>
        </div>
        <form id="editVehicleForm" enctype="multipart/form-data">
            <input type="hidden" id="editVehicleId">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-car"></i> Marca *</label>
                    <input type="text" id="editVehicleBrand" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-car-side"></i> Modelo *</label>
                    <input type="text" id="editVehicleModel" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> Matrícula *</label>
                    <input type="text" id="editVehiclePlate" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Ano</label>
                    <input type="number" id="editVehicleYear" min="1900" max="2025">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-palette"></i> Cor</label>
                    <input type="text" id="editVehicleColor">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-gas-pump"></i> Combustível</label>
                    <select id="editVehicleFuel">
                        <option value="diesel">Diesel</option>
                        <option value="gasoline">Gasolina</option>
                        <option value="electric">Elétrico</option>
                        <option value="hybrid">Híbrido</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-chart-line"></i> Status</label>
                    <select id="editVehicleStatus">
                        <option value="active">Em Operação</option>
                        <option value="maintenance">Manutenção</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-road"></i> KM Atual</label>
                    <input type="number" id="editVehicleKm" min="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group full-width">
                    <label><i class="fas fa-qrcode"></i> Número de Chassis (VIN)</label>
                    <input type="text" id="editVehicleChassis" placeholder="Número de chassis do veículo">
                </div>
            </div>
            <div class="form-group full-width">
                <label><i class="fas fa-image"></i> Imagem do Veículo</label>
                <div class="image-upload-area" onclick="document.getElementById('editVehicleImage').click()">
                    <input type="file" id="editVehicleImage" accept="image/jpeg,image/png,image/jpg,image/webp" style="display: none;">
                    <div class="upload-placeholder" id="editUploadPlaceholder">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Clique para selecionar uma imagem</p>
                        <span>PNG, JPG, WEBP até 5MB</span>
                    </div>
                    <div class="image-preview-wrapper" id="editImagePreviewWrapper" style="display: none;">
                        <img id="editImagePreview" src="">
                        <button type="button" class="remove-image" onclick="removeEditImage()"><i class="fas fa-trash-alt"></i> Remover</button>
                    </div>
                </div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-secondary" onclick="closeModal('editVehicleModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL DETALHES VEÍCULO -->
<!-- ============================================ -->
<div id="vehicleDetailsModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-car"></i> <span id="modalTitle">Detalhes do Veículo</span></h3>
            <button class="modal-close" onclick="closeModal('vehicleDetailsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="modalContent"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-danger" id="deleteFromDetailsBtn" onclick="confirmDeleteVehicle()">
                <i class="fas fa-trash-alt"></i> Eliminar
            </button>
            <button class="btn-secondary" onclick="closeModal('vehicleDetailsModal')">Fechar</button>
            <button class="btn-primary" id="editFromDetailsBtn">
                <i class="fas fa-edit"></i> Editar
            </button>
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
            <p class="modal-message">Tem certeza que deseja eliminar este veículo?</p>
            <p style="font-size: 0.85rem; color: var(--gray);">Esta ação não pode ser desfeita e todos os dados associados serão removidos.</p>
        </div>
        <div class="modal-footer" style="justify-content: center;">
            <button type="button" class="btn-secondary" onclick="closeModal('deleteConfirmModal')">Cancelar</button>
            <button type="button" class="btn-danger" id="confirmDeleteBtn">Eliminar</button>
        </div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div id="toastContainer" class="toast-container"></div>

<!-- JavaScript -->
<script src="<?= asset('js/dashboard/vehicles.js') ?>"></script>
<script src="<?= asset('js/dashboard/avatar.js') ?>"></script>


<script>
// Funções auxiliares globais
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
    document.body.style.overflow = '';
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function removeAddImage() {
    const wrapper = document.getElementById('addImagePreviewWrapper');
    const placeholder = document.getElementById('addUploadPlaceholder');
    const input = document.getElementById('addVehicleImage');
    if (wrapper) wrapper.style.display = 'none';
    if (placeholder) placeholder.style.display = 'block';
    if (input) input.value = '';
}

function removeEditImage() {
    const wrapper = document.getElementById('editImagePreviewWrapper');
    const placeholder = document.getElementById('editUploadPlaceholder');
    const input = document.getElementById('editVehicleImage');
    if (wrapper) wrapper.style.display = 'none';
    if (placeholder) placeholder.style.display = 'block';
    if (input) input.value = '';
}

function showToast(type, message) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${escapeHtml(message)}</span>
        <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentElement) toast.remove();
    }, 5000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    if (!token) {
        window.location.href = '/cardoxis/login';
        return;
    }
    
    // Inicializar sidebar
    initSidebar();
    
    // Carregar veículos
    loadVehicles();
});
</script>
</body>
</html>