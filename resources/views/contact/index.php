<?php
/**
 * CARDOXIS - Página de Contacto RF
 */

// Carregar configurações
require_once __DIR__ . '/../layouts/config.php';
require_once __DIR__ . '/../layouts/helpers.php';

// CONFIGURAÇÕES DA PÁGINA

$pageTitle = "Contacto - CARDOXIS | Suporte e Informações";
$pageDescription = "Entre em contacto com a CARDOXIS. Suporte técnico, comercial e informações sobre gestão de frotas com IA.";

// Mensagens de status
$success = isset($_GET['success']) ? $_GET['success'] : null;
$error = isset($_GET['error']) ? $_GET['error'] : null;

// DADOS DA EMPRESA PARA CONTACTO

$contactInfo = [
    'phone' => '+351 300 123 456',
    'phone_display' => '300 123 456',
    'email' => 'geral@cardoxis.com',
    'support_email' => 'suporte@cardoxis.com',
    'address' => 'Avenida da Liberdade, 245',
    'postal_code' => '1250-143',
    'city' => 'Lisboa',
    'country' => 'Portugal',
    'map_lat' => 38.7223,
    'map_lng' => -9.1393,
    'working_hours' => 'Segunda a Sexta, 9h - 18h',
    'working_hours_saturday' => 'Sábado, 9h - 13h'
];

// PERGUNTAS FREQUENTES

