<?php
/**
 * CARDOXIS - Política de Privacidade RF
 */

// Carregar configurações
require_once __DIR__ . '/../layouts/config.php';
require_once __DIR__ . '/../layouts/helpers.php';

// CONFIGURAÇÕES DA PÁGINA

$pageTitle = "Política de Privacidade - CARDOXIS | Proteção de Dados RGPD";
$pageDescription = "Política de Privacidade da CARDOXIS. Saiba como protegemos os seus dados pessoais em conformidade com a RGPD (UE) e LGPD (Brasil).";
$lastUpdated = "15 de Janeiro de 2024";
$currentVersion = "2.0";
$contactEmail = "dpo@cardoxis.com";

// ÍNDICE DE NAVEGAÇÃO

$tocSections = [
    ['id' => 'section1', 'number' => '01', 'title' => 'Informações que Coletamos'],
    ['id' => 'section2', 'number' => '02', 'title' => 'Como Utilizamos os seus Dados'],
    ['id' => 'section3', 'number' => '03', 'title' => 'Compartilhamento de Informações'],
    ['id' => 'section4', 'number' => '04', 'title' => 'Os seus Direitos (RGPD/LGPD)'],
    ['id' => 'section5', 'number' => '05', 'title' => 'Segurança dos Dados'],
    ['id' => 'section6', 'number' => '06', 'title' => 'Retenção de Dados'],
    ['id' => 'section7', 'number' => '07', 'title' => 'Cookies e Tecnologias'],
    ['id' => 'section8', 'number' => '08', 'title' => 'Dados de Menores'],
    ['id' => 'section9', 'number' => '09', 'title' => 'Transferência Internacional de Dados'],
    ['id' => 'section10', 'number' => '10', 'title' => 'Alterações nesta Política'],
    ['id' => 'section11', 'number' => '11', 'title' => 'Contacto e Encarregado (DPO)']
];

// DIREITOS DO TITULAR (RGPD)

