<?php
/**
 * BonosPremium Lanzarote - Theme Functions
 */

// Panel de ajustes de la tienda (colores, SMTP Brevo, emails de formularios)
require_once get_template_directory() . '/admin-bp-settings.php';

// Versión dinámica basada en la fecha de modificación del style.css principal.
// Si no se puede leer el archivo, cae al número manual como respaldo.
if (!defined('BP_LZ_VERSION')) {
    $bp_style_file = get_template_directory() . '/style.css';
    if (file_exists($bp_style_file)) {
        define('BP_LZ_VERSION', (string) filemtime($bp_style_file));
    } else {
        define('BP_LZ_VERSION', '1.4.71');
    }
}

// Colores personalizados desde el panel (solo si hay cambios)
// Prioridad 9999: debe ganar SIEMPRE, incluso al "CSS adicional" del Customizer
// (wp_custom_css se imprime en wp_head con prioridad 101 y pisaba los overrides).
add_action('wp_head', function () {
    $s = bp_get_settings();
    $vars = array();

    $primary = trim($s['primary_color'] ?? '');
    if ($primary && $primary !== '#039CDC') {
        $vars['--bp-primary']       = $primary;
        $vars['--bp-primary-dark']  = bp_adjust_brightness($primary, -0.14);
        $vars['--bp-primary-light'] = bp_adjust_brightness($primary, 0.14);
        $vars['--bp-primary-rgb']   = bp_hex_to_rgb($primary);
    }

    $header_bg = trim($s['header_bg'] ?? '');
    if ($header_bg) {
        $vars['--bp-header-bg'] = $header_bg;
    }
    if (trim($s['header_bg_mobile'] ?? '')) $vars['--bp-header-bg-mobile'] = trim($s['header_bg_mobile']);
    if (trim($s['footer_bg'] ?? ''))         $vars['--bp-footer-bg']       = trim($s['footer_bg']);
    if (trim($s['page_bg'] ?? ''))           $vars['--bp-bg']              = trim($s['page_bg']);
    if (trim($s['text_color'] ?? ''))        $vars['--bp-text']            = trim($s['text_color']);
    if (trim($s['button_bg'] ?? ''))         $vars['--bp-button-bg']       = trim($s['button_bg']);
    if (trim($s['button_hover'] ?? ''))      $vars['--bp-button-hover']    = trim($s['button_hover']);
    if (trim($s['sale_color'] ?? ''))        $vars['--bp-sale-color']      = trim($s['sale_color']);

    if (empty($vars)) return;
    $css = ':root{' . implode('', array_map(function ($k, $v) { return $k . ':' . $v . ';'; }, array_keys($vars), $vars)) . '}';
    echo "\n<style id=\"bp-theme-custom-colors\">" . $css . "</style>\n";
}, 9999);

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

// ============================================================
// GOOGLE ANALYTICS (GA4) + TAG MANAGER — desde el panel Integraciones
// Solo se carga el código si el ID está configurado en el panel.
// El Consent Mode v2 se declara ANTES (bloque de arriba, priority 1).
// ============================================================
add_action('wp_head', function() {
    $ga_id  = bp_get_setting('ga_id', '');
    $gtm_id = bp_get_setting('gtm_id', '');

    if (!empty($gtm_id)) :
    ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?php echo esc_js($gtm_id); ?>');</script>
    <!-- End Google Tag Manager -->
    <?php
    endif;

    if (!empty($ga_id)) :
    ?>
    <!-- Google Analytics GA4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js($ga_id); ?>"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?php echo esc_js($ga_id); ?>', { send_page_view: true });
    </script>
    <!-- End Google Analytics -->
    <?php
    endif;
}, 2);

// Noscript de GTM (obligatorio para que GTM funcione sin JS)
add_action('wp_body_open', function() {
    $gtm_id = bp_get_setting('gtm_id', '');
    if (empty($gtm_id)) return;
    ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr($gtm_id); ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php
});

// ============================================================
// GOOGLE MAPS — API key desde el panel Integraciones
// Si la key está vacía, el plugin usa su modo sin key.
// ============================================================
add_filter('pre_option_wpgmza_google_maps_api_key', function($value) {
    $key = bp_get_setting('maps_api_key', '');
    if (!empty($key)) return $key;
    return $value;
});

