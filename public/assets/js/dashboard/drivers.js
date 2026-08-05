/**
 * CARDOXIS - Drivers RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES

    const CONFIG = {
        API_URL: window.CARDOXIS?.API_URL || window.location.origin + '/cardoxis/api/v1',
        ITEMS_PER_PAGE: 12,
        REFRESH_INTERVAL: 60000
    };
    
    // ESTADO

    let state = {
        drivers: [],
        vehicles: [],
        currentPage: 1,
        currentStatus: 'all',
        totalPages: 1,
        isLoading: false,
        currentDriverId: null,
        isEditing: false
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

    function formatNumber(num) {
        if (!num && num !== 0) return '0';
        return num.toLocaleString('pt-PT');
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
        return date.toLocaleDateString('pt-PT') + ' ' + 
               date.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
    }
    
    function formatPhone(phone) {
        if (!phone) return '-';
        let cleaned = phone.replace(/\D/g, '');
        if (cleaned.length === 9) {
            return cleaned.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
        }
        return phone;
    }
    
    function getInitials(name) {
        if (!name) return '?';
        const parts = name.trim().split(' ');
        if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
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
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    function updateElementText(id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    }
    
    function updateLastUpdateTime() {
        const element = document.getElementById('lastUpdateTime');
        if (element) element.textContent = new Date().toLocaleTimeString('pt-PT');
    }
    
    // FUNÇÕES AUXILIARES

    function getStatusText(status) {
        const texts = { 'active': 'Ativo', 'inactive': 'Inativo' };
        return texts[status] || 'Desconhecido';
    }
    
    function getLicenseCategoryText(category) {
        const categories = {
            'AM': 'AM - Ciclomotores',
            'A1': 'A1 - Motociclos até 125cc',
            'A2': 'A2 - Motociclos até 35kW',
            'A': 'A - Motociclos',
            'B': 'B - Ligeiros',
            'B+E': 'B+E - Ligeiros c/ reboque',
            'C1': 'C1 - Pesados até 7.5t',
            'C1+E': 'C1+E - Pesados até 7.5t c/ reboque',
            'C': 'C - Pesados',
            'C+E': 'C+E - Pesados c/ reboque',
            'D1': 'D1 - Passageiros até 16 lugares',
            'D1+E': 'D1+E - Passageiros até 16 lugares c/ reboque',
            'D': 'D - Passageiros',
            'D+E': 'D+E - Passageiros c/ reboque'
        };
        return categories[category] || category;
    }
    
    function getDaysUntilExpiry(expiryDate) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const expiry = new Date(expiryDate);
        expiry.setHours(0, 0, 0, 0);
        return Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
    }
    
    function getExpiryClass(daysUntilExpiry) {
        if (daysUntilExpiry < 0) return 'urgent';
        if (daysUntilExpiry <= 15) return 'urgent';
        if (daysUntilExpiry <= 30) return 'warning';
        return 'success';
    }
    
    function getExpiryText(daysUntilExpiry) {
        if (daysUntilExpiry < 0) return 'Expirada';
        if (daysUntilExpiry <= 15) return `Expira em ${daysUntilExpiry} dias`;
        if (daysUntilExpiry <= 30) return `Expira em ${daysUntilExpiry} dias`;
        return 'Válida';
    }
    
    // CARREGAMENTO DE VEÍCULOS - REGRA DE NEGÓCIO

    async function loadVehicles(driverId = null) {
        try {
            let url = '/drivers/vehicles/available';
            if (driverId) {
                url += `?driver_id=${driverId}`;
            }
            
            console.log('[CARDOXIS] Carregando veículos:', url);
            
            const result = await apiRequest(url);
            console.log('[CARDOXIS] Resposta:', result);
            
            if (result && result.success) {
                const vehiclesData = result.data?.data || result.data || [];
                state.vehicles = vehiclesData;
                
                console.log('[CARDOXIS] Veículos carregados:', state.vehicles.length);
                console.log('[CARDOXIS] Modo:', result.data?.mode || 'unknown');
                console.log('[CARDOXIS] Mensagem:', result.data?.message || '');
                
                updateVehicleSelects();
                return true;
            } else {
                console.warn('[Load Vehicles] Nenhum veículo disponível');
                state.vehicles = [];
                updateVehicleSelects();
                return false;
            }
        } catch (error) {
            console.error('[Load Vehicles Error]', error);
            state.vehicles = [];
            updateVehicleSelects();
            return false;
        }
    }
    
    function updateVehicleSelects() {
        const addSelect = document.getElementById('addVehicleId');
        const editSelect = document.getElementById('editVehicleId');
        
        console.log('[CARDOXIS] Atualizando selects com', state.vehicles.length, 'veículos');
        
        if (!state.vehicles || state.vehicles.length === 0) {
            const msg = '<option value="">Nenhum veículo disponível para associação</option>';
            if (addSelect) addSelect.innerHTML = msg;
            if (editSelect) editSelect.innerHTML = msg;
            return;
        }
        
        // REGRA: Filtrar apenas veículos que podem ser selecionados
        const selectableVehicles = state.vehicles.filter(v => v.can_select == 1 || v.can_select === true);
        
        if (selectableVehicles.length === 0) {
            const msg = '<option value="">Nenhum veículo disponível para seleção</option>';
            if (addSelect) addSelect.innerHTML = msg;
            if (editSelect) editSelect.innerHTML = msg;
            return;
        }
        
        const options = selectableVehicles.map(vehicle => {
            let label = `${vehicle.brand} ${vehicle.model} - ${vehicle.plate}`;
            let icon = '';
            
            if (vehicle.availability_status === 'assigned_to_this_driver') {
                label += ' ✓ (Atual)';
                icon = '⭐ ';
            } else if (vehicle.availability_status === 'available') {
                label += ' 📌 (Disponível)';
                icon = '✅ ';
            }
            
            return `<option value="${vehicle.id}" data-status="${vehicle.availability_status || 'available'}" data-label="${vehicle.availability_label || ''}">${icon}${escapeHtml(label)}</option>`;
        }).join('');
        
        if (addSelect) {
            addSelect.innerHTML = options;
        }
        
        if (editSelect) {
            editSelect.innerHTML = options;
        }
    }
    
    // CARREGAMENTO DE DADOS

    async function loadStats() {
        try {
            const result = await apiRequest('/drivers?limit=1000');
            
            if (result && result.success && result.data) {
                const drivers = result.data.data || [];
                const active = drivers.filter(d => d.status === 'active').length;
                const inactive = drivers.filter(d => d.status === 'inactive').length;
                const expiringSoon = drivers.filter(d => {
                    const daysLeft = getDaysUntilExpiry(d.license_expiry_date);
                    return daysLeft <= 30 && daysLeft > 0;
                }).length;
                
                updateElementText('totalDrivers', formatNumber(drivers.length));
                updateElementText('activeDrivers', formatNumber(active));
                updateElementText('inactiveDrivers', formatNumber(inactive));
                updateElementText('expiringLicenses', formatNumber(expiringSoon));
                
                updateElementText('filterAllCount', formatNumber(drivers.length));
                updateElementText('filterActiveCount', formatNumber(active));
                updateElementText('filterInactiveCount', formatNumber(inactive));
            }
        } catch (error) {
            console.error('[Load Stats Error]', error);
        }
    }
    
    async function loadDrivers() {
        if (state.isLoading) return;
        
        state.isLoading = true;
        showLoading();
        
        try {
            let url = `/drivers?limit=${CONFIG.ITEMS_PER_PAGE}&page=${state.currentPage}`;
            
            if (state.currentStatus !== 'all') {
                url += `&status=${state.currentStatus}`;
            }
            
            const result = await apiRequest(url);
            
            if (result && result.success && result.data) {
                state.drivers = result.data.data || [];
                state.totalPages = result.data.pagination?.last_page || 1;
                renderDrivers();
                renderPagination();
                updateLastUpdateTime();
            } else {
                state.drivers = [];
                renderEmptyState();
            }
        } catch (error) {
            console.error('[Load Drivers Error]', error);
            state.drivers = [];
            renderEmptyState();
        } finally {
            state.isLoading = false;
        }
    }
    
    function showLoading() {
        const gridContainer = document.getElementById('driversGridContainer');
        const listContainer = document.getElementById('driversListContainer');
        
        const loadingHtml = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Carregando motoristas...</p>
            </div>
        `;
        
        if (gridContainer) gridContainer.innerHTML = loadingHtml;
        if (listContainer) listContainer.innerHTML = loadingHtml;
    }
    
    // RENDERIZAÇÃO

    function renderDrivers() {
        if (!state.drivers || state.drivers.length === 0) {
            renderEmptyState();
            return;
        }
        
        renderGridView();
        renderListView();
    }
    
    function renderGridView() {
        const container = document.getElementById('driversGridContainer');
        if (!container) return;
        
        container.innerHTML = state.drivers.map(driver => {
            const daysUntilExpiry = getDaysUntilExpiry(driver.license_expiry_date);
            const expiryClass = getExpiryClass(daysUntilExpiry);
            const expiryText = getExpiryText(daysUntilExpiry);
            
            return `
                <div class="driver-card" onclick="viewDriverDetails(${driver.id})">
                    <div class="driver-card-badge">
                        <span class="driver-card-status status-${driver.status}">
                            <i class="fas ${driver.status === 'active' ? 'fa-check-circle' : 'fa-user-slash'}"></i>
                            ${getStatusText(driver.status)}
                        </span>
                    </div>
                    <div class="driver-card-header">
                        <div class="driver-avatar-large">${getInitials(driver.name)}</div>
                    </div>
                    <div class="driver-card-content">
                        <h3 class="driver-card-name">${escapeHtml(driver.name)}</h3>
                        ${driver.email ? `<div class="driver-card-email"><i class="fas fa-envelope"></i> ${escapeHtml(driver.email)}</div>` : ''}
                        <div class="driver-card-details">
                            <div class="driver-card-detail">
                                <i class="fas fa-phone-alt"></i>
                                <span>${formatPhone(driver.phone)}</span>
                            </div>
                            <div class="driver-card-detail">
                                <i class="fas fa-id-card"></i>
                                <span>${escapeHtml(driver.license_number)}</span>
                            </div>
                            <div class="driver-card-detail">
                                <i class="fas fa-calendar-alt"></i>
                                <span>${formatDate(driver.license_expiry_date)}</span>
                            </div>
                            <div class="driver-card-detail">
                                <i class="fas fa-truck"></i>
                                <span>${driver.vehicles_count || 0} veículo(s)</span>
                            </div>
                        </div>
                        <div class="driver-card-footer">
                            <div class="expiry-warning ${expiryClass}">
                                <i class="fas ${daysUntilExpiry < 0 ? 'fa-exclamation-triangle' : 'fa-clock'}"></i>
                                ${expiryText}
                            </div>
                            <div class="driver-card-actions" onclick="event.stopPropagation()">
                                <button class="action-btn" onclick="viewDriverDetails(${driver.id})" title="Ver detalhes">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="action-btn" onclick="editDriver(${driver.id})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn danger" onclick="deleteDriver(${driver.id})" title="Eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    function renderListView() {
        const container = document.getElementById('driversListContainer');
        if (!container) return;
        
        container.innerHTML = `
            <div class="drivers-list-header">
                <div><i class="fas fa-user"></i> Motorista</div>
                <div><i class="fas fa-phone-alt"></i> Contacto</div>
                <div><i class="fas fa-id-card"></i> Carta</div>
                <div><i class="fas fa-calendar-alt"></i> Validade</div>
                <div><i class="fas fa-truck"></i> Veículos</div>
                <div><i class="fas fa-chart-line"></i> Status</div>
                <div><i class="fas fa-cog"></i> Ações</div>
            </div>
            ${state.drivers.map(driver => {
                const daysUntilExpiry = getDaysUntilExpiry(driver.license_expiry_date);
                const expiryClass = getExpiryClass(daysUntilExpiry);
                const expiryText = getExpiryText(daysUntilExpiry);
                
                return `
                    <div class="drivers-list-item" onclick="viewDriverDetails(${driver.id})">
                        <div class="driver-list-info">
                            <div class="driver-list-name">${escapeHtml(driver.name)}</div>
                            ${driver.email ? `<div class="driver-list-email"><i class="fas fa-envelope"></i> ${escapeHtml(driver.email)}</div>` : ''}
                        </div>
                        <div class="driver-list-phone">
                            <i class="fas fa-phone-alt"></i>
                            ${formatPhone(driver.phone)}
                        </div>
                        <div class="driver-list-license">
                            ${escapeHtml(driver.license_number)}
                            <small style="display: block; color: var(--gray); font-size: 0.65rem;">${getLicenseCategoryText(driver.license_category)}</small>
                        </div>
                        <div class="driver-list-expiry">
                            <div class="expiry-date">
                                <i class="fas fa-calendar-alt"></i>
                                ${formatDate(driver.license_expiry_date)}
                            </div>
                            <div class="expiry-warning ${expiryClass}">
                                <i class="fas ${daysUntilExpiry < 0 ? 'fa-exclamation-triangle' : 'fa-clock'}"></i>
                                ${expiryText}
                            </div>
                        </div>
                        <div>
                            <div class="driver-list-vehicles">
                                <i class="fas fa-truck"></i>
                                ${driver.vehicles_count || 0}
                            </div>
                        </div>
                        <div>
                            <span class="driver-list-status status-${driver.status}">
                                <i class="fas ${driver.status === 'active' ? 'fa-check-circle' : 'fa-user-slash'}"></i>
                                ${getStatusText(driver.status)}
                            </span>
                        </div>
                        <div class="driver-list-actions" onclick="event.stopPropagation()">
                            <button class="action-btn" onclick="viewDriverDetails(${driver.id})" title="Ver detalhes">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn" onclick="editDriver(${driver.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn danger" onclick="deleteDriver(${driver.id})" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('')}
        `;
    }
    
    function renderEmptyState() {
        const gridContainer = document.getElementById('driversGridContainer');
        const listContainer = document.getElementById('driversListContainer');
        
        const emptyHtml = `
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>Nenhum motorista encontrado</h3>
                <p>Comece adicionando o primeiro motorista da sua frota</p>
                <button class="btn-primary" onclick="openAddDriverModal()">
                    <i class="fas fa-plus"></i> Adicionar Motorista
                </button>
            </div>
        `;
        
        if (gridContainer) gridContainer.innerHTML = emptyHtml;
        if (listContainer) listContainer.innerHTML = emptyHtml;
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
    
    // FUNÇÕES DE SELEÇÃO DE VEÍCULOS

    function updateVehicleTags(selectId, containerId) {
        const select = document.getElementById(selectId);
        const container = document.getElementById(containerId);
        
        if (!select || !container) return;
        
        const selectedOptions = Array.from(select.selectedOptions);
        
        if (selectedOptions.length === 0) {
            container.innerHTML = '<span class="no-vehicles">Nenhum veículo selecionado</span>';
            return;
        }
        
        container.innerHTML = selectedOptions.map(opt => {
            const isCurrent = opt.dataset?.status === 'assigned_to_this_driver';
            const label = opt.dataset?.label || '';
            return `<span class="vehicle-tag ${isCurrent ? 'current-vehicle' : ''}">
                <i class="fas fa-truck"></i>
                ${opt.text}
                ${isCurrent ? '<span class="badge-current">✓ Atual</span>' : ''}
                ${label ? `<span class="badge-status">${label}</span>` : ''}
                <button type="button" class="remove-vehicle-btn" onclick="removeVehicleFromSelect('${selectId}', '${opt.value}')">
                    <i class="fas fa-times"></i>
                </button>
            </span>`;
        }).join('');
    }
    
    function removeVehicleFromSelect(selectId, value) {
        const select = document.getElementById(selectId);
        if (!select) return;
        
        const option = Array.from(select.options).find(opt => opt.value === value);
        if (option) {
            option.selected = false;
            const containerId = selectId === 'addVehicleId' ? 'addSelectedVehicles' : 'editSelectedVehicles';
            updateVehicleTags(selectId, containerId);
        }
    }
    
    // AÇÕES

    window.setView = function(view) {
        const gridContainer = document.getElementById('driversGridContainer');
        const listContainer = document.getElementById('driversListContainer');
        const gridBtn = document.getElementById('viewGridBtn');
        const listBtn = document.getElementById('viewListBtn');
        
        if (view === 'grid') {
            if (gridContainer) gridContainer.style.display = 'grid';
            if (listContainer) listContainer.style.display = 'none';
            if (gridBtn) gridBtn.classList.add('active');
            if (listBtn) listBtn.classList.remove('active');
        } else {
            if (gridContainer) gridContainer.style.display = 'none';
            if (listContainer) listContainer.style.display = 'block';
            if (gridBtn) gridBtn.classList.remove('active');
            if (listBtn) listBtn.classList.add('active');
        }
    };
    
    window.openAddDriverModal = function() {
        const form = document.getElementById('addDriverForm');
        if (form) form.reset();
        const statusSelect = document.getElementById('addStatus');
        if (statusSelect) statusSelect.value = 'active';
        
        // Resetar seleção de veículos
        const addSelect = document.getElementById('addVehicleId');
        if (addSelect) {
            Array.from(addSelect.options).forEach(opt => opt.selected = false);
            updateVehicleTags('addVehicleId', 'addSelectedVehicles');
        }
        
        // REGRA: Carregar veículos disponíveis (modo ADD - apenas veículos sem motorista)
        state.isEditing = false;
        loadVehicles();
        
        openModal('addDriverModal');
    };
    
    window.filterDrivers = function(status) {
        state.currentStatus = status;
        state.currentPage = 1;
        
        document.querySelectorAll('.filter-status-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.status === status) {
                btn.classList.add('active');
            }
        });
        
        loadDrivers();
    };
    
    window.changePage = function(page) {
        if (page < 1 || page > state.totalPages || page === state.currentPage) return;
        state.currentPage = page;
        loadDrivers();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    window.viewDriverDetails = async function(id) {
        if (!id) {
            showToast('error', 'ID do motorista inválido');
            return;
        }
        
        try {
            const result = await apiRequest(`/drivers/${id}`);
            
            if (result && result.success && result.data) {
                const d = result.data;
                state.currentDriverId = id;
                
                const vehiclePlates = d.vehicles_plates ? d.vehicles_plates.split(',').map(p => p.trim()) : [];
                const daysUntilExpiry = getDaysUntilExpiry(d.license_expiry_date);
                const expiryClass = getExpiryClass(daysUntilExpiry);
                const expiryText = getExpiryText(daysUntilExpiry);
                
                const modalContent = document.getElementById('detailModalContent');
                if (modalContent) {
                    modalContent.innerHTML = `
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label><i class="fas fa-user"></i> Nome</label>
                                <div class="value">${escapeHtml(d.name)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-id-card"></i> NIF</label>
                                <div class="value">${escapeHtml(d.document || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-phone-alt"></i> Telefone</label>
                                <div class="value">${formatPhone(d.phone) || '-'}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-envelope"></i> Email</label>
                                <div class="value">${escapeHtml(d.email) || '-'}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-id-card"></i> Carta</label>
                                <div class="value">${escapeHtml(d.license_number)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-tag"></i> Categoria</label>
                                <div class="value">${getLicenseCategoryText(d.license_category)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-calendar-alt"></i> Validade</label>
                                <div class="value">
                                    ${formatDate(d.license_expiry_date)}
                                    <div style="margin-top: 8px;"><span class="expiry-warning ${expiryClass}"><i class="fas ${daysUntilExpiry < 0 ? 'fa-exclamation-triangle' : 'fa-clock'}"></i> ${expiryText}</span></div>
                                </div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-chart-line"></i> Status</label>
                                <div class="value">
                                    <span class="driver-list-status status-${d.status}">
                                        <i class="fas ${d.status === 'active' ? 'fa-check-circle' : 'fa-user-slash'}"></i>
                                        ${getStatusText(d.status)}
                                    </span>
                                </div>
                            </div>
                            ${vehiclePlates.length > 0 ? `
                                <div class="detail-item full-width">
                                    <label><i class="fas fa-truck"></i> Veículos</label>
                                    <div class="vehicles-list-modal">
                                        ${vehiclePlates.map(plate => `<span class="vehicle-tag">${escapeHtml(plate)}</span>`).join('')}
                                    </div>
                                </div>
                            ` : ''}
                            <div class="detail-item full-width">
                                <label><i class="fas fa-calendar-alt"></i> Registo</label>
                                <div class="value">${formatDateTime(d.created_at)}</div>
                            </div>
                        </div>
                    `;
                }
                
                const editBtn = document.getElementById('editFromDetailsBtn');
                if (editBtn) {
                    editBtn.onclick = function() { 
                        closeModal('viewDriverModal');
                        editDriver(id);
                    };
                }
                
                openModal('viewDriverModal');
            } else {
                showToast('error', 'Erro ao carregar detalhes do motorista');
            }
        } catch (error) {
            console.error('[View Details Error]', error);
            showToast('error', 'Erro ao carregar detalhes do motorista');
        }
    };
    
    window.editDriver = async function(id) {
        if (!id) {
            showToast('error', 'ID do motorista inválido');
            return;
        }
        
        try {
            const result = await apiRequest(`/drivers/${id}`);
            
            if (result && result.success && result.data) {
                const d = result.data;
                state.isEditing = true;
                
                document.getElementById('editDriverId').value = d.id;
                document.getElementById('editName').value = d.name || '';
                document.getElementById('editDocument').value = d.document || '';
                document.getElementById('editPhone').value = d.phone || '';
                document.getElementById('editEmail').value = d.email || '';
                document.getElementById('editAddress').value = d.address || '';
                document.getElementById('editLicenseNumber').value = d.license_number || '';
                document.getElementById('editLicenseCategory').value = d.license_category || '';
                document.getElementById('editLicenseExpiryDate').value = d.license_expiry_date || '';
                document.getElementById('editLicenseIssueDate').value = d.license_issue_date || '';
                document.getElementById('editHireDate').value = d.hire_date || '';
                document.getElementById('editStatus').value = d.status || 'active';
                
                // REGRA: Carregar veículos disponíveis + os do motorista (modo EDIT)
                await loadVehicles(id);
                
                // Selecionar veículos do motorista
                const driverVehicleIds = await loadDriverVehicles(id);
                const editSelect = document.getElementById('editVehicleId');
                if (editSelect) {
                    Array.from(editSelect.options).forEach(opt => {
                        opt.selected = driverVehicleIds.includes(parseInt(opt.value));
                    });
                    updateVehicleTags('editVehicleId', 'editSelectedVehicles');
                }
                
                openModal('editDriverModal');
            } else {
                showToast('error', 'Erro ao carregar dados do motorista');
            }
        } catch (error) {
            console.error('[Edit Error]', error);
            showToast('error', 'Erro ao carregar dados do motorista');
        }
    };
    
    window.deleteDriver = function(id) {
        if (!id) {
            showToast('error', 'ID do motorista inválido');
            return;
        }
        
        state.currentDriverId = id;
        
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (confirmBtn) {
            confirmBtn.onclick = function() { 
                deleteDriverAction(id);
                closeModal('deleteConfirmModal');
            };
        }
        
        openModal('deleteConfirmModal');
    };
    
    window.closeModal = closeModal;
    window.openModal = openModal;
    window.removeVehicleFromSelect = removeVehicleFromSelect;
    
    // CRUD OPERAÇÕES

    async function createDriver(formData) {
        showToast('info', 'A adicionar motorista...');
        
        const addSelect = document.getElementById('addVehicleId');
        const vehicleIds = addSelect ? Array.from(addSelect.selectedOptions).map(opt => parseInt(opt.value)) : [];
        
        const data = {
            ...formData,
            vehicle_ids: vehicleIds
        };
        
        const response = await apiRequest('/drivers', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        
        if (response && response.success) {
            showToast('success', 'Motorista adicionado com sucesso!');
            closeModal('addDriverModal');
            await loadStats();
            await loadDrivers();
        } else {
            showToast('error', response?.message || 'Erro ao adicionar motorista');
        }
    }
    
    async function updateDriver(id, formData) {
        showToast('info', 'A atualizar motorista...');
        
        const editSelect = document.getElementById('editVehicleId');
        const vehicleIds = editSelect ? Array.from(editSelect.selectedOptions).map(opt => parseInt(opt.value)) : [];
        
        const data = {
            ...formData,
            vehicle_ids: vehicleIds
        };
        
        const response = await apiRequest(`/drivers/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
        
        if (response && response.success) {
            showToast('success', 'Motorista atualizado com sucesso!');
            closeModal('editDriverModal');
            await loadStats();
            await loadDrivers();
        } else {
            showToast('error', response?.message || 'Erro ao atualizar motorista');
        }
    }
    
    async function deleteDriverAction(id) {
        showToast('info', 'A eliminar motorista...');
        
        const response = await apiRequest(`/drivers/${id}`, {
            method: 'DELETE'
        });
        
        if (response && response.success) {
            showToast('success', 'Motorista eliminado com sucesso!');
            await loadStats();
            await loadDrivers();
        } else {
            showToast('error', response?.message || 'Erro ao eliminar motorista');
        }
    }
    
    async function loadDriverVehicles(driverId) {
        try {
            const result = await apiRequest(`/drivers/${driverId}/vehicles`);
            if (result && result.success && result.data) {
                return result.data.map(v => v.id);
            }
            return [];
        } catch (error) {
            console.error('[Load Driver Vehicles Error]', error);
            return [];
        }
    }
    
    // FORMULÁRIOS

    document.addEventListener('DOMContentLoaded', function() {
        const addSelect = document.getElementById('addVehicleId');
        if (addSelect) {
            addSelect.addEventListener('change', function() {
                updateVehicleTags('addVehicleId', 'addSelectedVehicles');
            });
        }
        
        const editSelect = document.getElementById('editVehicleId');
        if (editSelect) {
            editSelect.addEventListener('change', function() {
                updateVehicleTags('editVehicleId', 'editSelectedVehicles');
            });
        }
    });
    
    document.getElementById('addDriverForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = {
            name: document.getElementById('addName').value.trim(),
            document: document.getElementById('addDocument').value,
            phone: document.getElementById('addPhone').value,
            email: document.getElementById('addEmail').value,
            address: document.getElementById('addAddress').value,
            license_number: document.getElementById('addLicenseNumber').value.trim(),
            license_category: document.getElementById('addLicenseCategory').value,
            license_expiry_date: document.getElementById('addLicenseExpiryDate').value,
            license_issue_date: document.getElementById('addLicenseIssueDate').value,
            hire_date: document.getElementById('addHireDate').value,
            status: document.getElementById('addStatus').value
        };
        
        if (!formData.name || !formData.license_number || !formData.license_expiry_date) {
            showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
            return;
        }
        
        await createDriver(formData);
    });
    
    document.getElementById('editDriverForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('editDriverId').value;
        const formData = {
            name: document.getElementById('editName').value.trim(),
            document: document.getElementById('editDocument').value,
            phone: document.getElementById('editPhone').value,
            email: document.getElementById('editEmail').value,
            address: document.getElementById('editAddress').value,
            license_number: document.getElementById('editLicenseNumber').value.trim(),
            license_category: document.getElementById('editLicenseCategory').value,
            license_expiry_date: document.getElementById('editLicenseExpiryDate').value,
            license_issue_date: document.getElementById('editLicenseIssueDate').value,
            hire_date: document.getElementById('editHireDate').value,
            status: document.getElementById('editStatus').value
        };
        
        if (!formData.name || !formData.license_number || !formData.license_expiry_date) {
            showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
            return;
        }
        
        await updateDriver(id, formData);
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
    
    function initSearch() {
        const searchInput = document.getElementById('globalSearch');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const query = e.target.value.trim().toLowerCase();
                    if (query.length >= 2) {
                        console.log('[CARDOXIS] Busca:', query);
                    }
                }, 500);
            });
        }
    }
    
    // INICIALIZAÇÃO
    
    window.loadDrivers = loadDrivers;
    
    async function init() {
        console.log('[CARDOXIS] Inicializando Drivers v22.0.0 - Enterprise Ready...');
        
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        initSidebar();
        initSearch();
        
        await loadVehicles();
        await loadStats();
        await loadDrivers();
        
        setView('grid');
        
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            loadStats();
            updateLastUpdateTime();
        }, CONFIG.REFRESH_INTERVAL);
        
        console.log('[CARDOXIS] Drivers inicializado com sucesso!');
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();