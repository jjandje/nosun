<?php
/**
 * Lost password confirmation text.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/lost-password-confirmation.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>


<section class="nosun-login row-padding">
	<div class="container">
		<div class="nosun-login__login">

			<div id="login" class="login-popup">
				<?php wc_print_notices(); ?>
				<div class="title-wrapper">
					<h3 class="regular-title">Wachtwoord is verstuurd</h3>
					<p>Een wachtwoord reset-email is naar je geregistreerde e-mailadres gestuurd, maar het kan enkele minuten duren voor deze in je inbox verschijnt. Wacht a.u.b. minimaal 10 minuten, om te voorkomen dat wachtwoorden elkaar kruizen.</p>
				</div>
			</div>
		</div>
	</div>
</section>
