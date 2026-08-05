/**
 * CARDOXIS - Profile Management JavaScript
 * Version: 11.0.0 (Enterprise Edition - Com Sincronização em Tempo Real)
 */

(function() {
    'use strict';
    
    const CONFIG = {
        API_URL: window.CARDOXIS?.API_URL || window.location.origin + '/cardoxis/api/v1',
        REFRESH_INTERVAL: 60000
    };
    
    let refreshInterval = null;
    let currentTab = 'personal';
    let isLoggingOut = false;
    let userData = null;
    
    // AUTENTICAÇÃO
    
    function getToken() {
        return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    }
    
    function clearSession() {
        try {
            localStorage.removeItem('auth_token');
            sessionStorage.removeItem('auth_token');
            localStorage.removeItem('user_data');
            sessionStorage.removeItem('user_data');
            localStorage.removeItem('user_name');
            sessionStorage.removeItem('user_name');
        } catch (e) {
            console.warn('[Clear Session]', e);
        }
    }
    
    function redirectToLogout() {
        if (isLoggingOut) return;
        isLoggingOut = true;
        clearSession();
        setTimeout(function() {
            window.location.replace('/cardoxis/logout');
        }, 500);
    }
    
    function handleUnauthorized() {
        clearSession();
        window.location.replace('/cardoxis/login?expired=1');
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
            
            if (response.status === 400) {
                const data = await response.json();
                return { success: false, message: data.message || 'Requisição inválida' };
            }
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('[API Error]', endpoint, error);
            showToast('error', 'Erro na comunicação: ' + error.message);
            return { success: false, message: error.message, data: null };
        }
    }
    
    // UTILITÁRIOS

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatDate(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '—';
        return date.toLocaleDateString('pt-PT') + ' ' + date.toLocaleTimeString('pt-PT');
    }
    
    function showToast(type, message) {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            container.id = 'toastContainer';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const iconMap = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        const icon = iconMap[type] || 'fa-info-circle';
        
        toast.innerHTML = `
            <i class="fas ${icon}"></i>
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
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    function updateElementText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }
    
    function updateLastUpdateTime() {
        const element = document.getElementById('lastUpdateTime');
        if (element) {
            element.textContent = new Date().toLocaleTimeString('pt-PT');
        }
    }
    
    // ATUALIZAR UI EM TEMPO REAL

    function updateUI(user) {
        if (!user) return;
        
        userData = user;
        
        // 1. Atualizar nome no perfil
        const profileNameDisplay = document.getElementById('profileNameDisplay');
        if (profileNameDisplay) {
            profileNameDisplay.textContent = user.name || '';
        }
        
        // 2. Atualizar email no perfil
        const profileEmailDisplay = document.getElementById('profileEmailDisplay');
        if (profileEmailDisplay) {
            profileEmailDisplay.textContent = user.email || '';
        }
        
        // 3. Atualizar nome no header (welcome-title)
        const welcomeTitle = document.querySelector('.welcome-title .user-highlight');
        if (welcomeTitle) {
            welcomeTitle.textContent = user.name || '';
        }
        
        // 4. Atualizar nome na sidebar
        const sidebarUserName = document.querySelector('.user-info .user-name');
        if (sidebarUserName) {
            sidebarUserName.textContent = user.name || '';
        }
        
        // 5. Atualizar iniciais do avatar
        const avatarInitials = document.querySelector('.avatar-initials');
        if (avatarInitials && user.name) {
            const initials = user.name
                .split(' ')
                .map(word => word.charAt(0).toUpperCase())
                .join('')
                .substring(0, 2);
            avatarInitials.textContent = initials;
        }
        
        // 6. Atualizar localStorage para persistência
        try {
            localStorage.setItem('user_name', user.name || '');
            sessionStorage.setItem('user_name', user.name || '');
        } catch (e) {
            console.warn('[Update UI] Erro ao salvar no storage', e);
        }
        
        // 7. Atualizar título da página (opcional)
        if (user.name) {
            document.title = `${user.name} | CARDOXIS`;
        }
        
        console.log('[Profile] UI atualizada com sucesso para:', user.name);
    }
    
    // SINCronizar COM SIDEBAR

    function syncWithSidebar(user) {
        // Atualizar o nome do usuário na sidebar
        const sidebarUserName = document.querySelector('.user-info .user-name');
        if (sidebarUserName && user.name) {
            sidebarUserName.textContent = user.name;
        }
        
        // Atualizar o email na sidebar
        const sidebarUserEmail = document.querySelector('.user-info .user-email');
        if (sidebarUserEmail && user.email) {
            sidebarUserEmail.textContent = user.email;
        }
        
        // Atualizar as iniciais do avatar na sidebar
        const sidebarAvatar = document.querySelector('.user-avatar');
        if (sidebarAvatar && user.name) {
            const initials = user.name
                .split(' ')
                .map(word => word.charAt(0).toUpperCase())
                .join('')
                .substring(0, 2);
            sidebarAvatar.textContent = initials;
        }
    }
    
    // TABS

    function initTabs() {
        const tabs = document.querySelectorAll('.tab-btn');
        const contents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const target = this.dataset.tab;
                
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                contents.forEach(c => c.classList.remove('active'));
                const targetContent = document.getElementById(`tab-${target}`);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
                
                currentTab = target;
                
                if (target === 'activity') {
                    loadActivity();
                }
            });
        });
    }
    
    // LOAD PROFILE

    async function loadProfile() {
        try {
            const result = await apiRequest('/profile');
            
            if (result && result.success && result.data) {
                const data = result.data;
                
                // Form
                const profileName = document.getElementById('profileName');
                const profileEmail = document.getElementById('profileEmail');
                const profilePhone = document.getElementById('profilePhone');
                const profileDepartment = document.getElementById('profileDepartment');
                const profilePosition = document.getElementById('profilePosition');
                const profileDocument = document.getElementById('profileDocument');
                
                if (profileName) profileName.value = data.name || '';
                if (profileEmail) profileEmail.value = data.email || '';
                if (profilePhone) profilePhone.value = data.phone || '';
                if (profileDepartment) profileDepartment.value = data.department || '';
                if (profilePosition) profilePosition.value = data.position || '';
                if (profileDocument) profileDocument.value = data.document || '';
                
                // Atualizar toda a UI com os dados
                updateUI(data);
                syncWithSidebar(data);
                
                // Stats
                updateElementText('statVehicles', data.vehicles_count || 0);
                updateElementText('statDrivers', data.drivers_count || 0);
                updateElementText('statMaintenances', data.maintenances_count || 0);
                updateElementText('statDocuments', data.documents_count || 0);
                updateElementText('statAlerts', data.alerts_count || 0);
                
                updateLastUpdateTime();
            } else {
                showToast('error', result?.message || 'Erro ao carregar perfil');
            }
        } catch (error) {
            console.error('[Load Profile Error]', error);
            showToast('error', 'Erro ao carregar perfil');
        }
    }
    
    // UPDATE PROFILE - COM SINCRONIZAÇÃO

    async function updateProfile(formData) {
        showToast('info', 'A atualizar perfil...');
        
        try {
            const result = await apiRequest('/profile', {
                method: 'PUT',
                body: JSON.stringify(formData)
            });
            
            if (result && result.success) {
                showToast('success', 'Perfil atualizado com sucesso!');
                
                // Recarregar perfil para obter dados atualizados
                const reloadResult = await apiRequest('/profile');
                if (reloadResult && reloadResult.success && reloadResult.data) {
                    const data = reloadResult.data;
                    
                    // Atualizar UI em tempo real
                    updateUI(data);
                    syncWithSidebar(data);
                    
                    // Atualizar campos do formulário
                    const profileName = document.getElementById('profileName');
                    const profilePhone = document.getElementById('profilePhone');
                    const profileDepartment = document.getElementById('profileDepartment');
                    const profilePosition = document.getElementById('profilePosition');
                    
                    if (profileName) profileName.value = data.name || '';
                    if (profilePhone) profilePhone.value = data.phone || '';
                    if (profileDepartment) profileDepartment.value = data.department || '';
                    if (profilePosition) profilePosition.value = data.position || '';
                    
                    // Atualizar estatísticas
                    updateElementText('statVehicles', data.vehicles_count || 0);
                    updateElementText('statDrivers', data.drivers_count || 0);
                    updateElementText('statMaintenances', data.maintenances_count || 0);
                    updateElementText('statDocuments', data.documents_count || 0);
                    updateElementText('statAlerts', data.alerts_count || 0);
                }
            } else {
                showToast('error', result?.message || 'Erro ao atualizar perfil');
            }
        } catch (error) {
            console.error('[Update Profile Error]', error);
            showToast('error', 'Erro ao atualizar perfil');
        }
    }
    
    // CHANGE PASSWORD

    async function changePassword(formData) {
        showToast('info', 'A alterar palavra-passe...');
        
        try {
            const result = await apiRequest('/profile/password', {
                method: 'PUT',
                body: JSON.stringify(formData)
            });
            
            if (result && result.success) {
                showToast('success', 'Palavra-passe alterada com sucesso!');
                const currentPassword = document.getElementById('currentPassword');
                const newPassword = document.getElementById('newPassword');
                const confirmPassword = document.getElementById('confirmPassword');
                if (currentPassword) currentPassword.value = '';
                if (newPassword) newPassword.value = '';
                if (confirmPassword) confirmPassword.value = '';
            } else {
                showToast('error', result?.message || 'Erro ao alterar palavra-passe');
            }
        } catch (error) {
            console.error('[Change Password Error]', error);
            showToast('error', 'Erro ao alterar palavra-passe');
        }
    }
    
    // LOAD ACTIVITY

    async function loadActivity() {
        const container = document.getElementById('activityList');
        if (!container) return;
        
        container.innerHTML = `
            <div style="text-align:center;padding:40px;">
                <div class="spinner"></div>
                <p style="margin-top:12px;color:var(--profile-text-muted);font-size:0.9rem;">Carregando atividades...</p>
            </div>
        `;
        
        try {
            const result = await apiRequest('/profile/activity');
            
            if (result && result.success && result.data) {
                const activities = result.data.activities || [];
                
                if (activities.length === 0) {
                    container.innerHTML = `
                        <div style="text-align:center;padding:40px;">
                            <i class="fas fa-clock" style="font-size:2rem;color:var(--profile-text-muted);margin-bottom:12px;"></i>
                            <p style="color:var(--profile-text-muted);">Nenhuma atividade registada</p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = activities.map(function(activity) {
                    const icon = getActionIcon(activity.action);
                    return `
                        <div class="activity-item">
                            <div class="activity-icon ${getIconClass(activity.action)}">
                                <i class="fas ${icon}"></i>
                            </div>
                            <div class="activity-content">
                                <div class="description">${escapeHtml(activity.description)}</div>
                                <div class="detail">${escapeHtml(activity.detail || '')}</div>
                                <div class="time"><i class="fas fa-clock"></i> ${formatDate(activity.created_at)}</div>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        } catch (error) {
            console.error('[Load Activity Error]', error);
            container.innerHTML = `
                <div style="text-align:center;padding:40px;">
                    <i class="fas fa-exclamation-circle" style="font-size:2rem;color:#dc2626;margin-bottom:12px;"></i>
                    <p style="color:var(--profile-text-muted);">Erro ao carregar atividades</p>
                </div>
            `;
        }
    }
    
    function getActionIcon(action) {
        const icons = {
            'login': 'fa-sign-in-alt',
            'logout': 'fa-sign-out-alt',
            'update_profile': 'fa-user-edit',
            'change_password': 'fa-key',
            'create': 'fa-plus-circle',
            'update': 'fa-edit',
            'delete': 'fa-trash-alt',
            'terminate_sessions': 'fa-power-off',
            'delete_account': 'fa-user-slash',
            'profile_view': 'fa-eye'
        };
        return icons[action] || 'fa-circle';
    }
    
    function getIconClass(action) {
        const classes = {
            'login': 'primary',
            'logout': 'danger',
            'update_profile': 'success',
            'change_password': 'warning',
            'create': 'success',
            'update': 'primary',
            'delete': 'danger',
            'terminate_sessions': 'warning',
            'delete_account': 'danger',
            'profile_view': 'primary'
        };
        return classes[action] || 'primary';
    }
    
    // EXPORT DATA

    async function exportData() {
        showToast('info', 'A exportar dados...');
        
        try {
            const result = await apiRequest('/profile/export-data');
            
            if (result && result.success && result.data) {
                const data = result.data;
                const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `dados_${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
                
                showToast('success', 'Dados exportados com sucesso!');
            } else {
                showToast('error', result?.message || 'Erro ao exportar dados');
            }
        } catch (error) {
            console.error('[Export Data Error]', error);
            showToast('error', 'Erro ao exportar dados');
        }
    }
    
    // DELETE ACCOUNT

    async function deleteAccount(password) {
        showToast('info', 'A eliminar conta...');
        
        try {
            const result = await apiRequest('/profile/delete', {
                method: 'DELETE',
                body: JSON.stringify({ password: password })
            });
            
            if (result && result.success) {
                showToast('success', 'Conta eliminada com sucesso');
                redirectToLogout();
            } else {
                showToast('error', result?.message || 'Erro ao eliminar conta');
            }
        } catch (error) {
            console.error('[Delete Account Error]', error);
            showToast('error', 'Erro ao eliminar conta');
        }
    }
    
    // TERMINATE SESSIONS

    async function terminateAllSessions() {
        showToast('info', 'A terminar todas as sessões...');
        
        try {
            const result = await apiRequest('/profile/sessions/terminate-all', {
                method: 'POST'
            });
            
            if (result && result.success) {
                showToast('success', 'Todas as sessões foram terminadas');
                redirectToLogout();
            } else {
                showToast('error', result?.message || 'Erro ao terminar sessões');
            }
        } catch (error) {
            console.error('[Terminate Sessions Error]', error);
            showToast('error', 'Erro ao terminar sessões');
        }
    }
    
    // PASSWORD STRENGTH

    function initPasswordStrength() {
        const passwordInput = document.getElementById('newPassword');
        const strengthBar = document.getElementById('passwordStrengthBar');
        
        if (!passwordInput || !strengthBar) return;
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (!password) {
                strengthBar.style.width = '0%';
                strengthBar.className = 'password-strength-bar';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            const levels = ['strength-weak', 'strength-weak', 'strength-medium', 'strength-medium', 'strength-strong'];
            const widths = ['33%', '33%', '66%', '66%', '100%'];
            
            const index = Math.min(strength, 4);
            strengthBar.className = 'password-strength-bar ' + levels[index];
            strengthBar.style.width = widths[index];
        });
    }

    // ESCUTAR MUDANÇAS DE PERFIL (POLLING)

    function startPolling() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        refreshInterval = setInterval(function() {
            // Atualizar apenas o nome e email (não recarregar tudo)
            updateProfileInBackground();
        }, CONFIG.REFRESH_INTERVAL);
    }
    
    async function updateProfileInBackground() {
        try {
            const result = await apiRequest('/profile');
            if (result && result.success && result.data) {
                const data = result.data;
                // Verificar se o nome mudou
                if (userData && userData.name !== data.name) {
                    updateUI(data);
                    syncWithSidebar(data);
                }
                // Atualizar estatísticas
                updateElementText('statVehicles', data.vehicles_count || 0);
                updateElementText('statDrivers', data.drivers_count || 0);
                updateElementText('statMaintenances', data.maintenances_count || 0);
                updateElementText('statDocuments', data.documents_count || 0);
                updateElementText('statAlerts', data.alerts_count || 0);
                updateLastUpdateTime();
            }
        } catch (error) {
            // Silencioso - não mostrar erro em background
        }
    }
    
    // EVENTOS

    function initEvents() {
        // Profile Form
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const nameInput = document.getElementById('profileName');
                const phoneInput = document.getElementById('profilePhone');
                const departmentInput = document.getElementById('profileDepartment');
                const positionInput = document.getElementById('profilePosition');
                
                const formData = {
                    name: nameInput ? nameInput.value.trim() : '',
                    phone: phoneInput ? phoneInput.value.trim() : '',
                    department: departmentInput ? departmentInput.value.trim() : '',
                    position: positionInput ? positionInput.value.trim() : ''
                };
                
                if (!formData.name) {
                    showToast('warning', 'O nome é obrigatório');
                    return;
                }
                
                updateProfile(formData);
            });
        }
        
        // Password Form
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const currentPassword = document.getElementById('currentPassword');
                const newPassword = document.getElementById('newPassword');
                const confirmPassword = document.getElementById('confirmPassword');
                
                const current = currentPassword ? currentPassword.value : '';
                const newPwd = newPassword ? newPassword.value : '';
                const confirm = confirmPassword ? confirmPassword.value : '';
                
                if (!current) {
                    showToast('warning', 'Digite a palavra-passe atual');
                    return;
                }
                
                if (!newPwd || newPwd.length < 6) {
                    showToast('warning', 'A nova palavra-passe deve ter no mínimo 6 caracteres');
                    return;
                }
                
                if (newPwd !== confirm) {
                    showToast('warning', 'As palavras-passe não coincidem');
                    return;
                }
                
                changePassword({
                    current_password: current,
                    new_password: newPwd,
                    new_password_confirmation: confirm
                });
            });
        }
        
        // Export Data
        const exportBtn = document.getElementById('exportData');
        if (exportBtn) {
            exportBtn.addEventListener('click', function() {
                exportData();
            });
        }
        
        // Delete Account
        const deleteBtn = document.getElementById('deleteAccount');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                const confirmTitle = document.getElementById('confirmTitle');
                const confirmMessage = document.getElementById('confirmMessage');
                const confirmPasswordField = document.getElementById('confirmPasswordField');
                const confirmDeletePassword = document.getElementById('confirmDeletePassword');
                
                if (confirmTitle) confirmTitle.textContent = '⚠️ Eliminar Conta Permanentemente';
                if (confirmMessage) {
                    confirmMessage.innerHTML = `
                        Tem certeza que deseja <strong>ELIMINAR PERMANENTEMENTE</strong> a sua conta?<br>
                        <span style="color:var(--profile-text-muted);font-size:0.85rem;">Esta ação é irreversível e todos os seus dados serão removidos.</span>
                    `;
                }
                if (confirmPasswordField) confirmPasswordField.style.display = 'block';
                if (confirmDeletePassword) {
                    confirmDeletePassword.value = '';
                    confirmDeletePassword.placeholder = 'Digite sua palavra-passe para confirmar';
                }
                
                const confirmBtn = document.getElementById('confirmActionBtn');
                if (confirmBtn) {
                    const newConfirmBtn = confirmBtn.cloneNode(true);
                    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
                    newConfirmBtn.textContent = 'Eliminar Conta';
                    newConfirmBtn.className = 'btn btn-danger';
                    newConfirmBtn.addEventListener('click', function() {
                        const password = document.getElementById('confirmDeletePassword');
                        if (!password || !password.value) {
                            showToast('warning', 'Digite sua palavra-passe para confirmar');
                            return;
                        }
                        closeModal('confirmModal');
                        deleteAccount(password.value);
                    });
                }
                
                openModal('confirmModal');
            });
        }
        
        // Terminate All Sessions
        const terminateBtn = document.getElementById('terminateAllSessions');
        if (terminateBtn) {
            terminateBtn.addEventListener('click', function() {
                const confirmTitle = document.getElementById('confirmTitle');
                const confirmMessage = document.getElementById('confirmMessage');
                const confirmPasswordField = document.getElementById('confirmPasswordField');
                
                if (confirmTitle) confirmTitle.textContent = '🔌 Terminar Todas as Sessões';
                if (confirmMessage) {
                    confirmMessage.innerHTML = `
                        Tem certeza que deseja terminar todas as sessões ativas?<br>
                        <span style="color:var(--profile-text-muted);font-size:0.85rem;">Você será desconectado de <strong>TODOS</strong> os dispositivos.</span>
                    `;
                }
                if (confirmPasswordField) confirmPasswordField.style.display = 'none';
                
                const confirmBtn = document.getElementById('confirmActionBtn');
                if (confirmBtn) {
                    const newConfirmBtn = confirmBtn.cloneNode(true);
                    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
                    newConfirmBtn.textContent = 'Terminar Todas';
                    newConfirmBtn.className = 'btn btn-warning';
                    newConfirmBtn.addEventListener('click', function() {
                        closeModal('confirmModal');
                        terminateAllSessions();
                    });
                }
                
                openModal('confirmModal');
            });
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
        
        if (menuToggle) {
            menuToggle.addEventListener('click', openSidebar);
        }
        if (sidebarClose) {
            sidebarClose.addEventListener('click', closeSidebar);
        }
        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });
        
        const userMenu = document.getElementById('userMenu');
        const userDropdown = document.getElementById('userDropdown');
        const userChevron = document.getElementById('userChevron');
        
        if (userMenu && userDropdown) {
            userMenu.addEventListener('click', function(e) {
                e.preventDefault();
                userDropdown.classList.toggle('show');
                if (userChevron) {
                    userChevron.style.transform = userDropdown.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            });
            
            document.addEventListener('click', function(e) {
                if (!userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.remove('show');
                    if (userChevron) {
                        userChevron.style.transform = 'rotate(0deg)';
                    }
                }
            });
        }
    }
    
    // INIT
    
    async function init() {
        console.log('[CARDOXIS] Inicializando Profile v11.0.0...');
        
        const token = getToken();
        if (!token) {
            window.location.replace('/cardoxis/login');
            return;
        }
        
        initSidebar();
        initTabs();
        initEvents();
        initPasswordStrength();
        
        await loadProfile();
        await loadActivity();
        
        // Iniciar polling para atualizações em background
        startPolling();
        
        console.log('[CARDOXIS] Profile inicializado com sucesso!');
    }
    
    // Expor funções globais
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.loadProfile = loadProfile;
    window.loadActivity = loadActivity;
    window.exportData = exportData;
    window.deleteAccount = deleteAccount;
    window.terminateAllSessions = terminateAllSessions;
    window.updateUI = updateUI;
    window.syncWithSidebar = syncWithSidebar;
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();