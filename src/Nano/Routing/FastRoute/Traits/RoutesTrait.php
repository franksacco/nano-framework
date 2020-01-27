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


namespace Nano\Routing\FastRoute\Traits;

use Nano\Routing\FastRoute\Route;

/**
 * Methods for route definition.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait RoutesTrait
{
    /**
     * @var Route[]
     */
    private $routes = [];

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
        $route = new Route($method, $route, $handler, $name, $this->container);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * Adds a GET route to the collection.
     *
     * This is an alias of `$this->route('GET', $route, $handler, $name)`.
     *
     * @param string $route The pattern of the route.
     * @param mixed $handler The handler of the route.
     * @param string $name [optional] The name of the route.
     * @return Route Returns self reference for methods chaining.
     */
    public function get($route, $handler, string $name = null): Route
    {
        return $this->route('GET', $route, $handler, $name);
    }

    /**
     * Adds a POST route to the collection.
     *
     * This is an alias of `$this->route('POST', $route, $handler, $name)`.
     *
     * @param string $route The pattern of the route.
     * @param mixed $handler The handler of the route.
     * @param string $name [optional] The name of the route.
     * @return Route Returns the created route.
     */
    public function post($route, $handler, string $name = null): Route
    {
        return $this->route('POST', $route, $handler, $name);
    }

    /**
     * Adds a PUT route to the collection.
     *
     * This is an alias of `$this->route('PUT', $route, $handler, $name)`.
     *
     * @param string $route The pattern of the route.
     * @param mixed $handler The handler of the route.
     * @param string $name [optional] The name of the route.
     * @return Route Returns the created route.
     */
    public function put($route, $handler, string $name = null): Route
    {
        return $this->route('PUT', $route, $handler, $name);
    }

    /**
     * Adds a DELETE route to the collection.
     *
     * This is an alias of `$this->route('DELETE', $route, $handler, $name)`.
     *
     * @param string $route The pattern of the route.
     * @param mixed $handler The handler of the route.
     * @param string $name [optional] The name of the route.
     * @return Route Returns the created route.
     */
    public function delete($route, $handler, string $name = null): Route
    {
        return $this->route('DELETE', $route, $handler, $name);
    }

    /**
     * Adds a PATCH route to the collection.
     *
     * This is an alias of `$this->route('PATCH', $route, $handler, $name)`.
     *
     * @param string $route The pattern of the route.
     * @param mixed $handler The handler of the route.
     * @param string $name [optional] The name of the route.
     * @return Route Returns the created route.
     */
    public function patch($route, $handler, string $name = null): Route
    {
        return $this->route('PATCH', $route, $handler, $name);
    }

    /**
     * Adds a HEAD route to the collection.
     *
     * This is an alias of `$this->route('HEAD', $route, $handler, $name)`.
     *
     * @param string $route The pattern of the route.
     * @param mixed $handler The handler of the route.
     * @param string $name [optional] The name of the route.
     * @return Route Returns the created route.
     */
    public function head($route, $handler, string $name = null): Route
    {
        return $this->route('HEAD', $route, $handler, $name);
    }
}
