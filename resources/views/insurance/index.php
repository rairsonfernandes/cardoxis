<?php
/**
 * CARDOXIS - Gestão de Seguros RF 
 */

if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: /cardoxis/login');
    exit;
}

$pageTitle = 'Seguros | CARDOXIS';
$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userRole = $_SESSION['user_role'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0052CC">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    

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
    <link rel="stylesheet" href="/cardoxis/public/assets/css/Dashboard/dashboard.css">
    <link rel="stylesheet" href="/cardoxis/public/assets/css/Dashboard/insurance.css">
	
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
                <div class="header-title-group">
                    <h1 class="header-title">
                        <i class="fas fa-shield-alt"></i>
                        Seguros
                    </h1>
                </div>
                
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearch" placeholder="Pesquisar por apólice, seguradora ou veículo...">
                </div>
                
                <div class="header-actions">
                    <button class="btn-secondary" onclick="location.reload()" title="Atualizar">
                        <i class="fas fa-sync-alt"></i>
                        <span>Atualizar</span>
                    </button>
                    <button class="btn-primary" onclick="openAddInsuranceModal()">
                        <i class="fas fa-plus"></i>
                        <span>Novo Seguro</span>
                    </button>
                </div>
            </div>
            
            <div class="header-bottom">
                <div class="welcome-section">
                    <p class="welcome-subtitle">Controle todos os seguros da sua frota</p>
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
                    <span class="stat-title">Total de Seguros</span>
                    <div class="stat-icon primary"><i class="fas fa-shield-alt"></i></div>
                </div>
                <div class="stat-value" id="totalPolicies">0</div>
                <div class="stat-change">Apólices</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Seguros Ativos</span>
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-value" id="activePolicies">0</div>
                <div class="stat-change">Em vigor</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">A Vencer (30 dias)</span>
                    <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
                </div>
                <div class="stat-value" id="expiringSoon">0</div>
                <div class="stat-change">Atenção!</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Prémio Total</span>
                    <div class="stat-icon info"><i class="fas fa-euro-sign"></i></div>
                </div>
                <div class="stat-value" id="totalPremium">€ 0</div>
                <div class="stat-change">Acumulado</div>
            </div>
        </div>
        
        <!-- FILTERS -->
        <div class="quick-actions">
            <div class="section-header">
                <div class="filter-group">
                    <button class="filter-btn active" data-status="all" onclick="filterInsurance('all')">
                        <i class="fas fa-list"></i> Todos
                    </button>
                    <button class="filter-btn" data-status="active" onclick="filterInsurance('active')">
                        <i class="fas fa-check-circle"></i> Ativos
                    </button>
                    <button class="filter-btn" data-status="expired" onclick="filterInsurance('expired')">
                        <i class="fas fa-times-circle"></i> Expirados
                    </button>
                    <button class="filter-btn" data-status="expiring_soon" onclick="filterInsurance('expiring_soon')">
                        <i class="fas fa-clock"></i> A Vencer
                    </button>
                </div>
                <div class="filter-group">
                    <button class="filter-btn" data-type="all" onclick="filterInsuranceByType('all')">
                        <i class="fas fa-tag"></i> Todos
                    </button>
                    <button class="filter-btn" data-type="comprehensive" onclick="filterInsuranceByType('comprehensive')">
                        <i class="fas fa-shield-alt"></i> Danos Próprios
                    </button>
                    <button class="filter-btn" data-type="liability" onclick="filterInsuranceByType('liability')">
                        <i class="fas fa-handshake"></i> Responsabilidade
                    </button>
                    <button class="filter-btn" data-type="partial" onclick="filterInsuranceByType('partial')">
                        <i class="fas fa-umbrella"></i> Parcial
                    </button>
                    <button class="filter-btn" data-type="fleet" onclick="filterInsuranceByType('fleet')">
                        <i class="fas fa-truck"></i> Frota
                    </button>
                </div>
            </div>
        </div>
        
        <!-- TABLE -->
        <div class="insurance-table-container" id="tableContainer">
            <table class="insurance-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-car"></i> Veículo</th>
                        <th><i class="fas fa-building"></i> Seguradora</th>
                        <th><i class="fas fa-hashtag"></i> Apólice</th>
                        <th><i class="fas fa-tag"></i> Tipo</th>
                        <th><i class="fas fa-calendar"></i> Início</th>
                        <th><i class="fas fa-calendar-alt"></i> Fim</th>
                        <th><i class="fas fa-euro-sign"></i> Prémio</th>
                        <th><i class="fas fa-chart-line"></i> Status</th>
                        <th><i class="fas fa-cog"></i> Ações</th>
                    </tr>
                </thead>
                <tbody id="insuranceTableBody">
                    <tr>
                        <td colspan="9" style="text-align:center; padding:60px;">
                            <div class="spinner"></div>
                            <p style="margin-top:16px; color:#5a6b7c;">Carregando seguros...</p>
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
            Clique em um seguro para ver mais detalhes. Use os filtros para organizar a visualização.
        </div>
        
    </div>
</main>

<!-- MODAL ADICIONAR -->
<div id="addInsuranceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Novo Seguro</h3>
            <button class="modal-close" onclick="closeModal('addInsuranceModal')">&times;</button>
        </div>
        <form id="addInsuranceForm">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-car"></i> Veículo *</label>
                        <select id="addVehicleId" required>
                            <option value="">Selecione um veículo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Seguradora *</label>
                        <input type="text" id="addInsuranceCompany" required placeholder="Nome da seguradora">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> Número da Apólice *</label>
                        <input type="text" id="addPolicyNumber" required placeholder="Número da apólice">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Tipo *</label>
                        <select id="addType">
                            <option value="comprehensive">Danos Próprios</option>
                            <option value="liability">Responsabilidade Civil</option>
                            <option value="partial">Parcial</option>
                            <option value="fleet">Frota</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Data Início *</label>
                        <input type="date" id="addStartDate" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Data Fim *</label>
                        <input type="date" id="addEndDate" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-euro-sign"></i> Prémio (€) *</label>
                        <input type="number" id="addPremiumAmount" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-shield-alt"></i> Franquia (€)</label>
                        <input type="number" id="addDeductible" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Beneficiário</label>
                        <input type="text" id="addBeneficiary" placeholder="Nome do beneficiário">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-handshake"></i> Corretor</label>
                        <input type="text" id="addBrokerName" placeholder="Nome do corretor">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Telefone Corretor</label>
                        <input type="text" id="addBrokerPhone" placeholder="Telefone do corretor">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Corretor</label>
                        <input type="email" id="addBrokerEmail" placeholder="Email do corretor">
                    </div>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-align-left"></i> Coberturas</label>
                    <textarea id="addCoverageDetails" rows="2" placeholder="Detalhes das coberturas..."></textarea>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-sticky-note"></i> Observações</label>
                    <textarea id="addNotes" rows="2" placeholder="Observações adicionais..."></textarea>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-file-alt"></i> Documento da Apólice</label>
                    <div class="image-upload-area" onclick="document.getElementById('addDocument').click()">
                        <input type="file" id="addDocument" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                        <div class="upload-placeholder" id="addUploadPlaceholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Clique para fazer upload do documento</p>
                            <span>PDF, JPEG ou PNG até 5MB</span>
                        </div>
                        <div class="image-preview-wrapper" id="addPreviewWrapper" style="display:none;">
                            <i class="fas fa-file-pdf" style="font-size:3rem; color:var(--danger);"></i>
                            <p id="addFileName" style="margin-top:8px;"></p>
                            <button type="button" class="remove-image" onclick="removeAddDocument()">
                                <i class="fas fa-trash-alt"></i> Remover
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addInsuranceModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR -->
<div id="editInsuranceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Seguro</h3>
            <button class="modal-close" onclick="closeModal('editInsuranceModal')">&times;</button>
        </div>
        <form id="editInsuranceForm">
            <div class="modal-body">
                <input type="hidden" id="editInsuranceId">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-car"></i> Veículo *</label>
                        <select id="editVehicleId" required>
                            <option value="">Selecione um veículo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Seguradora *</label>
                        <input type="text" id="editInsuranceCompany" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> Número da Apólice *</label>
                        <input type="text" id="editPolicyNumber" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Tipo *</label>
                        <select id="editType">
                            <option value="comprehensive">Danos Próprios</option>
                            <option value="liability">Responsabilidade Civil</option>
                            <option value="partial">Parcial</option>
                            <option value="fleet">Frota</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Data Início *</label>
                        <input type="date" id="editStartDate" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Data Fim *</label>
                        <input type="date" id="editEndDate" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-euro-sign"></i> Prémio (€) *</label>
                        <input type="number" id="editPremiumAmount" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-shield-alt"></i> Franquia (€)</label>
                        <input type="number" id="editDeductible" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Beneficiário</label>
                        <input type="text" id="editBeneficiary">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-handshake"></i> Corretor</label>
                        <input type="text" id="editBrokerName">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Telefone Corretor</label>
                        <input type="text" id="editBrokerPhone">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Corretor</label>
                        <input type="email" id="editBrokerEmail">
                    </div>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-align-left"></i> Coberturas</label>
                    <textarea id="editCoverageDetails" rows="2"></textarea>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-sticky-note"></i> Observações</label>
                    <textarea id="editNotes" rows="2"></textarea>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-file-alt"></i> Documento da Apólice</label>
                    <div class="image-upload-area" onclick="document.getElementById('editDocument').click()">
                        <input type="file" id="editDocument" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                        <div class="upload-placeholder" id="editUploadPlaceholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Clique para substituir o documento</p>
                            <span>PDF, JPEG ou PNG até 5MB</span>
                        </div>
                        <div class="image-preview-wrapper" id="editPreviewWrapper" style="display:none;">
                            <i class="fas fa-file-pdf" style="font-size:3rem; color:var(--danger);"></i>
                            <p id="editFileName" style="margin-top:8px;"></p>
                            <button type="button" class="remove-image" onclick="removeEditDocument()">
                                <i class="fas fa-trash-alt"></i> Remover
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editInsuranceModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Atualizar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DETALHES -->
<div id="viewInsuranceModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Detalhes do Seguro</h3>
            <button class="modal-close" onclick="closeModal('viewInsuranceModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailModalContent">
            <div class="loading-spinner"><div class="spinner"></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('viewInsuranceModal')">Fechar</button>
            <button class="btn-primary" id="editFromDetailsBtn">Editar</button>
            <button class="btn-danger" id="deleteFromDetailsBtn">Eliminar</button>
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
        <div class="modal-body" style="text-align:center; padding:30px;">
            <i class="fas fa-exclamation-triangle" style="font-size:3rem; color:var(--danger); margin-bottom:16px;"></i>
            <p id="confirmMessage" style="font-size:1.1rem; margin-bottom:8px;">Tem certeza que deseja eliminar este seguro?</p>
            <p style="color:var(--gray); font-size:0.85rem;">Esta ação não pode ser desfeita.</p>
        </div>
        <div class="modal-footer" style="justify-content:center;">
            <button class="btn-cancel" onclick="closeModal('deleteConfirmModal')">Cancelar</button>
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
</script>

<script src="/cardoxis/public/assets/js/dashboard/insurance.js"></script>

</body>
</html>