<?php

namespace lib\woocommerce_reports\controllers\revenue;

use lib\woocommerce_reports\IReportController;
use lib\woocommerce_reports\models\BookingReport;
use lib\woocommerce_reports\models\PaymentData;

/**
 * Holds all functionality used by the Report section of the Revenue report tab.
 *
 * Class ReportController
 * @package lib\woocommerce_reports\controllers\revenue
 * @author Chris van Zanten <chris@vazquez.nl>
 */
class ReportController implements IReportController {

    /**
     * Renders the content of the implementing controller.
     * @return void
     */
    public static function render() {
        global $errors;
        $errors = [];
        // Check if there is any post data.
        global $reportWeek;
        $reportWeek = 50;
        global $targetGrowPercentage;
        $targetGrowPercentage = 5;
        if (!empty($_POST)) {
            // Check if the nonce is valid.
            check_admin_referer('revenue-report', 'report-nonce');
            // Get the fields.
            $reportWeek = isset($_POST['report-week-filter']) ? $_POST['report-week-filter'] : 50; // Default to week 50.
            if (!is_numeric($reportWeek)) $reportWeek = 50;
            elseif($reportWeek > 52 || $reportWeek < 1) $reportWeek = 50;
            $targetGrowPercentage = isset($_POST['grow-percentage-filter']) ? $_POST['grow-percentage-filter'] : 5; // Default to 5%.
            if (!is_numeric($targetGrowPercentage)) $targetGrowPercentage = 5;
        }
        // Get the current year.
        $thisYear = (int)date("Y");
        // Get the BookingReports for the current year and 4 years back.
        $weekString = sprintf("W%s", $reportWeek < 10 ? "0".$reportWeek : $reportWeek);
        $bookingReportsPerYear = [];
        for ($year = $thisYear; $year >= $thisYear-4; $year--) {
            $bookingReportsPerYear[$year] = BookingReport::get_by_travel_date("{$year}W01", "{$year}{$weekString} +6 days", true, false, true);
        }
        $dataPerYear = [];
        $revenuePerYear = [];
        $bookingsPerYear = [];
        foreach($bookingReportsPerYear as $year => $bookingReports) {
            // For each trip in this year, get the total number of bookings, total revenue and the TripReport and save it under its TemplateId.
            $dataPerYear[$year] = [];
            $revenuePerYear[$year] = 0;
            $bookingsPerYear[$year] = 0;
            if (!empty($bookingReports)) {
                $trips = [];
                foreach ($bookingReports as $bookingReport) {
                    /** @var BookingReport $bookingReport */
                    if ($bookingReport->PaymentStatus === 'Unpaid' || $bookingReport->NosunStatus === 'Cancelled') continue;
                    $bookingsPerYear[$year]++;
                    if (!empty($bookingReport->TripReport)) {
                        if (!key_exists($bookingReport->TripReport->TemplateId, $trips)) $trips[$bookingReport->TripReport->TemplateId] = [];
                        // Increase the number of bookings for this trip by 1.
                        if (!key_exists("nBookings", $trips[$bookingReport->TripReport->TemplateId])) $trips[$bookingReport->TripReport->TemplateId]["nBookings"] = 1;
                        else $trips[$bookingReport->TripReport->TemplateId]["nBookings"]++;
                        // Sum the payment amounts for this booking.
                        if (!empty($bookingReport->PaymentDatas)) {
                            $totalPaid = 0;
                            foreach ($bookingReport->PaymentDatas as $paymentData) {
                                /** @var PaymentData $paymentData */
                                $totalPaid += $paymentData->Amount;
                            }
                            if (!key_exists("revenue", $trips[$bookingReport->TripReport->TemplateId])) $trips[$bookingReport->TripReport->TemplateId]["revenue"] = $totalPaid;
                            else $trips[$bookingReport->TripReport->TemplateId]["revenue"] += $totalPaid;
                            // Add the booking revenue to the total per year.
                            $revenuePerYear[$year] += $totalPaid;
                        }
                        // Set the TripReport data should it not have been set yet.
                        if (!key_exists("tripReport", $trips[$bookingReport->TripReport->TemplateId])) $trips[$bookingReport->TripReport->TemplateId]["tripReport"] = $bookingReport->TripReport;
                        if (!key_exists("growPercentage", $trips[$bookingReport->TripReport->TemplateId])) $trips[$bookingReport->TripReport->TemplateId]["growPercentage"] = 100;
                    }
                }
                uasort($trips, function($a, $b) {
                    $revenueA = isset($a["revenue"]) ? $a["revenue"] : 0;
                    $revenueB = isset($b["revenue"]) ? $b["revenue"] : 0;
                    if ($revenueA === $revenueB) return 0;
                    return $revenueA < $revenueB ? 1 : -1;
                });
                $dataPerYear[$year] = $trips;
            }
        }

        // Calculate the grow percentages.
        for ($year = $thisYear; $year >= $thisYear-3; $year--) {
            // For each trip that has an existing record in the previous year, calculate the grow percentage.
            foreach ($dataPerYear[$year] as $templateId => $data) {
                if (key_exists($templateId, $dataPerYear[$year-1])) {
                    $oldRevenue = isset($dataPerYear[$year-1][$templateId]["revenue"]) ? $dataPerYear[$year-1][$templateId]["revenue"] : 0;
                    $newRevenue = isset($data["revenue"]) ? $data["revenue"] : 0;
                    $growPercentage = $oldRevenue === 0 ? -1 : ($newRevenue / $oldRevenue * 100) - 100;
                    $dataPerYear[$year][$templateId]["growPercentage"] = $growPercentage;
                }
            }
            // Check for trips that have no reports in the current year, but that do exist in the last year.
            $oldTemplateIds = array_keys($dataPerYear[$year-1]);
            $newTemplateIds = array_keys($dataPerYear[$year]);
            $missingTemplateIds = array_diff($oldTemplateIds, $newTemplateIds);
            foreach ($missingTemplateIds as $missingTemplateId) {
                $dataPerYear[$year][$missingTemplateId] = [
                    "nBookings" => 0,
                    "revenue" => 0,
                    "tripReport" => $dataPerYear[$year-1][$missingTemplateId]["tripReport"],
                    "growPercentage" => -100
                ];
            }
        }

        // Compute total growth, if that target has been met and how many bookings need to be made to get to that percentage.
        $averageBookingRevenue = array_sum($revenuePerYear) / array_sum($bookingsPerYear);
        $targetRevenue = $revenuePerYear[$thisYear-1] * ($targetGrowPercentage + 100) / 100;
        $currentGrowth = $revenuePerYear[$thisYear] / $revenuePerYear[$thisYear-1] * 100 - 100;
        $revenueDifference = $revenuePerYear[$thisYear] - $revenuePerYear[$thisYear-1];
        $revenueNeeded = $targetRevenue - $revenuePerYear[$thisYear];
        $bookingsNeeded = $revenueNeeded < 0 ? -1 : $revenueNeeded / $averageBookingRevenue;

        // Include the template part. Variables declared above can be used inside the template.
        include(locate_template('templates/woocommerce_reports/revenue-report.php', false, false));
    }
}