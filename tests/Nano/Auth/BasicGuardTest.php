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

namespace Nano\Auth;

use Nano\Auth\Exception\NotAuthenticatedException;
use Nano\Auth\Exception\UnexpectedValueException;
use Nano\Config\ConfigurationInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class BasicGuardTest extends TestCase
{
    public function testDefaultProvider()
    {
        $providerClass = 'providerClass';
        $provider = $this->createMock(ProviderInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with($providerClass)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with($providerClass)
            ->willReturn($provider);

        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('auth.provider')
            ->willReturn($providerClass);

        $basicGuard = new BasicGuard($container, $config);
        $this->assertSame($provider, $basicGuard->getProvider());
    }

    public function testInvalidProvider()
    {
        $container = $this->createMock(ContainerInterface::class);
        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('auth.provider')
            ->willReturn(null);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('User provider set in configuration not implements Nano\Auth\ProviderInterface');

        new BasicGuard($container, $config);
    }

    public function testSetProvider()
    {
        $basicGuard = $this->getMockBuilder(BasicGuard::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setProvider', 'getProvider'])
            ->getMock();

        $expected = $this->createMock(ProviderInterface::class);
        $basicGuard->setProvider($expected);

        $this->assertSame($expected, $basicGuard->getProvider());
    }

    public function testAuthenticateByAuthIdentifier()
    {
        $basicGuard = $this->getMockBuilder(BasicGuard::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setProvider', 'authenticateByAuthIdentifier'])
            ->getMock();

        $user = $this->createMock(AuthenticableInterface::class);

        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())
            ->method('getUserByAuthIdentifier')
            ->with('test')
            ->willReturn($user);

        $basicGuard->setProvider($provider);
        $this->assertSame($user, $basicGuard->authenticateByAuthIdentifier('test'));
    }

    public function testNotAuthenticateByAuthIdentifier()
    {
        $basicGuard = $this->getMockBuilder(BasicGuard::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setProvider', 'authenticateByAuthIdentifier'])
            ->getMock();

        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())
            ->method('getUserByAuthIdentifier')
            ->with('test')
            ->willReturn(null);
        $basicGuard->setProvider($provider);

        $this->expectException(NotAuthenticatedException::class);
        $this->expectExceptionMessage('The user with identifier "test" does not exist');

        $basicGuard->authenticateByAuthIdentifier('test');
    }

    public function testAuthenticateByCredentials()
    {
        $username = 'username';
        $password = 'password';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $user = $this->createMock(AuthenticableInterface::class);
        $user->expects($this->once())
            ->method('getAuthSecret')
            ->willReturn($hashedPassword);

        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['passwords.algorithm', PASSWORD_DEFAULT],
                ['passwords.options', []]
            ]);

        $basicGuard = $this->getMockBuilder(BasicGuard::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['authenticateByCredentials'])
            ->getMock();
        $basicGuard->expects($this->once())
            ->method('authenticateByAuthIdentifier')
            ->with($username)
            ->willReturn($user);
        $basicGuard->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->assertSame($user,
            $basicGuard->authenticateByCredentials($username, $password));
    }

    public function testNotAuthenticatedByCredentials()
    {
        $username = 'username';
        $password = 'wrong_password';
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);

        $user = $this->createMock(AuthenticableInterface::class);
        $user->expects($this->once())
            ->method('getAuthSecret')
            ->willReturn($hashedPassword);

        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['passwords.algorithm', PASSWORD_DEFAULT],
                ['passwords.options', []]
            ]);

        $basicGuard = $this->getMockBuilder(BasicGuard::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['authenticateByCredentials'])
            ->getMock();
        $basicGuard->expects($this->once())
            ->method('authenticateByAuthIdentifier')
            ->with($username)
            ->willReturn($user);
        $basicGuard->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->expectException(NotAuthenticatedException::class);
        $this->expectExceptionMessage('Secret provided for user "username" is not correct');

        $basicGuard->authenticateByCredentials($username, $password);
    }

    public function testAuthenticateByToken()
    {
        $basicGuard = $this->getMockBuilder(BasicGuard::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['authenticateByToken'])
            ->getMock();

        $this->expectException(NotAuthenticatedException::class);
        $this->expectExceptionMessage('Authentication through token not supported');

        $basicGuard->authenticateByToken('test');
    }
}