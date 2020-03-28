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

use Nano\Routing\FastRoute\Traits\GroupsTrait;
use Nano\Routing\FastRoute\Traits\MiddlewareTrait;
use Nano\Routing\FastRoute\Traits\RoutesTrait;

/**
 * Representation of a route group with a common prefix.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class RouteGroup
{
    use GroupsTrait, MiddlewareTrait, RoutesTrait {
        group as protected createGroup;
    }

    /**
     * @var string
     */
    private $prefix;

    /**
     * Initialize the route group.
     *
     * @param string $prefix The prefix of the group.
     * @param callable $callback The callback used to define routes.
     * @param Router $router The router
     */
    public function __construct(string $prefix, callable $callback, Router $router)
    {
        $this->prefix    = $prefix;
        $this->router    = $router;
        $this->container = $router->getContainer();

        $this->loadRoutes($callback);
    }

    /**
     * Execute the callback in order to define routes.
     *
     * @param callable $callback The callback used to define routes.
     */
    private function loadRoutes(callable $callback)
    {
        $callback($this);
    }

    /**
     * Get the prefix of the route group.
     *
     * @return string Returns the prefix of the group.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @inheritDoc
     */
    public function group(string $prefix, callable $callback): RouteGroup
    {
        return $this->createGroup($this->prefix . $prefix, $callback);
    }

    /**
     * @inheritDoc
     */
    public function middleware($middleware): self
    {
        foreach ($this->routes as $route) {
            $route->middleware($middleware);
        }

        foreach ($this->groups as $group) {
            $group->middleware($middleware);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function route(string $method, string $route, $handler, ?string $name = null): Route
    {
        $route = $this->router->route($method, $this->prefix . $route, $handler, $name);
        $this->routes[] = $route;
        return $route;
    }
}