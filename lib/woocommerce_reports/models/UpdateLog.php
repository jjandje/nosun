<?php /** @noinspection SqlNoDataSourceInspection */

namespace lib\woocommerce_reports\models;

use Exception;

/**
 * Model implementation that holds log data for site updates.
 * Site updates could be anything, but are mainly used by the Webhooks.
 *
 * Class UpdateLog
 * @package lib\woocommerce_reports\models
 *
 * @property int NosunId
 * @property string Type
 * @property string Action
 * @property string CreatedAt
 */
class UpdateLog extends Model {
    public static $DB_TABLE = 'update_log';
    public static $DB_STRUCTURE = [
        'nosun_id' => ['type' => 'BIGINT(20)', 'unsigned' => true],
        'type' => ['type' => 'VARCHAR(255)'],
        'action' => ['type' => 'VARCHAR(255)'],
        'created_at' => ['type' => 'DATETIME', 'allow_null' => false]
    ];

    /**
     * Parses UpdateLog database rows into UpdateLog objects.
     *
     * @param array $dbDataRows Rows from the UpdateLog table used to construct UpdateLog objects.
     * @return UpdateLog[]|null A list of UpdateLog objects or null if something went wrong.
     */
    public static function parse_database_rows($dbDataRows) {
        if (empty($dbDataRows)) return [];
        $updateLogs = [];
        foreach ($dbDataRows as $row) {
            $updateLog = new static();
            try {
                $updateLog->parse_row($row);
            } catch (Exception $exc) {
                error_log($exc->getMessage());
                error_log($exc->getTraceAsString());
                return null;
            }
            $updateLogs[$updateLog->Id] = $updateLog;
        }
        return $updateLogs;
    }

    /**
     * Creates a new UpdateLog object and saves it to the database.
     *
     * @param string $type The type of the log entry.
     * @param string $action The action of the log entry.
     * @param int $nosunId The optional nosun id that the action operated on.
     */
    public static function add_entry($type, $action, $nosunId = null) {
        $updateLog = new static();
        $updateLog->Type = $type;
        $updateLog->Action = $action;
        $updateLog->NosunId = $nosunId;
        $updateLog->CreatedAt = date("Y-m-d H:i:s");
        if (empty($updateLog->save())) {
            error_log("[UpdateLog->add_entry]: Could not add a new update log entry for with type: {$type}, action {$action} and {$nosunId}.");
        }
    }

    /**
     * Tries to obtain all the UpdateLogs from the database where the created_at column is further in the future or equal
     * to the supplied date.
     *
     * @param string $startDate The start date.
     * @return UpdateLog[] A list of UpdateLog objects which have been created after the supplied date.
     */
    public static function get_by_start_date($startDate) {
        if (empty($startDate)) return [];
        global $wpdb;
        // Parse the dates into a mysql valid date.
        $parsedStartDate = date("Y-m-d H:i:s", strtotime($startDate));
        $query = sprintf("SELECT * FROM %s WHERE created_at >= '%s' ORDER BY created_at DESC;",
            self::$DB_TABLE,
            $parsedStartDate);
        $rows = $wpdb->get_results($query, ARRAY_A);
        if (!isset($rows)) return null;
        return self::parse_database_rows($rows);
    }
}