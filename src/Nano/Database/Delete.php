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
use Nano\Database\Traits\FilterTrait;
use Nano\Database\Traits\LimitTrait;

/**
 * Class for SQL DELETE query generation.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Delete extends Query implements UpdateQueryInterface
{
    use FilterTrait, LimitTrait;

    /**
     * @inheritDoc
     */
    public function getStatement(): string
    {
        return sprintf(
            'DELETE FROM %s%s%s;',
            $this->table,
            $this->getWhereClause(),
            $this->getLimitClause()
        );
    }
}