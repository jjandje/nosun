<?php

namespace lib\woocommerce_reports;

/**
 * Interface IReportController
 * @package lib\woocommerce_reports
 * @author Chris van Zanten <chris@vazquez.nl>
 */
interface IReportController {
    /**
     * Renders the content of the implementing controller.
     * @return void
     */
    public static function render();
}