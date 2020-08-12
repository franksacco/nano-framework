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

class DigestHttpAuthMiddlewareTest extends TestCase
{
    public function testRealm()
    {
        /** @var DigestHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(DigestHttpAuthMiddleware::class)
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
            ->with('http_digest.realm')
            ->willReturn($realm);

        /** @var DigestHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(DigestHttpAuthMiddleware::class)
            ->setConstructorArgs([$container, $config, null])
            ->setMethodsExcept(['setConfiguration', 'getConfig', 'getRealm'])
            ->getMock();

        $this->assertSame($realm, $mock->getRealm());
    }

    public function testNonce()
    {
        /** @var DigestHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(DigestHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setNonce', 'getNonce'])
            ->getMock();

        $nonce = 'SomeNonce';
        $mock->setNonce($nonce);

        $this->assertSame($nonce, $mock->getNonce());
    }

    public function testDefaultNonce()
    {
        /** @var DigestHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(DigestHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getNonce'])
            ->getMock();

        $this->assertIsString($mock->getNonce());
    }

    public function testAlgorithm()
    {
        /** @var DigestHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(DigestHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setAlgorithm', 'getAlgorithm'])
            ->getMock();

        $mock->setAlgorithm(DigestHttpAuthMiddleware::ALGORITHM_MD5);

        $this->assertSame(DigestHttpAuthMiddleware::ALGORITHM_MD5, $mock->getAlgorithm());
    }

    public function testDefaultAlgorithm()
    {
        /** @var DigestHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(DigestHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getAlgorithm'])
            ->getMock();

        $this->assertSame(DigestHttpAuthMiddleware::ALGORITHM_SHA256, $mock->getAlgorithm());
    }

    public function testInvalidAlgorithm()
    {
        /** @var DigestHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(DigestHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setAlgorithm'])
            ->getMock();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The algorithm is not valid');

        $mock->setAlgorithm('not-valid');
    }

    public function testAuthentication()
    {
        $username = 'test';
        $password = '1234';

        $user = $this->createMock(AuthenticableInterface::class);
        $user->expects($this->once())
            ->method('getAuthSecret')
            ->willReturn($password);

        $guard = $this->createMock(GuardInterface::class);
        $guard->expects($this->once())
            ->method('authenticateByAuthIdentifier')
            ->with($username)
            ->willReturn($user);

        $realm  = 'SomeRealm';
        $nonce  = uniqid();
        $uri    = '/';
        $nc     = '00000001';
        $cnonce = md5($nonce);
        $qop    = 'auth';
        $a1 = "{$username}:{$realm}:{$password}";
        $a2 = "GET:{$uri}";
        $response = hash('sha256', implode(':', [
            hash('sha256', $a1),
            $nonce, $nc, $cnonce, $qop,
            hash('sha256', $a2)
        ]));
        $header = sprintf(
            'Digest username="%s", realm="%s", uri="%s", qop=%s, nonce="%s", nc=%s, cnonce="%s", response="%s"',
            $username, $realm, $uri, $qop, $nonce, $nc, $cnonce, $response
        );

        $request = (new ServerRequest([], [], 'https://www.example.com/'))
            ->withHeader('Authorization', $header);
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        /** @var DigestHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(DigestHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setLogger', 'setGuard', 'getGuard', 'setRealm',
                'getRealm', 'setNonce', 'getNonce', 'getAlgorithm', 'process'])
            ->getMock();
        $mock->setLogger(new NullLogger());
        $mock->setGuard($guard);
        $mock->setRealm($realm);
        $mock->setNonce($nonce);

        $this->assertSame($response, $mock->process($request, $handler));
    }

    public function testNotAuthenticatedResponse()
    {
        $request = new ServerRequest([], [], 'https://www.example.com/');
        $handler = $this->createMock(RequestHandlerInterface::class);

        $realm = 'SomeRealm';
        $nonce = 'SomeNonce';

        /** @var DigestHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(DigestHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setLogger', 'setRealm', 'getRealm', 'setNonce', 'getNonce', 'getAlgorithm', 'process'])
            ->getMock();
        $mock->setLogger(new NullLogger());
        $mock->setRealm($realm);
        $mock->setNonce($nonce);

        $response = $mock->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $expected = sprintf(
            'Digest realm="%s",qop="auth",algorithm=%s,nonce="%s",opaque="%s"',
            $realm, 'SHA-256', $nonce, md5($realm)
        );
        $this->assertEquals([$expected], $response->getHeader('WWW-Authenticate'));
    }

    public function testInvalidAuthorizationHeader()
    {
        $request = (new ServerRequest([], [], 'https://www.example.com/'))
            ->withHeader('Authorization', 'Digest '. base64_encode('wrong_format'));
        $handler = $this->createMock(RequestHandlerInterface::class);

        $realm = 'SomeRealm';
        $nonce = 'SomeNonce';

        /** @var DigestHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(DigestHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setLogger', 'setRealm', 'getRealm',
                'setNonce', 'getNonce', 'getAlgorithm', 'process'])
            ->getMock();
        $mock->setLogger(new NullLogger());
        $mock->setRealm($realm);
        $mock->setNonce($nonce);

        $response = $mock->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $expected = sprintf(
            'Digest realm="%s",qop="auth",algorithm=%s,nonce="%s",opaque="%s"',
            $realm, 'SHA-256', $nonce, md5($realm)
        );
        $this->assertEquals([$expected], $response->getHeader('WWW-Authenticate'));
    }

    public function testInvalidResponseChallenge()
    {
        $username = 'test';
        $password = '1234';

        $user = $this->createMock(AuthenticableInterface::class);
        $user->expects($this->once())
            ->method('getAuthSecret')
            ->willReturn($password);

        $guard = $this->createMock(GuardInterface::class);
        $guard->expects($this->once())
            ->method('authenticateByAuthIdentifier')
            ->with($username)
            ->willReturn($user);

        $realm  = 'SomeRealm';
        $nonce  = uniqid();
        $uri    = '/';
        $nc     = '00000001';
        $cnonce = md5($nonce);
        $qop    = 'auth';
        $response = hash('sha256', 'wrong-response-challenge');
        $header = sprintf(
            'Digest username="%s", realm="%s", uri="%s", qop=%s, nonce="%s", nc=%s, cnonce="%s", response="%s"',
            $username, $realm, $uri, $qop, $nonce, $nc, $cnonce, $response
        );

        $request = (new ServerRequest([], [], 'https://www.example.com/'))
            ->withHeader('Authorization', $header);
        $handler = $this->createMock(RequestHandlerInterface::class);

        /** @var DigestHttpAuthMiddleware|MockObject $mock */
        $mock = $this->getMockBuilder(DigestHttpAuthMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setLogger', 'setGuard', 'getGuard', 'setRealm',
                'getRealm', 'setNonce', 'getNonce', 'getAlgorithm', 'process'])
            ->getMock();
        $mock->setLogger(new NullLogger());
        $mock->setGuard($guard);
        $mock->setRealm($realm);
        $mock->setNonce($nonce);

        $response = $mock->process($request, $handler);
        $this->assertSame(401, $response->getStatusCode());
    }
}