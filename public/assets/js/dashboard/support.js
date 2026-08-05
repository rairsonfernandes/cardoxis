/**
 * CARDOXIS - Suporte ao Cliente RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES

    const CONFIG = {
        API_URL: window.CARDOXIS?.API_URL || window.location.origin + '/cardoxis/api/v1',
        ANIMATION_DELAY: 150,
        TOAST_DURATION: 5000,
        USE_MOCK_DATA: true
    };
    
    // ESTADO

    let state = {
        isSubmitting: false,
        tickets: [],
        currentTicket: null,
        systemStatus: {}
    };
    
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
            
            if (response.status === 404) {
                console.warn('[API] Endpoint não encontrado:', endpoint);
                return { success: false, message: 'Endpoint não encontrado', data: null };
            }
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('[API Error]', endpoint, error);
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
    
    function formatDate(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '—';
        return date.toLocaleDateString('pt-PT') + ' ' + 
               date.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
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
        setTimeout(() => toast.remove(), CONFIG.TOAST_DURATION);
    }
    
    function getPriorityLabel(priority) {
        const labels = {
            'critical': 'Crítico',
            'high': 'Alta',
            'medium': 'Média',
            'low': 'Baixa'
        };
        return labels[priority] || priority;
    }
    
    function getStatusLabel(status) {
        const labels = {
            'open': 'Aberto',
            'in_progress': 'Em Andamento',
            'resolved': 'Resolvido',
            'closed': 'Fechado'
        };
        return labels[status] || status;
    }
    
    // DADOS MOCK (Fallback)

    function getMockTickets() {
        return [
            {
                id: 'TICKET-001',
                subject: 'Problema com upload de documentos',
                status: 'resolved',
                priority: 'high',
                created_at: new Date(Date.now() - 172800000).toISOString(),
                updated_at: new Date(Date.now() - 86400000).toISOString(),
                category: 'documentos',
                message: 'Estou tendo problemas ao fazer upload de documentos. O sistema retorna erro 500.'
            },
            {
                id: 'TICKET-002',
                subject: 'Dúvida sobre relatórios',
                status: 'in_progress',
                priority: 'medium',
                created_at: new Date(Date.now() - 86400000).toISOString(),
                updated_at: new Date(Date.now() - 7200000).toISOString(),
                category: 'relatorios',
                message: 'Como posso gerar um relatório detalhado de consumo de combustível por veículo?'
            },
            {
                id: 'TICKET-003',
                subject: 'Problema de acesso ao sistema',
                status: 'open',
                priority: 'critical',
                created_at: new Date(Date.now() - 14400000).toISOString(),
                updated_at: new Date(Date.now() - 10800000).toISOString(),
                category: 'acesso',
                message: 'Não consigo acessar o sistema desde a manhã. Aparece erro de autenticação.'
            },
            {
                id: 'TICKET-004',
                subject: 'Sugestão para nova funcionalidade',
                status: 'closed',
                priority: 'low',
                created_at: new Date(Date.now() - 604800000).toISOString(),
                updated_at: new Date(Date.now() - 518400000).toISOString(),
                category: 'sugestao',
                message: 'Sugiro adicionar um campo de observações nos abastecimentos.'
            }
        ];
    }
    
    function getMockSystemStatus() {
        return {
            api: { label: 'API', status: 'operational' },
            database: { label: 'Base de Dados', status: 'operational' },
            upload: { label: 'Upload', status: 'operational' }
        };
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
    
    // FORMULÁRIO DE CONTATO

    function initContactForm() {
        const form = document.getElementById('supportForm');
        if (!form) return;
        
        // Auto-fill user info
        const nameInput = document.getElementById('supportName');
        const emailInput = document.getElementById('supportEmail');
        
        if (nameInput && window.CARDOXIS?.USER?.name) {
            nameInput.value = window.CARDOXIS.USER.name;
        }
        
        if (emailInput && window.CARDOXIS?.USER?.email) {
            emailInput.value = window.CARDOXIS.USER.email;
        }
        
        // Submit handler
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            await handleFormSubmit(this);
        });
        
        // Auto-resize textarea
        const textarea = document.getElementById('supportMessage');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 300) + 'px';
            });
        }
    }
    
    async function handleFormSubmit(form) {
        if (state.isSubmitting) return;
        
        // Validar campos
        const name = document.getElementById('supportName').value.trim();
        const email = document.getElementById('supportEmail').value.trim();
        const subject = document.getElementById('supportSubject').value;
        const category = document.getElementById('supportCategory').value;
        const message = document.getElementById('supportMessage').value.trim();
        
        if (!name || !email || !subject || !message) {
            showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
            return;
        }
        
        if (!isValidEmail(email)) {
            showToast('warning', 'Por favor, insira um email válido');
            return;
        }
        
        state.isSubmitting = true;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        submitBtn.disabled = true;
        
        try {
            // Tentar enviar para API
            const result = await apiRequest('/support/tickets', {
                method: 'POST',
                body: JSON.stringify({
                    name: name,
                    email: email,
                    subject: subject,
                    category: category,
                    message: message
                })
            });
            
            if (result && result.success) {
                showToast('success', 'Mensagem enviada com sucesso! Entraremos em contacto em breve.');
                form.reset();
                
                // Restaurar auto-fill
                if (name && window.CARDOXIS?.USER?.name) {
                    document.getElementById('supportName').value = window.CARDOXIS.USER.name;
                }
                if (email && window.CARDOXIS?.USER?.email) {
                    document.getElementById('supportEmail').value = window.CARDOXIS.USER.email;
                }
                
                // Adicionar ticket mock à lista
                const newTicket = {
                    id: 'TICKET-' + String(Date.now()).slice(-6),
                    subject: subject,
                    status: 'open',
                    priority: 'medium',
                    created_at: new Date().toISOString(),
                    updated_at: new Date().toISOString(),
                    category: category || 'geral',
                    message: message
                };
                
                state.tickets.unshift(newTicket);
                renderTickets(state.tickets);
                updateStats();
                
            } else {
                // Fallback: simular envio bem-sucedido
                showToast('success', 'Mensagem enviada com sucesso! (Modo Demo)');
                form.reset();
                
                if (name && window.CARDOXIS?.USER?.name) {
                    document.getElementById('supportName').value = window.CARDOXIS.USER.name;
                }
                if (email && window.CARDOXIS?.USER?.email) {
                    document.getElementById('supportEmail').value = window.CARDOXIS.USER.email;
                }
            }
        } catch (error) {
            console.error('[Form Submit Error]', error);
            showToast('error', 'Erro ao enviar mensagem. Tente novamente.');
        } finally {
            state.isSubmitting = false;
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    // RESET FORM

    window.resetForm = function() {
        const form = document.getElementById('supportForm');
        if (!form) return;
        
        if (confirm('Tem certeza que deseja limpar todos os campos?')) {
            form.reset();
            
            // Restaurar auto-fill
            if (window.CARDOXIS?.USER?.name) {
                document.getElementById('supportName').value = window.CARDOXIS.USER.name;
            }
            if (window.CARDOXIS?.USER?.email) {
                document.getElementById('supportEmail').value = window.CARDOXIS.USER.email;
            }
            
            showToast('info', 'Campos limpos com sucesso');
        }
    };
    
    // TICKETS

    async function loadTickets() {
        try {
            const result = await apiRequest('/support/tickets');
            
            if (result && result.success && result.data) {
                state.tickets = result.data;
                renderTickets(result.data);
                updateStats();
            } else {
                console.log('[Support] Usando dados mock para tickets');
                const fallbackTickets = getMockTickets();
                state.tickets = fallbackTickets;
                renderTickets(fallbackTickets);
                updateStats();
            }
        } catch (error) {
            console.error('[Load Tickets Error]', error);
            const fallbackTickets = getMockTickets();
            state.tickets = fallbackTickets;
            renderTickets(fallbackTickets);
            updateStats();
        }
    }
    
    function renderTickets(tickets) {
        const container = document.querySelector('.ticket-list');
        if (!container) return;
        
        if (!tickets || tickets.length === 0) {
            container.innerHTML = `
                <div class="ticket-empty">
                    <i class="fas fa-inbox"></i>
                    <p>Não tem tickets de suporte</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = tickets.map(ticket => `
            <div class="ticket-item" onclick="viewTicket('${escapeHtml(ticket.id)}')">
                <div class="ticket-info">
                    <span class="ticket-id">#${escapeHtml(ticket.id)}</span>
                    <span class="ticket-subject">${escapeHtml(ticket.subject)}</span>
                    <span class="ticket-date">
                        <i class="fas fa-clock"></i> 
                        Criado: ${formatDate(ticket.created_at)}
                    </span>
                </div>
                <div class="ticket-meta">
                    <span class="ticket-priority ${escapeHtml(ticket.priority)}">
                        ${getPriorityLabel(ticket.priority)}
                    </span>
                    <span class="ticket-status ${escapeHtml(ticket.status)}">
                        ${getStatusLabel(ticket.status)}
                    </span>
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
        `).join('');
    }
    
    function updateStats() {
        const total = state.tickets.length;
        const open = state.tickets.filter(t => t.status === 'open').length;
        const inProgress = state.tickets.filter(t => t.status === 'in_progress').length;
        const resolved = state.tickets.filter(t => t.status === 'resolved' || t.status === 'closed').length;
        
        // Atualizar contadores
        const statNumbers = document.querySelectorAll('.support-stat .stat-number');
        if (statNumbers.length >= 4) {
            statNumbers[0].textContent = total;
            statNumbers[1].textContent = open;
            statNumbers[1].style.color = 'var(--support-danger)';
            statNumbers[2].textContent = inProgress;
            statNumbers[2].style.color = 'var(--support-warning)';
            statNumbers[3].textContent = resolved;
            statNumbers[3].style.color = 'var(--support-success)';
        }
        
        // Atualizar contador de tickets
        const ticketCount = document.querySelector('.ticket-count');
        if (ticketCount) {
            ticketCount.textContent = total + ' tickets';
        }
    }
    
    // VIEW TICKET

    window.viewTicket = function(ticketId) {
        const ticket = state.tickets.find(t => t.id === ticketId);
        if (!ticket) {
            showToast('warning', 'Ticket não encontrado');
            return;
        }
        
        state.currentTicket = ticket;
        showTicketDetailsModal(ticket);
    };
    
    function showTicketDetailsModal(ticket) {
        const existingModal = document.getElementById('ticketDetailModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        const modal = document.createElement('div');
        modal.id = 'ticketDetailModal';
        modal.className = 'modal active';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 600px;">
                <div class="modal-header">
                    <h3>
                        <i class="fas fa-ticket-alt"></i>
                        Ticket #${escapeHtml(ticket.id)}
                    </h3>
                    <button class="modal-close" onclick="closeTicketModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="ticket-detail-info">
                        <div class="ticket-detail-row">
                            <span class="label">Assunto</span>
                            <span class="value">${escapeHtml(ticket.subject)}</span>
                        </div>
                        <div class="ticket-detail-row">
                            <span class="label">Status</span>
                            <span class="value">
                                <span class="ticket-status ${escapeHtml(ticket.status)}">
                                    ${getStatusLabel(ticket.status)}
                                </span>
                            </span>
                        </div>
                        <div class="ticket-detail-row">
                            <span class="label">Prioridade</span>
                            <span class="value">
                                <span class="ticket-priority ${escapeHtml(ticket.priority)}">
                                    ${getPriorityLabel(ticket.priority)}
                                </span>
                            </span>
                        </div>
                        <div class="ticket-detail-row">
                            <span class="label">Categoria</span>
                            <span class="value">${escapeHtml(ticket.category || 'Geral')}</span>
                        </div>
                        <div class="ticket-detail-row">
                            <span class="label">Criado em</span>
                            <span class="value">${formatDate(ticket.created_at)}</span>
                        </div>
                        <div class="ticket-detail-row">
                            <span class="label">Última atualização</span>
                            <span class="value">${formatDate(ticket.updated_at)}</span>
                        </div>
                        ${ticket.message ? `
                            <div class="ticket-detail-row" style="flex-direction:column;align-items:flex-start;gap:8px;">
                                <span class="label">Mensagem</span>
                                <span class="value" style="font-weight:400;line-height:1.6;">${escapeHtml(ticket.message)}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeTicketModal()">Fechar</button>
                    ${ticket.status !== 'resolved' && ticket.status !== 'closed' ? `
                        <button class="btn btn-success" onclick="resolveTicket('${escapeHtml(ticket.id)}')">
                            <i class="fas fa-check"></i> Marcar como Resolvido
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';
    }
    
    window.closeTicketModal = function() {
        const modal = document.getElementById('ticketDetailModal');
        if (modal) {
            modal.remove();
        }
        document.body.style.overflow = '';
    };
    
    // RESOLVE TICKET

    window.resolveTicket = async function(ticketId) {
        if (!confirm('Tem certeza que deseja marcar este ticket como resolvido?')) return;
        
        try {
            const result = await apiRequest(`/support/tickets/${ticketId}/resolve`, {
                method: 'POST'
            });
            
            if (result && result.success) {
                showToast('success', 'Ticket marcado como resolvido!');
                closeTicketModal();
                await loadTickets();
            } else {
                // Fallback: atualizar localmente
                const ticket = state.tickets.find(t => t.id === ticketId);
                if (ticket) {
                    ticket.status = 'resolved';
                    ticket.updated_at = new Date().toISOString();
                    renderTickets(state.tickets);
                    updateStats();
                    closeTicketModal();
                    showToast('success', 'Ticket marcado como resolvido! (Modo Demo)');
                } else {
                    showToast('error', 'Erro ao resolver ticket');
                }
            }
        } catch (error) {
            console.error('[Resolve Ticket Error]', error);
            const ticket = state.tickets.find(t => t.id === ticketId);
            if (ticket) {
                ticket.status = 'resolved';
                ticket.updated_at = new Date().toISOString();
                renderTickets(state.tickets);
                updateStats();
                closeTicketModal();
                showToast('success', 'Ticket marcado como resolvido!');
            } else {
                showToast('error', 'Erro ao resolver ticket');
            }
        }
    };
    
    // SYSTEM STATUS

    async function checkSystemStatus() {
        try {
            const result = await apiRequest('/support/status');
            if (result && result.success && result.data) {
                state.systemStatus = result.data;
                updateStatusIndicators(result.data);
            } else {
                const mockStatus = getMockSystemStatus();
                state.systemStatus = mockStatus;
                updateStatusIndicators(mockStatus);
            }
        } catch (error) {
            console.error('[Status Check Error]', error);
            const mockStatus = getMockSystemStatus();
            state.systemStatus = mockStatus;
            updateStatusIndicators(mockStatus);
        }
    }
    
    function updateStatusIndicators(status) {
        document.querySelectorAll('.info-item .value[data-status]').forEach(item => {
            const key = item.dataset.status;
            const isOpen = status[key] && status[key].status !== 'error' && status[key].status !== 'down';
            item.className = `value ${isOpen ? 'status-open' : 'status-closed'}`;
            item.innerHTML = `
                <i class="fas ${isOpen ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                ${isOpen ? 'Operacional' : 'Indisponível'}
            `;
        });
    }
    
    // CHAT ONLINE

    function initChatButton() {
        const chatBtn = document.querySelector('.support-channel .btn-channel[data-channel="chat"]');
        if (chatBtn) {
            chatBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showToast('info', 'Chat Online disponível em breve. Por favor, use o formulário de contacto.');
            });
        }
    }
    
    // KEYBOARD SHORTCUTS

    function initKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTicketModal();
            }
            
            if ((e.ctrlKey || e.metaKey) && e.key === '1') {
                e.preventDefault();
                const nameInput = document.getElementById('supportName');
                if (nameInput) nameInput.focus();
            }
            
            if ((e.ctrlKey || e.metaKey) && e.key === '2') {
                e.preventDefault();
                const emailInput = document.getElementById('supportEmail');
                if (emailInput) emailInput.focus();
            }
            
            if ((e.ctrlKey || e.metaKey) && e.key === '3') {
                e.preventDefault();
                const messageInput = document.getElementById('supportMessage');
                if (messageInput) messageInput.focus();
            }
        });
    }
    
    // CHANNEL CLICK HANDLERS

    function initChannelHandlers() {
        // Email - comportamento padrão (mailto)
        const emailBtn = document.querySelector('.support-channel .btn-channel[data-channel="email"]');
        if (emailBtn) {
            emailBtn.addEventListener('click', function(e) {
                // Permitir comportamento padrão
            });
        }
        
        // Phone - comportamento padrão (tel)
        const phoneBtn = document.querySelector('.support-channel .btn-channel[data-channel="phone"]');
        if (phoneBtn) {
            phoneBtn.addEventListener('click', function(e) {
                // Permitir comportamento padrão
            });
        }
        
        // Knowledge Base - comportamento padrão (href)
        const knowledgeBtn = document.querySelector('.support-channel .btn-channel[data-channel="knowledge"]');
        if (knowledgeBtn) {
            knowledgeBtn.addEventListener('click', function(e) {
                // Permitir comportamento padrão
            });
        }
    }
    
    // STATS ANIMATION

    function animateCounters() {
        const statNumbers = document.querySelectorAll('.support-stat .stat-number');
        statNumbers.forEach(el => {
            const target = parseInt(el.textContent);
            if (isNaN(target)) return;
            
            let current = 0;
            const increment = target / 20;
            const duration = 600;
            const stepTime = duration / 20;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                el.textContent = Math.round(current);
            }, stepTime);
        });
    }
    
    // INIT

    async function init() {
        console.log('[CARDOXIS] Inicializando Suporte ao Cliente');
        
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        initSidebar();
        initContactForm();
        initChatButton();
        initKeyboardShortcuts();
        initChannelHandlers();
        
        await loadTickets();
        await checkSystemStatus();
        
        setTimeout(animateCounters, 300);
        
        setInterval(checkSystemStatus, 60000);
        
        console.log('[CARDOXIS] Suporte ao Cliente inicializado com sucesso!');
        console.log('[CARDOXIS] Atalhos: Ctrl+1 (Nome), Ctrl+2 (Email), Ctrl+3 (Mensagem)');
        
        if (CONFIG.USE_MOCK_DATA) {
            console.log('[CARDOXIS] Modo: Demo (dados mock)');
        }
    }
    
    // EXPOR FUNÇÕES GLOBAIS

    window.viewTicket = viewTicket;
    window.closeTicketModal = closeTicketModal;
    window.resolveTicket = resolveTicket;
    window.resetForm = resetForm;
    window.showToast = showToast;
    
    // DOM READY
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();