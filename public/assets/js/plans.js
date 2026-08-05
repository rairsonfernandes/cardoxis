/**
 * CARDOXIS Admin Plans JavaScript RF
 */

(function() {
    'use strict';
    
    const API_URL = 'http://localhost/cardoxis/api/v1';
    let currentView = 'cards';
    let currentPlanId = null;
    
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
                showToast('error', 'Sessão expirada. Faça login novamente.');
                setTimeout(() => { window.location.href = '/cardoxis/login'; }, 2000);
                return null;
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('[API Error]', endpoint, error);
            showToast('error', 'Erro de conexão com o servidor');
            return { success: false, message: error.message };
        }
    }

    // CARREGAR PLANOS

    async function loadPlans() {
        showLoading(true);
        
        const response = await apiRequest('/admin/plans');
        
        if (response && response.success) {
            renderPlans(response.data);
            updateStats(response.data);
        } else {
            showEmptyState();
            showToast('error', response?.message || 'Erro ao carregar planos');
        }
        
        showLoading(false);
    }
    
    function renderPlans(plans) {
        const container = document.getElementById('plansContainer');
        
        if (!plans || plans.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-tag"></i>
                    <h3>Nenhum plano encontrado</h3>
                    <p>Clique em "Novo Plano" para adicionar</p>
                    <button class="btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Criar Plano
                    </button>
                </div>
            `;
            return;
        }
        
        if (currentView === 'cards') {
            renderCardsView(plans);
        } else {
            renderTableView(plans);
        }
    }
    
    function renderCardsView(plans) {
        const container = document.getElementById('plansContainer');
        
        container.innerHTML = `<div class="plans-grid">${
            plans.map(plan => `
                <div class="plan-card ${plan.is_active === 1 && plan.name === 'Profissional' ? 'popular' : ''}">
                    ${plan.is_active === 1 && plan.name === 'Profissional' ? '<div class="plan-badge">MAIS POPULAR</div>' : ''}
                    <div class="plan-header">
                        <div class="plan-name">${escapeHtml(plan.name)}</div>
                        <div class="plan-price">
                            <span class="plan-currency">€</span>
                            <span class="plan-amount">${plan.price}</span>
                            <span class="plan-period">/${plan.billing_interval || 'month'}</span>
                        </div>
                        <div class="plan-description">${escapeHtml(plan.description || '')}</div>
                    </div>
                    <div class="plan-body">
                        <ul class="plan-features">
                            ${plan.features && plan.features.length > 0 ? 
                                plan.features.map(feature => `
                                    <li><i class="fas fa-check-circle"></i> ${escapeHtml(feature)}</li>
                                `).join('') : 
                                '<li><i class="fas fa-minus-circle"></i> Nenhuma funcionalidade definida</li>'
                            }
                        </ul>
                    </div>
                    <div class="plan-footer">
                        <div class="action-buttons">
                            <button class="action-btn edit" onclick="editPlan(${plan.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn delete" onclick="deletePlan(${plan.id})" title="Excluir">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('')
        }</div>`;
    }
    
    function renderTableView(plans) {
        const container = document.getElementById('plansContainer');
        
        container.innerHTML = `
            <div class="plans-table-container">
                <table class="plans-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Preço</th>
                            <th>Veículos</th>
                            <th>Motoristas</th>
                            <th>Status</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${plans.map(plan => `
                            <tr>
                                <td class="plan-name-cell">${escapeHtml(plan.name)}</td>
                                <td><span class="price-tag">€ ${plan.price}</span><small>/${plan.billing_interval || 'month'}</small></td>
                                <td>${plan.max_vehicles === 999999 ? '∞' : plan.max_vehicles}</td>
                                <td>${plan.max_drivers === 999999 ? '∞' : plan.max_drivers}</td>
                                <td>${plan.is_active === 1 ? '<span class="status-badge status-active"><i class="fas fa-check-circle"></i> Ativo</span>' : '<span class="status-badge status-inactive"><i class="fas fa-minus-circle"></i> Inativo</span>'}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn edit" onclick="editPlan(${plan.id})" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete" onclick="deletePlan(${plan.id})" title="Excluir">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }
    
    function updateStats(plans) {
        const total = plans.length;
        const active = plans.filter(p => p.is_active === 1).length;
        const inactive = plans.filter(p => p.is_active === 0).length;
        
        const totalEl = document.getElementById('totalPlans');
        const activeEl = document.getElementById('activePlans');
        const inactiveEl = document.getElementById('inactivePlans');
        
        if (totalEl) totalEl.textContent = total;
        if (activeEl) activeEl.textContent = active;
        if (inactiveEl) inactiveEl.textContent = inactive;
    }
    
    function showEmptyState() {
        const container = document.getElementById('plansContainer');
        if (container) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-tag"></i>
                    <h3>Nenhum plano encontrado</h3>
                    <p>Clique em "Novo Plano" para adicionar</p>
                    <button class="btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Criar Plano
                    </button>
                </div>
            `;
        }
    }
    
    // CRUD OPERATIONS

    window.openCreateModal = function() {
        currentPlanId = null;
        const titleEl = document.getElementById('modalTitle');
        const form = document.getElementById('planForm');
        const featuresList = document.getElementById('featuresList');
        const planId = document.getElementById('planId');
        
        if (titleEl) titleEl.textContent = 'Novo Plano';
        if (form) form.reset();
        if (planId) planId.value = '';
        if (featuresList) featuresList.innerHTML = '';
        
        // Resetar valores padrão
        const planCurrency = document.getElementById('planCurrency');
        const planInterval = document.getElementById('planInterval');
        const planTrialDays = document.getElementById('planTrialDays');
        const planIsActive = document.getElementById('planIsActive');
        
        if (planCurrency) planCurrency.value = 'EUR';
        if (planInterval) planInterval.value = 'month';
        if (planTrialDays) planTrialDays.value = 14;
        if (planIsActive) planIsActive.checked = true;
        
        // Resetar máximos
        const planMaxVehicles = document.getElementById('planMaxVehicles');
        const planMaxDrivers = document.getElementById('planMaxDrivers');
        if (planMaxVehicles) planMaxVehicles.value = 10;
        if (planMaxDrivers) planMaxDrivers.value = 5;
        
        const modal = document.getElementById('planModal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };
    
    window.closeModal = function() {
        const planModal = document.getElementById('planModal');
        const deleteModal = document.getElementById('deleteModal');
        
        if (planModal) planModal.classList.remove('active');
        if (deleteModal) deleteModal.classList.remove('active');
        document.body.style.overflow = '';
    };
    
    window.editPlan = async function(id) {
        showLoading(true);
        const response = await apiRequest(`/admin/plans/${id}`);
        showLoading(false);
        
        if (response && response.success) {
            const plan = response.data;
            currentPlanId = plan.id;
            
            document.getElementById('modalTitle').textContent = 'Editar Plano';
            document.getElementById('planId').value = plan.id;
            document.getElementById('planName').value = plan.name;
            document.getElementById('planDescription').value = plan.description || '';
            document.getElementById('planPrice').value = plan.price;
            document.getElementById('planAnnualPrice').value = plan.annual_price || '';
            document.getElementById('planCurrency').value = plan.currency || 'EUR';
            document.getElementById('planInterval').value = plan.billing_interval || plan.interval || 'month';
            document.getElementById('planTrialDays').value = plan.trial_days || 14;
            document.getElementById('planMaxVehicles').value = plan.max_vehicles;
            document.getElementById('planMaxDrivers').value = plan.max_drivers;
            document.getElementById('planIsActive').checked = plan.is_active === 1;
            
            const featuresList = document.getElementById('featuresList');
            if (featuresList) {
                featuresList.innerHTML = '';
                if (plan.features && plan.features.length > 0) {
                    plan.features.forEach(feature => {
                        addFeatureToList(feature);
                    });
                }
            }
            
            document.getElementById('planModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            showToast('error', response?.message || 'Erro ao carregar plano');
        }
    };
    
    window.savePlan = async function() {
        const id = document.getElementById('planId')?.value;
        
        // Coletar features
        const features = [];
        const featureItems = document.querySelectorAll('#featuresList .feature-item span:first-child');
        featureItems.forEach(item => {
            let text = item.textContent || item.innerText;
            // Remover o ícone se existir
            text = text.replace(/[✓✔✅]/g, '').trim();
            if (text) {
                features.push(text);
            }
        });
        
        const data = {
            name: document.getElementById('planName')?.value,
            description: document.getElementById('planDescription')?.value || '',
            price: parseFloat(document.getElementById('planPrice')?.value) || 0,
            annual_price: parseFloat(document.getElementById('planAnnualPrice')?.value) || null,
            currency: document.getElementById('planCurrency')?.value || 'EUR',
            interval: document.getElementById('planInterval')?.value || 'month',
            trial_days: parseInt(document.getElementById('planTrialDays')?.value) || 14,
            max_vehicles: parseInt(document.getElementById('planMaxVehicles')?.value) || 10,
            max_drivers: parseInt(document.getElementById('planMaxDrivers')?.value) || 5,
            features: features,
            is_active: document.getElementById('planIsActive')?.checked ? 1 : 0
        };
        
        // Validação
        if (!data.name || data.name.trim() === '') {
            showToast('error', 'Nome do plano é obrigatório');
            return;
        }
        if (!data.price || data.price <= 0) {
            showToast('error', 'Preço deve ser maior que zero');
            return;
        }
        if (data.max_vehicles < 0) {
            showToast('error', 'Número de veículos inválido');
            return;
        }
        if (data.max_drivers < 0) {
            showToast('error', 'Número de motoristas inválido');
            return;
        }
        
        showLoading(true);
        
        let response;
        if (id) {
            response = await apiRequest(`/admin/plans/${id}`, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        } else {
            response = await apiRequest('/admin/plans', {
                method: 'POST',
                body: JSON.stringify(data)
            });
        }
        
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', id ? 'Plano atualizado com sucesso!' : 'Plano criado com sucesso!');
            closeModal();
            loadPlans();
        } else {
            showToast('error', response?.message || 'Erro ao salvar plano');
        }
    };
    
    window.deletePlan = function(id) {
        currentPlanId = id;
        document.getElementById('deleteModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    
    window.confirmDelete = async function() {
        if (!currentPlanId) return;
        
        closeModal();
        showLoading(true);
        
        const response = await apiRequest(`/admin/plans/${currentPlanId}`, {
            method: 'DELETE'
        });
        
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', 'Plano removido com sucesso!');
            loadPlans();
        } else {
            showToast('error', response?.message || 'Erro ao remover plano');
        }
        
        currentPlanId = null;
    };
    
    // FEATURES MANAGEMENT

    function addFeatureToList(featureText) {
        const featuresList = document.getElementById('featuresList');
        if (!featuresList) return;
        
        const featureDiv = document.createElement('div');
        featureDiv.className = 'feature-item';
        featureDiv.innerHTML = `
            <span><i class="fas fa-check-circle" style="color:#00875A; margin-right:8px;"></i> ${escapeHtml(featureText)}</span>
            <i class="fas fa-trash-alt" onclick="this.parentElement.remove()" style="cursor:pointer; color:#DE350B;"></i>
        `;
        featuresList.appendChild(featureDiv);
    }
    
    window.addFeature = function() {
        const input = document.getElementById('newFeature');
        if (!input) return;
        
        const featureText = input.value.trim();
        
        if (!featureText) {
            showToast('warning', 'Digite uma funcionalidade');
            return;
        }
        
        // Verificar se a feature já existe
        const existingFeatures = document.querySelectorAll('#featuresList .feature-item span:first-child');
        let exists = false;
        existingFeatures.forEach(item => {
            let text = item.textContent || item.innerText;
            text = text.replace(/[✓✔✅]/g, '').trim();
            if (text.toLowerCase() === featureText.toLowerCase()) {
                exists = true;
            }
        });
        
        if (exists) {
            showToast('warning', 'Esta funcionalidade já foi adicionada');
            return;
        }
        
        addFeatureToList(featureText);
        input.value = '';
        input.focus();
    };
    
    // VIEW TOGGLE

    window.setView = function(view) {
        currentView = view;
        
        const viewCards = document.getElementById('viewCards');
        const viewTable = document.getElementById('viewTable');
        
        if (viewCards && viewTable) {
            if (view === 'cards') {
                viewCards.classList.add('active');
                viewTable.classList.remove('active');
            } else {
                viewCards.classList.remove('active');
                viewTable.classList.add('active');
            }
        }
        
        loadPlans();
    };

    // EXPORT

    window.exportPlans = async function() {
        showLoading(true);
        
        const response = await apiRequest('/admin/plans');
        showLoading(false);
        
        if (response && response.success && response.data) {
            const plans = response.data;
            const csv = convertToCSV(plans);
            downloadCSV(csv, `planos_${new Date().toISOString().slice(0, 19)}.csv`);
            showToast('success', 'Exportação concluída!');
        } else {
            showToast('error', 'Erro ao exportar planos');
        }
    };
    
    function convertToCSV(plans) {
        const headers = ['ID', 'Nome', 'Preço', 'Moeda', 'Intervalo', 'Veículos', 'Motoristas', 'Status'];
        const rows = plans.map(plan => [
            plan.id,
            `"${plan.name}"`,
            plan.price,
            plan.currency || 'EUR',
            plan.billing_interval || plan.interval || 'month',
            plan.max_vehicles === 999999 ? 'Ilimitado' : plan.max_vehicles,
            plan.max_drivers === 999999 ? 'Ilimitado' : plan.max_drivers,
            plan.is_active === 1 ? 'Ativo' : 'Inativo'
        ]);
        
        return [headers, ...rows].map(row => row.join(',')).join('\n');
    }
    
    function downloadCSV(csv, filename) {
        const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.href = url;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }
    
    // UTILITÁRIOS

    function showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            if (show) overlay.classList.add('active');
            else overlay.classList.remove('active');
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
        toast.innerHTML = `
            <div class="toast-icon"><i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : 'info-circle')}"></i></div>
            <div class="toast-content"><div class="toast-message">${escapeHtml(message)}</div></div>
            <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), duration);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function updateActiveMenu() {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-item').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '/cardoxis/admin/plans') {
                link.classList.add('active');
            }
        });
    }
    
    // INICIALIZAÇÃO
    
    document.addEventListener('DOMContentLoaded', () => {
        if (!checkAuth()) return;
        
        updateActiveMenu();
        loadPlans();
        
        const newFeatureInput = document.getElementById('newFeature');
        if (newFeatureInput) {
            newFeatureInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addFeature();
                }
            });
        }
    });
    
    // Expor funções globalmente
    window.openCreateModal = openCreateModal;
    window.closeModal = closeModal;
    window.editPlan = editPlan;
    window.savePlan = savePlan;
    window.deletePlan = deletePlan;
    window.confirmDelete = confirmDelete;
    window.addFeature = addFeature;
    window.setView = setView;
    window.exportPlans = exportPlans;
})();