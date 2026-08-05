<?php
/**
 * CARDOXIS - Página Sobre Nós RF
 */

// Carregar configurações
require_once __DIR__ . '/../layouts/config.php';
require_once __DIR__ . '/../layouts/helpers.php';

// CONFIGURAÇÕES DA PÁGINA

$pageTitle = "Sobre a CARDOXIS - Plataforma de Gestão de Frota com IA";
$pageDescription = "Conheça a CARDOXIS, a plataforma inteligente de gestão de frotas que utiliza IA para reduzir custos e aumentar a eficiência operacional. Saiba mais sobre a nossa história e equipa.";

// EQUIPA DE LIDERANÇA

$teamMembers = [
    [
        'name' => 'Dr. André Almeida',
        'position' => 'CEO & Fundador',
        'bio' => 'Doutor em Inteligência Artificial pela Universidade de Lisboa, com mais de 15 anos de experiência em gestão de frotas e otimização de operações logísticas.',
        'image' => asset('img/team/andre-almeida.jpg'),
        'social' => [
            'linkedin' => 'https://linkedin.com/in/andre-almeida-cardoxis',
            'twitter' => 'https://twitter.com/andre_almeida'
        ]
    ],
    [
        'name' => 'Dra. Sofia Mendes',
        'position' => 'CTO - Diretora de Tecnologia',
        'bio' => 'Especialista em Machine Learning e Big Data. Lidera a equipa de desenvolvimento com foco em inovação e arquitetura de sistemas escaláveis.',
        'image' => asset('img/team/sofia-mendes.jpg'),
        'social' => [
            'linkedin' => 'https://linkedin.com/in/sofia-mendes-cardoxis',
            'twitter' => 'https://twitter.com/sofia_mendes'
        ]
    ],
    [
        'name' => 'Miguel Costa',
        'position' => 'COO - Diretor de Operações',
        'bio' => 'Ex-executivo de logística com vasta experiência em otimização de operações de frota em escala, liderando equipas multidisciplinares.',
        'image' => asset('img/team/miguel-costa.jpg'),
        'social' => [
            'linkedin' => 'https://linkedin.com/in/miguel-costa-cardoxis',
            'twitter' => 'https://twitter.com/miguel_costa'
        ]
    ],
    [
        'name' => 'Dra. Patrícia Lima',
        'position' => 'Head de Produto',
        'bio' => 'PhD em Experiência do Utilizador. Apaixonada por criar soluções que resolvem problemas reais dos clientes com design centrado no utilizador.',
        'image' => asset('img/team/patricia-lima.jpg'),
        'social' => [
            'linkedin' => 'https://linkedin.com/in/patricia-lima-cardoxis',
            'twitter' => 'https://twitter.com/patricia_lima'
        ]
    ],
];

// VALORES DA EMPRESA

$values = [
    [
        'icon' => 'fa-lightbulb',
        'title' => 'Inovação',
        'description' => 'Buscamos constantemente novas soluções e tecnologias para entregar o melhor aos nossos clientes, antecipando as necessidades do mercado.'
    ],
    [
        'icon' => 'fa-handshake',
        'title' => 'Transparência',
        'description' => 'Relacionamento honesto e aberto com clientes, parceiros e colaboradores, construindo confiança em cada interação.'
    ],
    [
        'icon' => 'fa-chart-line',
        'title' => 'Excelência',
        'description' => 'Compromisso com a qualidade em tudo o que fazemos, superando expectativas e entregando resultados superiores.'
    ],
    [
        'icon' => 'fa-users',
        'title' => 'Colaboração',
        'description' => 'Trabalhamos juntos para alcançar resultados extraordinários, valorizando o trabalho em equipa e a diversidade de ideias.'
    ],
    [
        'icon' => 'fa-leaf',
        'title' => 'Sustentabilidade',
        'description' => 'Comprometidos com práticas sustentáveis que reduzem o impacto ambiental e promovem um futuro mais verde.'
    ],
    [
        'icon' => 'fa-heart',
        'title' => 'Paixão',
        'description' => 'Amamos o que fazemos e colocamos paixão em cada projeto, transformando desafios em oportunidades.'
    ]
];

