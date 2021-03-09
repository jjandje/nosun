<?php

namespace Vazquez\NosunAssumaxConnector;

use Vazquez\NosunAssumaxConnector\Api\Accommodations;
use Vazquez\NosunAssumaxConnector\Api\Attachments;
use Vazquez\NosunAssumaxConnector\Api\Bookings;
use Vazquez\NosunAssumaxConnector\Api\Cronjobs;
use Vazquez\NosunAssumaxConnector\Api\Customers;
use Vazquez\NosunAssumaxConnector\Api\Documents;
use Vazquez\NosunAssumaxConnector\Api\Emails;
use Vazquez\NosunAssumaxConnector\Api\Guides;
use Vazquez\NosunAssumaxConnector\Api\Locks;
use Vazquez\NosunAssumaxConnector\Api\Products;
use Vazquez\NosunAssumaxConnector\Api\Templates;
use Vazquez\NosunAssumaxConnector\Api\TravelGroups;
use Vazquez\NosunAssumaxConnector\Api\Trips;
use Vazquez\NosunAssumaxConnector\Api\Webhooks;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Plugin {
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('PLUGIN_NAME_VERSION')) {
            $this->version = PLUGIN_NAME_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'nosun-assumax-api';
        $this->loader = new Loader();

        register_activation_hook(plugin_dir_path(__FILE__), ['Api\Activator', 'activate']);
        register_deactivation_hook(plugin_dir_path(__FILE__), ['Api\Deactivator', 'deactivate']);

        $this->load_hooks();
    }

    /**
     * Run the loader to add all of the hooks with WordPress.
     *
     * @since 1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Loads hooks for all classes.
     */
    private function load_hooks() {
        Accommodations::load($this->get_loader());
        Attachments::load($this->get_loader());
        Bookings::load($this->get_loader());
        Cronjobs::load($this->get_loader());
        Customers::load($this->get_loader());
        Documents::load($this->get_loader());
        Emails::load($this->get_loader());
        Guides::load($this->get_loader());
        Locks::load($this->get_loader());
        Products::load($this->get_loader());
        Templates::load($this->get_loader());
        Trips::load($this->get_loader());
        Webhooks::load($this->get_loader());
        TravelGroups::load($this->get_loader());
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return string The name of the plugin.
     * @since 1.0.0
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return Loader Orchestrates the hooks of the plugin.
     * @since 1.0.0
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string The version number of the plugin.
     * @since 1.0.0
     */
    public function get_version() {
        return $this->version;
    }
}
