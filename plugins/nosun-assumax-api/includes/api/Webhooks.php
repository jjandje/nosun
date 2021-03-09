<?php

namespace Vazquez\NosunAssumaxConnector\Api;

use WP_REST_Request;

/**
 * Holds all the functionality used by the webhooks.
 *
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Webhooks implements ILoadable {
    /**
     * Main entrypoint for the webhooks.
     * Checks if all the required fields are set and then acquires a lock before calling the correct method.
     * Releases the lock after the method has been executed.
     *
     * @param WP_REST_Request $request The request object send by the hook.
     * @return string Always an empty string.
     */
    public static function webhook_callback(WP_REST_Request $request) {
        $body = $request->get_body();
        if (!empty($body)) {
            $decoded = json_decode($body);
            if (!empty($decoded)) {
                // Check if all the required fields are set and that Item equals one of the supported resources.
                if (empty($decoded->Id) || empty($decoded->Action) || empty($decoded->Item)) {
                    error_log("[Webhooks->webhook_callback]: Not all the required fields are set.");
                    error_log($body);
                    return "";
                }
                // Acquire a lock on the id and resource and return if it cannot be acquired.
                $id = Locks::acquire_lock($decoded->Id, $decoded->Item, $decoded->Action);
                if ($id <= 0) return "";
                switch ($decoded->Item) {
                    case "Template": {
                        Templates::webhook_callback($decoded->Id, $decoded->Action);
                        break;
                    }
                    case "Trip": {
                        Trips::webhook_callback($decoded->Id, $decoded->Action);
                        break;
                    }
                    case "Booking": {
                        Bookings::webhook_callback($decoded->Id, $decoded->Action);
                        break;
                    }
                    case "Customer": {
                        Customers::webhook_callback($decoded->Id, $decoded->Action);
                        break;
                    }
                    case "Accommodation": {
                        Accommodations::webhook_callback($decoded->Id, $decoded->Action);
                        break;
                    }
                    case "Guide": {
                        Guides::webhook_callback($decoded->Id, $decoded->Action);
                        break;
                    }
	                case "EMail": {
	                	Emails::webhook_callback( $decoded->Id, $decoded->Action);
	                	break;
	                }
                    default: {
                        error_log("[Webhooks->webhook_callback]: The supplied Item: {$decoded->Item} is not supported.");
                        break;
                    }
                }
                // Release the lock.
                Locks::release_lock($id);
            }
        }
    return "";
    }

    /**
     * Obtains locks for webhooks that have been queued and executes them one by one.
     */
    public static function advance_webhook_locks() {
        $locks = Locks::get_next_queued_locks();
        if (empty($locks)) return;
        foreach ($locks as $key => $lock) {
            // Check the resource type and pick the correct webhook callback to execute.
            switch ($lock->resource) {
                case "Template": { Templates::webhook_callback($lock->assumax_id, $lock->api_action); break; }
                case "Trip": { Trips::webhook_callback($lock->assumax_id, $lock->api_action); break; }
                case "Booking": { Bookings::webhook_callback($lock->assumax_id, $lock->api_action); break; }
                case "Customer": { Customers::webhook_callback($lock->assumax_id, $lock->api_action); break; }
                case "Accommodation": { Accommodations::webhook_callback($lock->assumax_id, $lock->api_action); break; }
                case "Guide": { Guides::webhook_callback($lock->assumax_id, $lock->api_action); break; }
	            case "EMail": { Emails::webhook_callback( $lock->assumax_id, $lock->api_section); break; }
                default: {
                    error_log("[Webhooks->execute_queued_webhooks]: There is no valid callback for the resource with type: {$lock->resource}.");
                    break;
                }
            }
            // Release the lock.
            Locks::release_lock($lock->id);
        }
    }

    /**
     * Sets up the webhook endpoints and saves the route uri's to the definedRoutes class property.
     */
    public static function setup_webhook_endpoints() {
        register_rest_route("nosun/v1", '/webhook', [
            'methods' => 'POST',
            'callback' => ['\Vazquez\NosunAssumaxConnector\Api\Webhooks', 'webhook_callback']
        ]);
    }

    /**
     * Hides the webhook endpoint from the public.
     *
     * @param $routes
     * @return mixed
     */
    public static function filter_rest_endpoints($routes) {
        if (key_exists("/nosun/v1/webhook", $routes)) unset($routes["/nosun/v1/webhook"]);
        return $routes;
    }

    /**
     * Runs an action that adds all webhooks to the API. Should only be run once.
     */
    public static function add_webhook_endpoint_to_api() : void
    {
        if (get_option('vazquez_webhooks_setup') === '1') {
            return;
        }
        update_option('vazquez_webhooks_setup', '1', true);
        try {
            $client = AssumaxClient::getInstance();
            // First delete all the old webhooks to be sure we have no double executions.
            $currentWebhooks = $client->get('/webhooks');
            $error = false;
            if (!empty($currentWebhooks)) {
                foreach ($currentWebhooks as $webhook) {
                    $result = $client->delete("/webhooks/{$webhook->Id}", false);
                    if ($result === false) {
                        error_log("[Webhooks->add_webhook_endpoint_to_api]: Could not delete the webhook with Id: {$webhook->Id}.");
                        $error = true;
                    }
                }
            }
            // Add the new webhooks.
            if (!$error) {
                $url = get_site_url(null, "wp-json/nosun/v1/webhook");
                do_action('vazquez_webhooks_setup', $client, $url);
            }
        } catch (\Exception $e) {
            error_log("[Webhooks->add_webhook_endpoint_to_api]: Could not obtain a client instance.");
        }
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_filter('rest_route_data', [self::class, 'filter_rest_endpoints']);
        $loader->add_action('rest_api_init', [self::class, 'setup_webhook_endpoints']);
        $loader->add_action('advance_webhook_locks_event', [self::class, 'advance_webhook_locks']);
        $loader->add_action('init', [self::class, 'add_webhook_endpoint_to_api']);
    }
}
