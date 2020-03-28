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
use PHPUnit\Framework\TestCase;

class UrlGeneratorTest extends TestCase
{
    private function createDummyData()
    {
        $router = new Router(new Std(), new GroupCountBased());

        $router->get('/test', 'handler0', 'test0');
        $router->get('/test/{something}', 'handler1', 'test1');
        $router->get('/test/{param1}[/{param2}]', 'handler2', 'test2');

        return $router->getReverseData();
    }

    public function testGetPath()
    {
        $urlGenerator = new UrlGenerator($this->createDummyData());

        $this->assertSame('/test', $urlGenerator->getPath('test0'));
        $this->assertSame('/test/foo', $urlGenerator->getPath('test1', 'foo'));
        $this->assertSame('/test/foo', $urlGenerator->getPath('test2', 'foo'));
        $this->assertSame('/test/foo/bar', $urlGenerator->getPath('test2', 'foo', 'bar'));
    }

    public function testNotFoundRoute()
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('The route with name "not-exists" was not found');

        $urlGenerator = new UrlGenerator([]);
        $urlGenerator->getPath('not-exists');
    }

    public function testNotEnoughParams()
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Not enough parameters given for route "test1"');

        $urlGenerator = new UrlGenerator($this->createDummyData());
        $urlGenerator->getPath('test1');
    }

    public function testTooManyParams()
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Too many parameters given for route "test1"');

        $urlGenerator = new UrlGenerator($this->createDummyData());
        $urlGenerator->getPath('test1', 'foo', 'bar');
    }
}