<?php
/**
 * CARDOXIS - Fines RF
 */

if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Gestão de Multas | CARDOXIS';

$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'user';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));

$stats = $stats ?? [];
$totalFines = $totalFines ?? 0;
$totalAmount = $totalAmount ?? 0;
$pendingAmount = $pendingAmount ?? 0;
$paidAmount = $paidAmount ?? 0;
$overdueCount = $overdueCount ?? 0;
$fines = $fines ?? [];
$hasMoreFines = $hasMoreFines ?? false;

// Funções auxiliares
function getStatusText($status) {
    $texts = [
        'pending' => 'Pendente',
        'paid' => 'Paga',
        'overdue' => 'Vencida',
        'contested' => 'Contestada'
    ];
    return $texts[$status] ?? $status;
}

function getStatusClass($status) {
    $classes = [
        'pending' => 'pending',
        'paid' => 'paid',
        'overdue' => 'overdue',
        'contested' => 'contested'
    ];
    return $classes[$status] ?? 'pending';
}

function getTypeText($type) {
    $texts = [
        'speeding' => 'Excesso de Velocidade',
        'parking' => 'Estacionamento',
        'traffic_light' => 'Semáforo',
        'documentation' => 'Documentação',
        'technical' => 'Técnica',
        'other' => 'Outra'
    ];
    return $texts[$type] ?? $type;
}

function getTypeClass($type) {
    $classes = [
        'speeding' => 'speeding',
        'parking' => 'parking',
        'traffic_light' => 'traffic_light',
        'documentation' => 'documentation',
        'technical' => 'technical',
        'other' => 'other'
    ];
    return $classes[$type] ?? 'other';
}

function getSeverityText($severity) {
    $texts = [
        'mild' => 'Leve',
        'serious' => 'Grave',
        'very_serious' => 'Muito Grave'
    ];
    return $texts[$severity] ?? $severity;
}

