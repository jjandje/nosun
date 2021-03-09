<?php /** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */

namespace lib\controllers;

use DateInterval;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Exception;
use Roots\Sage\Helpers;
use Roots\Sage\Helpers as NosunHelpers;
use stdClass;
use Vazquez\NosunAssumaxConnector\Api\Bookings;
use Vazquez\NosunAssumaxConnector\Api\Customers;
use Vazquez\NosunAssumaxConnector\Api\Locks;
use Vazquez\NosunAssumaxConnector\Api\Products;
use Vazquez\NosunAssumaxConnector\Helpers as ApiHelpers;

/**
 * Holds all the functionality pertaining to the modification of bookings.
 *
 * Class Booking
 * @package lib\controllers
 */
class Booking {
    /**
     * Cache objects
     */
    public static $bookingCustomers = [];

    /**
     * Redirects to the provided url with a query var named 'error' which hold the provided message and sets
     * a cookie named 'previous_data' to hold the previous post data.
     * Exits afterwards to complete the redirection.
     *
     * @param string $url The url to redirect towards.
     * @param string $message The error message to display.
     * @param mixed $postData Optional, the previously submitted post data.
     */
    private static function redirect_on_error($url, $message, $postData = []) {
        if (!empty($postData)) {
            $serialized = maybe_serialize($postData);
            $key = Key::loadFromAsciiSafeString(NOSUN_CRYPTO_KEY);
            if (!empty($key)) {
                $encrypted = Crypto::encrypt($serialized, $key);
                setcookie('previous_data', $encrypted, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
            }
        }
        wp_safe_redirect(add_query_arg('err', urlencode($message), $url));
        exit;
    }

    /**
     * Does several checks to determine if the booking can be created and if all of them pass,
     * then a new booking is created for the user using the values supplied in the POST request.
     * For each of the customers that do not already have an account, a new account will be created.
     */
    public static function new_booking() {
        nocache_headers();
        if ('POST' !== $_SERVER['REQUEST_METHOD'] || !isset($_REQUEST['submit_form']) || $_REQUEST['submit_form'] !== 'new_booking') {
            wp_die('Geen toegang', 403);
        }
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'new_booking')) {
            wp_die('Geen toegang', 403);
        }
        // Check if the product_id is that for a Trip that can be booked.
        $tripPostId = $_POST['product_id'];
        if (empty($tripPostId)) {
            wp_safe_redirect(get_home_url());
            exit;
        }
        $tripPost = get_post($tripPostId);
        if (empty($tripPost)) {
            wp_safe_redirect(get_home_url());
            exit;
        }
        $tripStatus = get_field('trip_status', $tripPostId);
        if (empty($tripStatus) || $tripStatus === 'Unavailable') {
            self::redirect_on_error(get_the_permalink($tripPost), 'Deze reis is helaas niet beschikbaar om te boeken.');
        }
        $tripAssumaxId = get_post_meta($tripPostId, '_assumax_id', true);
        if (empty($tripAssumaxId)) {
            self::redirect_on_error(get_the_permalink($tripPost), 'Deze reis is helaas niet beschikbaar om te boeken.');
        }
        $tripPrice = floatval(get_post_meta($tripPostId, '_price', true));
        if (empty($tripPrice)) {
            self::redirect_on_error(get_the_permalink($tripPost), 'Deze reis is helaas niet beschikbaar om te boeken.');
        }
        if (empty($_POST['customer']) || !is_array($_POST['customer'])) {
            self::redirect_on_error(get_the_permalink($tripPost), 'Er is iets mis gegaan tijdens het verwerken van je gegevens. Neem alsjeblieft contact met ons op.');
        }
        if ($_POST['algemenevoorwaarden'] !== 'on') {
            self::redirect_on_error(get_the_permalink($tripPost), 'De algemene voorwaarden dienen geaccepteerd te worden.', $_POST);
        }
        $geboektPage = get_page_by_path('geboekt');
        if (empty($geboektPage)) {
            self::redirect_on_error(get_the_permalink($tripPost), 'Er is iets mis gegaan tijdens het ophalen van de reisgegevens. Neem alsjeblieft contact met ons op.', $_POST);
        }
        $isOption = !empty($_POST['optie']);
        // Create a new booking and insert it into the API.
        $booking = new stdClass();
        $booking->TripId = $tripAssumaxId;
        $booking->Option = $isOption;
        try {
            $customerData = self::handle_customer_post_data($_POST['customer']);
        } catch (Exception $e) {
            self::redirect_on_error(get_the_permalink($tripPost), $e->getMessage(), $_POST);
        }
        if (empty($customerData)) {
            error_log("[Booking->new_booking]: No customers available after handling the post data.");
            self::redirect_on_error(get_the_permalink($tripPost), 'Er is iets fout gegaan tijdens het verwerken van je boeking. Neem alsjeblieft contact met ons op.', $_POST);
        }
        if (!self::handle_customer_upserts($customerData)) {
            error_log("[Booking->new_booking]: Something went wrong while trying to upsert the customers.");
            self::redirect_on_error(get_the_permalink($tripPost), 'Er is iets fout gegaan tijdens het verwerken van je boeking. Neem alsjeblieft contact met ons op.', $_POST);
        }
        $booking->Customers = $customerData;

        // Insert the Booking into the API.
        $bookingAPIData = Bookings::insert_into_api($booking);
        if (empty($bookingAPIData)) {
            error_log("[Booking->new_booking]: Could not insert the booking into the API.");
            self::redirect_on_error(get_the_permalink($tripPost), 'Er is iets fout gegaan tijdens het verwerken van je boeking. Neem alsjeblieft contact met ons op.', $_POST);
        }
        // Sync the website data with the new API state.
        $lock = Locks::acquire_lock($bookingAPIData->Id, 'Booking', 'Created', true);
        if ($lock && Bookings::upsert($bookingAPIData->Id, $bookingAPIData) === false) {
            Locks::release_lock($lock);
            error_log("[Booking->new_booking]: Could not sync the booking data.");
            self::redirect_on_error(get_the_permalink($tripPost), 'Er is iets fout gegaan tijdens het verwerken van je boeking. Neem alsjeblieft contact met ons op.', $_POST);
        }
        Locks::release_lock($lock);
        // Safe redirect to the booked page which lets the customer update several other fields.
        update_user_meta(get_current_user_id(), '_new_booking_assumax_id', $bookingAPIData->Id);
        setcookie('previous_data', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        wp_safe_redirect(get_the_permalink($geboektPage));
        exit;
    }

