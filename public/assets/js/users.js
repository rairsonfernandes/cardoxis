/**
 * CARDOXIS Admin Users JavaScript RF 
 */

(function() {
    'use strict';
    

    // CONFIGURAÇÕES

    const API_URL = 'http://localhost/cardoxis/api/v1';
    let currentPage = 1;
    let totalPages = 1;
    let perPage = 15;
    let currentFilters = {
        search: '',
        role: '',
        status: ''
    };
    let currentUserId = null;
    
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
            console.warn('[CARDOXIS] Sem token');
            return { success: false, message: 'Não autenticado' };
        }
        
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`,
                    ...options.headers
                }
            });
            
            if (response.status === 401) {
                localStorage.removeItem('auth_token');
                sessionStorage.removeItem('auth_token');
                window.location.href = '/cardoxis/login';
                return null;
            }
            
            const data = await response.json();
            console.log('[CARDOXIS] API Response:', endpoint, data);
            return data;
        } catch (error) {
            console.error('[API Error]', endpoint, error);
            return { success: false, message: 'Erro de ligação ao servidor' };
        }
    }
    
    // TOAST NOTIFICATIONS

    function showToast(type, message, duration = 4000) {
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
        
        setTimeout(() => {
            if (toast.parentElement) toast.remove();
        }, duration);
    }
    
    // LOADING

    function showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            if (show) overlay.classList.add('active');
            else overlay.classList.remove('active');
        }
    }
    
    // CARREGAR EMPRESAS PARA DROPDOWN

    async function loadCompanies() {
        try {
            const response = await apiRequest('/admin/companies?limit=1000');
            if (response && response.success) {
                const companies = response.data || [];
                const companySelect = document.getElementById('userCompany');
                if (companySelect) {
                    const currentValue = companySelect.value;
                    companySelect.innerHTML = '<option value="">Seleccionar empresa</option>';
                    companies.forEach(company => {
                        const option = document.createElement('option');
                        option.value = company.id;
                        option.textContent = company.name;
                        companySelect.appendChild(option);
                    });
                    if (currentValue) companySelect.value = currentValue;
                }
            }
        } catch (error) {
            console.error('Erro ao carregar empresas:', error);
        }
    }
    
    // CARREGAR UTILIZADORES

    async function loadUsers() {
        console.log('[CARDOXIS] Carregando utilizadores...');
        showLoading(true);
        
        try {
            const params = new URLSearchParams({
                page: currentPage,
                limit: perPage,
                search: currentFilters.search,
                role: currentFilters.role,
                status: currentFilters.status
            });
            
            const response = await apiRequest(`/admin/users?${params}`);
            
            if (response && response.success) {
                let users = [];
                let pagination = {};
                
                if (response.data && Array.isArray(response.data)) {
                    users = response.data;
                    pagination = response.pagination || {};
                } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
                    users = response.data.data;
                    pagination = response.data.pagination || {};
                } else {
                    users = [];
                    pagination = {};
                }
                
                renderUsersTable(users);
                updatePagination(pagination, users.length);
                updateStats(users);
            } else {
                renderUsersTable([]);
                updateStats([]);
            }
        } catch (error) {
            console.error('[CARDOXIS] Erro:', error);
            renderUsersTable([]);
            updateStats([]);
        }
        
        showLoading(false);
    }
    
    function renderUsersTable(users) {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) return;
        
        if (!Array.isArray(users)) users = [];
        
        if (users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 60px;">
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>Nenhum utilizador encontrado</h3>
                            <p>Clique em "Novo Utilizador" para adicionar</p>
                            <button class="btn-primary" onclick="openCreateModal()">
                                <i class="fas fa-plus"></i> Novo Utilizador
                            </button>
                            <button class="btn-secondary" onclick="openImportModal()" style="margin-left:10px;">
                                <i class="fas fa-file-import"></i> Importar
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = users.map(user => `
            <tr>
                <td>
                    <div class="user-name">${escapeHtml(user.name)}</div>
                    <div class="user-email">${escapeHtml(user.email)}</div>
                </td>
                <td>${getRoleBadge(user.role)}</td>
                <td>${getStatusBadge(user.status)}</td>
                <td>${user.company_name || '-'}</td>
                <td>${formatDate(user.last_login_at)}</td>
                <td>${formatDate(user.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view" onclick="viewUser(${user.id})" title="Ver detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit" onclick="editUser(${user.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn reset" onclick="resetPassword(${user.id})" title="Resetar senha">
                            <i class="fas fa-key"></i>
                        </button>
                        ${user.status === 'active' ? `
                            <button class="action-btn suspend" onclick="suspendUser(${user.id})" title="Suspender">
                                <i class="fas fa-pause-circle"></i>
                            </button>
                        ` : `
                            <button class="action-btn activate" onclick="activateUser(${user.id})" title="Activar">
                                <i class="fas fa-play-circle"></i>
                            </button>
                        `}
                        <button class="action-btn delete" onclick="confirmDeleteUser(${user.id}, '${escapeHtml(user.name)}')" title="Eliminar">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    function getRoleBadge(role) {
        const badges = {
            'super_admin': '<span class="role-badge role-super_admin"><i class="fas fa-crown"></i> Super Admin</span>',
            'admin': '<span class="role-badge role-admin"><i class="fas fa-shield-alt"></i> Admin</span>',
            'manager': '<span class="role-badge role-manager"><i class="fas fa-chart-line"></i> Gestor</span>',
            'user': '<span class="role-badge role-user"><i class="fas fa-user"></i> Utilizador</span>'
        };
        return badges[role] || badges.user;
    }
    
    function getStatusBadge(status) {
        const badges = {
            'active': '<span class="status-badge status-active"><i class="fas fa-check-circle"></i> Activo</span>',
            'inactive': '<span class="status-badge status-inactive"><i class="fas fa-minus-circle"></i> Inactivo</span>',
            'suspended': '<span class="status-badge status-suspended"><i class="fas fa-pause-circle"></i> Suspenso</span>'
        };
        return badges[status] || badges.inactive;
    }
    
    function updateStats(users) {
        const total = users ? users.length : 0;
        const active = users ? users.filter(u => u.status === 'active').length : 0;
        const inactive = users ? users.filter(u => u.status === 'inactive').length : 0;
        const suspended = users ? users.filter(u => u.status === 'suspended').length : 0;
        const admins = users ? users.filter(u => u.role === 'admin' || u.role === 'super_admin').length : 0;
        
        const totalEl = document.getElementById('totalUsers');
        const activeEl = document.getElementById('activeUsers');
        const inactiveEl = document.getElementById('inactiveUsers');
        const suspendedEl = document.getElementById('suspendedUsers');
        const adminsEl = document.getElementById('adminUsers');
        
        if (totalEl) totalEl.textContent = total;
        if (activeEl) activeEl.textContent = active;
        if (inactiveEl) inactiveEl.textContent = inactive;
        if (suspendedEl) suspendedEl.textContent = suspended;
        if (adminsEl) adminsEl.textContent = admins;
    }
    
    function updatePagination(pagination, totalItems) {
        const total = pagination.total || totalItems;
        totalPages = pagination.last_page || Math.ceil(total / perPage) || 1;
        currentPage = pagination.current_page || currentPage;
        
        const pageInfo = document.getElementById('pageInfo');
        if (pageInfo && total > 0) {
            const start = ((currentPage - 1) * perPage) + 1;
            const end = Math.min(currentPage * perPage, total);
            pageInfo.textContent = `Mostrando ${start} - ${end} de ${total} utilizadores`;
        } else if (pageInfo) {
            pageInfo.textContent = 'Nenhum utilizador encontrado';
        }
        
        const paginationContainer = document.getElementById('pagination');
        if (!paginationContainer) return;
        
        let html = `
            <div class="pagination-controls">
                <button onclick="goToPage(1)" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-double-left"></i>
                </button>
                <button onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i>
                </button>
        `;
        
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<button onclick="goToPage(${i})" class="${i === currentPage ? 'active' : ''}">${i}</button>`;
        }
        
        html += `
                <button onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button onclick="goToPage(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''}>
                    <i class="fas fa-chevron-double-right"></i>
                </button>
            </div>
        `;
        
        paginationContainer.innerHTML = html;
    }
    
    // ============================================
    // CRUD OPERATIONS
    // ============================================
    window.openCreateModal = function() {
        document.getElementById('modalTitle').textContent = 'Novo Utilizador';
        document.getElementById('modalIcon').className = 'fas fa-user-plus';
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('userStatus').value = 'active';
        document.getElementById('userRole').value = 'user';
        document.getElementById('userPassword').value = '';
        document.getElementById('userModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId || 'userModal');
        if (modal) modal.classList.remove('active');
        document.body.style.overflow = '';
    };
    
    window.generatePassword = function() {
        const length = 10;
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        let password = '';
        for (let i = 0; i < length; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('userPassword').value = password;
        showToast('info', 'Senha gerada automaticamente');
    };
    
    window.saveUser = async function() {
        const id = document.getElementById('userId').value;
        const formData = {
            name: document.getElementById('userName')?.value.trim() || '',
            email: document.getElementById('userEmail')?.value.trim() || '',
            password: document.getElementById('userPassword')?.value || '',
            role: document.getElementById('userRole')?.value || 'user',
            phone: document.getElementById('userPhone')?.value.trim() || '',
            status: document.getElementById('userStatus')?.value || 'active',
            company_id: document.getElementById('userCompany')?.value || null
        };
        
        if (!formData.name) {
            showToast('error', 'O nome é obrigatório');
            document.getElementById('userName').focus();
            return;
        }
        
        if (!formData.email) {
            showToast('error', 'O email é obrigatório');
            document.getElementById('userEmail').focus();
            return;
        }
        
        if (!id && !formData.password) {
            showToast('error', 'A senha é obrigatória para novos utilizadores');
            document.getElementById('userPassword').focus();
            return;
        }
        
        if (!id && formData.password.length < 6) {
            showToast('error', 'A senha deve ter no mínimo 6 caracteres');
            return;
        }
        
        if (!isValidEmail(formData.email)) {
            showToast('error', 'Email inválido');
            return;
        }
        
        showLoading(true);
        
        let response;
        if (id) {
            if (!formData.password) delete formData.password;
            response = await apiRequest(`/admin/users/${id}`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });
        } else {
            response = await apiRequest('/admin/users', {
                method: 'POST',
                body: JSON.stringify(formData)
            });
        }
        
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', id ? 'Utilizador actualizado com sucesso!' : 'Utilizador criado com sucesso!');
            closeModal('userModal');
            loadUsers();
        } else {
            showToast('error', response?.message || 'Erro ao guardar utilizador');
        }
    };
    
    window.viewUser = async function(id) {
        showLoading(true);
        const response = await apiRequest(`/admin/users/${id}`);
        showLoading(false);
        
        if (response && response.success) {
            const u = response.data;
            document.getElementById('viewUserName').innerHTML = `<strong>${escapeHtml(u.name || 'N/A')}</strong>`;
            document.getElementById('viewUserEmail').textContent = u.email || 'N/A';
            document.getElementById('viewUserRole').innerHTML = getRoleBadge(u.role || 'user');
            document.getElementById('viewUserPhone').textContent = u.phone || '-';
            document.getElementById('viewUserStatus').innerHTML = getStatusBadge(u.status || 'inactive');
            document.getElementById('viewUserCompany').textContent = u.company_name || '-';
            document.getElementById('viewUserCreated').textContent = formatDate(u.created_at);
            document.getElementById('viewUserLastLogin').textContent = formatDate(u.last_login_at) || 'Nunca';
            
            document.getElementById('viewModal').setAttribute('data-user-id', id);
            document.getElementById('viewModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            showToast('error', response?.message || 'Erro ao carregar detalhes do utilizador');
        }
    };
    
    window.editUser = async function(id) {
        showLoading(true);
        const response = await apiRequest(`/admin/users/${id}`);
        showLoading(false);
        
        if (response && response.success) {
            const user = response.data;
            document.getElementById('modalTitle').textContent = 'Editar Utilizador';
            document.getElementById('modalIcon').className = 'fas fa-edit';
            document.getElementById('userId').value = user.id;
            document.getElementById('userName').value = user.name || '';
            document.getElementById('userEmail').value = user.email || '';
            document.getElementById('userPhone').value = user.phone || '';
            document.getElementById('userRole').value = user.role || 'user';
            document.getElementById('userStatus').value = user.status || 'active';
            document.getElementById('userPassword').value = '';
            document.getElementById('userPassword').placeholder = 'Deixar em branco para manter a actual';
            
            if (user.company_id) {
                document.getElementById('userCompany').value = user.company_id;
            } else {
                document.getElementById('userCompany').value = '';
            }
            
            document.getElementById('userModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            showToast('error', response?.message || 'Erro ao carregar dados do utilizador');
        }
    };
    
    window.resetPassword = async function(id) {
        if (!confirm('Tem certeza que deseja redefinir a senha deste utilizador?')) return;
        
        showLoading(true);
        const response = await apiRequest(`/admin/users/${id}/reset-password`, { method: 'POST' });
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', `Nova senha: ${response.data.new_password}`);
        } else {
            showToast('error', response?.message || 'Erro ao redefinir senha');
        }
    };
    
    window.suspendUser = async function(id) {
        if (!confirm('Tem certeza que deseja suspender este utilizador?')) return;
        
        showLoading(true);
        const response = await apiRequest(`/admin/users/${id}/suspend`, { method: 'POST' });
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', 'Utilizador suspenso com sucesso!');
            loadUsers();
        } else {
            showToast('error', response?.message || 'Erro ao suspender utilizador');
        }
    };
    
    window.activateUser = async function(id) {
        if (!confirm('Tem certeza que deseja activar este utilizador?')) return;
        
        showLoading(true);
        const response = await apiRequest(`/admin/users/${id}/activate`, { method: 'POST' });
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', 'Utilizador activado com sucesso!');
            loadUsers();
        } else {
            showToast('error', response?.message || 'Erro ao activar utilizador');
        }
    };
    
    // CONFIRMAR ELIMINAÇÃO DE UTILIZADOR (CORRIGIDO)

    window.confirmDeleteUser = function(id, name) {
        if (!id) {
            showToast('error', 'ID do utilizador inválido');
            return;
        }
        
        currentUserId = id;
        
        // Preencher o modal de confirmação
        document.getElementById('confirmDeleteTitle').textContent = 'Eliminar Utilizador';
        document.getElementById('confirmDeleteMessage').innerHTML = `
            Tem certeza que deseja <strong>eliminar permanentemente</strong> o utilizador 
            "<strong>${escapeHtml(name)}</strong>"?<br><br>
            <span style="color: #DE350B; font-size: 0.9rem;">
                <i class="fas fa-exclamation-triangle"></i> 
                Esta acção não pode ser desfeita e todos os dados associados serão removidos.
            </span>
        `;
        
        // Abrir o modal
        const modal = document.getElementById('confirmDeleteModal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };
    
    // EXECUTAR ELIMINAÇÃO DE UTILIZADOR (CORRIGIDO)

    window.executeDeleteUser = async function() {
        if (!currentUserId) {
            showToast('error', 'ID do utilizador não encontrado');
            return;
        }
        
        // Fechar modal de confirmação
        closeModal('confirmDeleteModal');
        
        showLoading(true);
        showToast('info', 'A eliminar utilizador...', 2000);
        
        try {
            const response = await apiRequest(`/admin/users/${currentUserId}`, { 
                method: 'DELETE' 
            });
            
            showLoading(false);
            
            console.log('[CARDOXIS] Delete response:', response);
            
            if (response && response.success) {
                showToast('success', 'Utilizador eliminado com sucesso!');
                // Recarregar a lista de utilizadores
                loadUsers();
            } else {
                const errorMsg = response?.message || 'Erro ao eliminar utilizador';
                showToast('error', errorMsg);
                console.error('[CARDOXIS] Delete error:', response);
            }
        } catch (error) {
            showLoading(false);
            console.error('[CARDOXIS] Delete exception:', error);
            showToast('error', 'Erro ao eliminar utilizador: ' + error.message);
        }
        
        currentUserId = null;
    };
    
    // FILTROS E PAGINAÇÃO

    window.searchUsers = function() {
        currentFilters.search = document.getElementById('searchInput')?.value || '';
        currentPage = 1;
        loadUsers();
    };
    
    window.filterByRole = function() {
        currentFilters.role = document.getElementById('roleFilter')?.value || '';
        currentPage = 1;
        loadUsers();
    };
    
    window.filterByStatus = function() {
        currentFilters.status = document.getElementById('statusFilter')?.value || '';
        currentPage = 1;
        loadUsers();
    };
    
    window.resetFilters = function() {
        const searchInput = document.getElementById('searchInput');
        const roleFilter = document.getElementById('roleFilter');
        const statusFilter = document.getElementById('statusFilter');
        
        if (searchInput) searchInput.value = '';
        if (roleFilter) roleFilter.value = '';
        if (statusFilter) statusFilter.value = '';
        
        currentFilters = { search: '', role: '', status: '' };
        currentPage = 1;
        loadUsers();
        showToast('info', 'Filtros limpos');
    };
    
    window.goToPage = function(page) {
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        loadUsers();
    };
    
    // EXPORTAR UTILIZADORES

    window.exportUsers = async function() {
        const token = getToken();
        if (!token) {
            showToast('error', 'Sessão expirada');
            return;
        }
        
        showToast('info', 'A preparar exportação...', 2000);
        showLoading(true);
        
        try {
            const response = await fetch(`${API_URL}/admin/users/export`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            
            if (response.status === 401) {
                localStorage.removeItem('auth_token');
                window.location.href = '/cardoxis/login';
                return;
            }
            
            const data = await response.json();
            
            if (data.success && data.data && data.data.download_url) {
                window.open(data.data.download_url, '_blank');
                showToast('success', `Exportação iniciada! ${data.data.total || 0} registos.`);
            } else {
                showToast('error', data.message || 'Erro ao exportar utilizadores');
            }
        } catch (error) {
            console.error('Export error:', error);
            showToast('error', 'Erro ao exportar utilizadores');
        }
        showLoading(false);
    };
    
    // IMPORTAÇÃO DE UTILIZADORES

    window.openImportModal = function() {
        const modal = document.getElementById('importModal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Resetar todos os campos
            document.getElementById('importFile').value = '';
            document.getElementById('importFileLabel').textContent = 'Nenhum ficheiro selecionado';
            document.getElementById('importProgress').style.display = 'none';
            document.getElementById('importResult').style.display = 'none';
            document.getElementById('importResultSuccess').style.display = 'none';
            document.getElementById('importResultError').style.display = 'none';
            document.getElementById('importFile').style.borderColor = '#DFE1E6';
            document.getElementById('importBtn').disabled = false;
            document.getElementById('importBtn').innerHTML = '<i class="fas fa-upload"></i> Importar';
            document.getElementById('fileUploadArea').style.borderColor = '#DFE1E6';
            document.getElementById('fileUploadArea').style.background = '#FAFBFC';
        }
    };
    
    window.resetImport = function() {
        document.getElementById('importFile').value = '';
        document.getElementById('importFileLabel').textContent = 'Nenhum ficheiro selecionado';
        document.getElementById('importResult').style.display = 'none';
        document.getElementById('importResultSuccess').style.display = 'none';
        document.getElementById('importResultError').style.display = 'none';
        document.getElementById('importProgress').style.display = 'none';
        document.getElementById('importFile').style.borderColor = '#DFE1E6';
        document.getElementById('importBtn').disabled = false;
        document.getElementById('importBtn').innerHTML = '<i class="fas fa-upload"></i> Importar';
        document.getElementById('fileUploadArea').style.borderColor = '#DFE1E6';
        document.getElementById('fileUploadArea').style.background = '#FAFBFC';
        showToast('info', 'Pronto para uma nova importação');
    };
    
    // Validar ficheiro
    function validateFile(file) {
        if (!file) {
            return { valid: false, message: 'Nenhum ficheiro selecionado' };
        }
        
        const validExtensions = ['csv'];
        const extension = file.name.split('.').pop().toLowerCase();
        
        if (!validExtensions.includes(extension)) {
            return { valid: false, message: 'Formato não suportado. Use apenas CSV.' };
        }
        
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            return { valid: false, message: 'Ficheiro muito grande. Máximo 5MB.' };
        }
        
        if (file.size === 0) {
            return { valid: false, message: 'Ficheiro vazio.' };
        }
        
        return { valid: true, message: '' };
    }
    
    // Evento de seleção de ficheiro
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('importFile');
        const fileLabel = document.getElementById('importFileLabel');
        const uploadArea = document.getElementById('fileUploadArea');
        
        if (fileInput && fileLabel) {
            fileInput.addEventListener('change', function(e) {
                const file = this.files[0];
                if (file) {
                    const validation = validateFile(file);
                    if (!validation.valid) {
                        fileLabel.textContent = '❌ ' + validation.message;
                        this.style.borderColor = '#DE350B';
                        uploadArea.style.borderColor = '#DE350B';
                        showToast('error', validation.message);
                    } else {
                        fileLabel.textContent = '✅ ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                        this.style.borderColor = '#00875A';
                        uploadArea.style.borderColor = '#00875A';
                        uploadArea.style.background = '#E3FCEF';
                        showToast('success', 'Ficheiro válido! Pronto para importar.');
                    }
                } else {
                    fileLabel.textContent = 'Nenhum ficheiro selecionado';
                    this.style.borderColor = '#DFE1E6';
                    uploadArea.style.borderColor = '#DFE1E6';
                    uploadArea.style.background = '#FAFBFC';
                }
            });
        }
    });
    
    window.importUsers = async function() {
        const fileInput = document.getElementById('importFile');
        const file = fileInput.files[0];
        const importBtn = document.getElementById('importBtn');
        const uploadArea = document.getElementById('fileUploadArea');
        
        // Validar ficheiro
        const validation = validateFile(file);
        if (!validation.valid) {
            showToast('error', validation.message);
            fileInput.style.borderColor = '#DE350B';
            uploadArea.style.borderColor = '#DE350B';
            uploadArea.style.background = '#FFEBE6';
            return;
        }
        
        // Desabilitar botão e mostrar loading
        importBtn.disabled = true;
        importBtn.innerHTML = '<span class="spinner-small"></span> A importar...';
        fileInput.style.borderColor = '#DFE1E6';
        uploadArea.style.borderColor = '#DFE1E6';
        uploadArea.style.background = '#FAFBFC';
        
        // Mostrar progresso
        document.getElementById('importProgress').style.display = 'block';
        document.getElementById('importProgressText').textContent = 'A processar ficheiro...';
        document.getElementById('importResult').style.display = 'none';
        document.getElementById('importResultSuccess').style.display = 'none';
        document.getElementById('importResultError').style.display = 'none';
        
        showLoading(true);
        
        try {
            const formData = new FormData();
            formData.append('file', file);
            
            const token = getToken();
            const response = await fetch(`${API_URL}/admin/users/import`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                body: formData
            });
            
            const data = await response.json();
            
            showLoading(false);
            document.getElementById('importProgress').style.display = 'none';
            
            // Mostrar resultado
            document.getElementById('importResult').style.display = 'block';
            
            if (data.success) {
                document.getElementById('importResultSuccess').style.display = 'block';
                document.getElementById('importResultError').style.display = 'none';
                
                document.getElementById('importTotal').textContent = data.data.total || 0;
                document.getElementById('importCreated').textContent = data.data.created || 0;
                document.getElementById('importUpdated').textContent = data.data.updated || 0;
                document.getElementById('importFailed').textContent = data.data.failed || 0;
                
                showToast('success', `Importação concluída! ${data.data.created || 0} utilizadores criados.`);
                
                // Recarregar lista
                loadUsers();
                
                // Fechar modal automaticamente após 3 segundos
                setTimeout(() => {
                    closeModal('importModal');
                    showToast('success', 'Importação concluída com sucesso!');
                }, 3000);
                
            } else {
                document.getElementById('importResultSuccess').style.display = 'none';
                document.getElementById('importResultError').style.display = 'block';
                document.getElementById('importErrorMessage').textContent = data.message || 'Erro ao importar ficheiro';
                
                showToast('error', data.message || 'Erro ao importar ficheiro');
                
                importBtn.disabled = false;
                importBtn.innerHTML = '<i class="fas fa-upload"></i> Importar';
            }
            
        } catch (error) {
            console.error('Import error:', error);
            showLoading(false);
            document.getElementById('importProgress').style.display = 'none';
            
            document.getElementById('importResult').style.display = 'block';
            document.getElementById('importResultSuccess').style.display = 'none';
            document.getElementById('importResultError').style.display = 'block';
            document.getElementById('importErrorMessage').textContent = 'Erro interno ao processar o ficheiro. Verifique o formato.';
            
            showToast('error', 'Erro ao importar ficheiro');
            
            importBtn.disabled = false;
            importBtn.innerHTML = '<i class="fas fa-upload"></i> Importar';
        }
    };
    
    window.downloadTemplate = function() {
        const headers = ['Nome', 'Email', 'Papel', 'Telefone', 'Estado', 'Empresa'];
        let csv = headers.join(';') + '\n';
        csv += 'João Silva;joao@empresa.pt;user;+351 912 345 678;active;CARDOXIS Administração\n';
        csv += 'Maria Santos;maria@empresa.pt;manager;+351 913 456 789;active;CARDOXIS Administração\n';
        csv += 'Pedro Costa;pedro@empresa.pt;admin;+351 914 567 890;active;CARDOXIS Administração\n';
        csv += 'Ana Pereira;ana@empresa.pt;user;+351 915 678 901;inactive;CARDOXIS Administração\n';
        
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'modelo_utilizadores.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
        
        showToast('info', 'Modelo CSV descarregado');
    };
    
    // CARREGAR PAPÉIS

    async function loadRoles() {
        try {
            const response = await apiRequest('/admin/users/roles');
            if (response && response.success) {
                const roleSelect = document.getElementById('userRole');
                if (roleSelect && response.data) {
                    const currentValue = roleSelect.value;
                    roleSelect.innerHTML = '<option value="">Seleccionar papel</option>' +
                        response.data.map(role => `<option value="${role.value}">${role.label}</option>`).join('');
                    if (currentValue) roleSelect.value = currentValue;
                }
            }
        } catch (error) {
            console.error('Erro ao carregar papéis:', error);
        }
    }
    
    // UTILITÁRIOS

    function isValidEmail(email) {
        const re = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
        return re.test(email);
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-PT') + ' ' + date.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // ATUALIZAR MENU ATIVO

    function updateActiveMenu() {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-item').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '/cardoxis/admin/users') {
                link.classList.add('active');
            }
        });
    }

    // INICIALIZAÇÃO
    
    document.addEventListener('DOMContentLoaded', () => {
        console.log('[CARDOXIS] Inicializando página de utilizadores v8.0');
        
        if (!checkAuth()) return;
        
        updateActiveMenu();
        loadUsers();
        loadRoles();
        loadCompanies();
        
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') searchUsers();
            });
        }
        
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    });
    
    // Expor funções globalmente
    window.loadUsers = loadUsers;
    window.goToPage = goToPage;
    window.viewUser = viewUser;
    window.editUser = editUser;
    window.resetPassword = resetPassword;
    window.suspendUser = suspendUser;
    window.activateUser = activateUser;
    window.confirmDeleteUser = confirmDeleteUser;
    window.executeDeleteUser = executeDeleteUser;
    window.searchUsers = searchUsers;
    window.filterByRole = filterByRole;
    window.filterByStatus = filterByStatus;
    window.resetFilters = resetFilters;
    window.exportUsers = exportUsers;
    window.openCreateModal = openCreateModal;
    window.closeModal = closeModal;
    window.saveUser = saveUser;
    window.generatePassword = generatePassword;
    window.openImportModal = openImportModal;
    window.resetImport = resetImport;
    window.importUsers = importUsers;
    window.downloadTemplate = downloadTemplate;
})();