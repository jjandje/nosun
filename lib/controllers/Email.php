<?php /** @noinspection SqlNoDataSourceInspection */
/** @noinspection SqlDialectInspection */

namespace lib\controllers;

use lib\email_triggers\TriggerFactory;
use Roots\Sage\Helpers;

/**
 * Email controller that handles the sending of all the triggered e-mails. This excludes any mails that are a default
 * for wordpress/woocommerce.
 *
 * Class Email
 * @package lib\controllers
 */
class Email
{
    /**
     * The main trigger function that is used to send email on various types of triggers.
     *
     * @param string $trigger The trigger key used to specify the type of trigger.
     * @param array $recipients A list of e-mail addresses to send the e-mail to.
     * @param array $data A map containing any data needed by the trigger.
     * @param string $singletonValue Optional singleton value.
     * @param array $attachments Optional array of attachment filepaths.
     * @return array A map in the following format:
     * [
     *      event_id => [<e-mail addres>, ...] | false
     * ]
     * The event id will be combined with false when an error occurs during the send process.
     */
    public static function trigger_email_events(
        string $trigger,
        array $recipients,
        array $data,
        string $singletonValue = '',
        array $attachments = []) : array
    {
        $events = self::obtain_triggered_events($trigger);
        if (empty($events)) {
            return [];
        }
        $results = [];
        foreach ($events as $event) {
            if (empty($event->email_template)) {
                continue;
            }
            if (!empty($event->recipients)) {
                $eventRecipients = explode(';', $event->recipients);
            } else {
                $eventRecipients = $recipients;
            }
            if (boolval($event->singleton)) {
                $eventRecipients = self::obtain_remaining_recipients($event->id, $eventRecipients, $singletonValue);
            }
            if (empty($eventRecipients)) {
                continue;
            }
            $triggerObject = TriggerFactory::get($trigger, $event->email_template, $data);
            if (empty($triggerObject)) {
                continue;
            }
            $bccRecipients = [];
            if (!empty($event->bcc)) {
                $bccRecipients = explode(';', $event->bcc);
            }
            // Insert the email sent rows before actually sending the e-mails to prevent double e-mails on errors.
            if (!self::insert_email_sent_database_rows($trigger, $event->id, $eventRecipients, $singletonValue)) {
                error_log("[Email->trigger_email_events]: Could not insert the sent e-mails into the database.");
                $results[$event->id] = false;
                continue;
            }
            if ($triggerObject->send($eventRecipients, $bccRecipients, $attachments)) {
                $results[$event->id] = $eventRecipients;
            } else {
            	error_log('######################');
                error_log("[Email->trigger_email_events]: Could not send the e-mails.");
                error_log("[Email->trigger_email_events]: For trigger: {$trigger}.");
                error_log("[Email->trigger_email_events]: Recipients.");
                error_log(var_export( $eventRecipients, true ));
	            error_log('######################');
            }
        }
        return $results;
    }

    /**
     * Obtains a list of available triggers which is supplied by classes that add
     * the vazquez_email_available_triggers filter.
     *
     * @return array The list of available triggers.
     */
    public static function obtain_available_triggers() : array
    {
        $triggers = apply_filters('vazquez_email_available_triggers', []);
        if (!is_array($triggers)) {
            return [];
        }
        return $triggers;
    }

    /**
     * Obtains a list of recipients for which the event with the provided id has not yet been fired from among the
     * list of provided recipients.
     * Filters our any recipients for which the singleton_value field equals the singletonValue parameter.
     *
     * @param int $eventId The id of the event.
     * @param array $recipients List of recipients.
     * @param string $singletonValue Optional singleton value.
     * @return array List of recipients that have not yet received the e-mail. Returns an empty array on any errors.
     */
    public static function obtain_remaining_recipients(int $eventId, array $recipients, string $singletonValue = '') : array
    {
        if (empty($recipients)) {
            return [];
        }
        global $wpdb;
        if (!empty($singletonValue)) {
            $query = sprintf("SELECT email_address FROM api_emails_sent 
                                WHERE event_id = %d 
                                AND email_address IN ('%s') 
                                AND singleton_value = '%s'
                                GROUP BY email_address;",
                $eventId, implode("','", $recipients), $singletonValue);
        } else {
            $query = sprintf("SELECT email_address FROM api_emails_sent 
                                WHERE event_id = %d 
                                AND email_address IN ('%s') 
                                GROUP BY email_address;",
                $eventId, implode("','", $recipients));
        }
        $sentTo = $wpdb->get_col($query);
        return array_diff($recipients, $sentTo);
    }

