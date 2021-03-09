<?php

namespace Vazquez\NosunAssumaxConnector\Api;

use lib\controllers\Email;
use lib\controllers\User;

/**
 * This class was used to deal with E-mail from Assumax
 * Check the API documentation should you find the need to implement it here.
 *
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl> & Jeroen Venderbosch <jeroen@vazquez.nl>
 */
class Emails implements ILoadable {
	/**
	 * @inheritDoc
	 */
	public static function load($loader): void {
		$loader->add_action('vazquez_webhooks_setup', [self::class, 'vazquez_webhooks_setup'], 10, 2);
	}

	/**
	 * Upserts the template with the provided id if the action is either 'Created' or 'Modified'.
	 * Deletes the template if the action is 'Deleted'.
	 *
	 * @param string $assumaxId The Assumax id of the template.
	 * @param string $action The action to perform as supplied by Assumax.
	 */
	public static function webhook_callback($assumaxId, $action)
	{

		// TODO: make this prettier and maybe more dynamic. i.e. make different functions for different email types etc.

		if(empty($assumaxId) || empty($action) || $action !== 'Created') return;

		$client = AssumaxClient::getInstance();
		// First delete all the old webhooks to be sure we have no double executions.
		$pendingEmails = $client->get('/emails/pending');
		$indexed = [];
		if(!empty($pendingEmails)) {
			foreach($pendingEmails as $pendingEmail) {
				if(in_array($assumaxId, (array)$pendingEmail)) {
					$indexed[] = (array)$pendingEmail;
				}
			}
		}

		if(!empty($indexed)) {
			if(is_array($indexed)) {
				foreach($indexed as $index) {
					self::sendWelcomeHomeEmail($client, $index );
				}
			} else if(!key_exists( 0, $indexed)) {
				return;
			} else {
				if(key_exists(0, $indexed)) {
					self::sendWelcomeHomeEmail($client, $indexed[0]);
				}
			}
		}
	}

	/**
	 * Sends the WelcomeHomeEmail
	 *
	 * @param $indexed
	 */
	private static function sendWelcomeHomeEmail($client, $indexed)
	{
		if(empty($client) || empty($indexed) || !key_exists( 'Id', $indexed) || !key_exists( 'TripId', $indexed) || !key_exists( 'EmailType', $indexed) || !key_exists( 'Customers', $indexed)) return;

		$emailId = $indexed['Id']; // Get the email Id
		$tripId = $indexed['TripId']; // Get the trip Id that belongs to this email action
		$tripEntryId = $indexed['TripEntryId']; // not sure what to do with this
		$emailType = $indexed['EmailType']; // Get the email type
		$customers = $indexed['Customers']; // Get the contacts for this email action
		$customerObjects = [];

		if($emailType !== 'WelcomeHome') return; // We want to cancel if it's not the WelcomeHome email

		// Check to see whether the email action has contacts selected
		if(!empty($customers)) {
			foreach($customers as $customer) {
				$customer = (array)$customer; // Cast the object to an array

				// build new customerObj array
				$customerObj = [];
				$customerObj['Id'] = $customer['Id'];
				$customerObj['Name'] = $customer['Name'];
				$customerObj['EmailAddress'] = $customer['EmailAddress'];
				$customerObj['Trip'] = Trips::get_by_assumax_id( $tripId );
				$customerObj['CustomerPost'] = Customers::get_by_assumax_id( $customer['Id'] );
				$customerPost = $client->get('/customers/'.$customer['Id']);
				$customerBookings = (array)$customerPost->Bookings; // cast the bookings object to an array
				$bookingId = false;
				foreach($customerBookings as $key => $customerBooking) {
					// We only want the booking_id if the tripId === the booking->tripId
					if($customerBooking->TripId === $tripId) {
						$bookingId = $customerBooking->Id;
					}
				}
				$customerObj['CustomerBookingId'] = $bookingId;

				// append the new customerObj array to the customerObjects array
				$customerObjects[] = $customerObj;

			}
		}

		// Only execute this action for the WelcomeHome email
		if($emailType === 'WelcomeHome') {
			foreach($customerObjects as $key => $customerObject) {

				$trigger = 'booking_done';
				$booking_id = $customerObject['CustomerBookingId'];
				$booking_data = $client->get("/bookings/{$booking_id}");
				$trip = $customerObject['Trip'];
				$emailAddress = $customerObject['EmailAddress'];
				$nickName = get_field('customer_nick_name', Customers::get_by_assumax_id($customerObject['Id']));
				$status = 'Done';
				$singletonValue = $booking_data->Id.'_'.$status;
				$email_sent = false;

				// Abort if any of these variables are empty
				if(empty($booking_id) || empty($booking_data) || empty($trip) || empty($emailAddress) || empty($nickName)) return;

				// Check to see whether this specific email has already been sent or not
				$email_has_been_sent = Email::email_event_has_been_sent( $trigger, [$emailAddress], $singletonValue);

				// If the email hasn't been sent yet, send it.
				if(!$email_has_been_sent) {
					$email_sent = Email::trigger_email_events( $trigger, [ $emailAddress ], [
						'booking_id'   => $booking_id,
						'booking_data' => $booking_data,
						'trip'         => $trip,
						'nickname'     => $nickName
					], "{$singletonValue}" );
				}

				// Mark the email as sent in Assumax
				if(!empty($email_sent)) {
					$client->post("/emails/{$emailId}/sent");
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
			"Item" => "Email",
			"Action" => "Created",
			"Url" => $url
		], true, false);
		if ($result === false) {
			error_log("[Emails->vazquez_webhooks_setup]: Could not add the Created action webhook for the Emails class.");
		}
		$result = $client->put("/webhooks", [
			"Item" => "Email",
			"Action" => "Modified",
			"Url" => $url
		], true, false); // Not sure if we need this modified action
		if ($result === false) {
			error_log("[Emails->vazquez_webhooks_setup]: Could not add the Modified action webhook for the Emails class.");
		}
	}
}
