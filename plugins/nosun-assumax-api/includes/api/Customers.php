<?php
/** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */

namespace Vazquez\NosunAssumaxConnector\Api;

use Exception;
use lib\controllers\User;
use lib\woocommerce_reports\models\UpdateLog;
use WP_Post;

/**
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Customers implements ILoadable {
    /**
     * Obtains the Customer which AssumaxId is equal to the one provided.
     *
     * @param string $assumaxId The Assumax Id for the Customer.
     * @return WP_Post | null A Customer that has the provided Assumax Id, or null if something fails.
     */
    public static function get_by_assumax_id($assumaxId) {
        if (empty($assumaxId)) return null;
        $customers = self::get_by_assumax_ids([$assumaxId], 1);
        return empty($customers) ? null : $customers[0];
    }

    /**
     * Obtains the Customers which have Assumax Ids equal to one inside the provided array of ids.
     *
     * @param array $assumaxIds An array of Assumax Ids.
     * @param int $limit The maximum amount of customers to return.
     * @return WP_Post[] The list of customers that correspond to the provided ids or an empty list if something went
     * wrong.
     */
    public static function get_by_assumax_ids($assumaxIds, $limit = -1) {
        if (empty($assumaxIds)) return [];
        $args = [
            'post_type' => 'customer',
            'post_status' => get_post_statuses(),
            'numberposts' => $limit,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_assumax_id',
                    'value' => $assumaxIds,
                    'compare' => 'IN'
                ]
            ]
        ];
        return get_posts($args);
    }

    /**
     * Obtains the Customer that belongs to the provided user id.
     *
     * @param int $userId The user id for which to obtain the Customer.
     * @return WP_Post|null The Customer post object belonging to the user id.
     */
    public static function get_by_user_id($userId) {
        if (empty($userId)) return null;
        $customer = get_user_meta($userId, 'user_customer', true);
        if (empty($customer)) return null;
        return get_post($customer);
    }

    /**
     * Returns all the customers for which their birthdate is today (includes leapyears).
     *
     * @return int[] List of customer post ids.
     */
    public static function get_by_birthdate() : array
    {
        global $wpdb;
        $query = "SELECT post_id FROM tri_postmeta
                    WHERE meta_key = 'customer_date_of_birth' 
                    AND (
                        (date_format(meta_value,\"%m-%d\") = date_format(now(),\"%m-%d\")) OR
                        ((date_format(meta_value,\"%m-%d\") = '02-29' AND date_format(now(), '%m') = '02' AND last_day(now()) = DATE(now())))
                    );";
        return $wpdb->get_col($query);
    }

    /**
     * Obtains the user id of the user that is claiming this Customer.
     *
     * @param int $customerPostId The post id of the Customer.
     * @return string|null The user id or null when the Customer isn't claimed.
     */
    public static function get_user_id($customerPostId) {
        global $wpdb;
        $query = sprintf("SELECT user_id FROM %s WHERE meta_key='user_customer' AND meta_value='%s' LIMIT 1;",
            $wpdb->usermeta, $customerPostId);
        return $wpdb->get_var($query);
    }

    /**
     * Pulls all the available customers from the API and upserts them.
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
        $clientIterator = $client->get_all("/customers", []);
        foreach ($clientIterator as $customerData) {
            if (empty($customerData->Id)) continue;
            error_log("[Api\Customers->upsert_all_from_api]: Upserting customer with id: {$customerData->Id}.");
            if (self::upsert($customerData->Id, $customerData) === false) {
                $unsuccessful[] = $customerData->Id;
            } else {
                $successful[] = $customerData->Id;
            }
        }
        error_log("[Api\Customers->upsert_all_from_api]: Total amount: {$clientIterator->get_size()}.");
        error_log(sprintf("[Api\Customers->upsert_all_from_api]: List of successful updates: %s.", implode(', ', $successful)));
        error_log(sprintf("[Api\Customers->upsert_all_from_api]: List of unsuccessful updates: %s.", implode(', ', $unsuccessful)));
        return true;
    }

    /**
     * Pulls the current state of the Customer with the provided Assumax Id from the API and either inserts a new
     * post should it not exist or updates the existing one should it exist.
     *
     * @param int $assumaxId The Assumax Id for the Customer. Only used when the data needs to be pulled from the API.
     * @param object $customerData Optional API data to use instead of pulling it from the API.
     * @return int|false The Assumax Id of the Customer or false should something go wrong.
     */
    public static function upsert($assumaxId, $customerData = null) {
        ini_set('memory_limit', '256M');
        set_time_limit(600);
        if (empty($assumaxId)) return false;
        if (!empty($customerData)) {
            $data = $customerData;
        } else {
            try {
                $client = AssumaxClient::getInstance();
            } catch (Exception $e) {
                error_log("[Nosun_Assumax_Api_Customers->upsert]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
                return false;
            }
            $data = $client->get("/customers/{$assumaxId}");
        }
        if (empty($data)) {
            error_log("[Nosun_Assumax_Api_Customers->upsert]: Could not obtain the customer with Id: {$assumaxId} from the API.");
            return false;
        }
        $customer = self::get_by_assumax_id($data->Id);
        if (empty($customer)) {
            return self::create_customer($data);
        } else {
            return self::update_customer($customer, $data);
        }
    }

    /**
     * Create a new customer with the provided data.
     *
     * @param mixed $data The Customer data received from the API.
     * @return int|false Either the Assumax Id of the Customer or false should something fail.
     */
    private static function create_customer($data) {
        if (empty($data)) return false;
        if (empty($data->Id)) {
            error_log("[Nosun_Assumax_Api_Customers->create_customer]: No Assumax Id present in the API data.");
            return false;
        }
        if(empty($data->FirstName) && !empty($data->NickName)) {
        	error_log('create_customer setting $data->FirstName');
        	$data->FirstName = $data->NickName;
        }
        if (empty($data->FirstName) || empty($data->LastName)) {
            error_log("[Nosun_Assumax_Api_Customers->create_customer]: Either the FirstName or the LastName is empty for the customer with Assumax Id {$data->Id}.");
            return false;
        }
        $postArgs = [
            'post_title' => sanitize_title(sprintf("%s %s", $data->FirstName, $data->LastName)),
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'customer'
        ];
        $customerPostId = wp_insert_post($postArgs);
        if (is_wp_error($customerPostId)) {
            error_log("[Nosun_Assumax_Api_Customers->create_customer]: Could not insert a new customer post for the customer with Assumax Id: {$data->Id}.");
            error_log("[Nosun_Assumax_Api_Customers->create_customer]: {$customerPostId->get_error_message()}.");
            return false;
        }
        update_post_meta($customerPostId, '_assumax_id', $data->Id);
        if (!self::update_customer_meta($customerPostId, $data)) {
	        error_log('create customer -> self::update_customer_meta failed');
            error_log("[Nosun_Assumax_Api_Customers->create_customer]: Could not update the post meta for the customer with Assumax Id: {$data->Id}.");
            return false;
        }
        // Update the documents if there are any.
        self::upsert_documents($customerPostId, $data->Id);
        // Update the customer report data.
        do_action('vazquez_upsert_customer_report_data', $customerPostId, $data);

        return $data->Id;
    }

    /**
     * Updates the target customer's names and meta values.
     *
     * @param WP_Post $customer The customer object which needs to be updated.
     * @param mixed $data Customer data from the API.
     * @return int|bool The Assumax Id if the customer is updated successfully or false if otherwise.
     */
    private static function update_customer($customer, $data) {
        if (empty($customer) || empty($data) || empty($data->Id)) return false;
        if(empty($data->FirstName) && !empty($data->NickName)) {
        	error_log('update_customer setting $data->FirstName');
        	$data->FirstName = $data->NickName;
        }
        if (empty($data->FirstName) || empty($data->LastName)) {
            error_log("[Nosun_Assumax_Api_Customers->update_customer]: Either the FirstName or the LastName is empty for the customer with Assumax Id {$data->Id}.");
            return false;
        }
        $postArgs = [
            'ID' => $customer->ID,
            'post_title' => sanitize_title(sprintf("%s %s", $data->FirstName, $data->LastName))
        ];
        $customerPostId = wp_update_post($postArgs);
        if (is_wp_error($customerPostId)) {
            error_log("[Nosun_Assumax_Api_Customers->update_customer]: Could not update the user with user id: {$customer->ID}.");
            error_log("[Nosun_Assumax_Api_Customers->update_customer]: {$customerPostId->get_error_message()}.");
            return false;
        }
        if (!self::update_customer_meta($customerPostId, $data)) {
	        error_log('update customer -> self::update_customer_meta failed');
            error_log("[Nosun_Assumax_Api_Customers->update_customer]: Could not update the meta fields for the customer with Assumax Id: {$data->Id}.");
            return false;
        }
        // Update the documents if there are any.
        self::upsert_documents($customerPostId, $data->Id);
        // Update the customer report data.
        do_action('vazquez_upsert_customer_report_data', $customerPostId, $data);

        return $data->Id;
    }

    /**
     * Updates the target Customer's meta values with the data provided.
     *
     * @param int $customerPostId The post id for the Customer whose meta fields need to be updated.
     * @param mixed $data Customer data from the API.
     * @return bool The value true when all the fields have been updated, the value false when something fails.
     */
    private static function update_customer_meta($customerPostId, $data) {
        if (empty($customerPostId) || empty($data)) return false;
        if (empty($data->FirstName) || empty($data->LastName) || empty($data->NickName) || empty($data->EmailAddress)) {
            error_log("[Nosun_Assumax_Api_Customers->update_customer_meta]: One of the required fields is empty.");
            return false;
        }
	    $user = User::upsert_user_account($customerPostId, $data);

        update_field('customer_first_name', $data->FirstName, $customerPostId);
        update_field('customer_last_name', $data->LastName, $customerPostId);
        update_field('customer_nick_name', $data->NickName, $customerPostId);
        update_field('customer_email_address', $data->EmailAddress, $customerPostId);
        update_field('customer_prefix', isset($data->Prefix) ? $data->Prefix : '', $customerPostId);
        update_field('customer_date_of_birth', isset($data->DateOfBirth) ? date('Y-m-d', strtotime($data->DateOfBirth)) : '', $customerPostId);
        update_field('customer_gender', isset($data->Sex) ? $data->Sex : '', $customerPostId);
        update_field('customer_nationality', isset($data->Nationality) ? $data->Nationality : '', $customerPostId);
        update_field('customer_emergency_contact_name', isset($data->EmergencyContactName) ? $data->EmergencyContactName : '', $customerPostId);
        update_field('customer_emergency_contact_phone', isset($data->EmergencyContactPhone) ? $data->EmergencyContactPhone : '', $customerPostId);
        update_field('customer_street', isset($data->Street) ? $data->Street : '', $customerPostId);
        update_field('customer_street_number', isset($data->StreetNumber) ? $data->StreetNumber : '', $customerPostId);
        update_field('customer_city', isset($data->City) ? $data->City : '', $customerPostId);
        update_field('customer_postal_code', isset($data->PostalCode) ? $data->PostalCode : '', $customerPostId);
        update_field('customer_phone_number', isset($data->PhoneNumber) ? $data->PhoneNumber : '', $customerPostId);
        update_field('customer_dietary_wishes', isset($data->DietaryWishes) ? $data->DietaryWishes : '', $customerPostId);
        update_field('customer_note', isset($data->Note) ? $data->Note : '', $customerPostId);
        update_post_meta($customerPostId, 'customer_bookings', !empty($data->Bookings) ? array_column($data->Bookings, 'Id') : []);
        update_post_meta($customerPostId ,'customer_documents', !empty($data->Documents) ? array_column($data->Documents, 'Id') : []);
        return true;
    }

    /**
     * Obtains the documents for the provided customer post id and upserts each one available.
     *
     * @param int $customerPostId The post if of the customer whose documents need to be upserted.
     * @param int $customerAssumaxId The Assumax Id of the customer.
     */
    private static function upsert_documents($customerPostId, $customerAssumaxId) {
        $documentIds = get_post_meta($customerPostId, 'customer_documents', true);
        if (!empty($documentIds)) {
            foreach ($documentIds as $documentId) {
                $lock = Locks::acquire_lock($documentId, 'Document', 'Modified', true);
                if ($lock && empty(Documents::upsert($customerAssumaxId, $documentId))) {
                    error_log("[Nosun_Assumax_Api_Customers->upsert_documents]: Could not upsert the document with Assumax Id: {$documentId} for customer with Assumax Id: {$customerAssumaxId}.");
                }
                Locks::release_lock($lock);
            }
        }
    }

    /**
     * Puts the new customer to the API and returns the Customer data from the API should it be successful.
     *
     * @param mixed $data The customer data in the same format as the api.
     * @return mixed|null The API data of the newly inserted customer or null if something went wrong.
     */
    public static function insert_into_api($data) {
        if (empty($data)) return null;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Customers->insert_into_api]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return null;
        }
        if (empty($data->EmailAddress) || empty($data->FirstName) || empty($data->LastName) ||
            empty($data->NickName) || empty($data->DateOfBirth)) {
            error_log("[Nosun_Assumax_Api_Customers->insert_into_api]: One of the required fields is empty.");
            return null;
        }
        $apiData = self::create_api_array($data);
        if (empty($apiData)) {
            error_log("[Nosun_Assumax_Api_Customers->insert_into_api]: There are no fields to insert.");
            return null;
        }
        $customerApiData = $client->put('customers', $apiData, true);
        if (empty($customerApiData)) {
            error_log("[Nosun_Assumax_Api_Customers->insert_into_api]: An error occurred while trying to put new customer data into the API.");
            return null;
        }
        return $customerApiData;
    }

    /**
     * Updates the data in the API for the customer with the provided Assumax Id.
     *
     * @param int $assumaxId The Assumax Id of the customer that needs to be updated in the API.
     * @param mixed $data The customer data in the same format as the API.
     * @return int|null Either the Assumax Id of the customer or null should something go wrong.
     */
    public static function update_api_data($assumaxId, $data) {
        if (empty($data) || empty($assumaxId)) return null;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Customers->update_api_data]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return null;
        }
        $apiData = self::create_api_array($data);
        if (empty($apiData)) {
            error_log("[Nosun_Assumax_Api_Customers->update_api_data]: There are no fields to insert.");
            return null;
        }
        $result = $client->post("customers/{$assumaxId}", $apiData, true);
        if ($result === false) {
            error_log("[Nosun_Assumax_Api_Customers->update_api_data]: An error occurred while trying to post updated customer data into the API.");
            return null;
        }
        return $assumaxId;
    }

    /**
     * Create an array with the fields provided in the data object.
     *
     * @param mixed $data Object which holds Customer data in the same format as the API.
     * @return array The customer data formatted into an array that is accepted by the API.
     */
    private static function create_api_array($data) {
        if (empty($data)) return [];
        $apiData = [];
        if (isset($data->EmailAddress)) $apiData['EmailAddress'] = $data->EmailAddress;
        if (isset($data->FirstName)) $apiData['FirstName'] = $data->FirstName;
        if (isset($data->LastName)) $apiData['LastName'] = $data->LastName;
        if (isset($data->NickName)) $apiData['NickName'] = $data->NickName;
        if (isset($data->DateOfBirth)) $apiData['DateOfBirth'] = date('Y-m-d\TH:i:s', strtotime($data->DateOfBirth));
        if (isset($data->Prefix)) $apiData['Prefix'] = $data->Prefix;
        if (isset($data->PhoneNumber)) $apiData['PhoneNumber'] = $data->PhoneNumber;
        if (isset($data->Sex)) $apiData['Sex'] = $data->Sex ? '1' : '0';
        if (isset($data->Nationality)) $apiData['Nationality'] = $data->Nationality;
        if (isset($data->Street)) $apiData['Street'] = $data->Street;
        if (isset($data->StreetNumber)) $apiData['StreetNumber'] = $data->StreetNumber;
        if (isset($data->PostalCode)) $apiData['PostalCode'] = $data->PostalCode;
        if (isset($data->City)) $apiData['City'] = $data->City;
        if (isset($data->DietaryWishes)) $apiData['DietaryWishes'] = $data->DietaryWishes;
        if (isset($data->EmergencyContactName)) $apiData['EmergencyContactName'] = $data->EmergencyContactName;
        if (isset($data->EmergencyContactPhone)) $apiData['EmergencyContactPhone'] = $data->EmergencyContactPhone;
        if (isset($data->Note)) $apiData['Note'] = $data->Note;
        return $apiData;
    }

    /**
     * Sets the Customer with the provided Assumax Id to deleted.
     * Note: Unlike the other post models, Customers will be hard deleted from the website and all assigned posts
     * will be transferred to the user with id 1.
     *
     * @param string $assumaxId The Assumax Id of the Customer.
     * @return bool true when the Customer has successfully been set to deleted, false if otherwise.
     */
    public static function delete($assumaxId) {
        $customer = self::get_by_assumax_id($assumaxId);
        if (!isset($customer)) {
            error_log("[Nosun_Assumax_Api_Customers->delete]: There exists no customer with Assumax Id: {$assumaxId}.]");
            return false;
        }
        if (empty(wp_delete_post($customer->ID))) return false;
        return true;
    }

    /**
     * Callback function for the Customer webhook that should be called via a Post request by the Assumax ERP.
     *
     * @param string $assumaxId The Assumax id of the Customer.
     * @param string $action The action to perform as supplied by Assumax.
     */
    public static function webhook_callback($assumaxId, $action) {
        do_action('vazquez_add_update_log_entry', 'Customer', $action, $assumaxId);
        if ($action === 'Created' || $action === 'Modified') {
            self::upsert($assumaxId);
        }
        elseif ($action === 'Deleted') {
            self::delete($assumaxId);
        } else {
            error_log("[Nosun_Assumax_Api_Customers->webhook_callback]: Action: {$action} for id: {$assumaxId} is not supported.]");
        }
    }

    /**
     * Registers the reiziger custom post type which will be used extensively by this API.
     */
    public static function register_posttype() {
        $labels = array (
            'name'               => 'Reizigers',
            'singular_name'      => 'Reiziger',
            'add_new'            => 'Toevoegen',
            'add_new_item'       => 'Reiziger toevoegen',
            'edit_item'          => 'Bewerk reiziger',
            'new_item'           => 'Nieuw',
            'view_item'          => 'Bekijk reiziger',
            'search_items'       => 'Zoek reizigers',
            'not_found'          => 'Geen reizigers gevonden',
            'not_found_in_trash' => 'Geen reizigers gevonden in prullenbak'
        );

        $args = array (
            'label'               => 'Reizigers',
            'description'         => 'Reizigers',
            'labels'              => $labels,
            'supports'            => array('title'),
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-universal-access-alt',
            'can_export'          => false,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'post',
            'rewrite'             => array('slug' => 'reiziger', 'with_front' => false)
        );

        register_post_type('customer', $args);
    }

    /**
     * Downloads a document from the API using the parameters provided.
     *
     * @param int $customerAssumaxId The Assumax id of the customer who holds the document.
     * @param int $documentId The Assumax id of the document.
     * @return null|string Either the document data or null should something go wrong.
     */
    public static function download_customer_document($customerAssumaxId, $documentId) {
        if (empty($customerAssumaxId) || empty($documentId) ||
            intval($customerAssumaxId) === 0 || intval($documentId) === 0) return null;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Customers->download_customer_document]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return null;
        }
        $document = $client->get("customers/{$customerAssumaxId}/document/{$documentId}", [], false, false);
        return ($document === false) ? null : $document;
    }

    /**
     * Downloads an invoice from the API using the parameters provided.
     *
     * @param int $customerAssumaxId The Assumax Id of the customer who holds the invoice.
     * @param int $invoiceId The Assumax Id of the invoice.
     * @return null|string Either the invoice data or null should something go wrong.
     */
    public static function download_customer_invoice($customerAssumaxId, $invoiceId) {
        if (empty($customerAssumaxId) || empty($invoiceId) ||
            intval($customerAssumaxId) === 0 || intval($invoiceId) === 0) return null;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Customers->download_customer_invoice]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return null;
        }
        $invoice = $client->get("customers/{$customerAssumaxId}/invoice/{$invoiceId}", [], false, false);
        return ($invoice === false) ? null : $invoice;
    }

    /**
     * Downloads an invoice from the API and saves it in the private html folder.
     *
     * @param int $customerAssumaxId The Assumax Id of the Customer.
     * @param int $invoiceId The Id of the Invoice.
     * @return string The filepath to the downloaded invoice.
     * @throws Exception When the Invoice cannot be downloaded.
     */
    public static function download_customer_invoice_file($customerAssumaxId, $invoiceId) : string
    {
        $fileName = "klant_{$customerAssumaxId}_factuur_{$invoiceId}.PDF";
        $invoiceData = self::download_customer_invoice($customerAssumaxId, $invoiceId);
        if (empty($invoiceData)) {
            throw new Exception("Could not download the invoice with id: {$invoiceId} for customer: {$customerAssumaxId}.");
        }
        $filePath = path_join(PRIVATE_HTML_FOLDER, $fileName);
        if (file_put_contents($filePath, $invoiceData) === false) {
            throw new Exception("Could not write the invoice with filepath: {$filePath} to disk.");
        }
        return $filePath;
    }

    /**
     * Obtains all the Customer post titles indexed by their Assumax Id.
     *
     * @return array A list of all the Customer post titles indexed by their Assumax Id.
     */
    public static function get_repeater_values() {
        global $wpdb;
        $query = sprintf("SELECT post_title, meta_value FROM %s JOIN %s ON ID=post_id AND post_type = 'customer' WHERE meta_key = '_assumax_id';",
            $wpdb->posts, $wpdb->postmeta);
        $results = $wpdb->get_results($query);
        $customers = [];
        foreach ($results as $result) {
            $customers[$result->meta_value] = $result->post_title;
        }
        return $customers;
    }

    /**
     * Adds and removes several columns to the customer post list on the admin screen.
     *
     * @param mixed $columns The current set of columns.
     * @return mixed The modified set of columns.
     */
    public static function manage_customer_admin_columns($columns) {
        // Add new columns.
        $columns['email'] = __('Email adres', 'nosun_assumax');
        $columns['nickname'] = __('Roepnaam', 'nosun_assumax');
        $columns['assumax_id'] = __('Assumax Id', 'nosun_assumax');
        $columns['update_assumax'] = __('Update uit Assumax', 'nosun_assumax');
        return $columns;
    }

    /**
     * Fills in the extra customer columns depending on the column name provided.
     *
     * @param string $column The column name.
     * @param int $postId The post id of the customer.
     */
    public static function manage_customer_admin_column($column, $postId) {
        switch ($column) {
            case 'assumax_id': {
                echo get_post_meta($postId, '_assumax_id', true);
                break;
            }
            case 'email': {
                echo get_field('customer_email_address', $postId);
                break;
            }
            case 'nickname': {
                echo get_field('customer_nick_name', $postId);
                break;
            }
            case 'update_assumax': {
                $assumaxId = get_post_meta($postId, '_assumax_id', true);
                echo "<button class='update-assumax' data-assumax_id='{$assumaxId}' data-action='update_assumax_customer'>Update</button>";
                break;
            }
        }
    }

    /**
     * AJAX function that upserts the Customer with the Assumax Id provided in the POST global.
     */
    public static function ajax_update_customer() {
        if (!is_admin()) return;
        $assumaxId = $_POST['assumax_id'];
        if (empty($assumaxId)) return;
        $lock = Locks::acquire_lock($assumaxId, 'Customer', 'Modified', 1);
        if ($lock > 0) {
            static::upsert($assumaxId);
        }
        Locks::release_lock($lock);
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
            "Item" => "Customer",
            "Action" => "Created",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Customers->vazquez_webhooks_setup]: Could not add the Created action webhook for the Customers class.");
        }
        $result = $client->put("/webhooks", [
            "Item" => "Customer",
            "Action" => "Modified",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Customers->vazquez_webhooks_setup]: Could not add the Modified action webhook for the Customers class.");
        }
        $result = $client->put("/webhooks", [
            "Item" => "Customer",
            "Action" => "Deleted",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Customers->vazquez_webhooks_setup]: Could not add the Deleted action webhook for the Customers class.");
        }
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_action('init', [self::class, 'register_posttype']);
        $loader->add_filter('manage_customer_posts_columns', [self::class, 'manage_customer_admin_columns'], 20);
        $loader->add_action('manage_customer_posts_custom_column', [self::class, 'manage_customer_admin_column'], 20, 2);
        $loader->add_action('wp_ajax_update_assumax_customer', [self::class, 'ajax_update_customer']);
        $loader->add_action('vazquez_webhooks_setup', [self::class, 'vazquez_webhooks_setup'], 10, 2);
    }
}
