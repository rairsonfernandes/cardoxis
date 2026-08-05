/**
 * CARDOXIS - Reset Password RF
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
        form: document.getElementById('resetForm'),
        password: document.getElementById('password'),
        passwordConfirm: document.getElementById('password_confirm'),
        resetBtn: document.getElementById('resetBtn'),
        togglePasswordBtn: document.getElementById('togglePasswordBtn'),
        toggleConfirmBtn: document.getElementById('toggleConfirmBtn'),
        strengthBars: document.querySelectorAll('.strength-bar'),
        strengthText: document.getElementById('strengthText'),
        matchText: document.querySelector('.match-text'),
        alertContainer: document.getElementById('alertContainer'),
        loadingOverlay: document.getElementById('loadingOverlay'),
        csrfToken: document.querySelector('input[name="csrf_token"]'),
        reqLength: document.getElementById('req-length'),
        reqUpper: document.getElementById('req-upper'),
        reqLower: document.getElementById('req-lower'),
        reqNumber: document.getElementById('req-number'),
        reqSpecial: document.getElementById('req-special')
    };
    

    // FUNÇÕES DE UI - ALERTAS 
    
    function showAlert(type, title, message, duration = 5000) {
        const container = elements.alertContainer;
        if (!container) return;
        
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
        
        if (elements.resetBtn) {
            elements.resetBtn.disabled = true;
            elements.resetBtn.innerHTML = `
                <i class="fas fa-spinner fa-spin"></i>
                <span>A redefinir...</span>
            `;
        }
    }
    
    function hideLoading() {
        const overlay = elements.loadingOverlay;
        if (overlay) overlay.classList.remove('active');
        
        if (elements.resetBtn) {
            elements.resetBtn.disabled = false;
            elements.resetBtn.innerHTML = `
                <i class="fas fa-save"></i>
                <span>Redefinir Senha</span>
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
        field.classList.remove('input-error', 'input-success');
        
        const wrapper = field.closest('.input-wrapper');
        if (!wrapper) return;
        
        const errorMsg = wrapper.parentElement?.querySelector('.error-msg');
        if (errorMsg) errorMsg.remove();
    }
    
    function setFieldSuccess(field) {
        if (!field) return;
        field.classList.remove('input-error');
        field.classList.add('input-success');
    }
    
    function clearAllErrors() {
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
        document.querySelectorAll('.input-success').forEach(el => el.classList.remove('input-success'));
        document.querySelectorAll('.error-msg').forEach(el => el.remove());
    }

    // PASSWORD STRENGTH
    
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[$@#&!]/.test(password)) strength++;
        return strength;
    }
    
    function updatePasswordStrength() {
        const password = elements.password?.value || '';
        const strength = checkPasswordStrength(password);
        
        // Update bars
        elements.strengthBars.forEach(bar => {
            bar.className = 'strength-bar';
            bar.style.background = '';
        });
        
        if (password.length === 0) {
            if (elements.strengthText) elements.strengthText.textContent = '';
            return;
        }
        
        const levels = [
            { min: 0, max: 2, class: 'weak', text: 'Senha fraca', color: '#EF4444' },
            { min: 3, max: 3, class: 'medium', text: 'Senha média', color: '#F59E0B' },
            { min: 4, max: 4, class: 'strong', text: 'Senha forte', color: '#10B981' },
            { min: 5, max: 5, class: 'very-strong', text: 'Senha muito forte', color: '#059669' }
        ];
        
        let level = levels[0];
        for (const l of levels) {
            if (strength >= l.min && strength <= l.max) {
                level = l;
                break;
            }
        }
        
        const count = Math.min(strength, elements.strengthBars.length);
        for (let i = 0; i < elements.strengthBars.length; i++) {
            if (i < count) {
                elements.strengthBars[i].classList.add(level.class);
                elements.strengthBars[i].style.background = level.color;
            }
        }
        
        if (elements.strengthText) elements.strengthText.textContent = level.text;
        
        // Update requirements
        updateRequirements(password);
    }
    
    function updateRequirements(password) {
        const checks = [
            { element: elements.reqLength, valid: password.length >= 6 },
            { element: elements.reqUpper, valid: /[A-Z]/.test(password) },
            { element: elements.reqLower, valid: /[a-z]/.test(password) },
            { element: elements.reqNumber, valid: /[0-9]/.test(password) },
            { element: elements.reqSpecial, valid: /[$@#&!]/.test(password) }
        ];
        
        checks.forEach(({ element, valid }) => {
            if (!element) return;
            const icon = element.querySelector('i');
            if (valid) {
                element.classList.add('valid');
                if (icon) icon.className = 'fas fa-check-circle';
            } else {
                element.classList.remove('valid');
                if (icon) icon.className = 'fas fa-circle';
            }
        });
    }

    // PASSWORD MATCH

    function updatePasswordMatch() {
        const password = elements.password?.value || '';
        const confirm = elements.passwordConfirm?.value || '';
        
        if (!elements.matchText) return;
        
        if (confirm.length === 0) {
            elements.matchText.textContent = '';
            elements.matchText.className = 'match-text';
            return;
        }
        
        if (password === confirm) {
            elements.matchText.innerHTML = '<i class="fas fa-check-circle"></i> As senhas coincidem';
            elements.matchText.className = 'match-text match-valid';
            clearFieldError(elements.passwordConfirm);
        } else {
            elements.matchText.innerHTML = '<i class="fas fa-times-circle"></i> As senhas não coincidem';
            elements.matchText.className = 'match-text match-invalid';
        }
    }

    // TOGGLE PASSWORD VISIBILITY
    
    function togglePasswordVisibility(field, button) {
        if (!field) return;
        const isPassword = field.type === 'password';
        field.type = isPassword ? 'text' : 'password';
        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
        button.setAttribute('aria-label', isPassword ? 'Ocultar senha' : 'Mostrar senha');
    }

    // UTILITÁRIOS
    
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    function getCsrfToken() {
        return elements.csrfToken ? elements.csrfToken.value : '';
    }
    
    // API REQUESTS
    
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
        
        const password = elements.password?.value || '';
        const passwordConfirm = elements.passwordConfirm?.value || '';
        
        clearAlerts();
        clearAllErrors();
        
        // Validação
        let hasError = false;
        
        if (!password) {
            showFieldError(elements.password, 'Senha é obrigatória');
            hasError = true;
        } else if (password.length < CONFIG.MIN_PASSWORD_LENGTH) {
            showFieldError(elements.password, `A senha deve ter no mínimo ${CONFIG.MIN_PASSWORD_LENGTH} caracteres`);
            hasError = true;
        }
        
        if (!passwordConfirm) {
            showFieldError(elements.passwordConfirm, 'Confirmação de senha é obrigatória');
            hasError = true;
        } else if (password !== passwordConfirm) {
            showFieldError(elements.passwordConfirm, 'As senhas não coincidem');
            hasError = true;
        }
        
        if (hasError) return;
        
        showLoading();
        
        try {
            const result = await apiRequest('/auth/reset-password', {
                password: password,
                password_confirm: passwordConfirm,
                csrf_token: getCsrfToken()
            });
            
            if (result.success) {
                showAlert('success', 'Senha redefinida!', 
                    'A sua senha foi atualizada com sucesso. Redirecionando para o login...', 3000);
                
                // Limpar campos
                if (elements.password) elements.password.value = '';
                if (elements.passwordConfirm) elements.passwordConfirm.value = '';
                
                setTimeout(() => {
                    window.location.href = `${CONFIG.REDIRECT_URL}/login?reset=1`;
                }, 3000);
            }
            
        } catch (error) {
            console.error('[CARDOXIS] Reset password error:', error);
            
            let errorMessage = error.message || 'Erro ao redefinir senha. Tente novamente.';
            
            if (errorMessage.toLowerCase().includes('expirado')) {
                errorMessage = 'Sessão expirada. Solicite um novo código.';
            } else if (errorMessage.toLowerCase().includes('csr') || errorMessage.toLowerCase().includes('token')) {
                errorMessage = 'Token de segurança inválido. Recarregue a página.';
            }
            
            showAlert('error', 'Erro', errorMessage);
            hideLoading();
        }
    }

    // EVENT LISTENERS
    
    function setupEventListeners() {
        // Submit do formulário
        if (elements.form) {
            elements.form.addEventListener('submit', handleSubmit);
        }
        
        // Password strength
        if (elements.password) {
            elements.password.addEventListener('input', function() {
                updatePasswordStrength();
                updatePasswordMatch();
                clearFieldError(this);
            });
        }
        
        // Password confirm
        if (elements.passwordConfirm) {
            elements.passwordConfirm.addEventListener('input', function() {
                updatePasswordMatch();
                clearFieldError(this);
            });
        }
        
        // Toggle password
        if (elements.togglePasswordBtn && elements.password) {
            elements.togglePasswordBtn.addEventListener('click', function() {
                togglePasswordVisibility(elements.password, this);
            });
        }
        
        if (elements.toggleConfirmBtn && elements.passwordConfirm) {
            elements.toggleConfirmBtn.addEventListener('click', function() {
                togglePasswordVisibility(elements.passwordConfirm, this);
            });
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
        console.log('[CARDOXIS] Reset Password Module v2.0.0 loaded');
        
        setupEventListeners();
        
        // Foco no campo de senha
        if (elements.password) {
            setTimeout(() => elements.password.focus(), 300);
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