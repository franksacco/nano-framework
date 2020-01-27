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
 * Trait for handle sorting in SELECT statement.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait SortingTrait
{
    /**
     * The order criteria list.
     *
     * @var array
     */
    private $sortingRules = [];

    /**
     * Add a sorting rule for result-set.
     *
     * @param string $column The name of the column. The string can contains
     *   only alphanumeric or underscore characters. In addition, it is
     *   possible to prepend a table name/alias adding a dot between.
     * @param string $order [optional] The sort order using Query::SORT_*
     *   constants; default: Query::SORT_ASC.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidArgumentException if column name or order is not valid.
     */
    public function orderBy(string $column, string $order = Query::SORT_ASC): self
    {
        if (! preg_match('/^([a-zA-Z0-9_]+\.)?[a-zA-Z0-9_]+$/', $column)) {
            throw InvalidArgumentException::forInvalidColumnName($column);
        }
        $order = strtoupper($order);
        if (! in_array($order, Query::SORT_ORDERS)) {
            throw InvalidArgumentException::forInvalidSortOrder($order);
        }
        $this->sortingRules[] = $column . ' ' . $order;
        return $this;
    }

    /**
     * Generate SQL for ORDER BY clause.
     *
     * @return string
     */
    private function getOrderByClause(): string
    {
        $sql = '';
        if (! empty($this->sortingRules)) {
            $sql .= sprintf(
                ' ORDER BY %s',
                implode(',', $this->sortingRules)
            );
        }
        return $sql;
    }
}
