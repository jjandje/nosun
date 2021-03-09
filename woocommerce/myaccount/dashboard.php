<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * My Account dashboard.
 *
 * @since 2.6.0
 */
do_action( 'woocommerce_account_dashboard' );

do_action( 'woocommerce_before_my_page' );

/**
 * Deprecated woocommerce_before_my_account action.
 *
 * @deprecated 2.6.0
 */
do_action( 'woocommerce_before_my_account' );

/**
 * Deprecated woocommerce_after_my_account action.
 *
 * @deprecated 2.6.0
 */
do_action( 'woocommerce_after_my_account' );

use lib\controllers\User;
use Roots\Sage\Assets;
use Vazquez\NosunAssumaxConnector\Api\Customers;
use Vazquez\NosunAssumaxConnector\Api\TravelGroups as TravelGroupAPI;
use Roots\Sage\Helpers;

$userId = get_current_user_id();
$customer = Customers::get_by_user_id($userId);
$fullName = User::get_customer_full_name(empty($customer) ? null : $customer->ID, $userId);
$userHasIdentification = !empty($customer) && !empty(get_post_meta($customer->ID, 'customer_documents', true));
$additionalIdentificationRequired = false;
$profileImage = get_field('profile_image', 'user_' . $userId);
$bookingPosts = !empty($customer) ? User::get_customer_bookings($customer->ID) : [];
$bookings = [];
$options = [];
if (!empty($bookingPosts)) {
	foreach ($bookingPosts as $bookingPost) {
		$tripId = get_field('booking_trip', $bookingPost->ID);
		if (empty($tripId)) continue;
		$templateId = get_field('trip_template', $tripId);
		if (empty($templateId)) continue;
		$bookingData = [
			'booking' => $bookingPost,
			'trip' => $tripId,
			'template' => $templateId
		];
		if (get_field('booking_is_option', $bookingPost->ID)) {
			$options[] = $bookingData;
		} else {
			$templateRequiresIdentification = get_field('template_requires_identification', $templateId);
			if ($templateRequiresIdentification && !$userHasIdentification) $additionalIdentificationRequired = true;
			$bookings[] = $bookingData;
		}
	}
}
$travelGroups = TravelGroupAPI::get_by_user_id($userId);

