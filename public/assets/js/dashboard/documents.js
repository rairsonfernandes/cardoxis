/**
 * CARDOXIS - Documents RF
 */

(function() {
    'use strict';
    
    const CONFIG = {
        API_URL: window.location.origin + '/cardoxis/api/v1',
        ITEMS_PER_PAGE: 15,
        MAX_FILE_SIZE: 10 * 1024 * 1024,
        ALLOWED_TYPES: [
            'application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'image/webp',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]
    };
    
    let state = {
        documents: [],
        vehicles: [],
        drivers: [],
        currentPage: 1,
        currentStatus: 'all',
        currentEntityType: 'all',
        currentType: 'all',
        totalPages: 1,
        totalItems: 0,
        isLoading: false
    };
    
    let selectedFile = null;
    let currentDocumentId = null;
    let refreshInterval = null;
    
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
    
    async function apiRequest(endpoint, options = {}) {
        const token = getToken();
        const headers = { 'Accept': 'application/json', ...options.headers };
        
        if (!(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
        }
        
        if (token) headers['Authorization'] = `Bearer ${token}`;
        
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
            console.error('[API Error]', endpoint, error);
            showToast('error', 'Erro na comunicação com o servidor');
            return { success: false, data: { data: [] } };
        }
    }
    
    // UTILITÁRIOS

    function formatNumber(num) {
        if (!num) return '0';
        return num.toLocaleString('pt-PT');
    }
    
    function formatFileSize(bytes) {
        if (!bytes || bytes === 0) return '—';
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function formatDate(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '—';
        return date.toLocaleDateString('pt-PT', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
    
    function formatDateTime(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '—';
        return date.toLocaleDateString('pt-PT', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + 
               date.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function getValidId(id) {
        if (!id || id === 'null' || id === 'undefined' || id === '') {
            return null;
        }
        const parsed = parseInt(id);
        if (isNaN(parsed) || parsed <= 0) {
            return null;
        }
        return parsed;
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
        const icon = type === 'success' ? 'check-circle' : 
                     type === 'error' ? 'exclamation-circle' : 
                     type === 'warning' ? 'exclamation-triangle' : 'info-circle';
        
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
    
    // ÍCONES E STATUS

    function getFileIcon(fileType) {
        if (!fileType) return { icon: 'fa-file-alt', class: 'default' };
        if (fileType === 'application/pdf') return { icon: 'fa-file-pdf', class: 'pdf' };
        if (fileType.includes('image')) return { icon: 'fa-file-image', class: 'image' };
        if (fileType.includes('word')) return { icon: 'fa-file-word', class: 'word' };
        if (fileType.includes('excel') || fileType.includes('spreadsheet')) return { icon: 'fa-file-excel', class: 'excel' };
        return { icon: 'fa-file-alt', class: 'default' };
    }
    
    function getStatusClass(doc) {
        if (!doc.expiry_date) return 'valid';
        const today = new Date();
        const expiry = new Date(doc.expiry_date);
        const daysRemaining = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
        if (daysRemaining < 0) return 'expired';
        if (daysRemaining <= 30) return 'expiring';
        return 'valid';
    }
    
    function getStatusText(doc) {
        if (!doc.expiry_date) return 'Válido';
        const today = new Date();
        const expiry = new Date(doc.expiry_date);
        const daysRemaining = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
        if (daysRemaining < 0) return 'Vencido';
        if (daysRemaining <= 30) return `A vencer (${daysRemaining} dias)`;
        return 'Válido';
    }
    
    // CARREGAMENTO DE DADOS - CORRIGIDO

    async function loadVehicles() {
        try {
            const result = await apiRequest('/vehicles?limit=100');
            console.log('[Load Vehicles] Resposta:', result);
            
            if (result && result.success) {
                // CORREÇÃO: Verificar estrutura de dados
                if (Array.isArray(result.data)) {
                    state.vehicles = result.data;
                } else if (result.data && Array.isArray(result.data.data)) {
                    state.vehicles = result.data.data;
                } else {
                    state.vehicles = [];
                }
                console.log('[Load Vehicles] Veículos carregados:', state.vehicles.length);
                updateEntitySelects();
            } else {
                state.vehicles = [];
                updateEntitySelects();
            }
        } catch (error) {
            console.error('[Load Vehicles Error]', error);
            state.vehicles = [];
            updateEntitySelects();
        }
    }
    
    async function loadDrivers() {
        try {
            const result = await apiRequest('/drivers?limit=100');
            console.log('[Load Drivers] Resposta:', result);
            
            if (result && result.success) {
                // CORREÇÃO: Verificar estrutura de dados
                if (Array.isArray(result.data)) {
                    state.drivers = result.data;
                } else if (result.data && Array.isArray(result.data.data)) {
                    state.drivers = result.data.data;
                } else {
                    state.drivers = [];
                }
                console.log('[Load Drivers] Motoristas carregados:', state.drivers.length);
                console.log('[Load Drivers] Motoristas:', state.drivers);
                updateEntitySelects();
            } else {
                console.log('[Load Drivers] Erro ou sem dados:', result);
                state.drivers = [];
                updateEntitySelects();
            }
        } catch (error) {
            console.error('[Load Drivers Error]', error);
            state.drivers = [];
            updateEntitySelects();
        }
    }
    
    function updateEntitySelects() {
        const entitySelect = document.getElementById('addEntityId');
        const entityTypeSelect = document.getElementById('addEntityType');
        if (!entitySelect || !entityTypeSelect) {
            console.log('[Update Entity] Selects não encontrados');
            return;
        }
        
        function updateOptions() {
            const entityType = entityTypeSelect.value;
            console.log('[Update Entity] Tipo selecionado:', entityType);
            let options = '<option value="">Selecione</option>';
            
            if (entityType === 'vehicle') {
                console.log('[Update Entity] Veículos disponíveis:', state.vehicles.length);
                if (state.vehicles && state.vehicles.length > 0) {
                    options += state.vehicles.map(v => 
                        `<option value="${v.id}">${escapeHtml(v.brand)} ${escapeHtml(v.model)} - ${escapeHtml(v.plate)}</option>`
                    ).join('');
                } else {
                    options += '<option value="">Nenhum veículo disponível</option>';
                }
            } else if (entityType === 'driver') {
                console.log('[Update Entity] Motoristas disponíveis:', state.drivers.length);
                if (state.drivers && state.drivers.length > 0) {
                    options += state.drivers.map(d => 
                        `<option value="${d.id}">${escapeHtml(d.name)} - ${escapeHtml(d.document || '')}</option>`
                    ).join('');
                } else {
                    options += '<option value="">Nenhum motorista disponível</option>';
                }
            } else if (entityType === 'company') {
                options += `<option value="1">CARDOXIS Administração</option>`;
            }
            entitySelect.innerHTML = options;
            console.log('[Update Entity] Select atualizado com', entitySelect.options.length - 1, 'opções');
        }
        
        // Remover listeners antigos para evitar duplicação
        entityTypeSelect.removeEventListener('change', updateOptions);
        entityTypeSelect.addEventListener('change', updateOptions);
        
        // Atualizar inicialmente
        updateOptions();
    }
    
    async function loadStats() {
        try {
            const result = await apiRequest('/documents?limit=1000');
            if (result && result.success) {
                const documents = Array.isArray(result.data?.data) ? result.data.data : [];
                const total = documents.length;
                const expired = documents.filter(d => d.expiry_date && new Date(d.expiry_date) < new Date()).length;
                const expiring = documents.filter(d => {
                    if (!d.expiry_date) return false;
                    const days = Math.ceil((new Date(d.expiry_date) - new Date()) / (1000 * 60 * 60 * 24));
                    return days >= 0 && days <= 30;
                }).length;
                const valid = total - expired - expiring;
                const totalStorage = documents.reduce((sum, d) => sum + (d.file_size || 0), 0);
                
                updateElementText('totalDocuments', formatNumber(total));
                updateElementText('validDocuments', formatNumber(valid));
                updateElementText('expiringDocuments', formatNumber(expiring));
                updateElementText('expiredDocuments', formatNumber(expired));
                updateElementText('storageUsed', formatFileSize(totalStorage));
                updateElementText('filterAllCount', formatNumber(total));
                updateElementText('filterValidCount', formatNumber(valid));
                updateElementText('filterExpiringCount', formatNumber(expiring));
                updateElementText('filterExpiredCount', formatNumber(expired));
            }
        } catch (error) {
            console.error('[Load Stats Error]', error);
        }
    }
    
    async function loadDocuments() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();
        
        try {
            let url = `/documents?limit=${CONFIG.ITEMS_PER_PAGE}&page=${state.currentPage}`;
            if (state.currentStatus !== 'all') url += `&status=${state.currentStatus}`;
            if (state.currentEntityType !== 'all') url += `&entity_type=${state.currentEntityType}`;
            if (state.currentType !== 'all') url += `&type=${state.currentType}`;
            
            const result = await apiRequest(url);
            
            console.log('[CARDOXIS] API Response:', result);
            
            if (result && result.success) {
                state.documents = Array.isArray(result.data?.data) ? result.data.data : [];
                state.totalPages = result.data?.pagination?.last_page || 1;
                state.totalItems = result.data?.pagination?.total || 0;
                
                console.log('[CARDOXIS] Documentos carregados:', state.documents.length);
                
                if (state.documents.length > 0) {
                    renderTable();
                } else {
                    renderEmptyState();
                }
                renderPagination();
                updateLastUpdateTime();
            } else {
                state.documents = [];
                renderEmptyState();
            }
        } catch (error) {
            console.error('[Load Documents Error]', error);
            state.documents = [];
            renderEmptyState();
            showToast('error', 'Erro ao carregar documentos');
        } finally {
            state.isLoading = false;
        }
    }

    // RENDERIZAÇÃO

    function showLoading() {
        const container = document.getElementById('documentsTableBody');
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 60px;">
                        <div class="spinner"></div>
                        <p style="margin-top: 16px;">Carregando documentos...</p>
                    </td>
                </tr>
            `;
        }
    }
    
    function renderTable() {
        const container = document.getElementById('documentsTableBody');
        if (!container) return;
        
        if (!state.documents || state.documents.length === 0) {
            renderEmptyState();
            return;
        }
        
        container.innerHTML = state.documents.map(doc => {
            const docId = getValidId(doc.id);
            if (!docId) {
                return '';
            }
            
            const fileIcon = getFileIcon(doc.file_type);
            const statusClass = getStatusClass(doc);
            const statusText = getStatusText(doc);
            
            return `
                <tr data-id="${docId}" onclick="viewDocumentDetails(${docId})">
                    <td>
                        <div class="doc-icon ${fileIcon.class}">
                            <i class="fas ${fileIcon.icon}"></i>
                        </div>
                    </td>
                    <td class="file-name-cell">
                        <strong>${escapeHtml(doc.title)}</strong>
                        <small>${escapeHtml(doc.file_name || 'Documento')}</small>
                    </td>
                    <td>
                        <div class="entity-name">${escapeHtml(doc.entity_name || '-')}</div>
                        <span class="entity-type-badge ${doc.entity_type}">
                            ${doc.entity_type === 'vehicle' ? 'Veículo' : doc.entity_type === 'driver' ? 'Motorista' : 'Empresa'}
                        </span>
                    </td>
                    <td>
                        <span class="category-badge">${escapeHtml(doc.type)}</span>
                    </td>
                    <td>
                        <span class="status-badge status-${statusClass}">
                            <i class="fas ${statusClass === 'expired' ? 'fa-exclamation-triangle' : statusClass === 'expiring' ? 'fa-clock' : 'fa-check-circle'}"></i>
                            ${statusText}
                        </span>
                    </td>
                    <td class="date-cell">
                        ${doc.expiry_date ? `
                            <span class="date-main">
                                <i class="fas fa-calendar-alt"></i>
                                ${formatDate(doc.expiry_date)}
                            </span>
                        ` : '<span class="date-main">—</span>'}
                    </td>
                    <td class="size-cell">
                        ${formatFileSize(doc.file_size)}
                    </td>
                    <td class="action-buttons" onclick="event.stopPropagation()">
                        <button class="action-btn" onclick="editDocument(${docId})" title="Editar" data-id="${docId}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn" onclick="downloadDocument(${docId})" title="Descarregar" data-id="${docId}">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="action-btn danger" onclick="deleteDocument(${docId})" title="Eliminar" data-id="${docId}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    function renderEmptyState() {
        const container = document.getElementById('documentsTableBody');
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 60px;">
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <h3>Nenhum documento encontrado</h3>
                            <p>Comece a carregar os documentos da sua empresa</p>
                            <button class="btn-primary" onclick="openUploadModal()">
                                <i class="fas fa-upload"></i> Carregar Documento
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
    
    // CRUD OPERAÇÕES
    
    window.downloadDocument = function(id) {
        try {
            const docId = getValidId(id);
            if (!docId) {
                showToast('error', 'ID do documento inválido');
                return;
            }
            
            const token = getToken();
            if (!token) {
                showToast('error', 'Token de autenticação não encontrado');
                return;
            }
            
            const downloadUrl = `${CONFIG.API_URL}/documents/${docId}/download?token=${encodeURIComponent(token)}`;
            window.open(downloadUrl, '_blank');
            showToast('success', 'Download iniciado.');
        } catch (error) {
            console.error('[Download Error]', error);
            showToast('error', 'Erro ao fazer download');
        }
    };
    
    async function uploadDocument(formData) {
        showToast('info', 'A enviar documento...');
        const response = await apiRequest('/documents', { method: 'POST', body: formData });
        if (response && response.success) {
            showToast('success', 'Documento enviado com sucesso!');
            closeModal('uploadDocumentModal');
            resetUploadForm();
            await loadStats();
            await loadDocuments();
        } else {
            showToast('error', response?.message || 'Erro ao enviar documento');
        }
    }
    
    async function updateDocument(id, data) {
        const docId = getValidId(id);
        if (!docId) {
            showToast('error', 'ID do documento inválido');
            return;
        }
        
        showToast('info', 'A atualizar documento...');
        const response = await apiRequest(`/documents/${docId}`, { method: 'PUT', body: JSON.stringify(data) });
        if (response && response.success) {
            showToast('success', 'Documento atualizado com sucesso!');
            closeModal('editDocumentModal');
            await loadStats();
            await loadDocuments();
        } else {
            showToast('error', response?.message || 'Erro ao atualizar documento');
        }
    }
    
    async function deleteDocumentAction(id) {
        const docId = getValidId(id);
        if (!docId) {
            showToast('error', 'ID do documento inválido');
            return;
        }
        
        showToast('info', 'A eliminar documento...');
        const response = await apiRequest(`/documents/${docId}`, { method: 'DELETE' });
        if (response && response.success) {
            showToast('success', 'Documento eliminado com sucesso!');
            closeModal('deleteConfirmModal');
            await loadStats();
            await loadDocuments();
        } else {
            showToast('error', response?.message || 'Erro ao eliminar documento');
        }
    }
    
    // EVENT HANDLERS (GLOBAIS)
    
    window.openUploadModal = function() {
        resetUploadForm();
        // Forçar atualização dos selects antes de abrir o modal
        updateEntitySelects();
        openModal('uploadDocumentModal');
    };
    
    window.filterDocuments = function(status) {
        state.currentStatus = status;
        state.currentPage = 1;
        
        document.querySelectorAll('.filter-status-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.status === status) {
                btn.classList.add('active');
            }
        });
        
        loadDocuments();
    };
    
    window.changePage = function(page) {
        if (page < 1 || page > state.totalPages || page === state.currentPage) return;
        state.currentPage = page;
        loadDocuments();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    window.closeModal = closeModal;
    
    window.viewDocumentDetails = function(id) {
        try {
            const docId = getValidId(id);
            if (!docId) {
                showToast('error', 'ID do documento inválido');
                return;
            }
            
            apiRequest(`/documents/${docId}`).then(result => {
                if (result && result.success && result.data) {
                    const doc = result.data;
                    currentDocumentId = doc.id;
                    
                    const fileIcon = getFileIcon(doc.file_type);
                    const statusClass = getStatusClass(doc);
                    const statusText = getStatusText(doc);
                    
                    document.getElementById('detailModalContent').innerHTML = `
                        <div class="detail-grid">
                            <div class="detail-item full-width" style="text-align: center;">
                                <div class="doc-icon ${fileIcon.class}" style="width: 80px; height: 80px; font-size: 2.5rem; margin: 0 auto;">
                                    <i class="fas ${fileIcon.icon}"></i>
                                </div>
                            </div>
                            <div class="detail-item full-width">
                                <label><i class="fas fa-tag"></i> Título</label>
                                <div class="value">${escapeHtml(doc.title)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-file"></i> Nome do Arquivo</label>
                                <div class="value">${escapeHtml(doc.file_name || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-database"></i> Tamanho</label>
                                <div class="value">${formatFileSize(doc.file_size)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-building"></i> Entidade</label>
                                <div class="value">${escapeHtml(doc.entity_name || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-tag"></i> Tipo</label>
                                <div class="value">${escapeHtml(doc.type)}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-chart-line"></i> Status</label>
                                <div class="value">
                                    <span class="status-badge status-${statusClass}">
                                        ${statusText}
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-calendar"></i> Data de Validade</label>
                                <div class="value">${doc.expiry_date ? formatDate(doc.expiry_date) : 'Sem validade'}</div>
                            </div>
                            ${doc.description ? `
                                <div class="detail-item full-width">
                                    <label><i class="fas fa-align-left"></i> Descrição</label>
                                    <div class="value">${escapeHtml(doc.description)}</div>
                                </div>
                            ` : ''}
                            <div class="detail-item">
                                <label><i class="fas fa-user"></i> Carregado por</label>
                                <div class="value">${escapeHtml(doc.uploaded_by_name || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <label><i class="fas fa-calendar-alt"></i> Data de Carregamento</label>
                                <div class="value">${formatDateTime(doc.created_at)}</div>
                            </div>
                        </div>
                    `;
                    
                    const downloadBtn = document.querySelector('#viewDocumentModal .modal-footer .btn-primary');
                    if (downloadBtn) {
                        downloadBtn.onclick = function() {
                            downloadDocument(doc.id);
                        };
                    }
                    
                    openModal('viewDocumentModal');
                } else {
                    showToast('error', 'Erro ao carregar detalhes do documento');
                }
            }).catch(error => {
                console.error('[View Details Error]', error);
                showToast('error', 'Erro ao carregar detalhes do documento');
            });
        } catch (error) {
            console.error('[View Details Error]', error);
            showToast('error', 'Erro ao carregar detalhes do documento');
        }
    };
    
    window.editDocument = function(id) {
        try {
            const docId = getValidId(id);
            if (!docId) {
                showToast('error', 'ID do documento inválido');
                return;
            }
            
            apiRequest(`/documents/${docId}`).then(result => {
                if (result && result.success && result.data) {
                    const doc = result.data;
                    
                    document.getElementById('editDocumentId').value = doc.id;
                    document.getElementById('editTitle').value = doc.title || '';
                    document.getElementById('editDescription').value = doc.description || '';
                    document.getElementById('editExpiryDate').value = doc.expiry_date || '';
                    document.getElementById('editReminderDays').value = doc.reminder_days || 30;
                    
                    openModal('editDocumentModal');
                } else {
                    showToast('error', 'Erro ao carregar dados do documento');
                }
            }).catch(error => {
                console.error('[Edit Error]', error);
                showToast('error', 'Erro ao carregar dados do documento');
            });
        } catch (error) {
            console.error('[Edit Error]', error);
            showToast('error', 'Erro ao carregar dados do documento');
        }
    };
    
    window.deleteDocument = function(id) {
        try {
            const docId = getValidId(id);
            if (!docId) {
                showToast('error', 'ID do documento inválido');
                return;
            }
            
            currentDocumentId = docId;
            openModal('deleteConfirmModal');
        } catch (error) {
            console.error('[Delete Error]', error);
            showToast('error', 'Erro ao preparar exclusão');
        }
    };
    
    window.confirmDeleteDocument = function() {
        if (currentDocumentId) {
            deleteDocumentAction(currentDocumentId);
        } else {
            showToast('error', 'ID do documento inválido');
        }
    };
    
    // FORMULÁRIO DE UPLOAD

    function resetUploadForm() {
        const form = document.getElementById('uploadDocumentForm');
        if (form) form.reset();
        const entitySelect = document.getElementById('addEntityId');
        if (entitySelect) entitySelect.innerHTML = '<option value="">Selecione primeiro o tipo</option>';
        document.getElementById('addEntityType').value = '';
        document.getElementById('addType').value = '';
        document.getElementById('addTitle').value = '';
        document.getElementById('addDescription').value = '';
        document.getElementById('addExpiryDate').value = '';
        document.getElementById('addReminderDays').value = '30';
        selectedFile = null;
        const fileInfo = document.getElementById('fileInfo');
        if (fileInfo) fileInfo.classList.remove('show');
        const fileInput = document.getElementById('fileInput');
        if (fileInput) fileInput.value = '';
    }
    
    // Drag and drop
    const dropZone = document.getElementById('dropZone');
    if (dropZone) {
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            if (e.dataTransfer.files.length > 0) {
                document.getElementById('fileInput').files = e.dataTransfer.files;
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });
        
        dropZone.addEventListener('click', () => {
            document.getElementById('fileInput').click();
        });
    }
    
    const fileInputElement = document.getElementById('fileInput');
    if (fileInputElement) {
        fileInputElement.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleFileSelect(e.target.files[0]);
        });
    }
    
    function handleFileSelect(file) {
        if (!CONFIG.ALLOWED_TYPES.includes(file.type)) {
            showToast('error', 'Tipo de arquivo não permitido');
            return;
        }
        if (file.size > CONFIG.MAX_FILE_SIZE) {
            showToast('error', 'Arquivo muito grande. Máximo 10MB');
            return;
        }
        selectedFile = file;
        const fileInfo = document.getElementById('fileInfo');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        if (fileInfo) fileInfo.classList.add('show');
        if (fileNameDisplay) fileNameDisplay.textContent = file.name;
    }
    
    const uploadFormElement = document.getElementById('uploadDocumentForm');
    if (uploadFormElement) {
        uploadFormElement.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!selectedFile) {
                showToast('warning', 'Selecione um arquivo');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('entity_type', document.getElementById('addEntityType').value);
            formData.append('entity_id', document.getElementById('addEntityId').value);
            formData.append('type', document.getElementById('addType').value);
            formData.append('title', document.getElementById('addTitle').value.trim());
            formData.append('description', document.getElementById('addDescription').value);
            formData.append('expiry_date', document.getElementById('addExpiryDate').value);
            formData.append('reminder_days', document.getElementById('addReminderDays').value);
            
            if (!formData.get('entity_type') || !formData.get('entity_id') || !formData.get('type') || !formData.get('title')) {
                showToast('warning', 'Preencha todos os campos obrigatórios');
                return;
            }
            await uploadDocument(formData);
        });
    }
    
    // FORMULÁRIO DE EDIÇÃO

    const editFormElement = document.getElementById('editDocumentForm');
    if (editFormElement) {
        editFormElement.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('editDocumentId').value;
            if (!id) {
                showToast('error', 'ID do documento inválido');
                return;
            }
            
            const data = {
                title: document.getElementById('editTitle').value.trim(),
                description: document.getElementById('editDescription').value,
                expiry_date: document.getElementById('editExpiryDate').value,
                reminder_days: parseInt(document.getElementById('editReminderDays').value) || 30
            };
            if (!data.title) {
                showToast('warning', 'O título é obrigatório');
                return;
            }
            await updateDocument(id, data);
        });
    }
    
    // INICIALIZAÇÃO

    function initSearch() {
        const searchInput = document.getElementById('globalSearch');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const value = e.target.value.trim();
                    if (value.length >= 2 || value.length === 0) {
                        console.log('Search:', value);
                    }
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
        localStorage.removeItem('user_data');
        sessionStorage.removeItem('user_data');
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
        console.log('[CARDOXIS] Inicializando Documents v13.1.0...');
        
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        initSidebar();
        initSearch();
        
        // Carregar dados na ordem correta
        await loadVehicles();
        await loadDrivers();
        await loadStats();
        await loadDocuments();
        
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            loadStats();
            updateLastUpdateTime();
        }, 60000);
        
        console.log('[CARDOXIS] Documents inicializado com sucesso!');
        console.log('[CARDOXIS] Motoristas carregados:', state.drivers.length);
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();