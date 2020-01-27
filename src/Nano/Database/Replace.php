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

/**
 * Class for SQL REPLACE query generation.
 *
 * NOTE: this command can be used only with MySQL databases.
 *
 * REPLACE works exactly like INSERT, except that if an old row in the table
 * has the same value as a new row for a PRIMARY KEY or a UNIQUE index, the
 * old row is deleted before the new row is inserted.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Replace extends Insert
{
    /**
     * @inheritDoc
     */
    public function getStatement(): string
    {
        return sprintf(
            'REPLACE INTO %s (%s) VALUES (%s);',
            $this->table,
            implode(',', $this->columns),
            implode(',', $this->values)
        );
    }
}
