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

namespace Nano\Database;

use Nano\Database\Exception\InvalidArgumentException;
use Nano\Database\Facade\QueryInterface;
use Nano\Database\Traits\AggregationTrait;
use Nano\Database\Traits\LimitTrait;
use Nano\Database\Traits\SortingTrait;

/**
 * Class for SQL select query generation.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Select extends Query implements QueryInterface
{
    use AggregationTrait, SortingTrait, LimitTrait;

    /**
     * Columns to show in result-set.
     *
     * If empty, all columns are selected.
     *
     * @var array
     */
    private $columns = [];

    /**
     * The join list.
     *
     * Associative array that contains for each join table name, alias,
     * foreign key column name, referenced column name and join type.
     *
     * @var array
     */
    private $join = [];

    /**
     * Add one or more columns to show in the result-set.
     *
     * @example $query->select('table.column', "alias");
     * @example $query->select(['alias' => 'table.column', ...]);
     *
     * @param string|array $column The column name or column list. The name
     *   can contains only alphanumeric or underscore characters. In addition,
     *   it is possible to prepend a table name/alias adding a dot between.
     * @param string $alias [optional] The alias name for the column.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidArgumentException if column name or alias is not valid.
     */
    public function select($column, string $alias = null): self
    {
        if (is_array($column)) {
            foreach ($column as $i => $c) {
                if (is_string($i)) {
                    $this->select($c, $i);
                } else {
                    $this->select($c);
                }
            }

        } else {
            $column = (string) $column;
            if (! preg_match('/^([a-zA-Z0-9_]+\.)?[a-zA-Z0-9_]+$/', $column)) {
                throw InvalidArgumentException::forInvalidColumnName($column);
            }
            if ($alias) {
                if (! preg_match('/^[a-zA-Z0-9_]+$/', $alias)) {
                    throw InvalidArgumentException::forInvalidAlias($alias);
                }
                $column .= ' as ' . $alias;
            }
            $this->columns[] = $column;
        }

        return $this;
    }

    /**
     * Show distinct values from a column.
     *
     * @param string $column The name of the column. The string can contains
     *   only alphanumeric or underscore characters. In addition, it is
     *   possible to prepend a table name/alias adding a dot between.
     * @param string $alias [optional] The alias name for the column.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidArgumentException if column name or alias is not valid.
     */
    public function selectDistinct(string $column, string $alias = null): self
    {
        if ($alias) {
            $this->select($column, $alias);

        } else {
            $this->select($column);
        }

        $index = count($this->columns) - 1;
        $this->columns[$index] = 'DISTINCT ' . $this->columns[$index];
        return $this;
    }

    /**
     * Add an aggregate function to selection.
     *
     * @param string $function The name of the aggregate function from: AVG,
     *   COUNT, MAX, MIN or SUM.
     * @param string $column The name of the column or an asterisk to select
     *   all columns. The string can contains only alphanumeric or underscore
     *   characters. In addition, it is possible to prepend a table name/alias
     *   adding a dot between.
     * @param string $alias The alias name for the column. The string can
     *   contains only alphanumeric or underscore characters.
     * @param bool $distinct [optional] Whether to not consider duplicate
     *   records, default: FALSE.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidArgumentException if aggregate function, column name or
     *   alias is not valid.
     */
    public function aggregate(string $function, string $column, string $alias, bool $distinct = false): self
    {
        $function = strtoupper($function);
        if (! in_array($function, Query::AGGREGATE_FUNCTIONS)) {
            throw InvalidArgumentException::forInvalidAggregateFunction($function);
        }
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $alias)) {
            throw InvalidArgumentException::forInvalidAlias($alias);
        }
        $distinct = $distinct ? 'DISTINCT ' : '';

        if ($column === '*') {
            $this->columns[] = sprintf(
                '%s(%s*) as %s',
                $function,
                $distinct,
                $alias
            );

        } else {
            if (! preg_match('/^([a-zA-Z0-9_]+\.)?[a-zA-Z0-9_]+$/', $column)) {
                throw InvalidArgumentException::forInvalidColumnName($column);
            }
            $this->columns[] = $this->columns[] = sprintf(
                '%s(%s%s) as %s',
                $function,
                $distinct,
                $column,
                $alias
            );
        }

        return $this;
    }

    /**
     * Combine rows from two or more tables, based on a related column between them.
     *
     * @param string $table The name of the referenced table. The string can
     *   contains only alphanumeric or underscore characters.
     * @param string $alias The alias name of the table. The string can
     *   contains only alphanumeric or underscore characters.
     * @param string $keyColumn [optional] The name of the foreign key column.
     *   The string can contains only alphanumeric or underscore characters.
     *   In addition, it is possible to prepend a table name/alias adding a
     *   dot between.
     * @param string $refColumn [optional] The name of the referenced column.
     *   The string can contains only alphanumeric or underscore characters.
     * @param string $type [optional] The join type; default: 'LEFT JOIN'.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidArgumentException if table name, alias, column name or
     *   type is not valid.
     */
    public function join(
        string $table,
        string $alias,
        string $keyColumn = null,
        string $refColumn = null,
        string $type = 'LEFT JOIN'): self
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw InvalidArgumentException::forInvalidTableName($table);
        } elseif (! preg_match('/^[a-zA-Z0-9_]+$/', $alias)) {
            throw InvalidArgumentException::forInvalidAlias($alias);
        } elseif ($keyColumn && ! preg_match('/^([a-zA-Z0-9_]+\.)?[a-zA-Z0-9_]+$/', $keyColumn)) {
            throw InvalidArgumentException::forInvalidColumnName($keyColumn);
        } elseif ($refColumn && ! preg_match('/^[a-zA-Z0-9_]+$/', $refColumn)) {
            throw InvalidArgumentException::forInvalidColumnName($refColumn);
        }

        $type = strtoupper($type);
        if (! in_array($type, Query::JOIN_TYPES)) {
            throw InvalidArgumentException::forInvalidJoinType($type);
        }

        $this->join[] = [
            'table'  => $table,
            'alias'  => $alias,
            'keyCol' => $keyColumn,
            'refCol' => $refColumn,
            'type'   => $type
        ];

        return $this;
    }

    /**
     * Generate SQL for JOIN clause.
     *
     * @return string
     */
    private function getJoinClause() : string
    {
        $sql = '';
        foreach ($this->join as $j) {
            $sql .= sprintf(' %s %s %s', $j['type'], $j['table'], $j['alias']);
            if ($j['keyCol'] && $j['refCol']) {
                $sql .= sprintf(' ON %s=%s.%s', $j['keyCol'], $j['alias'], $j['refCol']);
            }
        }

        return $sql;
    }

    /**
     * @inheritDoc
     */
    public function getStatement(): string
    {
        return sprintf(
            "SELECT %s FROM %s%s%s%s%s%s%s;",
            empty($this->columns) ? '*' : implode(', ', $this->columns),
            $this->table,
            $this->getJoinClause(),
            $this->getWhereClause(),
            $this->getGroupByClause(),
            $this->getHavingClause(),
            $this->getOrderByClause(),
            $this->getLimitClause()
        );
    }
}
