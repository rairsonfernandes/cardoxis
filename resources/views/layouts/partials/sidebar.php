<?php
/**
 * CARDOXIS - SIDEBAR COMPLETO RF
 */

// Iniciar sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Cache version para assets
$cache_version = '11.0.0';

// ============================================
// DADOS DO USUÁRIO (VINDOS DA SESSÃO)
// ============================================
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Usuário', ENT_QUOTES, 'UTF-8');
$userRole = $_SESSION['user_role'] ?? 'user';
$userEmail = htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES, 'UTF-8');
$userId = (int)($_SESSION['user_id'] ?? 0);
$userInitials = strtoupper(substr($userName, 0, 2));

// Contagens
$alertCount = (int)($_SESSION['alert_count'] ?? 0);
$maintenanceCount = (int)($_SESSION['maintenance_count'] ?? 0);
$totalVehicles = (int)($_SESSION['total_vehicles'] ?? 0);

// ============================================
// URL ATIVA PARA DESTAQUE DO MENU
// ============================================
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';
$isActive = function($route) use ($currentUrl) {
    return strpos($currentUrl, $route) !== false;
};

// ============================================
// FUNÇÕES HELPER
// ============================================
if (!function_exists('url')) {
    function url($path = '') {
        return '/cardoxis/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        global $cache_version;
        return '/cardoxis/public/assets/' . ltrim($path, '/') . '?v=' . $cache_version;
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin']);
    }
}

// Gerar CSRF token se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Labels para roles
$roleLabels = [
    'super_admin' => 'Super Administrador',
    'admin' => 'Administrador',
    'manager' => 'Gestor',
    'user' => 'Utilizador'
];
?>

