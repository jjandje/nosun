<?php

namespace lib\controllers;

use Roots\Sage\Helpers;
use Vazquez\NosunAssumaxConnector\Api\Guides;

/**
 * Controller for the Trips.
 * Has several functions that deal with obtaining several Trip related elements
 *
 * Class Trip
 * @package lib\controllers
 */
class Trip {
    // Caches
    private static $acfFields = [];
    private static $guides = [];

    /**
     * Obtains the AssumaxId of the current Trip.
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
     * Obtains the acf fields for the provided post id and returns them.
     * Uses a cache to prevent multiple lookups for the same request.
     *
     * @param int $postId The post id for which to obtain the acf fields.
     * @return array|bool|mixed The results from the get_fields function.
     */
    private static function get_the_acf_fields($postId) {
        if (key_exists($postId, self::$acfFields)) return self::$acfFields[$postId];
        return self::$acfFields[$postId] = get_fields($postId);
    }

    /**
     * Whether or not a Trip should be shown on the Templates page.
     * A Trip will be shown when its start date lies in the future and its status isn't 'Cancelled' or 'Deleted'.
     *
     * @param int|null $postId Optional post id, will use the global post when null.
     * @return boolean True when the Trip should be shown, false if otherwise.
     */
    public static function is_shown($postId = null) {
        $id = !empty($postId) ? $postId : get_the_ID();
        if (empty($id)) return false;
        $fields = self::get_the_acf_fields($id);
        if (empty($fields['trip_status']) || $fields['trip_status'] === 'Cancelled' || $fields['trip_status'] === 'Deleted') return false;
        if (empty($fields['trip_start_date'])) return false;
        $today = Helpers::today();
        $startDate = Helpers::create_local_datetime($fields['trip_start_date']);
        return $today < $startDate;
    }

    /**
     * Checks which guides are available for the Trip and obtains the nickname from the actual Guide posts should
     * they exist. Defaults to the provided name should no Guide post exist.
     *
     * @param int|null $postId Optional post id, will use the global post when null.
     * @return array A map containing the post id of the Guide, the nickname of the Guide.
     */
    public static function get_guides($postId = null) {
        $id = !empty($postId) ? $postId : get_the_ID();
        if (empty($id)) return [];
        $fields = self::get_the_acf_fields($id);
        if (empty($fields['trip_guides'])) return [];
        $guides = [];
        foreach ($fields['trip_guides'] as $tripGuide) {
            $guide = [
                'name' => !empty($tripGuide['name']) ? $tripGuide['name'] : '',
                'index' => isset($tripGuide['index']) ? $tripGuide['index'] : 999,
                'url' => '',
                'post' => null
            ];
            if (!empty($tripGuide['assumax_id'])) {
                if (key_exists($tripGuide['assumax_id'], self::$guides)) {
                    $guide = self::$guides[$tripGuide['assumax_id']];
                } else {
                    $guidePost = Guides::get_by_assumax_id($tripGuide['assumax_id']);
                    if (!empty($guidePost)) {
                        $guide['post'] = $guidePost;
                        $guide['name'] = get_field('tourguide_nickname', $guidePost->ID);
                        $guide['url'] = get_the_permalink($guidePost);
                    }
                    self::$guides[$tripGuide['assumax_id']] = $guide;
                }
            }
            $guides[] = $guide;
        }
        usort($guides, function ($a, $b) {
            if ($a['index'] === $b['index']) return 0;
            return $a['index'] < $b['index'] ? -1 : 1;
        });
        return $guides;
    }

    /**
     * Obtains the availability statistics for the Trip.
     *
     * @param int|null $postId Optional post id, will use the global post when null.
     * @return array A map containing status and text which will default to 'red' and 'Vol'.
     */
    public static function get_availability($postId = null) {
        $id = !empty($postId) ? $postId : get_the_ID();
        if (empty($id)) return ['status' => 'red', 'text' => 'Vol'];
        $fields = self::get_the_acf_fields($id);
        switch ($fields['trip_availability']) {
            case 'PartiallyAvailable':
                $availability = 'Nog enkele plaatsen beschikbaar';
                $availabilityStatus = 'orange';
                break;
            case 'Available':
                $availability = 'Nog volop te boeken';
                $availabilityStatus = 'green';
                break;
            default:
                $availability = 'Vol';
                $availabilityStatus = 'red';
                break;
        }
        return ['status' => $availabilityStatus, 'text' => $availability];
    }

