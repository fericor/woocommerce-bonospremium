<?php
/*
 * Plugin Name: Woocommerce Bonos Premium
 * Plugin URI: https://bonospremium.com
 * Description: Agrega un QR a cada producto del pedido.
 * Version: 1.0.0
 * Author: Felix Cortez (Bonospremium)
 * Author URI: https://vallamketing.es
 * License: GPL-2.0+
 */


if ( ! defined( 'ABSPATH' ) ) { exit; }

/////////////////////////////////////////////////////////
// Quitar los desimales cuando sea necesario
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );

/*******************************************/
// Activar modo mantenimiento
function kb_modo_mantenimiento() {
    if ( !current_user_can( 'administrator' ) && !is_user_logged_in() ) {
        wp_die('
            <h1>Estamos actualizando nuestra tienda</h1>
            <p>Por favor, vuelve a visitarnos en unos minutos.</p>
            <p>Disculpa las molestias.</p>
        ', 'Tienda en Mantenimiento');
    }
}
// add_action('get_header', 'kb_modo_mantenimiento');
/*******************************************/

/***************************************************************************** 
 *   AÑADIR UN NUEVO MENU AL PERFIL DE WOOCOMMERCE
 */

// 1. REGISTRAR ENDPOINT
add_action('init', function() {
    add_rewrite_endpoint('credito-bonospremium', EP_PAGES);
}, 0);

// 2. AÑADIR AL MENÚ (VERSIÓN SEGURA)
add_filter('woocommerce_account_menu_items', function($menu_links) {
    
    // Verificar que $menu_links es un array
    if (!is_array($menu_links)) {
        return array();
    }
    
    // Crear NUEVO array (no modificar el existente directamente)
    $new_menu_links = array();
    
    // Recorrer el menú original y añadir nuestro item
    $added = false;
    foreach ($menu_links as $key => $title) {
        // Añadir el item actual
        $new_menu_links[$key] = $title;
        
        // Insertar "Crédito BonosPremium" después de "Dashboard"
        if ($key === 'dashboard' && !$added) {
            $new_menu_links['credito-bonospremium'] = 'Crédito BonosPremium';
            $added = true;
        }
    }
    
    // Si por alguna razón no se añadió (no había dashboard), añadir al final
    if (!$added) {
        // Guardar logout temporalmente
        $logout = isset($new_menu_links['customer-logout']) ? $new_menu_links['customer-logout'] : '';
        if ($logout) {
            unset($new_menu_links['customer-logout']);
        }
        
        $new_menu_links['credito-bonospremium'] = 'Crédito BonosPremium';
        
        if ($logout) {
            $new_menu_links['customer-logout'] = $logout;
        }
    }
    
    return $new_menu_links;
}, 999); // Prioridad alta para asegurar

// 3. CONTENIDO CON bono_wallet
add_action('woocommerce_account_credito-bonospremium_endpoint', function() {
    // Mostrar el shortcode bono_wallet
    echo do_shortcode('[bono_wallet]');
});

// 4. ACTUALIZAR REWRITE RULES
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules(true);
});

// 5. Forzar flush también al guardar permalinks
add_action('after_switch_theme', function() {
    flush_rewrite_rules(false);
});
/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */


/***************************************************************************** 
 * CONTROL DE EXISTENCIAS EN PRODUCTOS VARIABLES
 * SOLO PARA EMPRESAS COLABORADORAS
 * SOLO PARA PRODUCTOS CREADOS POR ESA EMPRESA
 *****************************************************************************/

// 1. Añadir script JavaScript para manejar la verificación de variaciones
// add_action('wp_footer', 'verificar_codigos_disponibles_variaciones');
function verificar_codigos_disponibles_variaciones() {
    if (!is_product()) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Variable para controlar el estado de verificación
        var verificandoCodigos = false;
        
        // Manejar cambios en las variaciones
        $('.variations_form').on('found_variation', function(event, variation) {
            var nombreVariacion = variation.variation_description || variation.attributes_string || '';
            
            // Si no encontramos el nombre en variation, intentamos obtenerlo de los atributos seleccionados
            if (!nombreVariacion) {
                nombreVariacion = '';
                $('.variations select').each(function() {
                    var valor = $(this).val();
                    if (valor) {
                        if (nombreVariacion) nombreVariacion += ' + ';
                        var label = $(this).find('option[value="' + valor + '"]').text();
                        nombreVariacion += label;
                    }
                });
            }
            
            if (!nombreVariacion) return;
            
            if (verificandoCodigos) return;
            verificandoCodigos = true;
            
            // Mostrar mensaje de carga
            $(this).find('.single_variation_wrap .woocommerce-variation-price').before(
                '<div class="wc-codigos-mensaje" style="padding: 10px; margin: 10px 0; background: #f8f8f8;">' +
                '<span class="spinner is-active" style="float: left; margin-right: 5px;"></span>' +
                'Verificando disponibilidad de códigos para: ' + nombreVariacion + '...' +
                '</div>'
            );
            
            // Hacer la petición AJAX
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'verificar_codigos_disponibles',
                    nombre_variacion: nombreVariacion,
                    security: '<?php echo wp_create_nonce("verificar_codigos_nonce"); ?>'
                },
                success: function(response) {
                    $('.wc-codigos-mensaje').remove();
                    
                    if (response.success) {
                        if (response.data.disponible) {
                            // Mostrar mensaje de códigos disponibles
                            $('.single_variation_wrap .woocommerce-variation-price').before(
                                '<div class="wc-codigos-disponibles woocommerce-message" style="padding: 10px; margin: 10px 0;">' +
                                '✅ ' + response.data.mensaje + ' (' + response.data.cantidad + ' códigos disponibles)' +
                                '</div>'
                            );
                        } else {
                            // Mostrar mensaje de NO disponibles
                            $('.single_variation_wrap .woocommerce-variation-price').before(
                                '<div class="wc-codigos-no-disponibles woocommerce-error" style="padding: 10px; margin: 10px 0;">' +
                                '❌ ' + response.data.mensaje +
                                '</div>'
                            );
                            
                            // Deshabilitar el botón de añadir al carrito
                            $('.single_add_to_cart_button').prop('disabled', true)
                                                         .css('opacity', '0.5');
                        }
                    } else {
                        console.error('Error en la respuesta:', response);
                    }
                    verificandoCodigos = false;
                },
                error: function() {
                    $('.wc-codigos-mensaje').remove();
                    console.error('Error en la petición AJAX');
                    verificandoCodigos = false;
                }
            });
        });
        
        // Resetear cuando se cambia la variación
        $('.variations_form').on('reset_data', function() {
            $('.wc-codigos-mensaje, .wc-codigos-disponibles, .wc-codigos-no-disponibles').remove();
            $('.single_add_to_cart_button').prop('disabled', false)
                                         .css('opacity', '1');
        });
        
        // También resetear cuando se cambia cualquier atributo
        $('.variations select').on('change', function() {
            $('.wc-codigos-mensaje, .wc-codigos-disponibles, .wc-codigos-no-disponibles').remove();
            $('.single_add_to_cart_button').prop('disabled', false)
                                         .css('opacity', '1');
        });
    });
    </script>
    <?php
}

// 2. Función AJAX para verificar códigos disponibles basados en el nombre de variación
// add_action('wp_ajax_verificar_codigos_disponibles', 'verificar_codigos_disponibles_callback');
// add_action('wp_ajax_nopriv_verificar_codigos_disponibles', 'verificar_codigos_disponibles_callback');
function verificar_codigos_disponibles_callback() {
    // Verificar nonce para seguridad
    check_ajax_referer('verificar_codigos_nonce', 'security');
    
    if (!isset($_POST['nombre_variacion']) || empty($_POST['nombre_variacion'])) {
        wp_send_json_error(array('mensaje' => 'Nombre de variación no recibido'));
        wp_die();
    }
    
    $nombre_variacion = sanitize_text_field($_POST['nombre_variacion']);
    
    global $wpdb;
    $tabla_codigos = $wpdb->prefix . 'wc_codes_extras';
    
    // Consulta para contar códigos disponibles donde el campo "tipo" coincide con el nombre de la variación
    $cantidad = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_codigos} 
         WHERE tipo = %s 
         AND activo = 1",
        $nombre_variacion
    ));
    
    // Si no encuentra exactamente, intenta búsqueda parcial
    if ($cantidad === null || $cantidad === 0) {
        // Intenta buscar coincidencias parciales (por si hay espacios o formato diferente)
        $cantidad = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_codigos} 
             WHERE REPLACE(tipo, ' ', '') LIKE %s 
             AND activo = 1",
            '%' . str_replace(' ', '', $nombre_variacion) . '%'
        ));
    }
    
    // Depuración (elimina esto en producción)
    // error_log("Buscando tipo: " . $nombre_variacion);
    // error_log("Cantidad encontrada: " . $cantidad);
    
    $disponible = ($cantidad > 0);
    
    if ($disponible) {
        $mensaje = sprintf(__('Hay códigos disponibles para: %s'), $nombre_variacion);
    } else {
        $mensaje = sprintf(__('Lo sentimos, no hay códigos disponibles para: %s'), $nombre_variacion);
    }
    
    wp_send_json_success(array(
        'disponible' => $disponible,
        'cantidad' => $cantidad ? intval($cantidad) : 0,
        'mensaje' => $mensaje,
        'nombre_buscado' => $nombre_variacion
    ));
}

// 3. Función para verificar antes de añadir al carrito (seguridad adicional)
// add_filter('woocommerce_add_to_cart_validation', 'verificar_codigos_antes_carrito', 10, 3);
function verificar_codigos_antes_carrito($passed, $product_id, $quantity) {
    // Obtener el nombre de la variación seleccionada
    $nombre_variacion = '';
    
    // Intentar obtener de los atributos seleccionados
    if (isset($_POST['variation_id']) && $_POST['variation_id'] > 0) {
        $variation_id = intval($_POST['variation_id']);
        $variation = wc_get_product($variation_id);
        
        if ($variation) {
            // Obtener atributos de la variación
            $attributes = $variation->get_attributes();
            $nombre_variacion = implode(' + ', array_values($attributes));
            
            // Si no funciona, intentar otra forma
            if (empty($nombre_variacion)) {
                $nombre_variacion = $variation->get_name();
            }
        }
    }
    
    // Si aún no tenemos el nombre, usar el valor del campo de atributos
    if (empty($nombre_variacion) && isset($_POST['attribute_pa_tipo'])) {
        $nombre_variacion = sanitize_text_field($_POST['attribute_pa_tipo']);
    }
    
    if (empty($nombre_variacion)) {
        return $passed; // No podemos verificar sin el nombre
    }
    
    global $wpdb;
    $tabla_codigos = $wpdb->prefix . 'wc_codes_extras';
    
    // Verificar códigos disponibles
    $cantidad = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_codigos} 
         WHERE tipo = %s 
         AND activo = 1",
        $nombre_variacion
    ));
    
    if ($cantidad < $quantity) {
        wc_add_notice(
            sprintf(
                __('Lo sentimos, solo hay %d código(s) disponible(s) para: %s', 'tu-textdomain'),
                $cantidad,
                $nombre_variacion
            ),
            'error'
        );
        return false;
    }
    
    return $passed;
}

// 4. Función para mostrar códigos disponibles en la página del producto (opcional)
// add_action('woocommerce_single_product_summary', 'mostrar_info_codigos_variaciones', 25);
function mostrar_info_codigos_variaciones() {
    global $product;
    
    if (!$product->is_type('variable')) return;
    
    echo '<div class="info-codigos-variaciones" style="margin: 15px 0; padding: 10px; background: #f5f5f5; border-radius: 4px;">';
    echo '<p><strong>Información de disponibilidad:</strong></p>';
    echo '<p>Selecciona una función/horario para verificar la disponibilidad de códigos.</p>';
    echo '</div>';
}

// 5. Estilos CSS para mejorar la apariencia
// add_action('wp_head', 'estilos_verificacion_codigos');
function estilos_verificacion_codigos() {
    if (!is_product()) return;
    ?>
    <style>
    .wc-codigos-mensaje {
        border-left: 3px solid #0073aa;
        animation: fadeIn 0.3s ease-in;
        font-size: 14px;
    }
    .wc-codigos-disponibles {
        border-left: 3px solid #46b450;
        animation: fadeIn 0.3s ease-in;
        font-size: 14px;
    }
    .wc-codigos-no-disponibles {
        border-left: 3px solid #dc3232;
        animation: fadeIn 0.3s ease-in;
        font-size: 14px;
    }
    .info-codigos-variaciones {
        font-size: 13px;
        color: #555;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
    </style>
    <?php
}

// 6. Función de depuración para ver los nombres de variaciones (opcional - eliminar en producción)
// add_action('wp_footer', 'depurar_nombres_variaciones', 100);
function depurar_nombres_variaciones() {
    if (!is_product()) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Solo para depuración - mostrar en consola
        $('.variations_form').on('found_variation', function(event, variation) {
            console.log('Variación encontrada:', variation);
            console.log('Nombre completo:', variation.variation_description || variation.attributes_string);
            console.log('Atributos:', variation.attributes);
        });
    });
    </script>
    <?php
}

// Agrega esto temporalmente para depurar
// add_action('init', 'depurar_tabla_codigos');
function depurar_tabla_codigos() {
    if (is_admin() && current_user_can('manage_options')) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'wc_codes_extras';
        
        // Ver valores únicos en el campo "tipo"
        $tipos = $wpdb->get_col("SELECT DISTINCT tipo FROM {$tabla} WHERE activo = 1");
        error_log('Tipos disponibles en la tabla: ' . print_r($tipos, true));
    }
}

/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */

/***************************************************************************** 
 *   W A L L E T
 */

// Cargar módulos de administración
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/wallet-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/settings-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-widget.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/control-shortcodes.php';

// Añadir CSS para admin
add_action('admin_enqueue_scripts', 'bono_credit_admin_styles');

function bono_credit_admin_styles($hook) {
    if (strpos($hook, 'bono-credit') !== false) {
        wp_enqueue_style(
            'bono-admin-css',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css'
        );
    }
}

/////////////////////////////////////////////////////////////////////////////////////////

// Registrar gateway
add_filter('woocommerce_payment_gateways', function($gateways) {
    $gateways[] = 'WC_Bono_Credit_Gateway';
    return $gateways;
});

// Cargar archivos
add_action('plugins_loaded', function() {
    if (class_exists('WC_Payment_Gateway')) {
        require_once plugin_dir_path(__FILE__) . 'includes/gateways/class-bono-credit-gateway.php';
        require_once plugin_dir_path(__FILE__) . 'includes/hooks/credit-discount-hook.php';
        require_once plugin_dir_path(__FILE__) . 'includes/hooks/process-credit-after-payment.php';

        // Cargar hooks simples
        require_once plugin_dir_path(__FILE__) . 'includes/hooks/credit-checkout-simple.php';
        require_once plugin_dir_path(__FILE__) . 'includes/hooks/process-credit-simple.php';
    }
});

/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */


/***************************************************************************** 
 *   A D M I N I S T R A D O R
 */

add_filter( 'woocommerce_loop_add_to_cart_link', 'remove_select_options_button', 10, 2 );
function remove_select_options_button( $html, $product ) {
  if ( $product && $product->is_type( 'variable' ) ) {
    return '';
  }
  return $html;
}

function buscar_solo_en_productos($query) {
    if (!is_admin() && $query->is_search() && $query->is_main_query()) {
        $query->set('post_type', 'product'); // Solo buscar en productos
    }
}
add_action('pre_get_posts', 'buscar_solo_en_productos');


////////////////////////////////////////////////////////////
// Restringir acceso al admin solo para ciertos roles
function restringir_acceso_admin() {
    if (is_admin() && !defined('DOING_AJAX')) {
        $usuario_actual = wp_get_current_user();
        $roles_permitidos = array('administrator', 'auxiliar_bonospremium');

        if (!array_intersect($roles_permitidos, (array) $usuario_actual->roles)) {
            wp_redirect(home_url('/mi-cuenta/')); // Redirige a "mi-cuenta" o donde prefieras
            exit;
        }
    }
}
add_action('init', 'restringir_acceso_admin');

// Asegurar que los usuarios con rol permitido vean la barra de administración
function mostrar_barra_admin($mostrar) {
    $usuario_actual = wp_get_current_user();
    $roles_permitidos = array('administrator', 'auxiliar_bonospremium');

    if (array_intersect($roles_permitidos, (array) $usuario_actual->roles)) {
        return true;
    }
    return false;
}
add_filter('show_admin_bar', 'mostrar_barra_admin');

// Bloquear acceso directo a wp-login.php
function bloquear_acceso_wp_login() {
    if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false && $_SERVER['REQUEST_METHOD'] == 'GET') {
        wp_redirect(home_url('/mi-cuenta/')); // Cambia '/mi-cuenta/' por la URL a la que quieras redirigir
        exit;
    }
}
add_action('init', 'bloquear_acceso_wp_login');

function custom_hello_elementor_viewport_content() {
	return 'width=device-width, initial-scale=1.0, maximum-scale=1.0,user-scalable=0';
}
add_filter( 'hello_elementor_viewport_content', 'custom_hello_elementor_viewport_content' );

add_filter( 'auto_update_core_send_email', '__return_false' );
add_filter( 'auto_plugin_update_send_email', '__return_false' );
add_filter( 'auto_theme_update_send_email', '__return_false' );

wp_register_script( 'bp_simple-modal-js', plugin_dir_url( __FILE__ ) . 'librerias/simpleModal/jquery.simple-modal.js', array( 'jquery' ) );
wp_enqueue_script( 'bp_simple-modal-js' );
wp_enqueue_style( 'bp-simple-modal-css', plugin_dir_url( __FILE__ ) . 'librerias/simpleModal/jquery.simple-modal.css', false, '1.4', 'all');
wp_enqueue_style( 'bonos-premium-css', plugin_dir_url( __FILE__ ) . 'woocommerce-bonospremium.css', false, '1.4', 'all');

