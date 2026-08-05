<?php
// Verificar autenticação
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Gestão de Empresas | CARDOXIS Portugal';
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0052CC">
    <meta name="description" content="Gestão de empresas - CARDOXIS Portugal">
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
    <link rel="stylesheet" href="<?= asset('css/admin/companies.css') ?>">
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
        <div class="mobile-logo-text">CARDOXIS<span>.pt</span></div>
    </div>
    <div class="mobile-actions">
        <button class="btn-icon-refresh" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i>
            <span>Actualizar</span>
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
                <h1 class="page-title">Gestão de Empresas</h1>
                <p class="page-subtitle">Gerencie todas as empresas da plataforma</p>
            </div>
            <div class="page-actions">
                <button class="btn-secondary" onclick="exportCompanies()">
                    <i class="fas fa-download"></i> Exportar
                </button>
                <button class="btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Nova Empresa
                </button>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Empresas</span>
                    <div class="stat-icon primary"><i class="fas fa-building"></i></div>
                </div>
                <div class="stat-value" id="totalCompanies">-</div>
                <div class="stat-change">Total registadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Activas</span>
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-value" id="activeCompanies">-</div>
                <div class="stat-change">Empresas activas</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Inactivas</span>
                    <div class="stat-icon warning"><i class="fas fa-minus-circle"></i></div>
                </div>
                <div class="stat-value" id="inactiveCompanies">-</div>
                <div class="stat-change">Empresas inactivas</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Suspensas</span>
                    <div class="stat-icon danger"><i class="fas fa-pause-circle"></i></div>
                </div>
                <div class="stat-value" id="suspendedCompanies">-</div>
                <div class="stat-change">Empresas suspensas</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-bar">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Pesquisar por nome ou NIF..." autocomplete="off">
            </div>
            <select id="statusFilter" class="filter-select" onchange="filterByStatus()">
                <option value="">Todos os estados</option>
                <option value="active">Activas</option>
                <option value="inactive">Inactivas</option>
                <option value="suspended">Suspensas</option>
            </select>
            <button class="btn-secondary" onclick="resetFilters()">
                <i class="fas fa-undo-alt"></i> Limpar Filtros
            </button>
            <button class="btn-primary" onclick="searchCompanies()">
                <i class="fas fa-search"></i> Pesquisar
            </button>
        </div>
        
        <!-- Companies Table -->
        <div class="companies-table-container">
            <table class="companies-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Data Registo</th>
                        <th>Estado</th>
                        <th width="140">Acções</th>
                    </tr>
                </thead>
                <tbody id="companiesTableBody">
                    <tr>
                        <td colspan="7">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <div class="pagination-info" id="pageInfo"></div>
            <div id="pagination"></div>
        </div>
    </div>
</main>

<!-- MODAL DE EMPRESA (CRIAR/EDITAR) -->
<div id="companyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i id="modalIcon" class="fas fa-building"></i> <span id="modalTitle">Nova Empresa</span></h3>
            <button class="modal-close" onclick="closeModal('companyModal')">&times;</button>
        </div>
        <form id="companyForm" onsubmit="event.preventDefault(); saveCompany();">
            <div class="modal-body">
                <input type="hidden" id="companyId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome da Empresa <span class="required">*</span></label>
                        <input type="text" id="companyName" required placeholder="Ex: Transportadora XYZ, Lda.">
                    </div>
                    <div class="form-group">
                        <label>NIF <span class="required">*</span></label>
                        <input type="text" id="companyDocument" required placeholder="123456789" maxlength="9">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="companyEmail" placeholder="geral@empresa.pt">
                    </div>
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="tel" id="companyPhone" placeholder="+351 912 345 678">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Morada</label>
                    <input type="text" id="companyAddress" placeholder="Rua, número, freguesia">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" id="companyCity" placeholder="Lisboa">
                    </div>
                    <div class="form-group">
                        <label>Distrito</label>
                        <input type="text" id="companyState" placeholder="Lisboa">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Código Postal</label>
                        <input type="text" id="companyZipCode" placeholder="1000-001">
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select id="companyStatus">
                            <option value="active">Activa</option>
                            <option value="inactive">Inactiva</option>
                            <option value="suspended">Suspensa</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('companyModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE VISUALIZAÇÃO DE EMPRESA -->
<div id="viewModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-building" style="color: #0052CC;"></i> Detalhes da Empresa</h3>
            <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-group">
                <div class="detail-label">Nome da Empresa</div>
                <div class="detail-value" id="viewCompanyName">-</div>
            </div>
            <div class="detail-group">
                <div class="detail-label">NIF</div>
                <div class="detail-value" id="viewCompanyDocument">-</div>
            </div>
            <div class="form-row">
                <div class="detail-group">
                    <div class="detail-label">Email</div>
                    <div class="detail-value" id="viewCompanyEmail">-</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Telefone</div>
                    <div class="detail-value" id="viewCompanyPhone">-</div>
                </div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Morada</div>
                <div class="detail-value" id="viewCompanyAddress">-</div>
            </div>
            <div class="form-row">
                <div class="detail-group">
                    <div class="detail-label">Cidade</div>
                    <div class="detail-value" id="viewCompanyCity">-</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Distrito</div>
                    <div class="detail-value" id="viewCompanyState">-</div>
                </div>
            </div>
            <div class="form-row">
                <div class="detail-group">
                    <div class="detail-label">Código Postal</div>
                    <div class="detail-value" id="viewCompanyZipCode">-</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Estado</div>
                    <div class="detail-value" id="viewCompanyStatus">-</div>
                </div>
            </div>
            <div class="form-row">
                <div class="detail-group">
                    <div class="detail-label">Data Registo</div>
                    <div class="detail-value" id="viewCompanyCreated">-</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Utilizadores</div>
                    <div class="detail-value" id="viewCompanyUsers">-</div>
                </div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Veículos</div>
                <div class="detail-value" id="viewCompanyVehicles">-</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('viewModal')">Fechar</button>
            <button class="btn-primary" onclick="editCompanyFromView()">Editar</button>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMAÇÃO PARA ELIMINAR -->
<div id="confirmDeleteModal" class="modal">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle" style="color: #DE350B;"></i> <span id="confirmDeleteTitle">Confirmar Eliminação</span></h3>
            <button class="modal-close" onclick="closeModal('confirmDeleteModal')">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <div class="confirm-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="confirm-title">Tem certeza?</div>
            <div class="confirm-message" id="confirmDeleteMessage">Esta acção não pode ser desfeita.</div>
        </div>
        <div class="modal-footer" style="justify-content: center;">
            <button class="btn-secondary" onclick="closeModal('confirmDeleteModal')">Cancelar</button>
            <button class="btn-danger" onclick="executeDeleteCompany()">Eliminar</button>
        </div>
    </div>
</div>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner-large"></div>
</div>

<!-- Admin JS -->
<script src="<?= asset('js/admin.js') ?>"></script>
<script src="<?= asset('js/companies.js') ?>"></script>

<script>
    // Função para editar a partir do modal de visualização
    function editCompanyFromView() {
        const companyId = document.getElementById('viewModal').getAttribute('data-company-id');
        if (companyId) {
            closeModal('viewModal');
            editCompany(companyId);
        }
    }
</script>
</body>
</html>