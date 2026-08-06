<?php
/**
 * Orders - APP STYLE personalizado (customizado para BonosPremium)
 *
 * Shows orders on the account page.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders ); ?>

<div class="bp-orders-app">

<?php if ( $has_orders ) : ?>

	<div class="bp-orders-title">
		<h2>Mis pedidos</h2>
		<p>Todos tus bonos y compras realizadas.</p>
	</div>

	<div class="bp-orders-list">
		<?php
		foreach ( $customer_orders->orders as $customer_order ) {
			$order      = wc_get_order( $customer_order );
			$item_count = $order->get_item_count() - $order->get_item_count_refunded();
			$status     = $order->get_status();
			?>
			<div class="bp-order-card status-<?php echo esc_attr( $status ); ?>">
				<div class="bp-order-top">
					<div class="bp-order-number">
						<span class="bp-order-label">Pedido</span>
						<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="bp-order-num">
							#<?php echo esc_html( $order->get_order_number() ); ?>
						</a>
					</div>
					<span class="bp-order-status"><?php echo esc_html( wc_get_order_status_name( $status ) ); ?></span>
				</div>
				<div class="bp-order-row">
					<div class="bp-order-col">
						<span class="bp-order-label">Fecha</span>
						<span class="bp-order-value"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
					</div>
					<div class="bp-order-col">
						<span class="bp-order-label">Total</span>
						<span class="bp-order-value"><?php echo wp_kses_post( sprintf( _n( '%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'woocommerce' ), $order->get_formatted_order_total(), $item_count ) ); ?></span>
					</div>
				</div>
				<div class="bp-order-actions">
					<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="bp-order-btn bp-order-btn-view">Detalle</a>
				</div>
			</div>
			<?php
		}
		?>
	</div>

	<?php if ( 1 < $customer_orders->max_num_pages ) : ?>
		<div class="bp-orders-pagination">
			<?php if ( 1 !== $current_page ) : ?>
				<a class="bp-order-btn" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>">← Anterior</a>
			<?php endif; ?>
			<?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
				<a class="bp-order-btn" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>">Siguiente →</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

<?php else : ?>
	<div class="bp-orders-empty">
		<div class="bp-orders-empty-icon"><i class="fab fa-shopify"></i></div>
		<h3>No has realizado pedidos todavía</h3>
		<p>Explora nuestras ofertas y encuentra el bono perfecto.</p>
		<a class="bp-order-btn" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">Ver productos</a>
	</div>
<?php endif; ?>

</div>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
