<?php
/**
 * CARDOXIS - Página de Redefinição de Senha RF
 */

// Headers para evitar cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se existe uma recuperação verificada
if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
    header('Location: /cardoxis/forgot-password?error=invalid_token');
    exit;
}

// Verificar se o token ainda é válido
if (isset($_SESSION['reset_expires']) && time() > $_SESSION['reset_expires']) {
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_token']);
    unset($_SESSION['reset_verified']);
    unset($_SESSION['reset_expires']);
    header('Location: /cardoxis/forgot-password?error=invalid_token');
    exit;
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

// Email para exibição (mascarado)
$email = $_SESSION['reset_email'] ?? '';
$emailDisplay = $email;
if (strpos($email, '@') !== false) {
    list($user, $domain) = explode('@', $email);
    $emailDisplay = substr($user, 0, 2) . '***@' . $domain;
}

// Verificar mensagens
$errorMessage = '';
if (isset($_GET['error'])) {
    $errorMessages = [
        'password_required' => 'Por favor, insira a nova senha.',
        'password_short' => 'A senha deve ter no mínimo 6 caracteres.',
        'password_mismatch' => 'As senhas não coincidem.',
        'server_error' => 'Ocorreu um erro. Tente novamente.',
        'invalid_token' => 'Sessão inválida. Solicite um novo código.'
    ];
    $errorMessage = $errorMessages[$_GET['error']] ?? 'Ocorreu um erro. Tente novamente.';
}

$pageConfig = [
    'title' => 'Redefinir Senha - CARDOXIS',
    'description' => 'Crie uma nova senha para a sua conta.',
    'theme_color' => '#0052CC',
    'version' => '2.0.0'
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
    
    <!-- CSS Específico -->
    <link rel="stylesheet" href="<?php echo asset('css/auth/auth.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/auth/reset-password.css'); ?>">
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?php echo asset('img/logo/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo asset('img/logo/apple-touch-icon.png'); ?>">
    
    <script>
        // Limpar tokens e cache ao carregar
        (function() {
            try {
                const tokenKeys = ['auth_token', 'user_data', 'user_name', 'token_expiry', 'reset_token'];
                tokenKeys.forEach(key => {
                    localStorage.removeItem(key);
                    sessionStorage.removeItem(key);
                });
            } catch(e) {}
        })();
    </script>
</head>
<body>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="spinner"></div>
</div>

<!-- Skip to main -->
<a href="#main-content" class="skip-to-main">Saltar para o conteúdo principal</a>

<!-- Container Principal -->
<div class="reset-container" data-aos="fade-up" data-aos-duration="800">
    
    <!-- Left Side - Branding -->
    <div class="reset-brand" data-aos="fade-right" data-aos-duration="800" data-aos-delay="100">
        <a href="<?php echo url(''); ?>" class="brand-logo">
            <div class="logo-icon">
                <img src="<?php echo asset('img/logo/logo-white.png'); ?>" alt="CARDOXIS Logo" width="44" height="44" loading="eager">
            </div>
            <div class="logo-text">CARDOXIS<span>.io</span></div>
        </a>

        <div class="brand-content">
            <div class="brand-quote">
                <p>Crie uma nova <span style="color: #7AA9E0;">senha segura</span> para a sua conta.</p>
                <div class="security-badge">
                    <i class="fas fa-shield-alt"></i> 
                    <span>Protegido com criptografia AES-256</span>
                </div>
            </div>
            <div class="stats-row">
                <div class="stat-item"><h4>+5k</h4><p>EMPRESAS</p></div>
                <div class="stat-item"><h4>99.9%</h4><p>UPTIME</p></div>
                <div class="stat-item"><h4>24/7</h4><p>SUPORTE</p></div>
            </div>
        </div>
    </div>

    <!-- Right Side - Form -->
    <div class="reset-form-wrapper" id="main-content" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
        
        <div class="welcome-header">
            <h1><i class="fas fa-lock" style="color: var(--primary); margin-right: 12px;"></i>Nova Senha</h1>
            <p>Crie uma nova senha forte e segura para a sua conta.</p>
            <div class="email-display">
                <i class="fas fa-envelope"></i>
                <span><?php echo htmlspecialchars($emailDisplay); ?></span>
            </div>
        </div>

        <!-- Alert Container -->
        <div class="alert-container" id="alertContainer">
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-error" id="serverAlert">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <span class="alert-title">Erro</span>
                        <span class="alert-message"><?php echo htmlspecialchars($errorMessage); ?></span>
                    </div>
                    <button class="alert-close" aria-label="Fechar alerta">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="alert-progress"></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Form -->
        <form id="resetForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <!-- Nova Senha -->
            <div class="input-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Nova Senha
                </label>
                <div class="input-wrapper password-wrapper">
                    <input type="password" id="password" name="password" 
                           placeholder="••••••••" 
                           autocomplete="new-password"
                           required>
                    <button type="button" class="toggle-password-btn" id="togglePasswordBtn" aria-label="Mostrar senha">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-bar"></div>
                    <div class="strength-bar"></div>
                    <div class="strength-bar"></div>
                    <div class="strength-bar"></div>
                    <span class="strength-text" id="strengthText"></span>
                </div>
                <div class="password-requirements">
                    <ul>
                        <li id="req-length"><i class="fas fa-circle"></i> Mínimo 6 caracteres</li>
                        <li id="req-upper"><i class="fas fa-circle"></i> Pelo menos 1 letra maiúscula</li>
                        <li id="req-lower"><i class="fas fa-circle"></i> Pelo menos 1 letra minúscula</li>
                        <li id="req-number"><i class="fas fa-circle"></i> Pelo menos 1 número</li>
                        <li id="req-special"><i class="fas fa-circle"></i> Pelo menos 1 caractere especial</li>
                    </ul>
                </div>
            </div>

            <!-- Confirmar Senha -->
            <div class="input-group">
                <label for="password_confirm">
                    <i class="fas fa-check-circle"></i> Confirmar Senha
                </label>
                <div class="input-wrapper password-wrapper">
                    <input type="password" id="password_confirm" name="password_confirm" 
                           placeholder="••••••••" 
                           autocomplete="new-password"
                           required>
                    <button type="button" class="toggle-password-btn" id="toggleConfirmBtn" aria-label="Mostrar senha">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>
                <div class="password-match" id="passwordMatch">
                    <span class="match-text"></span>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-reset" id="resetBtn">
                <i class="fas fa-save"></i> 
                <span>Redefinir Senha</span>
            </button>

            <!-- Back to Login -->
            <div class="back-to-login">
                <i class="fas fa-arrow-left"></i>
                <a href="<?php echo url('login'); ?>">Voltar para o login</a>
            </div>

            <!-- Help -->
            <div class="help-section">
                <p>
                    <i class="fas fa-headset"></i>
                    Precisa de ajuda? <a href="mailto:suporte@cardoxis.io">suporte@cardoxis.io</a>
                </p>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="<?php echo asset('js/auth/reset-password.js'); ?>"></script>

<script>
    // Inicializar AOS
    AOS.init({ 
        duration: 800, 
        once: true, 
        offset: 50, 
        easing: 'ease-out' 
    });
    
    // Auto close server alerts
    setTimeout(function() {
        const alert = document.getElementById('serverAlert');
        if (alert && alert.parentElement) {
            alert.classList.add('alert-closing');
            setTimeout(function() {
                if (alert && alert.parentElement) {
                    alert.remove();
                }
            }, 300);
        }
    }, 8000);
</script>

</body>
</html>