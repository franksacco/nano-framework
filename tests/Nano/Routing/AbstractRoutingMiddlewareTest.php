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

use Nano\Config\ConfigurationInterface;
use Nano\Routing\FastRoute\Router;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AbstractRoutingMiddlewareTest extends TestCase
{
    public function testFoundResult()
    {
        $method = 'GET';
        $path   = '/test/foo';

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        $request = $this->getMockBuilder(ServerRequestMock::class)
            ->setMethodsExcept(['withAttribute', 'getAttribute'])
            ->getMock();
        $request->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);
        $request->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);

        $response = $this->createMock(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('has')
            ->willReturn(false);

        $configuration = $this->createMock(ConfigurationInterface::class);
        $configuration->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap([
                ['routing.cache', false, false],
                ['routing.cache_dir', '', '']
            ]));

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);

        $routingMiddleware = new DummyRoutingMiddleware($container, $configuration, $responseFactory);
        $routingMiddleware->handler = function (ServerRequestInterface $request) use ($response) {
            $this->assertSame(['something' => 'foo'], $request->getAttribute(Dispatcher::REQUEST_HANDLER_PARAMS));
            return $response;
        };

        $this->assertSame($response, $routingMiddleware->process($request, $handler));
    }

    public function testMethodNotAllowedResult()
    {
        $method = 'POST';
        $path   = '/test/foo';

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);
        $request->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);

        $response = $this->getMockBuilder(ResponseMock::class)
            ->setMethodsExcept(['withHeader'])
            ->getMock();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('has')
            ->willReturn(false);

        $configuration = $this->createMock(ConfigurationInterface::class);
        $configuration->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap([
                ['routing.cache', false, false],
                ['routing.cache_dir', '', '']
            ]));

        /** @noinspection PhpUndefinedMethodInspection */
        $methodNotAllowedResponse = $response->withHeader('Allow', 'GET');

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory->expects($this->once())
            ->method('createResponse')
            ->with(405)
            ->willReturn($response);

        $routingMiddleware = new DummyRoutingMiddleware($container, $configuration, $responseFactory);

        $this->assertSame($methodNotAllowedResponse, $routingMiddleware->process($request, $handler));
    }

    public function testNotFoundResult()
    {
        $method = 'GET';
        $path   = '/not-exists';

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);
        $request->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);

        $response = $this->getMockBuilder(ResponseMock::class)
            ->setMethodsExcept(['withHeader'])
            ->getMock();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('has')
            ->willReturn(false);

        $configuration = $this->createMock(ConfigurationInterface::class);
        $configuration->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap([
                ['routing.cache', false, false],
                ['routing.cache_dir', '', '']
            ]));

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory->expects($this->once())
            ->method('createResponse')
            ->with(404)
            ->willReturn($response);

        $routingMiddleware = new DummyRoutingMiddleware($container, $configuration, $responseFactory);

        $this->assertSame($response, $routingMiddleware->process($request, $handler));
    }
}

abstract class ServerRequestMock implements ServerRequestInterface
{
    private $attributes = [];

    public function withAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }
}

abstract class ResponseMock implements ResponseInterface
{
    public $headers = [];

    public function withHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }
}

class DummyRoutingMiddleware extends AbstractRoutingMiddleware
{
    public $handler;

    public function routing(Router $router)
    {
        $router->get('/test/{something}', $this->handler);
    }
}