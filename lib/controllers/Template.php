<?php
/** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */

namespace lib\controllers;

use DateTime;
use Roots\Sage\Helpers;
use Vazquez\NosunAssumaxConnector\Api\Accommodations;
use Vazquez\NosunAssumaxConnector\Api\Templates;
use Vazquez\NosunAssumaxConnector\Api\Trips;
use WP_Post;
use WP_Term;
use WPSEO_Primary_Term;

/**
 * Controller for the Templates.
 * Has several functions that can be used to obtain various template elements.
 * Uses a static class cache for all the obtained values to prevent multiple queries.
 *
 * Class Template
 * @package lib\controllers
 */
class Template {
    // Static caches
    private static $hasAvailableTrips = [];
    private static $availableTemplates = [];
    private static $trips = [];
    private static $nextDeparture = [];
    private static $numDaysRange = [];
    private static $lowestPrice = [];
    private static $insurances = [];
    private static $accommodations = [];

    /**
     * Obtains the AssumaxId of the current Template.
     * Always returns the AssumaxId provided as parameter when it is non-empty.
     *
     * @param int|null $assumaxId Optional Assumax Id of the template.
     * @return int|null Either the Assumax Id or null should none exist.
     */
    private static function get_the_assumax_id($assumaxId = null) {
        if (!empty($assumaxId)) return $assumaxId;
        if (empty(get_the_ID())) return null;
        $id = get_post_meta(get_the_ID(), '_assumax_id', true);
        if (!empty($id)) return $id;
        return null;
    }

    /**
     * Checks whether or not the target Template has any available trips.
     * Uses the global post id to obtain the AssumaxId should none be provided.
     *
     * @param int|null $assumaxId Optional Assumax Id of the Template.
     * @return boolean Whether or not the Template has any available trips.
     */
    public static function has_available_trips($assumaxId = null) {
        $id = self::get_the_assumax_id($assumaxId);
        if (empty($id)) return false;
        if (key_exists($id, self::$hasAvailableTrips)) return self::$hasAvailableTrips[$id];
        global $wpdb;
        $today = Helpers::today();
        if (empty($today)) return self::$hasAvailableTrips = false;
        $query = sprintf("SELECT count(trip_assumax_id) FROM api_trip_template_pivot 
                            WHERE template_assumax_id=%d
                            AND trip_availability!='Unavailable' 
                            AND trip_start_date>='%s';",
            $id, $today->format('Y-m-d'));
        $rows = $wpdb->get_col($query);
        return self::$hasAvailableTrips[$id] = !empty($rows);
    }

    /**
     * Obtains the Trips for the target Template.
     * Uses the global post id to obtain the AssumaxId should none be provided.
     *
     * @param int|null $assumaxId Optional Assumax Id of the Template.
     * @return array Array of Trip posts or an empty list should no Trips exist or when something has gone wrong.
     */
    public static function get_trips($assumaxId = null) {
        $id = self::get_the_assumax_id($assumaxId);
        if (empty($id)) return [];
        if (key_exists($id, self::$trips)) return self::$trips[$id];
        return self::$trips[$id] = Trips::get_by_template_id($id);
    }

    /**
     * For the target Template retrieves the Trip date that is next in line to depart.
     * Uses the global post id to obtain the AssumaxId should none be provided.
     *
     * @param int|null $assumaxId Optional Assumax Id of the Template.
     * @return null|DateTime Either a DateTime object of the next departure date, or null if the Template has no
     * Trips that will depart in the future.
     */
    public static function get_next_departure($assumaxId = null) {
        $id = self::get_the_assumax_id($assumaxId);
        if (empty($id)) return null;
        if (key_exists($id, self::$nextDeparture)) return self::$nextDeparture[$id];
        $today = Helpers::today();
        if (empty($today)) return self::$nextDeparture[$id] = null;
        global $wpdb;
        $query = sprintf('SELECT trip_start_date FROM api_trip_template_pivot 
                            WHERE template_assumax_id=%d 
                            AND trip_availability!=\'Unavailable\' 
                            AND trip_start_date>=\'%s\' 
                            ORDER BY trip_start_date ASC LIMIT 1;',
            $id, $today->format('Y-m-d'));
        $nextDeparture = $wpdb->get_var($query);
        if (empty($nextDeparture)) return self::$nextDeparture[$id] = null;
        return self::$nextDeparture[$id] = Helpers::create_local_datetime($nextDeparture);
    }

