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

namespace Nano\Model\QueryBuilder;

use Nano\Database\Connection\ConnectionInterface;
use Nano\Model\Exception\ModelExecutionException;
use Psr\Container\ContainerInterface;

/**
 * Collect database connection from DI container statically.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class ConnectionCollector
{
    /**
     * @var ContainerInterface
     */
    private static $container;

    /**
     * @var ConnectionInterface
     */
    private static $connection;

    /**
     * Avoid class instantiation.
     */
    private function __construct() {}

    /**
     * Set the Dependency Injection container.
     *
     * @param ContainerInterface $container The DI container.
     */
    public static function setContainer(ContainerInterface $container)
    {
        self::$container = $container;
    }

    /**
     * Retrieve database connection.
     *
     * @return ConnectionInterface Returns the database connection.
     *
     * @throws ModelExecutionException if DI container or database connection
     *   is not set.
     */
    public static function getConnection(): ConnectionInterface
    {
        if (! self::$connection) {
            if (! self::$container) {
                throw new ModelExecutionException('DI container required');
            }
            if (! self::$container->has(ConnectionInterface::class)) {
                throw new ModelExecutionException('Database connection required in DI container');
            }
            self::$connection = self::$container->get(ConnectionInterface::class);
        }
        return self::$connection;
    }
}