// MARCOS DA HISTÓRIA

$timeline = [
    [
        'year' => '2021',
        'title' => 'Fundação da CARDOXIS',
        'description' => 'Iniciámos a nossa jornada com a visão de revolucionar a gestão de frotas através da inteligência artificial, reunindo os melhores talentos do setor.'
    ],
    [
        'year' => '2022',
        'title' => 'Lançamento da Plataforma',
        'description' => 'Primeira versão da nossa plataforma de gestão de frotas com IA, atendendo 50 empresas no primeiro ano e validando a nossa solução no mercado.'
    ],
    [
        'year' => '2023',
        'title' => 'Expansão Nacional',
        'description' => 'Alcançámos 500 clientes e lançámos o aplicativo móvel com recursos avançados de OCR e integração com sistemas de gestão de transporte.'
    ],
    [
        'year' => '2024',
        'title' => 'Reconhecimento Internacional',
        'description' => 'Premiada como uma das startups mais inovadoras do setor de logística, com expansão para mercados internacionais e novas parcerias estratégicas.'
    ],
    [
        'year' => '2025',
        'title' => 'O Futuro',
        'description' => 'Continuamos a inovar com novas funcionalidades baseadas em IA, expandindo a nossa presença global e impactando positivamente a gestão de frotas.'
    ]
];

// TECNOLOGIAS UTILIZADAS

