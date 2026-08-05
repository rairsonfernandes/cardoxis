<?php
/**
 * CARDOXIS - Central de Ajuda RF
 */

// Verificar autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Central de Ajuda | CARDOXIS';

$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'user';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));
$isAdmin = in_array($userRole, ['admin', 'super_admin']);

// CONFIGURAÇÕES DA PÁGINA 

// Categorias de ajuda com artigos
$helpCategories = [
    [
        'id' => 'getting-started',
        'icon' => 'fa-rocket',
        'color' => '#2563EB',
        'title' => 'Primeiros Passos',
        'description' => 'Aprenda como configurar e começar a usar o CARDOXIS',
        'articles' => [
            ['title' => 'Bem-vindo ao CARDOXIS', 'url' => '/help/welcome', 'description' => 'Visão geral do sistema e primeiros passos'],
            ['title' => 'Como configurar a sua empresa', 'url' => '/help/company-setup', 'description' => 'Configurações iniciais da empresa'],
            ['title' => 'Adicionar o primeiro veículo', 'url' => '/help/add-vehicle', 'description' => 'Como registrar um novo veículo na frota'],
            ['title' => 'Adicionar o primeiro motorista', 'url' => '/help/add-driver', 'description' => 'Como cadastrar um novo motorista']
        ]
    ],
    [
        'id' => 'vehicles',
        'icon' => 'fa-truck',
        'color' => '#059669',
        'title' => 'Veículos',
        'description' => 'Tudo sobre gestão de veículos na sua frota',
        'articles' => [
            ['title' => 'Gerenciar veículos', 'url' => '/help/manage-vehicles', 'description' => 'Listar, editar e excluir veículos'],
            ['title' => 'Manutenções programadas', 'url' => '/help/maintenance', 'description' => 'Agendar e acompanhar manutenções'],
            ['title' => 'Documentos dos veículos', 'url' => '/help/vehicle-documents', 'description' => 'Gerenciar documentos do veículo'],
            ['title' => 'Seguros dos veículos', 'url' => '/help/vehicle-insurance', 'description' => 'Gestão de seguros e vencimentos']
        ]
    ],
    [
        'id' => 'drivers',
        'icon' => 'fa-users',
        'color' => '#D97706',
        'title' => 'Motoristas',
        'description' => 'Gerencie os motoristas da sua frota',
        'articles' => [
            ['title' => 'Adicionar motoristas', 'url' => '/help/add-drivers', 'description' => 'Cadastro de novos motoristas'],
            ['title' => 'Licenças e documentos', 'url' => '/help/driver-licenses', 'description' => 'Gestão de documentos dos motoristas'],
            ['title' => 'Atribuir motoristas a veículos', 'url' => '/help/assign-drivers', 'description' => 'Vinculação de motoristas aos veículos']
        ]
    ],
    [
        'id' => 'fuel',
        'icon' => 'fa-gas-pump',
        'color' => '#7C3AED',
        'title' => 'Combustível',
        'description' => 'Controle de abastecimentos e consumo',
        'articles' => [
            ['title' => 'Registar abastecimentos', 'url' => '/help/fuel-entries', 'description' => 'Como registrar um novo abastecimento'],
            ['title' => 'Relatórios de combustível', 'url' => '/help/fuel-reports', 'description' => 'Gerar relatórios de consumo'],
            ['title' => 'Análise de consumo', 'url' => '/help/fuel-analysis', 'description' => 'Analisar eficiência de combustível']
        ]
    ],
    [
        'id' => 'documents',
        'icon' => 'fa-file-alt',
        'color' => '#0891B2',
        'title' => 'Documentos',
        'description' => 'Gestão centralizada de documentos',
        'articles' => [
            ['title' => 'Upload de documentos', 'url' => '/help/upload-documents', 'description' => 'Como enviar e armazenar documentos'],
            ['title' => 'Categorias de documentos', 'url' => '/help/document-categories', 'description' => 'Organizar documentos por categoria'],
            ['title' => 'Alertas de vencimento', 'url' => '/help/document-alerts', 'description' => 'Configurar alertas para documentos']
        ]
    ],
    [
        'id' => 'reports',
        'icon' => 'fa-chart-bar',
        'color' => '#DC2626',
        'title' => 'Relatórios',
        'description' => 'Análises e relatórios da sua frota',
        'articles' => [
            ['title' => 'Gerar relatórios', 'url' => '/help/generate-reports', 'description' => 'Como gerar relatórios personalizados'],
            ['title' => 'Exportar dados', 'url' => '/help/export-data', 'description' => 'Exportar dados em diferentes formatos'],
            ['title' => 'Dashboards e análises', 'url' => '/help/dashboards', 'description' => 'Análises visuais da frota']
        ]
    ]
];

