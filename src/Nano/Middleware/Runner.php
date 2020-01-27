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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Run a middleware queue.
 *
 * @package Nano\Application
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Runner implements RequestHandlerInterface
{
    /**
     * @var int
     */
    private $index = 0;

    /**
     * @var MiddlewareQueue
     */
    private $queue;

    /**
     * Initialize the middleware queue runner.
     *
     * @param MiddlewareQueue $middleware The middleware queue.
     */
    public function __construct(MiddlewareQueue $middleware)
    {
        $this->queue = $middleware;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $next = $this->queue->get($this->index++);
        if ($next == null) {
            throw new InvalidMiddlewareException('Middleware queue exhausted with no result');
        }

        return $next->process($request, $this);
    }
}
