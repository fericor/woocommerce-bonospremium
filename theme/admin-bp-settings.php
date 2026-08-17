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
    <div class="wrap">
        <h1>⚙️ Ajustes de la tienda BonosPremium</h1>
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

        <h2 class="nav-tab-wrapper" style="margin-top:12px;">
            <a href="javascript:void(0)" class="nav-tab nav-tab-active" data-tab="tab-colores">🎨 Colores</a>
            <a href="javascript:void(0)" class="nav-tab" data-tab="tab-smtp">✉️ SMTP Brevo</a>
            <a href="javascript:void(0)" class="nav-tab" data-tab="tab-formularios">📨 Formularios</a>
        </h2>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="bp-settings-form">
            <input type="hidden" name="action" value="bp_save_settings" />
            <?php wp_nonce_field('bp_settings_nonce'); ?>

            <!-- 🎨 COLORES -->
            <div id="tab-colores" class="bp-tab">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="bp-primary">Color principal</label></th>
                        <td>
                            <input type="color" id="bp-primary" name="bp[primary_color]" value="<?php echo esc_attr($s['primary_color']); ?>" />
                            <input type="text" name="bp[primary_color]" value="<?php echo esc_attr($s['primary_color']); ?>" class="small-text" />
                            <p class="description">Color de acento del tema: enlaces, iconos y elementos destacados.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bp-header">Header (fondo)</label></th>
                        <td>
                            <input type="color" id="bp-header" name="bp[header_bg]" value="<?php echo esc_attr($s['header_bg'] ?: '#039CDC'); ?>" />
                            <input type="text" name="bp[header_bg]" value="<?php echo esc_attr($s['header_bg']); ?>" class="small-text" placeholder="vacío = color principal" />
                            <p class="description">Solo afecta al header. Puedes poner un gradiente: <code>linear-gradient(135deg, #039CDC, #027ba8)</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bp-header-mobile">Header móvil</label></th>
                        <td>
                            <input type="color" id="bp-header-mobile" name="bp[header_bg_mobile]" value="<?php echo esc_attr($s['header_bg_mobile'] ?: '#039CDC'); ?>" />
                            <input type="text" name="bp[header_bg_mobile]" value="<?php echo esc_attr($s['header_bg_mobile']); ?>" class="small-text" placeholder="vacío = header normal" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bp-footer">Footer (fondo)</label></th>
                        <td>
                            <input type="color" id="bp-footer" name="bp[footer_bg]" value="<?php echo esc_attr($s['footer_bg'] ?: '#32373c'); ?>" />
                            <input type="text" name="bp[footer_bg]" value="<?php echo esc_attr($s['footer_bg']); ?>" class="small-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bp-bg">Fondo de página</label></th>
                        <td>
                            <input type="color" id="bp-bg" name="bp[page_bg]" value="<?php echo esc_attr($s['page_bg'] ?: '#ffffff'); ?>" />
                            <input type="text" name="bp[page_bg]" value="<?php echo esc_attr($s['page_bg']); ?>" class="small-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bp-text">Color de texto</label></th>
                        <td>
                            <input type="color" id="bp-text" name="bp[text_color]" value="<?php echo esc_attr($s['text_color'] ?: '#090909'); ?>" />
                            <input type="text" name="bp[text_color]" value="<?php echo esc_attr($s['text_color']); ?>" class="small-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bp-button">Color botones</label></th>
                        <td>
                            <input type="color" id="bp-button" name="bp[button_bg]" value="<?php echo esc_attr($s['button_bg'] ?: '#039CDC'); ?>" />
                            <input type="text" name="bp[button_bg]" value="<?php echo esc_attr($s['button_bg']); ?>" class="small-text" placeholder="vacío = color principal" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bp-sale">Color ofertas/rebajas</label></th>
                        <td>
                            <input type="color" id="bp-sale" name="bp[sale_color]" value="<?php echo esc_attr($s['sale_color'] ?: '#039CDC'); ?>" />
                            <input type="text" name="bp[sale_color]" value="<?php echo esc_attr($s['sale_color']); ?>" class="small-text" placeholder="vacío = color principal" />
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ✉️ SMTP BREVO -->
            <div id="tab-smtp" class="bp-tab" style="display:none;">
                <p class="description">Usado por <code>wp_mail()</code> y los formularios. Si usuario/contraseña están vacíos se usa el <code>mail()</code> del sistema.</p>
                <table class="form-table" role="presentation">
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
                    <tr>
                        <th scope="row"><label for="bp-test-to">📨 Email de prueba</label></th>
                        <td>
                            <input type="email" id="bp-test-to" name="bp_test_to" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" class="regular-text" placeholder="email@destino.com" />
                            <button type="submit" name="bp_send_test" class="button" style="margin-left:6px;">🚀 Enviar prueba</button>
                            <p class="description">Guarda los ajustes primero y luego pulsa <strong>Enviar prueba</strong> para comprobar que el SMTP funciona. Llegará un email con el resultado.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 📨 FORMULARIOS -->
            <div id="tab-formularios" class="bp-tab" style="display:none;">
                <p class="description">A qué email llega cada formulario y el asunto del correo.</p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Contacto<br><small><code>/contacta-con-nosotros/</code></small></th>
                        <td>
                            <label>Email destino</label>
                            <input type="email" name="bp[form_contacto_to]" value="<?php echo esc_attr($s['form_contacto_to']); ?>" class="regular-text" /><br>
                            <label>Asunto</label>
                            <input type="text" name="bp[form_contacto_subject]" value="<?php echo esc_attr($s['form_contacto_subject']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Promociona tu negocio<br><small><code>/promociona-tu-negocio/</code></small></th>
                        <td>
                            <label>Email destino</label>
                            <input type="email" name="bp[form_promociona_to]" value="<?php echo esc_attr($s['form_promociona_to']); ?>" class="regular-text" /><br>
                            <label>Asunto</label>
                            <input type="text" name="bp[form_promociona_subject]" value="<?php echo esc_attr($s['form_promociona_subject']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Recibir ofertas<br><small><code>/recibir-ofertas/</code></small></th>
                        <td>
                            <label>Email destino</label>
                            <input type="email" name="bp[form_ofertas_to]" value="<?php echo esc_attr($s['form_ofertas_to']); ?>" class="regular-text" /><br>
                            <label>Asunto</label>
                            <input type="text" name="bp[form_ofertas_subject]" value="<?php echo esc_attr($s['form_ofertas_subject']); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
            </div>

            <p class="submit"><button type="submit" class="button button-primary button-large">💾 Guardar ajustes</button></p>
        </form>
    </div>

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
