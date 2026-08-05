/**
 * CARDOXIS - Maintenance RF
 * 
 * @package CARDOXIS
 * @author CARDOXIS Team
 * @license Proprietary
 * @copyright 2025 CARDOXIS
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES GLOBAIS

    const CONFIG = {
        // URL BASE - SEM /api/v1 porque as rotas já estão definidas no router
        BASE_URL: window.location.origin + '/cardoxis/api/v1',
        ITEMS_PER_PAGE: 15,
        REFRESH_INTERVAL: 60000
    };
    
    let refreshInterval = null;
    let currentMaintenanceId = null;
    let isDeleting = false;
    let isCompleting = false;
    
    // Estado da aplicação
    let state = {
        maintenances: [],
        vehicles: [],
        currentPage: 1,
        currentStatus: 'all',
        currentSearch: '',
        totalPages: 1,
        isLoading: false
    };
    
    // FUNÇÕES DE AUTENTICAÇÃO

    
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
    
    // REQUISIÇÕES API
    
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
            // Remove /api/v1 se existir no endpoint
            let cleanEndpoint = endpoint;
            if (cleanEndpoint.startsWith('/api/v1')) {
                cleanEndpoint = cleanEndpoint.replace('/api/v1', '');
            }
            
            const url = CONFIG.BASE_URL + cleanEndpoint;
            console.log('[API Request]', url);
            
            const response = await fetch(url, {
                ...options,
                headers,
                credentials: 'same-origin'
            });
            
            if (response.status === 401) {
                handleUnauthorized();
                return null;
            }
            
            // Tenta ler a resposta como JSON
            let data;
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                console.error('[API Error Response]', text);
                throw new Error(`HTTP ${response.status}`);
            }
            
            if (!response.ok) {
                console.error('[API Error Response]', data);
                throw new Error(data.message || `HTTP ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('[API Error]', endpoint, error);
            return { success: false, message: error.message || 'Erro na comunicação com o servidor', data: null };
        }
    }
    
    // FUNÇÕES UTILITÁRIAS
    
    function formatNumber(num) {
        return num.toLocaleString('pt-PT');
    }
    
    function formatMoney(value) {
        if (!value) return '€ 0,00';
        return `€ ${parseFloat(value).toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-PT');
    }
    
    function formatDateTime(dateString) {
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
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('active');
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
    
    // CARREGAMENTO DE DADOS
    
    async function loadVehicles() {
        try {
            const result = await apiRequest('/vehicles?limit=100');
            if (result && result.success && result.data) {
                state.vehicles = result.data;
                updateVehicleSelects();
            } else {
                console.warn('[Load Vehicles] Nenhum veículo encontrado');
            }
        } catch (error) {
            console.error('[Load Vehicles Error]', error);
        }
    }
    
    function updateVehicleSelects() {
        const select = document.getElementById('addVehicleId');
        const filterSelect = document.getElementById('filterVehicle');
        
        const options = state.vehicles.map(vehicle => 
            `<option value="${vehicle.id}">${escapeHtml(vehicle.brand)} ${escapeHtml(vehicle.model)} - ${escapeHtml(vehicle.plate)}</option>`
        ).join('');
        
        if (select) {
            select.innerHTML = '<option value="">Selecione um veículo</option>' + options;
        }
        
        if (filterSelect) {
            filterSelect.innerHTML = '<option value="">Todos os veículos</option>' + options;
        }
    }
    
    async function loadStats() {
        try {
            const result = await apiRequest('/maintenances?limit=1000');
            if (result && result.success && result.data) {
                const maintenances = result.data.data || [];
                const scheduled = maintenances.filter(m => m.status === 'scheduled').length;
                const inProgress = maintenances.filter(m => m.status === 'in_progress').length;
                const completed = maintenances.filter(m => m.status === 'completed').length;
                const totalCost = maintenances.filter(m => m.status === 'completed').reduce((sum, m) => sum + (parseFloat(m.cost) || 0), 0);
                
                updateElementText('totalMaintenances', formatNumber(maintenances.length));
                updateElementText('scheduledCount', formatNumber(scheduled + inProgress));
                updateElementText('completedCount', formatNumber(completed));
                updateElementText('totalCost', formatMoney(totalCost));
            }
        } catch (error) {
            console.error('[Load Stats Error]', error);
        }
    }
    
    async function loadMaintenances(silent) {
        silent = silent || false;
        
        if (state.isLoading) return;
        
        state.isLoading = true;
        if (!silent) showLoading();
        
        try {
            let url = `/maintenances?limit=${CONFIG.ITEMS_PER_PAGE}&page=${state.currentPage}`;
            
            if (state.currentStatus !== 'all') {
                url += `&status=${state.currentStatus}`;
            }
            
            const result = await apiRequest(url);
            
            if (result && result.success && result.data) {
                state.maintenances = result.data.data || [];
                state.totalPages = result.data.total_pages || 1;
                renderTable();
                renderPagination();
                updateLastUpdateTime();
            } else {
                state.maintenances = [];
                renderEmptyState();
            }
        } catch (error) {
            console.error('[Load Maintenances Error]', error);
            renderEmptyState();
            if (!silent) showToast('error', 'Erro ao carregar manutenções');
        } finally {
            state.isLoading = false;
        }
    }
    
    function showLoading() {
        const container = document.getElementById('maintenancesTableBody');
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 60px;">
                        <div class="spinner"></div>
                        <p style="margin-top: 16px;">Carregando manutenções...</p>
                    </td>
                </tr>
            `;
        }
    }
    
    function renderTable() {
        const container = document.getElementById('maintenancesTableBody');
        if (!container) return;
        
        if (state.maintenances.length === 0) {
            renderEmptyState();
            return;
        }
        
        container.innerHTML = state.maintenances.map(maintenance => {
            const daysUntil = Math.ceil((new Date(maintenance.scheduled_date) - new Date()) / (1000 * 60 * 60 * 24));
            const isOverdue = daysUntil < 0 && maintenance.status !== 'completed' && maintenance.status !== 'cancelled';
            const canEdit = maintenance.status !== 'completed' && maintenance.status !== 'cancelled';
            
            return `
                <tr>
                    <td>
                        <strong>${escapeHtml(maintenance.title)}</strong>
                        ${maintenance.description ? `<br><small style="color: var(--gray);">${escapeHtml(maintenance.description.substring(0, 50))}${maintenance.description.length > 50 ? '...' : ''}</small>` : ''}
                    </td>
                    <td>
                        <div><strong>${escapeHtml(maintenance.brand)} ${escapeHtml(maintenance.model)}</strong></div>
                        <small style="color: var(--gray);">${escapeHtml(maintenance.plate)}</small>
                    </td>
                    <td>
                        <span class="priority-badge priority-${maintenance.priority || 'medium'}">
                            <i class="fas ${getPriorityIcon(maintenance.priority)}"></i>
                            ${getPriorityText(maintenance.priority)}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-${maintenance.status}">
                            <i class="fas ${getStatusIcon(maintenance.status)}"></i>
                            ${getStatusText(maintenance.status)}
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <span><i class="fas fa-calendar"></i> ${formatDate(maintenance.scheduled_date)}</span>
                            ${isOverdue ? `<span class="priority-high" style="font-size: 0.7rem; margin-top: 4px;"><i class="fas fa-exclamation-triangle"></i> Atrasado ${Math.abs(daysUntil)} dias</span>` : ''}
                            ${daysUntil >= 0 && daysUntil <= 7 && maintenance.status !== 'completed' && maintenance.status !== 'cancelled' ? `<span class="priority-warning" style="font-size: 0.7rem; margin-top: 4px;"><i class="fas fa-clock"></i> Em ${daysUntil} dias</span>` : ''}
                        </div>
                    </td>
                    <td class="cost-cell ${maintenance.cost ? 'positive' : ''}">
                        ${formatMoney(maintenance.cost)}
                    </td>
                    <td>${maintenance.workshop_name ? escapeHtml(maintenance.workshop_name) : '-'}</td>
                    <td class="action-buttons">
                        <button class="action-btn" onclick="viewMaintenanceDetails(${maintenance.id})" title="Ver detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${canEdit ? `
                            <button class="action-btn" onclick="editMaintenance(${maintenance.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn success" onclick="completeMaintenance(${maintenance.id})" title="Concluir">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <button class="action-btn danger" onclick="deleteMaintenance(${maintenance.id})" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    function renderEmptyState() {
        const container = document.getElementById('maintenancesTableBody');
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 60px;">
                        <div class="empty-state">
                            <i class="fas fa-tools"></i>
                            <h3>Nenhuma manutenção encontrada</h3>
                            <p>Comece adicionando a primeira manutenção da sua frota</p>
                            <button class="btn-primary" onclick="openAddMaintenanceModal()">
                                <i class="fas fa-plus"></i> Agendar Manutenção
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
        
        container.innerHTML = `
            <button class="page-btn" onclick="changePage(${state.currentPage - 1})" ${state.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
            ${pages.map(page => `
                <button class="page-btn ${page === state.currentPage ? 'active' : ''}" onclick="changePage(${page})">
                    ${page}
                </button>
            `).join('')}
            <button class="page-btn" onclick="changePage(${state.currentPage + 1})" ${state.currentPage === state.totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
    }
    
    // FUNÇÕES AUXILIARES
    
    function getPriorityIcon(priority) {
        const icons = { 'critical': 'fa-exclamation-circle', 'high': 'fa-arrow-up', 'medium': 'fa-minus', 'low': 'fa-arrow-down' };
        return icons[priority] || 'fa-minus';
    }
    
    function getPriorityText(priority) {
        const texts = { 'critical': 'Crítica', 'high': 'Alta', 'medium': 'Média', 'low': 'Baixa' };
        return texts[priority] || 'Média';
    }
    
    function getStatusIcon(status) {
        const icons = { 'scheduled': 'fa-calendar', 'in_progress': 'fa-spinner', 'completed': 'fa-check-circle', 'overdue': 'fa-exclamation-triangle', 'cancelled': 'fa-times-circle' };
        return icons[status] || 'fa-calendar';
    }
    
    function getStatusText(status) {
        const texts = { 'scheduled': 'Agendada', 'in_progress': 'Em Andamento', 'completed': 'Concluída', 'overdue': 'Atrasada', 'cancelled': 'Cancelada' };
        return texts[status] || status;
    }
    
    // CRUD OPERAÇÕES
    
    async function createMaintenance(formData) {
        showToast('info', 'A agendar manutenção...');
        
        const response = await apiRequest('/maintenances', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        if (response && response.success) {
            showToast('success', response.message || 'Manutenção agendada com sucesso!');
            closeModal('addMaintenanceModal');
            resetAddForm();
            await loadStats();
            await loadMaintenances();
        } else {
            showToast('error', response?.message || 'Erro ao agendar manutenção');
        }
    }
    
    async function updateMaintenance(id, formData) {
        if (!id || isNaN(id)) {
            showToast('error', 'ID da manutenção inválido');
            return;
        }
        
        showToast('info', 'A atualizar manutenção...');
        
        const response = await apiRequest(`/maintenances/${id}`, {
            method: 'PUT',
            body: JSON.stringify(formData)
        });
        
        if (response && response.success) {
            showToast('success', response.message || 'Manutenção atualizada com sucesso!');
            closeModal('editMaintenanceModal');
            await loadStats();
            await loadMaintenances();
        } else {
            showToast('error', response?.message || 'Erro ao atualizar manutenção');
        }
    }
    
    async function completeMaintenanceAction(id, cost, odometer) {
        if (!id || isNaN(id)) {
            showToast('error', 'ID da manutenção inválido');
            return;
        }
        
        if (isCompleting) return;
        isCompleting = true;
        
        showToast('info', 'A concluir manutenção...');
        
        const data = {};
        if (cost && !isNaN(cost)) data.cost = parseFloat(cost);
        if (odometer && !isNaN(odometer)) data.odometer_at_maintenance = parseInt(odometer);
        
        const response = await apiRequest(`/maintenances/${id}/complete`, {
            method: 'POST',
            body: JSON.stringify(data)
        });
        
        if (response && response.success) {
            showToast('success', response.message || 'Manutenção concluída com sucesso!');
            closeModal('completeMaintenanceModal');
            await loadStats();
            await loadMaintenances();
        } else {
            showToast('error', response?.message || 'Erro ao concluir manutenção');
        }
        
        isCompleting = false;
    }
    
    async function deleteMaintenanceAction(id) {
        if (!id || isNaN(id)) {
            showToast('error', 'ID da manutenção inválido');
            return;
        }
        
        if (isDeleting) return;
        isDeleting = true;
        
        showToast('info', 'A eliminar manutenção...');
        
        const response = await apiRequest(`/maintenances/${id}`, {
            method: 'DELETE'
        });
        
        if (response && response.success) {
            showToast('success', response.message || 'Manutenção eliminada com sucesso!');
            closeModal('deleteConfirmModal');
            await loadStats();
            await loadMaintenances();
        } else {
            showToast('error', response?.message || 'Erro ao eliminar manutenção');
        }
        
        isDeleting = false;
    }
    
    // EVENT HANDLERS (GLOBAIS)
    
    window.openAddMaintenanceModal = function() {
        resetAddForm();
        openModal('addMaintenanceModal');
    };
    
    window.filterMaintenances = function(status) {
        state.currentStatus = status;
        state.currentPage = 1;
        
        document.querySelectorAll('.filter-status-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.status === status) {
                btn.classList.add('active');
            }
        });
        
        loadMaintenances();
    };
    
    window.changePage = function(page) {
        if (page < 1 || page > state.totalPages || page === state.currentPage) return;
        state.currentPage = page;
        loadMaintenances();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    window.viewMaintenanceDetails = async function(id) {
        if (!id || isNaN(id)) {
            showToast('error', 'ID da manutenção inválido');
            return;
        }
        
        try {
            const result = await apiRequest(`/maintenances/${id}`);
            
            if (result && result.success && result.data) {
                const m = result.data;
                currentMaintenanceId = id;
                
                const modalContent = document.getElementById('detailModalContent');
                if (modalContent) {
                    modalContent.innerHTML = `
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label><i class="fas fa-wrench"></i> Título</label>
                                <div class="value">${escapeHtml(m.title)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-car"></i> Veículo</label>
                                <div class="value">${escapeHtml(m.brand)} ${escapeHtml(m.model)} (${escapeHtml(m.plate)})</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-calendar"></i> Data Agendada</label>
                                <div class="value">${formatDate(m.scheduled_date)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-flag"></i> Prioridade</label>
                                <div class="value"><span class="priority-badge priority-${m.priority}">${getPriorityText(m.priority)}</span></div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-chart-line"></i> Status</label>
                                <div class="value"><span class="status-badge status-${m.status}">${getStatusText(m.status)}</span></div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-euro-sign"></i> Custo</label>
                                <div class="value">${formatMoney(m.cost)}</div>
                            </div>
                            ${m.description ? `
                                <div class="detail-item full-width">
                                    <label><i class="fas fa-align-left"></i> Descrição</label>
                                    <div class="value">${escapeHtml(m.description)}</div>
                                </div>
                            ` : ''}
                            ${m.workshop_name ? `
                                <div class="detail-item">
                                    <label><i class="fas fa-building"></i> Oficina</label>
                                    <div class="value">${escapeHtml(m.workshop_name)}</div>
                                </div>
                            ` : ''}
                            ${m.odometer_at_maintenance ? `
                                <div class="detail-item">
                                    <label><i class="fas fa-road"></i> KM na Manutenção</label>
                                    <div class="value">${formatNumber(m.odometer_at_maintenance)} km</div>
                                </div>
                            ` : ''}
                            ${m.completion_date ? `
                                <div class="detail-item">
                                    <label><i class="fas fa-check-circle"></i> Data Conclusão</label>
                                    <div class="value">${formatDate(m.completion_date)}</div>
                                </div>
                            ` : ''}
                            <div class="detail-item full-width">
                                <label><i class="fas fa-calendar-alt"></i> Criado em</label>
                                <div class="value">${formatDateTime(m.created_at)}</div>
                            </div>
                        </div>
                    `;
                }
                
                openModal('viewMaintenanceModal');
            } else {
                showToast('error', result?.message || 'Erro ao carregar detalhes');
            }
        } catch (error) {
            console.error('[View Details Error]', error);
            showToast('error', 'Erro ao carregar detalhes');
        }
    };
    
    window.editMaintenance = async function(id) {
        if (!id || isNaN(id)) {
            showToast('error', 'ID da manutenção inválido');
            return;
        }
        
        try {
            const result = await apiRequest(`/maintenances/${id}`);
            
            if (result && result.success && result.data) {
                const m = result.data;
                currentMaintenanceId = id;
                
                document.getElementById('editMaintenanceId').value = m.id;
                document.getElementById('editTitle').value = m.title || '';
                document.getElementById('editDescription').value = m.description || '';
                document.getElementById('editScheduledDate').value = m.scheduled_date || '';
                document.getElementById('editPriority').value = m.priority || 'medium';
                document.getElementById('editWorkshopName').value = m.workshop_name || '';
                document.getElementById('editCost').value = m.cost || '';
                
                closeModal('viewMaintenanceModal');
                openModal('editMaintenanceModal');
            } else {
                showToast('error', result?.message || 'Erro ao carregar dados');
            }
        } catch (error) {
            console.error('[Edit Error]', error);
            showToast('error', 'Erro ao carregar dados');
        }
    };
    
    window.completeMaintenance = function(id) {
        if (!id || isNaN(id)) {
            showToast('error', 'ID da manutenção inválido');
            return;
        }
        currentMaintenanceId = id;
        document.getElementById('completeCost').value = '';
        document.getElementById('completeOdometer').value = '';
        openModal('completeMaintenanceModal');
    };
    
    window.confirmCompleteMaintenance = function() {
        const id = currentMaintenanceId;
        if (!id || isNaN(id)) {
            showToast('error', 'ID da manutenção inválido');
            return;
        }
        
        const cost = document.getElementById('completeCost').value;
        const odometer = document.getElementById('completeOdometer').value;
        completeMaintenanceAction(id, cost, odometer);
    };
    
    window.deleteMaintenance = function(id) {
        if (!id || isNaN(id)) {
            showToast('error', 'ID da manutenção inválido');
            return;
        }
        currentMaintenanceId = id;
        openModal('deleteConfirmModal');
    };
    
    window.confirmDeleteMaintenance = function() {
        const id = currentMaintenanceId;
        if (!id || isNaN(id)) {
            showToast('error', 'ID da manutenção inválido');
            return;
        }
        deleteMaintenanceAction(id);
    };
    
    window.closeModal = closeModal;
    
    // FORMULÁRIOS
    
    function resetAddForm() {
        const form = document.getElementById('addMaintenanceForm');
        if (form) form.reset();
        const vehicleSelect = document.getElementById('addVehicleId');
        if (vehicleSelect) vehicleSelect.value = '';
        const dateInput = document.getElementById('addScheduledDate');
        if (dateInput) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            dateInput.value = tomorrow.toISOString().split('T')[0];
        }
        const prioritySelect = document.getElementById('addPriority');
        if (prioritySelect) prioritySelect.value = 'medium';
    }
    
    // Formulário de Adicionar
    const addForm = document.getElementById('addMaintenanceForm');
    if (addForm) {
        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                vehicle_id: document.getElementById('addVehicleId').value,
                title: document.getElementById('addTitle').value.trim(),
                description: document.getElementById('addDescription').value,
                scheduled_date: document.getElementById('addScheduledDate').value,
                priority: document.getElementById('addPriority').value,
                workshop_name: document.getElementById('addWorkshopName').value,
                cost: document.getElementById('addCost').value ? parseFloat(document.getElementById('addCost').value) : null
            };
            
            if (!formData.vehicle_id || !formData.title || !formData.scheduled_date) {
                showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
                return;
            }
            
            await createMaintenance(formData);
        });
    }
    
    // Formulário de Editar
    const editForm = document.getElementById('editMaintenanceForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const id = document.getElementById('editMaintenanceId').value;
            if (!id || isNaN(id)) {
                showToast('error', 'ID da manutenção inválido');
                return;
            }
            
            const formData = {
                title: document.getElementById('editTitle').value.trim(),
                description: document.getElementById('editDescription').value,
                scheduled_date: document.getElementById('editScheduledDate').value,
                priority: document.getElementById('editPriority').value,
                workshop_name: document.getElementById('editWorkshopName').value,
                cost: document.getElementById('editCost').value ? parseFloat(document.getElementById('editCost').value) : null
            };
            
            if (!formData.title || !formData.scheduled_date) {
                showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
                return;
            }
            
            await updateMaintenance(id, formData);
        });
    }
    
    // INICIALIZAÇÕES ADICIONAIS
    
    function initSearch() {
        const searchInput = document.getElementById('globalSearch');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    state.currentSearch = e.target.value;
                    state.currentPage = 1;
                    loadMaintenances();
                }, 500);
            });
        }
    }
    
    async function logout() {
        try {
            await apiRequest('/auth/logout', { method: 'POST' });
        } catch (error) {
            console.error('[CARDOXIS] Erro no logout:', error);
        }
        localStorage.removeItem('auth_token');
        sessionStorage.removeItem('auth_token');
        window.location.href = '/cardoxis/login';
    }
    
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
        
        const logoutBtn = document.getElementById('logoutSidebarBtn');
        if (logoutBtn) logoutBtn.addEventListener('click', (e) => { e.preventDefault(); logout(); });
    }
    
    // INICIALIZAÇÃO PRINCIPAL
    
    async function init() {
        console.log('[CARDOXIS] Inicializando Maintenance ');
        
        const token = getToken();
        if (!token) {
            console.warn('[CARDOXIS] Token não encontrado, redirecionando para login...');
            window.location.href = '/cardoxis/login';
            return;
        }
        
        initSidebar();
        initSearch();
        
        await loadVehicles();
        await loadStats();
        await loadMaintenances();
        
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            loadStats();
            loadMaintenances(true);
            updateLastUpdateTime();
        }, CONFIG.REFRESH_INTERVAL);
        
        console.log('[CARDOXIS] Maintenance inicializado com sucesso!');
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();