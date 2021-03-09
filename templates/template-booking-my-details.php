<?php
/**
 * Template Name: Boeking - Mijn gegevens
 */
use lib\controllers\User;
use lib\controllers\Booking;
use Vazquez\NosunAssumaxConnector\Api\Bookings;

$hasAccess = true;
$detailsData = Booking::get_booking_details_data();
if (!empty($detailsData)) {
    list($encryptedBookingId, $customers, $boardingPoints, $selectedBoardingPoint) = $detailsData;
} else {
    $hasAccess = false;
}
if ($hasAccess):
    do_action('woocommerce_before_edit_account_form'); ?>

<?php
$bookingId = $_GET['boeking'];
$bookingCount = User::count_user_bookings(get_current_user_id());
$bookingPost = Bookings::get_by_assumax_id($bookingId);
$booking = wc_get_order($bookingPost);
$isOption = false;

	if(!empty($_GET['boeking'])):
		$bookingId = $_GET['boeking'];
		$data = Booking::get_gtm_booked_data($bookingId);

		$i=0;
		foreach ($customers as $customer):
			$customerName = $customer->FirstName;
			$customerLastName = $customer->LastName;
			if($i == 0){
				continue;
			}
		endforeach;
	endif;
	if (!empty($data)): ?>

		   <script type="text/javascript">
                travelAffiliate = 14;
                travelPrice = <?= number_format($data['transaction_total'], 2, '.', ''); ?>;
                travelDescription = '<?= $bookingId; ?> - <?= $data['product_name']; ?> ~ <?= $customerName; ?> <?= $customerLastName; ?>';
                (function() {
                    var tTS = document.createElement('script');
                    tTS.type = 'text/javascript';
                    tTS.async = true;
                    if ( document.location.protocol == 'https:' ) {
                        tTS.src = 'https://www.toureasy.nl/js/tracking.js';
                    } else {
                        tTS.src = 'http://track.toureasy.nl/js/tracking.js';
                    }
                    var s = document.getElementsByTagName('script')[0]; 
                    s.parentNode.insertBefore(tTS, s);
                })();
            </script>

		<script>
            jQuery( window ).load(function() {
            window.dataLayer = window.dataLayer || [];
            dataLayer.push({
            'event': 'transaction',
              'ecommerce': {
                'purchase': {
                  'actionField': {
                    'id': '<?= $bookingId; ?>',                         // Transaction ID. Required for purchases and refunds.
                    'revenue': '<?= number_format($data['transaction_total'], 2, '.', ''); ?>',  
                    'shipping': '<?= $data['extra_charges']; ?>',                   // Total transaction value (incl. tax and shipping)
                  },
                  'products': [{                            // List of productFieldObjects.
                    'name': '<?= $data['product_name']; ?>',     // Name or ID is required.
                    'id': '<?= $data['product_sku']; ?>',
                    'price': '<?= number_format($data['product_price'], 2, '.', ''); ?>',
                    'quantity': <?= $data['product_quantity']; ?>,
                    <?php if(!empty($data['product_category'])):?>'category': '<?= $data['product_category']; ?>',<?php endif;?>
                   }]
                }
              }
            });
                    });

		</script>

    <script type="text/javascript">
        jQuery( document ).ready(function() {
            waitForFbq(function () {
                fbq('track', 'Purchase', {currency: 'EUR', value: '<?= number_format($data['transaction_total'], 2, '.', ''); ?>'});
            });
        });
    </script>

	<?php endif;
	?>

    <div class="nosun-account row-padding">
        <div class="booking-wrapper">
            <div class="container">
                <ul class="booking-wrapper__header">
                    <li class="step-completed">
                        1. Kies jouw groepsreis
                    </li>
                    <li class="step-completed">
                        2. Vul je gegevens in
                    </li>
                    <li class="step-active">
                        3. Geboekt
                    </li>
                    <li>
                        4. Verzekeringen en verdere gegevens
                    </li>
                </ul>
            </div>
            <div class="booking-form step-3">
                <div class="container">
                    <div class="booking-form__login">
                        <h2>Controleer hieronder je gegevens en vul waar nodig aan.</h2>
                    </div>
                    <form action="<?= esc_url(admin_url('admin-post.php')); ?>" method="post" id="bookform" class="nosun-form">
                        <?= wp_nonce_field('update_booking_details'); ?>
                        <div class="accordion">
                            <?php
                            $i = 0;
                            /** @var stdClass $customer */
                            foreach ($customers as $customer): ?>
                                <div class="header">Reiziger <?= $i+1; ?> - <?= $customer->FirstName; ?> <?= $customer->LastName; ?> </div>
                                <div class="content">
                                    <div class="booking-form__column">
                                        <h3>Persoonsgegevens</h3>
                                        <ul>
                                            <li>
                                                <label for="first_name_<?= $i; ?>">Alle voornamen volgens paspoort</label>
                                                <input id="first_name_<?= $i; ?>" name="customer[<?= $i; ?>][first_name]" autocomplete="off" value="<?= $customer->FirstName;; ?>" class="input required" placeholder="Dit hebben we nodig voor o.a. ferry- en/of vliegtickets" />
                                            </li>
                                            <li>
                                                <label for="nickname_<?= $i; ?>">Roepnaam</label>
                                                <input id="nickname_<?= $i; ?>" name="customer[<?= $i; ?>][nickname]" class="input required" value="<?= $customer->NickName; ?>" placeholder="We spreken je graag aan met je roepnaam" />
                                            </li>
                                            <li>
                                                <label for="last_name_<?= $i; ?>">Achternaam</label>
                                                <input id="last_name_<?= $i; ?>" autocomplete="off" name="customer[<?= $i; ?>][last_name]" value="<?= $customer->LastName; ?>" class="input required" placeholder="" />
                                            </li>
                                            <li>
                                                <label for="birthdate_<?= $i; ?>">Geboortedatum</label>
                                                <input id="birthdate_<?= $i; ?>" name="customer[<?= $i; ?>][birthdate]" value="<?= date('d-m-Y', strtotime($customer->DateOfBirth)); ?>" class="input birthdate required" placeholder="dd-mm-jjjj" />
                                            </li>
                                            <li>
                                                <label for="phone_<?= $i; ?>">Telefoonnummer</label>
                                                <input id="phone_<?= $i; ?>" name="customer[<?= $i; ?>][phone]" value="<?= $customer->PhoneNumber; ?>" class="input phonenumber required" placeholder="Als wij dringende vragen hebben over (vlieg)tickets bellen wij liever." />
                                            </li>
                                            <li>
                                                <label>Geslacht</label>
                                                <div style="padding: 0;">
                                                    <input id="gender_0_<?= $i; ?>" name="customer[<?= $i; ?>][gender]" type="radio" value="0" <?= ( !$customer->Sex ? 'checked' : null ); ?> checked>
                                                    <label for="gender_0_<?= $i; ?>">Ik ben een man</label>
                                                </div>
                                                <div style="padding: 0;">
                                                    <input id="gender_1_<?= $i; ?>" name="customer[<?= $i; ?>][gender]" type="radio" value="1" <?= ( $customer->Sex ? 'checked' : null ); ?>>
                                                    <label for="gender_1_<?= $i; ?>">Ik ben een vrouw</label>
                                                </div>
                                                <div class="errorTxt"></div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="booking-form__column">
                                        <h3>Adresgegevens</h3>
                                        <ul>
                                            <li>
                                                <label for="billing_address_1_<?= $i; ?>">Straatnaam</label>
                                                <input id="billing_address_1_<?= $i; ?>" name="customer[<?= $i; ?>][billing_address_1]" value="<?= $customer->Street; ?>" class="input required" />
                                            </li>
                                            <li>
                                                <label for="billing_house_number_1_<?= $i; ?>">Huisnummer en toevoeging</label>
                                                <div class="clearfix"></div>
                                                <input id="billing_house_number_1_<?= $i; ?>" name="customer[<?= $i; ?>][billing_house_number]" value="<?= $customer->StreetNumber; ?>" class="input required" />
                                            </li>
                                            <li>
                                                <label for="billing_postcode_<?= $i; ?>">Postcode</label>
                                                <input id="billing_postcode_<?= $i; ?>" name="customer[<?= $i; ?>][billing_postcode]" value="<?= $customer->PostalCode; ?>" class="input postcode required" minlength="4" />
                                            </li>
                                            <li>
                                                <label for="billing_city_<?= $i; ?>">Plaats</label>
                                                <input id="billing_city_<?= $i; ?>" name="customer[<?= $i; ?>][billing_city]" value="<?= $customer->City; ?>" class="input required" />
                                            </li>
                                            <li style="display: none;">
                                                <label for="billing_country_<?= $i; ?>">Land</label>
                                                <input type="hidden" id="billing_country_<?= $i; ?>" name="customer[<?= $i; ?>][billing_country]" value="Nederland" class="input required" />
                                            </li>
                                            <li>
                                                <label for="nationality">Nationaliteit</label>
                                                <input id="nationality" name="customer[<?= $i; ?>][nationality]" value="<?= $customer->Nationality; ?>" class="input required" />
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="booking-form__column">
                                        <h3>Gegevens thuisblijver</h3>
                                        <ul>
                                            <li>
                                                <label for="emergencycontactname_<?= $i; ?>">Contactpersoon</label>
                                                <input id="emergencycontactname_<?= $i; ?>" name="customer[<?= $i; ?>][emergencycontactname]" value="<?= $customer->EmergencyContactName; ?>" class="input required" />
                                            </li>
                                            <li>
                                                <label for="emergencycontactphone_<?= $i; ?>">Telefoon thuisblijver</label>
                                                <input id="emergencycontactphone_<?= $i; ?>" name="customer[<?= $i; ?>][emergencycontactphone]" value="<?= $customer->EmergencyContactPhone; ?>" class="input phonenumber required" />
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="booking-form__column">
                                        <h3>Overige informatie</h3>
                                        <ul>
                                            <li>
                                                <label for="dietary_wishes_<?= $i; ?>">Voedselallergie en dieetwensen</label>
                                                <div class="clearfix"></div>
                                                <textarea name="customer[<?= $i; ?>][dietary_wishes]" id="dietary_wishes_<?= $i; ?>" cols="30" rows="4"><?= $customer->DietaryWishes; ?></textarea>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="booking-form__column">
                                        <h3>Opmerkingen</h3>
                                        <ul>
                                            <li>
                                                <label for="note_<?= $i; ?>">Wil je verder nog iets kwijt?</label>
                                                <div class="clearfix"></div>
                                                <textarea name="customer[<?= $i; ?>][note]" id="note_<?= $i; ?>" cols="30" rows="5"><?= $customer->Note; ?></textarea>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php if ($i < count($customers)-1): ?><div class="clearfix"></div><?php endif; ?>
                                </div>
                                <input type="hidden" id="user_id_<?= $i; ?>" name="customer[<?= $i; ?>][user_id]" value="<?= $customer->Id; ?>" />
                                <?php $i++;
                            endforeach; ?>
                            <div class="clearfix"></div>
                            <hr />
                            <div class="booking-form__column">
                                <h3>Opstapplaats</h3>
                                <p>Waar wil je opstappen?</p>
                                <ul>
                                    <li>
                                        <select name="boarding_place" required>
                                            <option value="">-- Selecteer een opstapplaats --</option>
                                            <?php
                                            foreach ($boardingPoints as $boardingPoint): ?>
                                                <option value="<?= $boardingPoint; ?>"<?= $boardingPoint === $selectedBoardingPoint ? 'selected': ''; ?>><?= $boardingPoint; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="clearfix"></div>
                        <div class="booking-form__submit">
                            <input type="hidden" name="booking_id" value="<?= $encryptedBookingId; ?>" />
                            <input type="hidden" name="action" value="update_booking_details">
                            <button name="submit_form" type="submit" class="btn btn--pink btn--arrow-white" value="update_booking_details">Gegevens opslaan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <section class="nosun-account tour-information row-padding">
        <div class="container">
            <div class="message">
                <div class="inner" style="color: #a94442; background-color: #f2dede; border-color: #ebccd1;">
                    <i class="fa fa-times"></i> <span>Je hebt geen toegang tot deze boeking.</span>
                </div>
            </div>
        </div>
    </section>
    <script>
        window.setTimeout(function () {
            window.location.href = "<?= site_url(); ?>";
        }, 3000);
    </script>
<?php endif; ?>
