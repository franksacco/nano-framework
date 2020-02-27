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

use Nano\Database\Facade\UpdateQueryInterface;
use Nano\Database\Traits\ValuesTrait;

/**
 * Class for SQL INSERT query generation.
 *
 * INSERT statement inserts new rows into an existing table.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Insert extends Query implements UpdateQueryInterface
{
    use ValuesTrait;

    /**
     * @inheritDoc
     */
    public function getStatement(): string
    {
        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s);',
            $this->table,
            implode(',', $this->columns),
            implode(',', $this->values)
        );
    }
}