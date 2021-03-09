<?php

namespace lib\controllers;

use Roots\Sage\Assets;
use WP_Post;

/**
 * Handles all the functionality needed to display the admin menu's and fields used for the e-mail module.
 *
 * Class EmailAdmin
 * @package lib\controllers
 */
class EmailAdmin
{
    /**
     * AJAX event that updates/inserts an e-mail trigger event using the values set in the global $_POST variable.
     */
    public static function ajax_upsert_event() : void
    {
        check_ajax_referer('upsert_event_nonce', 'security');
        if (!isset($_POST['trigger']) ||
            !isset($_POST['event_id']) ||
            !isset($_POST['template_id']) ||
            !isset($_POST['recipients']) ||
            !isset($_POST['bcc']) ||
            !isset($_POST['singleton'])) {
            wp_die('Bad request', 400);
        }
        $trigger = sanitize_text_field($_POST['trigger']);
        $availableTriggers = Email::obtain_available_triggers();
        if (empty($availableTriggers) || !key_exists($trigger, $availableTriggers)) {
            wp_die('Bad request', 400);
        }
        $eventId = intval(sanitize_text_field($_POST['event_id']));
        $templateId = intval(sanitize_text_field($_POST['template_id']));
        $recipients = sanitize_text_field($_POST['recipients']);
        $bcc = sanitize_text_field($_POST['bcc']);
        $singleton = sanitize_text_field($_POST['singleton']) === 'true';
        global $wpdb;
        if (empty($eventId)) {
            if ($wpdb->insert('api_email_events', [
                'trigger' => $trigger,
                'email_template' => $templateId,
                'singleton' => $singleton ? 1 : 0,
                'recipients' => $recipients,
                'bcc' => $bcc,
            ]) === false) {
                wp_die('Could not insert a new event row into the database.', 500);
            } else {
                wp_die($wpdb->insert_id, 200);
            }
        } else {
            if ($wpdb->update('api_email_events', [
                'trigger' => $trigger,
                'email_template' => $templateId,
                'singleton' => $singleton ? 1 : 0,
                'recipients' => $recipients,
                'bcc' => $bcc,
            ], ['id' => $eventId]) === false) {
                wp_die('Could not update the event row in the database.', 500);
            } else {
                wp_die($eventId, 200);
            }
        }
    }

    /**
     * AJAX event to delete an email trigger event using the event_id supplied in the $_POST global variable.
     */
    public static function ajax_delete_event() : void
    {
        check_ajax_referer('delete_event_nonce', 'security');
        if (!isset($_POST['event_id'])) {
            wp_die('Bad request', 400);
        }
        $eventId = intval(sanitize_text_field($_POST['event_id']));
        global $wpdb;
        if ($wpdb->delete('api_email_events', ['id' => $eventId]) === false) {
            wp_die('Could not delete the event row in the database.', 500);
        }
        wp_die($eventId, 200);
    }

    /**
     * Adds the Email Module menu item to the menu.
     */
    public static function add_email_menu_page() : void
    {
        $pageTitle = _x('E-mail module', 'email_module_menu', 'vazquez');
        $menuTitle = _x('E-mail module', 'email_module_menu', 'vazquez');
        $capability = 'edit_posts';
        $menuSlug = 'email_module';
        $callback = [EmailAdmin::class, 'render_email_module_page'];
        $iconUrl = 'dashicons-email-alt';
        $position = 15;
        add_menu_page($pageTitle, $menuTitle, $capability, $menuSlug, $callback, $iconUrl, $position);
    }

    /**
     * Renders the e-mail module menu page.
     */
    public static function render_email_module_page() : void
    {
        set_query_var('triggers', Email::obtain_available_triggers());
        set_query_var('events', Email::obtain_events());
        set_query_var('email_templates', Email::obtain_email_templates());
        get_template_part('templates/template-email-module');
    }

    /**
     * Adds the shortcodes text box to the email templates admin page.
     */
    public static function add_shortcodes_box() : void
    {
        add_meta_box(
            'vazquez_email_module_shortcodes',
            __('Shortcodes', 'email_triggers', 'vazquez'),
            [EmailAdmin::class, 'display_shortcodes_meta_box'],
            'email'
        );
    }

    /**
     * Displays a meta box on the e-mail template post edit admin page that will show a select input containing all
     * the possible triggers and a div containing the shortcodes for the chosen (uses javascript) option.
     *
     * @param WP_Post $post The current e-mail template being edited.
     */
    public static function display_shortcodes_meta_box(WP_Post $post) : void
    {
        set_query_var('triggers', Email::obtain_available_triggers());
        get_template_part('templates/modules/shortcodes_metabox');
    }

    /**
     * Enqueues the email_module.js script and the email_module.css stylesheet.
     *
     * @param string $hookSuffix The suffix of the current page.
     */
    public static function enqueue_reports_script(string $hookSuffix) : void
    {
        $screen = get_current_screen();
        if (empty($screen)) {
            return;
        }
        if ($hookSuffix === 'toplevel_page_email_module' ||
            (($hookSuffix === 'post.php' || $hookSuffix === 'post-new.php') && $screen->post_type === 'email')) {
            wp_enqueue_script('email_module_script', Assets\asset_path('scripts/email-module.js'), ['jquery'], null, true);
            wp_localize_script('email_module_script', 'ajax_object', ['ajaxurl' => admin_url('admin-ajax.php')]);
            wp_enqueue_style('email_module_style', Assets\asset_path('styles/email-module.css'));
        }
    }

}

// Hooks and filters
add_action('admin_menu', [EmailAdmin::class, 'add_email_menu_page']);
add_action('add_meta_boxes', [EmailAdmin::class, 'add_shortcodes_box']);
add_action('admin_enqueue_scripts', [EmailAdmin::class, 'enqueue_reports_script']);
add_action('wp_ajax_email_upsert_event', [EmailAdmin::class, 'ajax_upsert_event']);
add_action('wp_ajax_email_delete_event', [EmailAdmin::class, 'ajax_delete_event']);
