<?php

namespace Roots\Sage;

/**
 * Holds all the theme functions that are unrelated to a specific function and that are fired by hooks.
 *
 * Class Hooks
 * @package Roots\Sage
 */
class Hooks {

    /**
     * TODO: Document this function.
     *
     * @param $classes
     * @param $item
     * @return mixed
     */
    public static function wp_nav_parent_class($classes, $item) {
        if (get_post_type() === 'product' && $item->title === "Bestemmingen") {
            array_push($classes, 'current-menu-item');
        }
        if (!empty($post)) {
            // Getting the post type of the current post
            $current_post_type = get_post_type_object(get_post_type($post->ID));
            $current_post_type_slug = $current_post_type->rewrite['slug'];
            // Getting the URL of the menu item
            $menu_slug = strtolower(trim($item->url));
            // If the menu item URL contains the current post types slug add the current-menu-item class
            if (strpos($menu_slug, $current_post_type_slug) !== false) {
                $classes[] = 'current-menu-item';
            }
        }
        return $classes;
    }

    /**
     * Changes the 'from' email address to something different.
     *
     * @param string $original_email_address The original email address.
     * @return string The new email address.
     */
    public static function wpb_sender_email($original_email_address) {
        return 'vragen@nosun.nl';
    }

    /**
     * Changes the 'from' name to something different.
     *
     * @param string $original_email_from The original name.
     * @return string The new name.
     */
    public static function wpb_sender_name($original_email_from) {
        return 'NoSun';
    }

    /**
     * TODO: Document this function
     *
     * @param $classes
     * @return array
     */
    public static function body_class($classes) {
        if (is_single() || is_page() && !is_front_page()) {
            if (!in_array(basename(get_permalink()), $classes)) {
                $classes[] = basename(get_permalink());
            }
        }
        if (get_field('page_settings')) {
            $classes [] = get_field('page_settings');
        } else {
            $classes [] = 'full-width';
        }
        return $classes;
    }

    /**
     * Cleanup the excerpt.
     *
     * @return string The excerpt cleaned.
     */
    public static function excerpt_more() {
        return ' &hellip; <a href="' . get_permalink() . '">' . __('Continued', 'sage') . '</a>';
    }

    /**
     * Sets the load point for the acf groups.
     *
     * @param array $paths The current set of paths.
     * @return array The paths modified to contain the new load point.
     */
    public static function acf_json_load_point($paths) {
        unset($paths[0]);
        $paths[] = get_stylesheet_directory() . '/acf';
        return $paths;
    }

    /**
     * Sets the save point for the acf groups.
     *
     * @param string $path The current save point.
     * @return string The new save point.
     */
    public static function acf_json_save_point($path) {
        return get_stylesheet_directory() . '/acf';
    }

    /**
     * Setup option pages
     */
    public static function add_website_settings_options() {
        if (function_exists('acf_add_options_page')) {

            acf_add_options_page(array(
                'page_title' => 'noSun instellingen',
                'menu_title' => 'noSun instellingen',
                'menu_slug' => 'theme-settings',
                'capability' => 'edit_posts',
                'redirect' => true
            ));

            acf_add_options_sub_page(array(
                'page_title' => 'Archiefpagina instellingen',
                'menu_title' => 'Archiefpagina instellingen',
                'parent_slug' => 'theme-settings',
            ));

            acf_add_options_sub_page(array(
                'page_title' => 'Bestanden',
                'menu_title' => 'Bestanden',
                'parent_slug' => 'theme-settings',
            ));

            acf_add_options_sub_page(array(
                'page_title' => 'USP\'s',
                'menu_title' => 'USP\'s',
                'parent_slug' => 'theme-settings',
            ));

            acf_add_options_sub_page(array(
                'page_title' => 'Populaire reizen',
                'menu_title' => 'Populaire reizen',
                'parent_slug' => 'theme-settings',
            ));

            acf_add_options_sub_page(array(
                'page_title' => 'Bekend van',
                'menu_title' => 'Bekend van',
                'parent_slug' => 'theme-settings',
            ));

            acf_add_options_sub_page(array(
                'page_title' => 'Social Media',
                'menu_title' => 'Social Media',
                'parent_slug' => 'theme-settings',
            ));

            acf_add_options_sub_page(array(
                'page_title' => 'Adres Gegevens',
                'menu_title' => 'Adres Gegevens',
                'parent_slug' => 'theme-settings',
            ));

            acf_add_options_sub_page(array(
                'page_title' => 'Ratings',
                'menu_title' => 'Ratings',
                'parent_slug' => 'theme-settings',
            ));

            acf_add_options_sub_page(array(
                'page_title' => "Boeking Notities",
                'menu_title' => "Boeking Notities",
                'parent_slug' => 'theme-settings',
            ));

            acf_add_options_sub_page(array(
                'page_title' => "API Instellingen",
                'menu_title' => "API Instellingen",
                'parent_slug' => 'theme-settings',
            ));

            acf_add_options_sub_page(array(
                'page_title' => "Verzekeringen",
                'menu_title' => "Verzekeringen",
                'parent_slug' => 'theme-settings',
            ));

            acf_add_options_sub_page(array(
                'page_title' => "Footer",
                'menu_title' => "Footer",
                'parent_slug' => 'theme-settings',
            ));
        }
    }

    /**
     * Changes the order of the post loop on certain archives.
     *
     * @param mixed $query The current query object.
     */
    public static function change_archive_order($query)
    {
        if ($query->get('post_type') === 'destination') {
            $query->set('order', 'ASC');
            $query->set('orderby', 'title');
        } elseif ($query->get('post_type') === 'blog') {
            $query->set('order', 'DESC');
            $query->set('orderby', 'publish_date');
        }
    }

    /**
     * Adds a new orderby function that can be used to sort the output by a predefined list setup in the
     * 'meta_value_list' argument.
     *
     * Props to Sally CJ @ https://stackoverflow.com/questions/56184677/wordpress-orderby-meta-values-in-array
     *
     * @param string $orderby The current sort order query string.
     * @param mixed $query The query object.
     * @return string Either a new filter string for the sorted list or the current query string.
     */
    public static function posts_orderby_meta_value_list($orderby, $query) {
        $key = 'meta_value_list';
        if ($key === $query->get('orderby') &&
            ($list = $query->get($key))) {
            global $wpdb;
            $list = "'" . implode(wp_parse_list($list), "', '") . "'";
            return "FIELD( $wpdb->postmeta.meta_value, $list )";
        }
        return $orderby;
    }
}

// Filters
add_filter('nav_menu_css_class', [Hooks::class, 'wp_nav_parent_class'], 10, 2);
add_filter('wp_mail_from', [Hooks::class, 'wpb_sender_email']);
add_filter('wp_mail_from_name', [Hooks::class, 'wpb_sender_name']);
add_filter('body_class', [Hooks::class, 'body_class']);
add_filter('excerpt_more', [Hooks::class, 'excerpt_more']);
add_filter('acf/settings/save_json', [Hooks::class, 'acf_json_save_point']);
add_filter('acf/settings/load_json', [Hooks::class, 'acf_json_load_point']);
add_filter('woocommerce_show_page_title', '__return_false');
add_filter('posts_orderby', [Hooks::class, 'posts_orderby_meta_value_list'], 10, 2);

// Actions
add_action('acf/init', [Hooks::class, 'add_website_settings_options']);
add_action('pre_get_posts', [Hooks::class, 'change_archive_order']);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
