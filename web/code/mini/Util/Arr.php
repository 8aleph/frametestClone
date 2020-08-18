<?php

namespace Mini\Util;

use ArrayAccess;
use Exception;

/**
 * An array wrapper to help with dot notation.
 *
 * Note: This is a subset of Laravel's Arr class.
 *
 * Reference: https://github.com/laravel/framework/blob/7.x/src/Illuminate/Support/Arr.php
 */
class Arr
{
    /**
     * Determine whether the given value is array accessible.
     *
     * @param mixed $value value
     * 
     * @return bool whether it's accessible
     */
    public static function accessible($value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     *
     * @param \ArrayAccess|array $array array to work on
     * @param string|int|null    $key   key to use
     * @param mixed              $value value to save
     * 
     * @return array array we just added data to
     */
    public static function add($array, $key, $value): array
    {
        if (is_null(static::get($array, $key))) {
            static::set($array, $key, $value);
        }

        return $array;
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param \ArrayAccess|array $array   array to work on
     * @param string|int|null    $key     key to get
     * @param mixed              $default default value
     * 
     * @return mixed value
     */
    public static function get($array, $key, $default = null)
    {
        if (!static::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $array[$key] ?? value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param \ArrayAccess|array $array array to work on
     * @param string|array       $keys  keys to check for
     * 
     * @return bool whether the array has the keys
     */
    public static function has($array, $keys)
    {
        $keys = (array) $keys;

        if (!$array || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (static::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determine if any of the keys exist in an array using "dot" notation.
     *
     * @param \ArrayAccess|array $array array to work on
     * @param string|array       $keys  keys to check for
     * 
     * @return bool whether at least one key exists
     */
    public static function hasAny($array, $keys): bool
    {
        if (is_null($keys)) {
            return false;
        }

        $keys = (array) $keys;

        if (!$array) {
            return false;
        }

        if ($keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            if (static::has($array, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param array       $array array to modify
     * @param string|null $key   key to set
     * @param mixed       $value value to set
     * 
     * @return array $array array we set data on
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param \ArrayAccess|array $array array to work on
     * @param string|int         $key   key to check for
     * 
     * @return bool whether it exists
     */
    public static function exists($array, $key): bool
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param iterable $array   array to flatten
     * @param string   $prepend optional key prepend
     * 
     * @return array $results flattened array
     */
    public static function dot($array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param array        $array array to work on
     * @param array|string $keys  keys to forget
     * 
     * @return array array without key(s)
     */
    public static function except($array, $keys)
    {
        static::forget($array, $keys);

        return $array;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param iterable     $array array to work on
     * @param array|string $keys  keys to remove
     * 
     * @return void
     */
    public static function forget(&$array, $keys): void
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }
}
