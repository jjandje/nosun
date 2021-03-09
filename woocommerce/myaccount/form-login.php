<?php
/**
 * Login Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="container">
	<?php wc_print_notices(); ?>
</div>
<section class="nosun-login row-padding">
	<div class="container">
		<div class="nosun-login__login">

			<div id="login" class="login-popup">
				<h3 class="regular-title title-wrapper">Inloggen</h3>

				<div class="fb-login">
					<?php if (function_exists('jfb_output_facebook_btn')) jfb_output_facebook_btn(); ?>
				</div>

				<form class="nosun-form" method="post" id="loginform">
					<?php do_action( 'woocommerce_login_form_start' ); ?>
					<ul class="nosun-login__column">
						<li>
							<label for="username">Gebruikersnaam of e-mailadres</label>
							<input type="text" class="input" name="username" id="username" value="<?php echo ( ! empty( $_POST[ 'username' ] ) ) ? esc_attr( wp_unslash( $_POST[ 'username' ] ) ) : ''; ?>" />
						</li>
					</ul>
					<ul class="nosun-login__column">
						<li>
							<label for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?></label>
							<input class="input required" type="password" name="password" id="password" />
						</li>
					</ul>
					<?php do_action( 'woocommerce_login_form' ); ?>
					<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>

						<button type="submit" class="woocommerce-Button button btn btn--blue btn--padding" name="login" value="<?php esc_attr_e( 'Login', 'woocommerce' ); ?>"><?php esc_html_e( 'Login', 'woocommerce' ); ?></button>
						<div class="login-popup__remember">
							<label>
								<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
								<span>Onthouden</span>
							</label>
							<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
						</div>
					<?php do_action( 'woocommerce_login_form_end' ); ?>

				</form>
			</div>
		</div>
	</div>
</section>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
