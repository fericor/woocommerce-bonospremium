<?php
// Aplicar descuento de crédito BonosPremium en el carrito
add_action('woocommerce_cart_calculate_fees', 'bono_apply_credit_discount');

function bono_apply_credit_discount() {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    if (!is_checkout()) {
        return;
    }
    
    if (!is_user_logged_in()) {
        return;
    }
    
    // Solo si se seleccionó el método de crédito BonosPremium
    $selected_method = WC()->session->get('chosen_payment_method');
    
    if ($selected_method !== 'bono_credit') {
        // Limpiar si no está seleccionado
        WC()->session->set('bono_credit_amount', 0);
        return;
    }
    
    // Obtener monto desde POST o sesión
    $credit_amount = 0;
    
    if (isset($_POST['post_data'])) {
        parse_str($_POST['post_data'], $post_data);
        
        if (isset($post_data['payment_method']) && $post_data['payment_method'] === 'bono_credit') {
            $credit_amount = isset($post_data['bono_credit_amount']) ? floatval($post_data['bono_credit_amount']) : 0;
            WC()->session->set('bono_credit_amount', $credit_amount);
        }
    } elseif (WC()->session->get('bono_credit_amount')) {
        $credit_amount = WC()->session->get('bono_credit_amount');
    }
    
    // Aplicar descuento si hay monto
    if ($credit_amount > 0) {
        $cart_total = WC()->cart->total;
        
        // No aplicar más que el total
        if ($credit_amount > $cart_total) {
            $credit_amount = $cart_total;
        }
        
        WC()->cart->add_fee(
            __('Descuento por Crédito BonosPremium', 'bonospremium'),
            -$credit_amount,
            false
        );
    }
}

// Asegurar que otros métodos de pago siempre estén disponibles
add_filter('woocommerce_available_payment_gateways', 'bono_keep_all_payment_methods', 20, 1);

function bono_keep_all_payment_methods($gateways) {
    if (!is_checkout()) {
        return $gateways;
    }
    
    // Siempre mantener todos los métodos disponibles
    // No ocultamos nada
    
    return $gateways;
}

// JS para la interacción en checkout
add_action('wp_footer', 'bono_credit_checkout_js');

function bono_credit_checkout_js() {
    if (!is_checkout()) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Actualizar cuando cambia el método de pago
        $(document.body).on('change', 'input[name="payment_method"]', function() {
            const method = $(this).val();
            
            if (method === 'bono_credit') {
                // Mostrar campos de crédito BonosPremium
                $('.bono-credit-payment-fields').slideDown();
                
                // Asegurar que al menos un otro método esté disponible
                setTimeout(() => {
                    // Forzar visibilidad de otros métodos
                    $('.wc_payment_method:not(.payment_method_bono_credit)').show();
                }, 100);
            } else {
                // Ocultar campos de crédito BonosPremium
                $('.bono-credit-payment-fields').slideUp();
            }
        });
        
        // Inicializar
        if ($('input[name="payment_method"][value="bono_credit"]').is(':checked')) {
            $('.bono-credit-payment-fields').show();
        } else {
            $('.bono-credit-payment-fields').hide();
        }
    });
    </script>
    <?php
}