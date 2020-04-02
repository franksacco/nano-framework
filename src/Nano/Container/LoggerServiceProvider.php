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

use League\Container\ServiceProvider\AbstractServiceProvider;
use Nano\Config\ConfigurationInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Register PSR-3 logging engine.
 *
 * @package Nano\Container
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class LoggerServiceProvider extends AbstractServiceProvider
{
    /**
     * @var array
     */
    protected $provides = [
        LoggerInterface::class
    ];

    /**
     * @inheritDoc
     */
    public function register()
    {
        $container = $this->getLeagueContainer();
        $container->share(LoggerInterface::class, function () use ($container) {

            $config = $container->get(ConfigurationInterface::class);
            $logger = $config->get('log.logger', NullLogger::class);
            return $container->get($logger);
        });
    }
}