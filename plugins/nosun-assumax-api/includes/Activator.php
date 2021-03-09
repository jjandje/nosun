<?php

namespace Vazquez\NosunAssumaxConnector;

use Vazquez\NosunAssumaxConnector\Api\Locks;
use Vazquez\NosunAssumaxConnector\Api\Trips;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Activator {
	/**
	 * Runs when the plugin is activated.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
        Locks::create_locks_table();
        Trips::create_pivot_table();
	}
}
