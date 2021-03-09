<?php /** @noinspection SqlNoDataSourceInspection */

namespace Roots\Sage;

use Vazquez\NosunAssumaxConnector\Api\Accommodations;
use Vazquez\NosunAssumaxConnector\Api\Bookings;
use Vazquez\NosunAssumaxConnector\Api\Customers;
use Vazquez\NosunAssumaxConnector\Api\Guides;
use Vazquez\NosunAssumaxConnector\Api\Templates;
use Vazquez\NosunAssumaxConnector\Api\Trips;

add_action('init', function() {
    $mutex = get_option('_all_update_active');
    if ($mutex === '1') {
        error_log("[ApiAllUpdate]: Already updating.");
    } else {
        set_time_limit(3600);
        ini_set('memory_limit','4096M');
        wp_suspend_cache_addition(true);
        update_option('_all_update_active', '1', false);
        error_log("[ApiAllUpdate]: Starting updating process.");

        /**
         * Pulls all Customers from the API and upserts them.
         */
        if (defined('VAZQUEZ_API_ALL_CUSTOMERS') && VAZQUEZ_API_ALL_CUSTOMERS === true) {
            Customers::upsert_all_from_api();
        }

        /**
         * Matches all Customers with users by their Assumax Id.
         */
        if (defined('VAZQUEZ_API_MATCH_CUSTOMERS') && VAZQUEZ_API_MATCH_CUSTOMERS === true) {
            global $wpdb;
            $query = "SELECT post_id, meta_value FROM {$wpdb->postmeta} 
                    JOIN {$wpdb->posts} ON post_id=ID 
                    WHERE meta_key='_assumax_id' AND post_type='customer';";
            $results = $wpdb->get_results($query);
            $assumaxIdIndex = [];
            if (!empty($results)) {
                foreach ($results as $result) {
                    $assumaxIdIndex[$result->meta_value] = $result->post_id;
                }
            }
            if (!empty($assumaxIdIndex)) {
                if (!empty($results)) {
                    $query = "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = '_nosun_customer_id';";
                    $results = $wpdb->get_results($query);
                    error_log(sprintf("[ApiAllUpdate->MatchCustomers]: Matching %d customers.", count($results)));
                    foreach ($results as $result) {
                        if (!empty($result->meta_value) && key_exists($result->meta_value, $assumaxIdIndex)) {
                            error_log(sprintf("[ApiAllUpdate->MatchCustomers]: Matching user: %s with customer: %s.",
                                $result->user_id, $assumaxIdIndex[$result->meta_value]));
                            update_field('user_customer', $assumaxIdIndex[$result->meta_value], 'user_' . $result->user_id);
                        }
                    }
                }
            }
            error_log(sprintf("[ApiAllUpdate->MatchCustomers]: Done matching."));
        }

        /**
         * Pulls all Templates from the API and upserts them.
         */
        if (defined('VAZQUEZ_API_ALL_TEMPLATES') && VAZQUEZ_API_ALL_TEMPLATES === true) {
            Templates::upsert_all_from_api();
        }

        /**
         * Pulls all Trips from the API and upserts them.
         */
        if (defined('VAZQUEZ_API_ALL_TRIPS') && VAZQUEZ_API_ALL_TRIPS === true) {
            Trips::upsert_all_from_api();
        }

        /**
         * Pulls all Guides from the API and upserts them.
         */
        if (defined('VAZQUEZ_API_ALL_GUIDES') && VAZQUEZ_API_ALL_GUIDES === true) {
            Guides::upsert_all_from_api();
        }

        /**
         * Pulls all Accommodations from the API and upserts them.
         */
        if (defined('VAZQUEZ_API_ALL_ACCOMMODATIONS') && VAZQUEZ_API_ALL_ACCOMMODATIONS === true) {
            Accommodations::upsert_all_from_api();
        }

        /**
         * Pulls all Bookings from the API and upserts them.
         */
        if (defined('VAZQUEZ_API_ALL_BOOKINGS') && VAZQUEZ_API_ALL_BOOKINGS === true) {
            Bookings::upsert_all_from_api();
        }

        update_option('_all_update_active', '0', false);
        wp_suspend_cache_addition(false);
        error_log("[ApiAllUpdate]: Ending updating process.");
    }
});
