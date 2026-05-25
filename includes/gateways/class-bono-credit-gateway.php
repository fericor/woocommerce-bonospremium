<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gateway simple para crédito BonosPremium + otros métodos
 */
class WC_Bono_Credit_Gateway extends WC_Payment_Gateway {
    
    public function __construct() {
        $this->id                 = 'bono_credit';
        $this->icon               = '';
        $this->has_fields         = true;
        $this->method_title       = __('Crédito BonosPremium + Otro Método', 'bonospremium');
        $this->method_description = __('Usa crédito parcialmente y paga el resto con otro método', 'bonospremium');
        $this->supports           = array('products');
        
        $this->init_form_fields();
        $this->init_settings();
        
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }
    
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Habilitar', 'bonospremium'),
                'type'    => 'checkbox',
                'label'   => __('Habilitar pago con crédito BonosPremium', 'bonospremium'),
                'default' => 'yes'
            ),
            'title' => array(
                'title'       => __('Título', 'bonospremium'),
                'type'        => 'text',
                'description' => __('Título en checkout', 'bonospremium'),
                'default'     => __('Usar Crédito BonosPremium Disponible', 'bonospremium'),
            ),
            'description' => array(
                'title'       => __('Descripción', 'bonospremium'),
                'type'        => 'textarea',
                'description' => __('Descripción en checkout', 'bonospremium'),
                'default'     => __('Usa parte de tu crédito BonosPremium y paga el resto con otro método', 'bonospremium'),
            ),
        );
    }
    
    public function payment_fields() {
        if (!is_user_logged_in()) {
            echo '<div class="alert alert-warning">' . 
                 __('Inicia sesión para usar tu crédito BonosPremium', 'bonospremium') . 
                 '</div>';
            return;
        }
        
        $user_id = get_current_user_id();
        global $wpdb;
        
        // Obtener saldo
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
            $user_id
        ));
        
        $saldo = $result ? floatval($result->saldo) : 0;
        $cart_total = WC()->cart->total;
        
        if ($saldo <= 0) {
            echo '<div class="alert alert-info">' . 
                 __('No tienes crédito BonosPremium disponible', 'bonospremium') . 
                 '</div>';
            return;
        }
        
        $max_usable = min($saldo, $cart_total);
        ?>
        <div class="bono-credit-payment-fields">
            <div class="bono-credit-info">
                <p>
                    <i class="fas fa-wallet text-primary me-2"></i>
                    <strong><?php _e('Saldo disponible:', 'bonospremium'); ?></strong>
                    <span class="text-success"><?php echo number_format($saldo, 2); ?> €</span>
                </p>
                
                <div class="form-group">
                    <label for="bono_credit_amount">
                        <?php _e('¿Cuánto crédito BonosPremium quieres usar?', 'bonospremium'); ?>
                        <small class="text-muted">(Máx: <?php echo number_format($max_usable, 2); ?> €)</small>
                    </label>
                    <input type="number" 
                           id="bono_credit_amount" 
                           name="bono_credit_amount" 
                           class="form-control" 
                           min="0" 
                           max="<?php echo $max_usable; ?>" 
                           step="0.01"
                           value="0"
                           style="max-width: 200px;">
                </div>
                
                <div class="bono-credit-preview mt-3 p-3 bg-light rounded">
                    <p class="mb-1">
                        <small><?php _e('Total pedido:', 'bonospremium'); ?></small>
                        <strong><?php echo number_format($cart_total, 2); ?> €</strong>
                    </p>
                    <p class="mb-1">
                        <small><?php _e('Crédito BonosPremium a usar:', 'bonospremium'); ?></small>
                        <strong id="bono-credit-display">0.00 €</strong>
                    </p>
                    <p class="mb-0">
                        <small><?php _e('A pagar con otro método:', 'bonospremium'); ?></small>
                        <strong id="bono-remaining-display" class="text-primary"><?php echo number_format($cart_total, 2); ?> €</strong>
                    </p>
                </div>
                
                <div class="alert alert-info mt-3">
                    <small>
                        <i class="fas fa-info-circle"></i>
                        <?php _e('Selecciona otro método de pago para el resto.', 'bonospremium'); ?>
                    </small>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            const cartTotal = <?php echo $cart_total; ?>;
            const maxUsable = <?php echo $max_usable; ?>;
            
            function updateDisplay() {
                let creditAmount = parseFloat($('#bono_credit_amount').val()) || 0;
                
                if (creditAmount > maxUsable) {
                    creditAmount = maxUsable;
                    $('#bono_credit_amount').val(creditAmount);
                }
                
                if (creditAmount < 0) {
                    creditAmount = 0;
                    $('#bono_credit_amount').val(creditAmount);
                }
                
                const remaining = cartTotal - creditAmount;
                
                $('#bono-credit-display').text(creditAmount.toFixed(2) + ' €');
                $('#bono-remaining-display').text(remaining.toFixed(2) + ' €');
                
                // Guardar en variable global
                window.bonoCreditAmount = creditAmount;
                
                // Actualizar checkout si es necesario
                if (typeof update_checkout !== 'undefined') {
                    setTimeout(() => {
                        $('body').trigger('update_checkout');
                    }, 100);
                }
            }
            
            $('#bono_credit_amount').on('input change', updateDisplay);
            updateDisplay();
        });
        </script>
        
        <style>
        .bono-credit-payment-fields {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 10px 0;
        }
        .bono-credit-preview {
            border: 1px solid #dee2e6;
        }
        </style>
        <?php
    }
    
    public function validate_fields() {
        if (!is_user_logged_in()) {
            wc_add_notice(__('Debes iniciar sesión para usar crédito BonosPremium', 'bonospremium'), 'error');
            return false;
        }
        
        $user_id = get_current_user_id();
        $credit_amount = isset($_POST['bono_credit_amount']) ? floatval($_POST['bono_credit_amount']) : 0;
        
        if ($credit_amount < 0) {
            wc_add_notice(__('El monto de crédito BonosPremium no puede ser negativo', 'bonospremium'), 'error');
            return false;
        }
        
        if ($credit_amount > 0) {
            global $wpdb;
            
            // Verificar saldo
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
                $user_id
            ));
            
            if (!$result) {
                wc_add_notice(__('No tienes crédito BonosPremium disponible', 'bonospremium'), 'error');
                return false;
            }
            
            $saldo = floatval($result->saldo);
            
            if ($credit_amount > $saldo) {
                wc_add_notice(
                    sprintf(
                        __('Crédito BonosPremium insuficiente. Tienes %s € disponibles', 'bonospremium'),
                        number_format($saldo, 2)
                    ),
                    'error'
                );
                return false;
            }
            
            // Guardar en sesión
            WC()->session->set('bono_credit_amount', $credit_amount);
        }
        
        return true;
    }
    
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $credit_amount = WC()->session->get('bono_credit_amount', 0);
        
        // Limpiar sesión
        WC()->session->set('bono_credit_amount', null);
        
        if ($credit_amount > 0) {
            // Guardar información del crédito BonosPremium
            $order->update_meta_data('_bono_credit_amount', $credit_amount);
            $order->update_meta_data('_bono_credit_user_id', $order->get_user_id());
            
            // Añadir nota
            $order->add_order_note(
                sprintf(
                    __('Cliente usará %s € de crédito BonosPremium. Restante a pagar con otro método.', 'bonospremium'),
                    number_format($credit_amount, 2)
                )
            );
            
            // NO procesar el pago aquí - solo guardar info
            // El pago real se hará con el otro método seleccionado
            $order->update_status('pending', __('Esperando pago del resto', 'bonospremium'));
        }
        
        $order->save();
        
        // Redirigir a la misma página (no procesamos pago real aquí)
        return array(
            'result'   => 'success',
            'redirect' => wc_get_checkout_url()
        );
    }
    
    public function is_available() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_id = get_current_user_id();
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
            $user_id
        ));
        
        if (!$result || floatval($result->saldo) <= 0) {
            return false;
        }
        
        return parent::is_available();
    }
}