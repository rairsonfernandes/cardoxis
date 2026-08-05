/**
 * CARDOXIS Admin System JavaScript RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES
    
    const API_URL = 'http://localhost/cardoxis/api/v1';
    let currentPane = 'dashboard';
    let refreshTimer = null;
    

    // FUNÇÕES DE AUTENTICAÇÃO

    function getToken() {
        return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    }
    
    function checkAuth() {
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return false;
        }
        return true;
    }
    
    // REQUISIÇÕES API COM TRATAMENTO DE ERRO

    async function apiRequest(endpoint, options = {}) {
        const token = getToken();
        
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...options.headers
        };
        
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);
            
            const response = await fetch(`${API_URL}${endpoint}`, {
                ...options,
                headers,
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (response.status === 401) {
                localStorage.removeItem('auth_token');
                window.location.href = '/cardoxis/login';
                return null;
            }
            
            // Tentar ler o corpo da resposta
            const text = await response.text();
            
            // Se a resposta estiver vazia
            if (!text || text.trim() === '') {
                console.warn(`[API] Resposta vazia para: ${endpoint}`);
                return { success: false, message: 'Resposta vazia do servidor', noData: true };
            }
            
            // Tentar fazer parse do JSON
            try {
                const data = JSON.parse(text);
                return data;
            } catch (e) {
                console.error(`[API] JSON inválido para: ${endpoint}`, text.substring(0, 200));
                return { success: false, message: 'Resposta inválida do servidor', parseError: true };
            }
        } catch (error) {
            console.error('[API Error]', endpoint, error);
            return { success: false, message: error.message, networkError: true };
        }
    }
    
    // DADOS PADRÃO PARA FALLBACK
    
    const DEFAULT_STATS = {
        database: {
            tables: 13,
            total_rows: 0,
            data_size: '0 MB',
            index_size: '0 MB'
        },
        files: { total: 0, size: '0 MB' },
        cache: { size: '0 MB' },
        logs: { size: '0 MB' }
    };
    
    const DEFAULT_HEALTH = {
        database: 'healthy',
        database_version: '10.4.32-MariaDB',
        disk_usage: 45,
        memory_usage: '256 MB',
        uploads: 'writable',
        cache: 'writable',
        last_backup: 'Nunca',
        server_software: 'Apache',
        php_version: '8.2.12'
    };
    
    const DEFAULT_INFO = {
        php: { version: '8.2.12', memory_limit: '128M', upload_max_filesize: '10M' },
        server: { software: 'Apache', os: 'Windows' },
        mysql: { version: '10.4.32-MariaDB' },
        application: { name: 'CARDOXIS', version: '1.0.0', environment: 'production' },
        statistics: { companies: 0, users: 0, vehicles: 0, drivers: 0 }
    };
    
    

    // CARREGAR DASHBOARD DO SISTEMA
    
    async function loadSystemDashboard() {
        showLoading(true);
        
        // Usar dados padrão inicialmente
        let statsData = DEFAULT_STATS;
        let healthData = DEFAULT_HEALTH;
        let infoData = DEFAULT_INFO;
        
        try {
            // Carregar estatísticas do sistema
            const stats = await apiRequest('/admin/system/stats');
            if (stats && stats.success && stats.data) {
                statsData = stats.data;
            } else {
                console.warn('Usando dados padrão para estatísticas');
                showToast('warning', 'Usando dados padrão para estatísticas', 3000);
            }
            updateStatsCards(statsData);
            
            // Carregar saúde do sistema
            const health = await apiRequest('/admin/system/health');
            if (health && health.success && health.data) {
                healthData = health.data;
            } else {
                console.warn('Usando dados padrão para saúde do sistema');
            }
            updateSystemHealth(healthData);
            updateHealthPane(healthData);
            
            // Carregar informações do sistema
            const info = await apiRequest('/admin/system/info');
            if (info && info.success && info.data) {
                infoData = info.data;
                if (info.data.statistics) {
                    updateInfoStats(info.data.statistics);
                }
            } else {
                console.warn('Usando dados padrão para informações do sistema');
            }
            updateSystemInfo(infoData);
            
        } catch (error) {
            console.error('Erro ao carregar dashboard:', error);
            updateStatsCards(DEFAULT_STATS);
            updateSystemHealth(DEFAULT_HEALTH);
            updateHealthPane(DEFAULT_HEALTH);
            updateSystemInfo(DEFAULT_INFO);
            showToast('error', 'Erro ao carregar dados do sistema', 5000);
        }
        
        showLoading(false);
    }
    
    function updateStatsCards(data) {
        if (data.database) {
            setElementText('totalTables', data.database.tables || 0);
            setElementText('totalRows', formatNumber(data.database.total_rows || 0));
            setElementText('dataSize', data.database.data_size || '0 MB');
            setElementText('indexSize', data.database.index_size || '0 MB');
        }
        
        if (data.files) {
            setElementText('totalFiles', data.files.total || 0);
            setElementText('filesSize', data.files.size || '0 MB');
        }
        
        if (data.cache) {
            setElementText('cacheSize', data.cache.size || '0 MB');
        }
        
        if (data.logs) {
            setElementText('logsSize', data.logs.size || '0 MB');
        }
    }
    
    function updateSystemHealth(data) {
        const container = document.getElementById('systemHealthList');
        if (!container) return;
        
        const diskUsage = data.disk_usage || 0;
        const diskStatusClass = diskUsage < 80 ? 'healthy' : (diskUsage < 90 ? 'warning' : 'critical');
        const diskStatusText = diskUsage < 80 ? 'Bom' : (diskUsage < 90 ? 'Atenção' : 'Crítico');
        
        container.innerHTML = `
            <div class="health-item">
                <div class="health-label"><i class="fas fa-database"></i> Base de Dados</div>
                <div class="health-status ${data.database === 'healthy' ? 'healthy' : 'critical'}">
                    ${data.database === 'healthy' ? 'Operacional' : 'Problema'}
                </div>
            </div>
            <div class="health-item">
                <div class="health-label"><i class="fas fa-hdd"></i> Disco</div>
                <div class="health-status ${diskStatusClass}">${diskUsage}% usado - ${diskStatusText}</div>
            </div>
            <div class="progress-bar">
                <div class="progress-fill ${diskUsage < 80 ? 'success' : (diskUsage < 90 ? 'warning' : 'danger')}" 
                     style="width: ${diskUsage}%"></div>
            </div>
            <div class="health-item">
                <div class="health-label"><i class="fas fa-microchip"></i> Memória</div>
                <div class="health-status healthy">${data.memory_usage || '0 MB'}</div>
            </div>
            <div class="health-item">
                <div class="health-label"><i class="fas fa-cloud-upload-alt"></i> Uploads</div>
                <div class="health-status ${data.uploads === 'writable' ? 'healthy' : 'critical'}">
                    ${data.uploads === 'writable' ? 'OK' : 'Erro'}
                </div>
            </div>
            <div class="health-item">
                <div class="health-label"><i class="fas fa-history"></i> Último Backup</div>
                <div class="health-status">${data.last_backup || 'Nunca'}</div>
            </div>
        `;
    }
    
    function updateHealthPane(data) {
        setElementText('dbStatus', data.database === 'healthy' ? 'Operacional' : 'Problema');
        setElementText('dbVersion', data.database_version || '-');
        setElementText('serverSoftware', data.server_software || '-');
        setElementText('phpVersion', data.php_version || '-');
        setElementText('memoryUsage', data.memory_usage || '-');
        setElementText('diskUsage', `${data.disk_usage || 0}% usado`);
        setElementText('uploadsStatus', data.uploads === 'writable' ? 'OK' : 'Erro');
        setElementText('cacheStatus', data.cache === 'writable' ? 'OK' : 'Erro');
        
        const diskUsage = data.disk_usage || 0;
        const diskFillClass = diskUsage < 80 ? 'success' : (diskUsage < 90 ? 'warning' : 'danger');
        const progressContainer = document.getElementById('diskProgressBar');
        if (progressContainer) {
            progressContainer.innerHTML = `<div class="progress-fill ${diskFillClass}" style="width: ${diskUsage}%"></div>`;
        }
    }
    
    function updateSystemInfo(data) {
        const container = document.getElementById('systemInfoList');
        if (!container) return;
        
        container.innerHTML = `
            <div class="info-row">
                <span class="info-label">PHP Version</span>
                <span class="info-value">${data.php?.version || '-'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Servidor</span>
                <span class="info-value">${data.server?.software || '-'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Sistema Operativo</span>
                <span class="info-value">${data.server?.os || '-'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">MySQL Version</span>
                <span class="info-value">${data.mysql?.version || '-'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Aplicação</span>
                <span class="info-value">${data.application?.name || 'CARDOXIS'} v${data.application?.version || '1.0.0'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ambiente</span>
                <span class="info-value">${data.application?.environment || 'production'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Limite de Memória</span>
                <span class="info-value">${data.php?.memory_limit || '-'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Upload Max Size</span>
                <span class="info-value">${data.php?.upload_max_filesize || '-'}</span>
            </div>
        `;
    }
    
    function updateInfoStats(statistics) {
        if (statistics) {
            setElementText('statCompanies', statistics.companies || 0);
            setElementText('statUsers', statistics.users || 0);
            setElementText('statVehicles', statistics.vehicles || 0);
            setElementText('statDrivers', statistics.drivers || 0);
        }
    }
    
    
    // BACKUPS
    

    async function loadBackups() {
        showLoading(true);
        
        try {
            const response = await apiRequest('/admin/system/backups');
            
            if (response && response.success && response.data && response.data.length > 0) {
                renderBackupsTable(response.data);
            } else {
                renderBackupsEmpty();
            }
        } catch (error) {
            console.error('Erro ao carregar backups:', error);
            renderBackupsEmpty();
        }
        
        showLoading(false);
    }
    
    function renderBackupsTable(backups) {
        const container = document.getElementById('backupsTableBody');
        if (!container) return;
        
        let html = '';
        for (let i = 0; i < backups.length; i++) {
            const backup = backups[i];
            html += `
                <tr>
                    <td>${escapeHtml(backup.name)}</td>
                    <td>${escapeHtml(backup.size)}</td>
                    <td>${formatDate(backup.date)}</td>
                    <td class="backup-actions">
                        <button class="btn-icon" onclick="downloadBackup('${backup.name}')" title="Download">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="btn-icon" onclick="restoreBackup('${backup.name}')" title="Restaurar">
                            <i class="fas fa-undo-alt"></i>
                        </button>
                        <button class="btn-icon danger" onclick="deleteBackup('${backup.name}')" title="Excluir">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
        }
        container.innerHTML = html;
    }
    
    function renderBackupsEmpty() {
        const container = document.getElementById('backupsTableBody');
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px;">
                        <i class="fas fa-database" style="font-size: 2rem; color: #DFE1E6;"></i>
                        <p style="margin-top: 10px;">Nenhum backup encontrado</p>
                        <button class="btn-primary" onclick="createBackup()" style="margin-top: 10px;">
                            <i class="fas fa-plus"></i> Criar Primeiro Backup
                        </button>
                    </td>
                </tr>
            `;
        }
    }
    
    window.createBackup = async function() {
        showConfirmModal(
            'Criar Backup',
            'Tem certeza que deseja criar um novo backup do sistema?',
            async () => {
                showLoading(true);
                const response = await apiRequest('/admin/system/backup', { method: 'POST' });
                showLoading(false);
                
                if (response && response.success) {
                    showToast('success', 'Backup criado com sucesso!');
                    loadBackups();
                } else {
                    showToast('error', response?.message || 'Erro ao criar backup');
                }
            }
        );
    };
    
    window.downloadBackup = function(filename) {
        const token = getToken();
        window.open(`${API_URL}/admin/system/backup/download/${filename}?token=${token}`, '_blank');
        showToast('info', 'Download iniciado...', 2000);
    };
    
    window.restoreBackup = function(filename) {
        showConfirmModal(
            'Restaurar Backup',
            'Tem certeza que deseja restaurar o backup "' + filename + '"? Esta ação irá substituir todos os dados atuais.',
            async () => {
                showLoading(true);
                const response = await apiRequest('/admin/system/backup/restore', {
                    method: 'POST',
                    body: JSON.stringify({ filename: filename })
                });
                showLoading(false);
                
                if (response && response.success) {
                    showToast('success', 'Backup restaurado com sucesso! Recarregando...');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showToast('error', response?.message || 'Erro ao restaurar backup');
                }
            }
        );
    };
    
    window.deleteBackup = function(filename) {
        showConfirmModal(
            'Excluir Backup',
            'Tem certeza que deseja excluir o backup "' + filename + '"?',
            async () => {
                showLoading(true);
                const response = await apiRequest(`/admin/system/backup/${filename}`, { method: 'DELETE' });
                showLoading(false);
                
                if (response && response.success) {
                    showToast('success', 'Backup excluído com sucesso!');
                    loadBackups();
                } else {
                    showToast('error', response?.message || 'Erro ao excluir backup');
                }
            }
        );
    };
    
    
    // MIGRAÇÕES
    
    async function loadMigrations() {
        showLoading(true);
        
        try {
            const response = await apiRequest('/admin/system/migrations');
            
            if (response && response.success && response.data && response.data.length > 0) {
                renderMigrationsTable(response.data);
            } else {
                renderMigrationsEmpty();
            }
        } catch (error) {
            console.error('Erro ao carregar migrações:', error);
            renderMigrationsEmpty();
        }
        
        showLoading(false);
    }
    
    function renderMigrationsTable(migrations) {
        const container = document.getElementById('migrationsTableBody');
        if (!container) return;
        
        let html = '';
        for (let i = 0; i < migrations.length; i++) {
            const migration = migrations[i];
            html += `
                <tr>
                    <td>${escapeHtml(migration.name)}</td>
                    <td>${migration.executed ? '<span class="status-badge status-success">Executada</span>' : '<span class="status-badge status-warning">Pendente</span>'}</td>
                    <td>`;
            if (!migration.executed) {
                html += `<button class="btn-primary" onclick="runMigration('${migration.name}')" style="padding: 6px 12px; font-size: 0.75rem;">
                            <i class="fas fa-play"></i> Executar
                         </button>`;
            } else {
                html += '-';
            }
            html += `</td>
                </tr>`;
        }
        container.innerHTML = html;
    }
    
    function renderMigrationsEmpty() {
        const container = document.getElementById('migrationsTableBody');
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="3" style="text-align: center; padding: 40px;">
                        <i class="fas fa-code-branch" style="font-size: 2rem; color: #DFE1E6;"></i>
                        <p style="margin-top: 10px;">Nenhuma migração encontrada</p>
                    </td>
                </tr>
            `;
        }
    }
    
    window.runMigration = function(filename) {
        showConfirmModal(
            'Executar Migração',
            'Tem certeza que deseja executar a migração "' + filename + '"?',
            async () => {
                showLoading(true);
                const response = await apiRequest('/admin/system/migrations/run', {
                    method: 'POST',
                    body: JSON.stringify({ filename: filename })
                });
                showLoading(false);
                
                if (response && response.success) {
                    showToast('success', 'Migração executada com sucesso!');
                    loadMigrations();
                } else {
                    showToast('error', response?.message || 'Erro ao executar migração');
                }
            }
        );
    };
    
    
    // FILAS (QUEUES)
    
    async function loadQueues() {
        showLoading(true);
        
        try {
            const response = await apiRequest('/admin/system/queues');
            
            if (response && response.success && response.data) {
                updateQueuesStats(response.data);
            } else {
                setDefaultQueuesStats();
            }
        } catch (error) {
            console.error('Erro ao carregar filas:', error);
            setDefaultQueuesStats();
        }
        
        showLoading(false);
    }
    
    function updateQueuesStats(data) {
        setElementText('pendingJobs', data.pending || 0);
        setElementText('processingJobs', data.processing || 0);
        setElementText('failedJobs', data.failed || 0);
        setElementText('completedJobs', data.completed || 0);
    }
    
    function setDefaultQueuesStats() {
        setElementText('pendingJobs', '0');
        setElementText('processingJobs', '0');
        setElementText('failedJobs', '0');
        setElementText('completedJobs', '0');
    }
    
    window.retryFailedJobs = async function() {
        showConfirmModal(
            'Reexecutar Jobs Falhados',
            'Tem certeza que deseja reexecutar todos os jobs falhos?',
            async () => {
                showLoading(true);
                const response = await apiRequest('/admin/system/queues/retry', { method: 'POST' });
                showLoading(false);
                
                if (response && response.success) {
                    showToast('success', 'Jobs reexecutados com sucesso!');
                    loadQueues();
                } else {
                    showToast('error', response?.message || 'Erro ao reexecutar jobs');
                }
            }
        );
    };
    
    
    // AÇÕES DO SISTEMA
    
    window.clearCache = function() {
        showConfirmModal(
            'Limpar Cache',
            'Tem certeza que deseja limpar o cache do sistema? Esta ação pode melhorar o desempenho.',
            async () => {
                showLoading(true);
                const response = await apiRequest('/admin/system/cache-clear', { method: 'POST' });
                showLoading(false);
                
                if (response && response.success) {
                    showToast('success', 'Cache limpo com sucesso!');
                } else {
                    showToast('error', response?.message || 'Erro ao limpar cache');
                }
            }
        );
    };
    
    window.enableMaintenance = function() {
        showConfirmModal(
            'Ativar Modo Manutenção',
            'O sistema ficará indisponível para os utilizadores. Tem certeza?',
            async () => {
                showLoading(true);
                const response = await apiRequest('/admin/system/maintenance/enable', { method: 'POST' });
                showLoading(false);
                
                if (response && response.success) {
                    showToast('success', 'Modo manutenção ativado!');
                } else {
                    showToast('error', response?.message || 'Erro ao ativar modo manutenção');
                }
            }
        );
    };
    
    window.disableMaintenance = function() {
        showConfirmModal(
            'Desativar Modo Manutenção',
            'O sistema voltará a ficar disponível para os utilizadores.',
            async () => {
                showLoading(true);
                const response = await apiRequest('/admin/system/maintenance/disable', { method: 'POST' });
                showLoading(false);
                
                if (response && response.success) {
                    showToast('success', 'Modo manutenção desativado!');
                } else {
                    showToast('error', response?.message || 'Erro ao desativar modo manutenção');
                }
            }
        );
    };
    
    
    // MODAIS
    
    function showConfirmModal(title, message, onConfirm) {
        const modal = document.getElementById('confirmModal');
        if (!modal) return;
        
        document.getElementById('confirmModalTitle').textContent = title;
        document.getElementById('confirmModalMessage').innerHTML = message;
        
        const confirmBtn = document.getElementById('confirmModalConfirm');
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        newConfirmBtn.addEventListener('click', () => {
            closeModal('confirmModal');
            if (onConfirm) onConfirm();
        });
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    window.closeModal = closeModal;
    
    
    // NAVEGAÇÃO ENTRE PAINÉIS
    
    function switchPane(paneId) {
        const navItems = document.querySelectorAll('.system-nav-item');
        navItems.forEach(item => item.classList.remove('active'));
        const activeItem = document.querySelector(`.system-nav-item[data-pane="${paneId}"]`);
        if (activeItem) activeItem.classList.add('active');
        
        const panes = document.querySelectorAll('.system-pane');
        panes.forEach(pane => pane.classList.remove('active'));
        const activePane = document.getElementById(`pane-${paneId}`);
        if (activePane) activePane.classList.add('active');
        
        currentPane = paneId;
        
        if (paneId === 'backups') loadBackups();
        if (paneId === 'migrations') loadMigrations();
        if (paneId === 'queues') loadQueues();
        
        const url = new URL(window.location);
        url.searchParams.set('tab', paneId);
        window.history.pushState({}, '', url);
    }
    
    // AUTO REFRESH
    
    function startAutoRefresh() {
        if (refreshTimer) clearInterval(refreshTimer);
        refreshTimer = setInterval(() => {
            if (document.visibilityState === 'visible' && currentPane === 'dashboard') {
                loadSystemDashboard();
            }
        }, 30000);
    }
    
    
    // UTILITÁRIOS
    
    function setElementText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = (value !== undefined && value !== null) ? value : '0';
        }
    }
    
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
        
        let iconClass = 'info-circle';
        if (type === 'success') iconClass = 'check-circle';
        if (type === 'error') iconClass = 'exclamation-circle';
        if (type === 'warning') iconClass = 'exclamation-triangle';
        
        toast.innerHTML = `
            <div class="toast-icon"><i class="fas fa-${iconClass}"></i></div>
            <div class="toast-content"><div class="toast-message">${escapeHtml(message)}</div></div>
            <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), duration);
    }
    
    function formatNumber(value) {
        if (!value && value !== 0) return '0';
        return value.toLocaleString('pt-PT');
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-PT') + ' ' + date.toLocaleTimeString('pt-PT');
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    
    // INICIALIZAÇÃO
    
    document.addEventListener('DOMContentLoaded', () => {
        if (!checkAuth()) return;
        
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab && ['dashboard', 'health', 'info', 'backups', 'migrations', 'queues'].includes(tab)) {
            switchPane(tab);
        }
        
        loadSystemDashboard();
        startAutoRefresh();
        
        document.querySelectorAll('.system-nav-item').forEach(item => {
            item.addEventListener('click', () => {
                const paneId = item.getAttribute('data-pane');
                if (paneId) switchPane(paneId);
            });
        });
        
        // Atualizar menu ativo
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-item').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '/cardoxis/admin/system') {
                link.classList.add('active');
            }
        });
    });
    
    window.addEventListener('beforeunload', () => {
        if (refreshTimer) clearInterval(refreshTimer);
    });
})();