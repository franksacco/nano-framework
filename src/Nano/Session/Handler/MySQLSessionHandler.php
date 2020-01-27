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

namespace Nano\Session\Handler;

use Nano\Config\ConfigurationInterface;
use Nano\Database\Connection\ConnectionInterface;
use Nano\Database\Delete;
use Nano\Database\Exception\QueryExecutionException;
use Nano\Database\Facade\Types;
use Nano\Database\Replace;
use Nano\Database\Select;
use Nano\Session\Exception\SessionException;
use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Session handler based on MySQL database storage.
 *
 * This handler implements database locking to avoid server-side race
 * conditions, collision free ID generation and session ID validation
 * to avoid uninitialized session ID.
 *
 * MySQL session handler can be configured through the "session.database" key
 * in application settings.
 * Available options for this class are:
 *  - "table": database table name with schema defined below;
 *   default value: 'sessions'.
 *  - "lock": enable database locking to avoid server-side race conditions;
 *   default value: TRUE.
 *  - "lock_timeout": timeout for database lock in seconds; default: 20.
 *
 * Example of table definition:
 * <code>
 * CREATE TABLE `sessions` (
 *     `id` varchar(256) NOT NULL,
 *     `data` text NOT NULL,
 *     `updated` datetime NOT NULL,
 *     PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * </code>
 *
 * @package Nano\Session
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class MySQLSessionHandler implements SessionHandlerInterface,
                                     SessionIdInterface,
                                     SessionUpdateTimestampHandlerInterface
{
    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $lastCreatedId;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * Initialize the MySQL session handler.
     *
     * @param ConfigurationInterface $config The application settings.
     * @param ConnectionInterface $connection
     */
    public function __construct(ConfigurationInterface $config, ConnectionInterface $connection)
    {
        $this->config = $config->fork('session.database');
        $this->table  = $config->get('table', 'sessions');
        $this->connection = $connection;
    }

    /**
     * @inheritDoc
     */
    public function open($savePath, $name): bool
    {
        return true;
    }

    /**
     * Tries to obtain an exclusive database lock for the current session.
     *
     * @param string $id The session ID.
     *
     * @throws SessionException if an error occur during lock obtaining.
     */
    private function getLock(string $id)
    {
        if ($this->config->get('lock', true)) {
            try {
                // MySQL 5.7 and later enforces a maximum
                // length on lock names of 64 characters.
                $lockName    = 'session_' . sha1($id);
                $lockTimeout = (int) $this->config->get('lock_timeout', 20);
                $this->connection->executeRawQuery(
                    "SELECT GET_LOCK(?, ?);",
                    [$lockName, $lockTimeout]
                );

            } catch (QueryExecutionException $exception) {
                throw new SessionException(
                    sprintf(
                        'Unable to get database lock: %s',
                        $exception->getMessage()
                    ), 0, $exception
                );
            }
        }
    }

    /**
     * Try to release all database locks for the current session.
     *
     * @throws SessionException if an error occur during locks releasing.
     */
    private function releaseAllLocks()
    {
        if ($this->config->get('lock', true)) {
            try {
                $this->connection->executeRawQuery("SELECT RELEASE_ALL_LOCKS();");

            } catch (QueryExecutionException $exception) {
                throw new SessionException(
                    sprintf(
                        'Unable to release database locks: %s',
                        $exception->getMessage()
                    ), 0, $exception
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function read($id): string
    {
        $this->getLock($id);
        try {
            $expiration = date(
                'Y-m-d H:i:s',
                time() - (int) ini_get('session.gc_maxlifetime')
            );
            $query = (new Select($this->table))
                ->select('data')
                ->where('id', '=', $id, Types::STRING)
                ->where('updated', '>=', $expiration, Types::DATETIME)
                ->limit(1);
            $result = $this->connection->executeSelect($query);
            return $result[0]['data'] ?? '';

        } catch (QueryExecutionException $exception) {
            throw new SessionException(
                sprintf(
                    'Unable to read session "%s": %s',
                    $id, $exception->getMessage()
                ), 0, $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function write($id, $data): bool
    {
        try {
            $query = (new Replace($this->table))
                ->addValues([
                    'id'      => [$id, Types::STRING],
                    'data'    => [$data, Types::STRING],
                    'updated' => [date('Y-m-d H:i:s'), Types::DATETIME]
                ]);
            $this->connection->executeUpdate($query);
            return true;

        } catch (QueryExecutionException $exception) {
            throw new SessionException(
                sprintf(
                    'Unable to write the session "%s": %s',
                    $id, $exception->getMessage()
                ), 0, $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function close(): bool
    {
        $this->releaseAllLocks();
        return true;
    }

    /**
     * @inheritDoc
     */
    public function destroy($id): bool
    {
        try {
            $query = (new Delete($this->table))
                ->where('id', '=', $id, Types::STRING)
                ->limit(1);
            $this->connection->executeUpdate($query);
            return true;

        } catch (QueryExecutionException $exception) {
            throw new SessionException(
                sprintf(
                    'Unable to destroy the session "%s": %s',
                    $id, $exception->getMessage()
                ), 0,$exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function gc($maxlifetime)
    {
        try {
            $expiration = date('Y-m-d H:i:s', time() - $maxlifetime);
            $query = (new Delete($this->table))
                ->where('updated', '<', $expiration, Types::DATETIME);
            return $this->connection->executeUpdate($query);

        } catch (QueryExecutionException $exception) {
            throw new SessionException(
                sprintf(
                    'Unable to perform session garbage collection: %s',
                    $exception->getMessage()
                ), 0, $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    /** @noinspection PhpMethodNamingConventionInspection */
    public function create_sid(): string
    {
        do {
            $id = session_create_id();
            try {
                $query = (new Select($this->table))
                    ->aggregate('COUNT', '*', 'count')
                    ->where('id', '=', $id, Types::STRING);
                $result = $this->connection->executeSelect($query);
                $count = (int) $result[0]['count'];

            } catch (QueryExecutionException $exception) {
                throw new SessionException(
                    sprintf(
                        'Unable to check collision for session ID: %s',
                        $exception->getMessage()
                    ), 0, $exception
                );
            }
        } while ($count > 0);

        return ($this->lastCreatedId = $id);
    }

    /**
     * @inheritDoc
     */
    public function validateId($id): bool
    {
        // This is a workaround for the problem that session ID is validated
        // even when create_sid() generates a collision free ID.
        // For more details see https://bugs.php.net/bug.php?id=77178.
        if ($id === $this->lastCreatedId) {
            return true;
        }
        try {
            $query = (new Select($this->table))
                ->aggregate('COUNT', '*', 'count')
                ->where('id', '=', $id, Types::STRING);
            $result = $this->connection->executeSelect($query);
            return intval($result[0]['count'] ?? 0) > 0;

        } catch (QueryExecutionException $exception) {
            throw new SessionException(
                sprintf(
                    'Unable to validate session ID: %s',
                    $exception->getMessage()
                ), 0, $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function updateTimestamp($id, $data): bool
    {
        return $this->write($id, $data);
    }
}
