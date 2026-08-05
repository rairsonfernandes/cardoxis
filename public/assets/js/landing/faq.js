/**
 * CARDOXIS - FAQ Page JavaScript RF
 */

// Initialize AOS
document.addEventListener('DOMContentLoaded', function() {
    AOS.init({
        duration: 1000,
        once: true,
        offset: 100
    });
    
    initFaqAccordion();
    initCategoryFilter();
    initSearch();
    initStickyCategories();
});

// FAQ Accordion
function initFaqAccordion() {
    const faqCards = document.querySelectorAll('.faq-card');
    
    faqCards.forEach(card => {
        const question = card.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            const isActive = card.classList.contains('active');
            
            // Close all other cards in the same category group
            const parentGroup = card.closest('.faq-category-group');
            const cardsInGroup = parentGroup.querySelectorAll('.faq-card');
            
            cardsInGroup.forEach(otherCard => {
                if (otherCard !== card && otherCard.classList.contains('active')) {
                    otherCard.classList.remove('active');
                }
            });
            
            // Toggle current card
            if (!isActive) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });
    });
}

// Category Filter
function initCategoryFilter() {
    const categoryBtns = document.querySelectorAll('.category-btn');
    const categoryGroups = document.querySelectorAll('.faq-category-group');
    
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const category = btn.dataset.category;
            
            // Update active button
            categoryBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Filter category groups
            if (category === 'all') {
                categoryGroups.forEach(group => {
                    group.classList.remove('hidden');
                });
            } else {
                categoryGroups.forEach(group => {
                    if (group.dataset.category === category) {
                        group.classList.remove('hidden');
                    } else {
                        group.classList.add('hidden');
                    }
                });
            }
            
            // Scroll to top of content
            const faqContent = document.querySelector('.faq-content');
            if (faqContent) {
                faqContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            
            // Reset search input
            const searchInput = document.getElementById('faqSearch');
            if (searchInput) {
                searchInput.value = '';
                clearHighlights();
                const resultCount = document.getElementById('resultCount');
                if (resultCount) resultCount.textContent = '';
                const clearBtn = document.getElementById('clearSearch');
                if (clearBtn) clearBtn.style.display = 'none';
            }
        });
    });
}

// Search Functionality
function initSearch() {
    const searchInput = document.getElementById('faqSearch');
    const clearBtn = document.getElementById('clearSearch');
    const resultCountSpan = document.getElementById('resultCount');
    const noResultsDiv = document.getElementById('noResults');
    const faqContainer = document.querySelector('.faq-container');
    
    if (!searchInput) return;
    
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase().trim();
        
        // Show/hide clear button
        if (clearBtn) {
            clearBtn.style.display = searchTerm ? 'flex' : 'none';
        }
        
        if (searchTerm === '') {
            // Reset to show all
            resetSearch();
            return;
        }
        
        // Search through all FAQ cards
        const allCards = document.querySelectorAll('.faq-card');
        let visibleCount = 0;
        
        // First, show all category groups
        const allGroups = document.querySelectorAll('.faq-category-group');
        allGroups.forEach(group => {
            group.classList.remove('hidden');
        });
        
        // Activate "all" category button
        const allBtn = document.querySelector('.category-btn[data-category="all"]');
        if (allBtn) {
            document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
            allBtn.classList.add('active');
        }
        
        allCards.forEach(card => {
            const question = card.querySelector('.faq-question h3')?.textContent.toLowerCase() || '';
            const answer = card.querySelector('.faq-answer p')?.textContent.toLowerCase() || '';
            const category = card.closest('.faq-category-group')?.dataset.category || '';
            
            if (question.includes(searchTerm) || answer.includes(searchTerm) || category.includes(searchTerm)) {
                card.style.display = 'block';
                visibleCount++;
                highlightText(card, searchTerm);
            } else {
                card.style.display = 'none';
            }
        });
        
        // Update result count
        if (resultCountSpan) {
            if (visibleCount === 1) {
                resultCountSpan.textContent = `${visibleCount} pergunta encontrada`;
            } else {
                resultCountSpan.textContent = `${visibleCount} perguntas encontradas`;
            }
        }
        
        // Show/hide no results message
        if (noResultsDiv) {
            if (visibleCount === 0) {
                noResultsDiv.style.display = 'block';
                if (faqContainer) faqContainer.style.display = 'none';
            } else {
                noResultsDiv.style.display = 'none';
                if (faqContainer) faqContainer.style.display = 'block';
            }
        }
    });
    
    // Clear search
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            resetSearch();
            searchInput.focus();
        });
    }
    
    // Reset search button in no results
    const resetBtn = document.getElementById('resetSearch');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            searchInput.value = '';
            resetSearch();
            searchInput.focus();
        });
    }
}

