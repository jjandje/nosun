<?php

namespace Vazquez\NosunAssumaxConnector\Api;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Vazquez\NosunAssumaxConnector\ClientIterator;

/**
 * Holds all functionality used to connect with the Assumax API.
 * An instance of the class is used to execute function of the API and can be obtained by using the getInstance()
 * static method.
 * This class implements the singleton design pattern and therefore only one client will ever be created during script
 * execution.
 *
 * @since       2.0.0
 * @package     Nosun_Assumax_Api
 * @subpackage  Nosun_Assumax_Api/includes
 * @author      Chris van Zanten <chris@vazquez.nl>
 */
class AssumaxClient {
    /** @var self $Client  */
    private static $Client = null;

    /**
     * API settings.
     */
	private $ApiId;
	private $ApiKey;
	private $ApiUrl;

    /**
     * Returns an instance of the client which can be used to execute functions on the Assumax API.
     * A new client instance will only be created the first time this function is called during script execution.
     * All subsequent call will return the previously created client instance.
     *
     * @return self A client object.
     * @throws Exception When one of the required options isn't set an Exception is thrown.
     */
    public static function getInstance() {
        if (empty(self::$Client)){
            self::$Client = new self();
        }
        return self::$Client;
    }

    /**
     * Client constructor.
     *
     * @throws Exception When one of the required options is missing.
     */
	private function __construct() {
        // Get the url, id and secret from the options.
        $url = get_field('assumax_api_url', 'options');
        $urlField = get_field_object('assumax_api_url', 'options');
        if (empty($url)) throw new Exception("No URL has been set for the Assumax API!");
        $this->ApiUrl = $urlField['choices'][$url];
        if (empty($this->ApiUrl)) throw new Exception("An empty URL has been set for the Assumax API!");
        // Check whether or not we need to get the live or the test server credentials.
        if ($url === 'Live') {
            $this->ApiId = get_field('assumax_id_live', 'options');
            $this->ApiKey = get_field('assumax_secret_live', 'options');
        } else if ($url === 'Test') {
            $this->ApiId = get_field('assumax_id_test', 'options');
            $this->ApiKey = get_field('assumax_secret_test', 'options');
        }
        if (empty($this->ApiId) || empty($this->ApiKey)) throw new Exception("Either the ID or the Secret is empty for the Assumax API.");
	}

    /**
     * Creates a authentication hash string using the client Api Key and a supplied uri and optional data array.
     *
     * @param string $method The http method used.
     * @param string $uri The API endpoint uri.
     * @param string $dateTime The current date string in UTC Zulu time format.
     * @param array $data Key/value array of data elements.
     * @return string The authentication hash or null if something went wrong.
     */
    private function create_authentication_hash($method, $uri, $dateTime, $data = []) {
        if (empty($uri)) return null;
        $key = strtoupper($this->ApiKey);
        $parameters = [$method, $dateTime, $uri, urldecode(http_build_query($data, '', '&'))];
        $parameterString = implode(PHP_EOL, $parameters);
        $hash = hash_hmac('sha256', $parameterString, $key, true);
        return base64_encode($hash);
    }