$techStack = [
    ['icon' => 'fab fa-html5', 'name' => 'HTML5'],
    ['icon' => 'fab fa-css3-alt', 'name' => 'CSS3'],
    ['icon' => 'fab fa-js-square', 'name' => 'JavaScript'],
    ['icon' => 'fab fa-php', 'name' => 'PHP'],
    ['icon' => 'fas fa-key', 'name' => 'JWT Auth'],
    ['icon' => 'fas fa-database', 'name' => 'MySQL'],
    ['icon' => 'fab fa-react', 'name' => 'React'],
    ['icon' => 'fab fa-node', 'name' => 'Node.js'],
    ['icon' => 'fas fa-cloud', 'name' => 'AWS'],
    ['icon' => 'fas fa-robot', 'name' => 'IA & ML']
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
    <meta property="og:url" content="<?= url('about') ?>">
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
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= asset('css/landing/landing.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/landing/about.css') ?>">
</head>
<body>

<!-- NAVBAR -->
<?php include_once __DIR__ . '/../layouts/partials/navbar.php'; ?>

<!-- CONTEÚDO PRINCIPAL -->
<main>

    <!-- SECÇÃO HERO - SOBRE NÓS -->
    <section class="about-hero" id="about-hero">
        <div class="container">
            <div class="about-hero-content" data-aos="fade-up">
                <div class="hero-badge">
                    <span>Sobre Nós</span>
                </div>
                <h1 class="about-hero-title">
                    Transformando a<br>
                    <span class="gradient-text">Gestão de Frotas com IA</span>
                </h1>
                <p class="about-hero-description">
                    A CARDOXIS nasceu com a missão de revolucionar a gestão de frotas através da inteligência artificial,
                    oferecendo soluções inovadoras que reduzem custos e aumentam a eficiência operacional.
                </p>
                <div class="about-hero-stats">
                    <div class="hero-stat">
                        <span class="stat-number">2021</span>
                        <span class="stat-label">Ano de Fundação</span>
                    </div>
                    <div class="hero-stat">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Clientes</span>
                    </div>
                    <div class="hero-stat">
                        <span class="stat-number">98%</span>
                        <span class="stat-label">Satisfação</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Elementos decorativos -->
        <div class="about-hero-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </section>

    <!--  SECÇÃO MISSÃO E VISÃO -->
    <section class="mission-vision" id="mission-vision">
        <div class="container">
            <div class="mission-vision-grid">
                
                <!-- Missão -->
                <div class="mv-card mission-card" data-aos="fade-right">
                    <div class="mv-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3 class="mv-title">Nossa Missão</h3>
                    <p class="mv-description">
                        Capacitar empresas com tecnologia de ponta para otimizar as suas operações de frota,
                        reduzir custos e promover uma gestão mais sustentável e eficiente.
                    </p>
                    <div class="mv-tag">Missão</div>
                </div>
                
                <!-- Visão -->
                <div class="mv-card vision-card" data-aos="fade-left">
                    <div class="mv-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="mv-title">Nossa Visão</h3>
                    <p class="mv-description">
                        Ser a plataforma líder global em gestão inteligente de frotas, reconhecida pela inovação,
                        confiabilidade e impacto positivo nos negócios dos nossos clientes.
                    </p>
                    <div class="mv-tag">Visão</div>
                </div>
                
            </div>
        </div>
    </section>

    <!-- SECÇÃO NÚMEROS -->
    <section class="numbers-section" id="numbers">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Resultados Reais</div>
                <h2>Números que<br><span class="gradient-text">falam por si</span></h2>
                <p>O impacto da CARDOXIS na gestão de frotas dos nossos clientes</p>
            </div>
            
            <div class="numbers-grid" data-aos="fade-up" data-aos-delay="100">
                <div class="number-item">
                    <div class="number counter" data-count="500">500</div>
                    <div class="label">Empresas Clientes</div>
                </div>
                <div class="number-item">
                    <div class="number counter" data-count="10000">10.000</div>
                    <div class="label">Veículos Monitorizados</div>
                </div>
                <div class="number-item">
                    <div class="number counter" data-count="35">35</div>
                    <div class="label">% Redução de Custos</div>
                </div>
                <div class="number-item">
                    <div class="number counter" data-count="98">98</div>
                    <div class="label">% Satisfação</div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECÇÃO VALORES -->
    <section class="values-section" id="values">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Nossos Valores</div>
                <h2>O que nos<br><span class="gradient-text">impulsiona</span></h2>
                <p>Princípios que guiam cada decisão e ação na CARDOXIS</p>
            </div>
            
            <div class="values-grid">
                <?php foreach ($values as $index => $value): ?>
                    <div class="value-card" data-aos="fade-up" data-aos-delay="<?= $index * 50 ?>">
                        <div class="value-icon">
                            <i class="fas <?= htmlspecialchars($value['icon']) ?>"></i>
                        </div>
                        <h3 class="value-title"><?= htmlspecialchars($value['title']) ?></h3>
                        <p class="value-description"><?= htmlspecialchars($value['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!--  SECÇÃO TIMELINE-->
    <section class="timeline-section" id="timeline">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Nossa Jornada</div>
                <h2>Uma trajetória de<br><span class="gradient-text">inovação e crescimento</span></h2>
                <p>Conheça os principais marcos da nossa história</p>
            </div>
            
            <div class="timeline" data-aos="fade-up">
                <?php foreach ($timeline as $index => $item): ?>
                    <div class="timeline-item <?= $index === array_key_last($timeline) ? 'last' : '' ?>">
                        <div class="timeline-dot">
                            <span class="timeline-year"><?= htmlspecialchars($item['year']) ?></span>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-card">
                                <span class="timeline-year-badge"><?= htmlspecialchars($item['year']) ?></span>
                                <h4 class="timeline-title"><?= htmlspecialchars($item['title']) ?></h4>
                                <p class="timeline-description"><?= htmlspecialchars($item['description']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECÇÃO DIFERENCIAIS -->
    <section class="differentials-section" id="differentials">
        <div class="container">
            <div class="differentials-grid">
                
                <!-- Conteúdo -->
                <div class="differentials-content" data-aos="fade-right">
                    <div class="section-badge">Diferenciais</div>
                    <h2 class="section-title">
                        Por que escolher<br>
                        <span class="gradient-text">a CARDOXIS?</span>
                    </h2>
                    <p class="section-description">
                        Combinamos tecnologia de ponta com uma equipa apaixonada por resolver problemas reais.
                    </p>
                    
                    <ul class="differentials-list">
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Suporte especializado 24/7</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Atualizações constantes e gratuitas</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Certificação de segurança internacional</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Parceria estratégica com os clientes</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Equipa de especialistas dedicados</span>
                        </li>
                    </ul>
                    
                    <div class="differentials-buttons">
                        <a href="<?= url('register') ?>" class="btn btn-primary">
                            Começar Agora <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="<?= url('contact') ?>" class="btn btn-outline">
                            Falar Connosco <i class="fas fa-headset"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Imagem -->
                <div class="differentials-image" data-aos="fade-left">
                    <div class="image-wrapper">
                        <img
                            src="<?= asset('img/landing/equipa.jpg') ?>"
                            alt="CARDOXIS Dashboard - Gestão de Frotas"
                            loading="lazy"
                            width="550"
                            height="400">
                        <div class="image-badge">
                            <i class="fas fa-star"></i>
                            4.8/5 Avaliação
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <!--  SECÇÃO TECNOLOGIAS -->
    <section class="tech-section" id="tech">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Tecnologia de Ponta</div>
                <h2>Construído com as<br><span class="gradient-text">melhores tecnologias</span></h2>
                <p>Utilizamos as mais modernas ferramentas para garantir performance, segurança e escalabilidade</p>
            </div>
            
            <div class="tech-grid" data-aos="fade-up">
                <?php foreach ($techStack as $tech): ?>
                    <div class="tech-item">
                        <i class="<?= htmlspecialchars($tech['icon']) ?>"></i>
                        <span><?= htmlspecialchars($tech['name']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECÇÃO EQUIPA -->
    <section class="team-section" id="team">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Equipa de Especialistas</div>
                <h2>Conheça a nossa<br><span class="gradient-text">liderança</span></h2>
                <p>Profissionais apaixonados por tecnologia e inovação, prontos para transformar a sua gestão.</p>
            </div>
            
            <div class="team-grid">
                <?php foreach ($teamMembers as $index => $member): ?>
                    <div class="team-card" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                        <div class="team-image">
                            <img
                                src="<?= htmlspecialchars($member['image']) ?>"
                                alt="<?= htmlspecialchars($member['name']) ?>"
                                loading="lazy"
                                onerror="this.src='https://placehold.co/400x400/0052CC/white?text=<?= urlencode(substr($member['name'], 0, 2)) ?>'">
                            <div class="team-social">
                                <a href="<?= htmlspecialchars($member['social']['linkedin']) ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn de <?= htmlspecialchars($member['name']) ?>">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="<?= htmlspecialchars($member['social']['twitter']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Twitter de <?= htmlspecialchars($member['name']) ?>">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            </div>
                        </div>
                        <div class="team-info">
                            <h4 class="team-name"><?= htmlspecialchars($member['name']) ?></h4>
                            <p class="team-position"><?= htmlspecialchars($member['position']) ?></p>
                            <p class="team-bio"><?= htmlspecialchars($member['bio']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!--  SECÇÃO CTA -->
    <section class="cta">
        <div class="container">
            <div class="cta-content" data-aos="fade-up">
                <div class="cta-badge">Junte-se a nós</div>
                <h2 class="cta-title">
                    Faça parte da<br>
                    <span class="gradient-text">revolução na gestão de frotas</span>
                </h2>
                <p class="cta-description">
                    Mais de 500 empresas já transformaram a sua gestão com a CARDOXIS. O próximo passo é seu.
                </p>
                <div class="cta-buttons">
                    <a href="<?= url('register') ?>" class="btn btn-primary-cta btn-large">
                        <i class="fas fa-rocket"></i> Começar Grátis
                        <span class="btn-badge">14 dias</span>
                    </a>
                    <a href="<?= url('contact') ?>" class="btn btn-outline-light btn-large">
                        <i class="fas fa-headset"></i> Falar com Especialista
                    </a>
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

<!-- RODAPÉ -->
<?php include_once __DIR__ . '/../layouts/partials/footer.php'; ?>

<!-- BOTÃO VOLTAR AO TOPO -->
<button class="back-to-top" id="backToTop" title="Voltar ao topo">
    <i class="fas fa-chevron-up"></i>
</button>

<!--  SCRIPTS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="<?= asset('js/landing/about.js') ?>"></script>
<?php include_once __DIR__ . '/../layouts/partials/scripts.php'; ?>

</body>
</html>