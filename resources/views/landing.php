<?php
/**
 *  CARDOXIS - Landing Page RF 
 */

// Carregar configurações
require_once __DIR__ . '/layouts/config.php';
require_once __DIR__ . '/layouts/helpers.php';
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="<?= htmlspecialchars($theme_color) ?>">
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($keywords) ?>">
    <meta name="author" content="<?= htmlspecialchars($pageConfig['seo']['author']) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    
    <title><?= htmlspecialchars($title) ?></title>

    <!-- Favicons -->
    <link rel="icon" type="image/png" href="<?= asset('img/logo/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/logo/apple-touch-icon.png') ?>">
    
    <!-- Pré-conexão para performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Fontes e Estilos -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= asset('css/landing/landing.css') ?>">
    
    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
        <?= json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
</head>
<body>

<!-- ============================================================
    MODAL DE VÍDEO
    ============================================================ -->
<div class="video-modal" id="videoModal">
    <div class="video-modal-overlay"></div>
    <div class="video-modal-container">
        <button class="video-modal-close" id="closeModalBtn">
            <i class="fas fa-times"></i>
        </button>
        <div class="video-modal-content">
            <video id="demoVideo" controls preload="none">
                <source src="<?= asset('video/demo.mp4') ?>" type="video/mp4">
                O seu navegador não suporta vídeos.
            </video>
        </div>
    </div>
</div>

<!-- ============================================================
    NAVBAR
    ============================================================ -->
<?php include_once __DIR__ . '/layouts/partials/navbar.php'; ?>

<!-- ============================================================
    CONTEÚDO PRINCIPAL
    ============================================================ -->
