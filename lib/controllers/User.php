<?php /** @noinspection SqlNoDataSourceInspection */

/** @noinspection SqlDialectInspection */

namespace lib\controllers;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Exception;
use Roots\Sage\Helpers;
use stdClass;
use Vazquez\NosunAssumaxConnector\Api\Bookings;
use Vazquez\NosunAssumaxConnector\Api\Customers;
use Vazquez\NosunAssumaxConnector\Api\Documents;
use Vazquez\NosunAssumaxConnector\Api\Locks;
use WP_Post;
use WP_User;

/**
 * Controller that holds several functions that deal with users.
 *
 * Class User
 * @package Roots\Sage
 */
class User {
    /**
     * Creates a new account with the provided information and sets the customer role.
     *
     * @param string $firstName The user's first name.
     * @param string $lastName The user's last name.
     * @param string $nickName The user's nick name.
     * @param string $emailAddress The user's e-mail address. This will be validated and checked for existence.
     * @return array An array containing the credentials for the newly created user.
     * @throws Exception When some of the required fields are empty or invalid or when something fails while
     * creating the new user.
     */
    public static function create_new_account($firstName, $lastName, $nickName, $emailAddress) {
        if (empty($firstName) || empty($lastName) || empty($nickName) || empty($emailAddress)) {
            throw new Exception("Één of meerdere velden zijn niet ingevuld bij de hoofdboeker.");
        }
        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Het bij de hoofdboeker ingevulde e-mail adres is niet geldig.");
        }
        if (email_exists($emailAddress)) {
            throw new Exception("Het bij de hoofdboeker ingevulde e-mail adres bestaat al.");
        }
        $userName = sanitize_user(sprintf("%s %s", $firstName, $lastName), true);
        // Check if the target username already exists, if it does add an integer value starting with 0 until a free username is found.
        $baseName = $userName;
        $i = 0;
        while (username_exists($userName)) {
            $userName = $baseName . (++$i);
        }
        $password = wp_generate_password();
        $userData = array (
            'user_login' => $userName,
            'first_name' => sanitize_text_field($firstName),
            'last_name'  => sanitize_text_field($lastName),
            'nickname'   => sanitize_text_field($nickName),
            'user_email' => sanitize_text_field($emailAddress),
            'user_pass'  => $password
        );
        $userId = wp_insert_user($userData);
        if (is_wp_error($userId)) {
            error_log("[User->create_new_account]: Could not create a new user for the user with username: {$userName} and e-mail address: {$emailAddress}.");
            error_log("[User->create_new_account]: {$userId->get_error_message()}.");
            throw new Exception("Er ging iets fout tijdens het aanmaken van een nieuw account, neem alsjeblieft contact met ons op.");
        }
        $user = get_user_by('ID', $userId);
        if ($user === false) {
            error_log("[User->create_new_account]: Could not obtain the user with Id: {$userId}.");
            throw new Exception("Er ging iets fout tijdens het aanmaken van een nieuw account, neem alsjeblieft contact met ons op.");
        }
        $user->set_role('customer');
        $events = Email::trigger_email_events('new_user', [$userData['user_email']], $userData, $userId);
        if (!empty($events)) {
            foreach ($events as $eventId => $result) {
                if ($result === false) {
                    error_log("[User->create_new_account]: Could not send the new user e-mail to user with id: {$userId} and e-mail address: {$userData['user_email']}.");
                }
            }
        }
        return [
            'user_login'    => $userName,
            'user_password' => $password,
            'remember'      => false
        ];
    }

	/**
	 * Creates a or modifies the WP user account for the provided emailaddress
	 * Sets / updates the user_meta field: user_customer
	 *
	 * @param $customerPostId
	 * @param $data
	 *
	 * @return bool|void|WP_User
	 */
	public static function upsert_user_account($customerPostId, $data) {
    	// set default variables
		$firstName = $data->FirstName ?? '';
		$lastName = $data->LastName ?? '';
		$nickName = $data->NickName ?? '';
		$emailAddress = trim($data->EmailAddress) ?? '';

		// Check if the provied emailaddress is already attached to a wp user
		$user = get_user_by('email', $emailAddress);

		if(!$user) {
			// Check if some variables are empty or not
			if (empty($firstName) || empty($lastName) || empty($nickName) || empty($emailAddress)) {
				error_log('[User::upsert_user_account] Failed because $firstName, $lastName, $nickName or $emailAddress is empty');
				return;
			}
			// Check if the provided emailaddress is a valid emailaddress
			if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
				error_log('[User::upsert_user_account] The provided emailaddress is not valid');
				error_log(var_export($emailAddress, true));
				return;
			}
			// Check to see if the provided emailaddress already exists
			if (email_exists($emailAddress)) {
				error_log('[User::upsert_user_account] The provided emailaddress is already taken.');
				error_log(var_export($emailAddress, true));
				return;
			}
			// Set the userName variable
			$userName = sanitize_user(sprintf("%s %s", $firstName, $lastName), true);

			// Check if the target username already exists, if it does add an integer value starting with 0 until a free username is found.
			$baseName = $userName;
			$i = 0;
			while (username_exists($userName)) {
				$userName = $baseName . (++$i);
			}
			$password = wp_generate_password();
			// set default $userData array
			$userData = array (
				'user_login' => $userName,
				'first_name' => sanitize_text_field($firstName),
				'last_name'  => sanitize_text_field($lastName),
				'nickname'   => sanitize_text_field($nickName),
				'user_email' => sanitize_text_field($emailAddress),
				'user_pass'  => $password,
			);
			$userId = wp_insert_user($userData);
			if (is_wp_error($userId)) {
				error_log("[User::upsert_user_account]: Could not create a new user for the user with username: {$userName} and e-mail address: {$emailAddress}.");
				error_log("[User::upsert_user_account]: {$userId->get_error_message()}.");
				return;
			}
			$user = get_user_by('ID', $userId);
			if ($user === false) {
				error_log("[User::upsert_user_account]: Could not obtain the user with Id: {$userId}.");
				return;
			}
			$user->set_role('customer');

			// check if the email event is already in the api_emails_sent table
			// check for trigger, email_addresses and singleton_value
			$email_has_been_sent = Email::email_event_has_been_sent( 'new_user', [$userData['user_email']], $data->Id);

			if(!$email_has_been_sent) {
				// Send the new_user email
				$events = Email::trigger_email_events('new_user', [$userData['user_email']], $userData, $userId);
				if (!empty($events)) {
					foreach ($events as $eventId => $result) {
						if ($result === false) {
							error_log("[User::upsert_user_account]: Could not send the new user e-mail to user with id: {$userId} and e-mail address: {$userData['user_email']}.");
						}
					}
				}
			}
		}
		// Make sure the user is attached to the CustomerPost
		$customer = update_user_meta($user->ID, 'user_customer', $customerPostId);
		return $user;
	}

	/**
	 * Creates a or modifies the WP guide account for the provided emailaddress
	 * Sets / updates the user_meta field: user_tourguide
	 *
	 * @param $guidePostId
	 * @param $data
	 *
	 * @return bool|void|WP_User
	 */
	public static function upsert_guide_account($guidePostId, $data)
	{
		// set default variables
		$firstName = $data->FirstName ?? '';
		$lastName = $data->LastName ?? '';
		$nickName = $data->NickName ?? '';
		$emailAddress = $data->EmailAddress ?? '';

		// Check if the provied emailaddress is already attached to a wp user
		$user = get_user_by('email', $emailAddress);

		if(!$user) {
			// Check if some variables are empty or not
			if (empty($firstName) || empty($lastName) || empty($nickName) || empty($emailAddress)) {
				error_log('[User::upsert_guide_account] Failed because $firstName, $lastName, $nickName or $emailAddress is empty');
				return;
			}
			// Check if the provided emailaddress is a valid emailaddress
			if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
				error_log('[User::upsert_guide_account] The provided emailaddress is not valid');
				error_log(var_export($emailAddress, true));
				return;
			}
			// Check to see if the provided emailaddress already exists
			if (email_exists($emailAddress)) {
				error_log('[User::upsert_guide_account] The provided emailaddress is already taken.');
				error_log(var_export($emailAddress, true));
				return;
			}
			// Set the userName variable
			$userName = sanitize_user(sprintf("%s %s", $firstName, $lastName), true);

			// Check if the target username already exists, if it does add an integer value starting with 0 until a free username is found.
			$baseName = $userName;
			$i = 0;
			while (username_exists($userName)) {
				$userName = $baseName . (++$i);
			}
			$password = wp_generate_password();
			// set default $userData array
			$userData = array (
				'user_login' => $userName,
				'first_name' => sanitize_text_field($firstName),
				'last_name'  => sanitize_text_field($lastName),
				'nickname'   => sanitize_text_field($nickName),
				'user_email' => sanitize_text_field($emailAddress),
				'user_pass'  => $password,
			);
			$userId = wp_insert_user($userData);
			if (is_wp_error($userId)) {
				error_log("[User::upsert_guide_account]: Could not create a new user for the user with username: {$userName} and e-mail address: {$emailAddress}.");
				error_log("[User::upsert_guide_account]: {$userId->get_error_message()}.");
				return;
			}
			$user = get_user_by('ID', $userId);
			if ($user === false) {
				error_log("[User::upsert_guide_account]: Could not obtain the user with Id: {$userId}.");
				return;
			}

			// check if the email event is already in the api_emails_sent table
			// check for trigger, email_addresses and singleton_value
			$email_has_been_sent = Email::email_event_has_been_sent( 'new_user', [$userData['user_email']], $data->Id);

			if(!$email_has_been_sent) {
				// Send the new_user email
				$events = Email::trigger_email_events('new_user', [$userData['user_email']], $userData, $userId);
				if (!empty($events)) {
					foreach ($events as $eventId => $result) {
						if ($result === false) {
							error_log("[User::upsert_user_account]: Could not send the new user e-mail to user with id: {$userId} and e-mail address: {$userData['user_email']}.");
						}
					}
				}
			}

		}
		// Make sure the user is attached to the CustomerPost
		$guide = update_user_meta($user->ID, 'user_tourguide', $guidePostId);
		return $user;
	}

    /**
     * Obtains the customer data for the current user to be displayed in the form-edit-account.php template.
     *
     * @return array Array in the following format:
     * [
     *      'customer' => null|stdClass -> Customer data.
     *      'documents' => stdClass[] -> Array containing Document data.
     *      'document_types' => Supported document types.
     *      'document_countries' => Supported document countries.
     * ]
     */
    public static function get_user_edit_account_data() : array
    {
        $user = wp_get_current_user();
        $customer = Customers::get_by_user_id($user->ID);
        $customerFields = empty($customer) ? [] : get_fields($customer->ID);
        $key = Key::loadFromAsciiSafeString(NOSUN_CRYPTO_KEY);
        if (empty($key)) {
            error_log("[User->get_user_edit_account_data]: No encryption key setup.");
            if (function_exists('wc_clear_notices')) wc_clear_notices();
            if (function_exists('wc_add_notice')) wc_add_notice("Er ging iets fout tijdens het verwerken van de persoonsgegevens, neem alsjeblieft contact met ons op.", 'error');
            return [];
        }
        $cookieData = [];
        if (!empty($_COOKIE['previous_data'])) {
            try {
                $decrypted = Crypto::decrypt($_COOKIE['previous_data'], $key);
                $cookieData = maybe_unserialize($decrypted);
                if (!is_array($cookieData)) {
                    $cookieData = [];
                }
            } /** @noinspection PhpRedundantCatchClauseInspection */
            catch (WrongKeyOrModifiedCiphertextException $e) {
                error_log("[User->get_user_edit_account_data]: {$e->getMessage()}");
                if (function_exists('wc_clear_notices')) wc_clear_notices();
                if (function_exists('wc_add_notice')) wc_add_notice("Er ging iets fout tijdens het verwerken van de persoonsgegevens, neem alsjeblieft contact met ons op.", 'error');
                return [];
            }
        }
        $customerObject = null;
        try {
            $customerObject = self::parse_customer_data($user->ID, $customerFields, $cookieData, true);
        } catch (Exception $e) {
            if (function_exists('wc_clear_notices')) wc_clear_notices();
            if (function_exists('wc_add_notice')) wc_add_notice($e->getMessage(), 'error');
        }
        // Parse the document data.
        $documentIds = !empty($customer) ? get_post_meta($customer->ID, 'customer_documents', true) : [];
        $documents = Documents::get_by_assumax_ids($documentIds);
        $documentFields = [];
        foreach ($documents as $document) {
            $documentFields[$document->ID] = get_fields($document->ID);
        }
        $documentObjects = [];
        try {
            $documentObjects = self::parse_document_data($documentFields, $cookieData['documents'] ?? [], true);
        } catch (Exception $e) {
            if (function_exists('wc_clear_notices')) wc_clear_notices();
            if (function_exists('wc_add_notice')) wc_add_notice($e->getMessage(), 'error');
        }
        return [
            'customer' => $customerObject,
            'documents' => $documentObjects,
            'document_types' => [
                '1' => 'Paspoort',
                '2' => 'Identiteitskaart',
                '4' => 'Visa',
                '8' => 'Verzekering',
            ],
            'document_countries' => [
                '1' => 'België',
                '37' => 'Bulgarije',
                '17' => 'Canada',
                '18' => 'Denemarken',
                '16' => 'Duitsland',
                '2' => 'Engeland',
                '23' => 'Europa',
                '28' => 'Faeröer eilanden',
                '3' => 'Finland',
                '4' => 'Frankrijk',
                '25' => 'Groot-Brittannië',
                '5' => 'IJsland',
                '6' => 'Ierland',
                '7' => 'Italië',
                '8' => 'Kroatië',
                '19' => 'Lapland',
                '27' => 'Madeira',
                '22' => 'Mallorca',
                '9' => 'Nederland',
                '26' => 'Noord-Ierland',
                '10' => 'Noorwegen',
                '11' => 'Oostenrijk',
                '20' => 'Portugal',
                '21' => 'Sardinië',
                '24' => 'Scandinavië',
                '12' => 'Schotland',
                '13' => 'Spanje',
                '14' => 'Zweden',
                '15' => 'Zwitserland'
            ]
        ];
    }

    /**
     * Parses the customer fields and post data parameters into a single object.
     * Post data has priority over the customer fields.
     *
     * @param int $userId The id of the user.
     * @param array $customerFields Array containing all the fields on a Customer.
     * @param array $postData Array containing POST values obtained through the form-edit-account template.
     * @param bool $allowEmpty Whether or not to allow empty values for the required fields.
     * @return stdClass An object containing fields that represent the current state of a customer.
     * @throws Exception Will be thrown when required fields are empty and $allowEmpty equals false.
     */
    private static function parse_customer_data(int $userId, array $customerFields, array $postData, bool $allowEmpty = false) : stdClass
    {
        $customerObject = new stdClass();
        // Required fields.
        $customerObject->FirstName = isset($postData['account_first_name']) ?
            sanitize_text_field($postData['account_first_name']) :
            ($customerFields['customer_first_name'] ?? get_user_meta($userId, 'first_name', true));
        if (!$allowEmpty && empty($customerObject->FirstName)) {
            throw new Exception("De voornaam is leeg.");
        }
        $customerObject->LastName = isset($postData['account_last_name']) ?
            sanitize_text_field($postData['account_last_name']) :
            ($customerFields['customer_last_name'] ?? get_user_meta($userId, 'last_name', true));
        if (!$allowEmpty && empty($customerObject->LastName)) {
            throw new Exception("De achternaam is leeg.");
        }
        $customerObject->NickName = isset($postData['account_display_name']) ?
            sanitize_text_field($postData['account_display_name']) :
            ($customerFields['customer_nick_name'] ?? get_user_meta($userId, 'nickname', true));
        if (!$allowEmpty && empty($customerObject->NickName)) {
            throw new Exception("De roepnaam is leeg.");
        }
        $customerObject->EmailAddress = isset($postData['account_email']) ?
            sanitize_text_field($postData['account_email']) :
            ($customerFields['customer_email_address'] ?? get_userdata($userId)->user_email);
        if (!$allowEmpty && empty($customerObject->EmailAddress)) {
            throw new Exception("Het e-mail adres is leeg.");
        }
        $customerObject->DateOfBirth = isset($postData['dateofbirth']) ?
            sanitize_text_field($postData['dateofbirth']) :
            ($customerFields['customer_date_of_birth'] ?? get_user_meta($userId, 'dateofbirth', true));
        if (!$allowEmpty && empty($customerObject->DateOfBirth)) {
            throw new Exception("De geboortedatum is leeg.");
        }
        $customerObject->PhoneNumber = isset($postData['phonenumber']) ?
            sanitize_text_field($postData['phonenumber']) :
            ($customerFields['customer_phone_number'] ?? '');
        if (!$allowEmpty && empty($customerObject->PhoneNumber)) {
            throw new Exception("Het telefoonnummer is leeg.");
        }
        $customerObject->Street = isset($postData['billing_address_1']) ?
            sanitize_text_field($postData['billing_address_1']) :
            ($customerFields['customer_street'] ?? '');
        if (!$allowEmpty && empty($customerObject->Street)) {
            throw new Exception("De straatnaam is leeg.");
        }
        $customerObject->StreetNumber = isset($postData['billing_house_number']) ?
            sanitize_text_field($postData['billing_house_number']) :
            ($customerFields['customer_street_number'] ?? '');
        if (!$allowEmpty && empty($customerObject->StreetNumber)) {
            throw new Exception("Het huisnummer is leeg.");
        }
        $customerObject->City = isset($postData['billing_city']) ?
            sanitize_text_field($postData['billing_city']) :
            ($customerFields['customer_city'] ?? '');
        if (!$allowEmpty && empty($customerObject->City)) {
            throw new Exception("De plaats is leeg.");
        }
        $customerObject->PostalCode = isset($postData['billing_postcode']) ?
            sanitize_text_field($postData['billing_postcode']) :
            ($customerFields['customer_postal_code'] ?? '');
        if (!$allowEmpty && empty($customerObject->PostalCode)) {
            throw new Exception("De postcode is leeg.");
        }
        $customerObject->EmergencyContactName = isset($postData['emergencycontactname']) ?
            sanitize_text_field($postData['emergencycontactname']) :
            ($customerFields['customer_emergency_contact_name'] ?? '');
        if (!$allowEmpty && empty($customerObject->EmergencyContactName)) {
            throw new Exception("De naam van de thuisblijver is leeg.");
        }
        $customerObject->EmergencyContactPhone = isset($postData['emergencycontactphone']) ?
            sanitize_text_field($postData['emergencycontactphone']) :
            ($customerFields['customer_emergency_contact_phone'] ?? '');
        if (!$allowEmpty && empty($customerObject->EmergencyContactPhone)) {
            throw new Exception("Het telefoonnummer van de thuisblijver is leeg.");
        }
        // Non-required fields.
        $customerObject->Sex = boolval(isset($postData['gender']) ?
            sanitize_text_field($postData['gender']) :
            ($customerFields['customer_gender'] ?? 0)) ? 1 : 0;
        $customerObject->Country = isset($postData['billing_country']) ?
            sanitize_text_field($postData['billing_country']) :
            ($customerFields['customer_country'] ?? '');
        $customerObject->Nationality = isset($postData['nationality']) ?
            sanitize_text_field($postData['nationality']) :
            ($customerFields['customer_nationality'] ?? '');
        $customerObject->Note = isset($postData['note']) ?
            sanitize_text_field($postData['note']) :
            ($customerFields['customer_note'] ?? '');
        $customerObject->DietaryWishes = isset($postData['dietary_wishes']) ?
            sanitize_text_field($postData['dietary_wishes']) :
            ($customerFields['customer_dietary_wishes'] ?? '');
        return $customerObject;
    }

    /**
     * Parses the document fields and post data parameters into an array of document objects.
     * Post data has priority over the document fields.
     *
     * @param array $documentsFields Array containing all the fields per available Document.
     * @param array $postData Array containing POST values obtained through the form-edit-account template.
     * @param bool $allowEmpty Whether or not to allow empty values for the required fields.
     * @return stdClass[] Objects containing fields that represent the current state of a document in a format accepted
     * by the Assumax API.
     * @throws Exception Will be thrown when required fields are empty and $allowEmpty equals false.
     */
    private static function parse_document_data(array $documentsFields, array $postData, bool $allowEmpty = false) : array
    {
        $key = Key::loadFromAsciiSafeString(NOSUN_CRYPTO_KEY);
        if (empty($key)) {
            error_log("[User->parse_document_data]: No encryption key setup.");
            throw new Exception("Er ging iets fout tijdens het verwerken van de persoonsgegevens, neem alsjeblieft contact met ons op.");
        }
        // Combine the two arrays into a single document array which can then be parsed in one pass.
        $documentData = [];
        foreach ($documentsFields as $postId => $documentFields) {
            $documentData[$postId] = [
                'fields' => $documentFields,
                'post' => []
            ];
        }
        foreach ($postData as $data) {
            $postId = 0;
            if (!empty($data['id'])) {
                try {
                    $postId = Crypto::decrypt($data['id'], $key);
                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (WrongKeyOrModifiedCiphertextException $e) {
                    error_log("[Users->parse_document_data]: {$e->getMessage()}");
                    throw new Exception("Er ging iets fout tijdens het verwerken van de persoonsgegevens, neem alsjeblieft contact met ons op.");
                }
            }
            if (key_exists($postId, $documentData)) {
                $documentData[$postId]['post'] = $data;
            } else {
                $documentData[$postId] = [
                    'fields' => [],
                    'post' => $data
                ];
            }
        }
        // Loop through all the documentData and parse each into a document object.
        $documentObjects = [];
        foreach ($documentData as $postId => $data) {
            $documentObject = new stdClass();
            $documentObject->DocumentType = isset($data['post']['type']) ? intval(sanitize_text_field($data['post']['type'])) : ($data['fields']['document_type'] ?? -1);
            if (!$allowEmpty && $documentObject->DocumentType === -1) {
                // Ignore any documents that do not have the document type filled in.
                continue;
            }
            $documentObject->Title = isset($data['post']['number']) ? sanitize_text_field($data['post']['number']) : ($data['fields']['document_title'] ?? '');
            if (!$allowEmpty && empty($documentObject->Title)) {
                throw new Exception("Het document nummer is leeg.");
            }
            $documentObject->Expires = isset($data['post']['expires']) ? sanitize_text_field($data['post']['expires']) : ($data['fields']['document_expires'] ?? '');
            if (!$allowEmpty && empty($documentObject->Expires)) {
                throw new Exception("De verloopdatum bij het document is leeg.");
            }
            $documentObject->City = isset($data['post']['city']) ? sanitize_text_field($data['post']['city']) : ($data['fields']['document_city'] ?? '');
            if (!$allowEmpty && empty($documentObject->City)) {
                throw new Exception("De plaats van afgifte bij het document is leeg.");
            }
            $documentObject->CountryId = isset($data['post']['country']) ? intval(sanitize_text_field($data['post']['country'])) : ($data['fields']['document_country_id'] ?? -1);
            if (!$allowEmpty && $documentObject->CountryId === -1) {
                throw new Exception("Het land van afgifte bij het document is leeg.");
            }
            $documentObjects[$postId] = $documentObject;
        }
        return $documentObjects;
    }

    /**
     * Updates the customer fields for the user with the provided id.
     * Should a customer not already exist for the current user and the user isn't waiting on the assignment process,
     * then a new customer is created.
     *
     * @param int $userId The id of the user.
     */
    public static function save_customer_fields($userId) {
        if (!current_user_can('edit_user', $userId)) {
            Helpers::redirect_with_status(
                get_the_permalink(),
                'error',
                "Je hebt niet de rechten om de gegevens van deze gebruiker te wijzigen.");
        }
        $myAccountPage = get_page_by_path('mijn-account/gegevens-wijzigen');
        if (empty($myAccountPage)) {
            Helpers::redirect_with_status(
                get_the_permalink(),
                'error',
                "Er is iets fout gegaan tijdens het wijzigen van de gegevens. Neem alsjeblieft contact met ons op.");
        }
        $customer = Customers::get_by_user_id($userId);
        try {
            $customerObject = self::parse_customer_data($userId, [], $_POST);
        } catch (Exception $e) {
            Helpers::redirect_with_status(
                get_the_permalink($myAccountPage),
                'error',
                $e->getMessage(),
                'previous_data',
                $_POST
            );
        }
        // If no customer exists, insert a new one.
        $customerAPIData = null;
        if (empty($customer)) {
            /** @noinspection PhpUndefinedVariableInspection */
            $customerAPIData = Customers::insert_into_api($customerObject);
            if (empty($customerAPIData)) {
                Helpers::redirect_with_status(
                    get_the_permalink($myAccountPage),
                    'error',
                    "Er is iets fout gegaan tijdens het wijzigen van de gegevens. Neem alsjeblieft contact met ons op.",
                    'previous_data',
                    $_POST);
            }
            // Acquire a lock on the Customer updates.
            $lock = Locks::acquire_lock($customerAPIData->Id, 'Customer', 'Created', true);
            $customerAssumaxId = $customerAPIData->Id;
            // Set the customer_assumax_id on the user which lets it assign itself on subsequent init hooks.
            update_user_meta($userId, 'customer_assumax_id', $customerAPIData->Id);
        } else {
            $customerAssumaxId = get_post_meta($customer->ID, '_assumax_id', true);
            /** @noinspection PhpUndefinedVariableInspection */
            $customerObject->Id = $customerAssumaxId;
            // Acquire a lock on the Customer updates before we insert anything into the API to prevent collisions.
            $lock = Locks::acquire_lock($customerAssumaxId, 'Customer', 'Modified', true);
            if (empty(Customers::update_api_data($customerAssumaxId, $customerObject))) {
                Helpers::redirect_with_status(
                    get_the_permalink($myAccountPage),
                    'error',
                    "Er is iets fout gegaan tijdens het wijzigen van de gegevens. Neem alsjeblieft contact met ons op.",
                    'previous_data',
                    $_POST);
            }
        }
        // Handle the documents if there are any.
        if (!empty($_POST['documents']) && is_array($_POST['documents'])) {
            $documentObjects = [];
            try {
                $documentObjects = self::parse_document_data([], $_POST['documents']);
            } catch (Exception $e) {
                Helpers::redirect_with_status(
                    get_the_permalink($myAccountPage),
                    'error',
                    "Één of meer velden zijn leeg bij de documenten.",
                    'previous_data',
                    $_POST);
            }
            foreach ($documentObjects as $documentPostId => $documentObject) {
                if ($documentPostId === -1) {
                    if (empty(Documents::insert_into_api($documentObject, $customerAssumaxId))) {
                        error_log("[User->save_customer_fields]: Could not insert a new document for customer with id: {$customerAssumaxId} into the API.");
                        Helpers::redirect_with_status(
                            get_the_permalink($myAccountPage),
                            'error',
                            "Er is iets fout gegaan tijdens het toevoegen van het document. Neem alsjeblieft contact met ons op.",
                            'previous_data',
                            $_POST);
                    }
                } else {
                    $documentObject->Id = get_post_meta($documentPostId, '_assumax_id', true);
                    if (empty(Documents::update_in_api($documentObject, $customerAssumaxId))) {
                        error_log("[User->save_customer_fields]: Could not update document with id: {$documentObject->Id} for customer with id: {$customerAssumaxId} into the API.");
                        Helpers::redirect_with_status(
                            get_the_permalink($myAccountPage),
                            'error',
                            "Er is iets fout gegaan tijdens het wijzigen van de document gegevens. Neem alsjeblieft contact met ons op.",
                            'previous_data',
                            $_POST);
                    }
                }
            }
        }
        // Upsert the Customer if there is a lock available.
        if ($lock) {
            $result = Customers::upsert($customerAssumaxId, $customerAPIData);
            Locks::release_lock($lock);
            if (empty($result)) {
                error_log("[User->save_customer_fields]: Could not upsert customer with id: {$customerAssumaxId}.");
                Helpers::redirect_with_status(
                    get_the_permalink($myAccountPage),
                    'error',
                    "Er is iets fout gegaan tijdens het wijzigen van de gegevens. Neem alsjeblieft contact met ons op.",
                    'previous_data',
                    $_POST);
            }
        }
    }

    /**
     * Obtains the booking (shop_order) posts objects for the customer with the provided post id.
     *
     * @param int $customerPostId The post id of the customer whose bookings need to be obtained.
     * @return WP_Post[] An array of posts of type 'shop_order' belonging to the customer or an empty array should
     * something go wrong.
     */
    public static function get_customer_bookings($customerPostId) {
        if (empty($customerPostId)) {
            return [];
        }
        $customerAssumaxId = get_post_meta($customerPostId, '_assumax_id', true);
        if (empty($customerAssumaxId)) {
            return [];
        }
        global $wpdb;
        $query = "SELECT booking_assumax_id FROM api_booking_customer_pivot
                    WHERE customer_assumax_id = {$customerAssumaxId};";
        $bookingAssumaxIds = $wpdb->get_col($query);
        return empty($bookingAssumaxIds) ? [] : Bookings::get_by_assumax_ids($bookingAssumaxIds, true);
    }

    /**
     * Counts the number of bookings that user has made.
     *
     * @param int $userId The wordpress id of the user.
     * @return int The number of bookings the user has.
     */
    public static function count_user_bookings(int $userId)
    {
        if (empty($userId)) {
            return 0;
        }
        $customer = Customers::get_by_user_id($userId);
        if (empty($customer)) {
            return 0;
        }
        $customerAssumaxId = get_post_meta($customer->ID, '_assumax_id', true);
        if (empty($customerAssumaxId)) {
            return 0;
        }
        global $wpdb;
        $query = "SELECT count(customer_assumax_id) FROM api_booking_customer_pivot
                    WHERE customer_assumax_id = {$customerAssumaxId};";
        return $wpdb->get_var($query);
    }

    /**
     * Obtains the full name (nickname + surname) for either the provided customer post id or the provided user id.
     *
     * @param string $customerId Optional customer post id, which will take precedence over any user id.
     * @param string $userId Optional user id which will only be used if there is no customer post id.
     * @return string The full name of customer or an empty string if it can't be obtained.
     */
    public static function get_customer_full_name($customerId = null, $userId = null) {
        if (!empty($customerId)) {
            $nickName = get_field('customer_nick_name', $customerId);
            $lastName = get_field('customer_last_name', $customerId);
            return "{$nickName} {$lastName}";
        } elseif (!empty($userId)) {
            $nickName = get_user_meta($userId, 'nickname', true);
            $lastName = get_user_meta($userId, 'last_name', true);
            return "{$nickName} {$lastName}";
        }
        return "";
    }

    /**
     * Handles the upload process of a user profile image.
     */
    public static function upload_profile_image() {
        if (isset($_POST['profile_image_nonce']) && wp_verify_nonce($_POST['profile_image_nonce'], 'profile_image')) {
            // These files need to be included as dependencies when on the front end.
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            // Create a new randomized filename for the uploaded file. But only while uploading a profile image.
            add_filter('sanitize_file_name', [User::class, 'randomize_filename_filter'], 10);
            $attachmentId = media_handle_upload('profile_image', 0);
            remove_filter('sanitize_file_name', [User::class, 'randomize_filename_filter'], 10);
            $filenameOnly = preg_replace('/\\.[^.\\s]{2,4}$/', '', basename(get_attached_file($attachmentId)));
            $updatedPost = array(
                'ID' => $attachmentId,
                'post_title' => $filenameOnly,
                'post_name' => $filenameOnly
            );
            wp_update_post($updatedPost);
            update_user_meta(get_current_user_id(), 'profile_image', $attachmentId);
            if (is_wp_error($attachmentId)) {
                $message = [
                    'status' => 'error',
                    'error' => $attachmentId,
                    'redirect_url' => ''
                ];
            } else {
                $message = [
                    'status' => 'success',
                    'redirect_url' => site_url('/mijn-account/gegevens-wijzigen/?profile_image_updated')
                ];
            }
            wp_send_json($message);
        }
        wp_die();
    }

    /**
     * A filter that creates a new GUID which will be used as a 'randomized' filename.
     * Should only be used while uploading a profile image. So remove this filter after the upload has been completed.
     *
     * @param string $filename The original filename.
     * @return string The new randomized filename.
     */
    public static function randomize_filename_filter($filename) {
        $info = pathinfo($filename);
        $ext  = empty($info['extension']) ? '' : '.' . $info['extension'];
        return wp_generate_uuid4() . $ext;
    }

    /**
     * Should the current user be logged in and have the field customer_assumax_id set to a non empty value, then
     * an attempt is made to assign the current user to the customer with that id.
     */
    public static function assign_to_customer() {
        $userId = get_current_user_id();
        if (empty($userId)) return;
        $assumaxId = get_user_meta($userId ,'customer_assumax_id', true);
        if (empty($assumaxId)) return;
        $customer = Customers::get_by_assumax_id($assumaxId);
        if (empty($customer)) return;
        update_user_meta($userId, 'user_customer', $customer->ID);
        delete_user_meta($userId, 'customer_assumax_id');
    }
}

/**
 * Add several hooks.
 */
add_action('wp_ajax_profile_image', [User::class, 'upload_profile_image']);
add_action('wp_ajax_nopriv_profile_image', [User::class, 'upload_profile_image']);
add_action('personal_options_update', [User::class, 'save_customer_fields']);
add_action('edit_user_profile_update', [User::class, 'save_customer_fields']);
add_action('woocommerce_save_account_details', [User::class, 'save_customer_fields'], 12, 1);
add_action('init', [User::class, 'assign_to_customer'], 9);
