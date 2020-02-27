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

namespace Nano\Routing\FastRoute;

use Nano\Routing\FastRoute\Traits\MiddlewareTrait;
use Psr\Container\ContainerInterface;

/**
 * Representation of a route.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Route
{
    use MiddlewareTrait;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $route;

    /**
     * @var mixed
     */
    private $handler;

    /**
     * @var string|null
     */
    private $name;

    /**
     * Initialize the route.
     *
     * @param string $method The HTTP method of the route.
     * @param string $route The pattern of the route.
     * @param mixed $handler The handler of the route.
     * @param string|null $name [optional] The name of the route.
     * @param ContainerInterface|null $container [optional] The DI container.
     */
    public function __construct(string $method,
                                string $route,
                                $handler,
                                ?string $name = null,
                                ?ContainerInterface $container = null)
    {
        $this->method    = $method;
        $this->route     = $route;
        $this->handler   = $handler;
        $this->name      = $name;
        $this->container = $container;
    }

    /**
     * Get the HTTP method of the route.
     *
     * @return string Returns the HTTP method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the route pattern.
     *
     * @return string Returns the route pattern.
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * Get the handler of the route.
     *
     * @return mixed Returns the handler.
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Get the name of the route.
     *
     * @return string|null Returns the route name if set, `null` otherwise.
     */
    public function getName()
    {
        return $this->name;
    }
}