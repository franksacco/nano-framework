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

use Nano\Middleware\CallableMiddleware;
use Nano\Middleware\InvalidMiddlewareException;
use Nano\Routing\FastRoute\Route;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareTraitTest extends TestCase
{
    public function testMiddleware()
    {
        $classname = ValidMiddleware::class;
        $instance  = new ValidMiddleware();
        $callable  = [new ValidMiddleware(), 'process'];
        $function  = function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            return $handler->handle($request);
        };

        $trait = new ClassWithMiddlewareTrait();

        $trait->middleware($classname);
        $trait->middleware($instance);
        $trait->middleware($callable);
        $trait->middleware($function);

        $this->assertEquals([
            new ValidMiddleware(),
            $instance,
            new CallableMiddleware($callable),
            new CallableMiddleware($function)
        ], $trait->getMiddlewares());
    }

    public function testMiddlewareContainer()
    {
        $route = new Route('GET', '/', 'handler', null, new TestContainer());

        $route->middleware(ValidMiddleware::class);
        $route->middleware('valid-middleware');

        $this->assertEquals([
            new ValidMiddleware(),
            new ValidMiddleware()
        ], $route->getMiddlewares());
    }

    /**
     * @dataProvider invalidMiddlewareProvider
     * @param $middleware
     */
    public function testInvalidMiddleware($middleware)
    {
        $route = new Route('GET', '/', 'handler');

        $this->expectException(InvalidMiddlewareException::class);
        $route->middleware($middleware);
    }

    public function invalidMiddlewareProvider()
    {
        return [
            [InvalidMiddleware::class],
            [new InvalidMiddleware()],
            ['NonExistentClass']
        ];
    }
}

class ClassWithMiddlewareTrait
{
    use MiddlewareTrait;
}

class TestContainer implements ContainerInterface
{
    public function get($id)
    {
        if (in_array($id, [ValidMiddleware::class, 'valid-middleware'])) {
            return new ValidMiddleware();
        }
        return null;
    }

    public function has($id)
    {
        return in_array($id, [ValidMiddleware::class, 'valid-middleware']);
    }
}

class ValidMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}

class InvalidMiddleware
{

}