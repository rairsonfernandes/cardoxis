<?php
// Dados do usuário logado
$userName = $_SESSION['user_name'] ?? 'Administrador';
$userEmail = $_SESSION['user_email'] ?? 'admin@cardoxis.com';
$userRole = $_SESSION['user_role'] ?? 'admin';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= url('admin/dashboard') ?>" class="logo">
            <div class="logo-icon">
                <img src="<?= asset('img/logo/logo.png') ?>" alt="CARDOXIS">
            </div>
            <div class="logo-text">CARDOXIS<span>.io</span></div>
        </a>
        <button class="sidebar-close" id="sidebarCloseBtn" aria-label="Fechar menu">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">PRINCIPAL</div>
            <a href="<?= url('admin/dashboard') ?>" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">GESTÃO</div>
            <a href="<?= url('admin/companies') ?>" class="nav-item">
                <i class="fas fa-building"></i>
                <span>Empresas</span>
                <span class="nav-badge" id="companiesCount">0</span>
            </a>
            <a href="<?= url('admin/users') ?>" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Utilizadores</span>
                <span class="nav-badge" id="usersCount">0</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">SISTEMA</div>
            <a href="<?= url('admin/logs') ?>" class="nav-item">
                <i class="fas fa-history"></i>
                <span>Registos</span>
            </a>
            <a href="<?= url('admin/system') ?>" class="nav-item">
                <i class="fas fa-server"></i>
                <span>Sistema</span>
            </a>
            <a href="<?= url('admin/settings') ?>" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Configurações</span>
            </a>

        </div>
        
        <div class="nav-divider"></div>
        
        <div class="nav-section">
           <a href="<?= url('dashboard') ?>" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard Normal</span>
            </a>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-menu" id="userMenu">
            <div class="user-info">
                <div class="user-avatar"><?= htmlspecialchars($userInitials) ?></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                    <div class="user-role">
                        <?php 
                        $roleLabels = [
                            'super_admin' => 'Super Administrador',
                            'admin' => 'Administrador',
                            'manager' => 'Gestor',
                            'user' => 'Utilizador'
                        ];
                        echo $roleLabels[$userRole] ?? ucfirst($userRole);
                        ?>
                    </div>
                </div>
            </div>
            <i class="fas fa-chevron-down" id="userChevron"></i>
        </div>
        
        <div class="user-dropdown" id="userDropdown">
            <a href="<?= url('profile') ?>">
                <i class="fas fa-user"></i>
                <span>Meu Perfil</span>
            </a>
            <a href="<?= url('admin/settings') ?>">
                <i class="fas fa-cog"></i>
                <span>Configurações</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="logout-item" id="logoutSidebarBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Terminar Sessão</span>
            </a>
        </div>
    </div>
</aside>
<!-- No final do body, antes do fechamento -->
<script src="/cardoxis/public/assets/js/offline.js"></script>
