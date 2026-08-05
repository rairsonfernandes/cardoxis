<?php
require_once __DIR__ . '/../layouts/helpers.php';

$pageTitle = "Carreiras - CARDOXIS | Faça parte do time que revoluciona a gestão de frotas";
$lastUpdated = date("d/m/Y");
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0052CC">
    <meta name="description" content="Trabalhe na CARDOXIS. Estamos procurando talentos para revolucionar a gestão de frotas com inteligência artificial. Vagas para desenvolvedores, IA, vendas e mais.">
    <meta name="author" content="CARDOXIS">
    <meta name="robots" content="index, follow">
    <title><?= $pageTitle ?></title>

    <!-- Favicons -->
    <link rel="icon" type="image/png" href="<?= asset('img/logo/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/logo/apple-touch-icon.png') ?>">
    
    <!-- Fonts & Libraries -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= asset('css/landing/landing.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/landing/careers.css') ?>">
</head>
<body>

<?php include_once __DIR__ . '/../layouts/partials/navbar.php'; ?>

<main>

<!-- Hero Section -->
<section class="careers-hero">
    <div class="container">
        <div class="hero-content" data-aos="fade-up" style="text-align: center; max-width: 800px; margin: 0 auto;">
            <div class="hero-badge" style="background: var(--primary-ultra-light); color: var(--primary); display: inline-flex; margin: 0 auto var(--spacing-lg);">
                <span><i class="fas fa-rocket"></i> Junte-se a nós</span>
            </div>
            <h1 class="hero-title">
                Faça parte do<br>
                <span class="gradient-text">time CARDOXIS</span>
            </h1>
            <p class="hero-description">
                Estamos revolucionando a gestão de frotas com inteligência artificial. 
                Buscamos talentos apaixonados por tecnologia, inovação e resultados.
            </p>
            <div class="careers-meta">
                <span><i class="fas fa-map-marker-alt"></i> Lisboa, Portugal</span>
                <span><i class="fas fa-globe"></i> Remoto disponível</span>
                <span><i class="fas fa-briefcase"></i> +10 vagas abertas</span>
            </div>
        </div>
    </div>
</section>

<!-- Why Join Us -->
<section class="why-join">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <div class="section-badge">Por que trabalhar conosco</div>
            <h2>O que oferecemos<br><span class="gradient-text">aos nossos colaboradores</span></h2>
            <p>Na CARDOXIS, valorizamos nossos talentos e oferecemos um ambiente de trabalho excepcional</p>
        </div>
        <div class="benefits-grid" data-aos="fade-up">
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-laptop-code"></i></div>
                <h3>Tecnologia de Ponta</h3>
                <p>Trabalhe com as mais recentes tecnologias e ferramentas do mercado</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-chart-line"></i></div>
                <h3>Plano de Carreira</h3>
                <p>Programa de desenvolvimento profissional e plano de carreira estruturado</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-home"></i></div>
                <h3>Trabalho Remoto</h3>
                <p>Modelo híbrido e remoto com horários flexíveis</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-graduation-cap"></i></div>
                <h3>Aprendizado Contínuo</h3>
                <p>Certificações, cursos e participação em conferências pagas pela empresa</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-heartbeat"></i></div>
                <h3>Saúde e Bem-estar</h3>
                <p>Plano de saúde, seguro de vida e programa de bem-estar</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-hand-holding-heart"></i></div>
                <h3>Ambiente Inclusivo</h3>
                <p>Cultura diversa, inclusiva e colaborativa</p>
            </div>
        </div>
    </div>
</section>

<!-- Our Values -->
<section class="our-values">
    <div class="container">
        <div class="values-grid" data-aos="fade-up">
            <div class="value-card-large">
                <div class="value-icon-large"><i class="fas fa-star-of-life"></i></div>
                <h3>Nossos Valores</h3>
                <p>Os princípios que guiam nossa cultura e decisões diárias</p>
            </div>
            <div class="value-item-list">
                <div class="value-list-item">
                    <i class="fas fa-lightbulb"></i>
                    <div>
                        <strong>Inovação sem limites</strong>
                        <p>Estamos sempre buscando novas soluções e desafios</p>
                    </div>
                </div>
                <div class="value-list-item">
                    <i class="fas fa-users"></i>
                    <div>
                        <strong>Colaboração real</strong>
                        <p>Trabalhamos juntos como um time, sem hierarquias rígidas</p>
                    </div>
                </div>
                <div class="value-list-item">
                    <i class="fas fa-chart-simple"></i>
                    <div>
                        <strong>Foco no impacto</strong>
                        <p>Cada ação deve gerar valor real para nossos clientes</p>
                    </div>
                </div>
                <div class="value-list-item">
                    <i class="fas fa-hand-peace"></i>
                    <div>
                        <strong>Transparência total</strong>
                        <p>Comunicação aberta e honesta em todos os níveis</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Open Positions -->
