<?php
/**
 * CARDOXIS - Suporte ao Cliente RF
 */

// Verificar autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Suporte | CARDOXIS';

$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'user';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));
$isAdmin = in_array($userRole, ['admin', 'super_admin']);

// CONFIGURAÇÕES DA PÁGINA

// Canais de suporte
$supportChannels = [
    [
        'id' => 'email',
        'icon' => 'fa-envelope',
        'color' => '#2563EB',
        'title' => 'Email',
        'description' => 'Envie-nos um email para suporte@cardoxis.com',
        'action' => 'mailto:suporte@cardoxis.com',
        'action_text' => 'Enviar Email'
    ],
    [
        'id' => 'phone',
        'icon' => 'fa-phone',
        'color' => '#059669',
        'title' => 'Telefone',
        'description' => 'Ligue-nos durante o horário de expediente',
        'action' => 'tel:+351300123456',
        'action_text' => 'Ligar Agora'
    ],
    [
        'id' => 'chat',
        'icon' => 'fa-comment-dots',
        'color' => '#D97706',
        'title' => 'Chat Online',
        'description' => 'Converse connosco em tempo real',
        'action' => '#chat',
        'action_text' => 'Iniciar Chat'
    ],
    [
        'id' => 'knowledge',
        'icon' => 'fa-book',
        'color' => '#7C3AED',
        'title' => 'Base de Conhecimento',
        'description' => 'Consulte a nossa base de conhecimento',
        'action' => '/cardoxis/help',
        'action_text' => 'Ver Artigos'
    ]
];

// Tickets de suporte (mock - serão substituídos por dados da API)
$supportTickets = [
    [
        'id' => 'TICKET-001',
        'subject' => 'Problema com upload de documentos',
        'status' => 'resolved',
        'priority' => 'high',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'category' => 'documentos'
    ],
    [
        'id' => 'TICKET-002',
        'subject' => 'Dúvida sobre relatórios',
        'status' => 'in_progress',
        'priority' => 'medium',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'category' => 'relatorios'
    ],
    [
        'id' => 'TICKET-003',
        'subject' => 'Problema de acesso ao sistema',
        'status' => 'open',
        'priority' => 'critical',
        'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-3 hours')),
        'category' => 'acesso'
    ]
];

// Horário de funcionamento
$workingHours = [
    'Segunda a Sexta' => '09:00 - 18:00',
    'Sábado' => '09:00 - 13:00',
    'Domingo' => 'Fechado'
];

// Status do sistema (mock)
$systemStatus = [
    'api' => ['label' => 'API', 'status' => 'operational'],
    'database' => ['label' => 'Base de Dados', 'status' => 'operational'],
    'upload' => ['label' => 'Upload', 'status' => 'operational']
];

// Categorias de assunto
$subjectCategories = [
    'duvida' => 'Dúvida Geral',
    'problema' => 'Problema Técnico',
    'sugestao' => 'Sugestão',
    'reclamacao' => 'Reclamação',
    'outro' => 'Outro'
];

// Categorias de tickets
$ticketCategories = [
    'veiculos' => 'Veículos',
    'motoristas' => 'Motoristas',
    'combustivel' => 'Combustível',
    'documentos' => 'Documentos',
    'seguros' => 'Seguros',
    'multas' => 'Multas',
    'relatorios' => 'Relatórios',
    'conta' => 'Conta'
];

// Prioridades dos tickets
$ticketPriorities = [
    'critical' => ['label' => 'Crítico', 'color' => '#DE350B'],
    'high' => ['label' => 'Alta', 'color' => '#FF8B00'],
    'medium' => ['label' => 'Média', 'color' => '#6554C0'],
    'low' => ['label' => 'Baixa', 'color' => '#00875A']
];

// Status dos tickets
$ticketStatuses = [
    'open' => ['label' => 'Aberto', 'color' => '#DE350B'],
    'in_progress' => ['label' => 'Em Andamento', 'color' => '#FF8B00'],
    'resolved' => ['label' => 'Resolvido', 'color' => '#00875A'],
    'closed' => ['label' => 'Fechado', 'color' => '#6B778C']
];

// Cor do avatar do usuário
$avatarColors = ['#2563EB', '#059669', '#D97706', '#DC2626', '#7C3AED', '#0891B2', '#DB2777', '#0F172A'];
$avatarColor = $avatarColors[abs(crc32($userName)) % count($avatarColors)];

