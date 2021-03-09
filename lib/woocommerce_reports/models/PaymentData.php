<?php
/**
 * PhpStorm specific doc.
 * @noinspection SqlNoDataSourceInspection
 */
namespace lib\woocommerce_reports\models;

/**
 * Holds functionality that allows rest operations on the Payment Data in the database.
 *
 * Class PaymentData
 * @package lib\woocommerce_reports\models
 * @author Chris van Zanten <chris@vazquez.nl>
 *
 * @property int NosunId
 * @property string DateTime
 * @property string Modified
 * @property int Amount
 * @property string Description
 * @property int $NosunBookingId
 */
class PaymentData extends Model {
    public static $DB_TABLE = 'payment_data';
    public static $DB_STRUCTURE = [
        'nosun_id' => ['type' => 'BIGINT(20)', 'unsigned' => true, 'index' => 'unique', 'allow_null' => false],
        'date_time' => ['type' => 'DATETIME'],
        'modified' => ['type' => 'DATETIME'],
        'amount' => ['type' => 'BIGINT(20)', 'unsigned' => true], // Times 100 as to not need floating points.
        'description' => ['type' => 'MEDIUMTEXT'],
        'nosun_booking_id' => ['type' => 'BIGINT(20)', 'unsigned' => true]
    ];

    /**
     * Properties belonging to the PaymentData model.
     */
    public $BookingReport;

    /**
     * Parses PaymentData database rows with optional eager loading of several other data elements.
     *
     * @param array $dbDataRows - Rows from the PaymentData table used to construct PaymentData objects.
     * @param boolean $loadBookingReports - Eager load the BookingReports.
     * @return PaymentData[] || null - An array of PaymentData instances holding the payment data or null if an exception occurred.
     */
    public static function parse_database_rows($dbDataRows, $loadBookingReports = false) {
        if (empty($dbDataRows)) return [];
        $paymentDatas = [];
        $nosunBookingIds = [];
        // Create a new PaymentData for each row and add a new entry for the BookingReports array.
        foreach ($dbDataRows as $row) {
            $paymentData = new PaymentData();
            try {
                $paymentData->parse_row($row);
            } catch (\Exception $exc) {
                error_log($exc->getMessage());
                error_log($exc->getTraceAsString());
                return null;
            }
            // Add the PaymentData to the list under its own ID for later reference.
            $paymentDatas[$paymentData->Id] = $paymentData;

            // Add the BookingReport id to the bookingReportsIds array if eager loading is enabled.
            if ($loadBookingReports && isset($paymentData->NosunBookingId)) {
                if (!key_exists($paymentData->NosunBookingId, $nosunBookingIds)) {
                    $nosunBookingIds[$paymentData->NosunBookingId] = [];
                }
                $nosunBookingIds[$paymentData->NosunBookingId][] = $paymentData->Id;
            }
        }
        // Load in the BookingReports should eager loading be enabled.
        if ($loadBookingReports) {
            if (!empty($nosunBookingIds)) {
                $keys = array_keys($nosunBookingIds);
                $bookingReportInstances = BookingReport::get_by_nosun_ids($keys);
                if (!empty($bookingReportInstances)) {
                    foreach ($bookingReportInstances as $bookingReportInstance) {
                        $paymentDataIds = $nosunBookingIds[$bookingReportInstance->NosunId];
                        foreach ($paymentDataIds as $paymentDataId) {
                            $paymentDatas[$paymentDataId]->BookingReport = $bookingReportInstance;
                        }
                    }
                }
            }
        }

        // Return the PaymentDatas as a simple array.
        return array_values($paymentDatas);
    }

    /**
     * Tries to obtain the PaymentDatas that have the selected nosun ids.
     * Can be made to return only the ids instead of the full objects.
     *
     * @param int[] $nosunIds - An array of nosun ids.
     * @param bool $onlyGetIds - Whether or not to only get the ids of the PaymentData.
     * @param bool $loadBookingReports - Eager load the BookingReports (Only used when onlyGetIds equals false).
     * @return array[]|PaymentData[]|null - Depending on the onlyGetIds parameter, the output changes between an array
     *  ids, or an array of PaymentData objects. null is returned when something went wrong.
     */
    public static function get_by_nosun_ids($nosunIds, $onlyGetIds = false, $loadBookingReports = false) {
        if (empty($nosunIds)) return [];
        global $wpdb;
        if ($onlyGetIds) {
            $query = sprintf("SELECT id FROM %s WHERE nosun_id IN (%s);", self::$DB_TABLE, implode(",", $nosunIds));
            return $wpdb->get_col($query);
        }
        else {
            $query = sprintf("SELECT * FROM %s WHERE nosun_id IN (%s);", self::$DB_TABLE, implode(",", $nosunIds));
            $rows = $wpdb->get_results($query, ARRAY_A);
            if (!isset($rows)) return null;
            return self::parse_database_rows($rows, $loadBookingReports);
        }
    }

    /**
     * Tries to obtain the PaymentDatas that have the date_time lying between the start- and end-date.
     *
     * @param string $startDate - The starting date, needs to be earlier in time than the endDate parameter.
     * @param string $endDate - The ending date, needs to be further in time than the startDate parameter. 'NOW' is the default and uses the current date.
     * @param bool $loadBookingReports - Eager load the BookingReports.
     * @return PaymentData[] || null - An array of PaymentDatas or null if something went wrong.
     */
    public static function get_by_payment_date($startDate, $endDate = 'NOW', $loadBookingReports = false) {
        if (empty($startDate) || empty($endDate)) return [];
        global $wpdb;
        // Parse the dates into a mysql valid date.
        $parsedStartDate = date("Y-m-d", strtotime($startDate));
        $parsedEndDate = date("Y-m-d", strtotime($endDate));
        if ($parsedStartDate > $parsedEndDate) return null;
        $query = sprintf("SELECT * FROM %s WHERE date_time BETWEEN '%s' AND '%s';", self::$DB_TABLE, $parsedStartDate, $parsedEndDate);
        $rows = $wpdb->get_results($query, ARRAY_A);
        if (!isset($rows)) return null;
        return self::parse_database_rows($rows, $loadBookingReports);
    }
}