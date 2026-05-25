<?php
// Procesar crédito BonosPremium cuando el pedido se paga
add_action('woocommerce_payment_complete', 'bono_process_credit_on_payment', 10, 1);
add_action('woocommerce_order_status_processing', 'bono_process_credit_on_payment', 10, 1);
add_action('woocommerce_order_status_completed', 'bono_process_credit_on_payment', 10, 1);

function bono_process_credit_on_payment($order_id) {
    $order = wc_get_order($order_id);
    
    // Verificar si ya se procesó
    if ($order->get_meta('_bono_credit_processed') === 'yes') {
        return;
    }
    
    // Verificar si hay crédito BonosPremium para usar
    $credit_amount = $order->get_meta('_bono_credit_amount', true);
    $user_id = $order->get_meta('_bono_credit_user_id', true);
    
    if (!$credit_amount || !$user_id || floatval($credit_amount) <= 0) {
        return;
    }
    
    $amount = floatval($credit_amount);
    
    // Deducir crédito BonosPremium
    $result = bono_deduct_credit_simple($user_id, $amount, $order_id);
    
    if ($result['success']) {
        // Actualizar orden
        $order->update_meta_data('_bono_credit_processed', 'yes');
        $order->update_meta_data('_bono_credit_processed_date', current_time('mysql'));
        $order->update_meta_data('_bono_credit_new_balance', $result['new_balance']);
        
        $order->add_order_note(
            sprintf(
                __('Crédito BonosPremium procesado: %s € descontados. Nuevo saldo: %s €', 'bonospremium'),
                number_format($amount, 2),
                number_format($result['new_balance'], 2)
            )
        );
        
        $order->save();
        
        // Enviar email
        bono_send_credit_email_simple($user_id, $order_id, $amount, $result['new_balance']);
        
        // Mensaje de éxito
        error_log('Crédito BonosPremium procesado exitosamente para orden ' . $order_id);
        
    } else {
        // Error
        $order->add_order_note(
            __('ERROR al procesar crédito BonosPremium: ', 'bonospremium') . $result['error']
        );
        $order->save();
        
        error_log('Error procesando crédito BonosPremium para orden ' . $order_id . ': ' . $result['error']);
    }
}

// Función simple para deducir crédito BonosPremium
function bono_deduct_credit_simple($user_id, $amount, $order_id) {
    global $wpdb;
    
    // Obtener saldo actual
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
        $user_id
    ));
    
    if (!$result) {
        return array('success' => false, 'error' => 'Usuario no encontrado');
    }
    
    $current_balance = floatval($result->saldo);
    
    if ($current_balance < $amount) {
        return array('success' => false, 'error' => 'Saldo insuficiente');
    }
    
    $new_balance = $current_balance - $amount;
    
    // Actualizar saldo
    $updated = $wpdb->update(
        $wpdb->prefix . 'usuario_creditos',
        array('saldo' => $new_balance),
        array('user_id' => $user_id),
        array('%f'),
        array('%d')
    );
    
    if ($updated === false) {
        return array('success' => false, 'error' => 'Error actualizando saldo');
    }
    
    // Registrar transacción
    $inserted = $wpdb->insert(
        $wpdb->prefix . 'credito_transacciones',
        array(
            'user_id' => $user_id,
            'tipo' => 'debito',
            'monto' => $amount,
            'saldo_anterior' => $current_balance,
            'saldo_nuevo' => $new_balance,
            'descripcion' => 'Compra BonosPremium #' . $order_id,
            'order_id' => $order_id,
            'fecha_transaccion' => current_time('mysql')
        )
    );
    
    if (!$inserted) {
        // Revertir si no se pudo registrar transacción
        $wpdb->update(
            $wpdb->prefix . 'usuario_creditos',
            array('saldo' => $current_balance),
            array('user_id' => $user_id),
            array('%f'),
            array('%d')
        );
        return array('success' => false, 'error' => 'Error registrando transacción');
    }
    
    return array('success' => true, 'new_balance' => $new_balance);
}

// Email simple
function bono_send_credit_email_simple($user_id, $order_id, $amount, $new_balance) {
    $user = get_user_by('id', $user_id);
    
    if (!$user) {
        return;
    }
    
    $to = $user->user_email;
    $subject = 'Crédito BonosPremium usado en tu compra #' . $order_id;
    
    $message = "
    Hola {$user->display_name},
    
    Se ha usado crédito BonosPremium en tu compra #{$order_id}.
    
    Crédito BonosPremium usado: " . number_format($amount, 2) . " €
    Nuevo saldo: " . number_format($new_balance, 2) . " €
    
    Gracias,
    BonosPremium
    ";
    
    // wp_mail($to, $subject, $message);
}

// Mostrar info en página de agradecimiento
add_action('woocommerce_thankyou', 'bono_show_credit_thankyou', 5, 1);

function bono_show_credit_thankyou($order_id) {
    $order = wc_get_order($order_id);
    $credit_used = $order->get_meta('_bono_credit_amount', true);
    
    if ($credit_used && floatval($credit_used) > 0) {
        $new_balance = $order->get_meta('_bono_credit_new_balance', true);
        $processed = $order->get_meta('_bono_credit_processed');
        
        echo '<div class="bono-thankyou-message" style="padding: 20px; background: #e8f4fd; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4361ee;">';
        echo '<h3 style="color: #4361ee; margin-top: 0;">';
        echo '<i class="fas fa-wallet"></i> ';
        echo 'Uso de Crédito BonosPremium';
        echo '</h3>';
        
        echo '<p>Se han usado <strong>' . number_format($credit_used, 2) . ' €</strong> de tu crédito.</p>';
        
        if ($processed === 'yes' && $new_balance) {
            echo '<p>Tu nuevo saldo: <strong style="color: #28a745;">' . number_format($new_balance, 2) . ' €</strong></p>';
            echo '<div class="alert alert-success" style="margin-top: 10px;">';
            echo '<i class="fas fa-check-circle"></i> ';
            echo 'Crédito BonosPremium procesado correctamente.';
            echo '</div>';
        } elseif ($processed === 'no') {
            echo '<div class="alert alert-info" style="margin-top: 10px;">';
            echo '<i class="fas fa-info-circle"></i> ';
            echo 'El crédito BonosPremium se procesará automáticamente en breve.';
            echo '</div>';
        }
        
        echo '</div>';
    }
}

// Debug hook para ver qué está pasando
// add_action('wp_footer', 'bono_debug_info', 999);

function bono_debug_info() {
    if (current_user_can('administrator') && is_checkout()) {
        echo '<!-- BONO DEBUG INFO -->';
        echo '<div style="position: fixed; bottom: 10px; right: 10px; background: white; padding: 10px; border: 1px solid #ccc; z-index: 9999; font-size: 12px;">';
        echo '<strong>Bono Credit Debug:</strong><br>';
        
        $user_id = get_current_user_id();
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
            $user_id
        ));
        
        echo 'Saldo: ' . ($result ? $result->saldo : '0') . '<br>';
        echo 'Session credit: ' . (WC()->session ? WC()->session->get('bono_credit_to_use') : 'no session') . '<br>';
        echo '</div>';
    }
}