?>
<section class="nosun-account tour-information row-padding">
	<div class="container">
		<?php wc_print_notices(); ?>
		<div class="tour-information__column account-column">
			<div class="tour-information__row">
				<h2><i class="fas fa-user"></i> Persoonsgegevens</h2>
				<div class="tour-information__profile" style="display: flex;">
					<?php if ($profileImage): ?>
						<?= wp_get_attachment_image($profileImage, 'thumbnail'); ?>
					<?php else: ?>
						<img src="<?= Assets\asset_path( 'images/no-profile-image.png' ); ?>" style="max-width: 96px;" alt="Geen profiel afbeelding"/>
					<?php endif; ?>
					<ul>
						<li>
							<span style="overflow-wrap: break-word;"><?= $fullName; ?></span>
						</li>
						<li>
							<a href="<?= wc_get_account_endpoint_url( 'edit-account' ); ?>">Profiel bewerken<i class="fas fa-pencil-alt" style="margin-left:10px;"></i></a>
						</li>
					</ul>
					<div class="clearfix"></div>
				</div>
			</div>
		</div>
		<div class="account-table-rows">
			<div class="account-table-rows__row">
				<h2>Reizen</h2>
				<table class="nosun-table">
					<thead>
					<tr>
						<td class="trip-max-width">Reis</td>
						<td>Bestemming</td>
						<td style="white-space: nowrap;">Boekingsdatum</td>
						<td style="white-space: nowrap;">Datum vertrek</td>
						<td>Boeking</td>
						<td>Reis</td>
					</tr>
					</thead>
					<?php if (!empty($bookings)) : ?>
						<tbody>
						<?php foreach ($bookings as $bookingData) :
							$primaryTermObject = new WPSEO_Primary_Term('destination', $bookingData['template']);
							$primaryDestinationTermId = $primaryTermObject->get_primary_term();
							if (!empty($primaryDestinationTermId)) {
								$primaryDestination = get_term_by('id', $primaryDestinationTermId, 'destination');
							}
							$bookingDate = get_field('booking_date', $bookingData['booking']->ID);
							$bookingAssumaxId = get_post_meta($bookingData['booking']->ID, '_assumax_id', true);
							$tripStartDate = get_field('trip_start_date', $bookingData['trip']);
							?>
							<tr>
								<td><span class="mobileTitle">Reis</span><?= get_the_title($bookingData['template']); ?></td>
								<td><span class="mobileTitle">Bestemming</span><?= !empty($primaryDestination) ? $primaryDestination->name : ''; ?></td>
								<td style="white-space: nowrap;">
									<span class="mobileTitle">Boekingsdatum</span><?= date_i18n('d-m-Y', strtotime($bookingDate)); ?>
								</td>
								<td style="white-space: nowrap;">
									<span class="mobileTitle">Datum vertrek</span><?= date_i18n('d-m-Y', strtotime($tripStartDate)); ?>
								</td>
                                <td class="td-btn"><a href="<?= site_url('/boeking/' . $bookingAssumaxId); ?>">Bekijk</a></td>
								<td class="td-btn">
									<?php
										$today = Helpers::today();
										// first check if the $tripStartDate is in the future
										if($today->format('Y-m-d') <= date('Y-m-d', strtotime($tripStartDate))) :
											// get trip by booking meta _assumax_trip_id
											$trip_post_id = get_post_meta($bookingData['booking']->ID, 'booking_trip', true);
											// get template by trip meta trip_template = post_id for template
											$template_post_id = get_post_meta($trip_post_id, 'trip_template', true);
											$template_post = get_post($template_post_id);
											// if template check if tripStartDate is in the past or not
											// if the tripstartdate is in the future, display the "Bekijk" button
											if(!empty($template_post)) : ?>
												<a href="<?php echo get_permalink($template_post); ?>" target="_blank" title="<?php echo esc_html(get_the_title($template_post)); ?>">
													Bekijk
												</a>
											<?php endif; // !empty($template_post);
										endif; // $today <= $tripStartdate;
									?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					<?php endif; ?>
				</table>
				<?php if ($additionalIdentificationRequired): ?>
					<div class="alert alert-warning" style="margin-top: 10px; text-align: center;">
						<strong>Let op!</strong> Legitimatie gegevens zijn verplicht.
						<a href="<?= site_url( 'mijn-account/gegevens-wijzigen' ); ?>" style="color: #8a6d3b;">Gegevens aanvullen ></a>
					</div>
				<?php endif; ?>
				<?php if (empty($bookings)): ?>
					<p class="account-table-rows__not-found">
						Je hebt nog geen boekingen<br /><br />
						<a href="<?= site_url( 'bestemmingen' ); ?>" class="btn btn--blue btn--padding">Bekijk alle reizen</a>
					</p>
				<?php endif; ?>
			</div>


			<div class="account-table-rows__row">
				<h2>Opties</h2>
				<table class="nosun-table">
					<thead>
					<tr>
						<td class="trip-max-width">Reis</td>
						<td>Bestemming</td>
						<td style="white-space: nowrap;">Boekingsdatum</td>
						<td style="white-space: nowrap;">Datum vertrek</td>
						<td>Status</td>
						<td></td>
					</tr>
					</thead>
					<?php 					if (!empty($options)): ?>
						<tbody>
						<?php foreach ($options as $bookingData):
                            $primaryTermObject = new WPSEO_Primary_Term('destination', $bookingData['template']);
                            $primaryDestinationTermId = $primaryTermObject->get_primary_term();
                            if (!empty($primaryDestinationTermId)) {
                                $primaryDestination = get_term_by('id', $primaryDestinationTermId, 'destination');
                            }
                            $bookingDate = get_field('booking_date', $bookingData['booking']->ID);
                            $bookingAssumaxId = get_post_meta($bookingData['booking']->ID, '_assumax_id', true);
                            $tripStartDate = get_field('trip_start_date', $bookingData['trip']);
                            try {
                                $now = new DateTime();
                                $bookingDateTime = new DateTime($bookingDate);
                                $bookingDateTime->add(new DateInterval('P7D'));
                                $difference = $now->diff($bookingDateTime);
                                $daysLeft = $difference->invert ? 0 : $difference->days;
                            } catch (Exception $e) {
                                $daysLeft = 0;
                            } ?>
							<tr>
								<td><span class="mobileTitle">Reis</span><?= get_the_title($bookingData['template']); ?></td>
								<td><span class="mobileTitle">Bestemming</span><?= !empty($primaryDestination) ? $primaryDestination->name : ''; ?></td>
								<td style="white-space: nowrap;">
									<span class="mobileTitle">Boekingsdatum</span><?= date_i18n('d-m-Y', strtotime($bookingDate)); ?>
								</td>
								<td style="white-space: nowrap;">
									<span class="mobileTitle">Datum vertrek</span><?= date_i18n('d-m-Y', strtotime($tripStartDate)); ?>
								</td>
                                <td><span class="mobileTitle">Status</span><?php if ($daysLeft > 0) : ?>Nog <?= $daysLeft === 1 ? "1 dag" : "{$daysLeft} dagen"; else: ?>Verlopen<?php endif; ?></td>
								<td class="td-btn"><a href="<?= site_url('/boeking/' . $bookingAssumaxId); ?>">Bekijk</a></td>
							</tr>
							<?php 						endforeach; ?>
						</tbody>
					<?php endif; ?>
				</table>
				<?php if (empty($options)): ?>
					<p class="account-table-rows__not-found">
						Je hebt nog geen opties<br /><br />
						<a href="<?= site_url( 'bestemmingen' ); ?>" class="btn btn--blue btn--padding">Bekijk alle reizen</a>
					</p>
				<?php endif; ?>
			</div>
            <div class="account-table-rows__row">
                <h2>Reisgroepen</h2>
                <table class="nosun-table">
                    <thead>
                    <tr>
                        <td>Reis</td>
                        <td></td>
                    </tr>
                    </thead>
                    <?php if (!empty($travelGroups)): ?>
                        <tbody>
                        <?php foreach ($travelGroups as $travelGroup) : ?>
                            <tr>
                                <td><span class="mobileTitle">Reis</span><?= get_the_title($travelGroup); ?></td>
                                <td style="width: 100px;"><a href="<?= get_the_permalink($travelGroup); ?>">Bekijk</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    <?php endif; ?>
                </table>
                <?php if (empty($travelGroups)): ?>
                    <p class="account-table-rows__not-found">
                        Je hebt nog geen reisgroepen<br/><br/>
                        <a href="<?= site_url('bestemmingen'); ?>" class="btn btn--blue btn--padding">Bekijk alle
                            reizen</a>
                    </p>
                <?php endif; ?>
            </div>
		</div>
	</div>
</section>





