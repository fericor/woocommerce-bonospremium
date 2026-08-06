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
window.addEventListener('load', function() {
    var loader = document.getElementById('bp-loader');
    if (loader) {
        loader.classList.add('bp-loader-hidden');
        setTimeout(function() {
            loader.style.display = 'none';
        }, 300);
    }
});
</script>
