<?php
/**
 * CARDOXIS - Novidades / Changelog  RF
 */

// Verificar autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Novidades | CARDOXIS';

$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'user';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));
$isAdmin = in_array($userRole, ['admin', 'super_admin']);

// DADOS DE NOVIDADES (Changelog)

$changelog = [
    [
        'version' => '2.0.0',
        'date' => '2025-06-30',
        'title' => 'Chat Inteligente com IA',
        'type' => 'feature',
        'description' => 'O CARDOXIS agora conta com um assistente virtual inteligente para ajudar na gestão da sua frota.',
        'details' => [
            'Assistente virtual 24/7 para dúvidas sobre o sistema',
            'Respostas inteligentes baseadas em IA',
            'Integração com a base de conhecimento do CARDOXIS',
            'Histórico de conversas salvo automaticamente',
            'Sugestões de perguntas frequentes'
        ]
    ],
    [
        'version' => '1.9.0',
        'date' => '2025-06-15',
        'title' => 'Central de Ajuda Reformulada',
        'type' => 'improvement',
        'description' => 'Nova Central de Ajuda com interface moderna e conteúdo otimizado para melhor experiência do usuário.',
        'details' => [
            'Design totalmente redesenhado',
            'Busca inteligente por artigos',
            'Categorias organizadas por tema',
            'FAQ interativo com filtros',
            'Dicas rápidas na página inicial'
        ]
    ],
    [
        'version' => '1.8.0',
        'date' => '2025-06-01',
        'title' => 'Notificações em Tempo Real',
        'type' => 'feature',
        'description' => 'Sistema de notificações em tempo real para manter você sempre informado sobre sua frota.',
        'details' => [
            'Alertas de manutenção programada',
            'Vencimento de documentos e seguros',
            'Multas e infrações registadas',
            'Novos abastecimentos registados',
            'Notificações personalizáveis por tipo'
        ]
    ],
    [
        'version' => '1.7.0',
        'date' => '2025-05-20',
        'title' => 'Dashboard Executivo',
        'type' => 'improvement',
        'description' => 'Novo dashboard com métricas avançadas e visualizações para melhor tomada de decisão.',
        'details' => [
            'Gráficos interativos de desempenho',
            'Indicadores de custo por veículo',
            'Análise de consumo de combustível',
            'Ranking de eficiência da frota',
            'Exportação de relatórios em PDF'
        ]
    ],
    [
        'version' => '1.6.0',
        'date' => '2025-05-10',
        'title' => 'Gestão de Motoristas Avançada',
        'type' => 'feature',
        'description' => 'Novas funcionalidades para gestão completa de motoristas e suas atividades.',
        'details' => [
            'Histórico de atribuições por veículo',
            'Gestão de licenças e habilitações',
            'Alertas de vencimento de documentos',
            'Relatório de desempenho por motorista',
            'Interface otimizada para mobile'
        ]
    ],
    [
        'version' => '1.5.0',
        'date' => '2025-04-25',
        'title' => 'Suporte Multicanal',
        'type' => 'feature',
        'description' => 'Novo sistema de suporte com múltiplos canais de atendimento para melhor assistência.',
        'details' => [
            'Chat online integrado',
            'Sistema de tickets organizado',
            'Base de conhecimento centralizada',
            'FAQ interativo',
            'Tempo médio de resposta reduzido'
        ]
    ],
    [
        'version' => '1.4.0',
        'date' => '2025-04-10',
        'title' => 'Otimização de Performance',
        'type' => 'improvement',
        'description' => 'Melhorias significativas na performance e velocidade do sistema.',
        'details' => [
            'Tempo de carregamento reduzido em 40%',
            'Cache inteligente implementado',
            'Otimização de consultas ao banco de dados',
            'Lazy loading de imagens',
            'Compressão de assets'
        ]
    ],
    [
        'version' => '1.3.0',
        'date' => '2025-03-28',
        'title' => 'Relatórios Avançados',
        'type' => 'feature',
        'description' => 'Novos relatórios com visualizações avançadas para análise da frota.',
        'details' => [
            'Relatório de custos por veículo',
            'Análise de consumo por período',
            'Comparativo de desempenho',
            'Exportação em múltiplos formatos',
            'Dashboards personalizáveis'
        ]
    ],
    [
        'version' => '1.2.0',
        'date' => '2025-03-15',
        'title' => 'App Mobile Responsivo',
        'type' => 'improvement',
        'description' => 'Interface totalmente responsiva para acesso em qualquer dispositivo.',
        'details' => [
            'Design adaptado para tablets e smartphones',
            'Navegação otimizada para touch',
            'Menos dados móveis utilizados',
            'Experiência consistente em todos os dispositivos',
            'Carregamento progressivo'
        ]
    ],
    [
        'version' => '1.1.0',
        'date' => '2025-03-01',
        'title' => 'Segurança Aprimorada',
        'type' => 'improvement',
        'description' => 'Novas camadas de segurança para proteger seus dados e informações.',
        'details' => [
            'Autenticação em dois fatores',
            'Criptografia de dados sensíveis',
            'Monitoramento de acessos suspeitos',
            'Políticas de senha mais rigorosas',
            'Logs de auditoria detalhados'
        ]
    ],
    [
        'version' => '1.0.0',
        'date' => '2025-02-15',
        'title' => 'Lançamento Oficial',
        'type' => 'feature',
        'description' => 'Lançamento oficial do CARDOXIS - Sistema de Gestão de Frotas.',
        'details' => [
            'Gestão completa de veículos',
            'Cadastro de motoristas',
            'Controle de abastecimentos',
            'Gestão de documentos',
            'Sistema de alertas e notificações',
            'Dashboard com métricas principais'
        ]
    ]
];

