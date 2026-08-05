<?php
/**
 * CARDOXIS - Termos de Uso RF
 */

// Carregar configurações
require_once __DIR__ . '/../layouts/config.php';
require_once __DIR__ . '/../layouts/helpers.php';

// CONFIGURAÇÕES DA PÁGINA

$pageTitle = "Termos de Uso - CARDOXIS | Plataforma de Gestão de Frota com IA";
$pageDescription = "Termos de Uso da plataforma CARDOXIS. Conheça as condições gerais, direitos e deveres ao utilizar a nossa plataforma de gestão de frotas com IA.";
$lastUpdated = "15 de Janeiro de 2024";
$currentVersion = "2.0";

// ÍNDICE DE NAVEGAÇÃO

$tocSections = [
    ['id' => 'section1', 'number' => '01', 'title' => 'Aceitação dos Termos'],
    ['id' => 'section2', 'number' => '02', 'title' => 'Cadastro e Conta'],
    ['id' => 'section3', 'number' => '03', 'title' => 'Utilização da Plataforma'],
    ['id' => 'section4', 'number' => '04', 'title' => 'Planos e Pagamentos'],
    ['id' => 'section5', 'number' => '05', 'title' => 'Condutas Proibidas'],
    ['id' => 'section6', 'number' => '06', 'title' => 'Propriedade Intelectual'],
    ['id' => 'section7', 'number' => '07', 'title' => 'Suporte e Disponibilidade'],
    ['id' => 'section8', 'number' => '08', 'title' => 'Limitação de Responsabilidade'],
    ['id' => 'section9', 'number' => '09', 'title' => 'Suspensão e Cancelamento'],
    ['id' => 'section10', 'number' => '10', 'title' => 'Alterações nos Termos'],
    ['id' => 'section11', 'number' => '11', 'title' => 'Lei Aplicável'],
    ['id' => 'section12', 'number' => '12', 'title' => 'Contacto']
];

// PLANOS E PREÇOS

