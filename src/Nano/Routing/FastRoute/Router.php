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
use FastRoute\RouteParser;
use Nano\Routing\FastRoute\Traits\GroupsTrait;
use Nano\Routing\FastRoute\Traits\RoutesTrait;
use Psr\Container\ContainerInterface;

/**
 * Routes collector and manager.
 *
 * @see https://github.com/franksacco/nano-framework/blob/master/docs/routing.md
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Router
{
    use GroupsTrait, RoutesTrait;

    /**
     * @var RouteParser
     */
    protected $routeParser;

    /**
     * @var DataGenerator
     */
    protected $dataGenerator;

    /**
     * @var array|null
     */
    protected $data;

    /**
     * @var array|null
     */
    protected $reverseData;

    /**
     * Initialize the router.
     *
     * @param RouteParser $routeParser The route parser.
     * @param DataGenerator $dataGenerator The data generator.
     * @param ContainerInterface|null $container [optional] The DI container.
     */
    public function __construct(RouteParser $routeParser,
                                DataGenerator $dataGenerator,
                                ?ContainerInterface $container = null)
    {
        $this->routeParser   = $routeParser;
        $this->dataGenerator = $dataGenerator;
        $this->container     = $container;
        $this->router        = $this;
    }

    /**
     * Get the DI container, if defined.
     *
     * @return ContainerInterface|null Returns the DI container
     *   if defined, `null` otherwise.
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * Calculate the parsed data for routing.
     */
    protected function calculateData()
    {
        $routes = $this->routes;
        foreach ($routes as $route) {

            $routeDatas = $this->routeParser->parse($route->getRoute());
            foreach ($routeDatas as $routeData) {
                $handler = [
                    'middlewares' => $route->getMiddlewares(),
                    'handler'     => $route->getHandler()
                ];
                $this->dataGenerator->addRoute($route->getMethod(), $routeData, $handler);
            }
        }

        return $this->dataGenerator->getData();
    }

    /**
     * Returns the collected route data, as provided by the data generator.
     *
     * @return array
     */
    public function getData(): array
    {
        if ($this->data === null) {
            $this->data = $this->calculateData();
        }

        return $this->data;
    }

    /**
     * Calculate the data for reverse routing.
     *
     * @return array
     */
    protected function calculateReverseData(): array
    {
        $reverseData = [];
        $routes = $this->routes;

        foreach ($routes as $route) {
            $routeDatas = $this->routeParser->parse($route->getRoute());

            $name = $route->getName();
            if ($name) {
                $reverseData[$name] = $routeDatas;
            }
        }

        return $reverseData;
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
        if ($this->reverseData === null) {
            $this->reverseData = $this->calculateReverseData();
        }

        return $this->reverseData;
    }
}