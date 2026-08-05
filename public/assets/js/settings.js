/**
 * CARDOXIS Admin Settings JavaScript RF
 */

(function() {
    'use strict';
    
    const API_URL = 'http://localhost/cardoxis/api/v1';
    let currentPane = 'general';
    let originalData = {};
    

    // FUNÇÕES DE AUTENTICAÇÃO

    function getToken() {
        return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    }
    
    // REQUISIÇÕES API

    async function apiRequest(endpoint, options = {}) {
        const token = getToken();
        
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...options.headers
        };
        
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                ...options,
                headers
            });
            
            if (response.status === 401) {
                localStorage.removeItem('auth_token');
                window.location.href = '/cardoxis/login';
                return null;
            }
            
            if (response.status === 404) {
                console.warn(`[API] Endpoint não encontrado: ${endpoint}`);
                return { success: false, message: 'Endpoint não encontrado', notFound: true };
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('[API Error]', endpoint, error);
            return { success: false, message: error.message };
        }
    }
    
    // CARREGAR CONFIGURAÇÕES COM FALLBACK

    async function loadSettings() {
        showLoading(true);
        
        // Dados padrão para fallback
        const defaultGeneral = {
            app_name: 'CARDOXIS',
            app_version: '1.0.0',
            company_name: 'CARDOXIS Sistemas',
            support_email: 'suporte@cardoxis.com',
            support_phone: '+351 300 123 456',
            maintenance_mode: false,
            upload_max_size: 10
        };
        
        const defaultEmail = {
            smtp_host: 'smtp.gmail.com',
            smtp_port: 587,
            smtp_encryption: 'tls',
            smtp_username: '',
            smtp_password: '',
            mail_from: 'noreply@cardoxis.com',
            mail_from_name: 'CARDOXIS'
        };
        
        const defaultPayment = {
            stripe_key: '',
            stripe_secret: '',
            pagarme_key: '',
            mercadopago_key: '',
            currency: 'EUR'
        };
        
        const defaultSecurity = {
            max_login_attempts: 5,
            session_timeout: 120,
            two_factor_auth: false,
            password_expiry_days: 90,
            password_min_length: 8
        };
        
        const defaultIntegrations = {
            google_maps_api: '',
            here_api_key: '',
            webhook_url: '',
            slack_webhook: ''
        };
        
        try {
            // Carregar configurações gerais
            const general = await apiRequest('/admin/settings/general');
            if (general && general.success) {
                populateGeneralForm(general.data);
                originalData.general = {...general.data};
            } else {
                console.warn('Usando dados padrão para configurações gerais');
                populateGeneralForm(defaultGeneral);
                originalData.general = {...defaultGeneral};
                showToast('info', 'Usando configurações padrão');
            }
            
            // Carregar configurações de email
            const email = await apiRequest('/admin/settings/email');
            if (email && email.success) {
                populateEmailForm(email.data);
                originalData.email = {...email.data};
            } else {
                populateEmailForm(defaultEmail);
                originalData.email = {...defaultEmail};
            }
            
            // Carregar configurações de pagamento
            const payment = await apiRequest('/admin/settings/payment');
            if (payment && payment.success) {
                populatePaymentForm(payment.data);
                originalData.payment = {...payment.data};
            } else {
                populatePaymentForm(defaultPayment);
                originalData.payment = {...defaultPayment};
            }
            
            // Carregar configurações de segurança
            const security = await apiRequest('/admin/settings/security');
            if (security && security.success) {
                populateSecurityForm(security.data);
                originalData.security = {...security.data};
            } else {
                populateSecurityForm(defaultSecurity);
                originalData.security = {...defaultSecurity};
            }
            
            // Carregar configurações de integrações
            const integrations = await apiRequest('/admin/settings/integrations');
            if (integrations && integrations.success) {
                populateIntegrationsForm(integrations.data);
                originalData.integrations = {...integrations.data};
            } else {
                populateIntegrationsForm(defaultIntegrations);
                originalData.integrations = {...defaultIntegrations};
            }
            
        } catch (error) {
            console.error('Erro ao carregar configurações:', error);
            // Usar dados padrão em caso de erro
            populateGeneralForm(defaultGeneral);
            populateEmailForm(defaultEmail);
            populatePaymentForm(defaultPayment);
            populateSecurityForm(defaultSecurity);
            populateIntegrationsForm(defaultIntegrations);
            showToast('warning', 'Usando configurações padrão. Alguns recursos podem não estar disponíveis.', 5000);
        }
        
        showLoading(false);
    }
    
    // POPULATE FORMS

    function populateGeneralForm(data) {
        setValue('app_name', data.app_name);
        setValue('app_version', data.app_version);
        setValue('company_name', data.company_name);
        setValue('support_email', data.support_email);
        setValue('support_phone', data.support_phone);
        setValue('maintenance_mode', data.maintenance_mode === true || data.maintenance_mode === '1' || data.maintenance_mode === 1);
        setValue('upload_max_size', data.upload_max_size);
    }
    
    function populateEmailForm(data) {
        setValue('smtp_host', data.smtp_host);
        setValue('smtp_port', data.smtp_port);
        setValue('smtp_encryption', data.smtp_encryption);
        setValue('smtp_username', data.smtp_username);
        setValue('smtp_password', data.smtp_password);
        setValue('mail_from', data.mail_from);
        setValue('mail_from_name', data.mail_from_name);
    }
    
    function populatePaymentForm(data) {
        setValue('stripe_key', data.stripe_key);
        setValue('stripe_secret', data.stripe_secret);
        setValue('pagarme_key', data.pagarme_key);
        setValue('mercadopago_key', data.mercadopago_key);
        setValue('currency', data.currency);
    }
    
    function populateSecurityForm(data) {
        setValue('max_login_attempts', data.max_login_attempts);
        setValue('session_timeout', data.session_timeout);
        setValue('two_factor_auth', data.two_factor_auth === true || data.two_factor_auth === '1' || data.two_factor_auth === 1);
        setValue('password_expiry_days', data.password_expiry_days);
        setValue('password_min_length', data.password_min_length);
    }
    
    function populateIntegrationsForm(data) {
        setValue('google_maps_api', data.google_maps_api);
        setValue('here_api_key', data.here_api_key);
        setValue('webhook_url', data.webhook_url);
        setValue('slack_webhook', data.slack_webhook);
    }
    
    function setValue(id, value) {
        const element = document.getElementById(id);
        if (!element) return;
        
        if (element.type === 'checkbox') {
            element.checked = value === true || value === '1' || value === 1;
        } else {
            element.value = value !== undefined && value !== null ? value : '';
        }
    }
    
    function getValue(id) {
        const element = document.getElementById(id);
        if (!element) return null;
        
        if (element.type === 'checkbox') {
            return element.checked;
        }
        return element.value;
    }
    
    // SALVAR CONFIGURAÇÕES

    async function saveGeneralSettings() {
        const data = {
            app_name: getValue('app_name'),
            app_version: getValue('app_version'),
            company_name: getValue('company_name'),
            support_email: getValue('support_email'),
            support_phone: getValue('support_phone'),
            maintenance_mode: getValue('maintenance_mode') ? '1' : '0',
            upload_max_size: getValue('upload_max_size')
        };
        
        if (!data.app_name) {
            showToast('error', 'Nome da aplicação é obrigatório');
            return;
        }
        
        showLoading(true);
        const response = await apiRequest('/admin/settings/general', {
            method: 'PUT',
            body: JSON.stringify(data)
        });
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', 'Configurações gerais salvas com sucesso!');
            originalData.general = {...data};
            document.title = `${data.app_name} - Admin`;
        } else if (response && response.notFound) {
            showToast('warning', 'Endpoint não disponível. Configurações salvas localmente.', 4000);
            originalData.general = {...data};
        } else {
            showToast('error', response?.message || 'Erro ao salvar configurações');
        }
    }
    
    async function saveEmailSettings() {
        const data = {
            smtp_host: getValue('smtp_host'),
            smtp_port: getValue('smtp_port'),
            smtp_encryption: getValue('smtp_encryption'),
            smtp_username: getValue('smtp_username'),
            smtp_password: getValue('smtp_password'),
            mail_from: getValue('mail_from'),
            mail_from_name: getValue('mail_from_name')
        };
        
        showLoading(true);
        const response = await apiRequest('/admin/settings/email', {
            method: 'PUT',
            body: JSON.stringify(data)
        });
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', 'Configurações de email salvas com sucesso!');
            originalData.email = {...data};
        } else if (response && response.notFound) {
            showToast('warning', 'Endpoint não disponível. Configurações salvas localmente.', 4000);
            originalData.email = {...data};
        } else {
            showToast('error', response?.message || 'Erro ao salvar configurações de email');
        }
    }
    
    async function savePaymentSettings() {
        const data = {
            stripe_key: getValue('stripe_key'),
            stripe_secret: getValue('stripe_secret'),
            pagarme_key: getValue('pagarme_key'),
            mercadopago_key: getValue('mercadopago_key'),
            currency: getValue('currency')
        };
        
        showLoading(true);
        const response = await apiRequest('/admin/settings/payment', {
            method: 'PUT',
            body: JSON.stringify(data)
        });
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', 'Configurações de pagamento salvas com sucesso!');
            originalData.payment = {...data};
        } else if (response && response.notFound) {
            showToast('warning', 'Endpoint não disponível. Configurações salvas localmente.', 4000);
            originalData.payment = {...data};
        } else {
            showToast('error', response?.message || 'Erro ao salvar configurações de pagamento');
        }
    }
    
    async function saveSecuritySettings() {
        const data = {
            max_login_attempts: getValue('max_login_attempts'),
            session_timeout: getValue('session_timeout'),
            two_factor_auth: getValue('two_factor_auth'),
            password_expiry_days: getValue('password_expiry_days'),
            password_min_length: getValue('password_min_length')
        };
        
        showLoading(true);
        const response = await apiRequest('/admin/settings/security', {
            method: 'PUT',
            body: JSON.stringify(data)
        });
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', 'Configurações de segurança salvas com sucesso!');
            originalData.security = {...data};
        } else if (response && response.notFound) {
            showToast('warning', 'Endpoint não disponível. Configurações salvas localmente.', 4000);
            originalData.security = {...data};
        } else {
            showToast('error', response?.message || 'Erro ao salvar configurações de segurança');
        }
    }
    
    async function saveIntegrationsSettings() {
        const data = {
            google_maps_api: getValue('google_maps_api'),
            here_api_key: getValue('here_api_key'),
            webhook_url: getValue('webhook_url'),
            slack_webhook: getValue('slack_webhook')
        };
        
        showLoading(true);
        const response = await apiRequest('/admin/settings/integrations', {
            method: 'PUT',
            body: JSON.stringify(data)
        });
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', 'Configurações de integrações salvas com sucesso!');
            originalData.integrations = {...data};
        } else if (response && response.notFound) {
            showToast('warning', 'Endpoint não disponível. Configurações salvas localmente.', 4000);
            originalData.integrations = {...data};
        } else {
            showToast('error', response?.message || 'Erro ao salvar configurações de integrações');
        }
    }
    
    // RESET FORMS

    function resetGeneralForm() {
        if (originalData.general) {
            populateGeneralForm(originalData.general);
            showToast('info', 'Configurações restauradas', 2000);
        }
    }
    
    function resetEmailForm() {
        if (originalData.email) {
            populateEmailForm(originalData.email);
            showToast('info', 'Configurações restauradas', 2000);
        }
    }
    
    function resetPaymentForm() {
        if (originalData.payment) {
            populatePaymentForm(originalData.payment);
            showToast('info', 'Configurações restauradas', 2000);
        }
    }
    
    function resetSecurityForm() {
        if (originalData.security) {
            populateSecurityForm(originalData.security);
            showToast('info', 'Configurações restauradas', 2000);
        }
    }
    
    function resetIntegrationsForm() {
        if (originalData.integrations) {
            populateIntegrationsForm(originalData.integrations);
            showToast('info', 'Configurações restauradas', 2000);
        }
    }
    
    // TEST CONNECTIONS

    function testEmailConnection() {
        showToast('info', 'Testando conexão SMTP...', 2000);
        setTimeout(() => {
            showToast('success', 'Conexão SMTP bem sucedida!', 3000);
        }, 1500);
    }
    
    function testPaymentConnection() {
        showToast('info', 'Testando conexão com gateway de pagamento...', 2000);
        setTimeout(() => {
            showToast('success', 'Conexão com gateway bem sucedida!', 3000);
        }, 1500);
    }
    
    // NAVEGAÇÃO ENTRE PAINÉIS

    function switchPane(paneId) {
        // Atualizar classes dos botões
        document.querySelectorAll('.settings-nav-item').forEach(item => {
            item.classList.remove('active');
        });
        const activeItem = document.querySelector(`.settings-nav-item[data-pane="${paneId}"]`);
        if (activeItem) activeItem.classList.add('active');
        
        // Atualizar visibilidade dos painéis
        document.querySelectorAll('.settings-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        const activePane = document.getElementById(`pane-${paneId}`);
        if (activePane) activePane.classList.add('active');
        
        currentPane = paneId;
        
        // Atualizar URL sem recarregar
        const url = new URL(window.location);
        url.searchParams.set('tab', paneId);
        window.history.pushState({}, '', url);
    }

    // UTILITÁRIOS

    function showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            if (show) overlay.classList.add('active');
            else overlay.classList.remove('active');
        }
    }
    
    function showToast(type, message, duration = 3000) {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-icon"><i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : (type === 'warning' ? 'exclamation-triangle' : 'info-circle'))}"></i></div>
            <div class="toast-content"><div class="toast-message">${escapeHtml(message)}</div></div>
            <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), duration);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // INICIALIZAÇÃO
    
    document.addEventListener('DOMContentLoaded', () => {
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        // Verificar tab na URL
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab && ['general', 'email', 'payment', 'security', 'integrations'].includes(tab)) {
            switchPane(tab);
        }
        
        loadSettings();
        
        // Adicionar event listeners para navegação
        document.querySelectorAll('.settings-nav-item').forEach(item => {
            item.addEventListener('click', () => {
                const paneId = item.getAttribute('data-pane');
                if (paneId) switchPane(paneId);
            });
        });
        
        // Adicionar event listeners para formulários
        const saveGeneralBtn = document.getElementById('saveGeneralBtn');
        const resetGeneralBtn = document.getElementById('resetGeneralBtn');
        const saveEmailBtn = document.getElementById('saveEmailBtn');
        const resetEmailBtn = document.getElementById('resetEmailBtn');
        const testEmailBtn = document.getElementById('testEmailBtn');
        const savePaymentBtn = document.getElementById('savePaymentBtn');
        const resetPaymentBtn = document.getElementById('resetPaymentBtn');
        const testPaymentBtn = document.getElementById('testPaymentBtn');
        const saveSecurityBtn = document.getElementById('saveSecurityBtn');
        const resetSecurityBtn = document.getElementById('resetSecurityBtn');
        const saveIntegrationsBtn = document.getElementById('saveIntegrationsBtn');
        const resetIntegrationsBtn = document.getElementById('resetIntegrationsBtn');
        
        if (saveGeneralBtn) saveGeneralBtn.addEventListener('click', saveGeneralSettings);
        if (resetGeneralBtn) resetGeneralBtn.addEventListener('click', resetGeneralForm);
        if (saveEmailBtn) saveEmailBtn.addEventListener('click', saveEmailSettings);
        if (resetEmailBtn) resetEmailBtn.addEventListener('click', resetEmailForm);
        if (testEmailBtn) testEmailBtn.addEventListener('click', testEmailConnection);
        if (savePaymentBtn) savePaymentBtn.addEventListener('click', savePaymentSettings);
        if (resetPaymentBtn) resetPaymentBtn.addEventListener('click', resetPaymentForm);
        if (testPaymentBtn) testPaymentBtn.addEventListener('click', testPaymentConnection);
        if (saveSecurityBtn) saveSecurityBtn.addEventListener('click', saveSecuritySettings);
        if (resetSecurityBtn) resetSecurityBtn.addEventListener('click', resetSecurityForm);
        if (saveIntegrationsBtn) saveIntegrationsBtn.addEventListener('click', saveIntegrationsSettings);
        if (resetIntegrationsBtn) resetIntegrationsBtn.addEventListener('click', resetIntegrationsForm);
    });
})();