<!-- SIDEBAR HTML -->
<aside class="sidebar" id="sidebar">
    <!-- SIDEBAR HEADER - LOGO -->
    <div class="sidebar-header">
        <a href="<?= url('dashboard') ?>" class="logo">
            <div class="logo-icon">
                <img src="<?= asset('img/logo/logo.png') ?>" alt="CARDOXIS">
            </div>
            <div class="logo-text">CARDOXIS<span>.io</span></div>
        </a>
        <button class="sidebar-close" id="sidebarCloseBtn">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <!-- SIDEBAR NAVIGATION -->
    <nav class="sidebar-nav">
        <!-- PRINCIPAL -->
        <div class="nav-section">
            <div class="nav-section-title">Principal</div>
            
            <a href="<?= url('dashboard') ?>" class="nav-item <?= $isActive('dashboard') && !$isActive('vehicles') && !$isActive('maintenance') ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="<?= url('vehicles') ?>" class="nav-item <?= $isActive('vehicles') ? 'active' : '' ?>">
                <i class="fas fa-car"></i>
                <span>Veículos</span>
                <?php if ($totalVehicles > 0): ?>
                <span class="nav-badge"><?= $totalVehicles ?></span>
                <?php endif; ?>
            </a>
            
            <a href="<?= url('maintenance') ?>" class="nav-item <?= $isActive('maintenance') ? 'active' : '' ?>">
                <i class="fas fa-wrench"></i>
                <span>Manutenções</span>
                <?php if ($maintenanceCount > 0): ?>
                <span class="nav-badge warning"><?= $maintenanceCount ?></span>
                <?php endif; ?>
            </a>
            
            <a href="<?= url('documents') ?>" class="nav-item <?= $isActive('documents') ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i>
                <span>Documentos</span>
            </a>
            
            <a href="<?= url('alerts') ?>" class="nav-item <?= $isActive('alerts') ? 'active' : '' ?>">
                <i class="fas fa-bell"></i>
                <span>Alertas</span>
                <?php if ($alertCount > 0): ?>
                <span class="nav-badge danger"><?= $alertCount ?></span>
                <?php endif; ?>
            </a>
            
            <a href="<?= url('reports') ?>" class="nav-item <?= $isActive('reports') ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Relatórios</span>
            </a>
        </div>
        
        <!-- DIVIDER -->
        <div class="nav-divider"></div>
        
        <!-- GESTÃO -->
        <div class="nav-section">
            <div class="nav-section-title">Gestão</div>
            
            <a href="<?= url('drivers') ?>" class="nav-item <?= $isActive('drivers') ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Motoristas</span>
            </a>
            
            <a href="<?= url('fuel') ?>" class="nav-item <?= $isActive('fuel') ? 'active' : '' ?>">
                <i class="fas fa-gas-pump"></i>
                <span>Abastecimentos</span>
            </a>
            
            <a href="<?= url('insurance') ?>" class="nav-item <?= $isActive('insurance') ? 'active' : '' ?>">
                <i class="fas fa-shield-alt"></i>
                <span>Seguros</span>
            </a>
            
            <a href="<?= url('fines') ?>" class="nav-item <?= $isActive('fines') ? 'active' : '' ?>">
                <i class="fas fa-gavel"></i>
                <span>Multas</span>
            </a>

        </div>
        
        <!-- DIVIDER -->
        <div class="nav-divider"></div>
        
        <!-- CONFIGURAÇÕES -->
        <div class="nav-section">
            <div class="nav-section-title">Configurações</div>
            
            <a href="<?= url('profile') ?>" class="nav-item <?= $isActive('profile') ? 'active' : '' ?>">
                <i class="fas fa-user-circle"></i>
                <span>Meu Perfil</span>
            </a>
            <a href="<?= url('chat') ?>" class="nav-item <?= $isActive('chat') ? 'active' : '' ?>">
                <i class="fas fa-comments"></i>
                <span>Chat</span>
            </a>
                        
            <?php if (isAdmin()): ?>
            <a href="<?= url('admin/dashboard') ?>" class="nav-item <?= $isActive('admin') ? 'active' : '' ?>">
                <i class="fas fa-shield-alt"></i>
                <span>Administração</span>
            </a>
            <?php endif; ?>
        </div>
        
        <!-- DIVIDER -->
        <div class="nav-divider"></div>
        
        <!-- SUPORTE -->
        <div class="nav-section">
            <div class="nav-section-title">Suporte</div>
            
            <a href="<?= url('help') ?>" class="nav-item">
                <i class="fas fa-question-circle"></i>
                <span>Ajuda</span>
            </a>
            
            
            <a href="<?= url('changelog') ?>" class="nav-item">
                <i class="fas fa-history"></i>
                <span>Novidades</span>
            </a>
        </div>
    </nav>
    
    <!-- SIDEBAR FOOTER - USER MENU -->
    <div class="sidebar-footer">
        <!-- Status do Sistema Online/Offline -->
        <div class="system-status" id="systemStatus">
            <div class="status-indicator online"></div>
            <span class="status-text">Sistema Online</span>
        </div>
        
        <div class="user-menu" id="userMenu">
            <div class="user-info">
                <div class="user-avatar"><?= htmlspecialchars($userInitials) ?></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                    <div class="user-role">
                        <?= $roleLabels[$userRole] ?? ucfirst($userRole) ?>
                    </div>
                </div>
            </div>
            <i class="fas fa-chevron-down" id="userChevron"></i>
        </div>
        
        <!-- USER DROPDOWN MENU -->
        <div class="user-dropdown" id="userDropdown">
            <a href="<?= url('profile') ?>">
                <i class="fas fa-user"></i>
                <span>Meu Perfil</span>
            </a>
            <a href="<?= url('profile') ?>">
                <i class="fas fa-lock"></i>
                <span>Segurança</span>
            </a>
            <a href="<?= url('notifications') ?>">
                <i class="fas fa-bell"></i>
                <span>Notificações</span>
                <?php if ($alertCount > 0): ?>
                <span class="dropdown-badge"><?= $alertCount ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-divider"></div>
            <a href="<?= url('help') ?>">
                <i class="fas fa-question-circle"></i>
                <span>Central de Ajuda</span>
            </a>
            <a href="<?= url('support') ?>">
                <i class="fas fa-headset"></i>
                <span>Suporte Técnico</span>
            </a>
            <div class="dropdown-divider"></div>
            <button type="button" class="logout-item" id="logoutSidebarBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Terminar Sessão</span>
            </button>
        </div>
    </div>
</aside>

<!-- Overlay para mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Botão Hamburger Menu (Mobile) -->
<button class="hamburger-menu" id="hamburgerMenu" aria-label="Menu">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- Meta tags -->
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, viewport-fit=cover">

<style>
/* ============================================
   SIDEBAR CSS PROFISSIONAL - VERSION 11.0.0
   MOBILE FIRST - 100% TELA
   ============================================ */
