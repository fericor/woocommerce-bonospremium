<?php
/**
 * Checkout billing information form
 *
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */
defined( 'ABSPATH' ) || exit;
$checkout = WC()->checkout();
$sel_country = $checkout->get_value('billing_country');
if ( empty( $sel_country ) ) $sel_country = 'ES';
?>
<div class="woocommerce-billing-fields">

	<h3>Nuevo Usuario</h3>

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="woocommerce-billing-fields__field-wrapper">
		<!-- ═══ NOMBRE ═══ -->
		<p class="form-row form-row-wide wooccm-field-first_name validate-required" id="billing_first_name_field">
			<label for="billing_first_name">Nombre&nbsp;<span class="required" aria-hidden="true">*</span></label>
			<span class="woocommerce-input-wrapper">
				<input type="text" class="input-text" name="billing_first_name" id="billing_first_name" value="<?php echo esc_attr( $checkout->get_value('billing_first_name') ); ?>" autocomplete="given-name" required />
			</span>
		</p>

		<!-- ═══ APELLIDOS ═══ -->
		<p class="form-row form-row-wide wooccm-field-last_name validate-required" id="billing_last_name_field">
			<label for="billing_last_name">Apellidos&nbsp;<span class="required" aria-hidden="true">*</span></label>
			<span class="woocommerce-input-wrapper">
				<input type="text" class="input-text" name="billing_last_name" id="billing_last_name" value="<?php echo esc_attr( $checkout->get_value('billing_last_name') ); ?>" autocomplete="family-name" required />
			</span>
		</p>

		<!-- ═══ PAÍS (requerido, dinámico) ═══ -->
		<input type="hidden" name="billing_country" id="billing_country" value="ES" />

		<!-- ═══ EMAIL ═══ -->
		<p class="form-row form-row-wide wooccm-field-email validate-required validate-email" id="billing_email_field">
			<label for="billing_email">Email&nbsp;<span class="required" aria-hidden="true">*</span></label>
			<span class="woocommerce-input-wrapper">
				<input type="email" class="input-text" name="billing_email" id="billing_email" value="<?php echo esc_attr( $checkout->get_value('billing_email') ); ?>" autocomplete="off" required />
			</span>
		</p>

		<?php if ( ! is_user_logged_in() ) : ?>
		<!-- ═══ CONFIRMAR EMAIL (solo si no está logueado) ═══ -->
		<p class="form-row form-row-wide validate-required" id="billing_email_confirm_field">
			<label for="billing_email_confirm">Confirmar email&nbsp;<span class="required" aria-hidden="true">*</span></label>
			<span class="woocommerce-input-wrapper">
				<input type="email" class="input-text" name="billing_email_confirm" id="billing_email_confirm" placeholder="" value="" required />
			</span>
		</p>
		<?php endif; ?>

	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>

</div>

<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
<div class="woocommerce-account-fields">

	<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

	<div class="create-account">
		<!-- ═══ CONTRASEÑA ═══ -->
		<p class="form-row validate-required" id="account_password_field">
			<label for="account_password">Contraseña&nbsp;<span class="required" aria-hidden="true">*</span></label>
			<span class="woocommerce-input-wrapper">
				<input type="password" class="input-text" name="account_password" id="account_password" placeholder="" value="" autocomplete="new-password" required />
			</span>
		</p>
		<div class="clear"></div>
	</div>

	<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>

</div>
<?php endif; ?>