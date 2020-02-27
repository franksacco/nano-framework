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

use InvalidArgumentException;

/**
 * Exception thrown if an invalid session handler is provided.
 *
 * @package Nano\Session\Exception
 * @author Francesco Saccani <saccani.francesco@gmail.com>
 */
class InvalidHandlerException extends InvalidArgumentException implements SessionExceptionInterface
{
    /**
     * @return InvalidHandlerException
     */
    public static function classNotExists(): self
    {
        return new static('The session handler class given does not exist');
    }

    /**
     * @return InvalidHandlerException
     */
    public static function interfaceNotImplemented(): self
    {
        return new static('The session handler must implements SessionHandlerInterface');
    }

    /**
     * @return InvalidHandlerException
     */
    public static function extendsSessionHandler(): self
    {
        return new static('The session handler must not extend SessionHandler class');
    }
}