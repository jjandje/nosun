<?php

namespace lib\controllers;

use Vazquez\NosunAssumaxConnector\Api\Customers;
use Vazquez\NosunAssumaxConnector\Api\Guides;
use Vazquez\NosunAssumaxConnector\Api\TravelGroups as TravelGroupAPI;

/**
 * Controller for the TravelGroups.
 * Has several functions that deal with the TravelGroups and chat.
 *
 * Class TravelGroup
 * @package lib\controllers
 */
class TravelGroup {
    /**
     * AJAX function that adds a new chat message to the TravelGroup and underlying Subgroup.
     * Uses the following $_POST parameters:
     *  - travelgroup
     *  - subgroup
     *  - message
     * Sends a response back containing the new message id.
     */
    public static function ajax_add_chat_message() {
        check_ajax_referer('travelgroups', 'security');
        if (empty($_POST['travelgroup']) || !isset($_POST['subgroup']) || empty($_POST['message'])) {
            die(1);
        }
        $travelGroup = sanitize_text_field($_POST['travelgroup']);
        $subGroup = sanitize_text_field($_POST['subgroup']);
        $message = sanitize_text_field($_POST['message']);
        $result = TravelGroupAPI::add_new_chat_message($travelGroup, $subGroup, $message);
        if (empty($result)) die(1);
        wp_send_json($result, 200);
    }

    /**
     * AJAX function that obtains the newest messages using the following query parameters as input:
     *  - travelgroup -> The post id of the TravelGroup.
     *  - subgroup -> The subgroup
     *  - latest_message -> The id of the latest message obtained.
     * Sends a JSON response back containing an array with of messages.
     * For the message format see the TravelGroups::get_chat_messages function.
     * @link TravelGroupAPI::get_chat_messages()
     */
    public static function ajax_get_new_messages() {
        check_ajax_referer('travelgroups', 'security');
        if (empty($_POST['travelgroup']) || !isset($_POST['subgroup']) || !isset($_POST['latest_message'])) {
            die(1);
        }
        $travelGroup = sanitize_text_field($_POST['travelgroup']);
        $subGroup = sanitize_text_field($_POST['subgroup']);
        $latestMessage = sanitize_text_field($_POST['latest_message']);
        if (empty(TravelGroupAPI::user_has_access($travelGroup, $subGroup))) {
            die(1);
        }
        $messages = TravelGroupAPI::get_chat_messages($travelGroup, $subGroup, $latestMessage);
        wp_send_json($messages, 200);
    }

    /**
     * Obtains all the user information needed to display in the chat.
     *
     * @param int $travelGroup The post id of the TravelGroup.
     * @param string $subGroup The subgroup.
     * @return array A map containing the customers and guides in the following format:
     * <assumax_id> => [
     *      'nickname' => The nickname
     *      'date_of_birth' => The date of birth
     *      'profile_image' => The profile image or a placeholder,
     *      'user_id' => The user id should one exist
     * ]
     */
    public static function get_participant_information($travelGroup, $subGroup) {
        $participants = TravelGroupAPI::get_participants($travelGroup, $subGroup);
        $information = [
            'customers' => [],
            'guides' => []
        ];
        foreach ($participants['customers'] as $customer) {
            $userId = Customers::get_user_id($customer->post_id);
	        if(empty($userId)) continue;
	        if(!key_exists( $customer->assumax_id, $information['customers'])) {
		        $information['customers'][$customer->assumax_id] = [
			        'nickname' => get_field('customer_nick_name', $customer->post_id),
			        'date_of_birth' => get_field('customer_date_of_birth', $customer->post_id),
			        'profile_image' => !empty($userId) ? get_user_meta($userId, 'profile_image', true) : null,
			        'user_id' => $userId
		        ];
	        }
        }
        foreach ($participants['guides'] as $guide) {
            $profileImage = null;
            $images = get_field('tourguide_images', $guide->post_id);
            if (!empty($images)) {
                $profileImage = $images[0]['image'];
            }
            $userId = Guides::get_user_id($guide->post_id);
            $information['guides'][$guide->assumax_id] = [
                'nickname' => get_field('tourguide_nickname', $guide->post_id) ?
                    get_field('tourguide_nickname', $guide->post_id) :
                    get_field('tourguide_first_name', $guide->post_id),
                'date_of_birth' => get_field('tourguide_birth_date', $guide->post_id),
                'profile_image' => $profileImage,
                'user_id' => $userId
            ];
        }
        return $information;
    }
}

/**
 * Hooks used by the TravelGroup controller.
 */
add_action('wp_ajax_add_new_travelgroup_message', [TravelGroup::class, 'ajax_add_chat_message']);
add_action('wp_ajax_nopriv_add_new_travelgroup_message', [TravelGroup::class, 'ajax_add_chat_message']);
add_action('wp_ajax_get_new_travelgroup_messages', [TravelGroup::class, 'ajax_get_new_messages']);
add_action('wp_ajax_nopriv_get_new_travelgroup_messages', [TravelGroup::class, 'ajax_get_new_messages']);
