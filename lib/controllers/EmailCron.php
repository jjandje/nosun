<?php /** @noinspection SqlNoDataSourceInspection */

namespace lib\controllers;

use Roots\Sage\Helpers;
use Vazquez\NosunAssumaxConnector\Api\Customers;

/**
 * EmailCron controller that handles all the e-mail triggers that are triggered using a cronjob.
 *
 * Class EmailCron
 * @package lib\controllers
 */
class EmailCron
{
    /**
     * Adds several schedules needed by the e-mail cronjobs.
     *
     * @param array $schedules The current set of schedules.
     * @return array The schedules modified to contain the e-mail related schedules.
     */
    public static function add_schedules(array $schedules) : array
    {
        // TODO: Add potential schedules.
//        $schedules['5min'] = array(
//            'interval' => 300,
//            'display' => __('Every 5 minutes')
//        );
        return $schedules;
    }

    /**
     * Schedules all the e-mail related cron events.
     */
    public static function schedule_cron_events() : void
    {
        if (!wp_next_scheduled('email_triggers_birthday_e-mail')) {
            $midnight = Helpers::create_local_datetime('tomorrow midnight');
            if (!empty($midnight)) {
                wp_schedule_event($midnight->getTimestamp(), 'daily', 'email_triggers_birthday_e-mail');
            }
        }
        if (!wp_next_scheduled('email_triggers_payment_reminder_e-mail')) {
            $midnight = Helpers::create_local_datetime('tomorrow midnight +2 hours');
            if (!empty($midnight)) {
                wp_schedule_event($midnight->getTimestamp() , 'daily', 'email_triggers_payment_reminder_e-mail');
            }
        }
        if (!wp_next_scheduled('email_triggers_option_reminder_e-mail')) {
            $midnight = Helpers::create_local_datetime('tomorrow midnight +3 hours');
            if (!empty($midnight)) {
                wp_schedule_event($midnight->getTimestamp() , 'daily', 'email_triggers_option_reminder_e-mail');
            }
        }
    }

    /**
     * Checks for every customer available if today is their birthday and if so fires the 'birthday' trigger.
     */
    public static function event_birthday_email(): void
    {
        $customerPostIds = Customers::get_by_birthdate();
        if (empty($customerPostIds)) {
            return;
        }
        foreach ($customerPostIds as $customerPostId) {
            $email = get_field('customer_email_address', $customerPostId);
            if (empty($email)) {
                continue;
            }
            $events = Email::trigger_email_events(
                'birthday',
                [$email],
                [
                    'nick_name' => get_field('customer_nick_name', $customerPostId),
                    'first_name' => get_field('customer_first_name', $customerPostId),
                    'last_name' => get_field('customer_last_name', $customerPostId)
                ],
                date('Y-m-d'));
            if (!empty($events)) {
                foreach ($events as $eventId => $result) {
                    if ($result === false) {
                        error_log("[EmailCron->event_birthday_email]: Could not send a birthday e-mail to the Customer with e-mail address: {$email}.");
                    }
                }
            }
        }
    }

    /**
     * Sends a payment reminder e-mail to all bookings which have the payment status not set to Paid
     * and which are not cancelled or done.
     */
    public static function event_payment_reminder_email(): void
    {
        global $wpdb;
        $query = "SELECT post_id FROM {$wpdb->postmeta}
                WHERE (meta_key = 'booking_status' AND meta_value = 'Confirmed') OR
                (meta_key = 'booking_payment_status' AND meta_value != 'Paid') OR
                (meta_key = 'booking_is_option' AND meta_value = '0')
                GROUP BY post_id HAVING COUNT(*) = 3;";
        $postIds = $wpdb->get_col($query);
        if (empty($postIds)) {
            return;
        }
        foreach ($postIds as $postId) {
            $booking = wc_get_order($postId);
            $paymentStatus = get_field('booking_payment_status', $postId);
            $assumaxId = get_post_meta('_assumax_id', $postId);
            if (empty($paymentStatus) || empty($assumaxId) || empty($booking)) {
                continue;
            }
            $depositDeadlineString = get_post_meta($postId, '_nosun_deposit_deadline', true);
            $paymentDeadlineString = get_post_meta($postId, '_nosun_payment_deadline', true);
            if (empty($depositDeadlineString) || empty($paymentDeadlineString)) {
                continue;
            }
            $today = Helpers::today();
            $depositDeadline = Helpers::create_local_datetime($depositDeadlineString);
            $paymentDeadline = Helpers::create_local_datetime($paymentDeadlineString);
            if (empty($today) || empty($depositDeadline) || empty($paymentDeadline)) {
                continue;
            }
            // Compute the difference in days for both deadlines compared to the current date.
            $interval = $today->diff($paymentDeadline);
            $daysTilPaymentDeadline = ($interval->h > 0) ? $interval->days + 1 : $interval->days;
            if ($interval->invert) {
                $daysTilPaymentDeadline = $daysTilPaymentDeadline * -1;
            }
            $interval = $today->diff($depositDeadline);
            $daysTilDepositDeadline = ($interval->h > 0) ? $interval->days + 1 : $interval->days;
            if ($interval->invert) {
                $daysTilDepositDeadline = $daysTilDepositDeadline * -1;
            }

            $customers = get_field('booking_customers', $postId);
            if (empty($customers)) {
                continue;
            }
            $primaryCustomer = null;
            foreach ($customers as $customer) {
                if ($customer['primary']) {
                    $primaryCustomer = Customers::get_by_assumax_id($customer['id']);
                    break;
                }
            }
            if (empty($primaryCustomer)) {
                continue;
            }
            $nickname = get_field('customer_nick_name', $primaryCustomer->ID);
            $emailAddress = get_field('customer_email_address', $primaryCustomer->ID);
            if (empty($nickname) || empty($emailAddress)) {
                continue;
            }
            if ($depositDeadlineString === $paymentDeadlineString) {
                $reminderType = $paymentStatus === 'Unpaid' ? 'total' : 'rest';
            } else {
                $reminderType = $paymentStatus === 'Unpaid' ? 'deposit' : 'rest';
            }
            if ($reminderType === 'total' || $reminderType === 'rest') {
                if ($daysTilPaymentDeadline > 7 || $daysTilPaymentDeadline < 1) {
                    continue;
                }
                $days = $daysTilPaymentDeadline;
                $fixedDays = $daysTilPaymentDeadline <= 3 ? 3 : 7;
            } else {
                if ($daysTilDepositDeadline > 7) {
                    continue;
                }
                $days = $daysTilDepositDeadline;
                $fixedDays = $daysTilDepositDeadline <= 3 ? 3 : 7;
            }
            $events = Email::trigger_email_events(
                "{$reminderType}_payment_reminder",
                [$emailAddress],
                [
                    'days' => $days,
                    'nick_name' => $nickname,
                    'assumax_id' => $assumaxId
                ],
                "{$assumaxId}_{$fixedDays}"
            );
            if (!empty($events)) {
                foreach ($events as $eventId => $status) {
                    if ($status === false) {
                        $booking->add_order_note("Kon geen betalingsreminder ({$reminderType}) sturen naar reiziger met e-mail adres: {$emailAddress}.");
                    } else {
                        $booking->add_order_note("{$fixedDays} dagen betalingsreminder ({$reminderType}) gestuurd naar reiziger met e-mail adres: {$emailAddress}.");
                    }
                }
            }
        }
    }