:root {
    --sidebar-primary: #0052CC;
    --sidebar-primary-dark: #003D99;
    --sidebar-primary-light: #4C9AFF;
    --sidebar-primary-ultra-light: #E6F0FF;
    --sidebar-dark: #172B4D;
    --sidebar-dark-gray: #42526E;
    --sidebar-gray: #6B778C;
    --sidebar-gray-light: #A5ADBA;
    --sidebar-gray-ultra-light: #DFE1E6;
    --sidebar-gray-bg: #F4F5F7;
    --sidebar-white: #FFFFFF;
    --sidebar-danger: #DE350B;
    --sidebar-warning: #FF8B00;
    --sidebar-success: #00875A;
    --sidebar-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --sidebar-shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --sidebar-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    --sidebar-shadow-primary: 0 4px 12px rgba(0, 82, 204, 0.25);
    --sidebar-radius-sm: 6px;
    --sidebar-radius-md: 8px;
    --sidebar-radius-lg: 12px;
    --sidebar-radius-full: 9999px;
    --sidebar-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background: #F4F5F7;
    overflow-x: hidden;
    position: relative;
}

/* ============================================
   HAMBURGER MENU BUTTON (MOBILE)
   ============================================ */
.hamburger-menu {
    display: none;
    position: fixed;
    top: 16px;
    left: 16px;
    width: 48px;
    height: 48px;
    background: blue;
    border: none;
    border-radius: var(--sidebar-radius-md);
    cursor: pointer;
    z-index: 1002;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-shadow: var(--sidebar-shadow-md);
    transition: var(--sidebar-transition);
}

.hamburger-menu span {
    width: 24px;
    height: 2px;
    background: white;
    border-radius: 2px;
    transition: var(--sidebar-transition);
}

.hamburger-menu:hover {
    background: var(--sidebar-primary);
}

.hamburger-menu:hover span {
    background: white;
}

/* Animação do hamburger quando aberto */
.hamburger-menu.open {
    background: var(--sidebar-primary);
}

.hamburger-menu.open span:nth-child(1) {
    transform: rotate(45deg) translate(8px, 6px);
    background: white;
}

.hamburger-menu.open span:nth-child(2) {
    opacity: 0;
}

.hamburger-menu.open span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -5px);
    background: white;
}

@media (max-width: 768px) {
    .hamburger-menu {
        display: flex;
    }
}

/* ============================================
   SIDEBAR - MOBILE OCUPA 100% DA TELA
   ============================================ */
.sidebar {
    width: 280px;
    background: linear-gradient(180deg, #FFFFFF 0%, #F8F9FA 100%);
    border-right: 1px solid var(--sidebar-gray-ultra-light);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1001;
    transition: transform 0.3s ease-in-out;
    box-shadow: var(--sidebar-shadow-sm);
}

/* Scrollbar Azul Personalizada */
.sidebar::-webkit-scrollbar {
    width: 5px;
}

.sidebar::-webkit-scrollbar-track {
    background: var(--sidebar-gray-bg);
    border-radius: 10px;
}

.sidebar::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--sidebar-primary) 0%, var(--sidebar-primary-light) 100%);
    border-radius: 10px;
    transition: var(--sidebar-transition);
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: var(--sidebar-primary-dark);
}

/* MOBILE: Sidebar ocupa 100% da tela */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        transform: translateX(-100%);
        box-shadow: var(--sidebar-shadow-lg);
    }
    .sidebar.open {
        transform: translateX(0);
    }
}

/* TABLET: Sidebar ocupa 80% da tela */
@media (min-width: 769px) and (max-width: 1024px) {
    .sidebar {
        width: 320px;
    }
}

/* Desktop normal */
@media (min-width: 1025px) {
    .sidebar {
        width: 280px;
    }
}

/* Sidebar Header */
.sidebar-header {
    padding: 24px 24px 20px 24px;
    border-bottom: 1px solid var(--sidebar-gray-ultra-light);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.sidebar-close {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--sidebar-gray);
    cursor: pointer;
    padding: 8px;
    border-radius: var(--sidebar-radius-sm);
    transition: var(--sidebar-transition);
}

.sidebar-close:hover {
    background: var(--sidebar-gray-bg);
    color: var(--sidebar-danger);
}

