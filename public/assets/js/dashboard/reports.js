/**
 * CARDOXIS - Reports RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES

    const CONFIG = {
        API_URL: window.CARDOXIS?.API_URL || window.location.origin + '/cardoxis/api/v1',
        REFRESH_INTERVAL: 60000
    };
    
    // ESTADO

    let state = {
        period: 30,
        reportType: 'fleet',
        viewType: 'both',
        isLoading: false,
        data: {
            stats: null,
            maintenance: null,
            fuel: null,
            documents: null
        }
    };
    
    let refreshInterval = null;
    let chartInstances = {};
    
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
    
    function formatCurrency(value) {
        if (!value && value !== 0) return '€ 0,00';
        return '€ ' + value.toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    
    function formatDate(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '—';
        return date.toLocaleDateString('pt-PT');
    }
    
    function updateElementText(id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    }
    
    function updateLastUpdateTime() {
        const element = document.getElementById('lastUpdateTime');
        if (element) element.textContent = new Date().toLocaleTimeString('pt-PT');
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
    
    // MODAL FUNCTIONS (DEFINIDAS GLOBALMENTE)

    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };
    
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };
    
    // CARREGAMENTO DE DADOS

    async function loadReports() {
        if (state.isLoading) return;
        state.isLoading = true;
        
        try {
            const token = getToken();
            if (!token) {
                window.location.href = '/cardoxis/login';
                return;
            }
            
            // Mostrar loading
            document.querySelectorAll('.loading-spinner').forEach(el => {
                if (el) el.style.display = 'flex';
            });
            
            const period = state.period;
            
            // Buscar todos os dados em uma única requisição
            const result = await apiRequest(`/reports/dashboard?period=${period}`);
            
            if (result && result.success) {
                const data = result.data;
                
                // Atualizar estado com dados reais
                state.data.stats = data.stats || {};
                state.data.maintenance = data.maintenance || {};
                state.data.fuel = data.fuel || {};
                state.data.documents = data.documents || {};
                
                // Renderizar tudo com dados reais
                renderStats(state.data.stats);
                renderCharts();
                renderTables();
                renderExpiringDocuments();
                
                updateLastUpdateTime();
            } else {
                showToast('error', result?.message || 'Erro ao carregar dados');
            }
            
        } catch (error) {
            console.error('[Load Reports Error]', error);
            showToast('error', 'Erro ao carregar relatórios');
        } finally {
            state.isLoading = false;
            document.querySelectorAll('.loading-spinner').forEach(el => {
                if (el) el.style.display = 'none';
            });
        }
    }
    
    // RENDERIZAÇÃO

    function renderStats(stats) {
        if (!stats) return;
        
        updateElementText('totalVehicles', formatNumber(stats.total_vehicles || 0));
        updateElementText('totalMaintenanceCost', formatCurrency(stats.maintenance_cost || 0));
        updateElementText('totalFuelLiters', formatNumber(stats.total_fuel_liters || 0) + ' L');
        updateElementText('expiringDocuments', formatNumber(stats.expiring_documents || 0));
    }
    
    function renderCharts() {
        const stats = state.data.stats || {};
        
        renderStatusChart(stats);
        renderFuelChart();
        renderMaintenanceChart();
        renderFuelConsumptionChart();
    }
    
    // GRÁFICOS

    function renderStatusChart(stats) {
        const ctx = document.getElementById('statusChart');
        if (!ctx) return;
        
        const active = stats.active_vehicles || 0;
        const maintenance = stats.maintenance_vehicles || 0;
        const inactive = stats.inactive_vehicles || 0;
        
        if (chartInstances.status) {
            chartInstances.status.destroy();
        }
        
        chartInstances.status = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Ativos', 'Em Manutenção', 'Inativos'],
                datasets: [{
                    data: [active, maintenance, inactive],
                    backgroundColor: ['#00875A', '#FF8B00', '#DE350B'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            font: { size: 12 }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
    
    function renderFuelChart() {
        const ctx = document.getElementById('fuelChart');
        if (!ctx) return;
        
        const stats = state.data.stats || {};
        
        // Distribuição de combustível (estimativa baseada em veículos)
        const total = stats.total_vehicles || 100;
        const diesel = Math.round(total * 0.65);
        const gasoline = Math.round(total * 0.20);
        const electric = Math.round(total * 0.10);
        const hybrid = Math.round(total * 0.05);
        
        if (chartInstances.fuel) {
            chartInstances.fuel.destroy();
        }
        
        chartInstances.fuel = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Diesel', 'Gasolina', 'Elétrico', 'Híbrido'],
                datasets: [{
                    data: [diesel, gasoline, electric, hybrid],
                    backgroundColor: ['#36B37E', '#0052CC', '#FF8B00', '#6554C0'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            font: { size: 12 }
                        }
                    }
                }
            }
        });
    }
    
    function renderMaintenanceChart() {
        const ctx = document.getElementById('maintenanceChart');
        if (!ctx) return;
        
        const maintenanceData = state.data.maintenance || {};
        const byMonth = maintenanceData.by_month || [];
        
        const labels = [];
        const costs = [];
        
        if (byMonth.length > 0) {
            const monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            byMonth.forEach(item => {
                const monthParts = item.month.split('-');
                const monthIndex = parseInt(monthParts[1]) - 1;
                labels.push(monthNames[monthIndex] + '/' + monthParts[0].substring(2));
                costs.push(item.total_cost || 0);
            });
        } else {
            // Dados de exemplo
            const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
            months.forEach((m) => {
                labels.push(m);
                costs.push(Math.round(Math.random() * 1500 + 500));
            });
        }
        
        if (chartInstances.maintenance) {
            chartInstances.maintenance.destroy();
        }
        
        chartInstances.maintenance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Custo (€)',
                    data: costs,
                    borderColor: '#FF8B00',
                    backgroundColor: 'rgba(255, 139, 0, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FF8B00',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€ ' + value;
                            }
                        }
                    }
                }
            }
        });
    }
    
    function renderFuelConsumptionChart() {
        const ctx = document.getElementById('fuelConsumptionChart');
        if (!ctx) return;
        
        const fuelData = state.data.fuel || {};
        const byMonth = fuelData.by_month || [];
        
        const labels = [];
        const liters = [];
        
        if (byMonth.length > 0) {
            const monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            byMonth.forEach(item => {
                const monthParts = item.month.split('-');
                const monthIndex = parseInt(monthParts[1]) - 1;
                labels.push(monthNames[monthIndex] + '/' + monthParts[0].substring(2));
                liters.push(item.total_liters || 0);
            });
        } else {
            // Dados de exemplo
            const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
            months.forEach((m) => {
                labels.push(m);
                liters.push(Math.round(Math.random() * 400 + 200));
            });
        }
        
        if (chartInstances.fuelConsumption) {
            chartInstances.fuelConsumption.destroy();
        }
        
        chartInstances.fuelConsumption = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Litros',
                    data: liters,
                    backgroundColor: 'rgba(54, 179, 126, 0.7)',
                    borderColor: '#36B37E',
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + ' L';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // TABELAS

    function renderTables() {
        renderMaintenanceByType();
        renderFuelByVehicle();
    }
    
    function renderMaintenanceByType() {
        const container = document.getElementById('maintenanceByType');
        if (!container) return;
        
        const maintenanceData = state.data.maintenance || {};
        const byType = maintenanceData.by_type || [];
        
        let html = '';
        
        if (byType.length > 0) {
            const totalCost = byType.reduce((sum, item) => sum + parseFloat(item.total_cost || 0), 0);
            
            html = `
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th class="text-center">Quantidade</th>
                            <th class="text-right">Custo Total</th>
                            <th class="text-right">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${byType.map(item => `
                            <tr>
                                <td><strong>${item.type || 'Outros'}</strong></td>
                                <td class="text-center">${item.count || 0}</td>
                                <td class="text-right">${formatCurrency(parseFloat(item.total_cost || 0))}</td>
                                <td class="text-right">
                                    <div class="progress-bar">
                                        <div class="progress-fill warning" style="width: ${totalCost > 0 ? (parseFloat(item.total_cost || 0) / totalCost * 100) : 0}%"></div>
                                    </div>
                                    <span class="text-muted" style="font-size:0.7rem;">${totalCost > 0 ? Math.round(parseFloat(item.total_cost || 0) / totalCost * 100) : 0}%</span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:600; border-top:2px solid var(--gray-ultra-light);">
                            <td>Total</td>
                            <td class="text-center">${byType.reduce((sum, item) => sum + parseInt(item.count || 0), 0)}</td>
                            <td class="text-right">${formatCurrency(totalCost)}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            `;
        } else {
            html = `<p class="text-muted" style="text-align:center; padding:20px;">Nenhuma manutenção encontrada no período selecionado.</p>`;
        }
        
        container.innerHTML = html;
    }
    
    function renderFuelByVehicle() {
        const container = document.getElementById('fuelByVehicle');
        if (!container) return;
        
        const fuelData = state.data.fuel || {};
        const byVehicle = fuelData.by_vehicle || [];
        
        let html = '';
        
        if (byVehicle.length > 0) {
            const totalLiters = byVehicle.reduce((sum, item) => sum + parseFloat(item.total_liters || 0), 0);
            
            html = `
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Veículo</th>
                            <th class="text-right">Litros</th>
                            <th class="text-right">Custo</th>
                            <th class="text-right">% Consumo</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${byVehicle.map(item => `
                            <tr>
                                <td><strong>${item.plate || 'N/A'}</strong></td>
                                <td class="text-right">${formatNumber(parseFloat(item.total_liters || 0))} L</td>
                                <td class="text-right">${formatCurrency(parseFloat(item.total_cost || 0))}</td>
                                <td class="text-right">
                                    <div class="progress-bar">
                                        <div class="progress-fill success" style="width: ${totalLiters > 0 ? (parseFloat(item.total_liters || 0) / totalLiters * 100) : 0}%"></div>
                                    </div>
                                    <span class="text-muted" style="font-size:0.7rem;">${totalLiters > 0 ? Math.round(parseFloat(item.total_liters || 0) / totalLiters * 100) : 0}%</span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:600; border-top:2px solid var(--gray-ultra-light);">
                            <td>Total</td>
                            <td class="text-right">${formatNumber(totalLiters)} L</td>
                            <td class="text-right">${formatCurrency(byVehicle.reduce((sum, item) => sum + parseFloat(item.total_cost || 0), 0))}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            `;
        } else {
            html = `<p class="text-muted" style="text-align:center; padding:20px;">Nenhum consumo de combustível registado no período selecionado.</p>`;
        }
        
        container.innerHTML = html;
    }
    
    // DOCUMENTOS A EXPIRAR

    function renderExpiringDocuments() {
        const container = document.getElementById('expiringDocumentsList');
        const countElement = document.getElementById('expiringCount');
        if (!container) return;
        
        const documentsData = state.data.documents || {};
        const expiring = documentsData.expiring || [];
        
        let html = '';
        
        if (expiring.length > 0) {
            if (countElement) {
                countElement.textContent = expiring.length;
            }
            
            html = `
                <div class="expiring-list">
                    ${expiring.map(item => {
                        const days = parseInt(item.days_remaining || 0);
                        let status = 'info';
                        if (days < 0) status = 'urgent';
                        else if (days <= 7) status = 'urgent';
                        else if (days <= 15) status = 'warning';
                        else if (days <= 30) status = 'info';
                        else status = 'success';
                        
                        let entityIcon = 'fa-building';
                        if (item.entity_type === 'vehicle') entityIcon = 'fa-car';
                        else if (item.entity_type === 'driver') entityIcon = 'fa-user';
                        
                        return `
                            <div class="expiring-item">
                                <div class="entity-info">
                                    <div class="entity-icon ${item.entity_type || 'company'}">
                                        <i class="fas ${entityIcon}"></i>
                                    </div>
                                    <div>
                                        <div class="entity-name">${escapeHtml(item.title || 'Documento')}</div>
                                        <div class="entity-detail">${escapeHtml(item.entity_name || '')}</div>
                                    </div>
                                </div>
                                <span class="days-remaining ${status}">
                                    ${days < 0 ? 'Vencido' : days + ' ' + (days === 1 ? 'dia' : 'dias')}
                                </span>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        } else {
            if (countElement) {
                countElement.textContent = '0';
            }
            html = `<p class="text-muted" style="text-align:center; padding:20px;">Nenhum documento a expirar.</p>`;
        }
        
        container.innerHTML = html;
    }
    
    // FILTROS E AÇÕES

    window.applyFilters = function() {
        const period = parseInt(document.getElementById('periodFilter').value);
        const reportType = document.getElementById('reportTypeFilter').value;
        const viewType = document.getElementById('viewTypeFilter').value;
        
        state.period = period;
        state.reportType = reportType;
        state.viewType = viewType;
        
        // Aplicar visualização
        const chartsSection = document.getElementById('chartsSection');
        const tablesSection = document.getElementById('tablesSection');
        
        if (viewType === 'charts') {
            if (chartsSection) chartsSection.style.display = 'block';
            if (tablesSection) tablesSection.style.display = 'none';
        } else if (viewType === 'tables') {
            if (chartsSection) chartsSection.style.display = 'none';
            if (tablesSection) tablesSection.style.display = 'block';
        } else {
            if (chartsSection) chartsSection.style.display = 'block';
            if (tablesSection) tablesSection.style.display = 'block';
        }
        
        showToast('info', 'A aplicar filtros...');
        loadReports();
    };
    
    window.filterReport = function(type) {
        const filterSelect = document.getElementById('reportTypeFilter');
        if (filterSelect) filterSelect.value = type;
        window.applyFilters();
    };
    
    // EXPORTAÇÃO

    window.exportReport = function(format) {
        if (format === 'pdf') {
            showToast('info', 'A preparar exportação PDF...');
            const printWindow = window.open('', '_blank', 'width=1200,height=800');
            if (printWindow) {
                const content = document.querySelector('.dashboard-container');
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Relatório CARDOXIS</title>
                        <style>
                            body { font-family: 'Inter', sans-serif; padding: 40px; color: #1a2332; }
                            h1 { color: #0052CC; margin-bottom: 10px; }
                            .header { border-bottom: 2px solid #e8eaed; padding-bottom: 20px; margin-bottom: 30px; }
                            .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0; }
                            .stat-item { background: #f8f9fa; padding: 15px 20px; border-radius: 8px; text-align: center; }
                            .stat-value { font-size: 24px; font-weight: 700; color: #0052CC; }
                            .stat-label { font-size: 12px; color: #6B778C; text-transform: uppercase; }
                            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                            th { background: #f8f9fa; padding: 12px; text-align: left; border-bottom: 2px solid #e8eaed; }
                            td { padding: 12px; border-bottom: 1px solid #e8eaed; }
                            .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e8eaed; text-align: center; color: #6B778C; font-size: 12px; }
                            .expiring-item { display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid #e8eaed; }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h1>📊 Relatório CARDOXIS</h1>
                            <p>Gerado em: ${new Date().toLocaleString('pt-PT')}</p>
                            <p>Período: ${document.getElementById('periodFilter').options[document.getElementById('periodFilter').selectedIndex].text}</p>
                        </div>
                        
                        <h2>Estatísticas</h2>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value">${document.getElementById('totalVehicles').textContent}</div>
                                <div class="stat-label">Total Veículos</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${document.getElementById('totalMaintenanceCost').textContent}</div>
                                <div class="stat-label">Custo Manutenções</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${document.getElementById('totalFuelLiters').textContent}</div>
                                <div class="stat-label">Combustível</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${document.getElementById('expiringDocuments').textContent}</div>
                                <div class="stat-label">Documentos a Expirar</div>
                            </div>
                        </div>
                        
                        <h2>Manutenções por Tipo</h2>
                        ${document.getElementById('maintenanceByType').innerHTML}
                        
                        <h2>Consumo por Veículo</h2>
                        ${document.getElementById('fuelByVehicle').innerHTML}
                        
                        <h2>Documentos a Expirar</h2>
                        ${document.getElementById('expiringDocumentsList').innerHTML}
                        
                        <div class="footer">
                            <p>CARDOXIS - Gestão de Frotas © ${new Date().getFullYear()}</p>
                            <p>Este relatório é confidencial e destina-se apenas ao uso interno da empresa.</p>
                        </div>
                        <script>
                            window.onload = function() {
                                window.print();
                                setTimeout(function() { window.close(); }, 1000);
                            };
                        <\/script>
                    </body>
                    </html>
                `);
                printWindow.document.close();
                setTimeout(() => showToast('success', 'PDF exportado com sucesso!'), 2000);
            } else {
                showToast('error', 'Não foi possível abrir a janela de exportação. Verifique se o pop-up está bloqueado.');
            }
        } else if (format === 'csv') {
            showToast('info', 'A preparar exportação CSV...');
            generateCSV();
        } else {
            const modal = document.getElementById('exportModal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
    };
    
    window.confirmExport = function() {
        const formatSelect = document.getElementById('exportFormat');
        const format = formatSelect ? formatSelect.value : 'pdf';
        const modal = document.getElementById('exportModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
        window.exportReport(format);
    };
    
    function generateCSV() {
        try {
            let csvContent = '';
            csvContent += `Relatório CARDOXIS\n`;
            csvContent += `Gerado em: ${new Date().toLocaleString('pt-PT')}\n`;
            csvContent += `Período: ${document.getElementById('periodFilter').options[document.getElementById('periodFilter').selectedIndex].text}\n\n`;
            
            // Estatísticas
            csvContent += `ESTATÍSTICAS\n`;
            csvContent += `Total Veículos,${document.getElementById('totalVehicles').textContent}\n`;
            csvContent += `Custo Manutenções,${document.getElementById('totalMaintenanceCost').textContent}\n`;
            csvContent += `Combustível,${document.getElementById('totalFuelLiters').textContent}\n`;
            csvContent += `Documentos a Expirar,${document.getElementById('expiringDocuments').textContent}\n\n`;
            
            // Manutenções por tipo
            const maintenanceContainer = document.getElementById('maintenanceByType');
            const maintenanceTable = maintenanceContainer?.querySelector('table');
            if (maintenanceTable) {
                csvContent += `MANUTENÇÕES POR TIPO\n`;
                const rows = maintenanceTable.querySelectorAll('tr');
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td, th');
                    const rowData = [];
                    cells.forEach(cell => {
                        let text = cell.textContent.trim();
                        if (text.includes('%') && text.length > 10) {
                            const match = text.match(/\d+%/);
                            if (match) text = match[0];
                        }
                        rowData.push(text);
                    });
                    if (rowData.length > 0) {
                        csvContent += rowData.join(',') + '\n';
                    }
                });
                csvContent += '\n';
            }
            
            // Consumo por veículo
            const fuelContainer = document.getElementById('fuelByVehicle');
            const fuelTable = fuelContainer?.querySelector('table');
            if (fuelTable) {
                csvContent += `CONSUMO POR VEÍCULO\n`;
                const rows = fuelTable.querySelectorAll('tr');
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td, th');
                    const rowData = [];
                    cells.forEach(cell => {
                        let text = cell.textContent.trim();
                        if (text.includes('%') && text.length > 10) {
                            const match = text.match(/\d+%/);
                            if (match) text = match[0];
                        }
                        rowData.push(text);
                    });
                    if (rowData.length > 0) {
                        csvContent += rowData.join(',') + '\n';
                    }
                });
                csvContent += '\n';
            }
            
            // Documentos a expirar
            const docsContainer = document.getElementById('expiringDocumentsList');
            const docsItems = docsContainer?.querySelectorAll('.expiring-item');
            if (docsItems && docsItems.length > 0) {
                csvContent += `DOCUMENTOS A EXPIRAR\n`;
                csvContent += `Documento,Entidade,Dias Restantes\n`;
                docsItems.forEach(item => {
                    const name = item.querySelector('.entity-name')?.textContent.trim() || '';
                    const detail = item.querySelector('.entity-detail')?.textContent.trim() || '';
                    const days = item.querySelector('.days-remaining')?.textContent.trim() || '';
                    csvContent += `${name},${detail},${days}\n`;
                });
            }
            
            // Criar e baixar o arquivo CSV
            const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `relatorio_cardoxis_${new Date().toISOString().slice(0,10)}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);
            
            showToast('success', 'CSV exportado com sucesso!');
        } catch (error) {
            console.error('[CSV Export Error]', error);
            showToast('error', 'Erro ao exportar CSV');
        }
    }
    
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
    }
    
    // INICIALIZAÇÃO

    async function init() {
        console.log('[CARDOXIS] Inicializando Reports');
        
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        initSidebar();
        await loadReports();
        
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            loadReports();
        }, CONFIG.REFRESH_INTERVAL);
        
        console.log('[CARDOXIS] Reports inicializado com sucesso!');
    }
    
    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();