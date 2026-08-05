<?php
/**
 * CARDOXIS - Chat Inteligente  RF
 */

if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Chat Inteligente | CARDOXIS';

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
    <meta name="description" content="Chat Inteligente CARDOXIS">
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
    <link rel="stylesheet" href="<?= asset('css/dashboard/chat.css') ?>">

    
    <script>
        window.userName = '<?= htmlspecialchars($userName) ?>';
        window.userEmail = '<?= htmlspecialchars($userEmail) ?>';
        window.userRole = '<?= htmlspecialchars($userRole) ?>';
        window.userInitials = '<?= htmlspecialchars($userInitials) ?>';
        window.avatarColor = '<?= htmlspecialchars($avatarColor) ?>';
        window.isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
        window.API_URL = '<?= url('api/v1') ?>';
    </script>
</head>
<body>

<!-- MOBILE HEADER -->
<div class="mobile-header">
    <button class="menu-hamburger" id="menuToggle"><i class="fas fa-bars"></i></button>
    <div class="mobile-logo">
        <div class="mobile-logo-icon"><i class="fas fa-route" style="color:white;font-size:1.2rem;"></i></div>
        <span class="mobile-logo-text">CARDO<span>XIS</span></span>
    </div>
    <button class="btn-icon-refresh" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
</div>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<?php include_once ROOT_PATH . '/resources/views/layouts/partials/sidebar.php'; ?>

<!-- MAIN CONTENT -->
<main class="main-content">
    <div class="chat-container">
        
        <!-- Header -->
        <div class="chat-header-card">
            <div class="chat-header-top">
                <div class="chat-header-title">
                    <i class="fas fa-robot"></i>
                    Assistente Inteligente
                    <span class="badge" style="font-size:0.6rem;background:var(--chat-primary-light);color:var(--chat-primary);padding:2px 12px;border-radius:12px;font-weight:600;">AI</span>
                </div>
                <div class="chat-status">
                    <span class="status-dot online" id="statusDot"></span>
                    <span id="statusText">Online</span>
                    <span style="margin:0 8px;">•</span>
                    <span id="modelInfo">GPT-4o</span>
                </div>
            </div>
            <div class="chat-header-bottom">
                <div class="chat-welcome">
                    <h2>Olá, <span class="highlight"><?= htmlspecialchars($userName) ?></span> <span style="display:inline-block;animation:wave 2.5s infinite;">👋</span></h2>
                </div>
                <div class="chat-actions">
                    <button class="btn btn-secondary btn-sm" id="clearHistory">
                        <i class="fas fa-trash-alt"></i> Limpar Histórico
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Chat Main -->
        <div class="chat-main">
            
            <!-- Messages -->
            <div class="chat-messages-card">
                <div class="chat-messages-header">
                    <h3>
                        <i class="fas fa-comment-dots" style="color:var(--chat-primary);"></i>
                        Conversa
                        <span class="badge" id="messageCount">0</span>
                    </h3>
                    <span style="font-size:0.7rem;color:var(--chat-text-muted);">
                        <i class="fas fa-clock"></i>
                        <span id="lastUpdateTime">-</span>
                    </span>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <!-- Mensagem de boas-vindas -->
                    <div class="message assistant">
                        <div class="message-avatar assistant">
                            <i class="fas fa-robot" style="font-size:0.9rem;"></i>
                        </div>
                        <div>
                            <div class="message-content">
                                Olá, <strong><?= htmlspecialchars($userName) ?></strong>! 👋<br><br>
                                Sou o assistente do <strong>CARDOXIS</strong>. Estou aqui para ajudar com:
                                <br><br>
                                • 🚗 Gestão de veículos<br>
                                • 👤 Motoristas<br>
                                • ⛽ Combustível<br>
                                • 📄 Documentos<br>
                                • 🔧 Manutenções<br>
                                • 💰 Multas<br>
                                • 🛡️ Seguros<br>
                                • 📊 Relatórios<br>
                                • 🔐 Segurança<br><br>
                                <strong>Como posso ajudá-lo hoje?</strong>
                            </div>
                            <span class="message-time">Agora</span>
                        </div>
                    </div>
                </div>
                
                <!-- Typing Indicator -->
                <div class="typing-indicator" id="typingIndicator">
                    <div class="message-avatar assistant">
                        <i class="fas fa-robot" style="font-size:0.9rem;"></i>
                    </div>
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <button class="quick-action-btn" data-question="Como adicionar um veículo?">🚗 Novo Veículo</button>
                    <button class="quick-action-btn" data-question="Como adicionar um motorista?">👤 Novo Motorista</button>
                    <button class="quick-action-btn" data-question="Como registrar um abastecimento?">⛽ Abastecimento</button>
                    <button class="quick-action-btn" data-question="Como gerar relatórios?">📊 Relatórios</button>
                    <button class="quick-action-btn" data-question="Como alterar a senha?">🔐 Senha</button>
                </div>
                
                <!-- Input -->
                <div class="chat-input-area">
                    <textarea id="chatInput" rows="1" placeholder="Digite sua pergunta..." onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}"></textarea>
                    <button class="btn-send" id="sendBtn" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                        Enviar
                    </button>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="chat-sidebar">
                <!-- Sugestões -->
                <div class="chat-sidebar-card">
                    <h4><i class="fas fa-lightbulb"></i> Perguntas Sugeridas</h4>
                    <div class="suggestion-item" onclick="askSuggestion('Como adicionar um veículo?')">
                        🚗 Como adicionar um veículo?
                    </div>
                    <div class="suggestion-item" onclick="askSuggestion('Como adicionar um motorista?')">
                        👤 Como adicionar um motorista?
                    </div>
                    <div class="suggestion-item" onclick="askSuggestion('Como registrar um abastecimento?')">
                        ⛽ Como registrar um abastecimento?
                    </div>
                    <div class="suggestion-item" onclick="askSuggestion('Como gerar relatórios?')">
                        📊 Como gerar relatórios?
                    </div>
                    <div class="suggestion-item" onclick="askSuggestion('Como funciona a gestão de documentos?')">
                        📄 Como funciona a gestão de documentos?
                    </div>
                    <div class="suggestion-item" onclick="askSuggestion('Como alterar a senha?')">
                        🔐 Como alterar a senha?
                    </div>
                </div>
                
                <!-- Estatísticas -->
                <div class="chat-sidebar-card">
                    <h4><i class="fas fa-chart-bar"></i> Estatísticas</h4>
                    <div class="chat-stats">
                        <div class="chat-stat-item">
                            <div class="number" id="statQuestions">0</div>
                            <div class="label">Perguntas</div>
                        </div>
                        <div class="chat-stat-item">
                            <div class="number" id="statSessions">1</div>
                            <div class="label">Sessões</div>
                        </div>
                    </div>
                </div>
                
                <!-- Dicas -->
                <div class="chat-sidebar-card">
                    <h4><i class="fas fa-info-circle"></i> Dicas</h4>
                    <div style="font-size:0.8rem;color:var(--chat-text-secondary);line-height:1.6;">
                        💡 Seja específico na pergunta<br>
                        🔍 Use palavras-chave do sistema<br>
                        📝 Pode fazer perguntas seguidas<br>
                        ⚡ Respostas em tempo real
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>
</main>

<!-- TOAST CONTAINER -->
<div id="toastContainer" class="toast-container"></div>

<!-- CHAT JS -->
<script src="<?= asset('js/dashboard/chat.js') ?>"></script>

</body>
</html>