/**
 * CARDOXIS Admin FAQ JavaScript
 * Version: 1.0.0
 */

(function() {
    'use strict';
    
    const API_URL = 'http://localhost/cardoxis/api/v1';
    let currentCategoryId = null;
    let categories = [];
    let faqs = [];
    
    // ============================================
    // FUNÇÕES DE AUTENTICAÇÃO
    // ============================================
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
    
    // ============================================
    // REQUISIÇÕES API
    // ============================================
    async function apiRequest(endpoint, options = {}) {
        const token = getToken();
        
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...options.headers
        };
        
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                ...options,
                headers
            });
            
            if (response.status === 401) {
                localStorage.removeItem('auth_token');
                window.location.href = '/cardoxis/login';
                return null;
            }
            
            return await response.json();
        } catch (error) {
            console.error('[API Error]', error);
            return { success: false, message: error.message };
        }
    }
    
    // ============================================
    // CARREGAR CATEGORIAS
    // ============================================
    async function loadCategories() {
        showLoading(true);
        
        const response = await apiRequest('/admin/faq/categories');
        
        if (response && response.success) {
            categories = response.data;
            renderCategories();
            updateStats();
        } else {
            // Dados de exemplo para fallback
            categories = [
                { id: 1, name: 'Geral', icon: 'fa-globe', count: 3, is_active: 1 },
                { id: 2, name: 'Conta e Faturação', icon: 'fa-credit-card', count: 2, is_active: 1 },
                { id: 3, name: 'Veículos', icon: 'fa-truck', count: 2, is_active: 1 },
                { id: 4, name: 'Motoristas', icon: 'fa-users', count: 1, is_active: 1 },
                { id: 5, name: 'Documentos', icon: 'fa-file-alt', count: 1, is_active: 1 }
            ];
            renderCategories();
            updateStats();
        }
        
        showLoading(false);
    }
    
    function renderCategories() {
        const container = document.getElementById('categoriesList');
        
        if (!container) return;
        
        if (!categories || categories.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="padding: 40px;">
                    <i class="fas fa-folder-open"></i>
                    <p>Nenhuma categoria encontrada</p>
                    <button class="btn-primary" onclick="openCategoryModal()">
                        <i class="fas fa-plus"></i> Criar Categoria
                    </button>
                </div>
            `;
            return;
        }
        
        container.innerHTML = categories.map(cat => `
            <div class="category-item ${currentCategoryId === cat.id ? 'active' : ''}" data-id="${cat.id}" onclick="selectCategory(${cat.id})">
                <div class="category-name">
                    <i class="fas ${cat.icon || 'fa-folder'}"></i>
                    <span>${escapeHtml(cat.name)}</span>
                </div>
                <div class="category-actions">
                    <button class="category-action-btn edit" onclick="event.stopPropagation(); editCategory(${cat.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="category-action-btn delete" onclick="event.stopPropagation(); deleteCategory(${cat.id})" title="Excluir">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    function updateStats() {
        const total = categories.length;
        const active = categories.filter(c => c.is_active === 1).length;
        const totalFaqs = faqs.length;
        
        const totalEl = document.getElementById('totalCategories');
        const activeEl = document.getElementById('activeCategories');
        const totalFaqsEl = document.getElementById('totalFaqs');
        
        if (totalEl) totalEl.textContent = total;
        if (activeEl) activeEl.textContent = active;
        if (totalFaqsEl) totalFaqsEl.textContent = totalFaqs;
    }
    
    // ============================================
    // CARREGAR FAQS
    // ============================================
    async function loadFaqs(categoryId = null) {
        showLoading(true);
        
        let endpoint = '/admin/faq';
        if (categoryId) {
            endpoint = `/admin/faq/category/${categoryId}`;
        }
        
        const response = await apiRequest(endpoint);
        
        if (response && response.success) {
            faqs = response.data;
            renderFaqs();
            updateStats();
        } else {
            // Dados de exemplo para fallback
            faqs = getSampleFaqs();
            renderFaqs();
        }
        
        showLoading(false);
    }
    
    function getSampleFaqs() {
        return [
            { id: 1, category_id: 1, question: 'Como criar uma nova empresa?', answer: 'Aceda ao menu "Empresas" e clique em "Nova Empresa". Preencha os dados e clique em "Salvar".', order: 1, is_active: 1 },
            { id: 2, category_id: 1, question: 'Como adicionar um utilizador?', answer: 'Aceda ao menu "Utilizadores", clique em "Novo Utilizador", preencha os dados e atribua uma função.', order: 2, is_active: 1 },
            { id: 3, category_id: 2, question: 'Como alterar o plano de assinatura?', answer: 'Aceda a "Assinaturas", selecione o plano desejado e confirme a alteração.', order: 1, is_active: 1 },
            { id: 4, category_id: 3, question: 'Como registar um veículo?', answer: 'Aceda a "Veículos", clique em "Novo Veículo" e preencha os dados do veículo.', order: 1, is_active: 1 }
        ];
    }
    
    function renderFaqs() {
        const container = document.getElementById('faqsList');
        
        if (!container) return;
        
        if (!faqs || faqs.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="padding: 60px;">
                    <i class="fas fa-question-circle"></i>
                    <h3>Nenhuma FAQ encontrada</h3>
                    <p>Clique em "Nova FAQ" para adicionar perguntas frequentes</p>
                    <button class="btn-primary" onclick="openFaqModal()">
                        <i class="fas fa-plus"></i> Nova FAQ
                    </button>
                </div>
            `;
            return;
        }
        
        container.innerHTML = faqs.map(faq => `
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(${faq.id})">
                    <h3>${escapeHtml(faq.question)}</h3>
                    <div class="faq-actions">
                        <button class="action-btn edit" onclick="event.stopPropagation(); editFaq(${faq.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="event.stopPropagation(); deleteFaq(${faq.id})" title="Excluir">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="faq-answer" id="faq-answer-${faq.id}">
                    ${escapeHtml(faq.answer)}
                </div>
            </div>
        `).join('');
    }
    
    function toggleFaq(id) {
        const answer = document.getElementById(`faq-answer-${id}`);
        const question = answer?.previousElementSibling;
        
        if (answer && question) {
            answer.classList.toggle('show');
            question.classList.toggle('active');
        }
    }
    
    // ============================================
    // SELEÇÃO DE CATEGORIA
    // ============================================
    window.selectCategory = function(categoryId) {
        currentCategoryId = categoryId;
        
        // Atualizar classe ativa nas categorias
        document.querySelectorAll('.category-item').forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('data-id') == categoryId) {
                item.classList.add('active');
            }
        });
        
        // Atualizar título
        const category = categories.find(c => c.id === categoryId);
        const titleEl = document.getElementById('currentCategoryTitle');
        if (titleEl && category) {
            titleEl.textContent = category.name;
        }
        
        // Carregar FAQs da categoria
        loadFaqs(categoryId);
    };
    
    // ============================================
    // CRUD - CATEGORIAS
    // ============================================
    window.openCategoryModal = function(category = null) {
        const modal = document.getElementById('categoryModal');
        const title = document.getElementById('categoryModalTitle');
        const idField = document.getElementById('categoryId');
        const nameField = document.getElementById('categoryName');
        const iconField = document.getElementById('categoryIcon');
        
        if (category) {
            title.textContent = 'Editar Categoria';
            idField.value = category.id;
            nameField.value = category.name;
            iconField.value = category.icon || 'fa-folder';
        } else {
            title.textContent = 'Nova Categoria';
            idField.value = '';
            nameField.value = '';
            iconField.value = 'fa-folder';
        }
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    
    window.saveCategory = async function() {
        const id = document.getElementById('categoryId').value;
        const data = {
            name: document.getElementById('categoryName').value,
            icon: document.getElementById('categoryIcon').value
        };
        
        if (!data.name) {
            showToast('error', 'Nome da categoria é obrigatório');
            return;
        }
        
        showLoading(true);
        
        let response;
        if (id) {
            response = await apiRequest(`/admin/faq/categories/${id}`, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        } else {
            response = await apiRequest('/admin/faq/categories', {
                method: 'POST',
                body: JSON.stringify(data)
            });
        }
        
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', id ? 'Categoria atualizada com sucesso!' : 'Categoria criada com sucesso!');
            closeModal('categoryModal');
            loadCategories();
            if (!id && currentCategoryId === null) {
                loadFaqs();
            }
        } else {
            showToast('error', response?.message || 'Erro ao salvar categoria');
        }
    };
    
    window.editCategory = async function(id) {
        const category = categories.find(c => c.id === id);
        if (category) {
            openCategoryModal(category);
        }
    };
    
    window.deleteCategory = function(id) {
        currentDeleteId = id;
        const modal = document.getElementById('deleteCategoryModal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };
    
    window.confirmDeleteCategory = async function() {
        if (!currentDeleteId) return;
        
        showLoading(true);
        const response = await apiRequest(`/admin/faq/categories/${currentDeleteId}`, {
            method: 'DELETE'
        });
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', 'Categoria removida com sucesso!');
            closeModal('deleteCategoryModal');
            loadCategories();
            if (currentCategoryId === currentDeleteId) {
                currentCategoryId = null;
                loadFaqs();
            }
        } else {
            showToast('error', response?.message || 'Erro ao remover categoria');
        }
        
        currentDeleteId = null;
    };
    
    // ============================================
    // CRUD - FAQS
    // ============================================
    window.openFaqModal = function(faq = null) {
        const modal = document.getElementById('faqModal');
        const title = document.getElementById('faqModalTitle');
        const idField = document.getElementById('faqId');
        const categoryField = document.getElementById('faqCategory');
        const questionField = document.getElementById('faqQuestion');
        const answerField = document.getElementById('faqAnswer');
        
        // Preencher categorias no select
        const categorySelect = document.getElementById('faqCategory');
        if (categorySelect) {
            categorySelect.innerHTML = '<option value="">Selecione uma categoria</option>' +
                categories.map(cat => `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`).join('');
        }
        
        if (faq) {
            title.textContent = 'Editar FAQ';
            idField.value = faq.id;
            if (categoryField) categoryField.value = faq.category_id;
            questionField.value = faq.question;
            answerField.value = faq.answer;
        } else {
            title.textContent = 'Nova FAQ';
            idField.value = '';
            if (categoryField && currentCategoryId) categoryField.value = currentCategoryId;
            questionField.value = '';
            answerField.value = '';
        }
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    
    window.saveFaq = async function() {
        const id = document.getElementById('faqId').value;
        const data = {
            category_id: document.getElementById('faqCategory').value,
            question: document.getElementById('faqQuestion').value,
            answer: document.getElementById('faqAnswer').value
        };
        
        if (!data.category_id) {
            showToast('error', 'Selecione uma categoria');
            return;
        }
        if (!data.question) {
            showToast('error', 'Pergunta é obrigatória');
            return;
        }
        if (!data.answer) {
            showToast('error', 'Resposta é obrigatória');
            return;
        }
        
        showLoading(true);
        
        let response;
        if (id) {
            response = await apiRequest(`/admin/faq/${id}`, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        } else {
            response = await apiRequest('/admin/faq', {
                method: 'POST',
                body: JSON.stringify(data)
            });
        }
        
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', id ? 'FAQ atualizada com sucesso!' : 'FAQ criada com sucesso!');
            closeModal('faqModal');
            loadFaqs(currentCategoryId);
        } else {
            showToast('error', response?.message || 'Erro ao salvar FAQ');
        }
    };
    
    window.editFaq = async function(id) {
        const response = await apiRequest(`/admin/faq/${id}`);
        if (response && response.success) {
            openFaqModal(response.data);
        } else {
            showToast('error', 'Erro ao carregar FAQ');
        }
    };
    
    window.deleteFaq = function(id) {
        currentDeleteId = id;
        const modal = document.getElementById('deleteFaqModal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };
    
    window.confirmDeleteFaq = async function() {
        if (!currentDeleteId) return;
        
        showLoading(true);
        const response = await apiRequest(`/admin/faq/${currentDeleteId}`, {
            method: 'DELETE'
        });
        showLoading(false);
        
        if (response && response.success) {
            showToast('success', 'FAQ removida com sucesso!');
            closeModal('deleteFaqModal');
            loadFaqs(currentCategoryId);
        } else {
            showToast('error', response?.message || 'Erro ao remover FAQ');
        }
        
        currentDeleteId = null;
    };
    
    // ============================================
    // UTILITÁRIOS
    // ============================================
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
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
            if (link.getAttribute('href') === '/cardoxis/admin/faq') {
                link.classList.add('active');
            }
        });
    }
    
    // ============================================
    // INICIALIZAÇÃO
    // ============================================
    let currentDeleteId = null;
    
    document.addEventListener('DOMContentLoaded', () => {
        if (!checkAuth()) return;
        
        updateActiveMenu();
        loadCategories();
        loadFaqs();
        
        // Expor funções globalmente
        window.closeModal = closeModal;
        window.openCategoryModal = openCategoryModal;
        window.saveCategory = saveCategory;
        window.openFaqModal = openFaqModal;
        window.saveFaq = saveFaq;
        window.confirmDeleteCategory = confirmDeleteCategory;
        window.confirmDeleteFaq = confirmDeleteFaq;
    });
})();