<section class="open-positions">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <div class="section-badge">Vagas em aberto</div>
            <h2>Venha fazer parte do<br><span class="gradient-text">nosso time</span></h2>
            <p>Confira as oportunidades disponíveis e candidate-se</p>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs" data-aos="fade-up">
            <button class="filter-btn active" data-filter="all">Todas</button>
            <button class="filter-btn" data-filter="tech">Tecnologia</button>
            <button class="filter-btn" data-filter="sales">Vendas</button>
            <button class="filter-btn" data-filter="product">Produto</button>
            <button class="filter-btn" data-filter="marketing">Marketing</button>
        </div>

        <!-- Jobs List -->
        <div class="jobs-container" data-aos="fade-up" id="jobsContainer">
            <!-- Job 1 - Tech -->
            <div class="job-card" data-category="tech">
                <div class="job-header">
                    <div class="job-title-section">
                        <h3 class="job-title">Desenvolvedor Full Stack Pleno</h3>
                        <div class="job-badge"><i class="fas fa-map-marker-alt"></i> Lisboa (Híbrido)</div>
                    </div>
                    <div class="job-type">Tempo Integral</div>
                </div>
                <div class="job-description">
                    <p>Buscamos um desenvolvedor Full Stack apaixonado por tecnologia para se juntar ao nosso time. Você irá trabalhar com tecnologias modernas como React, Node.js e Python.</p>
                </div>
                <div class="job-requirements">
                    <span class="requirement">React.js</span>
                    <span class="requirement">Node.js</span>
                    <span class="requirement">Python</span>
                    <span class="requirement">MongoDB/PostgreSQL</span>
                </div>
                <div class="job-footer">
                    <span class="job-salary"><i class="fas fa-money-bill-wave"></i> €35k - €55k / ano</span>
                    <button class="btn-apply" data-job="Desenvolvedor Full Stack Pleno">Candidatar-se</button>
                </div>
            </div>

            <!-- Job 2 - Tech -->
            <div class="job-card" data-category="tech">
                <div class="job-header">
                    <div class="job-title-section">
                        <h3 class="job-title">Especialista em IA / Machine Learning</h3>
                        <div class="job-badge"><i class="fas fa-globe"></i> Remoto</div>
                    </div>
                    <div class="job-type">Tempo Integral</div>
                </div>
                <div class="job-description">
                    <p>Procuramos um especialista em Inteligência Artificial para desenvolver e otimizar nossos algoritmos de predição e análise de dados de frota.</p>
                </div>
                <div class="job-requirements">
                    <span class="requirement">Python</span>
                    <span class="requirement">TensorFlow/PyTorch</span>
                    <span class="requirement">Machine Learning</span>
                    <span class="requirement">Data Science</span>
                </div>
                <div class="job-footer">
                    <span class="job-salary"><i class="fas fa-money-bill-wave"></i> €50k - €75k / ano</span>
                    <button class="btn-apply" data-job="Especialista em IA / Machine Learning">Candidatar-se</button>
                </div>
            </div>

            <!-- Job 3 - Tech -->
            <div class="job-card" data-category="tech">
                <div class="job-header">
                    <div class="job-title-section">
                        <h3 class="job-title">DevOps Engineer</h3>
                        <div class="job-badge"><i class="fas fa-map-marker-alt"></i> Lisboa (Híbrido)</div>
                    </div>
                    <div class="job-type">Tempo Integral</div>
                </div>
                <div class="job-description">
                    <p>Buscamos um profissional para gerenciar nossa infraestrutura cloud, pipelines CI/CD e garantir a alta disponibilidade da plataforma.</p>
                </div>
                <div class="job-requirements">
                    <span class="requirement">AWS/GCP</span>
                    <span class="requirement">Docker/Kubernetes</span>
                    <span class="requirement">Terraform</span>
                    <span class="requirement">CI/CD</span>
                </div>
                <div class="job-footer">
                    <span class="job-salary"><i class="fas fa-money-bill-wave"></i> €45k - €65k / ano</span>
                    <button class="btn-apply" data-job="DevOps Engineer">Candidatar-se</button>
                </div>
            </div>

            <!-- Job 4 - Sales -->
            <div class="job-card" data-category="sales">
                <div class="job-header">
                    <div class="job-title-section">
                        <h3 class="job-title">Sales Executive (SaaS)</h3>
                        <div class="job-badge"><i class="fas fa-map-marker-alt"></i> Lisboa (Presencial)</div>
                    </div>
                    <div class="job-type">Tempo Integral</div>
                </div>
                <div class="job-description">
                    <p>Procuramos um profissional de vendas experiente para expandir nossa base de clientes e fechar contratos com empresas de todos os portes.</p>
                </div>
                <div class="job-requirements">
                    <span class="requirement">Experiência em SaaS</span>
                    <span class="requirement">B2B</span>
                    <span class="requirement">Negociação</span>
                    <span class="requirement">Inglês fluente</span>
                </div>
                <div class="job-footer">
                    <span class="job-salary"><i class="fas fa-money-bill-wave"></i> €40k + Comissões</span>
                    <button class="btn-apply" data-job="Sales Executive (SaaS)">Candidatar-se</button>
                </div>
            </div>

            <!-- Job 5 - Product -->
            <div class="job-card" data-category="product">
                <div class="job-header">
                    <div class="job-title-section">
                        <h3 class="job-title">Product Manager</h3>
                        <div class="job-badge"><i class="fas fa-globe"></i> Remoto</div>
                    </div>
                    <div class="job-type">Tempo Integral</div>
                </div>
                <div class="job-description">
                    <p>Buscamos um Product Manager para liderar o desenvolvimento de novos produtos e funcionalidades, com foco na experiência do usuário.</p>
                </div>
                <div class="job-requirements">
                    <span class="requirement">Product Strategy</span>
                    <span class="requirement">UX/UI</span>
                    <span class="requirement">Agile/Scrum</span>
                    <span class="requirement">Data-driven</span>
                </div>
                <div class="job-footer">
                    <span class="job-salary"><i class="fas fa-money-bill-wave"></i> €55k - €80k / ano</span>
                    <button class="btn-apply" data-job="Product Manager">Candidatar-se</button>
                </div>
            </div>

            <!-- Job 6 - Marketing -->
            <div class="job-card" data-category="marketing">
                <div class="job-header">
                    <div class="job-title-section">
                        <h3 class="job-title">Marketing Digital Specialist</h3>
                        <div class="job-badge"><i class="fas fa-globe"></i> Remoto</div>
                    </div>
                    <div class="job-type">Tempo Integral</div>
                </div>
                <div class="job-description">
                    <p>Procuramos um especialista em marketing digital para gerenciar campanhas, redes sociais e produção de conteúdo.</p>
                </div>
                <div class="job-requirements">
                    <span class="requirement">SEO/SEM</span>
                    <span class="requirement">Google Analytics</span>
                    <span class="requirement">Content Marketing</span>
                    <span class="requirement">Social Media</span>
                </div>
                <div class="job-footer">
                    <span class="job-salary"><i class="fas fa-money-bill-wave"></i> €30k - €45k / ano</span>
                    <button class="btn-apply" data-job="Marketing Digital Specialist">Candidatar-se</button>
                </div>
            </div>

            <!-- Job 7 - Tech -->
            <div class="job-card" data-category="tech">
                <div class="job-header">
                    <div class="job-title-section">
                        <h3 class="job-title">Frontend Developer</h3>
                        <div class="job-badge"><i class="fas fa-map-marker-alt"></i> Lisboa (Híbrido)</div>
                    </div>
                    <div class="job-type">Tempo Integral</div>
                </div>
                <div class="job-description">
                    <p>Buscamos um desenvolvedor frontend para criar interfaces incríveis e responsivas para nossa plataforma.</p>
                </div>
                <div class="job-requirements">
                    <span class="requirement">React.js</span>
                    <span class="requirement">TypeScript</span>
                    <span class="requirement">Tailwind CSS</span>
                    <span class="requirement">Next.js</span>
                </div>
                <div class="job-footer">
                    <span class="job-salary"><i class="fas fa-money-bill-wave"></i> €35k - €50k / ano</span>
                    <button class="btn-apply" data-job="Frontend Developer">Candidatar-se</button>
                </div>
            </div>
        </div>

        <div class="no-results" id="noResults" style="display: none; text-align: center; padding: 60px 20px;">
            <i class="fas fa-search" style="font-size: 3rem; color: var(--gray-light); margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 10px;">Nenhuma vaga encontrada</h3>
            <p style="color: var(--gray);">Não encontramos vagas para a categoria selecionada.</p>
        </div>
    </div>
