<?php
/**
 * admin-bp-settings.php
 * Panel de ajustes de la tienda BonosPremium (LZ)
 *  - Colores de la tienda (variables CSS)
 *  - SMTP Brevo (envío de emails)
 *  - Emails de destino de los formularios (contacto, promociona, ofertas)
 */
if (!defined('ABSPATH')) exit;

if (!defined('BP_SETTINGS_KEY')) define('BP_SETTINGS_KEY', 'bp_theme_settings');

/* ─────────── Lectura de ajustes (front + admin) ─────────── */

function bp_get_settings() {
    $defaults = array(
        // Colores
        'primary_color'        => '#039CDC',
        'header_bg'            => '',
        'header_bg_mobile'     => '',
        'footer_bg'            => '',
        'page_bg'              => '',
        'text_color'           => '',
        'button_bg'            => '',
        'button_hover'         => '',
        'sale_color'           => '',
        // SMTP Brevo
        'smtp_host'            => 'smtp-relay.brevo.com',
        'smtp_port'            => 587,
        'smtp_user'            => '',
        'smtp_pass'            => '',
        'smtp_from'            => 'info@bonospremium.com',
        'smtp_from_name'       => 'BonosPremium',
        // Formularios
        'form_contacto_to'     => 'info@bonospremium.com',
        'form_contacto_subject' => '📩 Nuevo mensaje de contacto - BonosPremium',
        'form_promociona_to'   => 'info@bonospremium.com',
        'form_promociona_subject' => '🏪 Promociona tu negocio - BonosPremium',
        'form_ofertas_to'      => 'info@bonospremium.com',
        'form_ofertas_subject' => '🎁 Solicitud de recibir ofertas - BonosPremium',
        // Integraciones
        'recaptcha_site_key'   => '',
        'recaptcha_secret_key' => '',
        'ga_id'                => '',
        'gtm_id'               => '',
        'maps_api_key'         => '',
        'maptiler_api_key'     => '',
        'related_max_products'  => 8,
        'map_provider'         => 'osm',
        // Social Login (URLs para botones)
        'google_client_id' => '',
        'apple_client_id'  => '',
    );
    $saved = get_option(BP_SETTINGS_KEY, array());
    return wp_parse_args(is_array($saved) ? $saved : array(), $defaults);
}

function bp_get_setting($key, $default = '') {
    $s = bp_get_settings();
    return (isset($s[$key]) && $s[$key] !== '') ? $s[$key] : $default;
}

/* Helpers de color */
function bp_hex_to_rgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' . hexdec(substr($hex, 4, 2));
}
function bp_adjust_brightness($hex, $percent) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    $r = max(0, min(255, hexdec(substr($hex,0,2)) + round(255 * $percent)));
    $g = max(0, min(255, hexdec(substr($hex,2,2)) + round(255 * $percent)));
    $b = max(0, min(255, hexdec(substr($hex,4,2)) + round(255 * $percent)));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/* ─────────── Guardar ─────────── */

