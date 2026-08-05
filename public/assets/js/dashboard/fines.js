/**
 * CARDOXIS - Fines RF
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
    let currentFineId = null;
    
    let state = {
        fines: [],
        vehicles: [],
        drivers: [],
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

    async function downloadDocument(fineId) {
        try {
            const token = getToken();
            if (!token) {
                showToast('error', 'Sessão expirada. Faça login novamente.');
                return;
            }
            
            showToast('info', 'A preparar download...');
            
            const response = await fetch(`${CONFIG.API_URL}/fines/${fineId}/document`, {
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
            
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = `documento_multa_${fineId}.pdf`;
            
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
    
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            const openModals = document.querySelectorAll('.modal.active');
            if (openModals.length === 0) {
                document.body.style.overflow = '';
            }
        }
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
    
    // TIPOS E STATUS DE MULTAS

    function getTypeText(type) {
        const texts = {
            'speeding': 'Excesso de Velocidade',
            'parking': 'Estacionamento',
            'traffic_light': 'Semáforo',
            'documentation': 'Documentação',
            'technical': 'Técnica',
            'other': 'Outra'
        };
        return texts[type] || type;
    }
    
    function getTypeClass(type) {
        const classes = {
            'speeding': 'speeding',
            'parking': 'parking',
            'traffic_light': 'traffic_light',
            'documentation': 'documentation',
            'technical': 'technical',
            'other': 'other'
        };
        return classes[type] || 'other';
    }
    
    function getStatusText(status) {
        const texts = {
            'pending': 'Pendente',
            'paid': 'Paga',
            'overdue': 'Vencida',
            'contested': 'Contestada'
        };
        return texts[status] || status;
    }
    
    function getStatusClass(status) {
        const classes = {
            'pending': 'pending',
            'paid': 'paid',
            'overdue': 'overdue',
            'contested': 'contested'
        };
        return classes[status] || 'pending';
    }
    
    function getSeverityText(severity) {
        const texts = {
            'mild': 'Leve',
            'serious': 'Grave',
            'very_serious': 'Muito Grave'
        };
        return texts[severity] || severity;
    }
    
    function getSeverityClass(severity) {
        const classes = {
            'mild': 'mild',
            'serious': 'serious',
            'very_serious': 'very_serious'
        };
        return classes[severity] || 'mild';
    }
    
    // CARREGAMENTO DE DADOS - CORRIGIDO

    async function loadVehicles() {
        try {
            const result = await apiRequest('/vehicles?limit=100');
            
            if (result && result.success) {
                if (Array.isArray(result.data)) {
                    state.vehicles = result.data;
                } else if (result.data && Array.isArray(result.data.data)) {
                    state.vehicles = result.data.data;
                } else {
                    state.vehicles = [];
                }
                updateVehicleSelects();
            }
        } catch (error) {
            console.error('[Load Vehicles Error]', error);
        }
    }
    
    async function loadDrivers() {
        try {
            const result = await apiRequest('/drivers?limit=100');
            
            if (result && result.success) {
                if (Array.isArray(result.data)) {
                    state.drivers = result.data;
                } else if (result.data && Array.isArray(result.data.data)) {
                    state.drivers = result.data.data;
                } else {
                    state.drivers = [];
                }
                updateDriverSelects();
            } else {
                state.drivers = [];
                updateDriverSelects();
            }
        } catch (error) {
            console.error('[Load Drivers Error]', error);
            state.drivers = [];
            updateDriverSelects();
        }
    }
    
    function updateVehicleSelects() {
        const addSelect = document.getElementById('addVehicleId');
        const editSelect = document.getElementById('editVehicleId');
        
        if (!addSelect && !editSelect) return;
        
        const options = state.vehicles.map(vehicle => 
            `<option value="${vehicle.id}">${escapeHtml(vehicle.brand)} ${escapeHtml(vehicle.model)} - ${escapeHtml(vehicle.plate)}</option>`
        ).join('');
        
        if (addSelect) {
            addSelect.innerHTML = '<option value="">Selecione um veículo</option>' + options;
        }
        if (editSelect) {
            editSelect.innerHTML = '<option value="">Selecione um veículo</option>' + options;
        }
    }
    
    function updateDriverSelects() {
        const addSelect = document.getElementById('addDriverId');
        const editSelect = document.getElementById('editDriverId');
        
        if (!addSelect && !editSelect) return;
        
        const options = state.drivers.map(driver => 
            `<option value="${driver.id}">${escapeHtml(driver.name)}</option>`
        ).join('');
        
        if (addSelect) {
            addSelect.innerHTML = '<option value="">Selecione um motorista</option>' + options;
        }
        if (editSelect) {
            editSelect.innerHTML = '<option value="">Selecione um motorista</option>' + options;
        }
    }
    
    async function loadStats() {
        try {
            const result = await apiRequest('/fines/stats');
            
            if (result && result.success && result.data) {
                const stats = result.data;
                
                updateElementText('totalFines', formatNumber(stats.total_fines || 0));
                updateElementText('totalAmount', formatMoney(stats.total_amount || 0));
                updateElementText('pendingAmount', formatMoney(stats.pending_amount || 0));
                updateElementText('paidAmount', formatMoney(stats.paid_amount || 0));
                updateElementText('overdueCount', formatNumber(stats.overdue_count || 0));
            }
        } catch (error) {
            console.error('[Load Stats Error]', error);
        }
    }
    
    async function loadFines() {
        if (state.isLoading) return;
        
        state.isLoading = true;
        showLoading();
        
        try {
            let url = `/fines?limit=${CONFIG.ITEMS_PER_PAGE}&page=${state.currentPage}`;
            
            if (state.currentStatus !== 'all') {
                url += `&status=${state.currentStatus}`;
            }
            
            if (state.currentType !== 'all') {
                url += `&type=${state.currentType}`;
            }
            
            const result = await apiRequest(url);
            
            if (result && result.success && result.data) {
                if (Array.isArray(result.data)) {
                    state.fines = result.data;
                } else if (result.data.data && Array.isArray(result.data.data)) {
                    state.fines = result.data.data;
                } else {
                    state.fines = [];
                }
                
                state.totalPages = result.data.pagination?.last_page || 1;
                state.total = result.data.pagination?.total || 0;
                
                // Atualizar total no header
                const totalCount = document.getElementById('totalCount');
                if (totalCount) {
                    totalCount.textContent = state.total;
                }
                
                renderTable();
                renderPagination();
                updateLastUpdateTime();
            } else {
                state.fines = [];
                state.total = 0;
                const totalCount = document.getElementById('totalCount');
                if (totalCount) {
                    totalCount.textContent = '0';
                }
                renderEmptyState();
            }
        } catch (error) {
            console.error('[Load Fines Error]', error);
            state.fines = [];
            state.total = 0;
            const totalCount = document.getElementById('totalCount');
            if (totalCount) {
                totalCount.textContent = '0';
            }
            renderEmptyState();
        } finally {
            state.isLoading = false;
        }
    }
    
    function showLoading() {
        const container = document.getElementById('fineTableBody');
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 60px;">
                        <div class="spinner"></div>
                        <p style="margin-top: 16px;">Carregando multas...</p>
                    </td>
                </tr>
            `;
        }
    }
    
    function renderTable() {
        const container = document.getElementById('fineTableBody');
        if (!container) return;
        
        if (!Array.isArray(state.fines) || state.fines.length === 0) {
            renderEmptyState();
            return;
        }
        
        container.innerHTML = state.fines.map(fine => {
            const daysRemaining = parseInt(fine.days_remaining) || 0;
            const status = fine.status_calculated || fine.status || 'pending';
            const statusClass = getStatusClass(status);
            const statusText = getStatusText(status);
            
            let statusDisplay = statusText;
            if (status === 'pending' && daysRemaining > 0) {
                statusDisplay = `${statusText} (${daysRemaining} dias)`;
            } else if (status === 'pending' && daysRemaining <= 0) {
                statusDisplay = 'Vencida';
            }
            
            return `
                <tr onclick="viewFineDetails(${fine.id})">
                    <td><strong>${escapeHtml(fine.fine_number || '-')}</strong></td>
                    <td>
                        <div class="vehicle-cell">
                            <span class="vehicle-name">${escapeHtml(fine.vehicle_brand || '')} ${escapeHtml(fine.vehicle_model || '')}</span>
                            <span class="vehicle-plate">${escapeHtml(fine.vehicle_plate || '-')}</span>
                        </div>
                    </td>
                    <td>${escapeHtml(fine.driver_name || '-')}</td>
                    <td><span class="type-badge ${getTypeClass(fine.type)}">${getTypeText(fine.type)}</span></td>
                    <td>${formatDate(fine.issue_date)}</td>
                    <td>${formatDate(fine.due_date)}</td>
                    <td class="cost-cell">${formatMoney(fine.amount)}</td>
                    <td>
                        <span class="status-badge ${statusClass}">
                            <i class="fas ${status === 'paid' ? 'fa-check-circle' : status === 'overdue' ? 'fa-exclamation-circle' : status === 'contested' ? 'fa-gavel' : 'fa-clock'}"></i>
                            ${statusDisplay}
                        </span>
                    </td>
                    <td class="action-buttons" onclick="event.stopPropagation()">
                        ${fine.document_file ? `
                            <button class="action-btn" onclick="downloadDocument(${fine.id})" title="Baixar documento">
                                <i class="fas fa-file-pdf" style="color:var(--danger);"></i>
                            </button>
                        ` : ''}
                        ${status !== 'paid' && status !== 'contested' ? `
                            <button class="action-btn success" onclick="markAsPaid(${fine.id})" title="Marcar como paga">
                                <i class="fas fa-check"></i>
                            </button>
                        ` : ''}
                        ${status !== 'paid' && status !== 'contested' ? `
                            <button class="action-btn warning" onclick="markAsContested(${fine.id})" title="Contestar multa">
                                <i class="fas fa-gavel"></i>
                            </button>
                        ` : ''}
                        <button class="action-btn" onclick="viewFineDetails(${fine.id})" title="Ver detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn" onclick="editFine(${fine.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn danger" onclick="confirmDeleteFine(${fine.id})" title="Eliminar">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    function renderEmptyState() {
        const container = document.getElementById('fineTableBody');
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 60px;">
                        <div class="empty-state">
                            <i class="fas fa-file-invoice"></i>
                            <h3>Nenhuma multa encontrada</h3>
                            <p>Comece registando a primeira multa da sua frota</p>
                            <button class="btn-primary" onclick="openAddFineModal()">
                                <i class="fas fa-plus"></i> Registrar Multa
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

    window.filterFine = function(status) {
        state.currentStatus = status;
        state.currentPage = 1;
        
        document.querySelectorAll('.filter-btn[data-status]').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.status === status) {
                btn.classList.add('active');
            }
        });
        
        loadFines();
    };
    
    window.filterFineByType = function(type) {
        state.currentType = type;
        state.currentPage = 1;
        
        document.querySelectorAll('.filter-btn[data-type]').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.type === type) {
                btn.classList.add('active');
            }
        });
        
        loadFines();
    };
    
    window.changePage = function(page) {
        if (page < 1 || page > state.totalPages || page === state.currentPage) return;
        state.currentPage = page;
        loadFines();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    // FUNÇÃO DE DOWNLOAD GLOBAL

    window.downloadDocument = downloadDocument;

    // FUNÇÕES DE MODAL GLOBAIS
    
    window.closeModal = closeModal;
    
    
    // CRUD OPERAÇÕES
    
    window.openAddFineModal = function() {
        resetAddForm();
        openModal('addFineModal');
    };
    
    function resetAddForm() {
        const form = document.getElementById('addFineForm');
        if (form) form.reset();
        document.getElementById('addIssueDate').value = new Date().toISOString().split('T')[0];
        const dueDate = new Date();
        dueDate.setDate(dueDate.getDate() + 30);
        document.getElementById('addDueDate').value = dueDate.toISOString().split('T')[0];
    }
    
    async function createFine(formData) {
        showToast('info', 'A registar multa...');
        
        const response = await apiRequest('/fines', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        if (response && response.success) {
            const fineId = response.data.id;
            
            const documentFile = document.getElementById('addDocument').files[0];
            if (documentFile && fineId) {
                await uploadDocument(fineId, documentFile);
            }
            
            showToast('success', 'Multa registada com sucesso!');
            closeModal('addFineModal');
            await loadStats();
            await loadFines();
        } else {
            showToast('error', response?.message || 'Erro ao registrar multa');
        }
    }
    
    async function updateFine(id, formData) {
        showToast('info', 'A atualizar multa...');
        
        const response = await apiRequest(`/fines/${id}`, {
            method: 'PUT',
            body: JSON.stringify(formData)
        });
        
        if (response && response.success) {
            const documentFile = document.getElementById('editDocument').files[0];
            if (documentFile) {
                await uploadDocument(id, documentFile);
            }
            
            showToast('success', 'Multa atualizada com sucesso!');
            closeModal('editFineModal');
            await loadStats();
            await loadFines();
        } else {
            showToast('error', response?.message || 'Erro ao atualizar multa');
        }
    }
    
    async function uploadDocument(fineId, file) {
        const formData = new FormData();
        formData.append('document', file);
        
        try {
            const token = getToken();
            const response = await fetch(`${CONFIG.API_URL}/fines/${fineId}/document`, {
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
    
    // MARCAR COMO PAGA

    window.markAsPaid = async function(id) {
        if (!id) {
            showToast('error', 'ID da multa inválido');
            return;
        }
        
        if (!confirm('Tem certeza que deseja marcar esta multa como paga?')) {
            return;
        }
        
        showToast('info', 'A processar pagamento...');
        
        try {
            const response = await apiRequest(`/fines/${id}/pay`, {
                method: 'POST',
                body: JSON.stringify({})
            });
            
            if (response && response.success) {
                showToast('success', 'Multa marcada como paga com sucesso!');
                await loadStats();
                await loadFines();
            } else {
                showToast('error', response?.message || 'Erro ao marcar multa como paga');
            }
        } catch (error) {
            console.error('[Mark Paid Error]', error);
            showToast('error', 'Erro ao marcar multa como paga');
        }
    };
    
    // MARCAR COMO CONTESTADA

    window.markAsContested = async function(id) {
        if (!id) {
            showToast('error', 'ID da multa inválido');
            return;
        }
        
        if (!confirm('Tem certeza que deseja contestar esta multa?')) {
            return;
        }
        
        showToast('info', 'A processar contestação...');
        
        try {
            const response = await apiRequest(`/fines/${id}/contest`, {
                method: 'POST',
                body: JSON.stringify({})
            });
            
            if (response && response.success) {
                showToast('success', 'Multa marcada como contestada com sucesso!');
                await loadStats();
                await loadFines();
            } else {
                showToast('error', response?.message || 'Erro ao contestar multa');
            }
        } catch (error) {
            console.error('[Mark Contested Error]', error);
            showToast('error', 'Erro ao contestar multa');
        }
    };

    // VISUALIZAR DETALHES

    window.viewFineDetails = async function(id) {
        try {
            const result = await apiRequest(`/fines/${id}`);
            
            if (result && result.success && result.data) {
                const fine = result.data;
                currentFineId = id;
                
                const modalContent = document.getElementById('detailModalContent');
                if (modalContent) {
                    const daysRemaining = parseInt(fine.days_remaining) || 0;
                    const status = fine.status_calculated || fine.status || 'pending';
                    const statusClass = getStatusClass(status);
                    const statusText = getStatusText(status);
                    
                    modalContent.innerHTML = `
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label><i class="fas fa-hashtag"></i> Número da Multa</label>
                                <div class="value"><strong>${escapeHtml(fine.fine_number || '-')}</strong></div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-car"></i> Veículo</label>
                                <div class="value">${escapeHtml(fine.vehicle_brand || '')} ${escapeHtml(fine.vehicle_model || '')} (${escapeHtml(fine.vehicle_plate || '-')})</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-user"></i> Motorista</label>
                                <div class="value">${escapeHtml(fine.driver_name || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-tag"></i> Tipo</label>
                                <div class="value"><span class="type-badge ${getTypeClass(fine.type)}">${getTypeText(fine.type)}</span></div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-calendar"></i> Data Emissão</label>
                                <div class="value">${formatDate(fine.issue_date)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-calendar-alt"></i> Data Vencimento</label>
                                <div class="value">${formatDate(fine.due_date)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-euro-sign"></i> Valor</label>
                                <div class="value cost-cell">${formatMoney(fine.amount)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-euro-sign"></i> Valor Pago</label>
                                <div class="value cost-cell">${formatMoney(fine.paid_amount || 0)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-chart-line"></i> Status</label>
                                <div class="value"><span class="status-badge ${statusClass}">${statusText}</span></div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-clock"></i> Dias Restantes</label>
                                <div class="value">${daysRemaining > 0 ? daysRemaining + ' dias' : status === 'paid' ? 'Paga' : 'Vencida'}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-map-marker-alt"></i> Local</label>
                                <div class="value">${escapeHtml(fine.location || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-exclamation-triangle"></i> Gravidade</label>
                                <div class="value"><span class="severity-badge ${getSeverityClass(fine.severity)}">${getSeverityText(fine.severity)}</span></div>
                            </div>
                            ${fine.description ? `
                                <div class="detail-item full-width">
                                    <label><i class="fas fa-align-left"></i> Descrição</label>
                                    <div class="value">${escapeHtml(fine.description)}</div>
                                </div>
                            ` : ''}
                            ${fine.notes ? `
                                <div class="detail-item full-width">
                                    <label><i class="fas fa-sticky-note"></i> Observações</label>
                                    <div class="value">${escapeHtml(fine.notes)}</div>
                                </div>
                            ` : ''}
                            ${fine.document_file ? `
                                <div class="detail-item full-width">
                                    <label><i class="fas fa-file-alt"></i> Documento</label>
                                    <div class="value">
                                        <button onclick="downloadDocument(${fine.id})" class="btn-primary" style="padding:8px 16px; font-size:0.8rem; border:none; cursor:pointer;">
                                            <i class="fas fa-download"></i> Baixar Documento
                                        </button>
                                    </div>
                                </div>
                            ` : ''}
                            <div class="detail-item full-width">
                                <label><i class="fas fa-calendar-alt"></i> Registo no Sistema</label>
                                <div class="value">${formatDateTime(fine.created_at)}</div>
                            </div>
                        </div>
                    `;
                }
                
                const editBtn = document.getElementById('editFromDetailsBtn');
                if (editBtn) {
                    editBtn.onclick = function() { 
                        closeModal('viewFineModal');
                        editFine(id);
                    };
                }
                
                const deleteBtn = document.getElementById('deleteFromDetailsBtn');
                if (deleteBtn) {
                    deleteBtn.onclick = function() {
                        closeModal('viewFineModal');
                        confirmDeleteFine(id);
                    };
                }
                
                const payBtn = document.getElementById('payFromDetailsBtn');
                if (payBtn) {
                    payBtn.onclick = function() {
                        closeModal('viewFineModal');
                        markAsPaid(id);
                    };
                }
                
                const contestBtn = document.getElementById('contestFromDetailsBtn');
                if (contestBtn) {
                    contestBtn.onclick = function() {
                        closeModal('viewFineModal');
                        markAsContested(id);
                    };
                }
                
                openModal('viewFineModal');
            } else {
                showToast('error', 'Erro ao carregar detalhes');
            }
        } catch (error) {
            console.error('[View Details Error]', error);
            showToast('error', 'Erro ao carregar detalhes');
        }
    };
    
    // EDITAR - CORRIGIDO

    window.editFine = async function(id) {
        try {
            const result = await apiRequest(`/fines/${id}`);
            
            if (result && result.success && result.data) {
                const fine = result.data;
                
                document.getElementById('editFineId').value = fine.id;
                document.getElementById('editVehicleId').value = fine.vehicle_id;
                
                // CORREÇÃO: Carregar o motorista corretamente
                const driverId = fine.driver_id || '';
                document.getElementById('editDriverId').value = driverId;
                
                document.getElementById('editFineNumber').value = fine.fine_number || '';
                document.getElementById('editType').value = fine.type || 'other';
                document.getElementById('editSeverity').value = fine.severity || 'mild';
                document.getElementById('editIssueDate').value = fine.issue_date;
                document.getElementById('editDueDate').value = fine.due_date;
                document.getElementById('editAmount').value = fine.amount || 0;
                document.getElementById('editLocation').value = fine.location || '';
                document.getElementById('editDescription').value = fine.description || '';
                document.getElementById('editStatus').value = fine.status || 'pending';
                document.getElementById('editNotes').value = fine.notes || '';
                
                if (fine.document_file) {
                    const wrapper = document.getElementById('editPreviewWrapper');
                    const placeholder = document.getElementById('editUploadPlaceholder');
                    const fileName = document.getElementById('editFileName');
                    if (wrapper) wrapper.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                    if (fileName) fileName.textContent = 'Documento atual: ' + fine.document_file;
                } else {
                    const wrapper = document.getElementById('editPreviewWrapper');
                    const placeholder = document.getElementById('editUploadPlaceholder');
                    if (wrapper) wrapper.style.display = 'none';
                    if (placeholder) placeholder.style.display = 'block';
                }
                
                openModal('editFineModal');
            } else {
                showToast('error', 'Erro ao carregar dados');
            }
        } catch (error) {
            console.error('[Edit Error]', error);
            showToast('error', 'Erro ao carregar dados');
        }
    };
    
    // ELIMINAR

    window.confirmDeleteFine = function(id) {
        if (!id) {
            showToast('error', 'ID da multa inválido');
            return;
        }
        
        currentFineId = id;
        
        const messageEl = document.getElementById('confirmMessage');
        if (messageEl) {
            const fine = state.fines.find(f => f.id === id);
            if (fine) {
                messageEl.textContent = `Tem certeza que deseja eliminar a multa ${fine.fine_number || ''} do veículo ${fine.vehicle_brand || ''} ${fine.vehicle_model || ''} (${fine.vehicle_plate || 'sem placa'})?`;
            } else {
                messageEl.textContent = 'Tem certeza que deseja eliminar esta multa?';
            }
        }
        
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (confirmBtn) {
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            newConfirmBtn.addEventListener('click', function() {
                if (currentFineId) {
                    deleteFineAction(currentFineId);
                }
            });
        }
        
        openModal('deleteConfirmModal');
    };
    
    async function deleteFineAction(id) {
        if (!id) {
            showToast('error', 'ID da multa inválido');
            return;
        }
        
        showToast('info', 'A eliminar multa...');
        
        try {
            const response = await apiRequest(`/fines/${id}`, {
                method: 'DELETE'
            });
            
            if (response && response.success) {
                showToast('success', 'Multa eliminada com sucesso!');
                closeModal('deleteConfirmModal');
                currentFineId = null;
                await loadStats();
                await loadFines();
            } else {
                showToast('error', response?.message || 'Erro ao eliminar multa');
            }
        } catch (error) {
            console.error('[Delete Error]', error);
            showToast('error', 'Erro ao eliminar multa');
        }
    }
    
    // FORMULÁRIOS

    document.getElementById('addFineForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = {
            vehicle_id: document.getElementById('addVehicleId').value,
            driver_id: document.getElementById('addDriverId').value || null,
            fine_number: document.getElementById('addFineNumber').value.trim(),
            type: document.getElementById('addType').value,
            severity: document.getElementById('addSeverity').value,
            issue_date: document.getElementById('addIssueDate').value,
            due_date: document.getElementById('addDueDate').value,
            amount: parseFloat(document.getElementById('addAmount').value) || 0,
            location: document.getElementById('addLocation').value,
            description: document.getElementById('addDescription').value,
            status: document.getElementById('addStatus').value,
            notes: document.getElementById('addNotes').value
        };
        
        if (!formData.vehicle_id || !formData.fine_number || 
            !formData.issue_date || !formData.due_date || !formData.amount) {
            showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
            return;
        }
        
        await createFine(formData);
    });
    
    document.getElementById('editFineForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('editFineId').value;
        const formData = {
            vehicle_id: document.getElementById('editVehicleId').value,
            driver_id: document.getElementById('editDriverId').value || null,
            fine_number: document.getElementById('editFineNumber').value.trim(),
            type: document.getElementById('editType').value,
            severity: document.getElementById('editSeverity').value,
            issue_date: document.getElementById('editIssueDate').value,
            due_date: document.getElementById('editDueDate').value,
            amount: parseFloat(document.getElementById('editAmount').value) || 0,
            location: document.getElementById('editLocation').value,
            description: document.getElementById('editDescription').value,
            status: document.getElementById('editStatus').value,
            notes: document.getElementById('editNotes').value
        };
        
        if (!formData.vehicle_id || !formData.fine_number || 
            !formData.issue_date || !formData.due_date || !formData.amount) {
            showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
            return;
        }
        
        await updateFine(id, formData);
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
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                const openModals = document.querySelectorAll('.modal.active');
                if (openModals.length > 0) {
                    const lastModal = openModals[openModals.length - 1];
                    closeModal(lastModal.id);
                }
            }
        });
        
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
    }
    
    // INICIALIZAÇÃO
    
    async function init() {
        console.log('[CARDOXIS] Inicializando Fines Management...');
        
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        initSidebar();
        initModalEvents();
        
        // Carregar dados na ordem correta
        await loadVehicles();
        await loadDrivers();
        await loadStats();
        await loadFines();
        
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            loadStats();
            updateLastUpdateTime();
        }, CONFIG.REFRESH_INTERVAL);
        
        console.log('[CARDOXIS] Fines Management inicializado com sucesso!');
    }
    
    // Expor funções globais
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.downloadDocument = downloadDocument;
    window.viewFineDetails = viewFineDetails;
    window.editFine = editFine;
    window.confirmDeleteFine = confirmDeleteFine;
    window.openAddFineModal = openAddFineModal;
    window.filterFine = filterFine;
    window.filterFineByType = filterFineByType;
    window.changePage = changePage;
    window.markAsPaid = markAsPaid;
    window.markAsContested = markAsContested;
    window.removeAddDocument = removeAddDocument;
    window.removeEditDocument = removeEditDocument;
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();