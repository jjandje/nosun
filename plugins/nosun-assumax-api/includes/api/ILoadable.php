<?php

namespace Vazquez\NosunAssumaxConnector\Api;

use Vazquez\NosunAssumaxConnector\Loader;

/**
 * Interface that defines functions that load filters and actions.
 *
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes/api
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
interface ILoadable {
    /**
     * Adds new filters and actions to the supplied loader.
     *
     * @param Loader $loader The loader to which to assign actions and filters.
     */
    public static function load($loader) : void;
}