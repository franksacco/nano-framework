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
 * Trait for handling values in INSERT and UPDATE statement.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait ValuesTrait
{
    use ParametersTrait;

    /**
     * The column list.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * The list of parameters referred to values.
     *
     * @var array
     */
    protected $values = [];

    /**
     * Add column and value for the update.
     *
     * @param string $column The name of the column. The string can contains
     *   only alphanumeric or underscore characters. In addition, it is
     *   possible to prepend a table name/alias adding a dot between.
     * @param mixed $value The value of the column.
     * @param int $type [optional] The data type using the Types::* constants;
     *   if NULL, the type is evaluated from the value.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidArgumentException if column name is not valid.
     */
    public function addValue(string $column, $value, int $type = null): self
    {
        if (! preg_match('/^([a-zA-Z0-9_]+\.)?[a-zA-Z0-9_]+$/', $column)) {
            throw InvalidArgumentException::forInvalidColumnName($column);
        }
        $this->columns[] = $column;
        $this->values[]  = $this->setParameter($value, $type);
        return $this;
    }

    /**
     * Add columns and values for the update.
     *
     * Each item in the list must be in the form `column` => `value` or
     * `column` => [`value`, `type`], where:
     *  - `column` is the name of the column. The string can contains
     *   only alphanumeric or underscore characters. In addition, it is
     *   possible to prepend a table name/alias adding a dot between.<br>
     *  - `value` is the value of the column.
     *  - `type` is the data type using the Types::* constants;
     *   if NULL, the type is evaluated from the value.
     *
     * @param array $values The list of values.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidArgumentException if value list is not valid.
     */
    public function addValues(array $values): self
    {
        foreach ($values as $column => $value) {
            $column = (string) $column;
            if (is_array($value)) {
                list($v, $t) = $value;
                $this->addValue($column, $v, $t);

            } else {
                $this->addValue($column, $value);
            }
        }
        return $this;
    }
}