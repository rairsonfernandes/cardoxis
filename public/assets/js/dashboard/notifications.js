/**
 * CARDOXIS - Notifications RF
 */

(function() {
    'use strict';
    
    const CONFIG = {
        API_URL: window.CARDOXIS?.API_URL || window.location.origin + '/cardoxis/api/v1',
        ITEMS_PER_PAGE: 10,
        REFRESH_INTERVAL: 30000
    };
    
    let state = {
        notifications: [],
        filteredNotifications: [],
        currentPage: 1,
        currentFilter: 'all',
        totalPages: 1,
        isLoading: false,
        totalItems: 0,
        isAdmin: false
    };
    
    let refreshInterval = null;
    let isProcessing = false;
    
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
            
            if (response.status === 404) {
                console.warn('[API] Endpoint não encontrado:', endpoint);
                return { success: false, message: 'Endpoint não encontrado' };
            }
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            return data;
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
    
    // NOTIFICATIONS

    function applyFilters() {
        let filtered = [...state.notifications];
        
        if (state.currentFilter === 'unread') {
            filtered = filtered.filter(n => n.is_read == 0);
        } else if (state.currentFilter === 'read') {
            filtered = filtered.filter(n => n.is_read == 1);
        } else if (state.currentFilter !== 'all') {
            filtered = filtered.filter(n => n.type === state.currentFilter);
        }
        
        state.filteredNotifications = filtered;
        state.totalPages = Math.ceil(filtered.length / CONFIG.ITEMS_PER_PAGE);
        
        if (state.currentPage > state.totalPages) {
            state.currentPage = Math.max(1, state.totalPages);
        }
        if (state.currentPage < 1) state.currentPage = 1;
    }
    
    async function loadNotifications() {
        if (state.isLoading) return;
        state.isLoading = true;
        
        try {
            const token = getToken();
            if (!token) {
                window.location.href = '/cardoxis/login';
                return;
            }
            
            const result = await apiRequest('/notifications');
            
            let notifications = [];
            if (result && result.success) {
                if (result.data && Array.isArray(result.data)) {
                    notifications = result.data;
                } else if (result.data && result.data.data && Array.isArray(result.data.data)) {
                    notifications = result.data.data;
                }
                state.isAdmin = result.data?.is_admin || false;
            }
            
            state.notifications = notifications;
            state.totalItems = notifications.length;
            
            applyFilters();
            updateStats();
            renderNotifications();
            renderPagination();
            updateLastUpdateTime();
            
        } catch (error) {
            console.error('[Load Notifications Error]', error);
            state.notifications = [];
            state.totalItems = 0;
            applyFilters();
            updateStats();
            renderEmptyState();
        } finally {
            state.isLoading = false;
        }
    }
    
    // RENDER

    function updateStats() {
        const total = state.notifications.length;
        const unread = state.notifications.filter(n => n.is_read == 0).length;
        const read = total - unread;
        const system = state.notifications.filter(n => n.type === 'system').length;
        
        updateElementText('totalCount', total);
        updateElementText('unreadCount', unread);
        updateElementText('readCount', read);
        updateElementText('systemCount', system);
        
        updateElementText('filterAllCount', total);
        updateElementText('filterUnreadCount', unread);
        updateElementText('filterReadCount', read);
        updateElementText('filterSystemCount', system);
        updateElementText('filterDocumentCount', state.notifications.filter(n => n.type === 'document').length);
        updateElementText('filterMaintenanceCount', state.notifications.filter(n => n.type === 'maintenance').length);
        updateElementText('filterInsuranceCount', state.notifications.filter(n => n.type === 'insurance').length);
        updateElementText('filterFineCount', state.notifications.filter(n => n.type === 'fine').length);
        
        // Badge
        const badge = document.getElementById('unreadBadge');
        if (badge) {
            badge.textContent = unread;
            badge.style.display = unread > 0 ? 'inline-block' : 'none';
        }
    }
    
    function renderNotifications() {
        const container = document.getElementById('notificationsList');
        if (!container) return;
        
        const start = (state.currentPage - 1) * CONFIG.ITEMS_PER_PAGE;
        const end = start + CONFIG.ITEMS_PER_PAGE;
        const pageItems = state.filteredNotifications.slice(start, end);
        
        if (pageItems.length === 0) {
            renderEmptyState();
            return;
        }
        
        container.innerHTML = `
            <div class="notif-list">
                ${pageItems.map(notification => {
                    const isRead = notification.is_read == 1;
                    const iconClass = getIconClass(notification.type);
                    const typeText = getTypeText(notification.type);
                    const canDelete = notification.can_delete || state.isAdmin;
                    
                    return `
                        <div class="notif-item ${isRead ? 'read' : 'unread'}" data-id="${notification.id}">
                            ${!isRead ? '<div class="notif-dot"></div>' : ''}
                            <div class="notif-icon ${iconClass}">
                                <i class="fas ${getIcon(notification.type)}"></i>
                            </div>
                            <div class="notif-content" onclick="viewNotification(${notification.id})">
                                <div class="notif-title">${escapeHtml(notification.title)}</div>
                                <div class="notif-message">${escapeHtml(notification.message)}</div>
                                <div class="notif-meta">
                                    <span class="type-badge ${notification.type}">${typeText}</span>
                                    <span><i class="fas fa-clock"></i> ${formatDate(notification.created_at)}</span>
                                    ${notification.created_by_name ? `
                                        <span><i class="fas fa-user"></i> ${escapeHtml(notification.created_by_name)}</span>
                                    ` : ''}
                                </div>
                            </div>
                            <div class="notif-actions">
                                ${!isRead ? `
                                    <button onclick="markAsRead(${notification.id})" title="Marcar como lido">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                ` : ''}
                                ${canDelete ? `
                                    <button class="danger" onclick="deleteNotification(${notification.id})" title="Eliminar">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }
    
    function renderEmptyState() {
        const container = document.getElementById('notificationsList');
        if (!container) return;
        
        container.innerHTML = `
            <div class="notif-empty">
                <i class="fas fa-bell-slash"></i>
                <h3>Nenhuma notificação</h3>
                <p>Não há notificações para exibir no momento.</p>
            </div>
        `;
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
            <button class="page-btn" onclick="changePage(1)" ${state.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-angle-double-left"></i>
            </button>
            <button class="page-btn" onclick="changePage(${state.currentPage - 1})" ${state.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        if (startPage > 1) {
            html += `<span style="padding:0 8px;color:var(--notif-text-muted);">...</span>`;
        }
        
        pages.forEach(page => {
            html += `
                <button class="page-btn ${page === state.currentPage ? 'active' : ''}" onclick="changePage(${page})">
                    ${page}
                </button>
            `;
        });
        
        if (endPage < state.totalPages) {
            html += `<span style="padding:0 8px;color:var(--notif-text-muted);">...</span>`;
        }
        
        html += `
            <button class="page-btn" onclick="changePage(${state.currentPage + 1})" ${state.currentPage === state.totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
            <button class="page-btn" onclick="changePage(${state.totalPages})" ${state.currentPage === state.totalPages ? 'disabled' : ''}>
                <i class="fas fa-angle-double-right"></i>
            </button>
        `;
        
        container.innerHTML = html;
    }
    
    // HELPERS

    function getIcon(type) {
        const icons = {
            'document': 'fa-file-alt',
            'maintenance': 'fa-tools',
            'insurance': 'fa-shield-alt',
            'fine': 'fa-money-bill-wave',
            'system': 'fa-server',
            'alert': 'fa-exclamation-triangle'
        };
        return icons[type] || 'fa-bell';
    }
    
    function getIconClass(type) {
        const classes = {
            'document': 'primary',
            'maintenance': 'warning',
            'insurance': 'success',
            'fine': 'danger',
            'system': 'info',
            'alert': 'warning'
        };
        return classes[type] || 'primary';
    }
    
    function getTypeText(type) {
        const texts = {
            'document': 'Documento',
            'maintenance': 'Manutenção',
            'insurance': 'Seguro',
            'fine': 'Multa',
            'system': 'Sistema',
            'alert': 'Alerta'
        };
        return texts[type] || type;
    }
    
    // ACTIONS

    window.filterNotifications = function(filter) {
        state.currentFilter = filter;
        state.currentPage = 1;
        
        document.querySelectorAll('.notif-filter-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.filter === filter) {
                btn.classList.add('active');
            }
        });
        
        applyFilters();
        renderNotifications();
        renderPagination();
    };
    
    window.changePage = function(page) {
        if (page < 1 || page > state.totalPages || page === state.currentPage) return;
        state.currentPage = page;
        renderNotifications();
        renderPagination();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    window.markAsRead = async function(id) {
        if (isProcessing) return;
        isProcessing = true;
        
        try {
            const result = await apiRequest(`/notifications/${id}/read`, { method: 'POST' });
            if (result && result.success) {
                const notification = state.notifications.find(n => n.id === id);
                if (notification) {
                    notification.is_read = 1;
                    applyFilters();
                    updateStats();
                    renderNotifications();
                    renderPagination();
                    showToast('success', 'Notificação marcada como lida');
                }
            }
        } catch (error) {
            console.error('[Mark As Read Error]', error);
            showToast('error', 'Erro ao marcar notificação');
        } finally {
            isProcessing = false;
        }
    };
    
    window.deleteNotification = async function(id) {
        if (!confirm('Tem certeza que deseja eliminar esta notificação?')) return;
        
        if (isProcessing) return;
        isProcessing = true;
        
        try {
            const result = await apiRequest(`/notifications/${id}`, { method: 'DELETE' });
            if (result && result.success) {
                state.notifications = state.notifications.filter(n => n.id !== id);
                applyFilters();
                updateStats();
                renderNotifications();
                renderPagination();
                showToast('success', 'Notificação eliminada com sucesso');
            }
        } catch (error) {
            console.error('[Delete Error]', error);
            showToast('error', 'Erro ao eliminar notificação');
        } finally {
            isProcessing = false;
        }
    };
    
    window.viewNotification = function(id) {
        const notification = state.notifications.find(n => n.id === id);
        if (!notification) return;
        
        if (notification.is_read == 0) {
            markAsRead(id);
        }
        
        showToast('info', 'Visualizando: ' + notification.title);
    };
    
    // BULK ACTIONS

    document.getElementById('markAllRead')?.addEventListener('click', async function() {
        if (isProcessing) return;
        
        const unread = state.notifications.filter(n => n.is_read == 0);
        if (unread.length === 0) {
            showToast('warning', 'Não há notificações não lidas');
            return;
        }
        
        isProcessing = true;
        
        try {
            const result = await apiRequest('/notifications/read-all', { method: 'POST' });
            if (result && result.success) {
                state.notifications.forEach(n => { n.is_read = 1; });
                applyFilters();
                updateStats();
                renderNotifications();
                renderPagination();
                showToast('success', `${unread.length} notificações marcadas como lidas`);
            }
        } catch (error) {
            console.error('[Mark All Read Error]', error);
            showToast('error', 'Erro ao marcar notificações');
        } finally {
            isProcessing = false;
        }
    });
    
    document.getElementById('deleteAllRead')?.addEventListener('click', async function() {
        const read = state.notifications.filter(n => n.is_read == 1);
        if (read.length === 0) {
            showToast('warning', 'Não há notificações lidas para eliminar');
            return;
        }
        
        if (!confirm(`Tem certeza que deseja eliminar ${read.length} notificações lidas?`)) return;
        
        if (isProcessing) return;
        isProcessing = true;
        
        try {
            const result = await apiRequest('/notifications/delete-read', { method: 'DELETE' });
            if (result && result.success) {
                state.notifications = state.notifications.filter(n => n.is_read == 0);
                applyFilters();
                updateStats();
                renderNotifications();
                renderPagination();
                showToast('success', `${read.length} notificações eliminadas`);
            }
        } catch (error) {
            console.error('[Delete Read Error]', error);
            showToast('error', 'Erro ao eliminar notificações');
        } finally {
            isProcessing = false;
        }
    });
    
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
    
    // INIT
    
    async function init() {
        console.log('[CARDOXIS] Inicializando Notifications v2.0.0...');
        
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        initSidebar();
        await loadNotifications();
        
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            updateLastUpdateTime();
        }, CONFIG.REFRESH_INTERVAL);
        
        console.log('[CARDOXIS] Notifications inicializado com sucesso!');
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();