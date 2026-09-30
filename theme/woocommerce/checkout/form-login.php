<?php
/**
 * Checkout login form
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.0.0
 */
defined( 'ABSPATH' ) || exit;

$registration_at_checkout   = WC_Checkout::instance()->is_registration_enabled();
$login_reminder_at_checkout = 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' );

if ( is_user_logged_in() ) {
	return;
}

if ( $login_reminder_at_checkout ) : ?>
<!-- ═══ AVISO: edita este bloque ═══ -->
<div class="woocommerce-form-login-toggle">
	<div class="woocommerce-info" role="status">
		<b style="font-size: .85rem; color: #000000;">¿Ya eres usuario?</b> <a href="#" class="showlogin">Haz clic aquí para acceder</a>
	</div>


<?php if ( $registration_at_checkout || $login_reminder_at_checkout ) :
	$show_form = isset( $_POST['login'] );
?>
<!-- ═══ FORMULARIO LOGIN: edita este bloque ═══ -->
<form class="woocommerce-form woocommerce-form-login login" method="post" style="<?php echo $show_form ? '' : 'display:none;'; ?>">

	<p class="form-row form-row-first">
		<label for="username">Usuario o email&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">Obligatorio</span></label>
		<input type="text" class="input-text" name="username" id="username" autocomplete="username" required aria-required="true" />
	</p>
	<p class="form-row form-row-last">
		<label for="password">Contraseña&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">Obligatorio</span></label>
		<input class="input-text woocommerce-Input" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
	</p>
	<div class="clear"></div>

	<p class="form-row">
		<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
		<input type="hidden" name="redirect" value="<?php echo esc_url( wc_get_checkout_url() ); ?>" />
		<button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="Acceder">Acceder</button>
	</p>
	
	<div class="frmFooterLogin">
		<p class="rememberme">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
				<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
				<span>Recuérdame</span>
			</label>
		</p>
		<p class="lost_password">
			<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">¿Olvidaste la contraseña?</a>
		</p>
	</div>
	
	<div class="clear"></div>

</form>
<?php endif; ?>
	
	
	</div>
<?php endif; ?>