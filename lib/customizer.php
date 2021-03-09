<?php

namespace Roots\Sage;

use Roots\Sage\Assets;

/**
 * Holds functions that customize the styling of the website.
 *
 * Class Customizer
 * @package Roots\Sage
 */
class Customizer {
    /**
     * Add postMessage support
     *
     * @param $wp_customize
     */
    public static function customize_register($wp_customize) {
      $wp_customize->get_setting('blogname')->transport = 'postMessage';
    }

    /**
     * Customizer JS
     */
    public static function customize_preview_js() {
        wp_enqueue_script('sage/customizer', Assets\asset_path('scripts/customizer.js'), ['customize-preview'], null, true);
    }

    /**
     * Login style
     * TODO: Maybe move this to a proper css file?
     */
    public static function login_page() {
        ?>
        <style type="text/css">

            #login {
                padding: 5% 0 0;
            }

            body.login div#login h1 a {
                /* height: 100px;
                width: 250px;
                background-size: 250px 100px; */
            }

            .login {
                background: #17253F;
            }

            .login form {
                -webkit-border-radius: 5px;
                -moz-border-radius: 5px;
                border-radius: 5px;
                background: #ffd618 !important;
            }

            .login form label {
                color: #fff;
            }

            .login #backtoblog a, .login #nav a {
                color:#17253F !important;
            }

            .login #backtoblog a:hover, .login #nav a:hover {
                text-decoration: underline;
                color: #17253F !important;
            }

            .login .button-primary {
                background: #c9398d !important;
                border-color: #c9398d !important;
                -webkit-box-shadow: none !important;
                box-shadow: none !important;
                text-shadow: none !important;
                color: #FFF !important;
            }

            .login .button-primary:hover {
                background: #c9398d !important;
                border-color: #c9398d !important;
                -webkit-box-shadow: none !important;
                box-shadow: none !important;
            }

            .login .button-primary:focus {
                background: #c9398d !important;
                border-color: #c9398d !important;
            }

        </style>
        <?php
    }
}

// Hooks
add_action('customize_register', [Customizer::class, 'customize_register']);
add_action('login_enqueue_scripts', [Customizer::class, 'login_page']);
add_action('customize_preview_init', [Customizer::class, 'customize_preview_js']);
