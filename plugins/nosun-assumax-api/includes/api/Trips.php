<?php /** @noinspection SqlDialectInspection */

/** @noinspection SqlNoDataSourceInspection */

namespace Vazquez\NosunAssumaxConnector\Api;

use DateTime;
use DateTimeZone;
use Exception;
use WC_Product;
use WP_Post;

/**
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Trips implements ILoadable {
    /**
     * Obtains all the Trips that have the associated Template Id. The Trips can be filtered to only accept
     * those that depart sometime in the future and those that aren't cancelled.
     *
     * @param string $templateId The Assumax Id of the Template whose Trips needs to be obtained.
     * @param bool $onlyGetFutureTrips Whether to only obtain the trips that depart sometime in the future.
     * @param bool $filterCancelled
     * @return array List of posts with type 'product' or an empty list if something went wrong.
     */
    public static function get_by_template_id($templateId, $onlyGetFutureTrips = true, $filterCancelled = true) {
        if (empty($templateId)) return [];
        // Build the list of arguments.
        $metaQuery = [
            'relation' => 'AND',
            [
                'key' => '_assumax_template_id',
                'value' => $templateId,
                'compare' => '='
            ]
        ];
	    if ($onlyGetFutureTrips) {
		    try {
			    $today = new DateTime();
		    } catch (Exception $e) {
			    error_log("[Nosun_Assumax_Api_Trips->get_by_template_id]: Could not obtain the current date.\n{$e->getMessage()}");
			    return [];
		    }
		    $metaQuery[] = [
			    'key' => 'trip_start_date',
			    'value' => $today->format('Y-m-d'),
			    'compare' => '>=',
			    'DATE'
		    ];
	    }
        if ($filterCancelled) {
            $metaQuery[] = [
                'key' => 'trip_status',
                'value' => 'Cancelled',
                'compare' => '!='
            ];
        }
        $args = [
            'numberposts' => -1,
            'post_type' => 'product',
            'meta_query' => $metaQuery,
            'orderby' => 'meta_value',
            'meta_key' => 'trip_start_date',
            'order' => 'asc'
        ];
        // Return the resulting posts.
        return get_posts($args);
    }

    /**
     * Obtains the Trip (product) which AssumaxId is equal to the one provided.
     *
     * @param string $assumaxId The AssumaxId for the Trip (product).
     * @return WP_Post | null A post with type 'product' belonging to the provided Assumax Id, or null if something
     * went wrong.
     */
    public static function get_by_assumax_id($assumaxId) {
        if (empty($assumaxId)) return null;
        $args = [
            'numberposts' => 1,
            'post_type' => 'product',
            'meta_key' => '_assumax_id',
            'meta_value' => $assumaxId
        ];
        $posts = get_posts($args);
        if (empty($posts)) return null;
        return $posts[0];
    }

    /**
     * Pulls all the available Trips from the API and upserts them.
     */
    public static function upsert_all_from_api() {
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Api\Trips->upsert_all_from_api]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return false;
        }
        $successful = [];
        $unsuccessful = [];
        $clientIterator = $client->get_all("/trips", [], 0);
        foreach ($clientIterator as $tripData) {
            if (empty($tripData->Id)) continue;
            error_log("[Api\Trips->upsert_all_from_api]: Upserting trip with id: {$tripData->Id}.");
            if (self::upsert($tripData->Id, $tripData) === false) {
                $unsuccessful[] = $tripData->Id;
            } else {
                $successful[] = $tripData->Id;
            }
        }
        error_log("[Api\Trips->upsert_all_from_api]: Total amount: {$clientIterator->get_size()}.");
        error_log(sprintf("[Api\Trips->upsert_all_from_api]: List of successful updates: %s.", implode(', ', $successful)));
        error_log(sprintf("[Api\Trips->upsert_all_from_api]: List of unsuccessful updates: %s.", implode(', ', $unsuccessful)));
        return true;
    }

    /**
     * Tries to obtain the Trip (product) with the provided Assumax Id.
     * Should a product post not exist then a new is created, otherwise the existing one is updated.
     *
     * @param string $assumaxId The Assumax Id for the Trip.
     * @param object $tripData Optional API data to use instead of pulling it from the API.
     * @return bool true when the Trip has been upserted successfully, false if otherwise.
     */
    public static function upsert($assumaxId, $tripData = null) {
        ini_set('memory_limit', '256M');
        set_time_limit(600);
        if (empty($assumaxId)) return false;
        // Get the trip data from the API.
        if (!empty($tripData)) {
            $apiData = $tripData;
        } else {
            try {
                $client = AssumaxClient::getInstance();
            } catch (Exception $e) {
                error_log("[Nosun_Assumax_Api_Trips->upsert]: An exception occurred while trying to obtain the trip with Id: {$assumaxId} from the API.\n{$e->getMessage()}");
                return false;
            }
            $apiData = $client->get("/trips/{$assumaxId}");
        }
        if (empty($apiData)) {
            error_log("[Nosun_Assumax_Api_Trips->upsert]: Could not obtain the trip with Id: {$assumaxId} from the API.");
            return false;
        }
        if (empty($apiData->StartDate) || empty($apiData->EndDate) || empty($apiData->SalesPrice) || empty($apiData->InsuranceOptions) || empty($apiData->TemplateId)) {
            error_log("[Nosun_Assumax_Api_Trips->upsert]: One of the required fields is empty for the trip with id: {$assumaxId}.");
            return false;
        }
        // Obtain the Template post.
        $templatePost = Templates::get_by_assumax_id($apiData->TemplateId);
        if (empty($templatePost)) {
            error_log("[Nosun_Assumax_Api_Trips->upsert]: The template with Assumax id: {$apiData->TemplateId} does not exist on the website. Trip: {$assumaxId}.");
            return false;
        }
        // Convert the start and end dates to DateTime objects.
        $timeZoneString = get_option('timezone_string');
        if (empty($timeZoneString)) $timeZoneString = 'Europe/Amsterdam';
        try {
            $timeZone = new DateTimeZone($timeZoneString);
            $startDate = new DateTime($apiData->StartDate, $timeZone);
            $endDate = new DateTime($apiData->EndDate, $timeZone);
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Trips->upsert]: {$e->getMessage()}");
            return false;
        }
        $postTitle = sprintf("%s van %s tot %s",
            $templatePost->post_title,
            $startDate->format("d-m-Y"),
            $endDate->format("d-m-Y"));
        // Check if a trip (product) post already exists.
        $trip = self::get_by_assumax_id($assumaxId);
        if (empty($trip)) {
            // Create a new trip (product).
            $product = new WC_Product();
            $product->set_status('publish');
            $product->set_manage_stock(false);
            $product->set_backorders('no');
            $product->set_reviews_allowed(false);
            $product->set_sold_individually(false);
        } else {
            $product = new WC_Product($trip->ID);
        }
        $product->set_name($postTitle);
        $product->set_description($templatePost->post_content);
        $product->set_short_description($templatePost->post_excerpt);
        $product->set_price($apiData->SalesPrice);
        $product->set_regular_price($apiData->SalesPrice);
        $postId = $product->save();
        // Set the _assumax_id and the _assumax_template_id meta when it is a new trip (product).
        if (empty($trip)) {
            update_post_meta($postId, '_assumax_id', $assumaxId);
            update_post_meta($postId, '_assumax_template_id', $apiData->TemplateId);
        }
        update_field('trip_template', $templatePost->ID, $postId);
        self::upsert_pivot_data($assumaxId, $apiData->TemplateId, $startDate, $endDate,
            !empty($apiData->WebsiteAvailability) ? $apiData->WebsiteAvailability : 'Unavailable',
            isset($apiData->NumberOfDays) ? $apiData->NumberOfDays : 0, floatval($apiData->SalesPrice));
        return self::update_trip_meta($postId, $apiData, $startDate, $endDate, $postTitle);
    }

    /**
     * Updates all the meta data for the trip (product) with the provided post id and api data.
     *
     * @param int $postId The trip (product) post id.
     * @param mixed $apiData The data from the API in json decoded form.
     * @param DateTime $startDate The start date of the trip as a DateTime object.
     * @param DateTime $endDate The end date of the trip as a DateTime object.
     * @param string $postTitle The title of the trip post.
     * @return bool true when the update process completed successfully, false if otherwise.
     */
    private static function update_trip_meta($postId, $apiData, DateTime $startDate, DateTime $endDate, $postTitle) {
        if (empty($postId) || empty($apiData) || empty($startDate) || empty ($endDate)) return false;
	    // Guides
	    $guides = [];
	    if (!empty($apiData->Guides)) {
		    foreach ($apiData->Guides as $guide) {
			    if (empty($guide->Id) || empty($guide->Name) || empty($guide)) {
				    error_log("[Nosun_Assumax_Api_Trips->update_trip_meta]: There exists a guide without an id or name for trip with id: {$apiData->Id}. We will update the trip_guides with an empty array for post: {$postId}.");
				    // Makes sure the trip on the site also updates if the trip from the api has no guides
				    // Will return an empty array for the trip_guides, setting the trip_guides repeater to empty
				    $guides = [];
			    } else {
				    $guides[] = [
					    'assumax_id' => $guide->Id,
					    'name' => $guide->Name,
					    'index' => !isset($guide->SortIndex) ? 99999 : $guide->SortIndex
				    ];
			    }
		    }
	    }
	    update_field('trip_guides', $guides, $postId);
        // ExtraCharges
        if (!empty($apiData->ExtraCharges)) {
            $extraCharges = [];
            foreach ($apiData->ExtraCharges as $extraCharge) {
                if (empty($extraCharge->Title) || !isset($extraCharge->Price)) {
                    error_log("[Nosun_Assumax_Api_Trips->update_trip_meta]: There exists an extra charge without a title or price for trip with id: {$apiData->Id}.");
                    continue;
                }
                $extraCharges[] = [
                    'title' => $extraCharge->Title,
                    'price' => $extraCharge->Price
                ];
            }
            update_field('trip_extra_charges', $extraCharges, $postId);
        }
        // InsuranceOptions
        if (!empty($apiData->InsuranceOptions)) {
            $insuranceOptions = [];
            foreach ($apiData->InsuranceOptions as $insuranceOption) {
                if (empty($insuranceOption->Title) || !isset($insuranceOption->InsuranceType) || empty($insuranceOption->Description) || !isset($insuranceOption->Price)) {
                    error_log("[Nosun_Assumax_Api_Trips->update_trip_meta]: There exists an insurance option without a title, type, description or price for trip with id: {$apiData->Id}.");
                    continue;
                }
                $insuranceOptions[] = [
                    'title' => $insuranceOption->Title,
                    'type' => $insuranceOption->InsuranceType,
                    'description' => $insuranceOption->Description,
                    'price' => $insuranceOption->Price
                ];
            }
            update_field('trip_insurance_options', $insuranceOptions, $postId);
        }
        // CommercialEntries
        if (!empty($apiData->CommercialEntries)) {
            $commercialEntries = [];
            foreach ($apiData->CommercialEntries as $commercialEntry) {
                if (!isset($commercialEntry->Sex) || !isset($commercialEntry->DateOfBirth)) {
                    error_log("[Nosun_Assumax_Api_Trips->update_trip_meta]: There exists a commercial entry without a sex or date of birth for trip with id: {$apiData->Id}.");
	                // Makes sure the trip on the site also updates if the trip from the api has no commercialentries
	                // Will return an empty array for the commercial_entries, setting the trip_commercial_entries repeater to empty
	                $commercialEntries = [];
                } else {
	                $commercialEntries[] = [
	                    'sex' => $commercialEntry->Sex,
	                    'date_of_birth' => $commercialEntry->DateOfBirth
	                ];
                }
            }
            update_field('trip_commercial_entries', $commercialEntries, $postId);
        }
        // Customers
        $customers = [];
        if (!empty($apiData->Customers)) {
            foreach ($apiData->Customers as $customer) {
            	// quick fix for when the data doesn't return a firstname
            	if(empty($customer->FirstName) && !empty($customer->NickName)) $customer->FirstName = $customer->NickName;

                if (!isset($customer->Id) || empty($customer->FirstName) || empty($customer->LastName)) {
                    error_log("[Nosun_Assumax_Api_Trips->update_trip_meta]: There exists a customer without an id, first name or last name for trip with id: {$apiData->Id}.");
                    continue;
                }
                $customers[] = [
                    'assumax_id' => $customer->Id,
                    'prefix' => isset($customer->Prefix) ? $customer->Prefix : '',
                    'first_name' => $customer->FirstName,
                    'last_name' => $customer->LastName,
                    'nick_name' => isset($customer->NickName) ? $customer->NickName : '',
                    'sex' => isset($customer->Sex) ? $customer->Sex : 0,
                    'date_of_birth' => isset($customer->DateOfBirth) ? $customer->DateOfBirth : ''
                ];
            }
            update_field('trip_customers', $customers, $postId);
        }
        update_field('trip_start_date', $startDate->format("Y-m-d"), $postId);
        update_field('trip_end_date', $endDate->format("Y-m-d"), $postId);
        update_field('trip_num_days', isset($apiData->NumberOfDays) ? $apiData->NumberOfDays : 0, $postId);
        update_field('trip_min_num_customers', isset($apiData->MinNumberOfCustomers) ? $apiData->MinNumberOfCustomers : 0, $postId);
        update_field('trip_max_num_customers', isset($apiData->NumberOfCustomers) ? $apiData->NumberOfCustomers : 0, $postId);
        update_field('trip_num_entries', isset($apiData->NumberOfEntries) ? $apiData->NumberOfEntries : 0, $postId);
        update_field('trip_confirmed', isset($apiData->ShowConfirmedOnWebsite) ? $apiData->ShowConfirmedOnWebsite : 0, $postId);
        update_field('trip_num_commercial_entries', isset($apiData->NumberOfCommercialEntries) ? $apiData->NumberOfCommercialEntries : 0, $postId);
        update_field('trip_availability', !empty($apiData->WebsiteAvailability) ? $apiData->WebsiteAvailability : 'Unavailable', $postId);
        update_field('trip_status', !empty($apiData->Status) ? $apiData->Status : 'Created', $postId);
        // Upsert the TravelGroup.
        if (!TravelGroups::upsert_from_trip($postId, $postTitle, $customers, $guides, $startDate)) {
            error_log("[Api\Trips->update_trip_meta]: Could not upsert the TravelGroup for the trip with id: {$apiData->Id}.");
        }
        // Update the trip report.
        do_action('vazquez_upsert_trip_report_data', $postId, $apiData);
        return true;
    }

    /**
     * Upserts the pivot data for the provided Trip/Template relation.
     *
     * @param int $tripAssumaxId The Assumax id of the Trip.
     * @param int $templateAssumaxId The Assumax id of the Template.
     * @param DateTime $tripStartDate The start date of the Trip.
     * @param DateTime $tripEndDate The end date of the Trip.
     * @param string $tripAvailability The availability of the Trip.
     * @param int $tripNumDays The number of days the Trip will last.
     * @param int $tripPrice The price of the Trip.
     */
    public static function upsert_pivot_data($tripAssumaxId, $templateAssumaxId, DateTime $tripStartDate, DateTime $tripEndDate, $tripAvailability, $tripNumDays, $tripPrice) {
        global $wpdb;
        // Multiply the price by 100 to prevent floating point problems.
        $integerPrice = (int)($tripPrice * 100);
        $query = sprintf('INSERT INTO %s (trip_assumax_id, template_assumax_id, trip_start_date, trip_end_date, trip_availability, trip_numdays, trip_price) 
                            VALUES(%d,%d,\'%4$s\',\'%5$s\',\'%6$s\',%7$d,%8$d)
                            ON DUPLICATE KEY UPDATE 
                                trip_start_date=\'%4$s\',
                                trip_end_date=\'%5$s\',
                                trip_availability=\'%6$s\',
                                trip_numdays=%7$d,
                                trip_price=%8$d;',
            "api_trip_template_pivot", $tripAssumaxId, $templateAssumaxId,
            $tripStartDate->format('Y-m-d'), $tripEndDate->format('Y-m-d'), $tripAvailability,
            $tripNumDays, $integerPrice);
        if ($wpdb->query($query) === false) {
            error_log("[Api\Trips->upsert_pivot_data]: Could not upsert the pivot data for Trip with Assumax Id: {$tripAssumaxId}.");
        }
    }


    /**
     * Sets the Trip (product) with the provided Assumax Id to deleted.
     * Note: This will not actually hard delete the Trip (product), this can be done manually in the admin interface.
     *
     * @param string $assumaxId The Assumax Id of the Trip (product).
     * @return bool true when the Trip (product) has successfully been set to deleted, false if otherwise.
     */
    public static function delete($assumaxId) {
        $product = self::get_by_assumax_id($assumaxId);
        if (!isset($product)) {
            error_log("[Nosun_Assumax_Api_Trips->delete]: There exists no trip with Assumax Id: {$assumaxId}.]");
            return false;
        }
        if (empty(wp_delete_post($product->ID))) return false;
        return true;
    }

    /**
     * Callback function for the Trip webhook that should be called via a Post request by the Assumax ERP.
     *
     * @param string $assumaxId The Assumax id of the Trip (product).
     * @param string $action The action to perform as supplied by Assumax.
     */
    public static function webhook_callback($assumaxId, $action) {
        do_action('vazquez_add_update_log_entry', 'Trip', $action, $assumaxId);
        if ($action === 'Created' || $action === 'Modified') {
            self::upsert($assumaxId);
        }
        elseif ($action === 'Deleted') {
            self::delete($assumaxId);
        } else {
            error_log("[Nosun_Assumax_Api_Trips->webhook_callback]: Action: {$action} for id: {$assumaxId} is not supported.]");
        }
    }

    /**
     * Adds and removes several columns to the trip (product) post list on the admin screen.
     *
     * @param mixed $columns The current set of columns.
     * @return mixed The modified set of columns.
     */
    public static function manage_trip_admin_columns($columns) {
        // Unset unneeded columns.
        unset($columns['is_in_stock']);
        unset($columns['thumb']);
        unset($columns['product_tag']);
        unset($columns['sku']);
        unset($columns['featured']);
        unset($columns['product_type']);
        unset($columns['product_cat']);
        // Add new columns.
        $columns['availability'] = __('Beschikbaarheid', 'nosun_assumax');
        $columns['guaranteed_departure'] = __('Gegarandeerd vertrek', 'nosun_assumax');
        $columns['assumax_id'] = __('Assumax Id', 'nosun_assumax');
        $columns['update_assumax'] = __('Update uit Assumax', 'nosun_assumax');
        return $columns;
    }

    /**
     * Fills in the extra trip (product) columns depending on the column name provided.
     *
     * @param string $column The column name.
     * @param int $postId The post id of the trip (product).
     */
    public static function manage_trip_admin_column($column, $postId) {
        switch ($column) {
            case 'assumax_id': {
                echo get_post_meta($postId, '_assumax_id', true);
                break;
            }
            case 'availability': {
                $availability = get_field('trip_availability', $postId);
                echo $availability;
                break;
            }
            case 'guaranteed_departure': {
                $confirmed = get_field('trip_confirmed', $postId);
                echo $confirmed ? 'Ja' : 'Nee';
                break;
            }
            case 'update_assumax': {
                $assumaxId = get_post_meta($postId, '_assumax_id', true);
                echo "<button class='update-assumax' data-assumax_id='{$assumaxId}' data-action='update_assumax_trip'>Update</button>";
                break;
            }
        }
    }

    /**
     * Event function that should be called by the wordpress cron event scheduler.
     * Upserts the Trip with the provided Assumax Id.
     *
     * @param int $assumaxId The Assumax Id of the Trip
     */
    public static function event_upsert_trip(int $assumaxId) : void
    {
        if (empty($assumaxId)) {
            return;
        }
        $lock = Locks::acquire_lock($assumaxId, 'Trip', 'Modified', true);
        if ($lock > 0) {
            static::upsert($assumaxId);
        }
        Locks::release_lock($lock);
    }

    /**
     * AJAX function that upserts the Trip with the Assumax Id provided in the POST global.
     */
    public static function ajax_update_trip() {
        if (!is_admin()) return;
        $assumaxId = $_POST['assumax_id'];
        if (empty($assumaxId)) {
            return;
        }
        $lock = Locks::acquire_lock($assumaxId, 'Trip', 'Modified', true);
        if ($lock > 0) {
            static::upsert($assumaxId);
        }
        Locks::release_lock($lock);
    }

    /**
     * Changes the labels of the product post type to that of a trip.
     *
     * @param mixed $args The current product post arguments.
     * @return mixed The product post arguments with trip labels.
     */
    public static function custom_product_labels($args) {
        $labels = array(
            'name' => __('Reizen', 'nosun_custom_label'),
            'singular_name' => __('Reis', 'nosun_custom_label'),
            'menu_name' => _x('Reizen', 'Admin menu name', 'nosun_custom_label'),
            'add_new' => __('Nieuwe reis toevoegen', 'nosun_custom_label'),
            'add_new_item' => __('Reis toevoegen', 'nosun_custom_label'),
            'edit' => __('Reis wijzigen', 'nosun_custom_label'),
            'edit_item' => __('Wijzigen', 'nosun_custom_label'),
            'new_item' => __('Toevoegen', 'nosun_custom_label'),
            'view' => __('Bekijk reis', 'nosun_custom_label'),
            'view_item' => __('Bekijk', 'nosun_custom_label'),
            'search_items' => __('Zoeken', 'nosun_custom_label'),
            'not_found' => __('Geen reis gevonden', 'nosun_custom_label'),
            'not_found_in_trash' => __('Geen reizen gevonden', 'nosun_custom_label'),
            'parent' => __('Hoofditem', 'nosun_custom_label')
        );
        $args['labels'] = $labels;
        $args['description'] = __('Hier kan je reizen beheren.', 'nosun_custom_label');
        return $args;
    }

    /**
     * Adds a pivot table that holds extra relation information between a Trip and a Template.
     */
    public static function create_pivot_table() {
        global $wpdb;
        $query = 'CREATE TABLE IF NOT EXISTS `api_trip_template_pivot` (
                  `trip_assumax_id` int(11) unsigned NOT NULL,
                  `template_assumax_id` int(11) unsigned NOT NULL,
                  `trip_start_date` date DEFAULT NULL,
                  `trip_end_date` date DEFAULT NULL,
                  `trip_availability` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
                  `trip_numdays` int(11) DEFAULT NULL,
                  `trip_price` int(11) DEFAULT NULL,
                  UNIQUE KEY `trip_assumax_id` (`trip_assumax_id`,`template_assumax_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;';
        if ($wpdb->query($query) === false) {
            error_log("[Api\Trips->create_pivot_table]: Could not create the Trip/Template pivot table");
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
            "Item" => "Trip",
            "Action" => "Created",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Trips->vazquez_webhooks_setup]: Could not add the Created action webhook for the Trips class.");
        }
        $result = $client->put("/webhooks", [
            "Item" => "Trip",
            "Action" => "Modified",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Trips->vazquez_webhooks_setup]: Could not add the Modified action webhook for the Trips class.");
        }
        $result = $client->put("/webhooks", [
            "Item" => "Trip",
            "Action" => "Deleted",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Trips->vazquez_webhooks_setup]: Could not add the Deleted action webhook for the Trips class.");
        }
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_filter('manage_product_posts_columns', [self::class, 'manage_trip_admin_columns'], 20);
        $loader->add_action('manage_product_posts_custom_column', [self::class, 'manage_trip_admin_column'], 20, 2);
        $loader->add_filter('woocommerce_register_post_type_product', [self::class, 'custom_product_labels']);
        $loader->add_action('wp_ajax_update_assumax_trip', [self::class, 'ajax_update_trip']);
        $loader->add_action('vazquez_webhooks_setup', [self::class, 'vazquez_webhooks_setup'], 10, 2);
        $loader->add_action('event_upsert_trip', [self::class, 'event_upsert_trip'], 10, 1);
    }
}
