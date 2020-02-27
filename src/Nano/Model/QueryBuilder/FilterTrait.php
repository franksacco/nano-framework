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


namespace Nano\Model\QueryBuilder;

use Nano\Database\Exception\InvalidArgumentException;
use Nano\Database\Select;
use Nano\Model\Exception\InvalidValueException;

/**
 * Trait for filter result-set.
 *
 * @property Select $query
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait FilterTrait
{
    /**
     * Filter result-set with AND conditions.
     *
     * Each condition is concatenated to the others with an `AND` operator.
     *
     * @param string $column The name of the column. The string can contains
     *   only alphanumeric or underscore characters. In addition, it is
     *   possible to prepend a table name or an alias to the column name adding
     *   a dot between them.
     * @param string $operator The comparison operator from:
     *   <code>=, !=, <>, <, >, <=, >=, [NOT] LIKE, [NOT] IN, IS [NOT] `null`<code>.
     * @param mixed $value The condition value. For `[NOT] IN` operator this
     *   must be an `array`, for `[NOT] IS` operator this is not considered,
     *   otherwise this must be a `scalar`.
     * @param int $type [optional] The data type using the `Types::*` constants;
     *   if `null`, the type is evaluated from `$value`.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidValueException if column name, operator or value is
     *   not valid.
     */
    public function where(string $column, string $operator, $value, int $type = null): self
    {
        try {
            $type ?
                $this->query->where($column, $operator, $value, $type) :
                $this->query->where($column, $operator, $value);

        } catch (InvalidArgumentException $exception) {
            throw new InvalidValueException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        return $this;
    }

    /**
     * Filter result-set with OR conditions.
     *
     * Each condition must be in the form `[$column, $operator, $value]` or
     * `[$column, $operator, $value, $type]`, where:
     *  - `$column` is the name of the column. The string can contains only
     *   alphanumeric or underscore characters. In addition, it is possible
     *   to prepend a table name or an alias to the column name adding a dot
     *   between them.
     *  - `$operator` is the comparison operator from:
     *   <code>=, !=, <>, <, >, <=, >=, [NOT] LIKE, [NOT] IN, IS [NOT] `null`<code>.
     *  - `$value` is the condition value. For `[NOT] IN` operator this
     *   must be an `array`, for `[NOT] IS` operator this is not considered,
     *   otherwise this must be a `scalar`.
     *  - `$type` is the data type using the `Types::*` constants;
     *   if `null`, the type is evaluated from `$value`.
     *
     * @param array $conditions The condition list.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidValueException if the condition list is not valid.
     */
    public function orWhere(array $conditions): self
    {
        try {
            $this->query->orWhere($conditions);

        } catch (InvalidArgumentException $exception) {
            throw new InvalidValueException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        return $this;
    }
}