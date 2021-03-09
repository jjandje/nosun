<?php

namespace Roots\Sage\Setup;

use Roots\Sage\Assets;

/**
 * Theme setup
 */
function setup() {
	// Enable features from Soil when plugin is activated
	// https://roots.io/plugins/soil/
	add_theme_support( 'soil-clean-up' );
	add_theme_support( 'soil-nav-walker' );
	add_theme_support( 'soil-nice-search' );
	add_theme_support( 'soil-jquery-cdn' );
	add_theme_support( 'soil-relative-urls' );

	// Make theme available for translation
	// Community translations can be found at https://github.com/roots/sage-translations
	load_theme_textdomain( 'sage', get_template_directory() . '/lang' );

	// Enable plugins to manage the document title
	// http://codex.wordpress.org/Function_Reference/add_theme_support#Title_Tag
	add_theme_support( 'title-tag' );

	// Register wp_nav_menu() menus
	// http://codex.wordpress.org/Function_Reference/register_nav_menus
	register_nav_menus( [
		'top_navigation'  => __( 'Top navigatie', 'sage' ),
		'primary_navigation'  => __( 'Hoofdmenu', 'sage' ),
		'mobile_navigation'   => __( 'Mobiele navigatie', 'sage' ),
		'footer_menu_trips'   => __( 'Footer menu Reizen', 'sage' ),
		'footer_menu_nosun'   => __( 'Footer menu noSun', 'sage' ),
		'footer_menu_account' => __( 'Footer menu Account', 'sage' ),
	] );

	// Enable post thumbnails
	// http://codex.wordpress.org/Post_Thumbnails
	// http://codex.wordpress.org/Function_Reference/set_post_thumbnail_size
	// http://codex.wordpress.org/Function_Reference/add_image_size
	add_theme_support( 'post-thumbnails' );

	add_image_size( 'tourguide-thumb', 210, 200, array ( 'center', 'center' ) );
	add_image_size( 'tourguides-thumb', 280, 267, array ( 'center', 'center' ) );
	add_image_size( 'popular-trip-thumb', 330, 238, array( 'center', 'center' ) );
	add_image_size( 'product-slider', 1905, 490, array ( 'center', 'center' ) );


	// Enable post formats
	// http://codex.wordpress.org/Post_Formats
	add_theme_support( 'post-formats', [ 'aside', 'gallery', 'link', 'image', 'quote', 'video', 'audio' ] );

	// Enable HTML5 markup support
	// http://codex.wordpress.org/Function_Reference/add_theme_support#HTML5
	add_theme_support( 'html5', [ 'caption', 'comment-form', 'comment-list', 'gallery', 'search-form' ] );

	// Use main stylesheet for visual editor
	// To add custom styles edit /assets/styles/layouts/_tinymce.scss
	add_editor_style( Assets\asset_path( 'styles/main.css' ) );
}

add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );

/**
 * Adds several intervals that can be used by the WP cron scheduler.
 * @param $schedules
 * @return mixed
 */
function add_cron_schedules( $schedules ) {
    // add a 'weekly' schedule to the existing set
    $schedules['30Min'] = array(
        'interval' => 1800,
        'display' => __('Every 30 Minutes')
    );
    $schedules['10Min'] = array(
        'interval' => 600,
        'display' => __('Every 10 Minutes')
    );
    $schedules['5Min'] = array(
        'interval' => 300,
        'display' => __('Every 5 Minutes')
    );
    $schedules['3Min'] = array(
        'interval' => 180,
        'display' => __('Every 3 Minutes')
    );
    return $schedules;
}
add_filter( 'cron_schedules', __NAMESPACE__ . '\\add_cron_schedules' );

/**
 * Theme assets
 */
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\assets', 100 );
function assets() {

	//wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css?family=Life+Savers|Bungee|Open+Sans|Raleway:400,700', false );
	wp_enqueue_style( 'sage/css', Assets\asset_path( 'styles/main.css' ), false, null );

	if ( is_single() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}


	wp_enqueue_script( 'sage/js', Assets\asset_path( 'scripts/main.js' ), [ 'jquery' ], null, true );
	wp_localize_script( 'sage/js', 'ajax_object',
		array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ));
	wp_localize_script('sage/js', 'WPURLS', array( 'siteurl' => get_option('siteurl') ));
	wp_localize_script('sage/js', 'nosunRoute', array( 'nosun_route' => "https://www.google.nl/maps/dir/''/nosun/@52.2160187,6.7509991,12z/data=!4m8!4m7!1m0!1m5!1m1!1s0x47b81393d7f8ab73:0xf60bd7e0fe28af74!2m2!1d6.8210386!2d52.2160394" ));

	wp_enqueue_script( 'maps', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyBJMlyPYmftV9u8XuUfF6K0atAsVRLbaoU' );

}

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\admin_js' );
function admin_js( $hook ) {
	if ( 'edit.php' !== $hook ) {
		//return;
	}
	wp_enqueue_script( 'sage/adminjs', Assets\asset_path( 'scripts/admin.js' ), false, null, true );

	wp_localize_script( 'sage/adminjs', 'nosun_admin', array (
		'ajax_url' => admin_url( 'admin-ajax.php' )
	) );
}


