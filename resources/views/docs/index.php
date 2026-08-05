<?php
/**
 * CARDOXIS - Página de Documentação RF
 */

// Carregar configurações
require_once __DIR__ . '/../layouts/config.php';
require_once __DIR__ . '/../layouts/helpers.php';


// CONFIGURAÇÕES DA PÁGINA 

$pageTitle = "Documentação - CARDOXIS | Guia Completo da Plataforma";
$pageDescription = "Documentação completa da plataforma CARDOXIS. Guias, tutoriais e referência para gestão de frotas com IA.";
$lastUpdated = "15 de Janeiro de 2024";
$currentVersion = "v5.2.0";


// ARTIGOS DA DOCUMENTAÇÃO


$articles = [
    [
        'id' => 'getting-started',
        'title' => 'Primeiros Passos',
        'description' => 'Guia introdutório para começar a usar a plataforma CARDOXIS',
        'icon' => 'fa-rocket',
        'category' => 'Iniciar',
        'slug' => 'getting-started',
        'content' => 'getting-started'
    ],
    [
        'id' => 'fleet-management',
        'title' => 'Gestão de Frotas',
        'description' => 'Gerencie veículos, motoristas e manutenções',
        'icon' => 'fa-truck',
        'category' => 'Gestão',
        'slug' => 'fleet-management',
        'content' => 'fleet-management'
    ],
    [
        'id' => 'analytics-dashboard',
        'title' => 'Análises e Dashboards',
        'description' => 'Visualize dados e métricas da sua frota',
        'icon' => 'fa-chart-line',
        'category' => 'Análises',
        'slug' => 'analytics-dashboard',
        'content' => 'analytics-dashboard'
    ],
    [
        'id' => 'api-integration',
        'title' => 'API e Integrações',
        'description' => 'Integre a CARDOXIS com os seus sistemas existentes',
        'icon' => 'fa-code',
        'category' => 'Desenvolvedores',
        'slug' => 'api-integration',
        'content' => 'api-integration'
    ],
    [
        'id' => 'security-compliance',
        'title' => 'Segurança e Conformidade',
        'description' => 'Práticas de segurança e conformidade com RGPD',
        'icon' => 'fa-shield-alt',
        'category' => 'Segurança',
        'slug' => 'security-compliance',
        'content' => 'security-compliance'
    ],
    [
        'id' => 'mobile-app',
        'title' => 'Aplicativo Móvel',
        'description' => 'Gerencie a sua frota em qualquer lugar com o nosso app',
        'icon' => 'fa-mobile-alt',
        'category' => 'Móvel',
        'slug' => 'mobile-app',
        'content' => 'mobile-app'
    ],
    [
        'id' => 'faq',
        'title' => 'Perguntas Frequentes',
        'description' => 'Respostas para as dúvidas mais comuns',
        'icon' => 'fa-question-circle',
        'category' => 'Suporte',
        'slug' => 'faq',
        'content' => 'faq'
    ],
    [
        'id' => 'troubleshooting',
        'title' => 'Resolução de Problemas',
        'description' => 'Guias para solucionar problemas comuns',
        'icon' => 'fa-wrench',
        'category' => 'Suporte',
        'slug' => 'troubleshooting',
        'content' => 'troubleshooting'
    ],
    [
        'id' => 'release-notes',
        'title' => 'Notas de Lançamento',
        'description' => 'Histórico de versões e novidades da plataforma',
        'icon' => 'fa-tag',
        'category' => 'Atualizações',
        'slug' => 'release-notes',
        'content' => 'release-notes'
    ],
    [
        'id' => 'best-practices',
        'title' => 'Boas Práticas',
        'description' => 'Recomendações para otimizar o uso da plataforma',
        'icon' => 'fa-star',
        'category' => 'Dicas',
        'slug' => 'best-practices',
        'content' => 'best-practices'
    ]
];


// CATEGORIAS PARA NAVEGAÇÃO


$categories = [
    'Iniciar' => 'fa-rocket',
    'Gestão' => 'fa-truck',
    'Análises' => 'fa-chart-line',
    'Desenvolvedores' => 'fa-code',
    'Segurança' => 'fa-shield-alt',
    'Móvel' => 'fa-mobile-alt',
    'Suporte' => 'fa-headset',
    'Atualizações' => 'fa-tag',
    'Dicas' => 'fa-lightbulb'
];

// ARTIGO ATUAL (via GET)

