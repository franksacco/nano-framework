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

/**
 * This class provides a path generator from named routes.
 *
 * @see https://github.com/nikic/FastRoute/issues/66
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class UrlGenerator
{
    /**
     * @var array
     */
    private $data;

    /**
     * Initialize the url generator.
     *
     * The data array must be in the form: `route_name => route_parsed_data`.
     * Usually, data is taken by `getReverseData()` method from {@see Router}.
     *
     * @param array $data The named routes with parsed data.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Generate a path from a route name.
     *
     * This method is inspired by Nikic's code in the comment at
     * https://github.com/nikic/FastRoute/issues/66#issuecomment-130395124.
     *
     * @param string $name The name of a route.
     * @param mixed ...$params The parameters of the route.
     * @return string Returns the path associated to the route and parameters.
     *
     * @throws RoutingException if an error occur during the path generation.
     */
    public function getPath(string $name, ...$params): string
    {
        if (! isset($this->data[$name])) {
            throw RoutingException::forNotFoundRoute($name);
        }

        foreach ($this->data[$name] as $route) {
            $url = '';
            $paramIdx = 0;

            foreach ($route as $part) {
                if (is_string($part)) {
                    $url .= $part;

                } else {
                    if ($paramIdx === count($params)) {
                        throw RoutingException::forNotEnoughParams($name);
                    }
                    $url .= $params[$paramIdx++];
                }
            }

            if ($paramIdx === count($params)) {
                return $url;
            }
        }

        throw RoutingException::forTooManyParams($name);
    }
}