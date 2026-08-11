<?php
/**
 * BonosPremium - Banner de consentimiento de cookies (RGPD / Ley europea)
 * Se muestra hasta que el usuario acepta (all) o rechaza las no esenciales (essential).
 * La elección se guarda en la cookie bp_cookie_consent (180 días).
 */
?>
<div id="bp-cookie-banner" class="bp-cookie-banner" role="dialog" aria-label="Aviso de cookies" aria-live="polite" style="display:none;">
    <div class="bp-cookie-banner-inner">
        <div class="bp-cookie-text">
            <p>
                <strong>Tu privacidad importa</strong><br>
                Usamos cookies propias (necesarias para el funcionamiento de la tienda) y de terceros
                (Google y Facebook) para medir audiencia y mostrar publicidad personalizada.
                Puedes aceptarlas todas, solo las esenciales o consultar nuestra política de cookies.
            </p>
            <a href="<?php echo esc_url(home_url('/politica-de-cookies')); ?>">Política de cookies</a>
        </div>
        <div class="bp-cookie-actions">
            <button type="button" class="bp-cookie-btn bp-cookie-accept" onclick="bpCookieAccept()">Aceptar todas</button>
            <button type="button" class="bp-cookie-btn bp-cookie-essential" onclick="bpCookieEssential()">Solo esenciales</button>
        </div>
    </div>
</div>

<style>
.bp-cookie-banner {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 99999;
    background: rgba(0,0,0,.7);
    box-shadow: 0 -4px 24px rgba(0,0,0,.25);
    border-top: 1px solid rgba(255,255,255,.15);
    padding: 16px 20px 18px;
    transform: translateY(105%);
    transition: transform .35s ease;
}
.bp-cookie-banner.bp-cookie-visible { transform: translateY(0); }
.bp-cookie-banner-inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}
.bp-cookie-text {
    flex: 1 1 340px;
    font-size: 13px;
    color: rgba(255,255,255,.9);
    line-height: 1.55;
}
.bp-cookie-text p { margin: 0 0 6px; }
.bp-cookie-text strong { color: #ffffff; }
.bp-cookie-text a {
    /* FIX Félix 11/08: color primario light de la plantilla */
    color: var(--bp-primary-light, #33b0e3);
    font-weight: 700;
    text-decoration: none;
    font-size: 13px;
}
.bp-cookie-text a:hover { text-decoration: underline; }
.bp-cookie-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.bp-cookie-btn {
    border: none;
    border-radius: 8px;
    /* FIX Félix 11/08: botones menos altos (padding vertical reducido) */
    padding: 5px 18px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: all .2s;
    font-family: inherit;
    line-height: 1.4;
}
/* FIX Félix 11/08: colores con las variables primarias de la plantilla */
.bp-cookie-accept { background: var(--bp-primary); color: #fff; box-shadow: 0 3px 10px rgba(var(--bp-primary-rgb), .35); }
.bp-cookie-accept:hover { background: var(--bp-primary-dark); }
.bp-cookie-essential { background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,.6); }
.bp-cookie-essential:hover { border-color: #ffffff; background: rgba(255,255,255,.1); }
@media (max-width: 640px) {
    .bp-cookie-banner { padding: 14px 16px 16px; }
    .bp-cookie-btn { flex: 1; text-align: center; }
}
</style>

<script>
(function() {
    function bpGetCookie(name) {
        var m = document.cookie.match('(^|; )' + name + '=([^;]*)');
        return m ? decodeURIComponent(m[2]) : '';
    }
    var banner = document.getElementById('bp-cookie-banner');
    if (!banner) return;
    var consent = bpGetCookie('bp_cookie_consent');
    if (consent === 'all' || consent === 'essential') {
        banner.parentNode.removeChild(banner);
        return;
    }
    // Mostrar con animación
    banner.style.display = 'block';
    setTimeout(function() { banner.classList.add('bp-cookie-visible'); }, 80);
})();

// Aceptar todas las cookies: guarda elección, actualiza Consent Mode y recarga
// (al recargar se cargan los scripts de marketing/analítica ahora permitidos).
function bpCookieAccept() {
    document.cookie = 'bp_cookie_consent=all; max-age=15552000; path=/; SameSite=Lax';
    var d = window.dataLayer || (window.dataLayer = []);
    (function() {
        function g() { d.push(arguments); }
        g('consent', 'update', {
            'ad_storage': 'granted',
            'ad_user_data': 'granted',
            'ad_personalization': 'granted',
            'analytics_storage': 'granted',
            'functionality_storage': 'granted',
            'personalization_storage': 'granted'
        });
    })();
    location.reload();
}

// Solo esenciales: guarda elección y oculta el banner (scripts de terceros bloqueados)
function bpCookieEssential() {
    document.cookie = 'bp_cookie_consent=essential; max-age=15552000; path=/; SameSite=Lax';
    var banner = document.getElementById('bp-cookie-banner');
    if (banner) banner.parentNode.removeChild(banner);
}
</script>
