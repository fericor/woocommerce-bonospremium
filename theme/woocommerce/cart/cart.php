<?php
/**
 * Custom Cart page for BonosPremium
 * Carrito con quantity +/- que actualiza automaticamente
 */

get_header(); ?>

<main class="bp-main-content">
    <div class="bp-container">
        <h1 class="bp-page-title">Carrito</h1>

        <?php if (WC()->cart && !WC()->cart->is_empty()) : ?>

            <?php wc_print_notices(); ?>

            <form class="bp-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
                <div class="bp-cart-layout">
                    <!-- Tabla de productos -->
                    <table class="bp-cart-table">
                        <thead>
                            <tr>
                                <th class="bp-col-product">Producto</th>
                                <th class="bp-col-qtyprice bp-text-center">Cantidad y Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                                $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                                if (!$_product || !$_product->exists()) continue;
                                $product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
                                $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('thumbnail'), $cart_item, $cart_item_key);
                                $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                                $product_price = WC()->cart->get_product_price($_product);
                                $product_subtotal = WC()->cart->get_product_subtotal($_product, $cart_item['quantity']);
                            ?>
                            <tr>
                                <!-- TD IZQUIERDA: imagen + nombre + eliminar (horizontal) -->
                                <td class="bp-col-product">
                                    <div class="bp-product-row">
                                        <div class="bp-prod-thumb">
                                            <?php echo $thumbnail; ?>
                                        </div>
                                        <div class="bp-prod-info">
                                            <a href="<?php echo esc_url($product_permalink); ?>" class="bp-prod-name">
                                                <?php echo $product_name; ?>
                                            </a>
                                            <a href="<?php echo esc_url(wc_get_cart_remove_url($cart_item_key)); ?>" class="bp-prod-remove" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i> Eliminar
                                            </a>
                                        </div>
                                    </div>
                                </td>

                                <!-- TD DERECHA: quantity (arriba) + precio (abajo) -->
                                <td class="bp-col-qtyprice">
                                    <div class="bp-qtyprice-stack">
                                        <!-- Quantity +/- arriba -->
                                        <div class="bp-qty-selector">
                                            <button type="button" class="bp-qty-btn bp-qty-minus" data-key="<?php echo esc_attr($cart_item_key); ?>">−</button>
                                            <input type="number" name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]" 
                                                   value="<?php echo esc_attr($cart_item['quantity']); ?>" 
                                                   class="bp-qty-input" min="1" max="99" 
                                                   data-product-id="<?php echo esc_attr($_product->get_id()); ?>" />
                                            <button type="button" class="bp-qty-btn bp-qty-plus" data-key="<?php echo esc_attr($cart_item_key); ?>">+</button>
                                        </div>
                                        <!-- Precio debajo -->
                                        <span class="bp-prod-price"><?php echo $product_price; ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bp-cart-subtotal-row">
                                <th class="bp-text-right">Subtotal</th>
                                <td class="bp-text-right bp-cart-subtotal-val"><?php wc_cart_totals_subtotal_html(); ?></td>
                            </tr>
                            <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
                            <tr class="bp-cart-coupon-row">
                                <th class="bp-text-right"><?php wc_cart_totals_coupon_label($coupon); ?></th>
                                <td class="bp-text-right"><?php wc_cart_totals_coupon_html($coupon); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="bp-cart-total-row">
                                <th class="bp-text-right">Total</th>
                                <td class="bp-text-right bp-cart-total-val"><?php wc_cart_totals_order_total_html(); ?></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Acciones inferiores -->
                    <div class="bp-cart-bottom">
                    <!-- Cupón descuento -->
                        <div class="bp-coupon-section">
                            <div class="bp-coupon-inner">
                                <i class="fas fa-ticket-alt"></i>
                                <input type="text" name="coupon_code" class="bp-coupon-input" 
                                       placeholder="Introduce tu código de descuento" value="" />
                                <button type="submit" class="bp-btn-primary bp-coupon-btn" name="apply_coupon" value="Aplicar">
                                    Aplicar
                                </button>
                                <?php do_action('woocommerce_cart_coupon'); ?>
                            </div>
                        </div>

                        <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="bp-checkout-btn">
                            <i class="fas fa-lock"></i> Finalizar compra
                        </a>
                    </div>
                </div>
            </form>
        <?php else : ?>
            <div class="bp-cart-empty">
                <div class="bp-empty-icon"><i class="fas fa-shopping-bag"></i></div>
                <h2>Tu carrito está vacío</h2>
                <p>Explora nuestros productos y encuentra tu experiencia ideal.</p>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="bp-btn-primary">Ver productos</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.bp-page-title { font-size: 24px; font-weight: 700; color: var(--bp-text); margin: 0 0 24px; }

