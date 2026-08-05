/**
 * CARDOXIS - Vehicles RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES

    const CONFIG = {
        API_URL: window.location.origin + '/cardoxis/api/v1',
        ITEMS_PER_PAGE: 12,
        MAX_IMAGE_SIZE: 5 * 1024 * 1024,
        ALLOWED_IMAGE_TYPES: ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']
    };
    
    // Estado da aplicação
    let state = {
        vehicles: [],
        filteredVehicles: [],
        currentPage: 1,
        currentStatus: 'all',
        currentView: 'grid',
        currentSearch: '',
        totalPages: 1,
        isLoading: false,
        vehicleToDelete: null
    };
    
    // FUNÇÕES DE AUTENTICAÇÃO

    
    function getToken() {
        return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    }
    
    function getHeaders() {
        const token = getToken();
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': token ? `Bearer ${token}` : '',
            'X-CSRF-Token': window.CARDOXIS?.CSRF_TOKEN || ''
        };
    }
    
    function handleUnauthorized() {
        localStorage.removeItem('auth_token');
        sessionStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        sessionStorage.removeItem('user_data');
        window.location.href = '/cardoxis/login';
    }
    
    // TOAST NOTIFICATIONS

    
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
        
        setTimeout(() => {
            if (toast.parentElement) toast.remove();
        }, 5000);
    }
    
    // REQUISIÇÕES API
    
    async function apiRequest(endpoint, options = {}) {
        try {
            const response = await fetch(`${CONFIG.API_URL}${endpoint}`, {
                ...options,
                headers: getHeaders(),
                credentials: 'same-origin'
            });
            
            if (response.status === 401) {
                handleUnauthorized();
                return null;
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('[API Error]', endpoint, error);
            showToast('error', 'Erro na comunicação com o servidor');
            return null;
        }
    }
    
    // UPLOAD DE IMAGEM
    
    async function uploadVehicleImage(vehicleId, file) {
        const formData = new FormData();
        formData.append('image', file);
        
        try {
            const token = getToken();
            
            const response = await fetch(`${CONFIG.API_URL}/vehicles/${vehicleId}/image`, {
                method: 'POST',
                headers: {
                    'Authorization': token ? `Bearer ${token}` : ''
                },
                body: formData
            });
            
            if (response.status === 401) {
                handleUnauthorized();
                return null;
            }
            
            const data = await response.json();
            
            if (data && data.success) {
                console.log('Upload successful:', data);
                return data.data;
            } else {
                console.error('Upload failed:', data);
                showToast('error', data?.message || 'Erro ao fazer upload da imagem');
                return null;
            }
        } catch (error) {
            console.error('[Upload Error]', error);
            showToast('error', 'Erro ao fazer upload da imagem');
            return null;
        }
    }
    
    // CRUD OPERAÇÕES
    
    async function createVehicle(formData) {
        showToast('info', 'A criar veículo...');
        
        const vehicleData = {
            brand: formData.brand,
            model: formData.model,
            plate: formData.plate,
            year: formData.year,
            color: formData.color,
            fuel_type: formData.fuel_type,
            status: formData.status,
            odometer: formData.odometer,
            chassis: formData.chassis
        };
        
        const response = await apiRequest('/vehicles', {
            method: 'POST',
            body: JSON.stringify(vehicleData)
        });
        
        if (response && response.success) {
            const vehicleId = response.data.id;
            
            const imageFile = document.getElementById('addVehicleImage').files[0];
            if (imageFile && vehicleId) {
                const uploadResult = await uploadVehicleImage(vehicleId, imageFile);
                if (uploadResult) {
                    showToast('success', 'Veículo criado com imagem!');
                } else {
                    showToast('warning', 'Veículo criado, mas erro ao enviar imagem');
                }
            } else {
                showToast('success', 'Veículo criado com sucesso!');
            }
            
            closeModal('addVehicleModal');
            resetAddForm();
            await loadVehicles();
        } else {
            showToast('error', response?.message || 'Erro ao criar veículo');
        }
    }
    
    async function updateVehicle(id, formData) {
        showToast('info', 'A atualizar veículo...');
        
        const vehicleData = {
            brand: formData.brand,
            model: formData.model,
            plate: formData.plate,
            year: formData.year,
            color: formData.color,
            fuel_type: formData.fuel_type,
            status: formData.status,
            odometer: formData.odometer,
            chassis: formData.chassis
        };
        
        const response = await apiRequest(`/vehicles/${id}`, {
            method: 'PUT',
            body: JSON.stringify(vehicleData)
        });
        
        if (response && response.success) {
            const imageFile = document.getElementById('editVehicleImage').files[0];
            if (imageFile) {
                const uploadResult = await uploadVehicleImage(id, imageFile);
                if (uploadResult) {
                    showToast('success', 'Veículo atualizado com nova imagem!');
                } else {
                    showToast('warning', 'Veículo atualizado, mas erro ao enviar imagem');
                }
            } else {
                showToast('success', 'Veículo atualizado com sucesso!');
            }
            
            closeModal('editVehicleModal');
            await loadVehicles();
        } else {
            showToast('error', response?.message || 'Erro ao atualizar veículo');
        }
    }
    
    async function deleteVehicleById(id) {
        showToast('info', 'A eliminar veículo...');
        
        const response = await apiRequest(`/vehicles/${id}`, {
            method: 'DELETE'
        });
        
        if (response && response.success) {
            showToast('success', 'Veículo eliminado com sucesso!');
            closeModal('deleteConfirmModal');
            closeModal('vehicleDetailsModal');
            state.vehicleToDelete = null;
            await loadVehicles();
        } else {
            showToast('error', response?.message || 'Erro ao eliminar veículo');
        }
    }
    
    // CARREGAMENTO DE VEÍCULOS
    
    async function loadVehicles() {
        if (state.isLoading) return;
        
        state.isLoading = true;
        showLoading();
        
        try {
            const response = await apiRequest('/vehicles?limit=100');
            
            if (response && response.success && response.data) {
                state.vehicles = response.data;
                applyFilters();
                updateStats();
                updateFilterCounts();
                renderVehicles();
                updateLastUpdateTime();
            } else {
                state.vehicles = [];
                renderVehicles();
                showToast('warning', 'Nenhum veículo encontrado');
            }
        } catch (error) {
            console.error('[Load Error]', error);
            showToast('error', 'Erro ao carregar veículos');
            renderEmptyState();
        } finally {
            state.isLoading = false;
        }
    }
    
    function applyFilters() {
        let filtered = [...state.vehicles];
        
        if (state.currentStatus !== 'all') {
            filtered = filtered.filter(v => v.status === state.currentStatus);
        }
        
        if (state.currentSearch) {
            const search = state.currentSearch.toLowerCase();
            filtered = filtered.filter(v => 
                v.brand?.toLowerCase().includes(search) ||
                v.model?.toLowerCase().includes(search) ||
                v.plate?.toLowerCase().includes(search)
            );
        }
        
        state.filteredVehicles = filtered;
        state.totalPages = Math.ceil(filtered.length / CONFIG.ITEMS_PER_PAGE);
        
        if (state.currentPage > state.totalPages) {
            state.currentPage = Math.max(1, state.totalPages);
        }
    }
    
    // RENDERIZAÇÃO
    
    function showLoading() {
        const container = document.getElementById('vehiclesContainer');
        if (container) {
            container.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Carregando veículos...</p>
                </div>
            `;
        }
    }
    
    function renderVehicles() {
        const container = document.getElementById('vehiclesContainer');
        if (!container) return;
        
        const start = (state.currentPage - 1) * CONFIG.ITEMS_PER_PAGE;
        const end = start + CONFIG.ITEMS_PER_PAGE;
        const pageVehicles = state.filteredVehicles.slice(start, end);
        
        if (pageVehicles.length === 0) {
            renderEmptyState();
            return;
        }
        
        if (state.currentView === 'grid') {
            renderGridView(container, pageVehicles);
        } else {
            renderListView(container, pageVehicles);
        }
        
        renderPagination();
    }
    
    function renderGridView(container, vehicles) {
        container.innerHTML = `
            <div class="vehicles-grid">
                ${vehicles.map(vehicle => `
                    <div class="vehicle-card" onclick="viewVehicleDetails(${vehicle.id})">
                        <div class="vehicle-card-badge">
                            <span class="status-badge status-${getStatusClass(vehicle.status)}">
                                <i class="fas ${getStatusIcon(vehicle.status)}"></i>
                                ${getStatusText(vehicle.status)}
                            </span>
                        </div>
                        <div class="vehicle-card-image">
                            ${vehicle.image_url ? 
                                `<img src="${vehicle.image_url}?t=${Date.now()}" alt="${vehicle.brand} ${vehicle.model}" onerror="this.src='/cardoxis/public/assets/img/vehicles/placeholder.png'">` :
                                `<div class="vehicle-image-placeholder"><i class="fas fa-truck"></i></div>`
                            }
                        </div>
                        <div class="vehicle-card-content">
                            <h3 class="vehicle-card-title">${escapeHtml(vehicle.brand)} ${escapeHtml(vehicle.model)}</h3>
                            <div class="vehicle-card-plate">${escapeHtml(vehicle.plate)}</div>
                            <div class="vehicle-card-details">
                                <div class="vehicle-card-detail">
                                    <i class="fas fa-calendar"></i>
                                    <span>${vehicle.year || '-'}</span>
                                </div>
                                <div class="vehicle-card-detail">
                                    <i class="fas fa-palette"></i>
                                    <span>${vehicle.color || '-'}</span>
                                </div>
                                <div class="vehicle-card-detail">
                                    <i class="fas fa-gas-pump"></i>
                                    <span>${getFuelLabel(vehicle.fuel_type)}</span>
                                </div>
                                <div class="vehicle-card-detail">
                                    <i class="fas fa-road"></i>
                                    <span>${formatNumber(vehicle.odometer || 0)} km</span>
                                </div>
                            </div>
                            <div class="vehicle-card-actions">
                                <button class="action-btn" onclick="event.stopPropagation(); editVehicle(${vehicle.id})">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="action-btn danger" onclick="event.stopPropagation(); deleteVehicle(${vehicle.id})">
                                    <i class="fas fa-trash-alt"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    function renderListView(container, vehicles) {
        container.innerHTML = `
            <div class="vehicles-list">
                <div class="vehicles-list-header">
                    <div>Imagem</div>
                    <div>Veículo</div>
                    <div>Matrícula</div>
                    <div>Ano</div>
                    <div>Combustível</div>
                    <div>KM</div>
                    <div>Status</div>
                    <div>Ações</div>
                </div>
                ${vehicles.map(vehicle => `
                    <div class="vehicles-list-item" onclick="viewVehicleDetails(${vehicle.id})">
                        <div class="vehicle-list-image">
                            ${vehicle.image_url ? 
                                `<img src="${vehicle.image_url}?t=${Date.now()}" alt="${vehicle.brand} ${vehicle.model}" onerror="this.src='/cardoxis/public/assets/img/vehicles/placeholder.png'">` :
                                `<div class="vehicle-image-placeholder small"><i class="fas fa-truck"></i></div>`
                            }
                        </div>
                        <div class="vehicle-list-info">
                            <div class="vehicle-list-name">${escapeHtml(vehicle.brand)} ${escapeHtml(vehicle.model)}</div>
                            <div class="vehicle-list-color">${vehicle.color || '-'}</div>
                        </div>
                        <div class="vehicle-list-plate">${escapeHtml(vehicle.plate)}</div>
                        <div>${vehicle.year || '-'}</div>
                        <div>${getFuelLabel(vehicle.fuel_type)}</div>
                        <div>${formatNumber(vehicle.odometer || 0)} km</div>
                        <div>
                            <span class="status-badge status-${getStatusClass(vehicle.status)}">
                                ${getStatusText(vehicle.status)}
                            </span>
                        </div>
                        <div class="vehicle-list-actions">
                            <button class="action-btn" onclick="event.stopPropagation(); editVehicle(${vehicle.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn danger" onclick="event.stopPropagation(); deleteVehicle(${vehicle.id})" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    function renderEmptyState() {
        const container = document.getElementById('vehiclesContainer');
        if (!container) return;
        
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-car"></i>
                <h3>Nenhum veículo encontrado</h3>
                <p>Comece adicionando o seu primeiro veículo à frota</p>
                <button class="btn-primary" onclick="openAddVehicleModal()">
                    <i class="fas fa-plus"></i> Adicionar Veículo
                </button>
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
    
    // ESTATÍSTICAS
    
    function updateStats() {
        const total = state.vehicles.length;
        const active = state.vehicles.filter(v => v.status === 'active').length;
        const maintenance = state.vehicles.filter(v => v.status === 'maintenance').length;
        const inactive = state.vehicles.filter(v => v.status === 'inactive').length;
        
        updateElementText('totalVehicles', formatNumber(total));
        updateElementText('activeVehicles', formatNumber(active));
        updateElementText('maintenanceVehicles', formatNumber(maintenance));
        updateElementText('inactiveVehicles', formatNumber(inactive));
    }
    
    function updateFilterCounts() {
        const total = state.vehicles.length;
        const active = state.vehicles.filter(v => v.status === 'active').length;
        const maintenance = state.vehicles.filter(v => v.status === 'maintenance').length;
        const inactive = state.vehicles.filter(v => v.status === 'inactive').length;
        
        updateElementText('filterAllCount', total);
        updateElementText('filterActiveCount', active);
        updateElementText('filterMaintenanceCount', maintenance);
        updateElementText('filterInactiveCount', inactive);
    }
    
    function updateLastUpdateTime() {
        const element = document.getElementById('lastUpdateTime');
        if (element) {
            element.textContent = new Date().toLocaleTimeString('pt-PT');
        }
    }
    
    function resetAddForm() {
        const form = document.getElementById('addVehicleForm');
        if (form) form.reset();
        removeAddImage();
    }
    
    // FUNÇÕES UTILITÁRIAS
    
    function formatNumber(num) {
        return num.toLocaleString('pt-PT');
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function updateElementText(id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    }
    
    function getStatusClass(status) {
        const classes = {
            'active': 'success',
            'maintenance': 'warning',
            'inactive': 'danger'
        };
        return classes[status] || 'info';
    }
    
    function getStatusIcon(status) {
        const icons = {
            'active': 'fa-check-circle',
            'maintenance': 'fa-tools',
            'inactive': 'fa-minus-circle'
        };
        return icons[status] || 'fa-question-circle';
    }
    
    function getStatusText(status) {
        const texts = {
            'active': 'Ativo',
            'maintenance': 'Manutenção',
            'inactive': 'Inativo'
        };
        return texts[status] || 'Desconhecido';
    }
    
    function getFuelLabel(fuelType) {
        const labels = {
            'diesel': 'Diesel',
            'gasoline': 'Gasolina',
            'electric': 'Elétrico',
            'hybrid': 'Híbrido'
        };
        return labels[fuelType?.toLowerCase()] || fuelType || '-';
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
    
    // SIDEBAR (DEFINIDA DENTRO DO ESCOPO)
    
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

    // EVENT HANDLERS (GLOBAIS)
    
    window.openAddVehicleModal = function() {
        resetAddForm();
        openModal('addVehicleModal');
    };
    
    window.filterVehicles = function(status) {
        state.currentStatus = status;
        state.currentPage = 1;
        
        document.querySelectorAll('.filter-status-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.status === status) {
                btn.classList.add('active');
            }
        });
        
        document.querySelectorAll('.stat-card').forEach(card => {
            card.classList.remove('active');
            if (card.dataset.status === status) {
                card.classList.add('active');
            }
        });
        
        applyFilters();
        renderVehicles();
    };
    
    window.setView = function(view) {
        state.currentView = view;
        state.currentPage = 1;
        
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.view === view) {
                btn.classList.add('active');
            }
        });
        
        renderVehicles();
    };
    
    window.changePage = function(page) {
        if (page < 1 || page > state.totalPages || page === state.currentPage) return;
        state.currentPage = page;
        renderVehicles();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    window.viewVehicleDetails = async function(id) {
        try {
            const response = await apiRequest(`/vehicles/${id}`);
            
            if (response && response.success && response.data) {
                const v = response.data;
                const modalContent = document.getElementById('modalContent');
                const modalTitle = document.getElementById('modalTitle');
                
                if (modalTitle) modalTitle.textContent = `${v.brand} ${v.model}`;
                
                if (modalContent) {
                    modalContent.innerHTML = `
                        <div class="vehicle-details-container">
                            <div class="vehicle-details-image">
                                ${v.image_url ? 
                                    `<img src="${v.image_url}?t=${Date.now()}" alt="${v.brand} ${v.model}" onerror="this.src='/cardoxis/public/assets/img/vehicles/placeholder.png'">` :
                                    `<div class="vehicle-image-placeholder large"><i class="fas fa-truck"></i></div>`
                                }
                            </div>
                            <div class="vehicle-details-grid">
                                <div class="vehicle-details-field">
                                    <label><i class="fas fa-id-card"></i> Matrícula</label>
                                    <div class="value">${escapeHtml(v.plate)}</div>
                                </div>
                                <div class="vehicle-details-field">
                                    <label><i class="fas fa-calendar"></i> Ano</label>
                                    <div class="value">${v.year || '-'}</div>
                                </div>
                                <div class="vehicle-details-field">
                                    <label><i class="fas fa-palette"></i> Cor</label>
                                    <div class="value">${v.color || '-'}</div>
                                </div>
                                <div class="vehicle-details-field">
                                    <label><i class="fas fa-gas-pump"></i> Combustível</label>
                                    <div class="value">${getFuelLabel(v.fuel_type)}</div>
                                </div>
                                <div class="vehicle-details-field">
                                    <label><i class="fas fa-road"></i> Quilómetros</label>
                                    <div class="value">${formatNumber(v.odometer || 0)} km</div>
                                </div>
                                <div class="vehicle-details-field">
                                    <label><i class="fas fa-chart-line"></i> Status</label>
                                    <div class="value">
                                        <span class="status-badge status-${getStatusClass(v.status)}">
                                            ${getStatusText(v.status)}
                                        </span>
                                    </div>
                                </div>
                                <div class="vehicle-details-field full-width">
                                    <label><i class="fas fa-qrcode"></i> Número de Chassis (VIN)</label>
                                    <div class="value">${v.chassis || '-'}</div>
                                </div>
                                <div class="vehicle-details-field full-width">
                                    <label><i class="fas fa-calendar-alt"></i> Data de Registo</label>
                                    <div class="value">${v.created_at ? new Date(v.created_at).toLocaleDateString('pt-PT') : '-'}</div>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                const editBtn = document.getElementById('editFromDetailsBtn');
                if (editBtn) {
                    editBtn.onclick = () => editVehicle(id);
                }
                
                const deleteBtn = document.getElementById('deleteFromDetailsBtn');
                if (deleteBtn) {
                    deleteBtn.onclick = () => {
                        closeModal('vehicleDetailsModal');
                        deleteVehicle(id);
                    };
                }
                
                openModal('vehicleDetailsModal');
            } else {
                showToast('error', 'Erro ao carregar detalhes do veículo');
            }
        } catch (error) {
            console.error('[View Details Error]', error);
            showToast('error', 'Erro ao carregar detalhes do veículo');
        }
    };
    
    window.editVehicle = async function(id) {
        try {
            const response = await apiRequest(`/vehicles/${id}`);
            
            if (response && response.success && response.data) {
                const v = response.data;
                
                document.getElementById('editVehicleId').value = v.id;
                document.getElementById('editVehicleBrand').value = v.brand || '';
                document.getElementById('editVehicleModel').value = v.model || '';
                document.getElementById('editVehiclePlate').value = v.plate || '';
                document.getElementById('editVehicleYear').value = v.year || '';
                document.getElementById('editVehicleColor').value = v.color || '';
                document.getElementById('editVehicleFuel').value = v.fuel_type || 'diesel';
                document.getElementById('editVehicleStatus').value = v.status || 'active';
                document.getElementById('editVehicleKm').value = v.odometer || 0;
                document.getElementById('editVehicleChassis').value = v.chassis || '';
                
                const wrapper = document.getElementById('editImagePreviewWrapper');
                const placeholder = document.getElementById('editUploadPlaceholder');
                const preview = document.getElementById('editImagePreview');
                
                if (v.image_url && wrapper && preview) {
                    preview.src = v.image_url;
                    wrapper.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                } else {
                    if (wrapper) wrapper.style.display = 'none';
                    if (placeholder) placeholder.style.display = 'block';
                }
                
                closeModal('vehicleDetailsModal');
                openModal('editVehicleModal');
            } else {
                showToast('error', 'Erro ao carregar dados do veículo');
            }
        } catch (error) {
            console.error('[Edit Error]', error);
            showToast('error', 'Erro ao carregar dados do veículo');
        }
    };
    
    window.deleteVehicle = function(id) {
        state.vehicleToDelete = id;
        
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (confirmBtn) {
            confirmBtn.onclick = () => deleteVehicleById(id);
        }
        
        openModal('deleteConfirmModal');
    };
    
    window.confirmDeleteVehicle = function() {
        if (state.vehicleToDelete) {
            deleteVehicleById(state.vehicleToDelete);
        }
    };
    
    window.removeAddImage = function() {
        const wrapper = document.getElementById('addImagePreviewWrapper');
        const placeholder = document.getElementById('addUploadPlaceholder');
        const input = document.getElementById('addVehicleImage');
        if (wrapper) wrapper.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
        if (input) input.value = '';
    };
    
    window.removeEditImage = function() {
        const wrapper = document.getElementById('editImagePreviewWrapper');
        const placeholder = document.getElementById('editUploadPlaceholder');
        const input = document.getElementById('editVehicleImage');
        if (wrapper) wrapper.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
        if (input) input.value = '';
    };
    
    window.closeModal = closeModal;
    window.showToast = showToast;
    window.loadVehicles = loadVehicles;
    window.initSidebar = initSidebar; // EXPORTAR A FUNÇÃO PARA O ESCOPO GLOBAL
    
    // FORMULÁRIOS
    
    const addForm = document.getElementById('addVehicleForm');
    if (addForm) {
        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                brand: document.getElementById('addVehicleBrand').value.trim(),
                model: document.getElementById('addVehicleModel').value.trim(),
                plate: document.getElementById('addVehiclePlate').value.trim(),
                year: document.getElementById('addVehicleYear').value,
                color: document.getElementById('addVehicleColor').value,
                fuel_type: document.getElementById('addVehicleFuel').value,
                status: document.getElementById('addVehicleStatus').value,
                odometer: parseInt(document.getElementById('addVehicleKm').value) || 0,
                chassis: document.getElementById('addVehicleChassis').value
            };
            
            if (!formData.brand || !formData.model || !formData.plate) {
                showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
                return;
            }
            
            await createVehicle(formData);
        });
    }
    
    const editForm = document.getElementById('editVehicleForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const id = document.getElementById('editVehicleId').value;
            const formData = {
                brand: document.getElementById('editVehicleBrand').value.trim(),
                model: document.getElementById('editVehicleModel').value.trim(),
                plate: document.getElementById('editVehiclePlate').value.trim(),
                year: document.getElementById('editVehicleYear').value,
                color: document.getElementById('editVehicleColor').value,
                fuel_type: document.getElementById('editVehicleFuel').value,
                status: document.getElementById('editVehicleStatus').value,
                odometer: parseInt(document.getElementById('editVehicleKm').value) || 0,
                chassis: document.getElementById('editVehicleChassis').value
            };
            
            if (!formData.brand || !formData.model || !formData.plate) {
                showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
                return;
            }
            
            await updateVehicle(id, formData);
        });
    }
    
    // Preview de imagem para adicionar

    const addImageInput = document.getElementById('addVehicleImage');
    if (addImageInput) {
        addImageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                if (!CONFIG.ALLOWED_IMAGE_TYPES.includes(file.type)) {
                    showToast('error', 'Tipo de arquivo não permitido');
                    addImageInput.value = '';
                    return;
                }
                if (file.size > CONFIG.MAX_IMAGE_SIZE) {
                    showToast('error', 'Imagem muito grande. Máximo 5MB');
                    addImageInput.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = (event) => {
                    const preview = document.getElementById('addImagePreview');
                    const wrapper = document.getElementById('addImagePreviewWrapper');
                    const placeholder = document.getElementById('addUploadPlaceholder');
                    if (preview) preview.src = event.target.result;
                    if (wrapper) wrapper.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    const editImageInput = document.getElementById('editVehicleImage');
    if (editImageInput) {
        editImageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                if (!CONFIG.ALLOWED_IMAGE_TYPES.includes(file.type)) {
                    showToast('error', 'Tipo de arquivo não permitido');
                    editImageInput.value = '';
                    return;
                }
                if (file.size > CONFIG.MAX_IMAGE_SIZE) {
                    showToast('error', 'Imagem muito grande. Máximo 5MB');
                    editImageInput.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = (event) => {
                    const preview = document.getElementById('editImagePreview');
                    const wrapper = document.getElementById('editImagePreviewWrapper');
                    const placeholder = document.getElementById('editUploadPlaceholder');
                    if (preview) preview.src = event.target.result;
                    if (wrapper) wrapper.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // BUSCA
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                state.currentSearch = e.target.value;
                state.currentPage = 1;
                applyFilters();
                renderVehicles();
            }, 300);
        });
    }
    
    // INICIALIZAÇÃO (DENTRO DO IIFE)
    
    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            const token = getToken();
            if (!token) {
                window.location.href = '/cardoxis/login';
                return;
            }
            
            initSidebar();
            loadVehicles();
        });
    } else {
        // DOM já está carregado
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
        } else {
            initSidebar();
            loadVehicles();
        }
    }
    
})();