<main>

    <!-- ============================================================
        SECÇÃO HERO
        ============================================================ -->
    <section class="hero" id="home">
        <div class="hero-container">

            <!-- Conteúdo Hero -->
            <div class="hero-content" data-aos="fade-up">

                <!-- Badge -->
                <div class="hero-badge">
                    <span class="badge-new">NOVO</span>
                    <span>Solução completa para a gestão de frotas de veículos</span>
                </div>

                <!-- Título com Typewriter -->
                <h1 class="hero-title">
                    <span class="typewriter-prefix">Gestão Inteligente de</span><br>
                    <span class="gradient-text typewriter-text" id="typewriterText"></span>
                </h1>

                <!-- Descrição -->
                <p class="hero-description">
                    Controlo total da sua frota com uma plataforma completa.
                    Gerencie veículos, motoristas, documentos, seguros, multas,
                    manutenções e combustível num só lugar.
                    <br>
                    <strong class="highlight-text">+500 empresas já confiam na CARDOXIS.</strong>
                </p>

                <!-- Botões -->
                <div class="hero-buttons">
                    <a href="<?= url('register') ?>" class="btn btn-primary btn-large">
                        Começar Agora <i class="fas fa-arrow-right"></i>
                        <span class="btn-badge">14 dias grátis</span>
                    </a>

                    <button class="btn btn-outline btn-large" id="openModalBtn">
                        <i class="fas fa-play"></i> Ver Demonstração
                    </button>
                </div>

                <!-- Estatísticas -->
                <div class="hero-stats">
                    <?php foreach ($stats as $stat): ?>
                        <div>
                            <div class="stat-number counter"><?= htmlspecialchars($stat['number']) ?></div>
                            <div class="stat-label"><?= htmlspecialchars($stat['label']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- Pré-visualização do Dashboard -->
            <div class="responsive-showcase" data-aos="fade-left" data-aos-delay="200">
                <div class="responsive-showcase-wrapper">
                    <div class="responsive-showcase-glow"></div>
                    <img
                        src="<?= asset('img/landing/dashboard-preview.png') ?>"
                        alt="CARDOXIS Dashboard - Gestão de Frotas"
                        class="responsive-showcase-image"
                        loading="eager">
                </div>
            </div>

        </div>
    </section>

    <!-- ============================================================
        SECÇÃO FUNCIONALIDADES
        ============================================================ -->
    <section class="features" id="features">
        <div class="container">
            
            <!-- Cabeçalho da Secção -->
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Funcionalidades Completas</div>
                <h2>Tudo o que precisa para<br><span class="gradient-text">gerir a sua frota com excelência</span></h2>
                <p>Soluções integradas que transformam a gestão da sua frota em vantagem competitiva</p>
            </div>

            <!-- Grelha de Funcionalidades -->
            <div class="features-grid">
                <?php foreach ($features as $index => $feature): ?>
                    <div class="feature-card" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                        <div class="feature-icon">
                            <i class="fas <?= htmlspecialchars($feature['icon']) ?>"></i>
                        </div>
                        <h3><?= htmlspecialchars($feature['title']) ?></h3>
                        <p><?= htmlspecialchars($feature['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <!-- ============================================================
        SECÇÃO PRÉ-VISUALIZAÇÃO DO DASHBOARD
        ============================================================ -->
    <section class="dashboard-preview">
        <div class="container">
            <div class="dashboard-grid">
                
                <!-- Conteúdo do Dashboard -->
                <div class="dashboard-content" data-aos="fade-right">
                    <div class="section-badge">Dashboard Completo</div>
                    <h2 class="section-title">Visualize a sua frota<br>em <span class="gradient-text">tempo real</span></h2>
                    <p class="section-description">
                        Tenha uma visão 360° da sua operação com métricas em tempo real,
                        gráficos interativos e acesso rápido a todas as funcionalidades
                        do sistema.
                    </p>

                    <!-- Lista de Funcionalidades do Dashboard -->
                    <ul class="dashboard-features">
                        <?php foreach ($dashboardFeatures as $feature): ?>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <?= htmlspecialchars($feature) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <!-- Botões do Dashboard -->
                    <div class="dashboard-buttons">
                        <a href="<?= url('register') ?>" class="btn btn-primary">
                            Experimente Grátis <i class="fas fa-arrow-right"></i>
                        </a>
                        <button class="btn btn-outline" id="openModalBtn2">
                            Ver Demonstração <i class="fas fa-play"></i>
                        </button>
                    </div>
                </div>

                <!-- Imagem do Dashboard -->
                <div class="dashboard-image" data-aos="fade-left">
                    <div class="image-wrapper">
                        <img
                            src="<?= asset('img/landing/dashboard-full.png') ?>"
                            alt="CARDOXIS Dashboard completo - Gestão de Frotas"
                            width="550"
                            height="400"
                            loading="lazy">
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!--  SECÇÃO PREÇOS-->
    <section class="pricing" id="pricing">
        <div class="container">

            <!-- Cabeçalho da Secção -->
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Planos e Preços</div>
                <h2>Escolha o plano<span class="gradient-text"> ideal para a sua empresa</span></h2>
                <p>Planos flexíveis que se adaptam às necessidades da sua frota</p>
            </div>

            <!-- Grelha de Preços -->
            <div class="pricing-grid">
                <?php foreach ($plans as $index => $plan): ?>
                    <div class="pricing-card <?= $plan['popular'] ? 'popular' : '' ?>" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                        
                        <?php if ($plan['popular']): ?>
                            <div class="pricing-badge">MAIS POPULAR</div>
                        <?php endif; ?>

                        <div class="pricing-header">
                            <h3 class="pricing-name"><?= htmlspecialchars($plan['name']) ?></h3>
                            <div class="pricing-price">
                                <?= htmlspecialchars($plan['currency']) ?> <?= htmlspecialchars($plan['price']) ?>
                                <small><?= htmlspecialchars($plan['period']) ?></small>
                            </div>
                            <p><?= htmlspecialchars($plan['description']) ?></p>
                        </div>

                        <div class="pricing-features">
                            <?php foreach ($plan['features'] as $feature): ?>
                                <div class="pricing-feature">
                                    <i class="fas fa-check"></i>
                                    <?= htmlspecialchars($feature) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="pricing-footer">
                            <a href="<?= url(ltrim($plan['cta_url'], '/')) ?>" class="btn <?= $plan['popular'] ? 'btn-primary' : 'btn-outline' ?>">
                                <?= htmlspecialchars($plan['cta_text']) ?>
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <!-- SECÇÃO DEPOIMENTOS -->
    <section class="testimonials" id="testimonials">
        <div class="container">

            <!-- Cabeçalho da Secção -->
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Depoimentos</div>
                <h2>O que os nossos<br><span class="gradient-text">clientes dizem</span></h2>
                <p>Mais de 500 empresas confiam na CARDOXIS para gerir as suas frotas</p>
            </div>

            <!-- Grelha de Depoimentos -->
            <div class="testimonials-grid">
                <?php foreach ($testimonials as $index => $testimonial): ?>
                    <div class="testimonial-card" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                        
                        <!-- Ícone de Aspas -->
                        <div class="testimonial-quote">
                            <i class="fas fa-quote-left"></i>
                        </div>

                        <!-- Classificação -->
                        <div class="testimonial-rating">
                            <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                        </div>

                        <!-- Conteúdo -->
                        <p class="testimonial-content"><?= htmlspecialchars($testimonial['content']) ?></p>

                        <!-- Autor -->
                        <div class="testimonial-author">
                            <div class="testimonial-avatar"><?= htmlspecialchars($testimonial['initial']) ?></div>
                            <div>
                                <div class="testimonial-name"><?= htmlspecialchars($testimonial['name']) ?></div>
                                <div class="testimonial-position">
                                    <?= htmlspecialchars($testimonial['position']) ?> • <?= htmlspecialchars($testimonial['company']) ?>
                                </div>
                            </div>
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
                
                <!-- Badge -->
                <div class="cta-badge">Comece hoje mesmo</div>

                <!-- Título -->
                <h2 class="cta-title">Pronto para transformar<br><span class="gradient-text">a sua gestão de frota?</span></h2>

                <!-- Descrição -->
                <p class="cta-description">
                    Junte-se a mais de 500 empresas que já otimizaram as suas operações
                    e reduziram custos com a CARDOXIS.
                </p>

                <!-- Botões -->
                <div class="cta-buttons">
                    <a href="<?= url('register') ?>" class="btn btn-primary-cta btn-large">
                        <i class="fas fa-rocket"></i> Começar Grátis
                        <span class="btn-badge">14 dias</span>
                    </a>
                    <a href="<?= url('contact') ?>" class="btn btn-outline-light btn-large">
                        <i class="fas fa-headset"></i> Falar com Especialista
                    </a>
                </div>

                <!-- Itens de Confiança -->
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
<?php include_once __DIR__ . '/layouts/partials/footer.php'; ?>

<!--  BOTÃO VOLTAR AO TOPO -->
<button class="back-to-top" id="backToTop" title="Voltar ao topo">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- SCRIPTS -->
<?php include_once __DIR__ . '/layouts/partials/scripts.php'; ?>

</body>
</html>