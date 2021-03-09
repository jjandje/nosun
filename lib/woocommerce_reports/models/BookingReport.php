<?php
/**
 * PhpStorm specific doc.
 * @noinspection SqlNoDataSourceInspection
*/
namespace lib\woocommerce_reports\models;

/**
 * Holds functionality that allows rest operations on the Booking Report Data in the database.
 *
 * Class BookingReport
 * @package lib\woocommerce_reports\models
 * @author Chris van Zanten <chris@vazquez.nl>
 *
 * @property int InternalId
 * @property int NosunId
 * @property bool IsOption
 * @property string BookingDate
 * @property string PaymentStatus
 * @property string PaymentDeadline
 * @property string DepositDeadline
 * @property int NosunTripId
 * @property string NosunStatus
 * @property string NosunCustomerIds - JSON encoded
 * @property string NosunPaymentIds - JSON encoded
 * @property string NosunInvoiceIds - JSON encoded
 */
class BookingReport extends Model {
    protected static $DB_TABLE = 'booking_reports';
    protected static $DB_STRUCTURE = [
        'internal_id' => [ 'type' => 'BIGINT(20)', 'unsigned' => true, 'index' => 'index'],
        'nosun_id' => ['type' => 'BIGINT(20)', 'unsigned' => true, 'index' => 'unique', 'allow_null' => false],
        'is_option' => ['type' => 'TINYINT(1)', 'unsigned' => true, 'default' => 0],
        'booking_date' => ['type' => 'DATE'],
        'payment_status' => ['type' => 'VARCHAR(128)'],
        'payment_deadline' => ['type' => 'DATE'],
        'deposit_deadline' => ['type' => 'DATE'],
        'nosun_trip_id' => ['type' => 'BIGINT(20)', 'unsigned' => true],
        'nosun_status' => ['type' => 'VARCHAR(128)'],
        'nosun_customer_ids' => ['type' => 'MEDIUMTEXT'],
        'nosun_payment_ids' => ['type' => 'MEDIUMTEXT'],
        'nosun_invoice_ids' => ['type' => 'MEDIUMTEXT']
    ];

    /**
     * Properties belonging to the BookingReport model.
     */
    public $CustomerDatas;   // Loaded in if nosun_customer_ids is set and eager loading is enabled.
    public $PaymentDatas;    // Loaded in if nosun_payment_ids is set and eager loading is enabled.
    public $InvoiceDatas;    // Loaded in if nosun_invoice_ids is set and eager loading is enabled.
    public $TripReport;     // Loaded in if the nosun_trip_id is set and eager loading is enabled.

