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

use League\Container\Container;
use Nano\Config\Configuration;
use Nano\Config\ConfigurationInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class LoadConfigurationServiceProviderTest extends TestCase
{
    private $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root', null, [
            'config' => [
                'test.php' => "<?php return ['item0' => true, 'item1' => false];"
            ]
        ]);
    }

    public function testRegister()
    {
        $expected = new Configuration([
            'test' => [
                'item0' => true,
                'item1' => false
            ]
        ]);

        $container = new Container();
        $container->addServiceProvider(
            new ConfigurationsServiceProvider($this->root->url())
        );

        $this->assertEquals($expected, $container->get(ConfigurationInterface::class));
    }
}