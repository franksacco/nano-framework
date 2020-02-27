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

/**
 * Trait for LIMIT and OFFSET SQL clauses.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait LimitTrait
{
    /**
     * Number of records to return (0 = no limit).
     *
     * @var int
     */
    private $limit = 0;

    /**
     * Number of rows to skip (0 = no offset).
     *
     * @var int
     */
    private $offset = 0;

    /**
     * Specify the number of rows to return and to skip.
     *
     * `$limit = 0` means no limit,
     * `$offset = 0` means no offset.
     *
     * @param int $limit The limit value.
     * @param int $offset [optional] The offset value. Default 0.
     * @return static Returns self reference for method chaining.
     */
    public function limit(int $limit, int $offset = 0): self
    {
        if ($limit > 0) {
            $this->limit = $limit;
            if ($offset > 0) {
                $this->offset = $offset;
            }
        }
        return $this;
    }

    /**
     * Generate the SQL LIMIT clause.
     *
     * @return string
     */
    protected function getLimitClause(): string
    {
        $sql = '';
        if ($this->limit > 0) {
            $sql .= ' LIMIT ' . $this->limit;
            if ($this->offset > 0) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        return $sql;
    }
}