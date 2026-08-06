<?php
/**
 * View Order - APP STYLE con descarga de bonos PDF
 *
 * Shows the details of a particular order on the account page.
 * Añade botones para descargar los bonos/vouchers en PDF de la carpeta qrProductos.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.6.0
 */

defined( 'ABSPATH' ) || exit;

$notes = $order->get_customer_order_notes();

// Buscar los vouchers PDF de este pedido en la carpeta qrProductos
// Formato de archivo: voucher_{pedido_id}_{qrbono}.pdf
$order_no = $order->get_order_number();
$vouchers = array();
$qr_dir   = ABSPATH . 'qrProductos/';
$qr_url   = home_url( '/qrProductos/' );

if ( is_dir( $qr_dir ) ) {
	$pattern_voucher = '/^voucher_' . preg_quote( (string) $order_no, '/' ) . '_([a-zA-Z0-9]+\.pdf)$/';
	$files = scandir( $qr_dir );
	foreach ( $files as $file ) {
		if ( preg_match( $pattern_voucher, $file, $m ) ) {
			$vouchers[] = $file;
		}
	}
	sort( $vouchers );
}
?>

<?php if ( ! empty( $vouchers ) ) : ?>
	<div class="bp-vouchers-box">
		<h2 class="bp-vouchers-title"><i class="fas fa-ticket-alt"></i> Tus bonos</h2>
		<p class="bp-vouchers-sub">Descarga el PDF de cada bono para tu regalo o canje.</p>
		<div class="bp-vouchers-list">
			<?php if ( count( $vouchers ) === 1 ) : ?>
				<a href="<?php echo esc_url( $qr_url . rawurlencode( $vouchers[0] ) ); ?>" class="bp-voucher-btn" target="_blank" rel="noopener">
					<span class="bp-voucher-icon"><i class="fas fa-file-pdf"></i></span>
					<span class="bp-voucher-info">
						<span class="bp-voucher-name">Descargar bono</span>
						<span class="bp-voucher-file"><?php echo esc_html( $vouchers[0] ); ?></span>
					</span>
					<span class="bp-voucher-arrow"><i class="fas fa-download"></i></span>
				</a>
			<?php else : ?>
				<?php foreach ( $vouchers as $i => $voucher_file ) : ?>
					<a href="<?php echo esc_url( $qr_url . rawurlencode( $voucher_file ) ); ?>" class="bp-voucher-btn" target="_blank" rel="noopener">
						<span class="bp-voucher-icon"><i class="fas fa-file-pdf"></i></span>
						<span class="bp-voucher-info">
							<span class="bp-voucher-name">Descargar bono <?php echo ( $i + 1 ); ?></span>
							<span class="bp-voucher-file"><?php echo esc_html( $voucher_file ); ?></span>
						</span>
						<span class="bp-voucher-arrow"><i class="fas fa-download"></i></span>
					</a>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>

<p>
<?php
echo wp_kses_post(
	apply_filters(
		'woocommerce_order_details_status',
		sprintf(
			/* translators: 1: order number 2: order date 3: order status */
			esc_html__( 'Order #%1$s was placed on %2$s and is currently %3$s.', 'woocommerce' ),
			'<mark class="order-number">' . $order->get_order_number() . '</mark>',
			'<mark class="order-date">' . wc_format_datetime( $order->get_date_created() ) . '</mark>',
			'<mark class="order-status">' . wc_get_order_status_name( $order->get_status() ) . '</mark>'
		),
		$order
	)
);
?>
</p>

<?php if ( $notes ) : ?>
	<h2><?php esc_html_e( 'Order updates', 'woocommerce' ); ?></h2>
	<ol class="woocommerce-OrderUpdates commentlist notes">
		<?php foreach ( $notes as $note ) : ?>
		<li class="woocommerce-OrderUpdate comment note">
			<div class="woocommerce-OrderUpdate-inner comment_container">
				<div class="woocommerce-OrderUpdate-text comment-text">
					<p class="woocommerce-OrderUpdate-meta meta"><?php echo date_i18n( esc_html__( 'l jS \o\f F Y, h:ia', 'woocommerce' ), strtotime( $note->comment_date ) ); ?></p>
					<div class="woocommerce-OrderUpdate-description description">
						<?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?>
					</div>
					<div class="clear"></div>
				</div>
				<div class="clear"></div>
			</div>
		</li>
		<?php endforeach; ?>
	</ol>
<?php endif; ?>

<?php do_action( 'woocommerce_view_order', $order_id ); ?>
