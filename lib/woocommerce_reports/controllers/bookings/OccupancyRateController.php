<?php

namespace lib\woocommerce_reports\controllers\bookings;

use lib\woocommerce_reports\IReportController;
use lib\woocommerce_reports\models\TripReport;

/**
 * Holds all functionality used by the Occupancy Rate section of the Bookings report tab.
 *
 * Class OccupancyRateController
 * @package lib\woocommerce_reports\controllers\bookings
 * @author Chris van Zanten <chris@vazquez.nl>
 */
class OccupancyRateController implements IReportController {

    /**
     * Renders the content of the implementing controller.
     * @return void
     */
    public static function render() {
        global $errors;
        $errors = [];
        global $tripDateBegin;
        global $tripDateEnd;
        global $nosunTripId;
        // Check if there is any post data.
        if (!empty($_POST)) {
            // Check if the nonce is valid.
            check_admin_referer('bookings-occupancy-rate', 'report-nonce');
            // Get the fields.
            $tripDateBegin = isset($_POST['trip-date-filter-begin']) ? $_POST['trip-date-filter-begin'] : null;
            $tripDateEnd = isset($_POST['trip-date-filter-end']) ? $_POST['trip-date-filter-end'] : null;
            $nosunTripId = isset($_POST['trip-id-filter']) ? $_POST['trip-id-filter'] : null;
            if (!empty($tripDateBegin) && !empty($tripDateEnd)) {
                // Load the data using the trip dates.
                $results = TripReport::get_by_trip_date($tripDateBegin, $tripDateEnd);
                if (!isset($results)) $errors = ["Een van de reis datum velden bevat foutieve informatie."];
            } else if (!empty($nosunTripId)) {
                // Load the data for the specific trip.
                if (!is_numeric($nosunTripId)) $errors = ["De opgegeven waarde is geen geldige reis."];
                else {
                    $results = TripReport::get_similar_by_nosun_id($nosunTripId);
                    if (!isset($results)) $errors = ["Er is geen reis data beschikbaar voor deze id."];
                }
            } else {
                $results = TripReport::get_by_trip_date("-6 months", "+6 months");
                if (!isset($results)) $errors = ["Er is iets fout gegaan tijdens het ophalen van de huidige reizen."];
            }
        } else {
            $results = TripReport::get_by_trip_date("-6 months", "+6 months");
            if (!isset($results)) $errors = ["Er is iets fout gegaan tijdens het ophalen van de huidige reizen."];
        }
        // Parse the results if there are no errors.
        $maxOccupancy = 0;
        $currentOccupancy = 0;
        if (empty($errors) && !empty($results)) {
            usort($results, function(TripReport $a, TripReport $b) {
                if ($a->TemplateId == $b->TemplateId) {
                    $startTimeA = strtotime($a->StartDate);
                    $startTimeB = strtotime($b->StartDate);
                    if ($startTimeA === $startTimeB) return 0;
                    else {
                        return ($startTimeA < $startTimeB) ? -1 : 1;
                    }
                }
                return ($a->TemplateId < $b->TemplateId) ? -1 : 1;
            });
            foreach ($results as $result) {
                $currentOccupancy += $result->NEntries;
                $maxOccupancy += $result->NCustomers;
            }
        }
        $occupancyPercentage = ($currentOccupancy > 0) ? ($currentOccupancy / $maxOccupancy * 100) : 0;
        // Include the template part. Variables declared above can be used inside the template.
        include(locate_template('templates/woocommerce_reports/bookings-occupancy-rate.php', false, false));
    }
}