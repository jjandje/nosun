<?php
/**
 * PhpStorm specific doc.
 * @noinspection SqlNoDataSourceInspection
 */
namespace lib\woocommerce_reports\models;

/**
 * Holds functionality that allows rest operations on the Trip Reports in the database.
 *
 * Class TripReport
 * @package lib\woocommerce_reports\models
 * @author Chris van Zanten <chris@vazquez.nl>
 *
 * @property int InternalId
 * @property int NosunId
 * @property int TemplateId
 * @property string Title
 * @property string Countries
 * @property string BoardingPoints
 * @property string Accommodations
 * @property string StartDate
 * @property string EndDate
 * @property int NDays
 * @property int NCustomers
 * @property int NEntries
 * @property int SalesPrice
 * @property boolean RequiresIdentification
 * @property boolean IsConfirmed
 * @property string WebsiteAvailability
 * @property string Guides
 * @property string TripTypes
 * @property string AgeGroup
 * @property string NosunStatus
 * @property string NosunCustomerIds
 * @property string Data
 */
class TripReport extends Model {
    public static $DB_TABLE = 'trip_reports';
    public static $DB_STRUCTURE = [
        'internal_id' => ['type' => 'BIGINT(20)', 'unsigned' => true, 'index' => 'index'],
        'nosun_id' => ['type' => 'BIGINT(20)', 'unsigned' => true, 'index' => 'unique', 'allow_null' => false],
        'template_id' => ['type' => 'BIGINT(20)'],
        'title' => ['type' => 'TEXT'],
        'countries' => ['type' => 'MEDIUMTEXT'],
        'boarding_points' => ['type' => 'MEDIUMTEXT'],
        'accommodations' => ['type' => 'MEDIUMTEXT'],
        'start_date' => ['type' => 'DATE'],
        'end_date' => ['type' => 'DATE'],
        'n_days' => ['type' => 'BIGINT(20)'],
        'n_customers' => ['type' => 'BIGINT(20)'],
        'n_entries' => ['type' => 'BIGINT(20)'],
        'sales_price' => ['type' => 'BIGINT(20)'], // Times 100 as to not need floating points.
        'requires_identification' => ['type' => 'TINYINT(1)', 'unsigned' => true],
        'is_confirmed' => ['type' => 'TINYINT(1)', 'unsigned' => true],
        'website_availability' => ['type' => 'VARCHAR(255)'],
        'guides' => ['type' => 'MEDIUMTEXT'],
        'trip_types' => ['type' => 'MEDIUMTEXT'],
        'age_group' => ['type' => 'MEDIUMTEXT'],
        'nosun_status' => ['type' => 'VARCHAR(255)'],
        'nosun_customer_ids' => ['type' => 'MEDIUMTEXT'],
        'data' => ['type' => 'MEDIUMTEXT']
    ];

    /**
     * Properties belonging to the TripReport Model.
     */
    public $CustomerDatas;

    /**
     * Parses TripReport database rows with optional eager loading of several other data elements.
     *
     * @param array $dbDataRows - Rows from the TripReport table used to construct TripReport objects.
     * @param boolean $loadCustomerDatas - Eager load the CustomerDatas.
     * @return TripReport[] || null - An array of TripReport instances holding the report data or null if an exception occurred.
     */
    public static function parse_database_rows($dbDataRows, $loadCustomerDatas = false) {
        if (empty($dbDataRows)) return [];
        $tripReports = [];
        $nosunCustomerIds = [];
        // Create a new TripReport for each row and add a new entry for the nosunCustomerIds array.
        foreach ($dbDataRows as $row) {
            $tripReport = new TripReport();
            try {
                $tripReport->parse_row($row);
            } catch (\Exception $exc) {
                error_log($exc->getMessage());
                error_log($exc->getTraceAsString());
                return null;
            }
            // Add the TripReport to the list under its own ID for later reference.
            $tripReports[$tripReport->Id] = $tripReport;

            // Add the NosunCustomer ids to the nosunCustomerIds array if eager loading is enabled.
            if ($loadCustomerDatas && isset($tripReport->NosunCustomerIds)) {
                $linkedIds = json_decode($tripReport->NosunCustomerIds);
                if (!empty($linkedIds)) {
                    foreach ($linkedIds as $nosunCustomerId) {
                        if (!key_exists($nosunCustomerId, $nosunCustomerIds)) {
                            $nosunCustomerIds[$nosunCustomerId] = [];
                        }
                        $nosunCustomerIds[$nosunCustomerId][] = $tripReport->Id;
                    }
                }
            }
        }

        // Load in the CustomerDatas should eager loading be enabled.
        if ($loadCustomerDatas) {
            if (!empty($nosunCustomerIds)) {
                $keys = array_keys($nosunCustomerIds);
                $customerDataInstances = CustomerData::get_by_nosun_ids($keys);
                if (!empty($customerDataInstances)) {
                    foreach ($customerDataInstances as $customerDataInstance) {
                        $tripReportIds = $nosunCustomerIds[$customerDataInstance->NosunId];
                        foreach ($tripReportIds as $tripReportId) {
                            if (!isset($tripReports[$tripReportId]->CustomerDatas)) $tripReports[$tripReportId]->CustomerDatas = [];
                            $tripReports[$tripReportId]->CustomerDatas[] = $customerDataInstance;
                        }
                    }
                }
            }
        }

        // Return the TripReports as a simple array.
        return array_values($tripReports);
    }

