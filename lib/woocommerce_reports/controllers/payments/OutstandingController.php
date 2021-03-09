<?php

namespace lib\woocommerce_reports\controllers\payments;

use lib\woocommerce_reports\IReportController;
use lib\woocommerce_reports\models\BookingReport;
use lib\woocommerce_reports\models\InvoiceData;
use lib\woocommerce_reports\models\PaymentData;

/**
 * Holds all functionality used by the Outstanding Payments section of the Payments report tab.
 *
 * Class OutstandingController
 * @package lib\woocommerce_reports\controllers\payments
 * @author Chris van Zanten <chris@vazquez.nl>
 */
class OutstandingController implements IReportController {

    /**
     * Renders the content of the implementing controller.
     * @return void
     */
    public static function render() {
        global $errors;
        $errors = [];
        global $bookingDateBegin;
        global $bookingDateEnd;
        if (!empty($_POST)) {
            // Check if the nonce is valid.
            check_admin_referer('payments-outstanding', 'report-nonce');
            // Get the fields.
            $bookingDateBegin = isset($_POST['booking-date-filter-begin']) ? $_POST['booking-date-filter-begin'] : null;
            $bookingDateEnd = isset($_POST['booking-date-filter-end']) ? $_POST['booking-date-filter-end'] : null;
            if (!empty($bookingDateBegin) && !empty($bookingDateEnd)) {
                // Load the data using the booking dates.
                $results = BookingReport::get_by_booking_date($bookingDateBegin, $bookingDateEnd, true, true, true, true);
                if (!isset($results)) $errors = ["Een van de boeking datum velden bevat foutieve informatie."];
            } else {
                // Load the data using the default booking date range.
                $results = BookingReport::get_by_booking_date('-6 months', 'now', true, true, true, true);
            }
        } else {
            // Load the data using the default booking date range.
            $results = BookingReport::get_by_booking_date('-6 months', 'now', true, true, true, true);
        }
        // Filter all Bookings that are Paid, Cancelled, Have a payment amount higher than the total amounts.
        $outstandingBookings = [];
        $totalInvoiceAmount = 0;
        $totalPaidAmount = 0;
        $totalOutstandingAmount = 0;
        if (!empty($results)) {
            foreach ($results as $result) {
                if ($result->PaymentStatus === 'Paid' || $result->NosunStatus === 'Cancelled') continue;
                // Combine the Invoices.
                $invoiceAmount = 0;
                if (!empty($result->InvoiceDatas)) {
                    foreach ($result->InvoiceDatas as $invoiceData) {
                        /** @var InvoiceData $invoiceData */
                        if ((int)($invoiceData->Number) <= 1) continue;
                        $invoiceAmount += $invoiceData->Amount;
                    }
                }
                // Combine the Payments.
                $paymentAmount = 0;
                if (!empty($result->PaymentDatas)) {
                    foreach ($result->PaymentDatas as $paymentData) {
                        /** @var PaymentData $paymentData */
                        $paymentAmount += $paymentData->Amount;
                    }
                }
                if ($paymentAmount >= $invoiceAmount) continue;
                $totalInvoiceAmount += $invoiceAmount / 100.0;
                $totalPaidAmount += $paymentAmount / 100.0;
                $totalOutstandingAmount += ($invoiceAmount - $paymentAmount) / 100.0;
                // Construct a new object that holds the data we want to show to the user.
                $outstandingBooking = new \stdClass();
                $outstandingBooking->InvoiceAmount = $invoiceAmount / 100.0;
                $outstandingBooking->PaymentAmount = $paymentAmount / 100.0;
                $outstandingBooking->PaymentDeadline = strtotime($result->PaymentDeadline);
                $outstandingBooking->NosunId = $result->NosunId;
                $outstandingBooking->Trip = isset($result->TripReport) ? $result->TripReport->Title : "Onbekende reis met id: {$result->NosunTripId}";
                $outstandingBooking->Customer = !empty($result->CustomerDatas) ? $result->CustomerDatas[0] : null;
                $outstandingBookings[] = $outstandingBooking;
            }
        }
        // Sort by PaymentDeadline.
        usort($outstandingBookings, function($a, $b) {
            if ($a->PaymentDeadline === $b->PaymentDeadline) return 0;
            return ($a->PaymentDeadline < $b->PaymentDeadline) ? -1 : 1;
        });
        // Include the template part. Variables declared above can be used inside the template.
        include(locate_template('templates/woocommerce_reports/payments-outstanding.php', false, false));
    }
}