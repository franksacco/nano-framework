<?php /** @noinspection PhpUndefinedMethodInspection */
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

use Nano\Routing\Dispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AbstractApplicationTest extends TestCase
{
    public function testGetRootPath()
    {
        $app = new DummyApplication('/root/path/');
        $this->assertSame('/root/path', $app->getRootPath());
    }

    public function testRun()
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $app = $this->getMockBuilder(DummyApplication::class)
            ->setConstructorArgs([''])
            ->setMethodsExcept(['setLeagueContainer', 'run'])
            ->getMock();
        $app->expects($this->once())
            ->method('process')
            ->willReturn($response);

        $this->assertSame($response, $app->run($request));
    }

    public function testProcess()
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $action   = function () use ($response) {
            return $response;
        };
        $request->expects($this->exactly(2))
            ->method('getAttribute')
            ->willReturnMap([
                [Dispatcher::REQUEST_HANDLER_ACTION, null, $action],
                [Dispatcher::REQUEST_HANDLER_PARAMS, null, []]
            ]);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $app = $this->getMockBuilder(DummyApplication::class)
            ->setConstructorArgs([''])
            ->setMethodsExcept(['setLeagueContainer', 'process'])
            ->getMock();

        $this->assertEquals($response, $app->process($request, $handler));
    }
}

class DummyApplication extends AbstractApplication
{
    protected function onBoot() {/* do nothing */}
    protected function middleware(MiddlewareQueue $middleware) {/* do nothing */}
}