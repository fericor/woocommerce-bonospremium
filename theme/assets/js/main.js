jQuery(document).ready(function($) {
    // ===== HEADER DROPDOWN (menú hamburguesa) =====
    var $menuToggle = $('.bp-menu-toggle');
    var $userMenu = $('.bp-user-nav-menu');
    var $backBtn = $('.bp-back-btn');

    // Determinar si estamos en inicio o en categorías/archivo de productos
    // (clases del body: home, page-id..., post-type-archive-product, tax-product_cat, archive)
    function bpIsNavPage() {
        var $body = document.body;
        // Inicio (home) o archivo de productos o categoría de producto
        return $body.classList.contains('home')
            || $body.classList.contains('post-type-archive-product')
            || $body.classList.contains('tax-product_cat')
            || $body.classList.contains('woocommerce-page') && $body.classList.contains('archive')
            || $body.classList.contains('blog');
    }

    function bpUpdateHeaderControls() {
        if (bpIsNavPage()) {
            // En inicio/categorías: hamburguesa (mostrar) - flecha oculta
            $('body').removeClass('bp-is-back-page');
            $('body').addClass('bp-is-nav-page');
        } else {
            // En otras páginas: flecha atrás - hamburguesa oculta
            $('body').removeClass('bp-is-nav-page');
            $('body').addClass('bp-is-back-page');
        }
    }
    bpUpdateHeaderControls();

    // Flecha atrás: vuelve a la página anterior (o a la tienda si no hay historial)
    $backBtn.on('click', function(e) {
        e.preventDefault();
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = '/';
        }
    });

    $menuToggle.on('click', function(e) {
        e.stopPropagation();
        $userMenu.toggleClass('open');
        $(this).toggleClass('active');
        if ($(this).hasClass('active')) {
            $(this).find('span').eq(0).css('transform', 'rotate(45deg) translate(4px, 4px)');
            $(this).find('span').eq(1).css('opacity', '0');
            $(this).find('span').eq(2).css('transform', 'rotate(-45deg) translate(4px, -4px)');
        } else {
            $(this).find('span').each(function() { $(this).css('transform', '').css('opacity', ''); });
        }
    });

    // Search overlay toggle
    $('.bp-search-toggle').on('click', function(e) {
        e.preventDefault();
        $('.bp-search-overlay').addClass('open');
        setTimeout(function() {
            $('.bp-search-overlay input[type="search"]').focus();
        }, 100);
    });
    
    $('.bp-search-close').on('click', function() {
        $('.bp-search-overlay').removeClass('open');
    });

    // Close on escape
    $(document).on('keyup', function(e) {
        if (e.key === 'Escape') {
            $('.bp-search-overlay').removeClass('open');
            $('.bp-user-nav-menu').removeClass('open');
            $('.bp-menu-toggle').removeClass('active');
            $('.bp-menu-toggle span').each(function() { $(this).css('transform', '').css('opacity', ''); });
        }
    });

    // Close menu when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.bp-header-left').length) {
            $('.bp-user-nav-menu').removeClass('open');
            $('.bp-menu-toggle').removeClass('active');
            $('.bp-menu-toggle span').each(function() { $(this).css('transform', '').css('opacity', ''); });
        }
    });

    // ===== WISHLIST (Favoritos) con localStorage + servidor =====
    var wishlistKey = 'bp_wishlist';
    
    // Cargar wishlist: localStorage (todos) + servidor (logueados)
    function bpLoadWishlist() {
        var ids = [];
        // Guests: desde localStorage
        try {
            var local = JSON.parse(localStorage.getItem(wishlistKey));
            if (Array.isArray(local)) ids = local;
        } catch(e) {}
        // Logueados: desde el server (sobrescribe)
        if (bp_lz_ajax.wishlist && bp_lz_ajax.wishlist.length) {
            ids = bp_lz_ajax.wishlist;
        }
        return ids;
    }
    
    // Guardar wishlist
    function bpSaveWishlist(ids) {
        localStorage.setItem(wishlistKey, JSON.stringify(ids));
    }
    
    // Marcar corazones en la página
    function bpMarkHearts(ids) {
        $('.bp-wishlist-btn').each(function() {
            var $icon = $(this).find('i');
            var pid = parseInt($(this).data('product-id'));
            if (ids.indexOf(pid) !== -1) {
                $icon.removeClass('far').addClass('fas').css('color', '#e74c3c');
            } else {
                $icon.removeClass('fas').addClass('far').css('color', '');
            }
        });
    }
    
    // Inicializar corazones al cargar
    bpMarkHearts(bpLoadWishlist());
    
    // Click en corazón
    $('.bp-wishlist-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $icon = $btn.find('i');
        var productId = parseInt($btn.data('product-id'));
        var ids = bpLoadWishlist();
        var idx = ids.indexOf(productId);
        
        if (idx !== -1) {
            ids.splice(idx, 1);
        } else {
            ids.push(productId);
        }
        
        bpSaveWishlist(ids);
        bpMarkHearts(ids);
        
        // Sincronizar con servidor si está logueado
        if (bp_lz_ajax.user_id > 0) {
            $.post(bp_lz_ajax.ajax_url, {
                action: 'bp_toggle_wishlist',
                product_id: productId,
            });
        }
    });

    // Infinite scroll - Auto load on scroll
    var loadingMore = false;
    var $loadWrap = $('.bp-load-more-wrap');

    // Product image slider dots
    $('.bp-slider').each(function() {
        var $slider = $(this);
        var $track = $slider.find('.bp-slider-track');
        var $slides = $track.find('.bp-slide');
        var $dots = $slider.find('.bp-slider-dots');

        if ($slides.length > 1) {
            // Create dots
            for (var i = 0; i < $slides.length; i++) {
                $dots.append('<span data-index="' + i + '"></span>');
            }
            $dots.find('span:first').addClass('active');

            // Update active dot on scroll
            $track.on('scroll', function() {
                var index = Math.round($track.scrollLeft() / $track.outerWidth());
                $dots.find('span').removeClass('active').eq(index).addClass('active');
            });

            // Click dot to scroll
            $dots.on('click', 'span', function() {
                var idx = $(this).data('index');
                $track.animate({ scrollLeft: idx * $track.outerWidth() }, 300);
            });

            // Mouse drag to scroll
            var isDown = false, startX = 0, scrollStart = 0;
            $slider.on('mousedown', function(e) {
                isDown = true;
                $slider.addClass('bp-dragging');
                startX = e.pageX - $slider.offset().left;
                scrollStart = $track.scrollLeft();
                e.preventDefault();
            });
            $slider.on('mousemove', function(e) {
                if (!isDown) return;
                e.preventDefault();
                var x = e.pageX - $slider.offset().left;
                var walk = (x - startX) * 1.5;
                $track.scrollLeft(scrollStart - walk);
            });
            $(document).on('mouseup', function() {
                isDown = false;
                $slider.removeClass('bp-dragging');
            });
            $slider.on('mouseleave', function() {
                isDown = false;
                $slider.removeClass('bp-dragging');
            });
        }
    });

    // Sticky nav on scroll
    var $nav = $('.bp-nav');
    $(window).on('scroll', function() {
        var scrollTop = $(window).scrollTop();
        var headerHeight = $('.bp-header').outerHeight() || 0;
        
        // Activar cuando se ha scrolleado pasado el header
        if (scrollTop > headerHeight) {
            $nav.addClass('bp-nav-sticky');
        } else {
            $nav.removeClass('bp-nav-sticky');
        }

        // Infinite scroll
        if ($loadWrap.length && !loadingMore) {
            var wrapTop = $loadWrap.offset().top;
            var scrollBottom = scrollTop + $(window).height();
            if (scrollBottom >= wrapTop - 200) {
                loadingMore = true;
                loadMoreProducts();
            }
        }
    });

    function loadMoreProducts() {
        var $wrap = $('.bp-load-more-wrap');
        var page = parseInt($wrap.data('page')) + 1;
        var max = parseInt($wrap.data('max'));
        var category = $wrap.data('category') || '';

        $('.bp-load-more-spinner').show();

        $.post(bp_lz_ajax.ajax_url, {
            action: 'bp_load_more',
            page: page,
            category: category
        }, function(data) {
            $('.bp-load-more-spinner').hide();
            if (data) {
            $('.bp-products-grid').append(data);
            $wrap.data('page', page);
                if (page >= max) {
                    $wrap.remove();
                } else {
                    loadingMore = false;
                    // Re-trigger in case still visible
                    $(window).trigger('scroll');
                }
            } else {
                $wrap.remove();
            }
        }).fail(function() {
            $('.bp-load-more-spinner').hide();
            loadingMore = false;
        });
    }

    // ===== CUPÓN COLAPSIBLE (checkout) =====
    $(document.body).on('click', '.bp-coupon-toggle', function() {
        $(this).closest('.bp-checkout-coupon-wrap').find('.bp-coupon-body').slideToggle(250);
        $(this).closest('.bp-checkout-coupon-wrap').toggleClass('is-open');
    });
    $(document.body).on('click', '.bp-coupon-apply', function() {
        var code = $('#coupon_code').val();
        if (code) {
            var url = window.location.origin + '/?wc-ajax=apply_coupon';
            var data = new URLSearchParams();
            data.append('coupon_code', code);
            data.append('security', (bp_lz_ajax && bp_lz_ajax.coupon_nonce) || '');
            fetch(url, { method: 'POST', body: data }).then(function() {
                $(document.body).trigger('update_checkout');
            });
        }
    });

    // ===== CONDICIONES TOGGLE (single product) =====
    $('.bp-condiciones-toggle').on('click', function() {
        var panel = $(this).closest('.bp-condiciones-panel');
        panel.find('.bp-condiciones-body').slideToggle(250);
        panel.toggleClass('is-open');
    });
});
