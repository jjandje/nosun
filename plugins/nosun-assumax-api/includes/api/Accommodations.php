<?php /** @noinspection SqlNoDataSourceInspection */

namespace Vazquez\NosunAssumaxConnector\Api;

use Exception;
use WP_Post;

/**
 * Accommodations / https://nosun-api.assumax.nl/Help
 *
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Accommodations implements ILoadable {
    /**
     * Obtains the Accommodation for which the AssumaxId equals the provided one.
     *
     * @param int $assumaxId The Assumax Id of the Accommodation.
     * @return WP_Post|null The Accommodation or null should it not exist or when something goes wrong.
     */
    public static function get_by_assumax_id($assumaxId) {
        if (empty($assumaxId)) return null;
        $accommodationPosts = self::get_by_assumax_ids([$assumaxId], 1);
        return empty($accommodationPosts) ? null : $accommodationPosts[0];
    }

    /**
     * Obtains all the Accommodations for which there exist Assumax Ids in the provided array of Assumax Ids.
     *
     * @param array $assumaxIds An array of Assumax Ids for which to obtain Accommodations.
     * @param int $limit The maximum amount of Accommodations to obtain where -1 means no limit.
     * @return WP_Post[] An array of Accommodations which have the specified AssumaxIds or an empty array should something
     * go wrong.
     */
    public static function get_by_assumax_ids($assumaxIds, $limit = -1) {
        if (empty($assumaxIds)) return [];
        $args = [
            'post_type' => 'accommodation',
            'post_status' => 'publish',
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
     * Pulls all the available Accommodations from the API and upserts them.
     */
    public static function upsert_all_from_api() {
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Api\Accommodations->upsert_all_from_api]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return false;
        }
        $successful = [];
        $unsuccessful = [];
        $clientIterator = $client->get_all("/accommodations", []);
        foreach ($clientIterator as $accommodationData) {
            if (empty($accommodationData->Id)) continue;
            error_log("[Api\Accommodations->upsert_all_from_api]: Upserting accommodation with id: {$accommodationData->Id}.");
            if (self::upsert($accommodationData->Id, $accommodationData) === false) {
                $unsuccessful[] = $accommodationData->Id;
            } else {
                $successful[] = $accommodationData->Id;
            }
        }
        error_log("[Api\Accommodations->upsert_all_from_api]: Total amount: {$clientIterator->get_size()}.");
        error_log(sprintf("[Api\Accommodations->upsert_all_from_api]: List of successful updates: %s.", implode(', ', $successful)));
        error_log(sprintf("[Api\Accommodations->upsert_all_from_api]: List of unsuccessful updates: %s.", implode(', ', $unsuccessful)));
        return true;
    }

    /**
     * Tries to either update or create a new Accommodation for the provided Assumax Id.
     *
     * @param int $assumaxId The Assumax Id of the Accommodation.
     * @param object $accommodationData Optional API data to use instead of pulling it from the API.
     * @return bool True when the Accommodation has been upserted successfully or false if otherwise.
     */
    public static function upsert($assumaxId, $accommodationData = null) {
        ini_set('memory_limit', '256M');
        set_time_limit(600);
        if (empty($assumaxId)) return false;
        if (!empty($accommodationData)) {
            $apiData = $accommodationData;
        } else {
            try {
                $client = AssumaxClient::getInstance();
            } catch (Exception $e) {
                error_log("[Accommodations->upsert]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
                return false;
            }
            $apiData = $client->get("/accommodations/{$assumaxId}");
        }
        if (empty($apiData)) {
            error_log("[Accommodations->upsert]: Could not obtain the accommodation with id: {$assumaxId} from the API.");
            return false;
        }
        if (empty($apiData->Title)) {
            error_log("[Accommodations->upsert]: The title is missing for accommodation with id: {$assumaxId}.");
            return false;
        }
        $accommodation = self::get_by_assumax_id($assumaxId);
        if (empty($accommodation)) {
            $args = [
                'post_type' => 'accommodation',
                'post_title' => $apiData->Title,
                'post_status' => 'publish',
                'post_content' => isset($apiData->Description) ? $apiData->Description : ""
            ];
            $postId = wp_insert_post($args);
            if (is_wp_error($postId)) {
                error_log("[Accommodations->upsert]: Could not create a new accommodation for id: {$assumaxId}.\n{$postId->get_error_message()}");
                return false;
            }
            update_post_meta($postId, '_assumax_id', $assumaxId);
        } else {
            $args = [
                'ID' => $accommodation->ID,
                'post_title' => $apiData->Title,
                'post_content' => isset($apiData->Description) ? $apiData->Description : ""
            ];
            $postId = wp_update_post($args);
            if (is_wp_error($postId)) {
                error_log("[Accommodations->upsert]: Could not update the title for the accommodation with id: {$assumaxId}.\n{$postId->get_error_message()}");
                return false;
            }
        }
        return self::update_accommodation_meta($postId, $apiData);
    }

    /**
     * Updates all the meta data for the Accommodation with the provided post id and api data.
     * Will also download any images that are missing.
     *
     * @param int $postId The Accommodation post id.
     * @param mixed $apiData The data from the API in json decoded form.
     * @return bool True when the meta has been updates successfully, false if otherwise.
     */
    public static function update_accommodation_meta($postId, $apiData) {
        if (empty($postId) || empty($apiData)) return false;
        $attachmentIds = [];
        if (!empty($apiData->AccommodationImages)) {
            $images = [];
            foreach ($apiData->AccommodationImages as $accommodationImage) {
                $attachmentId = Attachments::handle_accommodation_image($accommodationImage, $postId);
                if ($attachmentId !== false) {
                    $attachmentIds[] = $attachmentId;
                    $images[] = [
                        'title' => $accommodationImage->Title,
                        'image' => $attachmentId
                    ];
                }
            }
            update_field('accommodation_images', $images, $postId);
        }
        update_post_meta($postId, '_attachments', $attachmentIds);
        // Create the rml shortcuts for the attachments.
        if (!empty($attachmentIds)) {
            $folderName = "{$apiData->Title}_{$postId}";
            Attachments::create_rml_shortcuts($folderName, $attachmentIds, 'accommodation');
        }
        // Update the remaining fields.
        update_field('accommodation_title', $apiData->Title, $postId);
        if (isset($apiData->Address)) update_field('accommodation_address', $apiData->Address, $postId);
        if (isset($apiData->Phone)) update_field('accommodation_phone', $apiData->Phone, $postId);
        if (isset($apiData->EmailAddress)) update_field('accommodation_email', $apiData->EmailAddress, $postId);
        if (isset($apiData->Website)) update_field('accommodation_website', $apiData->Website, $postId);
        if (isset($apiData->Rooms)) update_field('accommodation_rooms', $apiData->Rooms, $postId);
        return true;
    }

    /**
     * Sets the Accommodation with the provided Assumax Id to deleted.
     *
     * @param string $assumaxId The Assumax Id of the Accommodation.
     * @return bool true when the Accommodation has successfully been set to deleted, false if otherwise.
     */
    public static function delete($assumaxId) {
        $product = self::get_by_assumax_id($assumaxId);
        if (!isset($product)) {
            error_log("[Nosun_Assumax_Api_Accommodations->delete]: There exists no accommodation with Assumax Id: {$assumaxId}.]");
            return false;
        }
        if (empty(wp_delete_post($product->ID))) return false;
        return true;
    }

    /**
     * Callback function for the Accommodation webhook that should be called via a Post request by the Assumax ERP.
     *
     * @param string $assumaxId The Assumax id of the Accommodation.
     * @param string $action The action to perform as supplied by Assumax.
     */
    public static function webhook_callback($assumaxId, $action) {
        do_action('vazquez_add_update_log_entry', 'Accommodation', $action, $assumaxId);
        if ($action === 'Created' || $action === 'Modified') {
            self::upsert($assumaxId);
        }
        elseif ($action === 'Deleted') {
            self::delete($assumaxId);
        } else {
            error_log("[Nosun_Assumax_Api_Accommodations->webhook_callback]: Action: {$action} for id: {$assumaxId} is not supported.]");
        }
    }

    /**
     * Adds and removes several columns to the Accommodation post list on the admin screen.
     *
     * @param mixed $columns The current set of columns.
     * @return mixed The modified set of columns.
     */
    public static function manage_accommodation_admin_columns($columns) {
        // Add new columns.
        $columns['assumax_id'] = __('Assumax Id', 'nosun_assumax');
        $columns['update_assumax'] = __('Update uit Assumax', 'nosun_assumax');
        return $columns;
    }

    /**
     * Fills in the extra Accommodation columns depending on the column name provided.
     *
     * @param string $column The column name.
     * @param int $postId The post id of the Accommodation.
     */
    public static function manage_accommodation_admin_column($column, $postId) {
        switch ($column) {
            case 'assumax_id': {
                echo get_post_meta($postId, '_assumax_id', true);
                break;
            }
            case 'update_assumax': {
                $assumaxId = get_post_meta($postId, '_assumax_id', true);
                echo "<button class='update-assumax' data-assumax_id='{$assumaxId}' data-action='update_assumax_accommodation'>Update</button>";
                break;
            }
        }
    }

    /**
     * AJAX function that upserts the Accommodation with the Assumax Id provided in the POST global.
     */
    public static function ajax_update_accommodation() {
        if (!is_admin()) return;
        $assumaxId = $_POST['assumax_id'];
        if (empty($assumaxId)) return;
        $lock = Locks::acquire_lock($assumaxId, 'Accommodation', 'Modified', 1);
        if ($lock > 0) {
            static::upsert($assumaxId);
        }
        Locks::release_lock($lock);
    }

    /**
     * Registers the accommodation custom post type.
     */
    public static function register_posttype() {
        $labels = array (
            'name'               => 'Accommodaties',
            'singular_name'      => 'Accommodatie',
            'add_new'            => 'Toevoegen',
            'add_new_item'       => 'Accommodatie toevoegen',
            'edit_item'          => 'Bewerk Accommodatie',
            'new_item'           => 'Nieuw',
            'view_item'          => 'Bekijk Accommodatie',
            'search_items'       => 'Zoek Accommodatie',
            'not_found'          => 'Geen Accommodaties gevonden',
            'not_found_in_trash' => 'Geen Accommodaties gevonden in prullenbak'
        );
        $args = array (
            'label'               => 'Accommodaties',
            'description'         => 'Accommodaties',
            'labels'              => $labels,
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-groups',
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'rewrite'             => false
        );
        register_post_type( 'accommodation', $args );
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
            "Item" => "Accommodation",
            "Action" => "Created",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Accommodations->vazquez_webhooks_setup]: Could not add the Created action webhook for the Accommodations class.");
        }
        $result = $client->put("/webhooks", [
            "Item" => "Accommodation",
            "Action" => "Modified",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Accommodations->vazquez_webhooks_setup]: Could not add the Modified action webhook for the Accommodations class.");
        }
        $result = $client->put("/webhooks", [
            "Item" => "Accommodation",
            "Action" => "Deleted",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Accommodations->vazquez_webhooks_setup]: Could not add the Deleted action webhook for the Accommodations class.");
        }
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_filter('manage_accommodation_posts_columns', [self::class, 'manage_accommodation_admin_columns'], 20);
        $loader->add_action('manage_accommodation_posts_custom_column', [self::class, 'manage_accommodation_admin_column'], 20, 2);
        $loader->add_action('wp_ajax_update_assumax_accommodation', [self::class, 'ajax_update_accommodation']);
        $loader->add_action('init', [self::class, 'register_posttype']);
        $loader->add_action('vazquez_webhooks_setup', [self::class, 'vazquez_webhooks_setup'], 10, 2);
    }
}
