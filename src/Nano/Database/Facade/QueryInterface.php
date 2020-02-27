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

namespace Nano\Database\Facade;

/**
 * Common interface for SQL query builder.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
interface QueryInterface
{
    /**
     * Retrieve the generated SQL statement.
     *
     * @return string
     */
    public function getStatement(): string;

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
    public function getParameters(): array;
}