<?php
// Añadir widget en el dashboard
add_action('wp_dashboard_setup', 'bono_credit_dashboard_widget');

function bono_credit_dashboard_widget() {
    wp_add_dashboard_widget(
        'bono_credit_status_widget',
        'Estado del Sistema de Créditos',
        'bono_credit_dashboard_widget_content'
    );
}

function bono_credit_dashboard_widget_content() {
    $enabled = bono_credit_is_enabled();
    $status = $enabled ? 'Activado' : 'Desactivado';
    $status_color = $enabled ? '#28a745' : '#dc3545';
    $status_icon = $enabled ? '✅' : '⛔';
    
    global $wpdb;
    
    // Estadísticas
    $total_credit = $wpdb->get_var(
        "SELECT SUM(saldo) FROM {$wpdb->prefix}usuario_creditos"
    ) ?: 0;
    
    $active_users = $wpdb->get_var(
        "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}usuario_creditos WHERE saldo > 0"
    ) ?: 0;
    
    $today_transactions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}credito_transacciones WHERE DATE(fecha_transaccion) = %s",
        date('Y-m-d')
    )) ?: 0;
    
    ?>
    <div style="padding: 10px;">
        <div style="background: <?php echo $status_color; ?>; color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <h3 style="margin: 0; color: white;">
                <?php echo $status_icon; ?> Sistema <?php echo $status; ?>
            </h3>
        </div>
        
        <table style="width: 100%;">
            <tr>
                <td><strong>Crédito total:</strong></td>
                <td style="text-align: right;"><?php echo number_format($total_credit, 2); ?> €</td>
            </tr>
            <tr>
                <td><strong>Usuarios activos:</strong></td>
                <td style="text-align: right;"><?php echo $active_users; ?></td>
            </tr>
            <tr>
                <td><strong>Transacciones hoy:</strong></td>
                <td style="text-align: right;"><?php echo $today_transactions; ?></td>
            </tr>
        </table>
        
        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
            <a href="<?php echo admin_url('admin.php?page=bono-credit-settings'); ?>" class="button button-primary">
                Configurar
            </a>
            
            <?php if ($enabled): ?>
            <a href="<?php echo admin_url('admin.php?page=bono-credit-history'); ?>" class="button">
                Ver Historial
            </a>
            <?php endif; ?>
        </div>
        
        <?php if (!$enabled): ?>
        <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
            <small>
                <strong>⚠️ Sistema desactivado</strong><br>
                Los usuarios no pueden usar crédito en nuevas compras.
            </small>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// Añadir enlace rápido en barra de admin
add_action('admin_bar_menu', 'bono_credit_admin_bar_link', 999);

function bono_credit_admin_bar_link($wp_admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $enabled = bono_credit_is_enabled();
    $status_icon = $enabled ? '✅' : '⛔';
    
    $wp_admin_bar->add_node(array(
        'id'    => 'bono-credit-status',
        'title' => $status_icon . ' Crédito BonosPremium',
        'href'  => admin_url('admin.php?page=bono-credit-settings'),
        'meta'  => array(
            'title' => $enabled ? 'Sistema activado' : 'Sistema desactivado',
        ),
    ));
    
    // Submenú
    /*$wp_admin_bar->add_node(array(
        'parent' => 'bono-credit-status',
        'id'     => 'bono-credit-settings',
        'title'  => '⚙️ Configuración',
        'href'   => admin_url('admin.php?page=bono-credit-settings'),
    ));
    
    $wp_admin_bar->add_node(array(
        'parent' => 'bono-credit-status',
        'id'     => 'bono-credit-history',
        'title'  => '📊 Historial',
        'href'   => admin_url('admin.php?page=bono-credit-history'),
    ));*/
    
    // Toggle rápido
    $toggle_action = $enabled ? 'desactivar' : 'activar';
    $toggle_text = $enabled ? '⛔ Desactivar' : '✅ Activar';
    $toggle_url = wp_nonce_url(
        admin_url('admin-post.php?action=bono_toggle_quick&status=' . $toggle_action),
        'bono_toggle_quick'
    );
    
    $wp_admin_bar->add_node(array(
        'parent' => 'bono-credit-status',
        'id'     => 'bono-credit-toggle',
        'title'  => $toggle_text,
        'href'   => $toggle_url,
    ));
}

// Procesar toggle rápido
add_action('admin_post_bono_toggle_quick', 'bono_toggle_quick_handler');

function bono_toggle_quick_handler() {
    if (!current_user_can('manage_options')) {
        // wp_die('No tienes permisos');
    }
    
    check_admin_referer('bono_toggle_quick');
    
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $new_status = ($status === 'activar') ? 'yes' : 'no';
    
    update_option('bono_credit_enabled', $new_status);
    
    // wp_redirect(admin_url('admin.php?page=bono-credit-settings'));
    wp_redirect(admin_url('admin.php?page=wc-admin'));
    
    exit;
}