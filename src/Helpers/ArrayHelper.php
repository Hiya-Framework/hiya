<?php
/*
 * @author Hermans <github.com/hermans>
 * @copyright (c) taktikspace.com
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Helpers
 * @since 1.0
 */

namespace Hiya\Helpers;

/**
 * ArrayHelper - Array manipulation utilities for Hiya Framework
 * 
 * Provides convenient methods for working with arrays using dot notation,
 * filtering, mapping, sorting, and more.
 * 
 * @package Hiya\Helpers
 * @since 1.0
 * 
 * @example
 * // Basic usage examples
 * $data = [
 *     'user' => [
 *         'name' => 'John Doe',
 *         'email' => 'john@example.com',
 *         'profile' => [
 *             'age' => 30,
 *             'city' => 'Jakarta'
 *         ]
 *     ],
 *     'products' => [
 *         ['id' => 1, 'name' => 'Laptop', 'price' => 1000],
 *         ['id' => 2, 'name' => 'Mouse', 'price' => 25],
 *         ['id' => 3, 'name' => 'Keyboard', 'price' => 75],
 *     ]
 * ];
 * 
 * // Get values using dot notation
 * $name = ArrayHelper::get($data, 'user.name'); // 'John Doe'
 * $city = ArrayHelper::get($data, 'user.profile.city'); // 'Jakarta'
 * $age = ArrayHelper::get($data, 'user.profile.age', 25); // 30
 * 
 * // Set values using dot notation
 * ArrayHelper::set($data, 'user.profile.country', 'Indonesia');
 * 
 * // Check if keys exist
 * $hasEmail = ArrayHelper::has($data, 'user.email'); // true
 * $hasPhone = ArrayHelper::has($data, 'user.phone'); // false
 * 
 * // Pluck values from array of arrays
 * $productNames = ArrayHelper::pluck($data['products'], 'name'); 
 * // ['Laptop', 'Mouse', 'Keyboard']
 * 
 * $productsById = ArrayHelper::index($data['products'], 'id');
 * // [1 => ['id' => 1, 'name' => 'Laptop'], ...]
 * 
 * // Map to key-value pairs
 * $productMap = ArrayHelper::map($data['products'], 'id', 'name');
 * // [1 => 'Laptop', 2 => 'Mouse', 3 => 'Keyboard']
 * 
 * // Filter array
 * $expensive = ArrayHelper::where($data['products'], function($item) {
 *     return $item['price'] > 50;
 * });
 * 
 * // Sort array
 * $sorted = ArrayHelper::sort($data['products'], 'price', SORT_DESC);
 * 
 * // Dot notation conversion
 * $flat = ArrayHelper::dot($data);
 * // ['user.name' => 'John Doe', 'user.email' => 'john@example.com', ...]
 * 
 * // Only keep specific keys
 * $only = ArrayHelper::only($data, ['user.name', 'user.email']);
 * 
 * // Remove specific keys
 * $except = ArrayHelper::except($data, ['user.profile']);
 * 
 * // Get unique values
 * $unique = ArrayHelper::unique($data['products'], 'name');
 * 
 * // Get first element matching condition
 * $first = ArrayHelper::first($data['products'], function($item) {
 *     return $item['price'] > 50;
 * });
 */
