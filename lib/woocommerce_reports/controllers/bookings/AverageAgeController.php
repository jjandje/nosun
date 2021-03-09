<?php

namespace lib\woocommerce_reports\controllers\bookings;

use lib\woocommerce_reports\IReportController;
use lib\woocommerce_reports\models\BookingReport;
use lib\woocommerce_reports\models\CustomerData;
use lib\woocommerce_reports\models\TripReport;

/**
 * Holds all the functionality used by the AverageAge section of the Bookings report tab.
 *
 * Class AverageAgeController
 * @package lib\woocommerce_reports\controllers\bookings
 * @author Chris van Zanten <chris@vazquez.nl>
 */
class AverageAgeController implements IReportController {

    /**
     * Renders the content of the implementing controller.
     * @return void
     */
    public static function render() {
        $results = [];
        // Define global variables for the filter options.
        global $errors;
        global $bookingDateBegin;
        global $bookingDateEnd;
        global $tripDateBegin;
        global $tripDateEnd;
        global $nosunTripId;
        // Check if there is any post data.
        if (!empty($_POST)) {
            // Check if the nonce is valid.
            check_admin_referer('bookings-average-age', 'report-nonce');
            // Get the fields.
            $bookingDateBegin = isset($_POST['booking-date-filter-begin']) ? $_POST['booking-date-filter-begin'] : null;
            $bookingDateEnd = isset($_POST['booking-date-filter-end']) ? $_POST['booking-date-filter-end'] : null;
            $tripDateBegin = isset($_POST['trip-date-filter-begin']) ? $_POST['trip-date-filter-begin'] : null;
            $tripDateEnd = isset($_POST['trip-date-filter-end']) ? $_POST['trip-date-filter-end'] : null;
            $nosunTripId = isset($_POST['trip-id-filter']) ? $_POST['trip-id-filter'] : null;
            if (!empty($bookingDateBegin) && !empty($bookingDateEnd)) {
                // Load the data using the booking dates.
                $results = BookingReport::get_by_booking_date($bookingDateBegin, $bookingDateEnd, false, true);
                if (!isset($results)) $errors = ["Een van de boeking datum velden bevat foutieve informatie."];
            } elseif (!empty($tripDateBegin) && !empty($tripDateEnd)) {
                // Load the data using the trip dates.
                $results = TripReport::get_by_trip_date($tripDateBegin, $tripDateEnd, true);
                if (!isset($results)) $errors = ["Een van de reis datum velden bevat foutieve informatie."];
            } else if (!empty($nosunTripId)) {
                // Load the data for the specific trip.
                if (!is_numeric($nosunTripId)) $errors = ["De opgegeven waarde is geen geldige reis."];
                else {
                    $results = TripReport::get_by_nosun_ids([$nosunTripId], false, true);
                    if (!isset($results)) $errors = ["Er is geen reis data beschikbaar voor deze id."];
                }
            } else {
                // Load the data using the default booking date range.
                $results = BookingReport::get_by_booking_date('-6 months', 'now', false, true);
            }
        } else {
            // Load the data using the default booking date range.
            $results = BookingReport::get_by_booking_date('-6 months', 'now', false, true);
        }
        // Set default values.
        $ages = [];
        $totalAge = 0;
        $ageLabels = '[]';
        $ageValues = '[]';
        $averageAge = 0;
        $numAges = 0;
        if (empty($errors) && !empty($results)) {
            foreach ($results as $result) {
                if (!empty($result->CustomerDatas)) {
                    foreach ($result->CustomerDatas as $customerData) {
                        /** @var CustomerData $customerData */
                        $age = date_diff(date_create($customerData->DateOfBirth), date_create('now'))->y;
                        if (!key_exists($age, $ages)) $ages[$age] = 1;
                        else $ages[$age]++;
                        $totalAge += $age;
                    }
                }
            }
            ksort($ages);
            // Set the chart labels and values.
            $ageLabels = json_encode(array_keys($ages));
            $ageValues = json_encode(array_values($ages));
            $numAges = array_sum($ages);
            $averageAge = $totalAge / $numAges;
        }

        // Include the template part. Variables declared above can be used inside the template.
        include(locate_template('templates/woocommerce_reports/bookings-average-age.php', false, false));
    }
}