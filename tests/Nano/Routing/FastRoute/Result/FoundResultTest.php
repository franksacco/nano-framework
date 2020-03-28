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

namespace Nano\Routing\FastRoute\Result;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FoundResultTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     * @param $middlewares
     * @param $handler
     * @param $params
     */
    public function testFoundResult($middlewares, $handler, $params)
    {
        $foundResult = new FoundResult($middlewares, $handler, $params);

        $this->assertSame($middlewares, $foundResult->getMiddlewares());
        $this->assertSame($handler, $foundResult->getHandler());
        $this->assertSame($params, $foundResult->getParams());
    }

    public function dataProvider()
    {
        return [
            [
                [], 'handler0', []
            ], [
                [new TestMiddleware()], 'handler1', ['param0' => 'foo']
            ], [
                [new TestMiddleware(), new TestMiddleware()],
                'handler2',
                ['param0' => 'foo', 'param1' => 'bar']
            ]
        ];
    }
}

class TestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}