    /**
     * Sends a new request with a method as provided to the Assumax API.
     * The return value can be json decoded and will be by default.
     *
     * @param string $method The http method used. GET, POST or PUT.
     * @param string $uri The API endpoint uri.
     * @param array $data Key/value array of data elements.
     * @param bool $jsonDecode Whether or not to json decode the output string before returning it.
     * @param bool $debug Whether or not to run the Guzzle request in debug mode.
     * @return mixed | false The content string if jsonDecode is false, an object/array if jsonDecode is true and
     * the boolean value false should something go wrong.
     */
    public function request($method, $uri, $data = [], $jsonDecode = true, $debug = false) {
        if (!($method === 'GET' || $method === 'POST' || $method === 'PUT' || $method === 'DELETE') || empty($uri)) return false;
        // Make sure the uri starts with a forward slash.
        if (strpos($uri, '/') !== 0) {
            $uri = '/' . $uri;
        }
        $dateTime = str_replace('+0000', '.000Z', date(DATE_ISO8601));
        // Add an uuid to the data to prevent authorization issues.
        $uuid4 = wp_generate_uuid4();
        $data["uuid"] = $uuid4;
        // Generate the authentication hash,
        $authenticationHash = $this->create_authentication_hash($method, $uri, $dateTime, $data);
        if (empty($authenticationHash)) return false;
        // Create the Guzzle Client.
        $tempFile = false;
        if ($debug) {
            $tempFilePath = plugin_dir_path(__FILE__) . "temp/" . wp_generate_uuid4() . ".log";
            $tempFile = fopen($tempFilePath, "w");
        }
        $guzzleClient = new Client([
            'base_uri'      => $this->ApiUrl,
            'debug'         => $debug ? $tempFile : false,
            'http_errors'   => false
        ]);
        // Create the request parameters.
        $request = [
            'headers' => [
                'Authentication' => $this->ApiId . ':' . $authenticationHash,
                'Timestamp'      => $dateTime
            ],
            'synchronous'     => 1,
            'connect_timeout' => 10,
            'delay'           => 10
        ];
        if ($method === 'GET') $request['query'] = $data;
        else $request['form_params'] = $data;
        // Send the request.
        try {
            $response = $guzzleClient->request($method, $uri, $request);
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200 && $statusCode !== 201) {
                error_log("[Nosun_Assumax_Api_Client->request]: Received a {$statusCode} status code for the request with uri: {$uri}.");
                error_log($response->getBody());
                return false;
            }
            // Return the response either json decoded or plain.
            if ($jsonDecode) return json_decode($response->getBody());
            else return $response->getBody();
        } catch (GuzzleException $e) {
            error_log("[Nosun_Assumax_Api_Client->request]: An exception occurred while sending the request with uri: {$uri} to the API.\n{$e->getMessage()}");
            return false;
        }
    }

    /**
     * Sends a GET request to the API using the provided uri and data.
     * The response will be json decoded or not depending on the jsonDecode boolean.
     *
     * @param string $uri The API endpoint uri.
     * @param array $data Key/value array of data elements.
     * @param bool $jsonDecode Whether or not to json decode the output string before returning it.
     * @param bool $debug Whether or not to run the Guzzle request in debug mode.
     * @return mixed | false The content string if jsonDecode is false, an object/array if jsonDecode is true and
     * the boolean value false should something go wrong.
     */
    public function get($uri, $data = [], $jsonDecode = true, $debug = false) {
        return $this->request('GET', $uri, $data, $jsonDecode, $debug);
    }

    /**
     * Obtains all the results form an API point that returns an array of elements.
     * The resulting ClientIterator will obtain new (paginated) results when the resultset is bigger than the PAGE_SIZE
     * constant.
     * The content is always json decoded and debug mode is not possible unlike a normal get operation.
     *
     * @param string $uri The API endpoint uri.
     * @param array $data Key/value array of data elements.
     * @param int $offset The offset from which to start.
     * @return ClientIterator A ClientIterator which pulls extra elements from the API when needed.
     */
    public function get_all($uri, $data = [], int $offset = 0) {
        return new ClientIterator($this, $uri, $data, $offset);
    }

    /**
     * Sends a POST request to the API using the provided uri and data.
     * The response will be json decoded or not depending on the jsonDecode boolean.
     *
     * @param string $uri The API endpoint uri.
     * @param array $data Key/value array of data elements.
     * @param bool $jsonDecode Whether or not to json decode the output string before returning it.
     * @param bool $debug Whether or not to run the Guzzle request in debug mode.
     * @return mixed | false The content string if jsonDecode is false, an object/array if jsonDecode is true and
     * the boolean value false should something go wrong.
     */
    public function post($uri, $data = [], $jsonDecode = true, $debug = false) {
        return $this->request('POST', $uri, $data, $jsonDecode, $debug);
    }

    /**
     * Sends a PUT request to the API using the provided uri and data.
     * The response will be json decoded or not depending on the jsonDecode boolean.
     *
     * @param string $uri The API endpoint uri.
     * @param array $data Key/value array of data elements.
     * @param bool $jsonDecode Whether or not to json decode the output string before returning it.
     * @param bool $debug Whether or not to run the Guzzle request in debug mode.
     * @return mixed | false The content string if jsonDecode is false, an object/array if jsonDecode is true and
     * the boolean value false should something go wrong.
     */
    public function put($uri, $data = [], $jsonDecode = true, $debug = false) {
        return $this->request('PUT', $uri, $data, $jsonDecode, $debug);
    }

    /**
     * Sends a DELETE request to the API using the provided uri and data.
     * The response will be json decoded or not depending on the jsonDecode boolean.
     *
     * @param string $uri The API endpoint uri.
     * @param array $data Key/value array of data elements.
     * @param bool $jsonDecode Whether or not to json decode the output string before returning it.
     * @param bool $debug Whether or not to run the Guzzle request in debug mode.
     * @return mixed | false The content string if jsonDecode is false, an object/array if jsonDecode is true and
     * the boolean value false should something go wrong.
     */
    public function delete($uri, $data = [], $jsonDecode = true, $debug = false) {
        return $this->request('DELETE', $uri, $data, $jsonDecode, $debug);
    }
}
