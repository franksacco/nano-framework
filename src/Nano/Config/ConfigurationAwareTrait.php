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
 * Helper trait for classes that need configurations.
 *
 * @package Nano\Config
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait ConfigurationAwareTrait
{
    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * Set the configuration manager.
     *
     * @param ConfigurationInterface $config
     */
    public function setConfiguration(ConfigurationInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Check if a configuration item exists.
     *
     * You can use dot notation to access array elements.
     *
     * @param string $key The key of the item to check.
     * @return bool Returns `true` if the item is set, `false` otherwise.
     */
    public function hasConfig(string $key)
    {
        return $this->config->has($key);
    }

    /**
     * Get the specified configuration item.
     *
     * If `$key` is not set, this method returns the configuration manager.
     * You can use dot notation to access array elements.
     *
     * @param string $key [optional] The key of the item to obtain.
     * @param mixed $default [optional] The return value when the key is not set.
     * @return ConfigurationInterface|mixed Returns configuration item if set,
     *   `$default` otherwise.
     */
    public function getConfig(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config->get($key, $default);
    }
}