// Estatísticas de suporte
$supportStats = [
    'total_tickets' => count($supportTickets),
    'open_tickets' => count(array_filter($supportTickets, function($t) { return $t['status'] === 'open'; })),
    'in_progress_tickets' => count(array_filter($supportTickets, function($t) { return $t['status'] === 'in_progress'; })),
    'resolved_tickets' => count(array_filter($supportTickets, function($t) { return $t['status'] === 'resolved'; }))
];
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#2563EB">
    <meta name="description" content="Suporte ao Cliente CARDOXIS">
    <meta name="author" content="CARDOXIS Team">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= asset('img/logo/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/logo/apple-touch-icon.png') ?>">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= asset('css/Dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/Dashboard/support.css') ?>">
    
    <!-- Configurações -->
    <script>
        window.CARDOXIS = {
            API_URL: '<?= url('api/v1') ?>',
            CSRF_TOKEN: '<?= $_SESSION['csrf_token'] ?? '' ?>',
            USER: {
                id: <?= $_SESSION['user_id'] ?? 0 ?>,
                name: '<?= htmlspecialchars($userName) ?>',
                email: '<?= htmlspecialchars($userEmail) ?>',
                role: '<?= htmlspecialchars($userRole) ?>',
                initials: '<?= htmlspecialchars($userInitials) ?>',
                avatar_color: '<?= htmlspecialchars($avatarColor) ?>'
            },
            SUPPORT: {
                channels: <?= json_encode($supportChannels) ?>,
                tickets: <?= json_encode($supportTickets) ?>,
                workingHours: <?= json_encode($workingHours) ?>,
                systemStatus: <?= json_encode($systemStatus) ?>,
                subjectCategories: <?= json_encode($subjectCategories) ?>,
                ticketCategories: <?= json_encode($ticketCategories) ?>,
                ticketPriorities: <?= json_encode($ticketPriorities) ?>,
                ticketStatuses: <?= json_encode($ticketStatuses) ?>,
                stats: <?= json_encode($supportStats) ?>
            },
            IS_ADMIN: <?= $isAdmin ? 'true' : 'false' ?>
        };
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
    <div class="support-container">
        
        <!-- HEADER -->
        <div class="support-header-card">
            <div class="support-icon">
                <i class="fas fa-headset"></i>
            </div>
            <h1>Suporte ao Cliente</h1>
            <p>Estamos aqui para ajudar. Escolha o canal de contacto mais conveniente para si.</p>
        </div>
        
        <!-- STATS -->
        <div class="support-stats">
            <div class="support-stat">
                <span class="stat-number"><?= $supportStats['total_tickets'] ?></span>
                <span class="stat-label">Total de Tickets</span>
            </div>
            <div class="support-stat">
                <span class="stat-number" style="color: var(--support-danger);"><?= $supportStats['open_tickets'] ?></span>
                <span class="stat-label">Abertos</span>
            </div>
            <div class="support-stat">
                <span class="stat-number" style="color: var(--support-warning);"><?= $supportStats['in_progress_tickets'] ?></span>
                <span class="stat-label">Em Andamento</span>
            </div>
            <div class="support-stat">
                <span class="stat-number" style="color: var(--support-success);"><?= $supportStats['resolved_tickets'] ?></span>
                <span class="stat-label">Resolvidos</span>
            </div>
        </div>
        
        <!-- CHANNELS -->
        <div class="support-channels">
            <?php foreach ($supportChannels as $channel): ?>
                <div class="support-channel">
                    <div class="channel-icon" style="background: <?= $channel['color'] ?>;">
                        <i class="fas <?= $channel['icon'] ?>"></i>
                    </div>
                    <h3><?= htmlspecialchars($channel['title']) ?></h3>
                    <p><?= htmlspecialchars($channel['description']) ?></p>
                    <a href="<?= htmlspecialchars($channel['action']) ?>" class="btn-channel" data-channel="<?= $channel['id'] ?>">
                        <?= htmlspecialchars($channel['action_text']) ?>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- MAIN GRID -->
        <div class="support-grid">
            
            <!-- CONTACT FORM -->
            <div class="support-form-card">
                <div class="form-header">
                    <i class="fas fa-pen"></i>
                    <h2>Enviar Mensagem</h2>
                </div>
                <form class="support-form" id="supportForm" novalidate>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nome <span class="required">*</span></label>
                            <input type="text" id="supportName" value="<?= htmlspecialchars($userName) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" id="supportEmail" value="<?= htmlspecialchars($userEmail) ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Assunto <span class="required">*</span></label>
                            <select id="supportSubject" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($subjectCategories as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Categoria</label>
                            <select id="supportCategory">
                                <option value="">Selecione...</option>
                                <?php foreach ($ticketCategories as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Mensagem <span class="required">*</span></label>
                        <textarea id="supportMessage" placeholder="Descreva o seu problema ou dúvida em detalhe..." required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-undo"></i> Limpar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Enviar Mensagem
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- SIDEBAR -->
            <div class="support-sidebar">
                
                <!-- Working Hours -->
                <div class="info-card">
                    <h3><i class="fas fa-clock"></i> Horário de Funcionamento</h3>
                    <?php foreach ($workingHours as $day => $hours): ?>
                        <div class="info-item">
                            <span class="label"><?= htmlspecialchars($day) ?></span>
                            <span class="value"><?= htmlspecialchars($hours) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Contact Info -->
                <div class="info-card">
                    <h3><i class="fas fa-phone-alt"></i> Contactos</h3>
                    <div class="info-item">
                        <span class="label">Email</span>
                        <span class="value">suporte@cardoxis.com</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Telefone</span>
                        <span class="value">+351 300 123 456</span>
                    </div>
                    <div class="info-item">
                        <span class="label">WhatsApp</span>
                        <span class="value">+351 912 345 678</span>
                    </div>
                </div>
                
                <!-- System Status -->
                <div class="info-card">
                    <h3><i class="fas fa-info-circle"></i> Status do Sistema</h3>
                    <?php foreach ($systemStatus as $key => $status): ?>
                        <div class="info-item">
                            <span class="label"><?= htmlspecialchars($status['label']) ?></span>
                            <span class="value status-open" data-status="<?= $key ?>">
                                <i class="fas fa-check-circle"></i> Operacional
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <div class="info-item">
                        <span class="label">Última Atualização</span>
                        <span class="value"><?= date('d/m/Y H:i') ?></span>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="info-card quick-actions-card">
                    <h3><i class="fas fa-bolt"></i> Ações Rápidas</h3>
                    <a href="/cardoxis/help" class="quick-action-link">
                        <i class="fas fa-book"></i> Central de Ajuda
                    </a>
                    <a href="/cardoxis/faq" class="quick-action-link">
                        <i class="fas fa-question-circle"></i> Perguntas Frequentes
                    </a>
                    <a href="/cardoxis/docs" class="quick-action-link">
                        <i class="fas fa-file-alt"></i> Documentação
                    </a>
                </div>
                
            </div>
        </div>
        
        <!-- TICKETS -->
        <div class="support-tickets">
            <div class="tickets-header">
                <h2><i class="fas fa-ticket-alt"></i> Meus Tickets</h2>
                <span class="ticket-count"><?= count($supportTickets) ?> tickets</span>
            </div>
            <div class="ticket-list">
                <?php if (count($supportTickets) > 0): ?>
                    <?php foreach ($supportTickets as $ticket): ?>
                        <div class="ticket-item" onclick="viewTicket('<?= $ticket['id'] ?>')">
                            <div class="ticket-info">
                                <span class="ticket-id">#<?= htmlspecialchars($ticket['id']) ?></span>
                                <span class="ticket-subject"><?= htmlspecialchars($ticket['subject']) ?></span>
                                <span class="ticket-date">
                                    <i class="fas fa-clock"></i> 
                                    Criado: <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
                                </span>
                            </div>
                            <div class="ticket-meta">
                                <span class="ticket-priority <?= $ticket['priority'] ?>">
                                    <?= $ticketPriorities[$ticket['priority']]['label'] ?? $ticket['priority'] ?>
                                </span>
                                <span class="ticket-status <?= $ticket['status'] ?>">
                                    <?= $ticketStatuses[$ticket['status']]['label'] ?? $ticket['status'] ?>
                                </span>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ticket-empty">
                        <i class="fas fa-inbox"></i>
                        <p>Não tem tickets de suporte</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- FOOTER -->
        <div class="support-footer">
            <span>
                <i class="fas fa-copyright"></i> 2026 CARDOXIS. Todos os direitos reservados.
            </span>
            <span>
                <i class="fas fa-code-branch"></i> Versão 1.0.0
            </span>
        </div>
        
    </div>
</main>

<!-- TOAST CONTAINER -->
<div id="toastContainer" class="toast-container"></div>

<!-- SUPPORT JS -->
<script src="<?= asset('js/dashboard/support.js') ?>"></script>

</body>
</html>