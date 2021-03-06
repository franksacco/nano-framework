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

/**
 * Class representing a Method Not Allowed routing result.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class MethodNotAllowedResult implements RoutingResultInterface
{
    /**
     * @var string[]
     */
    private $allowedMethod;

    /**
     * @param string[] $allowedMethod The allowed HTTP method list.
     */
    public function __construct(array $allowedMethod)
    {
        $this->allowedMethod = $allowedMethod;
    }

    /**
     * Get the allowed HTTP method list.
     *
     * @return string[]
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethod;
    }
}