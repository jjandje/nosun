<?php

namespace Vazquez\NosunAssumaxConnector\Api;

use Exception;
use WP_Post;

/**
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Documents implements ILoadable {
    /**
     * Obtains the Document for which the AssumaxId equals the provided one.
     *
     * @param $assumaxId
     * @return WP_Post|null The Document or null should it not exist or when something goes wrong.
     */
    public static function get_by_assumax_id($assumaxId) {
        if (empty($assumaxId)) return null;
        $documentPosts = self::get_by_assumax_ids([$assumaxId], 1);
        return empty($documentPosts) ? null : $documentPosts[0];
    }

    /**
     * Obtains all the Documents for which there exist Assumax Ids in the provided array of Assumax Ids.
     *
     * @param array $assumaxIds An array of Assumax Ids for which to obtain Documents.
     * @param int $limit The maximum amount of Documents to obtain where -1 means no limit.
     * @return WP_Post[] An array of Documents which have the specified AssumaxIds or an empty array should something
     * go wrong.
     */
    public static function get_by_assumax_ids($assumaxIds, $limit = -1) {
        if (empty($assumaxIds)) return [];
        $args = [
            'post_type' => 'document',
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
     * Tries to either update or create a new Document depending on the provided data.
     * Should an Assumax Id be provided, then the function will try to obtain the data from the API.
     * If a websiteData parameter is provided and the assumaxId parameter is empty, then a new Document is created using
     * the provided data which needs to be in the same form as the API data. The document is then put to the API.
     *
     * @param int $customerAssumaxId The Assumax Id of the Customer whose Document this is.
     * @param int $assumaxId Assumax Id with which to pull the Document data from the API.
     * @return int|bool The Assumax Id of the Document or false should something go wrong.
     */
    public static function upsert($customerAssumaxId, $assumaxId) {
        ini_set('memory_limit', '256M');
        set_time_limit(600);
        if (empty($customerAssumaxId) || empty($assumaxId)) return false;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Documents->upsert]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return false;
        }
        $data = $client->get(sprintf("customers/%s/document/%s", $customerAssumaxId, $assumaxId));
        if (empty($data)) {
            error_log("[Nosun_Assumax_Api_Documents->upsert]: Could not obtain the document with id: {$assumaxId} for customer with id: {$customerAssumaxId} from the API.");
            return false;
        }
        $post = self::get_by_assumax_id($data->Id);
        if (empty($post)) {
            return self::create_document($data, $customerAssumaxId);
        } else {
            return self::update_document($post, $data, $customerAssumaxId);
        }
    }

    /**
     * Updates a Document with the data provided.
     *
     * @param WP_Post $post The Document post which needs to be updated.
     * @param mixed $data Data structure in the same form as defined by the API.
     * @param int $customerAssumaxId The Assumax Id of the Customer to which this Document belongs.
     * @return int|bool The Assumax Id of the Document or false should something go wrong.
     */
    public static function update_document($post, $data, $customerAssumaxId) {
        if (empty($post) || empty($data) || empty($data->Id) || empty($data->Title)) return false;
        $args = [
            'ID' => $post->ID,
            'post_title' => "{$customerAssumaxId} -> {$data->Id}",
            'post_content' => "",
            'post_excerpt' => ""
        ];
        $postId = wp_update_post($args);
        if (is_wp_error($postId)) {
            error_log("[Nosun_Assumax_Api_Documents->update_document]: Could not update the title of the document with Id: {$data->Id}.\n{$postId->get_error_message()}");
            return false;
        }
        return self::update_document_meta($postId, $data) ? $data->Id : false;
    }

    /**
     * Creates a new Document using the provided data.
     *
     * @param mixed $data Data structure in the same form as defined by the API.
     * @param int $customerAssumaxId The Assumax Id of the Customer to which this Document belongs.
     * @return int|bool The Assumax Id of the Document or false should something go wrong.
     */
    public static function create_document($data, $customerAssumaxId) {
        if (empty($data) || empty($data->Id)) return false;
        $args = [
            'post_type' => "document",
            'post_title' => "{$customerAssumaxId} -> {$data->Id}",
            'post_content' => "",
            'post_excerpt' => "",
            'post_status' => "publish"
        ];
        $postId = wp_insert_post($args);
        if (is_wp_error($postId)) {
            error_log("[Nosun_Assumax_Api_Documents->create_document]: Could not create a new document with Assumax Id: {$data->Id}.\n{$postId->get_error_message()}");
            return false;
        }
        update_post_meta($postId, '_assumax_id', $data->Id);
        if (!self::update_document_meta($postId, $data)) {
            error_log("[Nosun_Assumax_Api_Documents->create_document]: Could not update the meta fields for the document with Assumax Id: {$data->Id}.");
            wp_delete_post($postId, true);
            return false;
        }
        return $data->Id;
    }

    /**
     * Updates the meta fields for the provided Document with the provided data.
     *
     * @param int $postId The Document post for which the meta fields need to be changed.
     * @param mixed $data The data fields in the same format as the API.
     * @return bool true when the fields have been set successfully, false if otherwise.
     */
    public static function update_document_meta($postId, $data) {
        if (empty($postId) || empty($data)) return false;
        if (empty($data->City) || empty($data->Expires) ||
            !isset($data->CountryId) || !isset($data->DocumentType)) return false;
        update_field('document_title', $data->Title, $postId);
        update_field('document_city', $data->City, $postId);
        update_field('document_expires', date('Y-m-d', strtotime($data->Expires)), $postId);
        update_field('document_country_id', $data->CountryId, $postId);
        update_field('document_type', $data->DocumentType, $postId);
        return true;
    }

    /**
     * Tries to insert a new Document into the API using the data provided.
     *
     * @param mixed $data Data structure in the same form as specified in the API.
     * @param int $customerAssumaxId The AssumaxId of the Customer for which to insert the Document.
     * @return int|null The Assumax Id of the newly inserted Document or null should something go wrong.
     * @note The API requires the data to be in the following form:
     * {
     *      "Title": "string",
     *      "City": "string",
     *      "Expires": "2019-08-02T07:12:11.698Z",
     *      "CountryId": 0,
     *      "DocumentType": 1
     * }
     */
    public static function insert_into_api($data, $customerAssumaxId) {
        if (empty($data) || empty($data->Title) || empty($data->City) || empty($data->Expires) ||
            !isset($data->CountryId) || !isset($data->DocumentType) || empty($customerAssumaxId)) return null;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Documents->insert_into_api]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return null;
        }
        $apiData = [
            'Title' => $data->Title,
            'City' => $data->City,
            'Expires' => date('Y-m-d\TH:i:s', strtotime($data->Expires)),
            'CountryId' => $data->CountryId,
            'DocumentType' => $data->DocumentType
        ];
        $assumaxId = $client->put(sprintf("customers/%s/documents", $customerAssumaxId), $apiData);
        return $assumaxId === false ? null : $assumaxId;
    }

    /**
     * Tries to update an existing Document in the API using the data provided.
     *
     * @param mixed $data Data structure in the same form as specified in the API.
     * @param int $customerAssumaxId The Assumax Id of the Customer whose Document this is.
     * @return int|null The Assumax Id of the updated Document or null should something go wrong.
     * @note The API requires the data to be in the following form:
     * {
     *      "Title": "string",
     *      "City": "string",
     *      "Expires": "2019-08-02T07:12:11.698Z",
     *      "CountryId": 0,
     *      "DocumentType": 1
     * }
     */
    public static function update_in_api($data, $customerAssumaxId) {
        if (empty($data) || empty($data->Id) || empty($customerAssumaxId)) return null;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $e) {
            error_log("[Nosun_Assumax_Api_Documents->update_in_api]: An exception occurred while trying to create the client.\n{$e->getMessage()}");
            return null;
        }
        $apiData = [];
        if (!empty($data->Title)) $apiData['Title'] = $data->Title;
        if (!empty($data->City)) $apiData['City'] = $data->City;
        if (!empty($data->Expires)) $apiData['Expires'] = date('Y-m-d\TH:i:s', strtotime($data->Expires));
        if (isset($data->CountryId)) $apiData['CountryId'] = $data->CountryId;
        if (isset($data->DocumentType)) $apiData['DocumentType'] = $data->DocumentType;
        if (empty($apiData)) return $data->Id;
        $result = $client->post(sprintf("customers/%s/documents/%s", $customerAssumaxId, $data->Id), $apiData);
        return $result === false ? false : $data->Id;
    }

    /**
     * Registers the document custom post type.
     */
    public static function register_posttype() {
        $labels = array (
            'name'               => 'Documenten',
            'singular_name'      => 'Document',
            'add_new'            => 'Toevoegen',
            'add_new_item'       => 'Document toevoegen',
            'edit_item'          => 'Bewerk document',
            'new_item'           => 'Nieuw',
            'view_item'          => 'Bekijk document',
            'search_items'       => 'Zoek documenten',
            'not_found'          => 'Geen documenten gevonden',
            'not_found_in_trash' => 'Geen documenten gevonden in prullenbak'
        );

        $args = array (
            'label'               => 'Documenten',
            'description'         => 'Documenten',
            'labels'              => $labels,
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'edit.php?post_type=customer',
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false,
            'menu_icon'           => 'dashicons-format-aside',
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'post',
            'show_in_rest'        => false,
            'supports'            => ['title']
        );

        register_post_type( 'document', $args );
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_action('init', [self::class, 'register_posttype']);
    }
}
