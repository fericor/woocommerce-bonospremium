<?php
/**
 * BonosPremium Lanzarote - Theme Functions
 */

// Definir versión del tema
define('BP_LZ_VERSION', '1.2.2');

// Preconnect a los CDN de terceros (reduce latencia de DNS/TLS — Félix 10/08)
add_action('wp_head', function() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>' . "\n";
}, 1);

// ===== CONSENTIMIENTO DE COOKIES (RGPD / Ley europea) — Félix 10/08 =====
// Google Consent Mode v2: declara los estados de consentimiento ANTES de que
// cargue cualquier script de Google (Analytics/Ads). Por defecto todo 'denied'
// salvo lo estrictamente necesario; si el usuario ya aceptó, se actualiza a 'granted'.
// Además, el pixel de Facebook se bloquea hasta aceptar: si window.fbq ya existe,
// el snippet del plugin (if(f.fbq)return) no carga el script real de Meta.
add_action('wp_head', function() {
    $consent = isset($_COOKIE['bp_cookie_consent']) ? $_COOKIE['bp_cookie_consent'] : '';
    ?>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('consent', 'default', {
        'ad_storage': 'denied',
        'ad_user_data': 'denied',
        'ad_personalization': 'denied',
        'analytics_storage': 'denied',
        'functionality_storage': 'denied',
        'personalization_storage': 'denied',
        'security_storage': 'granted',
        'wait_for_update': 500
    });
    gtag('set', 'url_passthrough', true);
    <?php if ($consent === 'all') : ?>
    gtag('consent', 'update', {
        'ad_storage': 'granted',
        'ad_user_data': 'granted',
        'ad_personalization': 'granted',
        'analytics_storage': 'granted',
        'functionality_storage': 'granted',
        'personalization_storage': 'granted'
    });
    <?php endif; ?>
    </script>
    <?php if ($consent !== 'all') : ?>
    <script>
    window.fbq = window.fbq || function(){ window.fbq.queue = window.fbq.queue || []; window.fbq.queue.push(arguments); };
    window._fbq = window._fbq || window.fbq;
    </script>
    <?php endif;
}, 1);

// Soporte para WooCommerce
add_action('after_setup_theme', function() {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
    add_theme_support('title-tag');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    
    register_nav_menus([
        'primary' => __('Menú Principal', 'bonospremium'),
        'user-menu' => __('Menú de Usuario', 'bonospremium'),
        'footer-about' => __('Footer - Sobre Nosotros', 'bonospremium'),
        'footer-account' => __('Footer - Mi Cuenta', 'bonospremium'),
        'footer-offers' => __('Footer - Ofertas', 'bonospremium'),
    ]);
});

// Mejorar calidad de imágenes de productos
add_filter('woocommerce_get_image_size_shop_catalog', function($size) {
    return ['width' => 600, 'height' => 600, 'crop' => 1];
});
add_filter('woocommerce_get_image_size_shop_single', function($size) {
    return ['width' => 800, 'height' => 800, 'crop' => 0];
});
add_filter('woocommerce_get_image_size_shop_thumbnail', function($size) {
    return ['width' => 300, 'height' => 300, 'crop' => 1];
});
// JPEG quality al máximo
add_filter('jpeg_quality', function($quality) { return 90; });

// Cargar estilos y scripts
add_action('wp_enqueue_scripts', function() {
    // Google Fonts: Inter
    wp_enqueue_style('bp-lz-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap', [], null);
    
    // Font Awesome
    wp_enqueue_style('bp-lz-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css', [], '6.5.0');
    
    // Estilos del tema
    wp_enqueue_style('bp-lz-style', get_stylesheet_uri(), [], BP_LZ_VERSION);
    wp_enqueue_style('bp-lz-main', get_template_directory_uri() . '/assets/css/main.css', ['bp-lz-style'], BP_LZ_VERSION);
    
    // JavaScript
    wp_enqueue_script('bp-lz-main', get_template_directory_uri() . '/assets/js/main.js', ['jquery'], BP_LZ_VERSION, true);
    
    // Localize script para AJAX
    wp_localize_script('bp-lz-main', 'bp_lz_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bp_lz_nonce'),
        'user_id' => get_current_user_id(),
        'wishlist' => bp_get_wishlist(),
        'wc_ajax_url' => WC()->ajax_url(),
        'coupon_nonce' => wp_create_nonce('apply-coupon'),
    ]);
});

// Clases del body
add_filter('body_class', function($classes) {
    if (is_shop() || is_product_category() || is_product_tag()) {
        $classes[] = 'bp-shop-page';
    }
    if (is_product()) {
        $classes[] = 'bp-product-page';
    }
    if (is_cart() || is_checkout()) {
        $classes[] = 'bp-checkout-page';
    }
    if (is_account_page()) {
        $classes[] = 'bp-account-page';
    }
    return $classes;
});

// Redirigir al checkout después de añadir al carrito
add_filter('woocommerce_add_to_cart_redirect', function() {
    return wc_get_checkout_url();
});

// Modificar el loop de WooCommerce - 4 columnas
add_filter('loop_shop_columns', function() { return 4; });
add_filter('loop_shop_per_page', function() { return 10; });

