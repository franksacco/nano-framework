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

use League\Container\Container as LeagueContainer;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Nano\Config\ConfigurationInterface;
use Nano\Config\UnexpectedValueException;
use Nano\Database\Connection\Connection;
use Nano\Database\Connection\ConnectionInterface;
use Nano\Database\Connection\LoggingConnection;
use Nano\Error\ErrorResponseFactory;
use Nano\Model\QueryBuilder\ConnectionCollector;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Define default services for the application.
 *
 * @package Nano\Application
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class DefaultServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * DefaultServiceProvider constructor.
     *
     * @param ConfigurationInterface $config The application settings.
     */
    public function __construct(ConfigurationInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @var array
     */
    protected $provides = [
        ContainerInterface::class,
        ResponseFactoryInterface::class,
        LoggerInterface::class,
        Environment::class,
        ConnectionInterface::class
    ];

    /**
     * @inheritDoc
     */
    public function boot()
    {
        // Setup lazy database connection for Model package.
        ConnectionCollector::setContainer($this->getContainer());
    }

    /**
     * @inheritDoc
     */
    public function register()
    {
        $container = $this->getLeagueContainer();

        // Add container itself.
        $container->share(ContainerInterface::class, $container);

        // Add logging engine.
        $this->registerLogger($container);

        // Add error response factory.
        $this->registerErrorResponseFactory($container);

        // Add Twig environment.
        $this->registerTemplateRenderEngine($container);

        // Add database connection.
        $this->registerDatabaseConnection($container);
    }

    /**
     * Register logging engine.
     *
     * @param LeagueContainer $container The DI container.
     */
    protected function registerLogger(LeagueContainer $container)
    {
        $container->share(LoggerInterface::class, function () use ($container) {
            $logger = $this->config->get('log.logger', new NullLogger());
            if ($container->has($logger)) {
                $logger = $container->get($logger);
            }

            if (! $logger instanceof LoggerInterface) {
                throw new UnexpectedValueException(sprintf(
                    'Logger class must implements %s',
                    LoggerInterface::class
                ));
            }

            return $logger;
        });
    }

    /**
     * Register error response factory.
     *
     * @param LeagueContainer $container The DI container.
     */
    protected function registerErrorResponseFactory(LeagueContainer $container)
    {
        $container->share(ResponseFactoryInterface::class, function () use ($container) {
            $factory = $this->config->get('error.factory', ErrorResponseFactory::class);
            $factory = $container->get($factory);
            if (! $factory instanceof ResponseFactoryInterface) {
                throw new UnexpectedValueException(sprintf(
                    'Error response factory must implements %s',
                    ResponseFactoryInterface::class
                ));
            }

            return $factory;
        });
    }

    /**
     * Register template render engine.
     *
     * @param LeagueContainer $container The DI container.
     */
    protected function registerTemplateRenderEngine(LeagueContainer $container)
    {
        $container->share(Environment::class, function () use ($container) {
            $rootPath = $this->config->get('twig.root_path',
                $this->config->get('root_path', __DIR__ . '/../../..')
            );

            $loader = new FilesystemLoader(
                $this->config->get('twig.paths', 'templates'),
                $rootPath
            );

            return new Environment($loader, $this->config->get('twig.options', []));
        });
    }

    /**
     * Register database connection manager.
     *
     * @param LeagueContainer $container The DI container.
     */
    protected function registerDatabaseConnection(LeagueContainer $container)
    {
        $container->share(ConnectionInterface::class, function () use ($container) {
            return new LoggingConnection(
                new Connection($this->config),
                $container->get(LoggerInterface::class)
            );
        });
    }
}
