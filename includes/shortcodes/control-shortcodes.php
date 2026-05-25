<?php
// Shortcode para mostrar/ocultar crédito en checkout
add_shortcode('bono_credit_checkout', 'bono_credit_checkout_shortcode');

function bono_credit_checkout_shortcode() {
    if (!bono_credit_is_enabled()) {
        return '<div class="alert alert-info">El sistema de créditos está desactivado temporalmente.</div>';
    }
    
    ob_start();
    bono_display_credit_option();
    return ob_get_clean();
}

// Shortcode para ver estado del sistema
add_shortcode('bono_credit_status', 'bono_credit_status_shortcode');

function bono_credit_status_shortcode() {
    $enabled = bono_credit_is_enabled();
    $status_text = $enabled ? '🟢 ACTIVADO' : '🔴 DESACTIVADO';
    $status_class = $enabled ? 'text-success' : 'text-danger';
    
    $output = '<div class="bono-system-status">';
    $output .= '<h3>Estado del Sistema de Créditos</h3>';
    $output .= '<p><strong>Estado:</strong> <span class="' . $status_class . '">' . $status_text . '</span></p>';
    
    if (current_user_can('manage_options')) {
        $output .= '<p><small>Para cambiar la configuración, ve a <a href="' . admin_url('admin.php?page=bono-credit-settings') . '">Configuración de Créditos</a></small></p>';
    }
    
    $output .= '</div>';
    
    return $output;
}

// Shortcode para botón de activación/desactivación (solo admin)
add_shortcode('bono_credit_toggle', 'bono_credit_toggle_shortcode');

function bono_credit_toggle_shortcode() {
    if (!current_user_can('manage_options')) {
        return '<p>No tienes permisos para cambiar esta configuración.</p>';
    }
    
    $enabled = bono_credit_is_enabled();
    $action = $enabled ? 'desactivar' : 'activar';
    $nonce = wp_create_nonce('bono_toggle_credit');
    
    $output = '<div class="bono-toggle-control">';
    $output .= '<h4>Control Rápido del Sistema</h4>';
    $output .= '<p>Estado actual: <strong>' . ($enabled ? 'Activado' : 'Desactivado') . '</strong></p>';
    
    $output .= '<form method="post" action="" style="margin: 15px 0;">';
    $output .= '<input type="hidden" name="bono_toggle_action" value="' . $action . '">';
    $output .= '<input type="hidden" name="bono_toggle_nonce" value="' . $nonce . '">';
    
    if ($enabled) {
        $output .= '<button type="submit" class="button button-danger" onclick="return confirm(\'¿Seguro que quieres desactivar el sistema de créditos?\')">';
        $output .= '⛔ Desactivar Sistema';
        $output .= '</button>';
        $output .= '<p class="description">Desactivar: Los usuarios no podrán usar crédito en nuevas compras.</p>';
    } else {
        $output .= '<button type="submit" class="button button-primary">';
        $output .= '✅ Activar Sistema';
        $output .= '</button>';
        $output .= '<p class="description">Activar: Los usuarios podrán usar crédito en nuevas compras.</p>';
    }
    
    $output .= '</form>';
    $output .= '</div>';
    
    // Procesar el toggle
    if (isset($_POST['bono_toggle_action']) && isset($_POST['bono_toggle_nonce'])) {
        if (wp_verify_nonce($_POST['bono_toggle_nonce'], 'bono_toggle_credit')) {
            $new_status = ($_POST['bono_toggle_action'] === 'activar') ? 'yes' : 'no';
            update_option('bono_credit_enabled', $new_status);
            
            $output .= '<div class="notice notice-success"><p>Sistema ' . $action . 'do correctamente.</p></div>';
            
            // Redirigir para evitar reenvío del formulario
            echo '<script>setTimeout(function(){ location.reload(); }, 1000);</script>';
        }
    }
    
    return $output;
}