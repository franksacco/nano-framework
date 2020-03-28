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
use Psr\Container\ContainerInterface;

class RouterTest extends TestCase
{
    public function testGetContainer()
    {
        $containerMock = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $router = new Router(new Std(), new GroupCountBased(), $containerMock);
        $this->assertSame($containerMock, $router->getContainer());
    }

    public function testGetData()
    {
        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['calculateData'])
            ->getMock();

        $expected = ['some-data'];
        $router->expects($this->once())
            ->method('calculateData')
            ->willReturn($expected);

        $this->assertSame($expected, $router->getData());
    }

    public function testGetReverseData()
    {
        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['calculateReverseData'])
            ->getMock();

        $expected = ['some-data'];
        $router->expects($this->once())
            ->method('calculateReverseData')
            ->willReturn($expected);

        $this->assertSame($expected, $router->getReverseData());
    }
}