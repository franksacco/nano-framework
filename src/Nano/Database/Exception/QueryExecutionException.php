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

use Nano\Database\Facade\QueryInterface;
use Nano\Error\NanoExceptionInterface;
use PDOException;

/**
 * Exception thrown if an error occur during a query execution.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class QueryExecutionException extends \RuntimeException implements NanoExceptionInterface
{
    /**
     * @param QueryInterface $query
     * @param PDOException $exception
     * @return QueryExecutionException
     */
    public static function inQuery(QueryInterface $query, PDOException $exception): self
    {
        return new self(
            sprintf(
                'Could not execute the query "%s": %s',
                $query->getStatement(),
                $exception->getMessage()
            ),
            0,
            $exception
        );
    }

    /**
     * @param PDOException $exception
     * @return QueryExecutionException
     */
    public static function inTransaction(PDOException $exception): self
    {
        return new static(
            sprintf('Could not execute a query in transaction: %s', $exception->getMessage()),
            0,
            $exception
        );
    }

    /**
     * @param string $query
     * @param PDOException $exception
     * @return QueryExecutionException
     */
    public static function inRawQuery(string $query, PDOException $exception): self
    {
        return new static(
            sprintf(
                'Could not execute the raw query "%s": %s',
                $query,
                $exception->getMessage()
            ),
            0,
            $exception
        );
    }
}
