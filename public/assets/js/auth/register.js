/**
 * CARDOXIS - Register RF
 */

(function() {
    'use strict';

    // CONFIGURAÇÕES
    
    const CONFIG = {
        API_URL: '/cardoxis/api/v1',
        REDIRECT_URL: '/cardoxis',
        MIN_PASSWORD_LENGTH: 6,
        MAX_REGISTER_ATTEMPTS: 5,
        LOCKOUT_DURATION: 900000,
        PASSWORD_CHARS: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+-='
    };
    
    // STORAGE KEYS
    
    const STORAGE_KEYS = {
        REGISTER_ATTEMPTS: 'cardoxis_register_attempts',
        LOCKOUT_UNTIL: 'cardoxis_register_lockout'
    };

    // DOM ELEMENTS

    let iti = null;
    
    const elements = {
        form: document.getElementById('registerForm'),
        name: document.getElementById('name'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        password: document.getElementById('password'),
        passwordConfirm: document.getElementById('password_confirm'),
        termsCheckbox: document.getElementById('terms'),
        registerBtn: document.getElementById('registerBtn'),
        loadingOverlay: document.getElementById('loadingOverlay'),
        alertContainer: document.getElementById('alertContainer'),
        strengthBars: document.querySelectorAll('.strength-bar'),
        strengthText: document.getElementById('strengthText'),
        generateBtn: document.getElementById('generatePasswordBtn')
    };


    // FUNÇÕES DE SEGURANÇA - RATE LIMITING
    
    function getRegisterAttempts() {
        const attempts = localStorage.getItem(STORAGE_KEYS.REGISTER_ATTEMPTS);
        return attempts ? parseInt(attempts, 10) : 0;
    }
    
    function incrementRegisterAttempts() {
        const attempts = getRegisterAttempts() + 1;
        localStorage.setItem(STORAGE_KEYS.REGISTER_ATTEMPTS, attempts.toString());
        
        if (attempts >= CONFIG.MAX_REGISTER_ATTEMPTS) {
            const lockoutUntil = Date.now() + CONFIG.LOCKOUT_DURATION;
            localStorage.setItem(STORAGE_KEYS.LOCKOUT_UNTIL, lockoutUntil.toString());
        }
        return attempts;
    }
    
    function resetRegisterAttempts() {
        localStorage.removeItem(STORAGE_KEYS.REGISTER_ATTEMPTS);
        localStorage.removeItem(STORAGE_KEYS.LOCKOUT_UNTIL);
    }
    
    function isLockedOut() {
        const lockoutUntil = localStorage.getItem(STORAGE_KEYS.LOCKOUT_UNTIL);
        if (!lockoutUntil) return false;
        
        const lockoutTime = parseInt(lockoutUntil, 10);
        if (Date.now() > lockoutTime) {
            resetRegisterAttempts();
            return false;
        }
        
        const remainingMinutes = Math.ceil((lockoutTime - Date.now()) / 60000);
        showAlert('error', 'Tentativas excedidas', 
            `Muitas tentativas de registo. Tente novamente em ${remainingMinutes} minutos.`);
        return true;
    }
    

    // FUNÇÕES DE UI - LOADING
    
    function showLoading() {
        if (elements.loadingOverlay) elements.loadingOverlay.classList.add('active');
        if (elements.registerBtn) {
            elements.registerBtn.disabled = true;
            elements.registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A processar...';
        }
    }
    
    function hideLoading() {
        if (elements.loadingOverlay) elements.loadingOverlay.classList.remove('active');
        if (elements.registerBtn) {
            elements.registerBtn.disabled = false;
            elements.registerBtn.innerHTML = '<i class="fas fa-user-plus"></i> Criar Conta';
        }
    }

    // FUNÇÕES DE UI - ALERTAS 
    
    function showAlert(type, title, message, duration = 5000) {
        if (!elements.alertContainer) return;
        
        // Remover alertas anteriores com animação
        const existingAlerts = elements.alertContainer.querySelectorAll('.alert');
        existingAlerts.forEach(alert => {
            alert.classList.add('alert-closing');
            setTimeout(() => {
                if (alert.parentElement) alert.remove();
            }, 300);
        });
        
        // Mapear tipos para ícones
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
        
        // Botão de fechar
        const closeBtn = alertDiv.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                alertDiv.classList.add('alert-closing');
                setTimeout(() => {
                    if (alertDiv.parentElement) alertDiv.remove();
                }, 300);
            });
        }
        
        elements.alertContainer.appendChild(alertDiv);
        
        // Auto fechar
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
        if (elements.alertContainer) {
            const alerts = elements.alertContainer.querySelectorAll('.alert');
            alerts.forEach(alert => alert.remove());
        }
    }

    // FUNÇÕES DE UI - VALIDAÇÃO DE CAMPOS
    
    function showFieldError(field, message) {
        clearFieldError(field);
        field.classList.add('input-error');
        
        const errorSpan = document.createElement('span');
        errorSpan.className = 'error-msg';
        errorSpan.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${escapeHtml(message)}`;
        
        const inputWrapper = field.closest('.input-wrapper');
        if (inputWrapper) {
            inputWrapper.appendChild(errorSpan);
        } else {
            field.parentElement.appendChild(errorSpan);
        }
    }
    
    function clearFieldError(field) {
        field.classList.remove('input-error');
        const inputWrapper = field.closest('.input-wrapper');
        if (inputWrapper) {
            const errorMsg = inputWrapper.querySelector('.error-msg');
            if (errorMsg) errorMsg.remove();
        }
    }
    
    function clearAllErrors() {
        const errorInputs = document.querySelectorAll('.input-error');
        errorInputs.forEach(input => input.classList.remove('input-error'));
        const errorMessages = document.querySelectorAll('.error-msg');
        errorMessages.forEach(msg => msg.remove());
    }


    // GERAR SENHA FORTE

    function generateStrongPassword() {
        const length = 14;
        let password = '';
        const chars = CONFIG.PASSWORD_CHARS;
        
        const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const lower = 'abcdefghijklmnopqrstuvwxyz';
        const digits = '0123456789';
        const specials = '!@#$%^&*()_+-=';
        
        password += upper[Math.floor(Math.random() * upper.length)];
        password += lower[Math.floor(Math.random() * lower.length)];
        password += digits[Math.floor(Math.random() * digits.length)];
        password += specials[Math.floor(Math.random() * specials.length)];
        
        for (let i = password.length; i < length; i++) {
            password += chars[Math.floor(Math.random() * chars.length)];
        }
        
        password = password.split('').sort(() => Math.random() - 0.5).join('');
        
        return password;
    }
    
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showAlert('success', 'Copiado!', 'Senha copiada para a área de transferência.', 2000);
            }).catch(() => {
                fallbackCopy(text);
            });
        } else {
            fallbackCopy(text);
        }
    }
    
    function fallbackCopy(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showAlert('success', 'Copiado!', 'Senha copiada para a área de transferência.', 2000);
        } catch (e) {
            showAlert('warning', 'Copiar manualmente', 'Selecione e copie a senha gerada.');
        }
        document.body.removeChild(textarea);
    }
    
    function handleGeneratePassword() {
        if (!elements.password) return;
        
        const newPassword = generateStrongPassword();
        elements.password.value = newPassword;
        updatePasswordStrength();
        
        showAlert('info', 'Senha gerada com sucesso!', 
            `Senha: ${newPassword}`, 6000);
        
        // Atualizar campo de confirmação
        if (elements.passwordConfirm) {
            elements.passwordConfirm.value = newPassword;
            updatePasswordMatch();
        }
    }
    

    // VALIDAÇÕES
    
    function validateName(name) {
        return name && name.trim().length >= 3;
    }
    
    function validateEmail(email) {
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return emailRegex.test(email);
    }
    
    function validatePhone(phone) {
        if (!phone) return true;
        if (!iti) return phone.replace(/\s/g, '').length >= 9;
        return iti.isValidNumber();
    }
    
    function validatePassword(password) {
        return password && password.length >= CONFIG.MIN_PASSWORD_LENGTH;
    }
    
    function validatePasswordMatch(password, confirm) {
        return password === confirm;
    }
    
    // PASSWORD STRENGTH METER
    
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
        
        if (!elements.strengthBars.length) return;
        
        elements.strengthBars.forEach(bar => {
            bar.className = 'strength-bar';
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
    }
    
    function updatePasswordMatch() {
        const password = elements.password?.value || '';
        const confirm = elements.passwordConfirm?.value || '';
        
        if (confirm.length === 0) {
            clearFieldError(elements.passwordConfirm);
            return;
        }
        
        if (password !== confirm) {
            showFieldError(elements.passwordConfirm, 'As palavras-passe não coincidem');
        } else {
            clearFieldError(elements.passwordConfirm);
        }
    }
    
    // TOGGLE PASSWORD VISIBILITY - CORRIGIDO
    
    function togglePasswordVisibility(button) {
        const targetId = button.getAttribute('data-target');
        if (!targetId) return;
        
        const field = document.getElementById(targetId);
        if (!field) return;
        
        const isPassword = field.type === 'password';
        field.type = isPassword ? 'text' : 'password';
        
        // Toggle icon
        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
        
        // Update aria-label
        button.setAttribute('aria-label', isPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe');
    }
    
    // INTL TEL INPUT
    
    function initIntlTelInput() {
        if (!elements.phone || typeof intlTelInput === 'undefined') return;
        
        try {
            iti = intlTelInput(elements.phone, {
                initialCountry: 'pt',
                preferredCountries: ['pt', 'br', 'us', 'es', 'fr', 'de', 'it', 'uk', 'nl', 'be'],
                separateDialCode: true,
                utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js',
                autoPlaceholder: 'polite',
                formatOnDisplay: true,
                nationalMode: false,
                placeholderNumberType: 'MOBILE'
            });
            
            elements.phone.addEventListener('countrychange', function() {
                clearFieldError(elements.phone);
            });
        } catch (error) {
            console.warn('IntlTelInput initialization failed:', error);
        }
    }
    
    function getFullPhoneNumber() {
        if (!elements.phone || !elements.phone.value) return '';
        if (iti && iti.getNumber) {
            return iti.getNumber();
        }
        return elements.phone.value.replace(/\s/g, '');
    }

    // ESCAPE HTML (XSS PROTECTION)

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // API REGISTER REQUEST
    
    async function performRegister(formData) {
        const response = await fetch(`${CONFIG.API_URL}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData),
            credentials: 'same-origin'
        });
        
        const textResponse = await response.text();
        let data;
        
        try {
            data = JSON.parse(textResponse);
        } catch (e) {
            console.error('Resposta não é JSON válido:', textResponse);
            throw new Error('Erro de comunicação com o servidor');
        }
        
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Erro ao criar conta');
        }
        
        return data;
    }

    // FORM SUBMISSION HANDLER
    
    async function handleSubmit(event) {
        event.preventDefault();
        
        if (isLockedOut()) return;
        
        const name = elements.name?.value?.trim() || '';
        const email = elements.email?.value?.trim() || '';
        const phone = getFullPhoneNumber();
        const password = elements.password?.value || '';
        const passwordConfirm = elements.passwordConfirm?.value || '';
        const termsAccepted = elements.termsCheckbox?.checked || false;
        
        clearAllErrors();
        clearAlerts();
        
        let hasError = false;
        
        if (!name) {
            showFieldError(elements.name, 'Nome completo é obrigatório');
            hasError = true;
        } else if (!validateName(name)) {
            showFieldError(elements.name, 'Nome deve ter pelo menos 3 caracteres');
            hasError = true;
        }
        
        if (!email) {
            showFieldError(elements.email, 'Email é obrigatório');
            hasError = true;
        } else if (!validateEmail(email)) {
            showFieldError(elements.email, 'Email inválido');
            hasError = true;
        }
        
        if (phone && !validatePhone(phone)) {
            showFieldError(elements.phone, 'Número de telefone inválido');
            hasError = true;
        }
        
        if (!password) {
            showFieldError(elements.password, 'Senha é obrigatória');
            hasError = true;
        } else if (!validatePassword(password)) {
            showFieldError(elements.password, `Senha deve ter no mínimo ${CONFIG.MIN_PASSWORD_LENGTH} caracteres`);
            hasError = true;
        }
        
        if (!passwordConfirm) {
            showFieldError(elements.passwordConfirm, 'Confirmação de senha é obrigatória');
            hasError = true;
        } else if (!validatePasswordMatch(password, passwordConfirm)) {
            showFieldError(elements.passwordConfirm, 'As senhas não coincidem');
            hasError = true;
        }
        
        if (!termsAccepted) {
            showAlert('error', 'Aceite os termos', 'É necessário aceitar os termos e condições para se registar.');
            hasError = true;
        }
        
        if (hasError) return;
        
        incrementRegisterAttempts();
        showLoading();
        
        try {
            const result = await performRegister({
                name: name,
                email: email,
                password: password,
                phone: phone || null
            });
            
            if (result.success) {
                resetRegisterAttempts();
                showAlert('success', 'Conta criada com sucesso!', 
                    'Redirecionando para a página de login...', 2000);
                setTimeout(() => {
                    window.location.href = `${CONFIG.REDIRECT_URL}/login`;
                }, 2000);
            }
        } catch (error) {
            console.error('[CARDOXIS] Erro no registo:', error);
            showAlert('error', 'Erro no registo', error.message);
            hideLoading();
        }
    }

    // REAL-TIME VALIDATION
    
    function setupRealTimeValidation() {
        if (elements.name) {
            elements.name.addEventListener('input', () => {
                if (elements.name.value.trim().length >= 3) clearFieldError(elements.name);
            });
        }
        
        if (elements.email) {
            elements.email.addEventListener('input', () => {
                if (validateEmail(elements.email.value.trim())) clearFieldError(elements.email);
            });
        }
        
        if (elements.phone) {
            elements.phone.addEventListener('input', () => {
                if (!elements.phone.value || validatePhone(elements.phone.value)) {
                    clearFieldError(elements.phone);
                }
            });
        }
        
        if (elements.password) {
            elements.password.addEventListener('input', () => {
                updatePasswordStrength();
                if (validatePassword(elements.password.value)) clearFieldError(elements.password);
                updatePasswordMatch();
            });
        }
        
        if (elements.passwordConfirm) {
            elements.passwordConfirm.addEventListener('input', updatePasswordMatch);
        }
    }

    // SETUP PASSWORD TOGGLES - CORRIGIDO

    function setupPasswordToggles() {
        const toggleButtons = document.querySelectorAll('.toggle-password-icon');
        toggleButtons.forEach(button => {
            // Remover event listeners antigos
            button.removeEventListener('click', () => {});
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                togglePasswordVisibility(this);
            });
        });
    }

    // INITIALIZATION
    
    function setupEventListeners() {
        if (elements.form) elements.form.addEventListener('submit', handleSubmit);
        window.addEventListener('pageshow', () => hideLoading());
        
        // Botão gerar senha
        if (elements.generateBtn) {
            elements.generateBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                handleGeneratePassword();
            });
        }
    }
    
    async function init() {
        console.log('[CARDOXIS] Register Module v4.1.0 - Enterprise Ready');
        
        initIntlTelInput();
        setupEventListeners();
        setupRealTimeValidation();
        setupPasswordToggles();
        
        if (elements.name && !elements.name.value) elements.name.focus();
        
        if (window.location.hostname === 'localhost') {
            console.log('[CARDOXIS] API URL:', CONFIG.API_URL);
            console.log('[CARDOXIS] Modo desenvolvimento ativo');
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    if (window.location.hostname === 'localhost') {
        window.CARDOXIS = window.CARDOXIS || {};
        window.CARDOXIS.Register = { 
            CONFIG, 
            resetRegisterAttempts,
            generateStrongPassword,
            copyToClipboard
        };
    }
    
})();