<?php

namespace Vazquez\NosunAssumaxConnector\Api;

/**
 * Class that holds functions that aid in running cronjobs.
 *
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Cronjobs implements ILoadable {
    /**
     * Schedules several cronjobs that handle automated API tasks.
     */
    public static function schedule_cronjobs() {
        if (!wp_next_scheduled('api_release_old_locks_event')) {
            wp_schedule_event(time(), '1min', 'api_release_old_locks_event');
        }
        if (!wp_next_scheduled('advance_webhook_locks_event')) {
            wp_schedule_event(time(), '1min', 'advance_webhook_locks_event');
        }
        if (!wp_next_scheduled('api_prune_attachments_event')) {
            wp_schedule_event(strtotime('tomorrow midnight +4 hours'), 'daily', 'api_prune_attachments_event');
        }
        if (!wp_next_scheduled('update_extra_products_event')) {
            wp_schedule_event(strtotime('tomorrow midnight'), 'daily', 'update_extra_products_event');
        }
    }

    /**
     * Adds several new schedules to the default array which can be used by the cronjobs.
     *
     * @param mixed $schedules The previous schedules array.
     * @return mixed The schedules array modified to hold the new schedules.
     */
    public static function add_schedules($schedules) {
        $schedules['5min'] = array(
            'interval' => 300,
            'display' => __('Every 5 minutes')
        );

        $schedules['1min'] = array(
            'interval' => 60,
            'display' => __('Every 1 minute')
        );
        return $schedules;
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_filter('cron_schedules', [self::class, 'add_schedules'], 2);
        $loader->add_action('init', [self::class, 'schedule_cronjobs']);
    }
}