    /**
     * Sends an option reminder e-mail for bookings which are an option and have a booking date that lies between 6 and
     * 4 days in the past.
     */
    public static function event_option_reminder_email() : void
    {
        global $wpdb;
        $query = "SELECT post_id FROM {$wpdb->postmeta} WHERE 
            (meta_key = 'booking_date' AND DATEDIFF(meta_value, NOW()) BETWEEN -6 AND -4) OR
            (meta_key = 'booking_is_option' AND meta_value = '1')
            GROUP BY post_id HAVING COUNT(post_id) = 2;";
        $postIds = $wpdb->get_col($query);
        if (empty($postIds)) {
            return;
        }
        foreach ($postIds as $postId) {
            $shopOrder = wc_get_order($postId);
            $assumaxId = get_post_meta($postId, '_assumax_id', true);
            if (empty($shopOrder) || empty($assumaxId)) {
                continue;
            }
            $bookingURL = site_url("/boeking/{$assumaxId}");
            $customers = Booking::get_booking_customers($postId);
            $emailAddress = '';
            $nickName = '';
            foreach ($customers as $customerAssumaxId => $isPrimary) {
                if (!$isPrimary) {
                    continue;
                }
                $customerObject = Customers::get_by_assumax_id($customerAssumaxId);
                if (empty($customerObject)) {
                    $shopOrder->add_order_note("Kon geen optie herinnering sturen want de hoofdreiziger bestaat nog niet in de database.");
                    continue 2;
                }
                $emailAddress = get_field('customer_email_address', $customerObject->ID);
                $nickName = get_field('customer_nick_name', $customerObject->ID);
                break;
            }
            if (empty($emailAddress) || empty($nickName)) {
                $shopOrder->add_order_note("Kon geen optie herinnering sturen want de hoofdreiziger heeft geen e-mail adres of roepnaam.");
                continue;
            }
            $today = Helpers::today();
            $bookingDate = Helpers::create_local_datetime(get_field('booking_date', $postId));
            if (empty($today) || empty($bookingDate)) {
                $shopOrder->add_order_note("Kon geen optie herinnering sturen want de boekingsdatum kon niet worden verwerkt.");
                continue;
            }
            $numDays = 7 - $today->diff($bookingDate, true)->days;
            $events = Email::trigger_email_events(
                'option_reminder',
                [$emailAddress],
                [
                    'assumax_id' => $assumaxId,
                    'nick_name' => $nickName,
                    'numdays' => $numDays,
                    'booking_url' => $bookingURL
                ],
                "{$assumaxId}"
            );
            if (!empty($events)) {
                foreach ($events as $eventId => $status) {
                    if ($status === false) {
                        $shopOrder->add_order_note("Kon geen optie herinnering sturen naar: {$emailAddress} voor event met id: {$eventId}.");
                    } else {
                        $shopOrder->add_order_note("Optie herinnering gestuurd naar: {$emailAddress} voor event met id: {$eventId}.");
                    }
                }
            }
        }
    }
}

// Actions and filters
add_filter('cron_schedules', [EmailCron::class, 'add_schedules'], 2);
add_action('init', [EmailCron::class, 'schedule_cron_events']);
// Event related actions
add_action('email_triggers_birthday_e-mail', [EmailCron::class, 'event_birthday_email']);
add_action('email_triggers_payment_reminder_e-mail', [EmailCron::class, 'event_payment_reminder_email']);
add_action('email_triggers_option_reminder_e-mail', [EmailCron::class, 'event_option_reminder_email']);
