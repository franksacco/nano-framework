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

use Nano\Middleware\InvalidMiddlewareException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Request handler that executes middlewares associated to the route.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class RouteRequestHandler implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;

    /**
     * @var int
     */
    private $index = 0;

    /**
     * @var RequestHandlerInterface
     */
    private $handler;

    /**
     * Initialize the route request handler.
     *
     * @param MiddlewareInterface[] $middlewares The middleware list.
     * @param RequestHandlerInterface $handler The
     */
    public function __construct(array $middlewares, RequestHandlerInterface $handler)
    {
        $this->middlewares = $middlewares;
        $this->handler     = $handler;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! isset($this->middlewares[$this->index])) {
            return $this->handler->handle($request);
        }

        $middleware = $this->middlewares[$this->index];
        if (! $middleware instanceof MiddlewareInterface) {
            throw new InvalidMiddlewareException('Invalid middleware');
        }

        $this->index++;
        return $middleware->process($request, $this);
    }
}
