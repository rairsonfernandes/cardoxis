<?php
/**
 * CARDOXIS - Scripts Globais RF 
 */

// Carregar configurações para o typewriter
require_once __DIR__ . '/../config.php';
?>

<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
/**
 * CARDOXIS - Landing Page Scripts
 * Versão: 3.0.0
 */

(function() {
    'use strict';

    // 1. INICIALIZAR AOS

    AOS.init({
        duration: 1000,
        once: true,
        offset: 100,
        easing: 'ease-in-out'
    });


    // 2. EFEITO DE SCROLL NA NAVBAR

    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    // 3. BOTÃO VOLTAR AO TOPO

    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', function() {
            backToTop.classList.toggle('visible', window.scrollY > 300);
        });
        
        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // 4. MENU MOBILE

    const hamburger = document.getElementById('hamburgerBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileMenuOverlay');
    const closeMenu = document.getElementById('mobileMenuClose');

    if (hamburger && mobileMenu && overlay) {
        function openMenu() {
            mobileMenu.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            hamburger.classList.add('active');
        }

        function closeMenuFn() {
            mobileMenu.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            hamburger.classList.remove('active');
        }

        hamburger.addEventListener('click', openMenu);
        
        if (closeMenu) {
            closeMenu.addEventListener('click', closeMenuFn);
        }
        
        overlay.addEventListener('click', closeMenuFn);
    }

    // 5. ACCORDIONS MOBILE

    document.querySelectorAll('.mobile-accordion-trigger').forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const icon = this.querySelector('i:last-child');
            
            if (content) {
                content.classList.toggle('active');
            }
            
            if (icon) {
                icon.classList.toggle('rotated');
            }
        });
    });

    // 6. MODAL DE VÍDEO

    const modal = document.getElementById('videoModal');
    const openModalBtns = document.querySelectorAll('#openModalBtn, #openModalBtn2');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const video = document.getElementById('demoVideo');

    function openModalFn() {
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            if (video) {
                video.play();
            }
        }
    }

    function closeModalFn() {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            if (video) {
                video.pause();
                video.currentTime = 0;
            }
        }
    }

    // Abrir modal com os botões
    openModalBtns.forEach(function(btn) {
        if (btn) {
            btn.addEventListener('click', openModalFn);
        }
    });

    // Fechar modal
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModalFn);
    }

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal || e.target === modal.querySelector('.video-modal-overlay')) {
                closeModalFn();
            }
        });
    }

    // Fechar com tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            closeModalFn();
        }
    });

    // 7. ANIMAÇÃO DE CONTADORES

    const counters = document.querySelectorAll('.counter');
    
    if (counters.length > 0) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const text = counter.textContent;
                    
                    // Extrair número (ex: "10K+" -> 10000)
                    let number = parseInt(text.replace(/[^0-9]/g, ''));
                    let suffix = text.replace(/[0-9]/g, '');
                    
                    if (isNaN(number)) return;
                    
                    let current = 0;
                    const increment = Math.ceil(number / 60);
                    const duration = 1500;
                    const stepTime = Math.floor(duration / 60);
                    
                    const timer = setInterval(function() {
                        current += increment;
                        if (current >= number) {
                            current = number;
                            clearInterval(timer);
                        }
                        counter.textContent = current + suffix;
                    }, stepTime);
                    
                    observer.unobserve(counter);
                }
            });
        }, { threshold: 0.5 });
        
        counters.forEach(function(c) {
            observer.observe(c);
        });
    }

    // 8. SCROLL SUAVE

    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // 9. EFEITO TYPEWRITER 

    const typewriterElement = document.getElementById('typewriterText');
    
    if (typewriterElement) {
        // Palavras baseadas nas funcionalidades reais do sistema
        const words = <?= json_encode($typewriterWords) ?>;
        
        let wordIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        
        function typeEffect() {
            const currentWord = words[wordIndex];
            
            if (isDeleting) {
                // Remover caracteres
                typewriterElement.textContent = currentWord.substring(0, charIndex - 1);
                charIndex--;
            } else {
                // Adicionar caracteres
                typewriterElement.textContent = currentWord.substring(0, charIndex + 1);
                charIndex++;
            }
            
            let delay = isDeleting ? 50 : 100;
            
            // Verificar se a palavra está completa
            if (!isDeleting && charIndex === currentWord.length) {
                delay = 2000; // Pausa antes de deletar
                isDeleting = true;
            }
            
            // Verificar se a palavra foi completamente deletada
            if (isDeleting && charIndex === 0) {
                isDeleting = false;
                wordIndex = (wordIndex + 1) % words.length;
                delay = 500; // Pausa antes da próxima palavra
            }
            
            setTimeout(typeEffect, delay);
        }
        
        // Iniciar o efeito
        typeEffect();
    }

    // 10. CONFIGURAÇÕES GLOBAIS
    
    window.CARDOXIS_CONFIG = {
        baseUrl: '/cardoxis',
        logo: 'img/logo/logo.png'
    };

})();
</script>

<!-- Font Awesome (obrigatório para ícones) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Cookie Consent (opcional) -->
<?php if (file_exists(ROOT_PATH . '/public/assets/js/landing/cookie-consent.js')): ?>
    <script src="<?= asset('js/landing/cookie-consent.js') ?>"></script>
<?php endif; ?>