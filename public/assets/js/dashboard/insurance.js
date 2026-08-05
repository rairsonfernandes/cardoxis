/**
 * CARDOXIS - Insurance RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES GLOBAIS

    const CONFIG = {
        API_URL: window.CARDOXIS?.API_URL || window.location.origin + '/cardoxis/api/v1',
        ITEMS_PER_PAGE: 15,
        REFRESH_INTERVAL: 60000,
        MAX_FILE_SIZE: 5 * 1024 * 1024 // 5MB
    };
    
    let refreshInterval = null;
    let currentInsuranceId = null;
    
    let state = {
        insurances: [],
        vehicles: [],
        currentPage: 1,
        currentStatus: 'all',
        currentType: 'all',
        totalPages: 1,
        isLoading: false,
        total: 0
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
    
    // DOWNLOAD DE DOCUMENTO

    async function downloadDocument(insuranceId) {
        try {
            const token = getToken();
            if (!token) {
                showToast('error', 'Sessão expirada. Faça login novamente.');
                return;
            }
            
            showToast('info', 'A preparar download...');
            
            // Usar fetch com blob para download
            const response = await fetch(`${CONFIG.API_URL}/insurances/${insuranceId}/document`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            if (response.status === 401) {
                handleUnauthorized();
                return;
            }
            
            if (response.status === 404) {
                showToast('error', 'Documento não encontrado');
                return;
            }
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Obter o blob do arquivo
            const blob = await response.blob();
            
            // Criar URL para download
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            
            // Tentar obter o nome do arquivo do header Content-Disposition
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = `documento_seguro_${insuranceId}.pdf`;
            
            if (contentDisposition) {
                const match = contentDisposition.match(/filename="(.+)"/);
                if (match) {
                    filename = match[1];
                }
            }
            
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
            
            showToast('success', 'Download iniciado com sucesso!');
            
        } catch (error) {
            console.error('[Download Error]', error);
            showToast('error', 'Erro ao fazer download: ' + error.message);
        }
    }
    
    // UTILITÁRIOS

    function formatNumber(num) {
        if (!num) return '0';
        return parseFloat(num).toLocaleString('pt-PT');
    }
    
    function formatMoney(value) {
        if (!value || value == 0) return '€ 0,00';
        return `€ ${parseFloat(value).toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        return date.toLocaleDateString('pt-PT');
    }
    
    function formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        return date.toLocaleDateString('pt-PT') + ' ' + date.toLocaleTimeString('pt-PT');
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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
        
        let icon = 'info-circle';
        if (type === 'success') icon = 'check-circle';
        if (type === 'error') icon = 'exclamation-circle';
        if (type === 'warning') icon = 'exclamation-triangle';
        
        toast.innerHTML = `
            <i class="fas fa-${icon}"></i>
            <span>${escapeHtml(message)}</span>
            <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }
    
    /**
     * Abre um modal específico
     */
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Disparar evento para animações
            modal.dispatchEvent(new CustomEvent('modal:opened', { 
                detail: { modalId: modalId } 
            }));
        } else {
            console.warn(`[Modal] Modal "${modalId}" não encontrado`);
        }
    }
    
    /**
     * Fecha um modal específico
     */
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            
            // Aguardar animação de saída antes de ocultar
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            
            // Verificar se há outros modais abertos
            const openModals = document.querySelectorAll('.modal.active');
            if (openModals.length === 0) {
                document.body.style.overflow = '';
            }
            
            // Disparar evento para animações
            modal.dispatchEvent(new CustomEvent('modal:closed', { 
                detail: { modalId: modalId } 
            }));
        } else {
            console.warn(`[Modal] Modal "${modalId}" não encontrado`);
        }
    }
    
    /**
     * Fecha todos os modais abertos
     */
    function closeAllModals() {
        const openModals = document.querySelectorAll('.modal.active');
        openModals.forEach(modal => {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        });
        document.body.style.overflow = '';
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
    
    // TIPOS DE SEGURO

    function getTypeText(type) {
        const texts = {
            'comprehensive': 'Danos Próprios',
            'liability': 'Responsabilidade Civil',
            'partial': 'Parcial',
            'fleet': 'Frota'
        };
        return texts[type] || type;
    }
    
    function getTypeClass(type) {
        const classes = {
            'comprehensive': 'comprehensive',
            'liability': 'liability',
            'partial': 'partial',
            'fleet': 'fleet'
        };
        return classes[type] || 'comprehensive';
    }
    
    function getStatusText(status) {
        const texts = {
            'active': 'Ativo',
            'expired': 'Expirado',
            'expiring_soon': 'A Vencer'
        };
        return texts[status] || status;
    }
    
    function getStatusClass(status) {
        const classes = {
            'active': 'active',
            'expired': 'expired',
            'expiring_soon': 'expiring_soon',
            'valid': 'valid'
        };
        return classes[status] || 'valid';
    }
    
    // CARREGAMENTO DE DADOS

    async function loadVehicles() {
        try {
            const result = await apiRequest('/vehicles?limit=100');
            if (result && result.success && result.data) {
                state.vehicles = result.data;
                updateVehicleSelects();
            }
        } catch (error) {
            console.error('[Load Vehicles Error]', error);
        }
    }
    
    function updateVehicleSelects() {
        const addSelect = document.getElementById('addVehicleId');
        const editSelect = document.getElementById('editVehicleId');
        
        if (!addSelect) return;
        
        const options = state.vehicles.map(vehicle => 
            `<option value="${vehicle.id}">${escapeHtml(vehicle.brand)} ${escapeHtml(vehicle.model)} - ${escapeHtml(vehicle.plate)}</option>`
        ).join('');
        
        if (addSelect) addSelect.innerHTML = '<option value="">Selecione um veículo</option>' + options;
        if (editSelect) editSelect.innerHTML = '<option value="">Selecione um veículo</option>' + options;
    }
    
    async function loadStats() {
        try {
            const result = await apiRequest('/insurances/stats');
            
            if (result && result.success && result.data) {
                const stats = result.data;
                
                updateElementText('totalPolicies', formatNumber(stats.total_policies || 0));
                updateElementText('activePolicies', formatNumber(stats.active_policies || 0));
                updateElementText('expiringSoon', formatNumber(stats.expiring_soon || 0));
                updateElementText('totalPremium', formatMoney(stats.total_premium || 0));
            }
        } catch (error) {
            console.error('[Load Stats Error]', error);
        }
    }
    
    async function loadInsurances() {
        if (state.isLoading) return;
        
        state.isLoading = true;
        showLoading();
        
        try {
            let url = `/insurances?limit=${CONFIG.ITEMS_PER_PAGE}&page=${state.currentPage}`;
            
            if (state.currentStatus !== 'all') {
                url += `&status=${state.currentStatus}`;
            }
            
            if (state.currentType !== 'all') {
                url += `&type=${state.currentType}`;
            }
            
            const result = await apiRequest(url);
            
            if (result && result.success && result.data) {
                if (Array.isArray(result.data)) {
                    state.insurances = result.data;
                } else if (result.data.data && Array.isArray(result.data.data)) {
                    state.insurances = result.data.data;
                } else {
                    state.insurances = [];
                }
                
                state.totalPages = result.data.pagination?.last_page || 1;
                state.total = result.data.pagination?.total || 0;
                
                renderTable();
                renderPagination();
                updateLastUpdateTime();
            } else {
                state.insurances = [];
                renderEmptyState();
            }
        } catch (error) {
            console.error('[Load Insurances Error]', error);
            state.insurances = [];
            renderEmptyState();
        } finally {
            state.isLoading = false;
        }
    }
    
    function showLoading() {
        const container = document.getElementById('insuranceTableBody');
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 60px;">
                        <div class="spinner"></div>
                        <p style="margin-top: 16px;">Carregando seguros...</p>
                    </td>
                </tr>
            `;
        }
    }
    
    function renderTable() {
        const container = document.getElementById('insuranceTableBody');
        if (!container) return;
        
        if (!Array.isArray(state.insurances)) {
            state.insurances = [];
        }
        
        if (state.insurances.length === 0) {
            renderEmptyState();
            return;
        }
        
        container.innerHTML = state.insurances.map(insurance => {
            const daysRemaining = parseInt(insurance.days_remaining) || 0;
            const statusClass = getStatusClass(insurance.validity_status || 'valid');
            const statusText = getStatusText(insurance.validity_status || 'valid');
            
            let statusDisplay = statusText;
            if (insurance.validity_status === 'expiring_soon') {
                statusDisplay = `${daysRemaining} dias`;
            }
            
            return `
                <tr onclick="viewInsuranceDetails(${insurance.id})">
                    <td>
                        <div class="vehicle-cell">
                            <span class="vehicle-name">${escapeHtml(insurance.vehicle_brand || '')} ${escapeHtml(insurance.vehicle_model || '')}</span>
                            <span class="vehicle-plate">${escapeHtml(insurance.vehicle_plate || '-')}</span>
                        </div>
                    </td>
                    <td>${escapeHtml(insurance.insurance_company || '-')}</td>
                    <td><strong>${escapeHtml(insurance.policy_number || '-')}</strong></td>
                    <td><span class="type-badge ${getTypeClass(insurance.type)}">${getTypeText(insurance.type)}</span></td>
                    <td>${formatDate(insurance.start_date)}</td>
                    <td>${formatDate(insurance.end_date)}</td>
                    <td class="cost-cell">${formatMoney(insurance.premium_amount)}</td>
                    <td>
                        <span class="status-badge ${statusClass}">
                            <i class="fas ${insurance.validity_status === 'expired' ? 'fa-times-circle' : insurance.validity_status === 'expiring_soon' ? 'fa-clock' : 'fa-check-circle'}"></i>
                            ${statusDisplay}
                        </span>
                    </td>
                    <td class="action-buttons" onclick="event.stopPropagation()">
                        ${insurance.document_file ? `
                            <button class="action-btn" onclick="downloadDocument(${insurance.id})" title="Baixar documento">
                                <i class="fas fa-file-pdf" style="color:var(--danger);"></i>
                            </button>
                        ` : ''}
                        <button class="action-btn" onclick="viewInsuranceDetails(${insurance.id})" title="Ver detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn" onclick="editInsurance(${insurance.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn danger" onclick="confirmDeleteInsurance(${insurance.id})" title="Eliminar">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    function renderEmptyState() {
        const container = document.getElementById('insuranceTableBody');
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 60px;">
                        <div class="empty-state">
                            <i class="fas fa-shield-alt"></i>
                            <h3>Nenhum seguro encontrado</h3>
                            <p>Comece adicionando o primeiro seguro da sua frota</p>
                            <button class="btn-primary" onclick="openAddInsuranceModal()">
                                <i class="fas fa-plus"></i> Adicionar Seguro
                            </button>
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
        
        let paginationHtml = `
            <button class="pagination-btn" onclick="changePage(1)" ${state.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-angle-double-left"></i>
            </button>
            <button class="pagination-btn" onclick="changePage(${state.currentPage - 1})" ${state.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        if (startPage > 1) {
            paginationHtml += `<span class="pagination-ellipsis">...</span>`;
        }
        
        pages.forEach(page => {
            paginationHtml += `
                <button class="pagination-btn ${page === state.currentPage ? 'active' : ''}" onclick="changePage(${page})">
                    ${page}
                </button>
            `;
        });
        
        if (endPage < state.totalPages) {
            paginationHtml += `<span class="pagination-ellipsis">...</span>`;
        }
        
        paginationHtml += `
            <button class="pagination-btn" onclick="changePage(${state.currentPage + 1})" ${state.currentPage === state.totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
            <button class="pagination-btn" onclick="changePage(${state.totalPages})" ${state.currentPage === state.totalPages ? 'disabled' : ''}>
                <i class="fas fa-angle-double-right"></i>
            </button>
        `;
        
        container.innerHTML = paginationHtml;
    }
    
    // FILTROS

    window.filterInsurance = function(status) {
        state.currentStatus = status;
        state.currentPage = 1;
        
        document.querySelectorAll('.filter-btn[data-status]').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.status === status) {
                btn.classList.add('active');
            }
        });
        
        loadInsurances();
    };
    
    window.filterInsuranceByType = function(type) {
        state.currentType = type;
        state.currentPage = 1;
        
        document.querySelectorAll('.filter-btn[data-type]').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.type === type) {
                btn.classList.add('active');
            }
        });
        
        loadInsurances();
    };
    
    window.changePage = function(page) {
        if (page < 1 || page > state.totalPages || page === state.currentPage) return;
        state.currentPage = page;
        loadInsurances();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    // FUNÇÃO DE DOWNLOAD GLOBAL

    window.downloadDocument = downloadDocument;
    
    // FUNÇÕES DE MODAL GLOBAIS

    window.openModal = openModal;
    window.closeModal = closeModal;
    window.closeAllModals = closeAllModals;
    
    // CRUD OPERAÇÕES

    window.openAddInsuranceModal = function() {
        resetAddForm();
        openModal('addInsuranceModal');
    };
    
    function resetAddForm() {
        const form = document.getElementById('addInsuranceForm');
        if (form) form.reset();
        document.getElementById('addStartDate').value = new Date().toISOString().split('T')[0];
        const endDate = new Date();
        endDate.setFullYear(endDate.getFullYear() + 1);
        document.getElementById('addEndDate').value = endDate.toISOString().split('T')[0];
    }
    
    async function createInsurance(formData) {
        showToast('info', 'A adicionar seguro...');
        
        const response = await apiRequest('/insurances', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        if (response && response.success) {
            const insuranceId = response.data.id;
            
            const documentFile = document.getElementById('addDocument').files[0];
            if (documentFile && insuranceId) {
                await uploadDocument(insuranceId, documentFile);
            }
            
            showToast('success', 'Seguro adicionado com sucesso!');
            closeModal('addInsuranceModal');
            await loadStats();
            await loadInsurances();
        } else {
            showToast('error', response?.message || 'Erro ao adicionar seguro');
        }
    }
    
    async function updateInsurance(id, formData) {
        showToast('info', 'A atualizar seguro...');
        
        const response = await apiRequest(`/insurances/${id}`, {
            method: 'PUT',
            body: JSON.stringify(formData)
        });
        
        if (response && response.success) {
            const documentFile = document.getElementById('editDocument').files[0];
            if (documentFile) {
                await uploadDocument(id, documentFile);
            }
            
            showToast('success', 'Seguro atualizado com sucesso!');
            closeModal('editInsuranceModal');
            await loadStats();
            await loadInsurances();
        } else {
            showToast('error', response?.message || 'Erro ao atualizar seguro');
        }
    }
    
    async function uploadDocument(insuranceId, file) {
        const formData = new FormData();
        formData.append('document', file);
        
        try {
            const token = getToken();
            const response = await fetch(`${CONFIG.API_URL}/insurances/${insuranceId}/document`, {
                method: 'POST',
                headers: {
                    'Authorization': token ? `Bearer ${token}` : ''
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data && data.success) {
                showToast('success', 'Documento anexado com sucesso!');
                return data.data;
            } else {
                showToast('error', data?.message || 'Erro ao anexar documento');
                return null;
            }
        } catch (error) {
            console.error('[Upload Error]', error);
            showToast('error', 'Erro ao anexar documento');
            return null;
        }
    }
    
    // VISUALIZAR DETALHES

    window.viewInsuranceDetails = async function(id) {
        try {
            const result = await apiRequest(`/insurances/${id}`);
            
            if (result && result.success && result.data) {
                const insurance = result.data;
                currentInsuranceId = id;
                
                const modalContent = document.getElementById('detailModalContent');
                if (modalContent) {
                    const daysRemaining = parseInt(insurance.days_remaining) || 0;
                    const statusClass = getStatusClass(insurance.validity_status || 'valid');
                    const statusText = getStatusText(insurance.validity_status || 'valid');
                    
                    modalContent.innerHTML = `
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label><i class="fas fa-car"></i> Veículo</label>
                                <div class="value">${escapeHtml(insurance.vehicle_brand || '')} ${escapeHtml(insurance.vehicle_model || '')} (${escapeHtml(insurance.vehicle_plate || '-')})</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-building"></i> Seguradora</label>
                                <div class="value">${escapeHtml(insurance.insurance_company || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-hashtag"></i> Número da Apólice</label>
                                <div class="value"><strong>${escapeHtml(insurance.policy_number || '-')}</strong></div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-tag"></i> Tipo</label>
                                <div class="value"><span class="type-badge ${getTypeClass(insurance.type)}">${getTypeText(insurance.type)}</span></div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-calendar"></i> Data Início</label>
                                <div class="value">${formatDate(insurance.start_date)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-calendar-alt"></i> Data Fim</label>
                                <div class="value">${formatDate(insurance.end_date)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-euro-sign"></i> Prémio</label>
                                <div class="value cost-cell">${formatMoney(insurance.premium_amount)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-shield-alt"></i> Franquia</label>
                                <div class="value">${formatMoney(insurance.deductible || 0)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-chart-line"></i> Status</label>
                                <div class="value"><span class="status-badge ${statusClass}">${statusText}</span></div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-clock"></i> Dias Restantes</label>
                                <div class="value">${daysRemaining > 0 ? daysRemaining + ' dias' : 'Expirado'}</div>
                            </div>
                            ${insurance.beneficiary ? `
                                <div class="detail-item">
                                    <label><i class="fas fa-user"></i> Beneficiário</label>
                                    <div class="value">${escapeHtml(insurance.beneficiary)}</div>
                                </div>
                            ` : ''}
                            ${insurance.broker_name ? `
                                <div class="detail-item">
                                    <label><i class="fas fa-handshake"></i> Corretor</label>
                                    <div class="value">${escapeHtml(insurance.broker_name)}</div>
                                </div>
                            ` : ''}
                            ${insurance.broker_phone ? `
                                <div class="detail-item">
                                    <label><i class="fas fa-phone"></i> Telefone</label>
                                    <div class="value">${escapeHtml(insurance.broker_phone)}</div>
                                </div>
                            ` : ''}
                            ${insurance.broker_email ? `
                                <div class="detail-item">
                                    <label><i class="fas fa-envelope"></i> Email</label>
                                    <div class="value">${escapeHtml(insurance.broker_email)}</div>
                                </div>
                            ` : ''}
                            ${insurance.coverage_details ? `
                                <div class="detail-item full-width">
                                    <label><i class="fas fa-align-left"></i> Coberturas</label>
                                    <div class="value">${escapeHtml(insurance.coverage_details)}</div>
                                </div>
                            ` : ''}
                            ${insurance.notes ? `
                                <div class="detail-item full-width">
                                    <label><i class="fas fa-sticky-note"></i> Observações</label>
                                    <div class="value">${escapeHtml(insurance.notes)}</div>
                                </div>
                            ` : ''}
                            ${insurance.document_file ? `
                                <div class="detail-item full-width">
                                    <label><i class="fas fa-file-alt"></i> Documento</label>
                                    <div class="value">
                                        <button onclick="downloadDocument(${insurance.id})" class="btn-primary" style="padding:8px 16px; font-size:0.8rem; border:none; cursor:pointer;">
                                            <i class="fas fa-download"></i> Baixar Documento
                                        </button>
                                    </div>
                                </div>
                            ` : ''}
                            <div class="detail-item full-width">
                                <label><i class="fas fa-calendar-alt"></i> Registo no Sistema</label>
                                <div class="value">${formatDateTime(insurance.created_at)}</div>
                            </div>
                        </div>
                    `;
                }
                
                const editBtn = document.getElementById('editFromDetailsBtn');
                if (editBtn) {
                    editBtn.onclick = function() { 
                        closeModal('viewInsuranceModal');
                        editInsurance(id);
                    };
                }
                
                const deleteBtn = document.getElementById('deleteFromDetailsBtn');
                if (deleteBtn) {
                    deleteBtn.onclick = function() {
                        closeModal('viewInsuranceModal');
                        confirmDeleteInsurance(id);
                    };
                }
                
                openModal('viewInsuranceModal');
            } else {
                showToast('error', 'Erro ao carregar detalhes');
            }
        } catch (error) {
            console.error('[View Details Error]', error);
            showToast('error', 'Erro ao carregar detalhes');
        }
    };
    
    // EDITAR

    window.editInsurance = async function(id) {
        try {
            const result = await apiRequest(`/insurances/${id}`);
            
            if (result && result.success && result.data) {
                const insurance = result.data;
                
                document.getElementById('editInsuranceId').value = insurance.id;
                document.getElementById('editVehicleId').value = insurance.vehicle_id;
                document.getElementById('editInsuranceCompany').value = insurance.insurance_company || '';
                document.getElementById('editPolicyNumber').value = insurance.policy_number || '';
                document.getElementById('editType').value = insurance.type || 'comprehensive';
                document.getElementById('editStartDate').value = insurance.start_date;
                document.getElementById('editEndDate').value = insurance.end_date;
                document.getElementById('editPremiumAmount').value = insurance.premium_amount || 0;
                document.getElementById('editDeductible').value = insurance.deductible || 0;
                document.getElementById('editBeneficiary').value = insurance.beneficiary || '';
                document.getElementById('editBrokerName').value = insurance.broker_name || '';
                document.getElementById('editBrokerPhone').value = insurance.broker_phone || '';
                document.getElementById('editBrokerEmail').value = insurance.broker_email || '';
                document.getElementById('editCoverageDetails').value = insurance.coverage_details || '';
                document.getElementById('editNotes').value = insurance.notes || '';
                
                if (insurance.document_file) {
                    const wrapper = document.getElementById('editPreviewWrapper');
                    const placeholder = document.getElementById('editUploadPlaceholder');
                    const fileName = document.getElementById('editFileName');
                    if (wrapper) wrapper.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                    if (fileName) fileName.textContent = 'Documento atual: ' + insurance.document_file;
                } else {
                    const wrapper = document.getElementById('editPreviewWrapper');
                    const placeholder = document.getElementById('editUploadPlaceholder');
                    if (wrapper) wrapper.style.display = 'none';
                    if (placeholder) placeholder.style.display = 'block';
                }
                
                openModal('editInsuranceModal');
            } else {
                showToast('error', 'Erro ao carregar dados');
            }
        } catch (error) {
            console.error('[Edit Error]', error);
            showToast('error', 'Erro ao carregar dados');
        }
    };
    
    // ELIMINAR

    window.confirmDeleteInsurance = function(id) {
        if (!id) {
            showToast('error', 'ID do seguro inválido');
            return;
        }
        
        currentInsuranceId = id;
        
        const messageEl = document.getElementById('confirmMessage');
        if (messageEl) {
            const insurance = state.insurances.find(i => i.id === id);
            if (insurance) {
                messageEl.textContent = `Tem certeza que deseja eliminar o seguro do veículo ${insurance.vehicle_brand || ''} ${insurance.vehicle_model || ''} (${insurance.vehicle_plate || 'sem placa'})?`;
            } else {
                messageEl.textContent = 'Tem certeza que deseja eliminar este seguro?';
            }
        }
        
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (confirmBtn) {
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            newConfirmBtn.addEventListener('click', function() {
                if (currentInsuranceId) {
                    deleteInsuranceAction(currentInsuranceId);
                }
            });
        }
        
        openModal('deleteConfirmModal');
    };
    
    async function deleteInsuranceAction(id) {
        if (!id) {
            showToast('error', 'ID do seguro inválido');
            return;
        }
        
        showToast('info', 'A eliminar seguro...');
        
        try {
            const response = await apiRequest(`/insurances/${id}`, {
                method: 'DELETE'
            });
            
            if (response && response.success) {
                showToast('success', 'Seguro eliminado com sucesso!');
                closeModal('deleteConfirmModal');
                currentInsuranceId = null;
                await loadStats();
                await loadInsurances();
            } else {
                showToast('error', response?.message || 'Erro ao eliminar seguro');
            }
        } catch (error) {
            console.error('[Delete Error]', error);
            showToast('error', 'Erro ao eliminar seguro');
        }
    }
    
    // FORMULÁRIOS

    document.getElementById('addInsuranceForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = {
            vehicle_id: document.getElementById('addVehicleId').value,
            insurance_company: document.getElementById('addInsuranceCompany').value.trim(),
            policy_number: document.getElementById('addPolicyNumber').value.trim(),
            type: document.getElementById('addType').value,
            start_date: document.getElementById('addStartDate').value,
            end_date: document.getElementById('addEndDate').value,
            premium_amount: parseFloat(document.getElementById('addPremiumAmount').value) || 0,
            deductible: parseFloat(document.getElementById('addDeductible').value) || 0,
            coverage_details: document.getElementById('addCoverageDetails').value,
            beneficiary: document.getElementById('addBeneficiary').value,
            broker_name: document.getElementById('addBrokerName').value,
            broker_phone: document.getElementById('addBrokerPhone').value,
            broker_email: document.getElementById('addBrokerEmail').value,
            notes: document.getElementById('addNotes').value
        };
        
        if (!formData.vehicle_id || !formData.insurance_company || !formData.policy_number || 
            !formData.start_date || !formData.end_date || !formData.premium_amount) {
            showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
            return;
        }
        
        await createInsurance(formData);
    });
    
    document.getElementById('editInsuranceForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('editInsuranceId').value;
        const formData = {
            vehicle_id: document.getElementById('editVehicleId').value,
            insurance_company: document.getElementById('editInsuranceCompany').value.trim(),
            policy_number: document.getElementById('editPolicyNumber').value.trim(),
            type: document.getElementById('editType').value,
            start_date: document.getElementById('editStartDate').value,
            end_date: document.getElementById('editEndDate').value,
            premium_amount: parseFloat(document.getElementById('editPremiumAmount').value) || 0,
            deductible: parseFloat(document.getElementById('editDeductible').value) || 0,
            coverage_details: document.getElementById('editCoverageDetails').value,
            beneficiary: document.getElementById('editBeneficiary').value,
            broker_name: document.getElementById('editBrokerName').value,
            broker_phone: document.getElementById('editBrokerPhone').value,
            broker_email: document.getElementById('editBrokerEmail').value,
            notes: document.getElementById('editNotes').value
        };
        
        if (!formData.vehicle_id || !formData.insurance_company || !formData.policy_number || 
            !formData.start_date || !formData.end_date || !formData.premium_amount) {
            showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
            return;
        }
        
        await updateInsurance(id, formData);
    });
    
    // UPLOAD DE DOCUMENTOS

    function removeAddDocument() {
        const input = document.getElementById('addDocument');
        const wrapper = document.getElementById('addPreviewWrapper');
        const placeholder = document.getElementById('addUploadPlaceholder');
        if (input) input.value = '';
        if (wrapper) wrapper.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
    }
    
    function removeEditDocument() {
        const input = document.getElementById('editDocument');
        const wrapper = document.getElementById('editPreviewWrapper');
        const placeholder = document.getElementById('editUploadPlaceholder');
        if (input) input.value = '';
        if (wrapper) wrapper.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
    }
    
    window.removeAddDocument = removeAddDocument;
    window.removeEditDocument = removeEditDocument;
    
    document.getElementById('addDocument')?.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const wrapper = document.getElementById('addPreviewWrapper');
            const placeholder = document.getElementById('addUploadPlaceholder');
            const fileName = document.getElementById('addFileName');
            if (wrapper) wrapper.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
            if (fileName) fileName.textContent = file.name;
        }
    });
    
    document.getElementById('editDocument')?.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const wrapper = document.getElementById('editPreviewWrapper');
            const placeholder = document.getElementById('editUploadPlaceholder');
            const fileName = document.getElementById('editFileName');
            if (wrapper) wrapper.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
            if (fileName) fileName.textContent = file.name;
        }
    });
    
    // FECHAR MODAIS COM ESC E CLICK NO OVERLAY

    function initModalEvents() {
        // Fechar com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                const openModals = document.querySelectorAll('.modal.active');
                if (openModals.length > 0) {
                    // Fecha o último modal aberto
                    const lastModal = openModals[openModals.length - 1];
                    closeModal(lastModal.id);
                }
            }
        });
        
        // Fechar com clique no overlay
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay') || e.target.classList.contains('modal-backdrop')) {
                const modal = e.target.closest('.modal');
                if (modal) {
                    closeModal(modal.id);
                }
            }
        });
    }
    
    // SIDEBAR & SEARCH

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
                    userChevron.style.transform = userDropdown.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            });
            
            document.addEventListener('click', (e) => {
                if (!userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.remove('show');
                    if (userChevron) userChevron.style.transform = 'rotate(0deg)';
                }
            });
        }
    }
    
    function initSearch() {
        const searchInput = document.getElementById('globalSearch');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const query = e.target.value.trim();
                    if (query.length >= 2) {
                        // Implementar busca quando disponível
                    }
                }, 500);
            });
        }
    }
    
    // INICIALIZAÇÃO
    
    async function init() {
        console.log('[CARDOXIS] Inicializando Insurance...');
        
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        initSidebar();
        initSearch();
        initModalEvents();
        
        await loadVehicles();
        await loadStats();
        await loadInsurances();
        
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            loadStats();
            updateLastUpdateTime();
        }, CONFIG.REFRESH_INTERVAL);
        
        console.log('[CARDOXIS] Insurance inicializado com sucesso!');
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();