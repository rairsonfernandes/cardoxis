/**
 * CARDOXIS - Script da Página de Contacto RF 
 */

(function() {
    'use strict';

    // 1. INICIALIZAR AOS

    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100,
            easing: 'ease-in-out'
        });
    }

    // 2. MAPA COM LEAFLET

    function initMap() {
        const mapContainer = document.getElementById('contactMap');
        
        if (mapContainer && typeof L !== 'undefined') {
            const lat = parseFloat(mapContainer.dataset.lat) || 38.7223;
            const lng = parseFloat(mapContainer.dataset.lng) || -9.1393;
            
            // Criar o mapa
            const map = L.map('contactMap').setView([lat, lng], 15);
            
            // Adicionar camada de tiles
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                subdomains: 'abcd',
                maxZoom: 19,
                minZoom: 3
            }).addTo(map);
            
            // Criar ícone personalizado
            const customIcon = L.divIcon({
                html: '<div class="custom-marker"><i class="fas fa-map-marker-alt"></i></div>',
                iconSize: [48, 48],
                className: 'custom-marker-icon',
                iconAnchor: [24, 48]
            });
            
            // Adicionar marcador
            const marker = L.marker([lat, lng], {
                icon: customIcon
            }).addTo(map);
            
            marker.bindPopup(`
                <div style="font-family: 'Inter', sans-serif; padding: 8px; min-width: 180px;">
                    <strong style="color: #0052CC; font-size: 1rem;">CARDOXIS - Sede</strong><br>
                    <span style="color: #4A5568; font-size: 0.85rem;">Avenida da Liberdade, 245</span><br>
                    <span style="color: #4A5568; font-size: 0.85rem;">1250-143 Lisboa, Portugal</span>
                </div>
            `).openPopup();
            
            // Redimensionar mapa quando a janela mudar
            window.addEventListener('resize', function() {
                setTimeout(function() {
                    map.invalidateSize();
                }, 250);
            });
        }
    }

    // 3. FAQ ACCORDION

    function initFaq() {
        const faqItems = document.querySelectorAll('.faq-item');
        
        faqItems.forEach(function(item) {
            const question = item.querySelector('.faq-question');
            
            if (question) {
                question.addEventListener('click', function() {
                    const isActive = item.classList.contains('active');
                    
                    // Fechar todos
                    faqItems.forEach(function(otherItem) {
                        if (otherItem !== item && otherItem.classList.contains('active')) {
                            otherItem.classList.remove('active');
                            const btn = otherItem.querySelector('.faq-question');
                            if (btn) {
                                btn.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });
                    
                    // Alternar o item clicado
                    if (!isActive) {
                        item.classList.add('active');
                        question.setAttribute('aria-expanded', 'true');
                    } else {
                        item.classList.remove('active');
                        question.setAttribute('aria-expanded', 'false');
                    }
                });
                
                // Suporte para teclado
                question.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        question.click();
                    }
                });
            }
        });
        
        // Abrir o primeiro por padrão
        if (faqItems.length > 0) {
            const firstItem = faqItems[0];
            firstItem.classList.add('active');
            const firstBtn = firstItem.querySelector('.faq-question');
            if (firstBtn) {
                firstBtn.setAttribute('aria-expanded', 'true');
            }
        }
    }

    // 4. MODAL DE CALLBACK

    function initCallbackModal() {
        const modal = document.getElementById('callbackModal');
        const callbackBtn = document.getElementById('callbackBtn');
        const closeBtn = document.getElementById('closeModalBtn');
        const overlay = modal ? modal.querySelector('.modal-overlay') : null;
        
        if (modal && callbackBtn) {
            function openModal() {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                // Focar no primeiro campo
                const firstInput = modal.querySelector('input:not([type="hidden"])');
                if (firstInput) {
                    setTimeout(function() {
                        firstInput.focus();
                    }, 300);
                }
            }
            
            function closeModal() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            callbackBtn.addEventListener('click', openModal);
            
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }
            
            if (overlay) {
                overlay.addEventListener('click', closeModal);
            }
            
            // Fechar com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    closeModal();
                }
            });
        }
    }

    // 5. FORMULÁRIO DE CONTACTO

    function initContactForm() {
        const form = document.getElementById('contactForm');
        
        if (!form) return;
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validar campos
            let isValid = true;
            const fields = [
                { 
                    id: 'name', 
                    message: 'Por favor, insira o seu nome completo.',
                    validate: 'required'
                },
                { 
                    id: 'email', 
                    message: 'Por favor, insira um e-mail válido.',
                    validate: 'email'
                },
                { 
                    id: 'subject', 
                    message: 'Por favor, selecione um assunto.',
                    validate: 'required'
                },
                { 
                    id: 'message', 
                    message: 'Por favor, insira uma mensagem com pelo menos 10 caracteres.',
                    validate: 'minlength',
                    minLength: 10
                }
            ];
            
            // Limpar erros anteriores
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.remove();
            });
            document.querySelectorAll('.form-group input, .form-group select, .form-group textarea').forEach(function(el) {
                el.style.borderColor = '';
            });
            
            fields.forEach(function(field) {
                const input = document.getElementById(field.id);
                if (!input) return;
                
                const value = input.value.trim();
                
                if (field.validate === 'required' && !value) {
                    showError(input, field.message);
                    isValid = false;
                } else if (field.validate === 'email' && value && !isValidEmail(value)) {
                    showError(input, field.message);
                    isValid = false;
                } else if (field.validate === 'minlength' && value.length < field.minLength) {
                    showError(input, field.message);
                    isValid = false;
                } else {
                    clearError(input);
                }
            });
            
            // Validar checkbox de privacidade
            const privacy = document.querySelector('input[name="privacy"]');
            if (privacy && !privacy.checked) {
                showError(privacy, 'Por favor, aceite a Política de Privacidade.');
                isValid = false;
            } else if (privacy) {
                clearError(privacy);
            }
            
            if (!isValid) {
                // Scroll para o primeiro erro
                const firstError = document.querySelector('.error-message');
                if (firstError) {
                    firstError.closest('.form-group').scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }
                return;
            }
            
            // Enviar formulário
            const submitBtn = document.getElementById('contactSubmitBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A enviar...';
            
            try {
                const data = {
                    name: document.getElementById('name').value.trim(),
                    email: document.getElementById('email').value.trim(),
                    subject: document.getElementById('subject').value,
                    message: document.getElementById('message').value.trim(),
                    phone: document.getElementById('phone')?.value?.trim() || '',
                    csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
                };
                
                const response = await fetch('/cardoxis/contact-api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    form.reset();
                    showSuccessMessage('Mensagem enviada com sucesso! Entraremos em contacto em breve.');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    showErrorMessage(result.message || 'Erro ao enviar mensagem. Por favor, tente novamente.');
                }
            } catch (error) {
                console.error('Erro ao enviar formulário:', error);
                showErrorMessage('Erro ao enviar mensagem. Por favor, tente novamente ou contacte-nos por telefone.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    // 6. FORMULÁRIO DE CALLBACK

    function initCallbackForm() {
        const form = document.getElementById('callbackForm');
        
        if (!form) return;
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validar campos
            let isValid = true;
            const fields = [
                { id: 'callbackName', message: 'Por favor, insira o seu nome completo.' },
                { id: 'callbackEmail', message: 'Por favor, insira um e-mail válido.', validate: 'email' },
                { id: 'callbackPhone', message: 'Por favor, insira o seu número de telefone.' }
            ];
            
            // Limpar erros anteriores
            form.querySelectorAll('.error-message').forEach(function(el) {
                el.remove();
            });
            form.querySelectorAll('.form-group input, .form-group select').forEach(function(el) {
                el.style.borderColor = '';
            });
            
            fields.forEach(function(field) {
                const input = document.getElementById(field.id);
                if (!input) return;
                
                const value = input.value.trim();
                
                if (!value) {
                    showError(input, field.message);
                    isValid = false;
                } else if (field.validate === 'email' && !isValidEmail(value)) {
                    showError(input, field.message);
                    isValid = false;
                } else {
                    clearError(input);
                }
            });
            
            if (!isValid) {
                const firstError = form.querySelector('.error-message');
                if (firstError) {
                    firstError.closest('.form-group').scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }
                return;
            }
            
            // Enviar formulário
            const submitBtn = document.getElementById('callbackSubmitBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A enviar...';
            
            try {
                const data = {
                    name: document.getElementById('callbackName').value.trim(),
                    email: document.getElementById('callbackEmail').value.trim(),
                    phone: document.getElementById('callbackPhone').value.trim(),
                    time: document.getElementById('callbackTime').value,
                    csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
                };
                
                const response = await fetch('/cardoxis/contact-api.php?callback=1', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Fechar modal
                    const modal = document.getElementById('callbackModal');
                    if (modal) {
                        modal.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                    form.reset();
                    showSuccessMessage('Callback solicitado com sucesso! Entraremos em contacto em breve.');
                } else {
                    showErrorMessage(result.message || 'Erro ao solicitar callback. Por favor, tente novamente.');
                }
            } catch (error) {
                console.error('Erro ao solicitar callback:', error);
                showErrorMessage('Erro ao solicitar callback. Por favor, tente novamente.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    // 7. FUNÇÕES AUXILIARES
    
    // Validar email
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    // Mostrar erro
    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        if (!formGroup) return;
        
        // Remover erro existente
        const existingError = formGroup.querySelector('.error-message');
        if (existingError) existingError.remove();
        
        // Criar mensagem de erro
        const error = document.createElement('span');
        error.className = 'error-message';
        error.style.cssText = 'color: #EF4444; font-size: 0.75rem; margin-top: 4px; display: block;';
        error.textContent = message;
        
        // Destacar input
        input.style.borderColor = '#EF4444';
        
        // Adicionar após o input
        if (input.type === 'checkbox') {
            formGroup.appendChild(error);
        } else {
            input.parentNode.appendChild(error);
        }
    }
    
    // Limpar erro
    function clearError(input) {
        const formGroup = input.closest('.form-group');
        if (!formGroup) return;
        
        const existingError = formGroup.querySelector('.error-message');
        if (existingError) existingError.remove();
        
        input.style.borderColor = '';
    }
    
    // Mostrar mensagem de sucesso
    function showSuccessMessage(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success';
        alert.style.cssText = `
            position: fixed;
            top: 100px;
            right: 24px;
            z-index: 9999;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 420px;
            min-width: 300px;
            animation: slideInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: 'Inter', sans-serif;
        `;
        alert.innerHTML = `
            <i class="fas fa-check-circle" style="font-size: 1.3rem; flex-shrink: 0;"></i>
            <span style="flex: 1; font-size: 0.9rem; font-weight: 500;">${message}</span>
            <button onclick="this.parentElement.remove()" 
                    style="background: rgba(255,255,255,0.15); border: none; color: white; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-size: 1rem; transition: all 0.15s ease; display: flex; align-items: center; justify-content: center;">
                &times;
            </button>
        `;
        document.body.appendChild(alert);
        
        // Auto-fechar após 5 segundos
        setTimeout(function() {
            if (alert.parentElement) {
                alert.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(function() {
                    if (alert.parentElement) alert.remove();
                }, 300);
            }
        }, 5000);
    }
    
    // Mostrar mensagem de erro
    function showErrorMessage(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.style.cssText = `
            position: fixed;
            top: 100px;
            right: 24px;
            z-index: 9999;
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 420px;
            min-width: 300px;
            animation: slideInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: 'Inter', sans-serif;
        `;
        alert.innerHTML = `
            <i class="fas fa-exclamation-circle" style="font-size: 1.3rem; flex-shrink: 0;"></i>
            <span style="flex: 1; font-size: 0.9rem; font-weight: 500;">${message}</span>
            <button onclick="this.parentElement.remove()" 
                    style="background: rgba(255,255,255,0.15); border: none; color: white; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-size: 1rem; transition: all 0.15s ease; display: flex; align-items: center; justify-content: center;">
                &times;
            </button>
        `;
        document.body.appendChild(alert);
        
        // Auto-fechar após 5 segundos
        setTimeout(function() {
            if (alert.parentElement) {
                alert.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(function() {
                    if (alert.parentElement) alert.remove();
                }, 300);
            }
        }, 5000);
    }

    // 8. AUTO-FECHAR ALERTAS

    function autoHideAlert() {
        const alert = document.getElementById('alertMessage');
        if (alert) {
            setTimeout(function() {
                alert.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            }, 5000);
        }
    }

    // 9. NAVBAR

    function initNavbar() {
        const navbar = document.getElementById('navbar');
        if (navbar) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });
        }
    }

    // 10. BOTÃO VOLTAR AO TOPO

    function initBackToTop() {
        const backToTop = document.getElementById('backToTop');
        if (backToTop) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 300) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            });
            
            backToTop.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    }

    // 11. MENU MOBILE

    function initMobileMenu() {
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
                hamburger.setAttribute('aria-expanded', 'true');
            }
            
            function closeMenuFn() {
                mobileMenu.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
            }
            
            hamburger.addEventListener('click', openMenu);
            
            if (closeMenu) {
                closeMenu.addEventListener('click', closeMenuFn);
            }
            
            overlay.addEventListener('click', closeMenuFn);
            
            // Fechar com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                    closeMenuFn();
                }
            });
        }
    }

    // 12. ADICIONAR ESTILOS DINÂMICOS

    function addDynamicStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* Animações */
            @keyframes slideInRight {
                from {
                    transform: translateX(120%) scale(0.95);
                    opacity: 0;
                }
                to {
                    transform: translateX(0) scale(1);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0) scale(1);
                    opacity: 1;
                }
                to {
                    transform: translateX(120%) scale(0.95);
                    opacity: 0;
                }
            }
            
            /* Marcador do Mapa */
            .custom-marker-icon {
                background: none;
                border: none;
            }
            
            .custom-marker {
                background: #0052CC;
                width: 48px;
                height: 48px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 1.3rem;
                box-shadow: 0 4px 20px rgba(0, 82, 204, 0.4);
                transition: transform 0.3s ease;
                border: 3px solid white;
            }
            
            .custom-marker:hover {
                transform: scale(1.15);
            }
            
            /* Popup do Mapa */
            .leaflet-popup-content-wrapper {
                border-radius: 12px;
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            }
            
            .leaflet-popup-tip {
                background: white;
            }
        `;
        document.head.appendChild(style);
    }

    // 13. INICIALIZAR TUDO

    document.addEventListener('DOMContentLoaded', function() {
        addDynamicStyles();
        initMap();
        initFaq();
        initCallbackModal();
        initContactForm();
        initCallbackForm();
        autoHideAlert();
        initNavbar();
        initBackToTop();
        initMobileMenu();
    });

})();