// DESACTIVAR ALERTAS DE NOTIFICACICONES PARA TODOS LOS USUARIOS
function we_hide_update_nag() {
    remove_action( 'admin_notices', 'update_nag', 3 );
}
add_action('admin_menu','we_hide_update_nag');

/* AÑADIMOS LOS SCRIPT AL WP */
add_action('admin_enqueue_scripts', 'woocommerce_bonos_premium_add_script_wp_head');
function woocommerce_bonos_premium_add_script_wp_head() {
    wp_register_script( 'bonospremium-script', plugin_dir_url( __DIR__ ) . '/woocommerce-bonospremium/assets/js/woocommerce-bonospremium.js', array(), '1.0.0', true );
    wp_enqueue_script( 'bonospremium-script' );
}

// FUNCIONES AJAX
add_action('wp_ajax_my_ajax_action', 'bonospremium_ajax_function');
add_action('wp_ajax_nopriv_my_ajax_action', 'bonospremium_ajax_function');
function bonospremium_ajax_function() {
    global $woocommerce, $post;

    $RUTA_QR    = WP_PLUGIN_DIR . '/woocommerce-bonospremium';
	$URL_QR     = ABSPATH . 'qrProductos';
    $IDPRODUCTO = $_POST['idProducto'];

    include $RUTA_QR . '/librerias/phpqrcode/qrlib.php';

    $tempDir             = $RUTA_QR . '/qrProductos';
    $fileName            = 'qr_'.$IDPRODUCTO.'.png';
    $pngAbsoluteFilePath = $tempDir."/".$fileName;

    QRcode::png($IDPRODUCTO, $pngAbsoluteFilePath, QR_ECLEVEL_L, 10, 1);

    $IMAGEN_QR = $URL_QR . "/" . $fileName;

    $response = array('imagen' => $IMAGEN_QR, 'message' => 'Request received with data: ' . $IDPRODUCTO);
    wp_send_json($response);
    
    exit();
}

function my_acf_init() {
    acf_update_setting('google_api_key', 'AIzaSyDz9pICivQgezA8sJUA8qOxzfexbCXodV0');
}
add_action('acf/init', 'my_acf_init');


/***************************************************************************** 
 *   W O O C O M M E R C E 
 */

////////////////////////////////////////////////////////////////////////////////////////////////
function disable_autodraft_creation() {
    remove_action('wp_insert_post', 'wp_save_post_revision');
}
add_action('init', 'disable_autodraft_creation');

function track_new_post_creation($post_ID, $post, $update) {
    if ($update) return; // Solo rastrear nuevos posts, no actualizaciones

    $user_id = get_current_user_id();
    $user_info = get_userdata($user_id);
    $username = $user_info ? $user_info->user_login : 'Sistema';

    $log_entry = date('Y-m-d H:i:s') . " - Nuevo ID: $post_ID, Tipo: {$post->post_type}, Creado por: $username \n";
    
    error_log($log_entry, 3, WP_CONTENT_DIR . '/new_post_log.txt'); // Guarda el log en wp-content/
}
add_action('wp_insert_post', 'track_new_post_creation', 10, 3);

// AÑADIR UN NUEVO ESTADO
add_action( 'init', 'registrar_estado_cambiado_wc' );
function registrar_estado_cambiado_wc() {
    register_post_status( 'wc-cambiado', array(
        'label'                     => 'Cambiado',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Cambiado (%s)', 'Cambiado (%s)' )
    ) );
}
add_filter( 'wc_order_statuses', 'agregar_estado_cambiado_wc' );
function agregar_estado_cambiado_wc( $estados ) {
    $estados['wc-cambiado'] = 'Cambiado';
    return $estados;
}


// CAMBIAMOS EL NOMBRE DE LOS ESTADOS DE WC A UNOS PERSONALIZADOS
add_filter( 'wc_order_statuses', 'custom_woocommerce_order_statuses' );
function custom_woocommerce_order_statuses( $order_statuses ) {
    // $order_statuses['wc-pending']    = 'Pendiente de Pago';
    $order_statuses['wc-processing'] = 'Comprado';
   //  $order_statuses['wc-on-hold']    = 'En Espera';
   $order_statuses['wc-completed']  = 'Canjeado';
   //  $order_statuses['wc-cancelled']  = 'Cancelado';
   //  $order_statuses['wc-refunded']   = 'Reembolsado';
   //  $order_statuses['wc-failed']     = 'Fallido';
    
    return $order_statuses;
}


// add_action( 'woocommerce_review_order_after_submit', 'ts_review_order_before_submit' );
function ts_review_order_before_submit(){
    $total = WC()->cart->total;

    echo '<div
                data-pp-message
                data-pp-style-layout="text"
                data-pp-style-logo-type="inline"
                data-pp-style-text-color="black"
                data-pp-style-text-size="12"
                data-pp-amount='.$total.'
                data-pp-placement=payment> 
            </div>';
}

function divComoLlegar() {
    global $product;

    $direccionNew   = preg_replace('/\s+/', '+', $product->get_meta('direccion'));
    $NOMBRE_EMPRESA = $product->get_meta('nombre_establecimiento');
    $TEL_EMPRESA    = $product->get_meta('telefono');

    $product_url   = get_permalink($product->get_id());
    $product_title = get_the_title($product->get_id());
    
    /*echo '<div class="social-sharing-buttons">
            <a class="button twitter" href="https://twitter.com/share?url='.urlencode($product_url).'&text='.urlencode($product_title).'" target="_blank" rel="noopener noreferrer">
                <svg style="height: 14px;" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0,0,256,256" width="50px" height="50px" fill-rule="nonzero"><g fill="#ffffff" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal"><g transform="scale(5.12,5.12)"><path d="M5.91992,6l14.66211,21.375l-14.35156,16.625h3.17969l12.57617,-14.57812l10,14.57813h12.01367l-15.31836,-22.33008l13.51758,-15.66992h-3.16992l-11.75391,13.61719l-9.3418,-13.61719zM9.7168,8h7.16406l23.32227,34h-7.16406z"></path></g></g></svg>
            </a>
            <a class="button facebook" href="https://www.facebook.com/sharer.php?u='.urlencode($product_url).'" target="_blank" rel="noopener noreferrer">
                Facebook
            </a>
            <!-- Add more social media buttons as needed -->
        </div>';*/

    echo '<div class="col-xs-12 col-sm-12 col-md-12 ficha-bono">
	        <div class="col-xs-12 no-padding">
                <div class="nombre-dir">
                    <span style="color: #767676;">'.$NOMBRE_EMPRESA.'.</span>
                    <span style="color: #767676;">'.$product->get_meta('direccion').'</span>
                </div>
                <div class="web-tfno">
                    <a href="tel:+34'.$TEL_EMPRESA.'" class="web"><b>'.$TEL_EMPRESA.'</b></a> | 
                    <a href="https://www.google.com/maps/dir/?api=1&amp;origin=My+Location&amp;destination='.$direccionNew.'" target="_blank"><b>¿Cómo llego hasta allí?</b></a>
                </div>
            </div>
            <div id="lean_overlay"></div>
        </div>';

    echo "<br>";
}
add_shortcode('divComoLlegar', 'divComoLlegar');

// PARAMOS EL ENVIO DEL EMAIL DE WP PARA NUEVOS USUARIOS DESDE EL ADMIN
remove_action('register_new_user', 'wp_send_new_user_notifications');
// CREAMOS NUESTRA PLANTILLA DE ENVIO DE EMAIL DESDE EL ADMIN
add_filter('wp_new_user_notification_email', 'cwpai_custom_user_reg_admin_email', 10, 3);
function cwpai_custom_user_reg_admin_email($wp_new_user_notification_email, $user, $blogname) {
    // Poem for new user notification email
    $poem = '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#fff;border:1px solid #dedede;border-radius:3px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td align="center" valign="top">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#039cdc;color:#fff;border-bottom:0;font-weight:bold;line-height:100%;vertical-align:middle;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;border-radius:3px 3px 0 0" bgcolor="#039cdc">
                                <tbody>
                                    <tr>
                                        <td style="padding:36px 48px;display:block">
                                            <h1 style="font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:30px;font-weight:300;line-height:150%;margin:0;text-align:left;color:#fff;background-color:inherit" bgcolor="inherit">Bienvenido a BonosPremium</h1>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                        </td>
                    </tr>
                    <tr>
                        <td align="center" valign="top">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tbody>
                                    <tr>
                                        <td valign="top" style="background-color:#fff" bgcolor="#fff">
                                            <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                <tbody>
                                                    <tr>
                                                        <td valign="top" style="padding:48px 48px 32px">
                                                            <div style="color:#636363;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:14px;line-height:150%;text-align:left" align="left">
                                                                <p style="margin:0 0 16px">Hola '.$user->user_login.',</p>
                                                                <p style="margin:0 0 16px">Ya formas parte de BonosPremium. Su nombre de usuario es <strong>'.$user->user_login.'</strong>. Puede acceder a su área de gestion de bonos, cambiar su contraseña y más en: <a href="https://bonospremium.com/admin/" style="color:#039cdc;font-weight:normal;text-decoration:underline" target="_blank">https://bonospremium.com/admin/</a></p>
                                                                <p style="margin:0 0 16px">Esperamos verte pronto.</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>';

    // Set the message, subject and headers for the email
    $wp_new_user_notification_email['message'] = $poem;
    $wp_new_user_notification_email['subject'] = "[{$blogname}] Datos de acceso";
    $wp_new_user_notification_email['headers'] = 'Content-Type: text/html; charset=UTF-8';

    return $wp_new_user_notification_email;
}

// ESTA FUNCION SE EJECUTA CUANDO CAMBIA EL ESTADO DE UN PEDIDO
function bonosPremium_on_order_status_changed( $order_id, $from_status, $to_status ) {
    //global $wpdb;
    
    if ( ($from_status === 'pending') && ($to_status === 'processing') ) {
        bonospremium_payment_complete( $order_id );
    }
}
add_action( 'woocommerce_order_status_changed', 'bonosPremium_on_order_status_changed', 10, 3 );
 
// FUNCIONES PARA LA CREACION DE UN QR POR PRODUCTO
// add_action( 'woocommerce_thankyou', 'bonospremium_payment_complete', 10);
function bonospremium_payment_complete( $order_id ) {
    global $wpdb;

    $exite = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(orderId) AS NUM FROM ". $wpdb->prefix ."wc_pedidos_item WHERE orderId = ".$order_id ) );
    if($exite->NUM == 0){
        
        $user_id = get_current_user_id();
        $order   = wc_get_order( $order_id );

        $order_items = $order->get_items();

        $ESTADO             = cambioEstadoPedidoGetorBonos($order->get_status());
        $FECHA_CREACION     = $order->get_date_created()->date('Y-m-d H:i:s');

        $SQL_FECHA_ORDER    = "SELECT distinct ID as order_id, IF(post_status = 'wc-completed', post_modified_gmt, null ) as canjeadoT FROM hx4p_posts WHERE post_type = 'shop_order' AND ID = $order_id";
        $ARRAY_FECHA_ORDER  = $wpdb->get_row( $wpdb->prepare( $SQL_FECHA_ORDER ) ); 
        $FECHA_MODIFICACION = $ARRAY_FECHA_ORDER->canjeadoT;

        $RUTA_QR    = WP_PLUGIN_DIR . '/woocommerce-bonospremium';
		$RUTA_IMGS  = ABSPATH . '/qrProductos';
        $ARRAY_NAME_FILE = [];

        include $RUTA_QR . '/librerias/phpqrcode/qrlib.php';

        foreach( $order_items as $item_id => $item ){

            $item_id   = $item->get_id();
            $item_data = $item->get_data();
            $product   = $item->get_product();
            
            $product_name         = $item->get_name();
            $product_id           = $item->get_product_id();
            $precio               = $product->get_price();
            $precioRegular        = $product->get_regular_price();
            $precioOferta         = $product->get_sale_price();

            $product_variation_id = $item_data['variation_id'];
            $cantidad             = $item_data['quantity'];

            $product_instance = wc_get_product($product_id);
            $product_full_description  = $product_instance->get_description();
            $product_short_description = $product_instance->get_short_description();

            $empresaId   = $product_instance->get_meta('empresa_colaboradora');
            $comercialId = $product_instance->get_meta('comercial');

            $ARRAY_CODE = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM hx4p_postmeta WHERE post_id = $order_id AND meta_key = '_barcode_text'" ) ); 
            $QR_CODE_PEDIDO = ($empresaId == 56612)
                ? "Cine"
                : (($empresaId == 185053)
                    ? "WEGOO"
                    : $ARRAY_CODE->meta_value);  // 185053 => WEGOO | 56612 => CINES YELMO

            for($i=1; $i<=$item_data['quantity']; $i++){

                $QR_CODE = substr(md5(uniqid(mt_rand(), true)) , 0, 12);
                $result = $wpdb->query(
                    $wpdb->prepare("INSERT INTO ". $wpdb->prefix ."wc_pedidos_item (orderId, productId, empresaId, comercialId, productName, productDetail, userId, cantidad, precio, precioRegular, precioOferta, qrCode, qrCodePedido, fechaCreacion, fechaModificacion, estado) VALUES ( %d, %d, %d, %d, %s, %s, %d, %d, %d, %d, %s, %s, %s, %s, %s, %s)", $order_id, $product_id, $empresaId, $comercialId, $product_name, $product_full_description, $user_id, $cantidad, $precio, $precioRegular, $precioOferta, $QR_CODE, $QR_CODE_PEDIDO, $FECHA_CREACION, $FECHA_MODIFICACION, $ESTADO)
                );

                if ($result === false) {
                    error_log('Error en la inserción: ' . $wpdb->last_error);
                    // O mostrar directamente si estás en desarrollo
                    echo 'Error: ' . $wpdb->last_error;
                }
                
                $lastid = $wpdb->insert_id;

                $fileName               = 'qr_'.$QR_CODE.'.png';
                $fileNameSvg            = 'qr_'.$QR_CODE.'.svg';
                $pngAbsoluteFilePath    = $RUTA_IMGS ."/". $fileName;
                $pngAbsoluteFilePathSvg = $RUTA_IMGS ."/". $fileNameSvg;

                $filename = $RUTA_QR.'/'.$fileName;

                if (!file_exists($pngAbsoluteFilePath)) {

                    QRcode::png($QR_CODE, $pngAbsoluteFilePath, QR_ECLEVEL_L, 10, 1, false);
                    
                    crearPdf($order_id, $QR_CODE, "voucher_".$order_id."_".$QR_CODE.".pdf");
                    array_push($ARRAY_NAME_FILE, "voucher_".$order_id."_".$QR_CODE);
                }
            }
        }

        enviarEmail($order_id, $ARRAY_NAME_FILE);
    }
}

function insertar_pedido_db( $order_id ) {
    global $wpdb;

    $exite = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(orderId) AS NUM FROM ". $wpdb->prefix ."wc_pedidos_item WHERE orderId = ".$order_id ) );
    if($exite->NUM == 0){
        
        $user_id = get_current_user_id();
        $order   = wc_get_order( $order_id );

        $order_items = $order->get_items();

        $ESTADO             = cambioEstadoPedidoGetorBonos($order->get_status());
        $FECHA_CREACION     = $order->get_date_created()->date('Y-m-d H:i:s');

        $SQL_FECHA_ORDER    = "SELECT distinct ID as order_id, IF(post_status = 'wc-completed', post_modified_gmt, null ) as canjeadoT FROM hx4p_posts WHERE post_type = 'shop_order' AND ID = $ID_ORDER";
        $ARRAY_FECHA_ORDER  = $wpdb->get_row( $wpdb->prepare( $SQL_FECHA_ORDER ) ); 
        $FECHA_MODIFICACION = $ARRAY_FECHA_ORDER->canjeadoT;

        $RUTA_QR    = WP_PLUGIN_DIR . '/woocommerce-bonospremium';
        // $RUTA_IMGS  = WP_PLUGIN_DIR . '/woocommerce-bonospremium/qrProductos';
		$RUTA_IMGS  = ABSPATH . '/qrProductos';
        $ARRAY_NAME_FILE = [];

        include $RUTA_QR . '/librerias/phpqrcode/qrlib.php';

        foreach( $order_items as $item_id => $item ){

            $item_id   = $item->get_id();
            $item_data = $item->get_data();
            $product   = $item->get_product();
            
            $product_name         = $item->get_name();
            $product_id           = $item->get_product_id();
            $precio               = $product->get_price();
            $precioRegular        = $product->get_regular_price();
            $precioOferta         = $product->get_sale_price();

            $product_variation_id = $item_data['variation_id'];
            $cantidad             = $item_data['quantity'];

            $product_instance = wc_get_product($product_id);
            $product_full_description  = $product_instance->get_description();
            $product_short_description = $product_instance->get_short_description();

            $empresaId   = $product_instance->get_meta('empresa_colaboradora');
            $comercialId = $product_instance->get_meta('comercial');

            $ARRAY_CODE = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM hx4p_postmeta WHERE post_id = $order_id AND meta_key = '_barcode_text'" ) ); 
            $QR_CODE_PEDIDO = $ARRAY_CODE->meta_value;

            for($i=1; $i<=$item_data['quantity']; $i++){

                $QR_CODE = substr(md5(uniqid(mt_rand(), true)) , 0, 12);
                $wpdb->query(
                    $wpdb->prepare("INSERT INTO ". $wpdb->prefix ."wc_pedidos_item (orderId, productId, empresaId, comercialId, productName, productDetail, userId, cantidad, precio, precioRegular, precioOferta, qrCode, qrCodePedido, fechaCreacion, fechaModificacion, estado) VALUES ( %d, %d, %d, %d, %s, %s, %d, %d, %d, %d, %s, %s, %s, %s, %s, %s)", $order_id, $product_id, $empresaId, $comercialId, $product_name, $product_full_description, $user_id, $cantidad, $precio, $precioRegular, $precioOferta, $QR_CODE, $QR_CODE_PEDIDO, $FECHA_CREACION, $FECHA_MODIFICACION, $ESTADO)
                );
                
                $lastid = $wpdb->insert_id;

                $fileName               = 'qr_'.$QR_CODE.'.png';
                $fileNameSvg            = 'qr_'.$QR_CODE.'.svg';
                $pngAbsoluteFilePath    = $RUTA_IMGS ."/". $fileName;
                $pngAbsoluteFilePathSvg = $RUTA_IMGS ."/". $fileNameSvg;

                $filename = $RUTA_QR.'/'.$fileName;

                if (!file_exists($pngAbsoluteFilePath)) {

                    QRcode::png($QR_CODE, $pngAbsoluteFilePath, QR_ECLEVEL_L, 10, 1, false);
                    
                    crearPdf($order_id, $QR_CODE, "voucher_".$order_id."_".$QR_CODE.".pdf");
                    array_push($ARRAY_NAME_FILE, "voucher_".$order_id."_".$QR_CODE);
                }
            }
        }
    }
}

