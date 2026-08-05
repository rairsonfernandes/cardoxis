<?php
/**
 * CARDOXIS - Página de Gestão de Documentos RF
 */

// Verificar autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Documentos | CARDOXIS';
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
    <meta name="description" content="Gestão de documentos - CARDOXIS">
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
    <link rel="stylesheet" href="<?= asset('css/Dashboard/documents.css') ?>">
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
                <h1 class="header-title"><i class="fas fa-file-alt"></i>Documentos </h1>
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="globalSearch" placeholder="Pesquisar documentos...">
                </div>
                <div class="header-actions">
                    <button class="btn-icon-refresh" onclick="location.reload()" title="Atualizar">
                        <i class="fas fa-sync-alt"></i>
                        <span>Atualizar</span>
                    </button>
                    <button class="btn-primary" onclick="openUploadModal()">
                        <i class="fas fa-upload"></i>
                        <span>Carregar Documento</span>
                    </button>
                </div>
            </div>
            <div class="header-bottom">
                <div class="welcome-section">
                    <p class="welcome-subtitle">Gerencie todos os documentos da sua empresa</p>
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
                    <span class="stat-title">Total Documentos</span>
                    <div class="stat-icon primary"><i class="fas fa-folder"></i></div>
                </div>
                <div class="stat-value" id="totalDocuments">-</div>
                <div class="stat-change">Documentos registados</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Válidos</span>
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-value" id="validDocuments">-</div>
                <div class="stat-change">Dentro da validade</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">A Vencer (30d)</span>
                    <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
                </div>
                <div class="stat-value" id="expiringDocuments">-</div>
                <div class="stat-change">Vencem em breve</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Vencidos</span>
                    <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div class="stat-value" id="expiredDocuments">-</div>
                <div class="stat-change">Documentos vencidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Armazenamento</span>
                    <div class="stat-icon info"><i class="fas fa-database"></i></div>
                </div>
                <div class="stat-value" id="storageUsed">-</div>
                <div class="stat-change">Espaço utilizado</div>
            </div>
        </div>
        
        <!-- QUICK ACTIONS & FILTERS -->
        <div class="quick-actions">
            <div class="section-header">
                <div class="filter-group">
                    <button class="filter-status-btn active" data-status="all" onclick="filterDocuments('all')">
                        <i class="fas fa-list"></i> Todos
                        <span class="count" id="filterAllCount">0</span>
                    </button>
                    <button class="filter-status-btn" data-status="valid" onclick="filterDocuments('valid')">
                        <i class="fas fa-check-circle"></i> Válidos
                        <span class="count" id="filterValidCount">0</span>
                    </button>
                    <button class="filter-status-btn" data-status="expiring" onclick="filterDocuments('expiring')">
                        <i class="fas fa-clock"></i> A Vencer
                        <span class="count" id="filterExpiringCount">0</span>
                    </button>
                    <button class="filter-status-btn" data-status="expired" onclick="filterDocuments('expired')">
                        <i class="fas fa-exclamation-triangle"></i> Vencidos
                        <span class="count" id="filterExpiredCount">0</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- DOCUMENTS TABLE -->
        <div class="documents-table-container">
            <table class="documents-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"></th>
                        <th>Documento</th>
                        <th>Entidade</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Data Validade</th>
                        <th>Tamanho</th>
                        <th style="width: 100px;">Ações</th>
                    </tr>
                </thead>
                <tbody id="documentsTableBody">
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 60px;">
                            <div class="spinner"></div>
                            <p>Carregando documentos...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- PAGINATION -->
        <div id="pagination" class="pagination"></div>
        
        <div class="footer-note">
            <i class="fas fa-info-circle"></i> Clique em um documento para ver mais detalhes
        </div>
    </div>
</main>