add_action('admin_post_bp_save_settings', 'bp_save_settings_handler');
function bp_save_settings_handler() {
    if (!current_user_can('manage_options')) wp_die('Sin permisos');
    check_admin_referer('bp_settings_nonce');

    $in = isset($_POST['bp']) && is_array($_POST['bp']) ? $_POST['bp'] : array();
    $s = bp_get_settings();

    // Colores (solo hex válidos; vacío = dejar el valor actual)
    foreach (array('primary_color','header_bg','header_bg_mobile','footer_bg','page_bg','text_color','button_bg','button_hover','sale_color') as $k) {
        if (isset($in[$k]) && $in[$k] !== '') {
            $hex = sanitize_hex_color($in[$k]);
            if ($hex) $s[$k] = $hex;
        }
    }
    // SMTP
    if (isset($in['smtp_host']) && $in['smtp_host'] !== '') $s['smtp_host'] = sanitize_text_field($in['smtp_host']);
    if (isset($in['smtp_port']) && $in['smtp_port'] !== '') $s['smtp_port'] = absint($in['smtp_port']);
    if (isset($in['smtp_user'])) $s['smtp_user'] = sanitize_email($in['smtp_user']);
    if (isset($in['smtp_pass'])) $s['smtp_pass'] = sanitize_text_field($in['smtp_pass']); // contraseña: se guarda tal cual
    if (isset($in['smtp_from']) && $in['smtp_from'] !== '') $s['smtp_from'] = sanitize_email($in['smtp_from']);
    if (isset($in['smtp_from_name'])) $s['smtp_from_name'] = sanitize_text_field($in['smtp_from_name']);
    // Formularios
    foreach (array('form_contacto_to','form_promociona_to','form_ofertas_to') as $k) {
        if (isset($in[$k]) && $in[$k] !== '') $s[$k] = sanitize_email($in[$k]);
    }
    foreach (array('form_contacto_subject','form_promociona_subject','form_ofertas_subject') as $k) {
        if (isset($in[$k]) && $in[$k] !== '') $s[$k] = sanitize_text_field($in[$k]);
    }
    // Integraciones (se guardan aunque estén vacías para poder limpiarlas)
    $s['recaptcha_site_key']   = isset($in['recaptcha_site_key'])   ? sanitize_text_field($in['recaptcha_site_key'])   : '';
    $s['recaptcha_secret_key'] = isset($in['recaptcha_secret_key']) ? sanitize_text_field($in['recaptcha_secret_key']) : '';
    $s['ga_id']                = isset($in['ga_id'])                ? sanitize_text_field($in['ga_id'])                : '';
    $s['gtm_id']               = isset($in['gtm_id'])               ? sanitize_text_field($in['gtm_id'])               : '';
    $s['maps_api_key']         = isset($in['maps_api_key'])         ? sanitize_text_field($in['maps_api_key'])         : '';
    $s['maptiler_api_key']     = isset($in['maptiler_api_key'])     ? sanitize_text_field($in['maptiler_api_key'])     : '';
    $s['related_max_products']   = isset($in['related_max_products']) ? absint($in['related_max_products']) : 8;
    $s['map_provider']         = isset($in['map_provider']) && in_array($in['map_provider'], array('gmap', 'osm', 'maptiler'), true) ? $in['map_provider'] : 'osm';
    // Social Login (se guardan aunque vacias)
    $s['google_client_id'] = isset($in['google_client_id']) ? sanitize_text_field($in['google_client_id']) : '';
    $s['apple_client_id']  = isset($in['apple_client_id'])  ? sanitize_text_field($in['apple_client_id'])  : '';

    update_option(BP_SETTINGS_KEY, $s);
    wp_safe_redirect(add_query_arg('bp_saved', '1', wp_get_referer() ?: admin_url('admin.php?page=bp-settings')));
    exit;
}

/* ─────────── Enviar email de prueba ─────────── */

add_action('admin_post_bp_test_email', 'bp_send_test_email_handler');
function bp_send_test_email_handler() {
    if (!current_user_can('manage_options')) wp_die('Sin permisos');
    check_admin_referer('bp_test_email_nonce');

    $to = isset($_POST['bp_test_to']) ? sanitize_email($_POST['bp_test_to']) : '';
    $result = 'error';
    $msg = '';

    if (!is_email($to)) {
        $msg = 'Email de destino no válido.';
    } else {
        $subject = '🔔 Email de prueba - BonosPremium';
        $body = "Este es un email de prueba enviado desde el panel BonosPremium.\n\n";
        $body .= "Si estás leyendo esto, la configuración SMTP funciona correctamente.\n";
        $body .= 'Fecha: ' . date('d/m/Y H:i:s') . "\n";
        $body .= 'Transporte: ' . (defined('BP_BREVO_USER') && BP_BREVO_USER ? 'SMTP Brevo' : 'mail() del sistema') . "\n";

        $sent = wp_mail($to, $subject, $body);

        // Capturar errores SMTP si los hay
        global $phpmailer;
        $smtp_err = '';
        if ($phpmailer && is_a($phpmailer, 'PHPMailer\PHPMailer\PHPMailer')) {
            $smtp_err = $phpmailer->ErrorInfo;
        }

        if ($sent) {
            $result = 'success';
            $msg = 'Email de prueba enviado a ' . $to . '. Revisa la bandeja de entrada (y spam).';
        } else {
            $msg = 'El email NO se pudo enviar. Error: ' . ($smtp_err ? $smtp_err : 'desconocido (revisa las credenciales SMTP)');
        }
    }

    $back = wp_get_referer() ?: admin_url('admin.php?page=bp-settings');
    wp_safe_redirect(add_query_arg(array('bp_test' => $result, 'bp_test_msg' => urlencode($msg)), $back));
    exit;
}

