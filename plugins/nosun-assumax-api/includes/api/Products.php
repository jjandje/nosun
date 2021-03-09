<?php /** @noinspection SqlNoDataSourceInspection */

namespace Vazquez\NosunAssumaxConnector\Api;

use Exception;
use WP_Post;

/**
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Products implements ILoadable {
    /**
     * Obtains the Extra Product which AssumaxId is equal to the one provided.
     *
     * @param string $assumaxId The AssumaxId for the Extra Product.
     * @return WP_Post|null A post with type 'extraproduct' belonging to the provided Assumax Id, or null if something
     * went wrong.
     */
    public static function get_by_assumax_id($assumaxId) {
        if (empty($assumaxId)) return null;
        $args = [
            'numberposts' => 1,
            'post_type' => 'extraproduct',
            'meta_key' => '_extra_product_assumax_id',
            'meta_value' => $assumaxId
        ];
        $posts = get_posts($args);
        if (empty($posts)) return null;
        return $posts[0];
    }

    /**
     * Adds a new 'purchase' to the booking in Assumax using the parameters provided.
     *
     * @param $bookingId - The booking for which to add the product.
     * @param $description - The description of the product.
     * @param $vatRateId - The vatrate id of the product.
     * @param $count - The number of products.
     * @param $price - The price of the product.
     * @return string | bool - The api response if successful, false or '404' when otherwise.
     */
    public static function add_product_to_booking($bookingId, $description, $vatRateId, $count, $price) {
        try {
            $client = AssumaxClient::getInstance();
            $data = [
                "Description" => $description,
                "VatRateId" => $vatRateId,
                "Count" => $count,
                "Price" => $price
            ];
            return $client->put("/bookings/{$bookingId}/purchases", $data);
        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            return false;
        }
    }

    /**
     * Obtains the extra products from the api and updates the database accordingly.
     * Should the API return an empty array, then nothing is changed to preserve the old data.
     */
    public static function update_extra_products() {
        try {
            $client = AssumaxClient::getInstance();
            $webshopProducts = $client->get('/webshopproducts');
            if (!empty($webshopProducts)) {
                // Get all the current extra products.
                global $wpdb;
                $query = "SELECT {$wpdb->posts}.`ID`, {$wpdb->postmeta}.`meta_value` FROM {$wpdb->posts} INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.`ID`={$wpdb->postmeta}.`post_id` WHERE {$wpdb->posts}.`post_type`='extraproduct' AND {$wpdb->postmeta}.`meta_key`='_extra_product_assumax_id';";
                $results = $wpdb->get_results($query);
                $currentIds = [];
                foreach ($results as $result) {
                    $currentIds[$result->meta_value] = $result->ID;
                }
                // For each product in the api products, either update it or insert a new one.
                foreach ($webshopProducts as $webshopProduct) {
                    if (!array_key_exists($webshopProduct->Id, $currentIds)) {
                        // Create a new extra product.
                        $postArgs = [
                            'post_title' => $webshopProduct->Title,
                            'post_content' => $webshopProduct->Description,
                            'post_status' => 'publish',
                            'post_type' => 'extraproduct'
                        ];
                        $extraProduct = wp_insert_post($postArgs);
                        if (is_wp_error($extraProduct)) {
                            error_log("[ExtraProducts]:" . $extraProduct->get_error_message());
                            continue;
                        }
                    } else {
                        $extraProductPost = get_post($currentIds[$webshopProduct->Id]);
                        if ($extraProductPost === null) {
                            error_log("[ExtraProducts]:");
                            continue;
                        }
                        // Update the Title.
                        $postArgs = [
                            'ID' => $currentIds[$webshopProduct->Id],
                            'post_title' => $webshopProduct->Title
                        ];
                        $extraProduct = wp_update_post($postArgs);
                        if (is_wp_error($extraProduct)) {
                            error_log("[ExtraProducts]:" . $extraProduct->get_error_message());
                            continue;
                        }
                    }
                    // Update the remaining meta fields.
                    update_field('_extra_product_assumax_id', $webshopProduct->Id, $extraProduct);
                    update_field('_extra_product_description', $webshopProduct->Description, $extraProduct);
                    update_field('_extra_product_vatrate_id', $webshopProduct->VatRateId, $extraProduct);
                    update_field('_extra_product_price', $webshopProduct->Price, $extraProduct);
                    // Unset the Id.
                    unset($currentIds[$webshopProduct->Id]);
                }
                // Delete the extra products that are no longer present in the api.
                foreach ($currentIds as $assumaxId => $postId) {
                    wp_delete_post($postId, true);
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }
    }

    /**
     * Registers the extraproduct custom post type which will be used extensively by this API.
     */
    public static function register_posttype() {
        $labels = array (
            'name'               => 'Extra producten',
            'singular_name'      => 'Extra product',
            'add_new'            => 'Toevoegen',
            'add_new_item'       => 'Extra product toevoegen',
            'edit_item'          => 'Bewerk extra product',
            'new_item'           => 'Nieuw',
            'view_item'          => 'Bekijk extra product',
            'search_items'       => 'Zoek extra producten',
            'not_found'          => 'Geen extra producten gevonden',
            'not_found_in_trash' => 'Geen extra producten gevonden in prullenbak'
        );

        $args = array (
            'label'               => 'Extra producten',
            'description'         => 'Extra producten',
            'labels'              => $labels,
            'supports'            => array ( 'title' ),
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-products',
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'post',
            'rewrite'             => array ( 'slug' => 'extraproduct', 'with_front' => false )
        );

        register_post_type( 'extraproduct', $args );
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_action('init', [self::class, 'register_posttype']);
        $loader->add_action('update_extra_products_event', [self::class, 'update_extra_products']);
    }
}
