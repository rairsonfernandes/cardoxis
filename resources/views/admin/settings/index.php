<?php
// Verificar autenticação
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Configurações | CARDOXIS';

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
    <meta name="description" content="Configurações do sistema - CARDOXIS">
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
    <link rel="stylesheet" href="<?= asset('css/admin/settings.css') ?>">
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
        <div class="mobile-logo-text">CARDOXIS<span>.io</span></div>
    </div>
    <div class="mobile-actions">
        <button class="btn-icon-refresh" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i>
            <span>Atualizar</span>
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
        <div class="page-header">
            <div>
                <h1 class="page-title">Configurações do Sistema</h1>
                <p class="page-subtitle">Gerencie as configurações da plataforma</p>
            </div>
        </div>
        
        <!-- Settings Container -->
        <div class="settings-container">
            <!-- Sidebar -->
            <div class="settings-sidebar">
                <nav class="settings-nav">
                    <div class="settings-nav-item active" data-pane="general">
                        <i class="fas fa-globe"></i>
                        <span>Geral</span>
                    </div>
                    <div class="settings-nav-item" data-pane="email">
                        <i class="fas fa-envelope"></i>
                        <span>Email</span>
                    </div>
                    <div class="settings-nav-item" data-pane="payment">
                        <i class="fas fa-credit-card"></i>
                        <span>Pagamento</span>
                    </div>
                    <div class="settings-nav-item" data-pane="security">
                        <i class="fas fa-shield-alt"></i>
                        <span>Segurança</span>
                    </div>
                    <div class="settings-nav-item" data-pane="integrations">
                        <i class="fas fa-plug"></i>
                        <span>Integrações</span>
                    </div>
                </nav>
            </div>
            
            <!-- Content -->
            <div class="settings-content">

                <!-- PAINEL: CONFIGURAÇÕES GERAIS -->
                <div id="pane-general" class="settings-pane active">
                    <div class="settings-header">
                        <h2>Configurações Gerais</h2>
                        <p>Configure as informações básicas da plataforma</p>
                    </div>
                    <form class="settings-form" id="generalForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nome da Aplicação <span class="required">*</span></label>
                                <input type="text" id="app_name" placeholder="CARDOXIS">
                                <div class="help-text">Nome que aparecerá no título da página</div>
                            </div>
                            <div class="form-group">
                                <label>Versão</label>
                                <input type="text" id="app_version" placeholder="1.0.0">
                                <div class="help-text">Versão atual da aplicação</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Nome da Empresa</label>
                            <input type="text" id="company_name" placeholder="CARDOXIS Sistemas">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email de Suporte</label>
                                <input type="email" id="support_email" placeholder="suporte@cardoxis.com">
                            </div>
                            <div class="form-group">
                                <label>Telefone de Suporte</label>
                                <input type="text" id="support_phone" placeholder="+351 300 123 456">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tamanho Máximo de Upload (MB)</label>
                                <input type="number" id="upload_max_size" placeholder="10">
                                <div class="help-text">Tamanho máximo permitido para upload de arquivos</div>
                            </div>
                            <div class="switch-group">
                                <div>
                                    <div class="switch-label">Modo de Manutenção</div>
                                    <div class="switch-description">Ativar modo de manutenção do sistema</div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="maintenance_mode">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" id="resetGeneralBtn">Cancelar</button>
                            <button type="button" class="btn-primary" id="saveGeneralBtn">
                                <i class="fas fa-save"></i> Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- PAINEL: CONFIGURAÇÕES DE EMAIL -->
                <div id="pane-email" class="settings-pane">
                    <div class="settings-header">
                        <h2>Configurações de Email</h2>
                        <p>Configure o envio de emails do sistema</p>
                    </div>
                    <form class="settings-form" id="emailForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Servidor SMTP</label>
                                <input type="text" id="smtp_host" placeholder="smtp.gmail.com">
                            </div>
                            <div class="form-group">
                                <label>Porta SMTP</label>
                                <input type="number" id="smtp_port" placeholder="587">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Criptografia</label>
                                <select id="smtp_encryption">
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                    <option value="none">Nenhuma</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Usuário SMTP</label>
                                <input type="text" id="smtp_username" placeholder="seu@email.com">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Senha SMTP</label>
                                <input type="password" id="smtp_password" placeholder="********">
                                <div class="help-text">Deixe em branco para manter a senha atual</div>
                            </div>
                            <div class="form-group">
                                <label>Email de Envio</label>
                                <input type="email" id="mail_from" placeholder="noreply@cardoxis.com">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Nome do Remetente</label>
                            <input type="text" id="mail_from_name" placeholder="CARDOXIS">
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" id="resetEmailBtn">Cancelar</button>
                            <button type="button" class="btn-test" id="testEmailBtn">
                                <i class="fas fa-vial"></i> Testar Conexão
                            </button>
                            <button type="button" class="btn-primary" id="saveEmailBtn">
                                <i class="fas fa-save"></i> Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- PAINEL: CONFIGURAÇÕES DE PAGAMENTO -->
                <div id="pane-payment" class="settings-pane">
                    <div class="settings-header">
                        <h2>Configurações de Pagamento</h2>
                        <p>Configure os gateways de pagamento</p>
                    </div>
                    <form class="settings-form" id="paymentForm">
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fab fa-stripe"></i>
                                <span>Stripe</span>
                            </div>
                            <div class="form-group">
                                <label>Chave Pública (Publishable Key)</label>
                                <input type="text" id="stripe_key" placeholder="pk_test_...">
                            </div>
                            <div class="form-group">
                                <label>Chave Secreta (Secret Key)</label>
                                <input type="password" id="stripe_secret" placeholder="sk_test_...">
                                <div class="help-text">Deixe em branco para manter a chave atual</div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-credit-card"></i>
                                <span>Pagarme</span>
                            </div>
                            <div class="form-group">
                                <label>API Key</label>
                                <input type="password" id="pagarme_key" placeholder="ak_test_...">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fab fa-amazon-pay"></i>
                                <span>Mercado Pago</span>
                            </div>
                            <div class="form-group">
                                <label>Access Token</label>
                                <input type="password" id="mercadopago_key" placeholder="APP_USR-...">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Moeda Padrão</label>
                            <select id="currency">
                                <option value="EUR">Euro (€)</option>
                                <option value="USD">Dólar Americano ($)</option>
                                <option value="BRL">Real Brasileiro (R$)</option>
                                <option value="GBP">Libra Esterlina (£)</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" id="resetPaymentBtn">Cancelar</button>
                            <button type="button" class="btn-test" id="testPaymentBtn">
                                <i class="fas fa-vial"></i> Testar Conexão
                            </button>
                            <button type="button" class="btn-primary" id="savePaymentBtn">
                                <i class="fas fa-save"></i> Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- PAINEL: CONFIGURAÇÕES DE SEGURANÇA -->
                <div id="pane-security" class="settings-pane">
                    <div class="settings-header">
                        <h2>Configurações de Segurança</h2>
                        <p>Configure as políticas de segurança do sistema</p>
                    </div>
                    <form class="settings-form" id="securityForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Máximo de Tentativas de Login</label>
                                <input type="number" id="max_login_attempts" placeholder="5">
                                <div class="help-text">Número de tentativas antes de bloquear o usuário</div>
                            </div>
                            <div class="form-group">
                                <label>Tempo de Sessão (minutos)</label>
                                <input type="number" id="session_timeout" placeholder="120">
                                <div class="help-text">Tempo de inatividade antes de expirar a sessão</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Expiração de Senha (dias)</label>
                                <input type="number" id="password_expiry_days" placeholder="90">
                                <div class="help-text">0 para nunca expirar</div>
                            </div>
                            <div class="form-group">
                                <label>Tamanho Mínimo da Senha</label>
                                <input type="number" id="password_min_length" placeholder="8">
                            </div>
                        </div>
                        
                        <div class="switch-group">
                            <div>
                                <div class="switch-label">Autenticação de Dois Fatores</div>
                                <div class="switch-description">Exigir 2FA para todos os utilizadores</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="two_factor_auth">
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" id="resetSecurityBtn">Cancelar</button>
                            <button type="button" class="btn-primary" id="saveSecurityBtn">
                                <i class="fas fa-save"></i> Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- PAINEL: CONFIGURAÇÕES DE INTEGRAÇÕES -->
                <div id="pane-integrations" class="settings-pane">
                    <div class="settings-header">
                        <h2>Configurações de Integrações</h2>
                        <p>Configure APIs e serviços de terceiros</p>
                    </div>
                    <form class="settings-form" id="integrationsForm">
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-map-marked-alt"></i>
                                <span>Mapas</span>
                            </div>
                            <div class="form-group">
                                <label>Google Maps API Key</label>
                                <input type="text" id="google_maps_api" placeholder="AIzaSy...">
                                <div class="help-text">Chave da API do Google Maps</div>
                            </div>
                            <div class="form-group">
                                <label>Here Maps API Key</label>
                                <input type="text" id="here_api_key" placeholder="...">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-bell"></i>
                                <span>Notificações</span>
                            </div>
                            <div class="form-group">
                                <label>Webhook URL</label>
                                <input type="url" id="webhook_url" placeholder="https://...">
                                <div class="help-text">URL para webhooks de eventos</div>
                            </div>
                            <div class="form-group">
                                <label>Slack Webhook</label>
                                <input type="url" id="slack_webhook" placeholder="https://hooks.slack.com/...">
                                <div class="help-text">Webhook para notificações no Slack</div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" id="resetIntegrationsBtn">Cancelar</button>
                            <button type="button" class="btn-primary" id="saveIntegrationsBtn">
                                <i class="fas fa-save"></i> Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner-large"></div>
</div>

<!-- Admin JS -->
<script src="<?= asset('js/admin.js') ?>"></script>
<script src="<?= asset('js/settings.js') ?>"></script>

<script>
    // Atualizar menu ativo
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-item').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '/cardoxis/admin/settings') {
                link.classList.add('active');
            }
        });
    });
</script>
</body>
</html>