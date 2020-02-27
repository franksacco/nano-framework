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

namespace Nano\Routing\FastRoute;

use Nano\Error\NanoExceptionInterface;

/**
 * Helper class for exception creation.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class RoutingException extends \InvalidArgumentException implements NanoExceptionInterface
{
    /**
     * @return RoutingException
     */
    public static function forInvalidCacheDir(): self
    {
        return new self('Unable to load routing data: the cache directory given is not valid');
    }

    /**
     * @return RoutingException
     */
    public static function forInvalidCacheFile(): self
    {
        return new self('Unable to load routing data: invalid cache file(s)');
    }

    /**
     * @param string $name
     * @return RoutingException
     */
    public static function forNotFoundRoute(string $name): self
    {
        return new self(sprintf(
            'The route with name "%s" was not found',
            $name
        ));
    }

    /**
     * @param string $name
     * @return RoutingException
     */
    public static function forNotEnoughParams(string $name): self
    {
        return new self(sprintf(
            'Not enough parameters given for route "%s"',
            $name
        ));
    }

    /**
     * @param string $name
     * @return RoutingException
     */
    public static function forTooManyParams(string $name): self
    {
        return new self(sprintf(
            'Too many parameters given for route "%s"',
            $name
        ));
    }
}