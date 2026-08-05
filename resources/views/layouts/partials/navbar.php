<?php
/**
 * CARDOXIS - Navbar RF
 */
?>

<!--  NAVBAR PRINCIPAL -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        
        <!-- Logótipo -->
        <a href="<?= url('') ?>" class="logo" aria-label="CARDOXIS - Página inicial">
            <img src="<?= asset('img/logo/logo.png') ?>" alt="CARDOXIS" class="logo-img" width="40" height="40">
            <span class="logo-text">CARDOXIS<span>.io</span></span>
        </a>
        
        <!-- Links de Navegação Desktop -->
        <div class="nav-links">
            <a href="<?= url('') ?>" class="nav-link active">Início</a>
            
            <!-- Mega Dropdown: Produto -->
            <div class="dropdown">
                <button class="dropdown-btn" aria-haspopup="true" aria-expanded="false">
                    Produto <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-content">
                    <div class="mega-grid">
                        <div class="mega-links">
                            <a href="<?= url('vehicles') ?>" class="mega-item">
                                <div class="mega-icon"><i class="fas fa-car"></i></div>
                                <div>
                                    <strong>Gestão de Veículos</strong>
                                    <small>Cadastro e controlo completo da frota</small>
                                </div>
                            </a>
                            <a href="<?= url('drivers') ?>" class="mega-item">
                                <div class="mega-icon"><i class="fas fa-users"></i></div>
                                <div>
                                    <strong>Gestão de Motoristas</strong>
                                    <small>Controlo de motoristas e licenças</small>
                                </div>
                            </a>
                            <a href="<?= url('documents') ?>" class="mega-item">
                                <div class="mega-icon"><i class="fas fa-file-alt"></i></div>
                                <div>
                                    <strong>Gestão de Documentos</strong>
                                    <small>Organização e alertas de vencimento</small>
                                </div>
                            </a>
                            <a href="<?= url('insurance') ?>" class="mega-item">
                                <div class="mega-icon"><i class="fas fa-shield-alt"></i></div>
                                <div>
                                    <strong>Gestão de Seguros</strong>
                                    <small>Controlo de apólices e sinistros</small>
                                </div>
                            </a>
                            <a href="<?= url('fines') ?>" class="mega-item">
                                <div class="mega-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <div>
                                    <strong>Gestão de Multas</strong>
                                    <small>Registo e acompanhamento de multas</small>
                                </div>
                            </a>
                            <a href="<?= url('fuel') ?>" class="mega-item">
                                <div class="mega-icon"><i class="fas fa-gas-pump"></i></div>
                                <div>
                                    <strong>Gestão de Combustível</strong>
                                    <small>Controlo de abastecimentos e consumo</small>
                                </div>
                            </a>
                            <a href="<?= url('maintenance') ?>" class="mega-item">
                                <div class="mega-icon"><i class="fas fa-tools"></i></div>
                                <div>
                                    <strong>Manutenções Programadas</strong>
                                    <small>Preventivas e corretivas</small>
                                </div>
                            </a>
                            <a href="<?= url('alerts') ?>" class="mega-item">
                                <div class="mega-icon"><i class="fas fa-bell"></i></div>
                                <div>
                                    <strong>Alertas Inteligentes</strong>
                                    <small>Notificações automáticas de prazos</small>
                                </div>
                            </a>
                        </div>
                        <div class="mega-highlight">
                            <i class="fas fa-rocket"></i>
                            <h4>Gerencie a sua frota com excelência</h4>
                            <p>Reduza custos operacionais e aumente a eficiência</p>
                            <a href="<?= url('register') ?>" class="btn-highlight">
                                Começar agora <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mega Dropdown: Recursos -->
            <div class="dropdown">
                <button class="dropdown-btn" aria-haspopup="true" aria-expanded="false">
                    Recursos <i class="fas fa-chevron-down"></i>
                </button>

                <div class="dropdown-content">
                    <div class="mega-grid">

                        <div class="mega-links">

                            <a href="<?= url('help') ?>" class="mega-item">
                                <div class="mega-icon">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <div>
                                    <strong>Central de Ajuda</strong>
                                    <small>Guias, tutoriais e perguntas frequentes</small>
                                </div>
                            </a>

                            <a href="<?= url('support') ?>" class="mega-item">
                                <div class="mega-icon">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <div>
                                    <strong>Suporte Técnico</strong>
                                    <small>Obtenha ajuda da nossa equipa</small>
                                </div>
                            </a>

                            <a href="<?= url('chat') ?>" class="mega-item">
                                <div class="mega-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div>
                                    <strong>Chat</strong>
                                    <small>Fale connosco em tempo real</small>
                                </div>
                            </a>

                            <a href="<?= url('docs') ?>" class="mega-item">
                                <div class="mega-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div>
                                    <strong>Documentação</strong>
                                    <small>Guias completos da plataforma</small>
                                </div>
                            </a>

                            <a href="<?= url('/') ?>#testimonials" class="mega-item">
                                <div class="mega-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div>
                                    <strong>Depoimentos</strong>
                                    <small>O que os nossos clientes dizem</small>
                                </div>
                            </a>

                            <a href="<?= url('faq') ?>" class="mega-item">
                                <div class="mega-icon">
                                    <i class="fas fa-circle-question"></i>
                                </div>
                                <div>
                                    <strong>Perguntas Frequentes</strong>
                                    <small>Respostas às dúvidas mais comuns</small>
                                </div>
                            </a>

                            <a href="<?= url('changelog') ?>" class="mega-item">
                                <div class="mega-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div>
                                    <strong>Novidades</strong>
                                    <small>Conheça as últimas atualizações</small>
                                </div>
                            </a>

                            <a href="<?= url('contact') ?>" class="mega-item">
                                <div class="mega-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <strong>Contacto</strong>
                                    <small>Entre em contacto com a nossa equipa</small>
                                </div>
                            </a>

                        </div>

                        <div class="mega-highlight">
                            <i class="fas fa-life-ring"></i>

                            <h4>Estamos aqui para ajudar</h4>

                            <p>
                                Explore a documentação, fale com a nossa equipa,
                                consulte as novidades e descubra porque centenas de
                                empresas confiam no CARDOXIS.
                            </p>

                            <a href="<?= url('contact') ?>" class="btn-highlight">
                                Falar com a Equipa
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Links diretos -->
            <a href="<?= url('#pricing') ?>" class="nav-link">Planos</a>
            <a href="<?= url('about') ?>" class="nav-link">Sobre nós</a>
            <a href="<?= url('contact') ?>" class="nav-link">Contacto</a>
        </div>
        
        <!-- Ações do Navbar -->
        <div class="nav-actions">
            <a href="<?= url('login') ?>" class="btn btn-outline btn-sm">Entrar</a>
            <a href="<?= url('register') ?>" class="btn btn-primary btn-sm">Criar Conta</a>
        </div>
        
        <!-- Hamburger Menu (Mobile) -->
        <button class="hamburger" id="hamburgerBtn" aria-label="Menu" aria-expanded="false">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
    </div>
