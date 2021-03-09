<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
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

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Roots\Sage\Assets;
use Vazquez\NosunAssumaxConnector\Api\Customers;

use lib\controllers\Trip; // @since 26-05-2020
use Vazquez\NosunAssumaxConnector\Helpers; // @since 26-05-2020

defined('ABSPATH') || exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked wc_print_notices - 10
 */
do_action('woocommerce_before_single_product');

if (post_password_required()) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}

$error = get_query_var('err');

$today = Helpers::today(); // @since 06-05-2020

// Get all the Trip fields.
$postId = get_the_ID();
$fields = get_fields($postId);
$isNotBookable = false;
if (empty($fields) || empty($fields['trip_availability']) || $fields['trip_availability'] === 'Unavailable') $isNotBookable = true;
if (empty($fields['trip_start_date']) || empty($fields['trip_end_date'])) $isNotBookable = true;
$startDate = $fields['trip_start_date'];
$endDate = $fields['trip_end_date'];
$numDays = $fields['trip_num_days'];
$price = get_post_meta($postId, '_price', true);
if (empty($price)) $isNotBookable = true;
$totalPrice = floatval($price);
$extraCharges = isset($fields['trip_extra_charges']) ? $fields['trip_extra_charges'] : [];
foreach ($extraCharges as $extraCharge) {
	if (empty($extraCharge['title'] || empty($extraCharge['price']))) continue;
	$totalPrice += floatval($extraCharge['price']);
}
$templatePostId = get_post_meta($postId, 'trip_template', true);
if (empty($templatePostId)) {
	$isNotBookable = true;
} else {
	$template = get_post($templatePostId);
	if (!empty($template)) {
		$productTitle = $template->post_title;
		$subTitle2 = get_field('template_subtitle2', $template->ID);
		$profileImage = get_field('template_profile_image', $template->ID);
		$tripTypeTerms = wp_get_post_terms($template->ID, "trip-type");
		$primaryTermObject = new WPSEO_Primary_Term('destination', $template->ID);
		$primaryDestinationTermId = $primaryTermObject->get_primary_term();
		if (!empty($primaryDestinationTermId)) {
			$primaryDestination = get_term_by('id', $primaryDestinationTermId, 'destination');
		}
		$ageGroupsTerms = wp_get_post_terms($template->ID, "age-group");
	} else {
		$isNotBookable = true;
	}
}

$startDate = get_field('trip_start_date'); // @since 26-05-2020
$availability = Trip::get_availability(); // @since 26-05-2020
$tripConfirmed = get_field('trip_confirmed');
$tripStatus = get_field('trip_status');

// Get the user info when the user is logged in.
$userId = get_current_user_id();
$firstName = '';
$nickName = '';
$lastName = '';
$emailAddress = '';
$birthDate = '';
$phoneNumber = '';
if (!empty($userId)) {
	$customer = Customers::get_by_user_id($userId);
	if (!empty($customer)) {
		$firstName = get_field('customer_first_name', $customer->ID);
		$nickName = get_field('customer_nick_name', $customer->ID);
		$lastName = get_field('customer_last_name', $customer->ID);
		$emailAddress = get_field('customer_email_address', $customer->ID);
		$birthDate = date("d-m-Y", strtotime(get_field('customer_date_of_birth', $customer->ID)));
		$phoneNumber = get_field('customer_phone_number', $customer->ID);
	}
}
// Check if there is any date from a previous booking submission attempt.
$extraCustomers = [];
$isOption = false;
if (!empty($_COOKIE['previous_data'])) {
	$cookieData = null;
	$key = Key::loadFromAsciiSafeString(NOSUN_CRYPTO_KEY);
	if (!empty($key)) {
		try {
			$decrypted = Crypto::decrypt($_COOKIE['previous_data'], $key);
			$cookieData = maybe_unserialize($decrypted);
		} /** @noinspection PhpRedundantCatchClauseInspection */
		catch (WrongKeyOrModifiedCiphertextException $e) {
			error_log("[content-single-product.php]: {$e->getMessage()}");
		}
	}
	if (is_array($cookieData)) {
		if (!empty($cookieData['customer']) && is_array($cookieData['customer'])) {
			foreach ($cookieData['customer'] as $i => $customer) {
				if ($i === 0) {
					$firstName = $customer['first_name'] ?? $firstName;
					$nickName = $customer['nickname'] ?? $nickName;
					$lastName = $customer['last_name'] ?? $lastName;
					$emailAddress = $customer['email'] ?? $emailAddress;
					$birthDate = $customer['birthdate'] ?? $birthDate;
					$phoneNumber = $customer['phone'] ?? $phoneNumber;
				} elseif ($i !== 'NUMBER') {
					$extraCustomers[] = $customer;
				}
			}
		}
		$isOption = !empty($cookieData['optie']);
	}
}

