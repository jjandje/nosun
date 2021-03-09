<?php

namespace lib\woocommerce_reports;

/**
 * Holds filters used by the Woocommerce Reports functionality.
 *
 * Class Filters
 * @package lib\woocommerce_reports\filters
 * @author Chris van Zanten <chris@vazquez.nl>
 */
class Filters {
    /**
     * Clears all the default reports and builds up the new report structure.
     *
     * @param $reports - The original reports.
     * @return array - The new reports.
     */
    public static function reports_filter($reports) {
        $reports = [];

        // Boekingen
        $reports['bookings'] = [
            'title' => "Boekingen",
            'reports' => [
                'range_comparison' => [
                    'title'         => "Periode Vergelijk",
                    'description'   => '',
                    'hide_title'    => true,
                    'callback'      => ['\lib\woocommerce_reports\controllers\bookings\RangeComparisonController', 'render']
                ],
                'season_comparison' => [
                    'title'         => "Seizoenen Vergelijk",
                    'description'   => '',
                    'hide_title'    => true,
                    'callback'      => ['\lib\woocommerce_reports\controllers\bookings\SeasonComparisonController', 'render']
                ],
                'average_age' => [
                    'title'         => "Gemiddelde Leeftijd",
                    'description'   => '',
                    'hide_title'    => true,
                    'callback'      => ['\lib\woocommerce_reports\controllers\bookings\AverageAgeController', 'render']
                ],
                'occupancy_rate' => [
                    'title'         => "Bezettingsgraad",
                    'description'   => '',
                    'hide_title'    => true,
                    'callback'      => ['\lib\woocommerce_reports\controllers\bookings\OccupancyRateController', 'render']
                ],
                'average_gender' => [
                    'title'         => "Gemiddeld Geslacht",
                    'description'   => '',
                    'hide_title'    => true,
                    'callback'      => ['\lib\woocommerce_reports\controllers\bookings\AverageGenderController', 'render']
                ]
            ]
        ];

        // Omzet Rapportage
        $reports['revenue'] = [
            'title' => "Omzet",
            'reports' => [
                'report' => [
                    'title'         => "Omzet Rapportage",
                    'description'   => '',
                    'hide_title'    => true,
                    'callback'      => ['\lib\woocommerce_reports\controllers\revenue\ReportController', 'render']
                ]
            ]
        ];

        // Status Betalingen
        $reports['payments'] = [
            'title' => "Betalingen",
            'reports' => [
                'completed' => [
                    'title'         => "Voltooide Betalingen",
                    'description'   => '',
                    'hide_title'    => true,
                    'callback'      => ['\lib\woocommerce_reports\controllers\payments\CompletedController', 'render']
                ],
                'outstanding' => [
                    'title'         => "Openstaande Betalingen",
                    'description'   => '',
                    'hide_title'    => true,
                    'callback'      => ['\lib\woocommerce_reports\controllers\payments\OutstandingController', 'render']
                ]
            ]
        ];

        // Planning Reisbegeleiders
        $reports['tourguides'] = [
            'title' => "Reisbegeleiders",
            'reports' => [
                'schedule' => [
                    'title'         => "Planning",
                    'description'   => '',
                    'hide_title'    => true,
                    'callback'      => ['\lib\woocommerce_reports\controllers\tourguides\ScheduleController', 'render']
                ]
            ]
        ];

        // Update Log
        $reports['update_log'] = [
            'title' => "Update logboek",
            'reports' => [
                'schedule' => [
                    'title'         => "Update logboek",
                    'description'   => '',
                    'hide_title'    => true,
                    'callback'      => ['\lib\woocommerce_reports\controllers\updatelogs\UpdateLogController', 'render']
                ]
            ]
        ];

        return $reports;
    }

    /**
     * Adds several query vars that can be used by the IReportControllers.
     *
     * @param $vars
     * @return array
     */
    public static function add_query_vars_filter($vars) {
        $vars[] = "trip_id";
        $vars[] = "booking_range_start";
        $vars[] = "booking_range_end";
        $vars[] = "trip_range_start";
        $vars[] = "trip_range_end";
        return $vars;
    }
}

/**
 * Hook into the woocommerce filter that manages the admin reports.
 */
add_filter('woocommerce_admin_reports', ['\lib\woocommerce_reports\Filters', 'reports_filter']);

/**
 * Hook into the query_vars filter that manages the query variables.
 */
add_filter( 'query_vars', ['\lib\woocommerce_reports\Filters', 'add_query_vars_filter']);