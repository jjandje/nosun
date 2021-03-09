<?php

namespace Vazquez\NosunAssumaxConnector;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Holds helper functions for various general tasks.
 *
 * Class Helpers
 * @package Vazquez\NosunAssumaxConnector
 * @author Chris van Zanten <chris@vazquez.nl>
 * @since 2.0.0
 */
class Helpers {
    /**
     * Sends the file with the provided parameters to the user as file transfer.
     *
     * @param string $fileName The name of the file.
     * @param string $contentType The content type for the file.
     * @param string $fileData The data in stringified form.
     */
    public static function send_data_for_download($fileName, $contentType, $fileData) {
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($fileData));
        print $fileData;
        exit;
    }

    /**
     * Converts a datetime string to a localized DateTime object using the timezone provided in the site options or
     * 'Europe/Amsterdam' as default.
     *
     * @param string $dateTimeString The datetime string to convert.
     * @return DateTime|null The datetime string converted to a localized DateTime object or null if the string cannot be
     *  parsed to a DateTime object.
     */
    public static function create_local_datetime($dateTimeString) {
        $timeZoneString = get_option('timezone_string');
        if (empty($timeZoneString)) $timeZoneString = 'Europe/Amsterdam';
        $timeZone = new DateTimeZone($timeZoneString);
        try {
            $dateTime = new DateTime($dateTimeString, $timeZone);
        } catch (Exception $e) {
            error_log("[Helpers->create_local_datetime]: An exception occurred while trying to the date into a DateTime object.\n{$e->getMessage()}");
            return null;
        }
        return $dateTime;
    }

    /**
     * Returns the current date as a localized DateTime object using the timezone provided in the site options or
     * 'Europe/Amsterdam' as default.
     *
     * @return DateTime|null The current day converted to a localized DateTime object or null if the date cannot be parsed.
     */
    public static function today() {
        return self::create_local_datetime('now');
    }
}