/* --- Tabla: 2 columnas --- */
.bp-cart-table {
    width: 100%; border-collapse: collapse; background: var(--bp-card-bg);
    border-radius: 0 overflow: hidden;
    border: 1px solid var(--bp-border);
}
.bp-cart-table thead th {
    text-align: left; padding: 16px 20px; font-size: 12px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .5px; color: var(--bp-text-muted);
    background: #f9fafb; border-bottom: 1px solid var(--bp-border);
}
.bp-cart-table td { padding: 20px; border-bottom: 1px solid var(--bp-border); vertical-align: middle; }
.bp-cart-table tbody tr:last-child td { border-bottom: 1px solid var(--bp-border); }
.bp-text-right { text-align: right; }
.bp-text-center { text-align: center; }

/* --- TD izquierda: imagen + nombre + eliminar (horizontal) --- */
.bp-product-row {
    display: flex; align-items: center; gap: 16px;
}
.bp-prod-thumb {
    width: 80px; height: 80px; border-radius: 0; overflow: hidden; flex-shrink: 0;
}
.bp-prod-thumb img { width: 100%; height: 100%; object-fit: cover; }
.bp-prod-info {
    display: flex; flex-direction: column; gap: 6px;
}
.bp-prod-name {
    font-size: 16px; font-weight: 600; color: var(--bp-text); text-decoration: none; line-height: 1.3;
}
.bp-prod-name:hover { color: var(--bp-primary); }
.bp-prod-remove {
    font-size: 13px; color: #d1d5db; text-decoration: none; transition: color .2s;
    display: inline-flex; align-items: center; gap: 4px;
}
.bp-prod-remove:hover { color: #ef4444; }

/* --- TD derecha: quantity arriba + precio abajo (stack) --- */
.bp-qtyprice-stack {
    display: flex; flex-direction: column; align-items: center; gap: 10px;
}
.bp-qty-selector {
    display: inline-flex; align-items: center; gap: 0;
    border: 1px solid var(--bp-border); border-radius: 0
    overflow: hidden; background: var(--bp-card-bg);
}
.bp-qty-btn {
    width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;
    border: none; background: transparent; color: var(--bp-text);
    font-size: 20px; font-weight: 600; cursor: pointer;
    transition: background .15s, color .15s;
}
.bp-qty-btn:hover { background: var(--bp-primary); color: #fff; }
.bp-qty-input {
    width: 50px; height: 38px; border: none; border-left: 1px solid var(--bp-border);
    border-right: 1px solid var(--bp-border); text-align: center;
    font-size: 15px; font-weight: 600; color: var(--bp-text);
    -moz-appearance: textfield; background: var(--bp-card-bg);
}
.bp-qty-input::-webkit-inner-spin-button,
.bp-qty-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.bp-prod-price {
    font-size: 18px; font-weight: 700; color: var(--bp-primary); white-space: nowrap;
}

/* --- Footer totales --- */
.bp-cart-table tfoot th {
    padding: 14px 20px; font-size: 14px; color: var(--bp-text-light); font-weight: 500;
    border-top: 1px solid var(--bp-border);
}
.bp-cart-table tfoot td {
    padding: 14px 20px; font-size: 14px; color: var(--bp-text);
    border-top: 1px solid var(--bp-border);
}
.bp-cart-subtotal-row th,
.bp-cart-subtotal-row td { font-weight: 500; color: var(--bp-text-light); }
.bp-cart-coupon-row th,
.bp-cart-coupon-row td { color: var(--bp-primary); font-weight: 500; }
.bp-cart-total-row th,
.bp-cart-total-row td { font-weight: 700; color: var(--bp-text); font-size: 16px; border-top: 2px solid var(--bp-border); }

/* --- Cupón descuento --- */
.bp-cart-bottom { margin-top: 24px; display: flex; flex-direction: column; gap: 16px; }
.bp-coupon-section {
    background: var(--bp-card-bg);
    border: 1px solid var(--bp-border); border-radius: 0
    padding: 16px 20px;
}
.bp-coupon-inner {
    display: flex; align-items: center; gap: 12px;
}
.bp-coupon-inner > i:first-child { color: var(--bp-primary); font-size: 18px; flex-shrink: 0; }
.bp-coupon-input {
    flex: 1; min-width: 0;
    padding: 14px 18px; border: 1px solid var(--bp-border);
    border-radius: 0; font-size: 15px; background: var(--bp-bg); color: var(--bp-text);
    outline: none; transition: border-color .2s;
}
.bp-coupon-input:focus { border-color: var(--bp-primary); }
.bp-coupon-input::placeholder { color: var(--bp-text-muted); }
.bp-coupon-btn {
    flex-shrink: 0;
    padding: 14px 28px; border: none; cursor: pointer;
    font-size: 14px; font-weight: 600;
}
.bp-checkout-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 16px;
    background: var(--bp-primary); color: #fff;
    border: none; border-radius: 0
    font-size: 16px; font-weight: 600;
    text-decoration: none; cursor: pointer; transition: background .2s;
}
.bp-checkout-btn:hover { background: var(--bp-primary-dark); color: #fff; }
.bp-btn-primary, .bp-btn-secondary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 28px; border-radius: 0; font-size: 14px; font-weight: 600;
    text-decoration: none; transition: all .2s; border: none; cursor: pointer;
}
.bp-btn-primary { background: var(--bp-primary); color: #fff; }
.bp-btn-primary:hover { background: var(--bp-primary-dark); color: #fff; }
.bp-btn-secondary { background: #f3f4f6; color: var(--bp-text); }
.bp-btn-secondary:hover { background: #e5e7eb; color: var(--bp-text); }

/* --- Carrito vacío --- */
.bp-cart-empty { text-align: center; padding: 60px 20px; }
.bp-empty-icon { font-size: 64px; color: #d1d5db; margin-bottom: 16px; }
.bp-cart-empty h2 { font-size: 22px; font-weight: 700; color: var(--bp-text); margin: 0 0 8px; }
.bp-cart-empty p { color: var(--bp-text-light); margin: 0 0 24px; }
.bp-cart-updating { opacity: .6; pointer-events: none; }

/* --- Responsive --- */
@media (max-width: 768px) {
    .bp-cart-table thead { display: none; }
    .bp-cart-table tr { display: block; padding: 16px; border-bottom: 1px solid var(--bp-border); }
    .bp-cart-table td { display: flex; justify-content: space-between; padding: 6px 0; border: none; }
    .bp-product-row { flex-direction: row; }
    .bp-prod-thumb { width: 60px; height: 60px; }
    .bp-cart-table tfoot tr { display: flex; justify-content: space-between; padding: 10px 16px; }
    .bp-cart-table tfoot th, .bp-cart-table tfoot td { padding: 0; border: none; }
}
</style>

<script>
jQuery(document).ready(function($) {
    var timeout;
    function bpUpdateCart() {
        $('.bp-cart-form').addClass('bp-cart-updating');
        $('button[name="update_cart"]').prop('disabled', false);
        $('.bp-cart-form').submit();
    }
    $('.bp-qty-plus').on('click', function() {
        var input = $(this).siblings('.bp-qty-input');
        var val = parseInt(input.val(), 10) || 1;
        if (val < 99) { input.val(val + 1); clearTimeout(timeout); timeout = setTimeout(bpUpdateCart, 400); }
    });
    $('.bp-qty-minus').on('click', function() {
        var input = $(this).siblings('.bp-qty-input');
        var val = parseInt(input.val(), 10) || 1;
        if (val > 1) { input.val(val - 1); clearTimeout(timeout); timeout = setTimeout(bpUpdateCart, 400); }
    });
    $('.bp-qty-input').on('change', function() {
        var val = parseInt($(this).val(), 10) || 1;
        if (val < 1) $(this).val(1);
        if (val > 99) $(this).val(99);
        clearTimeout(timeout);
        timeout = setTimeout(bpUpdateCart, 400);
    });
    $('button[name="update_cart"]').hide();
});
</script>

<?php get_footer(); ?>