    /**
     * Parses the customer post data.
     * Logs in the main customer should the customer not already be logged in.
     *
     * @param array $customerPostData Customer post data from the booking submit form.
     * @return array Array of objects containing customer data.
     * @throws Exception When something goes wrong an Exception is thrown with an error message.
     */
    private static function handle_customer_post_data($customerPostData) {
        $customers = [];
        // Sanitize the customer form input.
        foreach ($customerPostData as $i => $customer) {
            if ($i === 'NUMBER') continue;
            foreach ($customer as $key => $value) {
                $customers[$i][$key] = sanitize_text_field($value);
            }
        }
        // For each of the customers try to upsert them.
        $customerObjects = [];
        for ($i = 0; $i < count($customers); $i++) {
            $customer = new stdClass();
            if (empty($customers[$i]['first_name']) || empty($customers[$i]['nickname'])
                || empty($customers[$i]['last_name']) || empty($customers[$i]['birthdate'])) {
                if ($i === 0) throw new Exception("Één of meerdere verplichte velden zijn niet ingevuld bij de hoofdboeker.");
                else throw new Exception("Één of meerdere verplichte velden zijn niet ingevuld bij extra reiziger {$i}.");
            }
            $assumaxId = null;
            if ($i === 0) {
                if (is_user_logged_in()) { // Use the existing account and check if a Customer exists for it.
                    $userId = get_current_user_id();
                    $userInfo = get_userdata($userId);
                    $mainCustomerPost = Customers::get_by_user_id($userId);
                    if (!empty($mainCustomerPost)) {
                        $assumaxId = get_post_meta($mainCustomerPost->ID, '_assumax_id', true);
                        if (empty($assumaxId)) {
                            error_log("[Booking->handle_customer_post_data]: The Customer post for user id {$userId} does not contain an Assumax Id.");
                            throw new Exception("Er is iets mis gegaan tijdens het verwerken van je gegevens, neem alsjeblieft contact met ons op.");
                        }
                    }
                    $customer->EmailAddress = $userInfo->user_email;
                    $customer->UserId = $userId;
                } else { // The user doesn't have an account, create a new one and log it in.
                    if (empty($customers[$i]['email'])) {
                        throw new Exception("Er is geen e-mail adres ingevuld voor de hoofdboeker.");
                    }
                    $userCredentials = User::create_new_account($customers[$i]['first_name'], $customers[$i]['last_name'],
                                                                    $customers[$i]['nickname'], $customers[$i]['email']);
                    $user = wp_signon($userCredentials, false);
                    if (is_wp_error($user)) {
                        throw new Exception("Er is iets mis gegaan tijdens het automatisch inloggen van je nieuwe account, probeer zelf handmatig in te loggen met de gegevens die je per e-mail hebt ontvangen.");
                    }
                    wp_set_current_user($user->ID);
                    $customer->EmailAddress = $customers[$i]['email'];
                    $customer->UserId = $user->ID;
                }
                $customer->Primary = true;
            }
            else {
                // Check if an encrypted id is present for this Customer signifying that a Customer already exists.
                if (!empty($customers[$i]['customer_id'])) {
                    $key = Key::loadFromAsciiSafeString(NOSUN_CRYPTO_KEY);
                    try {
                        $assumaxId = Crypto::decrypt($customers[$i]['customer_id'], $key);
                    } /** @noinspection PhpRedundantCatchClauseInspection */
                    catch (WrongKeyOrModifiedCiphertextException $e) {
                        error_log("[Booking->handle_customer_post_data]: {$e->getMessage()}");
                        throw new Exception("Er is iets mis gegaan tijdens het verwerken van je gegevens, neem alsjeblieft contact met ons op.");
                    }
                } else {
                    if (empty($customers[$i]['email'])) {
                        throw new Exception("Er is geen e-mail adres ingevuld voor extra reiziger {$i}.");
                    }
                    if (!filter_var($customers[$i]['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Het ingevulde e-mail adres voor extra reiziger {$i} is niet geldig.");
                    }
                    $customer->EmailAddress = $customers[$i]['email'];
                }
                $customer->Primary = false;
            }
            if (!empty($assumaxId)) $customer->Id = $assumaxId;
            $customer->FirstName = $customers[$i]['first_name'];
            $customer->LastName = $customers[$i]['last_name'];
            $customer->NickName = $customers[$i]['nickname'];
            $customer->DateOfBirth = $customers[$i]['birthdate'];
            if (!empty($customers[$i]['phone'])) $customer->PhoneNumber = $customers[$i]['phone'];
            $customerObjects[$i] = $customer;
        }
        return $customerObjects;
    }

    /**
     * @param array $customerData An array of customers that is in the same format needed to put/post data to the API.
     * The handle_customer_post_data method supplies the correct format.
     * @return bool true when all upserts have been handled; false if otherwise.
     */
    private static function handle_customer_upserts(array $customerData) : bool
    {
        $key = Key::loadFromAsciiSafeString(NOSUN_CRYPTO_KEY);
        if (empty($key)) {
            error_log("[Booking->handle_customer_upserts]: The cryptographic key can't be loaded.");
            return false;
        }
        foreach ($customerData as $i => $customerDataEntry) {
            if (empty($customerDataEntry->Id)) {
                $customerAPIData = Customers::insert_into_api($customerDataEntry);
                if (empty($customerAPIData)) {
                    error_log("[Booking->handle_customer_upserts]: Could not insert the customer into the API.");
                    return false;
                }
                $lock = Locks::acquire_lock($customerAPIData->Id, 'Customer' ,'Created', true);
                if ($lock && Customers::upsert($customerAPIData->Id, $customerAPIData) === false) {
                    Locks::release_lock($lock);
                    error_log("[Booking->handle_customer_upserts]: Could not insert the customer data for Assumax Id: {$customerDataEntry->Id}.");
                    return false;
                }
                Locks::release_lock($lock);
                $customerDataEntry->Id = $customerAPIData->Id;
                // Save the encrypted assumax id in the POST data to make sure it is available on an error redirect.
                $_POST['customer'][$i]['customer_id'] = Crypto::encrypt(strval($customerDataEntry->Id), $key);
            } else {
                $lock = Locks::acquire_lock($customerDataEntry->Id, 'Customer' ,'Modified', true);
                if (empty(Customers::update_api_data($customerDataEntry->Id, $customerDataEntry))) {
                    error_log("[Booking->handle_customer_upserts]: Could not update the customer in the API.");
                    return false;
                }
                if ($lock && Customers::upsert($customerDataEntry->Id) === false) {
                    Locks::release_lock($lock);
                    error_log("[Booking->handle_customer_upserts]: Could not update the customer data for Assumax Id: {$customerDataEntry->Id}.");
                    return false;
                }
                Locks::release_lock($lock);
            }
            if (isset($customerDataEntry->UserId)) {
                if (empty(get_user_meta($customerDataEntry->UserId, 'user_customer', true))) {
                    update_user_meta($customerDataEntry->UserId, 'customer_assumax_id', $customerDataEntry->Id);
                }
            } else {
                // Send a registration mail for the newly created extra Customer.
                $events = Email::trigger_email_events('new_extra_customer', [$customerDataEntry->EmailAddress], ['customer' => $customerDataEntry]);
                if (!empty($events)) {
                    foreach ($events as $eventId => $result) {
                        if ($result === false) {
                            error_log("[Booking->handle_customer_upserts]: Could not send a new account mail to the extra customer with id: {$customerDataEntry->Id}, e-mail address: {$customerDataEntry->EmailAddress} for event: {$eventId}.");
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * Get the current customers for the target booking.
     *
     * @param int $bookingPostId The post ID of the booking.
     * @return array An empty array if something goes wrong or an array in the following format:
     * [
     *      assumax_id => boolean
     * ]
     */
    public static function get_booking_customers($bookingPostId) {
        if (empty($bookingPostId)) {
            return [];
        }
        if (key_exists($bookingPostId, self::$bookingCustomers)) {
            return self::$bookingCustomers[$bookingPostId];
        }
        $bookingAssumaxId = intval(get_post_meta($bookingPostId, '_assumax_id', true));
        if (empty($bookingAssumaxId)) {
            return self::$bookingCustomers[$bookingPostId] = [];
        }
        global $wpdb;
        $query = "SELECT customer_assumax_id, `primary` 
                    FROM api_booking_customer_pivot 
                    WHERE booking_assumax_id = {$bookingAssumaxId}
                    ORDER BY `primary` DESC;";
        $results = $wpdb->get_results($query);
        if (empty($results)) {
            return self::$bookingCustomers[$bookingPostId] = [];
        }
        $customers = [];
        foreach ($results as $result) {
            $customers[$result->customer_assumax_id] = intval($result->primary) === 1;
        }
        return self::$bookingCustomers[$bookingPostId] = $customers;
    }

    /**
     * Checks whether or not the user has access to the booking.
     *
     * @param int $bookingPostId The post id of the Booking.
     * @param int $userId The user id.
     * @return int 0 when the user doesn't have access; 1 when the user has access; 2 when the user has access and is
     * the primary user.
     */
    public static function user_has_access($bookingPostId, $userId)
    {
        if (empty($bookingPostId) || empty($userId)) {
            return 0;
        }
        $bookingAssumaxId = intval(get_post_meta($bookingPostId, '_assumax_id', true));
        if (empty($bookingAssumaxId)) {
            return 0;
        }
        $currentCustomer = Customers::get_by_user_id($userId);
        if (empty($currentCustomer)) {
            return 0;
        }
        $currentCustomerAssumaxId = get_post_meta($currentCustomer->ID, '_assumax_id', true);
        if (empty($currentCustomerAssumaxId)) {
            return 0;
        }
        global $wpdb;
        $query = "SELECT `primary` FROM api_booking_customer_pivot WHERE booking_assumax_id={$bookingAssumaxId} LIMIT 1;";
        $primary = $wpdb->get_var($query);
        if ($primary === null) {
            return 0;
        }
        return $primary === 0 ? 1 : 2;
    }

    /**
     * Obtains the details data for the booking setup in the query vars.
     * Access should be denied when this function returns an empty array.
     * Tries to blend previously saved cookie data with data currently set in the database.
     *
     * @return array An empty array when access should be denied and an array in the following format when data can be
     * obtained.
     * array => [
     *      encryptedBookingId,
     *      customerObjects,
     *      boardingPoints,
     *      selectedBoardingPoint
     * ]
     */
    public static function get_booking_details_data() : array
    {
        if (!is_user_logged_in()) {
            return [];
        }
        $key = Key::loadFromAsciiSafeString(NOSUN_CRYPTO_KEY);
        if (empty($key)) {
            return [];
        }
        $assumaxId = sanitize_text_field(get_query_var('boeking'));
        $bookingPost = Bookings::get_by_assumax_id($assumaxId);
        if (empty($bookingPost)) {
            return [];
        }
        if (Booking::user_has_access($bookingPost->ID, get_current_user_id()) !== 2) {
            return [];
        }
        $bookingStatus = get_field('booking_status', $bookingPost->ID);
        if ($bookingStatus !== 'New' && $bookingStatus !== 'Confirmed') {
            return [];
        }
        $tripId = get_field('booking_trip', $bookingPost->ID);
        if (empty($tripId)) {
            return [];
        }
        $templateId = get_field('trip_template', $tripId);
        if (empty($templateId)) {
            return [];
        }
        $isOption = get_field('booking_is_option', $bookingPost->ID);
        $today = Helpers::today();
        $tripStartDateTime = Helpers::create_local_datetime(get_field('trip_start_date', $tripId));
        $bookingDateTime = Helpers::create_local_datetime(get_field('booking_date', $bookingPost->ID));
        if (empty($tripStartDateTime) || empty($bookingDateTime)) {
            return [];
        }
        $tripStartDateTime->sub(new DateInterval('P1D'));
        $bookingDateTime->add(new DateInterval('P7D'));
        $difference = $today->diff($bookingDateTime);
        if ($today >= $tripStartDateTime || ($isOption && $difference->invert === 1)) {
            return [];
        }
        // Obtain the customer data.
        $customers = Booking::get_booking_customers($bookingPost->ID);
        if (empty($customers)) {
            return [];
        }
        $cookieData = null;
        if (!empty($_COOKIE['previous_data'])) {
            try {
                $decrypted = Crypto::decrypt($_COOKIE['previous_data'], $key);
                $cookieData = maybe_unserialize($decrypted);
            } /** @noinspection PhpRedundantCatchClauseInspection */
            catch (WrongKeyOrModifiedCiphertextException $e) {
                error_log("[Booking->get_booking_details_data]: {$e->getMessage()}");
                return [];
            }
        }
        if (!is_array($cookieData)) {
            $cookieData = ['customers' => []];
            foreach ($customers as $customer => $primary) {
                $cookieData['customers'][] = ['user_id' => $customer];
            }
        }
        try {
            $customerObjects = self::get_booking_details_customers($customers, $cookieData['customers'], true);
        } catch (Exception $e) {
            error_log("[Booking->get_booking_details_data]: {$e->getMessage()}");
            return [];
        }
        // Obtain the boarding points.
        $boardingPoints = get_field('template_boarding_points', $templateId);
        if (!empty($boardingPoints)) {
            $boardingPoints = array_column($boardingPoints, 'title');
        }
        $encryptedBookingId = Crypto::encrypt(strval($assumaxId), $key);
        return [
            $encryptedBookingId,
            $customerObjects,
            $boardingPoints,
            $cookieData['boarding_place'] ?? get_field('booking_boarding_point', $bookingPost->ID)
        ];
    }

    /**
     * Parses the booking details post data and updates the customers and booking.
     */
    public static function update_booking_details() {
        nocache_headers();
        if ('POST' !== $_SERVER['REQUEST_METHOD'] || !isset($_REQUEST['submit_form']) || $_REQUEST['submit_form'] !== 'update_booking_details') {
            wp_die('Geen toegang', 403);
        }
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update_booking_details')) {
            wp_die('Geen toegang', 403);
        }
        if (!is_user_logged_in()) {
            wp_safe_redirect(site_url());
            exit;
        }
        $boekingGegevensWijzigenPage = get_page_by_path('boeking-gegevens-wijzigen');
        $myAccountPage = get_page_by_path('mijn-account');
        if (empty($boekingGegevensWijzigenPage) || empty($myAccountPage)) {
            wp_safe_redirect(get_home_url());
            exit;
        }
        if (empty($_POST['booking_id'])) {
            wp_safe_redirect(get_home_url());
            exit;
        }
        $key = Key::loadFromAsciiSafeString(NOSUN_CRYPTO_KEY);
        try {
            $assumaxId = Crypto::decrypt($_POST['booking_id'], $key);
        } /** @noinspection PhpRedundantCatchClauseInspection */
        catch (WrongKeyOrModifiedCiphertextException $e) {
            error_log("[Booking->update_booking_details]: {$e->getMessage()}");
            wp_safe_redirect(get_home_url());
            exit;
        }
        $bookingPost = Bookings::get_by_assumax_id($assumaxId);
        if (empty($bookingPost)) {
            wp_safe_redirect(get_home_url());
            exit;
        }
        if (self::user_has_access($bookingPost->ID, get_current_user_id()) !== 2) {
            self::redirect_on_error(get_the_permalink($boekingGegevensWijzigenPage), 'Je hebt niet de rechten om deze boeking aan te passen.', $_POST);
        }
        $booking = wc_get_order($bookingPost);
        if (empty($booking)) {
            wp_safe_redirect(get_home_url());
            exit;
        }
        // Customers.
        $bookingCustomers = self::get_booking_customers($bookingPost->ID);
        if (empty($bookingCustomers)) {
            self::redirect_on_error(get_the_permalink($boekingGegevensWijzigenPage), 'Je hebt niet de rechten om deze boeking aan te passen.', $_POST);
        }
        $customers = [];
        // Sanitize the customer form input.
        foreach ($_POST['customer'] as $i => $customer) {
            foreach ($customer as $key => $value) {
                $customers[$i][$key] = sanitize_text_field($value);
            }
        }
        $bookingStatus = get_field('booking_status', $bookingPost->ID);
        if ($bookingStatus !== 'New' && $bookingStatus !== 'Confirmed') {
            self::redirect_on_error(get_the_permalink($boekingGegevensWijzigenPage), 'Deze boeking kan niet langer aangepast worden.', $_POST);
        }
        $tripId = get_field('booking_trip', $bookingPost->ID);
        if (empty($tripId)) {
            self::redirect_on_error(get_the_permalink($boekingGegevensWijzigenPage), 'Er is iets fout gegaan tijdens het wijzigen van de gegevens. Neem alsjeblieft contact met ons op.', $_POST);
        }
        $templateId = get_field('trip_template', $tripId);
        if (empty($templateId)) {
            self::redirect_on_error(get_the_permalink($boekingGegevensWijzigenPage), 'Er is iets fout gegaan tijdens het wijzigen van de gegevens. Neem alsjeblieft contact met ons op.', $_POST);
        }
        $boardingPoints = get_field('template_boarding_points', $templateId);
        if (!empty($boardingPoints)) {
            $boardingPoints = array_column($boardingPoints, 'title');
            if (empty($_POST['boarding_place']) || !in_array($_POST['boarding_place'], $boardingPoints)) {
                self::redirect_on_error(get_the_permalink($boekingGegevensWijzigenPage), 'Er dient een vertrekpunt geselecteerd te worden.', $_POST);
            }
        }
        // Create customer data in API form to allow for upsertion.
        $customerObjects = [];
        try {
            $customerObjects = self::get_booking_details_customers($bookingCustomers, $customers);
        } catch (Exception $e) {
            self::redirect_on_error(get_the_permalink($boekingGegevensWijzigenPage), $e->getMessage(), $_POST);
        }
        foreach ($customerObjects as $customerObject) {
            $lock = Locks::acquire_lock($customerObject->Id, 'Customer', 'Modified', true);
            if (empty(Customers::update_api_data($customerObject->Id, $customerObject))) {
                if ($lock > 0) {
                    Locks::release_lock($lock);
                }
                error_log("[Booking->update_booking_details]: Could not update the API data for the customer with Assumax Id: {$customerObject->Id}.");
                self::redirect_on_error(get_the_permalink($boekingGegevensWijzigenPage), 'Er is iets fout gegaan tijdens het wijzigen van de gegevens. Neem alsjeblieft contact met ons op.', $_POST);
            }
            if ($lock > 0) {
                $upsertResult = Customers::upsert($customerObject->Id);
                Locks::release_lock($lock);
                if ($upsertResult === false) {
                    error_log("[Booking->update_booking_details]: Could not upsert the customer with Assumax Id: {$customerObject->Id}.");
                    self::redirect_on_error(get_the_permalink($boekingGegevensWijzigenPage), 'Er is iets fout gegaan tijdens het wijzigen van de gegevens. Neem alsjeblieft contact met ons op.', $_POST);
                }
            }
        }
        // Set the boarding point on the booking.
        $lock = Locks::acquire_lock($assumaxId, 'Booking', 'Modified', true);
        if (!Bookings::update_api_data(
            $assumaxId,
            null,
            sanitize_text_field($_POST['boarding_place'] ?? ''),
            sanitize_text_field($_POST['booking_note'] ?? '')))
        {
            Locks::release_lock($lock);
            error_log("[Booking->update_booking_details]: Could not update the API data for the booking with Assumax Id: {$assumaxId}.");
            self::redirect_on_error(get_the_permalink($boekingGegevensWijzigenPage), 'Er is iets fout gegaan tijdens het wijzigen van de gegevens. Neem alsjeblieft contact met ons op.', $_POST);
        }
        if ($lock) {
            $upsertResult = Bookings::upsert($assumaxId);
            Locks::release_lock($lock);
            if ($upsertResult === false) {
                error_log("[Booking->update_booking_details]: Could not upsert the booking with Assumax Id: {$assumaxId}.");
                self::redirect_on_error(get_the_permalink($boekingGegevensWijzigenPage), 'Er is iets fout gegaan tijdens het wijzigen van de gegevens. Neem alsjeblieft contact met ons op.', $_POST);
            }
        }
        // Redirect to the my account page.
        wp_safe_redirect(get_the_permalink($myAccountPage));
        exit;
    }

    /**
     * Parses customer details from post data into objects that will be accepted by the Assumax API.
     * All supplied customers need to be available on the booking.
     * Customers who are not the primary customer and have a user account will be ignored.
     *
     * @param array $bookingCustomers Array of customer Assumax Id and primary status.
     * @param array $customers Sanitized customer details form data.
     * @param bool $allowEmpty When true, required fields do not need to have a value.
     * @return array An array of customer detail objects.
     * @throws Exception When something goes wrong or when one of the required fields is empty and $allowEmpty equals false.
     */
    private static function get_booking_details_customers(array $bookingCustomers, array $customers = [], bool $allowEmpty = false) : array
    {
        $detailObjects = [];
        for ($i = 0; $i < count($customers); $i++) {
            if (!key_exists($customers[$i]['user_id'], $bookingCustomers)) {
                throw new Exception("Er is iets fout gegaan tijdens het wijzigen van de gegevens. Neem alsjeblieft contact met ons op.");
            }
            $customerObject = Customers::get_by_assumax_id($customers[$i]['user_id']);
            if (!empty($customerObject) && !$bookingCustomers[$customers[$i]['user_id']]) {
                continue;
            }
            $detailObject = new stdClass();
            $detailObject->Id = $customers[$i]['user_id'];
            // Parse the required fields.
            $detailObject->FirstName = empty($customers[$i]['first_name']) ? empty($customerObject) ? '' : get_field('customer_first_name', $customerObject->ID) : $customers[$i]['first_name'];
            if (!$allowEmpty && empty($detailObject->FirstName)) {
                throw new Exception("De voornaam is niet ingevuld.");
            }
            $detailObject->LastName = empty($customers[$i]['last_name']) ? empty($customerObject) ? '' : get_field('customer_last_name', $customerObject->ID) : $customers[$i]['last_name'];
            if (!$allowEmpty && empty($detailObject->LastName)) {
                throw new Exception("De achternaam is niet ingevuld.");
            }
            $detailObject->NickName = empty($customers[$i]['nickname']) ? empty($customerObject) ? '' : get_field('customer_nick_name', $customerObject->ID) : $customers[$i]['nickname'];
            if (!$allowEmpty && empty($detailObject->NickName)) {
                throw new Exception("De roepnaam is niet ingevuld.");
            }
            $detailObject->DateOfBirth = empty($customers[$i]['birthdate']) ? empty($customerObject) ? '' : get_field('customer_date_of_birth', $customerObject->ID) : $customers[$i]['birthdate'];
            if (!$allowEmpty && empty($detailObject->DateOfBirth)) {
                throw new Exception("De geboortedatum is niet ingevuld.");
            }
            $detailObject->PhoneNumber = empty($customers[$i]['phone']) ? empty($customerObject) ? '' : get_field('customer_phone_number', $customerObject->ID) : $customers[$i]['phone'];
            if (!$allowEmpty && empty($detailObject->PhoneNumber)) {
                throw new Exception("Het telefoonnummer is niet ingevuld.");
            }
            // Parse the non-required fields.
            $detailObject->Sex = boolval($customers[$i]['gender'] ??
                (empty($customerObject) ? 0 : get_field('customer_gender', $customerObject->ID))) ? 1 : 0;

	        // reset / set variables
	        $address_name_array = [];
	        $address_number_explode = [];
	        $address_number_array = [];
	        $address_number_unique = [];
	        $streetName = '';
	        $houseNumber = '';

            if(!empty($customers[$i]['billing_address_1'])) {
	            // find everything but numbers and store it in a new variable $address_name_array
	            preg_match('/[^0-9]+/', $customers[$i]['billing_address_1'], $address_name_array);

	            // check if the key 0 exists (meaning that it found a possible streetname)
            	if(key_exists( 0, $address_name_array) && !empty($address_name_array)) {
            		$streetName = rtrim(str_replace(',', '', $address_name_array[0]));
	            }
            } elseif(empty($customers[$i]['billing_address_1']) && !empty($customerObject)) {
            	$field = get_field('customer_street', $customerObject->ID);

	            if(!empty($field)) {
		            // find everything but numbers and store it in a new variable $address_name_array
		            preg_match('/[^0-9]+/', $field, $address_name_array);

		            // check if the key 0 exists (meaning that it found a possible streetname)
		            if(key_exists( 0, $address_name_array) && !empty($address_name_array)) {
			            $streetName = rtrim(str_replace(',', '', $address_name_array[0]));
		            }
	            }
            }

            if(!empty($customers[$i]['billing_house_number'])) {
	            // find everything but letters and store it in a new variable $address_number_array
            	preg_match('/[^a-zA-Z]+(.?)+/', $customers[$i]['billing_house_number'], $address_number_array);

	            // check if the key 0 exists (meaning that it found a number)
	            if(key_exists(0, $address_number_array)) {
		            // convert the found numbers to a new array
		            $address_number_explode = explode(' ', $address_number_array[0]);
		            // return only unique values from the new array
		            $address_number_unique = array_unique( $address_number_explode );

		            // set the house number with possible additions
		            $houseNumber = implode(' ', $address_number_unique);
	            }
            } elseif(empty($customers[$i]['billing_house_number']) && !empty($customerObject)) {
	            $field = get_field('customer_street_number', $customerObject->ID);

	            if(!empty($field)) {
		            // find everything but letters and store it in a new variable $address_number_array
		            preg_match('/[^a-zA-Z]+(.?)+/', $field, $address_number_array);

		            // check if the key 0 exists (meaning that it found a number)
		            if(key_exists( 0, $address_number_array) && !empty($address_number_array)) {
			            // convert the found numbers to a new array
			            $address_number_explode = explode(' ', $address_number_array[0]);
			            // return only unique values from the new array
			            $address_number_unique = array_unique( $address_number_explode );

			            // set the house number with possible additions
			            $houseNumber = implode(' ', $address_number_unique);
		            }
	            }
            }

//            $detailObject->Street = $streetName ??
//                (empty($customerObject) ? '' : get_field('customer_street', $customerObject->ID));
            $detailObject->Street = $streetName ?? '';

//            $detailObject->StreetNumber = $houseNumber ??
//                (empty($customerObject) ? '' : get_field('customer_street_number', $customerObject->ID));
            $detailObject->StreetNumber = $houseNumber ?? '';

            $detailObject->PostalCode = $customers[$i]['billing_postcode'] ??
                (empty($customerObject) ? '' : get_field('customer_postal_code', $customerObject->ID));
            $detailObject->City = $customers[$i]['billing_city'] ??
                (empty($customerObject) ? '' : get_field('customer_city', $customerObject->ID));
            $detailObject->Nationality = $customers[$i]['nationality'] ??
                (empty($customerObject) ? '' : get_field('customer_nationality', $customerObject->ID));
            $detailObject->EmergencyContactName = $customers[$i]['emergencycontactname'] ??
                (empty($customerObject) ? '' : get_field('customer_emergency_contact_name', $customerObject->ID));
            $detailObject->EmergencyContactPhone = $customers[$i]['emergencycontactphone'] ??
                (empty($customerObject) ? '' : get_field('customer_emergency_contact_phone', $customerObject->ID));
            $detailObject->DietaryWishes = $customers[$i]['dietary_wishes'] ??
                (empty($customerObject) ? '' : get_field('customer_dietary_wishes', $customerObject->ID));
            $detailObject->Note = $customers[$i]['note'] ??
                (empty($customerObject) ? '' : get_field('customer_note', $customerObject->ID));
            $detailObjects[] = $detailObject;
        }
        return $detailObjects;
    }

    /**
     * Redirect hook for the booked page to prevent access for users who shouldn't have access to a booking.
     */
    public static function booking_tracking() {
        if (basename(get_page_template()) == 'template-booked.php') {
            if (!is_user_logged_in()) {
                wp_redirect(site_url());
                exit;
            }
            global $assumaxId;
            $assumaxId = get_user_meta(get_current_user_id(), '_new_booking_assumax_id', true);
            if (empty($assumaxId)) {
                wp_redirect(site_url());
                exit;
            }
            delete_user_meta(get_current_user_id(), '_new_booking_assumax_id');
            $booking = Bookings::get_by_assumax_id($assumaxId);
            if (empty($booking)) {
                wp_redirect(site_url());
                exit;
            }
            if (Booking::user_has_access($booking->ID, get_current_user_id()) !== 2) {
                wp_redirect(site_url());
                exit;
            }
        }
    }

    /**
     * Obtains all the relevant information of a booking which can be used to send a data event to Google Tag Manager.
     *
     * @param int $assumaxId The id of the Booking in Assumax.
     * @return array Array of data in the following formay:
     * [
     *      'transaction_total' => The total transaction cost excluding any extra charges.
     *      'extra_charges' => The extra charges for the booking.
     *      'product_sku' => The SKU (Assumax Id) of the Trip.
     *      'product_name' => The title of the Trip.
     *      'product_quantity' => The quantity of the Trips booked.
     *      'product_price' => The price of one individual Trip.
     *      'product_category' => One of the trip types that the Trip belongs to.
     * ]
     */
    public static function get_gtm_booked_data(int $assumaxId) : array
    {
        $bookingPost = Bookings::get_by_assumax_id($assumaxId);
        if (empty($bookingPost)) {
            return [];
        }
        $bookingOrder = wc_get_order($bookingPost);
        if (empty($bookingOrder)) {
            return [];
        }
        $tripPostId = get_field('booking_trip', $bookingPost->ID);
        if (empty($tripPostId)) {
            return [];
        }
        $bookingTotal = $bookingOrder->get_total();
        $templatePostId = get_field('trip_template', $tripPostId);
        $tripAssumaxId = get_post_meta($tripPostId, '_assumax_id', true);
        $tripTitle = get_the_title($tripPostId);
        $tripExtraCharges = get_field('trip_extra_charges', $tripPostId);
        $tripTotalExtraCharges = 0;
        if (!empty($tripExtraCharges)) {
            foreach ($tripExtraCharges as $extraCharge) {
                $tripTotalExtraCharges += floatval($extraCharge['price']);
            }
        }
        $transactionTotal = $bookingTotal - $tripTotalExtraCharges;
        $bookingCustomers = get_field('booking_customers', $bookingPost->ID);
        if (empty($bookingCustomers)) {
            return [];
        }
        $customerCount = count($bookingCustomers);
        $categories = [];
        if (!empty($templatePostId)) {
            $categories = wp_get_post_terms($templatePostId, 'trip-type');
        }
        $category = empty($categories) ? null : $categories[0]->name;
        return [
            'transaction_total' => $transactionTotal,
            'extra_charges' => $tripTotalExtraCharges,
            'product_sku' => $tripAssumaxId,
            'product_name' => $tripTitle,
            'product_quantity' => $customerCount,
            'product_price' => $transactionTotal / $customerCount,
            'product_category' => $category
        ];
    }

    /**
     * Adds several booking related query variables to the set.
     *
     * @param array $vars The current set of query vars.
     * @return array The vars array augmented with the new booking related query variables.
     */
    public static function add_booking_query_vars($vars) {
        $vars[] = "boeking";
        $vars[] = "nc";
        $vars[] = "err";
        return $vars;
    }

    /**
     * Obtains a list of insurance options that are still available for the provided booking.
     *
     * @param int $bookingPostId The post Id of the Booking.
     * @param int $bookingAssumaxId The Assumax Id of the booking.
     * @param int $tripPostId The post Id of the Trip.
     * @return array An array of available insurances and their respective prices.
     */
    public static function get_booking_insurance_options($bookingPostId, $bookingAssumaxId, $tripPostId) {
        if (empty($bookingPostId) || empty($bookingAssumaxId) || empty($tripPostId)) return [];
        $bookingDate = get_field('booking_date', $bookingPostId);
        $tripStartDate = get_field('trip_start_date', $tripPostId);
        $today = Helpers::today();
        $bookingDateTime = Helpers::create_local_datetime($bookingDate);
        $tripStartDateTime = Helpers::create_local_datetime($tripStartDate);

        if (empty($tripStartDateTime) || empty($today) || ($today >= $tripStartDateTime)) return [];
        $insurances = get_field('booking_insurances', $bookingPostId);
        $insurancesReindexed = [];
        if (!empty($insurances)) {
            foreach ($insurances as $insurance) {
                $insurancesReindexed[$insurance['type']] = $insurance;
            }
        }
        $insuranceOptions = get_field('trip_insurance_options', $tripPostId);
        $availableInsuranceOptions = [];
        if (!empty($insuranceOptions)) {
            $today = Helpers::today();
            foreach ($insuranceOptions as $insuranceOption) {
                $discount = 0;
                if (key_exists($insuranceOption['type'], $insurancesReindexed)) {
                    if ($insurancesReindexed[$insuranceOption['type']]['active']) {
                        continue;
                    }
                    $expiration = Helpers::create_local_datetime($insurancesReindexed[$insuranceOption['type']]['expiration']);
                    if (!empty($expiration) && $today >= $expiration) {
                        continue;
                    }
                    $discountExpiration = Helpers::create_local_datetime($insurancesReindexed[$insuranceOption['type']]['discount_expiration']);
                    if (!empty($discountExpiration) && $today < $discountExpiration) {
                        $discount = $insurancesReindexed[$insuranceOption['type']]['expiration'];
                    }
                }
                switch ($insuranceOption['type']) {
                    case 'Trip': {
                        $availableInsuranceOptions[$insuranceOption['type']] = [
                            'price' => floatval($insuranceOption['price']),
                            'title' => $insuranceOption['title'],
                            'description' => $insuranceOption['description']
                        ];
                        $availableInsuranceOptions[$insuranceOption['type']]['price'] -= floatval($discount);
                        break;
                    }
                    case 'Cancellation': {
                        if (empty($bookingDateTime) || $bookingDateTime > $today) break;
                        $availableInsuranceOptions[$insuranceOption['type']] = [
                            'price' => floatval($insuranceOption['price']),
                            'title' => $insuranceOption['title'],
                            'description' => $insuranceOption['description']
                        ];
                        $availableInsuranceOptions[$insuranceOption['type']]['price'] -= floatval($discount);
                        break;
                    }
                    case 'SnowmobilePayOff': {
                        $availableInsuranceOptions[$insuranceOption['type']] = [
                            'price' => floatval($insuranceOption['price']),
                            'title' => $insuranceOption['title'],
                            'description' => $insuranceOption['description']
                        ];
                        break;
                    }
                    default: {
                        error_log("[Booking->get_booking_insurance_options]: Unknown insurance type: {$insuranceOption['type']} for trip with post id: {$tripPostId}.");
                        break;
                    }
                }
            }
        }
        // TODO: Ugly, find a better spot to filter out the cancellation insurance
	    if($today->modify('+7 days')->format('Y-m-d') > $bookingDateTime->format('Y-m-d')) unset($availableInsuranceOptions['Cancellation']);
	    return $availableInsuranceOptions;
    }

    /**
     * Ajax function that adds a new insurance to a booking.
     * Needs to be passed a data element containing the following elements:
     *  - booking_id - Id of the booking which needs to be updated.
     *  - insurance type - The type of insurance to add.
     * Checks whether or not the insurance is available for this booking.
     */
    public static function ajax_add_insurance() {
        $bookingAssumaxId = $_POST['booking_id'];
        $insuranceType = $_POST['insurance_type'];
        $questions = $_POST['questions'];
        if (empty($bookingAssumaxId) || empty($insuranceType) || empty($questions) || !is_array($questions))
            NosunHelpers::exit_with_json_status_message("Er is iets fout gegaan tijdens het toevoegen van de verzekering. Neem alsjeblieft contact met ons op.", true);
        // Try to obtain the order.
        $bookingPost = Bookings::get_by_assumax_id($bookingAssumaxId);
        if (empty($bookingPost))
            NosunHelpers::exit_with_json_status_message("Er is iets fout gegaan tijdens het toevoegen van de verzekering. Neem alsjeblieft contact met ons op.", true);
        $bookingOrder = wc_get_order($bookingPost);
        $tripPostId = get_field('booking_trip', $bookingPost->ID);
        if (empty($tripPostId))
            NosunHelpers::exit_with_json_status_message("Er is iets fout gegaan tijdens het toevoegen van de verzekering. Neem alsjeblieft contact met ons op.", true);
        // Check if the current user owns this booking.
        if (self::user_has_access($bookingPost->ID, get_current_user_id()) !== 2) {
            NosunHelpers::exit_with_json_status_message("Je bent niet de hoofdboeker van deze boeking en kunt geen verzekeringen afsluiten.", true);
        }
        // Obtain the remaining insurances.
        $insuranceOptions = self::get_booking_insurance_options($bookingPost->ID, $bookingAssumaxId, $tripPostId);
        if (empty($insuranceOptions))
            NosunHelpers::exit_with_json_status_message("Je kunt geen verzekeringen afsluiten voor deze boeking", true);
        if (!key_exists($insuranceType, $insuranceOptions))
            NosunHelpers::exit_with_json_status_message("Deze verzekering kan niet worden afgesloten voor deze boeking", true);
        // Obtain a lock on the booking.
        $lock = Locks::acquire_lock($bookingAssumaxId, 'Booking', 'Modified', true);
        // Send the correct data to the API depending on the insurance type.
        $apiResult = false;
        if ($insuranceType === 'Cancellation') {
            $apiResult = Bookings::add_cancellation_insurance($bookingAssumaxId);
        } elseif ($insuranceType === 'Trip') {
            $apiResult = Bookings::add_trip_insurance($bookingAssumaxId);
        } elseif ($insuranceType === 'SnowmobilePayOff') {
            $apiResult = Bookings::add_snowmobile_payoff($bookingAssumaxId);
        }
        if (!$apiResult) {
            Locks::release_lock($lock);
            NosunHelpers::exit_with_json_status_message("Er is iets fout gegaan tijdens het toevoegen de verzekering. Neem alsjeblieft contact met ons op.", true);
        }
        // Save the questions to the booking.
        $answers = [];
        $answerNote = '';
        foreach ($questions as $question) {
            if (empty($question['name']) || !isset($question['value'])) {
                continue;
            }
            $answer = sanitize_text_field($question['value']);
            $answers[] = $answer;
            $answerNote .= "{$question['name']}: {$answer} ;";
        }
        $bookingOrder->add_order_note("Klant beantwoorde de vragen voor verzekering: {$insuranceType} met: {$answerNote}.");
        // Send a new insurance added mail to the customer.
        $customer = Customers::get_by_user_id(get_current_user_id());
        if (!empty($customer)) {
            $emailAddress = get_field('customer_email_address', $customer->ID);
            $events = Email::trigger_email_events('new_insurance', [$emailAddress],
                [
                    'assumax_id' => $bookingAssumaxId,
                    'insurance_type' => $insuranceType,
                    'customer' => $customer,
                    'answers' => $answers
                ], "{$bookingAssumaxId}_{$insuranceType}");
            if (!empty($events)) {
                foreach ($events as $eventId => $status) {
                    if ($status === false) {
                        $bookingOrder->add_order_note("Er ging iets fout tijdens het versturen van een nieuwe verzekerings e-mail naar e-mail adres: {$emailAddress}.");
                    } else {
                        $bookingOrder->add_order_note("Nieuwe verzekering ({$insuranceType}) e-mail verzonden naar: {$emailAddress}.");
                    }
                }
            }
        } else {
            $bookingOrder->add_order_note("Kon geen nieuwe verzekering e-mail sturen omdat de reiziger niet bestaat.");
        }
        // Upsert the booking.
        if ($lock) {
            $upsertResult = Bookings::upsert($bookingAssumaxId);
            Locks::release_lock($lock);
            if ($upsertResult === false) {
                error_log("[Booking->ajax_add_insurance]: Could not upsert the booking with Assumax Id: {$bookingAssumaxId}.");
                NosunHelpers::exit_with_json_status_message("De verzekering is toegevoegd en de status wordt nu bijgewerkt. Check over een paar minuten nogmaals.", false);
            }
        }
        // Return a success message.
        NosunHelpers::exit_with_json_status_message("De verzekering is toegevoegd!", false);
    }

    /**
     * Ajax function that adds a new extra product to a booking.
     * Needs the be passed a data element containing the following elements:
     *  - booking_id - Id of the booking which needs to be updated.
     *  - product_id - Assumax Id of the extra product that needs to be added.
     */
    public static function ajax_add_extra_product() {
        $bookingAssumaxId = $_POST['booking_id'];
        $productAssumaxId = $_POST['product_id'];
        if (empty($bookingAssumaxId) || empty($productAssumaxId))
            NosunHelpers::exit_with_json_status_message("Er is iets fout gegaan tijdens het toevoegen van het product. Neem alsjeblieft contact met ons op.", true);
        // Try to obtain the order.
        $bookingPost = Bookings::get_by_assumax_id($bookingAssumaxId);
        if (empty($bookingPost))
            NosunHelpers::exit_with_json_status_message("Er is iets fout gegaan tijdens het toevoegen van het product. Neem alsjeblieft contact met ons op.", true);
        $shopOrder = wc_get_order($bookingPost);
        if (empty($shopOrder)){
            NosunHelpers::exit_with_json_status_message("Er is iets fout gegaan tijdens het toevoegen van het product. Neem alsjeblieft contact met ons op.", true);
        }
        // Check if the current user owns this booking.
        if (self::user_has_access($bookingPost->ID, get_current_user_id()) !== 2) {
            NosunHelpers::exit_with_json_status_message("Je bent niet de hoofdboeker van deze boeking en kunt geen extra producten toevoegen.", true);
        }
        $customer = Customers::get_by_user_id(get_current_user_id());
        // Check if the product exists.
        $extraProductPost = Products::get_by_assumax_id($productAssumaxId);
        if (empty($extraProductPost))
            NosunHelpers::exit_with_json_status_message("We kunnen het gekozen product niet vinden. Neem alsjeblieft contact met ons op.", true);
        $productTitle = get_the_title($extraProductPost); // Used as description for the api.
        $productFields = get_fields($extraProductPost->ID);
        // Obtain a lock on the booking.
        $lock = Locks::acquire_lock($bookingAssumaxId, 'Booking', 'Modified', true);
        // Use the API to add the product to the booking.
        $result = Products::add_product_to_booking(
            $bookingAssumaxId,
            $productTitle,
            $productFields['_extra_product_vatrate_id'],
            1,
            $productFields['_extra_product_price']);
        if ($result === false || $result === '404'){
            Locks::release_lock($lock);
            $shopOrder->add_order_note("Kon het extra product: '{$productTitle}' niet toevoegen aan de boeking.");
            NosunHelpers::exit_with_json_status_message("Er is iets fout gegaan tijdens het toevoegen van het product. Neem alsjeblieft contact met ons op.", true);
        }
        // Send a new 'Nieuw product aan boeking toegevoegd' e-mail to the user.
        $emailAddress = get_field('customer_email_address', $customer->ID);
        $nickName = get_field('customer_nick_name', $customer->ID);
        if (empty($emailAddress) || empty($nickName)) {
            $shopOrder->add_order_note("Kon geen nieuw extra product e-mail versturen omdat het e-mail adres of de roepnaam van de reiziger ontbreekt.");
        } else {
            $events = Email::trigger_email_events(
                'new_extra_product',
                [$emailAddress],
                [
                    'assumax_id' => $bookingAssumaxId,
                    'product' => $productTitle,
                    'nick_name' => $nickName,
                    'booking_url' => site_url('/boeking/' . $bookingAssumaxId)
                ],
                "{$bookingAssumaxId}_{$productAssumaxId}"
            );
            if (!empty($events)) {
                foreach ($events as $eventId => $result) {
                    if ($result === false) {
                        $shopOrder->add_order_note("Kon geen extra product e-mail verzenden voor product: '{$productTitle}' naar: {$emailAddress} voor event: {$eventId}.");
                    } else {
                        $shopOrder->add_order_note("Nieuw extra product e-mail verzonden voor product: '{$productTitle}' naar: {$emailAddress} voor event: {$eventId}.");
                    }
                }
            }
        }
        // Upsert the booking if a lock is obtained.
        if ($lock) {
            $upsertResult = Bookings::upsert($bookingAssumaxId);
            Locks::release_lock($lock);
            if ($upsertResult === false) {
                error_log("[Booking->ajax_add_extra_product]: Could not upsert the booking with Assumax Id: {$bookingAssumaxId}.");
                NosunHelpers::exit_with_json_status_message("Het product is toegevoegd en de status wordt nu bijgewerkt. Check over een paar minuten nogmaals.", false);
            }
        }
        // Return a success message.
        NosunHelpers::exit_with_json_status_message("Het product is toegevoegd!", false);
    }

    /**
     * Checks if a download request is being handled and if so does the following:
     * - Check if the booking is valid.
     * - Check if the current user is a customer on the booking or an administrator.
     * - Downloads the requested file from the API.
     * - Sends the content back to the user in the form of a file download.
     */
    public static function download_parse_request() {
        if (empty($_GET['boeking']) || (empty($_GET['factuur']) && empty($_GET['document']))) {
            return;
        }
        $bookingAssumaxID = sanitize_text_field($_GET['boeking']);
        $booking = Bookings::get_by_assumax_id($bookingAssumaxID);
        if (empty($booking)) {
            wp_die('Geen toegang.', 'Geen toegang.', 403);
        }
        $userId = get_current_user_id();
        if (empty($userId)) {
            wp_die('Geen toegang.', 'Geen toegang.', 403);
        }
        if (Booking::user_has_access($booking->ID, get_current_user_id()) !== 2) {
            wp_die('Geen toegang.', 'Geen toegang.', 403);
        }
        $customer = Customers::get_by_user_id($userId);
        if (empty($customer)) {
            wp_die('Geen toegang.', 'Geen toegang.', 403);
        }
        $customerAssumaxId = get_post_meta($customer->ID, '_assumax_id', true);
        // Either get the invoice or the document depending on the query parameter.
        if (!empty($_GET['factuur'])) {
            $invoiceIdSanitized = intval(sanitize_text_field($_GET['factuur']));
            $invoice = Customers::download_customer_invoice($customerAssumaxId, $invoiceIdSanitized);
            if ($invoice === null) {
                wp_die('De factuur bestaat niet.', 'De factuur bestaat niet.', 404);
            }
            ApiHelpers::send_data_for_download(
                "boeking_{$bookingAssumaxID}_factuur_{$invoiceIdSanitized}.PDF",
                "application/pdf",
                $invoice);
        } elseif (!empty($_GET['document'])) {
            $documentIdSanitized = intval(sanitize_text_field($_GET['document']));
            // Obtain the documents from the booking and obtain the filename of the chosen document.
            $documents = get_field('booking_documents', $booking->ID);
            if (empty($documents)) {
                wp_die('Geen toegang.', 'Geen toegang.', 403);
            }
            $fileName = "boeking_{$bookingAssumaxID}_document_{$documentIdSanitized}.PDF";
            foreach ($documents as $bookingDocument) {
                if ($bookingDocument['id'] == $documentIdSanitized) {
                    $fileName = $bookingDocument['file_name'];
                    break;
                }
            }
            $document = Bookings::download_booking_document($bookingAssumaxID, $documentIdSanitized);
            if ($document === null) {
                wp_die('Het document bestaat niet.', 'Het document bestaat niet.', 404);
            }
            ApiHelpers::send_data_for_download($fileName, "application/pdf", $document);
        }
    }
}

add_action('admin_post_new_booking', [Booking::class, 'new_booking']);
add_action('admin_post_nopriv_new_booking', [Booking::class, 'new_booking']);
add_action('admin_post_update_booking_details', [Booking::class, 'update_booking_details']);
add_action('admin_post_nopriv_update_booking_details', [Booking::class, 'update_booking_details']);
add_action('template_redirect', [Booking::class, 'booking_tracking']);
add_filter('query_vars', [Booking::class, 'add_booking_query_vars']);
add_action('wp_ajax_add_extra_product', [Booking::class, 'ajax_add_extra_product']);
add_action('wp_ajax_nopriv_add_extra_product', [Booking::class, 'ajax_add_extra_product']);
add_action('wp_ajax_add_insurance', [Booking::class, 'ajax_add_insurance']);
add_action('wp_ajax_nopriv_add_insurance', [Booking::class, 'ajax_add_insurance']);
add_action('init', [Booking::class, 'download_parse_request']);
