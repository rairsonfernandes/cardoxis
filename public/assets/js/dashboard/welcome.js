/**
 * CARDOXIS - Welcome rf
 */

class WelcomeSystem {
    constructor(config = {}) {
        this.config = {
            apiUrl: config.apiUrl || '/cardoxis/api/v1',
            debug: config.debug || false
        };
        
        this._initialized = false;
        
        this.init();
    }
    
    async init() {
        if (this._initialized) return;
        this._initialized = true;
        
        console.log('🚀 CARDOXIS Welcome');
        
        const token = this.getToken();
        if (!token) {
            console.log('⛔ Sem token');
            return;
        }
        
        // Verificar se deve mostrar
        const result = await this.apiRequest('/welcome/status');
        if (!result || !result.success) return;
        
        if (!result.data.show) {
            console.log('✅ Welcome já visto');
            return;
        }
        
        // Mostrar modal
        this.showWelcome();
    }
    
    showWelcome() {
        // Remover overlay existente
        this.removeExisting();
        
        const overlay = document.createElement('div');
        overlay.className = 'welcome-overlay';
        overlay.id = 'welcomeModal';
        overlay.innerHTML = `
            <div class="welcome-card">
                <div style="display:inline-block;background:#E6F0FF;color:#0052CC;padding:4px 16px;border-radius:20px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:16px;">
                    Gestão de frota
                </div>

                <div style="font-size:42px;font-weight:900;color:#0052CC;letter-spacing:-1px;margin-bottom:8px;">
                    CARDO<span style="color:#172B4D;">XIS</span>
                </div>

                <div class="welcome-icon-wrapper" style="margin:0 auto 16px;">
                    <i class="fas fa-rocket fa-2x"></i>
                </div>

                <h2 class="welcome-title">Bem-vindo ao CARDOXIS</h2>

                <p style="color:#42526E;font-weight:500;margin-bottom:4px;">
                    Sistema de Gestão de Frotas
                </p>

                <p style="color:#6B778C;font-size:14px;line-height:1.6;margin-bottom:24px;">
                    Está tudo pronto para começar a utilizar o sistema.<br>
                    Entre e comece a gerir a sua frota.
                </p>

                <button class="welcome-btn" id="enterSystem">
                    <i class="fas fa-arrow-right"></i> Entrar no sistema
                </button>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // Adicionar estilos
        this.injectStyles();
        
        // Evento do botão
        document.getElementById('enterSystem').addEventListener('click', async () => {
            // Marcar como visto
            await this.apiRequest('/welcome/mark-seen', { method: 'POST' });
            
            // Fechar modal com animação
            const modal = document.getElementById('welcomeModal');
            if (modal) {
                modal.style.opacity = '0';
                modal.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        });
    }
    
    injectStyles() {
        if (document.getElementById('welcome-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'welcome-styles';
        style.textContent = `
            .welcome-overlay {
                position:fixed;
                inset:0;
                background:rgba(9,30,66,0.65);
                backdrop-filter:blur(16px);
                -webkit-backdrop-filter:blur(16px);
                display:flex;
                justify-content:center;
                align-items:center;
                z-index:999999;
                padding:20px;
                animation: welcomeFadeIn 0.4s ease;
            }
            
            @keyframes welcomeFadeIn {
                from { opacity:0; backdrop-filter:blur(0px); }
                to { opacity:1; backdrop-filter:blur(16px); }
            }
            
            .welcome-card {
                background:#FFFFFF;
                border-radius:20px;
                padding:40px 48px;
                max-width:460px;
                width:100%;
                position:relative;
                box-shadow:0 25px 60px rgba(0,0,0,0.15);
                text-align:center;
                animation: welcomeSlideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            
            @keyframes welcomeSlideUp {
                from {
                    opacity:0;
                    transform:translateY(30px) scale(0.96);
                }
                to {
                    opacity:1;
                    transform:translateY(0) scale(1);
                }
            }
            
            .welcome-card::before {
                content:'';
                position:absolute;
                top:0;
                left:0;
                right:0;
                height:4px;
                background:linear-gradient(90deg, #0052CC, #4C9AFF, #0052CC);
                background-size:200% 100%;
                animation: shimmerGradient 3s ease-in-out infinite;
                border-radius:20px 20px 0 0;
            }
            
            @keyframes shimmerGradient {
                0%, 100% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
            }
            
            .welcome-icon-wrapper {
                width:72px;
                height:72px;
                border-radius:50%;
                background:#E6F0FF;
                color:#0052CC;
                display:flex;
                align-items:center;
                justify-content:center;
                font-size:28px;
                margin:0 auto 16px;
            }
            
            .welcome-title {
                font-size:24px;
                font-weight:700;
                color:#172B4D;
                margin-bottom:4px;
            }
            
            .welcome-btn {
                display:inline-flex;
                align-items:center;
                justify-content:center;
                gap:10px;
                background:#0052CC;
                color:#FFFFFF;
                border:none;
                padding:14px 32px;
                border-radius:12px;
                font-size:16px;
                font-weight:600;
                cursor:pointer;
                transition:all 0.25s ease;
                width:100%;
                box-shadow:0 4px 16px rgba(0,82,204,0.25);
            }
            
            .welcome-btn:hover {
                background:#003D99;
                transform:translateY(-2px);
                box-shadow:0 8px 24px rgba(0,82,204,0.35);
            }
            
            .welcome-btn:active {
                transform:translateY(0);
            }
            
            @media (max-width:640px) {
                .welcome-card {
                    padding:28px 20px;
                    margin:12px;
                }
                .welcome-title {
                    font-size:20px;
                }
                .welcome-icon-wrapper {
                    width:60px;
                    height:60px;
                    font-size:22px;
                }
                .welcome-overlay {
                    padding:12px;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    getToken() {
        return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    }
    
    async apiRequest(endpoint, options = {}) {
        const token = this.getToken();
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...options.headers
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        try {
            const response = await fetch(`${this.config.apiUrl}${endpoint}`, {
                ...options,
                headers,
                credentials: 'same-origin'
            });
            
            if (response.status === 401) {
                localStorage.removeItem('auth_token');
                sessionStorage.removeItem('auth_token');
                window.location.href = '/cardoxis/login';
                return null;
            }
            
            return await response.json();
        } catch (error) {
            console.error('❌ API Error:', error);
            return null;
        }
    }
    
    removeExisting() {
        const existing = document.querySelector('.welcome-overlay');
        if (existing) {
            existing.remove();
        }
    }
}

// INICIALIZAÇÃO AUTOMÁTICA

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.CardoxisWelcome = new WelcomeSystem({
            debug: true
        });
    });
} else {
    window.CardoxisWelcome = new WelcomeSystem({
        debug: true
    });
}