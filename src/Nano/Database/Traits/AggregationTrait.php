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

/**
 * Trait for handling aggregation function in SELECT statement.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait AggregationTrait
{
    use FilterTrait;

    /**
     * The grouping column list.
     *
     * @var array
     */
    private $groupBy = [];

    /**
     * The filter list for grouped result-set.
     *
     * @var array
     */
    private $having = [];

    /**
     * Group the result-set by one or more columns.
     *
     * @param string $column The name of the column. The string can contains
     *   only alphanumeric or underscore characters. In addition, it is
     *   possible to prepend a table name/alias adding a dot between.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidArgumentException if column name is not valid.
     */
    public function groupBy(string $column): self
    {
        if (! preg_match('/^([a-zA-Z0-9_]+\.)?[a-zA-Z0-9_]+$/', $column)) {
            throw InvalidArgumentException::forInvalidColumnName($column);
        }
        $this->groupBy[] = $column;
        return $this;
    }

    /**
     * Filter grouped result-set.
     *
     * {@see groupBy()} method must be launched before this.
     *
     * @param string $column The name of the column. The column must be in
     *   grouping column list.
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
    public function having(string $column, string $operator, $value, int $type = null): self
    {
        if (! in_array($column, $this->groupBy)) {
            throw new InvalidArgumentException('Column must be in grouping column list');
        }
        $param = $this->parseCondition(
            $column,
            strtoupper($operator),
            $value,
            $type
        );
        $this->having[] = $column . $operator . $param;
        return $this;
    }

    /**
     * Generate SQL for GROUP BY clause.
     *
     * @return string
     */
    private function getGroupByClause(): string
    {
        return empty($this->groupBy) ? '' : ' GROUP BY ' . implode(',', $this->groupBy);
    }

    /**
     * Generate SQL for HAVING clause.
     *
     * @return string
     */
    private function getHavingClause(): string
    {
        return empty($this->having) ? '' : ' HAVING ' . implode(' AND ', $this->having);
    }
}