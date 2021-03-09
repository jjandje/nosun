<?php /** @noinspection SqlNoDataSourceInspection */
/** @noinspection SqlDialectInspection */

namespace Vazquez\NosunAssumaxConnector\Api;

use DateTime;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use lib\controllers\Email;
use Roots\Sage\Helpers;
use WP_Post;

class TravelGroups implements ILoadable {
    /**
     * Obtains the TravelGroup that belongs to the Trip with the provided post id.
     *
     * @param int $tripPostId The post if of the Trip.
     * @return WP_Post|null The TravelGroup post or null should none exist.
     */
    public static function get_by_trip_post_id($tripPostId) {
        if (empty($tripPostId)) return null;
        $args = [
            'post_type' => 'travelgroup',
            'post_status' => get_post_statuses(),
            'numberposts' => 1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'travelgroup_trip',
                    'value' => $tripPostId,
                    'compare' => '='
                ]
            ]
        ];
        $results = get_posts($args);
        if (empty($results)) return null;
        return $results[0];
    }

    /**
     * Adds a new chat message to the TravelGroup with the provided post id only when the user is allowed to send
     * messages to the TravelGroup.
     *
     * @param int $travelGroup The post id of the TravelGroup.
     * @param string $subGroup The subgroup inside the TravelGroup.
     * @param string $message The message to add. Will be escaped.
     * @param int|null $userId Optional user id. Will use the current user instead should the value be null.
     * @return array An empty array on error or a message array in the following format:
     * [
     *  'id' => The id of the message.
     *  'user_id' => The id of the user.
     *  'message' => The message contents.
     *  'created_at' => The datetime when the message was created.
     *  'user_type' => The type of user that made the message.
     * ]
     */
    public static function add_new_chat_message($travelGroup, $subGroup, $message, $userId = null) {
        $uid = empty($userId) ? get_current_user_id() : $userId;
        if (empty($uid)) {
            return [];
        }
        $userType = self::user_has_access($travelGroup, $subGroup, $uid);
        if (empty($userType)) {
            return [];
        }
        $sanitized = sanitize_text_field($message);
        $today = Helpers::today();
        if (empty($today)) {
            return [];
        }
        $insertId = self::insert_chat_message_into_table(
            $travelGroup, $subGroup, $uid, $userType, $sanitized, $today->format('Y-m-d H:i:s'));
        if (empty($insertId)) {
            return [];
        }
        return [
            'id' => $insertId,
            'user_id' => $uid,
            'message' => $message,
            'created_at' => $today->format('Y-m-d H:i:s'),
            'user_type' => $userType
        ];
    }

    /**
     * Inserts a new row into the api_travelgroup_messages table containing the supplied information.
     *
     * @param int $travelGroupPostId The post id of the travel group.
     * @param string $subGroup The subgroup.
     * @param int $userId The user id.
     * @param string $userType The user type.
     * @param string $message The sanitized message.
     * @param string $createdAt A MySQL formatted datetimestring.
     * @return int|false Either the newly inserted id or false should something go wrong.
     */
    private static function insert_chat_message_into_table(
        int $travelGroupPostId,
        string $subGroup,
        int $userId,
        string $userType,
        string $message,
        string $createdAt)
    {
        if (empty($travelGroupPostId) || empty($subGroup) ||
            empty($userId) || empty($userType) || empty($createdAt)) {
            return false;
        }
        $key = Key::loadFromAsciiSafeString(NOSUN_CRYPTO_KEY);
        try {
            $encrypted = Crypto::encrypt($message, $key);
        } catch (Exception $e) {
            error_log("[Api\TravelGroups->insert_chat_message_into_table]: Could not encrypt a message for user: {$userId}, for TravelGroup: {$travelGroupPostId} and subgroup: {$subGroup}. Message: {$message}.\n{$e->getMessage()}");
            return false;
        }
        global $wpdb;
        $query = sprintf("INSERT INTO api_travelgroup_messages (travelgroup,subgroup,user_id,user_type,message,created_at) 
                            VALUES (%d,'%s',%d,'%s','%s','%s');",
            $travelGroupPostId, $subGroup, $userId, $userType, $encrypted, $createdAt);
        if ($wpdb->query($query) === false) {
            error_log("[Api\TravelGroups->insert_chat_message_into_table]: Could not insert a message for user: {$userId}, for TravelGroup: {$travelGroupPostId} and subgroup: {$subGroup}. Message: {$message}");
            return false;
        }
        return $wpdb->insert_id;
    }

    /**
     * Obtains all the chat messages that aren't deleted belonging to the provided TravelGroup and subgroup.
     *
     * @param int $travelGroup The post id of the TravelGroup.
     * @param string $subGroup The subgroup inside the TravelGroup.
     * @param int $latestMessageId Only messages with an id greater than this parameter will be obtained.
     * @return array List of messages in the following format:
     * [
     *  'id' => The id of the message.
     *  'user_id' => The id of the user.
     *  'message' => The message contents.
     *  'created_at' => The datetime when the message was created.
     *  'user_type' => The type of user that made the message.
     * ]
     */
    public static function get_chat_messages($travelGroup, $subGroup, $latestMessageId = 0)
    {
        global $wpdb;
        $query = sprintf("SELECT id,user_id,message,created_at,user_type FROM api_travelgroup_messages
                            WHERE travelgroup=%d AND subgroup='%s' AND deleted_at IS NULL AND id>%d
                            ORDER BY created_at ASC;", $travelGroup, $subGroup, $latestMessageId);
        $results = $wpdb->get_results($query);
        $messages = [];
        if (!empty($results)) {
            $key = Key::loadFromAsciiSafeString(NOSUN_CRYPTO_KEY);
            foreach ($results as $result) {
                try {
                    $message = Crypto::decrypt($result->message, $key);
                } catch (Exception $e) {
                    error_log("[Api\TravelGroups->get_chat_messages]: Could not decrypt the message: {$result->message}.\n{$e->getMessage()}");
                    $message = __('Bericht kon niet worden opgehaald.');
                }
                $messageObject = [
                    'id' => $result->id,
                    'user_id' => $result->user_id,
                    'message' => $message,
                    'created_at' => $result->created_at,
                    'user_type' => $result->user_type
                ];
                $messages[] = $messageObject;
            }
        }
        return $messages;
    }

    /**
     * Obtains the participants that take part in the provided TravelGroup and Subgroup.
     *
     * @param int $travelGroup The post id of the TravelGroup.
     * @param string $subGroup The subgroup.
     * @return array Map containing lists of customers and guides in the following format:
     * [
     *  {
     *      assumax_id -> The Assumax id,
     *      post_id -> The Post id
     *  }
     * ]
     */
    public static function get_participants($travelGroup, $subGroup) {
        global $wpdb;
        $query = sprintf("SELECT customer as assumax_id, post_id FROM api_travelgroup_customer_pivot 
                            JOIN %s ON meta_key='_assumax_id' AND meta_value=customer 
                            WHERE is_active=1 AND travelgroup=%d AND subgroup='%s';",
            $wpdb->postmeta, $travelGroup, $subGroup);
        $customers = $wpdb->get_results($query);
        $query = sprintf("SELECT guide as assumax_id, post_id, subgroups FROM api_travelgroup_guide_pivot 
                            JOIN %s ON meta_key='_assumax_id' AND meta_value=guide 
                            WHERE travelgroup=%d;",
            $wpdb->postmeta, $travelGroup);
        $results = $wpdb->get_results($query);
        $guides = [];
        foreach ($results as $result) {
            $subGroups = maybe_unserialize($result->subgroups);
            if (in_array($subGroup, $subGroups)) $guides[] = $result;
        }
        return ['customers' => $customers, 'guides' => $guides];
    }

    /**
     * Checks whether or not the user may access the provided subgroup in the TravelGroup with the provided post id.
     *
     * @param int|null $userId Optional user id. Will use the current user instead should the value be null.
     * @param string $subGroup The subgroup inside the TravelGroup.
     * @param int $travelGroup The post id of the TravelGroup for which to check access.
     * @return string|null The type of the user or null when the user doesn't have access. Can be one of three options:
     *  "admin", "tourguide", or "customer".
     */
    public static function user_has_access($travelGroup, $subGroup, $userId = null) {
        $subGroups = self::get_user_subgroups($travelGroup, $userId);
        if (empty($subGroups['subgroups'])) return null;
        elseif (!in_array($subGroup, $subGroups['subgroups'])) return null;
        return $subGroups['access_type'];
    }

    /**
     * Obtains the subgroups and the access they have to it for the provided TravelGroup and user.
     *
     * @param int $travelGroup The TravelGroup post id.
     * @param int|null $userId Optional user id. Will use the current user instead should the value be null.
     * @return array Map of subgroup keys and user access type values.
     *  The access types available are "none", "admin", "tourguide" and "customer".
     */
    public static function get_user_subgroups($travelGroup, $userId = null) {
        $uid = empty($userId) ? get_current_user_id() : $userId;
        if (empty($uid)) return [];
        global $wpdb;
        // First, check if the user is a guide in the TravelGroup.
        $tourGuidePostId = get_user_meta($uid, 'user_tourguide', true);
        if (!empty($tourGuidePostId)) {
            $tourGuideAssumaxId = get_post_meta($tourGuidePostId, '_assumax_id', true);
            if (!empty($tourGuideAssumaxId)) {
                $query = sprintf("SELECT subgroups FROM api_travelgroup_guide_pivot 
                                    WHERE travelgroup=%d AND guide=%d GROUP BY subgroups;",
                    $travelGroup, $tourGuideAssumaxId);
                $results = $wpdb->get_col($query);
                $subGroups = [];
                foreach ($results as $result) {
                    $guideSubgroups = maybe_unserialize($result);
                    if (!empty($guideSubgroups) && is_array($guideSubgroups)) {
                        $subGroups = $guideSubgroups;
                    }
                }
                if (!empty($subGroups)) return ['subgroups' => $subGroups, 'access_type' => 'tourguide'];
            }
        }
        // Second, check if the user is a customer in the TravelGroup.
        $customerPostId = get_user_meta($uid, 'user_customer', true);
        if (!empty($customerPostId)) {
            $customerAssumaxId = get_post_meta($customerPostId, '_assumax_id', true);
            if (!empty($customerAssumaxId)) {
                $query = sprintf("SELECT subgroup FROM api_travelgroup_customer_pivot 
                                WHERE travelgroup=%d AND customer=%d GROUP BY subgroup;", $travelGroup, $customerAssumaxId);
                $subGroups = $wpdb->get_col($query);
                if (!empty($subGroups)) return ['subgroups' => $subGroups, 'access_type' => 'customer'];
            }
        }
        // Third, check if the user is an admin, and if so obtain all the subgroups.
        if (is_super_admin()) {
            // Customer subgroups
            $query = sprintf("SELECT subgroup FROM api_travelgroup_customer_pivot 
                                WHERE travelgroup=%d GROUP BY subgroup;", $travelGroup);
            $subGroups = $wpdb->get_col($query);
            // Guide subgroups
            $query = sprintf("SELECT subgroups FROM api_travelgroup_guide_pivot 
                                WHERE travelgroup=%d GROUP BY subgroups;", $travelGroup);
            $results = $wpdb->get_col($query);
            foreach ($results as $result) {
                $guideSubgroups = maybe_unserialize($result);
                if (!empty($guideSubgroups) && is_array($guideSubgroups)) {
                    $subGroups = array_merge($subGroups, $guideSubgroups);
                }
            }
            $subGroups = array_unique($subGroups);
            return ['subgroups' => $subGroups, 'access_type' => 'admin'];
        }
        return ['subgroups' => [], 'access_type' => 'none'];
    }

    /**
     * Obtains a list of TravelGroup post ids that the user has access to. This ignores any subgroups the user may
     * be a part of.
     *
     * @param int|null $userId Optional user id. Will use the current user instead should the value be null.
     * @return array A list of TravelGroup post ids that the user belongs to.
     */
    public static function get_by_user_id($userId = null) {
        $uid = empty($userId) ? get_current_user_id() : $userId;
        if (empty($uid)) return [];
        global $wpdb;
        $travelGroups = [];
        $customer = get_user_meta($uid, 'user_customer', true);
        if (!empty($customer)) {
            $customerAssumaxId = get_post_meta($customer, '_assumax_id', true);
            $query = sprintf("SELECT travelgroup, trip_start_date FROM api_travelgroup_customer_pivot 
                                WHERE customer=%d AND is_active=1 ORDER BY trip_start_date DESC;", $customerAssumaxId);
            $travelGroups = $wpdb->get_col($query);
        }
        $tourGuide = get_user_meta($uid, 'user_tourguide', true);
        if (!empty($tourGuide)) {
            $guideAssumaxId = get_post_meta($tourGuide, '_assumax_id', true);
            if (!empty($guideAssumaxId)) {
                $query = sprintf("SELECT travelgroup, trip_start_date FROM api_travelgroup_guide_pivot 
                            WHERE guide=%d ORDER BY trip_start_date DESC;", $guideAssumaxId);
                $travelGroups = array_merge($travelGroups, $wpdb->get_col($query));
            }
        }
        return $travelGroups;
    }

    /**
     * Saves the pivot table rows for the customers in the saved TravelGroup.
     *
     * @note Should be called by the 'acf/save_post' hook.
     * @param int $postId The post id for the TravelGroup.
     */
    public static function on_admin_save($postId) {
        if (get_post_type($postId) !== 'travelgroup') return;
        $customers = get_field('travelgroup_customers', $postId);
        $guides = get_field('travelgroup_tourguides', $postId);
        $travelGroupTitle = get_the_title($postId);
        $travelGroupURL = get_permalink($postId);
        $tripStartDate = Helpers::create_local_datetime(get_field('travelgroup_trip_start_date', $postId));
        if (!empty($customers)) {
            foreach ($customers as &$customer) {
                if (!self::upsert_customer_pivot_row($postId, $customer['assumax_id'], $tripStartDate,
                    $customer['subgroup'], $customer['is_active'])) {
                    error_log("[Api\TravelGroups->on_admin_save]: Could not upsert the customer travelgroup pivot row for travelgroup: {$postId} and customer: {$customer['assumax_id']}.");
                }
                if ($customer['is_active'] && !$customer['mail_sent']) {
                    if (empty($travelGroupTitle) || empty($travelGroupURL)) {
                        error_log("[Api\TravelGroups->on_admin_save]: Could not send an e-mail to the customer with id: {$customer['assumax_id']} because the TravelGroup with id: {$postId} has no title or permalink.");
                        continue;
                    }
                    $customerObject = Customers::get_by_assumax_id($customer['assumax_id']);
                    if (empty($customerObject)) {
                        error_log("[Api\TravelGroups->on_admin_save]: Could not send an e-mail to the customer with id: {$customer['assumax_id']} because it doesn't exist yet.");
                        continue;
                    }
                    $emailAddress = get_field('customer_email_address', $customerObject->ID);
                    $nickName = get_field('customer_nick_name', $customerObject->ID);
                    if (empty($emailAddress) || empty($nickName)) {
                        error_log("[Api\TravelGroups->on_admin_save]: Could not send an e-mail to the customer with id: {$customer['assumax_id']} because it has no e-mail address or nickname.");
                        continue;
                    }
                    $events = Email::trigger_email_events(
                        'active_travelgroup',
                        [$emailAddress],
                        [
                            'nick_name' => $nickName,
                            'travelgroup_id' => $postId,
                            'travelgroup_title' => $travelGroupTitle,
                            'travelgroup_url' => $travelGroupURL,
                            'subgroup' => $customer['subgroup']
                        ],
                        "{$postId}"
                    );
                    if (!empty($events)) {
                        foreach ($events as $eventId => $status) {
                            if ($status === false) {
                                error_log("[Api\TravelGroups->on_admin_save]: Could not send an active TravelGroup e-mail to address: {$emailAddress} for event id: {$eventId}.");
                            } else {
                                $customer['mail_sent'] = true;
                            }
                        }
                    }
                }
            }
        }
        update_field('travelgroup_customers', $customers, $postId);
        if (!empty($guides)) {
            foreach ($guides as $guide) {
                if (empty($guide['subgroups'])) $subGroups = [];
                else $subGroups = array_column($guide['subgroups'], 'subgroup');
                if (!self::upsert_tourguide_pivot_row($postId, $guide['assumax_id'], $tripStartDate, $subGroups)) {
                    error_log("[Api\TravelGroups->on_admin_save]: Could not upsert the tourguide travelgroup pivot row for travelgroup: {$postId} and tourguide: {$guide['assumax_id']}.");
                }
            }
        }
    }

    /**
     * Creates a new TravelGroup using the parameters provided or updates it should it already exist.
     *
     * @param int $tripPostId Post id of the Trip.
     * @param string $tripTitle The title of the Trip.
     * @param array $customers List of customers in the same format as they are saved in the Trip.
     * @param array $tourguides List of tourguides in the same format as they are saved in the Trip.
     * @param DateTime $tripStartDate A DateTime object signifying the start date of the Trip.
     * @return bool True when the upsert was successfull, false if otherwise.
     */
    public static function upsert_from_trip($tripPostId, $tripTitle, $customers, $tourguides, DateTime $tripStartDate) {
        if (empty($tripPostId) || empty($tripTitle) || empty($tripStartDate)) return false;
        $travelGroup = self::get_by_trip_post_id($tripPostId);
        if (empty($travelGroup)) {
            $postArgs = [
                'post_title' => $tripTitle,
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'travelgroup'
            ];
            $travelGroupPostId = wp_insert_post($postArgs);
            if (is_wp_error($travelGroupPostId)) {
                error_log("[Api\TravelGroups->upsert]: Could not insert a new travelgroup post for the trip with post id: {$tripPostId}.");
                error_log("[Api\TravelGroups->upsert]: {$travelGroupPostId->get_error_message()}.");
                return false;
            }
            // Only insert pivot rows when the TravelGroup is first created.
            foreach ($customers as $customer) {
                if (!self::upsert_customer_pivot_row($travelGroupPostId, $customer['assumax_id'], $tripStartDate, 'a')) {
                    error_log("[Api\TravelGroups->upsert]: Could not insert a new customer travelgroup pivot row for travelgroup {$travelGroupPostId} and customer {$customer['assumax_id']}.");
                }
            }
            foreach ($tourguides as $tourguide) {
                if (!self::upsert_tourguide_pivot_row($travelGroupPostId, $tourguide['assumax_id'], $tripStartDate, ['a'])) {
                    error_log("[Api\TravelGroups->upsert]: Could not insert a new tourguide travelgroup pivot row for travelgroup {$travelGroupPostId} and tourguide {$tourguide['assumax_id']}.");
                }
            }
        } else {
            $postArgs = [
                'ID' => $travelGroup->ID,
                'post_title' => $tripTitle
            ];
            $travelGroupPostId = wp_update_post($postArgs);
            if (is_wp_error($travelGroupPostId)) {
                error_log("[Api\TravelGroups->upsert]: Could not update the travelgroup post with post id {$travelGroup->ID}.");
                error_log("[Api\TravelGroups->upsert]: {$travelGroupPostId->get_error_message()}.");
                return false;
            }
            // Delete the old customers and tourguides.
            self::delete_old_customer_pivot_rows($travelGroupPostId, array_column($customers, 'assumax_id'));
            self::delete_old_tourguide_pivot_rows($travelGroupPostId, array_column($tourguides, 'assumax_id'));
        }
        // Update the postmeta.
        return self::update_travelgroup_meta($travelGroupPostId, $tripPostId, $customers, $tourguides, $tripStartDate);
    }

    /**
     * Updates the meta fields for the TravelGroup with the provided parameters.
     *
     * @param int $travelGroup The post id of the TravelGroup.
     * @param int $tripPostId The post if of the trip.
     * @param array $customers A list of customers in the same format as they are saved to the Trip.
     * @param array $tourguides A list of tourguides in the same format as they are saved to the Trip.
     * @param DateTime $tripStartDate The start date of the Trip.
     * @return bool True when everything is updated successfully, false if otherwise.
     */
    private static function update_travelgroup_meta($travelGroup, $tripPostId, $customers, $tourguides, DateTime $tripStartDate) {
        if (empty($travelGroup) || empty($tripPostId) || empty($tripStartDate)) {
            error_log("[Api\TravelGroups->update_travelgroup_meta]: One of the required fields is missing for.");
            return false;
        }
        // Check if there is some old chat data available.
        $oldChat = self::parse_old_chat_data($tripPostId, $travelGroup);
        $updatedCustomers = [];
        $currentCustomers = get_field('travelgroup_customers', $travelGroup);
        $reindexedCustomers = [];
        if (!empty($currentCustomers)) {
            foreach ($currentCustomers as $customer) {
                $reindexedCustomers[$customer['assumax_id']] = $customer;
            }
        }
        foreach ($customers as $customer) {
            if (key_exists($customer['assumax_id'], $reindexedCustomers)) {
                if (key_exists($customer['assumax_id'], $oldChat)) {
                    $reindexedCustomers[$customer['assumax_id']]['is_active'] = $oldChat[$customer['assumax_id']]['active'];
                    $reindexedCustomers[$customer['assumax_id']]['mail_sent'] = $oldChat[$customer['assumax_id']]['mail_sent'];
                }
                $updatedCustomers[] = $reindexedCustomers[$customer['assumax_id']];
            } else {
                $updatedCustomer = [
                    'assumax_id' => $customer['assumax_id'],
                    'subgroup' => 'a',
                    'is_active' => false,
                    'mail_sent' => false
                ];
                if (key_exists($customer['assumax_id'], $oldChat)) {
                    $updatedCustomer['is_active'] = $oldChat[$customer['assumax_id']]['active'];
                    $updatedCustomer['mail_sent'] = $oldChat[$customer['assumax_id']]['mail_sent'];
                }
                $updatedCustomers[] = $updatedCustomer;
            }
        }
        update_field('travelgroup_customers', $updatedCustomers, $travelGroup);
        // Tourguides.
        $updatedTourguides = [];
        $currentTourguides = get_field('travelgroup_tourguides', $travelGroup);
        $reindexedTourguides = [];
        if (!empty($currentTourguides)) {
            foreach ($currentTourguides as $tourguide) {
                $reindexedTourguides[$tourguide['assumax_id']] = $tourguide;
            }
        }
        foreach ($tourguides as $tourguide) {
            if (key_exists($tourguide['assumax_id'], $reindexedTourguides)) {
                $updatedTourguides[] = $reindexedTourguides[$tourguide['assumax_id']];
            } else {
                $updatedTourguides[] = [
                    'assumax_id' => $tourguide['assumax_id'],
                    'subgroups' => ['a']
                ];
            }
        }
        update_field('travelgroup_tourguides', $updatedTourguides, $travelGroup);
        // Remaining fields.
        update_field('travelgroup_trip', $tripPostId, $travelGroup);
        update_field('travelgroup_trip_start_date', $tripStartDate->format('Y-m-d'), $travelGroup);
        return true;
    }

    /**
     * Either inserts or updates a row in the customer pivot table with the values provided.
     *
     * @param int $travelGroup The post id of the TravelGroup.
     * @param int $customer The Assumax Id of the Customer.
     * @param DateTime $tripStartDate The start date of the Trip.
     * @param string|null $subGroup The subgroup the customer is in within the TravelGroup.
     * @param bool $isActive Whether or not the customer is active in the group.
     * @return bool True when the row has been upserted, false if otherwise.
     */
    private static function upsert_customer_pivot_row($travelGroup, $customer, DateTime $tripStartDate, $subGroup = null, $isActive = false) {
        global $wpdb;
        $query = sprintf('INSERT INTO api_travelgroup_customer_pivot (travelgroup,customer,subgroup,is_active,trip_start_date) 
                            VALUES (%1$d,%2$d,\'%3$s\',%4$d,\'%5$s\') 
                            ON DUPLICATE KEY UPDATE
                                subgroup=\'%3$s\',
                                is_active=%4$d,
                                trip_start_date=\'%5$s\';',
            $travelGroup, $customer, $subGroup, $isActive ? 1 : 0, empty($tripStartDate) ? null : $tripStartDate->format('Y-m-d'));
        if ($wpdb->query($query) === false) {
            error_log("[Api\TravelGroups->upsert_customer_pivot_row]: Could not upsert the customer pivot data for the TravelGroup with post id: {$travelGroup}.");
            return false;
        }
        return true;
    }

    /**
     * Either inserts or updates a row in the tourguide pivot table with the values provided.
     *
     * @param int $travelGroup Post id of the TravelGroup.
     * @param int $tourguide Assumax Id of the tourguide.
     * @param DateTime $tripStartDate The start date of the Trip.
     * @param array $subGroups A set of subgroups that the tourguide has access to within the TravelGroup.
     * @return bool True when the row has been upserted, false if otherwise.
     */
    private static function upsert_tourguide_pivot_row($travelGroup, $tourguide, DateTime $tripStartDate, $subGroups) {
        global $wpdb;
        $query = sprintf('INSERT INTO api_travelgroup_guide_pivot (travelgroup,guide,subgroups,trip_start_date) 
                            VALUES (%1$d,%2$d,\'%3$s\',\'%4$s\') 
                            ON DUPLICATE KEY UPDATE
                                subgroups=\'%3$s\',
                                trip_start_date=\'%4$s\';',
            $travelGroup, $tourguide, maybe_serialize($subGroups), empty($tripStartDate) ? null : $tripStartDate->format('Y-m-d'));
        if ($wpdb->query($query) === false) {
            error_log("[Api\TravelGroups->upsert_tourguide_pivot_row]: Could not upsert the tourguide pivot data for the TravelGroup with post id: {$travelGroup}.");
            return false;
        }
        return true;
    }

    /**
     * Deletes all the rows in the travelgroup customer pivot table for customers that are no longer present in the
     * TravelGroup.
     *
     * @param int $travelGroup Post id of the TravelGroup.
     * @param array $currentCustomers List of customer Assumax Id's.
     * @return bool True when the deletion has been successfull and false if otherwise.
     */
    private static function delete_old_customer_pivot_rows($travelGroup, $currentCustomers) {
        global $wpdb;
        if (empty($currentCustomers)) {
            $query = sprintf("DELETE FROM api_travelgroup_customer_pivot WHERE travelgroup=%d;", $travelGroup);
        } else {
            $query = sprintf("DELETE FROM api_travelgroup_customer_pivot WHERE travelgroup=%d AND customer NOT IN(%s);",
                $travelGroup, implode(',', $currentCustomers));
        }
        if ($wpdb->query($query) === false) {
            error_log("[Api\TravelGroups->delete_old_customer_pivot_rows]: Could not delete the old customer pivot rows for the TravelGroup with post id: {$travelGroup}.");
            return false;
        }
        return true;
    }

    /**
     * Deletes all the rows in the travelgroup tourguide pivot table for tourguides that are no longer present in the
     * TravelGroup.
     *
     * @param int $travelGroup Post id of the TravelGroup.
     * @param array $currentTourguides List of tourguide Assumax Id's.
     * @return bool True when the deletion has been successfull and false if otherwise.
     */
    private static function delete_old_tourguide_pivot_rows($travelGroup, $currentTourguides) {
        global $wpdb;
        if (empty($currentTourguides)) {
            $query = sprintf("DELETE FROM api_travelgroup_guide_pivot WHERE travelgroup=%d;", $travelGroup);
        } else {
            $query = sprintf("DELETE FROM api_travelgroup_guide_pivot WHERE travelgroup=%d AND guide NOT IN(%s);",
                $travelGroup, implode(',', $currentTourguides));
        }
        if ($wpdb->query($query) === false) {
            error_log("[Api\TravelGroups->delete_old_tourguide_pivot_rows]: Could not delete the old tourguide pivot rows for the TravelGroup with post id: {$travelGroup}.");
            return false;
        }
        return true;
    }

    /**
     * Checks whether or not an old chat post exists for the provided trip and automatically converts chat messages
     * to a new table row in the api_travelgroup_messages table. It also returns a set of customers with relevant
     * information. Customers are located by their user_id should one exist.
     * Will delete the old chat post if one is found.
     *
     * @param int $tripPostId The post id of the Trip.
     * @param int $travelGroupPostId The post id of the TravelGroup.
     * @return array An array of customers containing the following elements:
     * [
     *      '<assumax_id>' => [
     *          'mail_sent' => bool,
     *          'active' => bool
     *      ], ...
     * ]
     */
    private static function parse_old_chat_data(int $tripPostId, int $travelGroupPostId)
    {
        global $wpdb;
        $tripAssumaxId = get_post_meta($tripPostId, '_assumax_id', true);
        $query = "SELECT post_id FROM {$wpdb->postmeta} 
                    WHERE meta_key = 'chat_nosun_trip_id' 
                    AND meta_value = '{$tripAssumaxId}' LIMIT 1;";
        $chatPostId = $wpdb->get_var($query);
        if (empty($chatPostId)) return [];
        $isActive = get_post_meta($chatPostId, 'reisgroep_actief', true);
        // First parse the messages.
        $query = "SELECT meta_key, meta_value FROM {$wpdb->postmeta}
                    WHERE meta_key LIKE 'chat_message_%_' AND post_id = {$chatPostId} 
                    ORDER BY meta_id ASC;";
        $rows = $wpdb->get_results($query);
        $parseResults = [];
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $matches = [];
                if (!preg_match('/_([\d]+)_/', $row->meta_key, $matches)) {
                    continue;
                }
                if (!isset($parseResults[$matches[1]])) {
                    $parseResults[$matches[1]] = [];
                }
                if (stripos($row->meta_key, 'user')) {
                    $parseResults[$matches[1]]['user_id'] = intval($row->meta_value);
                } elseif (stripos($row->meta_key, 'date')) {
                    $parseResults[$matches[1]]['date'] = $row->meta_value;
                } else { // The actual message.
                    $parseResults[$matches[1]]['message'] = $row->meta_value;
                }
            }
        }
        foreach ($parseResults as $parseResult) {
            self::insert_chat_message_into_table(
                $travelGroupPostId,
                'a',
                $parseResult['user_id'],
                'customer',
                $parseResult['message'],
                $parseResult['date']);
        }
        // Parse the customers.
        $parseResults = [];
        $customers = [];
        $query = "SELECT meta_key, meta_value FROM {$wpdb->postmeta}
                    WHERE meta_key LIKE 'chat_user_%_' AND post_id = {$chatPostId} 
                    ORDER BY meta_id ASC;";
        $rows = $wpdb->get_results($query);
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $matches = [];
                if (!preg_match('/_([\d]+)_/', $row->meta_key, $matches)) {
                    continue;
                }
                if (!isset($parseResults[$matches[1]])) {
                    $parseResults[$matches[1]] = [];
                }
                if (stripos($row->meta_key, 'welkom')) {
                    $parseResults[$matches[1]]['mail_sent'] = $row->meta_value == 1;
                } else {
                    $customer = Customers::get_by_user_id($row->meta_value);
                    if (empty($customer)) {
                        continue;
                    }
                    $parseResults[$matches[1]]['assumax_id'] = get_post_meta($customer->ID, '_assumax_id', true);
                }
            }
        }
        foreach ($parseResults as $parseResult) {
            if (empty($parseResult['assumax_id'])) {
                continue;
            }
            $customers[$parseResult['assumax_id']] = [
                'active' => $isActive == 1,
                'mail_sent' => isset($parseResult['mail_sent']) ? $parseResult['mail_sent'] : false
            ];
        }
        // Delete the old chat post.
        wp_delete_post($chatPostId, true);
        return $customers;
    }

    /**
     * Loads the Customer options available for the assumax_id subfield at the travelgroup_customers field.
     *
     * @param mixed $field The ACF field for the customer assumax id.
     * @return mixed The field modified to contain all the new options.
     */
    public static function acf_load_customer_field($field) {
        $field['choices'] = Customers::get_repeater_values();
        return $field;
    }

    /**
     * Loads the Guide options available for the assumax_id subfield at the travelgroup_tourguides field.
     *
     * @param mixed $field The ACF field for the tourguide assumax id.
     * @return mixed The field modified to contain all the new options.
     */
    public static function acf_load_tourguide_field($field) {
        $field['choices'] = Guides::get_repeater_values();
        return $field;
    }

    /**
     * Registers the post type used by the TravelGroups.
     */
    public static function register_post_type() {
        $labels = array (
            'name'               => 'Reisgroepen',
            'singular_name'      => 'Reisgroep',
            'add_new'            => 'Toevoegen',
            'add_new_item'       => 'Reisgroep toevoegen',
            'edit_item'          => 'Bewerk reisgroep',
            'new_item'           => 'Nieuw',
            'view_item'          => 'Bekijk reisgroep',
            'search_items'       => 'Zoek reisgroep',
            'not_found'          => 'Geen reisgroep gevonden',
            'not_found_in_trash' => 'Geen reisgroep gevonden in prullenbak'
        );
        $args = array (
            'label'               => 'Reisgroepen',
            'description'         => 'Reisgroepen',
            'labels'              => $labels,
            'supports'            => array ( 'title' ),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-businessman',
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'rewrite'             => array ( 'slug' => 'reisgroep', 'with_front' => false )
        );
        register_post_type('travelgroup', $args);
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_action('init', [self::class, 'register_post_type']);
        $loader->add_action('acf/save_post', [self::class, 'on_admin_save']);
        $loader->add_filter('acf/load_field/key=field_5e4be5e860041', [self::class, 'acf_load_customer_field']); // travelgroup_customers -> assumax_id
        $loader->add_filter('acf/load_field/key=field_5e4be82860046', [self::class, 'acf_load_tourguide_field']); // travelgroup_tourguides -> assumax_id
    }
}
