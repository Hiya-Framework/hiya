<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Base;

use JsonSerializable;

class ResourceCollection implements JsonSerializable
{
    protected $collection;
    protected $collects;
    protected $additional = [];
    protected $with = [];
    protected $preserveKeys = false;

    public function __construct($collection, $collects = null)
    {
        $this->collection = $collection;
        $this->collects = $collects ?: get_class($this);
    }

    /**
     * Transform collection to array
     */
    public function toArray(): array
    {
        $data = [];
        foreach ($this->collection as $key => $item) {
            $resource = new $this->collects($item);
            $data[$key] = $resource->resolve();
        }

        return $this->preserveKeys ? $data : array_values($data);
    }

    /**
     * Resolve collection
     */
    public function resolve(): array
    {
        $result = [
            'data' => $this->toArray(),
        ];

        if (!empty($this->with)) {
            $result['meta'] = $this->with;
        }

        return $result;
    }

    /**
     * Add additional data
     */
    public function additional(array $data): self
    {
        $this->additional = $data;
        return $this;
    }

    /**
     * Add meta data
     */
    public function with(array $data): self
    {
        $this->with = $data;
        return $this;
    }

    /**
     * Preserve keys
     */
    public function preserveKeys(bool $preserve = true): self
    {
        $this->preserveKeys = $preserve;
        return $this;
    }

    /**
     * Add pagination meta (Laravel-style)
     */
    public function paginate(int $total, int $perPage, int $currentPage): self
    {
        $lastPage = (int) ceil($total / $perPage);

        $this->with['pagination'] = [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'from' => (($currentPage - 1) * $perPage) + 1,
            'to' => min($currentPage * $perPage, $total),
            'next_page_url' => $currentPage < $lastPage ? $this->getPageUrl($currentPage + 1) : null,
            'prev_page_url' => $currentPage > 1 ? $this->getPageUrl($currentPage - 1) : null,
        ];

        return $this;
    }

    /**
     * Get page URL
     */
    protected function getPageUrl(int $page): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        // Remove existing page param
        $uri = preg_replace('/[?&]page=\d+/', '', $uri);
        $separator = strpos($uri, '?') === false ? '?' : '&';
        return $uri . $separator . 'page=' . $page;
    }

    /**
     * JsonSerializable implementation
     */
    public function jsonSerialize(): array
    {
        return $this->resolve();
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->resolve(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Magic toString
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}