</nav>

<!-- MENU MOBILE -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
<div class="mobile-menu" id="mobileMenu" role="dialog" aria-modal="true">
    
    <!-- Cabeçalho do Menu Mobile -->
    <div class="mobile-menu-header">
        <div class="mobile-menu-logo">
            <img src="<?= asset('img/logo/logo.png') ?>" alt="CARDOXIS" width="32" height="32">
            <span>CARDOXIS<span>.io</span></span>
        </div>
        <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Fechar menu">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <!-- Corpo do Menu Mobile -->
    <div class="mobile-menu-body">
        
        <div class="mobile-nav-section">
            
            <!-- Link Início -->
            <a href="<?= url('') ?>" class="mobile-nav-link">
                <i class="fas fa-home"></i> Início
            </a>
            
            <!-- Accordion: Produto -->
            <div class="mobile-accordion">
                <button class="mobile-accordion-trigger" aria-expanded="false">
                    <span><i class="fas fa-cube"></i> Produto</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="mobile-accordion-content">
                    <a href="<?= url('vehicles') ?>" class="mobile-sub-link">
                        <i class="fas fa-truck"></i> Gestão de Veículos
                    </a>
                    <a href="<?= url('drivers') ?>" class="mobile-sub-link">
                        <i class="fas fa-users"></i> Gestão de Motoristas
                    </a>
                    <a href="<?= url('documents') ?>" class="mobile-sub-link">
                        <i class="fas fa-file-alt"></i> Gestão de Documentos
                    </a>
                    <a href="<?= url('insurance') ?>" class="mobile-sub-link">
                        <i class="fas fa-shield-alt"></i> Gestão de Seguros
                    </a>
                    <a href="<?= url('fines') ?>" class="mobile-sub-link">
                        <i class="fas fa-money-bill-wave"></i> Gestão de Multas
                    </a>
                    <a href="<?= url('fuel') ?>" class="mobile-sub-link">
                        <i class="fas fa-gas-pump"></i> Gestão de Combustível
                    </a>
                    <a href="<?= url('maintenance') ?>" class="mobile-sub-link">
                        <i class="fas fa-tools"></i> Manutenções Programadas
                    </a>
                    <a href="<?= url('alerts') ?>" class="mobile-sub-link">
                        <i class="fas fa-bell"></i> Alertas Inteligentes
                    </a>
                </div>
            </div>
            
            <!-- Accordion: Recursos -->
            <div class="mobile-accordion">
                <button class="mobile-accordion-trigger" aria-expanded="false">
                    <span><i class="fas fa-book"></i> Recursos</span>
                    <i class="fas fa-chevron-down"></i>
                </button>

                <div class="mobile-accordion-content">

                    <a href="<?= url('help') ?>" class="mobile-sub-link">
                        <i class="fas fa-book-open"></i> Central de Ajuda
                    </a>

                    <a href="<?= url('support') ?>" class="mobile-sub-link">
                        <i class="fas fa-headset"></i> Suporte Técnico
                    </a>

                    <a href="<?= url('chat') ?>" class="mobile-sub-link">
                        <i class="fas fa-comments"></i> Chat
                    </a>

                    <a href="<?= url('docs') ?>" class="mobile-sub-link">
                        <i class="fas fa-file-alt"></i> Documentação
                    </a>

                    <a href="<?= url('/') ?>#testimonials" class="mobile-sub-link">
                        <i class="fas fa-star"></i> Depoimentos
                    </a>

                    <a href="<?= url('faq') ?>" class="mobile-sub-link">
                        <i class="fas fa-circle-question"></i> Perguntas Frequentes
                    </a>

                    <a href="<?= url('changelog') ?>" class="mobile-sub-link">
                        <i class="fas fa-history"></i> Novidades
                    </a>

                    <a href="<?= url('contact') ?>" class="mobile-sub-link">
                        <i class="fas fa-envelope"></i> Contacto
                    </a>

                </div>
            </div>
            
            <!-- Links diretos -->
            <a href="<?= url('#pricing') ?>" class="mobile-nav-link">
                <i class="fas fa-tag"></i> Planos
            </a>
            <a href="<?= url('about') ?>" class="mobile-nav-link">
                <i class="fas fa-info-circle"></i> Sobre nós
            </a>
            <a href="<?= url('support') ?>" class="mobile-nav-link">
                <i class="fas fa-headset"></i> Suporte
            </a>
            <a href="<?= url('contact') ?>" class="mobile-nav-link">
                <i class="fas fa-envelope"></i> Contacto
            </a>
            
        </div>
        
        <!-- Destaque do Menu Mobile -->
        <div class="mobile-menu-highlight">
            <i class="fas fa-rocket"></i>
            <div>
                <h4>Teste grátis por 14 dias</h4>
                <p>Sem compromisso, cancele quando quiser</p>
            </div>
        </div>
        
    </div>
    
    <!-- Rodapé do Menu Mobile -->
    <div class="mobile-menu-footer">
        <a href="<?= url('login') ?>" class="mobile-btn mobile-btn-outline">
            <i class="fas fa-sign-in-alt"></i> Entrar
        </a>
        <a href="<?= url('register') ?>" class="mobile-btn mobile-btn-primary">
            <i class="fas fa-rocket"></i> Criar Conta Grátis
            <span class="btn-badge">14 dias</span>
        </a>
    </div>
    
</div>