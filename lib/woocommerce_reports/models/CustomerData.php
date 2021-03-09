<?php
/**
 * PhpStorm specific doc.
 * @noinspection SqlNoDataSourceInspection
 */
namespace lib\woocommerce_reports\models;

use stdClass;

/**
 * Holds functionality that allows rest operations on the CustomerData in the database.
 *
 * Class CustomerData
 * @package lib\woocommerce_reports\models
 * @author Chris van Zanten <chris@vazquez.nl>
 *
 * @property int InternalId
 * @property int NosunId
 * @property string Email
 * @property string FirstName
 * @property string LastName
 * @property string NickName
 * @property string Prefix
 * @property string DateOfBirth
 * @property int Sex
 * @property string Nationality
 * @property string Data
 * @property string NosunBookingIds
 */
class CustomerData extends Model {
    public static $DB_TABLE = 'customer_data';
    public static $DB_STRUCTURE = [
        'internal_id' => ['type' => 'BIGINT(20)', 'unsigned' => true, 'index' => 'index'],
        'nosun_id' => ['type' => 'BIGINT(20)', 'unsigned' => true, 'index' => 'unique', 'allow_null' => false],
        'email' => ['type' => 'VARCHAR(100)'],
        'first_name' => ['type' => 'VARCHAR(255)'],
        'last_name' => ['type' => 'VARCHAR(255)'],
        'nick_name' => ['type' => 'VARCHAR(255)'],
        'prefix' => ['type' => 'VARCHAR(255)'],
        'date_of_birth' => ['type' => 'DATE'],
        'sex' => ['type' => 'TINYINT(1)', 'unsigned' => true],
        'nationality' => ['type' => 'VARCHAR(255)'],
        'data' => ['type' => 'LONGTEXT'],
        'nosun_booking_ids' => ['type' => 'MEDIUMTEXT']
    ];

    /**
     * Properties belonging to the CustomerData model.
     */
    public $BookingReports;  // Loaded in if the booking_reports_ids is set and eager loading is enabled.

    /**
     * Parses CustomerData database rows with optional eager loading of several other data elements.
     *
     * @param array $dbDataRows - Rows from the CustomerData table used to construct CustomerData objects.
     * @param bool $loadBookingReports - Eager load the BookingReports.
     * @return null || CustomerData[] - An array of CustomerData instances holding the report data or null if an exception occurred.
     */
    private static function parse_database_rows($dbDataRows, $loadBookingReports = false) {
        if (empty($dbDataRows)) return [];
        $customerDatas = [];
        $bookingNosunIds = [];
        // Create a new CustomerData for each row and add a new entry for the BookingReports array.
        foreach ($dbDataRows as $row) {
            $customerData = new CustomerData();
            try {
                $customerData->parse_row($row);
            } catch (\Exception $exc) {
                error_log($exc->getMessage());
                error_log($exc->getTraceAsString());
                return null;
            }
            // Add the CustomerData to the list under its own ID for later reference.
            $customerDatas[$customerData->Id] = $customerData;

            // Add the BookingNosun ids to the bookingNosunIds array if eager loading is enabled.
            if ($loadBookingReports && isset($customerData->NosunBookingIds)) {
                $linkedIds = json_decode($customerData->NosunBookingIds);
                if (!empty($linkedIds)) {
                    foreach ($linkedIds as $bookingNosunId) {
                        if (!key_exists($bookingNosunId, $bookingNosunIds)) {
                            $bookingNosunIds[$bookingNosunId] = [];
                        }
                        $bookingNosunIds[$bookingNosunId][] = $customerData->Id;
                    }
                }
            }
        }
        // Load in the BookingReports should eager loading be enabled.
        if ($loadBookingReports) {
            if (!empty($bookingNosunIds)) {
                $keys = array_keys($bookingNosunIds);
                $bookingReportInstances = BookingReport::get_by_nosun_ids($keys);
                if (!empty($bookingReportInstances)) {
                    foreach ($bookingReportInstances as $bookingReportInstance) {
                        $customerDataIds = $bookingNosunIds[$bookingReportInstance->NosunId];
                        foreach ($customerDataIds as $customerDataId) {
                            if (!isset($customerDatas[$customerDataId]->BookingReports)) $customerDatas[$customerDataId]->BookingReports = [];
                            $customerDatas[$customerDataId]->BookingReports[] = $bookingReportInstance;
                        }
                    }
                }
            }
        }
        // Return the CustomerDatas as a simple array.
        return array_values($customerDatas);
    }

