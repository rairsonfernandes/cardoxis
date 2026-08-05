/**
 * CARDOXIS - Verify PIN  RF
 */

(function() {
    'use strict';

    // CONFIGURAÇÕES

    const CONFIG = {
        API_URL: '/cardoxis/api/v1',
        REDIRECT_URL: '/cardoxis',
        PIN_LENGTH: 6,
        PIN_TIMEOUT: 300, // 5 minutos em segundos
        RESEND_COOLDOWN: 60 // 60 segundos
    };

    // DOM ELEMENTS
    
    const elements = {
        form: document.getElementById('verifyForm'),
        pinInputs: document.querySelectorAll('.pin-input-wrapper input'),
        pinContainer: document.getElementById('pinContainer'),
        verifyBtn: document.getElementById('verifyBtn'),
        resendBtn: document.getElementById('resendBtn'),
        countdown: document.getElementById('countdown'),
        alertContainer: document.getElementById('alertContainer'),
        loadingOverlay: document.getElementById('loadingOverlay'),
        csrfToken: document.querySelector('input[name="csrf_token"]')
    };
    
    // ESTADO
    
    let countdownInterval = null;
    let timeLeft = CONFIG.PIN_TIMEOUT;
    let isVerifying = false;
    let isResending = false;
    
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
        
        if (elements.verifyBtn) {
            elements.verifyBtn.disabled = true;
            elements.verifyBtn.innerHTML = `
                <i class="fas fa-spinner fa-spin"></i>
                <span>A verificar...</span>
            `;
        }
    }
    
    function hideLoading() {
        const overlay = elements.loadingOverlay;
        if (overlay) overlay.classList.remove('active');
        
        if (elements.verifyBtn && !isVerifying) {
            elements.verifyBtn.disabled = false;
            elements.verifyBtn.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>Verificar PIN</span>
            `;
        }
    }
    
    // FUNÇÕES DE UI - PIN
    
    function clearPinErrors() {
        elements.pinInputs.forEach(input => {
            input.classList.remove('input-error', 'input-success');
        });
    }
    
    function setPinError() {
        elements.pinInputs.forEach(input => {
            input.classList.add('input-error');
        });
    }
    
    function setPinSuccess() {
        elements.pinInputs.forEach(input => {
            input.classList.remove('input-error');
            input.classList.add('input-success');
        });
    }
    
    function getPinValue() {
        let pin = '';
        elements.pinInputs.forEach(input => {
            pin += input.value;
        });
        return pin;
    }
    
    function clearPinInputs() {
        elements.pinInputs.forEach(input => {
            input.value = '';
            input.classList.remove('input-error', 'input-success');
        });
        // Foco no primeiro input
        if (elements.pinInputs.length > 0) {
            elements.pinInputs[0].focus();
        }
    }
    
    function focusNextInput(currentIndex) {
        const nextIndex = currentIndex + 1;
        if (nextIndex < elements.pinInputs.length) {
            elements.pinInputs[nextIndex].focus();
        } else {
            // Último input - submeter automaticamente
            setTimeout(() => {
                if (elements.form) {
                    elements.form.dispatchEvent(new Event('submit'));
                }
            }, 300);
        }
    }
    
    function focusPrevInput(currentIndex) {
        const prevIndex = currentIndex - 1;
        if (prevIndex >= 0) {
            elements.pinInputs[prevIndex].focus();
        }
    }
    
    // FUNÇÕES DE UI - COUNTDOWN
    
    function startCountdown() {
        timeLeft = CONFIG.PIN_TIMEOUT;
        updateCountdownDisplay();
        
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
        
        countdownInterval = setInterval(function() {
            timeLeft--;
            updateCountdownDisplay();
            
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                countdownInterval = null;
                // PIN expirado
                showAlert('warning', 'Código expirado', 
                    'O código de verificação expirou. Solicite um novo código.', 5000);
                if (elements.resendBtn) {
                    elements.resendBtn.disabled = false;
                }
            }
        }, 1000);
    }
    
    function updateCountdownDisplay() {
        if (!elements.countdown) return;
        
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        elements.countdown.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        
        if (timeLeft <= 60) {
            elements.countdown.style.color = '#EF4444';
        } else {
            elements.countdown.style.color = '';
        }
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

    // HANDLERS
    
    // Submit do formulário
    async function handleSubmit(event) {
        event.preventDefault();
        
        if (isVerifying) return;
        
        const pin = getPinValue();
        
        clearAlerts();
        clearPinErrors();
        
        // Validar PIN
        if (pin.length !== CONFIG.PIN_LENGTH) {
            showAlert('warning', 'Código incompleto', 
                'Por favor, preencha todos os 6 dígitos do código.');
            setPinError();
            return;
        }
        
        isVerifying = true;
        showLoading();
        
        try {
            const result = await apiRequest('/auth/verify-pin', {
                pin: pin,
                csrf_token: getCsrfToken()
            });
            
            if (result.success) {
                setPinSuccess();
                showAlert('success', 'Código verificado!', 
                    'Redirecionando para redefinir a senha...', 2000);
                
                // Redirecionar após delay
                setTimeout(() => {
                    window.location.href = `${CONFIG.REDIRECT_URL}/reset-password`;
                }, 2000);
            }
            
        } catch (error) {
            console.error('[CARDOXIS] Verify PIN error:', error);
            
            let errorMessage = error.message || 'Código inválido. Tente novamente.';
            
            if (errorMessage.toLowerCase().includes('expirado')) {
                errorMessage = 'Código expirado. Solicite um novo código.';
                if (elements.resendBtn) {
                    elements.resendBtn.disabled = false;
                }
            } else if (errorMessage.toLowerCase().includes('tentativas') || errorMessage.toLowerCase().includes('attempts')) {
                errorMessage = 'Muitas tentativas. Solicite um novo código.';
            } else if (errorMessage.toLowerCase().includes('csr') || errorMessage.toLowerCase().includes('token')) {
                errorMessage = 'Token de segurança inválido. Recarregue a página.';
            }
            
            showAlert('error', 'Erro', errorMessage);
            setPinError();
            
            // Limpar campos para nova tentativa
            setTimeout(() => {
                clearPinInputs();
            }, 500);
            
            hideLoading();
            isVerifying = false;
        }
    }
    
    // Resend PIN
    async function handleResend() {
        if (isResending) return;
        
        clearAlerts();
        isResending = true;
        
        if (elements.resendBtn) {
            elements.resendBtn.disabled = true;
            elements.resendBtn.innerHTML = `
                <i class="fas fa-spinner fa-spin"></i>
                <span>A reenviar...</span>
            `;
        }
        
        try {
            const result = await apiRequest('/auth/resend-pin', {
                csrf_token: getCsrfToken()
            });
            
            if (result.success) {
                showAlert('success', 'Código reenviado!', 
                    'Um novo código foi enviado para o seu email. Verifique a sua caixa de entrada.', 5000);
                
                // Resetar PIN inputs
                clearPinInputs();
                
                // Resetar countdown
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                }
                startCountdown();
                
                // Habilitar resend após cooldown
                setTimeout(() => {
                    isResending = false;
                    if (elements.resendBtn) {
                        elements.resendBtn.disabled = false;
                        elements.resendBtn.innerHTML = `
                            <i class="fas fa-redo-alt"></i>
                            <span>Reenviar código</span>
                        `;
                    }
                }, CONFIG.RESEND_COOLDOWN * 1000);
            }
            
        } catch (error) {
            console.error('[CARDOXIS] Resend error:', error);
            showAlert('error', 'Erro', error.message || 'Erro ao reenviar código.');
            
            isResending = false;
            if (elements.resendBtn) {
                elements.resendBtn.disabled = false;
                elements.resendBtn.innerHTML = `
                    <i class="fas fa-redo-alt"></i>
                    <span>Reenviar código</span>
                `;
            }
        }
    }

    // EVENT LISTENERS - PIN INPUTS
    
    function setupPinInputs() {
        elements.pinInputs.forEach((input, index) => {
            // Input de dígitos
            input.addEventListener('input', function() {
                // Apenas números
                this.value = this.value.replace(/\D/g, '');
                
                // Limitar a 1 caractere
                if (this.value.length > 1) {
                    this.value = this.value.slice(0, 1);
                }
                
                // Remover erro
                this.classList.remove('input-error', 'input-success');
                
                // Avançar para o próximo input
                if (this.value.length === 1) {
                    focusNextInput(index);
                }
            });
            
            // Teclas especiais
            input.addEventListener('keydown', function(e) {
                // Backspace - voltar para o input anterior
                if (e.key === 'Backspace' && this.value === '') {
                    e.preventDefault();
                    focusPrevInput(index);
                }
                
                // Setas esquerda/direita
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    focusPrevInput(index);
                }
                if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    if (this.value === '') {
                        focusNextInput(index);
                    }
                }
                
                // Enter - submeter
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (elements.form) {
                        elements.form.dispatchEvent(new Event('submit'));
                    }
                }
                
                // Bloquear espaços
                if (e.key === ' ' || e.key === 'Space') {
                    e.preventDefault();
                }
            });
            
            // Focus - selecionar conteúdo
            input.addEventListener('focus', function() {
                this.select();
            });
            
            // Paste - permitir colar código completo
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text');
                const numbers = pasteData.replace(/\D/g, '');
                
                if (numbers.length > 0) {
                    // Distribuir os números pelos inputs
                    for (let i = 0; i < elements.pinInputs.length && i < numbers.length; i++) {
                        elements.pinInputs[i].value = numbers[i] || '';
                    }
                    
                    // Focar no próximo campo vazio ou no último
                    const nextEmpty = Array.from(elements.pinInputs).findIndex(inp => inp.value === '');
                    if (nextEmpty !== -1) {
                        elements.pinInputs[nextEmpty].focus();
                    } else {
                        elements.pinInputs[elements.pinInputs.length - 1].focus();
                        // Auto-submit após colar
                        setTimeout(() => {
                            if (elements.form) {
                                elements.form.dispatchEvent(new Event('submit'));
                            }
                        }, 300);
                    }
                }
            });
        });
    }
    
    // EVENT LISTENERS - GERAIS
    
    function setupEventListeners() {
        // Submit do formulário
        if (elements.form) {
            elements.form.addEventListener('submit', handleSubmit);
        }
        
        // Resend button
        if (elements.resendBtn) {
            elements.resendBtn.addEventListener('click', handleResend);
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
        console.log('[CARDOXIS] Verify PIN Module v2.0.0 loaded');
        
        setupPinInputs();
        setupEventListeners();
        startCountdown();
        
        // Foco no primeiro input após carregar
        if (elements.pinInputs.length > 0) {
            setTimeout(() => {
                elements.pinInputs[0].focus();
            }, 500);
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