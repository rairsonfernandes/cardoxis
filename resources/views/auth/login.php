<?php
/**
 * CARDOXIS - Página de Login RF
 */

// Headers para evitar cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Verificar se já existe sessão ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já estiver logado, redirecionar
if (isset($_SESSION['user_id']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $role = $_SESSION['user_role'] ?? 'user';
    $redirectPath = in_array($role, ['super_admin', 'admin']) ? '/admin/dashboard' : '/dashboard';
    header('Location: /cardoxis' . $redirectPath);
    exit;
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verificar mensagens da URL
$logoutMessage = '';
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $logoutMessage = 'Sessão terminada com sucesso. Faça login novamente.';
} elseif (isset($_GET['expired']) && $_GET['expired'] == '1') {
    $logoutMessage = 'Sessão expirada. Faça login novamente.';
}

$pageConfig = [
    'title' => 'Entrar - CARDOXIS',
    'description' => 'Acesso seguro à plataforma de gestão de frotas CARDOXIS.',
    'theme_color' => '#0052CC',
    'version' => '11.0.0'
];
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=yes">
    <meta name="theme-color" content="<?php echo $pageConfig['theme_color']; ?>">
    <meta name="description" content="<?php echo htmlspecialchars($pageConfig['description']); ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <title><?php echo htmlspecialchars($pageConfig['title']); ?></title>

    <!-- Preconnect para melhor performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- ============================================ -->
    <!-- AUTH STYLES - CSS EXTERNO -->
    <!-- ============================================ -->
    <link rel="stylesheet" href="<?php echo asset('css/auth/auth.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/auth/auth-responsive.css'); ?>">
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?php echo asset('img/logo/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo asset('img/logo/apple-touch-icon.png'); ?>">
    
    <script>
        // ============================================
        // LIMPAR CACHE E TOKENS AO CARREGAR
        // ============================================
        (function() {
            try {
                const tokenKeys = ['auth_token', 'user_data', 'user_name', 'token_expiry', 'remember_token'];
                tokenKeys.forEach(key => {
                    localStorage.removeItem(key);
                    sessionStorage.removeItem(key);
                });
                
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('logout') === '1') {
                    console.log('[CARDOXIS] Sessão terminada com sucesso');
                    try {
                        localStorage.clear();
                        sessionStorage.clear();
                    } catch(e) {}
                }
                
                if (urlParams.has('logout') || urlParams.has('expired')) {
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, newUrl);
                }
            } catch(e) {
                console.log('[CARDOXIS] Erro ao limpar cache:', e);
            }
        })();
    </script>
</head>
<body>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="spinner"></div>
</div>

<!-- SKIP TO MAIN -->
<a href="#main-content" class="skip-to-main">Saltar para o conteúdo principal</a>

<!-- LOGIN CONTAINER -->
<div class="login-container" data-aos="fade-up" data-aos-duration="800">
    
    <!-- Left Side - Branding -->
    <div class="login-brand" data-aos="fade-right" data-aos-duration="800" data-aos-delay="100">
        <a href="<?php echo url(''); ?>" class="brand-logo">
            <div class="logo-icon">
                <img src="<?php echo asset('img/logo/logo-white.png'); ?>" alt="CARDOXIS Logo" width="44" height="44" loading="eager">
            </div>
            <div class="logo-text">CARDOXIS<span>.io</span></div>
        </a>

        <div class="brand-content">
            <div class="brand-quote">
                <p>Acesso seguro à plataforma de inteligência empresarial.</p>
                <div class="cert-badge">
                    <i class="fas fa-shield-alt"></i> 
                    <span>Certificação ISO 27001</span>
                </div>
            </div>
            <div class="stats-row">
                <div class="stat-item"><h4>+5k</h4><p>EMPRESAS</p></div>
                <div class="stat-item"><h4>99.9%</h4><p>UPTIME</p></div>
                <div class="stat-item"><h4>24/7</h4><p>SUPORTE</p></div>
            </div>
        </div>
    </div>

    <!-- Right Side - Login Form -->
    <div class="login-form-wrapper" id="main-content" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
        
        <div class="welcome-header">
            <h1>Bem-vindo de volta</h1>
            <p>Inicie sessão com os seus dados para se manter ligado à plataforma.</p>
        </div>

        <!-- Alert Container - Apenas para mensagens do servidor (logout) -->
        <div class="alert-container" id="alertContainer">
            <?php if (!empty($logoutMessage)): ?>
                <div class="alert alert-success" id="logoutAlert">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($logoutMessage); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Login Form - Todo o JavaScript está em login.js -->
        <form id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <!-- Email -->
            <div class="input-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email
                </label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" 
                           placeholder="exemplo@cardoxis.io" 
                           autocomplete="email" 
                           required>
                </div>
            </div>

            <!-- Password -->
            <div class="input-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Palavra-passe
                </label>
                <div class="input-wrapper password-wrapper">
                    <input type="password" id="password" name="password" 
                           placeholder="••••••••" 
                           autocomplete="current-password" 
                           required>
                    <i class="fas fa-eye-slash toggle-password-icon" 
                       id="togglePasswordIcon" 
                       role="button" 
                       aria-label="Mostrar/ocultar palavra-passe"></i>
                </div>
            </div>

            <!-- Options -->
            <div class="options-row">
                <label class="checkbox-label">
                    <input type="checkbox" id="rememberCheckbox" name="remember">
                    <span>Lembrar-me</span>
                </label>
                <a href="<?php echo url('forgot-password'); ?>" class="forgot-link">
                    Esqueceu a palavra-passe?
                </a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-arrow-right-to-bracket"></i> 
                <span>Entrar</span>
            </button>

            <!-- Sign Up -->
            <div class="signup-prompt">
                Não tem uma conta? 
                <a href="<?php echo url('register'); ?>">Registe-se</a>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPTS -->

<!-- AOS Animation -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<!-- Login JS - Único arquivo com toda a lógica -->
<script src="<?php echo asset('js/auth/login.js'); ?>"></script>


<script>
    // AOS INIT

    AOS.init({ 
        duration: 800, 
        once: true, 
        offset: 50, 
        easing: 'ease-out' 
    });
    
    // AUTO CLOSE ALERTS DO SERVIDOR
    setTimeout(function() {
        const alert = document.getElementById('logoutAlert');
        if (alert && alert.parentElement) {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                if (alert && alert.parentElement) {
                    alert.remove();
                }
            }, 500);
        }
    }, 5000);
    
</script>

</body>
</html>