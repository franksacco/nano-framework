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

namespace Nano\Routing;

use Nano\Error\NanoExceptionInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown if an invalid request handler is provided.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class InvalidRequestHandlerException extends \InvalidArgumentException implements NanoExceptionInterface
{
    /**
     * @param string $class The class name.
     * @return InvalidRequestHandlerException
     */
    public static function forInvalidClass(string $class): self
    {
        return new self(sprintf(
            'Unable to resolve class %s for a non-existent class or a DI container without auto-wiring',
            $class
        ));
    }

    /**
     * @param string $class The class name.
     * @return InvalidRequestHandlerException
     */
    public static function forNonObject(string $class): self
    {
        return new self(sprintf(
            'Unable to resolve class %s for a non-object result from the container',
            $class
        ));
    }

    /**
     * @return InvalidRequestHandlerException
     */
    public static function forInvalidHandler(): self
    {
        return new self('Invalid request handler provided');
    }

    /**
     * @param mixed $result The result of handler execution.
     * @return InvalidRequestHandlerException
     */
    public static function forInvalidResult($result): self
    {
        return new self(sprintf(
            "The request handler must produce a %s or a string, got %s instead",
            ResponseInterface::class,
            is_object($result) ? get_class($result) : gettype($result)
        ));
    }
}