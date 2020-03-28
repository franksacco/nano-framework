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

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteParser\Std;
use Nano\Routing\FastRoute\RouteGroup;
use Nano\Routing\FastRoute\Router;
use PHPUnit\Framework\TestCase;

class GroupsTraitTest extends TestCase
{
    public function testGroup()
    {
        $router = new Router(new Std(), new GroupCountBased());

        $trait = new ClassWithGroupsTrait($router);
        $trait->group('/prefix', function () {});
        $trait->group('/another-prefix', function () {});

        $this->assertEquals([
            new RouteGroup('/prefix', function () {}, $router),
            new RouteGroup('/another-prefix', function () {}, $router)
        ], $trait->getGroups());
    }
}

class ClassWithGroupsTrait
{
    use GroupsTrait;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }
}