$currentSlug = $_GET['article'] ?? 'getting-started';
$currentArticle = null;

foreach ($articles as $article) {
    if ($article['slug'] === $currentSlug) {
        $currentArticle = $article;
        break;
    }
}

if (!$currentArticle) {
    $currentArticle = $articles[0];
    $currentSlug = $currentArticle['slug'];
}

// BREADCRUMB

$breadcrumb = [
    ['label' => 'Documentação', 'url' => url('docs')],
    ['label' => $currentArticle['title'], 'url' => '#' . $currentSlug]
];

// ÍNDICE DO ARTIGO (TOC)

$tocItems = [
    ['id' => 'introduction', 'label' => 'Introdução'],
    ['id' => 'what-is-cardoxis', 'label' => 'O que é a CARDOXIS?'],
    ['id' => 'system-requirements', 'label' => 'Requisitos do Sistema'],
    ['id' => 'quick-start', 'label' => 'Guia Rápido de Início'],
    ['id' => 'next-steps', 'label' => 'Próximos Passos']
];
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0052CC">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="author" content="CARDOXIS">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= url('docs') ?>">
    <meta property="og:image" content="<?= asset('img/landing/og-image.jpg') ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Favicons -->
    <link rel="icon" type="image/png" href="<?= asset('img/logo/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/logo/apple-touch-icon.png') ?>">
    
    <!-- Pré-conexão para performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fontes & Bibliotecas -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= asset('css/landing/landing.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/landing/docs.css') ?>">
</head>
<body>

<!-- NAVBAR -->
<?php include_once __DIR__ . '/../layouts/partials/navbar.php'; ?>