    /**
     * Parses BookingReport database rows with optional eager loading of several other data elements.
     *
     * @param array $dbDataRows - Rows from the BookingReport table used to construct BookingReport objects.
     * @param bool $loadPaymentDatas - Eager load the PaymentDatas.
     * @param bool $loadCustomerDatas - Eager load the CustomerDatas.
     * @param bool $loadTripReports - Eager load the TripReports.
     * @param bool $loadInvoiceDatas - Eager load the InvoiceDatas.
     * @return BookingReport[] | null - An array of BookingReport instances holding the report data or null if an exception occurred.
     */
    private static function parse_database_rows($dbDataRows, $loadPaymentDatas = false, $loadCustomerDatas = false, $loadTripReports = false, $loadInvoiceDatas = false) {
        if (empty($dbDataRows)) return [];
        $bookingReports = [];
        $nosunCustomerIds = [];
        $nosunPaymentIds = [];
        $nosunInvoiceIds = [];
        $nosunTripIds = [];
        // Create a new BookingReport for each row and add a new entry for the CustomerDatas and PaymentDatas arrays.
        foreach ($dbDataRows as $row) {
            $bookingReport = new BookingReport();
            try {
                $bookingReport->parse_row($row);
            } catch (\Exception $exc) {
                error_log($exc->getMessage());
                error_log($exc->getTraceAsString());
                return null;
            }
            // Add the BookingReport to the list under its own ID for later reference.
            $bookingReports[$bookingReport->Id] = $bookingReport;

            // Add the PaymentNosun ids to the nosunPaymentIds array if eager loading is enabled.
            if ($loadPaymentDatas && isset($bookingReport->NosunPaymentIds)) {
                $linkedIds = json_decode($bookingReport->NosunPaymentIds);
                if (!empty($linkedIds)) {
                    foreach ($linkedIds as $nosunPaymentId) {
                        if (!key_exists($nosunPaymentId, $nosunPaymentIds)) {
                            $nosunPaymentIds[$nosunPaymentId] = [];
                        }
                        $nosunPaymentIds[$nosunPaymentId][] = $bookingReport->Id;
                    }
                }
            }
            // Add the CustomerNosun ids to the nosunCustomerIds array if eager loading is enabled.
            if ($loadCustomerDatas && isset($bookingReport->NosunCustomerIds)) {
                $linkedIds = json_decode($bookingReport->NosunCustomerIds);
                if (!empty($linkedIds)) {
                    foreach ($linkedIds as $nosunCustomerId) {
                        if (!key_exists($nosunCustomerId, $nosunCustomerIds)) {
                            $nosunCustomerIds[$nosunCustomerId] = [];
                        }
                        $nosunCustomerIds[$nosunCustomerId][] = $bookingReport->Id;
                    }
                }
            }
            // Add the NosunInvoice ids to the nosunInvoiceIds array if eager loading is enabled.
            if ($loadInvoiceDatas && isset($bookingReport->NosunInvoiceIds)) {
                $linkedIds = json_decode($bookingReport->NosunInvoiceIds);
                if (!empty($linkedIds)) {
                    foreach ($linkedIds as $nosunInvoiceId) {
                        if (!key_exists($nosunInvoiceId, $nosunInvoiceIds)) {
                            $nosunInvoiceIds[$nosunInvoiceId] = [];
                        }
                        $nosunInvoiceIds[$nosunInvoiceId][] = $bookingReport->Id;
                    }
                }
            }

            // Add the NosunTripId to the nosunTripIds array if eager loading is enabled.
            if ($loadTripReports && isset($bookingReport->NosunTripId)) {
                if (!key_exists($bookingReport->NosunTripId, $nosunTripIds)) {
                    $nosunTripIds[$bookingReport->NosunTripId] = [];
                }
                $nosunTripIds[$bookingReport->NosunTripId][] = $bookingReport->Id;
            }
        }

        // Load in the PaymentData should eager loading be enabled.
        if ($loadPaymentDatas) {
            if (!empty($nosunPaymentIds)) {
                $keys = array_keys($nosunPaymentIds);
                $paymentDataInstances = PaymentData::get_by_nosun_ids($keys);
                if (!empty($paymentDataInstances)) {
                    foreach ($paymentDataInstances as $paymentDataInstance) {
                        $bookingReportIds = $nosunPaymentIds[$paymentDataInstance->NosunId];
                        foreach ($bookingReportIds as $bookingReportId) {
                            if (!isset($bookingReports[$bookingReportId]->PaymentDatas)) $bookingReports[$bookingReportId]->PaymentDatas = [];
                            $bookingReports[$bookingReportId]->PaymentDatas[] = $paymentDataInstance;
                        }
                    }
                }
            }
        }
        // Load in the CustomerData should eager loading be enabled.
        if ($loadCustomerDatas) {
            if (!empty($nosunCustomerIds)) {
                $keys = array_keys($nosunCustomerIds);
                $customerDataInstances = CustomerData::get_by_nosun_ids($keys);
                if (!empty($customerDataInstances)) {
                    foreach ($customerDataInstances as $customerDataInstance) {
                        $bookingReportIds = $nosunCustomerIds[$customerDataInstance->NosunId];
                        foreach ($bookingReportIds as $bookingReportId) {
                            if (!isset($bookingReports[$bookingReportId]->CustomerDatas)) $bookingReports[$bookingReportId]->CustomerDatas = [];
                            $bookingReports[$bookingReportId]->CustomerDatas[] = $customerDataInstance;
                        }
                    }
                }
            }
        }
        // Load in the InvoiceData should eager loading be enabled.
        if ($loadInvoiceDatas) {
            if (!empty($nosunInvoiceIds)) {
                $keys = array_keys($nosunInvoiceIds);
                $invoiceDataInstances = InvoiceData::get_by_nosun_ids($keys);
                if (!empty($invoiceDataInstances)) {
                    foreach ($invoiceDataInstances as $invoiceDataInstance) {
                        $bookingReportIds = $nosunInvoiceIds[$invoiceDataInstance->NosunId];
                        foreach ($bookingReportIds as $bookingReportId) {
                            if (!isset($bookingReports[$bookingReportId]->InvoiceDatas)) $bookingReports[$bookingReportId]->InvoiceDatas = [];
                            $bookingReports[$bookingReportId]->InvoiceDatas[] = $invoiceDataInstance;
                        }
                    }
                }
            }
        }
        // Load in the TripReports should eager loading be enabled.
        if ($loadTripReports) {
            if (!empty($nosunTripIds)) {
                $keys = array_keys($nosunTripIds);
                $tripReportInstances = TripReport::get_by_nosun_ids($keys);
                if (!empty($tripReportInstances)) {
                    foreach ($tripReportInstances as $tripReportInstance) {
                        $bookingReportIds = $nosunTripIds[$tripReportInstance->NosunId];
                        foreach ($bookingReportIds as $bookingReportId) {
                            $bookingReports[$bookingReportId]->TripReport = $tripReportInstance;
                        }
                    }
                }
            }
        }

        // Return the BookingReports as a simple array.
        return array_values($bookingReports);
    }

