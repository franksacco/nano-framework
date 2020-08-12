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

use Laminas\Diactoros\Response\RedirectResponse;
use Nano\Auth\AuthenticableInterface;
use Nano\Auth\Exception\UnexpectedValueException;
use Nano\Auth\GuardInterface;
use Nano\Config\ConfigurationInterface;
use Nano\Session\SessionInterface;
use Nano\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionAuthMiddlewareTest extends TestCase
{
    public function testAuthentication()
    {
        $username = 'username';
        $datetime = date('Y-m-d H:i:s', time() - 200);

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([SessionAuthMiddleware::AUTH_SESSION_USER],
                [SessionAuthMiddleware::AUTH_SESSION_UPDATED])
            ->willReturnOnConsecutiveCalls($username, $datetime);
        $session->expects($this->once())
            ->method('set')
            ->with(SessionAuthMiddleware::AUTH_SESSION_UPDATED, date('Y-m-d H:i:s'));

        $user = $this->createMock(AuthenticableInterface::class);

        $guard = $this->createMock(GuardInterface::class);
        $guard->expects($this->once())
            ->method('authenticateByAuthIdentifier')
            ->with($username)
            ->willReturn($user);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($session);
        $requestWithUser = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('withAttribute')
            ->with(AuthMiddleware::USER_ATTRIBUTE, $user)
            ->willReturn($requestWithUser);
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($requestWithUser)
            ->willReturn($response);

        $container = $this->createMock(ContainerInterface::class);
        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('session.expiration', 1200)
            ->willReturn(1200);

        /** @var SessionAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(SessionAuthMiddleware::class)
            ->setConstructorArgs([$container, $config])
            ->setMethodsExcept(['process', 'setConfiguration', 'setLogger',
                'getConfig', 'setGuard', 'getGuard'])
            ->getMock();
        $mock->setGuard($guard);

        $this->assertSame($response, $mock->process($request, $handler));
    }

    public function testInvalidSession()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn('wrong_value');
        $handler = $this->createMock(RequestHandlerInterface::class);

        /** @var SessionAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(SessionAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['process'])
            ->getMock();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Nano\Session\SessionInterface instance required in server request');

        $mock->process($request, $handler);
    }

    public function testInvalidUserIdentifier()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('get')
            ->with(SessionAuthMiddleware::AUTH_SESSION_USER)
            ->willReturn(null);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($session);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(['session.redirect', true], ['app.base_url', null],
                ['session.redirect_path', '/login'])
            ->willReturnOnConsecutiveCalls(true, 'https://www.example.com', '/login');

        /** @var SessionAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(SessionAuthMiddleware::class)
            ->setConstructorArgs([$container, $config])
            ->setMethodsExcept(['process', 'setConfiguration', 'setLogger', 'getConfig'])
            ->getMock();

        $response = $mock->process($request, $handler);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        /** @var RedirectResponse $response */
        $this->assertSame(['https://www.example.com/login'], $response->getHeader('Location'));
    }

    public function testInvalidUpdatedDatetime()
    {
        $username = 'username';

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([SessionAuthMiddleware::AUTH_SESSION_USER],
                [SessionAuthMiddleware::AUTH_SESSION_UPDATED])
            ->willReturnOnConsecutiveCalls($username, 'invalid');

        $user = $this->createMock(AuthenticableInterface::class);

        $guard = $this->createMock(GuardInterface::class);
        $guard->expects($this->once())
            ->method('authenticateByAuthIdentifier')
            ->with($username)
            ->willReturn($user);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($session);
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $container = $this->createMock(ContainerInterface::class);
        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('session.redirect', true)
            ->willReturn(false);

        /** @var SessionAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(SessionAuthMiddleware::class)
            ->setConstructorArgs([$container, $config])
            ->setMethodsExcept(['process', 'setConfiguration', 'setLogger',
                'getConfig', 'setGuard', 'getGuard'])
            ->getMock();
        $mock->setGuard($guard);

        $this->assertSame($response, $mock->process($request, $handler));
    }

    public function testInvalidExpiration()
    {
        $username = 'username';
        $datetime = date('Y-m-d H:i:s', time() - 200);

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([SessionAuthMiddleware::AUTH_SESSION_USER],
                [SessionAuthMiddleware::AUTH_SESSION_UPDATED])
            ->willReturnOnConsecutiveCalls($username, $datetime);

        $user = $this->createMock(AuthenticableInterface::class);

        $guard = $this->createMock(GuardInterface::class);
        $guard->expects($this->once())
            ->method('authenticateByAuthIdentifier')
            ->with($username)
            ->willReturn($user);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($session);
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $container = $this->createMock(ContainerInterface::class);
        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['session.expiration', 1200], ['session.redirect', true])
            ->willReturnOnConsecutiveCalls(-1, false);

        /** @var SessionAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(SessionAuthMiddleware::class)
            ->setConstructorArgs([$container, $config])
            ->setMethodsExcept(['process', 'setConfiguration', 'setLogger',
                'getConfig', 'setGuard', 'getGuard'])
            ->getMock();
        $mock->setGuard($guard);

        $this->assertSame($response, $mock->process($request, $handler));
    }

    public function testLogin()
    {
        $user = $this->createMock(AuthenticableInterface::class);
        $user->expects($this->once())
            ->method('getAuthIdentifier')
            ->willReturn('username');

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->atLeastOnce())
            ->method('set');
        $session->expects($this->once())
            ->method('regenerate')
            ->with(true);

        SessionAuthMiddleware::login($session, $user);
    }

    public function testLogout()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->atLeastOnce())
            ->method('remove');
        $session->expects($this->once())
            ->method('regenerate')
            ->with(true);

        SessionAuthMiddleware::logout($session);
    }
}