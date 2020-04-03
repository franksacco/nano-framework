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

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CallableMiddlewareTest extends TestCase
{
    public function testProcess()
    {
        $response = $this->createMock(ResponseInterface::class);
        $request  = $this->createMock(ServerRequestInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $callable = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        };

        $middleware = new CallableMiddleware($callable);
        $this->assertSame($response, $middleware->process($request, $handler));
    }

    public function testInvalidResponse()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $callable = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return null;
        };
        $middleware = new CallableMiddleware($callable);

        $this->expectException(InvalidMiddlewareException::class);
        $this->expectExceptionMessage('Callable middleware did not create a ' .
            'Psr\Http\Message\ResponseInterface, got "NULL" instead');

        $middleware->process($request, $handler);
    }
}