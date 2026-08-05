/**
 * CARDOXIS - Privacy Policy Page JavaScript  RF
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
    
    initPrintFunction();
    initPDFFunction();
    initCopyUrlFunction();
    initCopyLinkFunction();
    initSmoothScroll();
    initBackToTop();
    initCookieSettings();
    initNavbarScroll();
    initMobileMenu();
    initActiveSectionHighlight();
});

// PRINT FUNCTION

function initPrintFunction() {
    const printBtn = document.getElementById('printBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            const originalTitle = document.title;
            document.title = 'CARDOXIS - Política de Privacidade';
            window.print();
            setTimeout(function() {
                document.title = originalTitle;
            }, 100);
        });
    }
}

// PDF FUNCTION - Usando jsPDF com html2canvas

function initPDFFunction() {
    const pdfBtn = document.getElementById('pdfBtn');
    if (!pdfBtn) return;

    pdfBtn.addEventListener('click', async function() {
        const originalText = pdfBtn.innerHTML;
        pdfBtn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Gerando PDF...';
        pdfBtn.disabled = true;

        try {
            // Get the content element
            const element = document.getElementById('privacyContent');
            if (!element) {
                throw new Error('Conteúdo não encontrado');
            }

            // Clone the element
            const clone = element.cloneNode(true);
            
            // Create a wrapper for PDF
            const wrapper = document.createElement('div');
            wrapper.style.cssText = `
                position: fixed;
                top: 0;
                left: -9999px;
                width: 800px;
                background: white;
                padding: 40px;
                font-family: 'Inter', sans-serif;
            `;
            
            // Add cover
            const cover = createCoverElement();
            wrapper.appendChild(cover);
            
            // Style and add content
            styleForPDF(clone);
            wrapper.appendChild(clone);
            
            // Add footer
            const footer = createFooterElement();
            wrapper.appendChild(footer);
            
            document.body.appendChild(wrapper);
            
            // Wait for rendering
            await new Promise(resolve => setTimeout(resolve, 500));
            
            // Use html2canvas to capture
            const canvas = await html2canvas(wrapper, {
                scale: 2,
                backgroundColor: '#ffffff',
                logging: false,
                useCORS: true
            });
            
            // Create PDF
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({
                unit: 'mm',
                format: 'a4',
                orientation: 'portrait'
            });
            
            const imgWidth = 210; // A4 width in mm
            const pageHeight = 297; // A4 height in mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            let heightLeft = imgHeight;
            let position = 0;
            
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
            
            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            
            pdf.save('cardoxis-politica-privacidade.pdf');
            
            // Clean up
            document.body.removeChild(wrapper);
            
            showToast('PDF gerado com sucesso!', 'success');
            
        } catch (error) {
            console.error('PDF Error:', error);
            showToast('Erro ao gerar PDF', 'error');
        } finally {
            pdfBtn.innerHTML = originalText;
            pdfBtn.disabled = false;
        }
    });
}

function createCoverElement() {
    const cover = document.createElement('div');
    cover.style.cssText = `
        text-align: center;
        padding: 60px 40px;
        background: linear-gradient(135deg, #F0F5FF 0%, #E8F0FE 100%);
        margin-bottom: 40px;
        border-radius: 16px;
    `;
    
    // Get logo
    let logoHtml = '';
    const logoImg = document.querySelector('.logo-img, .navbar .logo img, .footer-logo img, .logo img');
    if (logoImg && logoImg.src) {
        logoHtml = `<img src="${logoImg.src}" alt="CARDOXIS" style="height: 60px; width: auto;">`;
    } else {
        logoHtml = '<div style="font-size: 32px; font-weight: 800; color: #0052CC;">CARDOXIS</div>';
    }
    
    const currentDate = new Date().toLocaleDateString('pt-PT');
    
    cover.innerHTML = `
        <div style="margin-bottom: 30px;">
            ${logoHtml}
        </div>
        <div style="display: inline-block; background: rgba(0,82,204,0.1); padding: 6px 20px; border-radius: 50px; margin-bottom: 25px;">
            <span style="color: #0052CC; font-weight: 600; font-size: 12px;">DOCUMENTO OFICIAL</span>
        </div>
        <h1 style="font-size: 36px; font-weight: 800; color: #172B4D; margin-bottom: 15px;">
            Política de<br>
            <span style="color: #0052CC;">Privacidade</span>
        </h1>
        <p style="font-size: 14px; color: #42526E; line-height: 1.5; margin-bottom: 30px; max-width: 400px; margin-left: auto; margin-right: auto;">
            Este documento descreve como a CARDOXIS coleta, utiliza e protege seus dados pessoais em conformidade com a LGPD e GDPR.
        </p>
        <div style="display: flex; justify-content: center; gap: 30px; margin-bottom: 40px;">
            <div>
                <div style="font-size: 10px; color: #6B778C;">VERSÃO</div>
                <div style="font-size: 16px; font-weight: 700; color: #0052CC;">2.0</div>
            </div>
            <div>
                <div style="font-size: 10px; color: #6B778C;">DATA</div>
                <div style="font-size: 14px; font-weight: 600; color: #172B4D;">${currentDate}</div>
            </div>
            <div>
                <div style="font-size: 10px; color: #6B778C;">STATUS</div>
                <div style="font-size: 14px; font-weight: 700; color: #10B981;">EM VIGOR</div>
            </div>
        </div>
        <div style="border-top: 1px solid rgba(0,0,0,0.1); padding-top: 25px;">
            <p style="font-size: 10px; color: #A5ADBA;">© ${new Date().getFullYear()} CARDOXIS - Todos os direitos reservados</p>
        </div>
    `;
    
    return cover;
}

function createFooterElement() {
    const footer = document.createElement('div');
    footer.style.cssText = `
        text-align: center;
        padding: 50px 40px;
        background: #F8FAFC;
        border-top: 1px solid #E2E8F0;
        margin-top: 40px;
        border-radius: 16px;
    `;
    
    footer.innerHTML = `
        <div style="max-width: 400px; margin: 0 auto;">
            <div style="width: 60px; height: 60px; background: #0052CC; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h3 style="font-size: 18px; font-weight: 700; color: #172B4D; margin-bottom: 12px;">Documento Oficial</h3>
            <p style="font-size: 12px; color: #42526E; line-height: 1.5; margin-bottom: 25px;">
                Este documento é a Política de Privacidade oficial da CARDOXIS.
            </p>
            <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #E2E8F0;">
                <p style="font-size: 9px; color: #A5ADBA;">
                    Em caso de dúvidas, entre em contato: dpo@cardoxis.com
                </p>
            </div>
        </div>
    `;
    
    return footer;
}

function styleForPDF(content) {
    // Style sections
    const sections = content.querySelectorAll('.privacy-section');
    sections.forEach(function(section) {
        section.style.cssText = `
            margin-bottom: 30px;
            page-break-inside: avoid;
        `;
    });
    
    // Style headers
    const headers = content.querySelectorAll('.section-header-inline h2');
    headers.forEach(function(header) {
        header.style.cssText = `
            font-size: 18px;
            color: #172B4D;
            margin: 0 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #0052CC;
            display: inline-block;
        `;
    });
    
    // Hide interactive elements
    const hiddenElements = content.querySelectorAll('.copy-link, .btn-exercise, .acceptance-buttons, .action-buttons, .summary-card, .privacy-actions-section');
    hiddenElements.forEach(function(el) {
        el.style.display = 'none';
    });
    
    // Style cards
    const cards = content.querySelectorAll('.info-card-mini, .right-card, .security-item, .purpose-item');
    cards.forEach(function(card) {
        card.style.cssText = `
            background: #F8FAFC;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 12px;
        `;
    });
    
    // Style grids
    const grids = content.querySelectorAll('.info-grid, .rights-grid, .security-list');
    grids.forEach(function(grid) {
        grid.style.display = 'grid';
        grid.style.gridTemplateColumns = 'repeat(2, 1fr)';
        grid.style.gap = '12px';
        grid.style.margin = '15px 0';
    });
    
    // Style tables
    const tables = content.querySelectorAll('table');
    tables.forEach(function(table) {
        table.style.width = '100%';
        table.style.borderCollapse = 'collapse';
        table.style.fontSize = '11px';
        
        const ths = table.querySelectorAll('th');
        ths.forEach(function(th) {
            th.style.cssText = `
                background: #F8FAFC;
                padding: 8px;
                text-align: left;
                font-weight: 600;
                border-bottom: 1px solid #E2E8F0;
            `;
        });
        
        const tds = table.querySelectorAll('td');
        tds.forEach(function(td) {
            td.style.cssText = `
                padding: 6px 8px;
                border-bottom: 1px solid #E2E8F0;
            `;
        });
    });
    
    // Style notes
    const notes = content.querySelectorAll('.info-note, .cookie-note, .eu-flag-note');
    notes.forEach(function(note) {
        note.style.cssText = `
            background: #E8F0FE;
            border-radius: 8px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
            font-size: 12px;
        `;
    });
    
    // Style exercise rights
    const exerciseRights = content.querySelector('.exercise-rights');
    if (exerciseRights) {
        exerciseRights.style.cssText = `
            display: flex;
            gap: 15px;
            background: #E8F0FE;
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
        `;
    }
    
    // Style certification badge
    const certBadge = content.querySelector('.certification-badge');
    if (certBadge) {
        certBadge.style.cssText = `
            display: flex;
            align-items: center;
            gap: 12px;
            background: #10B981;
            border-radius: 10px;
            padding: 12px;
            color: white;
            margin: 15px 0;
        `;
    }
    
    // Style text
    const paragraphs = content.querySelectorAll('p, li, span');
    paragraphs.forEach(function(p) {
        if (p.style.color !== 'white') {
            p.style.color = '#42526E';
        }
    });
}

// COPY FUNCTIONS

function initCopyUrlFunction() {
    const copyUrlBtn = document.getElementById('copyUrlBtn');
    if (copyUrlBtn) {
        copyUrlBtn.addEventListener('click', async function() {
            try {
                await navigator.clipboard.writeText(window.location.href);
                showToast('URL copiada!', 'success');
            } catch (err) {
                showToast('Erro ao copiar', 'error');
            }
        });
    }
}

function initCopyLinkFunction() {
    const copyLinks = document.querySelectorAll('.copy-link');
    copyLinks.forEach(function(link) {
        link.addEventListener('click', async function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('data-section');
            const url = window.location.origin + window.location.pathname + '#' + sectionId;
            
            try {
                await navigator.clipboard.writeText(url);
                const icon = this.querySelector('i');
                const originalIcon = icon.className;
                icon.className = 'fas fa-check';
                showToast('Link copiado!', 'success');
                setTimeout(function() {
                    icon.className = originalIcon;
                }, 1500);
            } catch (err) {
                showToast('Erro ao copiar', 'error');
            }
        });
    });
}

// SMOOTH SCROLL

function initSmoothScroll() {
    const summaryLinks = document.querySelectorAll('.summary-link');
    const navbar = document.getElementById('navbar');
    const navbarHeight = navbar ? navbar.offsetHeight : 80;
    
    summaryLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                const targetPosition = targetElement.offsetTop - navbarHeight - 20;
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                history.pushState(null, null, targetId);
            }
        });
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

// COOKIE SETTINGS

function initCookieSettings() {
    const cookieLink = document.getElementById('openCookieSettings');
    if (cookieLink) {
        cookieLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.CardoxisCookies && typeof window.CardoxisCookies.openSettings === 'function') {
                window.CardoxisCookies.openSettings();
            } else {
                showToast('Configurações de cookies disponíveis em breve.', 'info');
            }
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

// ACTIVE SECTION HIGHLIGHT

function initActiveSectionHighlight() {
    const sections = document.querySelectorAll('.privacy-section');
    const navLinks = document.querySelectorAll('.summary-link');
    const navbar = document.getElementById('navbar');
    const navbarHeight = navbar ? navbar.offsetHeight : 80;
    
    if (sections.length && navLinks.length) {
        function updateActiveSection() {
            let current = '';
            const scrollPosition = window.scrollY + navbarHeight + 100;
            
            sections.forEach(function(section) {
                const sectionTop = section.offsetTop;
                const sectionBottom = sectionTop + section.offsetHeight;
                
                if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                    current = '#' + section.getAttribute('id');
                }
            });
            
            navLinks.forEach(function(link) {
                link.classList.remove('active');
                if (link.getAttribute('href') === current) {
                    link.classList.add('active');
                }
            });
        }
        
        window.addEventListener('scroll', updateActiveSection);
        updateActiveSection();
    }
}

// TOAST NOTIFICATION

function showToast(message, type) {
    type = type || 'success';
    const existingToast = document.querySelector('.privacy-toast');
    if (existingToast) existingToast.remove();
    
    const toast = document.createElement('div');
    toast.className = 'privacy-toast privacy-toast-' + type;
    
    var iconClass = 'fa-check-circle';
    if (type === 'error') iconClass = 'fa-exclamation-circle';
    if (type === 'info') iconClass = 'fa-info-circle';
    
    toast.innerHTML = '<i class="fas ' + iconClass + '"></i><span>' + message + '</span>';
    document.body.appendChild(toast);
    
    if (!document.getElementById('privacyToastStyles')) {
        var styles = document.createElement('style');
        styles.id = 'privacyToastStyles';
        styles.textContent = `
            .privacy-toast {
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
            }
            .privacy-toast.privacy-toast-success {
                background: #10B981;
            }
            .privacy-toast.privacy-toast-error {
                background: #EF4444;
            }
            .privacy-toast.privacy-toast-info {
                background: #0052CC;
            }
            .privacy-toast.show {
                transform: translateX(0);
            }
        `;
        document.head.appendChild(styles);
    }
    
    setTimeout(function() {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(function() {
        toast.classList.remove('show');
        setTimeout(function() {
            toast.remove();
        }, 300);
    }, 4000);
}