    /**
     * Tries to obtain the report data linked to the provided post id belonging to a Booking.
     *
     * @param int $postID - The post id of a Booking.
     * @param bool $loadPayments - Eager load the PaymentData.
     * @param bool $loadCustomers - Eager load the CustomerData.
     * @param bool $loadTrip - Eager load the TripReport.
     * @param bool $loadInvoices - Eager load the InvoiceDatas.
     * @return null | BookingReport - A single BookingReport instance holding the report data or null if none could be found or if an exception occurred.
     */
    public static function get_by_booking_post_id($postID, $loadPayments = false, $loadCustomers = false, $loadTrip = false, $loadInvoices = false) {
        if (!isset($postID)) return null;
        global $wpdb;
        $query = sprintf("SELECT * FROM %s WHERE internal_id = %d LIMIT 1;", BookingReport::$DB_TABLE, $postID);
        $rows = $wpdb->get_results($query, ARRAY_A);
        if (!isset($rows)) return null;
        $bookingReports = self::parse_database_rows($rows, $loadPayments, $loadCustomers, $loadTrip, $loadInvoices);
        return empty($bookingReports) ? null : $bookingReports[0];
    }

    /**
     * Tries to obtain the BookingReports that have the selected nosun ids.
     * Can be made to return only the ids instead of the full objects.
     *
     * @param int[] $nosunIds - An array of nosun ids.
     * @param bool $onlyGetIds - Whether or not to only get the ids of the BookingReports.
     * @param bool $loadPaymentDatas - Eager load the PaymentData.
     * @param bool $loadCustomerDatas - Eager load the CustomerData.
     * @param bool $loadTrip - Eager load the TripReport.
     * @param bool $loadInvoices - Eager load the InvoiceDatas.
     * @return BookingReport[]|array[]|null - Depending on the onlyGetIds parameter, the output changes between an array
     *  ids, or an array of BookingReport objects. null is returned when something went wrong.
     */
    public static function get_by_nosun_ids($nosunIds, $onlyGetIds = false, $loadPaymentDatas = false, $loadCustomerDatas = false, $loadTrip = false, $loadInvoices = false) {
        if (empty($nosunIds)) return [];
        global $wpdb;
        if ($onlyGetIds) {
            $query = sprintf("SELECT id FROM %s WHERE nosun_id IN (%s);", self::$DB_TABLE, implode(",", $nosunIds));
            return $wpdb->get_col($query);
        } else {
            $query = sprintf("SELECT * FROM %s WHERE nosun_id IN (%s);", self::$DB_TABLE, implode(",", $nosunIds));
            $rows = $wpdb->get_results($query, ARRAY_A);
            if (!isset($rows)) return null;
            return self::parse_database_rows($rows, $loadPaymentDatas, $loadCustomerDatas, $loadTrip, $loadInvoices);
        }
    }

