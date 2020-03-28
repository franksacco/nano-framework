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

use Nano\Middleware\CallableMiddleware;
use Nano\Middleware\InvalidMiddlewareException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteTest extends TestCase
{
    /**
     * @dataProvider routeProvider
     * @param $method
     * @param $pattern
     * @param $handler
     * @param $name
     */
    public function testRoute($method, $pattern, $handler, $name)
    {
        $route = new Route($method, $pattern, $handler, $name);

        $this->assertSame($method, $route->getMethod());
        $this->assertSame($pattern, $route->getRoute());
        $this->assertSame($handler, $route->getHandler());
        $this->assertSame($name, $route->getName());
    }

    public function routeProvider()
    {
        return [
            ['GET', '/test', 'class::method', null],
            ['POST', '/test/{id:\d+}', 'class@method', 'test0'],
            ['PUT', '/[home]', 'class', 'test1'],
            ['PATCH', '/users[/{name}]', 'function', 'test2'],
            ['DELETE', '/', function () {}, 'test3'],
            ['HEAD', '/test/', ['class', 'method'], 'test4']
        ];
    }
}