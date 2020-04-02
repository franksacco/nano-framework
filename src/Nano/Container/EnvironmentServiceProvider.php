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
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

/**
 * Load environment variables using PHP dotenv.
 *
 * @see https://github.com/vlucas/phpdotenv/
 *
 * @package Nano\Config
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class EnvironmentServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * @var array
     */
    protected $provides = [
        Dotenv::class
    ];

    /**
     * @var Dotenv
     */
    protected $dotenv;

    /**
     * @var string
     */
    private $rootPath;

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
    public function boot()
    {
        $this->dotenv = Dotenv::createImmutable($this->rootPath);
        $this->dotenv->load();
    }

    /**
     * @inheritDoc
     */
    public function register()
    {
        $this->getLeagueContainer()
            ->share(Dotenv::class, $this->dotenv);
    }
}