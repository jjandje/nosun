<?php

namespace Vazquez\NosunAssumaxConnector;

use Iterator;
use Vazquez\NosunAssumaxConnector\Api\AssumaxClient;

/**
 *
 *
 * Class ClientIterator
 * @package Vazquez\NosunAssumaxConnector
 */
class ClientIterator implements Iterator {
    /**
     * The Assumax API limits requests to 200 max by default.
     */
    const PAGE_SIZE = 200;

    protected $elements;
    protected $baseOffset;
    protected $offset;
    protected $position;
    protected $client;
    protected $endpoint;
    protected $size;
    protected $data;

    /**
     * Constructs a new ClientIterator that sets up the default state.
     *
     * @param AssumaxClient $client A client object to use for the API calls.
     * @param string $endpoint The API endpoint from which to obtain the elements.
     * @param array $data A set of query data parameters that need to always be send to the API.
     * @param int $offset The offset from which to start.
     */
    public function __construct(AssumaxClient $client, string $endpoint, array $data = [], int $offset = 0) {
        $this->elements = [];
        $this->baseOffset = $offset;
        $this->offset = $this->baseOffset;
        $this->position = 0;
        $this->client = $client;
        $this->endpoint = $endpoint;
        $this->size = -1;
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function current() {
        return $this->elements[$this->position];
    }

    /**
     * @inheritDoc
     */
    public function next() {
        $this->position++;
        if ($this->position === $this->size && $this->size % self::PAGE_SIZE === 0) {
            $this->obtain_from_api();
        }
    }

    /**
     * @inheritDoc
     */
    public function key() {
        return $this->position;
    }

    /**
     * @inheritDoc
     */
    public function valid() {
        return isset($this->elements[$this->position]);
    }

    /**
     * @inheritDoc
     */
    public function rewind() {
        if ($this->size < 0) {
            $this->renew_from_api();
        } else {
            $this->position = 0;
        }
    }

    /**
     * Obtains the current amount of elements in the Iterator.
     * This value will change depending on pagination.
     *
     * @return int The amount of elements in the Iterator at this point in time.
     */
    public function get_size() {
        return $this->size;
    }

    /**
     * Resets the Iterator position to 0 and retrieves a fresh set of elements from the API if available.
     */
    public function renew_from_api() {
        $this->position = 0;
        $this->offset = $this->baseOffset;
        $this->size = 0;
        $this->elements = [];
        $this->obtain_from_api();
    }

    /**
     * Obtains elements from the API with offset equal to the current size and limit equal to the PAGE_SIZE constant.
     */
    private function obtain_from_api() {
        $data = $this->data + ['offset' => $this->offset, 'limit' => self::PAGE_SIZE];
        $result = $this->client->get($this->endpoint, $data);
        if (!empty($result) && is_array($result)) {
            $this->elements = array_merge($this->elements, $result);
            $this->size = count($this->elements);
            $this->offset += count($result);
        }
    }
}