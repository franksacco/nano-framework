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
use Nano\Database\Traits\ValuesTrait;

/**
 * Class for SQL UPDATE query generation.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Update extends Query implements UpdateQueryInterface
{
    use ValuesTrait, FilterTrait, LimitTrait {
        ValuesTrait::setParameter insteadof FilterTrait;
        ValuesTrait::evaluateType insteadof FilterTrait;
        ValuesTrait::getParameters insteadof FilterTrait;
    }

    /**
     * @inheritDoc
     */
    public function getStatement(): string
    {
        $set = implode(',', array_map(function ($c, $p) {
            return $c . '=' . $p;
        }, $this->columns, $this->values));

        return sprintf(
            'UPDATE %s SET %s%s%s;',
            $this->table,
            $set,
            $this->getWhereClause(),
            $this->getLimitClause()
        );
    }
}