@media (max-width: 768px) {
    .sidebar-close {
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

/* Logo */
.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    text-decoration: none;
}

.logo-icon {
    width: 44px;
    height: 44px;
    border-radius: var(--sidebar-radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #ffffff;
}

.logo-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.logo-text {
    font-size: 1.4rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--sidebar-dark) 0%, var(--sidebar-primary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.logo-text span {
    background: linear-gradient(135deg, var(--sidebar-primary) 0%, var(--sidebar-primary-light) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Mobile: Logo centralizado */
@media (max-width: 768px) {
    .logo {
        flex: 1;
        justify-content: center;
    }
    .logo-text {
        font-size: 1.6rem;
    }
}

/* Navigation */
.sidebar-nav {
    flex: 1;
    padding: 20px 16px;
    overflow-y: auto;
}

.nav-section {
    margin-bottom: 8px;
}

.nav-section-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--sidebar-gray-light);
    padding: 12px 16px 8px 16px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    border-radius: var(--sidebar-radius-md);
    cursor: pointer;
    transition: var(--sidebar-transition);
    color: var(--sidebar-dark-gray);
    font-weight: 500;
    margin-bottom: 4px;
    text-decoration: none;
    position: relative;
}

.nav-item i {
    width: 24px;
    font-size: 1.2rem;
    text-align: center;
}

.nav-item span:not(.nav-badge) {
    flex: 1;
    font-size: 0.95rem;
}

.nav-item:hover {
    background: var(--sidebar-primary-ultra-light);
    color: var(--sidebar-primary);
    transform: translateX(4px);
}

.nav-item.active {
    background: linear-gradient(135deg, var(--sidebar-primary) 0%, var(--sidebar-primary-dark) 100%);
    color: white;
    box-shadow: var(--sidebar-shadow-primary);
}

.nav-item.active i {
    color: white;
}

/* Badges */
.nav-badge {
    background: var(--sidebar-gray-bg);
    color: var(--sidebar-dark-gray);
    font-size: 0.7rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: var(--sidebar-radius-full);
    min-width: 24px;
    text-align: center;
}

.nav-badge.warning {
    background: #FFF3E0;
    color: var(--sidebar-warning);
}

.nav-badge.danger {
    background: #FFEBE6;
    color: var(--sidebar-danger);
}

.nav-item.active .nav-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

/* Divider */
.nav-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--sidebar-gray-ultra-light), transparent);
    margin: 16px 0;
}

/* Sidebar Footer */
.sidebar-footer {
    padding: 20px 16px;
    border-top: 1px solid var(--sidebar-gray-ultra-light);
    flex-shrink: 0;
    position: relative;
    background: inherit;
}

/* System Status - Online/Offline */
.system-status {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    margin-bottom: 16px;
    border-radius: var(--sidebar-radius-md);
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.system-status.online {
    background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
    border-left: 3px solid var(--sidebar-success);
}

.system-status.offline {
    background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%);
    border-left: 3px solid var(--sidebar-danger);
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.status-indicator.online {
    background: var(--sidebar-success);
    box-shadow: 0 0 0 0 rgba(0, 135, 90, 0.4);
    animation: pulse-green 2s infinite;
}

.status-indicator.offline {
    background: var(--sidebar-danger);
    box-shadow: 0 0 0 0 rgba(222, 53, 11, 0.4);
    animation: pulse-red 2s infinite;
}

@keyframes pulse-green {
    0% {
        box-shadow: 0 0 0 0 rgba(0, 135, 90, 0.4);
    }
    70% {
        box-shadow: 0 0 0 8px rgba(0, 135, 90, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(0, 135, 90, 0);
    }
}

@keyframes pulse-red {
    0% {
        box-shadow: 0 0 0 0 rgba(222, 53, 11, 0.4);
    }
    70% {
        box-shadow: 0 0 0 8px rgba(222, 53, 11, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(222, 53, 11, 0);
    }
}

.status-text {
    font-weight: 600;
}

.system-status.online .status-text {
    color: #1B5E20;
}

.system-status.offline .status-text {
    color: #C62828;
}

/* User Menu */
.user-menu {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    padding: 12px;
    border-radius: var(--sidebar-radius-md);
    transition: var(--sidebar-transition);
    background: var(--sidebar-gray-bg);
    width: 100%;
    border: none;
}

.user-menu:hover {
    background: var(--sidebar-primary-ultra-light);
    transform: translateY(-2px);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--sidebar-primary) 0%, var(--sidebar-primary-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
}

.user-name {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--sidebar-dark);
    line-height: 1.3;
}

.user-role {
    font-size: 0.7rem;
    color: var(--sidebar-gray);
}

/* User Dropdown */
.user-dropdown {
    position: absolute;
    bottom: 100%;
    left: 16px;
    right: 16px;
    margin-bottom: 10px;
    background: var(--sidebar-white);
    border-radius: var(--sidebar-radius-lg);
    box-shadow: var(--sidebar-shadow-lg);
    padding: 8px 0;
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.2s ease;
    border: 1px solid var(--sidebar-gray-ultra-light);
    z-index: 1002;
}

.user-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.user-dropdown a,
.user-dropdown button {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    text-decoration: none;
    color: var(--sidebar-dark);
    transition: var(--sidebar-transition);
    font-size: 0.9rem;
    width: 100%;
    background: none;
    border: none;
    cursor: pointer;
    font-family: inherit;
    text-align: left;
}

.user-dropdown a:hover,
.user-dropdown button:hover {
    background: var(--sidebar-primary-ultra-light);
    color: var(--sidebar-primary);
    padding-left: 24px;
}

.user-dropdown .logout-item {
    color: var(--sidebar-danger);
}

.user-dropdown .logout-item:hover {
    background: rgba(222, 53, 11, 0.08);
    color: var(--sidebar-danger);
}

.dropdown-divider {
    height: 1px;
    background: var(--sidebar-gray-ultra-light);
    margin: 8px 0;
}

.dropdown-badge {
    background: var(--sidebar-danger);
    color: white;
    font-size: 0.65rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: var(--sidebar-radius-full);
    margin-left: auto;
}

#userChevron {
    transition: transform 0.2s ease;
}

/* Overlay - Fundo escuro quando menu aberto */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.sidebar-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* Main Content Adjustment */
.main-content {
    margin-left: 280px;
    transition: margin-left 0.3s ease;
    min-height: 100vh;
    padding: 20px;
}

/* Mobile: Main content sem margin */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0 !important;
        padding-top: 80px;
    }
}