// Nota: el toggle del login y auto-dismiss de notices están ahora en assets/js/main.js

// Mi cuenta - wrapper estilo app
add_action('template_redirect', function() {
    if (!is_account_page()) return;
    ob_start(function($html) {
        if (!is_user_logged_in()) {
            $html = str_replace('class="woocommerce"', 'class="woocommerce bp-account-app"', $html);
        }
        return $html;
    });
});

add_filter('woocommerce_output_related_products_args', function($args) {
    $args['posts_per_page'] = 4;
    $args['columns'] = 4;
    return $args;
});

// Quitar sidebar de WooCommerce en shop
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

// Quitar badge de "¡Oferta!"
add_filter('woocommerce_sale_flash', '__return_false');

// Deshabilitar caché durante desarrollo
// Cache-Control selectivo (Félix 10/08, optimización de velocidad):
// - Carrito/checkout/mi cuenta: no-store (sesión activa, no cachear)
// - Resto (home, tienda, productos): no-cache + revalidación (el navegador
//   reutiliza con ETag/Last-Modified en vez de re-descargar todo)
// ⚠️ En template_redirect (no send_headers): las conditionals de WooCommerce
// (is_cart/is_checkout/is_account_page) solo resuelven tras query_posts().
add_action('template_redirect', function() {
    if (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page())) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    } else {
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
    }
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
});

// Ocultar wishlist duplicado del plugin YA NO se oculta: el plugin es el sistema principal
// de favoritos. Se eliminó la ocultación para que el corazón del plugin sea el visible.

// Definir variables JS globales que usa el plugin woocommerce-bonospremium
// en el modal de vista previa del BonoPremium (BP_IMG_BASE, BP_PRIMARY_COLOR)
add_action('wp_head', function() {
    $img_base = (!defined('BP_IMG_BASE') ? home_url() . '/wp-content/uploads/bonospremium' : BP_IMG_BASE);
    $color    = (!defined('BP_PRIMARY_COLOR') ? '#039CDC' : BP_PRIMARY_COLOR);
    ?>
    <script>
    window.BP_IMG_BASE = '<?php echo esc_js($img_base); ?>';
    window.BP_PRIMARY_COLOR = '<?php echo esc_js($color); ?>';
    var BP_IMG_BASE = window.BP_IMG_BASE;
    var BP_PRIMARY_COLOR = window.BP_PRIMARY_COLOR;
    </script>
    <?php
}, 1);

// Mensaje disuasorio + textos en español en la página de Eliminar cuenta
add_action('wp_footer', function() {
    if (!is_user_logged_in()) return;
    global $wp;
    $current_url = trailingslashit(home_url($wp->request));
    if (strpos($current_url, 'wpf-delete-account') === false) return;
    ?>
    <style>
    .bp-delete-account-notice {
        background: #fff;
        border: 1px solid #ffd1d1;
        border-left: 6px solid #e74c3c;
        padding: 24px;
        margin-bottom: 16px;
        max-width: 480px;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .bp-delete-account-notice h3 {
        margin: 0 0 10px;
        font-size: 1.15rem;
        font-weight: 700;
        color: #c0392b;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .bp-delete-account-notice h3 i { color: #e74c3c; }
    .bp-delete-account-notice p {
        margin: 0 0 10px;
        font-size: .9rem;
        color: #555;
        line-height: 1.6;
        text-align: left;
    }
    .bp-delete-account-notice ul {
        margin: 8px 0 0;
        padding: 0;
        list-style: none;
    }
    .bp-delete-account-notice li {
        padding: 6px 0;
        font-size: .88rem;
        color: #666;
        border-bottom: 1px solid #f5f5f5;
        text-align: left;
    }
    .bp-delete-account-notice li:last-child { border: none; }
    .bp-delete-account-notice li i {
        color: #e74c3c;
        margin-right: 8px;
        width: 16px;
    }
    </style>
    <script>
    jQuery(function($) {
        var $box = $('.wpfda-delete-account-container');
        if (!$box.length) return;
        $box.prepend(
            '<div class="bp-delete-account-notice">' +
              '<h3><i class="fas fa-exclamation-triangle"></i> ¿Seguro que quieres eliminar tu cuenta?</h3>' +
              '<p>Esta acción es <strong>permanente e irreversible</strong>. Al eliminar tu cuenta perderás:</p>' +
              '<ul>' +
                '<li><i class="fas fa-times-circle"></i> Todos tus bonos y compras realizadas</li>' +
                '<li><i class="fas fa-times-circle"></i> El acceso a tus favoritos y pedidos</li>' +
                '<li><i class="fas fa-times-circle"></i> Cualquier saldo o crédito disponible</li>' +
              '</ul>' +
              '<p style="margin-top:12px;margin-bottom:0;"><strong>Si tienes bonos activos o sin canjear, te recomendamos usarlos antes de eliminar tu cuenta.</strong></p>' +
            '</div>'
        );
        // Traducir el aviso del administrador del plugin (está en inglés, React lo inserta después)
        var traduceAdmin = function() {
            $('p, div, span').filter(function() {
                return $(this).text().indexOf('Just a heads up') !== -1 && $(this).children().length === 0;
            }).first().html('<strong>Atención:</strong> eres el administrador del sitio. Si continúas, tu propia cuenta será eliminada.');
        };
        // Intentar ahora y luego observar cambios del DOM (React)
        traduceAdmin();
        if ('MutationObserver' in window) {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function() {
                    if (document.body.innerHTML.indexOf('Just a heads up') !== -1) {
                        traduceAdmin();
                        observer.disconnect();
                    }
                });
            });
            observer.observe(document.body, { childList: true, subtree: true, characterData: true });
        }
        // Asegurar que el wrapper del botón no añada estilos extra
        $('.wpfda-submit').css('width', '100%');
    });
    </script>
    <?php
});

