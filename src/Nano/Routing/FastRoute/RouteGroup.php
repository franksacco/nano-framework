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
use Nano\Routing\FastRoute\Traits\RoutesTrait;
use Psr\Container\ContainerInterface;

/**
 * Representation of a route group with a common prefix.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class RouteGroup
{
    use MiddlewareTrait, RoutesTrait {
        route as private createRoute;
    }

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var callable
     */
    private $callback;

    /**
     * Initialize the route group.
     *
     * @param string $prefix The prefix of the group.
     * @param callable $callback The callable that defines group routes.
     * @param ContainerInterface|null $container [optional] The DI container.
     */
    public function __construct(string $prefix, callable $callback, ?ContainerInterface $container = null)
    {
        $this->prefix    = '/' . trim($prefix, '/');
        $this->callback  = $callback;
        $this->container = $container;
    }

    /**
     * Get the prefix of the route group.
     *
     * @return string Returns the prefix if the group.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get the list of routes of this group.
     *
     * @return Route[] Returns the route list.
     */
    public function getRoutes(): array
    {
        ($this->callback)($this);

        return $this->routes;
    }

    /**
     * Adds a route to the collection.
     *
     * The syntax used in the `$route` string depends on the used route parser.
     *
     * @param string $method The HTTP method of the route.
     * @param string $route The pattern of the route.
     * @param mixed $handler The handler of the route.
     * @param string|null $name [optional] The name of the route.
     * @return Route Returns the created route.
     */
    public function route(string $method, string $route, $handler, ?string $name = null): Route
    {
        $route = $this->createRoute($method, $this->prefix . $route, $handler, $name);
        foreach ($this->middlewares as $middleware) {
            $route->middleware($middleware);
        }

        return $route;
    }
}