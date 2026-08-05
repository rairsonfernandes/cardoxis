/**
 * CARDOXIS - Script da Página de Documentação RF
 */

(function() {
    'use strict';

    // 1. INICIALIZAR AOS
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true,
            offset: 50,
            easing: 'ease-in-out'
        });
    }

    // 2. PESQUISA NA DOCUMENTAÇÃO

    function initSearch() {
        const searchInput = document.getElementById('docsSearch');
        if (!searchInput) return;

        const articles = document.querySelectorAll('.docs-article-body');
        const articleTitles = document.querySelectorAll('.docs-article-header h1');

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();

            // Remover highlights anteriores
            document.querySelectorAll('.search-highlight').forEach(function(el) {
                el.classList.remove('search-highlight');
            });

            if (query.length < 2) {
                // Mostrar todos os artigos
                articles.forEach(function(article) {
                    article.style.display = '';
                });
                return;
            }

            let found = false;

            articles.forEach(function(article, index) {
                const text = article.textContent.toLowerCase();
                const title = articleTitles[index] ? articleTitles[index].textContent.toLowerCase() : '';
                
                if (text.includes(query) || title.includes(query)) {
                    article.style.display = '';
                    found = true;
                    
                    // Destacar correspondências
                    const walker = document.createTreeWalker(
                        article,
                        NodeFilter.SHOW_TEXT,
                        {
                            acceptNode: function(node) {
                                if (node.parentElement.tagName === 'SCRIPT' || 
                                    node.parentElement.tagName === 'STYLE' ||
                                    node.parentElement.closest('pre')) {
                                    return NodeFilter.FILTER_REJECT;
                                }
                                return NodeFilter.FILTER_ACCEPT;
                            }
                        }
                    );

                    const nodes = [];
                    let node;
                    while (node = walker.nextNode()) {
                        if (node.textContent.toLowerCase().includes(query)) {
                            nodes.push(node);
                        }
                    }

                    nodes.forEach(function(node) {
                        const parent = node.parentElement;
                        const text = node.textContent;
                        const regex = new RegExp('(' + query + ')', 'gi');
                        const fragment = document.createElement('span');
                        fragment.innerHTML = text.replace(regex, '<mark class="search-highlight">$1</mark>');
                        parent.replaceChild(fragment, node);
                    });

                } else {
                    article.style.display = 'none';
                }
            });

            // Mostrar mensagem de nenhum resultado
            let noResults = document.getElementById('noSearchResults');
            if (!found && query.length >= 2) {
                if (!noResults) {
                    noResults = document.createElement('div');
                    noResults.id = 'noSearchResults';
                    noResults.className = 'no-results';
                    noResults.innerHTML = `
                        <i class="fas fa-search"></i>
                        <h3>Nenhum resultado encontrado</h3>
                        <p>Tente usar outros termos ou verifique a ortografia.</p>
                    `;
                    document.querySelector('.docs-content').appendChild(noResults);
                }
                noResults.style.display = 'block';
            } else if (noResults) {
                noResults.style.display = 'none';
            }
        });

        // Atalho de teclado (Ctrl+K / Cmd+K)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    }

    // 3. ALTERNAR SIDEBAR (Móvel)

    function initSidebarToggle() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('docsSidebar');
        const closeBtn = document.getElementById('sidebarClose');

        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
                document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
                this.classList.toggle('active');
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    document.body.style.overflow = '';
                    toggleBtn.classList.remove('active');
                });
            }

            // Fechar ao clicar fora
            document.addEventListener('click', function(e) {
                if (sidebar.classList.contains('mobile-open') && 
                    !sidebar.contains(e.target) && 
                    !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('mobile-open');
                    document.body.style.overflow = '';
                    toggleBtn.classList.remove('active');
                }
            });
        }
    }

    // 4. NAVEGAÇÃO ATIVA

    function initActiveNav() {
        const navLinks = document.querySelectorAll('.sidebar-nav a');
        const sections = document.querySelectorAll('.docs-article-body h2, .docs-article-body h3');

        if (!navLinks.length || !sections.length) return;

        function updateActiveNav() {
            let current = '';
            const scrollPos = window.scrollY + 120;

            sections.forEach(function(section) {
                const top = section.offsetTop;
                const bottom = top + section.offsetHeight;
                if (scrollPos >= top && scrollPos < bottom) {
                    current = section.id;
                }
            });

            navLinks.forEach(function(link) {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        }

        window.addEventListener('scroll', updateActiveNav);
        updateActiveNav();
    }

    // 5. COPIAR CÓDIGO

    function initCopyCode() {
        const copyButtons = document.querySelectorAll('.code-copy');

        copyButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const pre = this.closest('pre');
                const code = pre ? pre.querySelector('code') : null;
                if (!code) return;

                const text = code.textContent;

                navigator.clipboard.writeText(text).then(function() {
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                    setTimeout(function() {
                        btn.innerHTML = originalText;
                    }, 2000);
                }).catch(function() {
                    // Fallback
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);

                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                    setTimeout(function() {
                        btn.innerHTML = originalText;
                    }, 2000);
                });
            });
        });
    }

    // 6. BOTÕES DE FEEDBACK

    function initFeedbackButtons() {
        const buttons = document.querySelectorAll('.docs-feedback-buttons button');

        buttons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const parent = this.closest('.docs-feedback-buttons');
                if (parent) {
                    parent.querySelectorAll('button').forEach(function(b) {
                        b.classList.remove('active');
                    });
                }
                this.classList.add('active');

                // Mostrar mensagem de agradecimento
                const feedbackContainer = this.closest('.docs-feedback');
                let thankYou = feedbackContainer.querySelector('.feedback-thank-you');

                if (thankYou) {
                    thankYou.style.display = 'block';
                    setTimeout(function() {
                        thankYou.style.display = 'none';
                    }, 3000);
                } else {
                    const msg = document.createElement('span');
                    msg.className = 'feedback-thank-you';
                    msg.style.cssText = `
                        color: #00875A;
                        font-weight: 600;
                        margin-left: auto;
                    `;
                    msg.innerHTML = '<i class="fas fa-check-circle"></i> Obrigado pelo seu feedback!';
                    feedbackContainer.appendChild(msg);
                    setTimeout(function() {
                        msg.style.display = 'none';
                    }, 3000);
                }
            });
        });
    }

    // 7. ÍNDICE DE CONTEÚDO (TOC)

    function initTocScroll() {
        const tocLinks = document.querySelectorAll('.docs-toc a');
        const headings = document.querySelectorAll('.docs-article-body h2, .docs-article-body h3');

        if (!tocLinks.length || !headings.length) return;

        function updateToc() {
            let current = '';
            const scrollPos = window.scrollY + 120;

            headings.forEach(function(heading) {
                const top = heading.offsetTop;
                const bottom = top + heading.offsetHeight;
                if (scrollPos >= top && scrollPos < bottom) {
                    current = heading.id;
                }
            });

            tocLinks.forEach(function(link) {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        }

        window.addEventListener('scroll', updateToc);
        updateToc();
    }

    // 8. BOTÃO VOLTAR AO TOPO

    function initBackToTop() {
        const backToTop = document.getElementById('backToTop');
        if (!backToTop) return;

        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // 9. NAVBAR SCROLL

    function initNavbarScroll() {
        const navbar = document.getElementById('navbar');
        if (!navbar) return;

        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // 10. MENU MÓVEL

    function initMobileMenu() {
        const hamburger = document.getElementById('hamburgerBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const overlay = document.getElementById('mobileMenuOverlay');
        const closeMenu = document.getElementById('mobileMenuClose');

        if (hamburger && mobileMenu && overlay) {
            function openMenu() {
                mobileMenu.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                hamburger.classList.add('active');
                hamburger.setAttribute('aria-expanded', 'true');
            }

            function closeMenuFn() {
                mobileMenu.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
            }

            hamburger.addEventListener('click', openMenu);

            if (closeMenu) {
                closeMenu.addEventListener('click', closeMenuFn);
            }

            overlay.addEventListener('click', closeMenuFn);

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                    closeMenuFn();
                }
            });
        }
    }

    // 11. TOAST (Notificações)

    function showToast(message, type) {
        type = type || 'success';
        const existingToast = document.querySelector('.docs-toast');
        if (existingToast) existingToast.remove();

        const toast = document.createElement('div');
        toast.className = 'docs-toast docs-toast-' + type;
        toast.style.cssText = `
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #172B4D;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 0.875rem;
            z-index: 1000000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            font-family: 'Inter', sans-serif;
        `;

        if (type === 'success') toast.style.background = '#10B981';
        if (type === 'error') toast.style.background = '#EF4444';
        if (type === 'info') toast.style.background = '#0052CC';

        toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle') + '"></i><span>' + message + '</span>';
        document.body.appendChild(toast);

        setTimeout(function() {
            toast.style.transform = 'translateX(0)';
        }, 100);

        setTimeout(function() {
            toast.style.transform = 'translateX(400px)';
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 4000);
    }

    // 12. INICIALIZAR TUDO
    
    document.addEventListener('DOMContentLoaded', function() {
        initSearch();
        initSidebarToggle();
        initActiveNav();
        initCopyCode();
        initFeedbackButtons();
        initTocScroll();
        initBackToTop();
        initNavbarScroll();
        initMobileMenu();
    });

})();