add_action( 'woocommerce_order_status_changed', 'cambioEstadoPedido', 10, 3);
function cambioEstadoPedido($order_id){
    global $wpdb;

    $order = wc_get_order( $order_id );

    switch ($order->get_status()) {
        case "pending":
            $ESTADO = "Pendiente";
            break;
        case "processing":
            $ESTADO = "No Canjeado";
            break;
        case "on-hold":
            $ESTADO = "Detenido";
            break;
        case "completed":
            $ESTADO = "Canjeado";
            break;
        case "cancelled":
            $ESTADO = "Cancelado";
            break;
        case "refunded":
            $ESTADO = "Reembolsado";
            break;
        case "failed":
            $ESTADO = "Fallido";
            break;
        case "lapsed":
            $ESTADO = "Caducado";
            break;
    }

    $wpdb->update(
        'hx4p_wc_pedidos_item',
        array(
            'estado' => $ESTADO,
        ),
        array(
            'orderId' => $order_id,
        )
    );
}

function bonospremium_init(){

    // $args = array('limit' => -1, 'status' => array('wc-completed', 'wc-on-hold', 'wc-processing'));
    $args = array('limit' => -1, 'status' => array('wc-pending', 'wc-cancelled', 'wc-refunded', 'wc-failed', 'wc-lapsed'));
    $orders = wc_get_orders($args);

    foreach ( $orders as $order ) {
        bonospremium_insert_order( $order );
    }
}

function bonospremium_insert_order( $order ) {
    global $wpdb;

    $order_items = $order->get_items();

    $ID_ORDER           = $order->get_id();
    $ESTADO             = $order->get_status();
    $FECHA_CREACION     = $order->get_date_created()->date('Y-m-d H:i:s');
    // $FECHA_MODIFICACION = $order->get_date_modified()->date('Y-m-d H:i:s');

    $SQL_FECHA_ORDER    = "SELECT distinct ID as order_id, IF(post_status = 'wc-completed', post_modified_gmt, null ) as canjeadoT FROM hx4p_posts WHERE post_type = 'shop_order' AND ID = $ID_ORDER";
    $ARRAY_FECHA_ORDER  = $wpdb->get_row( $wpdb->prepare( $SQL_FECHA_ORDER ) ); 
    $FECHA_MODIFICACION = $ARRAY_FECHA_ORDER->canjeadoT;

    foreach( $order_items as $item_id => $item ){

        $item_id   = $item->get_id();
        $item_data = $item->get_data();
        $product   = $item->get_product();
        
        $product_name         = $item->get_name();
        $product_id           = $item->get_product_id();
        $precio               = $product->get_price();
        $precioRegular        = $product->get_regular_price();
        $precioOferta         = $product->get_sale_price();

        $product_variation_id = $item_data['variation_id'];
        $cantidad             = $item_data['quantity'];

        $ARRAY_CODE = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM hx4p_postmeta WHERE post_id = $ID_ORDER AND meta_key = '_barcode_text'" ) ); 
        $QR_CODE_PEDIDO = $ARRAY_CODE->meta_value;

        $product_instance = wc_get_product($product_id);
        $product_full_description  = $product_instance->get_description();
        $product_short_description = $product_instance->get_short_description();

        $empresaId   = $product_instance->get_meta('empresa_colaboradora');
        $comercialId = $product_instance->get_meta('comercial');

        for($i=1; $i<=$item_data['quantity']; $i++){
            $QR_CODE = substr(md5(uniqid(mt_rand(), true)) , 0, 12);
            $wpdb->query(
                $wpdb->prepare("INSERT INTO ". $wpdb->prefix ."wc_pedidos_item (orderId, productId, empresaId, comercialId, productName, productDetail, userId, cantidad, precio, precioRegular, precioOferta, qrCode, qrCodePedido, fechaCreacion, fechaModificacion, estado) VALUES ( %d, %d, %d, %d, %s, %s, %d, %d, %d, %d, %s, %s, %s, %s, %s, %s)", $ID_ORDER, $product_id, $empresaId, $comercialId, $product_name, $product_full_description, $user_id, $cantidad, $precio, $precioRegular, $precioOferta, $QR_CODE, $QR_CODE_PEDIDO, $FECHA_CREACION, $FECHA_MODIFICACION, $ESTADO)
            );
        }
    }
}

// SELECTOR DE VARIACIONES
function mover_selector_variaciones_a_descripcion() {
    // Eliminar el selector de variaciones de su posición predeterminada
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

    // Agregar el selector de variaciones en la descripción completa
    add_action( 'woocommerce_after_single_product_summary', 'woocommerce_template_single_add_to_cart', 5 );
}
add_action( 'init', 'mover_selector_variaciones_a_descripcion' );

add_filter('woocommerce_available_variation', 'custom_variation_prices', 10, 3);

function custom_variation_prices($data, $product, $variation) {
    // Obtén los precios de la variación
    $regular_price = $variation->get_regular_price();
    $sale_price = $variation->get_sale_price();

    // Formatea los precios
    if ($sale_price && $sale_price < $regular_price) {
        $formatted_price = '<del>' . wc_price($regular_price) . '</del> <ins>' . wc_price($sale_price) . '</ins>';
    } else {
        $formatted_price = wc_price($regular_price);
    }

    // Añade el precio al array de variación
    $data['price_html'] = $formatted_price;

    return $data;
}




//////////////////////////////////////////////////////////
// Mostrar un solo precio para productos con variaciones en WooCommerce
add_filter('woocommerce_variable_sale_price_html', 'mostrar_precio_unico_producto_variable', 10, 2);
add_filter('woocommerce_variable_price_html', 'mostrar_precio_unico_producto_variable', 10, 2);
function mostrar_precio_unico_producto_variable($precio, $producto) {
    // Obtener las variaciones del producto
    $variaciones = $producto->get_children();
    if (!empty($variaciones)) {
        // Obtener los precios de las variaciones
        $precios = array_map(function($variation_id) {
            $variacion = wc_get_product($variation_id);
            return $variacion->get_price();
        }, $variaciones);

        // Filtrar precios vacíos y convertir a flotantes
        $precios = array_filter($precios);
        $precios = array_map('floatval', $precios);

        if (!empty($precios)) {
            // Mostrar el precio mínimo (puedes ajustarlo según tus necesidades)
            $precio_unico = min($precios);
            $precio = wc_price($precio_unico); // Formatear el precio
        }
    }

    return $precio;
}

add_filter('woocommerce_variable_sale_price_html', 'custom_variable_price_range', 10, 2);
add_filter('woocommerce_variable_price_html', 'custom_variable_price_range', 10, 2);
function custom_variable_price_range($price, $product) {
    // Obtén el rango de precios regular y en oferta
    $min_price = $product->get_variation_price('min', true);
    $max_price = $product->get_variation_price('max', true);
    $min_regular_price = $product->get_variation_regular_price('min', true);
    $max_regular_price = $product->get_variation_regular_price('max', true);

    if ($min_price !== $max_price) {
        // Si los precios varían, mostrar el rango
        // $price = '<del>' . wc_price($min_regular_price) . ' - ' . wc_price($max_regular_price) . '</del> <ins>' . wc_price($min_price) . ' - ' . wc_price($max_price) . '</ins>';
        $price = '<del>' . wc_price($min_regular_price) . '</del> <ins style="margin-left:20px; text-decoration: none;">' . wc_price($min_price) . '</ins>';
    } else {
        // Si no hay rango, mostrar el precio único
        if ($min_price < $min_regular_price) {
            // $price = '<del>' . wc_price($min_regular_price) . '</del> <ins>' . wc_price($min_price) . '</ins>';
        } else {
            // $price = wc_price($min_price);
        }
    }

    return $price;
}












////////////////////////////////////////////////////////////////
// EXTRAS FUNCIONES
////////////////////////////////////////////////////////////////
function cambioEstadoPedidoGetorBonos($ESTADO){
    switch($ESTADO)
    {
        case 'on-hold';
            $ESTADO = "Detenido";
            break;
        case 'pending';
            $ESTADO = "Pendiente";
            break;
        case 'processing';
            $ESTADO = "No Canjeado";
            break;
        case 'completed';
            $ESTADO = "Canjeado";
            break;
        case 'cancelled';
            $ESTADO = "Cancelado";
            break;
        case 'refunded';
            $ESTADO = "Reembolsado";
            break;
        case 'failed';
            $ESTADO = "Fallido";
            break;
    }

    return $ESTADO;
}

function enviarEmail($ORDERID, $NAME_ARRAY=[], $ENVIO=1){
    $ANO   = date("Y");
    $order = wc_get_order( $ORDERID );
    $data  = $order->get_data();

    $to           = $data['billing']['email'];
    $headers      = array('Content-Type: text/html; charset=UTF-8'); 
    $subject      = '¡Hemos recibido tu compra en BonosPremium!';
    $subjectAdmin = '[BonosPremium] Nueva compra #('.$ORDERID.')';

    ## BILLING INFORMATION:
    $billing_email      = $data['billing']['email'];
    $billing_phone      = $order_data['billing']['phone'];

    $billing_first_name = $data['billing']['first_name'];
    $billing_last_name  = $data['billing']['last_name'];
    $billing_company    = $data['billing']['company'];
    $billing_address_1  = $data['billing']['address_1'];
    $billing_address_2  = $data['billing']['address_2'];
    $billing_city       = $data['billing']['city'];
    $billing_state      = $data['billing']['state'];
    $billing_postcode   = $data['billing']['postcode'];
    $billing_country    = $data['billing']['country'];
    $billing_email      = $data['billing']['email'];
    $billing_phone      = $data['billing']['phone'];

    $NOMBRES      = $billing_first_name != "" ? $billing_first_name." ".$billing_last_name : "";
    $COMPANIA     = $billing_company != "" ? $billing_company."<br>" : "";
    $DIRECCION1   = $billing_address_1 != "" ? $billing_address_1."<br>" : "";
    $EMAIL        = $billing_email != "" ? $billing_email."<br>" : "";
    $TELEFONO     = $billing_phone != "" ? $billing_phone : "";
    $FECHA_PEDIDO = $order->get_date_modified()->date('d/m/Y');

    $DireccionFacturacion = '<p>'.$NOMBRES.'<br>'.$COMPANIA.' '.$DIRECCION1.' '.$EMAIL.' '.$TELEFONO.'</p>';

    $credit_amount = $order->get_meta('_bono_credit_amount', true);

    $trsBody_Pedidos = "";
    $SUB_TOTAL = 0;
    foreach ($data['line_items'] as $item) {
        $PRECIO_UNIDAD = $item['subtotal'] / $item["quantity"];
        $trsBody_Pedidos .= '<tr> <td>'.$item["name"].'</td> <td>'.$item["quantity"].'</td> <td>'.number_format($PRECIO_UNIDAD, 2).'€</td> </tr>';
        $SUB_TOTAL += $item['subtotal'];
    }

    $trsFoot_Pedidos  .= '<tr> <td colspan="2"><strong>Subtotal:</strong></td> <td>'.number_format($SUB_TOTAL, 2).'€</td> </tr>';

    foreach ($data['coupon_lines'] as $item1) {
        $trsFoot_Pedidos .= '<tr> <td colspan="2"><strong>Descuento ('.$item1['nominal_amount'].'%):</strong></td> <td> -'.number_format($item1['discount'], 2).'€</td> </tr>';
    }

    /*if(($credit_amount != 0) || ($credit_amount != "")){
        $trsFoot_Pedidos .= '<tr> <td colspan="2"><strong>Credito BonosPremium:</strong></td> <td> -'.number_format($credit_amount, 2).'€</td> </tr>';
    }*/

    $trsFoot_Pedidos .= '<tr> <td colspan="2"><strong>Total:</strong></td> <td>'.number_format($data['total'], 2).'€</td> </tr>';

    ob_start();
    include_once(WP_PLUGIN_DIR . "/woocommerce-bonospremium/templates/pedido_email.html");
    $PAGE_TPL = ob_get_contents();
    ob_end_clean();

    ob_start();
    include_once(WP_PLUGIN_DIR . "/woocommerce-bonospremium/templates/new_pedido_email.html");
    $NEW_PAGE_TPL = ob_get_contents();
    ob_end_clean();

    $ARRAY_TPL = array(
        "ano"                     => $ANO,
        "numPedido"               => $ORDERID,
        "nombre_Pedido"           => $NOMBRES,
        "fecha_Pedido"            => $FECHA_PEDIDO,
        "txtDireccionFacturacion" => $DireccionFacturacion,
        "trsBody_Pedidos"         => $trsBody_Pedidos,
        "trsFoot_Pedidos"         => $trsFoot_Pedidos,
    );

    $message    = parse_template($PAGE_TPL, $ARRAY_TPL);
    $messageNew = parse_template($NEW_PAGE_TPL, $ARRAY_TPL);

    $attachments = array();
    foreach($NAME_ARRAY as $item){
        // array_push($attachments, WP_PLUGIN_DIR . "/woocommerce-bonospremium/qrProductos/".$item.".pdf");
		array_push($attachments, ABSPATH . "/qrProductos/".$item.".pdf");
    }

    wp_mail( $to, $subject, $message, $headers, $attachments );
    
    if($ENVIO == 1){
        wp_mail( 'pedidos@bonospremium.com', $subjectAdmin, $messageNew, $headers, $attachments );
    }
}

