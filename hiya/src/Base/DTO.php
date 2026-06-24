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

class DTO implements JsonSerializable
{
    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $property->getValue($this);

            if ($value instanceof DTO) {
                $data[$name] = $value->toArray();
            } elseif (is_array($value)) {
                $data[$name] = array_map(function($item) {
                    return $item instanceof DTO ? $item->toArray() : $item;
                }, $value);
            } else {
                $data[$name] = $value;
            }
        }

        return $data;
    }

    /**
     * Convert DTO to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * JsonSerializable implementation
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): static
    {
        $dto = new static();
        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }
        return $dto;
    }

    /**
     * Create DTO from model
     */
    public static function fromModel($model): static
    {
        $dto = new static();
        foreach (get_object_vars($model) as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }
        return $dto;
    }

    /**
     * Create collection from array
     */
    public static function collection(array $data): array
    {
        return array_map(function($item) {
            return static::fromArray($item);
        }, $data);
    }

    /**
     * Magic toString
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}