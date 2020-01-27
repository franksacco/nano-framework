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

namespace Nano\Database\Exception;

use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown if an argument does not match with the expected value.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class InvalidArgumentException extends \InvalidArgumentException implements NanoExceptionInterface
{
    /**
     * @param string $name The table name provided.
     * @return InvalidArgumentException
     */
    public static function forInvalidTableName(string $name): self
    {
        return new self(sprintf('Invalid table name "%s"', $name));
    }

    /**
     * @param string $alias The alias provided.
     * @return InvalidArgumentException
     */
    public static function forInvalidAlias(string $alias): self
    {
        return new self(sprintf('Invalid alias "%s"', $alias));
    }

    /**
     * @param string $name The column name provided.
     * @return InvalidArgumentException
     */
    public static function forInvalidColumnName(string $name): self
    {
        return new self(sprintf('Invalid column name "%s"', $name));
    }

    /**
     * @param string $function The aggregate function provided.
     * @return InvalidArgumentException
     */
    public static function forInvalidAggregateFunction(string $function): self
    {
        return new self(sprintf('Invalid aggregate function "%s"', $function));
    }

    /**
     * @param string $joinType The join type provided.
     * @return InvalidArgumentException
     */
    public static function forInvalidJoinType(string $joinType): self
    {
        return new self(sprintf('Invalid JOIN type "%s"', $joinType));
    }

    /**
     * @param string $operator The comparison operator provided.
     * @return InvalidArgumentException
     */
    public static function forInvalidOperator(string $operator): self
    {
        return new self(sprintf('Invalid comparison operator "%s"', $operator));
    }

    /**
     * @param mixed $condition The condition provided.
     * @return InvalidArgumentException
     */
    public static function forInvalidCondition($condition): self
    {
        return new self(sprintf(
            'Invalid condition: array expected, %s given',
            gettype($condition)
        ));
    }

    /**
     * @param array $condition The condition provided.
     * @return InvalidArgumentException
     */
    public static function forInvalidConditionCount(array $condition): self
    {
        return new self(sprintf(
            'Invalid condition: 3 or 4 items expected, %i given',
            count($condition)
        ));
    }

    /**
     * @param string $order The sort order provided.
     * @return InvalidArgumentException
     */
    public static function forInvalidSortOrder(string $order): self
    {
        return new self(sprintf('Invalid sort order "%s"', $order));
    }
}
