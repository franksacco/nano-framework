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

use Laminas\Diactoros\ServerRequest;
use Nano\Auth\AuthenticableInterface;
use Nano\Auth\Exception\UnexpectedValueException;
use Nano\Auth\GuardInterface;
use Nano\Config\ConfigurationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;

class BasicHttpAuthMiddlewareTest extends TestCase
{
    public function testRealm()
    {
        /** @var BasicHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(BasicHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setRealm', 'getRealm'])
            ->getMock();

        $realm = 'SomeRealm';
        $mock->setRealm($realm);

        $this->assertSame($realm, $mock->getRealm());
    }

    public function testDefaultRealm()
    {
        $realm = 'SomeRealm';

        $container = $this->createMock(ContainerInterface::class);
        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('http_basic.realm')
            ->willReturn($realm);

        /** @var BasicHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(BasicHttpAuthMiddleware::class)
            ->setConstructorArgs([$container, $config, null])
            ->setMethodsExcept(['setConfiguration', 'getConfig', 'getRealm'])
            ->getMock();

        $this->assertSame($realm, $mock->getRealm());
    }

    public function testAuthentication()
    {
        $username = 'test';
        $password = '1234';

        $user = $this->createMock(AuthenticableInterface::class);

        $guard = $this->createMock(GuardInterface::class);
        $guard->expects($this->once())
            ->method('authenticateByCredentials')
            ->with($username, $password)
            ->willReturn($user);

        $request = (new ServerRequest([], [], 'https://www.example.com/'))
            ->withHeader('Authorization', 'Basic '. base64_encode("{$username}:{$password}"));
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        /** @var BasicHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(BasicHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setLogger', 'setGuard', 'getGuard', 'process'])
            ->getMock();
        $mock->setLogger(new NullLogger());
        $mock->setGuard($guard);

        $this->assertSame($response, $mock->process($request, $handler));
    }

    public function testNotAuthenticatedResponse()
    {
        $request = new ServerRequest([], [], 'https://www.example.com/');
        $handler = $this->createMock(RequestHandlerInterface::class);

        /** @var BasicHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(BasicHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setLogger', 'setRealm', 'getRealm', 'process'])
            ->getMock();
        $mock->setLogger(new NullLogger());
        $mock->setRealm('SomeRealm');

        $response = $mock->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(['Basic realm="SomeRealm", charset="UTF-8"'],
            $response->getHeader('WWW-Authenticate'));
    }

    public function testInsecureChannel()
    {
        $request = new ServerRequest([], [], 'http://www.example.com/');
        $handler = $this->createMock(RequestHandlerInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['http_basic.allowlist', ['localhost', '127.0.0.1'], []],
                ['http_basic.secure', true, true]
            ]);
        /** @var BasicHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(BasicHttpAuthMiddleware::class)
            ->setConstructorArgs([$container, $config, null])
            ->setMethodsExcept(['setConfiguration', 'setLogger', 'getConfig', 'process'])
            ->getMock();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot perform basic HTTP ' .
            'authentication through an insecure channel');

        $mock->process($request, $handler);
    }

    public function testInvalidCredentials()
    {
        $request = (new ServerRequest([], [], 'https://www.example.com/'))
            ->withHeader('Authorization', 'Basic '. base64_encode('wrong_format'));
        $handler = $this->createMock(RequestHandlerInterface::class);

        /** @var BasicHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(BasicHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setLogger', 'setRealm', 'getRealm', 'process'])
            ->getMock();
        $mock->setLogger(new NullLogger());
        $mock->setRealm('SomeRealm');

        $response = $mock->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(['Basic realm="SomeRealm", charset="UTF-8"'],
            $response->getHeader('WWW-Authenticate'));
    }
}