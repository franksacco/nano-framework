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

use Nano\Config\ConfigurationInterface;
use Nano\Database\Exception\ConnectionFailedException;
use Nano\Database\Exception\InvalidArgumentException;
use Nano\Database\Exception\QueryExecutionException;
use Nano\Database\Facade\UpdateQueryInterface;
use Nano\Database\Select;
use PDO;
use PDOException;
use PDOStatement;

/**
 * A database connection manager based on PDO.
 *
 * This class is a wrapper for a PHP Data Object (PDO) instance. The connection
 * can be retrieved in anytime by `getConnection()` method.
 *
 * Database connection management can be configured through the `database` key
 * in application settings.
 * List of available options:
 *  - 'dsn': the Database Source Name (DSN) used by PDO for connection.
 *  - 'username': the username of database.
 *  - 'password': the password of database.
 *  - 'options': optional driver-specific options for PDO; default: [].
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Connection implements ConnectionInterface
{
    /**
     * @var ConfigurationInterface
     */
    protected $config;

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * Initialize the database connection.
     *
     * @param ConfigurationInterface $config The application settings.
     *
     * @throws ConnectionFailedException if an error occur during connection attempt.
     */
    public function __construct(ConfigurationInterface $config)
    {
        $this->config = $config->fork('database');
        try {
            $this->pdo = new PDO(
                $this->config->get('dsn'),
                $this->config->get('username'),
                $this->config->get('password'),
                $this->config->get('options', [])
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $exception) {
            throw ConnectionFailedException::forException($exception);
        }
    }

    /**
     * Close database connection.
     */
    public function __destruct()
    {
        $this->pdo = null;
    }

    /**
     * @inheritDoc
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * @inheritDoc
     */
    public function executeSelect(Select $query): array
    {
        try {
            $statement = $this->pdo->prepare($query->getStatement());
            foreach ($query->getParameters() as $attribute => $item) {
                list($value, $type) = $item;
                $statement->bindValue($attribute, $value, $type);
            }
            $statement->execute();

        } catch (PDOException $exception) {
            throw QueryExecutionException::inQuery($query, $exception);
        }
        $result = $statement->fetchAll();
        $statement = null;
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function executeUpdate(UpdateQueryInterface $query): int
    {
        try {
            $statement = $this->pdo->prepare($query->getStatement());
            foreach ($query->getParameters() as $attribute => $item) {
                list($value, $type) = $item;
                $statement->bindValue($attribute, $value, $type);
            }
            $statement->execute();

        } catch (PDOException $exception) {
            throw QueryExecutionException::inQuery($query, $exception);
        }
        $result = $statement->rowCount();
        $statement = null;
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function executeTransaction(array $queries)
    {
        if (empty($queries)) {
            throw new InvalidArgumentException('Query list must not be empty');
        }
        array_map(function ($query) {
            if (! $query instanceof UpdateQueryInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Queries in transaction must implements %s',
                    UpdateQueryInterface::class
                ));
            }
        }, $queries);

        try {
            $this->pdo->beginTransaction();
            foreach ($queries as $query) {
                $statement = $this->pdo->prepare($query->getStatement());
                foreach ($query->getParameters() as $attribute => $item) {
                    list($value, $type) = $item;
                    $statement->bindValue($attribute, $value, $type);
                }
                $statement->execute();
            }
            $this->pdo->commit();

        } catch (PDOException $exception) {
            $this->pdo->rollBack();
            throw QueryExecutionException::inTransaction($exception);
        }
        $statement = null;
    }

    /**
     * @inheritDoc
     */
    public function executeRawQuery(string $query, array $params = []): PDOStatement
    {
        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute($params);
            return $statement;

        } catch (PDOException $exception) {
            throw QueryExecutionException::inRawQuery($query, $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}