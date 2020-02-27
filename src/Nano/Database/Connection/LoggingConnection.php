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

use Nano\Database\Facade\UpdateQueryInterface;
use Nano\Database\Select;
use PDO;
use PDOStatement;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Extend functionalities of a database connection with logging.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class LoggingConnection implements ConnectionInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * LoggingConnection constructor.
     *
     * @param ConnectionInterface $connection The database connection.
     * @param LoggerInterface $logger The PSR-3 logger instance.
     */
    public function __construct(ConnectionInterface $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger     = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getConnection(): PDO
    {
        return $this->connection->getConnection();
    }

    /**
     * @inheritDoc
     */
    public function executeSelect(Select $query): array
    {
        $start = microtime(true);
        $result = $this->connection->executeSelect($query);

        $this->logger->info("[DATABASE] Query executed in {t} ms: {q}", [
            't' => (microtime(true) - $start) * 1000,
            'q' => $query->getStatement()
        ]);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function executeUpdate(UpdateQueryInterface $query): int
    {
        $start = microtime(true);
        $result = $this->connection->executeUpdate($query);

        $this->logger->info("[DATABASE] Query executed in {t} ms: {q}", [
            't' => (microtime(true) - $start) * 1000,
            'q' => $query->getStatement()
        ]);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function executeTransaction(array $queries)
    {
        $start = microtime(true);
        $this->connection->executeTransaction($queries);

        $q = implode("\n\t", array_map(function (UpdateQueryInterface $query) {
            return $query->getStatement();
        }, $queries));
        $this->logger->info("[DATABASE] Transaction executed in {t} ms:\n\t{q}", [
            't' => (microtime(true) - $start) * 1000,
            'q' => $q
        ]);
    }

    /**
     * @inheritDoc
     */
    public function executeRawQuery(string $query, array $params = []): PDOStatement
    {
        $start = microtime(true);
        $result = $this->connection->executeRawQuery($query, $params);

        $this->logger->info("[DATABASE] Raw query executed in {t} ms: {q}", [
            't' => (microtime(true) - $start) * 1000,
            'q' => $query
        ]);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertId(): string
    {
        return $this->connection->getLastInsertId();
    }
}