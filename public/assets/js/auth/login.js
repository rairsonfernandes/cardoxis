 /**
 * CARDOXIS - Login RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES 
    
    const CONFIG = {
        API_URL: '/cardoxis/api/v1',
        REDIRECT_URL: '/cardoxis',
        TOKEN_KEY: 'auth_token',
        USER_KEY: 'user_data',
        VERSION: '8.0.0'
    };
    

    // STORAGE KEYS RF

    const STORAGE_KEYS = {
        REMEMBER_EMAIL: 'cardoxis_remember_email',
        REMEMBER_FLAG: 'cardoxis_remember_flag'
    };
    
    // DOM ELEMENTS 

    
    const elements = {
        form: document.getElementById('loginForm'),
        email: document.getElementById('email'),
        password: document.getElementById('password'),
        rememberCheckbox: document.getElementById('rememberCheckbox'),
        togglePassword: document.getElementById('togglePasswordIcon'),
        loginBtn: document.getElementById('loginBtn'),
        loadingOverlay: document.getElementById('loadingOverlay'),
        alertContainer: document.getElementById('alertContainer')
    };

    // FUNÇÕES DE UI - ALERTAS 
  
    
    function showAlert(type, message, duration = 5000) {
        if (!elements.alertContainer) return;
        
        // Remover alertas anteriores com animação
        const existingAlerts = elements.alertContainer.querySelectorAll('.alert');
        existingAlerts.forEach(alert => {
            alert.classList.add('alert-closing');
            setTimeout(() => {
                if (alert.parentElement) alert.remove();
            }, 300);
        });
        
        // Mapear tipos para ícones RF
        const iconMap = {
            'error': 'fa-exclamation-circle',
            'success': 'fa-check-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };
        
        // Mapear tipos para títulos RF
        const titleMap = {
            'error': 'Erro',
            'success': 'Sucesso',
            'warning': 'Aviso',
            'info': 'Informação'
        };
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.setAttribute('role', 'alert');
        
        alertDiv.innerHTML = `
            <div class="alert-icon">
                <i class="fas ${iconMap[type] || 'fa-info-circle'}"></i>
            </div>
            <div class="alert-content">
                <span class="alert-title">${titleMap[type] || type}</span>
                <span class="alert-message">${escapeHtml(message)}</span>
            </div>
            <button class="alert-close" aria-label="Fechar alerta">
                <i class="fas fa-times"></i>
            </button>
            <div class="alert-progress"></div>
        `;
        
        // Botão de fechar  RF
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
    
    function showLoading() {
        if (elements.loadingOverlay) {
            elements.loadingOverlay.classList.add('active');
        }
        if (elements.loginBtn) {
            elements.loginBtn.disabled = true;
            elements.loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A entrar...';
        }
    }
    
    function hideLoading() {
        if (elements.loadingOverlay) {
            elements.loadingOverlay.classList.remove('active');
        }
        if (elements.loginBtn) {
            elements.loginBtn.disabled = false;
            elements.loginBtn.innerHTML = '<i class="fas fa-arrow-right-to-bracket"></i> <span>Entrar</span>';
        }
    }
    
    function showFieldError(field, message) {
        field.classList.add('input-error');
        const wrapper = field.closest('.input-wrapper');
        if (!wrapper) return;
        
        const existingError = wrapper.querySelector('.error-msg');
        if (existingError) existingError.remove();
        
        const errorSpan = document.createElement('span');
        errorSpan.className = 'error-msg';
        errorSpan.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${escapeHtml(message)}`;
        wrapper.appendChild(errorSpan);
    }
    
    function clearFieldError(field) {
        field.classList.remove('input-error');
        const wrapper = field.closest('.input-wrapper');
        if (!wrapper) return;
        const errorMsg = wrapper.querySelector('.error-msg');
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
    
    function validatePassword(password) {
        return password && password.length >= 6;
    }
    
    function redirectByRole(role) {
        const roleMap = {
            'super_admin': '/admin/dashboard',
            'admin': '/admin/dashboard',
            'manager': '/manager/dashboard'
        };
        const path = roleMap[role] || '/dashboard';
        window.location.replace(`${CONFIG.REDIRECT_URL}${path}`);
    }
    

    // SESSÃO

    function clearSession() {
        const keys = [
            CONFIG.TOKEN_KEY, CONFIG.USER_KEY,
            'auth_token', 'user_data', 'user_name',
            'token_expiry', 'remember_token'
        ];
        keys.forEach(key => {
            localStorage.removeItem(key);
            sessionStorage.removeItem(key);
        });
    }
    
    function saveUserSession(user, token, remember) {
        const storage = remember ? localStorage : sessionStorage;
        storage.setItem(CONFIG.TOKEN_KEY, token);
        storage.setItem(CONFIG.USER_KEY, JSON.stringify(user));
        
        // Sincronizar com sessionStorage
        sessionStorage.setItem(CONFIG.TOKEN_KEY, token);
        sessionStorage.setItem(CONFIG.USER_KEY, JSON.stringify(user));
        
        // Compatibilidade com versões anteriores
        localStorage.setItem('auth_token', token);
        localStorage.setItem('user_data', JSON.stringify(user));
        localStorage.setItem('user_name', user.name);
        sessionStorage.setItem('auth_token', token);
        sessionStorage.setItem('user_data', JSON.stringify(user));
        sessionStorage.setItem('user_name', user.name);
    }
    
    function saveRememberMe(email, isChecked) {
        if (isChecked && email) {
            localStorage.setItem(STORAGE_KEYS.REMEMBER_EMAIL, email);
            localStorage.setItem(STORAGE_KEYS.REMEMBER_FLAG, 'true');
        } else {
            localStorage.removeItem(STORAGE_KEYS.REMEMBER_EMAIL);
            localStorage.setItem(STORAGE_KEYS.REMEMBER_FLAG, 'false');
        }
    }
    
    function loadRememberMe() {
        const savedEmail = localStorage.getItem(STORAGE_KEYS.REMEMBER_EMAIL);
        const isChecked = localStorage.getItem(STORAGE_KEYS.REMEMBER_FLAG) === 'true';
        
        if (savedEmail && isChecked && elements.email) {
            elements.email.value = savedEmail;
            if (elements.rememberCheckbox) {
                elements.rememberCheckbox.checked = true;
            }
        }
    }
    
    // VERIFICAR SESSÃO EXISTENTE
    
    function checkExistingSession() {
        const token = sessionStorage.getItem(CONFIG.TOKEN_KEY) || 
                     sessionStorage.getItem('auth_token') ||
                     localStorage.getItem(CONFIG.TOKEN_KEY) || 
                     localStorage.getItem('auth_token');
        
        const userData = sessionStorage.getItem(CONFIG.USER_KEY) || 
                        sessionStorage.getItem('user_data') ||
                        localStorage.getItem(CONFIG.USER_KEY) || 
                        localStorage.getItem('user_data');
        
        if (token && userData) {
            try {
                const user = typeof userData === 'string' ? JSON.parse(userData) : userData;
                if (user && user.id) {
                    console.log('[CARDOXIS] Sessão ativa encontrada, redirecionando...');
                    
                    if (!sessionStorage.getItem(CONFIG.TOKEN_KEY)) {
                        sessionStorage.setItem(CONFIG.TOKEN_KEY, token);
                    }
                    if (!sessionStorage.getItem(CONFIG.USER_KEY)) {
                        sessionStorage.setItem(CONFIG.USER_KEY, JSON.stringify(user));
                    }
                    
                    redirectByRole(user.role);
                    return true;
                }
            } catch (e) {
                console.warn('[CARDOXIS] Erro ao parsear userData:', e);
                clearSession();
            }
        }
        return false;
    }
    
    // SINCRONIZAÇÃO COM SESSÃO PHP
    
    async function syncPHPSession(user) {
        try {
            const response = await fetch(`${CONFIG.REDIRECT_URL}/sync-session`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    user_id: user.id,
                    user_name: user.name,
                    user_email: user.email,
                    user_role: user.role
                })
            });
            
            const result = await response.json();
            if (result.success) {
                console.log('[CARDOXIS] Sessão PHP sincronizada com sucesso');
            } else {
                console.warn('[CARDOXIS] Erro ao sincronizar sessão PHP:', result.message);
            }
        } catch (error) {
            console.warn('[CARDOXIS] Erro na sincronização de sessão:', error);
        }
    }

    // TOGGLE PASSWORD
    
    function togglePasswordVisibility() {
        if (!elements.password || !elements.togglePassword) return;
        
        const isPassword = elements.password.type === 'password';
        elements.password.type = isPassword ? 'text' : 'password';
        
        elements.togglePassword.classList.toggle('fa-eye-slash');
        elements.togglePassword.classList.toggle('fa-eye');
        elements.togglePassword.setAttribute(
            'aria-label',
            isPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'
        );
    }
    
    // LOGIN - ÚNICA CHAMADA FETCH
    
    async function performLogin(email, password) {
        const response = await fetch(`${CONFIG.API_URL}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ email, password }),
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            let errorMessage = `Erro HTTP: ${response.status}`;
            try {
                const errorData = await response.json();
                errorMessage = errorData.message || errorMessage;
            } catch (e) {}
            throw new Error(errorMessage);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Email ou palavra-passe inválidos');
        }
        
        return data;
    }
    
    // FORM SUBMIT - MANIPULADOR ÚNICO
    
    async function handleSubmit(event) {
        event.preventDefault();
        
        const email = elements.email?.value?.trim() || '';
        const password = elements.password?.value || '';
        const remember = elements.rememberCheckbox?.checked || false;
        
        // Limpar erros anteriores
        clearAllErrors();
        
        // Validação
        let hasError = false;
        
        if (!email) {
            showFieldError(elements.email, 'Email é obrigatório');
            hasError = true;
        } else if (!validateEmail(email)) {
            showFieldError(elements.email, 'Email inválido');
            hasError = true;
        }
        
        if (!password) {
            showFieldError(elements.password, 'Palavra-passe é obrigatória');
            hasError = true;
        } else if (!validatePassword(password)) {
            showFieldError(elements.password, 'Palavra-passe deve ter no mínimo 6 caracteres');
            hasError = true;
        }
        
        if (hasError) return;
        
        // Mostrar loading
        showLoading();
        
        try {
            const result = await performLogin(email, password);
            
            if (result.success && result.data) {
                const user = result.data.user;
                const token = result.data.token;
                
                // Salvar sessão
                saveUserSession(user, token, remember);
                saveRememberMe(email, remember);
                
                // Sincronizar com PHP
                await syncPHPSession(user);
                
                // Mostrar sucesso com alerta premium
                showAlert('success', `Bem-vindo, ${user.name}! Redirecionando...`, 1500);
                
                // Redirecionar após delay
                setTimeout(() => {
                    redirectByRole(user.role);
                }, 1200);
            }
        } catch (error) {
            console.error('[CARDOXIS] Erro no login:', error);
            
            let errorMessage = 'Email ou palavra-passe inválidos';
            
            if (error.message.includes('conexão') || error.message.includes('fetch')) {
                errorMessage = 'Erro de conexão com o servidor. Tente novamente.';
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            // Mostrar erro com alerta premium
            showAlert('error', errorMessage);
            hideLoading();
            
            if (elements.email) elements.email.focus();
        }
    }

    // INICIALIZAÇÃO
    
    function init() {
        console.log(`[CARDOXIS] Initializing Login Module v${CONFIG.VERSION}`);
        
        // Verificar sessão existente
        const token = sessionStorage.getItem(CONFIG.TOKEN_KEY) || 
                     localStorage.getItem(CONFIG.TOKEN_KEY) ||
                     sessionStorage.getItem('auth_token') || 
                     localStorage.getItem('auth_token');
        
        const userData = sessionStorage.getItem(CONFIG.USER_KEY) || 
                        localStorage.getItem(CONFIG.USER_KEY) ||
                        sessionStorage.getItem('user_data') || 
                        localStorage.getItem('user_data');
        
        // Só redirecionar se tiver token E userData válido
        if (token && userData) {
            try {
                const user = typeof userData === 'string' ? JSON.parse(userData) : userData;
                if (user && user.id) {
                    console.log('[CARDOXIS] Sessão válida encontrada, redirecionando...');
                    redirectByRole(user.role);
                    return;
                }
            } catch (e) {
                console.warn('[CARDOXIS] Sessão inválida, limpando...');
                clearSession();
            }
        }
        
        // Se chegou aqui, não tem sessão válida
        console.log('[CARDOXIS] Nenhuma sessão válida, mostrando formulário de login');
        
        // Carregar "Lembrar-me"
        loadRememberMe();
        
        // Event Listeners
        if (elements.form) {
            elements.form.addEventListener('submit', handleSubmit);
        }
        
        if (elements.togglePassword) {
            elements.togglePassword.addEventListener('click', togglePasswordVisibility);
        }
        
        if (elements.email) {
            elements.email.addEventListener('input', () => clearFieldError(elements.email));
        }
        
        if (elements.password) {
            elements.password.addEventListener('input', () => clearFieldError(elements.password));
        }
        
        // Foco no email
        if (elements.email && !elements.email.value) {
            setTimeout(() => elements.email.focus(), 100);
        }
        
        // Esconder loading se estiver visível
        hideLoading();
        
        console.log('[CARDOXIS] Login Module initialized successfully');
    }
    
    // Iniciar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})(); 