// ===== QUANTITY EN PRODUCTO ÚNICO =====
// En single product: cantidad fija a 1, ocultar selector
add_filter('woocommerce_quantity_input_args', function($args, $product) {
    if (is_product() && $product->is_type('simple') && !$product->is_type('variable')) {
        $args['min_value'] = 1;
        $args['max_value'] = 1;
        $args['input_value'] = 1;
    }
    return $args;
}, 10, 2);

add_action('wp_head', function() {
    if (is_product()) {
        echo '<style>.bp-product-page .quantity { display: none !important; }</style>';
    }
});

// ===== INFINITE SCROLL =====
remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
add_action('woocommerce_after_shop_loop', function() {
    global $wp_query;
    if ($wp_query->max_num_pages > 1) {
        echo '<div class="bp-load-more-wrap" data-page="1" data-max="' . $wp_query->max_num_pages . '">';
        echo '<div class="bp-load-more-spinner" style="display:none;"><span class="bp-spinner"></span> Cargando...</div>';
        echo '</div>';
    }
});

// Remove shop page title, description, result count, ordering
add_filter('woocommerce_show_page_title', '__return_false');
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);

// AJAX handler for load more
add_action('wp_ajax_bp_load_more', 'bp_load_more_products');
add_action('wp_ajax_nopriv_bp_load_more', 'bp_load_more_products');
function bp_load_more_products() {
    $page = (int)($_POST['page'] ?? 1);
    $args = [
        'post_type' => 'product',
        'posts_per_page' => 10,
        'paged' => $page,
        'post_status' => 'publish',
    ];
    if (!empty($_POST['category'])) {
        $args['tax_query'] = [[
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => sanitize_text_field($_POST['category']),
        ]];
    }
    $loop = new WP_Query($args);
    ob_start();
    if ($loop->have_posts()) {
        while ($loop->have_posts()) { $loop->the_post();
            // Ejecutar el hook personalizado que genera el card
            do_action('woocommerce_before_shop_loop_item');
        }
    }
    wp_reset_postdata();
    echo ob_get_clean();
    wp_die();
}

// ===== PRODUCT LOOP PERSONALIZADO =====
// Reemplazar el UL/LI de WooCommerce por nuestro propio marcado
add_filter('woocommerce_product_loop_start', function($html) {
    return '<div class="bp-products-grid">';
});

add_filter('woocommerce_product_loop_end', function($html) {
    return '</div>';
});

// Quitar hooks default de WooCommerce
remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);
remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);