$rights = [
    [
        'icon' => 'fa-check-circle',
        'title' => 'Confirmação e Acesso',
        'description' => 'Saber se tratamos os seus dados e aceder a eles.'
    ],
    [
        'icon' => 'fa-pen',
        'title' => 'Retificação',
        'description' => 'Corrigir dados incompletos ou desatualizados.'
    ],
    [
        'icon' => 'fa-trash-alt',
        'title' => 'Eliminação (Direito ao Esquecimento)',
        'description' => 'Solicitar a eliminação dos seus dados pessoais.'
    ],
    [
        'icon' => 'fa-ban',
        'title' => 'Oposição',
        'description' => 'Opor-se a tratamentos específicos dos seus dados.'
    ],
    [
        'icon' => 'fa-download',
        'title' => 'Portabilidade',
        'description' => 'Receber os seus dados em formato estruturado e legível.'
    ],
    [
        'icon' => 'fa-pause-circle',
        'title' => 'Limitação do Tratamento',
        'description' => 'Suspender o tratamento em determinadas situações.'
    ],
    [
        'icon' => 'fa-comment-dots',
        'title' => 'Revisão de Decisões Automatizadas',
        'description' => 'Solicitar revisão de decisões baseadas em IA.'
    ],
    [
        'icon' => 'fa-file-signature',
        'title' => 'Revogação de Consentimento',
        'description' => 'Cancelar consentimento previamente concedido.'
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
    <meta property="og:url" content="<?= url('privacy') ?>">
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
    <link rel="stylesheet" href="<?= asset('css/landing/privacy.css') ?>">
</head>
<body>

<!-- NAVBAR -->
<?php include_once __DIR__ . '/../layouts/partials/navbar.php'; ?>

<!-- CONTEÚDO PRINCIPAL -->
<main>

    <!--  SECÇÃO HERO -->
    <section class="privacy-hero" id="privacy-hero">
        <div class="container">
            <div class="privacy-hero-content" data-aos="fade-up">
                <div class="hero-badge">
                    <span><i class="fas fa-shield-alt"></i> Privacidade e Proteção de Dados</span>
                </div>
                <h1 class="privacy-hero-title">
                    Política de<br>
                    <span class="gradient-text">Privacidade</span>
                </h1>
                <p class="privacy-hero-description">
                    Na CARDOXIS, a sua privacidade é uma prioridade. Esta política descreve como coletamos,
                    utilizamos e protegemos as suas informações pessoais, em conformidade com a RGPD e LGPD.
                </p>
                <div class="privacy-hero-meta">
                    <span><i class="fas fa-calendar-alt"></i> Última atualização: <?= htmlspecialchars($lastUpdated) ?></span>
                    <span><i class="fas fa-code-branch"></i> Versão <?= htmlspecialchars($currentVersion) ?></span>
                    <span><i class="fas fa-globe"></i> Aplicável globalmente</span>
                    <span><i class="fas fa-check-circle"></i> RGPD & LGPD Compliant</span>
                </div>
            </div>
        </div>
        
        <!-- Elementos decorativos -->
        <div class="privacy-hero-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </section>

    <!-- SECÇÃO DE AÇÕES (IMPRIMIR, PDF, COPIAR) -->
    <section class="privacy-actions-section">
        <div class="container">
            <div class="privacy-actions" data-aos="fade-up">
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
            </div>
        </div>
    </section>

    <!--    SECÇÃO SUMÁRIO / ÍNDICE -->
    <section class="privacy-summary" id="privacy-summary">
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

    <!--CONTEÚDO DA POLÍTICA DE PRIVACIDADE -->
    
    <section class="privacy-content" id="privacy-content">
        <div class="container">
            <div class="privacy-container" id="privacyContent" data-aos="fade-up">
                
                <!-- SECÇÃO 1 - INFORMAÇÕES QUE COLETAMOS -->
                <div id="section1" class="privacy-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">01</span> Informações que Coletamos</h2>
                        <button class="copy-link" data-section="section1" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>
                            Para fornecer os nossos serviços de gestão de frotas com inteligência artificial, 
                            coletamos as seguintes categorias de informações:
                        </p>
                        
                        <div class="info-grid">
                            <div class="info-card-mini">
                                <div class="info-icon"><i class="fas fa-user"></i></div>
                                <h4>Dados Pessoais</h4>
                                <ul>
                                    <li>Nome completo</li>
                                    <li>E-mail corporativo</li>
                                    <li>Telefone de contacto</li>
                                    <li>Cargo e função</li>
                                    <li>Empresa / Organização</li>
                                </ul>
                            </div>
                            <div class="info-card-mini">
                                <div class="info-icon"><i class="fas fa-car"></i></div>
                                <h4>Dados da Frota</h4>
                                <ul>
                                    <li>Placas e identificação</li>
                                    <li>Dados de localização</li>
                                    <li>Consumo de combustível</li>
                                    <li>Manutenções realizadas</li>
                                    <li>Quilometragem percorrida</li>
                                </ul>
                            </div>
                            <div class="info-card-mini">
                                <div class="info-icon"><i class="fas fa-chart-line"></i></div>
                                <h4>Dados de Utilização</h4>
                                <ul>
                                    <li>Logs de acesso à plataforma</li>
                                    <li>IP e informações do dispositivo</li>
                                    <li>Navegador e sistema operativo</li>
                                    <li>Funcionalidades utilizadas</li>
                                    <li>Tempo de utilização</li>
                                </ul>
                            </div>
                            <div class="info-card-mini">
                                <div class="info-icon"><i class="fas fa-file-invoice"></i></div>
                                <h4>Dados Financeiros</h4>
                                <ul>
                                    <li>Informações de pagamento</li>
                                    <li>Histórico de faturas</li>
                                    <li>Endereço de cobrança</li>
                                    <li>NIF / NIPC da empresa</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="info-note">
                            <i class="fas fa-info-circle"></i>
                            <span>
                                <strong>Importante:</strong> Não coletamos dados sensíveis (como informações de saúde, 
                                orientação política, crenças religiosas) sem o seu consentimento explícito.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 2 - COMO UTILIZAMOS OS SEUS DADOS -->
                <div id="section2" class="privacy-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">02</span> Como Utilizamos os seus Dados</h2>
                        <button class="copy-link" data-section="section2" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Utilizamos as suas informações para as seguintes finalidades:</p>
                        
                        <div class="purposes-grid">
                            <div class="purpose-item">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <div>
                                    <strong>Fornecimento do Serviço</strong>
                                    <p>Operar, manter e melhorar a nossa plataforma de gestão de frotas.</p>
                                </div>
                            </div>
                            <div class="purpose-item">
                                <i class="fas fa-headset"></i>
                                <div>
                                    <strong>Suporte ao Cliente</strong>
                                    <p>Atender as suas solicitações, dúvidas e fornecer assistência técnica.</p>
                                </div>
                            </div>
                            <div class="purpose-item">
                                <i class="fas fa-chart-simple"></i>
                                <div>
                                    <strong>Análise e Melhorias</strong>
                                    <p>Compreender como utiliza a plataforma para otimizar recursos e funcionalidades.</p>
                                </div>
                            </div>
                            <div class="purpose-item">
                                <i class="fas fa-gavel"></i>
                                <div>
                                    <strong>Segurança e Conformidade</strong>
                                    <p>Prevenir fraudes, garantir a segurança e cumprir obrigações legais.</p>
                                </div>
                            </div>
                            <div class="purpose-item">
                                <i class="fas fa-bullhorn"></i>
                                <div>
                                    <strong>Comunicações</strong>
                                    <p>Enviar atualizações, newsletters e informações sobre o serviço.</p>
                                </div>
                            </div>
                            <div class="purpose-item">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <div>
                                    <strong>Faturação</strong>
                                    <p>Processar pagamentos e gerir a sua assinatura.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--  SECÇÃO 3 - COMPARTILHAMENTO DE INFORMAÇÕES -->
                <div id="section3" class="privacy-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">03</span> Compartilhamento de Informações</h2>
                        <button class="copy-link" data-section="section3" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>
                            A CARDOXIS não vende nem aluga os seus dados pessoais. Podemos compartilhar 
                            informações nas seguintes situações:
                        </p>
                        
                        <div class="table-wrapper">
                            <table class="share-table">
                                <thead>
                                    <tr>
                                        <th>Parceiro / Tipo</th>
                                        <th>Finalidade</th>
                                        <th>Medidas de Proteção</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Provedores de Cloud</strong><br><small>AWS, Google Cloud</small></td>
                                        <td>Hospedagem e infraestrutura</td>
                                        <td>Criptografia e acordos de confidencialidade</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Processamento de Pagamentos</strong><br><small>Stripe, PayPal</small></td>
                                        <td>Processamento de transações</td>
                                        <td>Dados criptografados, PCI DSS</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Autoridades Legais</strong></td>
                                        <td>Cumprimento de obrigações legais</td>
                                        <td>Somente mediante ordem judicial</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Integrações API</strong></td>
                                        <td>Conexão com sistemas parceiros</td>
                                        <td>Autorização prévia do cliente</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 4 - OS SEUS DIREITOS (RGPD/LGPD) -->
                <div id="section4" class="privacy-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">04</span> Os seus Direitos (RGPD/LGPD)</h2>
                        <button class="copy-link" data-section="section4" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Como titular dos seus dados pessoais, possui os seguintes direitos:</p>
                        
                        <div class="rights-grid">
                            <?php foreach ($rights as $right): ?>
                                <div class="right-card">
                                    <i class="fas <?= htmlspecialchars($right['icon']) ?>"></i>
                                    <strong><?= htmlspecialchars($right['title']) ?></strong>
                                    <p><?= htmlspecialchars($right['description']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="exercise-rights">
                            <div class="exercise-icon"><i class="fas fa-envelope"></i></div>
                            <div class="exercise-content">
                                <h4>Como exercer os seus direitos?</h4>
                                <p>
                                    Envie um e-mail para <strong><?= htmlspecialchars($contactEmail) ?></strong> 
                                    com o assunto "Solicitação RGPD" e descreva o seu pedido. 
                                    Responderemos em até 15 dias úteis.
                                </p>
                                <a href="<?= url('contact') ?>" class="btn-exercise">
                                    <i class="fas fa-paper-plane"></i> Formulário de Solicitação
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 5 - SEGURANÇA DOS DADOS -->
                <div id="section5" class="privacy-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">05</span> Segurança dos Dados</h2>
                        <button class="copy-link" data-section="section5" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Adotamos rigorosas medidas de segurança para proteger os seus dados:</p>
                        
                        <div class="security-list">
                            <div class="security-item">
                                <i class="fas fa-lock"></i>
                                <div>
                                    <strong>Criptografia</strong>
                                    <p>AES-256 em repouso e TLS 1.3 em trânsito</p>
                                </div>
                            </div>
                            <div class="security-item">
                                <i class="fas fa-key"></i>
                                <div>
                                    <strong>Autenticação Multifator (MFA)</strong>
                                    <p>Opcional para todos os utilizadores</p>
                                </div>
                            </div>
                            <div class="security-item">
                                <i class="fas fa-shield-haltered"></i>
                                <div>
                                    <strong>Firewalls e WAF</strong>
                                    <p>Proteção contra ataques e acessos não autorizados</p>
                                </div>
                            </div>
                            <div class="security-item">
                                <i class="fas fa-backup"></i>
                                <div>
                                    <strong>Backups Criptografados</strong>
                                    <p>Backups diários com retenção de 30 dias</p>
                                </div>
                            </div>
                            <div class="security-item">
                                <i class="fas fa-users"></i>
                                <div>
                                    <strong>Controlo de Acesso</strong>
                                    <p>Princípio do menor privilégio para funcionários</p>
                                </div>
                            </div>
                            <div class="security-item">
                                <i class="fas fa-virus"></i>
                                <div>
                                    <strong>Monitorização 24/7</strong>
                                    <p>Detecção de ameaças em tempo real</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="certification-badge">
                            <i class="fas fa-certificate"></i>
                            <div>
                                <strong>Certificações e Conformidades</strong>
                                <p>ISO 27001 • SOC 2 Type II • PCI DSS Level 1 • RGPD • LGPD</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!--SECÇÃO 6 - RETENÇÃO DE DADOS -->
                <div id="section6" class="privacy-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">06</span> Retenção de Dados</h2>
                        <button class="copy-link" data-section="section6" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Mantemos os seus dados pelo tempo necessário para as finalidades descritas:</p>
                        
                        <div class="table-wrapper">
                            <table class="retention-table">
                                <thead>
                                    <tr>
                                        <th>Tipo de Dado</th>
                                        <th>Período de Retenção</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Dados de cadastro e contrato</td>
                                        <td>5 anos após o término do contrato</td>
                                    </tr>
                                    <tr>
                                        <td>Histórico de localização da frota</td>
                                        <td>12 meses (configurável pelo cliente)</td>
                                    </tr>
                                    <tr>
                                        <td>Logs de acesso</td>
                                        <td>6 meses</td>
                                    </tr>
                                    <tr>
                                        <td>Dados financeiros / fiscais</td>
                                        <td>10 anos (obrigação legal - Portugal)</td>
                                    </tr>
                                    <tr>
                                        <td>Dados de suporte e tickets</td>
                                        <td>3 anos após resolução</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 7 - COOKIES E TECNOLOGIAS -->
                <div id="section7" class="privacy-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">07</span> Cookies e Tecnologias</h2>
                        <button class="copy-link" data-section="section7" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Utilizamos cookies e tecnologias similares para melhorar a sua experiência:</p>
                        
                        <div class="table-wrapper">
                            <table class="cookies-table">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Finalidade</th>
                                        <th>Duração</th>
                                        <th>Obrigatório</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Essenciais</td>
                                        <td>Funcionamento básico da plataforma</td>
                                        <td>Sessão</td>
                                        <td><i class="fas fa-check-circle text-success"></i> Sim</td>
                                    </tr>
                                    <tr>
                                        <td>Preferências</td>
                                        <td>Lembrar configurações e idioma</td>
                                        <td>1 ano</td>
                                        <td><i class="fas fa-circle text-muted"></i> Não</td>
                                    </tr>
                                    <tr>
                                        <td>Analíticos</td>
                                        <td>Compreender utilização da plataforma</td>
                                        <td>2 anos</td>
                                        <td><i class="fas fa-circle text-muted"></i> Não</td>
                                    </tr>
                                    <tr>
                                        <td>Marketing</td>
                                        <td>Anúncios personalizados</td>
                                        <td>3 meses</td>
                                        <td><i class="fas fa-circle text-muted"></i> Não</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="cookie-note">
                            <i class="fas fa-cookie-bite"></i>
                            <span>
                                Pode gerir as suas preferências de cookies através do nosso 
                                <a href="#" id="openCookieSettings" class="cookie-settings-link">sistema de consentimento</a>.
                            </span>
                        </div>
                    </div>
                </div>

                <!--  SECÇÃO 8 - DADOS DE MENORES -->
                <div id="section8" class="privacy-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">08</span> Dados de Menores</h2>
                        <button class="copy-link" data-section="section8" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>
                            A CARDOXIS não coleta intencionalmente dados de menores de 16 anos. A nossa plataforma 
                            é destinada exclusivamente para uso empresarial por maiores de idade.
                        </p>
                        <div class="info-note info-note-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>
                                Se é responsável por um menor e acredita que ele nos forneceu dados pessoais, 
                                entre em contacto para que possamos removê-los imediatamente.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 9 - TRANSFERÊNCIA INTERNACIONAL DE DADOS -->
                <div id="section9" class="privacy-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">09</span> Transferência Internacional de Dados</h2>
                        <button class="copy-link" data-section="section9" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>
                            Os seus dados podem ser transferidos e armazenados em servidores localizados na 
                            União Europeia (prioritariamente Portugal, Alemanha e Irlanda) ou nos Estados Unidos, 
                            sempre em conformidade com as leis aplicáveis.
                        </p>
                        <p>
                            Para transferências para países com nível de proteção inadequado, adotamos garantias 
                            apropriadas, como as Cláusulas Contratuais Padrão da Comissão Europeia (SCCs).
                        </p>
                        <div class="eu-flag-note">
                            <i class="fas fa-flag-eu"></i>
                            <span>Dados de clientes europeus permanecem prioritariamente na União Europeia.</span>
                        </div>
                    </div>
                </div>

                <!--  SECÇÃO 10 - ALTERAÇÕES NESTA POLÍTICA -->
                <div id="section10" class="privacy-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">10</span> Alterações nesta Política</h2>
                        <button class="copy-link" data-section="section10" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Podemos atualizar esta política periodicamente para refletir mudanças nas nossas práticas ou requisitos legais.</p>
                        <ul class="policy-list">
                            <li><i class="fas fa-envelope"></i> Alterações materiais serão comunicadas com 30 dias de antecedência por e-mail.</li>
                            <li><i class="fas fa-globe"></i> A versão mais recente estará sempre disponível nesta página.</li>
                            <li><i class="fas fa-history"></i> A data de "última atualização" indica quando a política foi revista.</li>
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

                <!-- SECÇÃO 11 - CONTACTO E ENCARREGADO (DPO) -->
                <div id="section11" class="privacy-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">11</span> Contacto e Encarregado (DPO)</h2>
                        <button class="copy-link" data-section="section11" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Se tiver dúvidas sobre esta política ou sobre como tratamos os seus dados, entre em contacto:</p>
                        
                        <div class="contact-dpo">
                            <div class="dpo-card">
                                <i class="fas fa-user-shield"></i>
                                <div>
                                    <strong>Encarregado de Proteção de Dados (DPO)</strong>
                                    <p>Dra. Ana Rodrigues - Especialista em Privacidade</p>
                                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($contactEmail) ?></p>
                                    <p><i class="fas fa-phone"></i> +351 234 567 891</p>
                                </div>
                            </div>
                            <div class="company-card">
                                <i class="fas fa-building"></i>
                                <div>
                                    <strong>CARDOXIS - Sede</strong>
                                    <p>Av. da Liberdade, 245</p>
                                    <p>1250-143 Lisboa, Portugal</p>
                                    <p><i class="fas fa-envelope"></i> privacy@cardoxis.com</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="complaint-info">
                            <h4><i class="fas fa-balance-scale"></i> Direito de Reclamação</h4>
                            <p>Caso entenda que os seus direitos não foram respeitados, pode registrar uma reclamação junto à autoridade de controlo:</p>
                            <ul>
                                <li><strong>Comissão Nacional de Proteção de Dados (CNPD) - Portugal</strong></li>
                                <li><strong>Autoridade Nacional de Proteção de Dados (ANPD) - Brasil</strong></li>
                                <li><strong>European Data Protection Board (EDPB)</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!--  SECÇÃO DE ACEITAÇÃO -->
    <section class="privacy-acceptance" id="privacy-acceptance">
        <div class="container">
            <div class="acceptance-card" data-aos="fade-up">
                <div class="acceptance-icon">
                    <i class="fas fa-hand-peace"></i>
                </div>
                <h3>Ao utilizar a plataforma CARDOXIS</h3>
                <p>
                    Você concorda com os termos desta Política de Privacidade e com o tratamento dos 
                    seus dados pessoais conforme descrito.
                </p>
                <div class="acceptance-buttons">
                    <a href="<?= url('register') ?>" class="btn-accept">
                        <i class="fas fa-check"></i> Concordar e Continuar
                    </a>
                    <a href="<?= url('contact') ?>" class="btn-contact">
                        <i class="fas fa-headset"></i> Falar com DPO
                    </a>
                </div>
                <div class="acceptance-date">
                    <i class="fas fa-history"></i> Esta versão da política está em vigor desde <?= htmlspecialchars($lastUpdated) ?>
                </div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="<?= asset('js/landing/privacy.js') ?>"></script>
<?php include_once __DIR__ . '/../layouts/partials/scripts.php'; ?>

</body>
</html>