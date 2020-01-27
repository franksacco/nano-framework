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

namespace Nano\Application;

use Laminas\Diactoros\ServerRequestFactory;
use Nano\Container\Container;
use Nano\Container\InvalidContainerException;
use Nano\Middleware\MiddlewareQueue;
use Nano\Middleware\Runner;
use Nano\Routing\Dispatcher;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Abstract application class.
 *
 * @package Nano\Application
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
     * Setup middleware queue to process a request and create a response.
     *
     * @param MiddlewareQueue $middleware The middleware queue.
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
