<?php

/**
 * noSun Assumax API Plugin
 *
 * Plugin that makes it possible to connect to and interact with the Assumax API.
 *
 * @link              https://vazquez.nl
 * @since             1.0.0
 * @package           Nosun_Assumax_Api
 *
 * @wordpress-plugin
 * Plugin Name:       Nosun API
 * Plugin URI:        https://vazquez.nl
 * Description:       Plugin that makes it possible to connect to and interact with the Assumax API.
 * Version:           2.0.1
 * Author:            Vazquez BV
 * Author URI:        https://vazquez.nl
 * Text Domain:       nosun-assumax-api
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Load the classes and dependencies.
require_once plugin_dir_path(__FILE__) . '/vendor/autoload.php';

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('PLUGIN_NAME_VERSION', '2.0.1');

// Setup and run the plugin.
$plugin = new \Vazquez\NosunAssumaxConnector\Plugin();
$plugin->run();
