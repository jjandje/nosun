<?php

namespace lib\controllers;

/**
 * Employee controller that holds functions that deal with the employees.
 *
 * Class Employee
 * @package lib\controllers
 */
class Employee {
    /**
     * Registers the 'employee' post type.
     */
    public static function register_post_type() {
        $labels = array(
            'name' => 'Medewerkers',
            'singular_name' => 'Medewerker',
            'add_new' => 'Toevoegen',
            'add_new_item' => 'Medewerker toevoegen',
            'edit_item' => 'Bewerk medewerker',
            'new_item' => 'Nieuw',
            'view_item' => 'Bekijk medewerker',
            'search_items' => 'Zoek medewerker',
            'not_found' => 'Geen medewerker gevonden',
            'not_found_in_trash' => 'Geen medewerker gevonden in prullenbak'
        );
        $args = array(
            'label' => 'Medewerker',
            'description' => 'Medewerker',
            'labels' => $labels,
            'supports' => array('title', 'editor', 'thumbnail'),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-businessman',
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'rewrite' => array('slug' => 'over-nosun/het-team', 'with_front' => false)
        );
        register_post_type('employee', $args);
    }
}

// Hooks
add_action('init', [Employee::class, 'register_post_type']);