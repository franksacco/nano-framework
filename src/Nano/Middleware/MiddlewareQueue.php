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

namespace Nano\Middleware;

use Countable;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Handler for a middleware queue.
 *
 * @package Nano\Application
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class MiddlewareQueue implements Countable
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @var MiddlewareInterface[]
     */
    private $queue = [];

    /**
     * Initialize the middleware queue.
     *
     * @param ContainerInterface|null $container [optional] The DI container.
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Append a middleware to the end of the queue.
     *
     * A middleware can be an instance of {@see MiddlewareInterface}, a
     * callable that accepts two arguments:
     *  - {@see ServerRequestInterface}: the PSR-3 server request,
     *  - {@see RequestHandlerInterface}: the PSR-3 request handler,
     * and returns a {@see ResponseInterface} instance, a classname or a string
     * that identifies an instance of {@see MiddlewareInterface} from the DI
     * container.
     *
     * @param MiddlewareInterface|string|callable $middleware The middleware.
     * @return self Return self reference for method chaining.
     *
     * @throws InvalidMiddlewareException if middleware not implements
     *   MiddlewareInterface.
     */
    public function add($middleware): self
    {
        if (is_callable($middleware)) {
            $middleware = new CallableMiddleware($middleware);

        } else if (is_string($middleware)) {
            if ($this->container === null && class_exists($middleware)) {
                $middleware = new $middleware;

            } else if ($this->container !== null && $this->container->has($middleware)) {
                $middleware = $this->container->get($middleware);
            }
        }

        if (! $middleware instanceof MiddlewareInterface) {
            throw new InvalidMiddlewareException(sprintf(
                "Middleware must implements %s, got %s instead",
                MiddlewareInterface::class,
                is_object($middleware) ? get_class($middleware) : gettype($middleware)
            ));
        }

        $this->queue[] = $middleware;
        return $this;
    }

    /**
     * Get a middleware instance at the given index of the queue.
     *
     * @param int $index The middleware index.
     * @return MiddlewareInterface|null Returns middleware instance if index
     *   exists, NULL otherwise.
     */
    public function get(int $index): ?MiddlewareInterface
    {
        return $this->queue[$index] ?? null;
    }

    /**
     * Get the number of queued middleware
     *
     * @return int Returns the number of middleware.
     */
    public function count(): int
    {
        return count($this->queue);
    }
}