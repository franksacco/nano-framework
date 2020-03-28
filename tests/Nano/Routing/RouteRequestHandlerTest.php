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
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteRequestHandlerTest extends TestCase
{
    public function testHandle()
    {
        $request     = $this->createMock(ServerRequestInterface::class);
        $response    = $this->createMock(ResponseInterface::class);
        $middlewares = [new DummyMiddleware()];
        $handler     = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $routeRequestHandler = new RouteRequestHandler($middlewares, $handler);
        $this->assertSame($response, $routeRequestHandler->handle($request));
    }

    public function testInvalidMiddleware()
    {
        $request     = $this->createMock(ServerRequestInterface::class);
        $middlewares = ['not-exists'];
        $handler     = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $this->expectException(InvalidMiddlewareException::class);
        $this->expectExceptionMessage('Invalid middleware provided');

        $routeRequestHandler = new RouteRequestHandler($middlewares, $handler);
        $routeRequestHandler->handle($request);
    }
}

class DummyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}