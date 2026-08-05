/**
 * CARDOXIS - Central de Ajuda RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES

    const CONFIG = {
        API_URL: window.CARDOXIS?.API_URL || window.location.origin + '/cardoxis/api/v1',
        SEARCH_DEBOUNCE: 300,
        ANIMATION_DELAY: 150
    };
    
    // ESTADO

    let state = {
        searchQuery: '',
        isSearching: false,
        searchResults: [],
        faqFilter: 'all',
        openFaq: null
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
    
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
    
    function showToast(type, message) {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
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
    
    // FAQ

    window.toggleFaq = function(element) {
        const item = element.closest('.faq-item');
        if (!item) return;
        
        const isActive = item.classList.contains('active');
        
        // Fechar todos os outros
        document.querySelectorAll('.faq-item').forEach(el => {
            el.classList.remove('active');
        });
        
        if (!isActive) {
            item.classList.add('active');
            state.openFaq = item.dataset.category;
        } else {
            state.openFaq = null;
        }
    };
    
    function filterFaqs(category) {
        state.faqFilter = category;
        
        // Atualizar botões
        document.querySelectorAll('.faq-filter').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.filter === category) {
                btn.classList.add('active');
            }
        });
        
        // Filtrar items
        document.querySelectorAll('.faq-item').forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
                item.classList.remove('active');
            }
        });
        
        // Reabrir primeiro se houver
        const firstVisible = document.querySelector('.faq-item[style*="display: none"]');
        if (!firstVisible) {
            const first = document.querySelector('.faq-item:not([style*="display: none"])');
            if (first) {
                first.classList.add('active');
            }
        }
    }
    
    // SEARCH

    function performSearch(query) {
        const results = [];
        const searchQuery = query.toLowerCase().trim();
        
        if (searchQuery.length < 2) {
            return results;
        }
        
        // Buscar em categorias e artigos
        const categories = window.CARDOXIS?.HELP?.categories || [];
        categories.forEach(category => {
            const categoryMatch = category.title.toLowerCase().includes(searchQuery) ||
                                 category.description.toLowerCase().includes(searchQuery);
            
            category.articles.forEach(article => {
                const articleMatch = article.title.toLowerCase().includes(searchQuery) ||
                                    (article.description && article.description.toLowerCase().includes(searchQuery));
                
                if (articleMatch || categoryMatch) {
                    results.push({
                        type: 'article',
                        category: category.title,
                        categoryId: category.id,
                        title: article.title,
                        url: article.url,
                        description: article.description || '',
                        icon: category.icon,
                        color: category.color
                    });
                }
            });
        });
        
        // Buscar em FAQ
        const faqs = window.CARDOXIS?.HELP?.faqs || [];
        faqs.forEach(faq => {
            if (faq.question.toLowerCase().includes(searchQuery) ||
                faq.answer.toLowerCase().includes(searchQuery)) {
                results.push({
                    type: 'faq',
                    question: faq.question,
                    answer: faq.answer,
                    category: faq.category,
                    icon: 'fa-question-circle',
                    color: '#6B778C'
                });
            }
        });
        
        return results.slice(0, 10);
    }
    
    function renderSearchResults(results) {
        const container = document.getElementById('searchResults');
        if (!container) return;
        
        if (results.length === 0) {
            container.innerHTML = '';
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'block';
        container.innerHTML = `
            <div class="search-results-list">
                ${results.map(result => {
                    if (result.type === 'article') {
                        return `
                            <a href="${escapeHtml(result.url)}" class="search-result-item">
                                <div class="search-result-icon" style="background: ${result.color}20; color: ${result.color};">
                                    <i class="fas ${result.icon}"></i>
                                </div>
                                <div class="search-result-content">
                                    <div class="search-result-title">${escapeHtml(result.title)}</div>
                                    <div class="search-result-meta">
                                        <span class="search-result-category">${escapeHtml(result.category)}</span>
                                        <span class="search-result-type">Artigo</span>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        `;
                    } else {
                        return `
                            <div class="search-result-item faq-result" onclick="handleFaqResult('${escapeHtml(result.question)}')">
                                <div class="search-result-icon" style="background: ${result.color}20; color: ${result.color};">
                                    <i class="fas ${result.icon}"></i>
                                </div>
                                <div class="search-result-content">
                                    <div class="search-result-title">${escapeHtml(result.question)}</div>
                                    <div class="search-result-meta">
                                        <span class="search-result-category">${escapeHtml(result.category)}</span>
                                        <span class="search-result-type">FAQ</span>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        `;
                    }
                }).join('')}
            </div>
        `;
    }
    
    window.handleFaqResult = function(question) {
        // Fechar search
        document.getElementById('searchResults').style.display = 'none';
        document.getElementById('searchResults').innerHTML = '';
        document.getElementById('helpSearch').value = '';
        
        // Procurar e abrir FAQ
        document.querySelectorAll('.faq-item').forEach(item => {
            const q = item.querySelector('.faq-question span');
            if (q && q.textContent.trim() === question) {
                document.getElementById('helpFaq').scrollIntoView({ behavior: 'smooth' });
                setTimeout(() => toggleFaq(item.querySelector('.faq-question')), 300);
            }
        });
    };
    
    const debouncedSearch = debounce(function(query) {
        const results = performSearch(query);
        renderSearchResults(results);
    }, CONFIG.SEARCH_DEBOUNCE);
    
    // SEARCH EVENTS


    function initSearch() {
        const searchInput = document.getElementById('helpSearch');
        const searchClear = document.getElementById('searchClear');
        const searchResults = document.getElementById('searchResults');
        
        if (!searchInput) return;
        
        // Input
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            state.searchQuery = query;
            
            if (query.length >= 2) {
                debouncedSearch(query);
                if (searchClear) searchClear.style.display = 'flex';
            } else {
                if (searchResults) searchResults.style.display = 'none';
                if (searchClear) searchClear.style.display = 'none';
            }
        });
        
        // Clear
        if (searchClear) {
            searchClear.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.focus();
                this.style.display = 'none';
                if (searchResults) {
                    searchResults.innerHTML = '';
                    searchResults.style.display = 'none';
                }
            });
        }
        
        // Fechar ao clicar fora
        document.addEventListener('click', function(e) {
            if (searchResults && !searchResults.contains(e.target) && 
                e.target !== searchInput && !searchInput.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
        
        // Keyboard shortcuts
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.blur();
                if (searchResults) searchResults.style.display = 'none';
            }
        });
    }
    
    // FAQ FILTERS

    function initFaqFilters() {
        document.querySelectorAll('.faq-filter').forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.dataset.filter;
                filterFaqs(filter);
            });
        });
    }
    
    // CATEGORY ANIMATIONS

    function animateCategories() {
        document.querySelectorAll('.help-category').forEach((el, index) => {
            el.style.animationDelay = `${(index + 1) * 0.05}s`;
        });
    }
    
    // KEYBOARD SHORTCUTS

    function initKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Ctrl+K ou Cmd+K para focar na busca
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.getElementById('helpSearch');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
            
            // ? para mostrar ajuda de atalhos
            if (e.key === '?' && !e.ctrlKey && !e.metaKey) {
                const activeElement = document.activeElement;
                if (activeElement && activeElement.tagName !== 'INPUT') {
                    e.preventDefault();
                    showToast('info', 'Atalho: Ctrl+K para pesquisar');
                }
            }
        });
    }
    
    // STATS COUNTER ANIMATION

    function animateCounters() {
        document.querySelectorAll('.help-stat .stat-number').forEach(el => {
            const target = parseInt(el.textContent);
            if (isNaN(target)) return;
            
            let current = 0;
            const increment = target / 30;
            const duration = 800;
            const stepTime = duration / 30;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                el.textContent = Math.round(current);
            }, stepTime);
        });
    }
    
    // INIT

    function init() {
        console.log('[CARDOXIS] Inicializando Central de Ajuda');
        
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        // Inicializar componentes
        initSidebar();
        initSearch();
        initFaqFilters();
        initKeyboardShortcuts();
        animateCategories();
        
        // Animar contadores após carregamento
        setTimeout(animateCounters, 300);
        
        // Abrir primeiro FAQ
        const firstFaq = document.querySelector('.faq-item');
        if (firstFaq) {
            firstFaq.classList.add('active');
        }
        
        console.log('[CARDOXIS] Central de Ajuda inicializada com sucesso!');
        console.log('[CARDOXIS] Atalhos: Ctrl+K para pesquisar');
    }
    
    // EXPOR FUNÇÕES GLOBAIS

    window.toggleFaq = toggleFaq;
    window.filterFaqs = filterFaqs;
    window.handleFaqResult = handleFaqResult;
    
    // DOM READY
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();