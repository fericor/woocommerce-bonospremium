<?php
/**
 * BonosPremium Theme - Loader
 * Muestra un loading spinner mientras la página carga
 */
?>
<div id="bp-loader">
    <div class="bp-loader-spinner"></div>
</div>

<style>
#bp-loader {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 999999;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity .3s ease;
}
#bp-loader.bp-loader-hidden {
    opacity: 0;
    pointer-events: none;
}
.bp-loader-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e0e0e0;
    border-top-color: #039CDC;
    border-radius: 50%;
    animation: bp-loader-spin .8s linear infinite;
}
@keyframes bp-loader-spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
// Ocultar el loader lo antes posible (DOMContentLoaded + fallback 2.5s):
// si un script externo (pixel, fuentes) tarda, la página no queda tapada.
(function() {
    function bpHideLoader() {
        var loader = document.getElementById('bp-loader');
        if (loader && !loader.classList.contains('bp-loader-hidden')) {
            loader.classList.add('bp-loader-hidden');
            setTimeout(function() { loader.style.display = 'none'; }, 300);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bpHideLoader);
    } else {
        bpHideLoader();
    }
    // Fallback: si window.load tarda (scripts de terceros), ocultar a los 2.5s
    setTimeout(bpHideLoader, 2500);
})();
</script>
