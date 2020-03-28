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

namespace Nano\Routing;

use League\Container\Container;
use League\Container\ReflectionContainer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DispatcherTest extends TestCase
{
    /**
     * @dataProvider dispatchProvider
     * @param $request
     * @param $response
     */
    public function testValidHandler($request, $response)
    {
        $container = new Container();
        $container->delegate(
            (new ReflectionContainer())->cacheResolutions()
        );
        $dispatcher = new Dispatcher($container);

        $this->assertSame($response, $dispatcher->dispatch($request));
    }

    public function dispatchProvider()
    {
        $response = $this->createMock(ResponseInterface::class);
        DummyHandler::$response = $response;

        $validHandlers = [
            'Nano\Routing\DummyHandler::action',
            'Nano\Routing\DummyHandler@action',
            [DummyHandler::class, 'action'],
            [new DummyHandler(), 'action'],
            new DummyHandler(),
            DummyHandler::class,
            'Nano\Routing\validHandler',
            function () use ($response) {return $response;}
        ];

        $data = [];
        foreach ($validHandlers as $handler) {
            $data[] = [
                $this->generateRequest($handler),
                $response
            ];
        }
        return $data;
    }

    private function generateRequest($action)
    {
        $mock = $this->createMock(ServerRequestInterface::class);
        $mock->expects($this->exactly(2))
            ->method('getAttribute')
            ->willReturnMap([
                [Dispatcher::REQUEST_HANDLER_ACTION, null, $action],
                [Dispatcher::REQUEST_HANDLER_PARAMS, null, []]
            ]);
        return $mock;
    }

    /**
     * @dataProvider invalidHandlerProvider
     * @param $request
     * @param $message
     */
    public function testInvalidHandler($request, $message)
    {
        $container = new Container();
        $container->delegate(
            (new ReflectionContainer())->cacheResolutions()
        );
        $dispatcher = new Dispatcher($container);

        $this->expectException(InvalidRequestHandlerException::class);
        $this->expectExceptionMessage($message);

        $dispatcher->dispatch($request);
    }

    public function invalidHandlerProvider()
    {
        return [
            [$this->generateRequest(null), 'Invalid request handler provided'],
            [
                $this->generateRequest('NonExistentClass'),
                'Unable to resolve class NonExistentClass for a non-existent ' .
                  'class or a DI container without auto-wiring'
            ]
        ];
    }
}

class DummyHandler
{
    public static $response;
    public function action() {
        return self::$response;
    }
    public function __invoke() {
        return self::$response;
    }
}

function validHandler() {
    return DummyHandler::$response;
}