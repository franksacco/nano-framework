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

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Nano\Config\ConfigurationInterface;
use Nano\Database\Connection\Connection;
use Nano\Database\Connection\ConnectionInterface;
use Nano\Database\Connection\LoggingConnection;
use Nano\Model\QueryBuilder\ConnectionCollector;
use Psr\Log\LoggerInterface;

/**
 * Register database connection manager.
 *
 * @package Nano\Container
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class DatabaseServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * @var array
     */
    protected $provides = [
        ConnectionInterface::class
    ];

    /**
     * @inheritDoc
     */
    public function boot()
    {
        // Setup lazy database connection for Model package.
        ConnectionCollector::setContainer($this->getContainer());
    }

    /**
     * @inheritDoc
     */
    public function register()
    {
        $container = $this->getLeagueContainer();
        $container->share(ConnectionInterface::class, function () use ($container) {

            return new LoggingConnection(
                new Connection($container->get(ConfigurationInterface::class)),
                $container->get(LoggerInterface::class)
            );
        });
    }
}