$pricingPlans = [
    [
        'name' => 'Básico',
        'price' => 'Demonstração',
        'vehicles' => 'Até 10 veículos',
        'features' => 'Funcionalidades essenciais'
    ],
    [
        'name' => 'Profissional',
        'price' => 'Demonstração',
        'vehicles' => 'Até 50 veículos',
        'features' => 'Recursos avançados + API'
    ],
    [
        'name' => 'Enterprise',
        'price' => 'Demonstração',
        'vehicles' => 'Veículos ilimitados',
        'features' => 'Todos os recursos + suporte prioritário'
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
    <meta property="og:url" content="<?= url('terms') ?>">
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
    <link rel="stylesheet" href="<?= asset('css/landing/terms.css') ?>">
</head>
<body>

<!-- NAVBAR  -->
<?php include_once __DIR__ . '/../layouts/partials/navbar.php'; ?>

<!--  CONTEÚDO PRINCIPAL -->
<main>

    <!-- SECÇÃO HERO -->
    <section class="terms-hero" id="terms-hero">
        <div class="container">
            <div class="terms-hero-content" data-aos="fade-up">
                <div class="hero-badge">
                    <span><i class="fas fa-file-contract"></i> Termos e Condições</span>
                </div>
                <h1 class="terms-hero-title">
                    Termos de<br>
                    <span class="gradient-text">Uso</span>
                </h1>
                <p class="terms-hero-description">
                    Leia atentamente os termos e condições que regem a utilização da plataforma CARDOXIS.
                    Ao utilizar os nossos serviços, você concorda com estes termos.
                </p>
                <div class="terms-hero-meta">
                    <span><i class="fas fa-calendar-alt"></i> Última atualização: <?= htmlspecialchars($lastUpdated) ?></span>
                    <span><i class="fas fa-code-branch"></i> Versão <?= htmlspecialchars($currentVersion) ?></span>
                    <span><i class="fas fa-globe"></i> Aplicável globalmente</span>
                    <span><i class="fas fa-check-circle"></i> Em vigor imediatamente</span>
                </div>
            </div>
        </div>
        
        <!-- Elementos decorativos -->
        <div class="terms-hero-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </section>

    <!-- SECÇÃO DE AÇÕES (IMPRIMIR, PDF, COPIAR)  -->
    <section class="terms-actions-section">
        <div class="container">
            <div class="terms-actions" data-aos="fade-up">
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

    <!--  SECÇÃO SUMÁRIO / ÍNDICE -->
    <section class="terms-summary" id="terms-summary">
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

    <!-- CONTEÚDO DOS TERMOS DE USO -->
    <section class="terms-content" id="terms-content">
        <div class="container">
            <div class="terms-container" id="termsContent" data-aos="fade-up">
                
                <!-- SECÇÃO 1 - ACEITAÇÃO DOS TERMOS -->
                <div id="section1" class="terms-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">01</span> Aceitação dos Termos</h2>
                        <button class="copy-link" data-section="section1" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>
                            Ao aceder ou utilizar a plataforma CARDOXIS, você declara que leu, compreendeu e 
                            concorda com todos os termos e condições estabelecidos neste documento. 
                            Se não concordar com qualquer parte destes termos, não está autorizado a utilizar 
                            os nossos serviços.
                        </p>
                        <div class="info-note">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>
                                <strong>Importante:</strong> Estes Termos de Uso constituem um acordo legal 
                                vinculativo entre você e a CARDOXIS.
                            </span>
                        </div>
                    </div>
                </div>

                <!--SECÇÃO 2 - CADASTRO E CONTA -->
                <div id="section2" class="terms-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">02</span> Cadastro e Conta</h2>
                        <button class="copy-link" data-section="section2" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Para utilizar a nossa plataforma, é necessário criar uma conta fornecendo informações precisas e atualizadas.</p>
                        
                        <div class="info-grid">
                            <div class="info-card-mini">
                                <div class="info-icon"><i class="fas fa-user"></i></div>
                                <h4>Responsabilidades do Utilizador</h4>
                                <ul>
                                    <li>Fornecer informações verdadeiras e completas</li>
                                    <li>Manter os seus dados atualizados</li>
                                    <li>Não compartilhar credenciais de acesso</li>
                                    <li>Responsabilizar-se por atividades na sua conta</li>
                                    <li>Notificar acesso não autorizado imediatamente</li>
                                </ul>
                            </div>
                            <div class="info-card-mini">
                                <div class="info-icon"><i class="fas fa-shield-alt"></i></div>
                                <h4>Segurança da Conta</h4>
                                <ul>
                                    <li>Utilize uma palavra-passe forte e única</li>
                                    <li>Ative a autenticação em dois fatores</li>
                                    <li>Nunca compartilhe a sua palavra-passe</li>
                                    <li>Faça logout em dispositivos compartilhados</li>
                                    <li>Mantenha o seu e-mail de recuperação atualizado</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="info-note info-note-warning">
                            <i class="fas fa-lock"></i>
                            <span>
                                A CARDOXIS não se responsabiliza por perdas decorrentes de acesso não autorizado 
                                devido a negligência do utilizador na proteção das suas credenciais.
                            </span>
                        </div>
                    </div>
                </div>

                <!--  SECÇÃO 3 - UTILIZAÇÃO DA PLATAFORMA -->
                <div id="section3" class="terms-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">03</span> Utilização da Plataforma</h2>
                        <button class="copy-link" data-section="section3" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>A plataforma CARDOXIS é uma ferramenta de gestão de frotas que utiliza inteligência artificial para:</p>
                        
                        <div class="purposes-grid">
                            <div class="purpose-item">
                                <i class="fas fa-chart-line"></i>
                                <div>
                                    <strong>Monitorização em Tempo Real</strong>
                                    <p>Acompanhamento da localização e estado dos veículos.</p>
                                </div>
                            </div>
                            <div class="purpose-item">
                                <i class="fas fa-brain"></i>
                                <div>
                                    <strong>Análise Preditiva</strong>
                                    <p>Previsão de manutenções e otimização de rotas.</p>
                                </div>
                            </div>
                            <div class="purpose-item">
                                <i class="fas fa-file-alt"></i>
                                <div>
                                    <strong>Gestão de Documentos</strong>
                                    <p>Armazenamento e gerenciamento de documentação da frota.</p>
                                </div>
                            </div>
                            <div class="purpose-item">
                                <i class="fas fa-chart-bar"></i>
                                <div>
                                    <strong>Relatórios e Analytics</strong>
                                    <p>Geração de relatórios gerenciais e indicadores de performance.</p>
                                </div>
                            </div>
                        </div>
                        
                        <p>Você concorda em utilizar a plataforma apenas para as finalidades permitidas e de acordo com estes Termos.</p>
                    </div>
                </div>

                <!-- SECÇÃO 4 - PLANOS E PAGAMENTOS -->
                <div id="section4" class="terms-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">04</span> Planos e Pagamentos</h2>
                        <button class="copy-link" data-section="section4" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>A CARDOXIS oferece diferentes planos de assinatura para atender às necessidades da sua frota.</p>
                        
                        <div class="table-wrapper">
                            <table class="pricing-table">
                                <thead>
                                    <tr>
                                        <th>Plano</th>
                                        <th>Preço Mensal</th>
                                        <th>Veículos</th>
                                        <th>Recursos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pricingPlans as $plan): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($plan['name']) ?></strong></td>
                                            <td><?= htmlspecialchars($plan['price']) ?></td>
                                            <td><?= htmlspecialchars($plan['vehicles']) ?></td>
                                            <td><?= htmlspecialchars($plan['features']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="payment-info">
                            <h4><i class="fas fa-credit-card"></i> Condições de Pagamento</h4>
                            <ul>
                                <li>Os pagamentos são processados mensalmente ou anualmente, conforme o plano escolhido.</li>
                                <li>Oferecemos 15% de desconto em planos anuais.</li>
                                <li>O não pagamento na data de vencimento poderá resultar na suspensão do serviço.</li>
                                <li>Não oferecemos reembolso de valores já pagos, exceto quando exigido por lei.</li>
                                <li>Pode cancelar a sua assinatura a qualquer momento, com efeito no próximo ciclo de cobrança.</li>
                            </ul>
                        </div>
                        
                        <div class="info-note">
                            <i class="fas fa-info-circle"></i>
                            <span>Período de teste gratuito de 14 dias disponível para novos utilizadores, sem necessidade de cartão de crédito.</span>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 5 - CONDUTAS PROIBIDAS -->
                <div id="section5" class="terms-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">05</span> Condutas Proibidas</h2>
                        <button class="copy-link" data-section="section5" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>É expressamente proibido utilizar a plataforma CARDOXIS para:</p>
                        
                        <div class="prohibited-list">
                            <div class="prohibited-item">
                                <i class="fas fa-ban"></i>
                                <div>
                                    <strong>Atividades Ilegais</strong>
                                    <p>Praticar qualquer atividade que viole leis aplicáveis.</p>
                                </div>
                            </div>
                            <div class="prohibited-item">
                                <i class="fas fa-virus"></i>
                                <div>
                                    <strong>Distribuir Malware</strong>
                                    <p>Transmitir vírus, worms, trojans ou qualquer código malicioso.</p>
                                </div>
                            </div>
                            <div class="prohibited-item">
                                <i class="fas fa-hack"></i>
                                <div>
                                    <strong>Acesso Não Autorizado</strong>
                                    <p>Tentar aceder a áreas restritas ou contas de terceiros.</p>
                                </div>
                            </div>
                            <div class="prohibited-item">
                                <i class="fas fa-copy"></i>
                                <div>
                                    <strong>Engenharia Reversa</strong>
                                    <p>Descompilar, fazer engenharia reversa ou copiar o software.</p>
                                </div>
                            </div>
                            <div class="prohibited-item">
                                <i class="fas fa-bug"></i>
                                <div>
                                    <strong>Teste de Vulnerabilidade</strong>
                                    <p>Realizar testes de invasão sem autorização prévia.</p>
                                </div>
                            </div>
                            <div class="prohibited-item">
                                <i class="fas fa-spam"></i>
                                <div>
                                    <strong>Spam e Phishing</strong>
                                    <p>Enviar mensagens não solicitadas ou tentativas de phishing.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-note info-note-warning">
                            <i class="fas fa-gavel"></i>
                            <span>A violação destas condutas poderá resultar no cancelamento imediato da conta e ação legal cabível.</span>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 6 - PROPRIEDADE INTELECTUAL -->
                <div id="section6" class="terms-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">06</span> Propriedade Intelectual</h2>
                        <button class="copy-link" data-section="section6" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>
                            A plataforma CARDOXIS, incluindo seu código-fonte, design, logotipos, marcas e conteúdos, 
                            é protegida por leis de propriedade intelectual.
                        </p>
                        
                        <div class="ip-grid">
                            <div class="ip-card">
                                <i class="fas fa-copyright"></i>
                                <h4>Direitos da CARDOXIS</h4>
                                <p>Todo o software, interfaces, gráficos e funcionalidades são de propriedade exclusiva da CARDOXIS.</p>
                            </div>
                            <div class="ip-card">
                                <i class="fas fa-chart-line"></i>
                                <h4>Os seus Dados</h4>
                                <p>Mantém a propriedade dos dados inseridos na plataforma. A CARDOXIS possui licença para utilizá-los na prestação dos serviços.</p>
                            </div>
                            <div class="ip-card">
                                <i class="fas fa-chart-bar"></i>
                                <h4>Dados Anonimizados</h4>
                                <p>A CARDOXIS pode utilizar dados anonimizados para melhorar os seus algoritmos e gerar insights agregados.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 7 - SUPORTE E DISPONIBILIDADE -->
                <div id="section7" class="terms-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">07</span> Suporte e Disponibilidade</h2>
                        <button class="copy-link" data-section="section7" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Comprometemo-nos a manter a plataforma operacional com alta disponibilidade.</p>
                        
                        <div class="support-grid">
                            <div class="support-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <strong>Disponibilidade</strong>
                                    <p>99.9% de uptime garantido (SLA disponível para planos Enterprise)</p>
                                </div>
                            </div>
                            <div class="support-item">
                                <i class="fas fa-headset"></i>
                                <div>
                                    <strong>Suporte Técnico</strong>
                                    <p>Atendimento via chat, e-mail e telefone em horário comercial</p>
                                </div>
                            </div>
                            <div class="support-item">
                                <i class="fas fa-tools"></i>
                                <div>
                                    <strong>Manutenção Programada</strong>
                                    <p>Notificaremos com 48h de antecedência sobre manutenções</p>
                                </div>
                            </div>
                            <div class="support-item">
                                <i class="fas fa-file-alt"></i>
                                <div>
                                    <strong>Base de Conhecimento</strong>
                                    <p>Documentação completa e tutoriais disponíveis 24/7</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-note">
                            <i class="fas fa-chart-line"></i>
                            <span>Monitorizamos a plataforma 24/7 para garantir a melhor experiência aos nossos utilizadores.</span>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 8 - LIMITAÇÃO DE RESPONSABILIDADE -->
                <div id="section8" class="terms-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">08</span> Limitação de Responsabilidade</h2>
                        <button class="copy-link" data-section="section8" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Na máxima extensão permitida por lei, a CARDOXIS não será responsável por:</p>
                        
                        <ul>
                            <li>Danos indiretos, incidentais ou consequenciais decorrentes do uso da plataforma</li>
                            <li>Perda de lucros, receitas ou oportunidades de negócio</li>
                            <li>Interrupções não programadas devido a eventos de força maior</li>
                            <li>Decisões tomadas com base em análises geradas pela plataforma</li>
                            <li>Ações de terceiros que acedam à sua conta com as suas credenciais</li>
                        </ul>
                        
                        <div class="info-note info-note-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>
                                A plataforma fornece ferramentas de apoio à decisão, mas a responsabilidade final 
                                sobre as decisões operacionais é do utilizador.
                            </span>
                        </div>
                    </div>
                </div>

                <!--SECÇÃO 9 - SUSPENSÃO E CANCELAMENTO -->
                <div id="section9" class="terms-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">09</span> Suspensão e Cancelamento</h2>
                        <button class="copy-link" data-section="section9" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>A CARDOXIS reserva-se o direito de suspender ou cancelar a sua conta nas seguintes situações:</p>
                        
                        <ul>
                            <li><strong>Violação dos Termos:</strong> Identificação de conduta proibida ou violação das políticas</li>
                            <li><strong>Inadimplência:</strong> Pagamento em atraso por mais de 15 dias</li>
                            <li><strong>Atividade Fraudulenta:</strong> Uso da plataforma para fins ilegais ou fraudulentos</li>
                            <li><strong>Solicitação Judicial:</strong> Por determinação de autoridade competente</li>
                        </ul>
                        
                        <p>Em caso de cancelamento por sua iniciativa, poderá exportar os seus dados nos formatos disponíveis (CSV, JSON, PDF).</p>
                        
                        <div class="info-note">
                            <i class="fas fa-database"></i>
                            <span>Os seus dados ficarão disponíveis para exportação por 30 dias após o cancelamento da conta.</span>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 10 - ALTERAÇÕES NOS TERMOS -->
                <div id="section10" class="terms-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">10</span> Alterações nos Termos</h2>
                        <button class="copy-link" data-section="section10" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Podemos modificar estes Termos de Uso periodicamente. Quando houver alterações materiais, notificaremos através de:</p>
                        
                        <ul>
                            <li>E-mail cadastrado (com 30 dias de antecedência)</li>
                            <li>Notificação na plataforma</li>
                            <li>Banner informativo ao aceder ao sistema</li>
                        </ul>
                        
                        <p>O uso continuado da plataforma após as alterações constitui aceitação dos novos termos.</p>
                        
                        <div class="version-history">
                            <h4><i class="fas fa-code-branch"></i> Histórico de Versões</h4>
                            <ul>
                                <li><strong>Versão 2.0</strong> - 15/01/2024 - Atualização completa dos termos</li>
                                <li><strong>Versão 1.0</strong> - 01/01/2023 - Lançamento inicial</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 11 - LEI APLICÁVEL -->
                <div id="section11" class="terms-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">11</span> Lei Aplicável</h2>
                        <button class="copy-link" data-section="section11" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>
                            Estes Termos são regidos pelas leis da República Portuguesa. Qualquer disputa será 
                            submetida ao foro da comarca de Lisboa, Portugal, com renúncia expressa a qualquer 
                            outro, por mais privilegiado que seja.
                        </p>
                        
                        <div class="eu-note">
                            <i class="fas fa-flag-eu"></i>
                            <span>
                                Para utilizadores na União Europeia, direitos adicionais podem ser aplicáveis 
                                sob o Regulamento Geral de Proteção de Dados (RGPD).
                            </span>
                        </div>
                    </div>
                </div>

                <!-- SECÇÃO 12 - CONTACTO -->
                <div id="section12" class="terms-section">
                    <div class="section-header-inline">
                        <h2><span class="section-number">12</span> Contacto</h2>
                        <button class="copy-link" data-section="section12" title="Copiar link para esta secção">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <p>Se tiver dúvidas sobre estes Termos de Uso, entre em contacto connosco:</p>
                        
                        <div class="contact-card">
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <strong>E-mail</strong>
                                    <p>legal@cardoxis.com</p>
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
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <strong>Endereço</strong>
                                    <p>Av. da Liberdade, 245, 1250-143 Lisboa, Portugal</p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <strong>Horário de Atendimento</strong>
                                    <p>Segunda a Sexta, 9h às 18h (horário de Lisboa)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- SECÇÃO DE ACEITAÇÃO -->
    <section class="terms-acceptance" id="terms-acceptance">
        <div class="container">
            <div class="acceptance-card" data-aos="fade-up">
                <div class="acceptance-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>Ao utilizar a plataforma CARDOXIS</h3>
                <p>
                    Você declara que leu, compreendeu e concorda com todos os termos e condições 
                    estabelecidos neste documento.
                </p>
                <div class="acceptance-buttons">
                    <a href="<?= url('register') ?>" class="btn-accept">
                        <i class="fas fa-check"></i> Aceito e Quero Começar
                    </a>
                    <a href="<?= url('contact') ?>" class="btn-contact">
                        <i class="fas fa-headset"></i> Tenho Dúvidas
                    </a>
                </div>
                <div class="acceptance-date">
                    <i class="fas fa-history"></i> Estes Termos estão em vigor desde <?= htmlspecialchars($lastUpdated) ?>
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
<script src="<?= asset('js/landing/terms.js') ?>"></script>
<?php include_once __DIR__ . '/../layouts/partials/scripts.php'; ?>

</body>
</html>