// Categorias de FAQ com cores
$faqCategories = [
    'vehicles' => ['label' => 'Veículos', 'color' => '#059669', 'icon' => 'fa-truck'],
    'fuel' => ['label' => 'Combustível', 'color' => '#7C3AED', 'icon' => 'fa-gas-pump'],
    'reports' => ['label' => 'Relatórios', 'color' => '#DC2626', 'icon' => 'fa-chart-bar'],
    'system' => ['label' => 'Sistema', 'color' => '#0891B2', 'icon' => 'fa-server'],
    'account' => ['label' => 'Conta', 'color' => '#2563EB', 'icon' => 'fa-user-cog'],
    'drivers' => ['label' => 'Motoristas', 'color' => '#D97706', 'icon' => 'fa-users'],
    'documents' => ['label' => 'Documentos', 'color' => '#0891B2', 'icon' => 'fa-file-alt']
];

// FAQ
$faqs = [
    [
        'id' => 1,
        'question' => 'Como adicionar um novo veículo?',
        'answer' => 'Aceda ao menu "Veículos" e clique em "Novo Veículo". Preencha os dados do veículo (marca, modelo, matrícula, etc.) e clique em "Salvar". O veículo será adicionado à sua frota imediatamente.',
        'category' => 'vehicles'
    ],
    [
        'id' => 2,
        'question' => 'Como registrar um abastecimento?',
        'answer' => 'Aceda ao menu "Combustível" e clique em "Novo Abastecimento". Selecione o veículo, preencha a data, litros, preço por litro e odômetro. O sistema calculará automaticamente o consumo médio.',
        'category' => 'fuel'
    ],
    [
        'id' => 3,
        'question' => 'Como gerar relatórios da frota?',
        'answer' => 'Aceda ao menu "Relatórios", selecione o tipo de relatório desejado (veículos, motoristas, combustível, etc.), configure os filtros de data e clique em "Gerar Relatório". Você pode exportar em PDF ou Excel.',
        'category' => 'reports'
    ],
    [
        'id' => 4,
        'question' => 'Como funciona o sistema de alertas?',
        'answer' => 'O sistema gera alertas automáticos para documentos a vencer, manutenções programadas, seguros a expirar e multas pendentes. Os alertas são exibidos no dashboard e na página de alertas com diferentes níveis de prioridade.',
        'category' => 'system'
    ],
    [
        'id' => 5,
        'question' => 'Como alterar a palavra-passe?',
        'answer' => 'Aceda ao seu "Perfil" > "Segurança". Insira a palavra-passe atual, a nova palavra-passe e confirme. Clique em "Salvar Alterações" para atualizar sua senha com segurança.',
        'category' => 'account'
    ],
    [
        'id' => 6,
        'question' => 'O que fazer se esquecer a palavra-passe?',
        'answer' => 'Na página de login, clique em "Esqueceu a palavra-passe?". Siga as instruções para redefinir a sua palavra-passe através do email. Você receberá um link seguro para criar uma nova senha.',
        'category' => 'account'
    ],
    [
        'id' => 7,
        'question' => 'Como atribuir um motorista a um veículo?',
        'answer' => 'Aceda ao menu "Motoristas", selecione o motorista desejado e clique em "Atribuir Veículo". Escolha o veículo na lista e confirme. O motorista será vinculado ao veículo selecionado.',
        'category' => 'drivers'
    ],
    [
        'id' => 8,
        'question' => 'Como gerenciar documentos dos veículos?',
        'answer' => 'Aceda ao menu "Documentos" > "Veículos". Selecione o veículo e faça upload dos documentos (seguro, inspeção, licenciamento, etc.). O sistema enviará alertas de vencimento automaticamente.',
        'category' => 'documents'
    ]
];

