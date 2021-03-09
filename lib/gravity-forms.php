<?php

namespace Roots\Sage;

use lib\controllers\Email;
use Vazquez\NosunAssumaxConnector\Api\Bookings;
use Vazquez\NosunAssumaxConnector\Api\Customers;
use Vazquez\NosunAssumaxConnector\Api\Locks;
use WC_Order;

/**
 * Class that holds all the extra gravity forms functionality utilized by this theme.
 *
 * Class GravityForms
 * @package Roots\Sage
 */
class GravityForms {
    /**
     * Prerenders the gravity forms form with id 4.
     *
     * @note Should be called by the gform_pre_render_4 hook.
     * @param mixed $form The form object passed by the hook.
     * @return mixed The form modified to hold the available payment options.
     */
    public static function prerender_payment_form_4($form) {
        $bookingAssumaxId = get_query_var('page', 0);
        $bookingPostId = get_query_var('booking_post_id', 0);
        $choices = [];
        if (!empty($bookingAssumaxId) && !empty($bookingPostId)) {
            $choices = self::get_payment_choices($bookingPostId);
        }
        // Set the correct field values.
        foreach ($form['fields'] as &$field) {
            switch ($field['id']) {
                case 1: {
                    $field['defaultValue'] = $bookingPostId;
                    break;
                }
                case 2: {
                    $field['defaultValue'] = $paymentStatus ?? '';
                    break;
                }
                case 3: {
                    $field['choices'] = $choices;
                    break;
                }
                case 6: {
                    $field['defaultValue'] = $bookingAssumaxId;
                    break;
                }
            }
        }
        return $form;
    }

	/**
	 * Obtains the payment choices for the gravity forms payment form for the target booking.
	 *
	 * @param int $bookingPostId The post id of the booking for which to obtain the payment choices.
	 * @return array A list of choices used by gravity forms to show a payment form.
	 */
	private static function get_payment_choices($bookingPostId) {
		$choices = [];
		$invoiceAmounts = Bookings::get_invoice_amounts($bookingPostId);
		if (empty($invoiceAmounts) || empty($invoiceAmounts['total'])) {
			return $choices;
		}
		$totalPaidAmount = Bookings::get_total_paid_amount($bookingPostId);
		if ($totalPaidAmount >= $invoiceAmounts['total']) {
			return $choices;
		}
		// CHANGE: show payment options even if the deadline is in the past
		// @since 02-06-2020
		//		$today = Helpers::today();
		//		$paymentDeadlineDateTime = Helpers::create_local_datetime(get_field('booking_payment_deadline', $bookingPostId));
		//        if ($today >= $paymentDeadlineDateTime) {
		//		    return $choices;
		//	    }
		$paymentStatus = get_field('booking_payment_status', $bookingPostId);
		if (empty($paymentStatus) || $paymentStatus === 'Unpaid') {
			// @brief in case noSun adds a manual payment without adjusting the payment status;
			// @since 02-07-2020
			// @change $invoiceAmounts['total'] -> $invoiceAmounts['total'] - $totalPaidAmount
			$totalPaymentAmount = $invoiceAmounts['total'] - $totalPaidAmount;
			$formattedTotal = number_format($totalPaymentAmount, 2, ',', '');
			$choiceTotal = [
				'text' => "Totaal betaling (&euro;{$formattedTotal})",
				'value' => $formattedTotal
			];

			// CHANGE: show payment options even if the deadline is in the past
			// @since 02-06-2020
			//			$depositDeadlineDateTime = Helpers::create_local_datetime(get_field('booking_deposit_deadline', $bookingPostId));
			//            if (empty($invoiceAmounts['deposit'])
			//                || empty($depositDeadlineDateTime)
			//                || $today >= $depositDeadlineDateTime
			//            ) {

			// @important the original line in case we need to put this back in the future
			//			if (empty($invoiceAmounts['deposit']) || empty($depositDeadlineDateTime) ) {
			if (empty($invoiceAmounts['deposit']) ) {
				// Total payment.
				$choices[0] = $choiceTotal;
			} else {
				// Deposit and Total payment.
				$formattedDeposit = number_format($invoiceAmounts['deposit'], 2, ',', '');
				$choiceDeposit = [
					'text' => "Aanbetaling (&euro;{$formattedDeposit})",
					'value' => $formattedDeposit
				];
				$choices[0] = $choiceDeposit;
				$choices[1] = $choiceTotal;
			}
		} else {
			// Rest payment.
			// @brief Always show the total amount of the invoice minus the paid amount(s)
			// @since 02-07-2020
			$restAmount = $invoiceAmounts['total'] - $totalPaidAmount;

			// @important the original if else statement in case we need to put this back in the future
//			if (empty($invoiceAmounts['payment'])) {
//				$restAmount = $invoiceAmounts['total'] - $totalPaidAmount;
//			} else {
//				$restAmount = $invoiceAmounts['payment'];
//			}


			$formattedRest = number_format($restAmount, 2, ',', '');
			$choice = [
				'text' => "Restbetaling (&euro;{$formattedRest})",
				'value' => $formattedRest
			];
			$choices[0] = $choice;
		}
		return $choices;
	}