    /**
     * Tries to obtain the TripReport belonging to a product.
     *
     * @param int $productId - The wordpress post id of a product.
     * @param bool $loadCustomerDatas - Eager load the CustomerDatas.
     * @return null | TripReport - Returns the TripReport belonging to this product, or null if something went
     *  wrong or the product has no TripReports available.
     */
    public static function get_by_product_id($productId, $loadCustomerDatas = false) {
        if (!isset($productId)) return null;
        global $wpdb;
        $query = sprintf("SELECT * FROM %s WHERE internal_id = %d LIMIT 1;", self::$DB_TABLE, $productId);
        $rows = $wpdb->get_results($query, ARRAY_A);
        if (!isset($rows)) return null;
        $tripReports = self::parse_database_rows($rows, $loadCustomerDatas);
        return empty($tripReports) ? null : $tripReports[0];
    }

    /**
     * Tries to obtain the TripReports that have the selected nosun ids.
     * Can be made to return only the ids instead of the full objects.
     *
     * @param int[] $nosunIds - An array of nosun ids.
     * @param bool $onlyGetIds - Whether or not to only get the ids of the TripReports.
     * @param bool $loadCustomerDatas - Eager load the CustomerDatas (Only used when onlyGetIds equals false).
     * @return array[]|TripReport[]|null - Depending on the onlyGetIds parameter, the output changes between an array
     *  ids, or an array of TripReport objects. null is returned when something went wrong.
     */
    public static function get_by_nosun_ids($nosunIds, $onlyGetIds = false, $loadCustomerDatas = false) {
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
            return self::parse_database_rows($rows, $loadCustomerDatas);
        }
    }

    /**
     * Tries to obtain the TripReports that have the trip dates lying between the start- and end-date.
     *
     * @param string $startDate - The starting date, needs to be earlier in time than the endDate parameter.
     * @param string $endDate - The ending date, needs to be further in time than the startDate parameter. 'NOW' is the default and uses the current date.
     * @param bool $loadCustomerDatas - Eager load the CustomerDatas.
     * @return TripReport[] || null - An array of TripReports or null if something went wrong.
     */
    public static function get_by_trip_date($startDate, $endDate = 'NOW', $loadCustomerDatas = false) {
        if (empty($startDate) || empty($endDate)) return [];
        global $wpdb;
        // Parse the dates into a mysql valid date.
        $parsedStartDate = date("Y-m-d", strtotime($startDate));
        $parsedEndDate = date("Y-m-d", strtotime($endDate));
        if ($parsedStartDate > $parsedEndDate) return null;
        $query = sprintf("SELECT * FROM %s WHERE start_date >= '%s' AND end_date <= '%s';", self::$DB_TABLE, $parsedStartDate, $parsedEndDate);
        $rows = $wpdb->get_results($query, ARRAY_A);
        if (!isset($rows)) return null;
        return self::parse_database_rows($rows, $loadCustomerDatas);
    }

    /**
     * Tries to obtain the TripReports that share a TemplateId with the trip that has the provided nosunId.
     *
     * @param int $nosunId - The nosunId which belongs to a trip.
     * @param bool $loadCustomerDatas - Eager load the CustomerDatas.
     * @return TripReport[] || null - An array of TripReports or null if something went wrong.
     */
    public static function get_similar_by_nosun_id($nosunId, $loadCustomerDatas = false) {
        if (empty($nosunId)) return [];
        global $wpdb;
        $query = sprintf('SELECT * FROM %1$s WHERE template_id = (SELECT template_id FROM %1$s WHERE nosun_id = %2$s);', self::$DB_TABLE, $nosunId);
        $rows = $wpdb->get_results($query, ARRAY_A);
        if (!isset($rows)) return null;
        return self::parse_database_rows($rows, $loadCustomerDatas);
    }

    /**
     * Creates or updates a TripReport belonging to a Trip.
     *
     * @param int $tripPostId The post id for the Trip.
     * @param mixed $tripApiData The api data for the Trip.
     */
    public static function upsert_trip_report($tripPostId, $tripApiData) {
        $tripReports = TripReport::get_by_nosun_ids(array($tripApiData->Id));
        if (!empty($tripReports)) $tripReport = $tripReports[0];
        else {
            $tripReport = new TripReport();
            $tripReport->InternalId = $tripPostId;
        }
        $tripReport->NosunId = $tripApiData->Id;
        $tripReport->TemplateId = $tripApiData->TemplateId;
        $tripReport->StartDate = date("Y-m-d", strtotime($tripApiData->StartDate));
        $tripReport->EndDate = date("Y-m-d", strtotime($tripApiData->EndDate));
        $tripReport->NDays = $tripApiData->NumberOfDays;
        $tripReport->NEntries = $tripApiData->NumberOfEntries;
        $tripReport->NCustomers = $tripApiData->NumberOfCustomers;
        $tripReport->SalesPrice = (int)($tripApiData->SalesPrice * 100);
        $tripReport->IsConfirmed = $tripApiData->ShowConfirmedOnWebsite;
        $tripReport->WebsiteAvailability = $tripApiData->WebsiteAvailability;
        $tripReport->Guides = json_encode($tripApiData->Guides);
        $tripReport->NosunStatus = $tripApiData->Status;
        $tripReport->Title = get_the_title($tripPostId);

        // Note: There are several fields that use data from the Template API which cannot reasonably be obtained here.
        // These fields aren't used for any of the calculations so will be left out for now. Check the TripReport class
        // for more information about these fields.

        // Obtain the NosunCustomerIds belonging to this trip.
        $nosunCustomerIds = [];
        if (!empty($tripApiData->Customers)) {
            foreach ($tripApiData->Customers as $customer) {
                $nosunCustomerIds[] = $customer->Id;
            }
        }
        $tripReport->NosunCustomerIds = json_encode($nosunCustomerIds);
        // Save the TripReport instance.
        $tripReport->save();
    }
}