    /**
     * Tries to obtain the CustomerData belonging to a customer.
     *
     * @param int $customerPostId - The post id of a customer.
     * @param bool $loadBookingReports - Eager load the BookingReports.
     * @return null | CustomerData - Returns the CustomerData belonging to this customer, or null if something went
     *  wrong or the user has no CustomerData available.
     */
    public static function get_by_customer_id($customerPostId, $loadBookingReports = false) {
        if (!isset($customerPostId)) return null;
        global $wpdb;
        $query = sprintf("SELECT * FROM %s WHERE internal_id = %d LIMIT 1;", self::$DB_TABLE, $customerPostId);
        $rows = $wpdb->get_results($query, ARRAY_A);
        if (!isset($rows)) return null;
        $customerDatas = self::parse_database_rows($rows, $loadBookingReports);
        return empty($customerDatas) ? null : $customerDatas[0];
    }

    /**
     * Tries to obtain the CustomerDatas that have the selected nosun ids.
     * Can be made to return only the ids instead of the full objects.
     *
     * @param int[] $nosunIds - An array of nosun ids.
     * @param bool $onlyGetIds - Whether or not to only get the ids of the CustomerData.
     * @param bool $loadBookingReports - Eager load the BookingReports (Only used when onlyGetIds equals false).
     * @return array[]|CustomerData[]|null - Depending on the onlyGetIds parameter, the output changes between an array
     *  ids, or an array of CustomerData objects. null is returned when something went wrong.
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
     * Upserts a CustomerData.
     *
     * @param int $customerId The wordpress customer post id.
     * @param mixed $apiData The api data.
     */
    public static function upsert_customer_report_data($customerId, $apiData) {
        $customerData = CustomerData::get_by_customer_id($customerId);
        if ($customerData === null) $customerData = new CustomerData();
        $customerData->InternalId = $customerId;
        $customerData->NosunId = $apiData->Id;
        $customerData->Email = isset($apiData->EmailAddress) ? $apiData->EmailAddress : '';
        $customerData->FirstName = $apiData->FirstName;
        $customerData->LastName = $apiData->LastName;
        $customerData->NickName = $apiData->NickName;
        $customerData->DateOfBirth = date('Y-m-d', strtotime($apiData->DateOfBirth));
        $customerData->Nationality = isset($apiData->Nationality) ? $apiData->Nationality : '';
        $customerData->Sex = isset($apiData->Sex) ? $apiData->Sex : 0;
        $customerData->Prefix = isset($apiData->Prefix) ? $apiData->Prefix : '';
        $data = new stdClass();
        $data->PhoneNumber = isset($apiData->PhoneNumber) ? $apiData->PhoneNumber : '';
        $data->EmergencyContactName = isset($apiData->EmergencyContactName) ? $apiData->EmergencyContactName : '';
        $data->EmergencyContactPhone = isset($apiData->EmergencyContactPhone) ? $apiData->EmergencyContactPhone : '';
        $data->Street = isset($apiData->Street) ? $apiData->Street : '';
        $data->StreetNumber = isset($apiData->StreetNumber) ? $apiData->StreetNumber : '';
        $data->City = isset($apiData->City) ? $apiData->City : '';
        $data->PostalCode = isset($apiData->PostalCode) ? $apiData->PostalCode : '';
        $data->DietaryWishes = isset($apiData->DietaryWishes) ? $apiData->DietaryWishes : '';
        $data->Note = isset($apiData->Note) ? $apiData->Note : '';
        $customerData->Data = json_encode($data);
        // Obtain all the nosunBookingIds belonging to this Customer.
        $nosunBookingIds = [];
        if (!empty($apiData->Bookings)) {
            foreach ($apiData->Bookings as $booking) {
                $nosunBookingIds[] = $booking->Id;
            }
        }
        $customerData->NosunBookingIds = json_encode($nosunBookingIds);
        // Save the CustomerData.
        $customerData->save();
    }
}
