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

use Nano\Utility\DotArrayAccessTrait;

/**
 * Manager for application configuration.
 *
 * @package Nano\Config
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    use DotArrayAccessTrait;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * Initialize the configuration values.
     *
     * @param array $values The configuration list.
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
        return $this->hasItem($this->values, $key);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        return $this->getItem($this->values, $key, $default);
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value)
    {
        try {
            $this->setItem($this->values, $key, $value);

        } catch (\UnexpectedValueException $exception) {
            throw new UnexpectedValueException('Setting configuration for a non-array value');
        }
    }

    /**
     * @inheritDoc
     */
    public function fork(string $key): ConfigurationInterface
    {
        $value = $this->get($key, []);

        return new self(is_array($value) ? $value : []);
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->values;
    }
}