$tripAssumaxId = get_post_meta($postId, '_assumax_id', true);

// if ($isNotBookable): ?>
<!-- <div class="booking-wrapper">
		<div class="container">
			<h2 class="alert alert-warning">Deze reis is helaas niet (meer) te boeken...</h2>
			<span>Je wordt na 5 seconden automatisch teruggebracht naar de home pagina, of klik <a
						href="<?= get_home_url(); ?>">hier</a> om zelf terug te gaan.</span>
			<script>
                setTimeout(function () {
                    window.location.href = "<?= get_home_url(); ?>";
                }, 5000);
			</script>
		</div>
	</div> -->
<?php // else: ?>

<script type="text/javascript">
    jQuery(document).ready(function () {
        waitForFbq(function () {
            fbq('track', 'AddToCart', {
                content_ids: ['<?= $tripAssumaxId; ?>'],
                content_type: 'product',
                value: <?= $price; ?>,
                currency: 'EUR'
            });
        });
    });
</script>
<div class="booking-wrapper">
	<div class="container">
		<ul class="booking-wrapper__header">
			<li class="step-completed">
				1. Kies jouw groepsreis
			</li>
			<li class="step-active">
				2. Vul je gegevens in
			</li>
			<li>
				3. Geboekt
			</li>
			<li>
				4. Verzekeringen en verdere gegevens
			</li>
		</ul>
	</div>
	<div class="booking-wrapper__overview">
		<div class="container">
			<div class="booking-form row-padding">
				<?php if(($today->format('Y-m-d') > date('Y-m-d', strtotime($startDate))) || ($availability['status'] == 'red' && $tripConfirmed === false ) ) : ?>
					<h1 class="page-title-border">Helaas.. Deze reis is niet meer te boeken</h1>
				<?php elseif($availability['status'] == 'red' && $tripConfirmed === true && $tripStatus === 'Confirmed') : ?>
					<h1 class="page-title-border">Deze reis is al volgeboekt</h1>
				<?php else : ?>
					<h1 class="page-title-border">Boek jouw favoriete groepsreis</h1>
					<?php if (!empty($error)): ?>
						<div class="notifications-box" style="margin-left: 0; width: 100%; float: none; padding: 0;">
							<div class="alert alert-danger">
								<i class="fa fa-times"></i> <?= $error; ?>
							</div>
						</div>
					<?php endif; ?>
					<?php if (!is_user_logged_in()): ?>
						<div class="booking-form__login">
							<a href="#" class="btn btn--grey btn--arrow-white login-btn"><i class="fa fa-user"></i>
								Eerder geboekt bij noSun? Login</a>
							<div id="login" class="login-popup" style="display: none">
								<h3 class="regular-title">Inloggen</h3>
								<?php
								jfb_output_facebook_btn();
								?>
								<form class="woocommerce-form woocommerce-form-login login nosun-form" method="post"
									  id="loginform">
									<?php do_action('woocommerce_login_form_start'); ?>
									<ul class="booking-form__column">
										<li>
											<label for="username">Gebruikersnaam of mailadres</label>
											<input type="text" class="input" name="username" id="username"
												   value="<?php echo (!empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>"/>
										</li>
									</ul>
									<ul class="booking-form__column">
										<li>
											<label for="password"><?php esc_html_e('Password', 'woocommerce'); ?></label>
											<input class="input required" type="password" name="password"
												   id="password"/>
										</li>
									</ul>
									<?php do_action('woocommerce_login_form'); ?>
									<?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
									<button type="submit" class="woocommerce-Button button" name="login"
											value="<?php esc_attr_e('Login', 'woocommerce'); ?>"><?php esc_html_e('Login', 'woocommerce'); ?></button>
									<div class="login-popup__remember">
										<label>
											<input class="woocommerce-form__input woocommerce-form__input-checkbox"
												   name="rememberme" type="checkbox" id="rememberme" value="forever"/>
											<span>Onthouden</span>
										</label>
										<a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Lost your password?', 'woocommerce'); ?></a>
									</div>
									<?php do_action('woocommerce_login_form_end'); ?>
								</form>
							</div>
						</div>
					<?php
					else: ?>
						<div class="booking-form__login">
							<p><?php if (is_user_logged_in()): ?> Controleer hieronder je gegevens en vul waar nodig aan.<?php endif; ?></p>
						</div>
					<?php endif; ?>
					<form action="<?= esc_url(admin_url('admin-post.php')); ?>" method="post" id="bookform" class="nosun-form">
						<?= wp_nonce_field('new_booking'); ?>
						<ul class="booking-form__column">
							<li>
								<label for="nickname_0">Roepnaam</label>
								<input id="nickname_0" name="customer[0][nickname]" class="input"
									   value="<?php echo $nickName ?>"
									   placeholder="We spreken je graag aan met je roepnaam"/>
							</li>
							<li>
								<label for="last_name_0">Achternaam</label>
								<input id="last_name_0" autocomplete="off" name="customer[0][last_name]"
									   value="<?php echo $lastName ?>" class="input" placeholder=""/>
							</li>
							<li>
								<label for="first_name_0">Alle voornamen volgens paspoort</label>
								<input id="first_name_0" name="customer[0][first_name]" autocomplete="off"
									   value="<?php echo $firstName ?>" class="input"
									   placeholder="Nodig voor o.a. ferry- en/of vliegtickets"/>
							</li>
							<li>
								<label for="birthdate_0">Geboortedatum</label>
								<input id="birthdate_0" name="customer[0][birthdate]" value="<?php echo $birthDate ?>"
									   class="input" placeholder="dd-mm-jjjj"/>
							</li>
							<li>
								<label for="phone_0">Telefoonnummer</label>
								<input id="phone_0" name="customer[0][phone]" value="<?php echo $phoneNumber ?>"
									   class="input"
									   placeholder="Bij vragen over (vlieg)tickets bellen wij liever"/>
							</li>
							<?php if (!is_user_logged_in()): ?>
								<li>
									<label for="email_0">E-mailadres</label>
									<input id="email_0" autocomplete="off" name="customer[0][email]"
										   value="<?php echo $emailAddress ?>" type="email" class="input"
										   placeholder="Je ontvangt direct een bevestiging per e-mail"/>
								</li>
								<li>
									<label for="email_confirm_0">Bevestig e-mailadres</label>
									<input id="email_confirm_0" autocomplete="off" name="customer[0][email_confirm]"
										   value="<?php echo $emailAddress ?>" type="email" class="input"
										   placeholder="Vul hier nogmaals je e-mail adres in"/>
								</li>
							<?php endif; ?>
						</ul>
						<div class="clearfix"></div>
						<div class="extra-travelers-container">
							<?php
							if (!empty($extraCustomers)):
								foreach ($extraCustomers as $i => $extraCustomer): ?>
									<div style="display: block;" class="extra-traveler-block">
										<h3>Extra Reiziger <b><?= $i+1; ?></b>
											<a href="#" class="delete-extra-traveler" title="Extra reiziger verwijderen"><i
														class="fas fa-times-circle"></i></a>
										</h3>
										<ul class="booking-form__column">
											<li>
												<label for="nickname_<?= $i+1; ?>">Roepnaam</label>
												<input id="nickname_<?= $i+1; ?>" name="customer[<?= $i+1; ?>][nickname]"
													   class="input required" value="<?= $extraCustomer['nickname'] ?? ''; ?>"
													   placeholder="We spreken je graag aan met je roepnaam"/>
											</li>
											<li>
												<label for="last_name_<?= $i+1; ?>">Achternaam</label>
												<input id="last_name_<?= $i+1; ?>" autocomplete="off"
													   name="customer[<?= $i+1; ?>][last_name]"
													   value="<?= $extraCustomer['last_name'] ?? ''; ?>" class="input required"
													   placeholder=""/>
											</li>
											<li>
												<label for="first_name_<?= $i+1; ?>">Eerste voornaam (volgens paspoort)</label>
												<input id="first_name_<?= $i+1; ?>" name="customer[<?= $i+1; ?>][first_name]"
													   autocomplete="off" value="<?= $extraCustomer['first_name'] ?? ''; ?>" class="input required"
													   placeholder="Dit hebben we nodig voor o.a. ferry- en/of vliegtickets"/>
											</li>

											<li>
												<label for="birthdate_<?= $i+1; ?>">Geboortedatum</label>
												<input id="birthdate_<?= $i+1; ?>" name="customer[<?= $i+1; ?>][birthdate]" type="text"
													   value="<?= $extraCustomer['birthdate'] ?? ''; ?>" class="input required" placeholder="dd-mm-jjjj"/>
											</li>

											<li>
												<label for="phone_<?= $i+1; ?>">Telefoonnummer</label>
												<input id="phone_<?= $i+1; ?>" name="customer[<?= $i+1; ?>][phone]"
													   value="<?= $extraCustomer['phone'] ?? ''; ?>"
													   class="input required"
													   placeholder="Bij dringende vragen over (vlieg)tickets bellen wij liever."/>
											</li>

											<li>
												<label for="email_<?= $i+1; ?>">E-mailadres</label>
												<input id="email_<?= $i+1; ?>" autocomplete="off" name="customer[<?= $i+1; ?>][email]"
													   value="<?= $extraCustomer['email'] ?? ''; ?>" class="input required"
													   placeholder="Je ontvangt direct een bevestiging per e-mail"/>
											</li>
											<li>
												<label for="email_confirm_<?= $i+1; ?>">Bevestig e-mailadres</label>
												<input id="email_confirm_<?= $i+1; ?>" autocomplete="off"
													   name="customer[<?= $i+1; ?>][email_confirm]"
													   value="<?= $extraCustomer['email_confirm'] ?? ''; ?>" class="input required"
													   placeholder="Vul hier nogmaals je e-mail adres in"/>
											</li>

										</ul>
										<div class="clearfix"></div>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<div class="clearfix"></div>
						<button id="add-extra-traveler" class="btn btn--blue btn--small">Extra reiziger toevoegen</button>
						<div class="clearfix"></div>
						<ul class="booking-form__column">
							<li class="checkbox">
								<input type="checkbox" class="required" id="algemenevoorwaarden"
									   name="algemenevoorwaarden"
									   title="Je dient akkoord te gaan met bovenstaande bepalingen."/>
								<label style="line-height: 20px; height: auto !important;" for="algemenevoorwaarden">Ik
									ga akkoord met de
									<a target="_blank" href="<?= home_url('/anvr-reisvoorwaarden/'); ?>">ANVR
										reisvoorwaarden</a>, de
									<a target="_blank" href="<?= home_url('/aanvullende-nosun-reisvoorwaarden/'); ?>">aanvullende
										noSun<br/> reisvoorwaarden</a>
									en de
									<a target="_blank" href="<?= home_url('/privacy-policy/'); ?>">Privacy Policy</a></label>
							</li>
						</ul>
						<div class="clearfix"></div>
						<div class="booking-form__submit">
							<input type="hidden" name="product_id" value="<?= $postId; ?>"/>
							<input type="hidden" name="action" value="new_booking">
							<button name="submit_form" type="submit" class="btn btn--pink btn--arrow-white submit-btn"
									value="new_booking"><?= $isOption ? "Neem een optie" : "Definitieve boeking"; ?></button>
							<div class="loading"><i class="fas fa-sync fa-spin"></i> We verwerken nu je <span
										class="kind-of">boeking</span>, moment geduld...</div>
							<ul>
								<li class="checkbox">
									<input type="checkbox" id="optie" name="optie" value="1" <?= $isOption ? 'checked' : ''; ?>/>
									<label for="optie">Ik wil een
										<a href="#optiecontent">optie</a> op deze reis
									</label>
								</li>
								<li class="info">
									<i class="fa fa-info-circle"></i>&nbsp;Je kunt je gegevens op een later tijdstip nog
									gerust wijzigen.
								</li>
							</ul>
						</div>
					</form>

					<div id="extra-traveler-data" style="display: none;">
						<h3>Extra Reiziger <b></b>
							<a href="#" class="delete-extra-traveler" title="Extra reiziger verwijderen"><i
										class="fas fa-times-circle"></i></a>
						</h3>
						<ul class="booking-form__column">
							<li>
								<label for="nickname_NUMBER">Roepnaam</label>
								<input id="nickname_NUMBER" name="customer[NUMBER][nickname]"
									   class="input required" value=""
									   placeholder="We spreken je graag aan met je roepnaam"/>
							</li>
							<li>
								<label for="last_name_NUMBER">Achternaam</label>
								<input id="last_name_NUMBER" autocomplete="off"
									   name="customer[NUMBER][last_name]" value="" class="input required"
									   placeholder=""/>
							</li>
							<li>
								<label for="first_name_NUMBER">Eerste voornaam (volgens paspoort)</label>
								<input id="first_name_NUMBER" name="customer[NUMBER][first_name]"
									   autocomplete="off" value="" class="input required"
									   placeholder="Dit hebben we nodig voor o.a. ferry- en/of vliegtickets"/>
							</li>

							<li>
								<label for="birthdate_NUMBER">Geboortedatum</label>
								<input id="birthdate_NUMBER" name="customer[NUMBER][birthdate]" type="text"
									   value="" class="input required" placeholder="dd-mm-jjjj"/>
							</li>

							<li>
								<label for="phone_NUMBER">Telefoonnummer</label>
								<input id="phone_NUMBER" name="customer[NUMBER][phone]" value=""
									   class="input required"
									   placeholder="Bij dringende vragen over (vlieg)tickets bellen wij liever."/>
							</li>

							<li>
								<label for="email_NUMBER">E-mailadres</label>
								<input id="email_NUMBER" autocomplete="off" name="customer[NUMBER][email]"
									   value="" class="input required"
									   placeholder="Je ontvangt direct een bevestiging per e-mail"/>
							</li>
							<li>
								<label for="email_confirm_NUMBER">Bevestig e-mailadres</label>
								<input id="email_confirm_NUMBER" autocomplete="off"
									   name="customer[NUMBER][email_confirm]" value="" class="input required"
									   placeholder="Vul hier nogmaals je e-mail adres in"/>
							</li>

						</ul>
						<div class="clearfix"></div>
					</div>

				<?php endif; ?>

			</div>
			<div class="overview-wrapper">
				<div class="inner bg-grey">
					<div class="overview-wrapper__column">
						<?php if (!empty($profileImage)): ?>
							<div class="product-image" style="background-image: url('<?= wp_get_attachment_image_url($profileImage, 'full'); ?>');"></div>
						<?php endif; ?>
						<span class="column-title">Reis samenstelling</span>
						<ul>
							<li>
								<?php foreach ($tripTypeTerms as $tripTypeTerm): ?>
									<span><img src="<?= Assets\asset_path('images/icon-rondreis.png'); ?>"
											   alt="icon singlerondreis"></span> <?= $tripTypeTerm->name; ?>
								<?php endforeach; ?>
							</li>
							<?php if (!empty($primaryDestination)): ?>
								<li>
                                    <span><img src="<?= Assets\asset_path('images/icon-pointer.png'); ?>"
											   alt="icon locatie alleen op vakantie"></span> <?= $primaryDestination->name; ?>
								</li>
							<?php endif; ?>
							<?php if (!empty($numDays)): ?>
								<li>
                                    <span><img src="<?= Assets\asset_path('images/icon-time.png'); ?>"
											   alt="icon single reizen last minute"></span> <?= $numDays; ?> dagen
								</li>
							<?php endif; ?>
							<li>
                                    <span><img src="<?= Assets\asset_path('images/icon-age.png'); ?>"
											   alt="icon leeftijd single reizen"></span> 20+
							</li>
						</ul>
						<span class="column-title"><?= $productTitle; ?></span>
						<?php if(!empty($subTitle2)): ?>
							<span class="column-subtitle"><?= $subTitle2; ?></span>
						<?php endif; ?>
						<table>
							<tr>
								<td>Vertrekdatum: <strong><?php echo date_i18n("d F Y", strtotime($startDate)); ?></strong></td>
							</tr>
							<tr>
								<td>Terugkomstdatum: <strong><?php echo date_i18n("d F Y", strtotime($endDate)); ?></strong></td>
							</tr>
						</table>
						<hr>
					</div>
					<div class="overview-wrapper__column last">
						<span class="column-title">Prijs samenstelling</span>
						<table>
							<tr>
								<td>Reis: <strong><?= wc_price($price); ?></strong></td>
							</tr>
							<?php if (!empty($extraCharges)):
								foreach ($extraCharges as $extraCharge): ?>
									<tr>
										<td><?= $extraCharge['title']; ?>: <strong><?= wc_price($extraCharge['price']); ?></strong></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
							<tr><td><br></td></tr>
							<tr>
								<td>Totale prijs: <strong><?= wc_price($totalPrice); ?></strong></td>
							</tr>
						</table>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="home-hero__awards booking-page">
					<?php
					$home_id = get_option('page_on_front');
					if (have_rows('home_header_awards', $home_id)) : ?>
						<ul>
							<?php while (have_rows('home_header_awards', $home_id)) : the_row(); ?>
								<li>
									<?php $home_header_awards_award = get_sub_field('home_header_awards_award', $home_id); ?>
									<?php if ($home_header_awards_award) { ?>
										<?php echo wp_get_attachment_image($home_header_awards_award, 'small'); ?>
									<?php } ?>
								</li>
							<?php endwhile; ?>
						</ul>
					<?php endif; ?>
				</div>

				<div class="inner bg-grey why-nosun">
					<div class="overview-wrapper__column">
						<?php if (have_rows('option_usps', 'option')) : ?>
							<span class="column-title">Daarom noSun</span>
							<section class="usps usps-booking">
								<?php while (have_rows('option_usps', 'option')) : the_row(); ?>
									<div class="usp">
										<div class="usp__inner">
											<img src="<?= Assets\asset_path('images/icon-check.png'); ?>"
												 alt="icon <?php the_sub_field('option_usps_usp'); ?>">
											<span><?php the_sub_field('option_usps_usp'); ?></span>
										</div>
									</div>
								<?php endwhile; ?>
								<div class="clearfix"></div>
							</section>
						<?php endif; ?>
						<?php
						if (isset($_GET['trip_id'])):
							if (have_rows('product-rating', $_GET['trip_id'])) : ?>
								<?php get_template_part('templates/modules/ratings'); ?>
							<?php endif;
						endif;
						?>
					</div>
					<div class="clearfix"></div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="remodal" data-remodal-id="optiecontent">
	<button data-remodal-action="close" class="remodal-close"></button>
	<p>Een optie is geheel vrijblijvend en 8 dagen geldig. Wij houden een plaats voor je vrij.
		<br/>Wanneer je een optie hebt op de laatst beschikbare plaats en iemand anders wil deze plaats definitief
		boeken, dan bellen wij je op. Wij vragen dan om diezelfde dag voor 16:00u een keuze te maken.
	</p>
</div>
<?php // endif; ?>
<?php do_action('woocommerce_after_single_product'); ?>
