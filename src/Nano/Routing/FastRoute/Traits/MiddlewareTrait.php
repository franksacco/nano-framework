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

namespace Nano\Routing\FastRoute\Traits;

use Nano\Middleware\CallableMiddleware;
use Nano\Middleware\InvalidMiddlewareException;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Methods for middleware management.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait MiddlewareTrait
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * Add a middleware to this route.
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
     *
     * @throws InvalidMiddlewareException if middleware not implements
     *     {@see MiddlewareInterface}.
     */
    public function middleware($middleware)
    {
        if (is_callable($middleware)) {
            $middleware = new CallableMiddleware($middleware);

        } else if (is_string($middleware)) {
            if (! isset($this->container) && class_exists($middleware)) {
                $middleware = new $middleware;

            } else if (isset($this->container) && $this->container->has($middleware)) {
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
        $this->middlewares[] = $middleware;
    }

    /**
     * Get the list of middlewares.
     *
     * @return MiddlewareInterface[] Returns the middleware list.
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
