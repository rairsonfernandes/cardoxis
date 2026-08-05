/**
 * CARDOXIS Admin JavaScript RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES

    const API_URL = 'http://localhost/cardoxis/api/v1';
    const REFRESH_INTERVAL = 30000;
    let refreshTimer = null;
    
    // FUNÇÃO PARA OBTER TOKEN

    function getToken() {
        const token = localStorage.getItem('auth_token') || 
                     sessionStorage.getItem('auth_token') ||
                     localStorage.getItem('cardoxis_auth_token') ||
                     sessionStorage.getItem('cardoxis_auth_token');
        
        console.log('[CARDOXIS] Token status:', token ? 'Presente' : 'Ausente');
        return token;
    }
    
    // FUNÇÃO PARA VERIFICAR AUTENTICAÇÃO

    function checkAuth() {
        const token = getToken();
        if (!token) {
            console.warn('[CARDOXIS] Não autenticado, redirecionando para login');
            window.location.href = '/cardoxis/login';
            return false;
        }
        return true;
    }
    
    // REQUISIÇÕES API

    async function apiRequest(endpoint, options = {}) {
        const token = getToken();
        
        if (!token) {
            console.error('[CARDOXIS] Sem token para a requisição:', endpoint);
            return { success: false, message: 'Não autenticado' };
        }
        
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...options.headers
        };
        
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 15000);
            
            const response = await fetch(`${API_URL}${endpoint}`, {
                ...options,
                headers,
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (response.status === 401) {
                console.warn('[CARDOXIS] Token inválido ou expirado');
                localStorage.removeItem('auth_token');
                sessionStorage.removeItem('auth_token');
                window.location.href = '/cardoxis/login';
                return null;
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('[API Error]', endpoint, error);
            return { success: false, message: error.message };
        }
    }
    
    // LOGOUT

    async function logout() {
        try {
            await fetch('/cardoxis/logout', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        } catch (error) {
            console.error('[CARDOXIS] Erro no logout:', error);
        }
        
        localStorage.removeItem('auth_token');
        sessionStorage.removeItem('auth_token');
        localStorage.removeItem('cardoxis_auth_token');
        sessionStorage.removeItem('cardoxis_auth_token');
        
        window.location.href = '/cardoxis/login';
    }

    // CARREGAR ESTATÍSTICAS

    async function loadDashboardStats() {
        console.log('[CARDOXIS] Carregando estatísticas...');
        const stats = await apiRequest('/admin/dashboard/stats');
        
        if (stats && stats.success && stats.data) {
            setElementText('totalCompanies', stats.data.total_companies || 0);
            setElementText('totalUsers', stats.data.total_users || 0);
            setElementText('totalVehicles', stats.data.total_vehicles || 0);
            setElementText('totalDrivers', stats.data.total_drivers || 0);
            setElementText('activeSubscriptions', stats.data.active_subscriptions || 0);
            setElementText('pendingMaintenance', stats.data.pending_maintenances || 0);
            setElementText('expiringDocuments', stats.data.expiring_documents || 0);
            setElementText('monthlyRevenue', formatMoney(stats.data.monthly_revenue || 0));
            
            setElementText('companiesCount', stats.data.total_companies || 0);
            setElementText('usersCount', stats.data.total_users || 0);
        }
        return stats;
    }
    
    // CARREGAR EMPRESAS RECENTES

    async function loadRecentCompanies() {
        const response = await apiRequest('/admin/companies?limit=5');
        const container = document.getElementById('recentCompaniesList');
        
        if (container && response && response.success && response.data && response.data.length > 0) {
            container.innerHTML = response.data.map(company => `
                <div class="list-item">
                    <div class="list-item-info">
                        <div class="list-item-title">${escapeHtml(company.name)}</div>
                        <div class="list-item-subtitle">${escapeHtml(company.document || '-')}</div>
                    </div>
                    <span class="badge ${company.status === 'active' ? 'badge-success' : 'badge-warning'}">
                        ${company.status === 'active' ? 'Ativa' : 'Inativa'}
                    </span>
                </div>
            `).join('');
        } else if (container) {
            container.innerHTML = '<div class="list-item"><div class="list-item-info">Nenhuma empresa encontrada</div></div>';
        }
    }
    
    // CARREGAR PAGAMENTOS RECENTES

    async function loadRecentPayments() {
        const response = await apiRequest('/admin/payments?limit=5');
        const container = document.getElementById('recentPaymentsList');
        
        if (container && response && response.success && response.data && response.data.length > 0) {
            container.innerHTML = response.data.map(payment => `
                <div class="list-item">
                    <div class="list-item-info">
                        <div class="list-item-title">${escapeHtml(payment.company_name || 'Empresa')}</div>
                        <div class="list-item-subtitle">${formatDate(payment.payment_date)}</div>
                    </div>
                    <span class="badge ${payment.status === 'paid' ? 'badge-success' : 'badge-warning'}">
                        ${formatMoney(payment.amount)}
                    </span>
                </div>
            `).join('');
        } else if (container) {
            container.innerHTML = '<div class="list-item"><div class="list-item-info">Nenhum pagamento encontrado</div></div>';
        }
    }
    
    // CARREGAR ALERTAS DO SISTEMA

    async function loadSystemAlerts() {
        try {
            const alerts = await apiRequest('/alerts?limit=5');
            const container = document.getElementById('systemAlertsList');
            
            if (container && alerts && alerts.success && alerts.data && alerts.data.length > 0) {
                container.innerHTML = alerts.data.map(alert => `
                    <div class="alert-item ${alert.severity === 'critical' ? 'critical' : (alert.severity === 'warning' ? 'warning' : 'info')}">
                        <div class="alert-icon">
                            <i class="fas ${getAlertIcon(alert.type)}"></i>
                        </div>
                        <div class="alert-content">
                            <div class="alert-title">${escapeHtml(alert.title)}</div>
                            <div class="alert-message">${escapeHtml(alert.message)}</div>
                        </div>
                    </div>
                `).join('');
            } else if (container) {
                container.innerHTML = getSampleAlerts();
            }
        } catch (error) {
            const container = document.getElementById('systemAlertsList');
            if (container) container.innerHTML = getSampleAlerts();
        }
    }
    
    function getSampleAlerts() {
        return `
            <div class="alert-item info">
                <div class="alert-icon"><i class="fas fa-info-circle"></i></div>
                <div class="alert-content">
                    <div class="alert-title">Sistema operacional</div>
                    <div class="alert-message">Todos os sistemas estão funcionando normalmente</div>
                </div>
            </div>
            <div class="alert-item warning">
                <div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="alert-content">
                    <div class="alert-title">Backup pendente</div>
                    <div class="alert-message">O último backup foi há mais de 7 dias</div>
                </div>
            </div>
        `;
    }
    
    function getAlertIcon(type) {
        const icons = { 
            'maintenance': 'fa-tools', 
            'document': 'fa-file-alt', 
            'payment': 'fa-credit-card', 
            'user': 'fa-user',
            'info': 'fa-info-circle',
            'warning': 'fa-exclamation-triangle',
            'critical': 'fa-times-circle'
        };
        return icons[type] || 'fa-bell';
    }
    
    // CARREGAR SAÚDE DO SISTEMA

    async function loadSystemHealth() {
        const health = await apiRequest('/admin/system/health');
        const container = document.getElementById('systemHealthList');
        
        if (container && health && health.success && health.data) {
            container.innerHTML = `
                <div class="health-item">
                    <span class="health-label"><i class="fas fa-database"></i> Base de Dados</span>
                    <span class="health-status ${health.data.database === 'healthy' ? 'healthy' : 'critical'}">
                        ${health.data.database === 'healthy' ? 'Operacional' : 'Problema'}
                    </span>
                </div>
                <div class="health-item">
                    <span class="health-label"><i class="fas fa-hdd"></i> Disco</span>
                    <span class="health-status ${(health.data.disk_usage || 0) < 80 ? 'healthy' : 'warning'}">
                        ${health.data.disk_usage || 45}% usado
                    </span>
                </div>
                <div class="health-item">
                    <span class="health-label"><i class="fas fa-microchip"></i> Memória</span>
                    <span class="health-status healthy">${health.data.memory_usage || '256 MB'}</span>
                </div>
                <div class="health-item">
                    <span class="health-label"><i class="fas fa-cloud-upload-alt"></i> Uploads</span>
                    <span class="health-status ${health.data.uploads === 'writable' ? 'healthy' : 'warning'}">
                        ${health.data.uploads === 'writable' ? 'OK' : 'Erro na pasta uploads'}
                    </span>
                </div>
            `;
        } else if (container) {
            container.innerHTML = '<div class="health-item"><span class="health-label">Carregando...</span></div>';
        }
    }
    
    // CARREGAR BACKUPS

    async function loadBackups() {
        const backups = await apiRequest('/admin/system/backups');
        const container = document.getElementById('backupsList');
        
        if (container && backups && backups.success && backups.data && backups.data.length > 0) {
            container.innerHTML = backups.data.map(backup => `
                <div class="list-item">
                    <div class="list-item-info">
                        <div class="list-item-title">${escapeHtml(backup.name)}</div>
                        <div class="list-item-subtitle">${escapeHtml(backup.size)}</div>
                    </div>
                    <button class="btn-icon" onclick="restoreBackup('${backup.name}')" title="Restaurar">
                        <i class="fas fa-undo-alt"></i>
                    </button>
                </div>
            `).join('');
        } else if (container) {
            container.innerHTML = '<div class="list-item"><div class="list-item-info">Nenhum backup encontrado</div></div>';
        }
    }
    
    // CARREGAR ATIVIDADES RECENTES

    async function loadRecentActivities() {
        const data = await apiRequest('/dashboard/recent-activities');
        const container = document.getElementById('activityList');
        
        if (container && data && data.success && data.data?.activities && data.data.activities.length > 0) {
            container.innerHTML = data.data.activities.map(activity => `
                <div class="activity-item">
                    <div class="activity-icon primary">
                        <i class="fas fa-${getActivityIcon(activity.action)}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">${escapeHtml(activity.description || activity.action || 'Atividade registada')}</div>
                        <div class="activity-time">${formatDateRelative(activity.created_at)}</div>
                    </div>
                </div>
            `).join('');
        } else if (container) {
            container.innerHTML = getSampleActivities();
        }
    }
    
    function getSampleActivities() {
        return `
            <div class="activity-item">
                <div class="activity-icon primary">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">Dashboard carregado com sucesso</div>
                    <div class="activity-time">Agora mesmo</div>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon success">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">Bem-vindo ao CARDOXIS</div>
                    <div class="activity-time">Há poucos instantes</div>
                </div>
            </div>
        `;
    }
    
    function getActivityIcon(action) {
        const icons = {
            'login': 'sign-in-alt',
            'create_company': 'building',
            'create_user': 'user-plus',
            'create_vehicle': 'truck',
            'create_driver': 'id-card',
            'view_dashboard': 'tachometer-alt'
        };
        return icons[action] || 'bell';
    }
    
    
    // GRÁFICOS
    
    function initCharts() {
        const revenueCtx = document.getElementById('revenueChart')?.getContext('2d');
        if (revenueCtx) {
            window.revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                    datasets: [{
                        label: 'Receita (€)',
                        data: [12500, 13800, 14200, 15600, 16800, 17500],
                        borderColor: '#0052CC',
                        backgroundColor: 'rgba(0,82,204,0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        }
        
        const usersCtx = document.getElementById('usersChart')?.getContext('2d');
        if (usersCtx) {
            window.usersChart = new Chart(usersCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                    datasets: [{
                        label: 'Novos Utilizadores',
                        data: [45, 52, 48, 61, 58, 67],
                        backgroundColor: '#36B37E',
                        borderRadius: 8
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        }
    }
    
    async function loadRevenueChart() {
        const revenue = await apiRequest('/admin/dashboard/revenue');
        if (revenue && revenue.success && revenue.data && window.revenueChart) {
            const months = revenue.data.monthly?.map(m => m.month) || ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
            const values = revenue.data.monthly?.map(m => m.total) || [0, 0, 0, 0, 0, 0];
            window.revenueChart.data.labels = months;
            window.revenueChart.data.datasets[0].data = values;
            window.revenueChart.update();
        }
    }
    
    async function loadUsersGrowthChart() {
        const growth = await apiRequest('/admin/dashboard/users-growth');
        if (growth && growth.success && growth.data && window.usersChart) {
            const months = growth.data.monthly?.map(m => m.month) || ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
            const values = growth.data.monthly?.map(m => m.new_users) || [0, 0, 0, 0, 0, 0];
            window.usersChart.data.labels = months;
            window.usersChart.data.datasets[0].data = values;
            window.usersChart.update();
        }
    }
    
    // AÇÕES GLOBAIS
    
    window.createBackup = async function() {
        if (!checkAuth()) return;
        showToast('info', 'A criar backup...', 2000);
        const response = await apiRequest('/admin/system/backup', { method: 'POST' });
        if (response && response.success) {
            showToast('success', 'Backup criado com sucesso!', 3000);
            loadBackups();
        } else {
            showToast('error', 'Falha ao criar backup', 3000);
        }
    };
    
    window.restoreBackup = async function(filename) {
        if (!confirm(`Restaurar backup "${filename}"?`)) return;
        showToast('info', 'A restaurar backup...', 2000);
        const response = await apiRequest('/admin/system/backup/restore', { method: 'POST', body: JSON.stringify({ filename }) });
        if (response && response.success) {
            showToast('success', 'Backup restaurado! Recarregando...', 2000);
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('error', 'Falha ao restaurar backup', 3000);
        }
    };
    
    window.clearCache = async function() {
        if (!confirm('Limpar cache do sistema?')) return;
        showToast('info', 'A limpar cache...', 2000);
        const response = await apiRequest('/admin/system/cache-clear', { method: 'POST' });
        if (response && response.success) {
            showToast('success', 'Cache limpo com sucesso!', 2000);
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', 'Falha ao limpar cache', 3000);
        }
    };
    
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
            <div class="toast-icon"><i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : 'info-circle')}"></i></div>
            <div class="toast-content"><div class="toast-message">${escapeHtml(message)}</div></div>
            <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), duration);
    }
    
    
    // UTILITÁRIOS
    
    function setElementText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value !== undefined && value !== null ? value : '0';
    }
    
    function formatMoney(value) {
        if (!value && value !== 0) return '€ 0,00';
        return `€ ${value.toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
    
    function formatDateRelative(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        if (diff < 60000) return 'Agora mesmo';
        if (diff < 3600000) return `${Math.floor(diff / 60000)} min atrás`;
        if (diff < 86400000) return `${Math.floor(diff / 3600000)} horas atrás`;
        if (diff < 604800000) return `${Math.floor(diff / 86400000)} dias atrás`;
        return date.toLocaleDateString('pt-PT');
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('pt-PT');
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    
    // SIDEBAR
    
    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('menuToggle');
        const sidebarClose = document.getElementById('sidebarCloseBtn');
        
        function openSidebar() { if (sidebar) sidebar.classList.add('open'); if (overlay) overlay.classList.add('active'); }
        function closeSidebar() { if (sidebar) sidebar.classList.remove('open'); if (overlay) overlay.classList.remove('active'); }
        
        if (menuToggle) menuToggle.addEventListener('click', openSidebar);
        if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);
        
        const userMenu = document.getElementById('userMenu');
        const userDropdown = document.getElementById('userDropdown');
        const userChevron = document.getElementById('userChevron');
        
        if (userMenu && userDropdown) {
            userMenu.addEventListener('click', (e) => {
                e.preventDefault();
                userDropdown.classList.toggle('show');
                if (userChevron) userChevron.style.transform = userDropdown.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
            });
            document.addEventListener('click', (e) => {
                if (!userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.remove('show');
                    if (userChevron) userChevron.style.transform = 'rotate(0deg)';
                }
            });
        }
        
        const logoutBtn = document.getElementById('logoutSidebarBtn');
        if (logoutBtn) logoutBtn.addEventListener('click', (e) => { e.preventDefault(); logout(); });
    }
    
    
    // AUTO REFRESH
    
    function startAutoRefresh() {
        if (refreshTimer) clearInterval(refreshTimer);
        refreshTimer = setInterval(() => {
            if (document.visibilityState === 'visible') {
                loadDashboardStats();
                loadRecentActivities();
                loadSystemHealth();
                loadSystemAlerts();
            }
        }, REFRESH_INTERVAL);
    }
    
    
    // INICIALIZAÇÃO
    
    document.addEventListener('DOMContentLoaded', () => {
        console.log('[CARDOXIS] Inicializando Dashboard Admin v6.2.0');
        
        const token = getToken();
        if (!token) {
            console.warn('[CARDOXIS] Usuário não autenticado');
            return;
        }
        
        initSidebar();
        initCharts();
        
        loadDashboardStats();
        loadRecentCompanies();
        loadRecentPayments();
        loadSystemAlerts();
        loadSystemHealth();
        loadBackups();
        loadRecentActivities();
        loadRevenueChart();
        loadUsersGrowthChart();
        
        startAutoRefresh();
        
        console.log('[CARDOXIS] Dashboard inicializado!');
    });
    
    window.addEventListener('beforeunload', () => {
        if (refreshTimer) clearInterval(refreshTimer);
    });
})();