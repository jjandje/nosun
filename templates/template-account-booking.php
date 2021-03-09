<?php
/**
 * Template Name: Bekijk boeking
 */

use lib\controllers\Booking;
use lib\controllers\User;
use Roots\Sage\Assets;
use Roots\Sage\Helpers;
use Vazquez\NosunAssumaxConnector\Api\Bookings;
use Vazquez\NosunAssumaxConnector\Api\Customers;

$hasAccess = false;
$assumaxId = get_query_var('page');
if (!empty($assumaxId)) {
    $bookingPost = Bookings::get_by_assumax_id($assumaxId);
    $booking = wc_get_order($bookingPost);
    if (!empty($booking)) {
        $userId = get_current_user_id();
        $profileImage = get_field('profile_image', 'user_' . $userId);
        $customer = Customers::get_by_user_id($userId);
        $customerAssumaxId = !empty($customer) ? get_post_meta($customer->ID, '_assumax_id', true) : -1;
        $bookingCustomers = get_field('booking_customers', $bookingPost->ID);
        $allowChanges = false;
        $primaryCustomer = null;
        foreach ($bookingCustomers as $bookingCustomer) {
            if ($bookingCustomer['id'] === $customerAssumaxId) {
                $hasAccess = true;
                if ($bookingCustomer['primary']) $allowChanges = true;
            }
            if ($bookingCustomer['primary']) $primaryCustomer = $bookingCustomer;
        }
        if ($hasAccess || current_user_can('administrator')) {
            // Get the rest of the booking data.
            $paymentStatus = get_field('booking_payment_status', $bookingPost->ID);
            $status = get_field('booking_status', $bookingPost->ID);
            if ($status === 'Cancelled' || $status === 'InProgress') $allowChanges = false;
            $bookingDate = get_field('booking_date', $bookingPost->ID);
            $isOption = get_field('booking_is_option', $bookingPost->ID);
            $payments = get_field('booking_payments', $bookingPost->ID);
            $invoices = get_field('booking_invoices', $bookingPost->ID);
            $boardingPoint = get_field('booking_boarding_point', $bookingPost->ID);
            $tripId = get_field('booking_trip', $bookingPost->ID);
            if (!empty($tripId)) {
                $templateId = get_field('trip_template', $tripId);
                if (!empty($templateId)) {
                    $primaryTermObject = new WPSEO_Primary_Term('destination', $templateId);
                    $primaryDestinationTermId = $primaryTermObject->get_primary_term();
                    if (!empty($primaryDestinationTermId)) {
                        $primaryDestination = get_term_by('id', $primaryDestinationTermId, 'destination');
                    }
                    $templateWebshopProducts = get_field('template_webshop_products', $templateId);
                    if (!empty($templateWebshopProducts)) {
                        $webshopAssumaxIds = array_column($templateWebshopProducts, 'assumax_id');
                        $postArgs = [
                            'post_type' => 'extraproduct',
                            'numberposts' => -1,
                            'meta_query' => [
                                [
                                    'key' => '_extra_product_assumax_id',
                                    'value' => $webshopAssumaxIds,
                                    'compare' => 'IN'
                                ]
                            ]
                        ];
                        $extraProducts = get_posts($postArgs);
                    }
                }
                $tripStartDate = get_field('trip_start_date', $tripId);
                $tripEndDate = get_field('trip_end_date', $tripId);
                $tripNumDays = get_field('trip_num_days', $tripId);
                $insuranceOptions = Booking::get_booking_insurance_options($bookingPost->ID, $assumaxId, $tripId);
            }
            $timeZoneString = get_option('timezone_string');
            if (empty($timeZoneString)) $timeZoneString = 'Europe/Amsterdam';
            try {
                $timeZone = new DateTimeZone($timeZoneString);
                $now = new DateTime('now', $timeZone);
                if (empty($tripStartDate)) {
                    $allowChanges = false;
                } else {
                    $tripStartDateTime = new DateTime($tripStartDate, $timeZone);
                    $tripStartDateTime->sub(new DateInterval('P1D'));
                    if ($now >= $tripStartDateTime) $allowChanges = false;
                }
                $bookingDateTime = new DateTime($bookingDate, $timeZone);
                $bookingDateTime->add(new DateInterval('P7D'));
                $difference = $now->diff($bookingDateTime);
                $optionDaysLeft = $difference->invert ? 0 : $difference->days;
            } catch (Exception $e) {
                $optionDaysLeft = 0;
            }
            if ($isOption && $optionDaysLeft === 0) $allowChanges = false;
            // General files
            $generalFiles = get_field('option_general_files', 'option');
            $documents = get_field('booking_documents', $bookingPost->ID);
            // Show the header.
            set_query_var('booking_post_id', $bookingPost->ID);
            get_template_part('templates/page-header-regular');
        }
    }
}
if (empty($booking) || (!$hasAccess && !current_user_can('administrator'))): ?>
    <section class="nosun-account tour-information row-padding">
        <div class="container">
            <div class="message">
                <div class="inner" style="color: #a94442; background-color: #f2dede; border-color: #ebccd1;">
                    <i class="fa fa-times"></i> <span>Je hebt geen toegang tot deze boeking.</span>
                </div>
            </div>
        </div>
    </section>