// Nuestro template de producto
remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
add_action('woocommerce_before_shop_loop_item', function() {
    global $product;
    $city = get_field('localidad') ?: get_post_meta(get_the_ID(), 'localidad', true);
    $nombre_establecimiento = get_field('nombre_establecimiento') ?: get_post_meta(get_the_ID(), 'nombre_establecimiento', true);
    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price() ?: $regular_price;
    
    echo '<div class="bp-product-card">';
    echo '<div class="bp-product-image-wrap">';
    echo '<a href="' . get_permalink() . '">';
    echo $product->get_image('medium_large');
    echo '</a>';
    echo '</div>';
    echo '<div class="bp-product-info">';
    echo '<h3 class="bp-product-title"><a href="' . get_permalink() . '">' . esc_html($nombre_establecimiento) . '</a></h3>';
    echo '<h4 class="bp-product-name">' . esc_html(get_the_title()) . '</h4>';
    echo '<div class="bp-product-bottom">';
    echo '<div class="bp-product-price">';
    if ($regular_price && $regular_price != $sale_price) {
        echo '<span class="bp-price-original">' . wc_price($regular_price) . '</span>';
    }
    echo '<span class="bp-price-sale">' . wc_price($sale_price) . '</span>';
    echo '</div>';
    if (!empty($city)) {
        echo '<span class="bp-product-city"><i class="fas fa-map-marker-alt"></i> ' . esc_html($city) . '</span>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
});

// Quitar el contenedor default de WooCommerce
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

add_action('woocommerce_before_main_content', function() {
    echo '<main class="bp-main-content"><div class="bp-container">';
});

add_action('woocommerce_after_main_content', function() {
    echo '</div></main>';
});

// ===== WISHLIST (Favoritos) con persistencia =====
// Obtener wishlist del usuario logueado (desde user_meta)
function bp_get_wishlist() {
    $user_id = get_current_user_id();
    if ($user_id) {
        $wishlist = get_user_meta($user_id, 'bp_wishlist', true);
        return is_array($wishlist) ? $wishlist : [];
    }
    return [];
}

// Cupón colapsible entre total y métodos de pago
// Se llama directo desde form-checkout.php (no via hook)
function bp_checkout_coupon_form() {
    if (wc_coupons_enabled()) {
        ?>
        <div class="bp-checkout-coupon-wrap">
            <button type="button" class="bp-coupon-toggle">
                <i class="fas fa-ticket-alt"></i> ¿Tienes un cupón de descuento?
                <i class="fas fa-chevron-down bp-coupon-arrow"></i>
            </button>
            <div class="bp-coupon-body" style="display:none;">
                <div class="bp-coupon-form">
                    <input type="text" name="coupon_code" class="bp-coupon-input" placeholder="Código del cupón" id="coupon_code" value="" />
                    <button type="button" class="bp-coupon-apply" name="apply_coupon" value="Aplicar">Aplicar</button>
                </div>
            </div>
        </div>
        <?php
    }
}

// El sistema de favoritos usa el plugin smart-wishlist (página wishlist).
// El endpoint "favoritos" del Mi Cuenta se elimina; el corazón del header
// apunta a la página de wishlist del plugin (/favoritos/).
// Refresh rewrite rules on theme switch
add_action('after_switch_theme', function() { flush_rewrite_rules(); });
add_action('wp_ajax_bp_toggle_wishlist', 'bp_toggle_wishlist');
function bp_toggle_wishlist() {
    $product_id = (int)($_POST['product_id'] ?? 0);
    if (!$product_id) wp_die('0');
    
    $wishlist = bp_get_wishlist();
    $index = array_search($product_id, $wishlist);
    
    if ($index !== false) {
        unset($wishlist[$index]);
    } else {
        $wishlist[] = $product_id;
    }
    
    update_user_meta(get_current_user_id(), 'bp_wishlist', array_values($wishlist));
    wp_send_json(['wishlist' => array_values($wishlist)]);
}

// Forzar el template My Account de WooCommerce para la página de mi cuenta
// La página oficial (sin shortcode) usa el template app personalizado
add_filter('template_include', function($template) {
    if (is_account_page()) {
        $tpl = locate_template('woocommerce/myaccount/my-account.php');
        if ($tpl) return $tpl;
    }
    return $template;
});

// ============================================================
// FORMULARIOS: Contacto / Promociona tu negocio / Recibir ofertas
// ============================================================

// CONFIGURACIÓN SMTP BREVO
// ⚠️ LAS CREDENCIALES SE DEFINEN EN wp-config.php
// Añade esto a tu wp-config.php:
//
//   define('BP_BREVO_USER', 'tu_usuario_brevo@smtp-brevo.com');
//   define('BP_BREVO_PASS', 'tu_smtp_key_brevo');
//   define('BP_BREVO_FROM', 'info@bonospremium.com');
//
// El host/puerto por defecto apuntan a Brevo y pueden sobreescribirse igualmente.

if (!defined('BP_BREVO_HOST')) define('BP_BREVO_HOST', 'smtp-relay.brevo.com');
if (!defined('BP_BREVO_PORT')) define('BP_BREVO_PORT', 587);
if (!defined('BP_BREVO_USER')) define('BP_BREVO_USER', '');
if (!defined('BP_BREVO_PASS')) define('BP_BREVO_PASS', '');
if (!defined('BP_BREVO_FROM')) define('BP_BREVO_FROM', 'info@bonospremium.com');

// Configuración de cada formulario: email destino CONFIGURABLE por tienda.
// Cada tienda define en su wp-config.php:  define('BP_FORM_CONTACTO_TO', '...'); etc.
// Si no se define, usa info@bonospremium.com (fallback genérico).
if (!defined('BP_FORM_CONTACTO_TO'))  define('BP_FORM_CONTACTO_TO', 'info@bonospremium.com');
if (!defined('BP_FORM_PROMOCIONA_TO')) define('BP_FORM_PROMOCIONA_TO', 'info@bonospremium.com');
if (!defined('BP_FORM_OFERTAS_TO'))   define('BP_FORM_OFERTAS_TO', 'info@bonospremium.com');

$bp_forms_config = [
    'contacto' => [
        'to'      => apply_filters('bp_form_contacto_to', BP_FORM_CONTACTO_TO),
        'subject' => '📩 Nuevo mensaje de contacto - BonosPremium',
    ],
    'promociona' => [
        'to'      => apply_filters('bp_form_promociona_to', BP_FORM_PROMOCIONA_TO),
        'subject' => '🏪 Promociona tu negocio - BonosPremium',
    ],
    'ofertas' => [
        'to'      => apply_filters('bp_form_ofertas_to', BP_FORM_OFERTAS_TO),
        'subject' => '🎁 Solicitud de recibir ofertas - BonosPremium',
    ],
];
// Filtro para sobreescribir todos los destinos desde child theme / snippet
function bp_forms_config() {
    return apply_filters('bp_forms_config', $GLOBALS['bp_forms_config']);
}

// ============================================================
// PÁGINAS DE FORMULARIO INTEGRADAS EN EL TEMA (sin crear páginas)
// Las URLs /promociona-tu-negocio/, /recibir-ofertas/ y /contacta-con-nosotros/
// funcionan automáticamente al activar el tema en CUALQUIER tienda.
// Si la tienda ya tiene una página creada con ese slug + template, se respeta
// (título y contenido de la página mandan). Félix 10/08: "estas paginas de los
// formularios no hay que crearlas sino que sean del tema".
add_action('init', function() {
    $bp_form_rutas = [
        'promociona-tu-negocio' => 'template-promociona.php',
        'recibir-ofertas'       => 'template-recibir-ofertas.php',
        'contacta-con-nosotros' => 'template-contacto.php',
        'contacta-con-nosotors' => 'template-contacto.php', // alias histórico con typo
    ];
    foreach ($bp_form_rutas as $slug => $tpl) {
        add_rewrite_rule('^' . $slug . '/?$', 'index.php?bp_form_page=' . $slug, 'top');
    }
    // Regenerar reglas de reescritura al cambiar la versión del tema
    if (get_option('bp_form_routes_flushed') !== BP_LZ_VERSION) {
        flush_rewrite_rules();
        update_option('bp_form_routes_flushed', BP_LZ_VERSION);
    }
});

add_filter('query_vars', function($vars) {
    $vars[] = 'bp_form_page';
    return $vars;
});

add_filter('template_include', function($template) {
    $page = get_query_var('bp_form_page');
    if (!$page) return $template;
    $mapa = [
        'promociona-tu-negocio' => 'template-promociona.php',
        'recibir-ofertas'       => 'template-recibir-ofertas.php',
        'contacta-con-nosotros' => 'template-contacto.php',
        'contacta-con-nosotors' => 'template-contacto.php',
    ];
    if (!isset($mapa[$page])) return $template;
    // La ruta del TEMA manda SIEMPRE (Félix 10/08: "estas paginas de los formularios
    // no hay que crearlas sino que sean del tema"). Aunque la tienda tenga una página
    // creada con el mismo slug (incluso contaminada), el template del tema gana.
    $tpl = locate_template($mapa[$page]);
    return $tpl ? $tpl : $template;
});

// Título por defecto de los formularios del tema (si NO hay página creada)
function bp_form_titulo($form) {
    if (is_page()) return get_the_title();
    $titulos = [
        'contacto'   => 'Contacta con nosotros',
        'promociona' => '¡Promociona tu negocio!',
        'ofertas'    => 'Recibir ofertas',
    ];
    return isset($titulos[$form]) ? $titulos[$form] : get_bloginfo('name');
}

// Texto introductorio por defecto (si NO hay página creada)
function bp_form_intro($form) {
    if (is_page() && have_posts()) { the_content(); return; }
    $intros = [
        'contacto'   => 'Cuéntanos tu consulta y te responderemos lo antes posible.',
        'promociona' => '¿Tienes un negocio en tu zona? Promociona tus ofertas entre nuestros clientes.',
        'ofertas'    => 'Apúntate y recibe las mejores ofertas en tu email.',
    ];
    echo isset($intros[$form]) ? esc_html($intros[$form]) : '';
}

// ============================================================
// RECAPTCHA v3 — protege los formularios de spam
// Las keys se definen en wp-config.php:
//
//   define('BP_RECAPTCHA_SITE_KEY', 'TU_SITE_KEY_V3');
//   define('BP_RECAPTCHA_SECRET_KEY', 'TU_SECRET_KEY_V3');
//
// Puedes obtenerlas en: https://www.google.com/recaptcha/admin/create
// (Tipo: reCAPTCHA v3)

if (!defined('BP_RECAPTCHA_SITE_KEY'))    define('BP_RECAPTCHA_SITE_KEY', '');
if (!defined('BP_RECAPTCHA_SECRET_KEY'))  define('BP_RECAPTCHA_SECRET_KEY', '');

// Cargar script de reCAPTCHA v3 + token en los formularios
add_action('wp_enqueue_scripts', function() {
    if (empty(BP_RECAPTCHA_SITE_KEY)) return;
    wp_enqueue_script('bp-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . BP_RECAPTCHA_SITE_KEY, [], null, true);
});

// Añadir token hidden a cada formulario via JS (se rellena al cargar)
add_action('wp_footer', function() {
    if (empty(BP_RECAPTCHA_SITE_KEY)) return;
    ?>
    <script>
    jQuery(function($) {
        if (typeof grecaptcha === 'undefined' || typeof grecaptcha.ready !== 'function') return;
        grecaptcha.ready(function() {
            function fillCaptcha() {
                $('.bp-form').each(function() {
                    var $form = $(this);
                    if ($form.find('input[name="g-recaptcha-response"]').length) return;
                    grecaptcha.execute('<?php echo esc_js(BP_RECAPTCHA_SITE_KEY); ?>', {action: 'submit'}).then(function(token) {
                        if (!$form.find('input[name="g-recaptcha-response"]').length) {
                            $('<input>').attr({type: 'hidden', name: 'g-recaptcha-response', value: token}).appendTo($form);
                        } else {
                            $form.find('input[name="g-recaptcha-response"]').val(token);
                        }
                    });
                });
            }
            fillCaptcha();
            // Regenerar token si ha pasado tiempo (cada 100s)
            setInterval(fillCaptcha, 100000);
        });
    });
    </script>
    <?php
});

// Validar reCAPTCHA en el servidor al procesar el formulario
function bp_verify_recaptcha() {
    if (empty(BP_RECAPTCHA_SECRET_KEY)) return true; // no configurado, se permite

    $token = $_POST['g-recaptcha-response'] ?? '';
    if (empty($token)) return false;

    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
            'secret'   => BP_RECAPTCHA_SECRET_KEY,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ],
    ]);

    if (is_wp_error($response)) return false;
    $result = json_decode(wp_remote_retrieve_body($response), true);
    // score mínimo aceptable 0.5 (ajustable)
    return !empty($result['success']) && ($result['score'] ?? 0) >= apply_filters('bp_recaptcha_min_score', 0.5);
}