function crearPdf($ORDERID, $QRCODE, $NAME_FILE=""){
    global $wp;
    global $wpdb;

    include_once 'librerias/dompdf/autoload.inc.php';

    $RUTA_IMGS  = ABSPATH . 'qrProductos';

    ob_start();
    include_once dirname( __FILE__ ) . '/templates/plantilla_qrcode.html';
    $PAGE_TPL = ob_get_contents();
    ob_end_clean();

    $IMG_LOGO = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/wp-content/uploads/2023/09/trans.png"));
	
	$order = wc_get_order($ORDERID);
	
	$FECHA_COMPRA = "";
    $purchase_date = $order->get_date_created(); // WC_DateTime
    if ( $purchase_date ) {
        $FECHA_COMPRA = '<p style="font-size: 10px;"><b>Fecha de la compra:</b> <i>'.$purchase_date->date( 'd/m/Y H:i:s' ).'</i></p>';
    }
	
	$CITA_SINO = $order->get_meta('_additional_wooccm0');
    $TXT_CITA  = $order->get_meta('_additional_wooccm1');
    $TEMA_CITA = $order->get_meta('_additional_wooccm2');

    $HTML_CITAS      = "";
    $HTML_IMAGEN     = "";
	$HTML_QR_OR_CINE = "";

    switch ($TEMA_CITA) {
        case "Día de la Madre":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_diadelamadre1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Día del Padre":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_diadelpadre1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Cumpleaños Hombre":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_cumpleanoshombre1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Cumpleaños Mujer":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_cumpleanosmujer1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Navidad":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_navidad1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Papá Noel":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_papanoel1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Reyes Magos":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_reyesmagos1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "San Valentin":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_sanvalentin1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
    }

    $HTML_CITAS  = $TXT_CITA == "" ? "" : ' <div style="margin: 20px;"> <blockquote style="width:100%; text-align:center; font-size: 20px; margin: 10px; font-style: italic;color: #a5a5a5;">"'.$TXT_CITA.'"</blockquote> </div>';
  
    $TICKETS = '<!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Bonos Premium</title>

                    <style>
                        @import url("https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap");

                        html, body {font-family: "Open Sans", sans-serif;}
                        li { text-align: start; margin-bottom: 10px; font-size: 14px; line-height: 13px;}
                        .text-container {
                            margin: auto;
                            width: 250px;
                            padding: 0px;
                            margin: 0px;
                            text-align: center;
                        }
                    </style>
                </head>
                <body>
                <!-- Contenedor Principal -->
                <div style="width: 100%; padding: 0px; font-family: Arial, sans-serif; background-color: #FFFFFF; border-radius: 0px; box-sizing: border-box;">
                    <!-- Encabezado -->
                    <div style="background-color: #019cdb; padding: 20px; border-radius: 0px; color: #FFFFFF; text-align: center; margin-top: 0px; margin-bottom: 0px;">
                        <img style="width: 400px; padding: 0px;" src="'.$IMG_LOGO.'" alt="">
                    </div>'.$HTML_IMAGEN;


    $registros = $wpdb->get_results( "SELECT * FROM ". $wpdb->prefix ."wc_pedidos_item WHERE qrCode = '$QRCODE'" );

    foreach ($registros as $key=>$value){
        $product_info   = wc_get_product( $value->productId );

        $CONDICIONES    = $product_info->get_meta('condiciones_generales');
        $NOMBRE_EMPRESA = $product_info->get_meta('nombre_establecimiento');

		$nombreImagen = ABSPATH . 'qrProductos/qr_'. $value->qrCode .'.png';
        $imagenBase64 = "data:image/png;base64," . base64_encode(file_get_contents($nombreImagen));


        $NEW_COLOR_DETAILS = preg_replace('/color:\s?#000000;?/i', "color: #8e8e8e;", $value->productDetail);

        $ARRAY_PRODUCTO   = explode(" - ", $value->productName);
        $NOMBRE_PRODUCTO  = $ARRAY_PRODUCTO[0];
        $TIPO_PRODUCTO    = isset($ARRAY_PRODUCTO[1]) ? "<p>(".$ARRAY_PRODUCTO[1].")</p>" : "";
		$STRING_CINE_TIPO = isset($ARRAY_PRODUCTO[1]) ? trim($ARRAY_PRODUCTO[1]) : "";
		
		///////////////////////////////////////////////////////////
		/* INCIO CODIGO PARA EL QR DEL CINE */
		if (preg_match('/\bCINE YELMO\b/i', $NOMBRE_EMPRESA)) {
			$TIPO_BUSCAR = $STRING_CINE_TIPO == "Entrada" ? "Entrada" : "Entrada + menú clásico";
				
			$ARRAY_POST = $wpdb->get_row( $wpdb->prepare( "SELECT codes FROM hx4p_wc_codes_cinema WHERE activo = 0 AND tipo = '$STRING_CINE_TIPO' AND orderId = 0 LIMIT 1" ) ); 
						
			$CODIDO_CINE      = $ARRAY_POST->codes;
			$cineImagen       = 'https://bonospremium.com/wp-content/uploads/2025/07/ticket_cine.png';
        	$cineImagenBase64 = "data:image/png;base64," . base64_encode(file_get_contents($cineImagen));
			
			// Actualizamos el estado del qr del cine
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE hx4p_wc_codes_cinema SET activo = %d, orderId = %d WHERE codes = %s",
					1,
					$ORDERID,
					$CODIDO_CINE
				)
			);
			
			$HTML_QR_OR_CINE = '<img src="'.$cineImagenBase64.'" style="width: 200px; height: auto; margin-top: 30px; padding: 0px; color: #11296b;"> <div class="text-container" style="width: 100%; text-align: center; font-size: 23px;letter-spacing: 1px; color: #11296b; padding: 0px; margin: 0px;font-family: monospace;">'.$CODIDO_CINE.'</div>';
			
		} else if (preg_match('/\bWEGOO\b/i', $NOMBRE_EMPRESA)) {
    
			$ID_PRODUCTO = $value->productId;

			// 1. Detectar si el ID es una variación o un producto padre
			$post_type = $wpdb->get_var( $wpdb->prepare( 
				"SELECT post_type FROM {$wpdb->posts} WHERE ID = %d", 
				$ID_PRODUCTO 
			) );

			$variation_ids = [];
			if ( $post_type === 'product_variation' ) {
				$variation_ids[] = $ID_PRODUCTO;
			} else {
				// Es producto padre: buscar todas sus variaciones publicadas
				$variaciones = $wpdb->get_col( $wpdb->prepare( 
					"SELECT ID FROM {$wpdb->posts} 
					 WHERE post_parent = %d AND post_type = 'product_variation' AND post_status = 'publish'", 
					$ID_PRODUCTO 
				) );
				$variation_ids = $variaciones;
			}

			$CODIDO_WEGOO = null;
			$CODE_ID = null;

			// 2. Buscar código disponible para la variación (o variaciones del padre)
			if ( !empty($variation_ids) ) {
				$placeholders = implode(',', array_fill(0, count($variation_ids), '%d'));
				$sql = "SELECT id, code, product_id 
						FROM hx4p_series_codes 
						WHERE is_active = 1 
						AND product_id IN ($placeholders)
						AND current_uses < max_uses 
						ORDER BY id ASC LIMIT 1";

				$ARRAY_POST = $wpdb->get_row( $wpdb->prepare( $sql, ...$variation_ids ) );

				if ( $ARRAY_POST ) {
					$CODIDO_WEGOO = $ARRAY_POST->code;
					$CODE_ID = $ARRAY_POST->id;
				}
			}

			if ( $CODIDO_WEGOO ) {
				// 4. Guardar en hx4p_wc_codes_wegoo
				$wpdb->insert(
					'hx4p_wc_codes_wegoo',
					array(
						'codes'        => $CODIDO_WEGOO,
						'orderId'      => $ORDERID,
						'productId'    => $ID_PRODUCTO,
						'tipo'         => $STRING_CINE_TIPO,
						'activo'       => 1,
						'fch_creacion' => current_time('mysql')
					),
					array(
						'%s',
						'%d',
						'%d',
						'%s',
						'%d',
						'%s'
					)
				);

				$HTML_QR_OR_CINE = '<img src="'.$imagenBase64.'" style="width: 200px; height: auto; margin-top: 30px; padding: 0px; color: #11296b;"> <div class="text-container" style="width: 100%; text-align: center; font-size: 23px;letter-spacing: 1px; color: #11296b; padding: 0px; margin: 0px;font-family: monospace;">'.$CODIDO_WEGOO.'</div>';
			} else {
				// Fallback: sin códigos disponibles para WEGOO
				$HTML_QR_OR_CINE = '<img src="'.$imagenBase64.'" style="width: 200px; height: auto; margin-top: 30px; padding: 0px; color: #039CDC;"> <div class="text-container" style="width: 100%; text-align: center; font-size: 23px;letter-spacing: 1px; color: #039CDC; padding: 0px; margin: 0px;font-family: monospace;">'.$value->qrCode.'</div>';
			}
		} else {
			// FALLBACK PARA OTROS PRODUCTOS (Esto es lo que faltaba)
			$HTML_QR_OR_CINE = '<img src="'.$imagenBase64.'" style="width: 200px; height: auto; margin-top: 30px; padding: 0px; color: #039CDC;"> <div class="text-container" style="width: 100%; text-align: center; font-size: 23px;letter-spacing: 1px; color: #039CDC; padding: 0px; margin: 0px;font-family: monospace;">'.$value->qrCode.'</div>';
		}
		/* FIN CODIGO PARA EL QR DEL CINE */
		////////////////////////////////////////////////////////////////////
		
        $TICKETS .= '<div style="text-align: center;">
                        '.$HTML_QR_OR_CINE.'
                        '.$HTML_CITAS.'
                        <br>
						'.$TIPO_PRODUCTO.'
                        <h2 style="font-size: 22px; color: #676767;">'.$NOMBRE_EMPRESA.'</h2>
                        <h1 style="color: #676767; font-size: 24px;">'.$NOMBRE_PRODUCTO.'</h1>
                        <div style="color: #8e8e8e !important; font-size: 14px;">'.$NEW_COLOR_DETAILS.'</div>
                    </div>
                    <!-- Condiciones del Bono -->
                    <div style="margin-top: 0px; padding: 10px; background-color: #019cdb; border: 0px solid #67C3E9; border-radius: 0px;">
                        <h3 style="color: #ffffff; font-size: 16px; margin: 0 0 10px;">Condiciones del Bono</h3>
                        <div class="text-color: #a5a5a5 !important;">'.$CONDICIONES.'</div>
						'.$FECHA_COMPRA.'
                    </div>';
    }

    $TICKETS .= '</div>
            </body>
            </html>';

    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $options->set('isPhpEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('tempDir', '/tmp');
    $options->set('chroot', __DIR__);
    
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml($TICKETS);
    $dompdf->setPaper('a4', 'portrait');
    $dompdf->render();
    
    $output = $dompdf->output();
    file_put_contents($RUTA_IMGS . "/" . $NAME_FILE, $output);
}

function crearPdfOld($ORDERID, $QRCODE, $NAME_FILE=""){
    global $wp;
    global $wpdb;

    include_once 'librerias/dompdf/autoload.inc.php';

    $RUTA_IMGS  = ABSPATH . 'qrProductos';

    ob_start();
    include_once dirname( __FILE__ ) . '/templates/plantilla_qrcode.html';
    $PAGE_TPL = ob_get_contents();
    ob_end_clean();

    $IMG_LOGO = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/wp-content/uploads/2023/09/trans.png"));
	
	$order = wc_get_order($ORDERID);
	
	$FECHA_COMPRA = "";
    $purchase_date = $order->get_date_created(); // WC_DateTime
    if ( $purchase_date ) {
        $FECHA_COMPRA = '<p style="font-size: 10px;"><b>Fecha de la compra:</b> <i>'.$purchase_date->date( 'd/m/Y H:i:s' ).'</i></p>';
    }
	
	$CITA_SINO = $order->get_meta('_additional_wooccm0');
    $TXT_CITA  = $order->get_meta('_additional_wooccm1');
    $TEMA_CITA = $order->get_meta('_additional_wooccm2');

    $HTML_CITAS      = "";
    $HTML_IMAGEN     = "";
	$HTML_QR_OR_CINE = "";

    switch ($TEMA_CITA) {
        case "Día de la Madre":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_diadelamadre1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Día del Padre":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_diadelpadre1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Cumpleaños Hombre":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_cumpleanoshombre1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Cumpleaños Mujer":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_cumpleanosmujer1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Navidad":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_navidad1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Papá Noel":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_papanoel1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Reyes Magos":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_reyesmagos1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "San Valentin":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_sanvalentin1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
    }

    $HTML_CITAS  = $TXT_CITA == "" ? "" : ' <div style="margin: 20px;"> <blockquote style="width:100%; text-align:center; font-size: 20px; margin: 10px; font-style: italic;color: #a5a5a5;">"'.$TXT_CITA.'"</blockquote> </div>';
  
    $TICKETS = '<!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Bonos Premium</title>

                    <style>
                        @import url("https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap");

                        html, body {font-family: "Open Sans", sans-serif;}
                        li { text-align: start; margin-bottom: 10px; font-size: 14px; line-height: 13px;}
                        .text-container {
                            margin: auto;
                            width: 250px;
                            padding: 0px;
                            margin: 0px;
                            text-align: center;
                        }
                    </style>
                </head>
                <body>
                <!-- Contenedor Principal -->
                <div style="width: 100%; padding: 0px; font-family: Arial, sans-serif; background-color: #FFFFFF; border-radius: 0px; box-sizing: border-box;">
                    <!-- Encabezado -->
                    <div style="background-color: #019cdb; padding: 20px; border-radius: 0px; color: #FFFFFF; text-align: center; margin-top: 0px; margin-bottom: 0px;">
                        <img style="width: 400px; padding: 0px;" src="'.$IMG_LOGO.'" alt="">
                    </div>'.$HTML_IMAGEN;


    $registros = $wpdb->get_results( "SELECT * FROM ". $wpdb->prefix ."wc_pedidos_item WHERE qrCode = '$QRCODE'" );

    foreach ($registros as $key=>$value){
        $product_info   = wc_get_product( $value->productId );

        $CONDICIONES    = $product_info->get_meta('condiciones_generales');
        $NOMBRE_EMPRESA = $product_info->get_meta('nombre_establecimiento');

		$nombreImagen = ABSPATH . 'qrProductos/qr_'. $value->qrCode .'.png';
        $imagenBase64 = "data:image/png;base64," . base64_encode(file_get_contents($nombreImagen));


        $NEW_COLOR_DETAILS = preg_replace('/color:\s?#000000;?/i', "color: #8e8e8e;", $value->productDetail);

        $ARRAY_PRODUCTO   = explode(" - ", $value->productName);
        $NOMBRE_PRODUCTO  = $ARRAY_PRODUCTO[0];
        $TIPO_PRODUCTO    = isset($ARRAY_PRODUCTO[1]) ? "<p>(".$ARRAY_PRODUCTO[1].")</p>" : "";
		$STRING_CINE_TIPO = isset($ARRAY_PRODUCTO[1]) ? trim($ARRAY_PRODUCTO[1]) : "";
		
		///////////////////////////////////////////////////////////
		/* INCIO CODIGO PARA EL QR DEL CINE */
		if (preg_match('/\bCINE YELMO\b/i', $NOMBRE_EMPRESA)) {
			$TIPO_BUSCAR = $STRING_CINE_TIPO == "Entrada" ? "Entrada" : "Entrada + menú clásico";
				
			$ARRAY_POST = $wpdb->get_row( $wpdb->prepare( "SELECT codes FROM hx4p_wc_codes_cinema WHERE activo = 0 AND tipo = '$STRING_CINE_TIPO' AND orderId = 0 LIMIT 1" ) ); 
						
			$CODIDO_CINE      = $ARRAY_POST->codes;
			$cineImagen       = 'https://bonospremium.com/wp-content/uploads/2025/07/ticket_cine.png';
        	$cineImagenBase64 = "data:image/png;base64," . base64_encode(file_get_contents($cineImagen));
			
			// Actualizamos el estado del qr del cine
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE hx4p_wc_codes_cinema SET activo = %d, orderId = %d WHERE codes = %s",
					1,
					$ORDERID,
					$CODIDO_CINE
				)
			);
			
			$HTML_QR_OR_CINE = '<img src="'.$cineImagenBase64.'" style="width: 200px; height: auto; margin-top: 30px; padding: 0px; color: #11296b;"> <div class="text-container" style="width: 100%; text-align: center; font-size: 23px;letter-spacing: 1px; color: #11296b; padding: 0px; margin: 0px;font-family: monospace;">'.$CODIDO_CINE.'</div>';
			
		} else if (preg_match('/\bWEGOO\b/i', $NOMBRE_EMPRESA)) {
				
			$ARRAY_POST = $wpdb->get_row( $wpdb->prepare( "SELECT codes FROM hx4p_wc_codes_extras WHERE activo = 0 AND tipo = '$STRING_CINE_TIPO' AND orderId = 0 LIMIT 1" ) ); 
						
			$CODIDO_WEGOO      = $ARRAY_POST->codes;
			$wegooImagen       = 'https://www.wegoo.es/6d8552db8d9f0393f4e4dbf656063570.svg';
        	$wegooImagenBase64 = "data:image/png;base64," . base64_encode(file_get_contents($wegooImagen));
			
			// Actualizamos el estado del qr del cine
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE hx4p_wc_codes_extras SET activo = %d, orderId = %d WHERE codes = %s",
					1,
					$ORDERID,
					$CODIDO_WEGOO
				)
			);
			
			$HTML_QR_OR_CINE = '<img src="'.$wegooImagenBase64.'" style="width: 200px; height: auto; margin-top: 30px; padding: 0px; color: #11296b;"> <div class="text-container" style="width: 100%; text-align: center; font-size: 23px;letter-spacing: 1px; color: #11296b; padding: 0px; margin: 0px;font-family: monospace;">'.$CODIDO_WEGOO.'</div>';
			
		} else {
			$HTML_QR_OR_CINE = '<img src="'.$imagenBase64.'" style="width: 200px; height: auto; margin-top: 30px; padding: 0px; color: #039CDC;"> <div class="text-container" style="width: 100%; text-align: center; font-size: 23px;letter-spacing: 1px; color: #039CDC; padding: 0px; margin: 0px;font-family: monospace;">'.$value->qrCode.'</div>';
		}
		/* FIN CODIGO PARA EL QR DEL CINE */
		////////////////////////////////////////////////////////////////////
		
        $TICKETS .= '<div style="text-align: center;">
                        '.$HTML_QR_OR_CINE.'
                        '.$HTML_CITAS.'
                        <br>
						'.$TIPO_PRODUCTO.'
                        <h2 style="font-size: 22px; color: #676767;">'.$NOMBRE_EMPRESA.'</h2>
                        <h1 style="color: #676767; font-size: 24px;">'.$NOMBRE_PRODUCTO.'</h1>
                        <div style="color: #8e8e8e !important; font-size: 14px;">'.$NEW_COLOR_DETAILS.'</div>
                    </div>
                    <!-- Condiciones del Bono -->
                    <div style="margin-top: 0px; padding: 10px; background-color: #019cdb; border: 0px solid #67C3E9; border-radius: 0px;">
                        <h3 style="color: #ffffff; font-size: 16px; margin: 0 0 10px;">Condiciones del Bono</h3>
                        <div class="text-color: #a5a5a5 !important;">'.$CONDICIONES.'</div>
						'.$FECHA_COMPRA.'
                    </div>';
    }

    $TICKETS .= '</div>
            </body>
            </html>';

    $ARRAY_TPL = array( "qrCodes" => $TICKETS );
    $HTML_TPL = parse_template($PAGE_TPL, $ARRAY_TPL);

    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $options->set('isPhpEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('tempDir', '/tmp');
    $options->set('chroot', __DIR__);
    
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml($TICKETS);
    $dompdf->setPaper('a4', 'portrait');
    $dompdf->render();
    
    $output = $dompdf->output();
    file_put_contents($RUTA_IMGS . "/" . $NAME_FILE, $output);
}

