<?php

// Verificar si el sistema está activado
function bono_credit_is_enabled() {
    return get_option('bono_credit_enabled', 'yes') === 'yes';
}

// Mostrar opción para usar crédito ANTES de los métodos de pago
add_action('woocommerce_review_order_before_payment', 'bono_display_credit_option');

function bono_display_credit_option() {
    if (!bono_credit_is_enabled()) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }
    
    $user_id = get_current_user_id();
    global $wpdb;
    
    // Obtener saldo
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
        $user_id
    ));
    
    if (!$result) {
        return;
    }
    
    $saldo = floatval($result->saldo);
    $cart_total = WC()->cart->total;
    
    if ($saldo <= 0) {
        return;
    }
    
    $max_usable = min($saldo, $cart_total);
    $current_credit = isset($_POST['use_bono_credit_amount']) ? floatval($_POST['use_bono_credit_amount']) : 0;
    
    ?>
    <div id="bono-credit-section" class="bono-credit-checkout-section">
        <h3 id="payment_credit_heading" class="wc_payment_heading">
            <i class="fas fa-wallet text-primary me-2"></i>
            <?php _e('Usar Crédito Disponible', 'bonospremium'); ?>
        </h3>
        
        <div class="bono-credit-option">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" 
                       id="use_bono_credit" 
                       name="use_bono_credit" 
                       value="1"
                       <?php echo $current_credit > 0 ? 'checked' : ''; ?>>
                <label class="form-check-label" for="use_bono_credit">
                    <strong><?php _e('Usar crédito BonosPremium en esta compra', 'bonospremium'); ?></strong>
                </label>
                <p class="text-muted mb-0">
                    <small>
                        <?php _e('Saldo disponible:', 'bonospremium'); ?>
                        <span class="text-success fw-bold"><?php echo number_format($saldo, 2); ?> €</span>
                    </small>
                </p>
            </div>
            
            <div id="bono-credit-amount-section" style="display: <?php echo $current_credit > 0 ? 'block' : 'none'; ?>;">
                <div class="mb-3">
                    <label for="use_bono_credit_amount" class="form-label">
                        <?php _e('¿Cuánto crédito BonosPremium quieres usar?', 'bonospremium'); ?>
                        <small class="text-muted">(Máximo: <?php echo number_format($max_usable, 2); ?> €)</small>
                    </label>
                    
                    <div class="input-group" style="display: flex; margin: 1rem;">
                        <input type="number" 
                               id="use_bono_credit_amount" 
                               name="use_bono_credit_amount" 
                               class="form-control" 
                               min="0" 
                               max="<?php echo $max_usable; ?>" 
                               step="0.01"
                               value="<?php echo $current_credit > 0 ? $current_credit : $max_usable; ?>" style="margin-right: 5px; padding: 0px; text-align: center;">
                   
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="bonoUseMaxCredit(<?php echo $max_usable; ?>)">
                            <?php _e('Máximo', 'bonospremium'); ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bonoSetCreditAmount(10)">
                            10 €
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bonoSetCreditAmount(25)">
                            25 €
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bonoSetCreditAmount(50)">
                            50 €
                        </button>
                    </div>
                </div>
                
                <div class="bono-credit-summary p-3 bg-light rounded" style="padding: 5px;">
                    <div class="row">
                        <div class="col-12" style="display: flex;">
                            <small class="text-muted"><?php _e('Total pedido:', 'bonospremium'); ?></small>
                            <div class="fw-bold"><b><?php echo number_format($cart_total, 2); ?> €</b></div>
                        </div>
                        <div class="col-12" style="display: flex;">
                            <small class="text-muted"><?php _e('Crédito BonosPremium a usar:', 'bonospremium'); ?></small>
                            <div id="bono-credit-amount-display" class="fw-bold text-success">
                                <?php echo number_format($current_credit > 0 ? $current_credit : $max_usable, 2); ?> €
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12" style="display: flex;">
                            <small class="text-muted"><?php _e('Total a pagar ahora:', 'bonospremium'); ?></small>
                            <div id="bono-total-to-pay" class="fw-bold text-primary fs-5">
                                <?php 
                                $remaining = $cart_total - ($current_credit > 0 ? $current_credit : $max_usable);
                                echo number_format($remaining > 0 ? $remaining : 0, 2); 
                                ?> €
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($max_usable < $cart_total): ?>
                    <div class="alert alert-info mt-2 mb-0">
                        <i class="fas fa-info-circle"></i>
                        <small>
                            <?php _e('El resto se pagará con el método de pago que selecciones.', 'bonospremium'); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .bono-credit-checkout-section {
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }
        
        .bono-credit-option .form-check-input {
            margin-top: 0.3rem;
        }
        
        .bono-credit-option .btn-sm {
            padding: 5px 10px;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .bono-credit-summary {
            border: 1px solid #dee2e6;
        }
    </style>
    
    <script>
        jQuery(document).ready(function($) {
            const cartTotal = <?php echo $cart_total; ?>;
            const maxUsable = <?php echo $max_usable; ?>;
            
            // Mostrar/ocultar sección de monto
            $('#use_bono_credit').change(function() {
                if ($(this).is(':checked')) {
                    $('#bono-credit-amount-section').slideDown(300);
                    updateCreditCalculations();
                } else {
                    $('#bono-credit-amount-section').slideUp(300);
                    // Resetear valor
                    $('#use_bono_credit_amount').val(0);
                    updateCreditCalculations();
                }
            });
            
            // Actualizar cálculos cuando cambia el monto
            $('#use_bono_credit_amount').on('input change', function() {
                updateCreditCalculations();
            });
            
            function updateCreditCalculations() {
                let creditAmount = parseFloat($('#use_bono_credit_amount').val()) || 0;
                const isChecked = $('#use_bono_credit').is(':checked');
                
                if (!isChecked) {
                    creditAmount = 0;
                }
                
                // Validar límites
                if (creditAmount > maxUsable) {
                    creditAmount = maxUsable;
                    $('#use_bono_credit_amount').val(creditAmount);
                }
                
                if (creditAmount < 0) {
                    creditAmount = 0;
                    $('#use_bono_credit_amount').val(creditAmount);
                }
                
                const remaining = cartTotal - creditAmount;
                
                // Actualizar displays
                $('#bono-credit-amount-display').text(creditAmount.toFixed(2) + ' €');
                $('#bono-total-to-pay').text((remaining > 0 ? remaining : 0).toFixed(2) + ' €');
                
                // Guardar en variable global
                window.bonoCreditAmount = creditAmount;
                
                // Actualizar checkout
                setTimeout(() => {
                    $('body').trigger('update_checkout');
                }, 300);
            }
            
            // Inicializar
            if ($('#use_bono_credit').is(':checked')) {
                updateCreditCalculations();
            }
        });
        
        function bonoUseMaxCredit(maxAmount) {
            jQuery('#use_bono_credit_amount').val(maxAmount).trigger('change');
        }
        
        function bonoSetCreditAmount(amount) {
            jQuery('#use_bono_credit_amount').val(amount).trigger('change');
        }
    </script>
    <?php
}