/* ─────────── Menú y página (tabs) ─────────── */

add_action('admin_menu', function () {
    add_menu_page(
        'Ajustes BonosPremium',
        'BonosPremium',
        'manage_options',
        'bp-settings',
        'bp_settings_page',
        'dashicons-admin-generic',
        3
    );
});

function bp_settings_page() {
    if (!current_user_can('manage_options')) return;
    $s = bp_get_settings();
    $saved = isset($_GET['bp_saved']);
    ?>
    <div class="wrap bp-admin-wrap">
        <div class="bp-admin-header">
            <div class="bp-admin-header__icon">⚙️</div>
            <div>
                <h1>Ajustes de la tienda BonosPremium</h1>
                <p class="bp-admin-header__sub">Personaliza colores, email SMTP y formularios de esta tienda.</p>
            </div>
        </div>

        <?php if ($saved): ?>
            <div class="notice notice-success is-dismissible"><p><strong>✅ Ajustes guardados.</strong></p></div>
        <?php endif; ?>

        <?php
        // Aviso del resultado del email de prueba
        if (isset($_GET['bp_test'])) {
            $bp_test_msg = isset($_GET['bp_test_msg']) ? wp_unslash($_GET['bp_test_msg']) : '';
            $bp_test_cls = ($_GET['bp_test'] === 'success') ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . $bp_test_cls . ' is-dismissible"><p>' . esc_html($bp_test_msg) . '</p></div>';
        }
        ?>

        <h2 class="nav-tab-wrapper bp-nav-tabs">
            <a href="javascript:void(0)" class="nav-tab nav-tab-active" data-tab="tab-colores">🎨 Colores</a>
            <a href="javascript:void(0)" class="nav-tab" data-tab="tab-smtp">✉️ SMTP Brevo</a>
            <a href="javascript:void(0)" class="nav-tab" data-tab="tab-formularios">📨 Formularios</a>
            <a href="javascript:void(0)" class="nav-tab" data-tab="tab-integraciones">🔌 Integraciones</a>
            <a href="javascript:void(0)" class="nav-tab" data-tab="tab-social">🔐 Social Login</a>
        </h2>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="bp-settings-form">
            <input type="hidden" name="action" value="bp_save_settings" />
            <?php wp_nonce_field('bp_settings_nonce'); ?>

            <!-- 🎨 COLORES -->
            <div id="tab-colores" class="bp-tab">
                <div class="bp-section">
                    <h3 class="bp-section__title">🎯 Acento principal</h3>
                    <p class="bp-section__desc">El color que identifica a la tienda: enlaces, iconos y elementos destacados. <span class="bp-badge">📧 Este color también se usa en los emails y PDFs de los pedidos</span></p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr class="bp-row-email">
                            <th scope="row"><label for="bp-primary">Color principal</label> <span class="bp-badge bp-badge--inline">📧 email + PDF</span></th>
                            <td>
                                <div class="bp-color-row">
                                    <input type="color" id="bp-primary" name="bp[primary_color]" value="<?php echo esc_attr($s['primary_color']); ?>" />
                                    <input type="text" name="bp[primary_color]" value="<?php echo esc_attr($s['primary_color']); ?>" class="small-text bp-hex" />
                                </div>
                                <p class="description">Se aplica también a emails y PDFs del plugin (cabecera del email, bordes del bono y condiciones).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bp-button">Color botones</label></th>
                            <td>
                                <div class="bp-color-row">
                                    <input type="color" id="bp-button" name="bp[button_bg]" value="<?php echo esc_attr($s['button_bg'] ?: '#039CDC'); ?>" />
                                    <input type="text" name="bp[button_bg]" value="<?php echo esc_attr($s['button_bg']); ?>" class="small-text bp-hex" placeholder="vacío = color principal" />
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bp-sale">Color ofertas / rebajas</label></th>
                            <td>
                                <div class="bp-color-row">
                                    <input type="color" id="bp-sale" name="bp[sale_color]" value="<?php echo esc_attr($s['sale_color'] ?: '#039CDC'); ?>" />
                                    <input type="text" name="bp[sale_color]" value="<?php echo esc_attr($s['sale_color']); ?>" class="small-text bp-hex" placeholder="vacío = color principal" />
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="bp-section">
                    <h3 class="bp-section__title">🧭 Header y footer</h3>
                    <p class="bp-section__desc">Fondos de las zonas superior e inferior de la web.</p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="bp-header">Header (fondo)</label></th>
                            <td>
                                <div class="bp-color-row">
                                    <input type="color" id="bp-header" name="bp[header_bg]" value="<?php echo esc_attr($s['header_bg'] ?: '#039CDC'); ?>" />
                                    <input type="text" name="bp[header_bg]" value="<?php echo esc_attr($s['header_bg']); ?>" class="small-text bp-hex" placeholder="vacío = color principal" />
                                </div>
                                <p class="description">Solo afecta al header. Puedes poner un gradiente: <code>linear-gradient(135deg, #039CDC, #027ba8)</code></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bp-header-mobile">Header móvil</label></th>
                            <td>
                                <div class="bp-color-row">
                                    <input type="color" id="bp-header-mobile" name="bp[header_bg_mobile]" value="<?php echo esc_attr($s['header_bg_mobile'] ?: '#039CDC'); ?>" />
                                    <input type="text" name="bp[header_bg_mobile]" value="<?php echo esc_attr($s['header_bg_mobile']); ?>" class="small-text bp-hex" placeholder="vacío = header normal" />
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bp-footer">Footer (fondo)</label></th>
                            <td>
                                <div class="bp-color-row">
                                    <input type="color" id="bp-footer" name="bp[footer_bg]" value="<?php echo esc_attr($s['footer_bg'] ?: '#32373c'); ?>" />
                                    <input type="text" name="bp[footer_bg]" value="<?php echo esc_attr($s['footer_bg']); ?>" class="small-text bp-hex" />
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="bp-section">
                    <h3 class="bp-section__title">📄 Página y texto</h3>
                    <p class="bp-section__desc">Fondo general y color del texto de la web.</p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="bp-bg">Fondo de página</label></th>
                            <td>
                                <div class="bp-color-row">
                                    <input type="color" id="bp-bg" name="bp[page_bg]" value="<?php echo esc_attr($s['page_bg'] ?: '#ffffff'); ?>" />
                                    <input type="text" name="bp[page_bg]" value="<?php echo esc_attr($s['page_bg']); ?>" class="small-text bp-hex" />
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bp-text">Color de texto</label></th>
                            <td>
                                <div class="bp-color-row">
                                    <input type="color" id="bp-text" name="bp[text_color]" value="<?php echo esc_attr($s['text_color'] ?: '#090909'); ?>" />
                                    <input type="text" name="bp[text_color]" value="<?php echo esc_attr($s['text_color']); ?>" class="small-text bp-hex" />
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- ✉️ SMTP BREVO -->
            <div id="tab-smtp" class="bp-tab" style="display:none;">
                <div class="bp-section">
                    <h3 class="bp-section__title">🔌 Configuración del servidor</h3>
                    <p class="bp-section__desc">Usado por <code>wp_mail()</code> y los formularios. Si usuario/contraseña están vacíos se usa el <code>mail()</code> del sistema.</p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="smtp-host">Servidor (host)</label></th>
                            <td><input type="text" id="smtp-host" name="bp[smtp_host]" value="<?php echo esc_attr($s['smtp_host']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="smtp-port">Puerto</label></th>
                            <td><input type="number" id="smtp-port" name="bp[smtp_port]" value="<?php echo esc_attr($s['smtp_port']); ?>" class="small-text" /> <span class="description">587 con TLS (Brevo)</span></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="smtp-user">Usuario</label></th>
                            <td><input type="email" id="smtp-user" name="bp[smtp_user]" value="<?php echo esc_attr($s['smtp_user']); ?>" class="regular-text" placeholder="usuario@smtp-brevo.com" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="smtp-pass">Contraseña / SMTP Key</label></th>
                            <td><input type="password" id="smtp-pass" name="bp[smtp_pass]" value="<?php echo esc_attr($s['smtp_pass']); ?>" class="regular-text" autocomplete="new-password" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="smtp-from">Remitente (desde dónde se envía)</label></th>
                            <td><input type="email" id="smtp-from" name="bp[smtp_from]" value="<?php echo esc_attr($s['smtp_from']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="smtp-from-name">Nombre del remitente</label></th>
                            <td><input type="text" id="smtp-from-name" name="bp[smtp_from_name]" value="<?php echo esc_attr($s['smtp_from_name']); ?>" class="regular-text" /></td>
                        </tr>
                    </table>
                </div>

                <div class="bp-section bp-section--accent">
                    <h3 class="bp-section__title">🚀 Email de prueba</h3>
                    <p class="bp-section__desc">Guarda los ajustes primero y luego pulsa <strong>Enviar prueba</strong> para comprobar que el SMTP funciona. Llegará un email con el resultado.</p>
                    <div class="bp-test-row">
                        <input type="email" id="bp-test-to" name="bp_test_to" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" class="regular-text" placeholder="email@destino.com" />
                        <button type="submit" id="bp-send-test" name="bp_send_test" class="button button-secondary">🚀 Enviar prueba</button>
                    </div>
                </div>
            </div>

            <!-- 📨 FORMULARIOS -->
            <div id="tab-formularios" class="bp-tab" style="display:none;">
                <p class="bp-section__desc" style="margin:0 0 16px;">A qué email llega cada formulario y el asunto del correo.</p>

                <div class="bp-section">
                    <h3 class="bp-section__title">📩 Contacto</h3>
                    <p class="bp-section__desc"><code>/contacta-con-nosotros/</code></p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label>Email destino</label></th>
                            <td><input type="email" name="bp[form_contacto_to]" value="<?php echo esc_attr($s['form_contacto_to']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Asunto</label></th>
                            <td><input type="text" name="bp[form_contacto_subject]" value="<?php echo esc_attr($s['form_contacto_subject']); ?>" class="regular-text" /></td>
                        </tr>
                    </table>
                </div>

                <div class="bp-section">
                    <h3 class="bp-section__title">🏪 Promociona tu negocio</h3>
                    <p class="bp-section__desc"><code>/promociona-tu-negocio/</code></p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label>Email destino</label></th>
                            <td><input type="email" name="bp[form_promociona_to]" value="<?php echo esc_attr($s['form_promociona_to']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Asunto</label></th>
                            <td><input type="text" name="bp[form_promociona_subject]" value="<?php echo esc_attr($s['form_promociona_subject']); ?>" class="regular-text" /></td>
                        </tr>
                    </table>
                </div>

                <div class="bp-section">
                    <h3 class="bp-section__title">🎁 Recibir ofertas</h3>
                    <p class="bp-section__desc"><code>/recibir-ofertas/</code></p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label>Email destino</label></th>
                            <td><input type="email" name="bp[form_ofertas_to]" value="<?php echo esc_attr($s['form_ofertas_to']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Asunto</label></th>
                            <td><input type="text" name="bp[form_ofertas_subject]" value="<?php echo esc_attr($s['form_ofertas_subject']); ?>" class="regular-text" /></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- 🔌 INTEGRACIONES -->
            <div id="tab-integraciones" class="bp-tab" style="display:none;">
                <div class="bp-section">
                    <h3 class="bp-section__title">🛡️ Google reCAPTCHA v3</h3>
                    <p class="bp-section__desc">Protege los formularios de la web contra spam. Si las claves están vacías, los formularios funcionan sin verificación. Obtén las claves en <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener noreferrer">Google reCAPTCHA Admin</a> (tipo: v3).</p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="recaptcha-site">Site Key (v3)</label></th>
                            <td><input type="text" id="recaptcha-site" name="bp[recaptcha_site_key]" value="<?php echo esc_attr($s['recaptcha_site_key']); ?>" class="regular-text" placeholder="6Lc... (dejar vacío para desactivar)" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="recaptcha-secret">Secret Key (v3)</label></th>
                            <td><input type="password" id="recaptcha-secret" name="bp[recaptcha_secret_key]" value="<?php echo esc_attr($s['recaptcha_secret_key']); ?>" class="regular-text" autocomplete="new-password" placeholder="6Lc... (dejar vacío para desactivar)" /></td>
                        </tr>
                    </table>
                </div>

                <div class="bp-section">
                    <h3 class="bp-section__title">📊 Google Analytics (GA4)</h3>
                    <p class="bp-section__desc">ID de medición de Google Analytics 4 (formato <code>G-XXXXXXXXXX</code>). Si está vacío no se carga nada. Respeta el consentimiento de cookies (Consent Mode v2).</p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="ga-id">GA4 ID</label></th>
                            <td><input type="text" id="ga-id" name="bp[ga_id]" value="<?php echo esc_attr($s['ga_id']); ?>" class="regular-text" placeholder="G-XXXXXXXXXX" /></td>
                        </tr>
                    </table>
                </div>

                <div class="bp-section">
                    <h3 class="bp-section__title">🏷️ Google Tag Manager</h3>
                    <p class="bp-section__desc">ID de contenedor de GTM (formato <code>GTM-XXXXXXX</code>). Útil para conversiones de Google Ads. Si está vacío no se carga nada.</p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="gtm-id">GTM ID</label></th>
                            <td><input type="text" id="gtm-id" name="bp[gtm_id]" value="<?php echo esc_attr($s['gtm_id']); ?>" class="regular-text" placeholder="GTM-XXXXXXX" /></td>
                        </tr>
                    </table>
                </div>

                <div class="bp-section">
                    <h3 class="bp-section__title">🗺️ Mapa de bonos (MapTiler)</h3>
                    <p class="bp-section__desc">El mapa de bonos usa el <strong>MapTiler SDK</strong> con la capa de calle. Pega aquí tu API Key de MapTiler.</p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="maptiler-key">MapTiler API Key</label></th>
                            <td><input type="text" id="maptiler-key" name="bp[maptiler_api_key]" value="<?php echo esc_attr($s['maptiler_api_key']); ?>" class="regular-text" placeholder="Tu clave de MapTiler..." style="min-width:320px;" />
                                <p class="description" style="margin-top:6px;">La consigues gratis en <a href="https://cloud.maptiler.com" target="_blank" rel="noopener">cloud.maptiler.com</a>. Sin clave el mapa no puede cargar.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="bp-section">
                    <h3 class="bp-section__title">🔄 Productos relacionados</h3>
                    <p class="bp-section__desc">Máximo de productos a mostrar en el scroller horizontal de la página de producto.</p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="related-max">Máximo productos</label></th>
                            <td><input type="number" id="related-max" name="bp[related_max_products]" value="<?php echo esc_attr($s['related_max_products'] ?: 8); ?>" class="small-text" min="1" max="30" /> <span class="description">Número máximo de bonos relacionados a mostrar (1-30).</span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- 🔐 SOCIAL LOGIN -->
            <div id="tab-social" class="bp-tab" style="display:none;">

                <div class="bp-section">
                    <h3 class="bp-section__title">🔵 Google Login</h3>
                    <p class="bp-section__desc">Configura el login con Google en <code>/auth/google/</code>. Crea un proyecto en <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console</a>, activa la API de OAuth y genera un Client ID + Client Secret. La URL de redirección permitida debe ser: <code><?php echo esc_url(home_url('/auth/google/callback/')); ?></code></p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="google-client-id">🔑 Client ID</label></th>
                            <td><input type="text" id="google-client-id" name="bp[google_client_id]" value="<?php echo esc_attr($s['google_client_id']); ?>" class="regular-text" style="min-width:360px;" placeholder="123456789-xxxxx.apps.googleusercontent.com" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="google-client-secret">🤫 Client Secret</label></th>
                            <td><input type="password" id="google-client-secret" name="bp[google_client_secret]" value="<?php echo esc_attr($s['google_client_secret']); ?>" class="regular-text" style="min-width:360px;" autocomplete="new-password" /></td>
                        </tr>
                    </table>
                </div>

                <div class="bp-section">
                    <h3 class="bp-section__title">🍏 Apple Sign in (opcional)</h3>
                    <p class="bp-section__desc">Configura el login con Apple en <code>/auth/apple/</code>. Necesitas suscripción Apple Developer. Crea un Service ID en Apple Developer, activa "Sign in with Apple" y genera una Key. URL de redirección: <code><?php echo esc_url(home_url('/auth/apple/callback/')); ?></code></p>
                    <table class="form-table bp-form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="apple-client-id">🔑 Service ID (Client ID)</label></th>
                            <td><input type="text" id="apple-client-id" name="bp[apple_client_id]" value="<?php echo esc_attr($s['apple_client_id']); ?>" class="regular-text" style="min-width:360px;" placeholder="com.tudominio.servicio" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="apple-team-id">🏢 Team ID</label></th>
                            <td><input type="text" id="apple-team-id" name="bp[apple_team_id]" value="<?php echo esc_attr($s['apple_team_id']); ?>" class="regular-text" placeholder="De tu cuenta Apple Developer" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="apple-key-id">🔐 Key ID</label></th>
                            <td><input type="text" id="apple-key-id" name="bp[apple_key_id]" value="<?php echo esc_attr($s['apple_key_id']); ?>" class="regular-text" placeholder="De tu Key de Sign in with Apple" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="apple-private-key">🗝️ Private Key (.p8)</label></th>
                            <td><textarea id="apple-private-key" name="bp[apple_private_key]" rows="5" class="large-text" style="font-family:monospace;font-size:12px;" placeholder="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"><?php echo esc_textarea($s['apple_private_key']); ?></textarea>
                                <p class="description">Pega el contenido completo del archivo .p8 descargado de Apple Developer.</p>
                            </td>
                        </tr>
                    </table>
                    <p class="bp-section__desc" style="margin-top:14px;"><strong>Nota:</strong> Apple solo muestra el email en el primer login. En logins posteriores usa un ID anónimo.</p>
                </div>

            </div>

            <div class="bp-submit-bar">
                <button type="submit" class="button button-primary button-large">💾 Guardar ajustes</button>
            </div>
        </form>
    </div>

    <style>
    .bp-admin-wrap { max-width: 1100px; }
    .bp-admin-header {
        display: flex; align-items: center; gap: 14px;
        background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 60%);
        border: 1px solid #e3e8ee; border-radius: 14px;
        padding: 20px 24px; margin: 10px 0 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .bp-admin-header__icon {
        font-size: 34px; line-height: 1;
        background: #fff; border: 1px solid #e3e8ee; border-radius: 12px;
        padding: 10px 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    }
    .bp-admin-header h1 { margin: 0; font-size: 22px; font-weight: 700; }
    .bp-admin-header__sub { margin: 4px 0 0; color: #6b7280; font-size: 13px; }

    .bp-nav-tabs { margin-bottom: 0 !important; border-bottom: 0 !important; }
    .bp-nav-tabs .nav-tab {
        border-radius: 10px 10px 0 0;
        font-weight: 600;
        padding: 9px 20px;
        margin-right: 4px;
        background: #f6f7f7;
    }
    .bp-nav-tabs .nav-tab-active { background: #fff; }

    .bp-tab {
        background: #fff;
        border: 1px solid #e3e8ee;
        border-top: 3px solid #039CDC;
        border-radius: 0 12px 12px 12px;
        padding: 8px 24px 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }

    .bp-section {
        background: #fafbfc;
        border: 1px solid #eef1f5;
        border-radius: 12px;
        padding: 18px 22px;
        margin-top: 20px;
    }
    .bp-section--accent {
        background: #f0f9ff;
        border-color: #cde9f7;
    }
    .bp-section__title {
        margin: 0 0 4px;
        font-size: 15px;
        font-weight: 700;
        color: #1d2327;
    }
    .bp-section__desc {
        margin: 0 0 14px;
        color: #6b7280;
        font-size: 12.5px;
    }

    .bp-form-table { margin-top: 4px; }
    .bp-form-table tr {
        border-bottom: 1px solid #eef1f5;
    }
    .bp-form-table tr:last-child { border-bottom: 0; }
    .bp-form-table th {
        width: 240px;
        padding: 14px 12px 14px 0;
        vertical-align: top;
    }
    .bp-form-table td {
        padding: 12px 0 12px 8px;
    }
    .bp-form-table input[type="text"].regular-text,
    .bp-form-table input[type="email"].regular-text,
    .bp-form-table input[type="password"].regular-text {
        padding: 8px 12px;
        border-radius: 8px;
        border-color: #d5dbe2;
        box-shadow: none;
    }
    .bp-form-table input[type="text"]:focus,
    .bp-form-table input[type="email"]:focus,
    .bp-form-table input[type="password"]:focus {
        border-color: #039CDC;
        box-shadow: 0 0 0 1px #039CDC;
    }
    .bp-color-row {
        display: flex; align-items: center; gap: 10px;
    }
    .bp-color-row input[type="color"] {
        width: 44px; height: 32px;
        padding: 2px; border-radius: 8px;
        border: 1px solid #d5dbe2;
        background: #fff; cursor: pointer;
    }
    .bp-hex {
        font-family: "SF Mono", Consolas, monospace !important;
        font-size: 13px !important;
    }
    .bp-badge {
        display: inline-block;
        background: #e6f6ff;
        color: #0369a1;
        border: 1px solid #b7e2f8;
        border-radius: 20px;
        padding: 2px 10px;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.6;
        vertical-align: middle;
    }
    .bp-badge--inline {
        margin-left: 6px;
        background: #fff3cd;
        color: #8a6d00;
        border-color: #f0df9a;
    }
    .bp-row-email {
        background: #f4fafe;
        border-radius: 10px;
    }
    .bp-row-email th,
    .bp-row-email td {
        border-bottom: 0 !important;
    }
    .bp-test-row {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .bp-test-row input[type="email"] { min-width: 280px; }

    .bp-submit-bar {
        margin-top: 18px;
        padding: 16px 24px;
        background: #fff;
        border: 1px solid #e3e8ee;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        text-align: right;
    }
    .bp-submit-bar .button-primary {
        background: #039CDC; border-color: #039CDC;
        border-radius: 8px; padding: 6px 22px; font-weight: 600;
    }
    .bp-submit-bar .button-primary:hover { background: #027ba8; border-color: #027ba8; }
    </style>

    <script>
    (function($) {
        $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
            e.preventDefault();
            $('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.bp-tab').hide();
            $('#' + $(this).data('tab')).show();
        });

        // Sincronizar picker de color <-> campo texto (mismo name, se envía el texto)
        $('input[type="color"]').each(function() {
            var $color = $(this);
            var $text = $color.next('input[type="text"]');
            if (!$text.length) return;
            $color.on('input change', function() { $text.val($color.val()); });
            $text.on('input change', function() {
                var v = $text.val().trim();
                if (/^#[0-9a-fA-F]{3}$|^#[0-9a-fA-F]{6}$/.test(v)) $color.val(v);
            });
        });

        // Botón "Enviar prueba": cambia el action del form al handler de prueba
        $('#bp-send-test').on('click', function(e) {
            e.preventDefault();
            var $form = $('#bp-settings-form');
            $form.find('input[name="action"]').val('bp_test_email');
            $form.find('#_wpnonce').val('<?php echo wp_create_nonce('bp_test_email_nonce'); ?>');
            $form.submit();
        });
    })(jQuery);
    </script>
    <?php
}