    /**
     * Parses a payment passed through the payment form.
     *
     * @note Should be called by the gform_ideal_fulfillment hook.
     * @param mixed $form The filled in form.
     * @param mixed $feed The feed.
     */
    public static function payment_received($form, $feed) {
        // Only process input from form 4.
        if ($form['form_id'] == 4) {
            $booking = wc_get_order($form['1']);
            if (empty($booking)) {
                error_log("[Roots\Sage\GravityForms->payment_received]: Not an existing booking.");
                error_log(var_export($form, true));
                return;
            }
            $assumaxId = get_post_meta($booking->get_id(), '_assumax_id', true);
            if (empty($assumaxId)) {
                error_log("[Roots\Sage\GravityForms->payment_received]: The booking has no Assumax Id.");
                error_log(var_export($form, true));
                return;
            }
            $paymentStatus = get_field('booking_payment_status', $booking->get_id());
            $paidAmount = floatval(str_replace(',', '.', str_replace('.', '', $form['3'] ?? 0)));
            if (empty($paidAmount)) {
                error_log("[Roots\Sage\GravityForms->payment_received]: Not a valid paid amount.");
                error_log(var_export($form, true));
                return;
            }
            $transactionID = $form['transaction_id'] ?? '';
            $invoiceAmounts = Bookings::get_invoice_amounts($booking->get_id());
            $previouslyPaidAmount = Bookings::get_total_paid_amount($booking->get_id());
            $newTotalPaidAmount = $previouslyPaidAmount + $paidAmount;
            // Obtain a lock on the Assumax Id so we can guarantee a successful upsert.
            $lock = Locks::acquire_lock($assumaxId, 'Booking', 'Modified', true);
            // Put the payment to the API.
            if ($paymentStatus === 'Unpaid') {
                if ($newTotalPaidAmount >= $invoiceAmounts['total']) {    // Total payment
                    Bookings::add_payment($assumaxId, 'Totaal betaling', $paidAmount, $transactionID);
                    $booking->add_order_note(sprintf("Totaal betaling van &euro;%d voldaan met iDeal op %s.", $paidAmount, current_time('mysql', 0)));
                    self::trigger_payment_received_email($assumaxId, $booking, $paidAmount, 'total');
                } else {                                        // Deposit
                    Bookings::add_payment($assumaxId, 'Aanbetaling', $paidAmount, $transactionID);
                    $booking->add_order_note(sprintf("Aanbetaling van &euro;%d voldaan met iDeal op %s.", $paidAmount, current_time('mysql', 0)));
                    self::trigger_payment_received_email($assumaxId, $booking, $paidAmount, 'deposit');
                }
            } else {                                            // Restpayment
                Bookings::add_payment($assumaxId, 'Restbetaling', $paidAmount, $transactionID);
                $booking->add_order_note(sprintf("Restbetaling van &euro;%d voldaan met iDeal op %s.", $paidAmount, current_time('mysql', 0)));
                self::trigger_payment_received_email($assumaxId, $booking, $paidAmount, 'rest');
            }
            // Regardless of the type of payment, if the booking was an option, then it now no longer is.
            $isOption = get_field('booking_is_option', $booking->get_id());
            if ($isOption) {
                if (!Bookings::update_api_data($assumaxId, true)) {
                    error_log("[Roots\Sage\GravityForms->payment_received]: Could not update the booking option status to true.");
                    $booking->add_order_note('Fout: Kon de optie niet omzetten naar een boeking!');
                }
            }
            // Upsert the booking to acquire its latest state.
            if ($lock) {
                $upsertResult = Bookings::upsert($assumaxId);
                Locks::release_lock($lock);
                if ($upsertResult === false) {
                    error_log("[Roots\Sage\GravityForms->payment_received]: Could not upsert the booking with Assumax Id: {$assumaxId}.");
                }
            }
        }
    }

