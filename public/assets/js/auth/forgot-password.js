/**
 * CARDOXIS - Forgot Password RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES
    
    const CONFIG = {
        API_URL: '/cardoxis/api/v1',
        REDIRECT_URL: '/cardoxis',
        MIN_PASSWORD_LENGTH: 6
    };
    
    // DOM ELEMENTS
    
    const elements = {
        form: document.getElementById('forgotForm'),
        email: document.getElementById('email'),
        submitBtn: document.getElementById('forgotBtn'),
        alertContainer: document.getElementById('alertContainer'),
        loadingOverlay: document.getElementById('loadingOverlay')
    };
   
    // FUNÇÕES DE UI - ALERTAS 
    
    function showAlert(type, title, message, duration = 5000) {
        const container = elements.alertContainer;
        if (!container) return;
        
        // Remover alertas anteriores com animação
        const existingAlerts = container.querySelectorAll('.alert');
        existingAlerts.forEach(alert => {
            alert.classList.add('alert-closing');
            setTimeout(() => {
                if (alert.parentElement) alert.remove();
            }, 300);
        });
        
        const iconMap = {
            'error': 'fa-exclamation-circle',
            'success': 'fa-check-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.setAttribute('role', 'alert');
        
        alertDiv.innerHTML = `
            <div class="alert-icon">
                <i class="fas ${iconMap[type] || 'fa-info-circle'}"></i>
            </div>
            <div class="alert-content">
                <span class="alert-title">${escapeHtml(title)}</span>
                <span class="alert-message">${escapeHtml(message)}</span>
            </div>
            <button class="alert-close" aria-label="Fechar alerta">
                <i class="fas fa-times"></i>
            </button>
            <div class="alert-progress"></div>
        `;
        
        const closeBtn = alertDiv.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                alertDiv.classList.add('alert-closing');
                setTimeout(() => {
                    if (alertDiv.parentElement) alertDiv.remove();
                }, 300);
            });
        }
        
        container.appendChild(alertDiv);
        
        if (duration > 0) {
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    alertDiv.classList.add('alert-closing');
                    setTimeout(() => {
                        if (alertDiv.parentElement) alertDiv.remove();
                    }, 300);
                }
            }, duration);
        }
    }
    
    function clearAlerts() {
        const container = elements.alertContainer;
        if (container) {
            const alerts = container.querySelectorAll('.alert');
            alerts.forEach(alert => alert.remove());
        }
    }

    // FUNÇÕES DE UI - LOADING
    
    function showLoading() {
        const overlay = elements.loadingOverlay;
        if (overlay) overlay.classList.add('active');
        
        if (elements.submitBtn) {
            elements.submitBtn.disabled = true;
            elements.submitBtn.innerHTML = `
                <i class="fas fa-spinner fa-spin"></i>
                <span>A enviar...</span>
            `;
        }
    }
    
    function hideLoading() {
        const overlay = elements.loadingOverlay;
        if (overlay) overlay.classList.remove('active');
        
        if (elements.submitBtn) {
            elements.submitBtn.disabled = false;
            elements.submitBtn.innerHTML = `
                <i class="fas fa-paper-plane"></i>
                <span>Enviar Código</span>
            `;
        }
    }
    
    // FUNÇÕES DE UI - VALIDAÇÃO
    
    function showFieldError(field, message) {
        if (!field) return;
        clearFieldError(field);
        field.classList.add('input-error');
        
        const wrapper = field.closest('.input-wrapper');
        if (!wrapper) return;
        
        const existingError = wrapper.parentElement?.querySelector('.error-msg');
        if (existingError) existingError.remove();
        
        const errorSpan = document.createElement('span');
        errorSpan.className = 'error-msg';
        errorSpan.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${escapeHtml(message)}`;
        
        wrapper.parentElement?.appendChild(errorSpan);
    }
    
    function clearFieldError(field) {
        if (!field) return;
        field.classList.remove('input-error');
        
        const wrapper = field.closest('.input-wrapper');
        if (!wrapper) return;
        
        const errorMsg = wrapper.parentElement?.querySelector('.error-msg');
        if (errorMsg) errorMsg.remove();
    }
    
    function clearAllErrors() {
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
        document.querySelectorAll('.error-msg').forEach(el => el.remove());
    }

    // UTILITÁRIOS
    
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    function validateEmail(email) {
        return /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(email);
    }
    
    function getCsrfToken() {
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }
    
    // API REQUEST
    
    async function apiRequest(endpoint, data) {
        const url = `${CONFIG.API_URL}${endpoint}`;
        const csrfToken = getCsrfToken();
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(data),
            credentials: 'same-origin'
        });
        
        const responseText = await response.text();
        
        if (!responseText || responseText.trim() === '') {
            throw new Error('Erro interno do servidor. Tente novamente.');
        }
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('Erro ao parsear JSON:', e);
            throw new Error('Erro de comunicação com o servidor');
        }
        
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Erro ao processar pedido');
        }
        
        return result;
    }

    // HANDLER DO FORMULÁRIO
    
    async function handleSubmit(event) {
        event.preventDefault();
        
        const email = elements.email?.value?.trim() || '';
        const csrfToken = getCsrfToken();
        
        clearAllErrors();
        clearAlerts();
        
        // Validação
        if (!email) {
            showFieldError(elements.email, 'Email é obrigatório');
            return;
        }
        
        if (!validateEmail(email)) {
            showFieldError(elements.email, 'Email inválido');
            return;
        }
        
        // Enviar requisição
        showLoading();
        
        try {
            const result = await apiRequest('/auth/forgot-password', {
                email: email,
                csrf_token: csrfToken
            });
            
            if (result.success) {
                showAlert('success', 'Código enviado!', 
                    'Um código de verificação foi enviado para o seu email. Verifique a sua caixa de entrada.', 
                    6000
                );
                
                // Redirecionar após delay
                setTimeout(() => {
                    window.location.href = `${CONFIG.REDIRECT_URL}/verify-pin?email=${encodeURIComponent(email)}`;
                }, 3000);
            }
            
        } catch (error) {
            console.error('[CARDOXIS] Forgot password error:', error);
            
            let errorMessage = error.message || 'Erro ao enviar código. Tente novamente.';
            
            if (errorMessage.toLowerCase().includes('not found') || errorMessage.toLowerCase().includes('encontrado')) {
                errorMessage = 'Email não encontrado. Verifique e tente novamente.';
            } else if (errorMessage.toLowerCase().includes('rate limit') || errorMessage.toLowerCase().includes('tentativas')) {
                errorMessage = 'Muitas tentativas. Aguarde alguns minutos.';
            } else if (errorMessage.toLowerCase().includes('csr') || errorMessage.toLowerCase().includes('token')) {
                errorMessage = 'Token de segurança inválido. Recarregue a página.';
            }
            
            showAlert('error', 'Erro', errorMessage);
            hideLoading();
            
            if (elements.email) {
                elements.email.focus();
                elements.email.select();
            }
        }
    }

    // EVENT LISTENERS

    function setupEventListeners() {
        // Submit do formulário
        if (elements.form) {
            elements.form.addEventListener('submit', handleSubmit);
        }
        
        // Limpar erro ao digitar
        if (elements.email) {
            elements.email.addEventListener('input', () => clearFieldError(elements.email));
            elements.email.addEventListener('focus', () => clearFieldError(elements.email));
        }
        
        // Fechar alertas com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.classList.add('alert-closing');
                    setTimeout(() => {
                        if (alert.parentElement) alert.remove();
                    }, 300);
                });
            }
        });
    }

    // INICIALIZAÇÃO
    
    function init() {
        console.log('[CARDOXIS] Forgot Password Module v3.0.0 loaded');
        
        setupEventListeners();
        
        // Foco no campo de email
        if (elements.email) {
            setTimeout(() => elements.email.focus(), 300);
        }
        
        // Auto-close para alertas do servidor
        const serverAlert = document.getElementById('serverAlert');
        if (serverAlert) {
            setTimeout(() => {
                serverAlert.classList.add('alert-closing');
                setTimeout(() => {
                    if (serverAlert.parentElement) serverAlert.remove();
                }, 300);
            }, 8000);
        }
        
        hideLoading();
    }
    
    // Iniciar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();