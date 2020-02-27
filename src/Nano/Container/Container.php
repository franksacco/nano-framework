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

namespace Nano\Container;

use League\Container\Container as LeagueContainer;
use League\Container\ReflectionContainer;
use Nano\Config\Configuration;
use Nano\Config\ConfigurationInterface;
use Nano\Middleware\AbstractApplication;

/**
 * Dependency injection container.
 *
 * Nano's default DI container is Container library from The PHP League.
 * This class is used by {@see AbstractApplication} to define default behavior
 * (i.e. enabling auto wiring), initialize application settings and setup
 * default services like logger or database connection.
 *
 * @see https://github.com/thephpleague/container
 *
 * @package Nano\Application
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Container extends LeagueContainer
{
    /**
     * Initialize the DI container.
     *
     * @param array $settings The application settings.
     */
    public function __construct(array $settings = [])
    {
        parent::__construct();

        // register the reflection container as a delegate to enable auto wiring
        $this->delegate(
            (new ReflectionContainer())->cacheResolutions()
        );

        // initialize application settings
        $settings = new Configuration($settings);
        $this->share(ConfigurationInterface::class, $settings);

        // setup default services
        $this->addServiceProvider(new DefaultServiceProvider($settings));
    }
}