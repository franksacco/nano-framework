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

namespace Nano\Middleware;

use Laminas\Diactoros\ServerRequestFactory;
use League\Container\Container;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\ReflectionContainer;
use Nano\Container\ConfigurationsServiceProvider;
use Nano\Container\DatabaseServiceProvider;
use Nano\Container\EnvironmentServiceProvider;
use Nano\Container\ErrorResponseFactoryServiceProvider;
use Nano\Container\LoggerServiceProvider;
use Nano\Container\TwigServiceProvider;
use Nano\Routing\Dispatcher;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Abstract application class.
 *
 * @package Nano\Middleware
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
abstract class AbstractApplication implements ContainerAwareInterface, MiddlewareInterface
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected $rootPath;

    /**
     * Initialize the application.
     *
     * @param string $rootPath The root path of the application.
     * @param ContainerInterface $container [optional] The DI container that
     *   overrides the default League's {@see Container}.
     */
    public function __construct(string $rootPath, ContainerInterface $container = null)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
        $container = $container ?: new Container();

        if ($container instanceof Container) {
            // Enable auto wiring.
            $container->delegate(
                (new ReflectionContainer())->cacheResolutions(true)
            );
            // Share container itself.
            $container->share(ContainerInterface::class, $container);
            $this->setLeagueContainer($container);

        } else {
            $this->setContainer($container);
        }

        $this->onBoot();
    }

    /**
     * Listener method for application boot event.
     *
     * This method can be overwritten in order to personalize actions executed
     * on bootstrap, such as service provider definitions.
     */
    protected function onBoot()
    {
        $this->getLeagueContainer()
            ->addServiceProvider(new EnvironmentServiceProvider($this->getRootPath()))
            ->addServiceProvider(new ConfigurationsServiceProvider($this->getRootPath()))
            ->addServiceProvider(LoggerServiceProvider::class)
            ->addServiceProvider(ErrorResponseFactoryServiceProvider::class)
            ->addServiceProvider(DatabaseServiceProvider::class)
            ->addServiceProvider(TwigServiceProvider::class);
    }

    /**
     * Get the root path of the application.
     *
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * Setup the middlewares queue of the application.
     *
     * This method must be overwritten in order to setup the middlewares queue
     * used to process the server request and create the response.
     * Note that the order in which the middlewares are added defines the
     * order in which they are executed.
     *
     * @param MiddlewareQueue $middleware The middlewares queue.
     */
    abstract protected function middleware(MiddlewareQueue $middleware);

    /**
     * Start processing server request to emit a response.
     *
     * @param ServerRequestInterface $request [optional] The server request.
     *   If set, this request overwrites the creation of a request from
     *   superglobal values.
     * @return ResponseInterface Returns the server response.
     */
    public function run(ServerRequestInterface $request = null): ResponseInterface
    {
        $request = $request ?: ServerRequestFactory::fromGlobals();

        $queue = new MiddlewareQueue($this->container);
        $this->middleware($queue);
        $queue->add($this);

        return (new Runner($queue))->handle($request);
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->container instanceof Container) {
            $this->container->add(ServerRequestInterface::class, $request);
        }

        return (new Dispatcher($this->container))->dispatch($request);
    }
}