<?php
/**
 * Custom checkout form for BonosPremium
 * Layout: grid 2 columnas en desktop, apilado vertical en móvil
 */
if (!defined('ABSPATH')) exit;

$checkout = WC()->checkout();
?>

<?php wc_get_template('checkout/form-login.php', array('checkout' => $checkout)); ?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" onsubmit="if(window.bpAbgRecoger)bpAbgRecoger();">
    <div class="bp-checkout-grid">

        <!-- Columna izquierda: formulario -->
        <div class="bp-checkout-col bp-checkout-col-form">
            <div class="bp-checkout-section">
                <?php do_action('woocommerce_checkout_billing', $checkout); ?>
            </div>

            <!-- Información adicional -->
            <div class="bp-checkout-section">
                <h3 class="bp-section-title">Información adicional</h3>
                <?php wc_get_template('checkout/form-shipping.php', array('checkout' => $checkout)); ?>
            </div>

            <!-- Formularios extra (bono de abogados, etc.): entre información personal y pedido -->
            <div class="bp-checkout-extra">
                <?php do_action('bp_checkout_after_order_review', $checkout); ?>
            </div>
        </div>

        <!-- Columna derecha: resumen -->
        <div class="bp-checkout-col bp-checkout-col-summary">
            <!-- Tu pedido -->
            <div class="bp-checkout-section">
                <h3 class="bp-section-title">Tu pedido</h3>
                <?php wc_get_template('checkout/review-order.php'); ?>
            </div>

            <!-- Cupón descuento -->
            <?php bp_checkout_coupon_form(); ?>

            <!-- Métodos de pago -->
            <div class="bp-checkout-section">
                <?php woocommerce_checkout_payment(); ?>
            </div>
        </div>

    </div>
</form>

<script>
jQuery(function($) {
    // Términos marcado por defecto
    $('#terms').prop('checked', true);

    $('input[name="payment_method"]').on('change', function() {
        $('.bp-payment-box').slideUp();
        $(this).closest('.bp-payment-method').find('.bp-payment-box').slideDown();
    });
});
</script>
