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
use Nano\Routing\FastRoute\Result\FoundResult;
use Nano\Routing\FastRoute\Result\MethodNotAllowedResult;
use Nano\Routing\FastRoute\Result\NotFoundResult;
use PHPUnit\Framework\TestCase;

class FastRouteTest extends TestCase
{
    public function testDispatchFound()
    {
        $fastRoute = new FastRoute(function (Router $router) {
            $router->get('/test/{something}', 'handler')
                ->middleware(function () {});
        });
        $fastRoute->loadData();
        $result = $fastRoute->dispatch('GET', '/test/foo');

        $this->assertTrue($result instanceof FoundResult);
        $this->assertSame([
            'something' => 'foo'
        ], $result->getParams());
        $this->assertSame('handler', $result->getHandler());
        $this->assertEquals([
            new CallableMiddleware(function () {})
        ], $result->getMiddlewares());
    }

    public function testDispatchMethodNotAllowed()
    {
        $fastRoute = new FastRoute(function (Router $router) {
            $router->get('/test', 'handler');
            $router->head('/test', 'handler');
        });
        $fastRoute->loadData();
        $result = $fastRoute->dispatch('POST', '/test');

        $this->assertTrue($result instanceof MethodNotAllowedResult);
        $this->assertSame(['GET', 'HEAD'], $result->getAllowedMethods());
    }

    public function testDispatchNotFound()
    {
        $fastRoute = new FastRoute(function (Router $router) {});
        $fastRoute->loadData();
        $result = $fastRoute->dispatch('GET', '/test');

        $this->assertTrue($result instanceof NotFoundResult);
    }

    public function testDispatchNotLoadedData()
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Routing data has not yet been loaded');

        $fastRoute = new FastRoute(function () {});
        $fastRoute->dispatch('GET', '/');
    }

    public function testGetUrlGenerator()
    {
        $fastRoute = new FastRoute(function () {});
        $fastRoute->loadData();
        $this->assertEquals(new UrlGenerator([]), $fastRoute->getUrlGenerator());
    }

    public function testGetUrlGeneratorNotLoadedData()
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Routing data has not yet been loaded');

        $fastRoute = new FastRoute(function () {});
        $fastRoute->getUrlGenerator();
    }
}