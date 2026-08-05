<?php
/**
 * CARDOXIS - Página de Recuperação de Senha RF
 */

// Headers para evitar cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Iniciar sessão
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
$errorMessage = '';
$successMessage = '';

if (isset($_GET['error'])) {
    $errorMessages = [
        'email_required' => 'Por favor, insira o seu email.',
        'email_invalid' => 'Por favor, insira um email válido.',
        'email_not_found' => 'Email não encontrado. Verifique e tente novamente.',
        'rate_limit' => 'Muitas tentativas. Aguarde alguns minutos antes de tentar novamente.',
        'server_error' => 'Ocorreu um erro ao processar o pedido. Tente novamente.',
        'invalid_token' => 'Token inválido ou expirado.',
        'user_not_found' => 'Utilizador não encontrado.'
    ];
    $errorMessage = $errorMessages[$_GET['error']] ?? 'Ocorreu um erro. Tente novamente.';
} elseif (isset($_GET['success'])) {
    $successMessages = [
        'email_sent' => 'Um código de recuperação foi enviado para o seu email. Verifique a sua caixa de entrada.',
        'reset_done' => 'A sua palavra-passe foi redefinida com sucesso!'
    ];
    $successMessage = $successMessages[$_GET['success']] ?? 'Operação realizada com sucesso!';
}

$pageConfig = [
    'title' => 'Recuperar Senha - CARDOXIS',
    'description' => 'Recupere o acesso à sua conta CARDOXIS.',
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
    <link rel="stylesheet" href="<?php echo asset('css/auth/forgot-password.css'); ?>">
    
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
<div class="forgot-container" data-aos="fade-up" data-aos-duration="800">
    
    <!-- Left Side - Branding -->
    <div class="forgot-brand" data-aos="fade-right" data-aos-duration="800" data-aos-delay="100">
        <a href="<?php echo url(''); ?>" class="brand-logo">
            <div class="logo-icon">
                <img src="<?php echo asset('img/logo/logo-white.png'); ?>" alt="CARDOXIS Logo" width="44" height="44" loading="eager">
            </div>
            <div class="logo-text">CARDOXIS<span>.io</span></div>
        </a>

        <div class="brand-content">
            <div class="brand-quote">
                <p>Recupere o acesso à sua conta com <span style="color: #7AA9E0;">segurança</span> e <span style="color: #7AA9E0;">rapidez</span>.</p>
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

    <!-- Right Side - Form -->
    <div class="forgot-form-wrapper" id="main-content" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
        
        <div class="welcome-header">
            <h1><i class="fas fa-key" style="color: var(--primary); margin-right: 12px;"></i>Recuperar Senha</h1>
            <p>Introduza o seu email para receber um código de verificação e redefinir a sua palavra-passe.</p>
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
            <?php elseif (!empty($successMessage)): ?>
                <div class="alert alert-success" id="serverAlert">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-content">
                        <span class="alert-title">Sucesso</span>
                        <span class="alert-message"><?php echo htmlspecialchars($successMessage); ?></span>
                    </div>
                    <button class="alert-close" aria-label="Fechar alerta">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="alert-progress"></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Formulário -->
        <form id="forgotForm" novalidate>
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
                    <div class="input-icon-right">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="input-helper">
                    <i class="fas fa-info-circle"></i>
                    Insira o email associado à sua conta
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-forgot" id="forgotBtn">
                <i class="fas fa-paper-plane"></i> 
                <span>Enviar Código</span>
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
<script src="<?php echo asset('js/auth/forgot-password.js'); ?>"></script>

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