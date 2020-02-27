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

namespace Nano\Routing\FastRoute\Result;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Class representing a positive routing result.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class FoundResult implements RoutingResultInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;

    /**
     * @var mixed
     */
    private $handler;

    /**
     * @var array
     */
    private $params;

    /**
     * @param array $middlewares the list of middlewares of this route.
     * @param mixed $handler The handler associated to the route.
     * @param array $params The parameters associated to the route.
     */
    public function __construct(array $middlewares, $handler, array $params)
    {
        $this->middlewares = $middlewares;
        $this->handler     = $handler;
        $this->params      = $params;
    }

    /**
     * Get the list of middlewares of this route.
     *
     * @return MiddlewareInterface[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Get the handler associated to the route.
     *
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Get parameters associated with the route.
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
}