// Dicas rápidas com ícones reais do Font Awesome
$quickTips = [
    [
        'icon' => 'fa-filter',
        'text' => 'Utilize os filtros para encontrar rapidamente os veículos que procura.'
    ],
    [
        'icon' => 'fa-calendar-check',
        'text' => 'Configure lembretes para manutenções e vencimentos de documentos.'
    ],
    [
        'icon' => 'fa-chart-line',
        'text' => 'Acompanhe os relatórios mensais para otimizar a gestão da frota.'
    ],
    [
        'icon' => 'fa-bell',
        'text' => 'Mantenha as notificações ativas para não perder prazos importantes.'
    ],
    [
        'icon' => 'fa-mobile-alt',
        'text' => 'Aceda ao CARDOXIS em qualquer dispositivo com navegador.'
    ],
    [
        'icon' => 'fa-sync-alt',
        'text' => 'Atualize regularmente os dados dos veículos e motoristas.'
    ]
];

// Estatísticas de ajuda
$helpStats = [
    'total_articles' => array_sum(array_map(function($cat) { return count($cat['articles']); }, $helpCategories)),
    'total_categories' => count($helpCategories),
    'total_faqs' => count($faqs)
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
    <meta name="theme-color" content="#2563EB">
    <meta name="description" content="Central de Ajuda CARDOXIS - Suporte e documentação">
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
    <link rel="stylesheet" href="<?= asset('css/Dashboard/help.css') ?>">
    
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
            HELP: {
                categories: <?= json_encode($helpCategories) ?>,
                faqs: <?= json_encode($faqs) ?>,
                quickTips: <?= json_encode($quickTips) ?>,
                stats: <?= json_encode($helpStats) ?>
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
    <div class="help-container">
        
        <!-- HEADER -->
        <div class="help-header-card">
            <div class="help-icon-wrapper">
                <div class="help-icon">
                    <i class="fas fa-life-ring"></i>
                </div>
            </div>
            <h1>Central de Ajuda</h1>
            <p class="help-description">
                Encontre respostas para as suas dúvidas e saiba como tirar o máximo proveito do CARDOXIS
            </p>
            <div class="help-search">
                <i class="fas fa-search"></i>
                <input 
                    type="text" 
                    id="helpSearch" 
                    placeholder="Pesquisar na ajuda..."
                    autocomplete="off"
                >
                <button class="help-search-clear" id="searchClear" style="display:none;">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
            <div class="help-search-results" id="searchResults"></div>
        </div>
        
        <!-- STATS -->
        <div class="help-stats">
            <div class="help-stat">
                <span class="stat-number"><?= $helpStats['total_articles'] ?></span>
                <span class="stat-label">Artigos</span>
            </div>
            <div class="help-stat">
                <span class="stat-number"><?= $helpStats['total_categories'] ?></span>
                <span class="stat-label">Categorias</span>
            </div>
            <div class="help-stat">
                <span class="stat-number"><?= $helpStats['total_faqs'] ?></span>
                <span class="stat-label">Perguntas Frequentes</span>
            </div>
            <div class="help-stat">
                <span class="stat-number">24/7</span>
                <span class="stat-label">Suporte</span>
            </div>
        </div>
        
        <!-- QUICK TIPS - COM ÍCONES REAIS -->
        <div class="quick-tips" id="quickTips">
            <?php foreach ($quickTips as $tip): ?>
                <div class="quick-tip">
                    <div class="quick-tip-icon">
                        <i class="fas <?= $tip['icon'] ?>"></i>
                    </div>
                    <span><?= htmlspecialchars($tip['text']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- CATEGORIES -->
        <div class="help-categories" id="helpCategories">
            <?php foreach ($helpCategories as $category): ?>
                <div class="help-category" data-category="<?= $category['id'] ?>">
                    <div class="category-icon" style="background: <?= $category['color'] ?>;">
                        <i class="fas <?= $category['icon'] ?>"></i>
                    </div>
                    <h3><?= htmlspecialchars($category['title']) ?></h3>
                    <p><?= htmlspecialchars($category['description']) ?></p>
                    <ul class="article-list">
                        <?php foreach ($category['articles'] as $article): ?>
                            <li>
                                <a href="<?= htmlspecialchars($article['url']) ?>" data-article="<?= htmlspecialchars($article['title']) ?>">
                                    <i class="fas fa-chevron-right"></i>
                                    <span><?= htmlspecialchars($article['title']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="category-articles-count">
                        <i class="fas fa-file-alt"></i>
                        <?= count($category['articles']) ?> artigos
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- FAQ -->
        <div class="help-faq" id="helpFaq">
            <div class="help-faq-header">
                <i class="fas fa-question-circle"></i>
                <h2>Perguntas Frequentes</h2>
                <span class="faq-count"><?= count($faqs) ?> perguntas</span>
            </div>
            <div class="faq-filters" id="faqFilters">
                <button class="faq-filter active" data-filter="all">
                    <i class="fas fa-list"></i> Todas
                </button>
                <?php foreach ($faqCategories as $key => $cat): ?>
                    <button class="faq-filter" data-filter="<?= $key ?>" style="--faq-color: <?= $cat['color'] ?>">
                        <i class="fas <?= $cat['icon'] ?>"></i>
                        <?= $cat['label'] ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div id="faqList">
                <?php foreach ($faqs as $index => $faq): ?>
                    <div class="faq-item <?= $index === 0 ? 'active' : '' ?>" data-category="<?= $faq['category'] ?>">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span>
                                <?= htmlspecialchars($faq['question']) ?>
                                <span class="faq-category" style="background: <?= $faqCategories[$faq['category']]['color'] ?? '#6B778C' ?>20; color: <?= $faqCategories[$faq['category']]['color'] ?? '#6B778C' ?>;">
                                    <i class="fas <?= $faqCategories[$faq['category']]['icon'] ?? 'fa-tag' ?>"></i>
                                    <?= $faqCategories[$faq['category']]['label'] ?? $faq['category'] ?>
                                </span>
                            </span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <?= htmlspecialchars($faq['answer']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- SUPPORT -->
        <div class="help-support">
            <div class="support-icon">
                <i class="fas fa-headset"></i>
            </div>
            <h3>Ainda precisa de ajuda?</h3>
            <p>
                Nossa equipe de suporte está disponível para ajudá-lo com qualquer dúvida ou problema.
                Estamos aqui para garantir que você tenha a melhor experiência possível.
            </p>
            <div class="support-actions">
                <a href="/cardoxis/contact" class="btn-support primary">
                    <i class="fas fa-envelope"></i> Contactar Suporte
                </a>
                <a href="/cardoxis/docs" class="btn-support secondary">
                    <i class="fas fa-book"></i> Ver Documentação
                </a>
            </div>
        </div>
        
        <!-- FOOTER -->
        <div class="help-footer">
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

<!-- HELP JS -->
<script src="<?= asset('js/dashboard/help.js') ?>"></script>

</body>
</html>