// Configurar PHPMailer para SMTP Brevo (solo si hay credenciales definidas)
add_action('phpmailer_init', function($phpmailer) {
    if (empty(BP_BREVO_USER) || empty(BP_BREVO_PASS)) return; // credenciales aún no configuradas
    $phpmailer->isSMTP();
    $phpmailer->Host       = BP_BREVO_HOST;
    $phpmailer->Port       = BP_BREVO_PORT;
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = BP_BREVO_USER;
    $phpmailer->Password   = BP_BREVO_PASS;
    $phpmailer->SMTPSecure = 'tls';
    $phpmailer->From       = BP_BREVO_FROM;
    $phpmailer->FromName   = 'BonosPremium';
});

// Procesar envíos de formularios
add_action('init', function() {
    if (empty($_POST['bp_form_submit'])) return;

    $form = sanitize_key($_POST['bp_form_submit']);
    $config = bp_forms_config();
    if (!isset($config[$form])) return;

    // Nonce
    if (!wp_verify_nonce($_POST['bp_form_nonce'] ?? '', 'bp_form_' . $form)) {
        wp_die('Error de seguridad. Recarga la página e inténtalo de nuevo.');
    }

    // Verificar reCAPTCHA v3
    if (!bp_verify_recaptcha()) {
        wp_die('Error de verificación anti-spam. Recarga la página e inténtalo de nuevo.');
    }

    $fields = [
        'contacto'   => ['nombre', 'email', 'telefono', 'mensaje'],
        'promociona' => ['nombre', 'email', 'telefono', 'negocio', 'web', 'mensaje'],
        'ofertas'    => ['nombre', 'email', 'ciudad'],
    ];

    $data = [];
    foreach (($fields[$form] ?? []) as $f) {
        $data[$f] = sanitize_text_field(wp_unslash($_POST[$f] ?? ''));
    }

    // Validar email
    if (!is_email($data['email'] ?? '')) {
        wp_safe_redirect(add_query_arg('bp_form', $form, wp_get_referer() ?: home_url()) . '#bp-form-' . $form);
        exit;
    }

    // Construir cuerpo del correo
    $labels = [
        'nombre'   => 'Nombre',
        'email'    => 'Email',
        'telefono' => 'Teléfono',
        'mensaje'  => 'Mensaje',
        'negocio'  => 'Nombre del negocio',
        'web'      => 'Web / RRSS',
        'ciudad'   => 'Ciudad',
    ];
    $body = "Formulario: {$config[$form]['subject']}\n\n";
    foreach ($data as $k => $v) {
        $body .= ($labels[$k] ?? ucfirst($k)) . ": " . $v . "\n";
    }

    $headers = ['Reply-To: ' . $data['email']];

    wp_mail($config[$form]['to'], $config[$form]['subject'], $body, $headers);

    wp_safe_redirect(add_query_arg('bp_form', $form, wp_get_referer() ?: home_url()) . '#bp-form-' . $form . '&bp_ok=1');
    exit;
});

