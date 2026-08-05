/**
 * CARDOXIS Dashboard RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES GLOBAIS

    const CONFIG = {
        API_URL: window.API_URL || '/cardoxis/api/v1',
        REFRESH_INTERVAL: 60000
    };
    
    let refreshInterval = null;
    let vehiclesChart = null;
    let fuelChart = null;
    let currentVehicleId = null;

    // FUNÇÕES DE AUTENTICAÇÃO
    
    function getToken() {
        return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    }
    
    function removeToken() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        sessionStorage.removeItem('auth_token');
        sessionStorage.removeItem('user_data');
    }
    
    function handleUnauthorized() {
        removeToken();
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
                throw new Error(`HTTP ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('[API Error]', error);
            return { success: false, message: 'Erro na comunicação com o servidor' };
        }
    }
    
    // FUNÇÕES UTILITÁRIAS
    
    function formatNumber(num) {
        if (!num && num !== 0) return '0';
        return num.toLocaleString('pt-PT');
    }
    
    function formatKm(km) {
        return `${(km / 1000).toFixed(1)}k`;
    }
    
    function formatMoney(value) {
        return `€ ${value.toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
    
    function formatDate(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '—';
        return date.toLocaleDateString('pt-PT');
    }
    
    function formatDateTime(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '—';
        return date.toLocaleDateString('pt-PT') + ' ' + date.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
    }
    
    function getFuelLabel(fuelType) {
        const labels = { 
            'electric': 'Elétrico', 
            'hybrid': 'Híbrido', 
            'diesel': 'Gasóleo', 
            'gasoline': 'Gasolina' 
        };
        return labels[fuelType?.toLowerCase()] || fuelType || '-';
    }
    
    function getStatusClass(status) {
        const classes = { 'active': 'active', 'maintenance': 'maintenance', 'inactive': 'inactive' };
        return classes[status] || 'inactive';
    }
    
    function getStatusText(status) {
        const texts = { 'active': 'Ativo', 'maintenance': 'Manutenção', 'inactive': 'Inativo' };
        return texts[status] || 'Desconhecido';
    }
    
    function getStatusBadgeClass(status) {
        const classes = { 'active': 'success', 'maintenance': 'warning', 'inactive': 'danger' };
        return classes[status] || 'info';
    }
    
    function getAlertSeverityClass(severity) {
        const classes = { 'critical': 'critical', 'warning': 'warning', 'info': 'info' };
        return classes[severity] || 'info';
    }
    
    function getSeverityText(severity) {
        const texts = { 'critical': 'Crítico', 'warning': 'Atenção', 'info': 'Informativo' };
        return texts[severity] || severity;
    }
    
    function getAlertIcon(type) {
        const icons = { 
            'maintenance': 'fa-tools', 
            'document': 'fa-file-alt', 
            'payment': 'fa-credit-card', 
            'user': 'fa-user',
            'license': 'fa-id-card',
            'system': 'fa-server',
            'insurance': 'fa-shield-alt'
        };
        return icons[type] || 'fa-bell';
    }
    
    function getVehicleIcon(fuelType) {
        const icons = { 'electric': 'bolt', 'hybrid': 'leaf', 'diesel': 'truck', 'gasoline': 'gas-pump' };
        return icons[fuelType?.toLowerCase()] || 'truck';
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
    
    function updateProgressBar(id, percent) {
        const element = document.getElementById(id);
        if (element) element.style.width = `${Math.min(100, Math.max(0, percent))}%`;
    }
    
    function updateLastUpdateTime() {
        const element = document.getElementById('lastUpdateTime');
        if (element) {
            element.textContent = new Date().toLocaleTimeString('pt-PT');
        }
    }
    
    // MODAL FUNCTIONS
    
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
        setTimeout(() => toast.remove(), 5000);
    }
    
    // CARREGAMENTO DE DADOS - ESTATÍSTICAS
    
    async function loadDashboardStats() {
        try {
            const result = await apiRequest('/dashboard/stats');
            
            if (result && result.success && result.data) {
                const stats = result.data;
                
                updateElementText('totalVehicles', formatNumber(stats.total_vehicles || 0));
                updateElementText('activeVehicles', formatNumber(stats.active_vehicles || 0));
                updateElementText('pendingMaintenance', formatNumber(stats.pending_maintenance || 0));
                updateElementText('totalAlerts', formatNumber(stats.active_alerts || 0));
                updateElementText('totalKm', formatKm(stats.total_km || 0));
                updateElementText('maintenanceCost', formatMoney(stats.maintenance_cost || 0));
                updateElementText('totalDrivers', formatNumber(stats.total_drivers || 0));
                
                const totalVehicles = stats.total_vehicles || 0;
                const activeVehicles = stats.active_vehicles || 0;
                const activePercent = totalVehicles > 0 ? Math.round((activeVehicles / totalVehicles) * 100) : 0;
                
                updateProgressBar('activeVehiclesProgress', activePercent);
                updateElementText('activeVehiclesPercent', `${activePercent}%`);
                
                updateElementText('alertCount', stats.active_alerts || 0);
                updateElementText('maintenanceCount', stats.pending_maintenance || 0);
                updateElementText('alertsCount', stats.active_alerts || 0);
                
                updateLastUpdateTime();
                
                return stats;
            }
        } catch (error) {
            console.error('[Stats Error]', error);
            return null;
        }
        return null;
    }
    
    // CARREGAMENTO DE DADOS - GRÁFICOS
    
    async function loadVehicleGrowthChart() {
        try {
            const ctx = document.getElementById('vehiclesChart')?.getContext('2d');
            if (!ctx) return;
            
            const result = await apiRequest('/dashboard/vehicles-growth');
            
            let labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            let data = new Array(12).fill(0);
            
            if (result && result.success && result.data && result.data.length > 0) {
                const monthMap = {
                    'Jan': 0, 'Fev': 1, 'Mar': 2, 'Abr': 3, 'Mai': 4, 'Jun': 5,
                    'Jul': 6, 'Ago': 7, 'Set': 8, 'Out': 9, 'Nov': 10, 'Dez': 11
                };
                result.data.forEach(item => {
                    const idx = monthMap[item.month];
                    if (idx !== undefined) data[idx] = item.count;
                });
            } else if (window.vehicleGrowthData && window.vehicleGrowthData.length > 0) {
                labels = window.vehicleGrowthData.map(item => item.month);
                data = window.vehicleGrowthData.map(item => item.count);
            }
            
            if (vehiclesChart) {
                vehiclesChart.destroy();
                vehiclesChart = null;
            }
            
            vehiclesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Novos Veículos',
                        data: data,
                        borderColor: '#0052CC',
                        backgroundColor: 'rgba(0, 82, 204, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#0052CC',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true } },
                        tooltip: { 
                            callbacks: { 
                                label: (ctx) => `${ctx.raw} veículo${ctx.raw !== 1 ? 's' : ''}` 
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#EBECF0' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        } catch (error) {
            console.error('[Chart Error]', error);
        }
    }
    
    async function loadFuelChart() {
        try {
            const ctx = document.getElementById('fuelChart')?.getContext('2d');
            if (!ctx) return;
            
            const result = await apiRequest('/dashboard/fuel-distribution');
            
            let labels = ['Gasóleo', 'Gasolina', 'Elétrico', 'Híbrido'];
            let data = [0, 0, 0, 0];
            
            if (result && result.success && result.data && result.data.length > 0) {
                const fuelMap = { 'diesel': 0, 'gasoline': 1, 'electric': 2, 'hybrid': 3 };
                result.data.forEach(item => {
                    const idx = fuelMap[item.fuel_type];
                    if (idx !== undefined) data[idx] = item.count;
                });
            } else if (window.fuelDistributionData && window.fuelDistributionData.length > 0) {
                labels = window.fuelDistributionData.map(item => getFuelLabel(item.fuel_type));
                data = window.fuelDistributionData.map(item => item.count);
            }
            
            if (fuelChart) {
                fuelChart.destroy();
                fuelChart = null;
            }
            
            fuelChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: ['#0052CC', '#36B37E', '#FF8B00', '#6554C0'],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true } },
                        tooltip: { 
                            callbacks: { 
                                label: (ctx) => `${ctx.label}: ${ctx.raw} veículo${ctx.raw !== 1 ? 's' : ''}` 
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('[Fuel Chart Error]', error);
        }
    }
    
    // CARREGAMENTO DE DADOS - VEÍCULOS RECENTES
    
    async function loadRecentVehicles() {
        try {
            const result = await apiRequest('/vehicles?limit=6');
            const container = document.getElementById('vehiclesList');
            if (!container) return;
            
            if (result && result.success && result.data && result.data.length > 0) {
                container.innerHTML = result.data.map(vehicle => `
                    <div class="vehicle-card" onclick="viewVehicleDetails(${vehicle.id})">
                        <div class="vehicle-image">
                            ${vehicle.image_url ? 
                                `<img src="${vehicle.image_url}" alt="${vehicle.brand} ${vehicle.model}" onerror="this.parentElement.innerHTML='<i class=\'fas fa-truck\'></i>'">` :
                                `<i class="fas fa-${getVehicleIcon(vehicle.fuel_type)}"></i>`
                            }
                            <span class="vehicle-badge ${getStatusClass(vehicle.status)}">
                                ${getStatusText(vehicle.status)}
                            </span>
                        </div>
                        <div class="vehicle-info">
                            <div class="vehicle-title">${escapeHtml(vehicle.brand)} ${escapeHtml(vehicle.model)}</div>
                            <div class="vehicle-plate">${escapeHtml(vehicle.plate)}</div>
                            <div class="vehicle-details">
                                <div class="vehicle-detail"><i class="fas fa-calendar"></i> ${vehicle.year || '-'}</div>
                                <div class="vehicle-detail"><i class="fas fa-palette"></i> ${escapeHtml(vehicle.color || '-')}</div>
                                <div class="vehicle-detail"><i class="fas fa-gas-pump"></i> ${getFuelLabel(vehicle.fuel_type)}</div>
                            </div>
                            <div class="vehicle-footer">
                                <div class="vehicle-odometer"><i class="fas fa-road"></i> ${formatNumber(vehicle.odometer || 0)} km</div>
                                <div class="vehicle-actions">
                                    <button class="btn-icon" onclick="event.stopPropagation(); viewVehicleDetails(${vehicle.id})" title="Ver detalhes">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-icon" onclick="event.stopPropagation(); editVehicleFromDashboard(${vehicle.id})" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-car"></i>
                        <p>Nenhum veículo registado</p>
                        <a href="/cardoxis/vehicles" class="btn-primary" style="padding: 8px 20px;">Adicionar veículo</a>
                    </div>
                `;
            }
        } catch (error) {
            console.error('[Vehicles Error]', error);
            const container = document.getElementById('vehiclesList');
            if (container) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-car"></i>
                        <p>Nenhum veículo registado</p>
                        <a href="/cardoxis/vehicles" class="btn-primary" style="padding: 8px 20px;">Adicionar veículo</a>
                    </div>
                `;
            }
        }
    }

    // CARREGAMENTO DE DADOS - MANUTENÇÕES
    
    async function loadUpcomingMaintenances() {
        try {
            const result = await apiRequest('/dashboard/upcoming-maintenances');
            const container = document.getElementById('upcomingMaintenances');
            if (!container) return;
            
            if (result && result.success && result.data && result.data.length > 0) {
                container.innerHTML = `
                    <div class="maintenance-list">
                        ${result.data.map(maintenance => {
                            const daysLeft = Math.ceil((new Date(maintenance.scheduled_date) - new Date()) / (1000 * 60 * 60 * 24));
                            const urgencyClass = daysLeft <= 7 ? 'urgent' : (daysLeft <= 15 ? 'warning' : '');
                            return `
                                <div class="maintenance-item">
                                    <div class="maintenance-icon ${daysLeft <= 7 ? 'danger' : (daysLeft <= 15 ? 'warning' : 'success')}">
                                        <i class="fas fa-tools"></i>
                                    </div>
                                    <div class="maintenance-content">
                                        <div class="maintenance-title">${escapeHtml(maintenance.title)}</div>
                                        <div class="maintenance-meta">
                                            ${escapeHtml(maintenance.brand)} ${escapeHtml(maintenance.model)} · ${escapeHtml(maintenance.plate)}
                                        </div>
                                    </div>
                                    <div class="maintenance-date ${urgencyClass}">
                                        <i class="fas fa-calendar"></i> ${formatDate(maintenance.scheduled_date)}
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>Nenhuma manutenção agendada</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('[Maintenance Error]', error);
            const container = document.getElementById('upcomingMaintenances');
            if (container) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>Nenhuma manutenção agendada</p>
                    </div>
                `;
            }
        }
    }
    
    // CARREGAMENTO DE DADOS - ALERTAS (SIMPLES, SEM FUNÇÕES COMPLEXAS)

    async function loadAlerts() {
        try {
            const result = await apiRequest('/dashboard/notifications');
            const container = document.getElementById('alertsList');
            
            if (!container) return;
            
            let alerts = [];
            let unreadCount = 0;
            
            if (result && result.success) {
                const data = result.data;
                alerts = data.notifications || [];
                unreadCount = data.unread_count || 0;
                
                updateElementText('alertsCount', unreadCount);
                updateElementText('alertCount', unreadCount);
                
                if (alerts.length > 0) {
                    let html = '<div class="alert-list">';
                    
                    // Limitar a 5 alertas
                    const displayAlerts = alerts.slice(0, 5);
                    
                    for (const alert of displayAlerts) {
                        const severityClass = getAlertSeverityClass(alert.severity);
                        const icon = getAlertIcon(alert.type);
                        const title = escapeHtml(alert.title);
                        const message = escapeHtml(alert.message);
                        const date = formatDateTime(alert.created_at);
                        
                        html += `
                            <div class="alert-item ${severityClass}">
                                <div class="alert-icon">
                                    <i class="fas ${icon}"></i>
                                </div>
                                <div class="alert-content">
                                    <div class="alert-title">${title}</div>
                                    <div class="alert-message">${message}</div>
                                    <div class="alert-time">
                                        <i class="fas fa-clock"></i> ${date}
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                    
                    html += '</div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <p>Nenhum alerta pendente</p>
                        </div>
                    `;
                }
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <p>Nenhum alerta pendente</p>
                    </div>
                `;
                updateElementText('alertsCount', 0);
                updateElementText('alertCount', 0);
            }
        } catch (error) {
            console.error('[Alerts Error]', error);
            const container = document.getElementById('alertsList');
            if (container) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <p>Nenhum alerta pendente</p>
                    </div>
                `;
            }
        }
    }
    
    // AÇÕES DE USUÁRIO - VEÍCULOS
    
    window.viewVehicleDetails = async function(vehicleId) {
        try {
            const result = await apiRequest(`/vehicles/${vehicleId}`);
            
            if (result && result.success && result.data) {
                const v = result.data;
                currentVehicleId = vehicleId;
                
                const modalContent = document.getElementById('modalContent');
                const modalTitle = document.getElementById('modalTitle');
                
                if (modalTitle) {
                    modalTitle.textContent = `${v.brand} ${v.model}`;
                }
                
                if (modalContent) {
                    modalContent.innerHTML = `
                        <div class="vehicle-details-container">
                            <div class="vehicle-details-image">
                                ${v.image_url ? 
                                    `<img src="${v.image_url}" alt="${v.brand} ${v.model}" onerror="this.parentElement.innerHTML='<div class=\'vehicle-image-placeholder large\'><i class=\'fas fa-truck\'></i></div>'">` :
                                    `<div class="vehicle-image-placeholder large"><i class="fas fa-truck"></i></div>`
                                }
                            </div>
                            <div class="vehicle-details-grid">
                                <div class="vehicle-detail-field">
                                    <label><i class="fas fa-id-card"></i> Matrícula</label>
                                    <div class="value">${escapeHtml(v.plate)}</div>
                                </div>
                                <div class="vehicle-detail-field">
                                    <label><i class="fas fa-calendar"></i> Ano</label>
                                    <div class="value">${v.year || '-'}</div>
                                </div>
                                <div class="vehicle-detail-field">
                                    <label><i class="fas fa-palette"></i> Cor</label>
                                    <div class="value">${escapeHtml(v.color || '-')}</div>
                                </div>
                                <div class="vehicle-detail-field">
                                    <label><i class="fas fa-gas-pump"></i> Combustível</label>
                                    <div class="value">${getFuelLabel(v.fuel_type)}</div>
                                </div>
                                <div class="vehicle-detail-field">
                                    <label><i class="fas fa-road"></i> Quilómetros</label>
                                    <div class="value">${formatNumber(v.odometer || 0)} km</div>
                                </div>
                                <div class="vehicle-detail-field">
                                    <label><i class="fas fa-chart-line"></i> Status</label>
                                    <div class="value">
                                        <span class="status-badge status-${getStatusBadgeClass(v.status)}">
                                            ${getStatusText(v.status)}
                                        </span>
                                    </div>
                                </div>
                                <div class="vehicle-detail-field full-width">
                                    <label><i class="fas fa-qrcode"></i> Número de Chassis (VIN)</label>
                                    <div class="value">${v.chassis || '-'}</div>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                openModal('vehicleDetailsModal');
            } else {
                showToast('error', 'Erro ao carregar detalhes do veículo');
            }
        } catch (error) {
            console.error('[Vehicle Error]', error);
            showToast('error', 'Erro ao carregar detalhes do veículo');
        }
    };
    
    window.editVehicleFromDashboard = async function(vehicleId) {
        try {
            const result = await apiRequest(`/vehicles/${vehicleId}`);
            
            if (result && result.success && result.data) {
                const v = result.data;
                currentVehicleId = vehicleId;
                
                document.getElementById('editVehicleId').value = v.id;
                document.getElementById('editVehicleBrand').value = v.brand || '';
                document.getElementById('editVehicleModel').value = v.model || '';
                document.getElementById('editVehiclePlate').value = v.plate || '';
                document.getElementById('editVehicleYear').value = v.year || '';
                document.getElementById('editVehicleColor').value = v.color || '';
                document.getElementById('editVehicleFuel').value = v.fuel_type || 'diesel';
                document.getElementById('editVehicleStatus').value = v.status || 'active';
                document.getElementById('editVehicleOdometer').value = v.odometer || 0;
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
    
    window.confirmDeleteVehicle = function() {
        if (currentVehicleId) {
            closeModal('vehicleDetailsModal');
            openModal('deleteConfirmModal');
        }
    };
    
    window.deleteVehicle = async function(vehicleId) {
        try {
            const result = await apiRequest(`/vehicles/${vehicleId}`, {
                method: 'DELETE'
            });
            
            if (result && result.success) {
                showToast('success', 'Veículo eliminado com sucesso!');
                closeModal('deleteConfirmModal');
                loadRecentVehicles();
                loadDashboardStats();
            } else {
                showToast('error', result?.message || 'Erro ao eliminar veículo');
            }
        } catch (error) {
            console.error('[Delete Error]', error);
            showToast('error', 'Erro ao eliminar veículo');
        }
    };
    
    window.removeEditImage = function() {
        const wrapper = document.getElementById('editImagePreviewWrapper');
        const placeholder = document.getElementById('editUploadPlaceholder');
        const input = document.getElementById('editVehicleImage');
        if (wrapper) wrapper.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
        if (input) input.value = '';
    };
    
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
            
            const data = await response.json();
            
            if (data && data.success) {
                return data.data;
            } else {
                showToast('error', data?.message || 'Erro ao fazer upload da imagem');
                return null;
            }
        } catch (error) {
            console.error('[Upload Error]', error);
            showToast('error', 'Erro ao fazer upload da imagem');
            return null;
        }
    }
    
    async function updateVehicle(id, formData) {
        showToast('info', 'A atualizar veículo...');
        
        const result = await apiRequest(`/vehicles/${id}`, {
            method: 'PUT',
            body: JSON.stringify(formData)
        });
        
        if (result && result.success) {
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
            loadRecentVehicles();
            loadDashboardStats();
            return true;
        } else {
            showToast('error', result?.message || 'Erro ao atualizar veículo');
            return false;
        }
    }
    
    // FORMULÁRIO DE EDIÇÃO
    
    function initEditForm() {
        const editForm = document.getElementById('editVehicleForm');
        if (editForm) {
            editForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const id = document.getElementById('editVehicleId').value;
                const formData = {
                    brand: document.getElementById('editVehicleBrand').value.trim(),
                    model: document.getElementById('editVehicleModel').value.trim(),
                    plate: document.getElementById('editVehiclePlate').value.trim().toUpperCase(),
                    year: document.getElementById('editVehicleYear').value,
                    color: document.getElementById('editVehicleColor').value,
                    fuel_type: document.getElementById('editVehicleFuel').value,
                    status: document.getElementById('editVehicleStatus').value,
                    odometer: parseInt(document.getElementById('editVehicleOdometer').value) || 0,
                    chassis: document.getElementById('editVehicleChassis').value
                };
                
                if (!formData.brand || !formData.model || !formData.plate) {
                    showToast('warning', 'Por favor, preencha todos os campos obrigatórios');
                    return;
                }
                
                await updateVehicle(id, formData);
            });
        }
    }
    
    // PREVIEW DE IMAGEM
    
    function initImagePreview() {
        const editImageInput = document.getElementById('editVehicleImage');
        if (editImageInput) {
            editImageInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        showToast('error', 'Tipo de arquivo não permitido');
                        editImageInput.value = '';
                        return;
                    }
                    if (file.size > 5 * 1024 * 1024) {
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
    }
    
    // BOTÕES DE CONFIRMAÇÃO
    
    function initButtons() {
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                if (currentVehicleId) deleteVehicle(currentVehicleId);
            });
        }
        
        const editBtn = document.getElementById('editFromDetailsBtn');
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                if (currentVehicleId) {
                    closeModal('vehicleDetailsModal');
                    editVehicleFromDashboard(currentVehicleId);
                }
            });
        }
    }

    // INICIALIZAÇÕES ADICIONAIS
    
    function initSearch() {
        const searchInput = document.getElementById('globalSearch');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const query = encodeURIComponent(searchInput.value);
                    window.location.href = `/cardoxis/vehicles?search=${query}`;
                }
            });
        }
    }
    
    function initSidebar() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (menuToggle && sidebar && overlay) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            });
            
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });
        }
    }
    
    function initUpdateTime() {
        updateLastUpdateTime();
        setInterval(updateLastUpdateTime, 30000);
    }
    
    // EXPOR FUNÇÕES GLOBAIS
    
    window.closeModal = closeModal;
    window.openModal = openModal;
    window.showToast = showToast;
    window.viewVehicleDetails = viewVehicleDetails;
    window.editVehicleFromDashboard = editVehicleFromDashboard;
    window.deleteVehicle = deleteVehicle;
    window.confirmDeleteVehicle = confirmDeleteVehicle;
    window.removeEditImage = removeEditImage;
    window.currentVehicleId = currentVehicleId;
    
  
    // INICIALIZAÇÃO PRINCIPAL
    
    async function init() {
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        console.log('🚀 CARDOXIS Dashboard v17.0.0');
        
        initSidebar();
        initSearch();
        initEditForm();
        initImagePreview();
        initButtons();
        initUpdateTime();
        
        await Promise.all([
            loadDashboardStats(),
            loadVehicleGrowthChart(),
            loadFuelChart(),
            loadRecentVehicles(),
            loadUpcomingMaintenances(),
            loadAlerts()
        ]);
        
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            loadDashboardStats();
            loadAlerts();
            updateLastUpdateTime();
        }, CONFIG.REFRESH_INTERVAL);
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();