class ArrayHelper
{
    /**
     * Get a value from array using dot notation
     * 
     * @param array $array The source array
     * @param string|array $key The key path (e.g., 'user.name' or ['user', 'name'])
     * @param mixed $default Default value if key not found
     * @return mixed The value or default
     * 
     * @example
     *   ArrayHelper::get($data, 'user.name', 'Guest')
     *   ArrayHelper::get($data, ['user', 'profile', 'city'], 'Unknown')
     */
    public static function get($array, $key, $default = null)
    {
        if (!is_array($array) || $key === null) {
            return self::value($default);
        }
        
        if (is_array($key)) {
            $key = implode('.', $key);
        }
        
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return self::value($default);
            }
            $array = $array[$segment];
        }
        
        return $array;
    }
    
    /**
     * Set a value in array using dot notation
     * 
     * @param array $array The source array (passed by reference)
     * @param string $key The key path (e.g., 'user.name')
     * @param mixed $value The value to set
     * @return array The modified array
     * 
     * @example
     *   ArrayHelper::set($data, 'user.profile.country', 'Indonesia');
     *   // ['user' => ['profile' => ['country' => 'Indonesia']]]
     */
    public static function set(&$array, $key, $value)
    {
        if ($key === null) {
            return $array = $value;
        }
        
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            
            $array = &$array[$key];
        }
        
        $array[array_shift($keys)] = $value;
        
        return $array;
    }
    
    /**
     * Check if key exists using dot notation
     * 
     * @param array $array The source array
     * @param string|array $keys The key path(s) to check
     * @return bool True if all keys exist
     * 
     * @example
     *   ArrayHelper::has($data, 'user.name'); // true
     *   ArrayHelper::has($data, ['user.name', 'user.email']); // true
     *   ArrayHelper::has($data, 'user.phone'); // false
     */
    public static function has($array, $keys)
    {
        if (empty($array) || $keys === null) {
            return false;
        }
        
        $keys = is_array($keys) ? $keys : [$keys];
        
        foreach ($keys as $key) {
            if (!self::hasKey($array, $key)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if single key exists
     * 
     * @param array $array
     * @param string $key
     * @return bool
     */
    protected static function hasKey($array, $key)
    {
        if (array_key_exists($key, $array)) {
            return true;
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }
        
        return true;
    }
    
    /**
     * Remove a value from array and return it
     * 
     * @param array $array The source array (passed by reference)
     * @param string $key The key path
     * @param mixed $default Default value if key not found
     * @return mixed The removed value or default
     * 
     * @example
     *   $name = ArrayHelper::pull($data, 'user.name'); // 'John Doe'
     *   // 'user.name' is removed from $data
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = self::get($array, $key, $default);
        self::forget($array, $key);
        return $value;
    }
    
    /**
     * Remove key from array using dot notation
     * 
     * @param array $array The source array (passed by reference)
     * @param string|array $keys The key path(s) to remove
     * 
     * @example
     *   ArrayHelper::forget($data, 'user.profile');
     *   // Removes 'profile' from 'user'
     *   
     *   ArrayHelper::forget($data, ['user.name', 'user.email']);
     *   // Removes both 'name' and 'email' from 'user'
     */
    public static function forget(&$array, $keys)
    {
        $keys = is_array($keys) ? $keys : [$keys];
        
        foreach ($keys as $key) {
            if ($key === null) {
                continue;
            }
            
            $keyParts = explode('.', $key);
            $arrayRef = &$array;
            
            while (count($keyParts) > 1) {
                $part = array_shift($keyParts);
                
                if (!isset($arrayRef[$part]) || !is_array($arrayRef[$part])) {
                    continue 2;
                }
                
                $arrayRef = &$arrayRef[$part];
            }
            
            unset($arrayRef[array_shift($keyParts)]);
        }
    }
    
    /**
     * Remove and return value by key (simple removal)
     * 
     * @param array $array The source array (passed by reference)
     * @param string $key The key to remove
     * @param mixed $default Default value if key not found
     * @return mixed The removed value or default
     * 
     * @example
     *   $email = ArrayHelper::remove($data, 'email');
     *   // Removes 'email' from $data and returns it
     */
    public static function remove(&$array, $key, $default = null)
    {
        if (array_key_exists($key, $array)) {
            $value = $array[$key];
            unset($array[$key]);
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Get all values for a given key from array of arrays
     * 
     * @param array $array Array of arrays/objects
     * @param string $key The key to pluck
     * @param string|null $indexKey Optional key to use as index
     * @return array Plucked values
     * 
     * @example
     *   $names = ArrayHelper::pluck($users, 'name');
     *   // ['John', 'Jane', 'Bob']
     *   
     *   $namesById = ArrayHelper::pluck($users, 'name', 'id');
     *   // [1 => 'John', 2 => 'Jane', 3 => 'Bob']
     */
    public static function pluck($array, $key, $indexKey = null)
    {
        $results = [];
        
        foreach ($array as $item) {
            if (is_array($item)) {
                $value = self::get($item, $key);
                
                if ($indexKey !== null) {
                    $index = self::get($item, $indexKey);
                    $results[$index] = $value;
                } else {
                    $results[] = $value;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Index array by a key
     * 
     * @param array $array Array of arrays/objects
     * @param string $key The key to index by
     * @param string|null $groupKey Optional key to group by
     * @return array Indexed array
     * 
     * @example
     *   $usersById = ArrayHelper::index($users, 'id');
     *   // [1 => ['id' => 1, 'name' => 'John'], ...]
     *   
     *   $usersByRole = ArrayHelper::index($users, 'id', 'role');
     *   // ['admin' => [1 => [...], 2 => [...]], 'user' => [...]]
     */
    public static function index($array, $key, $groupKey = null)
    {
        $results = [];
        
        foreach ($array as $item) {
            if (is_array($item)) {
                $index = self::get($item, $key);
                
                if ($groupKey !== null) {
                    $group = self::get($item, $groupKey);
                    $results[$group][$index] = $item;
                } else {
                    $results[$index] = $item;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Map array with key-value pairs
     * 
     * @param array $array Array of arrays/objects
     * @param string $from Key to use as key
     * @param string $to Key to use as value
     * @param string|null $group Optional key to group by
     * @return array Mapped array
     * 
     * @example
     *   $map = ArrayHelper::map($users, 'id', 'name');
     *   // [1 => 'John', 2 => 'Jane', 3 => 'Bob']
     *   
     *   $grouped = ArrayHelper::map($users, 'id', 'name', 'role');
     *   // ['admin' => [1 => 'John'], 'user' => [2 => 'Jane']]
     */
    public static function map($array, $from, $to, $group = null)
    {
        $results = [];
        
        foreach ($array as $item) {
            if (is_array($item)) {
                $key = self::get($item, $from);
                $value = self::get($item, $to);
                
                if ($group !== null) {
                    $groupKey = self::get($item, $group);
                    $results[$groupKey][$key] = $value;
                } else {
                    $results[$key] = $value;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Filter array using callback
     * 
     * @param array $array The source array
     * @param callable $callback Filter callback
     * @param bool $preserveKeys Whether to preserve keys
     * @return array Filtered array
     * 
     * @example
     *   $activeUsers = ArrayHelper::where($users, function($user) {
     *       return $user['active'] === true;
     *   });
     */
    public static function where($array, $callback, $preserveKeys = false)
    {
        return array_filter($array, $callback, $preserveKeys ? ARRAY_FILTER_USE_BOTH : 0);
    }
    
    /**
     * Sort array by a key
     * 
     * @param array $array The source array (passed by reference)
     * @param string $key The key to sort by
     * @param int $direction SORT_ASC or SORT_DESC
     * @param int $sortFlag Sorting flags
     * @return array Sorted array
     * 
     * @example
     *   $sorted = ArrayHelper::sort($users, 'name', SORT_ASC);
     *   $sorted = ArrayHelper::sort($users, 'age', SORT_DESC);
     */
    public static function sort($array, $key, $direction = SORT_ASC, $sortFlag = SORT_REGULAR)
    {
        usort($array, function($a, $b) use ($key, $direction) {
            $aValue = is_array($a) ? self::get($a, $key) : (is_object($a) ? $a->$key : $a);
            $bValue = is_array($b) ? self::get($b, $key) : (is_object($b) ? $b->$key : $b);
            
            if ($aValue == $bValue) {
                return 0;
            }
            
            $result = ($aValue < $bValue) ? -1 : 1;
            return $direction === SORT_DESC ? -$result : $result;
        });
        
        return $array;
    }
    
    /**
     * Check if array is associative
     * 
     * @param array $array The array to check
     * @return bool True if associative
     * 
     * @example
     *   ArrayHelper::isAssoc(['a' => 1, 'b' => 2]); // true
     *   ArrayHelper::isAssoc([1, 2, 3]); // false
     */
    public static function isAssoc($array)
    {
        if (!is_array($array) || $array === []) {
            return false;
        }
        
        return array_keys($array) !== range(0, count($array) - 1);
    }
    
    /**
     * Convert array to dot notation
     * 
     * @param array $array The source array
     * @param string $prefix Optional prefix
     * @return array Flattened array with dot notation
     * 
     * @example
     *   $flat = ArrayHelper::dot(['user' => ['name' => 'John', 'age' => 30]]);
     *   // ['user.name' => 'John', 'user.age' => 30]
     */
    public static function dot($array, $prefix = '')
    {
        $results = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, self::dot($value, $newKey));
            } else {
                $results[$newKey] = $value;
            }
        }
        
        return $results;
    }
    
    /**
     * Expand dot notation to array
     * 
     * @param array $array Dot notation array
     * @return array Expanded nested array
     * 
     * @example
     *   $expanded = ArrayHelper::undot(['user.name' => 'John', 'user.age' => 30]);
     *   // ['user' => ['name' => 'John', 'age' => 30]]
     */
    public static function undot($array)
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            self::set($result, $key, $value);
        }
        
        return $result;
    }
    
    /**
     * Merge arrays recursively
     * 
     * @param array $array1 First array
     * @param array $array2 Second array
     * @return array Merged array
     * 
     * @example
     *   $merged = ArrayHelper::merge(['a' => 1], ['b' => 2, 'a' => 3]);
     *   // ['a' => 3, 'b' => 2]
     */
    public static function merge($array1, $array2)
    {
        $merged = $array1;
        
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::merge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }
    
    /**
     * Get unique values from array
     * 
     * @param array $array The source array
     * @param string|null $key Optional key to compare
     * @return array Unique values
     * 
     * @example
     *   $unique = ArrayHelper::unique($users, 'email');
     */
    public static function unique($array, $key = null)
    {
        if ($key === null) {
            return array_unique($array);
        }
        
        $seen = [];
        $result = [];
        
        foreach ($array as $item) {
            $value = is_array($item) ? self::get($item, $key) : (is_object($item) ? $item->$key : $item);
            
            if (!in_array($value, $seen)) {
                $seen[] = $value;
                $result[] = $item;
            }
        }
        
        return $result;
    }
    
    /**
     * Flatten array to single dimension
     * 
     * @param array $array The source array
     * @param int $depth Maximum depth to flatten
     * @return array Flattened array
     * 
     * @example
     *   $flat = ArrayHelper::flatten([[1, 2], [3, 4]]);
     *   // [1, 2, 3, 4]
     */
    public static function flatten($array, $depth = INF)
    {
        $result = [];
        
        foreach ($array as $item) {
            if (is_array($item) && $depth > 0) {
                $result = array_merge($result, self::flatten($item, $depth - 1));
            } else {
                $result[] = $item;
            }
        }
        
        return $result;
    }
    
    /**
     * Only keep specified keys
     * 
     * @param array $array The source array
     * @param array $keys Keys to keep
     * @return array Filtered array
     * 
     * @example
     *   $only = ArrayHelper::only($user, ['name', 'email']);
     *   // ['name' => 'John', 'email' => 'john@example.com']
     */
    public static function only($array, $keys)
    {
        $result = [];
        $keys = is_array($keys) ? $keys : func_get_args();
        array_shift($keys); // Remove first argument (array)
        
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                $result[$key] = $array[$key];
            }
        }
        
        return $result;
    }
    
    /**
     * Remove specified keys
     * 
     * @param array $array The source array
     * @param array $keys Keys to remove
     * @return array Filtered array
     * 
     * @example
     *   $except = ArrayHelper::except($user, ['password', 'token']);
     *   // Returns user array without password and token
     */
    public static function except($array, $keys)
    {
        $result = $array;
        $keys = is_array($keys) ? $keys : func_get_args();
        array_shift($keys); // Remove first argument (array)
        
        foreach ($keys as $key) {
            unset($result[$key]);
        }
        
        return $result;
    }
    
    /**
     * Prepend value to array
     * 
     * @param array $array The source array
     * @param mixed $value Value to prepend
     * @param string|null $key Optional key for associative array
     * @return array Modified array
     * 
     * @example
     *   $array = ArrayHelper::prepend([2, 3], 1);
     *   // [1, 2, 3]
     *   
     *   $array = ArrayHelper::prepend(['b' => 2], 1, 'a');
     *   // ['a' => 1, 'b' => 2]
     */
    public static function prepend($array, $value, $key = null)
    {
        if ($key === null) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }
        
        return $array;
    }
    
    /**
     * Get first element matching callback
     * 
     * @param array $array The source array
     * @param callable|null $callback Filter callback
     * @param mixed $default Default value if not found
     * @return mixed The first matching value
     * 
     * @example
     *   $first = ArrayHelper::first($users, function($user) {
     *       return $user['active'] === true;
     *   });
     */
    public static function first($array, $callback = null, $default = null)
    {
        if ($callback === null) {
            return empty($array) ? self::value($default) : reset($array);
        }
        
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        
        return self::value($default);
    }
    
    /**
     * Get last element matching callback
     * 
     * @param array $array The source array
     * @param callable|null $callback Filter callback
     * @param mixed $default Default value if not found
     * @return mixed The last matching value
     * 
     * @example
     *   $last = ArrayHelper::last($users, function($user) {
     *       return $user['active'] === true;
     *   });
     */
    public static function last($array, $callback = null, $default = null)
    {
        if ($callback === null) {
            return empty($array) ? self::value($default) : end($array);
        }
        
        return self::first(array_reverse($array, true), $callback, $default);
    }
    
    /**
     * Get value by path with default
     * 
     * @param array $array The source array
     * @param string|array $path The path to the value
     * @param mixed $default Default value
     * @return mixed The value or default
     * 
     * @example
     *   $name = ArrayHelper::getValue($data, 'user.name', 'Guest');
     *   $city = ArrayHelper::getValue($data, ['user', 'profile', 'city']);
     */
    public static function getValue($array, $path, $default = null)
    {
        return self::get($array, $path, $default);
    }
    
    /**
     * Check if array is indexed (sequential)
     * 
     * @param array $array The array to check
     * @return bool True if indexed
     * 
     * @example
     *   ArrayHelper::isIndexed([1, 2, 3]); // true
     *   ArrayHelper::isIndexed(['a' => 1, 'b' => 2]); // false
     */
    public static function isIndexed($array)
    {
        if (!is_array($array) || $array === []) {
            return false;
        }
        
        return array_keys($array) === range(0, count($array) - 1);
    }
    
    /**
     * Convert mixed data to array
     * 
     * @param mixed $data The data to convert
     * @return array The converted array
     * 
     * @example
     *   $array = ArrayHelper::toArray($object);
     *   $array = ArrayHelper::toArray('string'); // ['string']
     */
    public static function toArray($data)
    {
        if (is_array($data)) {
            return $data;
        }
        
        if (is_object($data)) {
            return (array) $data;
        }
        
        return [$data];
    }
    
    /**
     * Get value or evaluate default if closure
     * 
     * @param mixed $value The value or closure
     * @return mixed The value or evaluated closure
     */
    protected static function value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }
    
    /**
     * Swap keys and values
     * 
     * @param array $array The source array
     * @return array Flipped array
     * 
     * @example
     *   $flipped = ArrayHelper::flip(['a' => 1, 'b' => 2]);
     *   // [1 => 'a', 2 => 'b']
     */
    public static function flip($array)
    {
        return array_flip($array);
    }
    
    /**
     * Combine two arrays into key-value pairs
     * 
     * @param array $keys Array of keys
     * @param array $values Array of values
     * @return array Combined array
     * 
     * @example
     *   $combined = ArrayHelper::combine(['id', 'name'], [1, 'John']);
     *   // ['id' => 1, 'name' => 'John']
     */
    public static function combine($keys, $values)
    {
        return array_combine($keys, $values);
    }
    
    /**
     * Get random value from array
     * 
     * @param array $array The source array
     * @param int $number Number of random items to return
     * @return mixed Random value or array of values
     * 
     * @example
     *   $random = ArrayHelper::random($users); // Single random user
     *   $randoms = ArrayHelper::random($users, 3); // 3 random users
     */
    public static function random($array, $number = 1)
    {
        if ($number === 1) {
            return $array[array_rand($array)];
        }
        
        $keys = array_rand($array, $number);
        $results = [];
        
        foreach ((array) $keys as $key) {
            $results[] = $array[$key];
        }
        
        return $results;
    }
    
    /**
     * Shuffle array preserving keys
     * 
     * @param array $array The source array
     * @return array Shuffled array with keys preserved
     * 
     * @example
     *   $shuffled = ArrayHelper::shuffle(['a' => 1, 'b' => 2, 'c' => 3]);
     *   // Keys preserved but order changed
     */
    public static function shuffle($array)
    {
        $keys = array_keys($array);
        shuffle($keys);
        
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $array[$key];
        }
        
        return $result;
    }
}