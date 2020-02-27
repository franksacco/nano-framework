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
use Nano\Container\Container;
use Nano\Container\InvalidContainerException;
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
abstract class AbstractApplication implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Initialize the application.
     *
     * @param array|ContainerInterface $container [optional] The application
     *   settings or a {@see ContainerInterface} instance.
     */
    public function __construct($container = [])
    {
        if (is_array($container)) {
            $container = new Container($container);
        }

        if (! $container instanceof ContainerInterface) {
            throw new InvalidContainerException(printf(
                'The application expects a %s instance, got %s',
                ContainerInterface::class,
                is_object($container) ? get_class($container) : gettype($container)
            ));
        }
        $this->container = $container;

        $this->onBoot();
    }

    /**
     * Retrieve the DI container of the application.
     *
     * @return ContainerInterface Returns the container instance.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Listener method for application boot event.
     *
     * This method can be overwritten in order to execute some code each time
     * the application is booted.
     */
    protected function onBoot() {}

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
     */
    public function run(ServerRequestInterface $request = null)
    {
        $request = $request ?: ServerRequestFactory::fromGlobals();

        $queue = new MiddlewareQueue($this->container);
        $this->middleware($queue);
        $queue->add($this);

        $runner = new Runner($queue);
        $runner->handle($request);
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