// Mostrar aviso de éxito
function bp_form_success($form) {
    if (isset($_GET['bp_ok']) && isset($_GET['bp_form']) && $_GET['bp_form'] === $form) {
        echo '<div class="bp-form-success">✅ ¡Gracias! Tu mensaje se ha enviado correctamente.</div>';
    }
}

// Campos comunes reutilizables
function bp_form_field($type, $name, $label, $required = true, $extra = '') {
    printf(
        '<p class="bp-form-row"><label for="%1$s">%2$s %3$s</label><input type="%4$s" name="%1$s" id="%1$s" placeholder="%2$s" %5$s /></p>',
        esc_attr($name),
        esc_html($label),
        $required ? '<span class="bp-form-required">*</span>' : '<span class="bp-form-opt">(opcional)</span>',
        esc_attr($type),
        $required ? 'required' : '',
        $extra
    );
}


// ============================================================
// TEXTO DEL BOTÓN DE PAGO EN EL CHECKOUT (Félix 11/08)
// "Realizar pedido" (texto por defecto de WooCommerce) -> "Finalizar compra"
// ============================================================
add_filter('woocommerce_order_button_text', function() {
    return 'Finalizar compra';
});

// ============================================================
// CRÉDITO BONOSPREMIUM - ENDPOINT Y COMPRA DE CRÉDITO
// ============================================================
// Reemplazamos la salida del endpoint credito-bonospremium del plugin
// por un diseño app + formulario de compra de crédito con pasarela de pago.