    /**
     * Obtains any events that need to be triggered.
     *
     * @param string $trigger The trigger key to lookup the events with.
     * @return array An array containing the events that need to be triggered.
     */
    public static function obtain_triggered_events(string $trigger) : array
    {
        global $wpdb;
        $query = "SELECT * FROM api_email_events WHERE `trigger` = '{$trigger}';";
        return $wpdb->get_results($query);
    }

    /**
     * Obtains a list of events that are set up indexed by their trigger key.
     *
     * @return array The list of events indexed by their trigger key.
     */
    public static function obtain_events() : array
    {
        global $wpdb;
        $query = "SELECT api_email_events.*, count(api_emails_sent.event_id) as `fired`
                    FROM api_email_events 
                    LEFT JOIN api_emails_sent ON api_email_events.id = api_emails_sent.event_id
                    GROUP BY 1;";
        $rows = $wpdb->get_results($query);
        $events = [];
        foreach ($rows as $row) {
            if (!key_exists($row->trigger, $events)) {
                $events[$row->trigger] = [];
            }
            $events[$row->trigger][] = $row;
        }
        return $events;
    }

    /**
     * Obtains the list of evailable E-mail templates in alphabetical order.
     *
     * @return array The list of available E-mail templates ordered alphabetically and indexed by the post id.
     */
    public static function obtain_email_templates() : array
    {
        global $wpdb;
        $query = "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'email' AND post_status = 'publish';";
        $rows = $wpdb->get_results($query);
        $emailTemplates = [];
        foreach ($rows as $row) {
            $emailTemplates[$row->ID] = $row->post_title;
        }
        asort($emailTemplates);
        return $emailTemplates;
    }


	/**
	 * Checks whether the provided trigger, emailAddresses and singletonValue (if provided) are present in the api_emails_sent table
	 *
	 * @param string $trigger
	 * @param array $emailAddresses
	 * @param string $singletonValue
	 *
	 * @return bool
	 */
    public static function email_event_has_been_sent(string $trigger, array $emailAddresses, string $singletonValue = '') : bool
    {
    	$has_been_sent = false;
        if(empty($trigger)) {
        	error_log("[Email->email_event_has_been_sent]: Provided trigger string is empty.");
        	return $has_been_sent;
        }
        if(empty($emailAddresses)) {
        	error_log("[Email->email_event_has_been_sent]: Provided emailaddresses array is empty.");
        	return $has_been_sent;
        }
        global $wpdb;
        foreach($emailAddresses as $emailAddress) {
        	if(empty($singletonValue)) {
        		$query = sprintf("SELECT * FROM api_emails_sent WHERE `trigger` = '%s' AND email_address = '%s'", $trigger, $emailAddress);
	        } else {
        	    $query = sprintf("SELECT * FROM api_emails_sent WHERE `trigger` = '%s' AND email_address = '%s' AND singleton_value = '%s'", $trigger, $emailAddress, $singletonValue);
	        }
        	$result = $wpdb->get_results($query);
        	if(!empty($result)) {
		        $has_been_sent = true;
	        }
        }
        return $has_been_sent;
    }

