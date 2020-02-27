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

namespace Nano\Database\Traits;

use Nano\Database\Facade\Types;

/**
 * Trait for statement parameter handling.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait ParametersTrait
{
    /**
     * The parameter values list.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * The counter for parameters.
     *
     * @var int
     */
    private $paramCounter = 0;

    /**
     * Add a parameter and retrieve his name.
     *
     * @param mixed $value The parameter value.
     * @param int|null $type [optional] The data type for the parameter using
     *   the Types::* constants; if NULL, the type is evaluated from the value.
     * @return string Returns the parameter name.
     */
    protected function setParameter($value, ?int $type = null): string
    {
        $name = ':p' . $this->paramCounter++;
        if ($type === null) {
            $type = $this->evaluateType($value);
        }
        $this->parameters[$name] = [$value, $type];
        return $name;
    }

    /**
     * Evaluate the type of a variable.
     *
     * @param mixed $value The value to be evaluated.
     * @return int Returns a Types::* constant.
     */
    private function evaluateType($value): int
    {
        if (is_null($value)) {
            return Types::NULL;
        } elseif (is_int($value)) {
            return Types::INT;
        } elseif (is_bool($value)) {
            return Types::BOOL;
        }
        return Types::STRING;
    }

    /**
     * Retrieve the parameter list.
     *
     * Each item of the list is in the form `name` => [`value`, `type`], where:
     *  - `name` is the parameter identifier: for a prepared statement using
     *   named placeholders, this is the parameter name of the form ":name";
     *  - `value` is the value to bind to the parameter;
     *  - `type` is the data type for the parameter using the `Types::*`
     *   constants.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}