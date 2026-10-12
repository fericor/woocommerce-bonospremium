// woo-search-script.js - VERSIÓN CORREGIDA (sin conflicto con $)
(function($) {
    'use strict';
    
    let searchTimeout;
    
    // Función para buscar productos
    function searchProducts(input, resultsContainer) {
        var searchTerm = $(input).val().trim();
        
        if (searchTerm.length < 2) {
            $(resultsContainer).html('').hide();
            return;
        }
        
        $(resultsContainer).html('<div class="woo-search-loading">Buscando...</div>').show();
        
        $.ajax({
            url: woo_search_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'woo_ajax_search',
                search: searchTerm,
                nonce: $(input).data('nonce')
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var html = '';
                    
                    response.data.forEach(function(product) {
                        var imageHtml = product.image ? 
                            '<img src="' + product.image + '" alt="' + product.title + '" class="woo-result-image">' : 
                            '<div class="woo-result-image no-image"></div>';
                        
                        html += '<a href="' + product.url + '" class="woo-search-result-item">' +
                                imageHtml +
                                '<div class="woo-result-info">' +
                                '<div class="woo-result-title">' + product.title + '</div>' +
                                '<div class="woo-result-price">' + product.price + '</div>' +
                                '</div>' +
                                '</a>';
                    });
                    
                    $(resultsContainer).html(html).show();
                } else {
                    $(resultsContainer).html('<div class="woo-search-no-results">No se encontraron productos</div>').show();
                }
            },
            error: function() {
                $(resultsContainer).html('<div class="woo-search-error">Error en la búsqueda</div>').show();
            }
        });
    }
    
    // ===== VERSIÓN ESCRITORIO =====
    $(document).on('input', '#woo-search-input-desk', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            searchProducts($('#woo-search-input-desk')[0], '#woo-results-desk');
        }, 300);
    });
    
    // Hacer clic en el icono para enfocar el input (CORREGIDO)
    $(document).on('click', '#woo-search-trigger', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#woo-search-input-desk').focus();
    });
    
    // Mostrar resultados al enfocar
    $(document).on('focus', '#woo-search-input-desk', function() {
        if ($(this).val().trim().length >= 2) {
            $('#woo-results-desk').show();
        }
    });
    
    // Ocultar resultados al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.woo-search-wrapper').length) {
            $('#woo-results-desk').hide();
        }
    });
    
    // ===== VERSIÓN MÓVIL =====
    $(document).on('click', '#woo-mobile-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#woo-mobile-overlay').fadeIn(200);
        $('#woo-mobile-panel').addClass('active');
        setTimeout(function() {
            $('#woo-search-input-mob').focus();
        }, 300);
    });
    
    $(document).on('click', '#woo-close-search, #woo-mobile-overlay', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#woo-mobile-panel').removeClass('active');
        $('#woo-mobile-overlay').fadeOut(200);
        $('#woo-results-mob').hide();
    });
    
    $(document).on('input', '#woo-search-input-mob', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            searchProducts($('#woo-search-input-mob')[0], '#woo-results-mob');
        }, 300);
    });
    
    // No cerrar al hacer clic en el panel
    $(document).on('click', '#woo-mobile-panel', function(e) {
        e.stopPropagation();
    });
    
    // Evento de búsqueda con Enter
    $(document).on('keypress', '.woo-search-input', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            var searchTerm = $(this).val().trim();
            if (searchTerm.length >= 2) {
                window.location.href = '?s=' + encodeURIComponent(searchTerm) + '&post_type=product';
            }
        }
    });
    
    // Inicializar al cargar
    $(document).ready(function() {
        // Asegurar que jQuery esté disponible
        if (typeof jQuery === 'undefined') {
            console.error('jQuery no está cargado');
        }
        
        // Localizar script si no existe
        if (typeof woo_search_ajax === 'undefined') {
            window.woo_search_ajax = {
                ajax_url: '/wp-admin/admin-ajax.php'
            };
        }
    });
    
})(jQuery); // ¡IMPORTANTE! Pasar jQuery como parámetro