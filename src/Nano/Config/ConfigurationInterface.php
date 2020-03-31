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

namespace Nano\Config;

/**
 * Interface for configuration collector.
 *
 * @package Nano\Config
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
interface ConfigurationInterface
{
    /**
     * Check if a configuration item exists.
     *
     * You can use dot notation to access array elements.
     * If a prefix is set, it is prepended to `$key`.
     *
     * @param string $key The key of the item to check.
     * @return bool Returns `true` if the item is set, `false` otherwise.
     */
    public function has(string $key): bool;

    /**
     * Get the specified configuration item.
     *
     * You can use dot notation to access array elements.
     * If a prefix is set, it is prepended to `$key`.
     *
     * @param string $key The key of the item to obtain.
     * @param mixed $default [optional] The return value when the key is not set.
     * @return mixed Returns configuration item if set, `$default` otherwise.
     */
    public function get(string $key, $default = null);

    /**
     * Retrieve all of the configuration items.
     *
     * If a prefix is set, this method returns all configuration items
     * associated to it.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Get the configuration with a fixed prefix.
     *
     * You can use dot notation to access array elements.
     *
     * @param string $prefix The prefix that refers to an array.
     * @return ConfigurationInterface Returns a copy of this instance with a
     *   fixed prefix for the provided keys.
     *
     * @throws InvalidPrefixException when the prefix does not refer to an
     *   element of type array.
     */
    public function withPrefix(string $prefix): ConfigurationInterface;

    /**
     * Get the key prefix for this instance.
     *
     * @return string
     */
    public function getPrefix(): string;
}