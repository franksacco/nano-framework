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

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteParser\Std;
use Nano\Middleware\CallableMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteGroupTest extends TestCase
{
    private function createRouter(): Router
    {
        return new Router(new Std(), new GroupCountBased());
    }

    public function testPrefix()
    {
        $router = $this->createRouter();

        $prefix = '/groupPrefix';
        $group = new RouteGroup($prefix, function () {}, $router);
        $this->assertSame($prefix, $group->getPrefix());
    }

    public function testGroupPrefix()
    {
        $router = $this->createRouter();

        $group = new RouteGroup('/prefix', function (RouteGroup $router) {
            $router->get('/test', 'handler');
        }, $router);

        $this->assertEquals([
            new Route('GET', '/prefix/test', 'handler')
        ], $group->getRoutes());
    }

    public function testNestedGroupPrefix()
    {
        $router = $this->createRouter();

        $group = new RouteGroup('/group1', function (RouteGroup $router) {
            $router->get('/route1', 'handler1');

            $router->group('/group2', function (RouteGroup $router) {
                $router->get('/route2', 'handler2');
            });

            $router->get('/route3', 'handler3');
        }, $router);


        $this->assertEquals([
            new Route('GET', '/group1/route1', 'handler1'),
            new Route('GET', '/group1/route3', 'handler3')
        ], $group->getRoutes());

        $this->assertEquals([
            new Route('GET', '/group1/route1', 'handler1'),
            new Route('GET', '/group1/group2/route2', 'handler2'),
            new Route('GET', '/group1/route3', 'handler3')
        ], $router->getRoutes());
    }

    public function testGroupMiddlewares()
    {
        $router = $this->createRouter();

        $middleware1 = new Middleware();
        $middleware2 = function (ServerRequestInterface $request,
                                 RequestHandlerInterface $handler): ResponseInterface {
            return $handler->handle($request);
        };
        $middleware3 = [Middleware::class, 'process'];

        $group = new RouteGroup('/prefix', function (RouteGroup $router) use ($middleware1) {
            $router->get('/test', 'handler')
                ->middleware($middleware1);
        }, $router);
        $group->middleware($middleware2);
        $group->middleware($middleware3);

        $this->assertEquals([
            (new Route('GET', '/prefix/test', 'handler'))
                ->middleware($middleware1)
                ->middleware(new CallableMiddleware($middleware2))
                ->middleware(new CallableMiddleware($middleware3))
        ], $router->getRoutes());
    }

    public function testNestedGroupMiddleware()
    {
        $router = $this->createRouter();

        $middleware1 = new Middleware();
        $middleware2 = function (ServerRequestInterface $request,
                                 RequestHandlerInterface $handler): ResponseInterface {
            return $handler->handle($request);
        };

        $group = new RouteGroup('/group1', function (RouteGroup $router) use ($middleware2) {
            $router->get('/route1', 'handler1');

            $router->group('/group2', function (RouteGroup $router) {
                $router->get('/test', 'handler2');
            })->middleware($middleware2);

            $router->get('/route3', 'handler3');
        }, $router);
        $group->middleware($middleware1);


        $this->assertEquals([
            (new Route('GET', '/group1/route1', 'handler1', null))
                ->middleware($middleware1),
            (new Route('GET', '/group1/route3', 'handler3', null))
                ->middleware($middleware1)
        ], $group->getRoutes());

        $this->assertEquals([
            (new Route('GET', '/group1/route1', 'handler1', null))
                ->middleware($middleware1),
            (new Route('GET', '/group1/group2/test', 'handler2', null))
                ->middleware($middleware2)
                ->middleware($middleware1),
            (new Route('GET', '/group1/route3', 'handler3', null))
                ->middleware($middleware1)
        ], $router->getRoutes());
    }
}

class Middleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}