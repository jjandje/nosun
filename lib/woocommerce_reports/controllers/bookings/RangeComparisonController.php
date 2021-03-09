<?php

namespace lib\woocommerce_reports\controllers\bookings;

use lib\woocommerce_reports\IReportController;
use lib\woocommerce_reports\models\BookingReport;
use lib\woocommerce_reports\models\InvoiceData;
use lib\woocommerce_reports\models\PaymentData;

/**
 * Holds all functionality used by the Range Comparison section of the Bookings report tab.
 *
 * Class RangeComparisonController
 * @package lib\woocommerce_reports\controllers\bookings
 * @author Chris van Zanten <chris@vazquez.nl>
 */
class RangeComparisonController implements IReportController {

    /**
     * Renders the content of the implementing controller.
     * @return void
     */
    public static function render() {
        global $errors;
        $errors = [];
        global $reportWeek;
        try {
            $currentDate = new \DateTime();
            $year = intval($currentDate->format('Y'));
            $reportWeek = intval($currentDate->format('W'));
        } catch (\Exception $e) {
            $errors[] = "Er ging iets fout tijdens het ophalen van de boekings data.";
            $year = 2019;
            $reportWeek = 2;
        }
        // Check if there is any post data.
        if (!empty($_POST)) {
            // Check if the nonce is valid.
            check_admin_referer('bookings-range-comparison', 'report-nonce');
            // Get the fields.
            $input = isset($_POST['report-week-filter']) ? $_POST['report-week-filter'] : 0;
            if (is_numeric($input)) {
                $input = intval($input);
                if ($input > 0 && $input <= 52) {
                    $reportWeek = $input;
                }
            }
        }
        $previousReportWeek = $reportWeek - 1;
        $usePriorYear = false;
        // Prepend a zero to the week should it be a single digit.
        if ($reportWeek > 0 && $reportWeek < 10) $reportWeek = sprintf("0%d", $reportWeek);
        if ($previousReportWeek === 0) {
            $previousReportWeek = 52;
            $usePriorYear = true;
        } elseif ($previousReportWeek > 0 && $previousReportWeek < 10) {
            $previousReportWeek = sprintf("0%d", $previousReportWeek);
        }
        // Generate the strtotime strings.
        $startDateThisWeek = sprintf("%dW%s", $year, $reportWeek);
        $endDateThisWeek = sprintf("%dW%s +6 days", $year, $reportWeek);
        $startDatePreviousWeek = sprintf("%dW%s", $usePriorYear ? $year-1 : $year, $previousReportWeek);
        $endDatePreviousWeek = sprintf("%dW%s +6 days", $usePriorYear ? $year-1 : $year, $previousReportWeek);
        // Get the BookingReports for the past 7 days and the 7 days before those.
        $thisWeek = BookingReport::get_by_booking_date($startDateThisWeek, $endDateThisWeek, false, false, false, true, true, true);
        $previousWeek = BookingReport::get_by_booking_date($startDatePreviousWeek, $endDatePreviousWeek, false, false, false, true, true, true);
        $revenueThisWeek = 0;
        $revenuePreviousWeek = 0;
        if ($thisWeek === null) {
            $errors[] = "Er ging iets fout tijdens het ophalen van de boekings data van deze week.";
            $thisWeek = [];
        } else {
            foreach ($thisWeek as $bookingReport) {
                if (!empty($bookingReport->InvoiceDatas)) {
                    foreach ($bookingReport->InvoiceDatas as $invoiceData) {
                        /** @var InvoiceData $invoiceData */
                        if ($invoiceData->InvoiceStatus === 'Draft') continue;
                        $revenueThisWeek += $invoiceData->Amount;
                    }
                }
            }
        }
        if ($previousWeek === null) {
            $errors[] = "Er ging iets fout tijdens het ophalen van de boekings data van vorige week.";
            $previousWeek = [];
        } else {
            foreach ($previousWeek as $bookingReport) {
                if (!empty($bookingReport->InvoiceDatas)) {
                    foreach ($bookingReport->InvoiceDatas as $invoiceData) {
                        /** @var InvoiceData $invoiceData */
                        if ($invoiceData->InvoiceStatus === 'Draft') continue;
                        $revenuePreviousWeek += $invoiceData->Amount;
                    }
                }
            }
        }
        $numThisWeek = count($thisWeek);
        $numPreviousWeek = count($previousWeek);
        $totalBookings = $numThisWeek + $numPreviousWeek;
        if ($reportWeek === '') $labelThisWeek = "Deze week";
        else $labelThisWeek = $usePriorYear ? sprintf("%d Week 1", $year) : sprintf("Week %d", $reportWeek);
        if ($reportWeek === '') $labelPreviousWeek = "Vorige week";
        else $labelPreviousWeek = $usePriorYear ? sprintf("%d Week 52", $year - 1) : sprintf("Week %d", $previousReportWeek);
        $rangeLabels = json_encode([$labelPreviousWeek, $labelThisWeek]);
        $rangeValues = json_encode([$numPreviousWeek, $numThisWeek]);

        // 1 Year back
        $previousYearStartDateThisWeek = sprintf("%dW%s", $year-1, $reportWeek);
        $previousYearEndDateThisWeek = sprintf("%dW%s +6 days", $year-1, $reportWeek);
        $previousYearStartDatePreviousWeek = sprintf("%dW%s", $usePriorYear ? $year-2 : $year-1, $previousReportWeek);
        $previousYearEndDatePreviousWeek = sprintf("%dW%s +6 days", $usePriorYear ? $year-2 : $year-1, $previousReportWeek);
        $thisWeekPreviousYear = BookingReport::get_by_booking_date($previousYearStartDateThisWeek, $previousYearEndDateThisWeek,
            false, false, false, true, true, true);
        $previousWeekPreviousYear = BookingReport::get_by_booking_date($previousYearStartDatePreviousWeek, $previousYearEndDatePreviousWeek,
            false, false, false, true, true, true);
        $revenueThisWeekPreviousYear = 0;
        $revenuePreviousWeekPreviousYear = 0;
        if ($thisWeekPreviousYear === null) {
            $errors[] = "Er ging iets fout tijdens het ophalen van de boekings data van huidige week van het jaar ervoor.";
            $thisWeekPreviousYear = [];
        } else {
            foreach ($thisWeekPreviousYear as $bookingReport) {
                if (!empty($bookingReport->InvoiceDatas)) {
                    foreach ($bookingReport->InvoiceDatas as $invoiceData) {
                        /** @var InvoiceData $invoiceData */
                        if ($invoiceData->InvoiceStatus === 'Draft') continue;
                        $revenueThisWeekPreviousYear += $invoiceData->Amount;
                    }
                }
            }
        }
        if ($previousWeekPreviousYear === null) {
            $errors[] = "Er ging iets fout tijdens het ophalen van de boekings data van de vorige week van het jaar ervoor.";
            $previousWeekPreviousYear = [];
        } else {
            foreach ($previousWeekPreviousYear as $bookingReport) {
                if (!empty($bookingReport->InvoiceDatas)) {
                    foreach ($bookingReport->InvoiceDatas as $invoiceData) {
                        /** @var InvoiceData $invoiceData */
                        if ($invoiceData->InvoiceStatus === 'Draft') continue;
                        $revenuePreviousWeekPreviousYear += $invoiceData->Amount;
                    }
                }
            }
        }
        $numThisWeekPreviousYear = count($thisWeekPreviousYear);
        $numPreviousWeekPreviousYear = count($previousWeekPreviousYear);
        $totalBookingsPreviousYear = $numThisWeek + $numPreviousWeek;
        if ($reportWeek === '') $labelThisWeekPreviousYear = "Deze week";
        else $labelThisWeekPreviousYear = $usePriorYear ? sprintf("%d Week 1", $year) : sprintf("Week %d", $reportWeek);
        if ($reportWeek === '') $labelPreviousWeekPreviousYear = "Vorige week";
        else $labelPreviousWeekPreviousYear = $usePriorYear ? sprintf("%d Week 52", $year - 1) : sprintf("Week %d", $previousReportWeek);
        $rangeLabelsPreviousYear = json_encode([$labelPreviousWeekPreviousYear, $labelThisWeekPreviousYear]);
        $rangeValuesPreviousYear = json_encode([$numPreviousWeekPreviousYear, $numThisWeekPreviousYear]);

        // Include the template part. Variables declared above can be used inside the template.
        include(locate_template('templates/woocommerce_reports/bookings-range-comparison.php', false, false));
    }
}