// Aplicar descuento de crédito BonosPremium
add_action('woocommerce_cart_calculate_fees', 'bono_apply_credit_discount_simple');

function bono_apply_credit_discount_simple() {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    if (!is_checkout()) {
        return;
    }
    
    if (!is_user_logged_in()) {
        return;
    }
    
    // Verificar si el usuario quiere usar crédito BonosPremium
    $use_credit = false;
    $credit_amount = 0;
    
    if (isset($_POST['post_data'])) {
        parse_str($_POST['post_data'], $post_data);
        
        if (isset($post_data['use_bono_credit']) && $post_data['use_bono_credit'] == '1') {
            $use_credit = true;
            $credit_amount = isset($post_data['use_bono_credit_amount']) ? floatval($post_data['use_bono_credit_amount']) : 0;
        }
    } elseif (isset($_POST['use_bono_credit']) && $_POST['use_bono_credit'] == '1') {
        $use_credit = true;
        $credit_amount = isset($_POST['use_bono_credit_amount']) ? floatval($_POST['use_bono_credit_amount']) : 0;
    }
    
    if ($use_credit && $credit_amount > 0) {
        // Validar que el usuario tenga suficiente crédito BonosPremium
        $user_id = get_current_user_id();
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
            $user_id
        ));
        
        if ($result) {
            $saldo = floatval($result->saldo);
            
            if ($credit_amount > $saldo) {
                $credit_amount = $saldo; // Limitar al saldo disponible
            }
            
            if ($credit_amount > 0) {
                // Aplicar como descuento
                WC()->cart->add_fee(
                    __('Descuento por Crédito BonosPremium Bonos Premium', 'bonospremium'),
                    -$credit_amount,
                    false
                );
                
                // Guardar en sesión para usar después
                WC()->session->set('bono_credit_to_use', $credit_amount);
                WC()->session->set('bono_credit_user_id', $user_id);
            }
        }
    } else {
        // Limpiar sesión si no se usa crédito BonosPremium
        WC()->session->set('bono_credit_to_use', 0);
    }
}

