<?php
/**
 * CARDOXIS - Página de Perguntas Frequentes (FAQ)  RF
 */

// Carregar configurações
require_once __DIR__ . '/../layouts/config.php';
require_once __DIR__ . '/../layouts/helpers.php';

// CONFIGURAÇÕES DA PÁGINA

$pageTitle = "FAQ - CARDOXIS | Perguntas Frequentes sobre Gestão de Frota com IA";
$pageDescription = "Encontre respostas para as perguntas mais frequentes sobre a plataforma CARDOXIS de gestão de frotas com inteligência artificial.";

// CATEGORIAS E PERGUNTAS

$faqCategories = [
    'plataforma' => [
        'icon' => 'fa-desktop',
        'title' => 'Sobre a Plataforma',
        'description' => 'Conheça os recursos e funcionalidades da CARDOXIS',
        'questions' => [
            [
                'question' => 'O que é a CARDOXIS?',
                'answer' => 'A CARDOXIS é uma plataforma completa de gestão de frotas que utiliza inteligência artificial para otimizar operações, reduzir custos e aumentar a eficiência. Oferecemos monitoramento em tempo real, análise preditiva, gestão de documentos automatizada e muito mais.'
            ],
            [
                'question' => 'Quais os principais recursos da plataforma?',
                'answer' => 'Nossos principais recursos incluem: Dashboard com IA, monitoramento em tempo real, alertas preditivos, OCR inteligente para documentos, gestão de manutenção, controlo de combustível, relatórios automatizados, aplicativo móvel e API completa para integrações.'
            ],
            [
                'question' => 'A plataforma funciona em qualquer dispositivo?',
                'answer' => 'Sim! A CARDOXIS é totalmente responsiva e funciona em qualquer dispositivo com acesso à internet. Além disso, oferecemos aplicativos nativos para iOS e Android, disponíveis na App Store e Google Play.'
            ],
            [
                'question' => 'A plataforma suporta múltiplos idiomas?',
                'answer' => 'Atualmente oferecemos suporte para Português (PT-PT e PT-BR), Inglês, Espanhol e Francês. Novos idiomas serão adicionados conforme demanda dos clientes.'
            ]
        ]
    ],
    'implementacao' => [
        'icon' => 'fa-rocket',
        'title' => 'Implementação',
        'description' => 'Saiba como começar a usar a CARDOXIS',
        'questions' => [
            [
                'question' => 'Como funciona o período de teste gratuito?',
                'answer' => 'Oferecemos 14 dias de teste gratuito com acesso completo a todas as funcionalidades. Não é necessário cartão de crédito e pode cancelar a qualquer momento. Durante o teste, a nossa equipa oferece suporte completo para garantir a sua experiência.'
            ],
            [
                'question' => 'Qual o tempo médio de implementação?',
                'answer' => 'A implementação geralmente leva entre 2 a 5 dias úteis, dependendo do tamanho da sua frota e necessidades específicas. Para empresas com mais de 100 veículos, recomendamos um planeamento mais detalhado de 7 a 10 dias.'
            ],
            [
                'question' => 'Preciso instalar hardware nos veículos?',
                'answer' => 'Não necessariamente! Oferecemos diferentes opções de integração: uso do aplicativo móvel, integração com rastreadores existentes ou instalação dos nossos dispositivos de telemetria. A nossa equipa avalia o melhor cenário para a sua frota.'
            ],
            [
                'question' => 'Oferecem treinamento para a equipa?',
                'answer' => 'Sim! Incluímos treinamento completo para a sua equipa durante a implementação. Oferecemos materiais de suporte, vídeos tutoriais, webinars ao vivo e suporte contínuo para garantir que todos aproveitem ao máximo a plataforma.'
            ]
        ]
    ],
    'precos' => [
        'icon' => 'fa-tag',
        'title' => 'Planos e Preços',
        'description' => 'Conheça as nossas opções de contratação',
        'questions' => [
            [
                'question' => 'Quais são os planos disponíveis?',
                'answer' => 'Oferecemos 3 planos principais: Básico (até 10 veículos - €99/mês), Profissional (até 50 veículos - €199/mês) e Enterprise (veículos ilimitados - €499/mês). Todos os planos incluem suporte e atualizações.'
            ],
            [
                'question' => 'Há desconto para planos anuais?',
                'answer' => 'Sim! Oferecemos 15% de desconto em contratos anuais e 25% em contratos bienais. Entre em contacto com a nossa equipa comercial para mais detalhes.'
            ],
            [
                'question' => 'Quais formas de pagamento são aceites?',
                'answer' => 'Aceitamos cartões de crédito (Visa, Mastercard, American Express), transferência bancária, PayPal e faturamento para empresas. Para grandes corporações, oferecemos condições especiais de pagamento.'
            ],
            [
                'question' => 'Posso cancelar a qualquer momento?',
                'answer' => 'Sim! Pode cancelar a sua assinatura a qualquer momento, sem multas ou taxas adicionais. O seu acesso continua ativo até ao fim do período já pago.'
            ]
        ]
    ],
    'seguranca' => [
        'icon' => 'fa-shield-alt',
        'title' => 'Segurança e Privacidade',
        'description' => 'Os seus dados protegidos com tecnologia de ponta',
        'questions' => [
            [
                'question' => 'Como protegem os dados da minha empresa?',
                'answer' => 'Utilizamos criptografia de ponta a ponta (AES-256), servidores em cloud com certificação ISO 27001, firewall de aplicação (WAF) e monitorização 24/7 contra ameaças. Todos os dados são armazenados em data centers na Europa.'
            ],
            [
                'question' => 'A CARDOXIS está em conformidade com a RGPD?',
                'answer' => 'Sim! A CARDOXIS está totalmente em conformidade com o Regulamento Geral de Proteção de Dados (RGPD) da UE e com a LGPD do Brasil. Implementamos medidas rigorosas para garantir a privacidade e segurança dos dados dos nossos clientes.'
            ],
            [
                'question' => 'Com que frequência os backups são realizados?',
                'answer' => 'Realizamos backups automáticos diários de todos os dados, com retenção por 30 dias. Os backups são armazenados em locais geograficamente redundantes, garantindo a recuperação em caso de desastre.'
            ],
            [
                'question' => 'A plataforma possui autenticação em dois fatores?',
                'answer' => 'Sim! Oferecemos autenticação em dois fatores (2FA) para todos os utilizadores. Pode ativar via SMS, aplicativo autenticador (Google Authenticator, Microsoft Authenticator) ou e-mail.'
            ]
        ]
    ],
    'suporte' => [
        'icon' => 'fa-headset',
        'title' => 'Suporte e Atendimento',
        'description' => 'Estamos aqui para ajudar',
        'questions' => [
            [
                'question' => 'Quais canais de suporte estão disponíveis?',
                'answer' => 'Oferecemos suporte via chat ao vivo (dentro da plataforma), e-mail (suporte@cardoxis.com), telefone (+351 234 567 890) e WhatsApp. Planos Profissional e Enterprise têm prioridade no atendimento.'
            ],
            [
                'question' => 'Qual o horário de funcionamento do suporte?',
                'answer' => 'O suporte funciona de segunda a sexta, das 9h às 18h (horário de Lisboa). Para clientes Enterprise, oferecemos suporte 24/7, incluindo feriados e fins de semana.'
            ],
            [
                'question' => 'Existe uma base de conhecimento ou documentação?',
                'answer' => 'Sim! Disponibilizamos uma base de conhecimento completa com artigos, tutoriais em vídeo, documentação da API, webinars gravados e guias passo a passo, acessível diretamente pela plataforma.'
            ],
            [
                'question' => 'Qual o tempo médio de resposta do suporte?',
                'answer' => 'O nosso tempo médio de primeiro contacto é de 15 minutos para chat, 2 horas para e-mail e 4 horas para tickets. Problemas críticos são priorizados e resolvidos em até 2 horas.'
            ]
        ]
    ]
];