function crearPdfSimple($ORDERID, $QRCODE, $IDPRODUCTO){
    global $wp;
    global $wpdb;

    include_once 'librerias/dompdf/autoload.inc.php';

    // $RUTA_IMGS = WP_PLUGIN_DIR . '/woocommerce-bonospremium/qrProductos';
	$RUTA_IMGS  = ABSPATH . 'qrProductos';

    ob_start();
    include_once dirname( __FILE__ ) . '/templates/plantilla_qrcode.html';
    $PAGE_TPL = ob_get_contents();
    ob_end_clean();

    $IMG_LOGO = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/wp-content/uploads/2023/09/trans.png"));
	
	$order = wc_get_order($ORDERID);
	
	$CITA_SINO = $order->get_meta('_additional_wooccm0');
    $TXT_CITA  = $order->get_meta('_additional_wooccm1');
    $TEMA_CITA = $order->get_meta('_additional_wooccm2');

    $HTML_CITAS      = "";
    $HTML_IMAGEN     = "";
	$HTML_QR_OR_CINE = "";

    switch ($TEMA_CITA) {
        case "Día de la Madre":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_diadelamadre1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Día del Padre":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_diadelpadre1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Cumpleaños Hombre":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_cumpleanoshombre1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Cumpleaños Mujer":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_cumpleanosmujer1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Navidad":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_navidad1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Papá Noel":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_papanoel1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "Reyes Magos":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_reyesmagos1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
        case "San Valentin":
            $IMG_TEMA    = "data:image/png;base64," . base64_encode(file_get_contents("https://bonospremium.com/admin/assets/imgPlugin/qr_sanvalentin1.png"));
            $HTML_IMAGEN = ' <div> <img style="width: 100%;" src="'.$IMG_TEMA.'" alt=""> </div>';
            break;
    }

    $HTML_CITAS  = $TXT_CITA == "" ? "" : ' <div style="margin: 20px;"> <blockquote style="width:100%; text-align:center; font-size: 20px; margin: 10px; font-style: italic;color: #a5a5a5;">"'.$TXT_CITA.'"</blockquote> </div>';
  
    $TICKETS = '<!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Bonos Premium</title>

                    <style>
                        @import url("https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap");

                        html, body {font-family: "Open Sans", sans-serif;}
                        li { text-align: start; margin-bottom: 10px; font-size: 14px; line-height: 13px;}
                        .text-container {
                            margin: auto;
                            width: 250px;
                            padding: 0px;
                            margin: 0px;
                            text-align: center;
                        }
                    </style>
                </head>
                <body>
                <!-- Contenedor Principal -->
                <div style="width: 100%; padding: 0px; font-family: Arial, sans-serif; background-color: #FFFFFF; border-radius: 0px; box-sizing: border-box;">
                    <!-- Encabezado -->
                    <div style="background-color: #019cdb; padding: 20px; border-radius: 0px; color: #FFFFFF; text-align: center; margin-top: 0px; margin-bottom: 0px;">
                        <img style="width: 400px; padding: 0px;" src="'.$IMG_LOGO.'" alt="">
                    </div>'.$HTML_IMAGEN;

    $product_info   = wc_get_product( $IDPRODUCTO );

    $CONDICIONES    = $product_info->get_meta('condiciones_generales');
    $NOMBRE_EMPRESA = $product_info->get_meta('nombre_establecimiento');

    // $nombreImagen = get_site_url() . '/wp-content/plugins/woocommerce-bonospremium/qrProductos/qr_'. $QRCODE .'.png';
	$nombreImagen = ABSPATH . 'qrProductos/qr_'. $QRCODE .'.png';
    $imagenBase64 = "data:image/png;base64," . base64_encode(file_get_contents($nombreImagen));


    $NEW_COLOR_DETAILS = preg_replace('/color:\s?#000000;?/i', "color: #8e8e8e;", $product_info->description);
	
	///////////////////////////////////////////////////////////
    /* INCIO CODIGO PARA EL QR DEL CINE */
    if (preg_match('/\bCINE YELMO\b/i', $NOMBRE_EMPRESA)) {
        $TIPO_BUSCAR = $STRING_CINE_TIPO == "Entrada" ? "Entrada" : "Entrada + menú clásico";
            
        $ARRAY_POST = $wpdb->get_row( $wpdb->prepare( "SELECT codes FROM hx4p_wc_codes_cinema WHERE activo = 0 AND tipo = '$TIPO_BUSCAR' AND orderId = 0 LIMIT 1" ) ); 
                    
        $CODIDO_CINE      = $ARRAY_POST->codes;
        $cineImagen       = 'https://bonospremium.com/wp-content/uploads/2025/07/ticket_cine.png';
        $cineImagenBase64 = "data:image/png;base64," . base64_encode(file_get_contents($cineImagen));
        
        // Actualizamos el estado del qr del cine
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE hx4p_wc_codes_cinema SET activo = %d, orderId = %d WHERE codes = %s",
                1,
                $ORDERID,
                $CODIDO_CINE
            )
        );
        
        $HTML_QR_OR_CINE = '<img src="'.$cineImagenBase64.'" style="width: 200px; height: auto; margin-top: 30px; padding: 0px; color: #11296b;"> <div class="text-container" style="width: 100%; text-align: center; font-size: 23px;letter-spacing: 1px; color: #11296b; padding: 0px; margin: 0px;font-family: monospace;">'.$CODIDO_CINE.'</div>';
        
    } else if (preg_match('/\bWEGOO\b/i', $NOMBRE_EMPRESA)) {
				
		$ARRAY_POST = $wpdb->get_row( $wpdb->prepare( "SELECT codes FROM hx4p_wc_codes_extras WHERE activo = 0 AND tipo = '$STRING_CINE_TIPO' AND orderId = 0 LIMIT 1" ) ); 

		$CODIDO_CINE      = $ARRAY_POST->codes;
		$cineImagen       = 'https://www.wegoo.es/6d8552db8d9f0393f4e4dbf656063570.svg';
		$cineImagenBase64 = "data:image/png;base64," . base64_encode(file_get_contents($cineImagen));

		// Actualizamos el estado del qr del cine
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE hx4p_wc_codes_extras SET activo = %d, orderId = %d WHERE codes = %s",
				1,
				$ORDERID,
				$CODIDO_CINE
			)
		);

		$HTML_QR_OR_CINE = '<img src="'.$cineImagenBase64.'" style="width: 200px; height: auto; margin-top: 30px; padding: 0px; color: #11296b;"> <div class="text-container" style="width: 100%; text-align: center; font-size: 23px;letter-spacing: 1px; color: #11296b; padding: 0px; margin: 0px;font-family: monospace;">'.$CODIDO_CINE.'</div>';

	} else {
        $HTML_QR_OR_CINE = '<img src="'.$imagenBase64.'" style="width: 200px; height: auto; margin-top: 30px; padding: 0px; color: #039CDC;"> <div class="text-container" style="width: 100%; text-align: center; font-size: 23px;letter-spacing: 1px; color: #039CDC; padding: 0px; margin: 0px;font-family: monospace;">'.$QRCODE.'</div>';
    }
    /* FIN CODIGO PARA EL QR DEL CINE */
    ////////////////////////////////////////////////////////////////////

    $TICKETS .= '<div style="text-align: center;">
					'.$HTML_QR_OR_CINE.'
                    '.$HTML_CITAS.'
                    <br>
                    <h2 style="font-size: 22px; color: #676767;">'.$NOMBRE_EMPRESA.'</h2>
                    <h1 style="color: #676767; font-size: 24px;">'.$product_info->name.'</h1>
                    <div style="color: #8e8e8e !important; font-size: 14px;">'.$NEW_COLOR_DETAILS.'</div>
                </div>
                <!-- Condiciones del Bono -->
                <div style="margin-top: 0px; padding: 10px; background-color: #019cdb; border: 0px solid #67C3E9; border-radius: 0px;">
                    <h3 style="color: #ffffff; font-size: 16px; margin: 0 0 10px;">Condiciones del Bono</h3>
                    <div class="text-color: #a5a5a5 !important;">'.$CONDICIONES.'</div>
                </div>';
    

    $TICKETS .= '</div>
            </body>
            </html>';

    $ARRAY_TPL = array( "qrCodes" => $TICKETS );
    $HTML_TPL = parse_template($PAGE_TPL, $ARRAY_TPL);

    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $options->set('isPhpEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('tempDir', '/tmp');
    $options->set('chroot', __DIR__);
    
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml($TICKETS);
    $dompdf->setPaper('a4', 'portrait');
    $dompdf->render();
    
    $output = $dompdf->output();

    $NAME_FILE = "voucher_".$ORDERID."_".$QRCODE.".pdf";
    file_put_contents($RUTA_IMGS . "/" . $NAME_FILE, $output);
}

function crearQrCode($ORDERID, $QRCODE, $IDPRODUCTO){
    global $wp;

    $RUTA_QR    = WP_PLUGIN_DIR . '/woocommerce-bonospremium';
    // $RUTA_IMGS  = WP_PLUGIN_DIR . '/woocommerce-bonospremium/qrProductos';
	$RUTA_IMGS  = ABSPATH . '/qrProductos';
    
    include $RUTA_QR . '/librerias/phpqrcode/qrlib.php';

    $fileName            = 'qr_'.$QRCODE.'.png';
    $pngAbsoluteFilePath = $RUTA_IMGS ."/". $fileName;

    QRcode::png($QRCODE, $pngAbsoluteFilePath, QR_ECLEVEL_L, 10, 1, false);

    crearPdfSimple($ORDERID, $QRCODE, $IDPRODUCTO);
	// crearPdf($ORDERID, $QRCODE, $IDPRODUCTO);
}

function parse_template($string, $hash) {
    foreach ( $hash as $ind=>$val ) {
        $string = str_replace('{{'.$ind.'}}',$val,$string);
    } 

    $string = preg_replace('/\{\{(.*?)\}\}/is','',$string);

    return $string;
}

////////////////////////////////////////////////////////////////
// ESTA FUNCION SE EJECUTA AL ACTIVAR EL PLUGIN
////////////////////////////////////////////////////////////////
register_activation_hook(__FILE__, 'bonospremium_plugin_activate_plugin'); //    bonospremium_plugin_activate_plugin  bonospremium_init
function bonospremium_plugin_activate_plugin() {
    global $wpdb;

    $NOMBRE_TABLA = $wpdb->prefix . 'wc_pedidos_item';

    $SQL = "CREATE TABLE IF NOT EXISTS $NOMBRE_TABLA (
                id int(11) NOT NULL AUTO_INCREMENT,
                orderId int(11) NOT NULL,
                productId int(11) NOT NULL,
                empresaId int(11) NOT NULL,
                comercialId int(11) NOT NULL,
                productName varchar(200) NOT NULL,
                productDetail text NOT NULL,
                userId int(11) NOT NULL,
                cantidad int(11) DEFAULT NULL,
                precio double DEFAULT NULL,
                precioRegular double DEFAULT NULL,
                precioOferta double DEFAULT NULL,
                qrCode varchar(20) NOT NULL,
                qrCodePedido varchar(20) NOT NULL,
                fechaCreacion datetime NOT NULL,
                fechaModificacion datetime NOT NULL,
                estado varchar(30) NOT NULL,
                PRIMARY KEY (id),
                KEY email (orderId)
            ) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $wpdb->query($SQL);
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/* 
    NOTAS:
    1.- Hay que desactivar todos los plugins anteriores
    2.- Quitar el tema chidren del wp
    3.- Desactivar el envio de los mail de woocommerce "porcesando pedido"
    4.- Crear la lista del select para la plantilla en el qr del mail

*/

/* Selector de cantidades al finalizar compra en Woo */
// Ocultamos la cadena de las cantidades junto al nombre del producto
add_filter( 'woocommerce_checkout_cart_item_quantity', '__return_empty_string' );
// Agregamos el selector de cantidades
add_filter( 'woocommerce_cart_item_subtotal', 'ayudawp_selector_cantidades_pago', 9999, 3 );
function ayudawp_selector_cantidades_pago( $product_quantity, $cart_item, $cart_item_key ) {
    if ( is_checkout() ) {
        $product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
        $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
        $unit_price = wc_price( $product->get_price() ); // Obtiene el costo unitario del producto

        // Generar el input con botones de más y menos
        $product_quantity = '<div class="quantity-selector" data-product-id="' . $product_id . '">';
        $product_quantity .= '<button type="button" class="qty-minus" data-action="decrease">-</button>';
        $product_quantity .= woocommerce_quantity_input( array(
            'input_name'  => 'shipping_method_qty_' . $product_id,
            'input_value' => $cart_item['quantity'],
            'max_value'   => $product->get_max_purchase_quantity(),
            'min_value'   => '0',
        ), $product, false );
        $product_quantity .= '<button type="button" class="qty-plus" data-action="increase">+</button>';
        $product_quantity .= '</div>';

        // Agregar el precio unitario debajo del selector de cantidad
        $product_quantity .= '<div class="unit-price">Precio unitario: <strong>' . $unit_price . '</strong></div>';

        $product_quantity .= '<input type="hidden" name="product_key_' . $product_id . '" value="' . $cart_item_key . '">';
    }
    return $product_quantity;
}

// Detectamos el cambio de cantidad para recalcular los totales
add_action( 'woocommerce_checkout_update_order_review', 'ayudawp_recalcular_totales_selector_cantidades_pago' );
function ayudawp_recalcular_totales_selector_cantidades_pago( $post_data ) {
    parse_str( $post_data, $post_data_array );
    $updated_qty = false;
    foreach ( $post_data_array as $key => $value ) { 
        if ( substr( $key, 0, 20 ) === 'shipping_method_qty_' ) { 
            $id = substr( $key, 20 ); 
            WC()->cart->set_quantity( $post_data_array['product_key_' . $id], $post_data_array[$key], false );
            $updated_qty = true;
        } 
    } 
    if ( $updated_qty ) WC()->cart->calculate_totals();
}

// Agregar JavaScript para manejar los botones más y menos
add_action( 'wp_footer', 'ayudawp_selector_cantidades_script' );
function ayudawp_selector_cantidades_script() {
    if ( is_checkout() ) {
        ?>
        <script>
            document.addEventListener('click', function (event) {
                if (event.target.classList.contains('qty-minus') || event.target.classList.contains('qty-plus')) {
                    const button = event.target;
                    const action = button.dataset.action;
                    const wrapper = button.closest('.quantity-selector');
                    const input = wrapper.querySelector('input.qty');
                    let value = parseInt(input.value, 10);
                    const min = parseInt(input.getAttribute('min'), 10) || 0;
                    const max = parseInt(input.getAttribute('max'), 10) || Infinity;

                    // Ajustar el valor del input según la acción (aumentar o disminuir)
                    if (action === 'decrease' && value > min) {
                        value--;
                    } else if (action === 'increase' && value < max) {
                        value++;
                    }

                    // Actualizar el valor del input
                    input.value = value;

                    // Simular el evento de cambio del input para activar las funciones de WooCommerce
                    jQuery(input).trigger('change');
                }
            });

            // Asegurarse de que el recalculo se ejecuta correctamente al cambiar el valor del input
            jQuery(document).on('change', '.quantity-selector input.qty', function () {
                jQuery('body').trigger('update_checkout');
            });

            jQuery("#mailchimp_woocommerce_newsletter").parent().parent().hide();

            // Añadimos mensaje al password
            jQuery("#account_password").attr("placeholder", "Pon tu contraseña BonosPremium");
            // jQuery("#account_password_field").append('<i style="font-size: 12px;">Pon tu nueva contraseña bonos Premium</i>');
        </script>
        <style>
            .quantity-selector {
                display: flex;
                align-items: center;
            }
            .qty-minus, .qty-plus {
                border: none;
                background: #ddd;
                padding: 5px 10px;
                cursor: pointer;
            }
            .qty-minus:hover, .qty-plus:hover {
                background: #bbb;
            }
            input.qty {
                text-align: center;
                width: 50px;
                margin: 0 5px;
            }
            .unit-price {
                margin-top: 5px;
                font-size: 14px;
                color: #666;
            }
            .unit-price strong {
                color: #333;
            }
            input.qty[type="number"] {
                -webkit-appearance: textfield;
                -moz-appearance: textfield;
                appearance: textfield;
            }
            
            input.qty[type=number]::-webkit-inner-spin-button,
            input.qty[type=number]::-webkit-outer-spin-button {
                -webkit-appearance: none;
            }
        </style>
        <?php
    }
}

// Eliminar productos desde el checkout con imagen de producto
function dl_quitar_productos_checkout( $product_name, $cart_item, $cart_item_key ) {
    if ( is_checkout() ) {
        $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
        $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

        // Obtener la URL de la imagen del producto
        $thumbnail = $_product->get_image(array(50, 50)); // El array especifica el tamaño de la imagen (ancho, alto)

        // Crear el enlace de eliminación del producto
        $remove_link = apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
            '<a href="%s" class="remove1" aria-label="%s" data-product_id="%s" data-product_sku="%s"> <i class="fas fa-trash"></i> </a>',
            esc_url( WC()->cart->get_remove_url( $cart_item_key ) ),
            __( 'Quitar producto', 'woocommerce' ),
            esc_attr( $product_id ),
            esc_attr( $_product->get_sku() )
        ), $cart_item_key );

        // Devolver la imagen, el enlace de eliminación y el nombre del producto
        return '<div style="display: flex;"> <span style="position: relative;">' . $remove_link . '</span> <span>' . $thumbnail . '</span> <span>' . $product_name . '</span> </div>';
    }

    return $product_name;
}
add_filter( 'woocommerce_cart_item_name', 'dl_quitar_productos_checkout', 10, 3 );





