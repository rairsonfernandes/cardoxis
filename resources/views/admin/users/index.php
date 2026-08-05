<?php
// Verificar autenticação
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Gestão de Utilizadores | CARDOXIS';

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
    <meta name="description" content="Gestão de utilizadores - CARDOXIS Portugal">
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
    <link rel="stylesheet" href="<?= asset('css/admin/users.css') ?>">
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
                <h1 class="page-title">Gestão de Utilizadores</h1>
                <p class="page-subtitle">Gerencie todos os utilizadores da plataforma</p>
            </div>
            <div class="page-actions">
                <div class="btn-group">
                    <button class="btn-secondary" onclick="exportUsers()">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                    <button class="btn-secondary" onclick="openImportModal()">
                        <i class="fas fa-file-import"></i> Importar
                    </button>
                </div>
                <button class="btn-secondary" onclick="resetFilters()">
                    <i class="fas fa-undo-alt"></i> Limpar Filtros
                </button>
                <button class="btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Novo Utilizador
                </button>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Utilizadores</span>
                    <div class="stat-icon primary"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-value" id="totalUsers">-</div>
                <div class="stat-change">Total registados</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Activos</span>
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-value" id="activeUsers">-</div>
                <div class="stat-change">Utilizadores activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Inactivos</span>
                    <div class="stat-icon warning"><i class="fas fa-minus-circle"></i></div>
                </div>
                <div class="stat-value" id="inactiveUsers">-</div>
                <div class="stat-change">Utilizadores inactivos</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Suspensos</span>
                    <div class="stat-icon danger"><i class="fas fa-pause-circle"></i></div>
                </div>
                <div class="stat-value" id="suspendedUsers">-</div>
                <div class="stat-change">Utilizadores suspensos</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-bar">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Pesquisar por nome, email..." autocomplete="off">
            </div>
            <select id="roleFilter" class="filter-select" onchange="filterByRole()">
                <option value="">Todos os papéis</option>
                <option value="super_admin">Super Administrador</option>
                <option value="admin">Administrador</option>
                <option value="manager">Gestor</option>
                <option value="user">Utilizador</option>
            </select>
            <select id="statusFilter" class="filter-select" onchange="filterByStatus()">
                <option value="">Todos os estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
                <option value="suspended">Suspensos</option>
            </select>
            <button class="btn-secondary" onclick="resetFilters()">
                <i class="fas fa-undo-alt"></i> Limpar Filtros
            </button>
            <button class="btn-primary" onclick="searchUsers()">
                <i class="fas fa-search"></i> Pesquisar
            </button>
        </div>
        
        <!-- Users Table -->
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Utilizador</th>
                        <th>Papel</th>
                        <th>Estado</th>
                        <th>Empresa</th>
                        <th>Último Acesso</th>
                        <th>Data Registo</th>
                        <th width="200">Acções</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
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