// RECURSOS ÚTEIS

$resources = [
    [
        'icon' => 'fa-file-alt',
        'title' => 'Guia de Implementação',
        'description' => 'Passo a passo para implementar a CARDOXIS na sua frota',
        'link' => url('docs?article=getting-started'),
        'link_text' => 'Baixar guia',
        'link_icon' => 'fa-download'
    ],
    [
        'icon' => 'fa-video',
        'title' => 'Tutoriais em Vídeo',
        'description' => 'Biblioteca com vídeos tutoriais sobre todas as funcionalidades',
        'link' => url('docs?article=video-tutorials'),
        'link_text' => 'Assistir agora',
        'link_icon' => 'fa-play'
    ],
    [
        'icon' => 'fa-code',
        'title' => 'Documentação API',
        'description' => 'Documentação completa para integrações personalizadas',
        'link' => url('docs?article=api-integration'),
        'link_text' => 'Explorar API',
        'link_icon' => 'fa-external-link-alt'
    ],
    [
        'icon' => 'fa-chart-line',
        'title' => 'Case de Sucesso',
        'description' => 'Veja como empresas transformaram a sua gestão com a CARDOXIS',
        'link' => url('about#testimonials'),
        'link_text' => 'Ler cases',
        'link_icon' => 'fa-arrow-right'
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
    <meta property="og:url" content="<?= url('faq') ?>">
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
    <link rel="stylesheet" href="<?= asset('css/landing/faq.css') ?>">
</head>
<body>

<!-- NAVBAR -->
<?php include_once __DIR__ . '/../layouts/partials/navbar.php'; ?>

<!--  CONTEÚDO PRINCIPAL -->
<main>

    <!-- SECÇÃO HERO -->
    <section class="faq-hero" id="faq-hero">
        <div class="container">
            <div class="faq-hero-content" data-aos="fade-up">
                <div class="hero-badge">
                    <span><i class="fas fa-question-circle"></i> FAQ</span>
                </div>
                <h1 class="faq-hero-title">
                    Perguntas<br>
                    <span class="gradient-text">Frequentes</span>
                </h1>
                <p class="faq-hero-description">
                    Encontre respostas rápidas para as principais dúvidas sobre a plataforma CARDOXIS.
                    Não encontrou o que procura? A nossa equipa está disponível para ajudar.
                </p>
            </div>
        </div>
        
        <!-- Elementos decorativos -->
        <div class="faq-hero-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </section>

    <!-- SECÇÃO DE PESQUISA -->
    <section class="faq-search-section" id="faq-search">
        <div class="container">
            <div class="search-wrapper" data-aos="fade-up">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="faqSearch" placeholder="Pesquisar perguntas frequentes..." autocomplete="off" aria-label="Pesquisar FAQ">
                    <button id="clearSearch" class="clear-search" style="display: none;" aria-label="Limpar pesquisa">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="search-info">
                    <span id="resultCount"></span>
                </div>
            </div>
        </div>
    </section>

    <!-- CATEGORIAS -->
    <section class="faq-categories" id="faq-categories">
        <div class="container">
            <div class="categories-tabs" data-aos="fade-up" role="tablist">
                <button class="category-btn active" data-category="all" role="tab" aria-selected="true">
                    <i class="fas fa-grid"></i> Todas
                </button>
                <?php foreach ($faqCategories as $key => $category): ?>
                    <button class="category-btn" data-category="<?= htmlspecialchars($key) ?>" role="tab" aria-selected="false">
                        <i class="fas <?= htmlspecialchars($category['icon']) ?>"></i> 
                        <?= htmlspecialchars($category['title']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CONTEÚDO FAQ -->
    <section class="faq-content" id="faq-content">
        <div class="container">
            <div class="faq-container" data-aos="fade-up">
                
                <?php foreach ($faqCategories as $key => $category): ?>
                    <!-- Categoria: <?= htmlspecialchars($category['title']) ?> -->
                    <div class="faq-category-group" data-category="<?= htmlspecialchars($key) ?>">
                        <div class="category-header">
                            <i class="fas <?= htmlspecialchars($category['icon']) ?>"></i>
                            <h2><?= htmlspecialchars($category['title']) ?></h2>
                            <p><?= htmlspecialchars($category['description']) ?></p>
                        </div>
                        
                        <div class="faq-grid">
                            <?php foreach ($category['questions'] as $index => $question): ?>
                                <div class="faq-card" data-aos="fade-up" data-aos-delay="<?= $index * 50 ?>">
                                    <div class="faq-question" role="button" tabindex="0" aria-expanded="false">
                                        <h3><?= htmlspecialchars($question['question']) ?></h3>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <p><?= htmlspecialchars($question['answer']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
            </div>
            
            <!-- Mensagem de Nenhum Resultado -->
            <div id="noResults" class="no-results" style="display: none;">
                <i class="fas fa-search"></i>
                <h3>Nenhum resultado encontrado</h3>
                <p>Não encontramos perguntas relacionadas à sua pesquisa.</p>
                <button class="btn btn-outline" id="resetSearch">
                    <i class="fas fa-undo"></i> Limpar pesquisa
                </button>
            </div>
        </div>
    </section>

    <!--  AINDA TEM DÚVIDAS? -->
    <section class="still-questions" id="still-questions">
        <div class="container">
            <div class="questions-card" data-aos="fade-up">
                <div class="questions-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h2>Ainda tem dúvidas?</h2>
                <p>A nossa equipa de especialistas está pronta para ajudar com qualquer questão específica sobre a sua frota.</p>
                <div class="questions-buttons">
                    <a href="<?= url('contact') ?>" class="btn btn-primary">
                        <i class="fas fa-envelope"></i> Falar Connosco
                    </a>
                    <a href="<?= url('contact#demo') ?>" class="btn btn-outline">
                        <i class="fas fa-calendar-alt"></i> Agendar Demo
                    </a>
                </div>
                
                <div class="contact-alternatives">
                    <div class="contact-alt">
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <strong>Ligue para nós</strong>
                            <p><a href="tel:+351234567890">+351 234 567 890</a></p>
                        </div>
                    </div>
                    <div class="contact-alt">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>E-mail</strong>
                            <p><a href="mailto:geral@cardoxis.com">geral@cardoxis.com</a></p>
                        </div>
                    </div>
                    <div class="contact-alt">
                        <i class="fas fa-comment-dots"></i>
                        <div>
                            <strong>Chat ao vivo</strong>
                            <p>Segunda a Sexta, 9h-18h</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- RECURSOS ÚTEIS -->
    <section class="resources-section" id="resources">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Recursos Úteis</div>
                <h2>Conteúdos que podem<br><span class="gradient-text">ajudar</span></h2>
                <p>Explore os nossos materiais educativos e guias práticos</p>
            </div>
            
            <div class="resources-grid" data-aos="fade-up">
                <?php foreach ($resources as $resource): ?>
                    <div class="resource-card">
                        <div class="resource-icon">
                            <i class="fas <?= htmlspecialchars($resource['icon']) ?>"></i>
                        </div>
                        <h3><?= htmlspecialchars($resource['title']) ?></h3>
                        <p><?= htmlspecialchars($resource['description']) ?></p>
                        <a href="<?= htmlspecialchars($resource['link']) ?>" class="resource-link">
                            <?= htmlspecialchars($resource['link_text']) ?> <i class="fas <?= htmlspecialchars($resource['link_icon']) ?>"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</main>

<!--  RODAPÉ -->
<?php include_once __DIR__ . '/../layouts/partials/footer.php'; ?>

<!--  BOTÃO VOLTAR AO TOPO -->
<button class="back-to-top" id="backToTop" title="Voltar ao topo">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- SCRIPTS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="<?= asset('js/landing/faq.js') ?>"></script>
<?php include_once __DIR__ . '/../layouts/partials/scripts.php'; ?>

</body>
</html>