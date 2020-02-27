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

namespace Nano\Session\Exception;

use RuntimeException;

/**
 * Generic session thrown if an error occur during session management.
 *
 * @package Nano\Session\Exception
 * @author Francesco Saccani <saccani.francesco@gmail.com>
 */
class SessionException extends RuntimeException implements SessionExceptionInterface
{
    /**
     * @return SessionException
     */
    public static function alreadyStarted(): self
    {
        return new static('Unable to start the session: a session was already started');
    }

    /**
     * @return SessionException
     */
    public static function headersSent(): self
    {
        return new static('Unable to start the session: headers were already sent');
    }

    /**
     * @return SessionException
     */
    public static function startError(): self
    {
        return new static('Unable to start the session');
    }

    /**
     * @return SessionException
     */
    public static function notUnserializableData(): self
    {
        return new static('Unable to start the session: data is not unserializable');
    }

    /**
     * @return SessionException
     */
    public static function regenerateError(): self
    {
        return new static('Unable to regenerate the session');
    }

    /**
     * @return SessionException
     */
    public static function destroyError(): self
    {
        return new static('Unable to destroy the session');
    }

    /**
     * @return SessionException
     */
    public static function closeError(): self
    {
        return new static('Unable to close the session');
    }

    /**
     * @return SessionException
     */
    public static function gcError(): self
    {
        return new static('Unable to execute session garbage collection');
    }
}