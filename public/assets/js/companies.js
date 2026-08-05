/**
 * CARDOXIS Admin Companies JavaScript RF 
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES

    const API_URL = 'http://localhost/cardoxis/api/v1';
    let currentPage = 1;
    let totalPages = 1;
    let perPage = 15;
    let currentFilters = {
        search: '',
        status: ''
    };
    let currentCompanyId = null;
    
    
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
    
    
    // REQUISIÇÕES API
    
    async function apiRequest(endpoint, options = {}) {
        const token = getToken();
        
        if (!token) {
            console.warn('[CARDOXIS] Sem token');
            return { success: false, message: 'Não autenticado' };
        }
        
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`,
                    ...options.headers
                }
            });
            
            if (response.status === 401) {
                localStorage.removeItem('auth_token');
                sessionStorage.removeItem('auth_token');
                window.location.href = '/cardoxis/login';
                return null;
            }
            
            const data = await response.json();
            console.log('[CARDOXIS] API Response:', endpoint, data);
            return data;
        } catch (error) {
            console.error('[API Error]', endpoint, error);
            return { success: false, message: 'Erro de ligação ao servidor' };
        }
    }
    
    
    // TOAST NOTIFICATIONS
    
    function showToast(type, message, duration = 4000) {
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
        
        setTimeout(() => {
            if (toast.parentElement) toast.remove();
        }, duration);
    }
    
    
    // LOADING
    
    function showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            if (show) overlay.classList.add('active');
            else overlay.classList.remove('active');
        }
    }
    
    
    // CARREGAR EMPRESAS
    
    async function loadCompanies() {
        console.log('[CARDOXIS] Carregando empresas...');
        showLoading(true);
        
        try {
            const params = new URLSearchParams({
                page: currentPage,
                limit: perPage,
                search: currentFilters.search,
                status: currentFilters.status
            });
            
            const response = await apiRequest(`/admin/companies?${params}`);
            console.log('[CARDOXIS] Resposta:', response);
            
            if (response && response.success) {
                // CORREÇÃO: Verificar onde estão os dados
                let companies = [];
                let pagination = {};
                
                if (response.data && Array.isArray(response.data)) {
                    companies = response.data;
                    pagination = response.pagination || {};
                } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
                    companies = response.data.data;
                    pagination = response.data.pagination || {};
                } else if (Array.isArray(response.data)) {
                    companies = response.data;
                } else {
                    companies = [];
                    pagination = {};
                }
                
                console.log('[CARDOXIS] Empresas carregadas:', companies.length);
                
                renderCompaniesTable(companies);
                updatePagination(pagination, companies.length);
                updateStats(companies);
                updateFiltersBadge();
            } else {
                console.warn('[CARDOXIS] Resposta sem sucesso ou sem dados:', response);
                renderCompaniesTable([]);
                updateStats([]);
                showToast('info', 'Nenhuma empresa encontrada');
            }
        } catch (error) {
            console.error('[CARDOXIS] Erro:', error);
            renderCompaniesTable([]);
            updateStats([]);
            showToast('error', 'Erro ao carregar empresas: ' + error.message);
        }
        
        showLoading(false);
    }
    
    function renderCompaniesTable(companies) {
        const tbody = document.getElementById('companiesTableBody');
        if (!tbody) return;
        
        // Garantir que é um array
        if (!Array.isArray(companies)) {
            console.warn('[CARDOXIS] companies não é um array:', companies);
            companies = [];
        }
        
        if (companies.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 60px;">
                        <div class="empty-state">
                            <i class="fas fa-building" style="font-size: 48px; color: #6B778C; margin-bottom: 16px; display: block;"></i>
                            <h3 style="color: #172B4D; margin-bottom: 8px;">Nenhuma empresa encontrada</h3>
                            <p style="color: #6B778C; margin-bottom: 20px;">Clique em "Nova Empresa" para adicionar</p>
                            <button class="btn-primary" onclick="openCreateModal()">
                                <i class="fas fa-plus"></i> Nova Empresa
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = companies.map(company => `
            <tr>
                <td>
                    <div class="company-name" style="font-weight: 600; color: #172B4D;">${escapeHtml(company.name || '-')}</div>
                    <div class="company-document" style="font-size: 0.8rem; color: #6B778C;">NIF: ${escapeHtml(company.document || '-')}</div>
                </td>
                <td>${escapeHtml(company.email || '-')}</td>
                <td>${escapeHtml(company.phone || '-')}</td>
                <td>${formatDate(company.created_at)}</td>
                <td>${getStatusBadge(company.status)}</td>
                <td>
                    <div class="action-buttons" style="display: flex; gap: 4px; flex-wrap: wrap;">
                        <button class="action-btn view" onclick="viewCompany(${company.id})" title="Ver detalhes" style="background: #EBECF0; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; color: #0052CC;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit" onclick="editCompany(${company.id})" title="Editar" style="background: #EBECF0; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; color: #00875A;">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${company.status === 'active' ? `
                            <button class="action-btn suspend" onclick="suspendCompany(${company.id})" title="Suspender" style="background: #EBECF0; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; color: #FF8B00;">
                                <i class="fas fa-pause-circle"></i>
                            </button>
                        ` : `
                            <button class="action-btn activate" onclick="activateCompany(${company.id})" title="Activar" style="background: #EBECF0; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; color: #00875A;">
                                <i class="fas fa-play-circle"></i>
                            </button>
                        `}
                        <button class="action-btn delete" onclick="confirmDeleteCompany(${company.id}, '${escapeHtml(company.name)}')" title="Eliminar" style="background: #EBECF0; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; color: #DE350B;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
        
        console.log('[CARDOXIS] Tabela renderizada com', companies.length, 'empresas');
    }
    
    function updateStats(companies) {
        // Garantir que é um array
        if (!Array.isArray(companies)) {
            companies = [];
        }
        
        const total = companies.length;
        const active = companies.filter(c => c.status === 'active').length;
        const inactive = companies.filter(c => c.status === 'inactive').length;
        const suspended = companies.filter(c => c.status === 'suspended').length;
        
        const totalEl = document.getElementById('totalCompanies');
        const activeEl = document.getElementById('activeCompanies');
        const inactiveEl = document.getElementById('inactiveCompanies');
        const suspendedEl = document.getElementById('suspendedCompanies');
        
        if (totalEl) totalEl.textContent = total;
        if (activeEl) activeEl.textContent = active;
        if (inactiveEl) inactiveEl.textContent = inactive;
        if (suspendedEl) suspendedEl.textContent = suspended;
    }
    
    function updateFiltersBadge() {
        const hasFilters = currentFilters.search || currentFilters.status;
        const filterBadge = document.getElementById('filterBadge');
        if (filterBadge) {
            if (hasFilters) {
                filterBadge.style.display = 'inline-block';
                filterBadge.textContent = 'Filtros activos';
            } else {
                filterBadge.style.display = 'none';
            }
        }
    }
    
    function getStatusBadge(status) {
        const badges = {
            'active': '<span class="status-badge status-active" style="background: #E3FCEF; color: #00875A; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-check-circle"></i> Activa</span>',
            'inactive': '<span class="status-badge status-inactive" style="background: #FFEBE6; color: #DE350B; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-minus-circle"></i> Inactiva</span>',
            'suspended': '<span class="status-badge status-suspended" style="background: #FFF3E0; color: #FF8B00; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-pause-circle"></i> Suspensa</span>'
        };
        return badges[status] || badges.inactive;
    }
    
    function updatePagination(pagination, totalItems) {
        const total = pagination.total || totalItems || 0;
        totalPages = pagination.last_page || Math.ceil(total / perPage) || 1;
        currentPage = pagination.current_page || currentPage || 1;
        
        const pageInfo = document.getElementById('pageInfo');
        if (pageInfo && total > 0) {
            const start = ((currentPage - 1) * perPage) + 1;
            const end = Math.min(currentPage * perPage, total);
            pageInfo.textContent = `Mostrando ${start} - ${end} de ${total} empresas`;
        } else if (pageInfo) {
            pageInfo.textContent = 'Nenhuma empresa encontrada';
        }
        
        const paginationContainer = document.getElementById('pagination');
        if (!paginationContainer) return;
        
        let html = `
            <div class="pagination-controls" style="display: flex; gap: 4px; align-items: center;">
                <button onclick="goToPage(1)" ${currentPage === 1 ? 'disabled' : ''} style="padding: 6px 12px; border: 1px solid #DFE1E6; border-radius: 6px; background: white; cursor: pointer; ${currentPage === 1 ? 'opacity: 0.5; cursor: not-allowed;' : ''}">
                    <i class="fas fa-chevron-double-left"></i>
                </button>
                <button onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} style="padding: 6px 12px; border: 1px solid #DFE1E6; border-radius: 6px; background: white; cursor: pointer; ${currentPage === 1 ? 'opacity: 0.5; cursor: not-allowed;' : ''}">
                    <i class="fas fa-chevron-left"></i>
                </button>
        `;
        
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<button onclick="goToPage(${i})" class="${i === currentPage ? 'active' : ''}" style="padding: 6px 12px; border: 1px solid ${i === currentPage ? '#0052CC' : '#DFE1E6'}; border-radius: 6px; background: ${i === currentPage ? '#0052CC' : 'white'}; color: ${i === currentPage ? 'white' : '#172B4D'}; cursor: pointer;">${i}</button>`;
        }
        
        html += `
                <button onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} style="padding: 6px 12px; border: 1px solid #DFE1E6; border-radius: 6px; background: white; cursor: pointer; ${currentPage === totalPages ? 'opacity: 0.5; cursor: not-allowed;' : ''}">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button onclick="goToPage(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''} style="padding: 6px 12px; border: 1px solid #DFE1E6; border-radius: 6px; background: white; cursor: pointer; ${currentPage === totalPages ? 'opacity: 0.5; cursor: not-allowed;' : ''}">
                    <i class="fas fa-chevron-double-right"></i>
                </button>
            </div>
        `;
        
        paginationContainer.innerHTML = html;
    }
    
    
    // CRUD OPERATIONS
    
    window.openCreateModal = function() {
        document.getElementById('modalTitle').textContent = 'Nova Empresa';
        document.getElementById('modalIcon').className = 'fas fa-building';
        document.getElementById('modalIcon').style.color = '#0052CC';
        document.getElementById('companyForm').reset();
        document.getElementById('companyId').value = '';
        document.getElementById('companyStatus').value = 'active';
        document.getElementById('companyModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId || 'companyModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };
    
    window.saveCompany = async function() {
        const id = document.getElementById('companyId').value;
        const formData = {
            name: document.getElementById('companyName')?.value.trim() || '',
            document: document.getElementById('companyDocument')?.value.trim() || '',
            email: document.getElementById('companyEmail')?.value.trim() || '',
            phone: document.getElementById('companyPhone')?.value.trim() || '',
            address: document.getElementById('companyAddress')?.value.trim() || '',
            city: document.getElementById('companyCity')?.value.trim() || '',
            state: document.getElementById('companyState')?.value.trim() || '',
            zip_code: document.getElementById('companyZipCode')?.value.trim() || '',
            status: document.getElementById('companyStatus')?.value || 'active'
        };
        
        if (!formData.name) {
            showToast('error', 'O nome da empresa é obrigatório');
            document.getElementById('companyName').focus();
            return;
        }
        
        if (!formData.document) {
            showToast('error', 'O NIF é obrigatório');
            document.getElementById('companyDocument').focus();
            return;
        }
        
        const cleanDocument = formData.document.replace(/\D/g, '');
        if (cleanDocument.length !== 9) {
            showToast('error', 'O NIF deve ter 9 dígitos');
            return;
        }
        
        showLoading(true);
        
        let response;
        if (id) {
            response = await apiRequest(`/admin/companies/${id}`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });
        } else {
            response = await apiRequest('/admin/companies', {
                method: 'POST',
                body: JSON.stringify(formData)
            });
        }
        
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', id ? 'Empresa actualizada com sucesso!' : 'Empresa criada com sucesso!');
            closeModal('companyModal');
            loadCompanies();
        } else {
            showToast('error', response?.message || 'Erro ao guardar empresa');
        }
    };
    
    window.viewCompany = async function(id) {
        showLoading(true);
        const response = await apiRequest(`/admin/companies/${id}`);
        showLoading(false);
        
        if (response && response.success) {
            const c = response.data;
            document.getElementById('viewCompanyName').innerHTML = `<strong>${escapeHtml(c.name)}</strong>`;
            document.getElementById('viewCompanyDocument').textContent = c.document || '-';
            document.getElementById('viewCompanyEmail').textContent = c.email || '-';
            document.getElementById('viewCompanyPhone').textContent = c.phone || '-';
            document.getElementById('viewCompanyAddress').textContent = c.address || '-';
            document.getElementById('viewCompanyCity').textContent = c.city || '-';
            document.getElementById('viewCompanyState').textContent = c.state || '-';
            document.getElementById('viewCompanyZipCode').textContent = c.zip_code || '-';
            document.getElementById('viewCompanyStatus').innerHTML = getStatusBadge(c.status);
            document.getElementById('viewCompanyCreated').textContent = formatDate(c.created_at);
            document.getElementById('viewCompanyUsers').textContent = c.total_users || 0;
            document.getElementById('viewCompanyVehicles').textContent = c.total_vehicles || 0;
            
            document.getElementById('viewModal').setAttribute('data-company-id', id);
            document.getElementById('viewModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            showToast('error', 'Erro ao carregar detalhes da empresa');
        }
    };
    
    window.editCompany = async function(id) {
        showLoading(true);
        const response = await apiRequest(`/admin/companies/${id}`);
        showLoading(false);
        
        if (response && response.success) {
            const company = response.data;
            document.getElementById('modalTitle').textContent = 'Editar Empresa';
            document.getElementById('modalIcon').className = 'fas fa-edit';
            document.getElementById('modalIcon').style.color = '#00875A';
            document.getElementById('companyId').value = company.id;
            document.getElementById('companyName').value = company.name || '';
            document.getElementById('companyDocument').value = company.document || '';
            document.getElementById('companyEmail').value = company.email || '';
            document.getElementById('companyPhone').value = company.phone || '';
            document.getElementById('companyAddress').value = company.address || '';
            document.getElementById('companyCity').value = company.city || '';
            document.getElementById('companyState').value = company.state || '';
            document.getElementById('companyZipCode').value = company.zip_code || '';
            document.getElementById('companyStatus').value = company.status || 'active';
            document.getElementById('companyModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            showToast('error', 'Erro ao carregar dados da empresa');
        }
    };
    
    window.confirmDeleteCompany = function(id, name) {
        currentCompanyId = id;
        document.getElementById('confirmDeleteTitle').textContent = 'Eliminar Empresa';
        document.getElementById('confirmDeleteMessage').innerHTML = `Tem certeza que deseja <strong>eliminar permanentemente</strong> a empresa "<strong>${escapeHtml(name)}</strong>"?<br><br><span style="color: #DE350B; font-size: 0.9rem;"><i class="fas fa-exclamation-triangle"></i> Esta acção não pode ser desfeita e todos os dados associados serão removidos.</span>`;
        document.getElementById('confirmDeleteModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    
    window.executeDeleteCompany = async function() {
        if (!currentCompanyId) {
            showToast('error', 'ID da empresa não encontrado');
            return;
        }
        
        closeModal('confirmDeleteModal');
        showLoading(true);
        
        try {
            const response = await apiRequest(`/admin/companies/${currentCompanyId}`, { method: 'DELETE' });
            showLoading(false);
            
            if (response && response.success) {
                showToast('success', 'Empresa eliminada com sucesso!');
                loadCompanies();
            } else {
                showToast('error', response?.message || 'Erro ao eliminar empresa');
            }
        } catch (error) {
            showLoading(false);
            showToast('error', 'Erro ao eliminar empresa: ' + error.message);
        }
        currentCompanyId = null;
    };
    
    window.suspendCompany = async function(id) {
        if (!confirm('Tem certeza que deseja suspender esta empresa?')) return;
        
        showLoading(true);
        const response = await apiRequest(`/admin/companies/${id}/suspend`, { method: 'POST' });
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', 'Empresa suspensa com sucesso!');
            loadCompanies();
        } else {
            showToast('error', response?.message || 'Erro ao suspender empresa');
        }
    };
    
    window.activateCompany = async function(id) {
        if (!confirm('Tem certeza que deseja activar esta empresa?')) return;
        
        showLoading(true);
        const response = await apiRequest(`/admin/companies/${id}/activate`, { method: 'POST' });
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', 'Empresa activada com sucesso!');
            loadCompanies();
        } else {
            showToast('error', response?.message || 'Erro ao activar empresa');
        }
    };
    
    
    // FILTROS E PAGINAÇÃO
    
    window.searchCompanies = function() {
        currentFilters.search = document.getElementById('searchInput')?.value || '';
        currentPage = 1;
        loadCompanies();
    };
    
    window.filterByStatus = function() {
        currentFilters.status = document.getElementById('statusFilter')?.value || '';
        currentPage = 1;
        loadCompanies();
    };
    
    window.resetFilters = function() {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = '';
        currentFilters = { search: '', status: '' };
        currentPage = 1;
        loadCompanies();
        showToast('info', 'Filtros limpos');
    };
    
    window.goToPage = function(page) {
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        loadCompanies();
    };
    
    // EXPORTAR EMPRESAS (PROFISSIONAL)
    
    window.exportCompanies = async function() {
        const token = getToken();
        if (!token) {
            showToast('error', 'Sessão expirada');
            return;
        }
        
        showToast('info', 'A preparar exportação...', 2000);
        showLoading(true);
        
        try {
            // Construir URL com filtros atuais
            const params = new URLSearchParams();
            if (currentFilters.search) params.append('search', currentFilters.search);
            if (currentFilters.status) params.append('status', currentFilters.status);
            
            const queryString = params.toString();
            const url = queryString ? `/admin/companies/export?${queryString}` : '/admin/companies/export';
            
            const response = await fetch(`${API_URL}${url}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            
            if (response.status === 401) {
                localStorage.removeItem('auth_token');
                sessionStorage.removeItem('auth_token');
                window.location.href = '/cardoxis/login';
                return;
            }
            
            const data = await response.json();
            
            if (data.success && data.data && data.data.download_url) {
                // Abrir o download em nova aba
                window.open(data.data.download_url, '_blank');
                showToast('success', `Exportação iniciada! ${data.data.total || 0} empresas exportadas.`);
            } else {
                showToast('error', data.message || 'Erro ao exportar empresas');
            }
        } catch (error) {
            console.error('[CARDOXIS] Export error:', error);
            showToast('error', 'Erro ao exportar empresas: ' + error.message);
        }
        
        showLoading(false);
    };
    
    
    // UTILITÁRIOS
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-PT');
        } catch (e) {
            return '-';
        }
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    
    // ATUALIZAR MENU ATIVO
    
    function updateActiveMenu() {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-item').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '/cardoxis/admin/companies') {
                link.classList.add('active');
            }
        });
    }
    
    
    // INICIALIZAÇÃO
    
    document.addEventListener('DOMContentLoaded', () => {
        console.log('[CARDOXIS] Inicializando página de empresas v8.0');
        
        if (!checkAuth()) return;
        
        updateActiveMenu();
        loadCompanies();
        
        // Enter key search
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') searchCompanies();
            });
        }
        
        // Formatar NIF
        const nifInput = document.getElementById('companyDocument');
        if (nifInput) {
            nifInput.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 9) value = value.substring(0, 9);
                this.value = value;
            });
        }
        
        // Fechar modais ao clicar fora
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    });
    
    // Expor funções globalmente
    window.loadCompanies = loadCompanies;
    window.goToPage = goToPage;
    window.viewCompany = viewCompany;
    window.editCompany = editCompany;
    window.suspendCompany = suspendCompany;
    window.activateCompany = activateCompany;
    window.confirmDeleteCompany = confirmDeleteCompany;
    window.executeDeleteCompany = executeDeleteCompany;
    window.searchCompanies = searchCompanies;
    window.filterByStatus = filterByStatus;
    window.resetFilters = resetFilters;
    window.exportCompanies = exportCompanies;
    window.openCreateModal = openCreateModal;
    window.closeModal = closeModal;
    window.saveCompany = saveCompany;
})();