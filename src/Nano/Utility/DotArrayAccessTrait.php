<?php
/**
 * Nano Framework
 *
 * @package   Nano
 * @author    Francesco Saccani <saccani.francesco@gmail.com>
 * @copyright Copyright (c) 2019 Francesco Saccani
 * @version   1.0
 */

declare(strict_types=1);

namespace Nano\Utility;

use UnexpectedValueException;

/**
 * Helper trait for array access with dot.
 *
 * @package Nano\Utility
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait DotArrayAccessTrait
{
    /**
     * Check if the key is set in the array.
     *
     * @param array $array The array.
     * @param string $key The key of the item.
     * @return bool Returns TRUE if the key is set, FALSE otherwise.
     */
    public function hasItem(array $array, string $key): bool
    {
        if (($lastDot = strrpos($key, '.')) !== false) {
            $keys = explode('.', $key, -1);
            foreach ($keys as $k) {

                if (! isset($array[$k])) {
                    return false;
                }
                $array = $array[$k];
            }

            $key = substr($key, $lastDot + 1);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Retrieve the value of an item in the array.
     *
     * @param array $array The array.
     * @param string $key The key of the item.
     * @param mixed $default [optional] The default value.
     * @return mixed Returns item value if set, $default otherwise.
     */
    public function getItem(array $array, string $key, $default = null)
    {
        if (($lastDot = strrpos($key, '.')) !== false) {

            $keys = explode('.', $key, -1);
            foreach ($keys as $k) {

                if (! isset($array[$k])) {
                    return $default;
                }
                $array = $array[$k];
            }

            $key = substr($key, $lastDot + 1);
        }

        return $array[$key] ?? $default;
    }

    /**
     * Set the value of an item in the array.
     *
     * @param array &$array The reference to the array.
     * @param string $key The key of the item.
     * @param mixed $value The value of the item.
     *
     * @throws UnexpectedValueException when attempting to set a non-array value.
     */
    public function setItem(array &$array, string $key, $value)
    {
        if (($lastDot = strrpos($key, '.')) !== false) {

            $keys = explode('.', $key, -1);
            foreach ($keys as $k) {

                if (is_array($array) && !array_key_exists($k, $array)) {
                    $array[$k] = [];

                } else if (!is_array($array) || !is_array($array[$k])) {
                    throw new UnexpectedValueException('Setting value for a non-array item');
                }

                $array = &$array[$k];
            }

            $key = substr($key, $lastDot + 1);
        }

        $array[$key] = $value;
    }
}
