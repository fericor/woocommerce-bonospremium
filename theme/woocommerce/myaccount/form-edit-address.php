<?php
/**
 * Edit address form - Personalizado BonosPremium
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-address.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$is_billing = ( 'billing' === $load_address );
$page_title = $is_billing ? 'Dirección de facturación' : 'Dirección de envío';

do_action( 'woocommerce_before_edit_account_address_form' ); ?>

<?php if ( ! $load_address ) : ?>
	<?php wc_get_template( 'myaccount/my-address.php' ); ?>
<?php else : ?>

	<form method="post" novalidate class="woocommerce-EditAddressForm">

		<div class="bp-edit-address-head">
			<h2><?php echo esc_html( apply_filters( 'woocommerce_my_account_edit_address_title', $page_title, $load_address ) ); ?></h2>
			<?php if ( $is_billing ) : ?>
				<p>Gestiona los datos de facturación de tu cuenta.</p>
			<?php else : ?>
				<p>Gestiona la dirección de envío de tu cuenta.</p>
			<?php endif; ?>
		</div>

		<div class="woocommerce-address-fields">
			<?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

			<div class="woocommerce-address-fields__field-wrapper">
				<?php
				foreach ( $address as $key => $field ) {
					woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) );
				}
				?>
			</div>

			<?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

			<p class="bp-edit-address-submit">
				<button type="submit" class="button woocommerce-Button bp-btn-primary" name="save_address" value="Guardar dirección">Guardar dirección</button>
				<?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
				<input type="hidden" name="action" value="edit_address" />
			</p>
		</div>

	</form>

<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
