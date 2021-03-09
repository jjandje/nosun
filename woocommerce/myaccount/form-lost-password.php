<?php
/**
 * Lost password form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-lost-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="nosun-login row-padding">

	<div class="container">
		<div class="nosun-login__login">

			<?php wc_print_notices(); ?>

			<div id="login" class="login-popup">
				<div class="title-wrapper">
					<h3 class="regular-title">Wachtwoord vergeten?</h3>
					<p><?php echo apply_filters( 'woocommerce_lost_password_message', __( 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'woocommerce' ) ); ?></p>

				</div>
				<form class="nosun-form woocommerce-ResetPassword lost_reset_password" method="post" id="loginform">
					<?php do_action( 'woocommerce_login_form_start' ); ?>
					<ul class="nosun-login__column nosun-login__column--push">
						<li>
							<label for="username">Gebruikersnaam of e-mailadres</label>
							<input class="input" type="text" name="user_login" id="user_login" />
						</li>
					</ul>

					<?php wp_nonce_field( 'lost_password' ); ?>

					<?php do_action( 'woocommerce_lostpassword_form' ); ?>

						<input type="hidden" name="wc_reset_password" value="true" />
					<div class="clearfix"></div>
						<button type="submit" class="woocommerce-Button button btn" name="login" value="Wachtwoord opnieuw instellen"><?php esc_attr_e( 'Reset password', 'woocommerce' ); ?></button>
				</form>
			</div>
		</div>
	</div>
</section>