<!-- CONTEÚDO PRINCIPAL -->
<main>

    <!-- SECÇÃO HERO -->
    <section class="docs-hero" id="docs-hero">
        <div class="container">
            <div class="docs-hero-content" data-aos="fade-up">
                <div class="docs-hero-badge">
                    <i class="fas fa-book-open"></i> Documentação Oficial
                </div>
                <h1 class="docs-hero-title">
                    Documentação <br>
                    <span class="gradient-text">CARDOXIS</span>
                </h1>
                <p class="docs-hero-description">
                    Guia completo da plataforma. Aprenda a gerir a sua frota com inteligência artificial,
                    análise de dados e automação.
                </p>
                <div class="docs-hero-meta">
                    <span><i class="fas fa-code-branch"></i> Versão <?= htmlspecialchars($currentVersion) ?></span>
                    <span><i class="fas fa-calendar-alt"></i> Última atualização: <?= htmlspecialchars($lastUpdated) ?></span>
                    <span><i class="fas fa-users"></i> 3.200+ empresas confiam</span>
                    <span><i class="fas fa-check-circle"></i> 100% compatível com RGPD</span>
                </div>
                <div class="docs-search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" class="docs-search" id="docsSearch" placeholder="Pesquisar na documentação..." autocomplete="off" aria-label="Pesquisar documentação">
                    <span class="docs-search-shortcut">⌘+K</span>
                </div>
            </div>
        </div>
    </section>

    <!--CONTEÚDO PRINCIPAL -->
    <section class="docs-main" id="docs-main">
        <div class="container">
            <div class="docs-layout">
                
                <!-- Sidebar -->
                <aside class="docs-sidebar" id="docsSidebar" role="navigation" aria-label="Navegação da documentação">
                    <button class="docs-sidebar-close" id="sidebarClose" aria-label="Fechar menu lateral">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <?php foreach ($categories as $category => $icon): ?>
                        <?php 
                            $categoryArticles = array_filter($articles, function($a) use ($category) {
                                return $a['category'] === $category;
                            });
                            if (empty($categoryArticles)) continue;
                        ?>
                        <div class="sidebar-section">
                            <div class="sidebar-section-title">
                                <i class="fas <?= htmlspecialchars($icon) ?>"></i> <?= htmlspecialchars($category) ?>
                            </div>
                            <ul class="sidebar-nav">
                                <?php foreach ($categoryArticles as $article): ?>
                                    <li>
                                        <a href="<?= url('docs?article=' . $article['slug']) ?>" 
                                           class="<?= $currentSlug === $article['slug'] ? 'active' : '' ?>"
                                           aria-current="<?= $currentSlug === $article['slug'] ? 'page' : 'false' ?>">
                                            <i class="fas <?= htmlspecialchars($article['icon']) ?>"></i>
                                            <?= htmlspecialchars($article['title']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="sidebar-divider"></div>
                    
                    <div class="sidebar-feedback">
                        <h4><i class="fas fa-comment-dots"></i> Ajudou-o?</h4>
                        <p>Encontrou o que procurava? Deixe o seu feedback para melhorarmos.</p>
                        <a href="<?= url('contact') ?>" class="btn-feedback">
                            <i class="fas fa-paper-plane"></i> Enviar Feedback
                        </a>
                    </div>
                </aside>

                <!-- Content -->
                <article class="docs-content" id="docsContent">
                    
                    <!-- Mobile Toggle -->
                    <button class="docs-sidebar-mobile-toggle" id="sidebarToggle" aria-label="Abrir menu de navegação">
                        <i class="fas fa-bars"></i> Menu de Navegação
                        <i class="fas fa-chevron-down" style="margin-left: auto;"></i>
                    </button>

                    <!-- Breadcrumb -->
                    <nav class="docs-breadcrumb" aria-label="Caminho de navegação">
                        <?php foreach ($breadcrumb as $index => $item): ?>
                            <?php if ($index > 0): ?>
                                <span class="separator">/</span>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a>
                        <?php endforeach; ?>
                    </nav>

                    <!-- Article Header -->
                    <header class="docs-article-header">
                        <h1><?= htmlspecialchars($currentArticle['title']) ?></h1>
                        <div class="article-meta">
                            <span><i class="fas fa-tag"></i> <?= htmlspecialchars($currentArticle['category']) ?></span>
                            <span><i class="fas fa-clock"></i> Leitura: 5 min</span>
                            <span><i class="fas fa-edit"></i> Atualizado: <?= htmlspecialchars($lastUpdated) ?></span>
                        </div>
                    </header>

                    <!-- Article Body -->
                    <div class="docs-article-body" id="articleBody">
                        
                        <!--  CONTEÚDO DO ARTIGO: PRIMEIROS PASSOS -->
                        <?php if ($currentSlug === 'getting-started'): ?>
                            <h2 id="introduction">Introdução à CARDOXIS</h2>
                            <p>
                                A CARDOXIS é uma plataforma inteligente de gestão de frotas que combina
                                <strong>Inteligência Artificial</strong>, <strong>análise de dados em tempo real</strong>
                                e <strong>automação</strong> para otimizar operações de transporte e logística.
                            </p>
                            
                            <div class="callout callout-info">
                                <div class="callout-icon"><i class="fas fa-info-circle"></i></div>
                                <div class="callout-content">
                                    <h4>O que vai aprender</h4>
                                    <p>Este guia cobre desde a criação da sua conta até a gestão avançada da sua frota.</p>
                                </div>
                            </div>

                            <h3 id="what-is-cardoxis">O que é a CARDOXIS?</h3>
                            <p>
                                A CARDOXIS é a solução completa para empresas que desejam transformar a sua gestão de frotas
                                através da tecnologia. Com a nossa plataforma, pode:
                            </p>
                            <ul>
                                <li><strong>Monitorizar</strong> a sua frota em tempo real</li>
                                <li><strong>Otimizar</strong> rotas e reduzir custos operacionais</li>
                                <li><strong>Prever</strong> manutenções com base em IA</li>
                                <li><strong>Analisar</strong> dados de desempenho com dashboards interativos</li>
                                <li><strong>Garantir</strong> conformidade com RGPD</li>
                            </ul>

                            <h3 id="system-requirements">Requisitos do Sistema</h3>
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr><th>Requisito</th><th>Mínimo</th><th>Recomendado</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>Navegador</td><td>Chrome 80+, Firefox 75+, Safari 13+</td><td>Chrome 100+</td></tr>
                                        <tr><td>Internet</td><td>5 Mbps</td><td>10 Mbps+</td></tr>
                                        <tr><td>Ecrã</td><td>1280x720</td><td>1920x1080</td></tr>
                                        <tr><td>App Móvel</td><td>iOS 13+ / Android 10+</td><td>iOS 15+ / Android 12+</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <h3 id="quick-start">Guia Rápido de Início</h3>
                            <ol>
                                <li>
                                    <strong>Crie a sua conta</strong>
                                    <p>Aceda ao portal de registo e preencha os seus dados.</p>
                                </li>
                                <li>
                                    <strong>Configure a sua frota</strong>
                                    <p>Adicione veículos, motoristas e defina parâmetros iniciais.</p>
                                </li>
                                <li>
                                    <strong>Explore o Dashboard</strong>
                                    <p>Visualize métricas e indicadores da sua frota.</p>
                                </li>
                                <li>
                                    <strong>Ative integrações</strong>
                                    <p>Conecte com sistemas ERP, GPS e outros.</p>
                                </li>
                            </ol>

                            <div class="callout callout-success">
                                <div class="callout-icon"><i class="fas fa-check-circle"></i></div>
                                <div class="callout-content">
                                    <h4>Pronto para começar?</h4>
                                    <p>Já tem acesso? <a href="<?= url('login') ?>">Faça login</a> e explore todas as funcionalidades.</p>
                                </div>
                            </div>

                            <h3 id="next-steps">Próximos Passos</h3>
                            <ul>
                                <li><a href="<?= url('docs?article=fleet-management') ?>">Gestão de Frotas</a> - Aprenda a gerir veículos e motoristas</li>
                                <li><a href="<?= url('docs?article=analytics-dashboard') ?>">Análises e Dashboards</a> - Visualize dados e métricas</li>
                                <li><a href="<?= url('docs?article=api-integration') ?>">API e Integrações</a> - Integre com os seus sistemas</li>
                            </ul>

                        <!--  CONTEÚDO DO ARTIGO: GESTÃO DE FROTAS -->
                        <?php elseif ($currentSlug === 'fleet-management'): ?>
                            <h2 id="introduction">Visão Geral da Gestão de Frotas</h2>
                            <p>
                                A gestão de frotas é o coração da CARDOXIS. Esta secção cobre todos os aspectos
                                da administração de veículos e motoristas.
                            </p>

                            <h3 id="vehicles">Gerenciar Veículos</h3>
                            <p>
                                Na página de veículos, pode visualizar, adicionar, editar e remover veículos da sua frota.
                            </p>

                            <div class="callout callout-warning">
                                <div class="callout-icon"><i class="fas fa-exclamation-triangle"></i></div>
                                <div class="callout-content">
                                    <h4>Dica importante</h4>
                                    <p>Mantenha os dados dos veículos sempre atualizados para garantir métricas precisas.</p>
                                </div>
                            </div>

                            <h3 id="drivers">Motoristas e Condutores</h3>
                            <p>Associe motoristas aos veículos e acompanhe o desempenho individual.</p>

                            <h3 id="maintenance">Manutenções Programadas</h3>
                            <p>
                                Agende e acompanhe manutenções preventivas e corretivas. A IA da CARDOXIS pode
                                sugerir manutenções com base no uso do veículo.
                            </p>

                            <div class="callout callout-info">
                                <div class="callout-icon"><i class="fas fa-lightbulb"></i></div>
                                <div class="callout-content">
                                    <h4>Manutenção Preventiva</h4>
                                    <p>A manutenção preventiva reduz custos e aumenta a vida útil dos veículos.</p>
                                </div>
                            </div>

                            <h3 id="next-steps">Próximos Passos</h3>
                            <ul>
                                <li><a href="<?= url('docs?article=analytics-dashboard') ?>">Análises e Dashboards</a> - Acompanhe métricas da frota</li>
                                <li><a href="<?= url('docs?article=security-compliance') ?>">Segurança e Conformidade</a> - Proteja os seus dados</li>
                            </ul>

                        <!-- CONTEÚDO DO ARTIGO: ANÁLISES E DASHBOARDS -->
                        <?php elseif ($currentSlug === 'analytics-dashboard'): ?>
                            <h2 id="introduction">Análises e Dashboards</h2>
                            <p>
                                Transforme dados em insights com dashboards interativos e relatórios personalizados.
                            </p>

                            <h3 id="real-time-dashboard">Dashboard em Tempo Real</h3>
                            <p>
                                Visualize métricas como quilómetros percorridos, consumo de combustível,
                                manutenções e muito mais.
                            </p>

                            <h3 id="custom-reports">Relatórios Personalizados</h3>
                            <p>
                                Crie relatórios personalizados com os filtros e métricas que mais importam para a sua operação.
                            </p>

                            <div class="callout callout-success">
                                <div class="callout-icon"><i class="fas fa-chart-line"></i></div>
                                <div class="callout-content">
                                    <h4>Métricas Essenciais</h4>
                                    <p>Acompanhe KPIs como: custo por quilómetro, eficiência de combustível e utilização da frota.</p>
                                </div>
                            </div>

                            <h3 id="next-steps">Próximos Passos</h3>
                            <ul>
                                <li><a href="<?= url('docs?article=api-integration') ?>">API e Integrações</a> - Exporte dados para outras ferramentas</li>
                                <li><a href="<?= url('docs?article=best-practices') ?>">Boas Práticas</a> - Otimize o uso da plataforma</li>
                            </ul>

                        <!-- CONTEÚDO DO ARTIGO: API E INTEGRAÇÕES -->
                        <?php elseif ($currentSlug === 'api-integration'): ?>
                            <h2 id="introduction">API e Integrações</h2>
                            <p>
                                A CARDOXIS oferece uma API RESTful completa para integração com os seus sistemas existentes.
                            </p>

                            <h3 id="authentication">Autenticação</h3>
                            <p>
                                Utilizamos tokens JWT para autenticação segura. Pode gerar as suas chaves de API
                                no painel de configurações.
                            </p>

                            <h3 id="endpoints">Endpoints Principais</h3>
                            <pre><code># Buscar todos os veículos
GET /api/v1/vehicles

# Criar um novo veículo
POST /api/v1/vehicles
{
    "brand": "Toyota",
    "model": "Hilux",
    "plate": "AA-12-BC",
    "year": 2023
}

# Buscar manutenções
GET /api/v1/maintenances</code></pre>

                            <div class="callout callout-info">
                                <div class="callout-icon"><i class="fas fa-code"></i></div>
                                <div class="callout-content">
                                    <h4>Documentação Completa</h4>
                                    <p>Consulte a referência completa da API para todos os endpoints disponíveis.</p>
                                </div>
                            </div>

                            <h3 id="next-steps">Próximos Passos</h3>
                            <ul>
                                <li><a href="<?= url('docs?article=security-compliance') ?>">Segurança e Conformidade</a> - Proteja as suas integrações</li>
                                <li><a href="<?= url('docs?article=best-practices') ?>">Boas Práticas</a> - Otimize o uso da API</li>
                            </ul>

                        <!-- CONTEÚDO DO ARTIGO: SEGURANÇA E CONFORMIDADE -->
                        <?php elseif ($currentSlug === 'security-compliance'): ?>
                            <h2 id="introduction">Segurança e Conformidade</h2>
                            <p>
                                A segurança dos seus dados é a nossa prioridade. Saiba como protegemos as suas informações.
                            </p>

                            <h3 id="encryption">Criptografia</h3>
                            <ul>
                                <li><strong>Em trânsito:</strong> TLS 1.3</li>
                                <li><strong>Em repouso:</strong> AES-256</li>
                                <li><strong>Backups:</strong> Criptografados e armazenados em local seguro</li>
                            </ul>

                            <h3 id="lgpd-gdpr">RGPD</h3>
                            <p>
                                A CARDOXIS está em total conformidade com o RGPD (Regulamento Geral de Proteção de Dados).
                                Tem controlo total sobre os seus dados.
                            </p>

                            <div class="callout callout-success">
                                <div class="callout-icon"><i class="fas fa-shield-alt"></i></div>
                                <div class="callout-content">
                                    <h4>Certificações</h4>
                                    <p>ISO 27001 • SOC 2 Type II • PCI DSS Level 1</p>
                                </div>
                            </div>

                            <h3 id="next-steps">Próximos Passos</h3>
                            <ul>
                                <li><a href="<?= url('privacy') ?>">Política de Privacidade</a> - Detalhes sobre o tratamento de dados</li>
                                <li><a href="<?= url('docs?article=best-practices') ?>">Boas Práticas</a> - Recomendações de segurança</li>
                            </ul>

                        <!--  CONTEÚDO DO ARTIGO: PERGUNTAS FREQUENTES -->
                        <?php elseif ($currentSlug === 'faq'): ?>
                            <h2 id="introduction">Perguntas Frequentes</h2>
                            <p>Respostas para as dúvidas mais comuns sobre a CARDOXIS.</p>

                            <h3 id="account">Conta e Acesso</h3>
                            <h4>Como criar uma conta?</h4>
                            <p>Aceda ao <a href="<?= url('register') ?>">portal de registo</a> e preencha os dados solicitados. Após a confirmação do e-mail, a sua conta estará ativa.</p>

                            <h4>Esqueci a minha palavra-passe</h4>
                            <p>Na página de <a href="<?= url('login') ?>">login</a>, clique em "Esqueceu a palavra-passe" e siga as instruções para redefinir.</p>

                            <h3 id="billing">Faturação</h3>
                            <h4>Quais são os métodos de pagamento?</h4>
                            <p>Aceitamos cartões de crédito (Visa, Mastercard, AMEX), transferência bancária e faturação para empresas.</p>

                            <h3 id="support">Suporte</h3>
                            <h4>Como entro em contacto com o suporte?</h4>
                            <p>Pode contactar-nos através do <a href="<?= url('contact') ?>">formulário de contacto</a>, e-mail ou telefone. Estamos disponíveis de segunda a sexta, das 9h às 18h.</p>

                        <!-- CONTEÚDO DO ARTIGO: CONTEÚDO GENÉRICO (FALLBACK) -->
                        <?php else: ?>
                            <h2 id="introduction">Bem-vindo à Documentação</h2>
                            <p>
                                Selecione um tópico no menu lateral para começar a explorar a documentação da CARDOXIS.
                            </p>
                            <div class="callout callout-info">
                                <div class="callout-icon"><i class="fas fa-lightbulb"></i></div>
                                <div class="callout-content">
                                    <h4>Dica</h4>
                                    <p>Use a barra de pesquisa para encontrar rapidamente o que procura.</p>
                                </div>
                            </div>
                            <h3 id="popular-articles">Artigos Populares</h3>
                            <ul>
                                <li><a href="<?= url('docs?article=getting-started') ?>">Primeiros Passos</a> - Guia introdutório</li>
                                <li><a href="<?= url('docs?article=fleet-management') ?>">Gestão de Frotas</a> - Gerencie veículos e motoristas</li>
                                <li><a href="<?= url('docs?article=api-integration') ?>">API e Integrações</a> - Integre com os seus sistemas</li>
                            </ul>
                        <?php endif; ?>

                        <!-- SECÇÃO DE FEEDBACK -->
                        <footer class="docs-article-footer">
                            <div class="docs-feedback">
                                <p><i class="fas fa-thumbs-up"></i> Este artigo foi útil?</p>
                                <div class="docs-feedback-buttons">
                                    <button class="active" aria-label="Sim, útil"><i class="fas fa-check"></i> Sim</button>
                                    <button aria-label="Não, não foi útil"><i class="fas fa-times"></i> Não</button>
                                </div>
                            </div>
                        </footer>

                        <!--  ARTIGOS RELACIONADOS -->
                        <div class="docs-related">
                            <h3><i class="fas fa-link"></i> Artigos Relacionados</h3>
                            <div class="docs-related-grid">
                                <?php 
                                $related = array_filter($articles, function($a) use ($currentArticle) {
                                    return $a['id'] !== $currentArticle['id'] && 
                                           $a['category'] === $currentArticle['category'];
                                });
                                $related = array_slice($related, 0, 3);
                                foreach ($related as $article): ?>
                                    <a href="<?= url('docs?article=' . $article['slug']) ?>" class="docs-related-card">
                                        <i class="fas <?= htmlspecialchars($article['icon']) ?>" style="color: var(--primary); margin-bottom: 8px; display: block;"></i>
                                        <h4><?= htmlspecialchars($article['title']) ?></h4>
                                        <p><?= htmlspecialchars($article['description']) ?></p>
                                    </a>
                                <?php endforeach; ?>
                                
                                <?php if (empty($related)): ?>
                                    <div class="docs-related-card" style="grid-column: span 3; text-align: center; padding: 30px;">
                                        <p style="color: var(--gray);">Mais artigos em breve.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Table of Contents (Desktop) -->
                <aside class="docs-toc" id="docsToc" aria-label="Índice do artigo">
                    <h4><i class="fas fa-list-ul"></i> Neste artigo</h4>
                    <ul id="tocList">
                        <?php foreach ($tocItems as $item): ?>
                            <li><a href="#<?= htmlspecialchars($item['id']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </aside>
            </div>
        </div>
    </section>

</main>

<!-- RODAPÉ -->
<?php include_once __DIR__ . '/../layouts/partials/footer.php'; ?>

<!-- BOTÃO VOLTAR AO TOPO -->
<button class="back-to-top" id="backToTop" title="Voltar ao topo">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- SCRIPTS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="<?= asset('js/landing/docs.js') ?>"></script>
<?php include_once __DIR__ . '/../layouts/partials/scripts.php'; ?>

</body>
</html>