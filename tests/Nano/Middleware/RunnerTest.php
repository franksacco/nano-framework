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
use Psr\Http\Server\MiddlewareInterface;

class RunnerTest extends TestCase
{
    public function testHandle()
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $queue  = $this->createMock(MiddlewareQueue::class);
        $runner = new Runner($queue);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($request, $runner)
            ->willReturn($response);

        $queue->expects($this->once())
            ->method('get')
            ->with(0)
            ->willReturn($middleware);

        $this->assertSame($response, $runner->handle($request));
    }

    public function testNoResult()
    {
        $queue = $this->createMock(MiddlewareQueue::class);
        $queue->expects($this->once())
            ->method('get')
            ->with(0)
            ->willReturn(null);
        $runner  = new Runner($queue);
        $request = $this->createMock(ServerRequestInterface::class);

        $this->expectException(InvalidMiddlewareException::class);
        $this->expectExceptionMessage('Middleware queue exhausted with no result');

        $runner->handle($request);
    }
}