    /**
     * Adds a new row to the api_emails_sent table.
     *
     * @param string $trigger The trigger for which this e-mail has been sent.
     * @param int $eventId The id of the event.
     * @param string[] $emailAddresses The e-mail addresses to which the e-mail has been sent.
     * @param string $singletonValue Optional singleton value.
     * @return bool True when the row has been inserted, false if otherwise.
     */
    private static function insert_email_sent_database_rows(string $trigger, int $eventId, array $emailAddresses, string $singletonValue = '') : bool
    {
        if (empty($trigger) || empty($emailAddresses)) {
            error_log("[Email->insert_email_sent_database_rows]: Empty trigger or e-mail addresses.");
            return false;
        }
        $now = Helpers::today();
        if (empty($now)) {
            error_log("[Email->insert_email_sent_database_rows]: Could not obtain the current datetime.");
            return false;
        }
        global $wpdb;
        $error = false;
        if ($wpdb->query('START TRANSACTION') === false) {
            return false;
        }
        foreach ($emailAddresses as $emailAddress) {
            $result = $wpdb->insert('api_emails_sent', [
                'event_id' => $eventId,
                'trigger' => $trigger,
                'email_address' => $emailAddress,
                'sent_on' => $now->format('Y-m-d H:i:s'),
                'singleton_value' => $singletonValue
            ]);
            if ($result === false) {
                $error = true;
                break;
            }
        }
        if ($error) {
            $wpdb->query('ROLLBACK');
            return false;
        }
        $wpdb->query('COMMIT');
        return true;
    }

    /**
     * Creates the database table for the triggers.
     */
    public static function create_trigger_database_tables() : void
    {
        if (get_option('vazquez_trigger_table_created')) {
            return;
        }
        global $wpdb;
        $query = "CREATE TABLE IF NOT EXISTS `api_email_events` (
                      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                      `trigger` varchar(255) NOT NULL DEFAULT '',
                      `email_template` int(11) unsigned NOT NULL,
                      `singleton` tinyint(2) unsigned NOT NULL DEFAULT '0',
                      `recipients` text,
                      `bcc` text,
                      PRIMARY KEY (`id`),
                      KEY `trigger` (`trigger`)
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
        if ($wpdb->query($query) === false) {
            error_log('[Controllers\Email->create_trigger_database_tables]: Could not create the events database table.');
            return;
        }

        $query = "CREATE TABLE IF NOT EXISTS `api_emails_sent` (
                      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                      `event_id` int(11) unsigned NOT NULL,
                      `trigger` varchar(255) NOT NULL DEFAULT '',
                      `email_address` varchar(255) NOT NULL DEFAULT '',
                      `sent_on` datetime DEFAULT NULL,
                      `singleton_value` varchar(255) DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      KEY `email_address_event_id` (`email_address`,`event_id`)
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
        if ($wpdb->query($query) === false) {
            error_log('[Controllers\Email->create_trigger_database_tables]: Could not create the sent table.');
            return;
        }
        update_option('vazquez_trigger_table_created', true, true);
    }

    /**
     * Registers the post type used by the Emails.
     */
    public static function register_post_type() : void {
        $labels = array(
            "name" => __('E-mail templates', ''),
            "singular_name" => __('E-mail templates', ''),
            "menu_name" => __('E-mail templates', ''),
            "add_new" => __('Nieuw e-mail template toevoegen', ''),
            "add_new_item" => __('Nieuw e-mail template toevoegen', ''),
        );

        $args = array(
            "labels" => $labels,
            "description" => "",
            "public" => false,
            "publicly_queryable" => false,
            "show_ui" => true,
            "show_in_rest" => false,
            "rest_base" => "",
            "has_archive" => false,
            "show_in_menu" => true,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-email-alt',
            "exclude_from_search" => true,
            "capability_type" => "post",
            "map_meta_cap" => true,
            "hierarchical" => false,
            "rewrite" => array("slug" => "email", "with_front" => true),
            "query_var" => true,
            "supports" => array("title", "editor"),
        );
        register_post_type('email', $args);
    }
}

// Hooks
add_action('init', [Email::class, 'create_trigger_database_tables']);
add_action('init', [Email::class, 'register_post_type']);