/** =========================================================================
 * // REMOVE JUNK FROM HEAD
 * // ========================================================================= */
function cubiq_setup() {
	remove_action( 'wp_head', 'wp_generator' );                // #1
	remove_action( 'wp_head', 'wlwmanifest_link' );            // #2
	remove_action( 'wp_head', 'rsd_link' );                    // #3
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );        // #4

	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );    // #5

	add_filter( 'the_generator', '__return_false' );            // #6
	//add_filter( 'show_admin_bar', '__return_false' );            // #7

	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );  // #8
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}

add_action( 'after_setup_theme', __NAMESPACE__ . '\\cubiq_setup' );


/**
 * Hide Widgets
 */
add_action( 'widgets_init', __NAMESPACE__ . '\\unregister_default_widgets', 11 );
function unregister_default_widgets() {
	unregister_widget( 'WP_Widget_Pages' );
	unregister_widget( 'WP_Widget_Calendar' );
	unregister_widget( 'WP_Widget_Archives' );
	unregister_widget( 'WP_Widget_Links' );
	unregister_widget( 'WP_Widget_Meta' );
	unregister_widget( 'WP_Widget_Search' );
	unregister_widget( 'WP_Widget_Categories' );
	unregister_widget( 'WP_Widget_Recent_Posts' );
	unregister_widget( 'WP_Widget_Recent_Comments' );
	unregister_widget( 'WP_Widget_RSS' );
	unregister_widget( 'WP_Widget_Tag_Cloud' );
	unregister_widget( 'WP_Widget_Media_Audio' );
	unregister_widget( 'WP_Widget_Media_Video' );
	unregister_widget( 'NF_Widget' );
}


add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\remove_dashboard_widgets' );
function remove_dashboard_widgets() {
	global $wp_meta_boxes;

	unset( $wp_meta_boxes[ 'dashboard' ][ 'side' ][ 'core' ][ 'dashboard_quick_press' ] );
	unset( $wp_meta_boxes[ 'dashboard' ][ 'normal' ][ 'core' ][ 'dashboard_activity' ] );
	unset( $wp_meta_boxes[ 'dashboard' ][ 'normal' ][ 'core' ][ 'dashboard_incoming_links' ] );
	unset( $wp_meta_boxes[ 'dashboard' ][ 'normal' ][ 'core' ][ 'dashboard_right_now' ] );
	unset( $wp_meta_boxes[ 'dashboard' ][ 'normal' ][ 'core' ][ 'dashboard_plugins' ] );
	unset( $wp_meta_boxes[ 'dashboard' ][ 'normal' ][ 'core' ][ 'dashboard_recent_drafts' ] );
	unset( $wp_meta_boxes[ 'dashboard' ][ 'normal' ][ 'core' ][ 'dashboard_recent_comments' ] );
	unset( $wp_meta_boxes[ 'dashboard' ][ 'side' ][ 'core' ][ 'dashboard_primary' ] );
	unset( $wp_meta_boxes[ 'dashboard' ][ 'side' ][ 'core' ][ 'dashboard_secondary' ] );

}


/**
 * Remove unused menu items
 */
add_action( 'admin_menu', __NAMESPACE__ . '\\custom_menu_page_removing' );
function custom_menu_page_removing() {
	remove_menu_page( 'edit-comments.php' );
	remove_menu_page( 'edit.php' );
	remove_submenu_page( 'themes.php', 'widgets.php' );
}


/**
 * Fonts
 */
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\wpb_add_google_fonts' );
function wpb_add_google_fonts() {

	wp_enqueue_style( 'wpb-google-fonts', 'https://fonts.googleapis.com/css?family=Open+Sans:400,600,700|Roboto:400,500,700', false );
}

/**
 * WooCommerce Sage
 */
add_theme_support( 'woocommerce' );

/**
 * WooCommerce Reports
 * Autoload all the files in the woocommerce_reports directory.
 */
if (!function_exists('glob_recursive')) {
// Does not support flag GLOB_BRACE
    function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, glob_recursive($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }
}

require_once __DIR__ . '/email_triggers/Trigger.php';
$files = glob_recursive(__DIR__ . '/woocommerce_reports/*.php');
$files = array_merge($files, glob_recursive(__DIR__ . '/controllers/*.php'));
$files = array_merge($files, glob_recursive(__DIR__ . '/email_triggers/*.php'));
if ($files === false) {
    throw new \Exception("Failed to glob for function files");
}
foreach ($files as $file) {
    require_once $file;
}
unset($file, $files);