/* APIREST CUSTOM */
////////////////////////////////////////////////////////////////////////
// CREA EL PDF Y LOS PRODUCTOS DE UN PEDIDO ASIGNANDO EL QR AL PRODUCTO
// https://bonospremium.com/wp-json/custom/v1/pdf/idPedido/idProducto/qrCode
////////////////////////////////////////////////////////////////////////
function regenerar_pdf_pedido_api_endpoint() {
    register_rest_route('custom/v1', '/pdf/(?P<idPedido>\d+)/(?P<idProducto>\d+)/(?P<qrCode>\w+)', array(
        'methods' => 'GET',
        'callback' => 'regenerar_pdf_pedido_api_endpoint_callback',
    ));
}
add_action('rest_api_init', 'regenerar_pdf_pedido_api_endpoint');
function regenerar_pdf_pedido_api_endpoint_callback(WP_REST_Request $request) {
    crearQrCode($request['idPedido'], $request['qrCode'], $request['idProducto']);
    
    $MyOrders = array(
        "error" => false,
        "msj"   => "Pdf creado con exito.",
    );

    return rest_ensure_response($MyOrders);
}
////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////
// REENVIA UN PEDIDO AL CLIENTE CON SU EMAIL Y PDF DE PEDIDO
// https://bonospremium.com/wp-json/custom/v1/email/20407
////////////////////////////////////////////////////////////////////////
function reenviar_email_pedido_api_endpoint() {
    register_rest_route('custom/v1', '/email/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'reenviar_email_pedido_api_endpoint_callback',
    ));
}
add_action('rest_api_init', 'reenviar_email_pedido_api_endpoint');
function reenviar_email_pedido_api_endpoint_callback(WP_REST_Request $request) {
    global $wpdb;

    $exite = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(orderId) AS NUM FROM ". $wpdb->prefix ."wc_pedidos_item WHERE orderId = ".$request['id'] ) );
    if($exite->NUM >= 1){
        $ARRAY_NAME_FILE = [];

        $table_name = "hx4p_wc_pedidos_item";
        $myrows = $wpdb->get_results( "SELECT orderId, qrCode, productId, qrCodePedido FROM ".$table_name." WHERE orderId = ".$request['id']);
            foreach ($myrows as $details) {
                crearQrCode($request['id'], $details->qrCode, $details->productId);
                array_push($ARRAY_NAME_FILE, "voucher_".$request['id']."_".$details->qrCode);
            }  
        
        enviarEmail($request['id'], $ARRAY_NAME_FILE, 0);
    }else{
        $numero = $request['id'];
        $numero = preg_replace('/\D/', '', $numero);
        $ruta   = ABSPATH . '/qrProductos';

        // Buscar el PDF que tenga ese número
        $pdfs = glob($ruta . "/voucher_{$numero}_*.pdf");

        foreach ($pdfs as $pdf) {

            $nombrePdf = basename($pdf);

            // Extraer el hash del nombre del PDF
            if (preg_match('/^voucher_\d+_([a-f0-9]+)\.pdf$/i', $nombrePdf, $m)) {

                $hash = $m[1];

                // Rutas completas
                $rutaPdf = $ruta . "/voucher_{$numero}_{$hash}.pdf";
                $rutaPng = $ruta . "/qr_{$hash}.png";

                // Borrar PDF
                if (file_exists($rutaPdf)) {
                    unlink($rutaPdf);
                    // echo "Borraría PDF: $rutaPdf<br>";

                }

                // Borrar PNG
                if (file_exists($rutaPng)) {
                    unlink($rutaPng);
                    // echo "Borraría PNG: $rutaPng<br>";
                }
            }
        }


        bonospremium_payment_complete( $request['id'] );
    }

    $MyOrders = array(
        "error" => false,
        "msj"   => "Email enviado con exito.",
    );

    return rest_ensure_response($MyOrders);
}
////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////
// LISTA UN JSON DE TODOS LOS PEDIDOS QUE NO ESTEN EN LA TABLA Y HAY UNA URL PARA PODER GENERARLO Y ENVIAR EMAIL AL CLIENTE
// https://bonospremium.com/wp-json/custom/v1/listar/0000-00-00/0000-00-00/Todo
////////////////////////////////////////////////////////////////////////
function register_listar_orders_api_endpoint() {
    register_rest_route('custom/v1', '/listar/(?P<start_date>\d{4}-\d{2}-\d{2})/(?P<end_date>\d{4}-\d{2}-\d{2})/(?P<categoria>[^/]+)', [
        'methods' => 'GET', // Método GET
        'callback' => 'listar_pedidos_perdidos', // Función callback para manejar la solicitud
        'args' => [
            'start_date' => [
                'required' => true, // El parámetro start_date es obligatorio
                'validate_callback' => function ($param, $request, $key) {
                    // Validar si la fecha tiene el formato correcto (YYYY-MM-DD)
                    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                }
            ],
            'end_date' => [
                'required' => true, // El parámetro end_date es obligatorio
                'validate_callback' => function ($param, $request, $key) {
                    // Validar si la fecha tiene el formato correcto (YYYY-MM-DD)
                    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                }
            ]
        ]
    ]);
}
add_action('rest_api_init', 'register_listar_orders_api_endpoint');
function listar_pedidos_perdidos(WP_REST_Request $request) {
    $start_date = $request->get_param('start_date') ?: '2000-01-01';
    $end_date   = $request->get_param('end_date') ?: current_time('Y-m-d');
    $categoria  = $request->get_param('categoria');

    // Asegura formato completo con hora
    $start_datetime = date('Y-m-d H:i:s', strtotime($start_date . ' 00:00:00'));
    $end_datetime   = date('Y-m-d H:i:s', strtotime($end_date . ' 23:59:59'));

    // Estados válidos
    $ARRAY_BUSQUEDA = ($categoria == "Todo" || !$categoria)
        ? ['wc-completed', 'wc-pending', 'wc-cancelled', 'wc-refunded', 'wc-failed', 'wc-lapsed', 'wc-processing']
        : [$categoria];

    // Preparar argumentos de búsqueda
    $args = [
        'limit'        => -1,
        'status'       => $ARRAY_BUSQUEDA,
        'date_created' => $start_datetime . '...' . $end_datetime,
        'orderby'      => 'date',
        'order'        => 'DESC',
        'paginate'     => false,
    ];

    $orders = wc_get_orders($args);

    // Inicializa arrays de resultados
    $resultados = [
        'completos'   => [],
        'nocanjeados' => [],
        'pendientes'  => [],
        'cancelados'  => [],
        'reembolsos'  => [],
        'fallidos'    => [],
        'perdidos'    => [],
    ];

    foreach ($orders as $order) {
        $estado = 'wc-' . $order->get_status();
        $item = [
            'ID'       => $order->get_id(),
            'Estado'   => $estado,
            'url'      => "https://bonospremium.com/wp-json/custom/v1/data/" . $order->get_id(),
            'Date'     => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '',
            'Cantidad' => count($order->get_items()),
            'Total'    => $order->get_total(),
        ];

        // Clasificación
        switch ($estado) {
            case 'wc-completed':
                $resultados['completos'][] = $item;
                break;
            case 'wc-processing':
                $resultados['nocanjeados'][] = $item;
                break;
            case 'wc-pending':
                $resultados['pendientes'][] = $item;
                break;
            case 'wc-cancelled':
                $resultados['cancelados'][] = $item;
                break;
            case 'wc-refunded':
                $resultados['reembolsos'][] = $item;
                break;
            case 'wc-failed':
                $resultados['fallidos'][] = $item;
                break;
            case 'wc-lapsed':
                $resultados['perdidos'][] = $item;
                break;
        }
    }

    // Debug temporal (opcional)
    $total_pedidos_encontrados = count($orders);

    return rest_ensure_response([
        'total_pedidos' => $total_pedidos_encontrados,
        'rango_fecha'   => [$start_datetime, $end_datetime],
        'estados'       => $ARRAY_BUSQUEDA,
        'pedidos'       => $resultados
    ]);
}


