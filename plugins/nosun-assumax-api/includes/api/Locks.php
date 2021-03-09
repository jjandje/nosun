<?php /** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */

namespace Vazquez\NosunAssumaxConnector\Api;

/**
 * Holds all the functionality for the lock table used to prevent asynchronous updates to a resource.
 *
 * @since      2.1.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Locks implements ILoadable {
    /** @var string The database table name for the locks. */
    const DB_TABLE = "api_resource_locks";

    /**
     * Tries to acquire a new lock on the resource with specified Assumax Id and resource.
     * Should a lock already exist, then a queued lock is created instead.
     * Each lock is unique on its AssumaxId/Resource/Queued combination. Which means that no duplicate locks
     * can exist at any one point and that at most there can be one queued lock for that AssumaxId/Resource combination.
     *
     * @param string $assumaxId The id of the resource in Assumax.
     * @param string $resource One of the following values:
     *  - Trip
     *  - Template
     *  - Booking
     *  - Accommodation
     *  - Guide
     *  - Customer
     * @param string $action The action as specified by the Assumax API.
     * @param bool $noQueue Whether or not to queue a lock or not should an active one already exist.
     * @return int -1 when a new lock could not be acquired; 0 when a queued lock has been acquired; id of the lock if
     *  a new non queued lock has been acquired.
     */
    public static function acquire_lock($assumaxId, $resource, $action, $noQueue = false) {
        global $wpdb;
        $wpdb->suppress_errors();
        $query = sprintf("SELECT count(assumax_id) FROM %s WHERE assumax_id=%d AND resource='%s';",
            self::DB_TABLE, $assumaxId, $resource);
        $queryArgs = [
            'assumax_id' => $assumaxId,
            'resource' => $resource,
            'created_at' => current_time('mysql'),
            'queued' => 0,
            'api_action' => $action
        ];
        $lockCount = $wpdb->get_var($query);
        $result = -1;
        if ($lockCount !== null) {
            if ((int)$lockCount === 0) { // Create a new lock.
                if ($wpdb->insert(self::DB_TABLE, $queryArgs) === false) $result = -1;
                else $result = $wpdb->insert_id;
            } elseif (!$noQueue) { // Create a new queued lock row.
                $queryArgs['queued'] = 1;
                if ($wpdb->insert(self::DB_TABLE, $queryArgs) === false) $result = -1;
                else $result = 0;
            }
        }
        $wpdb->suppress_errors(false);
        return $result;
    }

    /**
     * Obtains the locks from the table which are next in line to be executed.
     * A lock is ready to be executed if queued equals 1 and there is no similar lock where queued equals 0.
     *
     * @return array A key/value pair array in which the key is the lock id and the value is the database
     *  row of the lock.
     */
    public static function get_next_queued_locks() {
        global $wpdb;
        $query = sprintf("SELECT * FROM %s ORDER BY queued;", self::DB_TABLE);
        $rows = $wpdb->get_results($query);
        if (empty($rows)) return [];
        $queuedLocks = [];
        foreach ($rows as $row) {
            $key = serialize([$row->assumax_id, $row->resource]);
            if (!key_exists($key, $queuedLocks)) $queuedLocks[$key] = $row;
            if (intval($row->queued) === 0) $queuedLocks[$key] = null;
        }
        // Filter locks that are active.
        $filterLocks = [];
        foreach ($queuedLocks as $lock) {
            if (is_null($lock)) continue;
            $filterLocks[$lock->id] = $lock;
        }
        if (!empty($filterLocks)) {
            // Set the queued locks to active.
            $query = sprintf("UPDATE %s SET queued=0 WHERE id IN(%s);", self::DB_TABLE, implode(',', array_keys($filterLocks)));
            if ($wpdb->query($query) === false) {
                error_log("[Nosun_Assumax_Api_Locks->get_next_queued_locks]: Could not set the next queued locks to active..");
                return [];
            }
        }
        return $filterLocks;
    }

    /**
     * Tries to release a lock on the resource with the specified id.
     *
     * @param int $id Id of the lock in the database.
     */
    public static function release_lock($id) {
        if ($id <= 0) return;
        global $wpdb;
        if ($wpdb->delete(self::DB_TABLE, ['id' => $id]) === false) {
            error_log("[Nosun_Assumax_Api_Locks->release_lock]: Could not release the lock with id: {$id}.");
        }
    }

    /**
     * Releases all the locks that are 30 minutes or more old.
     * Should be called be called by a cron event every minute.
     */
    public static function release_old_locks() {
        global $wpdb;
        $query = sprintf("DELETE FROM %s WHERE DATE_SUB(NOW(), INTERVAL 30 MINUTE) > created_at AND queued=0;", self::DB_TABLE);
        if ($wpdb->query($query) === false) {
            error_log("[Nosun_Assumax_Api_Locks->release_old_locks]: Could not release the old locks.");
        }
    }

    /**
     * Creates the locks table.
     * This function should be called on plugin activation.
     */
    public static function create_locks_table() {
        global $wpdb;
        $query = sprintf("CREATE TABLE `%s` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `assumax_id` int(11) unsigned NOT NULL,
              `resource` varchar(184) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
              `created_at` datetime NOT NULL,
              `queued` tinyint(1) unsigned NOT NULL,
              `api_action` varchar(184) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_lock` (`assumax_id`,`resource`,`queued`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;", self::DB_TABLE);
        if ($wpdb->query($query) === false) {
            error_log("[Nosun_Assumax_Api_Locks->create_locks_table]: Could not create the locks table");
        }
    }

    /**
     * Drops the locks table.
     * This function should be called on plugin deactivation.
     */
    public static function drop_locks_table() {
        global $wpdb;
        $query = sprintf("DROP TABLE %s IF EXISTS;", self::DB_TABLE);
        if ($wpdb->query($query) === false) {
            error_log("[Nosun_Assumax_Api_Locks->drop_locks_table]: Could not drop the locks table.");
        }
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_action('api_release_old_locks_event', [self::class, 'release_old_locks']);
    }
}