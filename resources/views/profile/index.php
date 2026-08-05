<?php
/**
 * CARDOXIS - Perfil do Utilizador RF
 */

if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Perfil | CARDOXIS';

$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'user';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));
$isAdmin = in_array($userRole, ['admin', 'super_admin']);

$avatarColors = ['#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#db2777', '#0f172a'];
$avatarColor = $avatarColors[abs(crc32($userName)) % count($avatarColors)];
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <meta name="description" content="Perfil do utilizador CARDOXIS">
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
    
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/profile.css') ?>">
    
    <script>
        window.userName = '<?= htmlspecialchars($userName) ?>';
        window.userEmail = '<?= htmlspecialchars($userEmail) ?>';
        window.userRole = '<?= htmlspecialchars($userRole) ?>';
        window.isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
        window.userInitials = '<?= htmlspecialchars($userInitials) ?>';
        window.avatarColor = '<?= htmlspecialchars($avatarColor) ?>';
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
    <div class="profile-container">
        
        <!-- Profile Header Card -->
        <div class="profile-header-card">
            <div class="profile-banner"></div>
            <div class="profile-header-content">
                <div class="profile-avatar-section">
                    <div class="profile-avatar-wrapper">
                        <div class="profile-avatar" id="profileAvatar" style="background: <?= $avatarColor ?>;">
                            <span class="avatar-initials"><?= htmlspecialchars($userInitials) ?></span>
                            <span class="online-status"></span>
                        </div>
                    </div>
                    <div class="profile-info">
                        <h2 id="profileNameDisplay"><?= htmlspecialchars($userName) ?></h2>
                        <p class="profile-email" id="profileEmailDisplay"><?= htmlspecialchars($userEmail) ?></p>
                        <div class="profile-badges">
                            <?php if ($isAdmin): ?>
                                <span class="badge badge-admin"><i class="fas fa-shield-alt"></i> Administrador</span>
                            <?php else: ?>
                                <span class="badge badge-user"><i class="fas fa-user"></i> Utilizador</span>
                            <?php endif; ?>
                            <span class="badge badge-verified"><i class="fas fa-check-circle"></i> Verificado</span>
                        </div>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-value" id="statVehicles">-</span>
                        <span class="stat-label">Veículos</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" id="statDrivers">-</span>
                        <span class="stat-label">Motoristas</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" id="statMaintenances">-</span>
                        <span class="stat-label">Manutenções</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" id="statDocuments">-</span>
                        <span class="stat-label">Documentos</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" id="statAlerts">-</span>
                        <span class="stat-label">Alertas</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Tabs -->
        <div class="profile-tabs" id="profileTabs">
            <button class="tab-btn active" data-tab="personal">
                <i class="fas fa-user"></i> Informações Pessoais
            </button>
            <button class="tab-btn" data-tab="security">
                <i class="fas fa-shield-alt"></i> Segurança
            </button>
            <button class="tab-btn" data-tab="sessions">
                <i class="fas fa-desktop"></i> Sessões
            </button>
            <button class="tab-btn" data-tab="activity">
                <i class="fas fa-history"></i> Atividade
            </button>
            <?php if ($isAdmin): ?>
                <button class="tab-btn" data-tab="admin">
                    <i class="fas fa-cog"></i> Admin
                </button>
            <?php endif; ?>
        </div>
        
        <!-- TAB: Informações Pessoais -->
        <div class="tab-content active" id="tab-personal">
            <div class="form-card">
                <div class="form-card-header">
                    <h3><i class="fas fa-user-edit"></i> Informações Pessoais</h3>
                </div>
                <form id="profileForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nome Completo *</label>
                            <input type="text" id="profileName" placeholder="Seu nome completo" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" id="profileEmail" disabled>
                            <span class="helper-text">O email não pode ser alterado</span>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Telefone</label>
                            <input type="text" id="profilePhone" placeholder="+351 912 345 678">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- TAB: Segurança -->
        <div class="tab-content" id="tab-security">
            <div class="form-card">
                <div class="form-card-header">
                    <h3><i class="fas fa-lock"></i> Alterar Palavra-passe</h3>
                </div>
                <form id="passwordForm">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label><i class="fas fa-key"></i> Palavra-passe Atual *</label>
                            <input type="password" id="currentPassword" placeholder="Digite a palavra-passe atual" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Nova Palavra-passe *</label>
                            <input type="password" id="newPassword" placeholder="Nova palavra-passe" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-check-circle"></i> Confirmar Nova Palavra-passe *</label>
                            <input type="password" id="confirmPassword" placeholder="Confirme a nova palavra-passe" required>
                        </div>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Alterar Palavra-passe</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- TAB: Sessões -->
        <div class="tab-content" id="tab-sessions">
            <div class="form-card">
                <div class="form-card-header">
                    <h3><i class="fas fa-desktop"></i> Sessões Ativas</h3>
                    <button class="btn btn-warning btn-sm" id="terminateAllSessions" style="margin-left:auto;">
                        <i class="fas fa-power-off"></i> Terminar Todas
                    </button>
                </div>
                <div class="sessions-list">
                    <div class="session-item">
                        <div class="session-icon"><i class="fas fa-laptop"></i></div>
                        <div class="session-info">
                            <h4>Dispositivo Atual</h4>
                            <p><?= substr($_SERVER['HTTP_USER_AGENT'] ?? 'Navegador', 0, 80) ?></p>
                        </div>
                        <div class="session-status">
                            <span class="badge-success">Ativa</span>
                        </div>
                    </div>
                    <p style="text-align:center;color:var(--profile-text-muted);padding:16px;font-size:0.8rem;">
                        <i class="fas fa-info-circle"></i> Apenas a sessão atual está visível
                    </p>
                </div>
            </div>
        </div>
        
        <!-- TAB: Atividade -->
        <div class="tab-content" id="tab-activity">
            <div class="form-card">
                <div class="form-card-header">
                    <h3><i class="fas fa-history"></i> Atividade Recente</h3>
                </div>
                <div id="activityList">
                    <div style="text-align:center;padding:40px;">
                        <div class="spinner"></div>
                        <p style="margin-top:12px;color:var(--profile-text-muted);font-size:0.9rem;">Carregando atividades...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- TAB: Admin (Apenas para administradores) -->
        <?php if ($isAdmin): ?>
        <div class="tab-content" id="tab-admin">
            <div class="form-card">
                <div class="form-card-header">
                    <h3><i class="fas fa-cog"></i> Painel de Administração</h3>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                    <a href="<?= url('admin/users') ?>" style="background:#f8fafc;border-radius:10px;padding:20px;text-align:center;border:1px solid var(--profile-border);text-decoration:none;color:var(--profile-text);transition:var(--profile-transition);">
                        <i class="fas fa-users" style="font-size:2rem;color:#2563eb;margin-bottom:8px;display:block;"></i>
                        <h4 style="font-size:0.9rem;">Utilizadores</h4>
                        <p style="font-size:0.75rem;color:var(--profile-text-muted);">Gerenciar utilizadores</p>
                    </a>
                    <a href="<?= url('admin/companies') ?>" style="background:#f8fafc;border-radius:10px;padding:20px;text-align:center;border:1px solid var(--profile-border);text-decoration:none;color:var(--profile-text);transition:var(--profile-transition);">
                        <i class="fas fa-building" style="font-size:2rem;color:#059669;margin-bottom:8px;display:block;"></i>
                        <h4 style="font-size:0.9rem;">Empresas</h4>
                        <p style="font-size:0.75rem;color:var(--profile-text-muted);">Gerenciar empresas</p>
                    </a>
                    <a href="<?= url('admin/plans') ?>" style="background:#f8fafc;border-radius:10px;padding:20px;text-align:center;border:1px solid var(--profile-border);text-decoration:none;color:var(--profile-text);transition:var(--profile-transition);">
                        <i class="fas fa-credit-card" style="font-size:2rem;color:#d97706;margin-bottom:8px;display:block;"></i>
                        <h4 style="font-size:0.9rem;">Planos</h4>
                        <p style="font-size:0.75rem;color:var(--profile-text-muted);">Gerenciar planos</p>
                    </a>
                    <a href="<?= url('admin/reports') ?>" style="background:#f8fafc;border-radius:10px;padding:20px;text-align:center;border:1px solid var(--profile-border);text-decoration:none;color:var(--profile-text);transition:var(--profile-transition);">
                        <i class="fas fa-file-alt" style="font-size:2rem;color:#7c3aed;margin-bottom:8px;display:block;"></i>
                        <h4 style="font-size:0.9rem;">Relatórios</h4>
                        <p style="font-size:0.75rem;color:var(--profile-text-muted);">Ver relatórios</p>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- DANGER ZONE -->
        <div class="danger-zone">
            <h3><i class="fas fa-exclamation-triangle"></i> Zona de Risco</h3>
            <div class="danger-actions">
                <div class="danger-item">
                    <div class="danger-info">
                        <h4><i class="fas fa-file-export"></i> Exportar Dados</h4>
                        <p>Exporte todos os seus dados em formato JSON</p>
                    </div>
                    <button class="btn btn-secondary btn-sm" id="exportData"><i class="fas fa-download"></i> Exportar</button>
                </div>
                <div class="danger-item">
                    <div class="danger-info">
                        <h4><i class="fas fa-trash-alt" style="color:#dc2626;"></i> Eliminar Conta</h4>
                        <p>Elimina permanentemente a sua conta e todos os dados associados</p>
                    </div>
                    <button class="btn btn-danger btn-sm" id="deleteAccount"><i class="fas fa-trash-alt"></i> Eliminar Conta</button>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- MODAL: CONFIRMAÇÃO -->

<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <i class="fas fa-exclamation-triangle icon-danger"></i>
                Confirmar Ação
            </h3>
            <button class="modal-close" onclick="closeModal('confirmModal')">&times;</button>
        </div>
        <div class="modal-body">
            <i class="fas fa-exclamation-triangle modal-icon danger"></i>
            <h4 id="confirmTitle">Tem certeza que deseja realizar esta ação?</h4>
            <p id="confirmMessage">Esta ação é irreversível e todos os dados associados serão removidos permanentemente.</p>
            
            <div class="modal-divider"></div>
            
            <div id="confirmPasswordField" style="display:none;">
                <div class="password-input">
                    <label><i class="fas fa-key"></i> Digite sua palavra-passe para confirmar</label>
                    <input type="password" id="confirmDeletePassword" placeholder="Digite sua palavra-passe">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('confirmModal')">Cancelar</button>
            <button class="btn btn-danger" id="confirmActionBtn">Confirmar</button>
        </div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div id="toastContainer" class="toast-container"></div>

<!-- PROFILE JS -->
<script src="<?= asset('js/dashboard/profile.js') ?>"></script>

</body>
</html>