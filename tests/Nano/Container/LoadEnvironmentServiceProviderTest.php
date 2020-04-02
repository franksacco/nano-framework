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

namespace Nano\Container;

use Dotenv\Dotenv;
use League\Container\Container;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class LoadEnvironmentServiceProviderTest extends TestCase
{
    private $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root', null, [
            '.env' => "TEST_VARIABLE=\"test\"\nSECRET_KEY=\"abc123\""
        ]);
    }

    public function testRegister()
    {
        $container = new Container();
        $container->addServiceProvider(
            new EnvironmentServiceProvider($this->root->url())
        );

        $this->assertSame('test', getenv('TEST_VARIABLE'));
        $this->assertSame('abc123', getenv('SECRET_KEY'));
        $this->assertTrue($container->get(Dotenv::class) instanceof Dotenv);
    }
}