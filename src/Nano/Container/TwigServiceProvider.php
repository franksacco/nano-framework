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
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Register Twig template engine on application container.
 *
 * @package Nano\Container
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class TwigServiceProvider extends AbstractServiceProvider
{
    /**
     * @var array
     */
    protected $provides = [
        Environment::class
    ];

    /**
     * @inheritDoc
     */
    public function register()
    {
        $container = $this->getLeagueContainer();
        $container->share(Environment::class, function () use ($container) {

            $config   = $container->get(ConfigurationInterface::class);
            $rootPath = $config->get('twig.root_path');

            $loader = new FilesystemLoader(
                $config->get('twig.paths', 'templates'),
                $rootPath
            );

            return new Environment($loader, $config->get('twig.options', []));
        });
    }
}