// Agrupar por tipo para filtros
$types = array_unique(array_column($changelog, 'type'));
$typeLabels = [
    'feature' => 'Nova Funcionalidade',
    'improvement' => 'Melhoria',
    'bugfix' => 'Correção de Bug',
    'security' => 'Segurança'
];

$typeColors = [
    'feature' => '#0052CC',
    'improvement' => '#36B37E',
    'bugfix' => '#DE350B',
    'security' => '#6554C0'
];

$typeIcons = [
    'feature' => 'fa-rocket',
    'improvement' => 'fa-chart-line',
    'bugfix' => 'fa-bug',
    'security' => 'fa-shield-alt'
];

// Estatísticas
$stats = [
    'total' => count($changelog),
    'features' => count(array_filter($changelog, function($item) { return $item['type'] === 'feature'; })),
    'improvements' => count(array_filter($changelog, function($item) { return $item['type'] === 'improvement'; })),
    'latest_version' => $changelog[0]['version'] ?? '1.0.0'
];

// Cor do avatar do usuário
$avatarColors = ['#2563EB', '#059669', '#D97706', '#DC2626', '#7C3AED', '#0891B2', '#DB2777', '#0F172A'];
$avatarColor = $avatarColors[abs(crc32($userName)) % count($avatarColors)];
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0052CC">
    <meta name="description" content="Novidades e atualizações do CARDOXIS">
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
    <link rel="stylesheet" href="<?= asset('css/Dashboard/changelog.css') ?>">
    
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
            CHANGELOG: {
                items: <?= json_encode($changelog) ?>,
                stats: <?= json_encode($stats) ?>,
                types: <?= json_encode($types) ?>
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
    <div class="changelog-container">
        
        <!-- HEADER -->
        <div class="changelog-header-card">
            <div class="changelog-icon">
                <i class="fas fa-rocket"></i>
            </div>
            <h1>Novidades</h1>
            <p>Acompanhe todas as novidades, melhorias e atualizações do CARDOXIS</p>
            <div class="changelog-version-badge">
                <i class="fas fa-tag"></i>
                Versão atual: <strong><?= $stats['latest_version'] ?></strong>
            </div>
        </div>
        
        <!-- STATS -->
        <div class="changelog-stats">
            <div class="stat-card">
                <span class="stat-number"><?= $stats['total'] ?></span>
                <span class="stat-label">Total de Atualizações</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" style="color: #0052CC;"><?= $stats['features'] ?></span>
                <span class="stat-label">Novas Funcionalidades</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" style="color: #36B37E;"><?= $stats['improvements'] ?></span>
                <span class="stat-label">Melhorias</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" style="color: #6554C0;"><?= $stats['total'] - $stats['features'] - $stats['improvements'] ?></span>
                <span class="stat-label">Outras Atualizações</span>
            </div>
        </div>
        
        <!-- FILTERS -->
        <div class="changelog-filters" id="changelogFilters">
            <button class="filter-btn active" data-filter="all">
                <i class="fas fa-list"></i> Todas
            </button>
            <?php foreach ($types as $type): ?>
                <button class="filter-btn" data-filter="<?= $type ?>">
                    <i class="fas <?= $typeIcons[$type] ?? 'fa-tag' ?>"></i>
                    <?= $typeLabels[$type] ?? $type ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- CHANGELOG LIST -->
        <div class="changelog-timeline" id="changelogList">
            <?php foreach ($changelog as $index => $item): ?>
                <div class="changelog-item" data-type="<?= $item['type'] ?>">
                    <div class="changelog-item-header">
                        <div class="changelog-version">
                            <span class="version-tag">v<?= $item['version'] ?></span>
                            <span class="version-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?= date('d/m/Y', strtotime($item['date'])) ?>
                            </span>
                        </div>
                        <div class="changelog-type-badge" style="background: <?= $typeColors[$item['type']] ?? '#6B778C' ?>;">
                            <i class="fas <?= $typeIcons[$item['type']] ?? 'fa-tag' ?>"></i>
                            <?= $typeLabels[$item['type']] ?? $item['type'] ?>
                        </div>
                    </div>
                    <h3><?= htmlspecialchars($item['title']) ?></h3>
                    <p class="changelog-description"><?= htmlspecialchars($item['description']) ?></p>
                    <ul class="changelog-details">
                        <?php foreach ($item['details'] as $detail): ?>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span><?= htmlspecialchars($detail) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- FOOTER -->
        <div class="changelog-footer">
            <span>
                <i class="fas fa-copyright"></i> 2026 CARDOXIS. Todos os direitos reservados.
            </span>
            <span>
                <i class="fas fa-code-branch"></i> Versão <?= $stats['latest_version'] ?>
            </span>
        </div>
        
    </div>
</main>

<!-- TOAST CONTAINER -->
<div id="toastContainer" class="toast-container"></div>

<!-- CHANGELOG JS -->
<script src="<?= asset('js/dashboard/changelog.js') ?>"></script>

</body>
</html>