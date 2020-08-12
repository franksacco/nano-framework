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

namespace Nano\Auth\Middleware;

use Nano\Auth\AuthenticableInterface;
use Nano\Auth\BasicGuard;
use Nano\Auth\Exception\NotAuthenticatedException;
use Nano\Auth\Exception\UnexpectedValueException;
use Nano\Auth\GuardInterface;
use Nano\Config\ConfigurationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;

class AuthMiddlewareTest extends TestCase
{
    public function testGuard()
    {
        /** @var AuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(AuthMiddleware::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $guard = $this->createMock(GuardInterface::class);
        $mock->setGuard($guard);

        $this->assertSame($guard, $mock->getGuard());
    }

    public function testDefaultGuard()
    {
        $guardClass = 'GuardClass';
        $guard = $this->createMock(GuardInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with($guardClass)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with($guardClass)
            ->willReturn($guard);

        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('guard', BasicGuard::class)
            ->willReturn($guardClass);

        /** @var AuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(AuthMiddleware::class)
            ->setConstructorArgs([$container, $config, null])
            ->getMockForAbstractClass();

        $this->assertSame($guard, $mock->getGuard());
    }

    public function testInvalidGuard()
    {
        $guardClass = 'GuardClass';

        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('guard', BasicGuard::class)
            ->willReturn($guardClass);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with($guardClass)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with($guardClass)
            ->willReturn(null);

        /** @var AuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(AuthMiddleware::class)
            ->setConstructorArgs([$container, $config, null])
            ->getMockForAbstractClass();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Authentication guard set in ' .
            'configuration not implements Nano\Auth\GuardInterface');

        $mock->getGuard();
    }

    public function testProcess()
    {
        $user    = $this->createMock(AuthenticableInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('withAttribute')
            ->with(AuthMiddleware::USER_ATTRIBUTE, $user)
            ->willReturn($request);
        $response = $this->createMock(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        /** @var AuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(AuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['process', 'setLogger'])
            ->getMockForAbstractClass();
        $mock->setLogger(new NullLogger());
        $mock->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willReturn($user);

        $this->assertSame($response, $mock->process($request, $handler));
    }

    public function testNotAuthenticated()
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        /** @var AuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(AuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['process', 'processError', 'setLogger'])
            ->getMockForAbstractClass();
        $mock->setLogger(new NullLogger());
        $mock->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willThrowException(new NotAuthenticatedException());

        $this->assertSame($response, $mock->process($request, $handler));
    }
}