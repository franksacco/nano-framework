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
 * Collector for application configuration items.
 *
 * @package Nano\Config
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    use ArrayDotNotationTrait {
        hasItem as protected hasConfig;
        getItem as protected getConfig;
        setItem as protected setConfig;
    }

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * @var array
     */
    protected $values = [];

    /**
     * Initialize the configuration collector.
     *
     * @param array $values The configuration item list.
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        if ($this->prefix !== '') {
            return $this->hasConfig($this->values, $this->prefix . '.' . $key);
        }

        return $this->hasConfig($this->values, $key);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        if ($this->prefix !== '') {
            return $this->getConfig($this->values, $this->prefix . '.' . $key, $default);
        }

        return $this->getConfig($this->values, $key, $default);
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        if ($this->prefix !== '') {
            return $this->getConfig($this->values, $this->prefix, []);
        }

        return $this->values;
    }

    /**
     * @inheritDoc
     */
    public function withPrefix(string $prefix): ConfigurationInterface
    {
        if (! is_array($this->getConfig($this->values, $prefix))) {
            throw new InvalidPrefixException('The prefix does not refer to an item of type array');
        }

        $new = clone $this;
        $new->prefix = $prefix;
        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}