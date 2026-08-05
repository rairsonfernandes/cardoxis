/**
 * CARDOXIS - Alerts JavaScript   RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES

    const CONFIG = {
        API_URL: window.CARDOXIS?.API_URL || '/cardoxis/api/v1',
        ITEMS_PER_PAGE: 10,
        REFRESH_INTERVAL: 30000,
        TOAST_DURATION: 5000
    };
    
    // ESTADO

    let state = {
        alerts: [],
        filteredAlerts: [],
        currentPage: 1,
        currentFilter: 'all',
        totalPages: 1,
        isLoading: false,
        currentAlertId: null,
        totalAlerts: 0,
        isAdmin: false,
        userRole: 'user'
    };
    
    let refreshInterval = null;
    
    // AUTENTICAÇÃO

    function getToken() {
        return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    }
    
    function handleUnauthorized() {
        localStorage.removeItem('auth_token');
        sessionStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        sessionStorage.removeItem('user_data');
        window.location.href = '/cardoxis/login';
    }
    
    // API

    async function apiRequest(endpoint, options = {}) {
        const token = getToken();
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
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
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('[API Error]', error);
            return { success: false, data: [] };
        }
    }
    
    // UTILITÁRIOS

    function formatDate(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '—';
        
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) return 'Agora pouco';
        if (diff < 3600) return `${Math.floor(diff / 60)} min atrás`;
        if (diff < 86400) return `${Math.floor(diff / 3600)} h atrás`;
        if (diff < 604800) return `${Math.floor(diff / 86400)} dias atrás`;
        if (diff < 2592000) return `${Math.floor(diff / 604800)} semanas atrás`;
        
        return date.toLocaleDateString('pt-PT');
    }
    
    function formatDateTime(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '—';
        return date.toLocaleDateString('pt-PT') + ' ' + 
               date.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function updateElementText(id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    }
    
    function updateLastUpdateTime() {
        const element = document.getElementById('lastUpdateTime');
        if (element) {
            element.textContent = new Date().toLocaleTimeString('pt-PT');
        }
    }
    
    function showToast(type, message) {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        const iconMap = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        const icon = iconMap[type] || 'info-circle';
        
        toast.innerHTML = `
            <i class="fas fa-${icon}"></i>
            <span>${escapeHtml(message)}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, CONFIG.TOAST_DURATION);
    }
    
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    // ÍCONES E STATUS

    function getSeverityIcon(severity) {
        const icons = {
            critical: 'fa-exclamation-triangle',
            warning: 'fa-clock',
            info: 'fa-info-circle'
        };
        return icons[severity] || 'fa-bell';
    }
    
    function getSeverityText(severity) {
        const texts = {
            critical: 'Crítico',
            warning: 'Atenção',
            info: 'Informativo'
        };
        return texts[severity] || severity;
    }
    
    function getTypeIcon(type) {
        const icons = {
            maintenance: 'fa-tools',
            document: 'fa-file-alt',
            payment: 'fa-credit-card',
            user: 'fa-user',
            license: 'fa-id-card',
            system: 'fa-server',
            insurance: 'fa-shield-alt',
            fine: 'fa-money-bill-wave',
            vehicle: 'fa-car',
            driver: 'fa-user'
        };
        return icons[type] || 'fa-bell';
    }
    
    function getTypeText(type) {
        const texts = {
            maintenance: 'Manutenção',
            document: 'Documento',
            payment: 'Pagamento',
            user: 'Utilizador',
            license: 'Licença',
            system: 'Sistema',
            insurance: 'Seguro',
            fine: 'Multa',
            vehicle: 'Veículo',
            driver: 'Motorista'
        };
        return texts[type] || type;
    }
    
    // VERIFICAR DOCUMENTOS

    window.runDocumentCheck = async function() {
        if (!state.isAdmin) {
            showToast('warning', 'Apenas administradores podem executar esta ação');
            return;
        }
        
        showToast('info', 'A verificar documentos...');
        
        try {
            const result = await apiRequest('/alerts/check-documents', { method: 'POST' });
            
            if (result && result.success) {
                showToast('success', result.message || 'Verificação concluída!');
                await loadAlerts();
            } else {
                showToast('error', result?.message || 'Erro ao verificar documentos');
            }
        } catch (error) {
            console.error('[Run Document Check Error]', error);
            showToast('error', 'Erro ao verificar documentos');
        }
    };

    // CARREGAMENTO
    
    function applyFilters() {
        let filtered = [...state.alerts];
        
        if (state.currentFilter === 'unread') {
            filtered = filtered.filter(a => a.is_read == 0);
        } else if (state.currentFilter === 'read') {
            filtered = filtered.filter(a => a.is_read == 1);
        } else if (state.currentFilter === 'critical') {
            filtered = filtered.filter(a => a.severity === 'critical' && a.is_read == 0);
        } else if (state.currentFilter === 'document') {
            filtered = filtered.filter(a => a.type === 'document');
        }
        
        state.filteredAlerts = filtered;
        state.totalPages = Math.ceil(filtered.length / CONFIG.ITEMS_PER_PAGE);
        
        if (state.currentPage > state.totalPages) {
            state.currentPage = Math.max(1, state.totalPages);
        }
        if (state.currentPage < 1) state.currentPage = 1;
    }
    
    async function loadAlerts() {
        if (state.isLoading) return;
        state.isLoading = true;
        
        try {
            const token = getToken();
            if (!token) {
                window.location.href = '/cardoxis/login';
                return;
            }
            
            const result = await apiRequest('/alerts');
            
            if (result && result.success) {
                let alertsData = [];
                let total = 0;
                
                if (result.data && result.data.data && Array.isArray(result.data.data)) {
                    alertsData = result.data.data;
                    total = result.data.total || 0;
                } else if (result.data && Array.isArray(result.data)) {
                    alertsData = result.data;
                    total = alertsData.length;
                } else {
                    alertsData = [];
                    total = 0;
                }
                
                state.alerts = alertsData;
                state.totalAlerts = total;
                state.isAdmin = result.data?.is_admin || false;
                state.userRole = result.data?.user_role || 'user';
                
                applyFilters();
                updateStats();
                renderTable();
                renderPagination();
                updateLastUpdateTime();
                updateUIForRole();
            } else {
                state.alerts = [];
                state.filteredAlerts = [];
                state.totalAlerts = 0;
                state.isAdmin = false;
                updateStats();
                renderEmptyState();
                updateUIForRole();
            }
        } catch (error) {
            console.error('[Load Alerts Error]', error);
            state.alerts = [];
            state.filteredAlerts = [];
            state.totalAlerts = 0;
            state.isAdmin = false;
            updateStats();
            renderEmptyState();
            updateUIForRole();
            showToast('error', 'Erro ao carregar alertas');
        } finally {
            state.isLoading = false;
        }
    }
    

    // UI POR PERMISSÃO

    function updateUIForRole() {
        const adminOnlyElements = document.querySelectorAll('.admin-only');
        const userOnlyElements = document.querySelectorAll('.user-only');
        
        if (state.isAdmin) {
            adminOnlyElements.forEach(el => el.style.display = '');
            userOnlyElements.forEach(el => el.style.display = 'none');
        } else {
            adminOnlyElements.forEach(el => el.style.display = 'none');
            userOnlyElements.forEach(el => el.style.display = '');
        }
    }

    // ESTATÍSTICAS

    function updateStats() {
        const total = state.alerts.length;
        const unread = state.alerts.filter(a => a.is_read == 0).length;
        const critical = state.alerts.filter(a => a.severity === 'critical' && a.is_read == 0).length;
        const documents = state.alerts.filter(a => a.type === 'document').length;
        
        updateElementText('totalAlerts', total);
        updateElementText('unreadCount', unread);
        updateElementText('criticalCount', critical);
        updateElementText('documentCount', documents);
        updateElementText('filterAllCount', total);
        updateElementText('filterUnreadCount', unread);
        updateElementText('filterReadCount', total - unread);
        updateElementText('filterCriticalCount', critical);
        updateElementText('filterDocumentCount', documents);
        
        const badge = document.getElementById('unreadBadge');
        if (badge) {
            if (unread > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = unread;
            } else {
                badge.style.display = 'none';
            }
        }
    }

    // RENDERIZAÇÃO
    
    function renderTable() {
        const container = document.getElementById('alertsTableBody');
        if (!container) return;
        
        const start = (state.currentPage - 1) * CONFIG.ITEMS_PER_PAGE;
        const end = start + CONFIG.ITEMS_PER_PAGE;
        const pageAlerts = state.filteredAlerts.slice(start, end);
        
        if (pageAlerts.length === 0) {
            renderEmptyState();
            return;
        }
        
        container.innerHTML = pageAlerts.map(alert => {
            const severityIcon = getSeverityIcon(alert.severity);
            const severityText = getSeverityText(alert.severity);
            const typeIcon = getTypeIcon(alert.type);
            const typeText = getTypeText(alert.type);
            const isRead = alert.is_read == 1;
            const canDelete = alert.can_delete || state.isAdmin;
            const isOwner = alert.is_owner || false;
            
            return `
                <tr class="${isRead ? 'read' : ''} ${!isOwner && !state.isAdmin ? 'not-owner' : ''}">
                    <td class="alert-icon-col">
                        <div class="alert-icon ${alert.severity}" onclick="viewAlertDetails(${alert.id})">
                            <i class="fas ${severityIcon}"></i>
                        </div>
                    </td>
                    <td class="alert-col" onclick="viewAlertDetails(${alert.id})">
                        <div class="alert-info">
                            <strong>${escapeHtml(alert.title)}</strong>
                            <p>${escapeHtml(alert.message)}</p>
                            ${!state.isAdmin && alert.created_by_name ? `
                                <small style="color:var(--gray);font-size:0.7rem;display:block;margin-top:2px;">
                                    <i class="fas fa-user"></i> ${escapeHtml(alert.created_by_name)}
                                </small>
                            ` : ''}
                        </div>
                    </td>
                    <td class="type-col" onclick="viewAlertDetails(${alert.id})">
                        <span class="type-badge ${alert.type}">
                            <i class="fas ${typeIcon}"></i> ${typeText}
                        </span>
                    </td>
                    <td class="severity-col" onclick="viewAlertDetails(${alert.id})">
                        <div class="severity-indicator ${alert.severity}">
                            <i class="fas ${severityIcon}"></i>
                            <span>${severityText}</span>
                        </div>
                    </td>
                    <td class="date-col" onclick="viewAlertDetails(${alert.id})">
                        <div class="date-info">
                            <i class="fas fa-clock"></i>
                            <span>${formatDate(alert.created_at)}</span>
                        </div>
                    </td>
                    <td class="actions-col">
                        ${!isRead ? `
                            <button class="action-btn success" onclick="markAsRead(${alert.id})" title="Marcar como lido">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        ` : `
                            <span class="read-indicator" title="Lido">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        `}
                        ${canDelete ? `
                            <button class="action-btn danger" onclick="confirmDeleteAlert(${alert.id})" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    function renderEmptyState() {
        const container = document.getElementById('alertsTableBody');
        if (container) {
            let extraButton = '';
            if (state.isAdmin) {
                extraButton = `
                    <button class="btn-primary" onclick="runDocumentCheck()" style="margin-top:16px;">
                        <i class="fas fa-sync-alt"></i> Verificar Documentos
                    </button>
                `;
            }
            
            container.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center; padding:60px;">
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h3>Nenhum alerta encontrado</h3>
                            <p>Não há alertas para exibir no momento</p>
                            ${extraButton}
                        </div>
                    </td>
                </tr>
            `;
        }
    }
    
    function renderPagination() {
        const container = document.getElementById('pagination');
        if (!container) return;
        
        if (state.totalPages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let pages = [];
        const maxVisible = 5;
        let startPage = Math.max(1, state.currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(state.totalPages, startPage + maxVisible - 1);
        
        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
        }
        
        let html = `
            <button class="pagination-btn" onclick="changePage(1)" ${state.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-angle-double-left"></i>
            </button>
            <button class="pagination-btn" onclick="changePage(${state.currentPage - 1})" ${state.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        if (startPage > 1) {
            html += `<span class="pagination-ellipsis">...</span>`;
        }
        
        pages.forEach(page => {
            html += `
                <button class="pagination-btn ${page === state.currentPage ? 'active' : ''}" onclick="changePage(${page})">
                    ${page}
                </button>
            `;
        });
        
        if (endPage < state.totalPages) {
            html += `<span class="pagination-ellipsis">...</span>`;
        }
        
        html += `
            <button class="pagination-btn" onclick="changePage(${state.currentPage + 1})" ${state.currentPage === state.totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
            <button class="pagination-btn" onclick="changePage(${state.totalPages})" ${state.currentPage === state.totalPages ? 'disabled' : ''}>
                <i class="fas fa-angle-double-right"></i>
            </button>
        `;
        
        container.innerHTML = html;
    }
    
    // AÇÕES
    
    function confirmDeleteAlert(id) {
        state.currentAlertId = id;
        
        const messageEl = document.getElementById('confirmMessage');
        if (messageEl) {
            const alert = state.alerts.find(a => a.id === id);
            const title = alert ? alert.title : 'este alerta';
            messageEl.textContent = `Tem certeza que deseja eliminar "${title}"? Esta ação não pode ser desfeita.`;
        }
        
        const confirmBtn = document.getElementById('confirmActionBtn');
        if (confirmBtn) {
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            newConfirmBtn.addEventListener('click', function() {
                if (state.currentAlertId) {
                    deleteAlert(state.currentAlertId);
                }
            });
        }
        
        openModal('confirmModal');
    }
    
    async function markAsRead(id) {
        try {
            const result = await apiRequest(`/alerts/${id}/read`, { method: 'POST' });
            if (result && result.success) {
                const alert = state.alerts.find(a => a.id === id);
                if (alert) {
                    alert.is_read = 1;
                    applyFilters();
                    updateStats();
                    renderTable();
                    renderPagination();
                    showToast('success', 'Alerta marcado como lido');
                }
            }
        } catch (error) {
            console.error('[Mark As Read Error]', error);
            showToast('error', 'Erro ao marcar alerta');
        }
    }
    
    async function markAllAsRead() {
        try {
            const result = await apiRequest('/alerts/read-all', { method: 'POST' });
            if (result && result.success) {
                state.alerts.forEach(a => { a.is_read = 1; });
                applyFilters();
                updateStats();
                renderTable();
                renderPagination();
                showToast('success', 'Todos os alertas foram marcados como lidos');
            }
        } catch (error) {
            console.error('[Mark All As Read Error]', error);
            showToast('error', 'Erro ao marcar alertas');
        }
    }
    
    async function deleteAlert(id) {
        try {
            const result = await apiRequest(`/alerts/${id}/delete`, { method: 'DELETE' });
            if (result && result.success) {
                state.alerts = state.alerts.filter(a => a.id !== id);
                state.currentAlertId = null;
                applyFilters();
                updateStats();
                renderTable();
                renderPagination();
                closeModal('confirmModal');
                showToast('success', 'Alerta eliminado com sucesso');
            } else {
                showToast('error', result?.message || 'Erro ao eliminar alerta');
            }
        } catch (error) {
            console.error('[Delete Alert Error]', error);
            showToast('error', 'Erro ao eliminar alerta');
        }
    }
    
    async function deleteReadAlerts() {
        if (!confirm('Tem certeza que deseja eliminar todos os alertas lidos? Esta ação não pode ser desfeita.')) {
            return;
        }
        
        try {
            const result = await apiRequest('/alerts/delete-read', { method: 'DELETE' });
            if (result && result.success) {
                state.alerts = state.alerts.filter(a => a.is_read == 0);
                applyFilters();
                updateStats();
                renderTable();
                renderPagination();
                showToast('success', 'Alertas lidos eliminados com sucesso');
            }
        } catch (error) {
            console.error('[Delete Read Alerts Error]', error);
            showToast('error', 'Erro ao eliminar alertas lidos');
        }
    }
    
    function viewAlertDetails(id) {
        const alert = state.alerts.find(a => a.id === id);
        if (!alert) {
            showToast('error', 'Alerta não encontrado');
            return;
        }
        
        state.currentAlertId = id;
        const severityIcon = getSeverityIcon(alert.severity);
        const typeIcon = getTypeIcon(alert.type);
        const severityText = getSeverityText(alert.severity);
        const typeText = getTypeText(alert.type);
        
        const modalContent = document.getElementById('detailModalContent');
        if (modalContent) {
            modalContent.innerHTML = `
                <div class="detail-grid">
                    <div class="detail-item full-width" style="text-align:center;">
                        <div class="alert-icon ${alert.severity}" style="width:60px;height:60px;font-size:1.8rem;margin:0 auto;">
                            <i class="fas ${severityIcon}"></i>
                        </div>
                    </div>
                    <div class="detail-item full-width">
                        <label><i class="fas fa-tag"></i> Título</label>
                        <div class="value">${escapeHtml(alert.title)}</div>
                    </div>
                    <div class="detail-item full-width">
                        <label><i class="fas fa-align-left"></i> Mensagem</label>
                        <div class="value">${escapeHtml(alert.message)}</div>
                    </div>
                    <div class="detail-item">
                        <label><i class="fas fa-chart-line"></i> Severidade</label>
                        <div class="value">
                            <span class="severity-badge ${alert.severity}">
                                <i class="fas ${severityIcon}"></i> ${severityText}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label><i class="fas fa-tag"></i> Tipo</label>
                        <div class="value">
                            <span class="type-badge ${alert.type}">
                                <i class="fas ${typeIcon}"></i> ${typeText}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label><i class="fas fa-calendar-alt"></i> Data de Criação</label>
                        <div class="value">${formatDateTime(alert.created_at)}</div>
                    </div>
                    ${alert.read_at ? `
                        <div class="detail-item">
                            <label><i class="fas fa-check-circle"></i> Data de Leitura</label>
                            <div class="value">${formatDateTime(alert.read_at)}</div>
                        </div>
                    ` : ''}
                    ${alert.created_by_name ? `
                        <div class="detail-item">
                            <label><i class="fas fa-user"></i> Criado por</label>
                            <div class="value">${escapeHtml(alert.created_by_name)}</div>
                        </div>
                    ` : ''}
                    ${!state.isAdmin ? `
                        <div class="detail-item full-width" style="text-align:center;padding:12px;background:var(--gray-bg);border-radius:var(--radius-md);">
                            <small style="color:var(--gray);">
                                <i class="fas fa-lock"></i> 
                                ${alert.created_by == window.CARDOXIS?.USER?.id ? 'Você criou este alerta' : 'Apenas administradores podem gerenciar alertas de outros usuários'}
                            </small>
                        </div>
                    ` : ''}
                </div>
            `;
        }
        
        openModal('viewAlertModal');
    }
    
    function markCurrentAsRead() {
        if (state.currentAlertId) {
            markAsRead(state.currentAlertId);
            closeModal('viewAlertModal');
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
        
        const userMenu = document.getElementById('userMenu');
        const userDropdown = document.getElementById('userDropdown');
        const userChevron = document.getElementById('userChevron');
        
        if (userMenu && userDropdown) {
            userMenu.addEventListener('click', (e) => {
                e.preventDefault();
                userDropdown.classList.toggle('show');
                if (userChevron) {
                    userChevron.style.transform = userDropdown.classList.contains('show') 
                        ? 'rotate(180deg)' 
                        : 'rotate(0deg)';
                }
            });
            
            document.addEventListener('click', (e) => {
                if (!userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.remove('show');
                    if (userChevron) userChevron.style.transform = 'rotate(0deg)';
                }
            });
        }
        
        const logoutBtn = document.getElementById('logoutSidebarBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                try {
                    await apiRequest('/auth/logout', { method: 'POST' });
                } catch (error) {}
                localStorage.removeItem('auth_token');
                sessionStorage.removeItem('auth_token');
                window.location.href = '/cardoxis/login';
            });
        }
    }

    // SEARCH

    function initSearch() {
        const searchInput = document.getElementById('globalSearch');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const query = e.target.value.trim().toLowerCase();
                    if (query.length >= 2) {
                        const filtered = state.alerts.filter(a => 
                            a.title.toLowerCase().includes(query) || 
                            a.message.toLowerCase().includes(query)
                        );
                        state.filteredAlerts = filtered;
                        state.totalPages = Math.ceil(filtered.length / CONFIG.ITEMS_PER_PAGE);
                        state.currentPage = 1;
                        renderTable();
                        renderPagination();
                    } else if (query.length === 0) {
                        applyFilters();
                        renderTable();
                        renderPagination();
                    }
                }, 300);
            });
        }
        
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('globalSearch')?.focus();
            }
        });
    }

    // EVENTOS GLOBAIS

    window.filterAlerts = function(filter) {
        state.currentFilter = filter;
        state.currentPage = 1;
        
        document.querySelectorAll('.filter-status-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.status === filter) {
                btn.classList.add('active');
            }
        });
        
        applyFilters();
        renderTable();
        renderPagination();
    };
    
    window.changePage = function(page) {
        if (page < 1 || page > state.totalPages || page === state.currentPage) return;
        state.currentPage = page;
        renderTable();
        renderPagination();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    window.markAsRead = markAsRead;
    window.markAllAsRead = markAllAsRead;
    window.deleteAlert = deleteAlert;
    window.deleteReadAlerts = deleteReadAlerts;
    window.viewAlertDetails = viewAlertDetails;
    window.markCurrentAsRead = markCurrentAsRead;
    window.confirmDeleteAlert = confirmDeleteAlert;
    window.closeModal = closeModal;
    window.openModal = openModal;
    window.loadAlerts = loadAlerts;
    window.runDocumentCheck = runDocumentCheck;
    

    // INICIALIZAÇÃO

    async function init() {
        console.log('[CARDOXIS] Inicializando Alerts');
        
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        initSidebar();
        initSearch();
        await loadAlerts();
        
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            loadAlerts();
            updateLastUpdateTime();
        }, CONFIG.REFRESH_INTERVAL);
        
        console.log('[CARDOXIS] Alerts inicializado com sucesso!');
        console.log('[CARDOXIS] Total de alertas:', state.totalAlerts);
        console.log('[CARDOXIS] É admin?', state.isAdmin);
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();