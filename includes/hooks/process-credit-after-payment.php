<?php
// Procesar crédito BonosPremium después de que se complete el pago principal
add_action('woocommerce_payment_complete', 'bono_process_credit_after_payment', 20, 1);
add_action('woocommerce_order_status_processing', 'bono_process_credit_after_payment', 20, 1);
add_action('woocommerce_order_status_completed', 'bono_process_credit_after_payment', 20, 1);

function bono_process_credit_after_payment($order_id) {
    $order = wc_get_order($order_id);
    
    // Verificar si ya se procesó el crédito BonosPremium
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
    
    // Procesar deducción del crédito BonosPremium
    $result = bono_deduct_user_credit($user_id, $amount, $order_id);
    
    if ($result['success']) {
        // Marcar como procesado
        $order->update_meta_data('_bono_credit_processed', 'yes');
        $order->update_meta_data('_bono_credit_processed_date', current_time('mysql'));
        $order->update_meta_data('_bono_credit_new_balance', $result['new_balance']);
        
        // Añadir nota
        $order->add_order_note(
            sprintf(
                __('Se descontaron %s € del crédito BonosPremium del cliente. Nuevo saldo: %s €', 'bonospremium'),
                number_format($amount, 2),
                number_format($result['new_balance'], 2)
            )
        );
        
        // Recalcular total si es necesario
        $order_total = $order->get_total();
        $paid_total = $order->get_total_paid();
        
        // Si el crédito + pago cubren todo, marcar como pagado
        if (($paid_total + $amount) >= $order_total) {
            // Ya está pagado por el otro método + crédito BonosPremium
        }
        
        $order->save();
        
        // Enviar email de confirmación
        bono_send_credit_confirmation_email($user_id, $order_id, $amount, $result['new_balance']);
        
    } else {
        // Error
        $order->add_order_note(
            __('Error al procesar el crédito BonosPremium: ', 'bonospremium') . $result['error']
        );
        $order->save();
    }
}

// Función para deducir crédito BonosPremium
function bono_deduct_user_credit($user_id, $amount, $order_id) {
    global $wpdb;
    
    try {
        // 1. Obtener saldo
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
            $user_id
        ));
        
        if (!$result) {
            throw new Exception('Usuario sin crédito BonosPremium registrado');
        }
        
        $current_balance = floatval($result->saldo);
        
        // 2. Verificar saldo suficiente
        if ($current_balance < $amount) {
            throw new Exception('Saldo insuficiente');
        }
        
        $new_balance = $current_balance - $amount;
        
        // 3. Actualizar saldo
        $updated = $wpdb->update(
            $wpdb->prefix . 'usuario_creditos',
            array('saldo' => $new_balance),
            array('user_id' => $user_id),
            array('%f'),
            array('%d')
        );
        
        if ($updated === false) {
            throw new Exception('Error actualizando saldo');
        }
        
        // 4. Registrar transacción
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
            throw new Exception('Error registrando transacción');
        }
        
        return array(
            'success' => true,
            'new_balance' => $new_balance
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

// Email de confirmación
function bono_send_credit_confirmation_email($user_id, $order_id, $amount, $new_balance) {
    $user = get_user_by('id', $user_id);
    
    if (!$user) {
        return;
    }
    
    $to = $user->user_email;
    $subject = 'Uso de crédito BonosPremium confirmado - Orden #' . $order_id;
    
    $message = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <div style="background: #4361ee; color: white; padding: 20px; text-align: center;">
            <h2>Crédito BonosPremium Utilizado</h2>
        </div>
        <div style="background: #f9f9f9; padding: 20px;">
            <p>Hola ' . $user->display_name . ',</p>
            <p>Se ha usado crédito BonosPremium en tu orden <strong>#' . $order_id . '</strong>.</p>
            
            <div style="background: white; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <p><strong>Crédito BonosPremium usado:</strong> ' . number_format($amount, 2) . ' €</p>
                <p><strong>Nuevo saldo:</strong> <span style="color: #28a745; font-weight: bold;">' . number_format($new_balance, 2) . ' €</span></p>
            </div>
            
            <p>Gracias por usar Bonos Premium.</p>
        </div>
    </div>
    ';
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($to, $subject, $message, $headers);
}

// Añadir información en página de agradecimiento
add_action('woocommerce_thankyou', 'bono_display_credit_info_thankyou', 10, 1);

function bono_display_credit_info_thankyou($order_id) {
    $order = wc_get_order($order_id);
    $credit_used = $order->get_meta('_bono_credit_amount', true);
    
    if ($credit_used && floatval($credit_used) > 0) {
        $new_balance = $order->get_meta('_bono_credit_new_balance', true);
        
        echo '<div class="bono-thankyou-credit" style="padding: 20px; background: #f8f9fa; border-radius: 8px; margin: 20px 0;">';
        echo '<h3 style="color: #4361ee;">';
        echo '<i class="fas fa-wallet"></i> ';
        echo 'Crédito BonosPremium Usado';
        echo '</h3>';
        
        echo '<p>Se usaron <strong>' . number_format($credit_used, 2) . ' €</strong> de tu crédito BonosPremium.</p>';
        
        if ($new_balance) {
            echo '<p>Tu nuevo saldo: <strong style="color: #28a745;">' . number_format($new_balance, 2) . ' €</strong></p>';
        }
        
        echo '</div>';
    }
}