function listar_pedidos_perdidosOLD(WP_REST_Request $request) {
    global $wpdb;

    $MyOrders   = [];
    $start_date = $request->get_param('start_date'); // '2024-01-01';
    $end_date   = $request->get_param('end_date'); // '2024-01-31';
    $categoria  = $request->get_param('categoria'); // '2024-01-31';

    $COMPLETOS = $NOCANJEADOS = $PENDIENTES = $CANCELADOS = $REEMBOLSOS = $FALLIDOS = $PERDIDOS = [];

    $ARRAY_BUSQUEDA = $categoria == "Todo" ? array('wc-completed', 'wc-pending', 'wc-cancelled', 'wc-refunded', 'wc-failed', 'wc-lapsed', 'wc-processing') : $categoria;

    // Create a query to get orders between dates
    $args = array(
        'post_type'      => 'shop_order',
        'post_status'    => $ARRAY_BUSQUEDA,
        'date_query'     => array(
            'after'     => $start_date,
            'before'    => $end_date,
            'inclusive' => true, // Include orders on the start and end dates
        ),
        'posts_per_page' => -1,
        'nopaging'       => true, // Retrieve all orders, remove if you want pagination
    );

    // Get orders using WP_Query
    $orders_query = new WP_Query($args);

    // Check if there are orders
    if ($orders_query->have_posts()) {
        while ($orders_query->have_posts()) {
            $orders_query->the_post();

            $order_id = get_the_ID(); // Obtiene el ID del pedido
            $order    = wc_get_order($order_id); // Obtiene el objeto WC_Order

            $order_date     = $orders_query->post->post_date;
            $order_estado   = $orders_query->post->post_status;
            $order_cantidad = count($order->get_items());

            if($order_estado == "wc-completed"){ 
                $MyOrders = array(
                    "ID"       => $order_id, 
                    "Estado"   => $order_estado, 
                    "url"      => "https://bonospremium.com/wp-json/custom/v1/data/".$order_id,
                    "Date"     => $order_date,
                    "Cantidad" => $order_cantidad,
                    'Total'    => $order->get_total()
                );

                array_push($COMPLETOS, $MyOrders); 
            }

            if($order_estado == "wc-processing"){ 
                $MyOrders = array(
                    "ID"       => $order_id, 
                    "Estado"   => $order_estado, 
                    "url"      => "https://bonospremium.com/wp-json/custom/v1/data/".$order_id,
                    "Date"     => $order_date,
                    "Cantidad" => $order_cantidad,
                    'Total'    => $order->get_total()
                );

                array_push($NOCANJEADOS, $MyOrders); 
            }

            if($order_estado == "wc-pending"){ 
                $MyOrders = array(
                    "ID"       => $order_id, 
                    "Estado"   => $order_estado, 
                    "url"      => "https://bonospremium.com/wp-json/custom/v1/data/".$order_id,
                    "Date"     => $order_date,
                    "Cantidad" => $order_cantidad,
                    'Total'    => $order->get_total()
                );

                array_push($PENDIENTES, $MyOrders); 
            }

            if($order_estado == "wc-cancelled"){ 
                $MyOrders = array(
                    "ID"       => $order_id, 
                    "Estado"   => $order_estado, 
                    "url"      => "https://bonospremium.com/wp-json/custom/v1/data/".$order_id,
                    "Date"     => $order_date,
                    "Cantidad" => $order_cantidad,
                    'Total'    => $order->get_total()
                );

                array_push($CANCELADOS, $MyOrders); 
            }

            if($order_estado == "wc-refunded"){ 
                $MyOrders = array(
                    "ID"       => $order_id, 
                    "Estado"   => $order_estado, 
                    "url"      => "https://bonospremium.com/wp-json/custom/v1/data/".$order_id,
                    "Date"     => $order_date,
                    "Cantidad" => $order_cantidad,
                    'Total'    => $order->get_total()
                );

                array_push($REEMBOLSOS, $MyOrders); 
            }

            if($order_estado == "wc-failed"){ 
                $MyOrders = array(
                    "ID"       => $order_id, 
                    "Estado"   => $order_estado, 
                    "url"      => "https://bonospremium.com/wp-json/custom/v1/data/".$order_id,
                    "Date"     => $order_date,
                    "Cantidad" => $order_cantidad,
                    'Total'    => $order->get_total()
                );

                array_push($FALLIDOS, $MyOrders); 
            }

            if($order_estado == "wc-lapsed"){ 
                $MyOrders = array(
                    "ID"       => $order_id, 
                    "Estado"   => $order_estado, 
                    "url"      => "https://bonospremium.com/wp-json/custom/v1/data/".$order_id,
                    "Date"     => $order_date,
                    "Cantidad" => $order_cantidad,
                    'Total'    => $order->get_total()
                );

                array_push($PERDIDOS, $MyOrders); 
            }

            /*wc-pending
            wc-cancelled
            wc-refunded
            wc-failed
            wc-lapsed*/

            // $ARRAY_POST = $wpdb->get_row( $wpdb->prepare( "SELECT orderId FROM hx4p_wc_pedidos_item WHERE orderId = ".$orders_query->post->ID ) ); 
            // $QR_CODE_PEDIDO = $ARRAY_POST->orderId;

            // if( ($order_estado == "wc-processing") || ($order_estado == "wc-completed")){
            //     if($QR_CODE_PEDIDO != $order_id){
                    /*$MyOrders[] = array(
                        "ID"     => $order_id, 
                        "Estado" => $order_estado, 
                        "url"    => "https://bonospremium.com/wp-json/custom/v1/data/".$order_id,
                        "Date"   => $order_date
                    );*/
            //    }
            // }
        }
    }

    // Restore global post data
    wp_reset_postdata();

    return rest_ensure_response(
        array(
            'completos'   => $COMPLETOS,
            'nocanjeados' => $NOCANJEADOS,
            'pendientes'  => $PENDIENTES,
            'cancelados'  => $CANCELADOS,
            'reembolsos'  => $REEMBOLSOS,
            'fallidos'    => $FALLIDOS,
            'perdidos'    => $PERDIDOS
        )
    );

    // return rest_ensure_response($orders_query);
}
////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////
// INSERTA TODOS LOS PRODUCTOS EN LA TABLA Y CREA EL PDF Y QR DE UN PEDIDO Y ENVIA EMAIL AL CLIENTE
// https://bonospremium.com/wp-json/custom/v1/data/20407
////////////////////////////////////////////////////////////////////////
function register_custom_api_endpoint() {
    register_rest_route('custom/v1', '/data/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'custom_api_endpoint_callback',
    ));
}
add_action('rest_api_init', 'register_custom_api_endpoint');
function custom_api_endpoint_callback(WP_REST_Request $request) {
    
    bonospremium_payment_complete($request['id']);
    
    $MyOrders = array(
        "error" => false,
        "msj"   => "Pedido sincronizado correctamente.",
    );

    return rest_ensure_response($MyOrders);
}
////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////
// INSERTA TODOS LOS PRODUCTOS EN LA TABLA Y CREA EL PDF Y QR DE UN PEDIDO SIN ENVIAR EMAIL AL CLIENTE
// https://bonospremium.com/wp-json/custom/v1/insertar/20407
////////////////////////////////////////////////////////////////////////
function insert_pedido_api_endpoint() {
    register_rest_route('custom/v1', '/insertar/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'insert_pedido_api_endpoint_callback',
    ));
}
add_action('rest_api_init', 'insert_pedido_api_endpoint');
function insert_pedido_api_endpoint_callback(WP_REST_Request $request) {
    
    insertar_pedido_db($request['id']);
    
    $MyOrders = array(
        "error" => false,
        "msj"   => "Bonos regenerados con exito correctamente.",
    );

    return rest_ensure_response($MyOrders);
}
////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////
// ACTUALIZA LA FECHA DE MODIFICACION QUE ES IGUAL A LA FEHCA DE CANJEO DE LA TABLA DE LOS PEDISO
// http://bonospremium.com/wp-json/custom/v1/actualizarFechaPedidoCanjeado
////////////////////////////////////////////////////////////////////////
function api_actualizar_fecha_pedido_canjeado() {
    register_rest_route('custom/v1', '/actualizarFechaPedidoCanjeado', array(
        'methods' => 'GET',
        'callback' => 'actualizar_fecha_pedido_canjeado',
    ));
}
add_action('rest_api_init', 'api_actualizar_fecha_pedido_canjeado');
function actualizar_fecha_pedido_canjeado() {
    global $wpdb;

    $MyOrders   = [];
    $start_date = '2024-09-01';
    $end_date   = '2025-02-10'; // date("Y-m-d");

    $args = array(
        'post_type'      => 'shop_order',
        'post_status'    => 'cancelled', // 'completed', // Adjust the order status as needed
        'date_query'     => array(
            'after'     => $start_date,
            'before'    => $end_date,
            'inclusive' => true, // Include orders on the start and end dates
        ),
        'nopaging'       => true, // Retrieve all orders, remove if you want pagination
    );

    // Get orders using WP_Query
    $orders_query = new WP_Query($args);

    // Check if there are orders
    if ($orders_query->have_posts()) {
        while ($orders_query->have_posts()) {
            $orders_query->the_post();

            // Get order details as needed
            $order_id     = $orders_query->post->ID;
            $order_date   = $orders_query->post->post_date;
            $order_estado = $orders_query->post->post_status;
            $order_fecha  = $orders_query->post->post_modified; // $orders_query->post->post_modified_gmt;

            // if($order_estado == "wc-completed"){
            if($order_estado == "wc-cancelled"){
                $wpdb->update(
                    'hx4p_wc_pedidos_item',
                    array(
                        'fechaModificacion' => $order_fecha,
                        // 'estado' => 'Canjeado',
                    ),
                    array(
                        // 'fechaModificacion' => '0000-00-00 00:00:00',
                        'orderId' => $orders_query->post->ID,
                    )
                );

                $MyOrders[] = array(
                    "ID"        => $order_id, 
                    "Estado"    => $order_estado, 
                    "url"       => "https://bonospremium.com/wp-json/custom/v1/data/".$order_id,
                    "Date"      => $order_date,
                    "fCanjeado" => $order_fecha,
                );
            }
        }
    }

    // Restore global post data
    wp_reset_postdata();

    return rest_ensure_response($MyOrders);
}
////////////////////////////////////////////////////////////////////////
// wp_clear_scheduled_hook('updateLapsedOrders');
// ESTO CREA EL JOINS CON LAS TAREAS
function schedule_updateLapsedOrders() {
    if (!wp_next_scheduled('updateLapsedOrders')) {
        wp_schedule_event(time(), 'daily', 'updateLapsedOrders');
        error_log('✅ Tarea updateLapsedOrders programada.');
    }
}
add_action('init', 'schedule_updateLapsedOrders');
add_action('updateLapsedOrders', 'updateLapsedOrders');
function updateLapsedOrdersOLD() {
    global $wpdb;

    $args = array(
        'status'       => array('processing', 'on-hold', 'pending'),
        'date_created' => '<' . (time() - 90 * DAY_IN_SECONDS),
        'limit'        => -1,
    );
    $orders = wc_get_orders($args);

    if (empty($orders)) {
        error_log('❌ No hay pedidos antiguos para actualizar.');
        return;
    }

    // Construir la tabla en HTML para el correo
    $orders_text = "<html><body>";
    $orders_text .= "<h2>Pedidos Actualizados a Caducados</h2>";
    $orders_text .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    $orders_text .= "<tr>
                        <th>ID Pedido</th>
                        <th>Estado Anterior</th>
                        <th>Estado Nuevo</th>
                        <th>Fecha de Creación</th>
                        <th>Fecha de Modificación</th>
                    </tr>";

    $fecha_modificacion = current_time('mysql'); // Fecha actual en formato MySQL
    $quien = 'Tarea Automática - updateLapsedOrders'; // Quién hizo el cambio

    foreach ($orders as $order) {
        $order_id = $order->get_id();
        $order_status_before = wc_get_order_status_name($order->get_status());
        $order_date = $order->get_date_created()->date('Y-m-d H:i:s');

        // Actualizar el estado del pedido a 'lapsed'
        $order->update_status('lapsed', 'Pedido caducado, automáticamente por Tarea Automática.');

        // Actualizar la tabla personalizada hx4p_wc_pedidos_item
        $wpdb->update(
            'hx4p_wc_pedidos_item',
            array(
                'estado'             => 'Caducado',
                'fechaModificacion'  => $fecha_modificacion,
                'quien'              => $quien,
            ),
            array('orderid' => $order_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        // Agregar información a la tabla del correo
        $orders_text .= "<tr>
                            <td style='text-align: center;'>{$order_id}</td>
                            <td style='text-align: center;'>{$order_status_before}</td>
                            <td style='text-align: center;'>Caducado</td>
                            <td style='text-align: center;'>{$order_date}</td>
                            <td style='text-align: center;'>{$fecha_modificacion}</td>
                         </tr>";
    }

    $orders_text .= "</table>";
    $orders_text .= "</body></html>";

    // Cabeceras para enviar correo en formato HTML
    $headers = array('Content-Type: text/html; charset=UTF-8');

    // Enviar correos 
    wp_mail('info@bonospremium.com', 'Pedidos Caducados Automáticamente', $orders_text, $headers);
    // wp_mail('fericor@gmail.com', 'Pedidos Caducados Automáticamente', $orders_text, $headers);

    error_log('✅ Pedidos actualizados y correos enviados.');
}
function updateLapsedOrders() {
    global $wpdb;

    $args = array(
        'status'       => array('processing', 'on-hold', 'pending'),
        'date_created' => '<' . (time() - 90 * DAY_IN_SECONDS),
        'limit'        => -1,
    );
    $orders = wc_get_orders($args);

    if (empty($orders)) {
        error_log('❌ No hay pedidos antiguos para actualizar.');
        return;
    }

    // Construir la tabla en HTML para el correo
    $orders_text = "<html><body>";
    $orders_text .= "<h2>Pedidos Actualizados a Caducados</h2>";
    $orders_text .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    $orders_text .= "<tr>
                        <th>ID Pedido</th>
                        <th>Estado Anterior</th>
                        <th>Estado Nuevo</th>
                        <th>Fecha de Creación</th>
                        <th>Fecha de Modificación</th>
                    </tr>";

    $fecha_modificacion = current_time('mysql');
    $quien = 'Tarea Automática - updateLapsedOrders';

    foreach ($orders as $order) {
        $order_id = $order->get_id();
        $order_status_before = wc_get_order_status_name($order->get_status());
        $order_date = $order->get_date_created()->date('Y-m-d H:i:s');

        // Actualizar el estado del pedido a 'lapsed'
        $order->update_status('lapsed', 'Pedido caducado, automáticamente por Tarea Automática.');

        // Actualizar la tabla personalizada hx4p_wc_pedidos_item
        // Solo actualizar si estado_anterior no es 'Caducado'
        $resultado = $wpdb->query( $wpdb->prepare(
            "UPDATE hx4p_wc_wc_pedidos_item 
             SET estado = %s, 
                 fechaModificacion = %s, 
                 quien = %s 
             WHERE orderid = %d 
             AND (estado_anterior IS NULL OR estado_anterior != %s)",
            'Caducado',
            $fecha_modificacion,
            $quien,
            $order_id,
            'Caducado'
        ));

        // Si no se actualizó ninguna fila (ya estaba caducado), lo registramos
        if ($resultado === 0) {
            error_log("⚠️ Pedido {$order_id} ya estaba caducado, no se actualizó hx4p_wc_pedidos_item");
        }

        // Agregar información a la tabla del correo
        $orders_text .= "<tr>
                            <td style='text-align: center;'>{$order_id}</td>
                            <td style='text-align: center;'>{$order_status_before}</td>
                            <td style='text-align: center;'>Caducado</td>
                            <td style='text-align: center;'>{$order_date}</td>
                            <td style='text-align: center;'>{$fecha_modificacion}</td>
                         </tr>";
    }

    $orders_text .= "</table>";
    $orders_text .= "</body></html>";

    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail('info@bonospremium.com', 'Pedidos Caducados Automáticamente', $orders_text, $headers);

    error_log('✅ Pedidos actualizados y correos enviados.');
}



//////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////

function quitar_opciones_pantalla_gestor_productos() {
    $usuario_actual = wp_get_current_user();
    
    // Verificar si el usuario tiene el rol 'gestor_productos'
    if (in_array('auxiliar_bonospremium', (array) $usuario_actual->roles)) {
        add_filter('screen_options_show_screen', '__return_false');
    }
}
add_action('admin_init', 'quitar_opciones_pantalla_gestor_productos');


function menu_personalizado_gestor_productos() {
    // Obtener el usuario actual
    $usuario_actual = wp_get_current_user();

    // Si el usuario tiene el rol 'gestor_productos', modificar el menú
    if (in_array('auxiliar_bonospremium', (array) $usuario_actual->roles)) {

        // Eliminar todos los menús innecesarios
        remove_menu_page('index.php'); // Escritorio
        remove_menu_page('edit.php'); // Entradas
        remove_menu_page('upload.php'); // Medios
        remove_menu_page('edit.php?post_type=page'); // Páginas
        remove_menu_page('edit-comments.php'); // Comentarios
        remove_menu_page('themes.php'); // Apariencia
        remove_menu_page('plugins.php'); // Plugins
        // remove_menu_page('users.php'); // Usuarios
        remove_menu_page('tools.php'); // Herramientas
        remove_menu_page('options-general.php'); // Ajustes
		remove_menu_page('edit.php?post_type=elementor_library'); // Plantillas
		remove_menu_page('edit.php?post_type=tabs_group=library'); // Plantillas
		remove_menu_page('profile.php'); // Perfil
		
		// Dejar solo WooCommerce → Pedidos y Productos
		remove_submenu_page('woocommerce', 'wc-admin&path=/analytics'); 
		// remove_submenu_page('woocommerce', 'wc-admin&path=/marketing'); 

        remove_submenu_page('woocommerce', 'wc-admin&path=/customers'); // Eliminar clientes
        remove_submenu_page('woocommerce', 'wc-settings'); // Eliminar ajustes de WooCommerce
        remove_submenu_page('woocommerce', 'wc-status'); // Eliminar estado de WooCommerce
        remove_submenu_page('woocommerce', 'wc-addons'); // Eliminar extensiones de WooCommerce
		
		// Añadimos los menus a los pedidos de wc
		/*add_submenu_page(
			'woocommerce', // Slug del menú principal (WooCommerce)
			'Categorias', // Título de la página
			'Categorias', // Texto del menú
			'auxiliar_bonospremium', // Capacidad necesaria
			'edit-tags.php?taxonomy=product_cat&post_type=product' // URL del destino (Pedidos de WooCommerce)
		);*/
		
		add_submenu_page(
			'woocommerce',
			'Brevo',
			'Brevo',
			'auxiliar_bonospremium',       // nuestra capacidad personalizada
			'brevo-redirect',
			function() {
				wp_redirect('https://login.brevo.com/');
				exit;
			}
		);
    }
}
add_action('admin_menu', 'menu_personalizado_gestor_productos', 999);

function block_2024_posts_only($data, $postarr) {
    if ($data['post_type'] === 'post' && strpos($data['post_date'], '2024') === 0) {
        wp_die('No se pueden crear posts en 2024.');
    }
    return $data;
}
add_filter('wp_insert_post_data', 'block_2024_posts_only', 10, 2);





////////////////////////////////////////////////////////////////////////////////
// VALIDACIÓN DE EXISTENCIAS PARA YELMO CINE (ptn_wc_codes_extras)
////////////////////////////////////////////////////////////////////////////////

// 1. Función de comprobación de stock en base de datos (Retorna la cantidad)
if ( ! function_exists( 'bp_check_yelmo_cine_stock' ) ) {
    function bp_check_yelmo_cine_stock($product_id, $variation_id) {
        global $wpdb;

        $product_info = wc_get_product($product_id);
        if (!$product_info) return 0;

        $nombre_empresa = $product_info->get_meta('nombre_establecimiento');

        // Solo validamos si es CINE YELMO
        if (!preg_match('/\bCINE YELMO\b/i', $nombre_empresa)) {
            return 999; // Retornamos un número alto para productos que no son de Yelmo
        }

        $variation = wc_get_product($variation_id);
        if (!$variation) return 0;

        // Buscamos el nombre de la variación (tipo)
        $variation_name = "";
        $attributes = $variation->get_variation_attributes();
        foreach ($attributes as $attr_slug => $attr_value) {
            $term = get_term_by('slug', $attr_value, str_replace('attribute_', '', $attr_slug));
            if ($term) {
                $variation_name = $term->name;
            } else {
                $variation_name = $attr_value;
            }
        }

        if (empty($variation_name)) {
            $full_name = $variation->get_name();
            $array_name = explode(" - ", $full_name);
            $variation_name = isset($array_name[1]) ? trim($array_name[1]) : "";
        }

        // Consultamos la tabla hx4p_wc_codes_cinema
        $table_name = "hx4p_wc_codes_cinema"; 
        $stock_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE activo = 0 AND tipo = %s AND orderId = 0",
            $variation_name
        ));

        return intval($stock_count);
    }
}

// 2. Validación de seguridad al añadir al carrito
add_filter('woocommerce_add_to_cart_validation', 'bp_validate_yelmo_cine_stock_on_add', 10, 3);
function bp_validate_yelmo_cine_stock_on_add($passed, $product_id, $quantity) {
    if (isset($_POST['variation_id']) && $_POST['variation_id'] > 0) {
        $stock = bp_check_yelmo_cine_stock($product_id, $_POST['variation_id']);
        if ($stock <= 0) {
            wc_add_notice(__('⚠️ No hay entradas disponibles en esta selección.', 'woocommerce'), 'error');
            return false;
        }
    }
    return $passed;
}

// 3. AJAX Handler
add_action('wp_ajax_check_variation_stock', 'bp_ajax_check_variation_stock');
add_action('wp_ajax_nopriv_check_variation_stock', 'bp_ajax_check_variation_stock');
function bp_ajax_check_variation_stock() {
    $product_id = intval($_POST['product_id']);
    $variation_id = intval($_POST['variation_id']);

    $product_info = wc_get_product($product_id);
    if (!$product_info) {
        wp_send_json_error();
    }
    
    $nombre_empresa = $product_info->get_meta('nombre_establecimiento');
    $is_cine = preg_match('/\bCINE YELMO\b/i', $nombre_empresa);

    if (!$is_cine) {
        wp_send_json_success(array('is_cine' => false, 'stock_count' => 999));
    }

    $stock_count = bp_check_yelmo_cine_stock($product_id, $variation_id);
    wp_send_json_success(array('is_cine' => true, 'stock_count' => $stock_count));
}

// 4. Script Frontend
add_action('wp_footer', 'bp_yelmo_cine_stock_script');
function bp_yelmo_cine_stock_script() {
    if (!is_product()) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var $button = $('.single_add_to_cart_button');
        var $form = $('.variations_form');

        var originalBg = $button.css('background-color');
        var originalColor = $button.css('color');

        if (originalBg && originalBg !== 'rgba(0, 0, 0, 0)') {
            $('head').append('<style>.single_add_to_cart_button:disabled { background-color: ' + originalBg + ' !important; color: ' + originalColor + ' !important; cursor: not-allowed !important; }</style>');
        }

        $button.text('Comprar');

        $('.variations select').on('change', function() {
            $('.woocommerce-variation-price, .woocommerce-variation-availability').hide();
        });

        $(document).on('show_variation', function(event, variation) {
			$('.woocommerce-variation-price').hide();
            if (variation.variation_id) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'check_variation_stock',
                        product_id: $form.data('product_id'),
                        variation_id: variation.variation_id
                    },
                    success: function(response) {
                        var $price = $('.woocommerce-variation-price');
                        var $avail = $('.woocommerce-variation-availability');

                        if (response.success && response.data.is_cine) {
                            var stock = parseInt(response.data.stock_count);
                            
                            if (stock <= 0) {
                                // SIN STOCK
                                $button.text('Comprar').prop('disabled', true).css('opacity', '0.3');
                                $price.show();
                                $avail.html('<div style="color:red; font-size:11px; margin:10px 0;">No hay entradas disponibles en esta selección.</div>').show();
                            } else {
                                // CON STOCK - Muestra la cantidad disponible
                                $button.text('Comprar').prop('disabled', false).css('opacity', '1');
                                $price.show();
                                // $avail.html('<div style="font-size:11px; margin:10px 0;"> ' + stock + ' disponibles</div>').show();
                            }
                        } else {
                            // PRODUCTO NORMAL
                            $button.text('Comprar').prop('disabled', false).css('opacity', '1');
                            // $price.hide();
                            $avail.show();
                        }
                    }
                });
            }
        });
    });
    </script>
    <?php
}







//////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////

/**
 * BLOQUE UNIFICADO: Shortcode [contacto_bonospremium] 
 * (Envío Ajax + Reemplazo de contenido en Modal + WhatsApp Mensaje)
 */

// 1. PROCESADOR DE ENVÍO AJAX
add_action('wp_ajax_enviar_contacto_anunciante', 'procesar_contacto_anunciante');
add_action('wp_ajax_nopriv_enviar_contacto_anunciante', 'procesar_contacto_anunciante');

