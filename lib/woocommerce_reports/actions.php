<?php

namespace lib\woocommerce_reports;

use lib\woocommerce_reports\models\BookingReport;
use lib\woocommerce_reports\models\CustomerData;
use lib\woocommerce_reports\models\InvoiceData;
use lib\woocommerce_reports\models\PaymentData;
use lib\woocommerce_reports\models\TripReport;
use lib\woocommerce_reports\models\UpdateLog;
use Roots\Sage\Assets;

/**
 * Holds Actions used by the Woocommerce Reports functionality.
 *
 * Class Actions
 * @package lib\woocommerce_reports\actions
 * @author Chris van Zanten <chris@vazquez.nl>
 */
class Actions {
    /**
     * Migrates each model if needed when the woocommerce_report_migrate_enabled options has been enabled.
     */
    public static function migrate() {
        if (get_field('woocommerce_report_migrate_enabled', 'option') === true) {
            global $wpdb;
            $wpdb->hide_errors();
            if (BookingReport::needs_migration()) {
                error_log("Migrating the BookingReport database structure.");
                BookingReport::migrate();
            }
            if (TripReport::needs_migration()) {
                error_log("Migrating the TripReport database structure.");
                TripReport::migrate();
            }
            if (CustomerData::needs_migration()) {
                error_log("Migrating the CustomerData database structure.");
                CustomerData::migrate();
            }
            if (PaymentData::needs_migration()) {
                error_log("Migrating the PaymentData database structure.");
                PaymentData::migrate();
            }
            if (InvoiceData::needs_migration()) {
                error_log("Migrating the InvoiceData database structure.");
                InvoiceData::migrate();
            }
            if (UpdateLog::needs_migration()) {
                error_log("Migrating the InvoiceData database structure.");
                UpdateLog::migrate();
            }
            $wpdb->show_errors();
        }
    }

    /**
     * Adds an options page to the theme-settings menu.
     */
    public static function add_options_page() {
        if ( function_exists( 'acf_add_options_page' ) ) {
            acf_add_options_sub_page( array (
                'page_title'  => 'Woocommerce Reports instellingen',
                'menu_title'  => 'Woocommerce Reports instellingen',
                'parent_slug' => 'theme-settings',
            ) );
        }
    }

    /**
     * Enqueues the reports.js script when the page is equal to wc-reports.
     */
    public static function enqueue_reports_script() {
        if (isset($_GET['page']) && $_GET['page'] === 'wc-reports') {
            wp_enqueue_script('woocommerce_reports', Assets\asset_path('scripts/reports.js'), ['jquery'], null, true);
            wp_enqueue_style('woocommerce_reports_style', Assets\asset_path('styles/reports.css'));
        }
    }
}

/**
 * Hooks
 */
add_action('admin_init', ['\lib\woocommerce_reports\Actions', 'migrate']);
add_action('init', ['\lib\woocommerce_reports\Actions', 'add_options_page']);
add_action('admin_enqueue_scripts', ['\lib\woocommerce_reports\Actions', 'enqueue_reports_script']);
add_action('vazquez_upsert_customer_report_data', [CustomerData::class, 'upsert_customer_report_data'], 10, 2);
add_action('vazquez_upsert_booking_report_data', [BookingReport::class, 'upsert_booking_report'], 10, 2);
add_action('vazquez_upsert_trip_report_data', [TripReport::class, 'upsert_trip_report'], 10, 2);
add_action('vazquez_add_update_log_entry', [UpdateLog::class, 'add_entry'], 10, 3);
