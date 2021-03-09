<?php /** @noinspection SqlNoDataSourceInspection */

namespace Vazquez\NosunAssumaxConnector\Api;

use Exception;
use lib\controllers\Email;
use stdClass;
use Vazquez\NosunAssumaxConnector\Helpers;
use WC_Order;
use WC_Order_Item_Fee;
use WP_Post;

/**
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Bookings implements ILoadable {
    /**
     * Obtains the Booking (shop_order) which AssumaxId is equal to the one provided.
     *
     * @param string $assumaxId The Assumax Id for the Booking (shop_order).
     * @return WP_Post | null A post with type 'shop_order' belonging to the provided Assumax Id, or null if something
     * went wrong.
     */
    public static function get_by_assumax_id($assumaxId) {
        if (empty($assumaxId)) return null;
        $bookingPosts = self::get_by_assumax_ids([$assumaxId], false, 1);
        return empty($bookingPosts) ? null : $bookingPosts[0];
    }

    /**
     * Obtains the Bookings (shop_order) for which an Assumax Id exists in the provided array of ids.
     *
     * @param array $assumaxIds List of Assumax Ids for bookings that need to be retrieved.
     * @param bool $filterCancelled Whether to filter out bookings with have the 'Cancelled' status.
     * @param int $limit Limit the amount of bookings returned to the number provided where -1 is all bookings.
     * @return WP_Post[] An array of posts with type 'shop_order' belonging to the provided Assumax ids, or an empty
     * array should something go wrong.
     */
    public static function get_by_assumax_ids($assumaxIds, $filterCancelled = false, $limit = -1) {
        if (empty($assumaxIds)) return [];
        $args = [
            'post_type' => 'shop_order',
            'post_status' => array_keys(wc_get_order_statuses()),
            'numberposts' => $limit,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    [
                        'key' => '_assumax_id',
                        'value' => $assumaxIds,
                        'compare' => 'IN'
                    ],
                    [
                        'key' => '_nosun_booking_id',
                        'value' => $assumaxIds,
                        'compare' => 'IN'
                    ]
                ]
            ]
        ];
        if ($filterCancelled) {
            $args['meta_query'][] = [
                'key' => 'booking_status',
                'value' => 'Cancelled',
                'compare' => '!='
            ];
        }
        return get_posts($args);
    }

    /**
     * Pulls all the available Bookings from the API and upserts them.
     */
    public static function upsert_all_from_api() {
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Api\Customers->upsert_all_from_api]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return false;
        }
        $successful = [];
        $unsuccessful = [];
        $clientIterator = $client->get_all("/bookings", []);
        foreach ($clientIterator as $bookingData) {
            if (empty($bookingData->Id)) continue;
            error_log("[Api\Bookings->upsert_all_from_api]: Upserting booking with id: {$bookingData->Id}.");
            if (self::upsert($bookingData->Id, $bookingData) === false) {
                $unsuccessful[] = $bookingData->Id;
            } else {
                $successful[] = $bookingData->Id;
            }
        }
        error_log("[Api\Bookings->upsert_all_from_api]: Total amount: {$clientIterator->get_size()}.");
        error_log(sprintf("[Api\Bookings->upsert_all_from_api]: List of successful updates: %s.", implode(', ', $successful)));
        error_log(sprintf("[Api\Bookings->upsert_all_from_api]: List of unsuccessful updates: %s.", implode(', ', $unsuccessful)));
        return true;
    }

    /**
     * Tries to either update or create a new Booking (shop_order) depending on the provided data. Will pull the
     * newest version from the API.
     *
     * @param int $assumaxId The Assumax Id for the Booking.
     * @param object $bookingData Optional API data to use instead of pulling it from the API.
     * @return int|boolean The Assumax Id of the Booking when the Booking has been upserted successfully, false if otherwise.
     */
    public static function upsert($assumaxId, $bookingData = null) {
        ini_set('memory_limit', '256M');
        set_time_limit(600);
        if (empty($assumaxId)) return false;
        if (!empty($bookingData)) {
            $data = $bookingData;
        } else {
            try {
                $client = AssumaxClient::getInstance();
            } catch (Exception $e) {
                error_log("[Nosun_Assumax_Api_Bookings->upsert]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
                return false;
            }
            $data = $client->get("/bookings/{$assumaxId}");
        }
        if (empty($data)) {
            error_log("[Nosun_Assumax_Api_Bookings->upsert]: Could not obtain the booking with Id: {$assumaxId} from the API.");
            return false;
        }

        $post = self::get_by_assumax_id($data->Id);
        if (empty($post)) {
            return self::create_booking($data);
        } else {
            return self::update_booking($post, $data);
        }
    }

    /**
     * Updates an existing booking (shop_order) specified by the post parameter with the data inside the data parameter.
     *
     * @param WP_Post $post The existing post object for the booking.
     * @param mixed $data Data from either the API or the website. Always in the form of the API specification.
     * @return int|bool The AssumaxId when the booking has been updated successfully, false if otherwise.
     */
    private static function update_booking($post, $data) {
        if (empty($post) || empty($data)) return false;
        // Update the existing booking.
        $args = ['order_id' => $post->ID];
        if (!empty($data->Customers)) {
            foreach ($data->Customers as $customer) {
                if (isset($customer->Id) && isset($customer->Primary) && $customer->Primary) {
                    $customerPost = Customers::get_by_assumax_id($customer->Id);
                    if (!empty($customerPost)) {
                        $userAccountId = Customers::get_user_id($customerPost->ID);
                        if (!empty($userAccountId)) $args['customer_id'] = $userAccountId;
                    }
                    break;
                }
            }
        }
        $wcBooking = wc_update_order($args);
        if (is_wp_error($wcBooking)) {
            error_log("[Nosun_Assumax_Api_Bookings->update_booking]: Could not update the booking with Assumax id: {$data->Id}.\n{$wcBooking->get_error_message()}");
            return false;
        }
        // Update the meta fields and return the result.
        $result = self::update_booking_meta($wcBooking, $data);
        if (empty($result)) return false;
        return $data->Id;
    }

    /**
     * Creates a new booking (shop_order) for the provided data.
     *
     * @param mixed $data Data from either the API or the website. Always in the form of the API specification.
     * @return int|bool The Assumax ID of the Booking when the Booking has been created successfully, false if otherwise.
     */
    private static function create_booking($data) {
        if (empty($data)) return false;
        // Check if the primary customer already exists, if so use the user_id of that customer as the customer id on the booking.
        $args = [];
        if (!empty($data->Customers)) {
            foreach ($data->Customers as $customer) {
                if (isset($customer->Id) && isset($customer->Primary) && $customer->Primary) {
                    $customerPost = Customers::get_by_assumax_id($customer->Id);
                    if (!empty($customerPost)) {
                        $userAccountId = Customers::get_user_id($customerPost->ID);
                        if (!empty($userAccountId)) $args['customer_id'] = $userAccountId;
                    }
                    break;
                }
            }
        }
        $wcBooking = wc_create_order($args);
        if (is_wp_error($wcBooking)) {
            error_log("[Nosun_Assumax_Api_Bookings->create_booking]: Could not create a new booking.\n{$wcBooking->get_error_message()}");
            return false;
        }
        update_post_meta($wcBooking->get_id(), '_assumax_id', $data->Id);
        if (!self::update_booking_meta($wcBooking, $data)) {
            error_log("[Nosun_Assumax_Api_Bookings->create_booking]: Could not update the meta fields for the newly created booking.");
            return false;
        }
        return $data->Id;
    }

    /**
     * Updates the data for the provided booking using the provided data.
     *
     * @param WC_Order $booking A Woocommmerce booking (shop_order) object on which the meta fields need to be set.
     * @param stdClass $data Booking data provided either by the Assumax API or the website. Needs to follow the specification
     * set by the API.
     * @return bool true when all the fields have been successfully updated, false if otherwise.
     */
    private static function update_booking_meta($booking, $data) {
        if (empty($booking) || empty($data)) return false;
        if (!isset($data->TripId)) return false;
        $today = Helpers::today();
        if (empty($today)) {
            error_log("[Api\Bookings->update_booking_meta]: Could not obtain the current datetime.");
            return false;
        }
        update_post_meta($booking->get_id(), '_assumax_trip_id', $data->TripId);

        // Find the Trip post and should it exist, link it to the booking.
        $trip = Trips::get_by_assumax_id($data->TripId);
        if (!empty($trip)) update_field('booking_trip', $trip->ID, $booking->get_id());
        if (!isset($data->Status)) return false;
        if (!isset($data->PaymentStatus)) return false;
        update_field('booking_payment_status', $data->PaymentStatus, $booking->get_id());
        if (isset($data->Option) && $data->Option) {
            update_field('booking_is_option', '1', $booking->get_id());
            $isOption = true;
        } else {
            update_field('booking_is_option', '0', $booking->get_id());
            $isOption = false;
        }

        // Insurances
        $insurances = [];
        if (isset($data->CancellationInsurance)) {
            $insurances[] = self::create_insurance_field_array($data->CancellationInsurance, 'Cancellation');
        }
        if (isset($data->TripInsurance)) {
            $insurances[] = self::create_insurance_field_array($data->TripInsurance, 'Trip');
        }
        if (isset($data->SnowmobilePayOff)) {
            $insurances[] = self::create_insurance_field_array($data->SnowmobilePayOff, 'SnowmobilePayOff');
        }
        update_field('booking_insurances', $insurances, $booking->get_id());
        if (!empty($data->Note)) update_field('booking_note', $data->Note, $booking->get_id());
        if (!empty($data->BoardingPoint)) update_field('booking_boarding_point', $data->BoardingPoint, $booking->get_id());

        // Deadlines
        if (isset($data->Date)) {
            $bookingDate = Helpers::create_local_datetime($data->Date);
            update_field('booking_date',
                !empty($bookingDate) ? $bookingDate->format("Y-m-d H:i:s") : '', $booking->get_id());
        }
        if (isset($data->DepositDeadline)) {
            $depositDeadline = Helpers::create_local_datetime($data->DepositDeadline);
            update_field('booking_deposit_deadline',
                !empty($depositDeadline) ? $depositDeadline->format("Y-m-d") : '', $booking->get_id());
        }
        if (isset($data->PaymentDeadline)) {
            $paymentDeadline = Helpers::create_local_datetime($data->PaymentDeadline);
            update_field('booking_payment_deadline',
                !empty($paymentDeadline) ? $paymentDeadline->format("Y-m-d") : '', $booking->get_id());
        }

        // Customers
        if (empty($data->Customers)) {
            error_log("[Nosun_Assumax_Api_Bookings->update_booking_meta]: No customers set for the booking.");
            return false;
        }
        $customers = [];
        $primaryCustomer = 0;
        foreach ($data->Customers as $customer) {
            if (empty($customer->Id) || empty($customer->FullName)) {
                error_log("[Nosun_Assumax_Api_Bookings->update_booking_meta]: A customer exists with missing fields.");
                error_log(var_export($customer, true));
                continue;
            }
	        if(!empty($customer->Id)) {
		        $customerPost = Customers::upsert($customer->Id); // just a safe-guard to be sure we have a user account etc.
	        }
            $customers[] = [
                'id' => $customer->Id,
                'full_name' => $customer->FullName,
                'primary' => isset($customer->Primary) && $customer->Primary ? '1' : '0'
            ];
            self::upsert_booking_customer_pivot_row(
                $data->Id,
                $customer->Id,
                isset($customer->Primary) ? $customer->Primary : 0);
            if ($customer->Primary) {
                $primaryCustomer = $customer->Id;
            }
        }
        update_field('booking_customers', $customers, $booking->get_id());



        // Send the new booking and new option e-mails.
        foreach ($data->Customers as $customer) {
            if (empty($customer->Id) || empty($trip)) {
                continue;
            }
            $customerObject = Customers::get_by_assumax_id($customer->Id);
            if (empty($customerObject)) {
                continue;
            }
            $emailAddress = get_field('customer_email_address', $customerObject->ID);
            if (empty($emailAddress)) {
                continue;
            }

	        // find the user based on customerObject
	        global $wpdb;
            $user_query = sprintf("SELECT user_id FROM %s WHERE meta_key = 'user_customer' AND meta_value = %s", $wpdb->usermeta, $customerObject->ID);
	        $booking_user_id = $wpdb->get_var($user_query);
	        // attach user id to booking update_post_meta(id, '_customer_user', $userID);
	        if(!empty($booking_user_id)) {
	        	error_log("[Bookings->update_booking_meta]: updating booking meta: {$booking->get_id()} _customer_user: {$booking_user_id}");
	        	update_post_meta($booking->get_id(), '_customer_user', $booking_user_id);
	        } else {
	        	error_log("[Bookings->update_booking_meta]: Couldn't find the user id: {$booking_user_id} for customerObject->ID: {$customerObject->ID}");
	        }

            // set the trigger variable
	        $trigger = $isOption ? 'new_option' : 'new_booking';

            // check if the email event is already in the api_emails_sent table
	        // check for trigger, email_addresses and singleton_value
			$email_has_been_sent = Email::email_event_has_been_sent( $trigger, [$emailAddress], $data->Id);

			// If the email hasn't been sent yet, send it.
			if(!$email_has_been_sent) {
	            $events = Email::trigger_email_events(
	                $trigger,
	                [$emailAddress],
	                [
	                    'booking' => $data,
	                    'id' => $data->Id,
	                    'trip' => $trip,
	                    'customer' => $customer
	                ],
	                $data->Id);
	            if (!empty($events)) {
	                foreach ($events as $eventId => $emailAddresses) {
	                    if ($emailAddresses === false) {
	                        error_log("[Booking->update_booking_meta]: Something went wrong while trying to send a new booking e-mail for customer with e-mail address: {$emailAddress}.");
	                        $booking->add_order_note("Kon geen nieuwe boeking e-mail versturen voor reiziger met id: {$customer->Id} naar e-mail adres: {$emailAddress}.");
	                    } else {
	                        $booking->add_order_note("Nieuwe boeking e-mail verzonden voor reiziger met id: {$customer->Id} naar e-mail adres: {$emailAddress}.");
	                    }
	                }
	            }
			}
        }

        // Payments
        if (!empty($data->Payments)) {
            $payments = [];
            foreach ($data->Payments as $payment) {
                if (!isset($payment->Id) || !isset($payment->DateTime) || !isset($payment->Amount)) {
                    error_log("[Nosun_Assumax_Api_Bookings->update_booking_meta]: One of the required Payment fields is missing.");
                    continue;
                }
                $dateTime = Helpers::create_local_datetime($payment->DateTime);
                $modified = null;
                if (isset($payment->Modified)) {
                    $modified = Helpers::create_local_datetime($payment->Modified);
                }
                $payments[] = [
                    'id' => $payment->Id,
                    'date_time' => !empty($dateTime) ? $dateTime->format('Y-m-d H:i:s') : $today->format('Y-m-d H:i:s'),
                    'description' => empty($payment->Description) ? 'betaling' : $payment->Description,
                    'amount' => $payment->Amount,
                    'modified' => !empty($modified) ? $modified->format('Y-m-d H:i:s') : $today->format('Y-m-d H:i:s')
                ];
            }
            update_field('booking_payments', $payments, $booking->get_id());
        }

        // Documents
        if (!empty($data->Documents)) {
            $documents = [];
            foreach ($data->Documents as $document) {
                if (empty($document->Id) || empty($document->FileName)) continue;
                $modified = null;
                if (isset($document->Modified)) {
                    $modified = Helpers::create_local_datetime($document->Modified);
                }
                $documents[] = [
                    'id' => $document->Id,
                    'title' => isset($document->Title) ? $document->Title : '',
                    'file_name' => $document->FileName,
                    'modified' => !empty($modified) ? $modified->format('Y-m-d H:i:s') : $today->format('Y-m-d H:i:s')
                ];
            }
            update_field('booking_documents', $documents, $booking->get_id());
        }

        // Invoices
        if (empty($data->Invoices)) return false;
        $primaryInvoice = 0;
        $invoices = [];
        $totalAmount = 0;
        foreach ($data->Invoices as $invoice) {
            if (!isset($invoice->Id) || !isset($invoice->Number) || !isset($invoice->InvoiceStatus) ||
                !isset($invoice->Amount) || !isset($invoice->InvoiceLines)) {
                error_log("[Nosun_Assumax_Api_Bookings->update_booking_meta_from_api]: One of the required Invoice fields is missing.");
                return false;
            }
            if (empty($primaryInvoice)) {
                $primaryInvoice = $invoice->Id;
            }
            $modified = null;
            if (isset($invoice->Modified)) {
                $modified = Helpers::create_local_datetime($invoice->Modified);
            }
            $depositDeadline = null;
            if (isset($invoice->DepositDeadline)) {
                $depositDeadline = Helpers::create_local_datetime($invoice->DepositDeadline);
            }
            $depositAmount = isset($invoice->DepositAmount) ? $invoice->DepositAmount : 0;
            $paymentDeadline = null;
            if (isset($invoice->PaymentDeadline)) {
                $paymentDeadline = Helpers::create_local_datetime($invoice->PaymentDeadline);
            }
            $paymentAmount = isset($invoice->PaymentAmount) ? $invoice->PaymentAmount : 0;
            $acfInvoice = [
                'id' => $invoice->Id,
                'number' => $invoice->Number,
                'status' => $invoice->InvoiceStatus,
                'amount' => $invoice->Amount,
                'deposit_amount' => $depositAmount,
                'deposit_deadline' => !empty($depositDeadline) ? $depositDeadline->format('Y-m-d H:i:s') : '',
                'payment_amount' => $paymentAmount,
                'payment_deadline' => !empty($paymentDeadline) ? $paymentDeadline->format('Y-m-d H:i:s') : '',
                'modified' => !empty($modified) ? $modified->format('Y-m-d H:i:s') : $today->format('Y-m-d H:i:s'),
            ];
            $totalAmount += floatval($invoice->Amount);
            $acfInvoiceLines = [];
            foreach ($invoice->InvoiceLines as $invoiceLine) {
                if (empty($invoiceLine->Title) || !isset($invoiceLine->Count) || !isset($invoiceLine->VatRateId) || !isset($invoiceLine->Amount)) {
                    error_log("[Nosun_Assumax_Api_Bookings->update_booking_meta]: One of the required InvoiceLine fields is missing.");
                    return false;
                }
                $acfInvoiceLines[] = [
                    'title' => $invoiceLine->Title,
                    'sub_title' => isset($invoiceLine->SubTitle) ? $invoiceLine->SubTitle : '',
                    'count' => intval($invoiceLine->Count),
                    'vat_rate_id' => $invoiceLine->VatRateId,
                    'amount' => $invoiceLine->Amount
                ];
            }
            $acfInvoice['lines'] = $acfInvoiceLines;
            $invoices[] = $acfInvoice;
        }
        update_field('booking_invoices', $invoices, $booking->get_id());

        // Send an e-mail for each important status change.
        self::send_status_update_email($booking, $data, $trip, $primaryCustomer, $primaryInvoice);
        update_field('booking_status', $data->Status, $booking->get_id());

        // Clear the booking items.
        $booking->remove_order_items();

        // Create a single item for the total amount.
        $totalFee = new WC_Order_Item_Fee();
        $totalFee->set_name("Totaal bedrag vanuit ERP");
        $totalFee->set_amount(wc_format_decimal($totalAmount));
        $totalFee->set_total(wc_format_decimal($totalAmount));
        $totalFee->set_tax_class('');
        $totalFee->set_tax_status('none');
        $booking->add_item($totalFee);
        $booking->calculate_totals();
        $booking->save();

        // Delete the old _nosun_booking_id should it exist and replace it with _assumax_id.
        if (get_post_meta($booking->get_id(), '_nosun_booking_id', true) && !empty($data->Id)) {
            update_post_meta($booking->get_id(), '_assumax_id', $data->Id);
            delete_post_meta($booking->get_id(), '_nosun_booking_id');
        }

        //


	    // Update the BookingReport.
	    do_action('vazquez_upsert_booking_report_data', $booking->get_id(), $data);

	    // Queue an upsert event for the Trip.
	    wp_schedule_single_event(time() + 3600, 'event_upsert_trip', [$data->TripId]);
	    return true;
    }

    /**
     * Upserts a row in the the booking<->customer pivot table.
     *
     * @param int $bookingAssumaxId The Assumax Id for the Booking.
     * @param int $customerAssumaxId The Assumax Id for the Customer.
     * @param bool $primary Whether or not the Customer is the primary Customer for the Booking.
     */
    private static function upsert_booking_customer_pivot_row(int $bookingAssumaxId, int $customerAssumaxId, bool $primary)
    {
        global $wpdb;
        $query = sprintf('INSERT INTO %s (booking_assumax_id, customer_assumax_id, `primary`) 
                            VALUES(%d,%d,%d)
                            ON DUPLICATE KEY UPDATE `primary` = %4$d;',
            "api_booking_customer_pivot", $bookingAssumaxId, $customerAssumaxId, $primary ? 1 : 0);
        if ($wpdb->query($query) === false) {
            error_log("[Api\Bookings->upsert_booking_customer_pivot_row]: Could not upsert the pivot data for Booking with Assumax Id: {$bookingAssumaxId} and Customer with Assumax Id: {$customerAssumaxId}.");
        }
    }

    /**
     * Puts the new booking to the API and returns the Booking API data should it be successful.
     *
     * @param mixed $data Booking data in the same form as the data from an API booking get request.
     * @return mixed|null Either the Booking data from the API, or null should something go wrong.
     */
	public static function insert_into_api($data) {
	    if (empty($data)) return null;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Bookings->insert_into_api]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return null;
        }
        $apiData = [];
        if (empty($data->TripId)) {
            error_log("[Nosun_Assumax_Api_Bookings->insert_into_api]: No trip id set in the data.");
            return null;
        }
        $apiData['TripId'] = $data->TripId;
        if (empty($data->Customers)) {
            error_log("[Nosun_Assumax_Api_Bookings->insert_into_api]: No customers set in the data.");
            return null;
        }
        foreach ($data->Customers as $customer) {
            if (empty($customer->Id)) {
                error_log("[Nosun_Assumax_Api_Bookings->insert_into_api]: One of the customers doesn't have an id.");
                return null;
            }
            if (!isset($apiData['Customers'])) $apiData['Customers'] = [];
            $apiData['Customers'][] = $customer->Id;
        }
        if (!isset($data->Option)) {
            $apiData['Option'] = 'false';
        } else {
            $apiData['Option'] = $data->Option ? 'true' : 'false';
        }
        if (isset($data->BoardingPoint)) {
            $apiData['BoardingPoint'] = $data->BoardingPoint;
        }
        if (isset($data->Note)) {
            $apiData['Note'] = $data->Note;
        }
        $bookingAPIData = $client->put('bookings', $apiData, true);
        return empty($bookingAPIData) ? null : $bookingAPIData;
	}

    /**
     * Updates the option flag, note and/or the boarding point on the booking at the API for the booking with the provided Assumax Id.
     * Only updates the fields that aren't null.
     *
     * @param string $assumaxId The Assumax Id of the booking.
     * @param null $option Boolean value determining if the booking is an option or not.
     * @param null $boardingPoint Optional new boarding point.
     * @param null $note Optional new note.
     * @return boolean true when the booking has successfully been updated at the API; false if otherwise.
     */
    public static function update_api_data($assumaxId, $option = null, $boardingPoint = null, $note = null) {
        if (empty($assumaxId)) return false;
        if (empty($boardingPoint) && empty($note)) return true;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Bookings->update_in_api]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return null;
        }
        $apiData = [];
        if (!empty($boardingPoint)) $apiData['BoardingPoint'] = $boardingPoint;
        if ($note !== null) $apiData['Note'] = $note;
        if ($option !== null) $apiData['Option'] = $option ? 'true' : 'false';
        return $client->post("/bookings/{$assumaxId}", $apiData, true) === false ? false : true;
    }

    /**
     * Sets the Booking (shop_order) with the provided Assumax Id to deleted.
     * Note: This will not actually hard delete the Booking (shop_order), this can be done manually in the admin interface.
     *
     * @param string $assumaxId The Assumax Id of the Booking (shop_order).
     * @return bool true when the Booking (shop_order) has successfully been set to deleted, false if otherwise.
     */
    public static function delete($assumaxId) {
        $product = self::get_by_assumax_id($assumaxId);
        if (!isset($product)) {
            error_log("[Nosun_Assumax_Api_Bookings->delete]: There exists no booking with Assumax Id: {$assumaxId}.]");
            return false;
        }
        if (empty(wp_delete_post($product->ID))) return false;
        return true;
    }

    /**
     * Callback function for the Booking (shop_order) webhook that should be called via a Post request by the Assumax ERP.
     *
     * @param string $assumaxId The Assumax id of the Booking (shop_order).
     * @param string $action The action to perform as supplied by Assumax.
     */
    public static function webhook_callback($assumaxId, $action) {
        do_action('vazquez_add_update_log_entry', 'Booking', $action, $assumaxId);

        if ($action === 'Created' || $action === 'Modified') {
            self::upsert($assumaxId);
        }
        elseif ($action === 'Deleted') {
            self::delete($assumaxId);
        } else {
            error_log("[Nosun_Assumax_Api_Bookings->webhook_callback]: Action: {$action} for id: {$assumaxId} is not supported.]");
        }
    }

    /**
     * Sends a new payment to the API for the specified booking using the specified parameters.
     *
     * @param int $assumaxId The Assumax Id of the booking for which to add a new payment.
     * @param string $description Description of the payment.
     * @param float $amount The amount that has been paid.
     * @param string $mollieReference The Mollie reference issued by the payment should it exist.
     * @note The API expects data in the following form:
     * {
     *   "DateTime": "2019-07-11T07:42:15.570Z",
     *   "Description": "string",
     *   "Amount": 0,
     *   "Reference": "string"
     * }
     * @return int|false The payment id should it have been added successfully or the boolean value false if otherwise.
     */
	public static function add_payment($assumaxId, $description, $amount, $mollieReference) {
        if (empty($assumaxId) || empty($description) || empty($amount)) return false;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Bookings->add_payment]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return false;
        }
        $dateTime = str_replace('+0000', '.000Z', date(DATE_ISO8601));
        $data = [
            'Description' => $description,
            'Amount' => number_format($amount, 2, '.', ''),
            'DateTime' => $dateTime
        ];
        if (isset($mollieReference)) $data['Reference'] = $mollieReference;
		$result = $client->put( 'bookings/payments/' . $assumaxId, $data);
		return $result === false ? false : intval($result);
	}

    /**
     * Obtains the total amount paid on the target booking.
     *
     * @param int $bookingPostId The post id of the booking.
     * @return float The total amount paid on the booking.
     */
	public static function get_total_paid_amount($bookingPostId) {
        if (empty($bookingPostId)) return 0;
        $payments = get_field('booking_payments', $bookingPostId);
        if (empty($payments) || !is_array($payments)) return 0;
        $totalPaidAmount = 0;
        foreach ($payments as $payment) {
            $totalPaidAmount += floatval(str_replace(',', '.', $payment['amount']));
        }
        return $totalPaidAmount;
    }

    /**
     * Obtains the combined value of the invoices.
     *
     * @param int $bookingPostId The post id of the booking.
     * @return array An empty array on error or the invoice amounts in the following format:
     * [
     *      'total' => float -> The total amount of the invoices
     *      'deposit' => float -> The deposit amount
     *      'payment' => float -> The payment amount
     * ]
     */
    public static function get_invoice_amounts($bookingPostId) {
        if (empty($bookingPostId)) return [];
        $invoices = get_field('booking_invoices', $bookingPostId);
        if (empty($invoices) || !is_array($invoices)) return [];
        $totalInvoiceAmount = 0;
        $depositAmount = 0;
        $paymentAmount = 0;
        foreach ($invoices as $invoice) {
            $totalInvoiceAmount += floatval(str_replace(',', '.', $invoice['amount']));
            $depositAmount += floatval(str_replace(',', '.', $invoice['deposit_amount']));
            $paymentAmount += floatval(str_replace(',', '.', $invoice['payment_amount']));
        }
        return [
            'total' => $totalInvoiceAmount,
            'deposit' => $depositAmount,
            'payment' => $paymentAmount
        ];
    }

    /**
     * Adds a cancellation insurance to the specified booking.
     *
     * @param int $assumaxId The Assumax Id of the booking for which to add the cancellation insurance.
     * @return bool true when the cancellation insurance has been successfully added, false if otherwise.
     */
	public static function add_cancellation_insurance($assumaxId) {
        return self::add_insurance($assumaxId, 'cancellationinsurance');
    }

    /**
     * Adds a trip insurance to the specified booking.
     *
     * @param int $assumaxId The Assumax Id of the booking for which to add the trip insurance.
     * @return bool true when the trip insurance has been successfully added, false if otherwise.
     */
    public static function add_trip_insurance($assumaxId) {
        return self::add_insurance($assumaxId, 'tripinsurance');
    }

    /**
     * Adds a snowmobile payoff to the specified booking.
     *
     * @param int $assumaxId The Assumax Id of the booking for which to add the snowmobile payoff.
     * @return bool true when the snowmobile payoff has been successfully added, false if otherwise.
     */
    public static function add_snowmobile_payoff($assumaxId) {
        return self::add_insurance($assumaxId, 'snowmobilepayoff');
    }

    /**
     * Adds a new insurance for the specified booking of the specified type.
     *
     * @param int $assumaxId The Assumax Id of the booking for which to add a new insurance.
     * @param string $type The type of insurance. Can currently be one of three options:
     *  - cancellationinsurance
     *  - tripinsurance
     *  - snowmobilepayoff
     * @return bool true when the insurance has been successfully added, false if otherwise.
     */
	private static function add_insurance($assumaxId, $type) {
	    if (empty($assumaxId) || empty($type)) return false;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Bookings->add_insurance]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return false;
        }
        $result = $client->put('bookings/' . $assumaxId . '/' . $type, []);
        return $result === false ? false : true;
	}

	/**
     * Adds several new columns to the booking (shop_order) post list on the admin screen.
     *
     * @param mixed $columns The current set of columns.
     * @return mixed The current set of columns modified to include the new columns.
     */
    public static function manage_booking_admin_columns($columns) {
        return [
            'cb' => $columns['cb'],
            'order_number' => __('Boeking', 'nosun_assumax'),
            'assumax_id' => __('Assumax Id', 'nosun_assumax'),
            'booking_type' => __('Boeking type', 'nosun_assumax'),
            'customers' => __('Reizigers', 'nosun_assumax'),
            'status' => __('Status', 'nosun_assumax'),
            'payment_status' => __('Betaalstatus', 'nosun_assumax'),
            'order_total' => __('Totaal', 'nosun_assumax'),
            'update_assumax' => __('Update uit Assumax', 'nosun_assumax')
        ];
    }

    /**
     * Fills in the extra booking (shop_order) columns depending on the column name provided.
     *
     * @param string $column The column name.
     * @param int $postId The post id of the booking (shop_order).
     */
    public static function manage_booking_admin_column($column, $postId) {
        switch ($column) {
            case 'assumax_id': {
                $assumaxId = get_post_meta($postId, '_assumax_id', true);
                echo $assumaxId;
                break;
            }
            case 'booking_type': {
                $bookingType = get_field('booking_is_option', $postId) ? "Option" : "Booking";
                echo $bookingType;
                break;
            }
            case 'customers': {
                $customers = get_field('booking_customers', $postId);
                if (!empty($customers)) {
                    foreach ($customers as $customer) {
                        echo "{$customer['id']} - {$customer['full_name']}<br>";
                    }
                }
                break;
            }
            case 'status': {
                $status = get_field('booking_status', $postId);
                echo $status;
                break;
            }
            case 'payment_status': {
                $status = get_field('booking_payment_status', $postId);
                echo $status;
                break;
            }
            case 'update_assumax': {
                $assumaxId = get_post_meta($postId, '_assumax_id', true);
                echo "<button class='update-assumax' data-assumax_id='{$assumaxId}' data-action='update_assumax_booking'>Update</button>";
                break;
            }
        }
    }

    /**
     * AJAX function that upserts the Booking with the Assumax Id provided in the POST global.
     */
    public static function ajax_update_booking() {
        if (!is_admin()) return;
        $assumaxId = $_POST['assumax_id'];
        if (empty($assumaxId)) return;
        $lock = Locks::acquire_lock($assumaxId, 'Booking', 'Modified', 1);
        if ($lock > 0) {
            static::upsert($assumaxId);
        }
        Locks::release_lock($lock);
    }

    /**
     * Changes the labels of the shop order post type to that of a booking.
     *
     * @param mixed $args The current order post arguments.
     * @return mixed The order post arguments with booking labels.
     */
    public static function custom_shop_order_labels($args) {
        $labels = array(
            'name' => __('Boekingen', 'nosun_custom_label'),
            'singular_name' => __('Boeking', 'nosun_custom_label'),
            'menu_name' => _x('Boekingen', 'Admin menu name', 'nosun_custom_label'),
            'add_new' => __('Nieuwe boeking toevoegen', 'nosun_custom_label'),
            'add_new_item' => __('Boeking toevoegen', 'nosun_custom_label'),
            'edit' => __('Boeking wijzigen', 'nosun_custom_label'),
            'edit_item' => __('Wijzigen', 'nosun_custom_label'),
            'new_item' => __('Toevoegen', 'nosun_custom_label'),
            'view' => __('Bekijk boeking', 'nosun_custom_label'),
            'view_item' => __('Bekijk', 'nosun_custom_label'),
            'search_items' => __('Zoeken', 'nosun_custom_label'),
            'not_found' => __('Geen boeking gevonden', 'nosun_custom_label'),
            'not_found_in_trash' => __('Geen boekingen gevonden', 'nosun_custom_label'),
            'parent' => __('Hoofditem', 'nosun_custom_label')
        );
        $args['labels'] = $labels;
        $args['description'] = __('Hier kan je boekingen beheren.', 'nosun_custom_label');
        return $args;
    }

    /**
     * Registers the booking confirmed order status.
     */
    public static function register_booking_confirmed_order_status() {
        register_post_status('wc-booking-confirmed', array(
            'label' => 'Boeking bevestigd',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Boeking bevestigd <span class="count">(%s)</span>', 'Boeking bevestigd <span class="count">(%s)</span>')
        ));
    }

    /**
     * Add the booking confirmed status to the woocommerce statuses right after processing.
     *
     * @param array $orderStatuses The current set of woocommerse statuses.
     * @return array The order statuses modified to contain wc-booking-confirmed.
     */
    public static function add_booking_confirmed_to_order_statuses($orderStatuses) {
        $newOrderStatuses = array();
        foreach ($orderStatuses as $key => $status) {
            $newOrderStatuses[$key] = $status;
            if ('wc-on-hold' === $key) {
                $newOrderStatuses['wc-booking-confirmed'] = 'Boeking bevestigd';
            }
        }
        return $newOrderStatuses;
    }

    /**
     * Downloads a document from the API using the parameters provided.
     *
     * @param int $bookingAssumaxId The Assumax id of the booking which holds the document.
     * @param int $documentId The Assumax id of the document.
     * @return null|string Either the document data or null should something go wrong.
     */
    public static function download_booking_document($bookingAssumaxId, $documentId) {
        if (empty($bookingAssumaxId) || empty($documentId) ||
            intval($bookingAssumaxId) === 0 || intval($documentId) === 0) return null;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Bookings->download_booking_document]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return null;
        }
        $document = $client->get("bookings/{$bookingAssumaxId}/document/{$documentId}", [], false, false);
        return ($document === false) ? null : $document;
    }

    /**
     * Creates an insurance field array which can be used to update the booking_insurances acf field.
     *
     * @param stdClass|bool $insuranceObject Either an object in the InsuranceModel format from the Assumax API or
     * a boolean value. This depends on what the Assumax API returns.
     * @param string $type The type of the insurance.
     * @return array An insurance field array in the following format:
     * [
     *      'type' => The provided type,
     *      'active' => bool -> Whether or not the insurance is currently active,
     *      'expiration' => string -> The datetime when the insurance can no longer be added,
     *      'discount' => float -> The discount that needs to be applied to this insurance,
     *      'discount_expiration' => string -> The datetime when the discount expires
     * ]
     */
    private static function create_insurance_field_array($insuranceObject, $type) {
        $insurance = [
            'type' => $type,
            'active' => '0',
            'expiration' => '',
            'discount' => 0,
            'discount_expiration' => ''
        ];
        if ($type === 'Cancellation' || $type === 'Trip') {
            $insurance['active'] = !empty($insuranceObject->Active) ? '1' : '0';
            if (!empty($insuranceObject->Expiration)) {
                $expiration = Helpers::create_local_datetime($insuranceObject->Expiration);
            }
            if (!empty($expiration)) $insurance['expiration'] = $expiration->format('Y-m-d H:i:s');
            $insurance['discount'] = floatval($insuranceObject->Discount) ?? 0.0;
            if (!empty($insuranceObject->DiscountExpiration)) {
                $discountExpiration = Helpers::create_local_datetime($insuranceObject->DiscountExpiration);
            }
            if (!empty($discountExpiration)) $insurance['expiration'] = $discountExpiration->format('Y-m-d H:i:s');
        } else {
            $insurance['active'] = $insuranceObject ? '1' : '0';
        }
        return $insurance;
    }

    /**
     * Triggers the status update e-mail events for booking state changes.
     *
     * @param WC_Order $booking The Booking order object.
     * @param mixed $data API data for the Booking.
     * @param mixed $trip Trip object.
     * @param mixed $customerId Assumax Id of the Customer.
     * @param mixed $invoiceId The Assumax Id of the Invoice.
     */
    private static function send_status_update_email(
        $booking,
        $data,
        $trip,
        $customerId,
        $invoiceId = 0): void
    {
        if (empty($booking) || empty($data) || empty($trip)) {
        	error_log('Booking->send_status_update_email - empty $booking, $data or $trip');
            return;
        }
        if ($data->Status === get_field('booking_status', $booking->get_id())) {
            return;
        }
        $customer = Customers::get_by_assumax_id($customerId);
        if (empty($customer)) {
        	error_log('Booking->send_status_update_email - empty($customer)');
            return;
        }
        $nickName = get_field('customer_nick_name', $customer->ID);
        $emailAddress = get_field('customer_email_address', $customer->ID);
        if (empty($emailAddress) || empty($nickName)) {
        	error_log('Booking->send_status_update_email - empty($emailAddress) or empty($nickName)');
            $booking->add_order_note("Kon geen e-mail versturen voor de status wijziging naar '{$data->Status}' omdat de reiziger nog niet bestaat.");
            return;
        }
        $invoiceFile = null;
        if (!empty($invoiceId)) {
            try {
                $invoiceFile = Customers::download_customer_invoice_file($customerId, $invoiceId);
            } catch (Exception $e) {
                $booking->add_order_note("Kon de factuur niet meesturen met de bevestigingsmail.");
            }
        }
        $events = [];
        if ($data->Status === 'Confirmed') {
            $events = Email::trigger_email_events(
                'booking_confirmed',
                [$emailAddress],
                [
                    'booking_id' => $booking->get_id(),
                    'booking_data' => $data,
                    'trip' => $trip,
                    'nickname' => $nickName
                ],
                "{$data->Id}_{$data->Status}",
                empty($invoiceFile) ? [] : [$invoiceFile]
            );
        } elseif ($data->Status === 'Cancelled') {
        	// TODO: In case the cancelled status email needs to come back
	        error_log("[Bookings->send_status_update_email] Email Cancelled has been triggered for booking: {$booking->get_id()}");
//            $events = Email::trigger_email_events('booking_cancelled', [$emailAddress], [
//                'booking_id' => $booking->get_id(),
//                'booking_data' => $data,
//                'trip' => $trip,
//                'nickname' => $nickName
//            ], "{$data->Id}_{$data->Status}");
        } elseif ($data->Status === 'Done') {
        	// TODO: this has been moved to the Emails webhook
	        error_log("[Bookings->send_status_update_email] Email Done has been triggered for booking: {$booking->get_id()}");
//            $events = Email::trigger_email_events('booking_done', [$emailAddress], [
//                'booking_id' => $booking->get_id(),
//                'booking_data' => $data,
//                'trip' => $trip,
//                'nickname' => $nickName
//            ], "{$data->Id}_{$data->Status}");
        }
        if (!empty($events)) {
            foreach ($events as $eventId => $result) {
                if ($result === false) {
                    $booking->add_order_note("Er ging iets fout met het versturen van een e-mail voor de status wijziging naar '{$data->Status}' voor e-mail adres: {$emailAddress}.");
                } else {
                    $booking->add_order_note("Status wijziging e-mail voor status: '{$data->Status}' verzonden naar e-mail adres: {$emailAddress}.");
                }
            }
        }
    }

    /**
     * Puts new webhooks to the API.
     *
     * @param AssumaxClient $client A valid client object to do request on.
     * @param string $url The base url for the webhooks.
     */
    public static function vazquez_webhooks_setup(AssumaxClient $client, string $url) : void
    {
        $result = $client->put("/webhooks", [
            "Item" => "Booking",
            "Action" => "Created",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Bookings->vazquez_webhooks_setup]: Could not add the Created action webhook for the Bookings class.");
        }
        $result = $client->put("/webhooks", [
            "Item" => "Booking",
            "Action" => "Modified",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Bookings->vazquez_webhooks_setup]: Could not add the Modified action webhook for the Bookings class.");
        }
        $result = $client->put("/webhooks", [
            "Item" => "Booking",
            "Action" => "Deleted",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Bookings->vazquez_webhooks_setup]: Could not add the Deleted action webhook for the Bookings class.");
        }
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_filter('manage_shop_order_posts_columns', [self::class, 'manage_booking_admin_columns'], 20);
        $loader->add_action('manage_shop_order_posts_custom_column', [self::class, 'manage_booking_admin_column'], 20, 2);
        $loader->add_filter('woocommerce_register_post_type_shop_order', [self::class, 'custom_shop_order_labels']);
        $loader->add_action('wp_ajax_update_assumax_booking', [self::class, 'ajax_update_booking']);
        $loader->add_action('init', [self::class, 'register_booking_confirmed_order_status']);
        $loader->add_filter('wc_order_statuses', [self::class, 'add_booking_confirmed_to_order_statuses']);
        $loader->add_action('vazquez_webhooks_setup', [self::class, 'vazquez_webhooks_setup'], 10, 2);
    }
}