// Obtener saldo del usuario
function bp_get_user_wallet($user_id) {
    global $wpdb;
    $saldo = 0;
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", $user_id
    ));
    if ($row) $saldo = floatval($row->saldo);

    $historial = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}credito_transacciones WHERE user_id = %d ORDER BY fecha_transaccion DESC LIMIT 15",
        $user_id
    ));

    return ['saldo' => $saldo, 'historial' => $historial];
}

// Handler del endpoint de la página Mi Cuenta
// Prioridad 1: empieza a capturar el output (para descartar la salida del plugin que corre a prioridad 10)
add_action('woocommerce_account_credito-bonospremium_endpoint', function() {
    ob_start(); // capturamos TODO a partir de aquí, incluida la salida del plugin
}, 1);

// Prioridad 15: descarta lo capturado del plugin y muestra nuestro diseño
add_action('woocommerce_account_credito-bonospremium_endpoint', function() {
    ob_end_clean(); // descartar la salida del plugin (bono_wallet)
    if (!is_user_logged_in()) return;
    $user_id = get_current_user_id();
    $wallet = bp_get_user_wallet($user_id);
    $saldo = $wallet['saldo'];
    $historial = $wallet['historial'];
    ?>
    <div class="bp-credit-app">

        <!-- Cabecera -->
        <div class="bp-credit-header">
            <div class="bp-credit-title">
                <i class="fas fa-wallet"></i>
                <div>
                    <h2>Crédito BonosPremium</h2>
                    <p>Tu saldo y cómo recargarlo</p>
                </div>
            </div>
        </div>

        <!-- Tarjeta de saldo -->
        <div class="bp-credit-balance-card">
            <div class="bp-credit-balance-top">
                <span class="bp-credit-balance-label">Saldo disponible</span>
                <span class="bp-credit-balance-amount"><?php echo number_format($saldo, 2); ?> €</span>
            </div>
            <p class="bp-credit-balance-info">Este saldo se descuenta automáticamente en tu próximo pedido.</p>
        </div>

        <!-- Formulario para añadir crédito -->
        <div class="bp-credit-box">
            <h3><i class="fas fa-plus-circle"></i> Añadir crédito</h3>
            <p class="bp-credit-sub">Elige un importe y paga de forma segura con tu tarjeta o Bizum.</p>

            <form method="post" class="bp-credit-form" id="bp-credit-form">
                <?php wp_nonce_field('bp_credit_purchase', 'bp_credit_nonce'); ?>

                <!-- Importes rápidos -->
                <div class="bp-credit-presets">
                    <?php
                    $presets = [10, 25, 50, 100];
                    foreach ($presets as $p) {
                        echo '<button type="button" class="bp-credit-preset" data-amount="' . esc_attr($p) . '">' . esc_html($p) . ' €</button>';
                    }
                    ?>
                </div>

                <div class="bp-credit-amount-wrap">
                    <span class="bp-credit-euro">€</span>
                    <input type="number" name="bp_credit_amount" id="bp_credit_amount" class="bp-credit-input" min="1" step="0.01" value="25" placeholder="Importe" required />
                </div>

                <button type="submit" name="bp_add_credit" class="bp-credit-submit">
                    <i class="fas fa-arrow-right"></i> Recargar y pagar
                </button>
                <p class="bp-credit-note"><i class="fas fa-lock"></i> Pago seguro. Serás redirigido a la pasarela de pago.</p>
            </form>
        </div>

        <!-- Historial -->
        <div class="bp-credit-box">
            <h3><i class="fas fa-history"></i> Historial reciente</h3>
            <?php if (!empty($historial)) : ?>
                <div class="bp-credit-history">
                    <?php foreach ($historial as $t) :
                        $es_credito = ($t->tipo === 'credito');
                        $signo = $es_credito ? '+' : '-';
                        ?>
                        <div class="bp-credit-txn">
                            <div class="bp-credit-txn-icon <?php echo $es_credito ? 'in' : 'out'; ?>">
                                <i class="fas <?php echo $es_credito ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
                            </div>
                            <div class="bp-credit-txn-info">
                                <span class="bp-credit-txn-desc"><?php echo esc_html($t->descripcion); ?></span>
                                <span class="bp-credit-txn-date"><?php echo date('d/m/Y H:i', strtotime($t->fecha_transaccion)); ?></span>
                            </div>
                            <span class="bp-credit-txn-amount <?php echo $es_credito ? 'in' : 'out'; ?>"><?php echo $signo . number_format($t->monto, 2); ?> €</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="bp-credit-empty">Aún no tienes movimientos en tu crédito.</p>
            <?php endif; ?>
        </div>

    </div>

    <script>
    jQuery(function($) {
        // Botones de importes rápidos
        $('.bp-credit-preset').on('click', function() {
            $('.bp-credit-preset').removeClass('active');
            $(this).addClass('active');
            $('#bp_credit_amount').val($(this).data('amount'));
        });

        // Enviar formulario -> crea pedido y redirige a la pasarela
        $('#bp-credit-form').on('submit', function(e) {
            var amount = parseFloat($('#bp_credit_amount').val());
            if (!amount || amount <= 0) {
                e.preventDefault();
                alert('Introduce un importe válido mayor que 0.');
                return;
            }
            // El formulario se envía por POST normal -> PHP crea el pedido y redirige
        });
    });
    </script>
    <?php
});

