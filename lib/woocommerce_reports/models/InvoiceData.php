<?php
/**
 * PhpStorm specific doc.
 * @noinspection SqlNoDataSourceInspection
 */
namespace lib\woocommerce_reports\models;

/**
 * Holds functionality that allows rest operations on the Invoice Data in the database.
 *
 * Class InvoiceData
 * @package lib\woocommerce_reports\models
 * @author Chris van Zanten <chris@vazquez.nl>
 *
 * @property int NosunId
 * @property int Number
 * @property int Amount
 * @property string InvoiceStatus
 * @property string Modified
 * @property string InvoiceLines
 * @property int NosunBookingId
 */
class InvoiceData extends Model {
    public static $DB_TABLE = 'invoice_data';
    public static $DB_STRUCTURE = [
        'nosun_id' => ['type' => 'BIGINT(20)', 'unsigned' => true, 'index' => 'unique', 'allow_null' => false],
        'number' => ['type' => 'BIGINT(20)', 'unsigned' => true, 'allow_null' => false],
        'amount' => ['type' => 'BIGINT(20)'],
        'invoice_status' => ['type' => 'VARCHAR(255)'],
        'modified' => ['type' => 'DATETIME'],
        'invoice_lines' => ['type' => 'MEDIUMTEXT'],
        'nosun_booking_id' => ['type' => 'BIGINT(20)', 'unsigned' => true]
    ];

    /**
     * Properties belonging to the InvoiceData Model.
     */
    public $BookingReport;

    /**
     * Parses TripReport database rows with optional eager loading of several other data elements.
     *
     * @param array $dbDataRows - Rows from the TripReport table used to construct TripReport objects.
     * @param boolean $loadBookingReports - Eager load the BookingReports.
     * @return InvoiceData[] || null - An array of TripReport instances holding the report data or null if an exception occurred.
     */
    public static function parse_database_rows($dbDataRows, $loadBookingReports = false) {
        if (empty($dbDataRows)) return [];
        $invoiceDatas = [];
        $nosunBookingIds = [];
        // Create a new InvoiceData for each row and add a new entry for the nosunBookingIds array.
        foreach ($dbDataRows as $row) {
            $invoiceData = new InvoiceData();
            try {
                $invoiceData->parse_row($row);
            } catch (\Exception $exc) {
                error_log($exc->getMessage());
                error_log($exc->getTraceAsString());
                return null;
            }
            // Add the InvoiceData to the list under its own id.
            $invoiceDatas[$invoiceData->Id] = $invoiceData;

            // Add the BookingReport id to the bookingReportsIds array if eager loading is enabled.
            if ($loadBookingReports && isset($invoiceData->NosunBookingId)) {
                if (!key_exists($invoiceData->NosunBookingId, $nosunBookingIds)) {
                    $bookingNosunIds[$invoiceData->NosunBookingId] = [];
                }
                $bookingNosunIds[$invoiceData->NosunBookingId][] = $invoiceData->Id;
            }
        }
        // Load in the BookingReports should eager loading be enabled.
        if ($loadBookingReports) {
            if (!empty($nosunBookingIds)) {
                $keys = array_keys($nosunBookingIds);
                $bookingReportInstances = BookingReport::get_by_nosun_ids($keys);
                if (!empty($bookingReportInstances)) {
                    foreach ($bookingReportInstances as $bookingReportInstance) {
                        $invoiceDataIds = $nosunBookingIds[$bookingReportInstance->NosunId];
                        foreach ($invoiceDataIds as $invoiceDataId) {
                            $invoiceDatas[$invoiceDataId]->BookingReport = $bookingReportInstance;
                        }
                    }
                }
            }
        }

        // Return the InvoiceDatas as a simple array.
        return array_values($invoiceDatas);
    }

    /**
     * Tries to obtain the InvoiceDatas that have the selected nosun ids.
     * Can be made to return only the ids instead of the full objects.
     *
     * @param int[] $nosunIds - An array of nosun ids.
     * @param bool $onlyGetIds - Whether or not to only get the ids of the InvoiceData.
     * @param bool $loadBookingReports - Eager load the BookingReports (Only used when onlyGetIds equals false).
     * @return array[]|InvoiceData[]|null - Depending on the onlyGetIds parameter, the output changes between an array
     *  ids, or an array of InvoiceData objects. null is returned when something went wrong.
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
}