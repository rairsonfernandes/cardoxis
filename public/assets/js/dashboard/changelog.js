/**
 * CARDOXIS - Novidades RF
 */

(function() {
    'use strict';
    
    // CONFIGURAÇÕES

    const CONFIG = {
        API_URL: window.CARDOXIS?.API_URL || window.location.origin + '/cardoxis/api/v1',
        ANIMATION_DELAY: 150,
        TOAST_DURATION: 5000
    };
    
    // ESTADO

    let state = {
        currentFilter: 'all',
        items: [],
        filteredItems: []
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
    
~~
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
            
            if (response.status === 404) {
                console.warn('[API] Endpoint não encontrado:', endpoint);
                return { success: false, message: 'Endpoint não encontrado', data: null };
            }
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('[API Error]', endpoint, error);
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
        return date.toLocaleDateString('pt-PT', { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric' 
        });
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
        setTimeout(() => toast.remove(), CONFIG.TOAST_DURATION);
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
    
    // FILTROS

    function initFilters() {
        const filters = document.querySelectorAll('.filter-btn');
        
        filters.forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.dataset.filter;
                
                // Atualizar estado
                state.currentFilter = filter;
                
                // Atualizar UI dos botões
                filters.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Filtrar itens
                filterItems(filter);
            });
        });
    }
    
    function filterItems(filter) {
        const items = document.querySelectorAll('.changelog-item');
        
        items.forEach(item => {
            if (filter === 'all' || item.dataset.type === filter) {
                item.classList.remove('hidden');
                // Reaplicar animação
                item.style.animation = 'none';
                setTimeout(() => {
                    item.style.animation = 'fadeInUp 0.4s ease forwards';
                }, 10);
            } else {
                item.classList.add('hidden');
            }
        });
        
        // Atualizar contador visível
        const visibleItems = document.querySelectorAll('.changelog-item:not(.hidden)');
        const totalItems = document.querySelectorAll('.changelog-item').length;
        
        // Mostrar toast com contagem
        if (visibleItems.length < totalItems) {
            showToast('info', `Mostrando ${visibleItems.length} de ${totalItems} atualizações`);
        }
    }
    
    // DADOS MOCK (Fallback)

    function getMockChangelog() {
        return [
            {
                version: '2.0.0',
                date: '2025-06-30',
                title: 'Chat Inteligente com IA',
                type: 'feature',
                description: 'O CARDOXIS agora conta com um assistente virtual inteligente para ajudar na gestão da sua frota.',
                details: [
                    'Assistente virtual 24/7 para dúvidas sobre o sistema',
                    'Respostas inteligentes baseadas em IA',
                    'Integração com a base de conhecimento do CARDOXIS',
                    'Histórico de conversas salvo automaticamente',
                    'Sugestões de perguntas frequentes'
                ]
            },
            {
                version: '1.9.0',
                date: '2025-06-15',
                title: 'Central de Ajuda Reformulada',
                type: 'improvement',
                description: 'Nova Central de Ajuda com interface moderna e conteúdo otimizado para melhor experiência do usuário.',
                details: [
                    'Design totalmente redesenhado',
                    'Busca inteligente por artigos',
                    'Categorias organizadas por tema',
                    'FAQ interativo com filtros',
                    'Dicas rápidas na página inicial'
                ]
            },
            {
                version: '1.8.0',
                date: '2025-06-01',
                title: 'Notificações em Tempo Real',
                type: 'feature',
                description: 'Sistema de notificações em tempo real para manter você sempre informado sobre sua frota.',
                details: [
                    'Alertas de manutenção programada',
                    'Vencimento de documentos e seguros',
                    'Multas e infrações registadas',
                    'Novos abastecimentos registados',
                    'Notificações personalizáveis por tipo'
                ]
            }
        ];
    }
    
    // CARREGAR DADOS

    async function loadChangelog() {
        try {
            const result = await apiRequest('/changelog');
            
            if (result && result.success && result.data) {
                state.items = result.data;
                renderItems(result.data);
            } else {
                // Fallback para dados mock
                console.log('[Changelog] Usando dados mock');
                const mockData = getMockChangelog();
                state.items = mockData;
                renderItems(mockData);
            }
        } catch (error) {
            console.error('[Load Changelog Error]', error);
            const mockData = getMockChangelog();
            state.items = mockData;
            renderItems(mockData);
        }
    }
    
    function renderItems(items) {
        const container = document.getElementById('changelogList');
        if (!container) return;
        
        // Se já existem itens, atualizar apenas o conteúdo
        const existingItems = container.querySelectorAll('.changelog-item');
        
        if (existingItems.length > 0 && items.length === existingItems.length) {
            // Atualizar dados existentes se necessário
            return;
        }
        
        // Renderizar todos os itens
        const typeLabels = {
            'feature': 'Nova Funcionalidade',
            'improvement': 'Melhoria',
            'bugfix': 'Correção de Bug',
            'security': 'Segurança'
        };
        
        const typeIcons = {
            'feature': 'fa-rocket',
            'improvement': 'fa-chart-line',
            'bugfix': 'fa-bug',
            'security': 'fa-shield-alt'
        };
        
        const typeColors = {
            'feature': '#0052CC',
            'improvement': '#36B37E',
            'bugfix': '#DE350B',
            'security': '#6554C0'
        };
        
        container.innerHTML = items.map((item, index) => `
            <div class="changelog-item" data-type="${escapeHtml(item.type)}" style="animation-delay: ${(index + 1) * 0.05}s">
                <div class="changelog-item-header">
                    <div class="changelog-version">
                        <span class="version-tag">v${escapeHtml(item.version)}</span>
                        <span class="version-date">
                            <i class="fas fa-calendar-alt"></i>
                            ${formatDate(item.date)}
                        </span>
                    </div>
                    <div class="changelog-type-badge" style="background: ${typeColors[item.type] || '#6B778C'};">
                        <i class="fas ${typeIcons[item.type] || 'fa-tag'}"></i>
                        ${typeLabels[item.type] || item.type}
                    </div>
                </div>
                <h3>${escapeHtml(item.title)}</h3>
                <p class="changelog-description">${escapeHtml(item.description)}</p>
                <ul class="changelog-details">
                    ${item.details.map(detail => `
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>${escapeHtml(detail)}</span>
                        </li>
                    `).join('')}
                </ul>
            </div>
        `).join('');
    }
    
    // STATS ANIMATION

    function animateCounters() {
        const statNumbers = document.querySelectorAll('.stat-card .stat-number');
        statNumbers.forEach(el => {
            const target = parseInt(el.textContent);
            if (isNaN(target)) return;
            
            let current = 0;
            const increment = target / 20;
            const duration = 600;
            const stepTime = duration / 20;
            
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
    
    // KEYBOARD SHORTCUTS

    function initKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Ctrl+F ou Cmd+F para focar nos filtros
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const firstFilter = document.querySelector('.filter-btn');
                if (firstFilter) {
                    firstFilter.focus();
                }
            }
            
            // Teclas numéricas para filtros rápidos
            const filterMap = {
                '1': 'all',
                '2': 'feature',
                '3': 'improvement',
                '4': 'bugfix',
                '5': 'security'
            };
            
            if (!e.ctrlKey && !e.metaKey && !e.altKey) {
                const filter = filterMap[e.key];
                if (filter) {
                    const btn = document.querySelector(`.filter-btn[data-filter="${filter}"]`);
                    if (btn) {
                        btn.click();
                    }
                }
            }
        });
    } 

    // INIT

    async function init() {
        console.log('[CARDOXIS] Inicializando Novidades');
        
        const token = getToken();
        if (!token) {
            window.location.href = '/cardoxis/login';
            return;
        }
        
        // Inicializar componentes
        initSidebar();
        initFilters();
        initKeyboardShortcuts();
        
        // Carregar dados
        await loadChangelog();
        
        // Animar contadores
        setTimeout(animateCounters, 300);
        
        console.log('[CARDOXIS] Novidades inicializado com sucesso!');
        console.log('[CARDOXIS] Atalhos: 1-Todas, 2-Features, 3-Melhorias, 4-Bugfix, 5-Segurança');
    }
    
    // EXPOR FUNÇÕES GLOBAIS

    window.showToast = showToast;
    
    // DOM READY
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();