<?php else: ?>
    <section class="nosun-account tour-information row-padding">
        <div class="container">
            <?php wc_print_notices(); ?>
            <div class="tour-information__column">

                <div class="tour-information__row">
                    <h2><i class="fas fa-user"></i> Persoonsgegevens</h2>
                    <div class="tour-information__profile" style="display: flex;">
                        <div>
                            <?php if ($profileImage): ?>
                                <?= wp_get_attachment_image($profileImage, 'thumbnail'); ?>
                            <?php else: ?>
                                <img src="<?= Assets\asset_path( 'images/no-profile-image.png' ); ?>" style="max-width: 96px;" alt="Geen profiel afbeelding"/>
                            <?php endif; ?>
                        </div>
                        <ul>
                            <li>
                                <span style="overflow-wrap: break-word;"><?= User::get_customer_full_name($customer->ID, $userId); ?></span>
                            </li>
                            <li>
                                <a href="<?php echo get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ); ?>">Terug naar account</a>
                            </li>
                            <li><a href="<?= wc_customer_edit_account_url(); ?>">Profiel bewerken
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                            </li>
                        </ul>
                        <div class="clearfix"></div>
                    </div>
                </div>

                <div class="tour-information__row">
                    <h2><i class="fas fa-plane"></i> Reisinformatie</h2>
                    <table class="tour-information__tour">
                        <tbody>
                        <tr>
                            <td>
                                Op naam van:
                            </td>
                            <td>
                                <strong><?= isset($primaryCustomer) ? $primaryCustomer['full_name'] : '-'; ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Boekingsdatum:
                            </td>
                            <td>
                                <strong><?= isset($bookingDate) ? date_i18n("d-m-Y", strtotime($bookingDate)) : '-'; ?><strong>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Reisdatum:
                            </td>
                            <td>
                                <strong><?= isset($tripStartDate) ? date_i18n("d-m-Y", strtotime($tripStartDate)) : '-'; ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Terugkomstdatum:
                            </td>
                            <td>
                                <strong><?= isset($tripEndDate) ? date_i18n("d-m-Y", strtotime($tripEndDate)) : '-'; ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Type:
                            </td>
                            <td><strong class="cap"><?= $isOption ? "Optie" : "Boeking"; ?></strong></td>
                        </tr>
                        <?php if ($isOption): ?>
                            <tr>
                                <td>
                                    Optie verloopt:
                                </td>
                                <td>
                                    <strong><?php if ($optionDaysLeft > 0) : ?>Over <?= $optionDaysLeft; ?> dag(en)<?php else : ?>Verlopen<?php endif; ?></strong>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td>
                                Aantal reisdagen:
                            </td>
                            <td><strong><?= isset($tripNumDays) ? $tripNumDays : '-'; ?> dagen</strong></td>
                        </tr>
                        <tr>
                            <td>
                                Bestemming:
                            </td>
                            <td><strong><?= isset($primaryDestination) ? $primaryDestination->name : '-'; ?></strong></td>
                        </tr>
                        <tr>
                            <td>
                                Opstapplaats:
                            </td>
                            <td><strong><?= isset($boardingPoint) ? $boardingPoint : '-'; ?></strong></td>
                        </tr>
                        <?php                         if (count($bookingCustomers) > 1):
                            ?>
                            <tr>
                                <td>
                                    Extra reizigers:
                                </td>
                                <td>
                                    <ul style="padding: 0; margin: 0;">
                                        <?php foreach ($bookingCustomers as $bookingCustomer):
                                            if ($bookingCustomer['id'] === $customerAssumaxId) continue; ?>
                                            <li>
                                                <strong><?= $bookingCustomer['full_name']; ?></strong>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
                <div class="tour-information__row">
                    <h2><i class="fas fa-euro-sign"></i> Betalingen</h2>
                    <?php
                    if (!empty($payments)):
                        setlocale( LC_ALL, "nl_NL" );
                        ?>
                        <table class="tour-information__payments">
                            <thead>
                            <tr>
                                <td>Bedrag</td>
                                <td>Omschrijving</td>
                                <td>Datum</td>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>&euro; <?= isset($payment['amount']) ? number_format_i18n($payment['amount'], 2) : '-'; ?></td>
                                    <td><?= isset($payment['description']) ? $payment['description'] : '-' ?></td>
                                    <td><?= isset($payment['date_time']) ? strftime("%e %B %Y", strtotime($payment['date_time'])) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        Je hebt nog geen betalingen gedaan.
                    <?php endif; ?>
                    <?php if ($isOption && $optionDaysLeft > 0): ?>
                        <br /><br />
                        <strong><i class="fa fa-info-circle"></i> Optie definitief maken? Een aanbetaling is voldoende.</strong>
                    <?php endif; ?>
                </div>

                <div class="tour-information__row">
                    <h2><i class="fas fa-file"></i> Bestanden</h2>
                    <table class="tour-information__files">
                        <?php
                        $numFiles = 0;
                        if (!empty($generalFiles)):
                            foreach ($generalFiles as $generalFile): ?>
                                <tr>
                                    <td style="width: 40px; text-align: center">
                                        <i class="far fa-file-pdf"></i>
                                    </td>
                                    <td><?= $generalFile['option_general_files_name']; ?></td>
                                    <td style="width: 110px;">
                                        <a href="<?= $generalFile['option_general_files_file']; ?>" target="_blank">Download</a>
                                    </td>
                                </tr>
                                <?php $numFiles++; ?>
                            <?php endforeach;
                        endif; ?>
                        <?php if ($numFiles === 0): ?>
                            <tr>
                                <td>Er zijn nog geen bestanden toegevoegd.</td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($invoices)):
                            foreach ($invoices as $invoice):
                                if (empty($invoice['status']) || $invoice['status'] === 'Draft') continue; ?>
                                <tr>
                                    <td style="width: 40px; text-align: center">
                                        <i class="far fa-file-pdf"></i>
                                    </td>
                                    <td>Factuur <?= $invoice['id']; ?> - <?= $invoice['modified']; ?></td>
                                    <td style="width: 110px;">
                                        <?php $downloadUrl = site_url("/?boeking={$assumaxId}&factuur={$invoice['id']}"); ?>
                                        <a href="<?= $downloadUrl; ?>" target="_blank">Download</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($documents)):
                            foreach ($documents as $document): ?>
                                <tr>
                                    <td style="width: 40px; text-align: center">
                                        <i class="far fa-file-pdf"></i>
                                    </td>
                                    <td><?= empty($document['title']) ? $document['file_name'] : $document['title']; ?></td>
                                    <td style="width: 110px;">
                                        <?php $downloadUrl = site_url("/?boeking={$assumaxId}&document={$document['id']}"); ?>
                                        <a href="<?= $downloadUrl; ?>" target="_blank">Download</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="tour-information__column information-column">
                <div class="inner">
                    <div class="tour-information__row">
                        <h2 class="information-title"><i class="fas fa-list-ul"></i> Boeking overzicht</h2>
                        <table>
                            <thead>
                            <tr>
                                <td>Item</td>
                                <td style="text-align: right;">Aantal</td>
                                <td style="width: 80px; text-align: right;">Prijs</td>
                            </tr>
                            </thead>
                            <tbody>
                            <?php                             $invoiceTotalAmount = 0;
                            if (!empty($invoices)):
                                foreach ($invoices as $invoice):
                                    foreach ($invoice['lines'] as $invoiceLine): ?>
                                        <tr>
                                            <td><?= isset($invoiceLine['title']) ? $invoiceLine['title'] : '-'; ?></td>
                                            <td style="text-align: right;"><?= $invoiceLine['count']; ?></td>
                                            <td style="text-align: right;"><?= isset($invoiceLine['amount']) ? wc_price($invoiceLine['amount']) : '-'; ?></td>
                                        </tr>
                                        <?php $invoiceTotalAmount += floatval($invoiceLine['amount']) * $invoiceLine['count']; ?>
                                    <?php endforeach;
                                endforeach;
                            else: // TODO: Show some kind of message? ?>

                            <?php endif; ?>
                            <tr>
                                <td><strong>Totaalbedrag</strong></td>
                                <td colspan="2" style="text-align: right;"><strong><?= wc_price($invoiceTotalAmount); ?></strong></td>
                            </tr>
                            </tbody>
                        </table>
                        <br />
                    </div>
                    <br />

                    <?php if ($allowChanges): ?>
                        <?php if (!empty($insuranceOptions)): ?>
                            <div class="tour-information__row insurances">
                                <h2 class="information-title"><i class="fas fa-file-alt"></i> Verzekeringen</h2>
                                <table>
                                    <thead>
                                    <tr>
                                        <td>Item</td>
                                        <td style="width: 55px;">
                                            <div class="loader-message">
                                            </div>
                                        </td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $i = 0;
                                    foreach ($insuranceOptions as $insuranceType => $insuranceOption): ?>
                                        <tr>
                                            <td>
                                                <?= $insuranceOption['title']; ?> (&euro;<?= number_format_i18n($insuranceOption['price'],2); ?>)
                                                <a href="#modal_<?= $i; ?>"><i class="fa fa-info-circle" aria-hidden="true"></i></a>
                                                <div class="remodal" data-remodal-id="modal_<?= $i; ?>">
                                                    <button data-remodal-action="close" class="remodal-close"></button>
                                                    <h1><?= $insuranceOption['title']; ?></h1>
                                                    <p><?= $insuranceOption['description']; ?></p>
                                                </div>
                                            </td>
                                            <td style="width: 70px; text-align: right;">
                                                <i class="fa fa-refresh fa-spin fa-fw loading" style="display: none;"></i>
                                                <a href="#modal_add_<?= $i; ?>" class="add-insurace" data-booking-id="<?= $assumaxId; ?>" data-insurance-type="<?= $insuranceType; ?>">
                                                    <i class="fa fa-plus-circle" aria-hidden="true"></i>
                                                </a>
                                                <div class="remodal modal-add-insurance" data-remodal-id="modal_add_<?= $i; ?>">
                                                    <button data-remodal-action="close" class="remodal-close"></button>
                                                    <h1><?= $insuranceOption['title']; ?> afsluiten</h1>
                                                    <p>
                                                        <b><?= $insuranceOption['title']; ?> (&euro;<?= number_format_i18n($insuranceOption['price'],2); ?>)</b>
                                                    </p>
                                                    <form id="add_insurance_form_<?= $i; ?>" class="add-insurance-form" action="" method="post">

                                                        <p>
                                                            Om de gekozen verzekering(en) bij de Europeesche voor je te kunnen afsluiten, moeten we namens jou onderstaande vragen beantwoorden. S.v.p. aanvinken wat van toepassing is:
                                                        </p>

                                                        <div class="container">
                                                            <div class="question">
                                                                <p>
                                                                    1. Bent u, of is een van de personen die u wilt meeverzekeren, in de afgelopen 8 jaar verdacht van of veroordeeld voor een strafbaar feit?
                                                                </p>
                                                                <div class="answers">
                                                                    <input type="radio" name="question_1" id="answer_1_<?= $i; ?>_yes" value="1">
                                                                    <label for="answer_1_<?= $i; ?>_yes">Ja</label>

                                                                    <input type="radio" name="question_1" id="answer_1_<?= $i; ?>_no" value="0">
                                                                    <label for="answer_1_<?= $i; ?>_no">Nee</label>
                                                                </div>
                                                            </div>
                                                            <div class="clearfix"></div>
                                                            <div class="question">
                                                                <p>
                                                                    2. Bent u, of is een van de personen die u wilt meeverzekeren, in de afgelopen 8 jaar betrokken (geweest) bij verzekeringsfraude of opzettelijke misleiding van een financiële instelling?
                                                                </p>
                                                                <div class="answers">
                                                                    <input type="radio" name="question_2" id="answer_2_<?= $i; ?>_yes" value="1">
                                                                    <label for="answer_2_<?= $i; ?>_yes">Ja</label>

                                                                    <input type="radio" name="question_2" id="answer_2_<?= $i; ?>_no" value="0">
                                                                    <label for="answer_2_<?= $i; ?>_no">Nee</label>
                                                                </div>
                                                            </div>

                                                            <div class="alert alert-warning need-extra-info" style="display: none;">
                                                                Voor het afsluiten van de gekozen verzekering(en) is aanvullende informatie van je nodig. We nemen hiervoor z.s.m. contact met je op.
                                                            </div>
                                                        </div>
                                                        <br>
                                                        <button class="btn cancel-btn" data-remodal-action="cancel">Annuleren</button>
                                                        <button type="submit" class="btn btn--pink submit-btn" data-booking-id="<?= $assumaxId; ?>" data-insurance-type="<?= $insuranceType; ?>">Akkoord</button>
                                                        <i class="fa fa-refresh fa-spin fa-fw loading" style="display: none;"></i>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php $i++; ?>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <br />
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($extraProducts)): ?>
                            <div class="tour-information__row">
                                <h2><i class="fas fa-shopping-bag"></i> Extra producten</h2>
                                <table>
                                    <thead>
                                    <tr>
                                        <td>Item</td>
                                        <td style="width: 55px;"></td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $i = 0; ?>
                                    <?php foreach ($extraProducts as $extraProduct): ?>
                                        <tr>
                                            <td>
                                                <?= get_the_title($extraProduct->ID);?> (&euro;<?= number_format_i18n(get_field('_extra_product_price', $extraProduct->ID), 2 ); ?>)
                                                <a href="#modal_extra_product_<?= $i; ?>"><i class="fa fa-info-circle" aria-hidden="true"></i></a>
                                                <div class="remodal" data-remodal-id="modal_extra_product_<?= $i; ?>">
                                                    <button data-remodal-action="close" class="remodal-close"></button>
                                                    <h1><?= get_the_title($extraProduct->ID); ?></h1>
                                                    <p><?= get_field('_extra_product_description', $extraProduct->ID); ?></p>
                                                </div>
                                            </td>
                                            <td style="width: 70px; text-align: right;">
                                                <i class="fa fa-refresh fa-spin fa-fw loading" style="display: none;"></i>
                                                <a href="#modal_add_extra_product_<?= $i; ?>" class="add-extra-product" data-booking-id="<?= $assumaxId; ?>" data-product-id="<?= get_field('_extra_product_assumax_id', $extraProduct->ID); ?>">
                                                    <i class="fa fa-plus-circle" aria-hidden="true"></i>
                                                </a>
                                                <div class="remodal modal-add-extra-product" data-remodal-id="modal_add_extra_product_<?= $i; ?>">
                                                    <button data-remodal-action="close" class="remodal-close"></button>
                                                    <h1><?= get_the_title($extraProduct->ID);?> toevoegen</h1>
                                                    <p>
                                                        <b><?= get_the_title($extraProduct->ID);?> (&euro;<?= number_format_i18n(get_field('_extra_product_price', $extraProduct->ID), 2 ); ?>)</b>
                                                    </p>
                                                    <form id="add_extra_product_form_<?= $i; ?>" class="add-extra-product-form" action="" method="post">
                                                        <p><?= get_field('_extra_product_description', $extraProduct->ID); ?></p>
                                                        <button class="btn btn--pink cancel-btn" data-remodal-action="cancel">Annuleren</button>
                                                        <button type="submit" class="btn btn--pink submit-btn" data-booking-id="<?= $assumaxId; ?>" data-product-id="<?= get_field('_extra_product_assumax_id', $extraProduct->ID); ?>">Toevoegen</button>
                                                        <i class="fa fa-refresh fa-spin fa-fw loading" style="display: none;"></i>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php $i++; ?>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="tour-information__row" style="border-bottom: none;">
                        <h2><i class="fas fa-money-bill-alt"></i> Betaling</h2>
                        <?php
                        //$today = Helpers::today();
                        //$paymentDeadlineDateTime = Helpers::create_local_datetime(get_field('booking_payment_deadline', $bookingPost->ID));
                            if ($paymentStatus !== 'Paid'):
                                // if ($today < $paymentDeadlineDateTime):
                                // echo "De betaaltermijn is verstreken.";
                                // endif;
                                set_query_var('booking_post_id', $bookingPost->ID);
                                echo do_shortcode('[gravityform id="4" name="Restbetaling formulier"]');
                            else:
                                echo "We hebben het totaalbedrag succesvol ontvangen.";
                        endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>
