<?php
// Aplicar descuento cuando se selecciona pagar con crédito
add_action('woocommerce_cart_calculate_fees', 'bono_apply_credit_discount');

function bono_apply_credit_discount() {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    if (!is_user_logged_in()) {
        return;
    }
    
    // Solo aplicar si el método de pago seleccionado es crédito
    $chosen_payment_method = WC()->session->get('chosen_payment_method');
    
    if ($chosen_payment_method !== 'bono_credit') {
        return;
    }
    
    // Obtener monto desde el formulario o sesión
    $credit_amount = 0;
    
    if (isset($_POST['post_data'])) {
        parse_str($_POST['post_data'], $post_data);
        
        if (isset($post_data['payment_method']) && $post_data['payment_method'] === 'bono_credit') {
            $credit_amount = isset($post_data['bono_credit_amount']) ? floatval($post_data['bono_credit_amount']) : 0;
            
            // Guardar en sesión para validación
            WC()->session->set('bono_credit_amount', $credit_amount);
        }
    } elseif (WC()->session->get('bono_credit_amount')) {
        $credit_amount = WC()->session->get('bono_credit_amount');
    }
    
    if ($credit_amount > 0) {
        // Aplicar como descuento
        WC()->cart->add_fee(
            __('Descuento por Crédito BonosPremium', 'bonospremium'),
            -$credit_amount,
            false
        );
    }
}

// AJAX para actualizar métodos de pago cuando cambia el monto de crédito
add_action('wp_ajax_update_payment_methods', 'ajax_update_payment_methods');
add_action('wp_ajax_nopriv_update_payment_methods', 'ajax_update_payment_methods');

function ajax_update_payment_methods() {
    if (!is_user_logged_in()) {
        wp_send_json_error('No autenticado');
    }
    
    $credit_amount = isset($_POST['credit_amount']) ? floatval($_POST['credit_amount']) : 0;
    $cart_total = WC()->cart->total;
    
    // Guardar en sesión
    WC()->session->set('bono_credit_amount', $credit_amount);
    
    // Determinar qué métodos de pago mostrar
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    $filtered_gateways = array();
    
    foreach ($available_gateways as $id => $gateway) {
        // Siempre mostrar crédito si hay saldo
        if ($id === 'bono_credit') {
            $filtered_gateways[$id] = $gateway;
            continue;
        }
        
        // Si el crédito cubre todo, ocultar otros métodos
        if ($credit_amount >= $cart_total) {
            // No añadir otros métodos
        } else {
            // Mostrar otros métodos para pagar el resto
            $filtered_gateways[$id] = $gateway;
        }
    }
    
    // Actualizar gateways disponibles
    WC()->payment_gateways->set_current_gateway($filtered_gateways);
    
    wp_send_json_success(array(
        'gateways' => array_keys($filtered_gateways)
    ));
}

// JS para manejar la interacción
add_action('wp_footer', 'bono_credit_checkout_js');

function bono_credit_checkout_js() {
    if (!is_checkout()) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Cuando cambia el monto de crédito
        $(document.body).on('change input', '#bono_credit_amount', function() {
            const creditAmount = parseFloat($(this).val()) || 0;
            const cartTotal = parseFloat($('input[name="bonoCreditCartTotal"]').val()) || <?php echo WC()->cart->total; ?>;
            
            // Si el crédito cubre todo, seleccionar automáticamente crédito como método de pago
            if (creditAmount >= cartTotal) {
                $('input[name="payment_method"][value="bono_credit"]').prop('checked', true).trigger('change');
                
                // Ocultar otros métodos de pago
                // $('.payment_methods .payment_method:not(.payment_method_bono_credit)').hide();
            } else {
                // Mostrar todos los métodos
                $('.payment_methods .payment_method').show();
            }
            
            // Actualizar métodos de pago via AJAX
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'update_payment_methods',
                    credit_amount: creditAmount
                },
                success: function(response) {
                    if (response.success) {
                        // Recargar sección de pagos
                        $('body').trigger('update_checkout');
                    }
                }
            });
        });
        
        // Cuando cambia el método de pago
        $(document.body).on('change', 'input[name="payment_method"]', function() {
            const selectedMethod = $(this).val();
            
            if (selectedMethod === 'bono_credit') {
                // Mostrar campos de crédito
                $('.bono-credit-payment-fields').slideDown();
                
                // Si hay crédito suficiente, deshabilitar otros métodos
                const creditAmount = parseFloat($('#bono_credit_amount').val()) || 0;
                const cartTotal = <?php echo WC()->cart->total; ?>;
                
                if (creditAmount >= cartTotal) {
                    $('input[name="payment_method"]:not([value="bono_credit"])').prop('disabled', true);
                }
            } else {
                // Ocultar campos de crédito
                $('.bono-credit-payment-fields').slideUp();
                
                // Habilitar todos los métodos
                $('input[name="payment_method"]').prop('disabled', false);
            }
        });
        
        // Inicializar
        if ($('input[name="payment_method"][value="bono_credit"]').is(':checked')) {
            $('.bono-credit-payment-fields').show();
        }
    });
    </script>
    <?php
}

// Filtrar métodos de pago disponibles
add_filter('woocommerce_available_payment_gateways', 'bono_filter_payment_gateways', 10, 1);

function bono_filter_payment_gateways($available_gateways) {
    if (!is_checkout() || !is_user_logged_in()) {
        return $available_gateways;
    }
    
    // Obtener monto de crédito de la sesión
    $credit_amount = WC()->session->get('bono_credit_amount', 0);
    $cart_total = WC()->cart->total;
    
    // Si el crédito cubre todo el carrito, solo mostrar crédito
    if ($credit_amount >= $cart_total && $credit_amount > 0) {
        foreach ($available_gateways as $id => $gateway) {
            if ($id !== 'bono_credit') {
                unset($available_gateways[$id]);
            }
        }
    }
    
    // Verificar si el usuario tiene saldo para mostrar crédito
    if (isset($available_gateways['bono_credit'])) {
        $user_id = get_current_user_id();
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
            $user_id
        ));
        
        $saldo = $result ? floatval($result->saldo) : 0;
        
        // Ocultar crédito si no hay saldo
        if ($saldo <= 0) {
            unset($available_gateways['bono_credit']);
        }
    }
    
    return $available_gateways;
}