    /**
     * Obtain the age groups for the Trip. Contains the regular customers and the commercial customers.
     *
     * @param int|null $postId Optional post id, will use the global post when null.
     * @return array Map containing age groups separated by men and women in the following format:
     * [
     *      'men' => ['20-29' => 1, '30-39' => 4, ... '50-59' => 2],
     *      'women' => ...
     * ]
     */
    public static function get_age_groups($postId = null) {
        $id = !empty($postId) ? $postId : get_the_ID();
        if (empty($id)) return [];
        $fields = self::get_the_acf_fields($id);
        $actualCustomers = isset($fields['trip_customers']) ? $fields['trip_customers'] : [];
        $commercialCustomers = isset($fields['trip_commercial_entries']) ? $fields['trip_commercial_entries'] : [];
        $customers = [];
        if (!empty($actualCustomers)) {
            foreach ($actualCustomers as $customer) {
                $customers[] = [
                    'sex' => intval($customer['sex']),
                    'date_of_birth' => $customer['date_of_birth']
                ];
            }
        }
        if (!empty($commercialCustomers)) {
            foreach ($commercialCustomers as $customer) {
                $customers[] = [
                    'sex' => intval($customer['sex']),
                    'date_of_birth' => $customer['date_of_birth']
                ];
            }
        }
        $today = Helpers::today();
        if (empty($today) || empty($customers)) return [];
        $ageGroups = [
            'men' => [],
            'men_count' => 0,
            'women' => [],
            'women_count' => 0
        ];
        foreach ($customers as $customer) {
            $dateOfBirth = Helpers::create_local_datetime($customer['date_of_birth']);
            if (empty($dateOfBirth)) continue;
            $interval = $dateOfBirth->diff($today);
            $age = $interval->y;
            if ((20 <= $age) && ($age <= 29)) {
                if ($customer['sex'] === 0) {
                    if (key_exists('20-29', $ageGroups['men'])) $ageGroups['men']['20-29']++;
                    else $ageGroups['men']['20-29'] = 1;
                    $ageGroups['men_count']++;
                } else {
                    if (key_exists('20-29', $ageGroups['women'])) $ageGroups['women']['20-29']++;
                    else $ageGroups['women']['20-29'] = 1;
                    $ageGroups['women_count']++;
                }
            } elseif ((30 <= $age) && ($age <= 39)) {
                if ($customer['sex'] === 0) {
                    if (key_exists('30-39', $ageGroups['men'])) $ageGroups['men']['30-39']++;
                    else $ageGroups['men']['30-39'] = 1;
                    $ageGroups['men_count']++;
                } else {
                    if (key_exists('30-39', $ageGroups['women'])) $ageGroups['women']['30-39']++;
                    else $ageGroups['women']['30-39'] = 1;
                    $ageGroups['women_count']++;
                }
            } elseif ((40 <= $age) && ($age <= 49)) {
                if ($customer['sex'] === 0) {
                    if (key_exists('40-49', $ageGroups['men'])) $ageGroups['men']['40-49']++;
                    else $ageGroups['men']['40-49'] = 1;
                    $ageGroups['men_count']++;
                } else {
                    if (key_exists('40-49', $ageGroups['women'])) $ageGroups['women']['40-49']++;
                    else $ageGroups['women']['40-49'] = 1;
                    $ageGroups['women_count']++;
                }
            } else {
                if ($customer['sex'] === 0) {
                    if (key_exists('50-59', $ageGroups['men'])) $ageGroups['men']['50-59']++;
                    else $ageGroups['men']['50-59'] = 1;
                    $ageGroups['men_count']++;
                } else {
                    if (key_exists('50-59', $ageGroups['women'])) $ageGroups['women']['50-59']++;
                    else $ageGroups['women']['50-59'] = 1;
                    $ageGroups['women_count']++;
                }
            }
        }
        return $ageGroups;
    }
}