/**
 * CARDOXIS - Cookie Consent System v4.0
 * Enterprise Grade Cookie Management
 * GDPR & LGPD Compliant
 * Professional Production Ready
 */

(function () {

    const COOKIE_NAME = "cardoxis_cookie_consent_v4";
    const CONFIG_VERSION = "4.0.0";
    
    // ============================================
    // CORE UTILITIES
    // ============================================
    function getBaseUrl() {
        if (window.CARDOXIS_CONFIG && window.CARDOXIS_CONFIG.baseUrl) {
            return window.CARDOXIS_CONFIG.baseUrl.replace(/\/$/, '');
        }
        
        const path = window.location.pathname;
        const isSubfolder = path.includes('/cardoxis/') || path.includes('/public/');
        
        if (isSubfolder) {
            const parts = path.split('/');
            const baseIndex = parts.findIndex(p => p === 'cardoxis' || p === 'public');
            if (baseIndex > 0) {
                return '/' + parts.slice(1, baseIndex + 1).join('/');
            }
        }
        return '';
    }

    function getLogoUrl() {
        // Tentar obter do CONFIG global
        if (window.CARDOXIS_CONFIG && window.CARDOXIS_CONFIG.logoUrl) {
            return window.CARDOXIS_CONFIG.logoUrl;
        }
        
        // Tentar obter do CONFIG
        let logoPath = 'img/logo/logo.png';
        if (window.CARDOXIS_CONFIG && window.CARDOXIS_CONFIG.logo) {
            logoPath = window.CARDOXIS_CONFIG.logo;
        }
        
        // Tentar encontrar a logo no DOM
        const selectors = [
            '.logo-img',
            '.navbar .logo img',
            '.footer-logo img',
            '.logo img',
            '#logo img',
            'img[alt*="logo"]',
            'img[alt*="CARDOXIS"]'
        ];
        
        for (const selector of selectors) {
            const img = document.querySelector(selector);
            if (img && img.src && img.src.trim() !== '') {
                return img.src;
            }
        }
        
        // Construir URL base
        const baseUrl = getBaseUrl();
        const cleanLogo = logoPath.replace(/^\//, '');
        
        if (baseUrl) {
            return `${baseUrl}/${cleanLogo}`;
        }
        
        // Fallback - criar logo em canvas
        return createFallbackLogo();
    }
    
    function createFallbackLogo() {
        const canvas = document.createElement('canvas');
        canvas.width = 400;
        canvas.height = 100;
        const ctx = canvas.getContext('2d');
        
        const gradient = ctx.createLinearGradient(0, 0, canvas.width, 0);
        gradient.addColorStop(0, '#0052CC');
        gradient.addColorStop(1, '#003D99');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 36px "Inter", "Segoe UI", Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('CARDOXIS', canvas.width / 2, canvas.height / 2);
        
        ctx.strokeStyle = 'rgba(255,255,255,0.3)';
        ctx.lineWidth = 2;
        ctx.strokeRect(10, 10, canvas.width - 20, canvas.height - 20);
        
        return canvas.toDataURL('image/png');
    }

    function getConsent() {
        try {
            const data = localStorage.getItem(COOKIE_NAME);
            if (data) {
                const parsed = JSON.parse(data);
                if (parsed.date && (Date.now() - parsed.date) > 365 * 24 * 60 * 60 * 1000) {
                    localStorage.removeItem(COOKIE_NAME);
                    return null;
                }
                return parsed;
            }
            return null;
        } catch (e) {
            console.error('Cookie consent read error:', e);
            return null;
        }
    }

    function setConsent(data) {
        const consentData = {
            ...data,
            date: Date.now(),
            version: CONFIG_VERSION
        };
        localStorage.setItem(COOKIE_NAME, JSON.stringify(consentData));
        
        const event = new CustomEvent('cardoxis:cookieUpdate', { 
            detail: consentData 
        });
        window.dispatchEvent(event);
        
        applyConsentToServices(consentData);
        
        // Fechar modal se estiver aberto
        const modal = document.getElementById('cardoxis-cookie-overlay');
        if (modal) {
            closeModal(modal);
        }
    }

    function getPreference(type) {
        const consent = getConsent();
        if (!consent) return false;
        return consent[type] === true;
    }

    // ============================================
    // EXTERNAL SERVICES
    // ============================================
    function applyConsentToServices(consent) {
        if (consent.analytics) {
            enableGoogleAnalytics();
        } else {
            disableGoogleAnalytics();
        }
        
        if (consent.marketing) {
            enableMarketingCookies();
        } else {
            disableMarketingCookies();
        }
        
        console.log('[CARDOXIS] Cookie consent applied:', consent);
    }

    function enableGoogleAnalytics() {
        window['ga-disable-UA-XXXXXX-Y'] = false;
        
        if (!window.gtag && !document.querySelector('script[src*="gtag/js"]')) {
            const script = document.createElement('script');
            script.async = true;
            script.src = 'https://www.googletagmanager.com/gtag/js?id=UA-XXXXXX-Y';
            document.head.appendChild(script);
            
            window.dataLayer = window.dataLayer || [];
            window.gtag = function() { window.dataLayer.push(arguments); };
            window.gtag('js', new Date());
            window.gtag('config', 'UA-XXXXXX-Y', { 'anonymize_ip': true });
        }
    }

    function disableGoogleAnalytics() {
        window['ga-disable-UA-XXXXXX-Y'] = true;
    }

    function enableMarketingCookies() {
        if (!window.fbq && !document.querySelector('script[src*="facebook.net"]')) {
            !function(f,b,e,v,n,t,s) {
                if(f.fbq)return;
                n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)
            }(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', 'YOUR_PIXEL_ID');
            fbq('track', 'PageView');
        } else if (window.fbq) {
            fbq('consent', 'grant');
        }
    }

    function disableMarketingCookies() {
        if (window.fbq) {
            fbq('consent', 'revoke');
        }
    }

    // ============================================
    // STYLES - PROFESSIONAL ENTERPRISE
    // ============================================
    const style = document.createElement("style");
    style.innerHTML = `
        /* Overlay principal */
        #cardoxis-cookie-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            animation: cardoxisFadeIn 0.3s ease;
        }

        /* Container principal */
        .cardoxis-cookie-container {
            position: relative;
            width: 90%;
            max-width: 680px;
            max-height: 85vh;
            overflow-y: auto;
            background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%);
            border-radius: 32px;
            box-shadow: 0 32px 64px -12px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(0, 82, 204, 0.15);
            animation: cardoxisSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Botão fechar */
        .cardoxis-cookie-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 36px;
            height: 36px;
            background: #F1F5F9;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            z-index: 10;
            color: #6B778C;
            font-size: 1rem;
        }
        .cardoxis-cookie-close:hover {
            background: #E2E8F0;
            color: #172B4D;
            transform: rotate(90deg);
        }

        /* Custom Scrollbar */
        .cardoxis-cookie-container::-webkit-scrollbar {
            width: 6px;
        }
        .cardoxis-cookie-container::-webkit-scrollbar-track {
            background: #E2E8F0;
            border-radius: 10px;
        }
        .cardoxis-cookie-container::-webkit-scrollbar-thumb {
            background: #0052CC;
            border-radius: 10px;
        }

        /* Header */
        .cardoxis-cookie-header {
            padding: 32px 32px 20px;
            text-align: center;
            border-bottom: 1px solid #E2E8F0;
            background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%);
            border-radius: 32px 32px 0 0;
        }
        .cardoxis-cookie-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            min-height: 70px;
            align-items: center;
        }
        .cardoxis-cookie-logo img {
            height: 60px;
            width: auto;
            object-fit: contain;
            max-width: 200px;
        }
        .cardoxis-cookie-title {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0052CC 0%, #003D99 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        .cardoxis-cookie-subtitle {
            font-size: 0.875rem;
            color: #6B778C;
        }

        /* Content */
        .cardoxis-cookie-content {
            padding: 28px 32px;
        }
        .cardoxis-cookie-text {
            font-size: 0.875rem;
            color: #42526E;
            line-height: 1.6;
            margin-bottom: 28px;
            text-align: center;
        }

        /* Opções de cookies */
        .cardoxis-cookie-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 28px;
        }
        .cardoxis-cookie-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 20px;
            transition: all 0.2s ease;
        }
        .cardoxis-cookie-option:hover {
            border-color: #0052CC;
            box-shadow: 0 4px 12px rgba(0, 82, 204, 0.1);
        }
        .cardoxis-option-info { flex: 1; }
        .cardoxis-option-title {
            font-weight: 700;
            font-size: 0.9375rem;
            color: #172B4D;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .cardoxis-option-badge {
            font-size: 0.625rem;
            padding: 2px 8px;
            border-radius: 20px;
            background: #E8F0FE;
            color: #0052CC;
            font-weight: 600;
        }
        .cardoxis-option-desc {
            font-size: 0.75rem;
            color: #6B778C;
            line-height: 1.4;
        }

        /* Toggle Switch */
        .cardoxis-switch {
            width: 52px;
            height: 28px;
            background: #CBD5E1;
            border-radius: 50px;
            position: relative;
            cursor: pointer;
            transition: all 0.25s ease;
            flex-shrink: 0;
        }
        .cardoxis-switch.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .cardoxis-switch::after {
            content: "";
            position: absolute;
            top: 3px;
            left: 4px;
            width: 22px;
            height: 22px;
            background: #FFFFFF;
            border-radius: 50%;
            transition: all 0.25s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        .cardoxis-switch.active { background: #0052CC; }
        .cardoxis-switch.active::after { left: 26px; }

        /* Informações de segurança */
        .cardoxis-security-info {
            background: #F0F5FF;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .cardoxis-security-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.75rem;
            color: #0052CC;
        }
        .cardoxis-security-item i { font-size: 0.875rem; }

        /* Botões */
        .cardoxis-cookie-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .cardoxis-btn {
            flex: 1;
            padding: 14px 20px;
            border-radius: 40px;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .cardoxis-btn-primary {
            background: linear-gradient(135deg, #0052CC 0%, #003D99 100%);
            color: #FFFFFF;
            box-shadow: 0 8px 20px rgba(0, 82, 204, 0.25);
        }
        .cardoxis-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(0, 82, 204, 0.35);
        }
        .cardoxis-btn-secondary {
            background: #F1F5F9;
            color: #172B4D;
            border: 1px solid #E2E8F0;
        }
        .cardoxis-btn-secondary:hover {
            background: #E2E8F0;
            transform: translateY(-1px);
        }
        .cardoxis-btn-outline {
            background: transparent;
            color: #0052CC;
            border: 1px solid #0052CC;
        }
        .cardoxis-btn-outline:hover {
            background: #E8F0FE;
            transform: translateY(-1px);
        }

        /* Footer */
        .cardoxis-cookie-footer {
            padding: 20px 32px 32px;
            text-align: center;
            border-top: 1px solid #E2E8F0;
            background: #F8FAFC;
            border-radius: 0 0 32px 32px;
        }
        .cardoxis-cookie-footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .cardoxis-footer-link {
            font-size: 0.75rem;
            color: #6B778C;
            text-decoration: none;
            transition: color 0.2s;
        }
        .cardoxis-footer-link:hover { color: #0052CC; }
        .cardoxis-cookie-version {
            font-size: 0.625rem;
            color: #A5ADBA;
        }

        /* Animações */
        @keyframes cardoxisFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes cardoxisSlideUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        @keyframes cardoxisFadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* Toast Notification */
        .cardoxis-cookie-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #172B4D;
            color: white;
            padding: 14px 24px;
            border-radius: 16px;
            font-size: 0.875rem;
            z-index: 1000000;
            transform: translateX(450px);
            transition: transform 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        .cardoxis-cookie-toast.show { transform: translateX(0); }
        .cardoxis-cookie-toast.success { background: #10B981; }
        .cardoxis-cookie-toast.info { background: #0052CC; }

        /* Responsive */
        @media (max-width: 640px) {
            .cardoxis-cookie-container { width: 95%; max-height: 90vh; }
            .cardoxis-cookie-header { padding: 24px 20px 16px; }
            .cardoxis-cookie-content { padding: 20px; }
            .cardoxis-cookie-option { flex-direction: column; align-items: flex-start; gap: 12px; }
            .cardoxis-cookie-actions { flex-direction: column; }
            .cardoxis-security-info { flex-direction: column; align-items: flex-start; }
            .cardoxis-cookie-footer-links { gap: 16px; }
            .cardoxis-cookie-close { top: 12px; right: 12px; width: 32px; height: 32px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .cardoxis-cookie-container,
            .cardoxis-btn,
            .cardoxis-switch { animation: none; transition: none; }
        }
    `;
    document.head.appendChild(style);

    // ============================================
    // CREATE MODAL
    // ============================================
    let isModalOpen = false;

    function createModal() {
        // Não criar se já estiver aberto
        if (isModalOpen) return;
        
        const existing = document.getElementById('cardoxis-cookie-overlay');
        if (existing) {
            isModalOpen = true;
            return;
        }

        const overlay = document.createElement("div");
        overlay.id = "cardoxis-cookie-overlay";

        const currentConsent = getConsent();
        const logoUrl = getLogoUrl();
        
        overlay.innerHTML = `
            <div class="cardoxis-cookie-container">
                <button class="cardoxis-cookie-close" id="cardoxis-cookie-close-btn" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
                <div class="cardoxis-cookie-header">
                    <div class="cardoxis-cookie-logo">
                        <img src="${logoUrl}" alt="CARDOXIS" onerror="this.style.display='none'">
                    </div>
                    <div class="cardoxis-cookie-title">🍪 Sua privacidade é importante</div>
                    <div class="cardoxis-cookie-subtitle">Gerencie suas preferências de cookies</div>
                </div>

                <div class="cardoxis-cookie-content">
                    <div class="cardoxis-cookie-text">
                        Utilizamos cookies essenciais para o funcionamento da plataforma e cookies opcionais 
                        para melhorar sua experiência, analisar desempenho e personalizar conteúdo.
                    </div>

                    <div class="cardoxis-cookie-options">
                        <div class="cardoxis-cookie-option">
                            <div class="cardoxis-option-info">
                                <div class="cardoxis-option-title">
                                    Cookies Essenciais
                                    <span class="cardoxis-option-badge">Sempre Ativos</span>
                                </div>
                                <div class="cardoxis-option-desc">
                                    Necessários para o funcionamento básico do sistema e segurança.
                                </div>
                            </div>
                            <div class="cardoxis-switch active disabled"></div>
                        </div>

                        <div class="cardoxis-cookie-option">
                            <div class="cardoxis-option-info">
                                <div class="cardoxis-option-title">Cookies Analíticos</div>
                                <div class="cardoxis-option-desc">
                                    Permitem analisar o desempenho e melhorar nossos serviços.
                                </div>
                            </div>
                            <div class="cardoxis-switch ${currentConsent?.analytics ? 'active' : ''}" data-cookie="analytics"></div>
                        </div>

                        <div class="cardoxis-cookie-option">
                            <div class="cardoxis-option-info">
                                <div class="cardoxis-option-title">Cookies de Marketing</div>
                                <div class="cardoxis-option-desc">
                                    Utilizados para oferecer conteúdo e campanhas personalizadas.
                                </div>
                            </div>
                            <div class="cardoxis-switch ${currentConsent?.marketing ? 'active' : ''}" data-cookie="marketing"></div>
                        </div>
                    </div>

                    <div class="cardoxis-security-info">
                        <div class="cardoxis-security-item"><i class="fas fa-shield-alt"></i> Criptografia AES-256</div>
                        <div class="cardoxis-security-item"><i class="fas fa-lock"></i> LGPD & GDPR Compliant</div>
                        <div class="cardoxis-security-item"><i class="fas fa-database"></i> Servidores na Europa</div>
                    </div>

                    <div class="cardoxis-cookie-actions">
                        <button class="cardoxis-btn cardoxis-btn-secondary" id="cardoxis-reject-all">
                            <i class="fas fa-times"></i> Rejeitar todos
                        </button>
                        <button class="cardoxis-btn cardoxis-btn-outline" id="cardoxis-save-preferences">
                            <i class="fas fa-save"></i> Salvar preferências
                        </button>
                        <button class="cardoxis-btn cardoxis-btn-primary" id="cardoxis-accept-all">
                            <i class="fas fa-check"></i> Aceitar todos
                        </button>
                    </div>
                </div>

                <div class="cardoxis-cookie-footer">
                    <div class="cardoxis-cookie-footer-links">
                        <a href="${getBaseUrl()}/privacy" class="cardoxis-footer-link">Política de Privacidade</a>
                        <a href="${getBaseUrl()}/terms" class="cardoxis-footer-link">Termos de Uso</a>
                        <a href="${getBaseUrl()}/cookies" class="cardoxis-footer-link">Política de Cookies</a>
                    </div>
                    <div class="cardoxis-cookie-version">
                        Versão ${CONFIG_VERSION} | Atualizado em Janeiro 2024
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        isModalOpen = true;
        bindModalEvents(overlay);
    }

    function bindModalEvents(overlay) {
        // Toggle switches
        const switches = overlay.querySelectorAll('.cardoxis-switch[data-cookie]');
        switches.forEach(sw => {
            sw.addEventListener('click', (e) => {
                e.stopPropagation();
                sw.classList.toggle('active');
            });
        });

        // Accept all button
        const acceptBtn = overlay.querySelector('#cardoxis-accept-all');
        if (acceptBtn) {
            acceptBtn.addEventListener('click', () => {
                setConsent({ essential: true, analytics: true, marketing: true });
                showToast('Todas as preferências foram aceitas!', 'success');
            });
        }

        // Reject all button
        const rejectBtn = overlay.querySelector('#cardoxis-reject-all');
        if (rejectBtn) {
            rejectBtn.addEventListener('click', () => {
                setConsent({ essential: true, analytics: false, marketing: false });
                showToast('Cookies opcionais foram rejeitados.', 'success');
            });
        }

        // Save preferences button
        const saveBtn = overlay.querySelector('#cardoxis-save-preferences');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                const analytics = overlay.querySelector('[data-cookie="analytics"]')?.classList.contains('active') || false;
                const marketing = overlay.querySelector('[data-cookie="marketing"]')?.classList.contains('active') || false;
                setConsent({ essential: true, analytics, marketing });
                showToast('Preferências salvas com sucesso!', 'success');
            });
        }

        // Close button (X)
        const closeBtn = overlay.querySelector('#cardoxis-cookie-close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                closeModal(overlay);
            });
        }

        // Close on overlay click (apenas se não clicou no container)
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeModal(overlay);
            }
        });

        // ESC key to close
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                closeModal(overlay);
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    }

    function closeModal(overlay) {
        if (overlay && overlay.parentNode) {
            overlay.style.animation = 'cardoxisFadeOut 0.2s ease';
            setTimeout(() => {
                overlay.remove();
                isModalOpen = false;
            }, 200);
        } else {
            isModalOpen = false;
        }
    }

    // ============================================
    // TOAST NOTIFICATION
    // ============================================
    function showToast(message, type = 'success') {
        const existing = document.querySelector('.cardoxis-cookie-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = `cardoxis-cookie-toast ${type}`;
        toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i><span>${message}</span>`;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // ============================================
    // INITIALIZATION
    // ============================================
    function init() {
        if (!window.CARDOXIS_CONFIG) {
            window.CARDOXIS_CONFIG = { baseUrl: getBaseUrl(), logo: 'img/logo/logo.png' };
        }
        
        const consent = getConsent();
        
        if (!consent) {
            // Mostrar modal após um pequeno delay
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => setTimeout(createModal, 500));
            } else {
                setTimeout(createModal, 500);
            }
        } else {
            applyConsentToServices(consent);
        }
    }

    // ============================================
    // GLOBAL API
    // ============================================
    window.CardoxisCookies = {
        get: getConsent,
        set: setConsent,
        getPreference: getPreference,
        openSettings: createModal,
        showToast: showToast,
        hasConsent: () => getConsent() !== null,
        reset: () => { localStorage.removeItem(COOKIE_NAME); window.location.reload(); },
        version: CONFIG_VERSION
    };

    // Auto-initialize
    init();

    window.addEventListener('cardoxis:navigation', () => {
        const consent = getConsent();
        if (consent) applyConsentToServices(consent);
    });

})();