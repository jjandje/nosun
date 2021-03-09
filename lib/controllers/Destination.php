<?php

namespace lib\controllers;

/**
 * Destination controllers that holds functions that deal with the destinations.
 *
 * Class Destination
 * @package lib\controllers
 */
class Destination {
    /**
     * Obtains all the available destinations.
     *
     * @return array List of destination WP_Post objects.
     */
    public static function all() {
        $args = [
            'posts_per_page' => -1,
            'post_type' => 'destination',
            'post_status' => 'publish',
        ];
        return get_posts($args);
    }

    /**
     * Registers the 'destination' post type.
     */
    public static function register_post_type() {
        $labels = array(
            'name' => 'Bestemmingen',
            'singular_name' => 'Bestemming',
            'add_new' => 'Toevoegen',
            'add_new_item' => 'Bestemming toevoegen',
            'edit_item' => 'Bewerk bestemming',
            'new_item' => 'Nieuw',
            'view_item' => 'Bekijk reisbegeleider',
            'search_items' => 'Zoek bestemming',
            'not_found' => 'Geen bestemming gevonden',
            'not_found_in_trash' => 'Geen bestemming gevonden in prullenbak'
        );
        $args = array(
            'label' => 'Bestemming',
            'description' => 'Bestemming',
            'labels' => $labels,
            'supports' => array('title', 'thumbnail'),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-admin-site',
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'rewrite' => array('slug' => 'bestemmingen', 'with_front' => false)
        );
        register_post_type('destination', $args);
    }
}

// Hooks
add_action('init', [Destination::class, 'register_post_type']);