$faqs = [
    [
        'question' => 'Como funciona o período de teste gratuito?',
        'answer' => 'Oferecemos 14 dias de teste gratuito com acesso completo a todas as funcionalidades da plataforma. Não é necessário cartão de crédito e pode cancelar a qualquer momento.'
    ],
    [
        'question' => 'Qual o tempo médio de implementação?',
        'answer' => 'A implementação geralmente leva entre 2 a 5 dias úteis, dependendo do tamanho da sua frota e necessidades específicas. A nossa equipa oferece suporte completo durante todo o processo.'
    ],
    [
        'question' => 'A plataforma tem suporte em português?',
        'answer' => 'Sim! Oferecemos suporte completo em português (PT-PT e PT-BR) através de chat, e-mail e telefone, de segunda a sexta-feira, das 9h às 18h.'
    ],
    [
        'question' => 'É possível integrar com outros sistemas?',
        'answer' => 'Sim, a nossa plataforma possui API completa que permite integração com ERPs, sistemas de gestão e outras ferramentas que a sua empresa já utiliza.'
    ],
    [
        'question' => 'Quais são as formas de pagamento?',
        'answer' => 'Aceitamos cartões de crédito (Visa, Mastercard, AMEX), transferência bancária e faturamento para empresas. Planos anuais têm desconto especial.'
    ],
    [
        'question' => 'Como é a segurança dos meus dados?',
        'answer' => 'Utilizamos criptografia de ponta a ponta, servidores em cloud com certificação ISO 27001 e seguimos as diretrizes da RGPD para proteção dos seus dados.'
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
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= url('contact') ?>">
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Leaflet CSS para o mapa -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= asset('css/landing/landing.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/landing/contact.css') ?>">
</head>
<body>

<!-- NAVBAR -->
<?php include_once __DIR__ . '/../layouts/partials/navbar.php'; ?>

<!-- CONTEÚDO PRINCIPAL -->
<main>

    <!--  SECÇÃO HERO - CONTACTO -->
    <section class="contact-hero" id="contact-hero">
        <div class="container">
            <div class="contact-hero-content" data-aos="fade-up">
                <div class="hero-badge">
                    <span><i class="fas fa-headset"></i> Suporte 24/7</span>
                </div>
                <h1 class="contact-hero-title">
                    Estamos aqui para<br>
                    <span class="gradient-text">ajudar</span>
                </h1>
                <p class="contact-hero-description">
                    Tire as suas dúvidas, solicite uma demonstração ou agende uma consultoria especializada.
                    A nossa equipa está pronta para atender.
                </p>
            </div>
        </div>
        
        <!-- Elementos decorativos -->
        <div class="contact-hero-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </section>

    <!-- MENSAGENS DE ALERTA -->
    <?php if ($success == '1'): ?>
        <div class="alert alert-success" id="alertMessage">
            <i class="fas fa-check-circle"></i>
            <span>Mensagem enviada com sucesso! Entraremos em contacto em breve.</span>
            <button class="alert-close" onclick="this.parentElement.style.display='none'" aria-label="Fechar alerta">&times;</button>
        </div>
    <?php elseif ($error == '1'): ?>
        <div class="alert alert-error" id="alertMessage">
            <i class="fas fa-exclamation-circle"></i>
            <span>Erro ao enviar mensagem. Por favor, tente novamente ou contacte-nos por telefone.</span>
            <button class="alert-close" onclick="this.parentElement.style.display='none'" aria-label="Fechar alerta">&times;</button>
        </div>
    <?php endif; ?>

    <!-- SECÇÃO PRINCIPAL DE CONTACTO -->
    <section class="contact-main" id="contact-main">
        <div class="container">
            <div class="contact-grid">
                
                <!-- Informações de Contacto -->
                <div class="contact-info" data-aos="fade-right">
                    <div class="section-badge">Informações de Contacto</div>
                    <h2 class="section-title">
                        Vamos<br>
                        <span class="gradient-text">conversar?</span>
                    </h2>
                    <p class="section-description">
                        Estamos disponíveis através de diversos canais para melhor atender às suas necessidades.
                    </p>
                    
                    <div class="info-cards">
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="info-content">
                                <h4>Telefone</h4>
                                <p><a href="tel:<?= htmlspecialchars($contactInfo['phone']) ?>"><?= htmlspecialchars($contactInfo['phone']) ?></a></p>
                                <p class="info-small">Segunda a Sexta, 9h - 18h</p>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <h4>E-mail</h4>
                                <p><a href="mailto:<?= htmlspecialchars($contactInfo['email']) ?>"><?= htmlspecialchars($contactInfo['email']) ?></a></p>
                                <p class="info-small"><a href="mailto:<?= htmlspecialchars($contactInfo['support_email']) ?>"><?= htmlspecialchars($contactInfo['support_email']) ?></a></p>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-content">
                                <h4>Localização</h4>
                                <p><?= htmlspecialchars($contactInfo['address']) ?></p>
                                <p class="info-small"><?= htmlspecialchars($contactInfo['postal_code']) ?>, <?= htmlspecialchars($contactInfo['city']) ?></p>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <h4>Horário de Atendimento</h4>
                                <p><?= htmlspecialchars($contactInfo['working_hours']) ?></p>
                                <p class="info-small"><?= htmlspecialchars($contactInfo['working_hours_saturday']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Formulário de Contacto -->
                <div class="contact-form-wrapper" data-aos="fade-left">
                    <div class="form-card">
                        <h3><i class="fas fa-paper-plane"></i> Envie-nos uma mensagem</h3>
                        <p>Preencha o formulário abaixo e entraremos em contacto consigo o mais breve possível.</p>
                        
                        <form action="/cardoxis/contact-api.php" method="POST" class="contact-form" id="contactForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name"><i class="fas fa-user"></i> Nome Completo *</label>
                                    <input type="text" id="name" name="name" required placeholder="Seu nome completo" autocomplete="name">
                                </div>
                                <div class="form-group">
                                    <label for="email"><i class="fas fa-envelope"></i> E-mail *</label>
                                    <input type="email" id="email" name="email" required placeholder="seu@email.com" autocomplete="email">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone"><i class="fas fa-phone"></i> Telefone</label>
                                    <input type="tel" id="phone" name="phone" placeholder="+351 912 345 678" autocomplete="tel">
                                </div>
                                <div class="form-group">
                                    <label for="subject"><i class="fas fa-tag"></i> Assunto *</label>
                                    <select id="subject" name="subject" required>
                                        <option value="">Selecione o assunto</option>
                                        <option value="comercial">Informações Comerciais</option>
                                        <option value="suporte">Suporte Técnico</option>
                                        <option value="demonstracao">Solicitar Demonstração</option>
                                        <option value="parceria">Parcerias</option>
                                        <option value="outro">Outro Assunto</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="message"><i class="fas fa-comment"></i> Mensagem *</label>
                                <textarea id="message" name="message" rows="5" required placeholder="Digite a sua mensagem aqui..."></textarea>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="privacy" required>
                                    <span class="checkmark"></span>
                                    Concordo com a <a href="<?= url('privacy') ?>" target="_blank">Política de Privacidade</a> e autorizo o tratamento dos meus dados.
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-submit" id="contactSubmitBtn">
                                <i class="fas fa-paper-plane"></i> Enviar Mensagem
                            </button>
                            
                            <p class="form-note">
                                <i class="fas fa-lock"></i> Os seus dados estão seguros e não serão partilhados com terceiros.
                            </p>
                        </form>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <!-- SECÇÃO MAPA -->
    <section class="map-section" id="map-section">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Nossa Localização</div>
                <h2>Encontre-nos<br><span class="gradient-text">no mapa</span></h2>
                <p>Visite a nossa sede em Lisboa e conheça a nossa equipa pessoalmente</p>
            </div>
            
            <div class="map-container" data-aos="fade-up" data-aos-delay="100">
                <div id="contactMap" class="map" data-lat="<?= htmlspecialchars($contactInfo['map_lat']) ?>" data-lng="<?= htmlspecialchars($contactInfo['map_lng']) ?>"></div>
                <div class="map-overlay">
                    <div class="map-address">
                        <i class="fas fa-building"></i>
                        <div>
                            <strong>CARDOXIS - Sede</strong>
                            <p><?= htmlspecialchars($contactInfo['address']) ?>, <?= htmlspecialchars($contactInfo['postal_code']) ?>, <?= htmlspecialchars($contactInfo['city']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECÇÃO PERGUNTAS FREQUENTES -->
    <section class="faq-section" id="faq">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Dúvidas Frequentes</div>
                <h2>Perguntas<br><span class="gradient-text">frequentes</span></h2>
                <p>Esclareça as principais dúvidas sobre os nossos serviços</p>
            </div>
            
            <div class="faq-grid" data-aos="fade-up">
                <?php foreach ($faqs as $index => $faq): ?>
                    <div class="faq-item <?= $index === 0 ? 'active' : '' ?>" data-aos="fade-up" data-aos-delay="<?= $index * 50 ?>">
                        <div class="faq-question" role="button" tabindex="0" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>">
                            <h4><?= htmlspecialchars($faq['question']) ?></h4>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p><?= htmlspecialchars($faq['answer']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECÇÃO CTA -->
    <section class="cta" id="cta">
        <div class="container">
            <div class="cta-content" data-aos="fade-up">
                <div class="cta-badge">Atendimento Prioritário</div>
                <h2 class="cta-title">
                    Precisa de<br>
                    <span>atendimento imediato?</span>
                </h2>
                <p class="cta-description">
                    Ligue diretamente para a nossa equipa comercial ou solicite um callback
                </p>
                <div class="cta-buttons">
                    <a href="tel:<?= htmlspecialchars($contactInfo['phone']) ?>" class="btn btn-primary-cta btn-large">
                        <i class="fas fa-phone-alt"></i> Ligar Agora
                    </a>
                    <button class="btn btn-outline-light btn-large" id="callbackBtn">
                        <i class="fas fa-headset"></i> Solicitar Callback
                    </button>
                </div>
                <div class="cta-trust">
                    <div class="trust-item">
                        <i class="fas fa-shield-alt"></i> Não requer cartão de crédito
                    </div>
                    <div class="trust-item">
                        <i class="fas fa-clock"></i> Cancele quando quiser
                    </div>
                    <div class="trust-item">
                        <i class="fas fa-lock"></i> Dados protegidos com SSL
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<!-- MODAL DE CALLBACK -->
<div class="callback-modal" id="callbackModal" role="dialog" aria-modal="true" aria-labelledby="callbackModalTitle">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <button class="modal-close" id="closeModalBtn" aria-label="Fechar modal">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-header">
            <span class="header-icon"><i class="fas fa-phone-alt"></i></span>
            <h3 id="callbackModalTitle">Solicitar Callback</h3>
            <p>Deixe os seus dados que retornamos em até 15 minutos</p>
        </div>
        <form action="/cardoxis/contact-api.php?callback=1" method="POST" class="callback-form" id="callbackForm">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            
            <div class="form-group">
                <label for="callbackName">
                    <i class="fas fa-user"></i> Nome completo <span class="required">*</span>
                </label>
                <input type="text" id="callbackName" name="name" required placeholder="Seu nome completo" autocomplete="name">
            </div>
            
            <div class="form-group">
                <label for="callbackEmail">
                    <i class="fas fa-envelope"></i> E-mail <span class="required">*</span>
                </label>
                <input type="email" id="callbackEmail" name="email" required placeholder="seu@email.com" autocomplete="email">
            </div>
            
            <div class="form-group">
                <label for="callbackPhone">
                    <i class="fas fa-phone"></i> Telefone <span class="required">*</span>
                </label>
                <input type="tel" id="callbackPhone" name="phone" required placeholder="+351 912 345 678" autocomplete="tel">
            </div>
            
            <div class="form-group">
                <label for="callbackTime">
                    <i class="fas fa-clock"></i> Melhor horário
                </label>
                <select name="time" id="callbackTime">
                    <option value="Manhã (9h - 12h)">Manhã (9h - 12h)</option>
                    <option value="Tarde (14h - 17h)" selected>Tarde (14h - 17h)</option>
                    <option value="Fim de tarde (17h - 18h)">Fim de tarde (17h - 18h)</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary" id="callbackSubmitBtn">
                <i class="fas fa-phone-alt"></i> Solicitar Callback
            </button>
        </form>
    </div>
</div>

<!--  RODAPÉ -->
<?php include_once __DIR__ . '/../layouts/partials/footer.php'; ?>

<!--  BOTÃO VOLTAR AO TOPO -->
<button class="back-to-top" id="backToTop" title="Voltar ao topo">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- SCRIPTS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= asset('js/landing/contact.js') ?>"></script>
<?php include_once __DIR__ . '/../layouts/partials/scripts.php'; ?>

</body>
</html>