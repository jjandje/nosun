<?php

namespace Roots\Sage;

use DateTime;
use DateTimeZone;
use Exception;

/**
 *  Helper functions used by the other classes.
 *
 * Class Helpers
 * @package Roots\Sage
 */
class Helpers {
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

    /**
     * Redirects the user to the page with the provided link.
     * Optionally add a notice.
     * Optionally set a cookie for one hour with the provided name and data.
     * Exits code execution.
     *
     * @param string $link The link to which to redirect.
     * @param string $noticeType Optional type of notice.
     * @param string $noticeMessage Optional notice message.
     * @param string $cookieName Optional name of the cookie.
     * @param string $cookieData Optional cookie data.
     * @note $noticeType and $noticeMessage need to be both not empty before a notice is added.
     * @note $cookieName and $cookieData need to both be set to set the cookie.
     */
    public static function redirect_with_status($link, $noticeType = null, $noticeMessage = null, $cookieName = null, $cookieData = null) {
        if (empty($link)) $link = site_url();
        if (!empty($noticeType) && !empty($noticeMessage)) {
            if (function_exists('wc_clear_notices')) wc_clear_notices();
            if (function_exists('wc_add_notice')) wc_add_notice($noticeMessage, $noticeType);
        }
        if (!empty($cookieName) && !empty($cookieData)) {
            setcookie($cookieName, json_encode($cookieData), time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
        wp_safe_redirect($link);
        exit;
    }

    /**
     * Adds a new woocommerce notice, sets the appropriate status header and then exits.
     *
     * @param $message - The message that describes the state.
     * @param bool $isError - Whether or not the state is an error.
     */
    public static function exit_with_json_status_message($message, $isError) {
        // Set the appropriate headers.
        if ($isError) status_header(500);
        else status_header(200);
        // Set the notice.
        if (function_exists('wc_add_notice')) wc_add_notice($message, $isError ? 'error' : 'success');
        // Exit the running code.
        exit;
    }

    /**
     * Checks whether the email in the global POST exists and returns the result.
     */
    public static function check_if_email_exists() {
        // TODO: Check for a nonce.
        die((get_user_by('email', sanitize_email($_POST['email']))) ? 'true' : 'false');
    }

    /**
     * Updates an unsafe value which would normally break the serialization process of wordpress by base64 encoding it.
     *
     * @see update_post_meta()
     * @param $postId
     * @param $key
     * @param $value
     * @param string $previousValue
     * @return bool|int
     */
    public static function update_unsafe_post_meta($postId, $key, $value, $previousValue = '') {
        if (empty($previousValue)) return update_post_meta($postId, $key, base64_encode(serialize($value)));
        return update_post_meta($postId, $key, base64_encode(serialize($value)), base64_encode(serialize($previousValue)));
    }

    /**
     * Gets an unsafe value which has been base64 encoded by the update_unsafe_post_meta function.
     * Should the unsafe value not actually be a base64 encoded string, then the unsafe value is returned as is.
     *
     * @see get_post_meta()
     * @param $postId
     * @param string $key
     * @param bool $single
     * @return mixed
     */
    public static function get_unsafe_post_meta($postId, $key = '', $single = false) {
        $unsafeValue = get_post_meta($postId, $key, $single);
        if (is_string($unsafeValue) && preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $unsafeValue)) {
            return unserialize(base64_decode($unsafeValue, true));
        } else {
            return $unsafeValue;
        }
    }

    /**
     * Obtains the site title for the current location.
     *
     * @return string The title depending on the current location.
     */
    public static function title() {
        if (is_home()) {
            if (get_option('page_for_posts', true)) {
                return get_the_title(get_option('page_for_posts', true));
            } else {
                return __('Latest Posts', 'sage');
            }
        } elseif (is_archive()) {
            if (is_post_type_archive()) {
                if (is_post_type_archive('employee')) {
                    return 'Het team';
                } else {
                    return post_type_archive_title('', false);
                }
            } else {
                return single_term_title('', false);
            }
        } elseif (is_search()) {
            return sprintf(__('Search Results for %s', 'sage'), get_search_query());
        } elseif (is_404()) {
            return __('Not Found', 'sage');
        } else {
            return get_the_title();
        }
    }

    /**
     * TODO: Document this function.
     *
     * @param bool $post
     * @param int $length
     * @param bool $customContent
     * @param bool $selectContent
     * @return bool|mixed|string|string[]|void|null
     */
    public static function custom_excerpt($post = false, $length = 100, $customContent = false, $selectContent = false) {
        if ($post) {
            $excerpt = get_post_field('post_content', $post);
        } else if ($customContent) {
            $excerpt = $customContent;
        } else {
            $excerpt = get_the_content();
        }
        if ($selectContent) {
            $excerpt = get_field($selectContent, $post);
        }
        $excerpt = preg_replace(" (\[.*?\])", '', $excerpt);
        $excerpt = strip_shortcodes($excerpt);
        $excerpt = strip_tags($excerpt);
        $excerpt = substr($excerpt, 0, $length);
        $excerpt = substr($excerpt, 0, strripos($excerpt, " "));
        $excerpt = trim(preg_replace('/\s+/', ' ', $excerpt));
        $excerpt .= '...';
        return $excerpt;
    }
}

add_action('wp_ajax_check_if_email_exists', ['Roots\Sage\Helpers', 'check_if_email_exists']);
add_action('wp_ajax_nopriv_check_if_email_exists', ['Roots\Sage\Helpers', 'check_if_email_exists']);
