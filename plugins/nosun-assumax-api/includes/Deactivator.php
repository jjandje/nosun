<?php

namespace Vazquez\NosunAssumaxConnector;

use Vazquez\NosunAssumaxConnector\Api\Locks;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Deactivator {
	/**
	 * Runs when the plugin is deactivated.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
        Locks::drop_locks_table();
	}
}
