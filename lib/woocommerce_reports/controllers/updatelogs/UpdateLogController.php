<?php

namespace lib\woocommerce_reports\controllers\updatelogs;

use lib\woocommerce_reports\IReportController;
use lib\woocommerce_reports\models\UpdateLog;

/**
 * Holds all functionality used by the UpdateLog section of the UpdateLog report tab.
 *
 * Class UpdateLogController
 * @package lib\woocommerce_reports\controllers\updatelogs
 * @author Chris van Zanten <chris@vazquez.nl>
 */
class UpdateLogController implements IReportController {

    /**
     * Renders the content of the implementing controller.
     * @return void
     */
    public static function render() {
        global $errors;
        $errors = [];
        // Obtain all the updates of the last month.
        $updateLogs = UpdateLog::get_by_start_date('-1 month');
        // Include the template part. Variables declared above can be used inside the template.
        include(locate_template('templates/woocommerce_reports/update-log.php', false, false));
    }
}