    /**
     * For the target Template retrieves the range of the number of days that each Trip has.
     * Uses the global post id to obtain the AssumaxId should none be provided.
     *
     * @param int|null $assumaxId Optional Assumax Id of the Template.
     * @return string|null A formatted string with the range of the number of days or 'onbekend' when there are no available Trips.
     */
    public static function get_num_days_range($assumaxId = null) {
        $id = self::get_the_assumax_id($assumaxId);
        if (empty($id)) return null;
        if (key_exists($id, self::$numDaysRange)) return self::$numDaysRange[$id];
        $today = Helpers::today();
        if (empty($today)) return self::$numDaysRange[$id] = 'onbekend';
        global $wpdb;
        $query = sprintf('SELECT min(trip_numdays) as min_days, max(trip_numdays) as max_days FROM `api_trip_template_pivot`
                            WHERE template_assumax_id=%d 
                            AND trip_availability!=\'Unavailable\' 
                            AND trip_start_date>=\'%s\';',
            $id, $today->format('Y-m-d'));
        $results = $wpdb->get_row($query);
        if (empty($results)) return self::$numDaysRange[$id] = 'onbekend';
        if ($results->min_days === $results->max_days) return self::$numDaysRange[$id] = $results->min_days;
        return self::$numDaysRange[$id] = sprintf("%d-%d", $results->min_days, $results->max_days);
    }

    /**
     * For the target Template retrieves the lowest price among the available Trips.
     * Uses the global post id to obtain the AssumaxId should none be provided.
     *
     * @param int|null $assumaxId Optional Assumax Id of the Template.
     * @return int|null A formatted string with the lowest price or null if there is no price available.
     */
    public static function get_lowest_price($assumaxId = null) {
        $id = self::get_the_assumax_id($assumaxId);
        if (empty($id)) return null;
        if (key_exists($id, self::$lowestPrice)) return self::$lowestPrice[$id];
        $today = Helpers::today();
        if (empty($today)) return self::$lowestPrice[$id] = null;
        global $wpdb;
        $query = sprintf('SELECT min(trip_price) as min_price FROM `api_trip_template_pivot`
                            WHERE template_assumax_id=%d 
                            AND trip_availability!=\'Unavailable\' 
                            AND trip_start_date>=\'%s\';',
            $id, $today->format('Y-m-d'));
        return self::$lowestPrice[$id] = $wpdb->get_var($query) / 100;
    }

    /**
     * For the target Template retrieves the insurances using the first trip of the Template.
     * Uses the global post id to obtain the AssumaxId should none be provided.
     *
     * @param int|null $assumaxId Optional Assumax Id of the Template.
     * @return array List of insurances or an empty list should none exist or when something went wrong.
     */
    public static function get_insurances($assumaxId = null) {
        $id = self::get_the_assumax_id($assumaxId);
        if (empty($id)) return [];
        if (key_exists($id, self::$insurances)) return self::$insurances[$id];
        $trips = self::get_trips($id);
        if (empty($trips)) return self::$insurances[$id] = [];
        $tripPostId = $trips[0]->ID;
        $insurances = get_field('trip_insurance_options', $tripPostId);
        return self::$insurances[$id] = empty($insurances) ? [] : $insurances;
    }

    /**
     * For the target Template retrieves the Accommodations.
     *
     * @param int $postId The post id of the template.
     * @return array List of accommodations or an empty list should none exist or when something went wrong.
     */
    public static function get_accommodations($postId) {
        if (empty($postId)) return [];
        if (key_exists($postId, self::$accommodations)) return self::$accommodations[$postId];
        $templateAccommodations = get_field('template_accommodations', $postId);
        if (empty($templateAccommodations)) return self::$accommodations[$postId] = [];
        $assumaxIds = array_column($templateAccommodations, 'assumax_id');
        if (empty($assumaxIds)) return self::$accommodations[$postId] = [];
        return self::$accommodations[$postId] = Accommodations::get_by_assumax_ids($assumaxIds);
    }

    /**
     * Obtains all the Templates that have trips that are available and that have a start date in the future.
     * The resulting set is grouped by the month they are in.
     */
    public static function get_grouped_by_month() {
        global $wpdb;
        $today = Helpers::today();
        if (empty($today)) return [];
        $query = sprintf("SELECT template_assumax_id, trip_start_date 
                            FROM `api_trip_template_pivot` 
                            WHERE trip_start_date>='%s' AND trip_availability!='Unavailable' 
                            ORDER BY trip_start_date;",
                        $today->format('Y-m-d'));
        $results = $wpdb->get_results($query);
        if (empty($results)) return [];
        $months = [];
        foreach ($results as $result) {
            $startDate = Helpers::create_local_datetime($result->trip_start_date);
            $monthKey = date_i18n('F Y', $startDate->getTimestamp());
            if (!key_exists($monthKey, $months)) $months[$monthKey] = [];
            if (!in_array($result->template_assumax_id, $months[$monthKey])) $months[$monthKey][] = $result->template_assumax_id;
        }
        return $months;
    }