function getSeverityClass($severity) {
    $classes = [
        'mild' => 'mild',
        'serious' => 'serious',
        'very_serious' => 'very_serious'
    ];
    return $classes[$severity] ?? 'mild';
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
    <meta name="description" content="Gestão de multas CARDOXIS">
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
    <link rel="stylesheet" href="<?= asset('css/dashboard/fines.css') ?>">
    
    <script>
        window.userName = '<?= htmlspecialchars($userName) ?>';
        window.API_URL = '<?= url('api/v1') ?>';
        window.totalFines = <?= $totalFines ?>;
        window.totalAmount = <?= $totalAmount ?>;
        window.pendingAmount = <?= $pendingAmount ?>;
        window.paidAmount = <?= $paidAmount ?>;
        window.overdueCount = <?= $overdueCount ?>;
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
    <div class="fines-container">
        
        <!-- HEADER -->
        <div class="fines-header">
            <div class="header-top">
                <h1 class="header-title"><i class="fas fa-gavel"></i>Multas</h1>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearch" placeholder="Pesquisar multas, veículos...">
                </div>
                <div class="header-actions">
                    <button class="btn-icon-refresh" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Atualizar</button>
                    <button class="btn-primary" onclick="openAddFineModal()"><i class="fas fa-plus"></i> Nova Multa</button>
                </div>
            </div>
            <div class="header-bottom">
                <div>
                    <h2 class="welcome-title">Olá, <span class="user-highlight"><?= htmlspecialchars($userName) ?></span> <span class="welcome-emoji">👋</span></h2>
                    <p class="welcome-subtitle">Gerencie todas as multas da sua frota de forma eficiente.</p>
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
                    <span class="stat-title">Total Multas</span>
                    <div class="stat-icon primary"><i class="fas fa-file-invoice"></i></div>
                </div>
                <div class="stat-value" id="totalFines"><?= number_format($totalFines, 0, ',', '.') ?></div>
                <div class="stat-change">Registadas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Valor Total</span>
                    <div class="stat-icon success"><i class="fas fa-euro-sign"></i></div>
                </div>
                <div class="stat-value" id="totalAmount"><?= formatMoney($totalAmount) ?></div>
                <div class="stat-change">Em multas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Pendentes</span>
                    <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
                </div>
                <div class="stat-value" id="pendingAmount"><?= formatMoney($pendingAmount) ?></div>
                <div class="stat-change">A pagar</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Vencidas</span>
                    <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div class="stat-value" id="overdueCount"><?= number_format($overdueCount, 0, ',', '.') ?></div>
                <div class="stat-change">Multas vencidas</div>
            </div>
        </div>

        <!-- FILTERS -->
        <div class="filters-bar">
            <div class="filter-group">
                <button class="filter-btn active" data-status="all" onclick="filterFine('all')">Todas</button>
                <button class="filter-btn" data-status="pending" onclick="filterFine('pending')">Pendentes</button>
                <button class="filter-btn" data-status="overdue" onclick="filterFine('overdue')">Vencidas</button>
                <button class="filter-btn" data-status="paid" onclick="filterFine('paid')">Pagas</button>
                <button class="filter-btn" data-status="contested" onclick="filterFine('contested')">Contestadas</button>
            </div>
            <div class="filter-group">
                <button class="filter-btn active" data-type="all" onclick="filterFineByType('all')">Todos</button>
                <button class="filter-btn" data-type="speeding" onclick="filterFineByType('speeding')">Velocidade</button>
                <button class="filter-btn" data-type="parking" onclick="filterFineByType('parking')">Estacionamento</button>
                <button class="filter-btn" data-type="traffic_light" onclick="filterFineByType('traffic_light')">Semáforo</button>
                <button class="filter-btn" data-type="documentation" onclick="filterFineByType('documentation')">Documentação</button>
                <button class="filter-btn" data-type="other" onclick="filterFineByType('other')">Outras</button>
            </div>
        </div>

        <!-- TABLE -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-list"></i> Lista de Multas</div>
                <div class="table-info">
                    <span>Total: <strong id="totalCount">0</strong></span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="fines-table">
                        <thead>
                            <tr>
                                <th>Nº Multa</th>
                                <th>Veículo</th>
                                <th>Motorista</th>
                                <th>Tipo</th>
                                <th>Emissão</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="fineTableBody">
                            <!-- Renderizado via JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div class="pagination" id="pagination">
                    <!-- Renderizado via JavaScript -->
                </div>
            </div>
        </div>

    </div>
</main>

<!-- ============================================ -->
<!-- MODAL: ADICIONAR MULTA -->
<!-- ============================================ -->
<div id="addFineModal" class="modal">
    <div class="modal-content" style="max-width:700px;">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Registrar Multa</h3>
            <button class="modal-close" onclick="closeModal('addFineModal')">&times;</button>
        </div>
        <form id="addFineForm" enctype="multipart/form-data">
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
                        <label><i class="fas fa-hashtag"></i> Nº Multa *</label>
                        <input type="text" id="addFineNumber" placeholder="Ex: FT-2024-001" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Tipo *</label>
                        <select id="addType" required>
                            <option value="speeding">Excesso de Velocidade</option>
                            <option value="parking">Estacionamento</option>
                            <option value="traffic_light">Semáforo</option>
                            <option value="documentation">Documentação</option>
                            <option value="technical">Técnica</option>
                            <option value="other">Outra</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Data Emissão *</label>
                        <input type="date" id="addIssueDate" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Data Vencimento *</label>
                        <input type="date" id="addDueDate" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-euro-sign"></i> Valor *</label>
                        <input type="number" id="addAmount" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-exclamation-triangle"></i> Gravidade</label>
                        <select id="addSeverity">
                            <option value="mild">Leve</option>
                            <option value="serious">Grave</option>
                            <option value="very_serious">Muito Grave</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Local</label>
                        <input type="text" id="addLocation" placeholder="Local da infração">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-chart-line"></i> Status</label>
                        <select id="addStatus">
                            <option value="pending">Pendente</option>
                            <option value="contested">Contestada</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label><i class="fas fa-align-left"></i> Descrição</label>
                        <textarea id="addDescription" rows="2" placeholder="Descrição da infração..."></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label><i class="fas fa-sticky-note"></i> Observações</label>
                        <textarea id="addNotes" rows="2" placeholder="Observações adicionais..."></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label><i class="fas fa-file-upload"></i> Documento</label>
                        <div class="image-upload-area" onclick="document.getElementById('addDocument').click()">
                            <input type="file" id="addDocument" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                            <div class="upload-placeholder" id="addUploadPlaceholder">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Clique para anexar documento</p>
                                <span>PDF, JPG, PNG até 5MB</span>
                            </div>
                            <div class="image-preview-wrapper" id="addPreviewWrapper" style="display:none;">
                                <div id="addFileName" style="font-size:0.9rem; color:var(--primary); margin-bottom:8px;"></div>
                                <button type="button" class="remove-image" onclick="removeAddDocument()">
                                    <i class="fas fa-trash-alt"></i> Remover
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addFineModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Registrar Multa</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: EDITAR MULTA -->
<!-- ============================================ -->
<div id="editFineModal" class="modal">
    <div class="modal-content" style="max-width:700px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Multa</h3>
            <button class="modal-close" onclick="closeModal('editFineModal')">&times;</button>
        </div>
        <form id="editFineForm" enctype="multipart/form-data">
            <input type="hidden" id="editFineId">
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
                        <label><i class="fas fa-hashtag"></i> Nº Multa *</label>
                        <input type="text" id="editFineNumber" placeholder="Ex: FT-2024-001" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Tipo *</label>
                        <select id="editType" required>
                            <option value="speeding">Excesso de Velocidade</option>
                            <option value="parking">Estacionamento</option>
                            <option value="traffic_light">Semáforo</option>
                            <option value="documentation">Documentação</option>
                            <option value="technical">Técnica</option>
                            <option value="other">Outra</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Data Emissão *</label>
                        <input type="date" id="editIssueDate" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Data Vencimento *</label>
                        <input type="date" id="editDueDate" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-euro-sign"></i> Valor *</label>
                        <input type="number" id="editAmount" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-exclamation-triangle"></i> Gravidade</label>
                        <select id="editSeverity">
                            <option value="mild">Leve</option>
                            <option value="serious">Grave</option>
                            <option value="very_serious">Muito Grave</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Local</label>
                        <input type="text" id="editLocation" placeholder="Local da infração">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-chart-line"></i> Status</label>
                        <select id="editStatus">
                            <option value="pending">Pendente</option>
                            <option value="paid">Paga</option>
                            <option value="contested">Contestada</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label><i class="fas fa-align-left"></i> Descrição</label>
                        <textarea id="editDescription" rows="2" placeholder="Descrição da infração..."></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label><i class="fas fa-sticky-note"></i> Observações</label>
                        <textarea id="editNotes" rows="2" placeholder="Observações adicionais..."></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label><i class="fas fa-file-upload"></i> Documento</label>
                        <div class="image-upload-area" onclick="document.getElementById('editDocument').click()">
                            <input type="file" id="editDocument" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                            <div class="upload-placeholder" id="editUploadPlaceholder">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Clique para substituir documento</p>
                                <span>PDF, JPG, PNG até 5MB</span>
                            </div>
                            <div class="image-preview-wrapper" id="editPreviewWrapper" style="display:none;">
                                <div id="editFileName" style="font-size:0.9rem; color:var(--primary); margin-bottom:8px;"></div>
                                <button type="button" class="remove-image" onclick="removeEditDocument()">
                                    <i class="fas fa-trash-alt"></i> Remover
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editFineModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: VISUALIZAR DETALHES -->
<!-- ============================================ -->
<div id="viewFineModal" class="modal">
    <div class="modal-content" style="max-width:700px;">
        <div class="modal-header">
            <h3><i class="fas fa-file-invoice"></i> Detalhes da Multa</h3>
            <button class="modal-close" onclick="closeModal('viewFineModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailModalContent">
            <div class="loading-spinner"><div class="spinner"></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn-danger" id="deleteFromDetailsBtn"><i class="fas fa-trash-alt"></i> Eliminar</button>
            <button class="btn-secondary" onclick="closeModal('viewFineModal')">Fechar</button>
            <button class="btn-primary" id="editFromDetailsBtn"><i class="fas fa-edit"></i> Editar</button>
            <button class="btn-success" id="payFromDetailsBtn" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border:none;border-radius:var(--radius-md);background:var(--success);color:white;font-weight:500;cursor:pointer;">
                <i class="fas fa-check"></i> Pagar
            </button>
            <button class="btn-warning" id="contestFromDetailsBtn" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border:none;border-radius:var(--radius-md);background:var(--warning);color:white;font-weight:500;cursor:pointer;">
                <i class="fas fa-gavel"></i> Contestar
            </button>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: CONFIRMAÇÃO DELETE -->
<!-- ============================================ -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content" style="max-width:400px;">
        <div class="modal-header">
            <h3><i class="fas fa-trash-alt"></i> Confirmar Eliminação</h3>
            <button class="modal-close" onclick="closeModal('deleteConfirmModal')">&times;</button>
        </div>
        <div class="modal-body" style="text-align:center;">
            <i class="fas fa-exclamation-triangle" style="font-size:3rem;color:var(--danger);margin-bottom:16px;"></i>
            <p style="font-weight:600;font-size:1.1rem;" id="confirmMessage">Tem certeza que deseja eliminar esta multa?</p>
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

<!-- FINES JS -->
<script src="<?= asset('js/dashboard/fines.js') ?>"></script>

</body>
</html>