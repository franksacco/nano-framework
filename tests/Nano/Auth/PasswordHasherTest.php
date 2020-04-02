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

use Nano\Config\ConfigurationInterface;
use PHPUnit\Framework\TestCase;

class PasswordHasherTest extends TestCase
{
    public function testConstruct()
    {
        $algorithm = PASSWORD_BCRYPT;
        $options   = ['cost' => 10];

        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['passwords.algorithm', PASSWORD_DEFAULT, $algorithm],
                ['passwords.options', [], $options]
            ]);

        $hasher = new PasswordHasher($config);
        $this->assertSame($algorithm, $hasher->getAlgorithm());
        $this->assertSame($options, $hasher->getOptions());
    }

    public function testSetters()
    {
        $algorithm = PASSWORD_BCRYPT;
        $options   = ['cost' => 10];

        $hasher = $this->getMockBuilder(PasswordHasher::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setAlgorithm', 'setOptions', 'getAlgorithm', 'getOptions'])
            ->getMock();
        $hasher->setAlgorithm($algorithm);
        $hasher->setOptions($options);

        $this->assertSame($algorithm, $hasher->getAlgorithm());
        $this->assertSame($options, $hasher->getOptions());
    }

    public function testGetInfo()
    {

        $hash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 10]);

        $hasher = $this->getMockBuilder(PasswordHasher::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getInfo'])
            ->getMock();

        $this->assertSame([
            'algo'     => PASSWORD_BCRYPT,
            'algoName' => 'bcrypt',
            'options'  => ['cost' => 10]
        ], $hasher->getInfo($hash));
    }

    public function testCheck()
    {
        $algorithm     = PASSWORD_BCRYPT;
        $options       = ['cost' => 10];
        $password      = 'password';
        $wrongPassword = 'wrong_password';
        $hash          = password_hash($password, $algorithm, $options);

        $hasher = $this->getMockBuilder(PasswordHasher::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['check'])
            ->getMock();

        $this->assertTrue($hasher->check($password, $hash));
        $this->assertFalse($hasher->check($wrongPassword, $hash));
    }

    public function testHash()
    {
        $algorithm = PASSWORD_BCRYPT;
        $options   = ['cost' => 10];

        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['passwords.algorithm', PASSWORD_DEFAULT, $algorithm],
                ['passwords.options', [], $options]
            ]);

        $hasher = new PasswordHasher($config);
        $hash = $hasher->hash('password');
        $this->assertTrue(password_verify('password', $hash));
    }

    public function testNeedsRehash()
    {
        $algorithm = PASSWORD_BCRYPT;
        $options   = ['cost' => 10];
        $hash      = password_hash('password', $algorithm, ['cost' => 9]);

        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['passwords.algorithm', PASSWORD_DEFAULT, $algorithm],
                ['passwords.options', [], $options]
            ]);

        $hasher = new PasswordHasher($config);
        $this->assertTrue($hasher->needsRehash($hash));
    }
}