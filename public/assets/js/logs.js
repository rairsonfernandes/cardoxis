/**
 * CARDOXIS Admin Logs JavaScript
 * Version: 5.0.0 - Production Ready
 */

(function() {
    'use strict';
    
    const API_URL = 'http://localhost/cardoxis/api/v1';
    let currentTab = 'audit';
    let currentPage = 1;
    let totalPages = 1;
    let perPage = 20;
    let totalRecords = 0;
    
    // ============================================
    // FUNÇÕES DE AUTENTICAÇÃO
    // ============================================
    function getToken() {
        return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    }
    
    // ============================================
    // REQUISIÇÕES API
    // ============================================
    async function apiRequest(endpoint, options = {}) {
        const token = getToken();
        
        if (!token) {
            showToast('error', 'Sessão expirada. Faça login novamente.');
            setTimeout(() => { window.location.href = '/cardoxis/login'; }, 2000);
            return null;
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
                localStorage.removeItem('auth_token');
                sessionStorage.removeItem('auth_token');
                showToast('error', 'Sessão expirada. Faça login novamente.');
                setTimeout(() => { window.location.href = '/cardoxis/login'; }, 2000);
                return null;
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const data = await response.json();
                return data;
            } else {
                const text = await response.text();
                console.error('[API] Resposta não JSON:', text);
                return { success: false, message: 'Resposta inválida do servidor' };
            }
        } catch (error) {
            console.error('[API Error]', endpoint, error);
            return { success: false, message: error.message || 'Erro na requisição' };
        }
    }
    
    // ============================================
    // CARREGAR ESTATÍSTICAS
    // ============================================
    async function loadStats() {
        try {
            const auditResponse = await apiRequest('/admin/logs/audit?limit=1');
            const totalAudit = document.getElementById('totalAuditLogs');
            if (totalAudit && auditResponse?.success && auditResponse.data?.pagination) {
                totalAudit.textContent = auditResponse.data.pagination.total || 0;
            }
            
            const loginResponse = await apiRequest('/admin/logs/login?limit=1');
            const totalLogin = document.getElementById('totalLoginLogs');
            if (totalLogin && loginResponse?.success && loginResponse.data?.pagination) {
                totalLogin.textContent = loginResponse.data.pagination.total || 0;
            }
            
            const activityResponse = await apiRequest('/admin/logs/activity?limit=1');
            const totalActivity = document.getElementById('totalActivityLogs');
            if (totalActivity && activityResponse?.success && activityResponse.data?.pagination) {
                totalActivity.textContent = activityResponse.data.pagination.total || 0;
            }
            
            const apiResponse = await apiRequest('/admin/logs/api');
            const totalApi = document.getElementById('totalApiLogs');
            if (totalApi && apiResponse?.success) {
                const logs = apiResponse.data?.logs || [];
                const count = Array.isArray(logs) ? logs.length : 0;
                totalApi.textContent = count;
            }
            
        } catch (error) {
            console.error('Erro ao carregar estatísticas:', error);
        }
    }
    
    // ============================================
    // CARREGAR LOGS - CORRIGIDO
    // ============================================
    async function loadLogs() {
        showLoading(true);
        
        try {
            let endpoint = '';
            
            // Para tabs que não são API/Errors, usar paginação
            if (currentTab !== 'api' && currentTab !== 'errors') {
                const params = new URLSearchParams({
                    page: currentPage,
                    limit: perPage
                });
                
                // Adicionar filtros
                const searchInput = document.getElementById('searchInput');
                const actionFilter = document.getElementById('actionFilter');
                const startDate = document.getElementById('startDate');
                const endDate = document.getElementById('endDate');
                
                if (searchInput && searchInput.value.trim()) {
                    params.append('search', searchInput.value.trim());
                }
                if (actionFilter && actionFilter.value) {
                    params.append('action', actionFilter.value);
                }
                if (startDate && startDate.value) {
                    params.append('date_from', startDate.value);
                }
                if (endDate && endDate.value) {
                    params.append('date_to', endDate.value);
                }
                
                endpoint = `/admin/logs/${currentTab}?${params}`;
            } else {
                // API e Errors - sem paginação
                endpoint = `/admin/logs/${currentTab}`;
            }
            
            console.log('[LOGS] Carregando:', endpoint);
            const response = await apiRequest(endpoint);
            console.log('[LOGS] Resposta:', response);
            
            if (response && response.success) {
                if (currentTab === 'api' || currentTab === 'errors') {
                    renderFileLogs(response.data);
                    updateFileLogCounter(response.data);
                } else {
                    const logs = response.data?.data || [];
                    const pagination = response.data?.pagination || {};
                    renderTableLogs(logs);
                    updatePagination(pagination);
                }
            } else {
                console.error('[LOGS] Erro na resposta:', response);
                showEmptyState(response?.message || 'Erro ao carregar logs');
                showToast('error', response?.message || 'Erro ao carregar logs');
            }
        } catch (error) {
            console.error('[LOGS] Erro:', error);
            showEmptyState('Erro ao carregar logs: ' + error.message);
            showToast('error', 'Erro ao carregar logs');
        }
        
        showLoading(false);
    }
    
    function updateFileLogCounter(data) {
        const totalApi = document.getElementById('totalApiLogs');
        if (!totalApi) return;
        
        let count = 0;
        
        if (data && data.logs) {
            if (Array.isArray(data.logs)) {
                count = data.logs.length;
            } else if (typeof data.logs === 'object') {
                for (const key in data.logs) {
                    if (Array.isArray(data.logs[key])) {
                        count += data.logs[key].length;
                    }
                }
            }
        }
        
        totalApi.textContent = count;
    }
    
    function renderTableLogs(logs) {
        const container = document.getElementById('logsContainer');
        
        if (!logs || logs.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h3>Nenhum log encontrado</h3>
                    <p>Tente ajustar os filtros ou selecione outra categoria</p>
                </div>
            `;
            return;
        }
        
        let tableHtml = '';
        
        if (currentTab === 'audit') {
            tableHtml = `
                <div class="logs-table-container">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Usuário</th>
                                <th>Empresa</th>
                                <th>Ação</th>
                                <th>Descrição</th>
                                <th>IP</th>
                                <th width="80">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${logs.map(log => `
                                <tr>
                                    <td>${formatDateTime(log.created_at)}</td>
                                    <td>${escapeHtml(log.user_name || 'Sistema')}</td>
                                    <td>${escapeHtml(log.company_name || '-')}</td>
                                    <td><span class="log-level log-level-info">${escapeHtml(log.action)}</span></td>
                                    <td>${escapeHtml(log.description || '-')}</td>
                                    <td>${escapeHtml(log.ip_address || '-')}</td>
                                    <td>
                                        <button class="action-btn view" onclick='viewLogDetail(${JSON.stringify(log).replace(/'/g, "\\'")})' title="Ver detalhes">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } else if (currentTab === 'login') {
            tableHtml = `
                <div class="logs-table-container">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${logs.map(log => `
                                <tr>
                                    <td>${formatDateTime(log.created_at)}</td>
                                    <td>${escapeHtml(log.email || '-')}</td>
                                    <td>
                                        <span class="log-level ${log.status === 'success' ? 'log-level-success' : (log.status === 'logout' ? 'log-level-warning' : 'log-level-error')}">
                                            ${log.status === 'success' ? 'Sucesso' : (log.status === 'logout' ? 'Logout' : 'Falha')}
                                        </span>
                                    </td>
                                    <td>${escapeHtml(log.ip_address || '-')}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } else if (currentTab === 'activity') {
            tableHtml = `
                <div class="logs-table-container">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Usuário</th>
                                <th>Ação</th>
                                <th>Descrição</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${logs.map(log => `
                                <tr>
                                    <td>${formatDateTime(log.created_at)}</td>
                                    <td>${escapeHtml(log.user_name || 'Sistema')}</td>
                                    <td><span class="log-level log-level-info">${escapeHtml(log.action)}</span></td>
                                    <td>${escapeHtml(log.description || '-')}</td>
                                    <td>${escapeHtml(log.ip_address || '-')}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        container.innerHTML = tableHtml;
    }
    
    function renderFileLogs(data) {
        const container = document.getElementById('logsContainer');
        
        let logs = [];
        let fileInfo = '';
        let totalCount = 0;
        
        if (data && typeof data === 'object') {
            if (data.logs) {
                if (Array.isArray(data.logs)) {
                    logs = data.logs;
                    totalCount = logs.length;
                } else if (typeof data.logs === 'object') {
                    for (const key in data.logs) {
                        if (Array.isArray(data.logs[key])) {
                            logs = logs.concat(data.logs[key]);
                            totalCount += data.logs[key].length;
                        }
                    }
                }
            }
            
            if (data.file) {
                fileInfo = `Arquivo: ${data.file} | Tamanho: ${data.size || '0 KB'}`;
            } else if (data.files) {
                const filesStr = Object.keys(data.files).map(key => 
                    `${key}: ${data.files[key]?.size || '0 KB'}`
                ).join(' | ');
                fileInfo = filesStr;
            }
            
            if (data.type) {
                fileInfo += ` | Tipo: ${data.type}`;
            }
            if (data.limit) {
                fileInfo += ` | Limite: ${data.limit}`;
            }
        }
        
        const totalApi = document.getElementById('totalApiLogs');
        if (totalApi) {
            totalApi.textContent = totalCount;
        }
        
        if (logs.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h3>Nenhum log encontrado</h3>
                    <p>Os arquivos de log estão vazios</p>
                    ${fileInfo ? `<p style="margin-top: 8px; font-size: 12px; color: #6B778C;">${escapeHtml(fileInfo)}</p>` : ''}
                </div>
            `;
            return;
        }
        
        container.innerHTML = `
            <div class="logs-table-container">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th width="180">Timestamp</th>
                            <th>Mensagem</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${logs.slice(0, 100).map(log => {
                            if (typeof log === 'string') {
                                const match = log.match(/\[(.*?)\]/);
                                const timestamp = match ? match[1] : '';
                                const message = log.replace(/\[.*?\]/, '').trim();
                                return `
                                    <tr>
                                        <td>${escapeHtml(timestamp)}</td>
                                        <td class="log-entry">${escapeHtml(message)}</td>
                                    </tr>
                                `;
                            }
                            return `
                                <tr>
                                    <td>${formatDateTime(log.created_at || log.timestamp)}</td>
                                    <td class="log-entry">${escapeHtml(log.message || log.description || JSON.stringify(log))}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div class="file-info" style="padding: 12px; background: #F4F5F7; border-radius: 8px; flex: 1;">
                    <i class="fas fa-info-circle"></i> ${escapeHtml(fileInfo || 'Nenhuma informação disponível')}
                </div>
                <div style="font-size: 14px; color: #172B4D; font-weight: 600;">
                    Total: ${logs.length} registos
                </div>
            </div>
        `;
    }
    
    // ============================================
    // PAGINAÇÃO - CORRIGIDA
    // ============================================
    function updatePagination(pagination) {
        if (!pagination) return;
        
        const total = pagination.total || 0;
        const current = pagination.current_page || 1;
        const last = pagination.last_page || 1;
        
        totalRecords = total;
        totalPages = last;
        
        const paginationContainer = document.getElementById('pagination');
        const pageInfo = document.getElementById('pageInfo');
        
        // Atualizar informação de página
        if (pageInfo) {
            if (total > 0) {
                const start = ((current - 1) * perPage) + 1;
                const end = Math.min(current * perPage, total);
                pageInfo.textContent = `Mostrando ${start} - ${end} de ${total} registos`;
            } else {
                pageInfo.textContent = 'Nenhum registo encontrado';
            }
        }
        
        if (!paginationContainer) return;
        
        // Se não houver páginas, mostrar apenas uma mensagem
        if (last <= 1) {
            paginationContainer.innerHTML = `
                <span style="color: #6B778C; font-size: 14px;">Página única</span>
            `;
            return;
        }
        
        let paginationHtml = '';
        
        // Botão Primeira página
        paginationHtml += `
            <button onclick="goToPage(1)" ${current === 1 ? 'disabled' : ''} title="Primeira página">
                <i class="fas fa-chevron-double-left"></i>
            </button>
        `;
        
        // Botão Anterior
        paginationHtml += `
            <button onclick="goToPage(${current - 1})" ${current === 1 ? 'disabled' : ''} title="Página anterior">
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        // Números das páginas
        let startPage = Math.max(1, current - 2);
        let endPage = Math.min(last, current + 2);
        
        // Se estiver no início, mostrar mais à frente
        if (current <= 3) {
            endPage = Math.min(last, 5);
        }
        
        // Se estiver no fim, mostrar mais atrás
        if (current >= last - 2) {
            startPage = Math.max(1, last - 4);
        }
        
        // Primeira página se não estiver incluída
        if (startPage > 1) {
            paginationHtml += `<button onclick="goToPage(1)">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<button disabled>…</button>`;
            }
        }
        
        // Páginas do meio
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <button onclick="goToPage(${i})" class="${i === current ? 'active' : ''}">
                    ${i}
                </button>
            `;
        }
        
        // Última página se não estiver incluída
        if (endPage < last) {
            if (endPage < last - 1) {
                paginationHtml += `<button disabled>…</button>`;
            }
            paginationHtml += `<button onclick="goToPage(${last})">${last}</button>`;
        }
        
        // Botão Próxima
        paginationHtml += `
            <button onclick="goToPage(${current + 1})" ${current === last ? 'disabled' : ''} title="Próxima página">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
        
        // Botão Última página
        paginationHtml += `
            <button onclick="goToPage(${last})" ${current === last ? 'disabled' : ''} title="Última página">
                <i class="fas fa-chevron-double-right"></i>
            </button>
        `;
        
        paginationContainer.innerHTML = paginationHtml;
    }
    
    function showEmptyState(message) {
        const container = document.getElementById('logsContainer');
        if (container) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h3>Nenhum log encontrado</h3>
                    <p>${message || 'Tente ajustar os filtros ou selecione outra categoria'}</p>
                </div>
            `;
        }
        
        // Atualizar paginação vazia
        const pageInfo = document.getElementById('pageInfo');
        if (pageInfo) {
            pageInfo.textContent = 'Nenhum registo encontrado';
        }
        const paginationContainer = document.getElementById('pagination');
        if (paginationContainer) {
            paginationContainer.innerHTML = '';
        }
    }
    
    // ============================================
    // VIEW DETAIL
    // ============================================
    window.viewLogDetail = function(log) {
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('detailContent');
        
        if (modal && content) {
            content.innerHTML = `
                <div class="detail-group">
                    <div class="detail-label">Timestamp</div>
                    <div class="detail-value">${log.created_at || '-'}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Usuário</div>
                    <div class="detail-value">${log.user_name || 'Sistema'}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Empresa</div>
                    <div class="detail-value">${log.company_name || '-'}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Ação</div>
                    <div class="detail-value">${log.action || '-'}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Descrição</div>
                    <div class="detail-value">${log.description || '-'}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">IP Address</div>
                    <div class="detail-value">${log.ip_address || '-'}</div>
                </div>
            `;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };
    
    window.closeDetailModal = function() {
        const modal = document.getElementById('detailModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };
    
    // ============================================
    // FILTROS E PAGINAÇÃO
    // ============================================
    window.switchTab = function(tab) {
        currentTab = tab;
        currentPage = 1;
        
        document.querySelectorAll('.logs-tab').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Mapear nomes das tabs para IDs
        const tabMap = {
            'audit': 'tabAudit',
            'login': 'tabLogin',
            'activity': 'tabActivity',
            'api': 'tabApi',
            'errors': 'tabErrors'
        };
        
        const activeTab = document.getElementById(tabMap[tab]);
        if (activeTab) activeTab.classList.add('active');
        
        const filtersBar = document.getElementById('filtersBar');
        if (filtersBar) {
            if (tab === 'api' || tab === 'errors') {
                filtersBar.style.display = 'none';
            } else {
                filtersBar.style.display = 'flex';
            }
        }
        
        loadLogs();
        loadStats();
    };
    
    window.searchLogs = function() {
        currentPage = 1;
        loadLogs();
        showToast('info', 'Buscando logs...', 1500);
    };
    
    window.resetFilters = function() {
        const searchInput = document.getElementById('searchInput');
        const actionFilter = document.getElementById('actionFilter');
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        
        if (searchInput) searchInput.value = '';
        if (actionFilter) actionFilter.value = '';
        if (startDate) startDate.value = '';
        if (endDate) endDate.value = '';
        
        currentPage = 1;
        loadLogs();
        showToast('success', 'Filtros limpos!', 1500);
    };
    
    window.goToPage = function(page) {
        if (page < 1 || page > totalPages) return;
        if (page === currentPage) return;
        currentPage = page;
        loadLogs();
        // Scroll para o topo da tabela
        const container = document.getElementById('logsContainer');
        if (container) {
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };
    
   // ============================================
// EXPORT - CORRIGIDO (Suporte a todos os tipos)
// ============================================
window.exportLogs = async function() {
    showLoading(true);
    showToast('info', 'A preparar exportação...', 2000);
    
    try {
        // Mapear tipos suportados
        const validTypes = ['audit', 'login', 'activity', 'api', 'errors', 'security'];
        const exportType = validTypes.includes(currentTab) ? currentTab : 'audit';
        
        console.log('[EXPORT] Tipo:', exportType);
        
        const response = await apiRequest(`/admin/logs/export?type=${exportType}&format=csv`);
        
        console.log('[EXPORT] Resposta:', response);
        
        if (response && response.success) {
            if (response.data && response.data.download_url) {
                const downloadUrl = response.data.download_url;
                console.log('[EXPORT] URL:', downloadUrl);
                
                // Abrir em nova aba
                window.open(downloadUrl, '_blank');
                
                showToast('success', 
                    `Exportação iniciada! ${response.data.total || 0} registos exportados.`, 
                    4000
                );
            } else if (response.data && response.data.filename) {
                // Fallback
                const filename = response.data.filename;
                const downloadUrl = `/cardoxis/storage/exports/${filename}`;
                
                const link = document.createElement('a');
                link.href = downloadUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showToast('success', 
                    `Exportação iniciada! ${response.data.total || 0} registos exportados.`, 
                    4000
                );
            } else {
                showToast('error', 'Resposta inválida do servidor');
            }
        } else {
            showToast('error', response?.message || 'Erro ao exportar logs');
        }
    } catch (error) {
        console.error('[EXPORT] Erro:', error);
        showToast('error', 'Erro ao exportar logs: ' + error.message);
    }
    
    showLoading(false);
};  
    // ============================================
    // REFRESH
    // ============================================
    window.refreshLogs = function() {
        currentPage = 1;
        loadLogs();
        loadStats();
        showToast('success', 'Logs atualizados!', 2000);
    };
    
    // ============================================
    // FILTROS POR EVENTO
    // ============================================
    window.filterByAction = function() {
        currentPage = 1;
        loadLogs();
    };
    
    window.filterByDate = function() {
        currentPage = 1;
        loadLogs();
    };
    
    // ============================================
    // UTILITÁRIOS
    // ============================================
    function showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.toggle('active', show);
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
        const icon = type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : 'info-circle');
        toast.innerHTML = `
            <div class="toast-icon"><i class="fas fa-${icon}"></i></div>
            <div class="toast-content"><div class="toast-message">${escapeHtml(message)}</div></div>
            <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), duration);
    }
    
    function formatDateTime(dateString) {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-PT') + ' ' + date.toLocaleTimeString('pt-PT');
        } catch {
            return dateString;
        }
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // ============================================
    // INICIALIZAÇÃO
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        // Atualizar menu ativo
        document.querySelectorAll('.nav-item').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '/cardoxis/admin/logs') {
                link.classList.add('active');
            }
        });
        
        loadStats();
        loadLogs();
        
        // Event listener para Enter na busca
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchLogs();
                }
            });
        }
        
        // Atualizar a cada 30 segundos
        setInterval(() => {
            loadStats();
            if (currentTab !== 'api' && currentTab !== 'errors') {
                loadLogs();
            }
        }, 30000);
    });
    
    // Expor funções globalmente
    window.switchTab = switchTab;
    window.searchLogs = searchLogs;
    window.resetFilters = resetFilters;
    window.goToPage = goToPage;
    window.exportLogs = exportLogs;
    window.refreshLogs = refreshLogs;
    window.viewLogDetail = viewLogDetail;
    window.closeDetailModal = closeDetailModal;
    window.filterByAction = filterByAction;
    window.filterByDate = filterByDate;
    
})();