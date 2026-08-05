<?php
/**
 * CARDOXIS - Página de Registo RF
 */

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

if (isset($_SESSION['user_id']) && isset($_SESSION['authenticated'])) {
    $role = $_SESSION['user_role'] ?? 'user';
    if (in_array($role, ['super_admin', 'admin'])) {
        header('Location: /cardoxis/admin/dashboard');
    } else {
        header('Location: /cardoxis/dashboard');
    }
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$old_name = $_SESSION['old_name'] ?? '';
$old_email = $_SESSION['old_email'] ?? '';
$old_phone = $_SESSION['old_phone'] ?? '';
unset($_SESSION['old_name'], $_SESSION['old_email'], $_SESSION['old_phone']);
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0052CC">
    <meta name="robots" content="noindex, nofollow">
    <title>Registo - CARDOXIS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="<?php echo asset('css/auth/register.css'); ?>">

    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?php echo asset('img/logo/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo asset('img/logo/apple-touch-icon.png'); ?>">
</head>
<body>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="spinner"></div>
</div>

<!-- SKIP TO MAIN -->
<a href="#main-content" class="skip-to-main">Saltar para o conteúdo principal</a>

<!-- REGISTER CONTAINER -->
<div class="register-container">
    
    <!-- Left Side - Branding -->
    <div class="register-brand">
        <a href="<?php echo url(''); ?>" class="brand-logo">
            <div class="logo-icon">
                <img src="<?php echo asset('img/logo/logo-white.png'); ?>" alt="CARDOXIS Logo" width="44" height="44">
            </div>
            <div class="logo-text">CARDOXIS<span>.io</span></div>
        </a>
        <div class="brand-content">
            <div class="brand-quote">
                <p>Junte-se à plataforma de gestão de frotas mais completa do mercado.</p>
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
    <div class="register-form-wrapper" id="main-content">
        <div class="welcome-header">
            <h1>Criar Conta</h1>
            <p>Preencha os dados abaixo para começar a gerir a sua frota.</p>
        </div>

        <!-- Alert Container - Premium -->
        <div class="alert-container" id="alertContainer">
            <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <div class="alert alert-error">
                        <div class="alert-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="alert-content">
                            <span class="alert-title">Erro</span>
                            <span class="alert-message"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                        <button class="alert-close" aria-label="Fechar alerta">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="alert-progress"></div>
                    </div>
                <?php endforeach; ?>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>
        </div>

        <!-- Register Form -->
        <form id="registerForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <!-- Nome Completo -->
            <div class="input-group">
                <label for="name">
                    <i class="fas fa-user"></i> Nome completo <span class="required">*</span>
                </label>
                <div class="input-wrapper">
                    <input type="text" id="name" name="name" 
                           placeholder="João Silva" 
                           autocomplete="name"
                           value="<?php echo htmlspecialchars($old_name); ?>"
                           required>
                </div>
            </div>

            <!-- Email -->
            <div class="input-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email <span class="required">*</span>
                </label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" 
                           placeholder="exemplo@cardoxis.io" 
                           autocomplete="email"
                           value="<?php echo htmlspecialchars($old_email); ?>"
                           required>
                </div>
            </div>

            <!-- Telefone -->
            <div class="input-group">
                <label for="phone">
                    <i class="fas fa-phone-alt"></i> Telefone 
                    <span class="optional-badge">Opcional</span>
                </label>
                <div class="input-wrapper">
                    <input type="tel" id="phone" name="phone" 
                           placeholder="912 345 678" 
                           autocomplete="tel"
                           value="<?php echo htmlspecialchars($old_phone); ?>">
                </div>
                <div class="info-hint">
                    <i class="fas fa-info-circle"></i>
                    Número de contacto para notificações e suporte
                </div>
            </div>

            <!-- Senha -->
            <div class="input-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Senha <span class="required">*</span>
                </label>
                <div class="input-wrapper password-wrapper">
                    <input type="password" id="password" name="password" 
                           placeholder="••••••••" 
                           autocomplete="new-password"
                           required>
                    <div class="password-actions">
                        <button type="button" class="generate-password-btn" id="generatePasswordBtn" title="Gerar senha forte">
                            <i class="fas fa-dice"></i>
                        </button>
                        <button type="button" class="toggle-password-icon" data-target="password" aria-label="Mostrar palavra-passe">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>
                <div class="password-strength">
                    <div class="strength-bar"></div>
                    <div class="strength-bar"></div>
                    <div class="strength-bar"></div>
                    <div class="strength-bar"></div>
                    <span class="strength-text" id="strengthText"></span>
                </div>
                <div class="info-hint">
                    <i class="fas fa-info-circle"></i>
                    Mínimo 6 caracteres
                </div>
            </div>

            <!-- Confirmar Senha - CORRIGIDO -->
            <div class="input-group">
                <label for="password_confirm">
                    <i class="fas fa-check-circle"></i> Confirmar senha <span class="required">*</span>
                </label>
                <div class="input-wrapper password-wrapper">
                    <input type="password" id="password_confirm" name="password_confirm" 
                           placeholder="••••••••" 
                           autocomplete="off"
                           required>
                    <div class="password-actions">
                        <button type="button" class="toggle-password-icon" data-target="password_confirm" aria-label="Mostrar palavra-passe">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Termos -->
            <div class="terms-row">
                <label class="checkbox-label">
                    <input type="checkbox" id="terms" name="terms" required>
                    <span>
                        Eu li e aceito os <a href="<?php echo url('terms'); ?>" target="_blank">Termos de Serviço</a> 
                        e a <a href="<?php echo url('privacy'); ?>" target="_blank">Política de Privacidade</a>
                    </span>
                </label>
            </div>

            <button type="submit" class="btn-register" id="registerBtn">
                <i class="fas fa-user-plus"></i> Criar Conta
            </button>

            <div class="login-prompt">
                Já tem uma conta? <a href="<?php echo url('login'); ?>">Iniciar sessão</a>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPTS -->

<!-- IntlTelInput -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>

<!-- Register JS -->
<script src="<?php echo asset('js/auth/register.js'); ?>"></script>

</body>
</html>