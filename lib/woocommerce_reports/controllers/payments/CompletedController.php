<?php

namespace lib\woocommerce_reports\controllers\payments;

use lib\woocommerce_reports\IReportController;
use lib\woocommerce_reports\models\PaymentData;

/**
 * Holds all functionality used by the Completed Payments section of the Payments report tab.
 *
 * Class CompletedController
 * @package lib\woocommerce_reports\controllers\payments
 * @author Chris van Zanten <chris@vazquez.nl>
 */
class CompletedController implements IReportController {

    /**
     * Renders the content of the implementing controller.
     * @return void
     */
    public static function render() {
        global $errors;
        $errors = [];
        global $paymentDateBegin;
        global $paymentDateEnd;
        if (!empty($_POST)) {
            // Check if the nonce is valid.
            check_admin_referer('payments-completed', 'report-nonce');
            // Get the fields.
            $paymentDateBegin = isset($_POST['payment-date-filter-begin']) ? $_POST['payment-date-filter-begin'] : null;
            $paymentDateEnd = isset($_POST['payment-date-filter-end']) ? $_POST['payment-date-filter-end'] : null;
            if (!empty($paymentDateBegin) && !empty($paymentDateEnd)) {
                $results = PaymentData::get_by_payment_date($paymentDateBegin, $paymentDateEnd, true);
                if (!isset($results)) $errors = ["Een van de betalings datum velden bevat foutieve informatie."];
            } else {
                $results = PaymentData::get_by_payment_date("-6 MONTHS", "NOW", true);
            }
        } else {
            $results = PaymentData::get_by_payment_date("-6 MONTHS", "NOW", true);
        }
        // Calculate the total payment amount.
        $totalPaidAmount = 0;
        if (!empty($results)) {
            foreach ($results as $result) {
                $totalPaidAmount += $result->Amount;
            }
            // Sort by payment date.
            usort($results, function($a, $b) {
                $timeA = strtotime($a->DateTime);
                $timeB = strtotime($b->DateTime);
                if ($timeA === $timeB) return 0;
                return $timeA < $timeB ? 1 : -1;
            });
        }
        // Include the template part. Variables declared above can be used inside the template.
        include(locate_template('templates/woocommerce_reports/payments-completed.php', false, false));
    }
}