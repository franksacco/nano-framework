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
use Nano\Error\ErrorResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * Register default error response factory.
 *
 * @package Nano\Container
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class ErrorResponseFactoryServiceProvider extends AbstractServiceProvider
{
    /**
     * @var array
     */
    protected $provides = [
        ResponseFactoryInterface::class
    ];

    /**
     * @inheritDoc
     */
    public function register()
    {
        $container = $this->getLeagueContainer();
        $container->share(ResponseFactoryInterface::class, function () use ($container) {

            $config  = $container->get(ConfigurationInterface::class);
            $factory = $config->get('error.factory', ErrorResponseFactory::class);
            return $container->get($factory);
        });
    }
}