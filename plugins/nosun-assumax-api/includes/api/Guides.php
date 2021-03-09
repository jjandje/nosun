<?php /** @noinspection SqlNoDataSourceInspection */

namespace Vazquez\NosunAssumaxConnector\Api;

use Exception;
use lib\controllers\Email;
use lib\controllers\User;
use lib\woocommerce_reports\models\UpdateLog;
use WP_Post;

/**
 * Guides / https://nosun-api.assumax.nl/Help
 *
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Guides implements ILoadable {
    /**
     * Obtains the Gudie for which the AssumaxId equals the provided one.
     *
     * @param int $assumaxId The Assumax Id of the Guide.
     * @return WP_Post|null The Guide or null should it not exist or when something goes wrong.
     */
    public static function get_by_assumax_id($assumaxId) {
        if (empty($assumaxId)) return null;
        $guidePosts = self::get_by_assumax_ids([$assumaxId], 1);
        return empty($guidePosts) ? null : $guidePosts[0];
    }

    /**
     * Obtains all the Guides for which there exist Assumax Ids in the provided array of Assumax Ids.
     *
     * @param array $assumaxIds An array of Assumax Ids for which to obtain Guides.
     * @param int $limit The maximum amount of Guides to obtain where -1 means no limit.
     * @return WP_Post[] An array of Guides which have the specified AssumaxIds or an empty array should something
     * go wrong.
     */
    public static function get_by_assumax_ids($assumaxIds, $limit = -1) {
        if (empty($assumaxIds)) return [];
        $args = [
            'post_type' => 'tourguide',
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
     * Obtains the user id of the user that is claiming this Guide.
     *
     * @param int $guidePostId The post id of the Guide.
     * @return string|null The user id or null when the Guide isn't claimed.
     */
    public static function get_user_id($guidePostId) {
        global $wpdb;
        $query = sprintf("SELECT user_id FROM %s WHERE meta_key='user_tourguide' AND meta_value='%s' LIMIT 1;",
            $wpdb->usermeta, $guidePostId);
        return $wpdb->get_var($query);
    }

    /**
     * Pulls all the available Guides from the API and upserts them.
     */
    public static function upsert_all_from_api() {
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Api\Guides->upsert_all_from_api]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return false;
        }
        $successful = [];
        $unsuccessful = [];
        $clientIterator = $client->get_all("/guides", []);
        foreach ($clientIterator as $guideData) {
            if (empty($guideData->Id)) continue;
            error_log("[Api\Guides->upsert_all_from_api]: Upserting guide with id: {$guideData->Id}.");
            if (self::upsert($guideData->Id, $guideData) === false) {
                $unsuccessful[] = $guideData->Id;
            } else {
                $successful[] = $guideData->Id;
            }
        }
        error_log("[Api\Guides->upsert_all_from_api]: Total amount: {$clientIterator->get_size()}.");
        error_log(sprintf("[Api\Guides->upsert_all_from_api]: List of successful updates: %s.", implode(', ', $successful)));
        error_log(sprintf("[Api\Guides->upsert_all_from_api]: List of unsuccessful updates: %s.", implode(', ', $unsuccessful)));
        return true;
    }

    /**
     * Tries to either update or create a new Guide for the provided Assumax Id.
     *
     * @param int $assumaxId The Assumax Id of the Guide.
     * @param object $guideData Optional API data to use instead of pulling it from the API.
     * @return bool True when the Guide has been upserted successfully or false if otherwise.
     */
    public static function upsert($assumaxId, $guideData = null) {
        ini_set('memory_limit', '256M');
        set_time_limit(600);
        if (empty($assumaxId)) return false;
        if (!empty($guideData)) {
            $apiData = $guideData;
        } else {
            try {
                $client = AssumaxClient::getInstance();
            } catch (Exception $e) {
                error_log("[Guides->upsert]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
                return false;
            }
            $apiData = $client->get("/guides/{$assumaxId}");
        }
        if (empty($apiData)) {
            error_log("[Guides->upsert]: Could not obtain the guide with id: {$assumaxId} from the API.");
            return false;
        }
        if (empty($apiData->NickName)) {
            error_log("[Guides->upsert]: The nickname is missing for guide with id: {$assumaxId}.");
            return false;
        }
        $guide = self::get_by_assumax_id($assumaxId);
        if (empty($guide)) {
            $args = [
                'post_type' => 'tourguide',
                'post_title' => $apiData->NickName,
                'post_status' => 'publish',
                'post_content' => ''
            ];
            $postId = wp_insert_post($args);
            if (is_wp_error($postId)) {
                error_log("[Guides->upsert]: Could not create a new guide for id: {$assumaxId}.\n{$postId->get_error_message()}");
                return false;
            }
            update_post_meta($postId, '_assumax_id', $assumaxId);
        } else {
            $args = [
                'ID' => $guide->ID,
                'post_title' => $apiData->NickName,
                'post_content' => ''
            ];
            $postId = wp_update_post($args);
            if (is_wp_error($postId)) {
                error_log("[Guides->upsert]: Could not update the title for the guide with id: {$assumaxId}.\n{$postId->get_error_message()}");
                return false;
            }
        }
        return self::update_guide_meta($postId, $apiData);
    }

    /**
     * Updates all the meta data for the Guide with the provided post id and api data.
     * Will also download any images that are missing.
     *
     * @param int $postId The Guide post id.
     * @param mixed $apiData The data from the API in json decoded form.
     * @return bool True when the meta has been updates successfully, false if otherwise.
     */
    public static function update_guide_meta($postId, $apiData) {
        if (empty($postId) || empty($apiData)) return false;
        $attachmentIds = [];
        if (!empty($apiData->Images)) {
            $images = [];
            foreach ($apiData->Images as $image) {
                $attachmentId = Attachments::handle_guide_image($image, $postId);
                if ($attachmentId !== false) {
	                $attachmentIds[] = $attachmentId;
                    $images[] = [
                        'title' => $image->Title,
                        'image' => $attachmentId
                    ];
                }
            }
            update_field('tourguide_images', $images, $postId);
        }
        update_post_meta($postId, '_attachments', $attachmentIds);
        $guide = User::upsert_guide_account($postId, $apiData);

        // Update the remaining fields.
        if (isset($apiData->EmailAddress)) update_field('tourguide_email', $apiData->EmailAddress, $postId);
        if (isset($apiData->Prefix)) update_field('tourguide_prefix', $apiData->Prefix, $postId);
        if (isset($apiData->FirstName)) update_field('tourguide_first_name', $apiData->FirstName, $postId);
        if (isset($apiData->LastName)) update_field('tourguide_last_name', $apiData->LastName, $postId);
        if (isset($apiData->NickName)) update_field('tourguide_nickname', $apiData->NickName, $postId);
        if (isset($apiData->DateOfBirth)) update_field('tourguide_birth_date', $apiData->DateOfBirth, $postId);
        if (isset($apiData->PhoneNumber)) update_field('tourguide_phone_number', $apiData->PhoneNumber, $postId);
        if (isset($apiData->Sex)) update_field('tourguide_sex', $apiData->Sex, $postId);
        if (isset($apiData->Nationality)) update_field('tourguide_nationality', $apiData->Nationality, $postId);
        if (isset($apiData->Street)) update_field('tourguide_street', $apiData->Street, $postId);
        if (isset($apiData->StreetNumber)) update_field('tourguide_street_number', $apiData->StreetNumber, $postId);
        if (isset($apiData->PostalCode)) update_field('tourguide_postal_code', $apiData->PostalCode, $postId);
        if (isset($apiData->City)) update_field('tourguide_city', $apiData->City, $postId);
        if (isset($apiData->BirthPlace)) update_field('tourguide_birth_city', $apiData->BirthPlace, $postId);
        if (isset($apiData->StartYear)) update_field('tourguide_since', $apiData->StartYear, $postId);
        if (isset($apiData->Hobbies)) update_field('tourguide_hobbies', $apiData->Hobbies, $postId);
        if (isset($apiData->Countries)) update_field('tourguide_guideplace', $apiData->Countries, $postId);
        if (isset($apiData->FavoriteDestination)) update_field('tourguide_favorite', $apiData->FavoriteDestination, $postId);
        if (isset($apiData->Motivation)) update_field('tourguide_nosun', $apiData->Motivation, $postId);
        if (isset($apiData->Slogan)) update_field('tourguide_slogan', $apiData->Slogan, $postId);
        if (isset($apiData->PersonalDescription)) update_field('tourguide_text', $apiData->PersonalDescription, $postId);
        // Create the rml shortcuts for the attachments.
        if (!empty($attachmentIds)) {
            $folderName = sprintf("%s-%s-%s",
                !empty($apiData->FirstName) ? $apiData->FirstName : '',
                !empty($apiData->NickName) ? $apiData->NickName : '',
                !empty($apiData->LastName) ? $apiData->LastName : ''
            );
            Attachments::create_rml_shortcuts($folderName, $attachmentIds, 'guide');
        }
        return true;
    }

    /**
     * Sets the Guide with the provided Assumax Id to deleted.
     *
     * @param string $assumaxId The Assumax Id of the Guide.
     * @return bool true when the Guide has successfully been set to deleted, false if otherwise.
     */
    public static function delete($assumaxId) {
        $product = self::get_by_assumax_id($assumaxId);
        if (!isset($product)) {
            error_log("[Guides->delete]: There exists no Guide with Assumax Id: {$assumaxId}.]");
            return false;
        }
        if (empty(wp_delete_post($product->ID))) return false;
        return true;
    }

    /**
     * Callback function for the Guide webhook that should be called via a Post request by the Assumax ERP.
     *
     * @param string $assumaxId The Assumax id of the Guide.
     * @param string $action The action to perform as supplied by Assumax.
     */
    public static function webhook_callback($assumaxId, $action) {
        do_action('vazquez_add_update_log_entry', 'Guide', $action, $assumaxId);
        if ($action === 'Created' || $action === 'Modified') {
            self::upsert($assumaxId);
        }
        elseif ($action === 'Deleted') {
            self::delete($assumaxId);
        } else {
            error_log("[Guides->webhook_callback]: Action: {$action} for id: {$assumaxId} is not supported.]");
        }
    }

    /**
     * Adds and removes several columns to the Guide post list on the admin screen.
     *
     * @param mixed $columns The current set of columns.
     * @return mixed The modified set of columns.
     */
    public static function manage_tourguide_admin_columns($columns) {
        // Add new columns.
        $columns['assumax_id'] = __('Assumax Id', 'nosun_assumax');
        $columns['update_assumax'] = __('Update uit Assumax', 'nosun_assumax');
        return $columns;
    }

    /**
     * Fills in the extra Guide columns depending on the column name provided.
     *
     * @param string $column The column name.
     * @param int $postId The post id of the Guide.
     */
    public static function manage_tourguide_admin_column($column, $postId) {
        switch ($column) {
            case 'assumax_id': {
                echo get_post_meta($postId, '_assumax_id', true);
                break;
            }
            case 'update_assumax': {
                $assumaxId = get_post_meta($postId, '_assumax_id', true);
                echo "<button class='update-assumax' data-assumax_id='{$assumaxId}' data-action='update_assumax_tourguide'>Update</button>";
                break;
            }
        }
    }

    /**
     * AJAX function that upserts the Guide with the Assumax Id provided in the POST global.
     */
    public static function ajax_update_tourguide() {
        if (!is_admin()) return;
        $assumaxId = $_POST['assumax_id'];
        if (empty($assumaxId)) return;
        $lock = Locks::acquire_lock($assumaxId, 'Guide', 'Modified', 1);
        if ($lock > 0) {
            static::upsert($assumaxId);
        }
        Locks::release_lock($lock);
    }

    /**
     * Registers the 'tourguide' post type.
     */
    public static function register_post_type() {
        $labels = array(
            'name' => 'Reisbegeleiders',
            'singular_name' => 'Reisbegeleider',
            'add_new' => 'Toevoegen',
            'add_new_item' => 'Reisbegeleider toevoegen',
            'edit_item' => 'Bewerk reisbegeleider',
            'new_item' => 'Nieuw',
            'view_item' => 'Bekijk reisbegeleider',
            'search_items' => 'Zoek reisbegeleider',
            'not_found' => 'Geen reisbegeleider gevonden',
            'not_found_in_trash' => 'Geen reisbegeleider gevonden in prullenbak'
        );
        $args = array(
            'label' => 'Reisbegeleider',
            'description' => 'Reisbegeleider',
            'labels' => $labels,
            'supports' => array('title'),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-groups',
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'rewrite' => array('slug' => 'over-nosun/reisbegeleiders', 'with_front' => false)
        );
        register_post_type('tourguide', $args);
    }

    /**
     * Obtains all the Guide post titles indexed by their Assumax Id.
     *
     * @return array A list of all the Guide post titles indexed by their Assumax Id.
     */
    public static function get_repeater_values() {
        global $wpdb;
        $query = sprintf("SELECT post_title, meta_value FROM %s JOIN %s ON ID=post_id AND post_type = 'tourguide' WHERE meta_key = '_assumax_id';",
            $wpdb->posts, $wpdb->postmeta);
        $results = $wpdb->get_results($query);
        $customers = [];
        foreach ($results as $result) {
            $customers[$result->meta_value] = $result->post_title;
        }
        return $customers;
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
            "Item" => "Guide",
            "Action" => "Created",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Guides->vazquez_webhooks_setup]: Could not add the Created action webhook for the Guides class.");
        }
        $result = $client->put("/webhooks", [
            "Item" => "Guide",
            "Action" => "Modified",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Guides->vazquez_webhooks_setup]: Could not add the Modified action webhook for the Guides class.");
        }
        $result = $client->put("/webhooks", [
            "Item" => "Guide",
            "Action" => "Deleted",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Guides->vazquez_webhooks_setup]: Could not add the Deleted action webhook for the Guides class.");
        }
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_filter('manage_tourguide_posts_columns', [self::class, 'manage_tourguide_admin_columns'], 20);
        $loader->add_action('manage_tourguide_posts_custom_column', [self::class, 'manage_tourguide_admin_column'], 20, 2);
        $loader->add_action('wp_ajax_update_assumax_tourguide', [self::class, 'ajax_update_tourguide']);
        $loader->add_action('init', [Guides::class, 'register_post_type']);
        $loader->add_action('vazquez_webhooks_setup', [self::class, 'vazquez_webhooks_setup'], 10, 2);
    }
}
