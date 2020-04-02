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
use Nano\Config\Configuration;
use Nano\Config\ConfigurationInterface;

/**
 * Load configuration items
 *
 * @package Nano\Config
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class ConfigurationsServiceProvider extends AbstractServiceProvider
{
    /**
     * @var array
     */
    protected $provides = [
        ConfigurationInterface::class
    ];

    /**
     * @var string
     */
    protected $rootPath;

    /**
     * Initialize the environment variables loader.
     *
     * @param string $rootPath The root path of the application.
     */
    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * @inheritDoc
     */
    public function register()
    {
        $config = new Configuration($this->loadConfigurations());

        $this->getLeagueContainer()
            ->share(ConfigurationInterface::class, $config);
    }

    /**
     * Load configurations files and return their contents.
     *
     * @return array
     */
    protected function loadConfigurations(): array
    {
        $config = [];

        foreach ($this->getConfigurationFiles() as $key => $file) {
            $config[$key] = require $file;
        }

        return $config;
    }

    /**
     * Get the list of configuration files.
     *
     * @return array
     */
    protected function getConfigurationFiles(): array
    {
        $configPath = $this->rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

        $files = [];
        foreach (scandir($configPath) as $file) {
            if (fnmatch('*.php', $file)) {
                $files[basename($file, '.php')] = $configPath . $file;
            }
        }

        return $files;
    }
}