function procesar_contacto_anunciante() {
    $nombre     = sanitize_text_field($_POST['nombre']);
    $email_rem  = sanitize_email($_POST['email']);
    $mensaje    = sanitize_textarea_field($_POST['mensaje']);
    $prod_name  = sanitize_text_field($_POST['producto']);
    $prod_id    = intval($_POST['prod_id']);
    $id_empresa_raw = $_POST['id_empresa'];

    $id_empresa = is_numeric($id_empresa_raw) ? intval($id_empresa_raw) : 0;
    if (is_array($id_empresa_raw)) { $id_empresa = $id_empresa_raw['ID']; }
    if (is_object($id_empresa_raw)) { $id_empresa = $id_empresa_raw->ID; }

    $destinatario_fijo = "info@tupropiedadpremium.com";
    $asunto = "Consulta de " . $nombre . " por: " . $prod_name;
    $headers = array('Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $email_rem);

    $cuerpo  = "<h2>Nueva consulta desde BonosPremium</h2>";
    $cuerpo .= "<p><strong>Producto:</strong> $prod_name (Ref: #$prod_id)</p>";
    $cuerpo .= "<p><strong>Nombre del cliente:</strong> $nombre</p>";
    $cuerpo .= "<p><strong>Email del cliente:</strong> $email_rem</p>";
    $cuerpo .= "<p><strong>Mensaje:</strong><br>$mensaje</p>";

    $enviado = wp_mail($destinatario_fijo, $asunto, $cuerpo, $headers);

    if ($enviado) {
        $asunto_cliente = "Hemos recibido tu consulta sobre: " . $prod_name;
        $cuerpo_cliente  = "<h2>¡Hola, $nombre!</h2>";
        $cuerpo_cliente .= "<p>Gracias por contactar con nosotros. Hemos recibido correctamente tu consulta.</p>";
        $wp_enviado = wp_mail($email_rem, $asunto_cliente, $cuerpo_cliente, $headers);
        wp_send_json_success('Mensaje enviado correctamente.');
    } else {
        wp_send_json_error('Error al enviar el mensaje.');
    }
}

// 2. ESTILOS CSS (Corregido para centrado)
add_action('wp_head', function() {
    ?>
    <style>
        .bp-contact-card { padding: 30px; border: 1px solid #f0f0f0; background: #fff; border-radius: 15px; }
        .bp-contact-header { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; }
        .bp-contact-logo { width: 75px; height: 75px; border-radius: 50% !important; border: 3px solid #019cdb; object-fit: cover; }
        .bp-contact-initials { width: 75px; height: 75px; border-radius: 50%; background: #019cdb; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 24px; }
        .bp-contact-title h3 { margin: 0; font-size: 20px; color: #222; font-weight: 800; }
        .bp-locality { color: #019cdb; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 5px; }
        .bp-btn { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px; border-radius: 15px; font-weight: 700; text-decoration: none !important; cursor: pointer; border: none; width: 100%; margin-bottom: 12px; font-size: 15px; transition: 0.3s; }
        .bp-btn-primary { background: #019cdb; color: #fff !important; }
        .bp-btn-whatsapp { background: #25D366 !important; color: #fff !important; }
        .bp-btn-outline { background: #fff; color: #019cdb !important; border: 2px solid #019cdb; }
        .bp-social-row { display: flex; justify-content: center; gap: 20px; margin-top: 20px; border-top: 1px solid #f0f0f0; padding-top: 20px; }
        .bp-social-link { color: #444; font-size: 22px; text-decoration: none !important; }
        .bp-form-control { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #e0e0e0; border-radius: 10px; box-sizing: border-box; }
        
        /* FIX PARA CENTRADO DEL MODAL */
        #simple-modal {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important; /* Centrado perfecto */
            margin: 0 !important;
            max-width: 90%;
            width: 450px;
            z-index: 99999;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        #simple-modal-overlay {
            background-color: rgba(0,0,0,0.6) !important;
            backdrop-filter: blur(3px);
        }

        .bp-modal-response { padding: 30px; text-align: center; }
        .bp-modal-response i { font-size: 60px; margin-bottom: 20px; display: block; }
        .bp-modal-response h3 { font-weight: 800; color: #333; margin-bottom: 10px; }
    </style>
    <?php
});

// 3. SHORTCODE [contacto_bonospremium]
add_shortcode('contacto_bonospremium', function() {
    global $product;
    if ( ! is_a( $product, 'WC_Product' ) ) return '';

    $pid = $product->get_id();
    $raw_empresa = get_field('empresa_colaboradora', $pid);
    $id_empresa  = is_numeric($raw_empresa) ? $raw_empresa : ($raw_empresa['ID'] ?? ($raw_empresa->ID ?? 0));
    $user_meta_id = $id_empresa ? 'user_' . $id_empresa : false;

    $nombre_empresa = get_field('nombre_establecimiento', $pid) ?: ($user_meta_id ? get_field('nombre_establecimiento', $user_meta_id) : get_bloginfo('name'));
    $localidad      = get_field('localidad', $pid) ?: ($user_meta_id ? get_field('localidad', $user_meta_id) : '');
    $telefono       = get_field('telefono', $pid) ?: ($user_meta_id ? get_field('telefono', $user_meta_id) : '');
    $instagram      = get_field('instagram', $pid) ?: ($user_meta_id ? get_field('instagram', $user_meta_id) : '');
    $facebook       = get_field('facebook', $pid) ?: ($user_meta_id ? get_field('facebook', $user_meta_id) : '');
    $ocultar_compra = get_field('ocultar_compra', $pid);
	
	// ✅ AGREGAR ESTO:
    if ($ocultar_compra) {
        add_action('wp_footer', function() {
            echo '<style>.single-product form.cart, .single-product .single_add_to_cart_button { display: none !important; } #lblTuExperiencia { display: none !important; } #lblCondiciones { display: none !important; } </style>';
        }, 99);
    }

    ob_start();
    ?>
    <div class="bp-contact-card">
        <div class="bp-contact-actions">
            <?php if ($telefono) : 
                $tel_clean = preg_replace('/\D/', '', $telefono); 
                $wa_msg = rawurlencode("Hola, me interesa esta propiedad: " . $product->get_name() . ".");
                ?>
                <a href="tel:+34<?php echo $tel_clean; ?>" class="bp-btn bp-btn-outline">
                    <i class="fas fa-phone-alt"></i> Llamar ahora
                </a>
                <a href="https://wa.me/34<?php echo $tel_clean; ?>?text=<?php echo $wa_msg; ?>" target="_blank" class="bp-btn bp-btn-whatsapp">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="24" height="24" fill="#ffffff"><!--!Font Awesome Free v5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg> WhatsApp
                </a>
            <?php endif; ?>
            
            <button type="button" id="btn-abrir-contacto" class="bp-btn bp-btn-primary">
                <i class="fas fa-envelope"></i> Enviar Email
            </button>
        </div>

        <div class="bp-social-row">
            <?php if ($instagram) : ?>
                <a href="<?php echo esc_url($instagram); ?>" class="bp-social-link" target="_blank"><i class="fab fa-instagram"></i></a>
            <?php endif; ?>
            <?php if ($facebook) : ?>
                <a href="<?php echo esc_url($facebook); ?>" class="bp-social-link" target="_blank"><i class="fab fa-facebook-f"></i></a>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        function updateModalContent(html) {
            $('#bp-modal-container').fadeOut(200, function() {
                $(this).html(html).fadeIn(200);
            });
        }

        $('#btn-abrir-contacto').on('click', function() {
            $().simpleModal({
                title: "<?php echo esc_js($nombre_empresa); ?>",
                content: `
                    <div id="bp-modal-container" style="padding: 20px;">
                        <div id="bp-modal-form">
                            <input type="text" id="ct_nombre" placeholder="Tu Nombre" class="bp-form-control">
                            <input type="email" id="ct_email" placeholder="Tu Email" class="bp-form-control">
                            <textarea id="ct_mensaje" placeholder="Mensaje..." class="bp-form-control" style="height:120px;"></textarea>
                            <button type="button" id="btn-enviar-final" class="bp-btn bp-btn-primary">Enviar Mensaje</button>
                        </div>
                    </div>
                `
            });
        });

        $(document).on('click', '#btn-enviar-final', function() {
            const btn = $(this);
            const data = {
                action: 'enviar_contacto_anunciante',
                nombre: $('#ct_nombre').val(),
                email: $('#ct_email').val(),
                mensaje: $('#ct_mensaje').val(),
                producto: '<?php echo esc_js($product->get_name()); ?>',
                prod_id: '<?php echo $pid; ?>',
                id_empresa: <?php echo json_encode($raw_empresa); ?>
            };

            if(!data.nombre || !data.email) { 
                alert("Completa los campos obligatorios."); 
                return; 
            }

            btn.prop('disabled', true).text('Enviando...');

            $.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(res) {
                if(res.success) {
                    updateModalContent(`
                        <div class="bp-modal-response">
                            <i class="fas fa-check-circle" style="color: #25D366;"></i>
                            <h3>¡Mensaje Enviado!</h3>
                            <p>Tu consulta ha sido enviada correctamente.</p>
                            <!-- <button type="button" class="bp-btn bp-btn-primary simple-modal-close" style="margin-top:20px;">Cerrar Ventana</button> -->
                        </div>
                    `);
                } else {
                    alert("Error al enviar.");
                    btn.prop('disabled', false).text('Enviar Mensaje');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
});


////////////////////////////////////////////////////////////////////////////////
// VALIDACIÓN DE EXISTENCIAS PARA WEGOO (hx4p_series_codes)
////////////////////////////////////////////////////////////////////////////////

// 1. Función helper: SUMA los usos restantes (max_uses - current_uses)
if ( ! function_exists( 'bp_check_wegoo_stock' ) ) {
    function bp_check_wegoo_stock($target_id, $target_name = '', $parent_id = 0) {
        global $wpdb;
        if (!$target_id) return 0;
        
        $table = $wpdb->prefix . 'series_codes';
        
        // 1. Buscar por ID exacto de la variación en product_id
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(max_uses - current_uses) FROM {$table} 
             WHERE product_id = %d 
             AND is_active = 1 
             AND current_uses < max_uses",
            $target_id
        ));
        
        // 2. Si no hay stock, buscar si el código se asoció al ID del producto Padre
        if (empty($total) && !empty($parent_id) && $parent_id != $target_id) {
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(max_uses - current_uses) FROM {$table} 
                 WHERE product_id = %d 
                 AND is_active = 1 
                 AND current_uses < max_uses",
                $parent_id
            ));
        }

        // 3. Si sigue sin haber stock, buscar en la columna DESCRIPTION por el nombre de la variación
        if (empty($total) && !empty($target_name)) {
            $like_name = '%' . $wpdb->esc_like( $target_name ) . '%';
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(max_uses - current_uses) FROM {$table} 
                 WHERE description LIKE %s 
                 AND is_active = 1 
                 AND current_uses < max_uses",
                $like_name
            ));
        }
        
        return intval($total);
    }
}

// 2. Inyectar stock en cada variación
add_filter('woocommerce_available_variation', 'bp_add_wegoo_stock_to_variation', 10, 3);
function bp_add_wegoo_stock_to_variation($variation_data, $product, $variation) {
    $nombre_empresa = $product->get_meta('nombre_establecimiento');
    if (!preg_match('/\bWEGOO\b/i', $nombre_empresa)) {
        $variation_data['is_wegoo'] = false;
        return $variation_data;
    }
    
    // Pasamos ID, nombre de la variación y ID del padre
    $stock = bp_check_wegoo_stock($variation->get_id(), $variation->get_name(), $product->get_id());
    
    $variation_data['is_wegoo'] = true;
    $variation_data['wegoo_stock'] = $stock;
    
    return $variation_data;
}

// 3. Validación al añadir al carrito
add_filter('woocommerce_add_to_cart_validation', 'bp_validate_wegoo_stock_on_add', 15, 3);
function bp_validate_wegoo_stock_on_add($passed, $product_id, $quantity) {
    $product = wc_get_product($product_id);
    if (!$product) return $passed;
    
    $nombre_empresa = $product->get_meta('nombre_establecimiento');
    if (!preg_match('/\bWEGOO\b/i', $nombre_empresa)) {
        return $passed;
    }
    
    $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
    
    if ($variation_id) {
        $variation = wc_get_product($variation_id);
        $stock = bp_check_wegoo_stock($variation_id, $variation ? $variation->get_name() : '', $product_id);
    } else {
        $stock = bp_check_wegoo_stock($product_id, $product->get_name());
    }
    
    if ($stock <= 0) {
        wc_add_notice(__('⚠️ No hay usos WEGOO disponibles para esta selección.', 'woocommerce'), 'error');
        return false;
    }
    
    return $passed;
}

// 4. Script Frontend
add_action('wp_footer', 'bp_wegoo_stock_script', 11);
function bp_wegoo_stock_script() {
    if (!is_product()) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        
        $(document).on('show_variation', function(event, variation) {
            if (!variation.is_wegoo) return;
            
            var stock = parseInt(variation.wegoo_stock);
            var $button = $('.single_add_to_cart_button');
            var $price = $('.woocommerce-variation-price');
            var $avail = $('.woocommerce-variation-availability');
            
            function applyWegooState() {
                if (stock <= 0) {
                    $button.text('Comprar').prop('disabled', true).css('opacity', '0.3');
                    $price.show();
                    $avail.html('<div style="color:red; font-size:11px; margin:10px 0;">No hay usos WEGOO disponibles.</div>').show();
                } else {
                    $button.text('Comprar').prop('disabled', false).css('opacity', '1');
                    $price.show();
                    $avail.html('<div style="color:green; font-size:11px; margin:10px 0;">' + stock + ' usos disponibles</div>').show();
                }
            }
            
            applyWegooState();
            setTimeout(applyWegooState, 400);
            setTimeout(applyWegooState, 800);
        });
        
        // Productos simples WEGOO
        var $formSimple = $('form.cart');
        if ($formSimple.length && !$formSimple.hasClass('variations_form')) {
            if ($formSimple.data('is-wegoo')) {
                var simpleStock = parseInt($formSimple.data('wegoo-stock'));
                var $buttonSimple = $formSimple.find('.single_add_to_cart_button');
                
                if (simpleStock <= 0) {
                    $buttonSimple.text('Comprar').prop('disabled', true).css('opacity', '0.3');
                    $formSimple.before('<div class="wegoo-no-stock" style="color:red; font-size:14px; margin:10px 0; padding:10px; border:1px solid red; border-radius:5px;">⚠️ No hay usos WEGOO disponibles para este producto.</div>');
                } else {
                    $formSimple.before('<div class="wegoo-stock" style="color:green; font-size:14px; margin:10px 0;">' + simpleStock + ' usos disponibles</div>');
                }
            }
        }
    });
    </script>
    <?php
}

// 5. Productos simples WEGOO
add_action('woocommerce_before_add_to_cart_form', 'bp_wegoo_simple_product_data');
function bp_wegoo_simple_product_data() {
    global $product;
    if (!$product || !$product->is_type('simple')) return;
    
    $nombre_empresa = $product->get_meta('nombre_establecimiento');
    if (!preg_match('/\bWEGOO\b/i', $nombre_empresa)) return;
    
    $stock = bp_check_wegoo_stock($product->get_id(), $product->get_name());
    
    echo '<script>jQuery(document).ready(function($){ $("form.cart").attr("data-is-wegoo", "1").attr("data-wegoo-stock", "' . esc_js($stock) . '"); });</script>';
}

////////////////////////////////////////////////////////////////////////////////
// FIN WEGOO STOCK CHECKER
////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////
/* API PARA MOSTRAR LA INFORMACION DE LA TABLA DE PEDIDOS DE BONOSPREMIUM PERSONALZADA */
add_action('rest_api_init', function () {
    // Volvemos al nombre original que es más limpio
    register_rest_route('bonospremium/v1', '/items', array(
        'methods' => 'GET',
        'callback' => 'bp_obtener_items_personalizados',
        'permission_callback' => function($request) {
            // Verificamos nuestro Token Secreto de forma infalible
            $token = $request->get_param('auth_token');
            return $token === 'BonosSync2026!';
        }
    ));
});

function bp_obtener_items_personalizados($request) {
    global $wpdb;
    
    $dias = (int) $request->get_param('dias');
    $per_page = (int) $request->get_param('per_page');
    $page = (int) $request->get_param('page');
    
    if ($per_page <= 0) $per_page = 50;
    if ($page <= 0) $page = 1;
    $offset = ($page - 1) * $per_page;
    
	$tabla = $wpdb->prefix . 'wc_pedidos_item';
    $where = "";
    $params = array();
    
    if ($dias > 0) {
        $fecha = date('Y-m-d H:i:s', strtotime("-$dias days"));
        $where = "WHERE fechaModificacion >= %s";
        $params[] = $fecha;
    }
    
    $query = "SELECT * FROM {$tabla} $where ORDER BY fechaModificacion DESC LIMIT %d OFFSET %d";
    array_push($params, $per_page, $offset);
    
    $resultados = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
    
    if ($wpdb->last_error) {
        return new WP_Error('db_error', 'Error en base de datos: ' . $wpdb->last_error, array('status' => 500));
    }
    
    return new WP_REST_Response($resultados, 200);
}
/* ++++++++++ */





/* ================================ */
add_action('woocommerce_before_order_notes', 'custom_checkout_field', 15);
function custom_checkout_field($checkout){
?>
	<script>
        jQuery('.wooccm-additional-fields p:eq(2)').after('<p class="form-row wooccm-conditional-child form-row-wide wooccm-field wooccm-field-wooccm3 wooccm-type-select woocommerce-validated" id="additional_wooccm3_field" data-priority="30" style="display: none !important" data-conditional-parent="additional_wooccm0" data-conditional-parent-value="1"> <a href="javascript:;" id="bpPrevio_pdf" class=""> <i class="fas fa-eye"></i> Vista previa del BonoPremium</a> </p>');
        jQuery('#account_password').attr("placeholder", "");
      
        jQuery('#bpPrevio_pdf').on("click", function(e){
            e.stopPropagation();
            let TEXTO     = jQuery("#additional_wooccm1").val();
            let PLANTILLA = jQuery("#additional_wooccm2").val();

            let HTML_IMG_PLANTILLA = "";
            let IMAGEN             = "";

            if(PLANTILLA == "Selecciona plantilla de felicitación"){
                HTML_IMG_PLANTILLA = "";
            }else{
                if(PLANTILLA == "Día de la Madre"){ IMAGEN = "qr_diadelamadre1"; }
                if(PLANTILLA == "Día del Padre"){ IMAGEN = "qr_diadelpadre1"; }
                if(PLANTILLA == "Cumpleaños Hombre"){ IMAGEN = "qr_cumpleanoshombre1"; }
                if(PLANTILLA == "Cumpleaños Mujer"){ IMAGEN = "qr_cumpleanosmujer1"; }
                if(PLANTILLA == "Navidad"){ IMAGEN = "qr_navidad1"; }
                if(PLANTILLA == "Papá Noel"){ IMAGEN = "qr_papanoel1"; }
                if(PLANTILLA == "Reyes Magos"){ IMAGEN = "qr_reyesmagos1"; }
                if(PLANTILLA == "San Valentin"){ IMAGEN = "qr_sanvalentin1"; }
                
                HTML_IMG_PLANTILLA = "<img src=\"https://bonospremium.com/admin/assets/imgPlugin/"+IMAGEN+".png\" style=\"width: 600px; padding: 0px;\">";
            }

            console.log(PLANTILLA);


            let HTML_PLANTILLA = "<div style=\"width: 100%; background-color: #039CDC; padding: 0px; border-radius: 0px; color: #FFFFFF; text-align: center; margin-bottom: 0px;\"> <img style=\"width: 300px; padding: 20px;\" src=\"https://bonospremium.com/wp-content/uploads/2023/09/trans.png\" alt=\"\"> </div> <div style=\"text-align: center;\"> "+HTML_IMG_PLANTILLA+" <p style=\"font-style: oblique;\">"+TEXTO+"</p> </div>";

            jQuery().simpleModal({
                name: "BonoPremium",
                title: " ",
                size: "large",
                content: "<p class=\'textBPView\'>"+HTML_PLANTILLA+"</p>"
            });

            return false;
        });
    </script>

<?php } ?>