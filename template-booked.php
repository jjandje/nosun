<?php
/*
Template Name: Geboekt
*/

use lib\controllers\User;
use Roots\Sage\Assets;
use Vazquez\NosunAssumaxConnector\Api\Bookings;

$boekingGegevensWijzigenPage = get_page_by_path('boeking-gegevens-wijzigen');
if (empty($boekingGegevensWijzigenPage)) {
    $redirectURL = site_url();
} else {
    $redirectURL = add_query_arg('boeking', $assumaxId, get_the_permalink($boekingGegevensWijzigenPage));
}
global $assumaxId;
$bookingCount = User::count_user_bookings(get_current_user_id());
$bookingPost = Bookings::get_by_assumax_id($assumaxId);
$booking = wc_get_order($bookingPost);
$isOption = false;
if (!empty($booking)) :
    $bookingTotal = $booking->get_total();
    $isOption = get_field('booking_is_option', $bookingPost->ID);
    if ($bookingCount <= 1) :
        $tracked = get_post_meta($bookingPost->ID, '_booking_is_tracked', true);
        $hasReferral = get_post_meta($bookingPost->ID, '_booking_has_referral', true);
        if (!empty($hasReferral) && empty($tracked)):
            $tripPostId = get_field('booking_trip', $bookingPost->ID);
            $tripTitle = get_the_title($tripPostId);
        ?>
            <script type="text/javascript">
                travelAffiliate = 14;
                travelPrice = <?= $bookingTotal; ?>;
                travelDescription = '<?= $assumaxId; ?> - <?= $tripTitle; ?>';
                (function () {
                    var ga = document.createElement('script');
                    ga.type = 'text/javascript';
                    ga.async = true;
                    if (document.location.protocol === 'https:') {
                        ga.src = 'https://www.toureasy.nl/js/tracking.js';
                    } else {
                        ga.src = 'http://track.toureasy.nl/js/tracking.js';
                    }
                    var s = document.getElementsByTagName('script')[0];
                    s.parentNode.insertBefore(ga, s);
                })();
            </script>
        <?php endif;
        update_post_meta($bookingPost->ID, '_booking_is_tracked', true); ?>
    <?php endif; ?>
    <script type="text/javascript">
        jQuery( document ).ready(function() {
            waitForFbq(function () {
                fbq('track', 'Purchase', {currency: 'EUR', value: '<?= $bookingTotal; ?>'});
            });
        });
    </script>
<?php endif; ?>
<div class="booked-redirect">
    <div class="row header-title">
        <img class="logo" src="<?= Assets\asset_path('/images/logo.svg'); ?>" alt="nosun logo">
        <h1>Bedankt voor je <?= $isOption ? 'Optie' : 'Boeking'; ?></h1>
        <p>Je wordt binnen enkele ogenblikken doorverwezen</p>
        <p><img src="<?= Assets\asset_path('/images/loading.gif'); ?>" class="loader"
                alt="Enkele ogenblikken geduld..."/></p>
    </div>
</div>
<script type="text/javascript">
    window.setTimeout(function () {
        window.location.href = "<?= $redirectURL; ?>";
    }, 3000);
</script>
