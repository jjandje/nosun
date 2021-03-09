<?php /** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */

namespace Vazquez\NosunAssumaxConnector\Api;

use DateTime;
use DateTimeZone;
use Exception;
use lib\woocommerce_reports\models\UpdateLog;
use WP_Post;
use WPSEO_Primary_Term;

/**
 * Holds all functionality dealing with the Assumax Templates.
 *
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Templates implements ILoadable {
    /**
     * Obtains the Template which AssumaxId is equal to the one provided.
     *
     * @param string $assumaxId The AssumaxId for the Template.
     * @return WP_Post | null A post with type 'template' belonging to the provided Assumax Id, or null if something
     * went wrong.
     */
    public static function get_by_assumax_id($assumaxId) {
        if (empty($assumaxId)) return null;
        $args = [
            'numberposts' => 1,
            'post_type' => 'template',
            'meta_key' => '_assumax_id',
            'meta_value' => $assumaxId
        ];
        $posts = get_posts($args);
        if (empty($posts)) return null;
        return $posts[0];
    }

    /**
     * Pulls all the available Templates from the API and upserts them.
     */
    public static function upsert_all_from_api() {
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Api\Templates->upsert_all_from_api]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return false;
        }
        $successful = [];
        $unsuccessful = [];
        $clientIterator = $client->get_all("/templates", [], 0);
        foreach ($clientIterator as $templateData) {
            if (empty($templateData->Id)) continue;
            error_log("[Api\Templates->upsert_all_from_api]: Upserting template with id: {$templateData->Id}.");
            if (self::upsert($templateData->Id, $templateData) === false) {
                $unsuccessful[] = $templateData->Id;
            } else {
                $successful[] = $templateData->Id;
            }
        }
        error_log("[Api\Templates->upsert_all_from_api]: Total amount: {$clientIterator->get_size()}.");
        error_log(sprintf("[Api\Templates->upsert_all_from_api]: List of successful updates: %s.", implode(', ', $successful)));
        error_log(sprintf("[Api\Templates->upsert_all_from_api]: List of unsuccessful updates: %s.", implode(', ', $unsuccessful)));
        return true;
    }

    /**
     * Tries to obtain the Template with the provided Assumax Id.
     * Should a template post not exist then a new is created, otherwise the existing one is updated.
     *
     * @param string $assumaxId The Assumax Id for the template.
     * @param object $templateData Optional API data to use instead of pulling it from the API.
     * @return bool true when the Template has been upserted successfully, false if otherwise.
     */
    public static function upsert($assumaxId, $templateData = null) {
        ini_set('memory_limit', '256M');
        set_time_limit(600);
        if (empty($assumaxId)) return false;
        if (!empty($templateData)) {
            $apiData = $templateData;
        } else {
            try {
                $client = AssumaxClient::getInstance();
            } catch (Exception $e) {
                error_log("[Nosun_Assumax_Api_Templates->upsert]: An exception occurred while trying to obtain the template with id: {$assumaxId} from the API.\n{$e->getMessage()}");
                return false;
            }
            $apiData = $client->get("/templates/{$assumaxId}");
        }
        if (empty($apiData)) {
            error_log("[Nosun_Assumax_Api_Templates->upsert]: Could not obtain the template with id: {$assumaxId} from the API.");
            return false;
        }
        if (empty($apiData->Title)) {
            error_log("[Nosun_Assumax_Api_Templates->upsert]: Could not create a new template as there is no title set for the template with id: {$assumaxId}.");
            return false;
        }
        // Check if a template post already exists.
        $template = self::get_by_assumax_id($assumaxId);
        if (empty($template)) {
            $args = [
                'post_type' => 'template',
                'post_title' => $apiData->Title,
                'post_status' => 'publish',
                'post_content' => isset($apiData->Description) ? $apiData->Description : "",
                'post_excerpt' => isset($apiData->ShortDescription) ? $apiData->ShortDescription : ""
            ];
            $postId = wp_insert_post($args);
            if (is_wp_error($postId)) {
                error_log("[Nosun_Assumax_Api_Templates->upsert]: Could not create a new template for id: {$assumaxId}.\n{$postId->get_error_message()}");
                return false;
            }
            update_post_meta($postId, '_assumax_id', $assumaxId);
        } else {
            $args = [
                'ID' => $template->ID,
                'post_title' => $apiData->Title,
                'post_content' => isset($apiData->Description) ? $apiData->Description : "",
                'post_excerpt' => isset($apiData->ShortDescription) ? $apiData->ShortDescription : ""
            ];
            $postId = wp_update_post($args);
            if (is_wp_error($postId)) {
                error_log("[Nosun_Assumax_Api_Templates->upsert]: Could not update the title for the template with id: {$assumaxId}.\n{$postId->get_error_message()}");
                return false;
            }
        }
        // Update the template meta data.
        return self::update_template_meta($postId, $apiData);
    }

    /**
     * Updates all the meta data for the template with the provided post id and api data.
     * Will also download any images that are missing.
     *
     * @param int $postId The template post id.
     * @param mixed $apiData The data from the API in json decoded form.
     * @return bool true when the update process completed successfully, false if otherwise.
     */
    private static function update_template_meta($postId, $apiData) {
        if (empty($postId) || empty($apiData)) return false;
        $attachmentIds = [];
        // Update the product images.
        if (!empty($apiData->ProductImages)) {
            $productImages = [];
            foreach ($apiData->ProductImages as $productImage) {
                $attachmentId = Attachments::handle_product_image($productImage, $postId);
                if (!empty($attachmentId)) {
                    $attachmentIds[] = $attachmentId;
                    $productImages[] = [
                        'image' => $attachmentId,
                        'title' => $productImage->Title,
                        'index' => !isset($productImage->Index) ? 99999 : $productImage->Index
                    ];
                }
            }
            update_field('template_product_images', $productImages, $postId);
        }
        // Update the banner image.
        if (!empty($apiData->BannerImage)) {
            $attachmentId = Attachments::handle_profile_banner_image($apiData->BannerImage, $postId, false);
            if ($attachmentId !== false) {
                update_field('template_banner_image', $attachmentId, $postId);
                $attachmentIds[] = $attachmentId;
            }
        }
        // Update the profile image.
        if (!empty($apiData->ProfileImage)) {
            $attachmentId = Attachments::handle_profile_banner_image($apiData->ProfileImage, $postId, true);
            if ($attachmentId !== false) {
                update_field('template_profile_image', $attachmentId, $postId);
                $attachmentIds[] = $attachmentId;
            }
        }
        // Update the product days.
        if (!empty($apiData->ProductDays)) {
            usort($apiData->ProductDays, function ($a, $b) {
                if ($a->Index === $b->Index) return 0;
                return $a->Index < $b->Index ? -1 : 1;
            });
            $trip_days = [];
            foreach ($apiData->ProductDays as $productDay ) {
                if (empty($productDay->Title)) continue;
                $attachmentId = Attachments::handle_product_day_image($productDay, $postId);
                // Save the updated image url in the list.
                if ($attachmentId !== false) $attachmentIds[] = $attachmentId;
                // Add the meta data for this product day to the list.
                $trip_days[] = array (
                    'title' => $productDay->Title,
                    'description' => empty($productDay->Description) ? '' : $productDay->Description,
                    'image' => !empty($attachmentId) ? $attachmentId : '',
                    'days' => !isset($productDay->Days) ? 0 : $productDay->Days,
                    'index' => !isset($productDay->Index) ? 99999 : $productDay->Index
                );
            }
            update_field('template_product_days', $trip_days, $postId);
        }
        // All the attachments have been collected, move them to the correct folder and save the ids to the _attachments meta_key.
        if (!empty($attachmentIds)) {
            $folderName = $apiData->Title . ' - ' . $postId;
            Attachments::create_rml_shortcuts($folderName, $attachmentIds, 'template');
        }
        update_post_meta($postId, '_attachments', $attachmentIds);
        // TripTypes
        if (!empty($apiData->TripTypes)) {
            $tripTypes = array();
            foreach ($apiData->TripTypes as $tripType) {
                if (empty($tripType->Title)) continue;
                $tripTypes[] = $tripType->Title;
            }
            wp_set_object_terms($postId, $tripTypes, 'trip-type');
        }
        // AgeGroup
        if (!empty($apiData->AgeGroup)) {
            switch ($apiData->AgeGroup) {
                case 'Young':
                    $ageGroup = '20 - 45 jaar';
                    break;
                case 'Old':
                    $ageGroup = '40 - 59 jaar';
                    break;
                default:
                    $ageGroup = array(
                        '20 - 45 jaar',
                        '40 - 59 jaar'
                    );
            }
            wp_set_object_terms($postId, $ageGroup, 'age-group');
        }
        // Countries
        if (!empty($apiData->Countries)) {
            $countries = [];
            foreach ($apiData->Countries as $Country) {
                if (empty($Country->Title)) continue;
                $countries[] = $Country->Title;
            }
            wp_set_object_terms($postId, $countries, 'destination');
        }
        // Destination
        if (!empty($apiData->Destination) && !empty($apiData->Destination->Title)) {
            $primaryTerm = get_term_by('name', $apiData->Destination->Title, 'destination');
            if(empty($primaryTerm)) {
            	$newTerm = wp_insert_term($apiData->Destination->Title, 'destination');
            	$primaryTerm = null;
            	if(!is_wp_error($newTerm)) $primaryTerm = get_term_by('name', $apiData->Destination->Title, 'destination');
            }
            if(!empty($primaryTerm) && !is_wp_error( $primaryTerm )) {
	            $primaryTermObject = new WPSEO_Primary_Term('destination', $postId);
	            $primaryTermObject->set_primary_term($primaryTerm->term_id);
            }
        }
        // Accommodations
        if (!empty($apiData->Accommodations)) {
            $accommodations = [];
            foreach ($apiData->Accommodations as $accommodation) {
                if (!isset($accommodation->Id) || empty($accommodation->Title)) continue;
                $accommodations[] = array(
                    'assumax_id' => $accommodation->Id,
                    'title' => $accommodation->Title
                );
            }
            update_field('template_accommodations', $accommodations, $postId);
        }
        // WebshopProducts
	    $webshopProducts = [];
	    if (!empty($apiData->WebshopProducts)) {
            foreach ($apiData->WebshopProducts as $webshopProduct) {
                if (!isset($webshopProduct->Id) || empty($webshopProduct->Title)) continue;
                $webshopProducts[] = [
                    'assumax_id' => $webshopProduct->Id,
                    'title' => $webshopProduct->Title
                ];
            }
	    }
	    update_field('template_webshop_products', $webshopProducts, $postId);

	    // BoardingPoints
        if (!empty($apiData->BoardingPoints)) {
            $boardingPoints = array();
            foreach ($apiData->BoardingPoints as $boardingPoint) {
                if (empty($boardingPoint->Title)) continue;
                $boardingPoints[] = [
                    'title' => $boardingPoint->Title
                ];
            }
            update_field('template_boarding_points', $boardingPoints, $postId);
        }
        // Highlights
        if (!empty($apiData->Highlights)) {
            $highlights = array();
            foreach ($apiData->Highlights as $highlight) {
                if (empty($highlight->Title)) continue;
                $highlights[] = array(
                    'title' => $highlight->Title
                );
            }
            update_field('template_highlights', $highlights, $postId);
        }
        // USPs
        if (!empty($apiData->Usps)) {
            $usps = array();
            foreach ($apiData->Usps as $usp) {
                if (empty($usp->Title)) continue;
                $usps[] = array(
                    'title' => $usp->Title
                );
            }
            update_field('template_usps', $usps, $postId);
        }
        // PackItems
        if (!empty($apiData->PackItems)) {
            $packItems = array();
            foreach ($apiData->PackItems as $packItem) {
                if (empty($packItem->Title)) continue;
                $packItems[] = array(
                    'title' => $packItem->Title
                );
            }
            update_field('template_pack_items', $packItems, $postId);
        }
        // Included
        if (!empty($apiData->Included)) {
            $includedItems = array();
            foreach ($apiData->Included as $included) {
                if (empty($included->Title)) continue;
                $includedItems[] = array(
                    'title' => $included->Title
                );
            }
            update_field('template_included_items', $includedItems, $postId);
        }
        // Excluded
        if (!empty($apiData->Excluded)) {
            $excludedItems = array();
            foreach ($apiData->Excluded as $excluded) {
                if (empty($excluded->Title)) continue;
                $excludedItems[] = array(
                    'title' => $excluded->Title
                );
            }
            update_field('template_excluded_items', $excludedItems, $postId);
        }
        update_field('template_slogan', empty($apiData->Slogan) ? '' : $apiData->Slogan, $postId);
        update_field('template_subtitle', empty($apiData->Subtitle) ? '' : $apiData->Subtitle, $postId);
        update_field('template_subtitle2', empty($apiData->Subtitle2) ? '' : $apiData->Subtitle2, $postId);
        update_field('template_important_information', empty($apiData->ImportantInformation) ? '' : $apiData->ImportantInformation, $postId);
        update_field('template_requires_identification', !isset($apiData->RequiresIdentification) ? '0' : $apiData->RequiresIdentification, $postId);
        update_field('template_map_url', empty($apiData->MapUrl) ? '' : $apiData->MapUrl, $postId);
        update_field('template_youtube_url', empty($apiData->YoutubeUrl) ? '' : $apiData->YoutubeUrl, $postId);

        // Parse legacy ratings and delete any old products with te same Assumax Id.
        self::parse_legacy_ratings($apiData->Id, $postId);

        return true;
    }

    /**
     * Sets the template with the provided Assumax Id to deleted.
     * Note: This will not actually hard delete the template, this can be done manually in the admin interface.
     *
     * @param string $assumaxId The Assumax Id of the template.
     * @return bool true when the template has successfully been set to deleted, false if otherwise.
     */
    public static function delete($assumaxId) {
        $templatePost = self::get_by_assumax_id($assumaxId);
        if (!isset($templatePost)) {
            error_log("[Nosun_Assumax_Api_Templates->delete_template]: There exists no template with Assumax Id: {$assumaxId}.]");
            return false;
        }
        if (empty(wp_delete_post($templatePost->ID))) return false;
        return true;
    }

    /**
     * Upserts the template with the provided id if the action is either 'Created' or 'Modified'.
     * Deletes the template if the action is 'Deleted'.
     *
     * @param string $assumaxId The Assumax id of the template.
     * @param string $action The action to perform as supplied by Assumax.
     */
    public static function webhook_callback($assumaxId, $action) {
        do_action('vazquez_add_update_log_entry', 'Template', $action, $assumaxId);
        if ($action === 'Created' || $action === 'Modified') {
            self::upsert($assumaxId);
        }
        elseif ($action === 'Deleted') {
            self::delete($assumaxId);
        } else {
            error_log("[Nosun_Assumax_Api_Templates->webhook_callback]: Action: {$action} for id: {$assumaxId} is not supported.]");
        }
    }

    /**
     * Adds and removes several columns to the template post list on the admin screen.
     *
     * @param mixed $columns The current set of columns.
     * @return mixed The modified set of columns.
     */
    public static function manage_template_admin_columns($columns) {
        // Add new columns.
        $columns['assumax_id'] = __('Assumax Id', 'nosun_assumax');
        $columns['update_assumax'] = __('Update uit Assumax', 'nosun_assumax');
        return $columns;
    }

    /**
     * Fills in the extra template columns depending on the column name provided.
     *
     * @param string $column The column name.
     * @param int $postId The post id of the template.
     */
    public static function manage_template_admin_column($column, $postId) {
        switch ($column) {
            case 'assumax_id': {
                echo get_post_meta($postId, '_assumax_id', true);
                break;
            }
            case 'update_assumax': {
                $assumaxId = get_post_meta($postId, '_assumax_id', true);
                echo "<button class='update-assumax' data-assumax_id='{$assumaxId}' data-action='update_assumax_template'>Update</button>";
                break;
            }
        }
    }

    /**
     * AJAX function that upserts the Template with the Assumax Id provided in the POST global.
     */
    public static function ajax_update_template() {
        if (!is_admin()) return;
        $assumaxId = $_POST['assumax_id'];
        if (empty($assumaxId)) return;
        $lock = Locks::acquire_lock($assumaxId, 'Template', 'Modified', 1);
        if ($lock > 0) {
            static::upsert($assumaxId);
        }
        Locks::release_lock($lock);
    }

    /**
     * Checks if an old product exists with the _nosun_template_id set to the Assumax Id. Should it exist then obtain
     * the ratings and add them to the Template.
     *
     * Deletes the old product afterwards and does not override existing ratings.
     *
     * @param int $templateAssumaxId The id of the Template in Assumax.
     * @param int $templatePostId The post id of the Template.
     */
    public static function parse_legacy_ratings(int $templateAssumaxId, int $templatePostId) : void
    {
        if (empty($templateAssumaxId) || empty($templatePostId)) {
            return;
        }
        global $wpdb;
        $query = "SELECT post_id FROM {$wpdb->postmeta} 
                    WHERE meta_key = '_nosun_template_id' 
                    AND meta_value = '{$templateAssumaxId}'
                    LIMIT 1;";
        $oldPostId = $wpdb->get_var($query);
        if (empty($oldPostId)) {
            return;
        }
        $query = "SELECT meta_key, meta_value FROM {$wpdb->postmeta}
                    WHERE meta_key LIKE 'product-rating_%_product-rating%' AND post_id = {$oldPostId};";
        $rows = $wpdb->get_results($query);
        if (empty($rows)) {
            return;
        }
        $ratings = [];
        foreach ($rows as $row) {
            preg_match('/^product-rating_(\d+)_product-rating-([a-zA-Z]+)$/', $row->meta_key, $matches);
            if (empty($matches)) {
                continue;
            }
            if (!key_exists($matches[1], $ratings)) {
                $ratings[$matches[1]] = [];
            }
            switch ($matches[2]) {
                case 'name': {
                    $ratings[$matches[1]]['name'] = $row->meta_value;
                    break;
                }
                case 'age': {
                    $ratings[$matches[1]]['age'] = intval($row->meta_value);
                    break;
                }
                case 'date': {
                    $ratings[$matches[1]]['date'] = $row->meta_value;
                    break;
                }
                case 'text': {
                    $ratings[$matches[1]]['message'] = $row->meta_value;
                    break;
                }
                case 'rating': {
                    switch ($row->meta_value) {
                        case "one": {
                            $score = 1;
                            break;
                        }
                        case "two": {
                            $score = 2;
                            break;
                        }
                        case "three": {
                            $score = 3;
                            break;
                        }
                        case "five": {
                            $score = 5;
                            break;
                        }
                        default: { // "four" and other string values default to 4.
                            $score = 4;
                            break;
                        }
                    }
                    $ratings[$matches[1]]['score'] = $score;
                    break;
                }
                default: {
                    break;
                }
            }
        }
        $currentRatings = get_field('template_ratings', $templatePostId);
        if (empty($currentRatings)) {
            $newRatings = array_values($ratings);
        } else {
            $newRatings = array_merge($currentRatings, array_values($ratings));
        }
        update_field('template_ratings', $newRatings, $templatePostId);
        // Remove the old product.
        wp_delete_post($oldPostId, true);
    }

    /**
     * Registers the template custom post type.
     */
    public static function register_posttype() {
        $labels = array (
            'name'               => 'Templates',
            'singular_name'      => 'Template',
            'add_new'            => 'Toevoegen',
            'add_new_item'       => 'Template toevoegen',
            'edit_item'          => 'Bewerk template',
            'new_item'           => 'Nieuw',
            'view_item'          => 'Bekijk template',
            'search_items'       => 'Zoek templates',
            'not_found'          => 'Geen templates gevonden',
            'not_found_in_trash' => 'Geen templates gevonden in prullenbak'
        );

        $args = array (
            'label'               => 'Templates',
            'description'         => 'Templates',
            'labels'              => $labels,
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-products',
            'has_archive'         => false,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'rewrite'             => array ( 'slug' => 'reizen', 'with_front' => false )
        );

        register_post_type( 'template', $args );
    }

    /**
     * Registers the taxonomies used by the Templates.
     */
    public static function register_taxonomies() {
        $labels = array(
            'name'                       => 'Bestemmingen',
            'singular_name'              => 'Bestemming',
            'menu_name'                  => 'Bestemmingen',
            'all_items'                  => 'Alle bestemmingen',
            'parent_item'                => 'Hoofditem',
            'parent_item_colon'          => 'Hoofditem:',
            'new_item_name'              => 'Nieuwe bestemming',
            'add_new_item'               => 'Nieuwe bestemming',
            'edit_item'                  => 'Wijzig bestemming',
            'update_item'                => 'Update bestemming',
            'separate_items_with_commas' => 'Scheiden met een comma',
            'search_items'               => 'Zoek bestemmingen',
            'add_or_remove_items'        => 'Voeg toe of verwijder bestemmingen',
            'choose_from_most_used'      => 'Meest gebruikte',
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => false,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'rewrite'                    => array('slug' => 'bestemmingen', 'with_front' => false)
        );

        register_taxonomy( 'destination', 'template', $args );

        $labels = array(
            'name'                       => 'Type reizen',
            'singular_name'              => 'Type reis',
            'menu_name'                  => 'Type reis',
            'all_items'                  => 'Alle types',
            'parent_item'                => 'Hoofditem',
            'parent_item_colon'          => 'Hoofditem:',
            'new_item_name'              => 'Nieuwe reis type',
            'add_new_item'               => 'Nieuwe reis type',
            'edit_item'                  => 'Wijzig type',
            'update_item'                => 'Update type',
            'separate_items_with_commas' => 'Scheiden met een comma',
            'search_items'               => 'Zoek types',
            'add_or_remove_items'        => 'Voeg toe of verwijder types',
            'choose_from_most_used'      => 'Meest gebruikte',
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => false,
            'show_ui'                    => true,
            'show_admin_column'          => false,
            'show_in_nav_menus'          => true,
        );

        register_taxonomy( 'trip-type', 'template', $args );

        $labels = array(
            'name'                       => 'Leeftijdscategorie',
            'singular_name'              => 'Leeftijdscategorie',
            'menu_name'                  => 'Leeftijdscategorie',
            'all_items'                  => 'Alle leeftijdscategorieën',
            'parent_item'                => 'Hoofditem',
            'parent_item_colon'          => 'Hoofditem:',
            'new_item_name'              => 'Nieuwe leeftijdscategorie',
            'add_new_item'               => 'Nieuwe leeftijdscategorie',
            'edit_item'                  => 'Wijzig leeftijdscategorie',
            'update_item'                => 'Update leeftijdscategorie',
            'separate_items_with_commas' => 'Scheiden met een comma',
            'search_items'               => 'Zoek leeftijdscategorie',
            'add_or_remove_items'        => 'Voeg toe of verwijder',
            'choose_from_most_used'      => 'Meest gebruikte',
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => false,
            'show_ui'                    => true,
            'show_admin_column'          => false,
            'show_in_nav_menus'          => true,
        );

        register_taxonomy( 'age-group', 'template', $args );
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
            "Item" => "Template",
            "Action" => "Created",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Templates->vazquez_webhooks_setup]: Could not add the Created action webhook for the Templates class.");
        }
        $result = $client->put("/webhooks", [
            "Item" => "Template",
            "Action" => "Modified",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Templates->vazquez_webhooks_setup]: Could not add the Modified action webhook for the Templates class.");
        }
        $result = $client->put("/webhooks", [
            "Item" => "Template",
            "Action" => "Deleted",
            "Url" => $url
        ], true, false);
        if ($result === false) {
            error_log("[Templates->vazquez_webhooks_setup]: Could not add the Deleted action webhook for the Templates class.");
        }
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_filter('manage_template_posts_columns', [self::class, 'manage_template_admin_columns'], 20);
        $loader->add_action('manage_template_posts_custom_column', [self::class, 'manage_template_admin_column'], 20, 2);
        $loader->add_action('wp_ajax_update_assumax_template', [self::class, 'ajax_update_template']);
        $loader->add_action('init', [self::class, 'register_posttype']);
        $loader->add_action('init', [self::class, 'register_taxonomies']);
        $loader->add_action('vazquez_webhooks_setup', [self::class, 'vazquez_webhooks_setup'], 10, 2);
    }
}