/* Tablet: Main content com margin */
@media (min-width: 769px) and (max-width: 1024px) {
    .main-content {
        margin-left: 320px;
    }
}

/* Desktop */
@media (min-width: 1025px) {
    .main-content {
        margin-left: 280px;
    }
}

/* Focus styles */
.nav-item:focus-visible,
.logo:focus-visible,
.sidebar-close:focus-visible,
.user-menu:focus-visible,
.logout-item:focus-visible,
.hamburger-menu:focus-visible {
    outline: 2px solid var(--sidebar-primary);
    outline-offset: 2px;
}

/* Print styles */
@media print {
    .sidebar, .hamburger-menu, .sidebar-overlay {
        display: none;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.nav-item {
    animation: fadeIn 0.3s ease forwards;
}

/* Scroll behavior */
.sidebar-nav {
    scroll-behavior: smooth;
}

/* Mobile touch improvements */
@media (max-width: 768px) {
    .nav-item {
        padding: 14px 16px;
    }
    .nav-item i {
        font-size: 1.3rem;
    }
    .nav-item span:not(.nav-badge) {
        font-size: 1rem;
    }
    .nav-section-title {
        font-size: 0.8rem;
        padding: 16px 16px 8px 16px;
    }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script src="/cardoxis/public/assets/js/dashboard/welcome.js"></script>
<!-- No final do body, antes do fechamento -->
<script src="/cardoxis/public/assets/js/offline.js"></script>

<script src="<?php echo asset('js/dashboard/welcome.js'); ?>"></script>
<script src="<?php echo asset('js/offline.js'); ?>"></script>

<script>
(function() {
    'use strict';
    
    // ============================================
    // CONFIGURAÇÕES
    // ============================================
    const CONFIG = {
        baseUrl: '/cardoxis',
        logoutUrl: '/cardoxis/logout',
        version: '11.0.0'
    };
    
    // ============================================
    // FUNÇÃO DE LOGOUT
    // ============================================
    async function performLogout() {
        const logoutBtn = document.getElementById('logoutSidebarBtn');
        
        if (logoutBtn) {
            logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>A terminar sessão...</span>';
            logoutBtn.disabled = true;
        }
        
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            fetch(CONFIG.logoutUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin'
            }).catch(() => {});
            
        } catch (error) {
            console.error('[CARDOXIS] Erro no logout:', error);
        }
        
        // Limpar storages
        try {
            localStorage.clear();
            sessionStorage.clear();
        } catch(e) {}
        
        // Limpar cookies
        document.cookie.split(";").forEach(c => {
            document.cookie = c
                .replace(/^ +/, "")
                .replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
        });
        
        // Limpar cache
        if ('caches' in window) {
            try {
                const cacheNames = await caches.keys();
                await Promise.all(cacheNames.map(name => caches.delete(name)));
            } catch(e) {}
        }
        
        // Redirecionar
        window.location.href = CONFIG.baseUrl + '/login';
    }
    
    // ============================================
    // SISTEMA DE STATUS ONLINE/OFFLINE
    // ============================================
    function initSystemStatus() {
        const systemStatus = document.getElementById('systemStatus');
        const statusIndicator = document.querySelector('.status-indicator');
        const statusText = document.querySelector('.status-text');
        
        function updateStatus() {
            if (navigator.onLine) {
                systemStatus.classList.remove('offline');
                systemStatus.classList.add('online');
                statusIndicator.classList.remove('offline');
                statusIndicator.classList.add('online');
                statusText.textContent = 'Sistema Online';
            } else {
                systemStatus.classList.remove('online');
                systemStatus.classList.add('offline');
                statusIndicator.classList.remove('online');
                statusIndicator.classList.add('offline');
                statusText.textContent = 'Ligação à Internet indisponível';
            }
        }
        
        updateStatus();
        window.addEventListener('online', updateStatus);
        window.addEventListener('offline', updateStatus);
        setInterval(updateStatus, 5000);
    }
    
    // ============================================
    // MENU MOBILE - HAMBURGER
    // ============================================
    function initMobileMenu() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
        
        function openSidebar() {
            if (sidebar) sidebar.classList.add('open');
            if (overlay) overlay.classList.add('active');
            if (hamburgerMenu) hamburgerMenu.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        
        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            if (hamburgerMenu) hamburgerMenu.classList.remove('open');
            document.body.style.overflow = '';
        }
        
        function toggleSidebar() {
            if (sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }
        
        // Eventos do hamburger
        if (hamburgerMenu) {
            hamburgerMenu.addEventListener('click', function(e) {
                e.preventDefault();
                toggleSidebar();
            });
        }
        
        // Botão de fechar
        if (sidebarCloseBtn) {
            sidebarCloseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeSidebar();
            });
        }
        
        // Overlay
        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }
        
        // Fechar com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });
        
        // Fechar ao clicar em link no mobile
        document.querySelectorAll('.nav-item').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    setTimeout(closeSidebar, 300);
                }
            });
        });
    }
    
    // ============================================
    // USER DROPDOWN
    // ============================================
    function initUserDropdown() {
        const userMenu = document.getElementById('userMenu');
        const userDropdown = document.getElementById('userDropdown');
        const userChevron = document.getElementById('userChevron');
        
        if (userMenu && userDropdown) {
            let isOpen = false;
            
            function closeDropdown() {
                userDropdown.classList.remove('show');
                if (userChevron) userChevron.style.transform = 'rotate(0deg)';
                isOpen = false;
            }
            
            function openDropdown() {
                userDropdown.classList.add('show');
                if (userChevron) userChevron.style.transform = 'rotate(180deg)';
                isOpen = true;
            }
            
            userMenu.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (isOpen) {
                    closeDropdown();
                } else {
                    openDropdown();
                }
            });
            
            document.addEventListener('click', function(e) {
                if (!userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
                    closeDropdown();
                }
            });
        }
    }
    
    // ============================================
    // ACTIVE LINK DETECTION
    // ============================================
    function initActiveLinks() {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-item').forEach(link => {
            const href = link.getAttribute('href');
            if (href && href !== '#' && href !== '/') {
                if (currentPath.includes(href) || 
                    (href === '/cardoxis/dashboard' && currentPath === '/cardoxis/')) {
                    document.querySelectorAll('.nav-item').forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                }
            }
        });
    }
    
    // ============================================
    // LOGOUT BUTTON
    // ============================================
    function initLogout() {
        const logoutBtn = document.getElementById('logoutSidebarBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                performLogout();
            });
        }
    }
    
    // ============================================
    // INICIALIZAÇÃO
    // ============================================
    function init() {
        initMobileMenu();
        initUserDropdown();
        initActiveLinks();
        initLogout();
        initSystemStatus();
        console.log('[CARDOXIS] Sidebar inicializado - Versão ' + CONFIG.version);
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<!-- CSS adicional para o conteúdo principal -->
<style>
/* Ajustes para o conteúdo principal */
.main-content {
    margin-left: 280px;
    transition: margin-left 0.3s ease;
    min-height: 100vh;
    padding: 20px;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0 !important;
        padding: 80px 16px 20px 16px;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .main-content {
        margin-left: 320px;
    }
}

@media (min-width: 1025px) {
    .main-content {
        margin-left: 280px;
    }
}

/* Animações suaves */
* {
    -webkit-tap-highlight-color: transparent;
}

/* Melhorias para mobile */
@media (max-width: 768px) {
    body.sidebar-open {
        overflow: hidden;
    }
    
    ::-webkit-scrollbar {
        width: 3px;
    }
}
</style>