// API key para los campos ACF tipo "Google Map" (campo "Mapa" de los productos)
// Sin este filtro, ACF carga Google Maps sin key → error "¿Eres el propietario de este sitio web?"
// ⚠️ En ACF 6.x el filtro es 'acf/fields/google_map/api' (recibe el array completo).
// También se fija el setting para que el resto del campo lo use.
add_filter('acf/fields/google_map/api', function($api) {
    $key = bp_get_setting('maps_api_key', '');
    if (!empty($key)) {
        $api['key'] = $key;
        acf_update_setting('google_api_key', $key);
    }
    return $api;
});
add_action('acf/init', function() {
    $key = bp_get_setting('maps_api_key', '');
    if (!empty($key)) {
        acf_update_setting('google_api_key', $key);
    }
});

// Soporte para WooCommerce
add_action('after_setup_theme', function() {
    add_theme_support('woocommerce');
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

// Cache-busting PRODUCCIÓN (Félix 09/09): el HTML revalida siempre (no-cache →
// el navegador pregunta al servidor, que responde 304 si no cambió, sin descargar
// todo) y los CSS/JS llevan ver=BP_LZ_VERSION con caché LARGA (immutable) en nginx:
// al subir una versión nueva la URL cambia → los navegadores descargan solo lo nuevo.
add_action('send_headers', function() {
    header('Cache-Control: no-cache, must-revalidate');
});

// Cargar estilos y scripts
add_action('wp_enqueue_scripts', function() {
    // Google Fonts: Inter
    wp_enqueue_style('bp-lz-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap', [], null);
    
    // Font Awesome
    wp_enqueue_style('bp-lz-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css', [], '6.5.0');
    
    // Estilos del tema
    wp_enqueue_style('bp-lz-style', get_stylesheet_uri(), [], BP_LZ_VERSION);
    $bp_main_css = get_template_directory() . '/assets/css/main.css';
	$bp_main_js  = get_template_directory() . '/assets/js/main.js';
	$bp_main_css_ver = file_exists($bp_main_css) ? filemtime($bp_main_css) : BP_LZ_VERSION;
	$bp_main_js_ver  = file_exists($bp_main_js)  ? filemtime($bp_main_js)  : BP_LZ_VERSION;

	wp_enqueue_style('bp-lz-main', get_template_directory_uri() . '/assets/css/main.css', ['bp-lz-style'], $bp_main_css_ver);
	wp_enqueue_script('bp-lz-main', get_template_directory_uri() . '/assets/js/main.js', ['jquery'], $bp_main_js_ver, true);
    
    // MapTiler SDK JS v4.1.0 (ejemplo oficial "Display a map" LZ 08/09)
    wp_enqueue_style('bp-lz-maptiler', 'https://cdn.maptiler.com/maptiler-sdk-js/v4.1.0/maptiler-sdk.css', [], '4.1.0');
    wp_enqueue_script('bp-lz-maptiler', 'https://cdn.maptiler.com/maptiler-sdk-js/v4.1.0/maptiler-sdk.umd.min.js', [], '4.1.0', true);
    
    // Barra de compartir + mapa de bonos (27/08) — GLOBAL (03/09): el modal del
    // mapa vive en footer.php y se abre desde la ficha de producto (botón de la
    // barra superior) y desde el menú de WP (enlace con clase bp-open-map).
    wp_enqueue_script('bp-lz-compartir', get_template_directory_uri() . '/assets/js/bono-compartir.js', ['jquery', 'bp-lz-maptiler'], BP_LZ_VERSION, true);
    wp_localize_script('bp-lz-compartir', 'bp_compartir', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'maptiler_api_key' => bp_get_setting('maptiler_api_key', ''),
        'template_uri' => get_template_directory_uri(),
    ]);
    
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

// ============================================================
// LZ 05/09 — Ítem "Mapa" al inicio del menú horizontal (primary)
// Antepone un enlace que abre el modal del mapa (clase bp-open-map
// gestionada en bono-compartir.js) antes de los enlaces de WP.
// ============================================================
add_filter('wp_nav_menu_items', function($items, $args) {
    if (!isset($args->theme_location) || $args->theme_location !== 'primary') {
        return $items;
    }
    $map_item = '<li class="bp-nav-mapa"><a href="#mapa" class="bp-open-map">Mapa</a></li>';
    return $map_item . $items;
}, 10, 2);

// ============================================================
// LZ 05/09 — Quitar el ítem "Mapa" del menú desplegable (user-menu)
// El mapa se abre desde el botón del header y el menú horizontal.
// Excluye tanto el ítem manual del admin (clase bp-open-map) como
// cualquier enlace al mapa que pudiera añadirse al user-menu.
// ============================================================
add_filter('wp_nav_menu_objects', function($items, $args) {
    if (!isset($args->theme_location) || $args->theme_location !== 'user-menu') {
        return $items;
    }
    foreach ($items as $k => $item) {
        $cls = is_array($item->classes) ? implode(' ', $item->classes) : '';
        if (strpos($cls, 'bp-open-map') !== false) {
            unset($items[$k]);
        }
    }
    return array_values($items);
}, 10, 2);

// ============================================================
// LZ 26/08 — GEOCODIFICACIÓN AUTOMÁTICA desde "Dirección"
// Al escribir la dirección en el admin del producto, se geocodifica
// (Nominatim/OSM, sin key) y se guardan mapa_lat/mapa_lng automáticamente.
// El mapa en vivo del admin usa Google Maps JS (key del panel Integraciones).
// ============================================================
add_action('admin_enqueue_scripts', function($hook) {
    // Solo en edición de producto (post.php o post-new.php con post_type=product)
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;
    global $post_type;
    if ($post_type !== 'product') return;
    $key = bp_get_setting('maps_api_key', '');
    wp_enqueue_script('bp-lz-admin-geo', get_template_directory_uri() . '/assets/js/admin-geocodificar.js', [], BP_LZ_VERSION, true);
    wp_localize_script('bp-lz-admin-geo', 'bp_geo', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('bp_geo_nonce'),
        'maps_key' => $key,
    ]);
});

// Endpoint AJAX: geocodifica la dirección (Nominatim) y guarda mapa_lat/mapa_lng
add_action('wp_ajax_bp_geo_geocodificar', function() {
    check_ajax_referer('bp_geo_nonce', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['mensaje' => 'Sin permisos']);
    }
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    $direccion = isset($_POST['direccion']) ? sanitize_text_field(wp_unslash($_POST['direccion'])) : '';
    if (!$post_id || !$direccion) {
        wp_send_json_error(['mensaje' => 'Faltan datos']);
    }
    // Geocodificar con Nominatim (OpenStreetMap) — sin key, gratis.
    // 1º intento: la dirección tal cual (funciona para cualquier isla).
    // 2º intento: añadir ", España" si el primero no encuentra nada.
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'header' => "User-Agent: BonosPremiumLZ/1.0\r\n"]]);
    $data = [];
    foreach ([$direccion, $direccion . ', España'] as $intento) {
        $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($intento) . "&format=json&limit=1&countrycodes=es";
        $res = @file_get_contents($url, false, $ctx);
        if ($res !== false) {
            $data = json_decode($res, true);
            if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) break;
        }
    }
    if (empty($data[0]['lat']) || empty($data[0]['lon'])) {
        wp_send_json_error(['mensaje' => 'No se encontró la dirección. Prueba con más detalle (calle, número, población).']);
    }
    $lat = $data[0]['lat'];
    $lng = $data[0]['lon'];
    // Guardar automáticamente (campos ACF free + meta directa para robustez)
    update_post_meta($post_id, 'mapa_lat', $lat);
    update_post_meta($post_id, 'mapa_lng', $lng);
    if (function_exists('update_field')) {
        update_field('mapa_lat', $lat, $post_id);
        update_field('mapa_lng', $lng, $post_id);
    }
    wp_send_json_success([
        'lat' => $lat,
        'lng' => $lng,
        'dir' => $data[0]['display_name'] ?? $direccion,
    ]);
});

