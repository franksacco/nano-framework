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

namespace Nano\Routing\FastRoute\Traits;

use Nano\Routing\FastRoute\Route;
use PHPUnit\Framework\TestCase;

class RoutesTraitTest extends TestCase
{
    public function testRoute()
    {
        $trait = new ClassWithRoutesTrait();
        $trait->route('GET', '/', 'handler', 'name');
        $trait->route('HEAD', '/', 'handler');
        $trait->route('POST', '/', 'handler');
        $trait->route('PUT', '/', 'handler');
        $trait->route('PATCH', '/', 'handler');
        $trait->route('DELETE', '/', 'handler');

        $this->assertEquals([
            new Route('GET', '/', 'handler', 'name'),
            new Route('HEAD', '/', 'handler', null),
            new Route('POST', '/', 'handler', null),
            new Route('PUT', '/', 'handler', null),
            new Route('PATCH', '/', 'handler', null),
            new Route('DELETE', '/', 'handler', null)
        ], $trait->getRoutes());
    }

    public function testGet()
    {
        $trait = new ClassWithRoutesTrait();
        $this->assertEquals(
            new Route('GET', '/', 'handler', 'name'),
            $trait->get('/', 'handler', 'name')
        );
    }

    public function testHead()
    {
        $trait = new ClassWithRoutesTrait();
        $this->assertEquals(
            new Route('HEAD', '/', 'handler', null),
            $trait->head('/', 'handler')
        );
    }

    public function testPost()
    {
        $trait = new ClassWithRoutesTrait();
        $this->assertEquals(
            new Route('POST', '/', 'handler', null),
            $trait->post('/', 'handler')
        );
    }

    public function testPut()
    {
        $trait = new ClassWithRoutesTrait();
        $this->assertEquals(
            new Route('PUT', '/', 'handler', null),
            $trait->put('/', 'handler')
        );
    }

    public function testPatch()
    {
        $trait = new ClassWithRoutesTrait();
        $this->assertEquals(
            new Route('PATCH', '/', 'handler', null),
            $trait->patch('/', 'handler')
        );
    }

    public function testDelete()
    {
        $trait = new ClassWithRoutesTrait();
        $this->assertEquals(
            new Route('DELETE', '/', 'handler', null),
            $trait->delete('/', 'handler')
        );
    }
}

class ClassWithRoutesTrait
{
    use RoutesTrait;
}