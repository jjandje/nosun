<?php

namespace lib\controllers;

/**
 * Blog controller that holds functions that deal with the blog.
 *
 * Class Blog
 * @package lib\controllers
 */
class Blog {
    /**
     * Register the 'Blog' post type.
     */
    public static function register_post_type() {
        $labels = array (
            'name'               => 'Blog',
            'singular_name'      => 'Blog',
            'menu_name'          => 'Blog',
            'parent_item_colon'  => 'Hoofditem',
            'all_items'          => 'Alle blogitems',
            'view_item'          => 'Bekijk bericht',
            'add_new_item'       => 'Bericht toevoegen',
            'add_new'            => 'Toevoegen',
            'edit_item'          => 'Bewerk bericht',
            'update_item'        => 'Update bericht',
            'search_items'       => 'Zoeken',
            'not_found'          => 'Niet gevonden',
            'not_found_in_trash' => 'Niet gevonden in de prullenbak',
        );
        $args   = array (
            'label'               => 'blog',
            'description'         => 'Blog',
            'labels'              => $labels,
            'supports'            => array ( 'title', 'thumbnail', 'author' ),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 5,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'rewrite'             => array ( 'slug' => 'blog' ),
            'capability_type'     => 'post'
        );
        register_post_type( 'blog', $args );
    }

    /**
     * Register the 'Categorie' taxonomy.
     */
    public static function register_taxonomy() {
        $labels = array (
            'name'                       => 'Categorie',
            'singular_name'              => 'Categorie',
            'search_items'               => 'Categorie zoeken',
            'popular_items'              => 'Populaire categorie',
            'all_items'                  => 'Alle categorieën',
            'edit_item'                  => 'Bewerk categorie',
            'parent_item'                => 'Hoofdcategorie',
            'parent_item_colon'          => 'Hoofdcategorie:',
            'update_item'                => 'Update categorie',
            'add_new_item'               => 'Categorie toevoegen',
            'new_item_name'              => 'Nieuwe categorie',
            'separate_items_with_commas' => 'Onderscheid de categorie doormiddel van een komma',
            'add_or_remove_items'        => 'Voeg toe of verwijder categorie',
            'choose_from_most_used'      => 'Kies uit de meest gebruikte categorieën',
            'not_found'                  => 'Geen categorieën gevonden',
            'menu_name'                  => 'Categorieën'
        );
        $args = array (
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_menu'      => true,
            'show_in_nav_menus' => true,
            'query_var'         => true,
            'public'            => true,
            'sort'              => true,
            'rewrite'           => array ( 'slug' => 'blog-categorie' ),

        );
        register_taxonomy( 'blog_categorie', 'blog', $args );
    }

    /**
     * TODO: Document this function.
     */
    public static function nosun_numeric_posts_nav() {

        if (is_singular())
            return;

        global $wp_query;

        /** Stop execution if there's only 1 page */
        if ($wp_query->max_num_pages <= 1)
            return;

        $paged = get_query_var('paged') ? absint(get_query_var('paged')) : 1;
        $max = intval($wp_query->max_num_pages);

        /** Add current page to the array */
        if ($paged >= 1)
            $links[] = $paged;

        /** Add the pages around the current page to the array */
        if ($paged >= 3) {
            $links[] = $paged - 1;
            $links[] = $paged - 2;
        }

        if (($paged + 2) <= $max) {
            $links[] = $paged + 2;
            $links[] = $paged + 1;
        }

        echo '<div class="navigation"><ul>' . "\n";

        /** Previous Post Link */
        if (get_previous_posts_link())
            printf('<li>%s</li>' . "\n", get_previous_posts_link());

        /** Link to first page, plus ellipses if necessary */
        if (!in_array(1, $links)) {
            $class = 1 == $paged ? ' class="active"' : '';

            printf('<li%s><a href="%s">%s</a></li>' . "\n", $class, esc_url(get_pagenum_link(1)), '1');

            if (!in_array(2, $links))
                echo '<li>…</li>';
        }

        /** Link to current page, plus 2 pages in either direction if necessary */
        sort($links);
        foreach ((array)$links as $link) {
            $class = $paged == $link ? ' class="active"' : '';
            printf('<li%s><a href="%s">%s</a></li>' . "\n", $class, esc_url(get_pagenum_link($link)), $link);
        }

        /** Link to last page, plus ellipses if necessary */
        if (!in_array($max, $links)) {
            if (!in_array($max - 1, $links))
                echo '<li>…</li>' . "\n";

            $class = $paged == $max ? ' class="active"' : '';
            printf('<li%s><a href="%s">%s</a></li>' . "\n", $class, esc_url(get_pagenum_link($max)), $max);
        }

        /** Next Post Link */
        if (get_next_posts_link())
            printf('<li>%s</li>' . "\n", get_next_posts_link());

        echo '</ul></div>' . "\n";

    }
}

// Hooks
add_action('init', [Blog::class, 'register_post_type']);
add_action('init', [Blog::class, 'register_taxonomy']);