// ============================================================
// MAPA DE BONOS — devuelve todos los productos con coordenadas
// para pintar los markers en el modal de mapa a pantalla completa.
// ============================================================
// Precio para el marker: SOLO el precio de oferta si existe (Félix 05/09),
// si no, el precio normal. Para variables, el mínimo de las variaciones.
function bp_precio_oferta_txt($producto) {
    if (!$producto) return '';
    $precio = '';
    if ($producto->is_type('variable')) {
        $precio = $producto->get_variation_sale_price('min');
        if (empty($precio)) $precio = $producto->get_variation_price('min');
    } else {
        $precio = $producto->get_sale_price();
        if (empty($precio)) $precio = $producto->get_price();
    }
    if ($precio === '' || $precio === false) return '';
    return trim(wp_strip_all_tags(html_entity_decode(wc_price($precio))));
}
add_action('wp_ajax_bp_bonos_mapa', 'bp_bonos_mapa_data');
add_action('wp_ajax_nopriv_bp_bonos_mapa', 'bp_bonos_mapa_data');
function bp_bonos_mapa_data() {
    $productos = get_posts([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);
    $bonos = [];
    foreach ($productos as $pid) {
        $lat = get_field('mapa_lat', $pid) ?: get_post_meta($pid, 'mapa_lat', true);
        $lng = get_field('mapa_lng', $pid) ?: get_post_meta($pid, 'mapa_lng', true);
        if ((empty($lat) || empty($lng)) && function_exists('get_field')) {
            $m = get_field('mapa', $pid) ?: get_post_meta($pid, 'mapa', true);
            if (is_array($m) && isset($m['lat'], $m['lng'])) {
                $lat = $m['lat'];
                $lng = $m['lng'];
            }
        }
        $lat = (float) $lat;
        $lng = (float) $lng;
        if (empty($lat) || empty($lng)) continue;
        $producto = wc_get_product($pid);
        $img = get_the_post_thumbnail_url($pid, 'thumbnail');
        $bonos[] = [
            'id'        => $pid,
            'titulo'    => get_the_title($pid),
            'nombre'    => get_field('nombre_establecimiento', $pid) ?: get_post_meta($pid, 'nombre_establecimiento', true),
            'url'       => get_permalink($pid),
            'img'       => $img ? $img : '',
            'precio'    => $producto ? $producto->get_price_html() : '',
            'precio_txt' => bp_precio_oferta_txt($producto),
            'localidad' => get_field('localidad', $pid) ?: get_post_meta($pid, 'localidad', true),
            'lat'       => $lat,
            'lng'       => $lng,
        ];
    }
    wp_send_json_success($bonos);
}

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

// FIX 03/09: Forzar no-store en FICHAS DE PRODUCTO aunque WooCommerce (modo
// Coming Soon) sobreescriba con max-age=60. Sin esto el navegador servía la
// versión vieja del botón comprar (form + AJAX) aunque el servidor ya
// generara el enlace directo ?add-to-cart=...
add_filter('wp_headers', function($headers) {
    if (function_exists('is_product') && is_product()) {
        $headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
        $headers['Pragma'] = 'no-cache';
        $headers['Expires'] = 'Wed, 11 Jan 1984 05:00:00 GMT';
    }
    return $headers;
}, 999);

// Refuerzo: template_include con prioridad 9999 (corre DESPUÉS del ComingSoon
// handler de WooCommerce, que pone max-age=60 y pisaría el wp_headers de arriba)
add_filter('template_include', function($template) {
    if (function_exists('is_product') && is_product()) {
        header_remove('Cache-Control');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    }
    return $template;
}, 9999);



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
            // FIX 31/08: usar la MISMA plantilla que el loop inicial de archive-product.php
            // (wc_get_template_part content-product → genera <li class="product"> con el card
            // dentro vía hook woocommerce_before_shop_loop_item). Antes se usaba solo
            // do_action('woocommerce_before_shop_loop_item') → el card salía SIN el <li>
            // envolvente y la carga infinita no tenía el mismo formato que los li iniciales.
            wc_get_template_part('content', 'product');
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
    $sale_price = $product->get_price() ?: $regular_price;

    // FIX 18/08 (Félix): en productos VARIABLES el precio del loop es el de la
    // PRIMERA variación (la primera del listado), no el mínimo.
    // 25/08: se delega en bp_resolver_precio_loop() que respeta el modo elegido
    // en la ficha del producto (auto/min/max/variacion/manual).
    $resuelto = bp_resolver_precio_loop($product);
    $sale_price = $resuelto['sale'];
    $regular_price = $resuelto['regular'];
    if (!$regular_price) $regular_price = $sale_price; // sin regular → solo precio
    
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
        echo '<span class="bp-product-city"> ' . esc_html($city) . '</span>';
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
                ¿Tienes un cupón de descuento?
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
        'to'      => bp_get_setting('form_contacto_to', apply_filters('bp_form_contacto_to', BP_FORM_CONTACTO_TO)),
        'subject' => bp_get_setting('form_contacto_subject', '📩 Nuevo mensaje de contacto - BonosPremium'),
    ],
    'promociona' => [
        'to'      => bp_get_setting('form_promociona_to', apply_filters('bp_form_promociona_to', BP_FORM_PROMOCIONA_TO)),
        'subject' => bp_get_setting('form_promociona_subject', '🏪 Promociona tu negocio - BonosPremium'),
    ],
    'ofertas' => [
        'to'      => bp_get_setting('form_ofertas_to', apply_filters('bp_form_ofertas_to', BP_FORM_OFERTAS_TO)),
        'subject' => bp_get_setting('form_ofertas_subject', '🎁 Solicitud de recibir ofertas - BonosPremium'),
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
// Las keys se configuran en el panel BonosPremium > Integraciones
// (o, como respaldo, en wp-config.php):
//
//   define('BP_RECAPTCHA_SITE_KEY', 'TU_SITE_KEY_V3');
//   define('BP_RECAPTCHA_SECRET_KEY', 'TU_SECRET_KEY_V3');
//
// Puedes obtenerlas en: https://www.google.com/recaptcha/admin/create
// (Tipo: reCAPTCHA v3)

if (!defined('BP_RECAPTCHA_SITE_KEY'))    define('BP_RECAPTCHA_SITE_KEY', '');
if (!defined('BP_RECAPTCHA_SECRET_KEY'))  define('BP_RECAPTCHA_SECRET_KEY', '');

// Prioridad: panel (bp_theme_settings) > wp-config
function bp_recaptcha_site_key()   { return bp_get_setting('recaptcha_site_key', BP_RECAPTCHA_SITE_KEY); }
function bp_recaptcha_secret_key() { return bp_get_setting('recaptcha_secret_key', BP_RECAPTCHA_SECRET_KEY); }

// ¿Estamos en una de las 3 páginas de formulario del tema?
// (promociona-tu-negocio, recibir-ofertas, contacta-con-nosotros)
function bp_is_form_page() {
    if (get_query_var('bp_form_page')) return true;
    if (is_page_template('template-contacto.php')) return true;
    if (is_page_template('template-promociona.php')) return true;
    if (is_page_template('template-recibir-ofertas.php')) return true;
    return false;
}

// Cargar script de reCAPTCHA v3 SOLO en las páginas de formulario
add_action('wp_enqueue_scripts', function() {
    $site = bp_recaptcha_site_key();
    if (empty($site)) return;
    if (!bp_is_form_page()) return; // solo en los 3 formularios, no en toda la web
    wp_enqueue_script('bp-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . $site, [], null, true);
});

// Añadir token hidden a cada formulario via JS (se rellena al cargar)
add_action('wp_footer', function() {
    $site = bp_recaptcha_site_key();
    if (empty($site)) return;
    if (!bp_is_form_page()) return; // solo en los 3 formularios
    ?>
    <script>
    jQuery(function($) {
        if (typeof grecaptcha === 'undefined' || typeof grecaptcha.ready !== 'function') return;
        grecaptcha.ready(function() {
            function fillCaptcha() {
                $('.bp-form').each(function() {
                    var $form = $(this);
                    if ($form.find('input[name="g-recaptcha-response"]').length) return;
                    grecaptcha.execute('<?php echo esc_js($site); ?>', {action: 'submit'}).then(function(token) {
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
    $secret = bp_recaptcha_secret_key();
    if (empty($secret)) return true; // no configurado, se permite

    $token = $_POST['g-recaptcha-response'] ?? '';
    if (empty($token)) return false;

    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
            'secret'   => $secret,
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
// Las credenciales se pueden configurar en el panel BonosPremium > Ajustes (admin) o en wp-config.php
add_action('phpmailer_init', function($phpmailer) {
    $smtp_user = bp_get_setting('smtp_user', BP_BREVO_USER);
    $smtp_pass = bp_get_setting('smtp_pass', BP_BREVO_PASS);
    if (empty($smtp_user) || empty($smtp_pass)) return; // credenciales aún no configuradas
    $phpmailer->isSMTP();
    $phpmailer->Host       = bp_get_setting('smtp_host', BP_BREVO_HOST);
    $phpmailer->Port       = (int) bp_get_setting('smtp_port', BP_BREVO_PORT);
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = $smtp_user;
    $phpmailer->Password   = $smtp_pass;
    $phpmailer->SMTPSecure = 'tls';
    $phpmailer->From       = bp_get_setting('smtp_from', BP_BREVO_FROM);
    $phpmailer->FromName   = bp_get_setting('smtp_from_name', 'BonosPremium');
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
        $dest = wp_get_referer() ?: home_url($_SERVER['REQUEST_URI'] ?? '/');
        wp_safe_redirect(add_query_arg('bp_form', $form, $dest) . '#bp-form-' . $form);
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

    // Redirigir a la página del formulario con éxito. IMPORTANTE: bp_ok debe ir en el QUERY (antes del #),
    // no en el fragmento, o $_GET['bp_ok'] nunca existirá y no se mostrará el aviso de éxito.
    $dest = wp_get_referer() ?: home_url($_SERVER['REQUEST_URI'] ?? '/');
    wp_safe_redirect(add_query_arg(array('bp_form' => $form, 'bp_ok' => 1), $dest) . '#bp-form-' . $form);
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
        '<p class="bp-form-row"><label for="%1$s">%2$s %3$s</label><input type="%4$s" name="%1$s" id="%1$s" placeholder="" %5$s /></p>',
        esc_attr($name),
        esc_html($label),
        $required ? '<span class="bp-form-required">*</span>' : '<span class="bp-form-opt">(opcional)</span>',
        esc_attr($type),
        $required ? 'required' : '',
        $extra
    );
}

function bp_form_select($name, $label, $options, $selected = '', $required = true, $extra = '') {
    $output = sprintf(
        '<p class="bp-form-row"><label for="%s">%s %s</label>',
        esc_attr($name),
        esc_html($label),
        $required ? '<span class="bp-form-required">*</span>' : '<span class="bp-form-opt">(opcional)</span>'
    );
    $output .= sprintf('<select name="%s" id="%s" %s %s>',
        esc_attr($name),
        esc_attr($name),
        $required ? 'required' : '',
        $extra
    );
    foreach ($options as $value => $text) {
        $selected_attr = selected($selected, $value, false);
        $output .= sprintf('<option value="%s" %s>%s</option>',
            esc_attr($value),
            $selected_attr,
            esc_html($text)
        );
    }
    $output .= '</select></p>';
    echo $output;
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

// ===== RESOLVER PRECIO DEL LOOP (usado en woocommerce_before_shop_loop_item) =====
// Devuelve array ['sale' => float, 'regular' => float|null] según el modo configurado.

/**
 * Busca la variación por defecto del producto variable (default attributes).
 * Devuelve el objeto WC_Product_Variation o null.
 */
function bp_buscar_variacion_por_defecto($product, $children) {
    if (!$product || empty($children)) return null;
    $defaults = $product->get_default_attributes();
    if (empty($defaults)) return null;

    foreach ($children as $cid) {
        $v = wc_get_product($cid);
        if (!$v) continue;
        $attrs = $v->get_variation_attributes();
        if (!is_array($attrs)) continue;
        // Normalizar claves: attribute_sleccionar vs sleccionar
        $coincide = true;
        foreach ($defaults as $dk => $dv) {
            $dk_norm = $dk;
            if (strpos($dk_norm, 'attribute_') !== 0) $dk_norm = 'attribute_' . $dk_norm;
            $val = isset($attrs[$dk_norm]) ? $attrs[$dk_norm] : null;
            if ($val === null || $val === '') $val = isset($attrs[$dk]) ? $attrs[$dk] : null;
            if ((string)$val !== (string)$dv) { $coincide = false; break; }
        }
        if ($coincide) return $v;
    }
    return null;
}

function bp_resolver_precio_loop($product) {
    $pid = $product->get_id();
    $mode = get_post_meta($pid, '_bp_loop_price_mode', true) ?: 'auto';
    $var_id = (int) get_post_meta($pid, '_bp_loop_price_variation', true);
    $manual = (float) get_post_meta($pid, '_bp_loop_price_manual', true);

    $sale = $product->get_price();
    $regular = $product->get_regular_price();
    $es_variable = $product->is_type('variable');

    if ($es_variable) {
        $children = $product->get_children();
        switch ($mode) {
            case 'min':
                if ($children) {
                    $precios = array();
                    $regulares = array();
                    foreach ($children as $cid) {
                        $v = wc_get_product($cid);
                        if ($v) {
                            $precios[] = (float) $v->get_price();
                            $reg = $v->get_regular_price();
                            if ($reg !== '') $regulares[] = (float) $reg;
                        }
                    }
                    if ($precios) {
                        $sale = min($precios);
                        $regular = $regulares ? min($regulares) : null;
                    }
                }
                break;

            case 'max':
                if ($children) {
                    $precios = array();
                    $regulares = array();
                    foreach ($children as $cid) {
                        $v = wc_get_product($cid);
                        if ($v) {
                            $precios[] = (float) $v->get_price();
                            $reg = $v->get_regular_price();
                            if ($reg !== '') $regulares[] = (float) $reg;
                        }
                    }
                    if ($precios) {
                        $sale = max($precios);
                        $regular = $regulares ? max($regulares) : null;
                    }
                }
                break;

            case 'variacion':
                if ($var_id) {
                    $v = wc_get_product($var_id);
                    if ($v) {
                        $sale = $v->get_price();
                        $regular = $v->get_regular_price();
                        if ($regular === '') $regular = null;
                    }
                } elseif (!empty($children)) {
                    // Si no se eligió variación concreta, usar la primera (compatibilidad)
                    $first = wc_get_product($children[0]);
                    if ($first) {
                        $sale = $first->get_price();
                        $regular = $first->get_regular_price();
                        if ($regular === '') $regular = null;
                    }
                }
                break;

            case 'manual':
                if ($manual > 0) {
                    $sale = $manual;
                    $regular = null; // precio manual no muestra tachado
                }
                break;

            case 'auto':
            default:
                // Usar la variación por defecto del producto (default attributes) si existe;
                // si no, la primera variación; si no hay variaciones, el precio directo.
                $variacion_auto = bp_buscar_variacion_por_defecto($product, $children);
                if ($variacion_auto) {
                    $sale = $variacion_auto->get_price();
                    $regular = $variacion_auto->get_regular_price();
                    if ($regular === '') $regular = null;
                } elseif (!empty($children)) {
                    $first = wc_get_product($children[0]);
                    if ($first) {
                        $sale = $first->get_price();
                        $regular = $first->get_regular_price();
                        if ($regular === '') $regular = null;
                    }
                }
                break;
        }
    } else {
        // Producto simple: si hay modo manual configurado, aplica
        if ($mode === 'manual' && $manual > 0) {
            $sale = $manual;
            $regular = null;
        }
    }

    if (!$regular) $regular = $sale;
    return array('sale' => $sale, 'regular' => $regular);
}
// ===== PRECIO EN EL LISTADO (LOOP) PARA PRODUCTOS VARIABLE =====
// Félix 25/08: poder elegir qué precio sale en el loop de productos
// cuando el producto tiene variaciones.
// Modos: auto | min | max | variacion | manual
// Guarda en post_meta: _bp_loop_price_mode, _bp_loop_price_variation, _bp_loop_price_manual
add_action('add_meta_boxes', function () {
    add_meta_box(
        'bp_loop_price',
        'Precio en el listado (loop)',
        'bp_loop_price_metabox_html',
        'product',
        'side',
        'default'
    );
});

function bp_loop_price_metabox_html($post) {
    wp_nonce_field('bp_loop_price_save', 'bp_loop_price_nonce');
    $mode = get_post_meta($post->ID, '_bp_loop_price_mode', true) ?: 'auto';
    $var_id = (int) get_post_meta($post->ID, '_bp_loop_price_variation', true);
    $manual = get_post_meta($post->ID, '_bp_loop_price_manual', true);

    $product = wc_get_product($post->ID);
    $is_variable = $product && $product->is_type('variable');
    $variaciones = array();
    if ($is_variable) {
        $variaciones = $product->get_children();
    }
    ?>
    <p style="margin-top:2px;font-size:11px;color:#666;">
        Elige qué precio mostrar en la cuadrícula de productos.
        <?php if ($is_variable): ?>
            <strong>Producto variable con <?= count($variaciones) ?> variación/es.</strong>
        <?php else: ?>
            <strong>Producto simple</strong> — el modo solo aplica si se convierte en variable.
        <?php endif; ?>
    </p>
    <label style="display:block;font-size:11px;font-weight:600;margin-bottom:3px;">Modo</label>
    <select name="bp_loop_price_mode" id="bp-loop-price-mode" style="width:100%;margin-bottom:8px;">
        <option value="auto" <?php selected($mode, 'auto'); ?>>Automático (primera variación / precio actual)</option>
        <option value="min" <?php selected($mode, 'min'); ?>>Mínimo de las variaciones</option>
        <option value="max" <?php selected($mode, 'max'); ?>>Máximo de las variaciones</option>
        <option value="variacion" <?php selected($mode, 'variacion'); ?>>Elegir variación concreta</option>
        <option value="manual" <?php selected($mode, 'manual'); ?>>Precio fijo manual</option>
    </select>

    <div id="bp-loop-price-variacion" style="margin-bottom:8px;<?= $mode === 'variacion' ? '' : 'display:none;'; ?>">
        <label style="display:block;font-size:11px;font-weight:600;margin-bottom:3px;">Variación a mostrar</label>
        <?php if ($is_variable && !empty($variaciones)): ?>
            <select name="bp_loop_price_variation" style="width:100%;">
                <?php foreach ($variaciones as $vid): ?>
                    <?php $v = wc_get_product($vid); if (!$v) continue; ?>
                    <?php $attrs = $v->get_variation_attributes(); $label = is_array($attrs) ? implode(' / ', array_filter($attrs)) : 'Variación #' . $vid; ?>
                    <option value="<?= (int)$vid; ?>" <?php selected($var_id, $vid); ?>>
                        <?= esc_html($label); ?> — <?= wc_price($v->get_price()); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p style="font-size:10px;color:#888;margin-top:3px;">Se muestran las variaciones publicadas del producto.</p>
        <?php else: ?>
            <p style="font-size:11px;color:#b00;">Este producto no tiene variaciones publicadas todavía.</p>
            <input type="hidden" name="bp_loop_price_variation" value="<?= esc_attr($var_id); ?>">
        <?php endif; ?>
    </div>

    <div id="bp-loop-price-manual" style="margin-bottom:8px;<?= $mode === 'manual' ? '' : 'display:none;'; ?>">
        <label style="display:block;font-size:11px;font-weight:600;margin-bottom:3px;">Precio fijo (€)</label>
        <input type="number" step="0.01" min="0" name="bp_loop_price_manual" value="<?= esc_attr($manual); ?>" placeholder="Ej: 29.90" style="width:100%;">
    </div>

    <script>
    (function () {
        var sel = document.getElementById('bp-loop-price-mode');
        if (!sel) return;
        function toggle() {
            var m = sel.value;
            document.getElementById('bp-loop-price-variacion').style.display = (m === 'variacion') ? '' : 'none';
            document.getElementById('bp-loop-price-manual').style.display = (m === 'manual') ? '' : 'none';
        }
        sel.addEventListener('change', toggle);
    })();
    </script>
    <?php
}

// Guardar
add_action('save_post_product', function ($post_id) {
    if (!isset($_POST['bp_loop_price_nonce']) || !wp_verify_nonce($_POST['bp_loop_price_nonce'], 'bp_loop_price_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_product', $post_id)) return;

    $mode = isset($_POST['bp_loop_price_mode']) ? sanitize_text_field($_POST['bp_loop_price_mode']) : 'auto';
    $valid = array('auto', 'min', 'max', 'variacion', 'manual');
    if (!in_array($mode, $valid)) $mode = 'auto';
    update_post_meta($post_id, '_bp_loop_price_mode', $mode);

    $var = isset($_POST['bp_loop_price_variation']) ? (int) $_POST['bp_loop_price_variation'] : 0;
    update_post_meta($post_id, '_bp_loop_price_variation', $var);

    $manual = isset($_POST['bp_loop_price_manual']) ? (float) str_replace(',', '.', $_POST['bp_loop_price_manual']) : '';
    if ($manual <= 0) $manual = '';
    update_post_meta($post_id, '_bp_loop_price_manual', $manual);
});


add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', 'personalizar_texto_check_terminos', 999 );
function personalizar_texto_check_terminos( $text ) {
    return 'He leído y acepto las [terms] de la web';
}

/* --- Quitar scroll automatico al mostrar login en checkout --- */
add_action('wp_footer', function() {
    if (is_checkout()) {
        ?><script>
jQuery(function(jq) {
    if (typeof wc_checkout_login_form !== 'undefined')
        wc_checkout_login_form.show_login_form = function() {
            jq('form.login, form.woocommerce-form--login').slideToggle(400);
            return false;
        };
});
</script><?php
    }
});

/* ─── Desactivar zoom en galería de producto ─── */

/* ─── Desactivar zoom en galería de producto ─── */
add_action('wp', function() {
    remove_theme_support('wc-product-gallery-zoom');
    wp_dequeue_script('zoom');
}, 100);
