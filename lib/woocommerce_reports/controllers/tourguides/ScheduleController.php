<?php

namespace lib\woocommerce_reports\controllers\tourguides;

use lib\woocommerce_reports\IReportController;
use lib\woocommerce_reports\models\TripReport;

/**
 * Holds all functionality used by the Schedule section of the Tourguides report tab.
 *
 * Class ScheduleController
 * @package lib\woocommerce_reports\controllers\revenue
 * @author Chris van Zanten <chris@vazquez.nl>
 */
class ScheduleController implements IReportController {

    /**
     * Renders the content of the implementing controller.
     * @return void
     */
    public static function render() {
        global $errors;
        $errors = [];
        // Check if there is any post data.
        global $tripDateBegin;
        global $tripDateEnd;
        global $tourGuideName;
        $tourGuideName = '';
        if (!empty($_POST)) {
            // Check if the nonce is valid.
            check_admin_referer('tourguides-schedule', 'report-nonce');
            // Get the fields.
            $tripDateBegin = isset($_POST['trip-date-filter-begin']) ? $_POST['trip-date-filter-begin'] : null;
            $tripDateEnd = isset($_POST['trip-date-filter-end']) ? $_POST['trip-date-filter-end'] : null;
            $tourGuideName = isset($_POST['tourguide-name-filter']) ? $_POST['tourguide-name-filter'] : null;
            if (!empty($tripDateBegin) && !empty($tripDateEnd)) {
                // Load the data using the trip dates.
                $results = TripReport::get_by_trip_date($tripDateBegin, $tripDateEnd);
                if (!isset($results)) $errors = ["Een van de reis datum velden bevat foutieve informatie."];
            } else {
                $results = TripReport::get_by_trip_date("-6 months", "NOW");
            }
        } else {
            $results = TripReport::get_by_trip_date("-6 months", "NOW");
        }
        // Parse the results and assign each trip to their respective tourguides.
        $guides = [];
        if (!empty($results)) {
            foreach ($results as $result) {
                if ($result->NosunStatus === 'Cancelled') continue;
                if (!empty($result->Guides)) {
                    $guidesJson = json_decode($result->Guides);
                    foreach ($guidesJson as $guide) {
                        // Check if the provided name is somewhere in the guide name.
                        if ($tourGuideName !== '' && stripos($guide->Name, $tourGuideName) === false) continue;
                        if (!key_exists($guide->Name, $guides)) $guides[$guide->Name] = [];
                        $guides[$guide->Name][] = $result;
                    }
                }
            }
        }
        // Sort the trips for every guide by the trip start date.
        if (!empty($guides)) {
            foreach ($guides as $guide => $trips) {
                usort($trips, function($a, $b) {
                    $startDateA = strtotime($a->StartDate);
                    $startDateB = strtotime($b->StartDate);
                    if ($startDateA === $startDateB) return 0;
                    return $startDateA < $startDateB ? -1 : 1;
                });
            }
        }

        // Include the template part. Variables declared above can be used inside the template.
        include(locate_template('templates/woocommerce_reports/tourguides-schedule.php', false, false));
    }
}