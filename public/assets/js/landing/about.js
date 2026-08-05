/**
 * CARDOXIS - Script da Página Sobre Nós RF
 */

(function() {
    'use strict';

    // INICIALIZAR AOS

    AOS.init({
        duration: 1000,
        once: true,
        offset: 100,
        easing: 'ease-in-out'
    });

    // CONTADORES COM ANIMAÇÃO

    const counters = document.querySelectorAll('.counter');
    
    if (counters.length > 0) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = parseInt(counter.getAttribute('data-count') || counter.textContent.replace(/[^0-9]/g, ''));
                    
                    if (isNaN(target)) return;
                    
                    let current = 0;
                    const duration = 2000;
                    const steps = 60;
                    const increment = Math.ceil(target / steps);
                    const stepTime = Math.floor(duration / steps);
                    
                    // Adicionar sufixo se existir (ex: K, +)
                    const originalText = counter.textContent;
                    const suffix = originalText.replace(/[0-9.]/g, '');
                    
                    const timer = setInterval(function() {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        
                        // Formatar com separador de milhares
                        const formatted = current.toLocaleString('pt-PT');
                        counter.textContent = formatted + suffix;
                    }, stepTime);
                    
                    observer.unobserve(counter);
                }
            });
        }, { threshold: 0.5 });
        
        counters.forEach(function(c) {
            observer.observe(c);
        });
    }

    // TIMELINE ANIMAÇÃO
    
    const timelineItems = document.querySelectorAll('.timeline-item');
    
    if (timelineItems.length > 0) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const item = entry.target;
                    const dot = item.querySelector('.timeline-dot');
                    
                    if (dot) {
                        dot.style.transform = 'scale(1)';
                        dot.style.opacity = '1';
                    }
                    
                    const card = item.querySelector('.timeline-card');
                    if (card) {
                        card.style.opacity = '1';
                        card.style.transform = 'translateX(0)';
                    }
                }
            });
        }, { threshold: 0.3 });
        
        timelineItems.forEach(function(item, index) {
            const dot = item.querySelector('.timeline-dot');
            if (dot) {
                dot.style.transform = 'scale(0)';
                dot.style.transition = 'all 0.5s ease ' + (index * 0.1) + 's';
            }
            
            const card = item.querySelector('.timeline-card');
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                card.style.transition = 'all 0.5s ease ' + (index * 0.1 + 0.2) + 's';
            }
            
            observer.observe(item);
        });
    }

})();