    /**
     * Obtains a list of Template Assumax Ids for Templates that are available right now.
     *
     * @return int[] A list of Template Assumax Ids.
     */
    private static function get_available_templates() : array
    {
	    global $wpdb;
	    $today = Helpers::today();
	    if (empty($today)) return [];
	    if (empty(self::$availableTemplates)) {
		    $query = sprintf('SELECT template_assumax_id FROM api_trip_template_pivot
                                 WHERE trip_availability!=\'Unavailable\'
                                 AND trip_start_date>=\'%s\'
                                 ORDER BY trip_start_date ASC;',
                 $today->format('Y-m-d'));
		    self::$availableTemplates = array_values(array_unique($wpdb->get_col($query), SORT_NUMERIC));
	    }
	    return empty(self::$availableTemplates) ? [] : self::$availableTemplates;
    }

    /**
     * Obtains all the Templates which have terms with the provided ids and taxonomy as specified.
     * Only Templates with all the provided term ids will be returned.
     *
     * @param string $taxonomy The taxonomy slug.
     * @param array $termIds The ids of the terms.
     * @param int $limit The maximum amount of Templates obtained, -1 for all of them.
     * @return WP_Post[] A list of posts with type 'template' or an empty list should something go wrong.
     */
    public static function get_by_terms($taxonomy, $termIds, $limit = -1) {
        if (empty($taxonomy) || empty($termIds)) {
            return [];
        }
        self::$availableTemplates = self::get_available_templates();
        $taxQuery = [
            'relation' => 'AND',
            [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $termIds
            ]
        ];
        $args = [
            'numberposts' => $limit,
            'post_type' => 'template',
            'tax_query' => $taxQuery,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_assumax_id',
                    'value' => self::$availableTemplates,
                    'compare' => 'IN'
                ]
            ],
            'meta_key' => '_assumax_id',
            'orderby' => 'meta_value_list',
            'meta_value_list' => self::$availableTemplates,
            'suppress_filters' => false
        ];
        return get_posts($args);
    }

    /**
     * Checks if there are any alternative Templates for a given term.
     *
     * @param int $termId Term id of a destination, trip-type or age-group taxonomy term.
     * @param int $limit The maximum amount of Templates to obtain, -1 is all of them.
     * @return WP_Post[] List of available alternatives.
     */
    public static function get_alternatives(int $termId, int $limit = -1) : array
    {
        if (empty($termId)) {
            return [];
        }
        $alternativeTemplates = get_field('alternative_templates', "destination_{$termId}");
        if (empty($alternativeTemplates)) {
            return [];
        }
        $postIds = array_column($alternativeTemplates, 'template');
        self::$availableTemplates = self::get_available_templates();
        $args = [
            'numberposts' => $limit,
            'post_type' => 'template',
            'post__in' => $postIds,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_assumax_id',
                    'value' => self::$availableTemplates,
                    'compare' => 'IN'
                ]
            ],
            'meta_key' => '_assumax_id',
            'orderby' => 'meta_value_list',
            'meta_value_list' => self::$availableTemplates,
            'suppress_filters' => false
        ];
        return get_posts($args);
    }

    /**
     * Obtains all the Templates that have a trip that is available and has a start date between now and one month
     * in the future.
     *
     * @param int $limit The maximum amount of Templates obtained, -1 for all of them.
     * @return WP_Post[] A list of posts with type 'template' or an empty list should something go wrong.
     */
    public static function get_last_minutes($limit = -1) {
        global $wpdb;
        $today = Helpers::today();
        $nextMonth = Helpers::create_local_datetime('+1 month');
        if (empty($today) || empty($nextMonth)) return [];
        $query = sprintf("SELECT template_assumax_id 
                            FROM `api_trip_template_pivot` 
                            WHERE trip_start_date>='%s' 
                            AND trip_start_date<='%s'
                            AND trip_availability!='Unavailable' 
                            ORDER BY trip_start_date ASC;",
            $today->format('Y-m-d'), $nextMonth->format('Y-m-d'));
        $assumaxIds = array_values(array_unique($wpdb->get_col($query), SORT_NUMERIC));
        if (empty($assumaxIds)) return [];
        $args = [
            'numberposts' => $limit,
            'post_type' => 'template',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_assumax_id',
                    'value' => $assumaxIds,
                    'compare' => 'IN'
                ]
            ],
            'meta_key' => '_assumax_id',
            'orderby' => 'meta_value_list',
            'meta_value_list' => $assumaxIds,
            'suppress_filters' => false
        ];
        return get_posts($args);
    }

    /**
     * Obtains the primary destination term for the provided Template.
     *
     * @param int $templatePostId The post id for the Template.
     * @return WP_Term|false The primary destination term or false when it cannot be found.
     */
    public static function get_primary_destination($templatePostId) {
        if (empty($templatePostId)) return false;
        $primaryTermObject = new WPSEO_Primary_Term('destination', $templatePostId);
        $primaryDestinationTermId = $primaryTermObject->get_primary_term();
        if (!empty($primaryDestinationTermId)) {
            return get_term_by('id', $primaryDestinationTermId, 'destination');
        }
        return false;
    }
}