<!-- ============================================ -->
<!-- MODAL UPLOAD DOCUMENTO -->
<!-- ============================================ -->
<div id="uploadDocumentModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-upload"></i> Carregar Documento</h3>
            <button class="modal-close" onclick="closeModal('uploadDocumentModal')">&times;</button>
        </div>
        <form id="uploadDocumentForm" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Entidade *</label>
                        <select id="addEntityType" required>
                            <option value="">Selecione</option>
                            <option value="vehicle">Veículo</option>
                            <option value="driver">Motorista</option>
                            <option value="company">Empresa</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-search"></i> Selecionar *</label>
                        <select id="addEntityId" required>
                            <option value="">Selecione primeiro o tipo</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Tipo de Documento *</label>
                        <select id="addType" required>
                            <option value="">Selecione</option>
                            <option value="Contrato">Contrato</option>
                            <option value="Seguro">Seguro</option>
                            <option value="Licença">Licença</option>
                            <option value="Certificado">Certificado</option>
                            <option value="Fatura">Fatura</option>
                            <option value="Garantia">Garantia</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-heading"></i> Título *</label>
                        <input type="text" id="addTitle" placeholder="Ex: Seguro do Veículo ABC-1234" required>
                    </div>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-align-left"></i> Descrição</label>
                    <textarea id="addDescription" placeholder="Descrição do documento..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Data de Validade</label>
                        <input type="date" id="addExpiryDate">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-bell"></i> Dias de Antecedência</label>
                        <select id="addReminderDays">
                            <option value="7">7 dias</option>
                            <option value="15">15 dias</option>
                            <option value="30" selected>30 dias</option>
                            <option value="45">45 dias</option>
                            <option value="60">60 dias</option>
                            <option value="90">90 dias</option>
                        </select>
                    </div>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-file"></i> Arquivo *</label>
                    <div id="dropZone" class="file-upload-area">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <p class="upload-text">Arraste e solte ou clique para selecionar</p>
                        <p class="upload-hint">PDF, imagens, Word, Excel até 10MB</p>
                        <input type="file" id="fileInput" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" style="display: none;">
                    </div>
                    <div id="fileInfo" class="file-info">
                        <i class="fas fa-file"></i>
                        <span class="file-name-display" id="fileNameDisplay"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('uploadDocumentModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Carregar Documento</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL EDITAR DOCUMENTO -->
<!-- ============================================ -->
<div id="editDocumentModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Documento</h3>
            <button class="modal-close" onclick="closeModal('editDocumentModal')">&times;</button>
        </div>
        <form id="editDocumentForm">
            <input type="hidden" id="editDocumentId">
            <div class="modal-body">
                <div class="form-group full-width">
                    <label><i class="fas fa-heading"></i> Título *</label>
                    <input type="text" id="editTitle" required>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-align-left"></i> Descrição</label>
                    <textarea id="editDescription" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Data de Validade</label>
                        <input type="date" id="editExpiryDate">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-bell"></i> Dias de Antecedência</label>
                        <select id="editReminderDays">
                            <option value="7">7 dias</option>
                            <option value="15">15 dias</option>
                            <option value="30">30 dias</option>
                            <option value="45">45 dias</option>
                            <option value="60">60 dias</option>
                            <option value="90">90 dias</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editDocumentModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL DETALHES DOCUMENTO -->
<!-- ============================================ -->
<div id="viewDocumentModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Detalhes do Documento</h3>
            <button class="modal-close" onclick="closeModal('viewDocumentModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailModalContent">
            <div class="loading-spinner"><div class="spinner"></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('viewDocumentModal')">Fechar</button>
            <button class="btn-primary" onclick="downloadDocument(currentDocumentId)">Descarregar</button>
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
            <p class="modal-message">Tem certeza que deseja eliminar este documento?</p>
            <p style="font-size: 0.85rem; color: var(--gray);">Esta ação não pode ser desfeita.</p>
        </div>
        <div class="modal-footer" style="justify-content: center;">
            <button type="button" class="btn-cancel" onclick="closeModal('deleteConfirmModal')">Cancelar</button>
            <button type="button" class="btn-danger" onclick="confirmDeleteDocument()">Eliminar</button>
        </div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div id="toastContainer" class="toast-container"></div>

<!-- JavaScript -->
<script src="<?= asset('js/dashboard/documents.js') ?>"></script>
<script src="<?= asset('js/dashboard/avatar.js') ?>"></script>


<script>
// Variável global para uso nos modais
let currentDocumentId = null;
</script>

</body>
</html>