// Procesar la compra de crédito (crea pedido y redirige al checkout/pago)
add_action('init', function() {
    if (isset($_POST['bp_add_credit']) && isset($_POST['bp_credit_nonce'])) {
        if (!wp_verify_nonce($_POST['bp_credit_nonce'], 'bp_credit_purchase')) {
            wp_die('Nonce inválido.');
        }
        if (!is_user_logged_in()) {
            wp_safe_redirect(wc_get_account_endpoint_url('credito-bonospremium'));
            exit;
        }

        $amount = max(1, (float) sanitize_text_field($_POST['bp_credit_amount']));
        $user_id = get_current_user_id();

        // Crear el pedido de crédito
        $order = wc_create_order(['customer_id' => $user_id]);

        // Línea de item de crédito (sin producto real)
        $item = new WC_Order_Item_Product();
        $item->set_name('Recarga de Crédito BonosPremium');
        $item->set_quantity(1);
        $item->set_subtotal($amount);
        $item->set_total($amount);
        $order->add_item($item);

        $order->calculate_totals();

        // Meta para que el plugin añada el crédito tras el pago
        $order->update_meta_data('_bono_credit_add', 'yes');
        $order->update_meta_data('_bono_credit_add_amount', $amount);
        $order->update_meta_data('_bono_credit_add_user_id', $user_id);
        $order->save();

        // Redirigir a la pasarela de pago (checkout del pedido)
        wp_safe_redirect($order->get_checkout_payment_url());
        exit;
    }
});

// ============================================================
// AÑADIR CRÉDITO TRAS EL PAGO DEL PEDIDO DE RECARGA
// ============================================================
add_action('woocommerce_payment_complete', 'bp_add_credit_after_payment', 20, 1);
add_action('woocommerce_order_status_completed', 'bp_add_credit_after_payment', 20, 1);
add_action('woocommerce_order_status_processing', 'bp_add_credit_after_payment', 20, 1);

function bp_add_credit_after_payment($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Solo pedidos de recarga de crédito (marcados al crearse)
    if ($order->get_meta('_bono_credit_add') !== 'yes') return;
    if ($order->get_meta('_bono_credit_processed') === 'yes') return;

    $amount = (float) $order->get_meta('_bono_credit_add_amount');
    $user_id = (int) $order->get_meta('_bono_credit_add_user_id');
    if ($amount <= 0 || !$user_id) return;

    global $wpdb;
    $tabla_saldo  = $wpdb->prefix . 'usuario_creditos';
    $tabla_history = $wpdb->prefix . 'credito_transacciones';

    // Saldo actual
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT saldo FROM {$tabla_saldo} WHERE user_id = %d", $user_id
    ));
    $saldo_actual = $result ? floatval($result->saldo) : 0;
    $saldo_nuevo = $saldo_actual + $amount;

    // Actualizar o insertar saldo del usuario
    if ($result) {
        $wpdb->update($tabla_saldo, ['saldo' => $saldo_nuevo], ['user_id' => $user_id], ['%f'], ['%d']);
    } else {
        $wpdb->insert($tabla_saldo, [
            'user_id'      => $user_id,
            'saldo'        => $saldo_nuevo,
            'fecha_creacion' => current_time('mysql'),
            'fecha_actualizacion' => current_time('mysql'),
        ]);
    }

    // Registrar transacción
    $wpdb->insert($tabla_history, [
        'user_id'           => $user_id,
        'tipo'              => 'credito',
        'monto'             => $amount,
        'saldo_anterior'    => $saldo_actual,
        'saldo_nuevo'       => $saldo_nuevo,
        'descripcion'       => 'Recarga de crédito (pedido #' . $order_id . ')',
        'order_id'          => $order_id,
        'fecha_transaccion' => current_time('mysql'),
    ]);

    // Marcar como procesado
    $order->update_meta_data('_bono_credit_processed', 'yes');
    $order->add_order_note(sprintf(
        'Se añadieron %s € al crédito BonosPremium del usuario. Nuevo saldo: %s €',
        number_format($amount, 2),
        number_format($saldo_nuevo, 2)
    ));
    $order->save();
}

// ===== FIX LOGOUT (Cerrar sesión) =====
// El botón "Cerrar sesión" del bloque estándar de WooCommerce apunta a
// wp-login.php?action=logout, pero el plugin bloquea wp-login.php en GET
// (bloquear_acceso_wp_login redirige a /mi-cuenta/ SIN cerrar sesión).
// Este filtro convierte CUALQUIER enlace de logout al endpoint de WooCommerce
// (mi-cuenta/customer-logout/), que cierra sesión correctamente.
add_filter('logout_url', function ($logout_url, $redirect) {
    if (function_exists('wc_get_account_endpoint_url')) {
        $wc_logout = wc_get_account_endpoint_url('customer-logout');
        if ($wc_logout) return $wc_logout;
    }
    return $logout_url;
}, 10, 2);

// Añade bp_logout=1 al redirect tras cerrar sesión para mostrar el mensaje personalizado.
add_filter('woocommerce_logout_redirect', function ($redirect, $requested) {
    return add_query_arg('bp_logout', '1', $redirect);
}, 10, 2);

