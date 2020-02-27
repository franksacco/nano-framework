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

namespace Nano\Database\Exception;

use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown if a database connection attempt fails.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class ConnectionFailedException extends \RuntimeException implements NanoExceptionInterface
{
    /**
     * @param \PDOException $exception
     * @return ConnectionFailedException
     */
    public static function forException(\PDOException $exception): self
    {
        return new static(
            sprintf('Could not connect to the database: %s', $exception->getMessage()),
            $exception->getCode(),
            $exception
        );
    }
}