<!-- MODAL DE UTILIZADOR (CRIAR/EDITAR) -->
<div id="userModal" class="modal">
    <div class="modal-content" style="max-width: 550px;">
        <div class="modal-header">
            <h3><i id="modalIcon" class="fas fa-user-plus"></i> <span id="modalTitle">Novo Utilizador</span></h3>
            <button class="modal-close" onclick="closeModal('userModal')">&times;</button>
        </div>
        <form id="userForm" onsubmit="event.preventDefault(); saveUser();">
            <div class="modal-body">
                <input type="hidden" id="userId">
                
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label>Nome <span class="required">*</span></label>
                        <input type="text" id="userName" required placeholder="Nome completo">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" id="userEmail" required placeholder="exemplo@empresa.pt">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Telefone</label>
                        <input type="tel" id="userPhone" placeholder="+351 912 345 678">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Papel <span class="required">*</span></label>
                        <select id="userRole" required>
                            <option value="">Seleccionar papel</option>
                            <option value="super_admin">Super Administrador</option>
                            <option value="admin">Administrador</option>
                            <option value="manager">Gestor</option>
                            <option value="user">Utilizador</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Estado</label>
                        <select id="userStatus">
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                            <option value="suspended">Suspenso</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Empresa</label>
                    <select id="userCompany">
                        <option value="">Seleccionar empresa</option>
                    </select>
                    <small style="font-size:0.7rem; color:#6B778C;">Seleccione a empresa a que o utilizador pertence</small>
                </div>
                
                <div class="form-group">
                    <label>Senha <span id="passwordRequired" class="required">*</span></label>
                    <div class="password-generator">
                        <input type="text" id="userPassword" placeholder="••••••••">
                        <button type="button" class="btn-generate" onclick="generatePassword()">
                            <i class="fas fa-sync-alt"></i> Gerar
                        </button>
                    </div>
                    <small style="font-size:0.7rem; color:#6B778C;">Mínimo 6 caracteres</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('userModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE VISUALIZAÇÃO DE UTILIZADOR -->
<div id="viewModal" class="modal">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
            <h3><i class="fas fa-user-circle" style="color: #0052CC;"></i> Detalhes do Utilizador</h3>
            <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-group">
                <div class="detail-label">Nome</div>
                <div class="detail-value" id="viewUserName">-</div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Email</div>
                <div class="detail-value" id="viewUserEmail">-</div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Papel</div>
                <div class="detail-value" id="viewUserRole">-</div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Telefone</div>
                <div class="detail-value" id="viewUserPhone">-</div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Estado</div>
                <div class="detail-value" id="viewUserStatus">-</div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Empresa</div>
                <div class="detail-value" id="viewUserCompany">-</div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Data Registo</div>
                <div class="detail-value" id="viewUserCreated">-</div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Último Acesso</div>
                <div class="detail-value" id="viewUserLastLogin">-</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('viewModal')">Fechar</button>
            <button class="btn-primary" onclick="editUserFromView()">Editar</button>
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
            <button class="btn-danger" onclick="executeDeleteUser()">Eliminar</button>
        </div>
    </div>
</div>

<!-- MODAL DE IMPORTAÇÃO DE UTILIZADORES -->
<div id="importModal" class="modal">
    <div class="modal-content" style="max-width: 520px;">
        <div class="modal-header">
            <h3><i class="fas fa-file-import" style="color: #0052CC;"></i> Importar Utilizadores</h3>
            <button class="modal-close" onclick="closeModal('importModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Upload Area -->
            <div class="form-group">
                <label>Ficheiro CSV <span class="required">*</span></label>
                <div class="file-upload-area" id="fileUploadArea" 
                     style="border: 2px dashed #DFE1E6; border-radius: 8px; padding: 30px 20px; text-align: center; transition: all 0.3s; cursor: pointer; background: #FAFBFC;"
                     onclick="document.getElementById('importFile').click()"
                     onmouseenter="this.style.borderColor='#0052CC'; this.style.background='#F4F5F7'"
                     onmouseleave="this.style.borderColor='#DFE1E6'; this.style.background='#FAFBFC'">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 36px; color: #6B778C; margin-bottom: 10px; display: block;"></i>
                    <p style="margin: 8px 0 4px 0; font-weight: 600; color: #172B4D; font-size: 0.95rem;">Clique para selecionar ou arraste o ficheiro</p>
                    <p id="importFileLabel" style="color: #6B778C; font-size: 0.85rem;">Nenhum ficheiro selecionado</p>
                    <input type="file" id="importFile" accept=".csv" style="display: none;">
                </div>
                <small style="color: #6B778C; display: block; margin-top: 8px;">
                    <i class="fas fa-info-circle"></i> Formatos suportados: CSV (.csv) | Máximo: 5MB
                </small>
            </div>
            
            <!-- Progresso -->
            <div id="importProgress" style="display: none; text-align: center; padding: 20px;">
                <div class="spinner" style="margin: 0 auto;"></div>
                <p id="importProgressText" style="margin-top: 10px; color: #6B778C;">A processar ficheiro...</p>
            </div>
            
            <!-- Resultado -->
            <div id="importResult" style="display: none;">
                <!-- Sucesso -->
                <div id="importResultSuccess" style="display: none;">
                    <div class="confirm-icon" style="text-align: center;">
                        <i class="fas fa-check-circle" style="font-size: 48px; color: #00875A;"></i>
                    </div>
                    <div class="confirm-title" style="text-align: center; color: #00875A;">Importação concluída!</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
                        <div style="background: #E3FCEF; padding: 12px; border-radius: 8px; text-align: center;">
                            <strong id="importTotal" style="font-size: 24px; color: #00875A;">0</strong>
                            <small style="display: block; color: #00875A;">Total</small>
                        </div>
                        <div style="background: #E3FCEF; padding: 12px; border-radius: 8px; text-align: center;">
                            <strong id="importCreated" style="font-size: 24px; color: #00875A;">0</strong>
                            <small style="display: block; color: #00875A;">Criados</small>
                        </div>
                        <div style="background: #FFF3E0; padding: 12px; border-radius: 8px; text-align: center;">
                            <strong id="importUpdated" style="font-size: 24px; color: #FF8B00;">0</strong>
                            <small style="display: block; color: #FF8B00;">Actualizados</small>
                        </div>
                        <div style="background: #FFEBE6; padding: 12px; border-radius: 8px; text-align: center;">
                            <strong id="importFailed" style="font-size: 24px; color: #DE350B;">0</strong>
                            <small style="display: block; color: #DE350B;">Falhas</small>
                        </div>
                    </div>
                </div>
                
                <!-- Erro -->
                <div id="importResultError" style="display: none;">
                    <div class="confirm-icon" style="text-align: center;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #DE350B;"></i>
                    </div>
                    <div class="confirm-title" style="text-align: center; color: #DE350B;">Erro na Importação</div>
                    <p style="text-align: center; color: #42526E;" id="importErrorMessage">Erro ao processar o ficheiro.</p>
                </div>
                
                <!-- Botões após importação -->
                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                    <button class="btn-secondary" onclick="resetImport()" style="font-size: 0.85rem;">
                        <i class="fas fa-redo"></i> Nova Importação
                    </button>
                    <button class="btn-secondary" onclick="closeModal('importModal')" style="font-size: 0.85rem;">
                        <i class="fas fa-times"></i> Fechar
                    </button>
                </div>
            </div>
            
            <!-- Template -->
            <div style="margin-top: 15px; text-align: center; padding-top: 15px; border-top: 1px solid #DFE1E6;">
                <button class="btn-secondary" onclick="downloadTemplate()" style="font-size: 0.85rem;">
                    <i class="fas fa-download"></i> Descarregar Modelo CSV
                </button>
            </div>
        </div>
        <div class="modal-footer" style="justify-content: center; gap: 12px;">
            <button class="btn-secondary" onclick="closeModal('importModal')">Cancelar</button>
            <button class="btn-primary" onclick="importUsers()" id="importBtn">
                <i class="fas fa-upload"></i> Importar
            </button>
        </div>
    </div>
</div>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner-large"></div>
</div>

<!-- Admin JS -->
<script src="<?= asset('js/admin.js') ?>"></script>
<script src="<?= asset('js/users.js') ?>"></script>

<script>
    // Função para editar a partir do modal de visualização
    function editUserFromView() {
        var userId = document.getElementById('viewModal').getAttribute('data-user-id');
        if (userId) {
            closeModal('viewModal');
            editUser(userId);
        }
    }
    
    // Fechar modais ao clicar fora
    document.addEventListener('click', function(e) {
        var modals = document.querySelectorAll('.modal.active');
        modals.forEach(function(modal) {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
</script>
</body>
</html>