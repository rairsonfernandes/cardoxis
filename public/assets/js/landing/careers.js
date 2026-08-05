/**
 * CARDOXIS - Careers Page JavaScript RF
 */

'use strict';

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true,
            offset: 50,
            easing: 'ease-in-out'
        });
    }
    
    initFilterJobs();
    initApplyModal();
    initBackToTop();
    initNavbarScroll();
    initMobileMenu();
});

// FILTER JOBS

function initFilterJobs() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const jobCards = document.querySelectorAll('.job-card');
    const noResults = document.getElementById('noResults');
    
    if (!filterBtns.length) return;
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            let visibleCount = 0;
            
            // Filter jobs
            jobCards.forEach(card => {
                if (filter === 'all' || card.getAttribute('data-category') === filter) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });
            
            // Show/hide no results message
            if (noResults) {
                if (visibleCount === 0) {
                    noResults.style.display = 'block';
                } else {
                    noResults.style.display = 'none';
                }
            }
        });
    });
}

// APPLY MODAL

function initApplyModal() {
    const applyBtns = document.querySelectorAll('.btn-apply');
    const modal = document.getElementById('applyModal');
    const closeBtn = document.getElementById('closeModalBtn');
    const overlay = modal ? modal.querySelector('.modal-overlay') : null;
    const jobTitleField = document.getElementById('jobTitleField');
    
    if (!modal) return;
    
    applyBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const jobTitle = this.getAttribute('data-job');
            if (jobTitleField) {
                jobTitleField.value = jobTitle;
            }
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });
    
    const closeModal = () => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    };
    
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', closeModal);
    
    // Close with ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
}

// BACK TO TOP

function initBackToTop() {
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
}

// NAVBAR SCROLL

function initNavbarScroll() {
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
}

// MOBILE MENU

function initMobileMenu() {
    const hamburger = document.getElementById('hamburgerBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileMenuOverlay');
    const closeMenu = document.getElementById('mobileMenuClose');
    
    if (hamburger && mobileMenu && overlay) {
        const openMenu = function() {
            mobileMenu.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            hamburger.classList.add('active');
        };
        
        const closeMenuFn = function() {
            mobileMenu.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            hamburger.classList.remove('active');
        };
        
        hamburger.addEventListener('click', openMenu);
        if (closeMenu) closeMenu.addEventListener('click', closeMenuFn);
        overlay.addEventListener('click', closeMenuFn);
    }
}