    /**
     * Adds the Euro to the set of available currencies.
     *
     * @param mixed currencies The current set of currencies.
     * @return mixed The set of currencies modified to hold the euro.
     */
    public static function add_euro_currency($currencies) {
        $currencies['EUR'] = array(
            "name" => __("Euro", "gravityforms"),
            "symbol_left" => '€',
            "symbol_right" => "",
            "symbol_padding" => " ",
            "thousand_separator" => '.',
            "decimal_separator" => ',',
            "decimals" => 2
        );
        return $currencies;
    }

    /**
     * Changes the submit button
     *
     * @note Should be called by the gform_submit_button_4 hook.
     * @param mixed $button The current button.
     * @param mixed $form The form.
     * @return string HTML for the new submit button.
     */
    public static function form_submit_button_4($button, $form) {
        return "<button class='gform_button button' id='gform_submit_button_{$form["id"]}'>Nu betalen met Ideal <i class='fas fa-chevron-right'></i></button>";
    }

    /**
     * Trigger an e-mail payment received event for the primary customer for which a payment has been received.
     * The type of trigger depends on the payment type.
     *
     * @param int $bookingAssumaxId Assumax Id of the booking.
     * @param WC_Order $booking Order object of the Booking.
     * @param float $paidAmount The amount paid.
     * @param string $paymentType The type of the payment.
     * @return bool true when triggered events were successful or false if something went wrong.
     */
    private static function trigger_payment_received_email(int $bookingAssumaxId, WC_Order $booking, float $paidAmount, string $paymentType) : bool
    {
        if (empty($booking) || empty($paidAmount) || empty($paymentType)) {
            error_log("[GravityForms->trigger_payment_received_email]: One of the parameters is empty.");
            return false;
        }
        $customers = get_field('booking_customers', $booking->get_id());
        if (empty($customers)) {
            error_log("[GravityForms->trigger_payment_received_email]: No customers available on the booking.");
            return false;
        }
        $customerObject = null;
        foreach ($customers as $customer) {
            if ($customer['primary']) {
                $customerObject = Customers::get_by_assumax_id($customer['id']);
                if (empty($customerObject)) {
                    error_log("[GravityForms->trigger_payment_received_email]: There exists no Customer with id: {$customer['id']}.");
                    return false;
                }
                break;
            }
        }
        if (empty($customerObject)) {
            error_log("[GravityForms->trigger_payment_received_email]: No primary customer is set on the booking.");
            return false;
        }
        $emailAddress = get_field('customer_email_address', $customerObject->ID);
        if ($paymentType === 'total') {
            $key = 'total_payment_received';
        } elseif ($paymentType === 'deposit') {
            $key = 'deposit_payment_received';
        } else {
            $key = 'rest_payment_received';
        }
        $events = Email::trigger_email_events($key, [$emailAddress], [
            'customer' => $customerObject,
            'paid_amount' => $paidAmount,
            'booking_assumax_id' => $bookingAssumaxId
        ], "{$bookingAssumaxId}_{$paymentType}");
        $error = false;
        if (!empty($events)) {
            foreach ($events as $eventId => $status) {
                if ($status === false) {
                    $booking->add_order_note("Kon geen betaling ({$paymentType}) ontvangen mail versturen naar: {$emailAddress}. EmailEventId: {$eventId}.");
                    $error = true;
                } else {
                    $booking->add_order_note("Betaling ({$paymentType}) ontvangen mail verzonden naar: {$emailAddress}. EmailEventId: {$eventId}.");
                }
            }
        }
        return !$error;
    }
}

// Add the actions.
add_filter('gform_pre_render_4', ['Roots\Sage\GravityForms', 'prerender_payment_form_4']);
add_filter('gform_currencies', ['Roots\Sage\GravityForms', 'add_euro_currency']);
add_action('gform_ideal_fulfillment', ['Roots\Sage\GravityForms', 'payment_received'], 10, 2);
add_filter("gform_submit_button_4", ['Roots\Sage\GravityForms', 'form_submit_button_4'], 10, 2);
