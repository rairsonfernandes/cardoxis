/**
 * CARDOXIS - Chat Inteligente RF
 */

(function() {
    'use strict';
    
    const CONFIG = {
        API_URL: window.CARDOXIS?.API_URL || window.location.origin + '/cardoxis/api/v1',
        MAX_HISTORY: 50
    };
    
    let messageCount = 0;
    let isProcessing = false;
    let messageHistory = [];
    let sessionId = Date.now().toString(36) + Math.random().toString(36).substr(2);
    
    // AUTENTICAÇÃO

    function getToken() {
        return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    }
    
    function handleUnauthorized() {
        localStorage.removeItem('auth_token');
        sessionStorage.removeItem('auth_token');
        window.location.href = '/cardoxis/login';
    }
    
    // API

    async function apiRequest(endpoint, options = {}) {
        const token = getToken();
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        try {
            const response = await fetch(`${CONFIG.API_URL}${endpoint}`, {
                ...options,
                headers,
                credentials: 'same-origin'
            });
            
            if (response.status === 401) {
                handleUnauthorized();
                return null;
            }
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('[API Error]', endpoint, error);
            showToast('error', 'Erro na comunicação: ' + error.message);
            return { success: false, message: error.message, data: null };
        }
    }
    
    // UTILITÁRIOS

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatTime(date) {
        return date.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
    }
    
    function showToast(type, message) {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const iconMap = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        const icon = iconMap[type] || 'fa-info-circle';
        
        toast.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${escapeHtml(message)}</span>
            <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }
    
    function updateLastUpdateTime() {
        const element = document.getElementById('lastUpdateTime');
        if (element) {
            element.textContent = formatTime(new Date());
        }
    }
    
    // CHAT FUNCTIONS
    
    function addMessage(type, content, timestamp) {
        const container = document.getElementById('chatMessages');
        if (!container) return;
        
        const time = timestamp ? new Date(timestamp) : new Date();
        const timeStr = formatTime(time);
        
        let avatar = '';
        let avatarClass = '';
        let contentClass = '';
        let icon = '';
        
        if (type === 'user') {
            avatar = window.userInitials || 'U';
            avatarClass = 'user';
            contentClass = 'user';
            icon = '';
        } else if (type === 'assistant') {
            avatar = '<i class="fas fa-robot" style="font-size:0.9rem;"></i>';
            avatarClass = 'assistant';
            contentClass = 'assistant';
            icon = '';
        } else {
            avatar = '<i class="fas fa-info-circle"></i>';
            avatarClass = 'system';
            contentClass = 'system';
            icon = '';
        }
        
        // Processar markdown simples
        let formattedContent = escapeHtml(content);
        // Negrito
        formattedContent = formattedContent.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Itálico
        formattedContent = formattedContent.replace(/\*(.*?)\*/g, '<em>$1</em>');
        // Quebras de linha
        formattedContent = formattedContent.replace(/\n/g, '<br>');
        // Listas
        formattedContent = formattedContent.replace(/• /g, '• ');
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${contentClass}`;
        messageDiv.innerHTML = `
            <div class="message-avatar ${avatarClass}">${avatar}</div>
            <div>
                <div class="message-content">${formattedContent}</div>
                <span class="message-time">${timeStr}</span>
            </div>
        `;
        
        container.appendChild(messageDiv);
        
        // Scroll para o final
        container.scrollTop = container.scrollHeight;
        
        // Atualizar contador
        messageCount++;
        const countEl = document.getElementById('messageCount');
        if (countEl) countEl.textContent = messageCount;
        
        // Atualizar estatísticas
        const statQuestions = document.getElementById('statQuestions');
        if (statQuestions) statQuestions.textContent = messageCount;
        
        updateLastUpdateTime();
    }
    
    function showTypingIndicator() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) indicator.classList.add('active');
        const container = document.getElementById('chatMessages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }
    
    function hideTypingIndicator() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) indicator.classList.remove('active');
    }
    
    async function sendMessage() {
        const input = document.getElementById('chatInput');
        const sendBtn = document.getElementById('sendBtn');
        
        if (!input || !input.value.trim()) return;
        if (isProcessing) return;
        
        const question = input.value.trim();
        input.value = '';
        input.style.height = 'auto';
        
        // Adicionar mensagem do usuário
        addMessage('user', question);
        
        isProcessing = true;
        sendBtn.disabled = true;
        showTypingIndicator();
        
        try {
            const result = await apiRequest('/chat/ask', {
                method: 'POST',
                body: JSON.stringify({ question: question })
            });
            
            hideTypingIndicator();
            
            if (result && result.success) {
                let response = result.data.response || 'Desculpe, não consegui processar sua pergunta.';
                addMessage('assistant', response);
                
                // Atualizar status
                if (result.data.source) {
                    const statusText = document.getElementById('statusText');
                    if (statusText) {
                        const sources = {
                            'cache': 'Cache',
                            'smart': 'Inteligente',
                            'openai': 'OpenAI',
                            'fallback': 'Fallback'
                        };
                        statusText.textContent = sources[result.data.source] || 'Online';
                    }
                }
            } else {
                addMessage('assistant', 'Desculpe, ocorreu um erro. 😅 Por favor, tente novamente.');
                showToast('error', result?.message || 'Erro ao processar pergunta');
            }
        } catch (error) {
            hideTypingIndicator();
            addMessage('assistant', 'Desculpe, ocorreu um erro. 😅 Por favor, tente novamente.');
            showToast('error', 'Erro ao processar pergunta');
        } finally {
            isProcessing = false;
            sendBtn.disabled = false;
            input.focus();
        }
    }
    
    function askSuggestion(question) {
        const input = document.getElementById('chatInput');
        if (input) {
            input.value = question;
            sendMessage();
        }
    }
    
    async function clearHistory() {
        if (!confirm('Tem certeza que deseja limpar todo o histórico da conversa?')) return;
        
        try {
            const result = await apiRequest('/chat/clear-history', {
                method: 'POST'
            });
            
            if (result && result.success) {
                // Limpar mensagens da tela, mantendo apenas a mensagem de boas-vindas
                const container = document.getElementById('chatMessages');
                if (container) {
                    container.innerHTML = `
                        <div class="message assistant">
                            <div class="message-avatar assistant">
                                <i class="fas fa-robot" style="font-size:0.9rem;"></i>
                            </div>
                            <div>
                                <div class="message-content">
                                    Olá, <strong>${escapeHtml(window.userName || 'Utilizador')}</strong>! 👋<br><br>
                                    Sou o assistente do <strong>CARDOXIS</strong>. Como posso ajudá-lo hoje?
                                </div>
                                <span class="message-time">Agora</span>
                            </div>
                        </div>
                    `;
                    messageCount = 0;
                    const countEl = document.getElementById('messageCount');
                    if (countEl) countEl.textContent = '0';
                    const statQuestions = document.getElementById('statQuestions');
                    if (statQuestions) statQuestions.textContent = '0';
                }
                showToast('success', 'Histórico limpo com sucesso!');
            } else {
                showToast('error', result?.message || 'Erro ao limpar histórico');
            }
        } catch (error) {
            console.error('[Clear History Error]', error);
            showToast('error', 'Erro ao limpar histórico');
        }
    }

    // SIDEBAR

    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('menuToggle');
        const sidebarClose = document.getElementById('sidebarCloseBtn');
        
        function openSidebar() {
            if (sidebar) sidebar.classList.add('open');
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        if (menuToggle) menuToggle.addEventListener('click', openSidebar);
        if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar?.classList.contains('open')) closeSidebar();
        });
    }
    
    // AUTO-RESIZE TEXTAREA

    function initTextareaAutoResize() {
        const textarea = document.getElementById('chatInput');
        if (!textarea) return;
        
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
    }

    // STATUS CHECK

    async function checkStatus() {
        try {
            const result = await apiRequest('/chat/status');
            if (result && result.success) {
                const statusDot = document.getElementById('statusDot');
                const statusText = document.getElementById('statusText');
                const modelInfo = document.getElementById('modelInfo');
                
                if (result.data) {
                    if (result.data.is_working) {
                        statusDot.className = 'status-dot online';
                        statusText.textContent = 'Online';
                    } else {
                        statusDot.className = 'status-dot offline';
                        statusText.textContent = 'Offline - Modo Local';
                    }
                    
                    if (modelInfo && result.data.model) {
                        modelInfo.textContent = result.data.model;
                    }
                }
            }
        } catch (error) {
            console.error('[Status Check Error]', error);
        }
    }
    
    // LOAD HISTORY

    async function loadHistory() {
        try {
            const result = await apiRequest('/chat/history?limit=20');
            if (result && result.success && result.data && result.data.length > 0) {
                // Carregar histórico em ordem cronológica
                const history = result.data.reverse();
                for (const item of history) {
                    addMessage('user', item.question, item.created_at);
                    addMessage('assistant', item.response, item.created_at);
                }
            }
        } catch (error) {
            console.error('[Load History Error]', error);
        }
    }
    
    // INIT

    async function init() {
        console.log('[CARDOXIS] Inicializando Chat');
        
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        initSidebar();
        initTextareaAutoResize();
        
        // Status
        await checkStatus();
        
        // Carregar histórico
        await loadHistory();
        
        // Atualizar estatísticas
        const statSessions = document.getElementById('statSessions');
        if (statSessions) statSessions.textContent = '1';
        
        // Eventos
        document.getElementById('clearHistory')?.addEventListener('click', clearHistory);
        
        // Quick actions
        document.querySelectorAll('.quick-action-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const question = this.dataset.question;
                if (question) askSuggestion(question);
            });
        });
        
        // Sugestões
        document.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', function() {
                const text = this.textContent.trim();
                if (text) askSuggestion(text);
            });
        });
        
        // Atualizar status periodicamente
        setInterval(checkStatus, 30000);
        
        // Atualizar hora
        setInterval(updateLastUpdateTime, 60000);
        
        console.log('[CARDOXIS] Chat inicializado com sucesso!');
    }
    
    // Expor funções globais
    window.sendMessage = sendMessage;
    window.askSuggestion = askSuggestion;
    window.clearHistory = clearHistory;
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();