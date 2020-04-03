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
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

class MiddlewareQueueTest extends TestCase
{
    public function testMiddlewareFromContainer()
    {
        $middleware = $this->createMock(MiddlewareInterface::class);

        $container  = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('middleware')
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with('middleware')
            ->willReturn($middleware);

        $queue = new MiddlewareQueue($container);
        $queue->add('middleware');

        $this->assertSame($middleware, $queue->get(0));
    }

    public function testCallableMiddleware()
    {
        $middleware = function () {};
        $container  = $this->createMock(ContainerInterface::class);
        $queue      = new MiddlewareQueue($container);
        $queue->add($middleware);

        $this->assertEquals(new CallableMiddleware($middleware), $queue->get(0));
    }

    public function testInvalidMiddleware()
    {
        $container  = $this->createMock(ContainerInterface::class);
        $queue      = new MiddlewareQueue($container);

        $this->expectException(InvalidMiddlewareException::class);
        $this->expectExceptionMessage('Middleware must implements ' .
            'Psr\Http\Server\MiddlewareInterface, got NULL instead');

        $queue->add(null);
    }

    public function testGet()
    {
        $expected  = $this->createMock(MiddlewareInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $queue     = new MiddlewareQueue($container);
        $queue->add($expected);

        $this->assertSame($expected, $queue->get(0));
    }

    public function testCount()
    {
        $container = $this->createMock(ContainerInterface::class);
        $queue     = new MiddlewareQueue($container);
        $queue->add($this->createMock(MiddlewareInterface::class));
        $queue->add($this->createMock(MiddlewareInterface::class));
        $queue->add($this->createMock(MiddlewareInterface::class));

        $this->assertCount(3, $queue);
    }
}