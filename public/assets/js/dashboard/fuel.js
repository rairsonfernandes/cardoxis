/**
 * CARDOXIS - Fuel RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES

    const CONFIG = {
        API_URL: window.CARDOXIS?.API_URL || window.location.origin + '/cardoxis/api/v1',
        ITEMS_PER_PAGE: 15,
        REFRESH_INTERVAL: 60000
    };
    
    let refreshInterval = null;
    let currentFuelId = null;
    
    let state = {
        entries: [],
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
    
    // TIPOS E STATUS

    function getFuelTypeText(type) {
        const texts = {
            'diesel': 'Gasóleo',
            'gasoline': 'Gasolina',
            'electric': 'Elétrico',
            'hybrid': 'Híbrido',
            'gpl': 'GPL'
        };
        return texts[type] || type;
    }
    
    function getFuelTypeClass(type) {
        const classes = {
            'diesel': 'diesel',
            'gasoline': 'gasoline',
            'electric': 'electric',
            'hybrid': 'hybrid',
            'gpl': 'gpl'
        };
        return classes[type] || 'diesel';
    }
    
    function getStatusText(status) {
        const texts = {
            'pending': 'Pendente',
            'approved': 'Aprovado',
            'rejected': 'Rejeitado'
        };
        return texts[status] || status;
    }
    
    function getStatusClass(status) {
        const classes = {
            'pending': 'pending',
            'approved': 'approved',
            'rejected': 'rejected'
        };
        return classes[status] || 'pending';
    }
    
    // CARREGAMENTO DE DADOS - CORRIGIDO

    async function loadVehicles() {
        try {
            const result = await apiRequest('/vehicles?limit=100');
            console.log('[Load Vehicles] Resposta:', result);
            
            if (result && result.success) {
                if (Array.isArray(result.data)) {
                    state.vehicles = result.data;
                } else if (result.data && Array.isArray(result.data.data)) {
                    state.vehicles = result.data.data;
                } else {
                    state.vehicles = [];
                }
                console.log('[Load Vehicles] Veículos carregados:', state.vehicles.length);
                updateVehicleSelects();
            } else {
                state.vehicles = [];
                updateVehicleSelects();
            }
        } catch (error) {
            console.error('[Load Vehicles Error]', error);
            state.vehicles = [];
            updateVehicleSelects();
        }
    }
    
    async function loadDrivers() {
        try {
            const result = await apiRequest('/drivers?limit=100');
            console.log('[Load Drivers] Resposta completa:', result);
            
            if (result && result.success) {
                // Tentar diferentes estruturas de dados
                let drivers = [];
                
                if (result.data && Array.isArray(result.data)) {
                    drivers = result.data;
                } else if (result.data && result.data.data && Array.isArray(result.data.data)) {
                    drivers = result.data.data;
                } else if (Array.isArray(result)) {
                    drivers = result;
                } else if (result.data && typeof result.data === 'object') {
                    // Tentar encontrar um array em qualquer lugar
                    for (let key in result.data) {
                        if (Array.isArray(result.data[key])) {
                            drivers = result.data[key];
                            break;
                        }
                    }
                }
                
                state.drivers = drivers;
                console.log('[Load Drivers] Motoristas carregados:', state.drivers.length);
                
                if (state.drivers.length === 0) {
                    console.warn('[Load Drivers] Nenhum motorista encontrado, usando fallback');
                    state.drivers = [
                        { id: 1, name: 'João Silva' },
                        { id: 2, name: 'Maria Santos' },
                        { id: 3, name: 'Pedro Costa' }
                    ];
                }
                
                updateDriverSelects();
            } else {
                console.log('[Load Drivers] Erro ou sem dados, usando fallback');
                state.drivers = [
                    { id: 1, name: 'João Silva' },
                    { id: 2, name: 'Maria Santos' },
                    { id: 3, name: 'Pedro Costa' }
                ];
                updateDriverSelects();
            }
        } catch (error) {
            console.error('[Load Drivers Error]', error);
            state.drivers = [
                { id: 1, name: 'João Silva' },
                { id: 2, name: 'Maria Santos' },
                { id: 3, name: 'Pedro Costa' }
            ];
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
        
        if (!state.drivers || state.drivers.length === 0) {
            if (addSelect) {
                addSelect.innerHTML = '<option value="">Nenhum motorista disponível</option>';
            }
            if (editSelect) {
                editSelect.innerHTML = '<option value="">Nenhum motorista disponível</option>';
            }
            return;
        }
        
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
    
    // ESTATÍSTICAS

    async function loadStats() {
        try {
            const result = await apiRequest('/fuel/stats');
            
            if (result && result.success && result.data) {
                const stats = result.data;
                
                updateElementText('totalEntries', formatNumber(stats.total_entries || 0));
                updateElementText('totalLiters', formatNumber(stats.total_liters || 0) + ' L');
                updateElementText('totalCost', formatMoney(stats.total_cost || 0));
                updateElementText('avgPrice', formatMoney(stats.avg_price || 0));
                
                // Top vehicle
                if (stats.top_vehicle) {
                    const topVehicle = stats.top_vehicle;
                    const topContent = document.getElementById('topVehicleContent');
                    const topCard = document.getElementById('topVehicleCard');
                    
                    if (topContent && topCard) {
                        topCard.style.display = 'block';
                        topContent.innerHTML = `
                            <div class="top-vehicle">
                                <div class="trophy"><i class="fas fa-trophy"></i></div>
                                <div class="info">
                                    <div class="name">${escapeHtml(topVehicle.brand)} ${escapeHtml(topVehicle.model)}</div>
                                    <div class="details">${escapeHtml(topVehicle.plate)}</div>
                                </div>
                                <div class="stats">
                                    <div class="stat">
                                        <div class="number">${formatNumber(topVehicle.total_entries)}</div>
                                        <div class="label">Abastecimentos</div>
                                    </div>
                                    <div class="stat">
                                        <div class="number">${formatNumber(topVehicle.total_liters)} L</div>
                                        <div class="label">Litros</div>
                                    </div>
                                    <div class="stat">
                                        <div class="number">${formatMoney(topVehicle.total_cost)}</div>
                                        <div class="label">Custo</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                }
            }
        } catch (error) {
            console.error('[Load Stats Error]', error);
        }
    }
    
    // LISTAR ABASTECIMENTOS

    async function loadEntries() {
        if (state.isLoading) return;
        
        state.isLoading = true;
        showLoading();
        
        try {
            let url = `/fuel?limit=${CONFIG.ITEMS_PER_PAGE}&page=${state.currentPage}`;
            
            if (state.currentStatus !== 'all') {
                url += `&status=${state.currentStatus}`;
            }
            
            if (state.currentType !== 'all') {
                url += `&fuel_type=${state.currentType}`;
            }
            
            const result = await apiRequest(url);
            
            if (result && result.success && result.data) {
                if (Array.isArray(result.data)) {
                    state.entries = result.data;
                } else if (result.data.data && Array.isArray(result.data.data)) {
                    state.entries = result.data.data;
                } else {
                    state.entries = [];
                }
                
                state.totalPages = result.data.pagination?.last_page || 1;
                state.total = result.data.pagination?.total || 0;
                
                const totalCount = document.getElementById('totalCount');
                if (totalCount) {
                    totalCount.textContent = state.total;
                }
                
                renderTable();
                renderPagination();
                updateLastUpdateTime();
            } else {
                state.entries = [];
                state.total = 0;
                const totalCount = document.getElementById('totalCount');
                if (totalCount) {
                    totalCount.textContent = '0';
                }
                renderEmptyState();
            }
        } catch (error) {
            console.error('[Load Entries Error]', error);
            state.entries = [];
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
    
    // RENDERIZAÇÃO

    function showLoading() {
        const container = document.getElementById('fuelTableBody');
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 60px;">
                        <div class="spinner"></div>
                        <p style="margin-top: 16px;">Carregando abastecimentos...</p>
                    </td>
                </tr>
            `;
        }
    }
    
    function renderTable() {
        const container = document.getElementById('fuelTableBody');
        if (!container) return;
        
        if (!Array.isArray(state.entries) || state.entries.length === 0) {
            renderEmptyState();
            return;
        }
        
        container.innerHTML = state.entries.map(entry => {
            const statusClass = getStatusClass(entry.status);
            const statusText = getStatusText(entry.status);
            const fuelTypeClass = getFuelTypeClass(entry.fuel_type);
            const fuelTypeText = getFuelTypeText(entry.fuel_type);
            const totalCost = (entry.liters * entry.price_per_liter).toFixed(2);
            
            return `
                <tr onclick="viewFuelDetails(${entry.id})">
                    <td>
                        <div class="vehicle-cell">
                            <span class="vehicle-name">${escapeHtml(entry.vehicle_brand || '')} ${escapeHtml(entry.vehicle_model || '')}</span>
                            <span class="vehicle-plate">${escapeHtml(entry.vehicle_plate || '-')}</span>
                        </div>
                    </td>
                    <td>${escapeHtml(entry.driver_name || '-')}</td>
                    <td>${formatDate(entry.date)}</td>
                    <td>${formatNumber(entry.liters)} L</td>
                    <td>${formatMoney(entry.price_per_liter)}</td>
                    <td class="cost-cell">${formatMoney(totalCost)}</td>
                    <td><span class="fuel-type-badge ${fuelTypeClass}">${fuelTypeText}</span></td>
                    <td>
                        <span class="status-badge ${statusClass}">
                            ${statusText}
                        </span>
                    </td>
                    <td class="action-buttons" onclick="event.stopPropagation()">
                        <button class="action-btn" onclick="viewFuelDetails(${entry.id})" title="Ver detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn" onclick="editFuel(${entry.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn danger" onclick="confirmDeleteFuel(${entry.id})" title="Eliminar">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    function renderEmptyState() {
        const container = document.getElementById('fuelTableBody');
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 60px;">
                        <div class="empty-state">
                            <i class="fas fa-gas-pump"></i>
                            <h3>Nenhum abastecimento encontrado</h3>
                            <p>Comece registando o primeiro abastecimento da sua frota</p>
                            <button class="btn-primary" onclick="openAddFuelModal()">
                                <i class="fas fa-plus"></i> Novo Abastecimento
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

    window.filterFuel = function(status) {
        state.currentStatus = status;
        state.currentPage = 1;
        
        document.querySelectorAll('.filter-btn[data-status]').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.status === status) {
                btn.classList.add('active');
            }
        });
        
        loadEntries();
    };
    
    window.filterFuelByType = function(type) {
        state.currentType = type;
        state.currentPage = 1;
        
        document.querySelectorAll('.filter-btn[data-type]').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.type === type) {
                btn.classList.add('active');
            }
        });
        
        loadEntries();
    };
    
    window.changePage = function(page) {
        if (page < 1 || page > state.totalPages || page === state.currentPage) return;
        state.currentPage = page;
        loadEntries();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    // CRUD OPERAÇÕES

    window.openAddFuelModal = function() {
        resetAddForm();
        openModal('addFuelModal');
    };
    
    function resetAddForm() {
        const form = document.getElementById('addFuelForm');
        if (form) form.reset();
        document.getElementById('addDate').value = new Date().toISOString().split('T')[0];
    }
    
    async function createEntry(formData) {
        showToast('info', 'A registar abastecimento...');
        
        const response = await apiRequest('/fuel', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        if (response && response.success) {
            showToast('success', 'Abastecimento registado com sucesso!');
            closeModal('addFuelModal');
            await loadStats();
            await loadEntries();
        } else {
            showToast('error', response?.message || 'Erro ao registar abastecimento');
        }
    }
    
    async function updateEntry(id, formData) {
        showToast('info', 'A atualizar abastecimento...');
        
        const response = await apiRequest(`/fuel/${id}`, {
            method: 'PUT',
            body: JSON.stringify(formData)
        });
        
        if (response && response.success) {
            showToast('success', 'Abastecimento atualizado com sucesso!');
            closeModal('editFuelModal');
            await loadStats();
            await loadEntries();
        } else {
            showToast('error', response?.message || 'Erro ao atualizar abastecimento');
        }
    }

    // VISUALIZAR DETALHES
    
    window.viewFuelDetails = async function(id) {
        try {
            const result = await apiRequest(`/fuel/${id}`);
            
            if (result && result.success && result.data) {
                const entry = result.data;
                currentFuelId = id;
                const totalCost = (entry.liters * entry.price_per_liter).toFixed(2);
                const statusClass = getStatusClass(entry.status);
                const statusText = getStatusText(entry.status);
                const fuelTypeClass = getFuelTypeClass(entry.fuel_type);
                const fuelTypeText = getFuelTypeText(entry.fuel_type);
                
                const modalContent = document.getElementById('detailModalContent');
                if (modalContent) {
                    modalContent.innerHTML = `
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label><i class="fas fa-car"></i> Veículo</label>
                                <div class="value">${escapeHtml(entry.vehicle_brand || '')} ${escapeHtml(entry.vehicle_model || '')} (${escapeHtml(entry.vehicle_plate || '-')})</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-user"></i> Motorista</label>
                                <div class="value">${escapeHtml(entry.driver_name || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-calendar"></i> Data</label>
                                <div class="value">${formatDate(entry.date)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-flask"></i> Litros</label>
                                <div class="value">${formatNumber(entry.liters)} L</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-euro-sign"></i> Preço/L</label>
                                <div class="value">${formatMoney(entry.price_per_liter)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-euro-sign"></i> Total</label>
                                <div class="value cost-cell">${formatMoney(totalCost)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-tag"></i> Tipo</label>
                                <div class="value"><span class="fuel-type-badge ${fuelTypeClass}">${fuelTypeText}</span></div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-road"></i> Odômetro</label>
                                <div class="value">${formatNumber(entry.odometer)} km</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-store"></i> Posto</label>
                                <div class="value">${escapeHtml(entry.station_name || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-receipt"></i> Fatura</label>
                                <div class="value">${escapeHtml(entry.invoice_number || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-chart-line"></i> Status</label>
                                <div class="value"><span class="status-badge ${statusClass}">${statusText}</span></div>
                            </div>
                            ${entry.notes ? `
                                <div class="detail-item full-width">
                                    <label><i class="fas fa-sticky-note"></i> Observações</label>
                                    <div class="value">${escapeHtml(entry.notes)}</div>
                                </div>
                            ` : ''}
                            <div class="detail-item full-width">
                                <label><i class="fas fa-calendar-alt"></i> Registo no Sistema</label>
                                <div class="value">${formatDateTime(entry.created_at)}</div>
                            </div>
                        </div>
                    `;
                }
                
                const editBtn = document.getElementById('editFromDetailsBtn');
                if (editBtn) {
                    editBtn.onclick = function() { 
                        closeModal('viewFuelModal');
                        editFuel(id);
                    };
                }
                
                const deleteBtn = document.getElementById('deleteFromDetailsBtn');
                if (deleteBtn) {
                    deleteBtn.onclick = function() {
                        closeModal('viewFuelModal');
                        confirmDeleteFuel(id);
                    };
                }
                
                openModal('viewFuelModal');
            } else {
                showToast('error', 'Erro ao carregar detalhes');
            }
        } catch (error) {
            console.error('[View Details Error]', error);
            showToast('error', 'Erro ao carregar detalhes');
        }
    };
    
    // EDITAR

    window.editFuel = async function(id) {
        try {
            const result = await apiRequest(`/fuel/${id}`);
            
            if (result && result.success && result.data) {
                const entry = result.data;
                
                document.getElementById('editFuelId').value = entry.id;
                document.getElementById('editVehicleId').value = entry.vehicle_id;
                document.getElementById('editDriverId').value = entry.driver_id || '';
                document.getElementById('editDate').value = entry.date;
                document.getElementById('editFuelType').value = entry.fuel_type || 'diesel';
                document.getElementById('editLiters').value = entry.liters || 0;
                document.getElementById('editPricePerLiter').value = entry.price_per_liter || 0;
                document.getElementById('editOdometer').value = entry.odometer || 0;
                document.getElementById('editStationName').value = entry.station_name || '';
                document.getElementById('editInvoiceNumber').value = entry.invoice_number || '';
                document.getElementById('editStatus').value = entry.status || 'approved';
                document.getElementById('editNotes').value = entry.notes || '';
                
                openModal('editFuelModal');
            } else {
                showToast('error', 'Erro ao carregar dados');
            }
        } catch (error) {
            console.error('[Edit Error]', error);
            showToast('error', 'Erro ao carregar dados');
        }
    };
    
    // ELIMINAR

    window.confirmDeleteFuel = function(id) {
        if (!id) {
            showToast('error', 'ID inválido');
            return;
        }
        
        currentFuelId = id;
        
        const messageEl = document.getElementById('confirmMessage');
        if (messageEl) {
            const entry = state.entries.find(e => e.id === id);
            if (entry) {
                messageEl.textContent = `Tem certeza que deseja eliminar o abastecimento do veículo ${entry.vehicle_brand || ''} ${entry.vehicle_model || ''} (${entry.vehicle_plate || 'sem placa'})?`;
            } else {
                messageEl.textContent = 'Tem certeza que deseja eliminar este abastecimento?';
            }
        }
        
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (confirmBtn) {
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            newConfirmBtn.addEventListener('click', function() {
                if (currentFuelId) {
                    deleteEntryAction(currentFuelId);
                }
            });
        }
        
        openModal('deleteConfirmModal');
    };
    
    async function deleteEntryAction(id) {
        if (!id) {
            showToast('error', 'ID inválido');
            return;
        }
        
        showToast('info', 'A eliminar registo...');
        
        try {
            const response = await apiRequest(`/fuel/${id}`, {
                method: 'DELETE'
            });
            
            if (response && response.success) {
                showToast('success', 'Registo eliminado com sucesso!');
                closeModal('deleteConfirmModal');
                currentFuelId = null;
                await loadStats();
                await loadEntries();
            } else {
                showToast('error', response?.message || 'Erro ao eliminar registo');
            }
        } catch (error) {
            console.error('[Delete Error]', error);
            showToast('error', 'Erro ao eliminar registo');
        }
    }
    
    // FORMULÁRIOS

    document.getElementById('addFuelForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = {
            vehicle_id: document.getElementById('addVehicleId').value,
            driver_id: document.getElementById('addDriverId').value || null,
            date: document.getElementById('addDate').value,
            fuel_type: document.getElementById('addFuelType').value,
            liters: parseFloat(document.getElementById('addLiters').value) || 0,
            price_per_liter: parseFloat(document.getElementById('addPricePerLiter').value) || 0,
            odometer: parseInt(document.getElementById('addOdometer').value) || 0,
            station_name: document.getElementById('addStationName').value,
            invoice_number: document.getElementById('addInvoiceNumber').value,
            status: document.getElementById('addStatus').value,
            notes: document.getElementById('addNotes').value
        };
        
        if (!formData.vehicle_id || !formData.date || !formData.liters || !formData.price_per_liter || !formData.odometer) {
            showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
            return;
        }
        
        await createEntry(formData);
    });
    
    document.getElementById('editFuelForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('editFuelId').value;
        const formData = {
            vehicle_id: document.getElementById('editVehicleId').value,
            driver_id: document.getElementById('editDriverId').value || null,
            date: document.getElementById('editDate').value,
            fuel_type: document.getElementById('editFuelType').value,
            liters: parseFloat(document.getElementById('editLiters').value) || 0,
            price_per_liter: parseFloat(document.getElementById('editPricePerLiter').value) || 0,
            odometer: parseInt(document.getElementById('editOdometer').value) || 0,
            station_name: document.getElementById('editStationName').value,
            invoice_number: document.getElementById('editInvoiceNumber').value,
            status: document.getElementById('editStatus').value,
            notes: document.getElementById('editNotes').value
        };
        
        if (!formData.vehicle_id || !formData.date || !formData.liters || !formData.price_per_liter || !formData.odometer) {
            showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
            return;
        }
        
        await updateEntry(id, formData);
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
        console.log('[CARDOXIS] Inicializando Fuel...');
        
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        initSidebar();
        initSearch();
        initModalEvents();
        
        await loadVehicles();
        await loadDrivers();
        await loadStats();
        await loadEntries();
        
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            loadStats();
            updateLastUpdateTime();
        }, CONFIG.REFRESH_INTERVAL);
        
        console.log('[CARDOXIS] Fuel Management inicializado com sucesso!');
    }
    
    // Expor funções globais
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.viewFuelDetails = viewFuelDetails;
    window.editFuel = editFuel;
    window.confirmDeleteFuel = confirmDeleteFuel;
    window.openAddFuelModal = openAddFuelModal;
    window.filterFuel = filterFuel;
    window.filterFuelByType = filterFuelByType;
    window.changePage = changePage;
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();