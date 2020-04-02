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
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerServiceProviderTest extends TestCase
{
    public function testRegister()
    {
        $container = new Container();
        $container->add(ConfigurationInterface::class, new Configuration([]));
        $container->add(NullLogger::class, new NullLogger());
        $container->addServiceProvider(new LoggerServiceProvider());

        $this->assertTrue(
            $container->get(LoggerInterface::class) instanceof LoggerInterface
        );
    }
}