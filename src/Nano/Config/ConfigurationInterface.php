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
 * Interface for configuration manager.
 *
 * @package Nano\Config
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
interface ConfigurationInterface
{
    /**
     * Check if a configuration value exists.
     *
     * Use '.' to access array elements.
     *
     * @param string $key The key of the value to check.
     * @return bool Returns TRUE if value is set, FALSE otherwise.
     */
    public function has(string $key): bool;

    /**
     * Retrieve a configuration value.
     *
     * Use '.' to access array elements.
     *
     * @param string $key The key of the value to obtain.
     * @param mixed $default [optional] The return value when the key is not set.
     * @return mixed Returns configuration value if set, $default otherwise.
     */
    public function get(string $key, $default = null);

    /**
     * Set a configuration value.
     *
     * Use '.' to access array elements.
     *
     * @param string $key The of the value to set.
     * @param mixed $value The configuration value.
     *
     * @throws UnexpectedValueException when attempting to set a non-array value.
     */
    public function set(string $key, $value);

    /**
     * Create a partition of the configurations.
     *
     * Use '.' to access array elements.
     *
     * @param string $key The key of an array used for the partition.
     * @return ConfigurationInterface Returns new instance of this class
     *     containing the partition defined through the given key.
     */
    public function fork(string $key): ConfigurationInterface;

    /**
     * Retrieve all configuration variables.
     *
     * @return array Returns the configuration list.
     */
    public function all(): array;
}