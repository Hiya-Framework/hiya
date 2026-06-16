<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Helpers\Collection
 * @since 1.0
 */

namespace Hiya\Helpers;

use Hiya\Base\Component;

class Collection extends Component implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @var array Items in collection
     */
    protected $items = [];
    
    /**
     * Constructor
     * 
     * @param array $items
     */
    public function __construct($items = [])
    {
        parent::__construct();
        $this->items = $this->getArrayableItems($items);
    }
    
    /**
     * Get arrayable items
     * 
     * @param mixed $items
     * @return array
     */
    protected function getArrayableItems($items): array
    {
        if (is_array($items)) {
            return $items;
        }
        
        if ($items instanceof \stdClass) {
            return (array) $items;
        }
        
        if ($items instanceof self) {
            return $items->all();
        }
        
        if (is_object($items) && method_exists($items, 'toArray')) {
            return $items->toArray();
        }
        
        return (array) $items;
    }
    
    /**
     * Get all items
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }
    
    /**
     * Map items using callback
     * 
     * @param callable $callback
     * @return self
     */
    public function map(callable $callback): self
    {
        return new static(array_map($callback, $this->items));
    }
    
    /**
     * Filter items using callback
     * 
     * @param callable $callback
     * @param int $flag
     * @return self
     */
    public function filter(callable $callback, int $flag = 0): self
    {
        return new static(array_filter($this->items, $callback, $flag));
    }
    
    /**
     * Pluck values by key
     * 
     * @param string $key
     * @param string|null $indexKey
     * @return self
     */
    public function pluck(string $key, ?string $indexKey = null): self
    {
        return new static(ArrayHelper::pluck($this->items, $key, $indexKey));
    }
    
    /**
     * Sort by key
     * 
     * @param string $key
     * @param int $direction
     * @return self
     */
    public function sort(string $key, int $direction = SORT_ASC): self
    {
        return new static(ArrayHelper::sort($this->items, $key, $direction));
    }
    
    /**
     * Group by key
     * 
     * @param string $key
     * @return self
     */
    public function groupBy(string $key): self
    {
        $grouped = [];
        foreach ($this->items as $item) {
            $groupKey = is_array($item) ? ($item[$key] ?? 'undefined') : ($item->$key ?? 'undefined');
            $grouped[$groupKey][] = $item;
        }
        return new static($grouped);
    }
    
    /**
     * Index by key
     * 
     * @param string $key
     * @return self
     */
    public function indexBy(string $key): self
    {
        $indexed = [];
        foreach ($this->items as $item) {
            $index = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
            if ($index !== null) {
                $indexed[$index] = $item;
            }
        }
        return new static($indexed);
    }
    
    /**
     * Get first item
     * 
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function first(?callable $callback = null, $default = null)
    {
        return ArrayHelper::first($this->items, $callback, $default);
    }
    
    /**
     * Get last item
     * 
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function last(?callable $callback = null, $default = null)
    {
        return ArrayHelper::last($this->items, $callback, $default);
    }
    
    /**
     * Count items
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
    
    /**
     * Check if empty
     * 
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }
    
    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->items;
    }
    
    /**
     * Convert to JSON
     * 
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->items, $options);
    }
    
    /**
     * Push item
     * 
     * @param mixed $value
     * @return self
     */
    public function push($value): self
    {
        $this->items[] = $value;
        return $this;
    }
    
    /**
     * Prepend item
     * 
     * @param mixed $value
     * @param string|null $key
     * @return self
     */
    public function prepend($value, ?string $key = null): self
    {
        if ($key !== null) {
            $this->items = [$key => $value] + $this->items;
        } else {
            array_unshift($this->items, $value);
        }
        return $this;
    }
    
    /**
     * Unique items
     * 
     * @param string|null $key
     * @return self
     */
    public function unique(?string $key = null): self
    {
        return new static(ArrayHelper::unique($this->items, $key));
    }
    
    /**
     * Flatten array
     * 
     * @param float|int $depth Maximum depth to flatten (use INF for infinite)
     * @return self
     */
    public function flatten($depth = INF): self
    {
        return new static(ArrayHelper::flatten($this->items, $depth));
    }
    
    /**
     * Only specified keys
     * 
     * @param array $keys
     * @return self
     */
    public function only(array $keys): self
    {
        $result = [];
        foreach ($this->items as $item) {
            $result[] = ArrayHelper::only($item, $keys);
        }
        return new static($result);
    }
    
    /**
     * Except specified keys
     * 
     * @param array $keys
     * @return self
     */
    public function except(array $keys): self
    {
        $result = [];
        foreach ($this->items as $item) {
            $result[] = ArrayHelper::except($item, $keys);
        }
        return new static($result);
    }
    
    /**
     * Sum values
     * 
     * @param string|null $key
     * @return float
     */
    public function sum(?string $key = null): float
    {
        if ($key === null) {
            return (float) array_sum($this->items);
        }
        return (float) array_sum(array_column($this->items, $key));
    }
    
    /**
     * Average values
     * 
     * @param string|null $key
     * @return float
     */
    public function avg(?string $key = null): float
    {
        $sum = $this->sum($key);
        $count = $this->count();
        return $count > 0 ? $sum / $count : 0;
    }
    
    /**
     * Min value
     * 
     * @param string|null $key
     * @return mixed
     */
    public function min(?string $key = null)
    {
        if ($key === null) {
            return min($this->items);
        }
        return min(array_column($this->items, $key));
    }
    
    /**
     * Max value
     * 
     * @param string|null $key
     * @return mixed
     */
    public function max(?string $key = null)
    {
        if ($key === null) {
            return max($this->items);
        }
        return max(array_column($this->items, $key));
    }
    
    /**
     * Get random item
     * 
     * @param int $number
     * @return mixed|array
     */
    public function random(int $number = 1)
    {
        return ArrayHelper::random($this->items, $number);
    }
    
    /**
     * Shuffle items
     * 
     * @return self
     */
    public function shuffle(): self
    {
        return new static(ArrayHelper::shuffle($this->items));
    }
    
    // ============ ArrayAccess Implementation ============
    
    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }
    
    public function offsetGet($offset): mixed
    {
        return $this->items[$offset] ?? null;
    }
    
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }
    
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }
    
    // ============ IteratorAggregate Implementation ============
    
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}