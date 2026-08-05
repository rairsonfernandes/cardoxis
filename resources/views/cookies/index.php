<?php
/**
 * CARDOXIS - Política de Cookies 
 */

// Carregar configurações
require_once __DIR__ . '/../layouts/config.php';
require_once __DIR__ . '/../layouts/helpers.php';

// CONFIGURAÇÕES DA PÁGINA

$pageTitle = "Política de Cookies - CARDOXIS | Transparência e Privacidade";
$pageDescription = "Política de Cookies da CARDOXIS. Saiba como utilizamos cookies e tecnologias similares para melhorar a sua experiência na plataforma de gestão de frotas com IA.";
$lastUpdated = "15 de Janeiro de 2024";
$currentVersion = "2.0";

// ÍNDICE DE NAVEGAÇÃO

$tocSections = [
    ['id' => 'section1', 'number' => '01', 'title' => 'O que são Cookies?'],
    ['id' => 'section2', 'number' => '02', 'title' => 'Tipos de Cookies'],
    ['id' => 'section3', 'number' => '03', 'title' => 'Cookies que Utilizamos'],
    ['id' => 'section4', 'number' => '04', 'title' => 'Cookies de Terceiros'],
    ['id' => 'section5', 'number' => '05', 'title' => 'Gerenciamento de Cookies'],
    ['id' => 'section6', 'number' => '06', 'title' => 'Segurança e Privacidade'],
    ['id' => 'section7', 'number' => '07', 'title' => 'Retenção de Cookies'],
    ['id' => 'section8', 'number' => '08', 'title' => 'Alterações na Política'],
    ['id' => 'section9', 'number' => '09', 'title' => 'Contacto']
];

// COOKIES DA PLATAFORMA

$cookiesList = [
    [
        'name' => 'cardoxis_session',
        'purpose' => 'Identifica a sessão do utilizador logado',
        'duration' => 'Sessão',
        'type' => 'Essencial'
    ],
    [
        'name' => 'cardoxis_user',
        'purpose' => 'Armazena dados de autenticação do utilizador',
        'duration' => '30 dias',
        'type' => 'Essencial'
    ],
    [
        'name' => 'cardoxis_preferences',
        'purpose' => 'Salva preferências de idioma e layout',
        'duration' => '1 ano',
        'type' => 'Funcional'
    ],
    [
        'name' => '_ga',
        'purpose' => 'Google Analytics - Distingue utilizadores únicos',
        'duration' => '2 anos',
        'type' => 'Analítico'
    ],
    [
        'name' => '_gid',
        'purpose' => 'Google Analytics - Distingue utilizadores',
        'duration' => '24 horas',
        'type' => 'Analítico'
    ],
    [
        'name' => '_gat',
        'purpose' => 'Google Analytics - Limita requisições',
        'duration' => '1 minuto',
        'type' => 'Analítico'
    ],
    [
        'name' => '_fbp',
        'purpose' => 'Facebook Pixel - Anúncios personalizados',
        'duration' => '3 meses',
        'type' => 'Marketing'
    ],
    [
        'name' => 'cardoxis_consent',
        'purpose' => 'Regista o seu consentimento de cookies',
        'duration' => '1 ano',
        'type' => 'Essencial'
    ]
];

// COOKIES DE TERCEIROS