    /**
     * Tries to obtain the BookingReports that have the selected noSun trip ids.
     * Can be made to return only the ids instead of the full objects.
     *
     * @param int[] $tripIds - The noSun trip ids.
     * @param bool $onlyGetIds - Whether or not to only get the ids of the BookingReports.
     * @param bool $loadPaymentDatas - Eager load the PaymentData.
     * @param bool $loadCustomerDatas - Eager load the CustomerData.
     * @param bool $loadTrip - Eager load the TripReport.
     * @param bool $loadInvoices - Eager load the InvoiceDatas.
     * @return BookingReport[]|array[]|null - Depending on the onlyGetIds parameter, the output changes between an array
     *  ids, or an array of BookingReport objects. null is returned when something went wrong.
     */
    public static function get_by_nosun_trip_ids($tripIds, $onlyGetIds = false, $loadPaymentDatas = false, $loadCustomerDatas = false, $loadTrip = false, $loadInvoices = false) {
        if (empty($tripIds)) return [];
        global $wpdb;
        if ($onlyGetIds) {
            $query = sprintf("SELECT id FROM %s WHERE nosun_trip_id IN (%s);", self::$DB_TABLE, implode(",", $tripIds));
            return $wpdb->get_col($query);
        } else {
            $query = sprintf("SELECT * FROM %s WHERE nosun_trip_id IN (%s);", self::$DB_TABLE, implode(",", $tripIds));
            $rows = $wpdb->get_results($query, ARRAY_A);
            if (!isset($rows)) return null;
            return self::parse_database_rows($rows, $loadPaymentDatas, $loadCustomerDatas, $loadTrip, $loadInvoices);
        }
    }

    /**
     * Tries to obtain the BookingReports that have the booking_date lying between the start- and end-date.
     *
     * @param string $startDate - The starting date, needs to be earlier in time than the endDate parameter.
     * @param string $endDate - The ending date, needs to be further in time than the startDate parameter. 'NOW' is the default and uses the current date.
     * @param bool $loadPaymentDatas - Eager load the PaymentDatas.
     * @param bool $loadCustomerDatas - Eager load the CustomerDatas.
     * @param bool $loadTrip - Eager load the TripReport.
     * @param bool $loadInvoices - Eager load the InvoiceDatas.
     * @param bool $filterCancelled Filter out any cancelled bookings.
     * @param bool $filterOptions Filter any options.
     * @return BookingReport[] || null - An array of BookingReports or null if something went wrong.
     */
    public static function get_by_booking_date($startDate, $endDate = 'NOW', $loadPaymentDatas = false, $loadCustomerDatas = false, $loadTrip = false, $loadInvoices = false, $filterCancelled = false, $filterOptions = false) {
        if (empty($startDate) || empty($endDate)) return [];
        global $wpdb;
        // Parse the dates into a mysql valid date.
        $parsedStartDate = date("Y-m-d", strtotime($startDate));
        $parsedEndDate = date("Y-m-d", strtotime($endDate));
        if ($parsedStartDate > $parsedEndDate) return null;
        $query = sprintf("SELECT * FROM %s WHERE booking_date BETWEEN '%s' AND '%s' %s %s;",
            self::$DB_TABLE,
            $parsedStartDate,
            $parsedEndDate,
            $filterCancelled ? "AND nosun_status != 'Cancelled'" : "",
            $filterOptions ? "AND is_option != 1" : "");
        $rows = $wpdb->get_results($query, ARRAY_A);
        if (!isset($rows)) return null;
        return self::parse_database_rows($rows, $loadPaymentDatas, $loadCustomerDatas, $loadTrip, $loadInvoices);
    }

