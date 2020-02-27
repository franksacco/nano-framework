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

use Nano\Database\Exception\InvalidArgumentException;
use Nano\Database\Query;

/**
 * Trait for WHERE clause.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait FilterTrait
{
    use ParametersTrait;

    /**
     * The filter list.
     *
     * @var array
     */
    protected $where = [];

    /**
     * Filter result-set with AND condition.
     *
     * Each condition is concatenated to the others with an AND operator.
     *
     * @param string $column The name of the column. The string can contains
     *   only alphanumeric or underscore characters. In addition, it is
     *   possible to prepend a table name/alias adding a dot between.
     * @param string $operator The comparison operator from: =, !=, <>, >, <,
     *   >=, <=, [NOT] LIKE, [NOT] IN, IS [NOT] NULL.
     * @param mixed $value The condition value. For [NOT] IN operator this
     *   must be an array, for [NOT] IS operator this is not considered,
     *   otherwise this must be a scalar.
     * @param int $type [optional] The data type using the Types::* constants;
     *   if NULL, the type is evaluated from the value.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidArgumentException if column name, operator or value is
     *   not valid.
     */
    public function where(string $column, string $operator, $value, int $type = null): self
    {
        $param = $this->parseCondition(
            $column,
            strtoupper($operator),
            $value,
            $type
        );

        $this->where[] = sprintf("%s %s %s", $column, $operator, $param);
        return $this;
    }

    /**
     * Filter result-set with OR conditions.
     *
     * Each condition is concatenated to the others with an OR operator.
     *
     * Each condition must be in the form [`column`, `operator`, `value`] or
     * [`column`, `operator`, `value`, `type`], where:
     *  - `column` is the name of the column. The string can contains
     *   only alphanumeric or underscore characters. In addition, it is
     *   possible to prepend a table name/alias adding a dot between.
     *  - `operator` is the comparison operator from: =, !=, <>, >, <,
     *   >=, <=, [NOT] LIKE, [NOT] IN, IS [NOT] NULL.
     *  - `value` is the condition value. For [NOT] IN operator this
     *   must be an array, for [NOT] IS operator this is not considered,
     *   otherwise this must be a scalar.
     *  - `type` is the data type using the Types::* constants;
     *   if NULL, the type is evaluated from the value.
     *
     * @param array $conditions The condition list.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidArgumentException if the condition list is not valid.
     */
    public function orWhere(array $conditions): self
    {
        $filters = [];
        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                throw InvalidArgumentException::forInvalidCondition($condition);
            }
            $count = count($condition);
            if ($count === 3) {
                list($column, $operator, $value) = $condition;
                $param = $this->parseCondition(
                    $column,
                    strtoupper($operator),
                    $value
                );

            } elseif ($count === 4) {
                list($column, $operator, $value, $type) = $condition;
                $param = $this->parseCondition(
                    $column,
                    strtoupper($operator),
                    $value,
                    $type
                );

            } else {
                throw InvalidArgumentException::forInvalidConditionCount($condition);
            }
            $filters[] = sprintf("%s %s %s", $column, $operator, $param);
        }

        if (! empty($filters)) {
            $this->where[] = '(' . implode(' OR ', $filters) . ')';
        }

        return $this;
    }

    /**
     * Parse the condition.
     *
     * @param string $column The column name.
     * @param string $operator The comparison operator.
     * @param mixed $value The condition value.
     * @param int|null $type [optional] The data type.
     * @return string
     *
     * @throws InvalidArgumentException if column name, operator or value is
     *   not valid.
     */
    private function parseCondition(string $column, string $operator, $value, ?int $type = null): string
    {
        if (! preg_match('/^([a-zA-Z0-9_]+\.)?[a-zA-Z0-9_]+$/', $column)) {
            throw InvalidArgumentException::forInvalidColumnName($column);
        }

        if (in_array($operator, Query::COMPARISON_OPERATORS)) {
            if (! is_scalar($value)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid value: expected scalar, %s given',
                    gettype($value)
                ));
            }
            $param = $this->setParameter($value, $type);

        } elseif (in_array($operator, Query::COMPARISON_SET_OPERATORS)) {
            if (! is_array($value) || ! array_map('is_scalar', $value)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid value: expected array of scalar, %s given',
                    gettype($value)
                ));
            }
            $param = '(' .
                implode(',', array_map(function ($value) use ($type) {
                    return $this->setParameter($value, $type);
                }, $value)) . ')';

        } elseif (in_array($operator, Query::COMPARISON_NULL_OPERATORS)) {
            $param = 'NULL';

        } else {
            throw InvalidArgumentException::forInvalidOperator($operator);
        }

        return $param;
    }

    /**
     * Generate SQL for WHERE clause.
     *
     * @return string
     */
    protected function getWhereClause(): string
    {
        return empty($this->where) ? '' : ' WHERE ' . implode(' AND ', $this->where);
    }
}