$thirdPartyCookies = [
    [
        'icon' => 'fab fa-google',
        'name' => 'Google Analytics',
        'description' => 'Analisa o tráfego e comportamento dos utilizadores para melhorar a nossa plataforma.',
        'link' => 'https://policies.google.com/privacy',
        'link_text' => 'Política de Privacidade do Google'
    ],
    [
        'icon' => 'fab fa-facebook',
        'name' => 'Facebook Pixel',
        'description' => 'Mede a eficácia de campanhas publicitárias e otimiza anúncios.',
        'link' => 'https://www.facebook.com/privacy/policy/',
        'link_text' => 'Política de Privacidade do Facebook'
    ],
    [
        'icon' => 'fab fa-linkedin',
        'name' => 'LinkedIn Insights',
        'description' => 'Acompanha conversões e otimiza campanhas no LinkedIn.',
        'link' => 'https://www.linkedin.com/legal/privacy-policy',
        'link_text' => 'Política de Privacidade do LinkedIn'
    ],
    [
        'icon' => 'fab fa-hotjar',
        'name' => 'Hotjar',
        'description' => 'Analisa o comportamento dos utilizadores através de heatmaps e gravações.',
        'link' => 'https://www.hotjar.com/legal/policies/privacy/',
        'link_text' => 'Política de Privacidade do Hotjar'
    ]
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
    <meta property="og:url" content="<?= url('cookies') ?>">
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
    
    <!-- Fontes e Bibliotecas -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= asset('css/landing/landing.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/landing/cookies.css') ?>">
</head>
<body>

<!-- NAVBAR -->
<?php include_once __DIR__ . '/../layouts/partials/navbar.php'; ?>

<!--  CONTEÚDO PRINCIPAL -->
<main>

    <!-- SECÇÃO HERO-->
    <section class="cookies-hero" id="cookies-hero">
        <div class="container">
            <div class="cookies-hero-content" data-aos="fade-up">
                <div class="hero-badge">
                    <span><i class="fas fa-cookie-bite"></i> Política de Cookies</span>
                </div>
                <h1 class="cookies-hero-title">
                    Política de<br>
                    <span class="gradient-text">Cookies</span>
                </h1>
                <p class="cookies-hero-description">
                    Na CARDOXIS, utilizamos cookies e tecnologias similares para melhorar a sua experiência,
                    personalizar conteúdo e analisar o nosso tráfego. Saiba mais sobre como e por que utilizamos cookies.
                </p>
                <div class="cookies-hero-meta">
                    <span><i class="fas fa-calendar-alt"></i> Última atualização: <?= htmlspecialchars($lastUpdated) ?></span>
                    <span><i class="fas fa-code-branch"></i> Versão <?= htmlspecialchars($currentVersion) ?></span>
                    <span><i class="fas fa-globe"></i> Aplicável globalmente</span>
                    <span><i class="fas fa-check-circle"></i> RGPD & LGPD Compliant</span>
                </div>
            </div>
        </div>
        
        <!-- Elementos decorativos -->
        <div class="cookies-hero-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </section>

    <!-- SECÇÃO DE AÇÕES -->
    <section class="cookies-actions-section">
        <div class="container">
            <div class="cookies-actions" data-aos="fade-up">
                <button class="action-btn action-btn-print" id="printBtn" aria-label="Imprimir página">
                    <i class="fas fa-print"></i>
                    <span>Imprimir</span>
                </button>
                <button class="action-btn action-btn-pdf" id="pdfBtn" aria-label="Baixar PDF">
                    <i class="fas fa-file-pdf"></i>
                    <span>Baixar PDF</span>
                </button>
                <button class="action-btn action-btn-copy" id="copyUrlBtn" aria-label="Copiar URL">
                    <i class="fas fa-link"></i>
                    <span>Copiar URL</span>
                </button>
                <button class="action-btn action-btn-settings" id="openCookieSettings" aria-label="Gerenciar cookies">
                    <i class="fas fa-sliders-h"></i>
                    <span>Gerenciar Cookies</span>
                </button>
            </div>
        </div>
    </section>

    <!-- SECÇÃO SUMÁRIO / ÍNDICE-->
    <section class="cookies-summary" id="cookies-summary">
        <div class="container">
            <div class="summary-card" data-aos="fade-up">
                <div class="summary-header">
                    <i class="fas fa-list-ul"></i>
                    <h3>Navegação Rápida</h3>
                </div>
                <div class="summary-grid">
                    <?php foreach ($tocSections as $item): ?>
                        <a href="#<?= htmlspecialchars($item['id']) ?>" class="summary-link">
                            <i class="fas fa-chevron-right"></i>
                            <span><?= htmlspecialchars($item['number']) ?>. <?= htmlspecialchars($item['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTEÚDO DA POLÍTICA DE COOKIES -->
    <section class="cookies-content" id="cookies-content">
        <div class="container">
            <div class="cookies-container" id="cookiesContent" data-aos="fade-up">
                
                <!--  SECÇÃO 1 - O QUE SÃO COOKIES -->
                <div id="section1" class="cookies-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">01</span> O que são Cookies?</h2>
                        <button class="copy-link" data-section="section1" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>
                            Cookies são pequenos ficheiros de texto que os sites que visita armazenam no seu dispositivo 
                            (computador, tablet, smartphone). São amplamente utilizados para fazer os sites funcionarem 
                            de forma mais eficiente, além de fornecer informações aos proprietários do site.
                        </p>
                        
                        <div class="info-note">
                            <i class="fas fa-info-circle"></i>
                            <span>
                                <strong>Importante:</strong> Os cookies não são vírus ou programas maliciosos. 
                                Não podem aceder ao seu disco rígido ou danificar o seu dispositivo.
                            </span>
                        </div>
                        
                        <div class="cookie-types-intro">
                            <h3>Como os cookies nos ajudam?</h3>
                            <div class="benefits-grid">
                                <div class="benefit-item">
                                    <i class="fas fa-rocket"></i>
                                    <div>
                                        <strong>Performance</strong>
                                        <p>Melhoram a velocidade e eficiência do site</p>
                                    </div>
                                </div>
                                <div class="benefit-item">
                                    <i class="fas fa-user-check"></i>
                                    <div>
                                        <strong>Autenticação</strong>
                                        <p>Mantêm-no logado na plataforma</p>
                                    </div>
                                </div>
                                <div class="benefit-item">
                                    <i class="fas fa-chart-line"></i>
                                    <div>
                                        <strong>Análise</strong>
                                        <p>Ajudam-nos a compreender como utiliza o site</p>
                                    </div>
                                </div>
                                <div class="benefit-item">
                                    <i class="fas fa-palette"></i>
                                    <div>
                                        <strong>Personalização</strong>
                                        <p>Lembram as suas preferências e configurações</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 2 - TIPOS DE COOKIES -->
                <div id="section2" class="cookies-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">02</span> Tipos de Cookies</h2>
                        <button class="copy-link" data-section="section2" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Classificamos os cookies de acordo com a sua função e duração:</p>
                        
                        <div class="types-grid">
                            <div class="type-card">
                                <div class="type-icon"><i class="fas fa-star"></i></div>
                                <h4>Cookies Essenciais</h4>
                                <p>Necessários para o funcionamento básico do site. Sem eles, a plataforma não funciona corretamente.</p>
                                <span class="type-badge type-badge-essential">Sempre Ativos</span>
                            </div>
							<div class="type-card">
								<div class="type-icon">
									<i class="fas fa-chart-line"></i>
								</div>
								<h4>Cookies Analíticos</h4>
								<p>Permitem medir e analisar o tráfego e comportamento dos utilizadores no site.</p>
								<span class="type-badge type-badge-optional">Opcionais</span>
							</div>
                            <div class="type-card">
                                <div class="type-icon"><i class="fas fa-cog"></i></div>
                                <h4>Cookies Funcionais</h4>
                                <p>Lembram as suas preferências para oferecer uma experiência personalizada.</p>
                                <span class="type-badge type-badge-optional">Opcionais</span>
                            </div>
                            <div class="type-card">
                                <div class="type-icon"><i class="fas fa-bullhorn"></i></div>
                                <h4>Cookies de Marketing</h4>
                                <p>Utilizados para exibir anúncios relevantes e medir campanhas.</p>
                                <span class="type-badge type-badge-optional">Opcionais</span>
                            </div>
                        </div>
                        
                        <div class="duration-info">
                            <h4><i class="fas fa-clock"></i> Quanto aos prazos de validade</h4>
                            <ul>
                                <li><strong>Cookies de Sessão:</strong> Temporários, expiram quando fecha o navegador.</li>
                                <li><strong>Cookies Persistentes:</strong> Permanecem no dispositivo até expirarem ou serem excluídos.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 3 - COOKIES QUE UTILIZAMOS -->
                <div id="section3" class="cookies-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">03</span> Cookies que Utilizamos</h2>
                        <button class="copy-link" data-section="section3" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Detalhamos abaixo todos os cookies utilizados na nossa plataforma:</p>
                        
                        <div class="table-wrapper">
                            <table class="cookies-table">
                                <thead>
                                    <tr>
                                        <th>Nome do Cookie</th>
                                        <th>Finalidade</th>
                                        <th>Duração</th>
                                        <th>Tipo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cookiesList as $cookie): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($cookie['name']) ?></code></td>
                                            <td><?= htmlspecialchars($cookie['purpose']) ?></td>
                                            <td><?= htmlspecialchars($cookie['duration']) ?></td>
                                            <td>
                                                <span class="cookie-type-badge cookie-type-<?= strtolower($cookie['type']) ?>">
                                                    <?= htmlspecialchars($cookie['type']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="info-note">
                            <i class="fas fa-shield-alt"></i>
                            <span>Não utilizamos cookies para coletar informações pessoais sem o seu consentimento explícito.</span>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 4 - COOKIES DE TERCEIROS -->
                <div id="section4" class="cookies-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">04</span> Cookies de Terceiros</h2>
                        <button class="copy-link" data-section="section4" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Alguns cookies são fornecidos por parceiros e serviços terceiros que utilizamos para melhorar a nossa plataforma:</p>
                        
                        <div class="third-party-grid">
                            <?php foreach ($thirdPartyCookies as $thirdParty): ?>
                                <div class="third-party-card">
                                    <div class="third-party-icon"><i class="<?= htmlspecialchars($thirdParty['icon']) ?>"></i></div>
                                    <div>
                                        <h4><?= htmlspecialchars($thirdParty['name']) ?></h4>
                                        <p><?= htmlspecialchars($thirdParty['description']) ?></p>
                                        <a href="<?= htmlspecialchars($thirdParty['link']) ?>" target="_blank" rel="noopener noreferrer" class="third-party-link">
                                            <?= htmlspecialchars($thirdParty['link_text']) ?> <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="info-note info-note-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Recomendamos revisar as políticas de privacidade desses terceiros para compreender como utilizam os seus dados.</span>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 5 - GERENCIAMENTO DE COOKIES -->
                <div id="section5" class="cookies-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">05</span> Gerenciamento de Cookies</h2>
                        <button class="copy-link" data-section="section5" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Tem o controlo total sobre os cookies que deseja aceitar. Veja como gerenciá-los:</p>
                        
                        <div class="manage-grid">
                            <div class="manage-card">
                                <i class="fas fa-sliders-h"></i>
                                <h4>Nosso Sistema de Consentimento</h4>
                                <p>Pode gerir as suas preferências de cookies diretamente no nosso sistema de consentimento.</p>
                                <button class="btn-manage" id="manageCookiesBtn">
                                    <i class="fas fa-cog"></i> Gerenciar Preferências
                                </button>
                            </div>
                            <div class="manage-card">
                                <i class="fas fa-globe"></i>
                                <h4>Configurações do Navegador</h4>
                                <p>A maioria dos navegadores permite controlar cookies através das suas configurações.</p>
                                <div class="browser-links">
                                    <a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener noreferrer" class="browser-link">
                                        <i class="fab fa-chrome"></i> Chrome
                                    </a>
                                    <a href="https://support.mozilla.org/pt/kb/ative-desative-cookies" target="_blank" rel="noopener noreferrer" class="browser-link">
                                        <i class="fab fa-firefox"></i> Firefox
                                    </a>
                                    <a href="https://support.apple.com/pt-br/guide/safari/sfri11471/mac" target="_blank" rel="noopener noreferrer" class="browser-link">
                                        <i class="fab fa-safari"></i> Safari
                                    </a>
                                    <a href="https://support.microsoft.com/pt-br/windows/excluir-e-gerenciar-cookies" target="_blank" rel="noopener noreferrer" class="browser-link">
                                        <i class="fab fa-edge"></i> Edge
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="consent-options">
                            <h4>Opções de Consentimento</h4>
                            <div class="options-list">
                                <div class="option-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <strong>Aceitar Todos</strong>
                                    <span>Permite todos os tipos de cookies</span>
                                </div>
                                <div class="option-item">
                                    <i class="fas fa-times-circle text-danger"></i>
                                    <strong>Rejeitar Opcionais</strong>
                                    <span>Permite apenas cookies essenciais</span>
                                </div>
                                <div class="option-item">
                                    <i class="fas fa-cog"></i>
                                    <strong>Personalizar</strong>
                                    <span>Escolha quais categorias aceitar</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 6 - SEGURANÇA E PRIVACIDADE -->
                <div id="section6" class="cookies-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">06</span> Segurança e Privacidade</h2>
                        <button class="copy-link" data-section="section6" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Levamos a segurança dos seus dados muito a sério. Implementamos as seguintes medidas:</p>
                        
                        <div class="security-features">
                            <div class="security-feature">
                                <i class="fas fa-lock"></i>
                                <div>
                                    <strong>Criptografia</strong>
                                    <p>Dados transmitidos são criptografados com TLS 1.3</p>
                                </div>
                            </div>
                            <div class="security-feature">
                                <i class="fas fa-user-shield"></i>
                                <div>
                                    <strong>Anonimização</strong>
                                    <p>Dados analíticos são anonimizados sempre que possível</p>
                                </div>
                            </div>
                            <div class="security-feature">
                                <i class="fas fa-gavel"></i>
                                <div>
                                    <strong>Conformidade Legal</strong>
                                    <p>Seguimos rigorosamente a RGPD e ePrivacy Directive</p>
                                </div>
                            </div>
                            <div class="security-feature">
                                <i class="fas fa-database"></i>
                                <div>
                                    <strong>Retenção Limitada</strong>
                                    <p>Cookies têm prazo de validade definido e controlado</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 7 - RETENÇÃO DE COOKIES -->
                <div id="section7" class="cookies-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">07</span> Retenção de Cookies</h2>
                        <button class="copy-link" data-section="section7" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Os cookies têm diferentes períodos de retenção conforme a sua finalidade:</p>
                        
                        <div class="retention-grid">
                            <div class="retention-item">
                                <div class="retention-period">Sessão</div>
                                <p>Cookies temporários que expiram ao fechar o navegador</p>
                            </div>
                            <div class="retention-item">
                                <div class="retention-period">24 horas</div>
                                <p>Cookies de análise de curto prazo</p>
                            </div>
                            <div class="retention-item">
                                <div class="retention-period">30 dias</div>
                                <p>Cookies de autenticação e preferências</p>
                            </div>
                            <div class="retention-item">
                                <div class="retention-period">1 ano</div>
                                <p>Cookies de consentimento e preferências persistentes</p>
                            </div>
                            <div class="retention-item">
                                <div class="retention-period">2 anos</div>
                                <p>Cookies analíticos (como Google Analytics)</p>
                            </div>
                        </div>
                        
                        <div class="info-note">
                            <i class="fas fa-trash-alt"></i>
                            <span>Pode excluir cookies a qualquer momento através das configurações do seu navegador.</span>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 8 - ALTERAÇÕES NA POLÍTICA -->
                <div id="section8" class="cookies-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">08</span> Alterações na Política</h2>
                        <button class="copy-link" data-section="section8" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Podemos atualizar esta Política de Cookies periodicamente para refletir mudanças nas nossas práticas ou requisitos legais.</p>
                        
                        <ul>
                            <li>Alterações materiais serão comunicadas com 30 dias de antecedência por e-mail</li>
                            <li>A versão mais recente estará sempre disponível nesta página</li>
                            <li>A data de "última atualização" indica quando a política foi revista</li>
                            <li>Ao continuar usando a plataforma, aceita a política atualizada</li>
                        </ul>
                        
                        <div class="version-history">
                            <h4><i class="fas fa-code-branch"></i> Histórico de Versões</h4>
                            <ul>
                                <li><strong>Versão 2.0</strong> - 15/01/2024 - Atualização completa para conformidade com RGPD</li>
                                <li><strong>Versão 1.0</strong> - 01/01/2023 - Lançamento inicial</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 9 - CONTACTO -->
                <div id="section9" class="cookies-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">09</span> Contacto</h2>
                        <button class="copy-link" data-section="section9" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Se tiver dúvidas sobre a nossa Política de Cookies, entre em contacto connosco:</p>
                        
                        <div class="contact-card">
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <strong>E-mail</strong>
                                    <p>cookies@cardoxis.com</p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-phone-alt"></i>
                                <div>
                                    <strong>Telefone</strong>
                                    <p>+351 234 567 890</p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-shield-alt"></i>
                                <div>
                                    <strong>DPO (Encarregado)</strong>
                                    <p>dpo@cardoxis.com</p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <strong>Endereço</strong>
                                    <p>Av. da Liberdade, 245, 1250-143 Lisboa, Portugal</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- SECÇÃO DE GERENCIAMENTO DE COOKIES -->
    <section class="cookie-management-section" id="cookie-management">
        <div class="container">
            <div class="management-card" data-aos="fade-up">
                <div class="management-icon">
                    <i class="fas fa-cookie-bite"></i>
                </div>
                <h3>Gerencie as suas preferências de Cookies</h3>
                <p>Pode alterar as suas configurações de cookies a qualquer momento através do nosso sistema de consentimento.</p>
                <div class="management-buttons">
                    <button class="btn-manage-cookies" id="manageCookiesFooterBtn">
                        <i class="fas fa-sliders-h"></i> Gerenciar Preferências
                    </button>
                    <button class="btn-accept-all" id="acceptAllCookiesBtn">
                        <i class="fas fa-check"></i> Aceitar Todos
                    </button>
                    <button class="btn-reject-all" id="rejectAllCookiesBtn">
                        <i class="fas fa-times"></i> Rejeitar Opcionais
                    </button>
                </div>
                <div class="management-date">
                    <i class="fas fa-history"></i> Esta política está em vigor desde <?= htmlspecialchars($lastUpdated) ?>
                </div>
            </div>
        </div>
    </section>

</main>

<!-- RODAPÉ  -->
<?php include_once __DIR__ . '/../layouts/partials/footer.php'; ?>

<!-- BOTÃO VOLTAR AO TOPO -->
<button class="back-to-top" id="backToTop" title="Voltar ao topo">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- SCRIPTS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="<?= asset('js/landing/cookies.js') ?>"></script>
<?php include_once __DIR__ . '/../layouts/partials/scripts.php'; ?>

</body>
</html>