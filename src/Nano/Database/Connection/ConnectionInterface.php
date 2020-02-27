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

namespace Nano\Database\Connection;

use Nano\Database\Exception\InvalidArgumentException;
use Nano\Database\Exception\QueryExecutionException;
use Nano\Database\Facade\UpdateQueryInterface;
use Nano\Database\Select;
use PDO;
use PDOStatement;

/**
 * Interface for database connection manager.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
interface ConnectionInterface
{
    /**
     * Retrieve the PDO instance representing a connection to a database.
     *
     * @return PDO Returns the established PDO connection.
     */
    public function getConnection(): PDO;

    /**
     * Execute a SELECT query object.
     *
     * @param Select $query The select query object to execute.
     * @return array Returns an array containing all rows in the result set.
     *
     * @throws QueryExecutionException if the execution fails.
     */
    public function executeSelect(Select $query): array;

    /**
     * Execute an INSERT, UPDATE or DELETE query object.
     *
     * @param UpdateQueryInterface $query The query to be executed.
     * @return int Returns the number of affected rows by the executed query.
     */
    public function executeUpdate(UpdateQueryInterface $query): int;

    /**
     * Execute a raw SQL query.
     *
     * @param string $query The prepared statement.
     * @param array $params [optional] The parameter list; all values are
     *   treated as `PDO::PARAM_STR`.
     * @return PDOStatement Returns the PDO statement.
     *
     * @throws QueryExecutionException if an error occur during query execution.
     */
    public function executeRawQuery(string $query, array $params = []): PDOStatement;

    /**
     * Execute more query objects in a single transaction.
     *
     * @param UpdateQueryInterface[] $queries The list of queries to execute.
     *
     * @throws InvalidArgumentException if the list is empty or contains invalid items.
     * @throws QueryExecutionException if an error occur during transaction execution.
     */
    public function executeTransaction(array $queries);

    /**
     * Return the ID of the last inserted row.
     *
     * @return string Returns the row ID of the last row that was inserted
     *     into the database
     */
    public function getLastInsertId(): string;
}