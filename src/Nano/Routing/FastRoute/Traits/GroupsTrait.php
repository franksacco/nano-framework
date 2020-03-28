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

use Nano\Routing\FastRoute\RouteGroup;
use Nano\Routing\FastRoute\Router;

/**
 * Methods for group definition.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait GroupsTrait
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var RouteGroup[]
     */
    protected $groups = [];

    /**
     * Get the list of defined groups.
     *
     * @return RouteGroup[] Returns the group list.
     */
    public function getGroups(): array
    {
        return $this->groups;
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
        $group = new RouteGroup($prefix, $callback, $this->router);
        $this->groups[] = $group;
        return $group;
    }
}