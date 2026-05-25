<?php
// Procesar crédito después del pago (para pagos parciales)
add_action('woocommerce_order_status_changed', 'bono_process_partial_credit_payment', 10, 3);

function bono_process_partial_credit_payment($order_id, $old_status, $new_status) {
    // Solo procesar cuando la orden pasa a processing o completed
    if (!in_array($new_status, array('processing', 'completed'))) {
        return;
    }
    
    $order = wc_get_order($order_id);
    
    // Verificar si ya se procesó
    if (get_post_meta($order_id, '_bono_credit_processed', true) === 'yes') {
        return;
    }
    
    // Verificar si se usó crédito
    $credit_used = $order->get_meta('_bono_credit_used', true);
    $user_id = $order->get_meta('_bono_credit_user_id', true);
    
    if (!$credit_used || !$user_id || floatval($credit_used) <= 0) {
        return;
    }
    
    $monto = floatval($credit_used);
    global $wpdb;
    
    // Iniciar transacción
    $wpdb->query('START TRANSACTION');
    
    try {
        // 1. Obtener saldo actual
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
            $user_id
        ));
        
        if (!$result) {
            throw new Exception('Usuario no encontrado en créditos');
        }
        
        $saldo_actual = floatval($result->saldo);
        
        // 2. Verificar que tenga suficiente saldo
        if ($saldo_actual < $monto) {
            throw new Exception('Saldo insuficiente');
        }
        
        $nuevo_saldo = $saldo_actual - $monto;
        
        // 3. Actualizar saldo
        $update = $wpdb->update(
            "{$wpdb->prefix}usuario_creditos",
            array('saldo' => $nuevo_saldo),
            array('user_id' => $user_id),
            array('%f'),
            array('%d')
        );
        
        if ($update === false) {
            throw new Exception('Error al actualizar saldo');
        }
        
        // 4. Registrar transacción
        $insert = $wpdb->insert(
            "{$wpdb->prefix}credito_transacciones",
            array(
                'user_id' => $user_id,
                'tipo' => 'debito',
                'monto' => $monto,
                'saldo_anterior' => $saldo_actual,
                'saldo_nuevo' => $nuevo_saldo,
                'descripcion' => 'Compra BonosPremium #' . $order_id,
                'order_id' => $order_id,
                'fecha_transaccion' => current_time('mysql')
            ),
            array('%d', '%s', '%f', '%f', '%f', '%s', '%d', '%s')
        );
        
        if (!$insert) {
            throw new Exception('Error al registrar transacción');
        }
        
        // 5. Marcar como procesado
        $order->update_meta_data('_bono_credit_processed', 'yes');
        $order->update_meta_data('_bono_credit_processed_date', current_time('mysql'));
        
        // 6. Añadir nota a la orden
        $order->add_order_note(
            sprintf(
                __('Se descontaron %s € del crédito del cliente. Nuevo saldo: %s €', 'bonospremium'),
                number_format($monto, 2),
                number_format($nuevo_saldo, 2)
            )
        );
        
        $order->save();
        
        // Commit
        $wpdb->query('COMMIT');
        
        // Enviar email al cliente
        bono_send_credit_usage_email($user_id, $order_id, $monto, $nuevo_saldo);
        
    } catch (Exception $e) {
        // Rollback en caso de error
        $wpdb->query('ROLLBACK');
        
        error_log('Error procesando crédito para orden ' . $order_id . ': ' . $e->getMessage());
        
        $order->add_order_note(
            __('Error al procesar el crédito: ', 'bonospremium') . $e->getMessage()
        );
    }
}

// Manejar pagos parciales cuando se completa el pago restante
add_action('woocommerce_payment_complete', 'bono_handle_remaining_payment', 10, 1);

function bono_handle_remaining_payment($order_id) {
    $order = wc_get_order($order_id);
    
    // Si era un pago parcial con crédito
    if ($order->get_meta('_bono_credit_partial', true) === 'yes') {
        $credit_used = $order->get_meta('_bono_credit_used', true);
        $remaining = $order->get_meta('_bono_credit_remaining', true);
        
        // Verificar que el pago cubra lo restante
        $amount_paid = $order->get_total() - $remaining;
        
        if ($amount_paid >= $remaining) {
            // Pago completo, procesar crédito si no se ha hecho
            if ($order->get_meta('_bono_credit_processed', true) !== 'yes') {
                bono_process_partial_credit_payment($order_id, '', 'processing');
            }
        }
    }
}