</section>

<!-- Application Process -->
<section class="application-process">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <div class="section-badge">Processo Seletivo</div>
            <h2>Como é o nosso<br><span class="gradient-text">processo</span></h2>
            <p>Um processo transparente e focado em conhecer seu potencial</p>
        </div>
        <div class="process-steps" data-aos="fade-up">
            <div class="process-step">
                <div class="step-number">01</div>
                <div class="step-icon"><i class="fas fa-file-alt"></i></div>
                <h4>Inscrição</h4>
                <p>Envie seu currículo e informações de contato</p>
            </div>
            <div class="process-step">
                <div class="step-number">02</div>
                <div class="step-icon"><i class="fas fa-phone-alt"></i></div>
                <h4>Triagem</h4>
                <p>Conversa inicial para conhecer suas experiências</p>
            </div>
            <div class="process-step">
                <div class="step-number">03</div>
                <div class="step-icon"><i class="fas fa-laptop"></i></div>
                <h4>Desafio Técnico</h4>
                <p>Teste prático para avaliar suas habilidades</p>
            </div>
            <div class="process-step">
                <div class="step-number">04</div>
                <div class="step-icon"><i class="fas fa-users"></i></div>
                <h4>Entrevista Final</h4>
                <p>Conversa com o time e liderança</p>
            </div>
            <div class="process-step">
                <div class="step-number">05</div>
                <div class="step-icon"><i class="fas fa-handshake"></i></div>
                <h4>Oferta</h4>
                <p>Proposta formal e onboarding</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="testimonials-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <div class="section-badge">Depoimentos</div>
            <h2>Quem trabalha<br><span class="gradient-text">recomenda</span></h2>
            <p>Veja o que nossos colaboradores dizem sobre a CARDOXIS</p>
        </div>
        <div class="testimonials-grid" data-aos="fade-up">
            <div class="testimonial-card">
                <div class="testimonial-quote"><i class="fas fa-quote-left"></i></div>
                <p>A CARDOXIS me proporcionou um ambiente de crescimento incrível. Aqui tenho autonomia para criar e aprender todos os dias.</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">MS</div>
                    <div>
                        <div class="testimonial-name">Mariana Silva</div>
                        <div class="testimonial-position">Tech Lead</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-quote"><i class="fas fa-quote-left"></i></div>
                <p>O time é fantástico e a cultura de colaboração faz toda a diferença. É inspirador ver o impacto do nosso trabalho.</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">RM</div>
                    <div>
                        <div class="testimonial-name">Ricardo Mendes</div>
                        <div class="testimonial-position">Product Manager</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-quote"><i class="fas fa-quote-left"></i></div>
                <p>Trabalhar na CARDOXIS é estar na vanguarda da tecnologia. Além disso, o equilíbrio entre vida pessoal e profissional é respeitado.</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">CS</div>
                    <div>
                        <div class="testimonial-name">Carla Souza</div>
                        <div class="testimonial-position">Sales Executive</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-card" data-aos="fade-up">
            <div class="cta-icon"><i class="fas fa-paper-plane"></i></div>
            <h2>Não encontrou a vaga ideal?</h2>
            <p>Envie seu currículo espontâneo. Estamos sempre em busca de novos talentos!</p>
            <div class="cta-buttons">
                <a href="mailto:careers@cardoxis.com?subject=Candidatura Espontânea" class="btn btn-primary">
                    <i class="fas fa-envelope"></i> Enviar Currículo
                </a>
            </div>
        </div>
    </div>
