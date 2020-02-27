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

/**
 * Abstract class for SQL statement generation.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
abstract class Query
{
    /**
     * The supported aggregated function list.
     */
    const AGGREGATE_FUNCTIONS = [
        'AVG',
        'COUNT',
        'MAX',
        'MIN',
        'SUM'
    ];

    /**
     * The list of JOIN types.
     */
    const JOIN_TYPES = [
        'JOIN',
        'INNER JOIN',
        'LEFT JOIN',
        'LEFT OUTER JOIN',
        'RIGHT JOIN',
        'RIGHT OUTER JOIN',
        'FULL JOIN',
        'FULL OUTER JOIN'
    ];

    /**
     * The comparison operator list.
     */
    const COMPARISON_OPERATORS = [
        '=',
        '!=',
        '<>',
        '>',
        '<',
        '>=',
        '<=',
        'LIKE',
        'NOT LIKE'
    ];
    const COMPARISON_SET_OPERATORS = [
        'IN',
        'NOT IN'
    ];
    const COMPARISON_NULL_OPERATORS = [
        'IS',
        'IS NOT'
    ];

    /**
     * Sorts the result-set in ascending order.
     */
    const SORT_ASC = 'ASC';
    /**
     * Sorts the result-set in descending order.
     */
    const SORT_DESC = 'DESC';
    /**
     * The sort orders.
     */
    const SORT_ORDERS = [
        self::SORT_ASC,
        self::SORT_DESC
    ];

    /**
     * The main table name.
     *
     * @var string
     */
    protected $table;

    /**
     * Initialize a Query object.
     *
     * @param string $table The name of the main table. The string can
     *   contains only alphanumeric or underscore characters.
     * @param string $alias [optional] The alias name for the main table.
     *   The string can contains only alphanumeric or underscore characters.
     *
     * @throws InvalidArgumentException if table name or alias is invalid.
     */
    public function __construct(string $table, string $alias = null)
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw InvalidArgumentException::forInvalidTableName($table);
        }
        $this->table = $table;
        if ($alias) {
            if (! preg_match('/^[a-zA-Z0-9_]+$/', $alias)) {
                throw InvalidArgumentException::forInvalidAlias($alias);
            }
            $this->table .= ' ' . $alias;
        }
    }
}