function resetSearch() {
    const allCards = document.querySelectorAll('.faq-card');
    const resultCountSpan = document.getElementById('resultCount');
    const noResultsDiv = document.getElementById('noResults');
    const faqContainer = document.querySelector('.faq-container');
    const clearBtn = document.getElementById('clearSearch');
    
    allCards.forEach(card => {
        card.style.display = 'block';
    });
    
    clearHighlights();
    
    if (resultCountSpan) resultCountSpan.textContent = '';
    if (noResultsDiv) noResultsDiv.style.display = 'none';
    if (faqContainer) faqContainer.style.display = 'block';
    if (clearBtn) clearBtn.style.display = 'none';
    
    // Reset to show all categories
    const allGroups = document.querySelectorAll('.faq-category-group');
    allGroups.forEach(group => {
        group.classList.remove('hidden');
    });
}

function highlightText(card, searchTerm) {
    // Remove existing highlights
    clearHighlights();
    
    const questionElement = card.querySelector('.faq-question h3');
    const answerElement = card.querySelector('.faq-answer p');
    
    if (questionElement && questionElement.textContent.toLowerCase().includes(searchTerm)) {
        highlightElement(questionElement, searchTerm);
    }
    
    if (answerElement && answerElement.textContent.toLowerCase().includes(searchTerm)) {
        highlightElement(answerElement, searchTerm);
    }
    
    // Add highlight class to card
    card.classList.add('highlight');
    setTimeout(() => {
        card.classList.remove('highlight');
    }, 1000);
}

function highlightElement(element, searchTerm) {
    const text = element.textContent;
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    const newText = text.replace(regex, '<mark class="highlight-text">$1</mark>');
    element.innerHTML = newText;
}

function clearHighlights() {
    const allMarkers = document.querySelectorAll('.highlight-text');
    allMarkers.forEach(marker => {
        const parent = marker.parentNode;
        parent.innerHTML = parent.textContent;
    });
}

// Sticky Categories on Scroll
function initStickyCategories() {
    const categoriesSection = document.querySelector('.faq-categories');
    if (!categoriesSection) return;
    
    let lastScrollTop = 0;
    const navbar = document.getElementById('navbar');
    const navbarHeight = navbar ? navbar.offsetHeight : 70;
    
    window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > navbarHeight + 50) {
            categoriesSection.style.top = navbarHeight + 'px';
        } else {
            categoriesSection.style.top = '70px';
        }
        
        lastScrollTop = scrollTop;
    });
}

// Navbar scroll effect
(function() {
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
})();

// Back to Top Button
(function() {
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
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
})();

// Mobile Menu
(function() {
    const hamburger = document.getElementById('hamburgerBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileMenuOverlay');
    const closeMenu = document.getElementById('mobileMenuClose');
    
    if (hamburger && mobileMenu && overlay) {
        const openMenu = () => {
            mobileMenu.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            hamburger.classList.add('active');
        };
        
        const closeMenuFn = () => {
            mobileMenu.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            hamburger.classList.remove('active');
        };
        
        hamburger.addEventListener('click', openMenu);
        if (closeMenu) closeMenu.addEventListener('click', closeMenuFn);
        overlay.addEventListener('click', closeMenuFn);
    }
})();

console.log('CARDOXIS FAQ Page - Initialized');