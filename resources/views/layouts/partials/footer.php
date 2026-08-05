<?php
/**
 * CARDOXIS - Rodapé RF
 */
?>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Coluna da Marca -->
            <div class="footer-brand">
                <div class="footer-logo">
                    <img src="<?= asset('img/logo/logo-white.png') ?>" alt="CARDOXIS" width="40" height="40" loading="lazy">
                    <span class="footer-logo-text">CARDOXIS<span>.io</span></span>
                </div>
                <p class="footer-description">
                    Plataforma inteligente para gestão de frotas com automação, análise de dados e eficiência operacional.
                </p>
            </div>

            <!-- Coluna Produto -->
            <div class="footer-section">
                <h4>Produto</h4>
                <ul>
                    <li><a href="<?= url('/') ?>#features">Funcionalidades</a></li>
                    <li><a href="<?= url('/') ?>#pricing">Planos</a></li>
                    <li><a href="<?= url('faq') ?>">FAQ</a></li>
                    <li><a href="<?= url('contact') ?>#demo">Solicitar Demo</a></li>
                </ul>
            </div>

            <!-- Coluna Empresa -->
            <div class="footer-section">
                <h4>Empresa</h4>
                <ul>
                    <li><a href="<?= url('about') ?>">Sobre Nós</a></li>
                    <li><a href="<?= url('contact') ?>">Contacto</a></li>
                    <li><a href="<?= url('careers') ?>">Carreiras</a></li>
                    <li><a href="<?= url('docs') ?>">Documentação</a></li>
                </ul>
            </div>

            <!-- Coluna Suporte -->
            <div class="footer-section">
                <h4>Suporte</h4>
                <ul>
                    <li><a href="<?= url('faq') ?>">Central de Ajuda</a></li>
                    <li><a href="<?= url('privacy') ?>">Política de Privacidade</a></li>
                    <li><a href="<?= url('terms') ?>">Termos de Uso</a></li>
                    <li><a href="<?= url('cookies') ?>">Política de Cookies</a></li>
                </ul>
            </div>

            <!-- Coluna Contacto -->
            <div class="footer-section">
                <h4>Contacto</h4>
                <ul class="footer-contact">
                    <li><i class="fas fa-envelope"></i> <a href="mailto:contato@cardoxis.com">contato@cardoxis.com</a></li>
                    <li><i class="fas fa-phone-alt"></i> <a href="tel:+351234567890">+351 234 567 890</a></li>
                    <li><i class="fas fa-map-marker-alt"></i> Av. da Liberdade, 245<br>1250-143 Lisboa, Portugal</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> CARDOXIS. Todos os direitos reservados.</p>
            <div class="footer-legal">
                <a href="<?= url('privacy') ?>">Política de Privacidade</a>
                <a href="<?= url('terms') ?>">Termos de Uso</a>
                <a href="<?= url('cookies') ?>">Política de Cookies</a>
                <a href="#" id="openCookieSettingsFooter">Preferências de Cookies</a>
            </div>
        </div>
    </div>
</footer>

<style>
/* Estilos do Rodapé */
.footer {
    background: #172B4D;
    color: #A5ADBA;
    padding: 60px 0 30px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.footer-grid {
    display: grid;
    grid-template-columns: 2fr repeat(4, 1fr);
    gap: 40px;
    margin-bottom: 40px;
}

.footer-brand {
    max-width: 300px;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.footer-logo img {
    height: 40px;
    width: auto;
}

.footer-logo-text {
    font-size: 1.3rem;
    font-weight: 800;
    color: #FFFFFF;
}

.footer-logo-text span {
    color: #0052CC;
}

.footer-description {
    font-size: 0.875rem;
    line-height: 1.6;
    color: #A5ADBA;
    margin-bottom: 20px;
}

.footer-section h4 {
    color: #FFFFFF;
    font-size: 1rem;
    margin-bottom: 20px;
    font-weight: 600;
}

.footer-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-section ul li {
    margin-bottom: 12px;
}

.footer-section ul li a {
    color: #A5ADBA;
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.2s ease;
}

.footer-section ul li a:hover {
    color: #0052CC;
}

.footer-contact li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 15px;
    font-size: 0.875rem;
    color: #A5ADBA;
}

.footer-contact li i {
    width: 20px;
    color: #0052CC;
    margin-top: 2px;
}

.footer-contact li a {
    color: #A5ADBA;
    text-decoration: none;
}

.footer-contact li a:hover {
    color: #0052CC;
}

.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    padding-top: 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 0.75rem;
}

.footer-bottom p {
    color: #A5ADBA;
    margin: 0;
}

.footer-legal {
    display: flex;
    gap: 25px;
    flex-wrap: wrap;
}

.footer-legal a {
    color: #A5ADBA;
    text-decoration: none;
    transition: color 0.2s ease;
}

.footer-legal a:hover {
    color: #0052CC;
}

/* Responsivo */
@media (max-width: 1024px) {
    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
    }
    
    .footer-brand {
        grid-column: span 2;
        max-width: 100%;
        text-align: center;
    }
    
    .footer-logo {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .footer {
        padding: 40px 0 20px;
    }
    
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 30px;
        text-align: center;
    }
    
    .footer-brand {
        grid-column: span 1;
        text-align: center;
    }
    
    .footer-logo {
        justify-content: center;
    }
    
    .footer-contact li {
        justify-content: center;
    }
    
    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }
    
    .footer-legal {
        justify-content: center;
        gap: 15px;
    }
}
</style>

<script>
/**
 * Abrir configurações de cookies a partir do rodapé
 */
document.addEventListener('DOMContentLoaded', function() {
    const cookieLink = document.getElementById('openCookieSettingsFooter');
    if (cookieLink) {
        cookieLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.CardoxisCookies && typeof window.CardoxisCookies.openSettings === 'function') {
                window.CardoxisCookies.openSettings();
            }
        });
    }
});
</script>