// Validar antes de procesar checkout
add_action('woocommerce_checkout_process', 'bono_validate_credit_checkout');

function bono_validate_credit_checkout() {
    if (!is_user_logged_in()) {
        return;
    }
    
    if (isset($_POST['use_bono_credit']) && $_POST['use_bono_credit'] == '1') {
        $credit_amount = isset($_POST['use_bono_credit_amount']) ? floatval($_POST['use_bono_credit_amount']) : 0;
        $user_id = get_current_user_id();
        
        if ($credit_amount <= 0) {
            wc_add_notice(__('Por favor, introduce un monto válido para usar tu crédito BonosPremium.', 'bonospremium'), 'error');
            return;
        }
        
        global $wpdb;
        
        // Verificar saldo
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
            $user_id
        ));
        
        if (!$result) {
            wc_add_notice(__('No tienes crédito BonosPremium disponible.', 'bonospremium'), 'error');
            return;
        }
        
        $saldo = floatval($result->saldo);
        
        if ($credit_amount > $saldo) {
            wc_add_notice(
                sprintf(
                    __('No tienes suficiente crédito BonosPremium. Disponible: %s €', 'bonospremium'),
                    number_format($saldo, 2)
                ),
                'error'
            );
            return;
        }
        
        // Guardar en sesión
        WC()->session->set('bono_credit_to_use', $credit_amount);
        WC()->session->set('bono_credit_user_id', $user_id);
    }
}

// Guardar información del crédito BonosPremium en la orden
add_action('woocommerce_checkout_create_order', 'bono_save_credit_to_order', 10, 2);

function bono_save_credit_to_order($order, $data) {
    $credit_amount = WC()->session->get('bono_credit_to_use', 0);
    $user_id = WC()->session->get('bono_credit_user_id', 0);
    
    if ($credit_amount > 0 && $user_id > 0) {
        $order->update_meta_data('_bono_credit_amount', $credit_amount);
        $order->update_meta_data('_bono_credit_user_id', $user_id);
        $order->update_meta_data('_bono_credit_processed', 'no');
        
        // Añadir nota
        $order->add_order_note(
            sprintf(
                __('Cliente usará %s € de crédito BonosPremium. Pendiente de procesar.', 'bonospremium'),
                number_format($credit_amount, 2)
            )
        );
    }
    
    // Limpiar sesión
    WC()->session->set('bono_credit_to_use', 0);
    WC()->session->set('bono_credit_user_id', 0);
}