</section>

</main>

<?php include_once __DIR__ . '/../layouts/partials/footer.php'; ?>
<button class="back-to-top" id="backToTop"><i class="fas fa-chevron-up"></i></button>

<!-- Modal de Candidatura -->
<div class="apply-modal" id="applyModal">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <button class="modal-close" id="closeModalBtn">&times;</button>
        <div class="modal-header">
            <i class="fas fa-paper-plane"></i>
            <h3>Candidatar-se à vaga</h3>
        </div>
        <form class="apply-form" id="applyForm" action="<?= url('careers/apply') ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="job_title" id="jobTitleField">
            <div class="form-group">
                <label>Nome completo</label>
                <input type="text" name="name" required placeholder="Seu nome completo">
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" required placeholder="seu@email.com">
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="tel" name="phone" placeholder="+351 912 345 678">
            </div>
            <div class="form-group">
                <label>LinkedIn (opcional)</label>
                <input type="url" name="linkedin" placeholder="https://linkedin.com/in/seu-perfil">
            </div>
            <div class="form-group">
                <label>Mensagem / Apresentação</label>
                <textarea name="message" rows="4" placeholder="Conte-nos um pouco sobre você e por que gostaria de trabalhar conosco..."></textarea>
            </div>
            <div class="form-group">
                <label>Currículo (PDF, DOC, DOCX)</label>
                <input type="file" name="resume" accept=".pdf,.doc,.docx">
            </div>
            <button type="submit" class="btn btn-primary btn-submit">Enviar Candidatura</button>
            <p class="form-note">Ao enviar, você concorda com nossa <a href="<?= url('privacy') ?>">Política de Privacidade</a>.</p>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="<?= asset('js/landing/careers.js') ?>"></script>
<?php include_once __DIR__ . '/../layouts/partials/scripts.php'; ?>
</body>
</html>