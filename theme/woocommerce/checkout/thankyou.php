<?php
/**
 * Custom thank you / order received content
 * Override de WooCommerce checkout/thankyou.php - SOLO contenido (sin header/footer,
 * porque WooCommerce lo renderiza dentro de la página de checkout).
 */

defined('ABSPATH') || exit;
?>
<div class="bp-thankyou-wrap">
    <div class="bp-thankyou-header">
        <div class="bp-thankyou-icon">&#10003;</div>
        <h1 class="bp-thankyou-title">¡Gracias por tu compra!</h1>
        <p class="bp-thankyou-sub">Tu pedido ha sido recibido y está siendo procesado.</p>
        <?php if ($order) : ?>
        <p class="bp-thankyou-order-num">
            Pedido: <strong>#<?php echo $order->get_order_number(); ?></strong>
        </p>
        <?php endif; ?>
    </div>

    <?php if ($order) : ?>
    <div class="bp-thankyou-details">
        <div class="bp-detail-card">
            <h3><i class="fas fa-info-circle"></i> Detalles del pedido</h3>
            <div class="bp-detail-row">
                <span>Fecha</span>
                <span><?php echo wc_format_datetime($order->get_date_created()); ?></span>
            </div>
            <div class="bp-detail-row">
                <span>Total</span>
                <span><strong><?php echo $order->get_formatted_order_total(); ?></strong></span>
            </div>
            <div class="bp-detail-row">
                <span>Método de pago</span>
                <span><?php echo $order->get_payment_method_title(); ?></span>
            </div>
            <div class="bp-detail-row">
                <span>Estado</span>
                <span class="bp-status-<?php echo esc_attr($order->get_status()); ?>"><?php echo wc_get_order_status_name($order->get_status()); ?></span>
            </div>
        </div>

        <?php if ($order->has_downloadable_item()) : ?>
        <div class="bp-detail-card">
            <h3><i class="fas fa-download"></i> Descargas</h3>
            <p class="bp-detail-hint">Puedes descargar tus productos desde tu cuenta.</p>
        </div>
        <?php endif; ?>

        <div class="bp-detail-card">
            <h3><i class="fas fa-box"></i> Productos</h3>
            <table class="bp-thankyou-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th class="bp-text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order->get_items() as $item) : ?>
                    <tr>
                        <td><?php echo esc_html($item->get_name()); ?></td>
                        <td><?php echo $item->get_quantity(); ?></td>
                        <td class="bp-text-right"><?php echo $order->get_formatted_line_subtotal($item); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <?php foreach ($order->get_order_item_totals() as $total) : ?>
                    <tr>
                        <th colspan="2" class="bp-text-right"><?php echo esc_html($total['label']); ?></th>
                        <td class="bp-text-right"><?php echo $total['value']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="bp-thankyou-actions">
        <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="bp-btn-primary">
            <i class="fas fa-user"></i> Ir a mi cuenta
        </a>
        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="bp-btn-secondary">
            <i class="fas fa-arrow-left"></i> Seguir comprando
        </a>
    </div>
</div>

<style>
.bp-thankyou-wrap { max-width: 1100px; margin: 40px auto; }
.bp-thankyou-header { text-align: center; margin-bottom: 32px; }
.bp-thankyou-icon {
    width: 72px; height: 72px;
    background: var(--bp-primary);
    color: #fff; font-size: 32px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    margin-bottom: 16px;
}
.bp-thankyou-title { font-size: 28px; font-weight: 700; color: var(--bp-text); margin: 0 0 8px; }
.bp-thankyou-sub { color: var(--bp-text-light); margin: 0 0 4px; }
.bp-thankyou-order-num { font-size: 15px; color: var(--bp-primary); margin: 8px 0 0; }
.bp-thankyou-details { display: flex; flex-direction: column; gap: 20px; margin-bottom: 32px; }
.bp-detail-card {
    background: var(--bp-card-bg); border: 1px solid var(--bp-border);
    border-radius: 0; padding: 24px;
}
.bp-detail-card h3 {
    font-size: 15px; font-weight: 600; color: var(--bp-text);
    margin: 0 0 16px; display: flex; align-items: center; gap: 8px;
}
.bp-detail-card h3 i { color: var(--bp-primary); }
.bp-detail-row {
    display: flex; justify-content: space-between;
    padding: 8px 0; border-bottom: 1px solid var(--bp-border);
    font-size: 14px; color: var(--bp-text-light);
}
.bp-detail-row:last-child { border: none; }
.bp-detail-hint { color: var(--bp-text-muted); font-size: 14px; margin: 0; }
.bp-thankyou-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.bp-thankyou-table th {
    text-align: left; font-weight: 600; color: var(--bp-text-muted);
    padding: 8px 4px 12px; border-bottom: 2px solid var(--bp-border);
}
.bp-thankyou-table td {
    padding: 10px 4px; border-bottom: 1px solid var(--bp-border);
    color: var(--bp-text);
}
.bp-thankyou-table tfoot td,
.bp-thankyou-table tfoot th { padding: 8px 4px; border: none; font-weight: 600; }
.bp-text-right { text-align: right; }
.bp-thankyou-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; margin-top: 8px; }

/* Ambos botones con LA MISMA forma y tamaño */
.bp-btn-primary, .bp-btn-secondary {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    min-width: 200px !important;
    padding: 14px 30px !important;
    margin: 0 !important;
    border-radius: 0 !important;
    border: none !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    line-height: 1.2 !important;
    box-sizing: border-box !important;
    transition: all .2s !important;
    cursor: pointer;
}
/* "Ir a mi cuenta" -> color primario (azul) */
.bp-btn-primary {
    background: var(--bp-primary) !important;
    color: #fff !important;
}
.bp-btn-primary:hover {
    background: var(--bp-primary-dark) !important;
    color: #fff !important;
}
/* "Seguir comprando" -> mismo tamaño, color distinto (gris / neutro) */
.bp-btn-secondary {
    background: #f3f4f6 !important;
    color: var(--bp-text) !important;
    border: 1px solid #e0e0e0 !important;
}
.bp-btn-secondary:hover {
    background: #e5e7eb !important;
    color: var(--bp-text) !important;
}

/* Grid de detalles en desktop: 2 columnas */
@media (min-width: 769px) {
    .bp-thankyou-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        align-items: start;
    }
    /* Tarjeta descargas (si aparece sola) puede ocupar columna */
}
/* Móvil: 1 columna */
@media (max-width: 768px) {
    .bp-thankyou-wrap { max-width: 480px; padding: 0 16px; }
    .bp-thankyou-details { grid-template-columns: 1fr; }
}
</style>