    /**
     * Tries to obtain the BookingReports that have a TripReport starting date lying between the start- and end-date.
     * Filters out all cancelled bookings.
     *
     * @param string $startDate - The starting date, needs to be earlier in time than the endDate parameter.
     * @param string $endDate - The ending date, needs to be further in time than the startDate parameter. 'NOW' is the default and uses the current date.
     * @param bool $loadPaymentDatas - Eager load the PaymentDatas.
     * @param bool $loadCustomerDatas - Eager load the CustomerDatas.
     * @param bool $loadTrip - Eager load the TripReport.
     * @param bool $loadInvoices - Eager load the InvoiceDatas.
     * @return BookingReport[] || null - An array of BookingReports or null if something went wrong.
     */
    public static function get_by_travel_date($startDate, $endDate = "NOW", $loadPaymentDatas = false, $loadCustomerDatas = false, $loadTrip = false, $loadInvoices = false) {
        if (empty($startDate) || empty($endDate)) return [];
        global $wpdb;
        // Parse the dates into a mysql valid date.
        $parsedStartDate = date("Y-m-d", strtotime($startDate));
        $parsedEndDate = date("Y-m-d", strtotime($endDate));
        if ($parsedStartDate > $parsedEndDate) return null;
        $query = sprintf('SELECT %1$s.* FROM %1$s JOIN %2$s ON %1$s.nosun_trip_id = %2$s.nosun_id WHERE (%2$s.start_date BETWEEN \'%3$s\' AND \'%4$s\') AND %1$s.nosun_status != \'Cancelled\';',
            self::$DB_TABLE, TripReport::$DB_TABLE, $parsedStartDate, $parsedEndDate);
        $rows = $wpdb->get_results($query, ARRAY_A);
        if (!isset($rows)) return null;
        return self::parse_database_rows($rows, $loadPaymentDatas, $loadCustomerDatas, $loadTrip, $loadInvoices);
    }

