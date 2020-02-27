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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Empty session handler.
 *
 * This handler can be used during testing or when persisted sessions are not
 * desired.
 *
 * @package Nano\Session
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class NullSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Initialize the null session handler.
     *
     * @param LoggerInterface $logger [optional] The PSR-3 logger instance.
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @inheritDoc
     */
    public function open($savePath, $name): bool
    {
        $this->logger->debug('[SESSION] Session "{name}" opened', compact('name'));
        return true;
    }

    /**
     * @inheritDoc
     */
    public function read($id): string
    {
        $this->logger->debug('Session "{id}" read', compact('id'));
        return '';
    }

    /**
     * @inheritDoc
     */
    public function write($id, $data): bool
    {
        $this->logger->debug('Session "{id}" wrote', compact('id'));
        return true;
    }

    /**
     * @inheritDoc
     */
    public function close(): bool
    {
        $this->logger->debug('Session closed');
        return true;
    }

    /**
     * @inheritDoc
     */
    public function destroy($id): bool
    {
        $this->logger->debug('Session "{id}" destroyed', compact('id'));
        return true;
    }

    /**
     * @inheritDoc
     */
    public function gc($maxlifetime): bool
    {
        $this->logger->debug('Session garbage collection executed');
        return true;
    }

    /**
     * @inheritDoc
     */
    public function validateId($id): bool
    {
        $this->logger->debug('Session ID "{id}" validated', compact('id'));
        return true;
    }

    /**
     * @inheritDoc
     */
    public function updateTimestamp($id, $data): bool
    {
        $this->logger->debug('Timestamp of session "{id}" updated', compact('id'));
        return true;
    }
}