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

use FastRoute\DataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use Nano\Routing\FastRoute\Traits\RoutesTrait;
use Psr\Container\ContainerInterface;

/**
 * Proxy class for FastRoute RouteCollector.
 *
 * This class provides the following functionalities:
 *  - named routes: for each route a name can be provided in order to
 *   uniquely identify a route;
 *  - reverse routing: when a named route is created, parsed data from the
 *   definition can be used by {@see UrlGenerator} to perform reverse routing;
 *  - middlewares: it is possible to specify middlewares that are executed
 *   only for some routes.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Router extends RouteCollector
{
    use RoutesTrait;

    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @var RouteGroup[]
     */
    private $groups = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    private $reverseData = [];

    /**
     * @var bool
     */
    private $dataCalculated = false;

    /**
     * @inheritDoc
     * @param ContainerInterface|null $container [optional] The DI container.
     */
    public function __construct(RouteParser $routeParser,
                                DataGenerator $dataGenerator,
                                ?ContainerInterface $container = null)
    {
        parent::__construct($routeParser, $dataGenerator);
        $this->container = $container;
    }

    /**
     * Create a route group with a common prefix.
     *
     * All routes created in the passed callback will have the given group
     * prefix prepended.
     *
     * @param string $prefix The prefix of the group.
     * @param callable $callback The callable used to define routes that
     *   expects as single argument an instance of {@see RouteGroup}.
     * @return RouteGroup Returns the created route group.
     */
    public function group(string $prefix, callable $callback): RouteGroup
    {
        $group = new RouteGroup($prefix, $callback, $this->container);
        $this->groups[] = $group;
        return $group;
    }

    /**
     * Calculate the parsed data using the list of routes.
     */
    private function calculateData()
    {
        foreach ($this->groups as $group) {
            $this->routes = array_merge($this->routes, $group->getRoutes());
        }

        foreach ($this->routes as $route) {
            $routeDatas = $this->routeParser->parse($route->getRoute());

            $name = $route->getName();
            if ($name) {
                $this->reverseData[$name] = $routeDatas;
            }

            foreach ($routeDatas as $routeData) {
                $handler = [
                    'middlewares' => $route->getMiddlewares(),
                    'handler'     => $route->getHandler()
                ];
                $this->dataGenerator->addRoute($route->getMethod(), $routeData, $handler);
            }
        }

        $this->data = $this->dataGenerator->getData();
        $this->dataCalculated = true;
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        if (! $this->dataCalculated) {
            $this->calculateData();
        }

        return $this->data;
    }

    /**
     * Retrieve data used for reverse routing.
     *
     * The returned array is in the form: `route_name => route_parsed_data`.
     * This data can be used by {@see UrlGenerator} to perform reverse routing.
     *
     * @return array Returns the named routes with parsed data.
     */
    public function getReverseData(): array
    {
        if (! $this->dataCalculated) {
            $this->calculateData();
        }

        return $this->reverseData;
    }
}