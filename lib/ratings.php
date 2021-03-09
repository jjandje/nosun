<?php

namespace Roots\Sage;

/**
 * Holds all the functionality used by the template ratings system.
 *
 * Class Ratings
 * @package Roots\Sage
 */
class Ratings {
    /**
     * Aggregates all the trip ratings and computes the total number of ratings and their mean value.
     * Should be called by a cron scheduler on a once per day basis.
     */
    public static function aggregate_ratings() {
        global $wpdb;
        /** @noinspection SqlNoDataSourceInspection */
        $ratings = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE 'template_rating_%_score'");
        if (!isset($ratings)) return;
        $summedRating = 0;
        foreach ($ratings as $rating) {
            $summedRating += intval($rating);
        }
        $ratingsTotal = count($ratings);
        $ratingsMean = empty($ratingsTotal) ? 5 : $summedRating / $ratingsTotal;
        // Push these values to the database.
        update_field('ratings_total', $ratingsTotal, 'options');
        update_field('ratings_mean', $ratingsMean, 'options');
    }

    /**
     * Schedules the daily rating aggregation event.
     */
    public static function schedule_cron_events() : void
    {
        if (!wp_next_scheduled('vazquez_aggregate_ratings_event')) {
            $midnight = Helpers::create_local_datetime('tomorrow midnight +4 hours');
            if (!empty($midnight)) {
                wp_schedule_event($midnight->getTimestamp(), 'daily', 'vazquez_aggregate_ratings_event');
            }
        }
    }
}

add_action('init', [Ratings::class, 'schedule_cron_events']);
add_action('vazquez_aggregate_ratings_event', [Ratings::class, 'aggregate_ratings']);
