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

namespace Nano\Config;

use PHPUnit\Framework\TestCase;

class ConfigurationAwareTraitTest extends TestCase
{
    public function testSetConfiguration()
    {
        $config = $this->createMock(ConfigurationInterface::class);

        $obj = new DummyObject();
        $obj->setConfiguration($config);

        $this->assertSame($config, $obj->getConfig());
    }

    public function testHasConfig()
    {
        $key = 'some_config';
        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->once())
            ->method('has')
            ->with($key)
            ->willReturn(true);

        $obj = new DummyObject();
        $obj->setConfiguration($config);

        $this->assertTrue($obj->hasConfig($key));
    }

    public function testGetConfig()
    {
        $key = 'some_config';
        $value = 'some_value';
        $config = $this->createMock(ConfigurationInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with($key, 'default')
            ->willReturn($value);

        $obj = new DummyObject();
        $obj->setConfiguration($config);

        $this->assertSame($value, $obj->getConfig($key, 'default'));
    }
}

class DummyObject
{
    use ConfigurationAwareTrait;
}