    /**
     * Creates or updates a BookingReport belonging to a booking.
     * Will also update and/or create the PaymentData and InvoiceData instances.
     *
     * @param $bookingId - The wordpress post id belonging to the booking.
     * @param $apiData - The api data for the booking.
     */
    public static function upsert_booking_report($bookingId, $apiData) {
        $results = BookingReport::get_by_nosun_ids([$apiData->Id]);
        if (empty($results)) {
            $bookingReport = new BookingReport();
            $bookingReport->InternalId = $bookingId;
        } else {
            $bookingReport = $results[0];
        }
        $bookingReport->NosunId = $apiData->Id;
        $bookingReport->NosunStatus = $apiData->Status;
        $bookingReport->PaymentStatus = $apiData->PaymentStatus;
        $bookingReport->PaymentDeadline = date("Y-m-d", strtotime($apiData->PaymentDeadline));
        $bookingReport->DepositDeadline = date("Y-m-d", strtotime($apiData->DepositDeadline));
        $bookingReport->IsOption = $apiData->Option;
        $bookingReport->NosunTripId = $apiData->TripId;
        $bookingReport->BookingDate = date("Y-m-d", strtotime($apiData->Date));
        // Obtain the NosunCustomerIds belonging to this order.
        $nosunCustomerIds = [];
        if (!empty($apiData->Customers)) {
            foreach ($apiData->Customers as $customer) {
                $nosunCustomerIds[] = $customer->Id;
            }
        }
        $bookingReport->NosunCustomerIds = json_encode($nosunCustomerIds);
        // Create/Update PaymentData instances and apply the ids to the PaymentDataIds field.
        $payments = [];
        if (!empty($apiData->Payments)) {
            foreach ($apiData->Payments as $payment) {
                $payments[$payment->Id] = $payment;
            }
        }
        $paymentNosunIds = array_keys($payments);
        $existingPaymentDatas = PaymentData::get_by_nosun_ids($paymentNosunIds);
        $paymentDatas = [];
        if ($existingPaymentDatas !== null) {
            if (!empty($existingPaymentDatas)) {
                foreach ($existingPaymentDatas as $existingPaymentData) {
                    $paymentDatas[$existingPaymentData->NosunId] = $existingPaymentData;
                }
            }
            // Check which payments are missing and create a new PaymentData for those that are.
            $missingPayments = array_diff($paymentNosunIds, array_keys($paymentDatas));
            if (!empty($missingPayments)) {
                foreach ($missingPayments as $missingPayment) {
                    $paymentDatas[$missingPayment] = new PaymentData();
                }
            }
        }
        $bookingReport->NosunPaymentIds = json_encode($paymentNosunIds);
        // Create/Update InvoiceData instances and apply the ids to the InvoiceDataIds field.
        $invoices = [];
        if (!empty($apiData->Invoices)) {
            foreach ($apiData->Invoices as $invoice) {
                $invoices[$invoice->Id] = $invoice;
            }
        }
        $invoiceNosunIds = array_keys($invoices);
        $existingInvoiceDatas = InvoiceData::get_by_nosun_ids($invoiceNosunIds);
        $invoiceDatas = [];
        if ($existingInvoiceDatas !== null) {
            if (!empty($existingInvoiceDatas)) {
                foreach ($existingInvoiceDatas as $existingInvoiceData) {
                    $invoiceDatas[$existingInvoiceData->NosunId] = $existingInvoiceData;
                }
            }
            // Check which invoices ae missing and create a new InvoiceData for those that are.
            $missingInvoices = array_diff($invoiceNosunIds, array_keys($invoiceDatas));
            if (!empty($missingInvoices)) {
                foreach ($missingInvoices as $missingInvoice) {
                    $invoiceDatas[$missingInvoice] = new InvoiceData();
                }
            }
        }
        $bookingReport->NosunInvoiceIds = json_encode($invoiceNosunIds);
        // Save the BookingReport..
        $bookingReport->save();
        // Fill in and save the PaymentDatas.
        if (!empty($paymentDatas)) {
            foreach ($paymentDatas as $nosunId => $paymentData) {
                /** @var PaymentData $paymentData  */
                $paymentData->NosunId = (int)$nosunId;
                $paymentData->NosunBookingId = $apiData->Id;
                $paymentData->Amount = (int)($payments[$nosunId]->Amount * 100);
                $paymentData->DateTime = str_replace('T', ' ', $payments[$nosunId]->DateTime);
                $paymentData->Modified = str_replace('T', ' ', $payments[$nosunId]->Modified);
                $paymentData->save();
            }
        }
        // Fill in and save the InvoiceDatas.
        if (!empty($invoiceDatas)) {
            foreach ($invoiceDatas as $nosunId => $invoiceData) {
                /** @var InvoiceData $invoiceData */
                $invoiceData->NosunId = (int)$nosunId;
                $invoiceData->NosunBookingId = $apiData->Id;
                $invoiceData->Number = $invoices[$nosunId]->Number;
                $invoiceData->Amount = (int)($invoices[$nosunId]->Amount * 100);
                $invoiceData->Modified = str_replace('T', ' ', $invoices[$nosunId]->Modified);
                $invoiceData->InvoiceStatus = $invoices[$nosunId]->InvoiceStatus;
                $invoiceData->InvoiceLines = json_encode($invoices[$nosunId]->InvoiceLines);
                $invoiceData->save();
            }
        }
    }
}