<?php

namespace lib\woocommerce_reports\controllers\bookings;

use lib\woocommerce_reports\IReportController;
use lib\woocommerce_reports\models\BookingReport;

/**
 * Holds all functionality used by the Season Comparison section of the Bookings report tab.
 *
 * Class SeasonComparisonController
 * @package lib\woocommerce_reports\controllers\bookings
 * @author Chris van Zanten <chris@vazquez.nl>
 */
class SeasonComparisonController implements IReportController {
    // Define the seasons.
    const SEASONS = [
        "voorjaar" => [
            "seasonStart" => "1 April",
            "seasonEnd" => "30 June"
        ],
        "zomer" => [
            "seasonStart" => "1 July",
            "seasonEnd" => "31 August"
        ],
        "najaar" => [
            "seasonStart" => "1 September",
            "seasonEnd" => "15 December"
        ],
        "winter" => [
            "seasonStart" => "16 December",
            "seasonEnd" => "31 March"
        ]
    ];

    /**
     * Renders the content of the implementing controller.
     * @return void
     */
    public static function render() {
        global $errors;
        $errors = [];
        global $reportSeason;
        $reportSeason = "winter";
        if (!empty($_POST)) {
            // Check if the nonce is valid.
            check_admin_referer('bookings-season-comparison', 'report-nonce');
            // Get the fields.
            $reportSeason = isset($_POST['report-season-filter']) ? $_POST['report-season-filter'] : "winter";
        }
        // Get the BookingReports for the correct season of this year and 4 years back.
        $year = (int)date("Y");
        $years = [];
        $totalBookings = 0;
        for ($index = $year; $index > $year-5; $index--) {
            $seasonStart = self::SEASONS[$reportSeason]["seasonStart"];
            $seasonEnd = self::SEASONS[$reportSeason]["seasonEnd"];
            $seasonAdvancesYear = strtotime($seasonStart) > strtotime($seasonEnd);
            $startDate = $seasonStart . " " . ($seasonAdvancesYear ? $index-1 : $index);
            $endDate = $seasonEnd . " " . $index;
            $bookingReports = BookingReport::get_by_travel_date($startDate, $endDate);
            if ($bookingReports === null) $bookingReports = [];
            $years[$index] = count($bookingReports);
            $totalBookings += $years[$index];
        }
        $seasonLabels = json_encode([$year-4, $year-3, $year-2, $year-1, $year]);
        $seasonValues = json_encode([$years[$year-4], $years[$year-3], $years[$year-2], $years[$year-1], $years[$year]]);
        $seasonColors = json_encode(['rgba(54, 162, 235, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(221, 122, 15, 0.2)']);
        $seasonBorders = json_encode(['rgba(54, 162, 235, 1)', 'rgba(54, 162, 235, 1)', 'rgba(54, 162, 235, 1)', 'rgba(54, 162, 235, 1)', 'rgba(221, 122, 15, 1)']);

        // Include the template part. Variables declared above can be used inside the template.
        include(locate_template('templates/woocommerce_reports/bookings-season-comparison.php', false, false));
    }
}