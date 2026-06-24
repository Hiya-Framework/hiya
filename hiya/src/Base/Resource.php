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

abstract class Resource implements JsonSerializable
{
    protected $resource;
    protected $additional = [];
    protected $with = [];

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transform resource to array
     */
    abstract public function toArray(): array;

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
     * Resolve resource to array
     */
    public function resolve(): array
    {
        $data = $this->toArray();

        if (!empty($this->additional)) {
            $data = array_merge($data, $this->additional);
        }

        return $data;
    }

    /**
     * Get meta data
     */
    public function getMeta(): array
    {
        return $this->with;
    }

    /**
     * JsonSerializable implementation
     */
    public function jsonSerialize(): array
    {
        return $this->resolve();
    }

    /**
     * Create collection
     */
    public static function collection($resources): ResourceCollection
    {
        return new ResourceCollection($resources, static::class);
    }

    /**
     * Create single resource
     */
    public static function make($resource): self
    {
        return new static($resource);
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