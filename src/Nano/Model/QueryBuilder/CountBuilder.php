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
use Nano\Model\Exception\ModelExecutionException;

/**
 * Helper class for count a group of entities that respect particular conditions.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class CountBuilder extends QueryBuilder
{
    use FilterTrait;

    private const COUNT_COLUMN_ALIAS = 'count';

    /**
     * @inheritDoc
     *
     * @throws InvalidValueException
     */
    protected function initializeQuery(): Select
    {
        try {
            return (new Select($this->metadata->getTable(), 't_0'))
                ->aggregate('COUNT', '*', self::COUNT_COLUMN_ALIAS);

        } catch (InvalidArgumentException $exception) {
            throw new InvalidValueException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Get the count result.
     *
     * @return int Returns the number of entities with given conditions.
     *
     * @throws ModelExecutionException if an error occur during query execution.
     */
    public function execute(): int
    {
        $result = $this->executeQuery();
        if (! isset($result[0][self::COUNT_COLUMN_ALIAS])) {
            throw new ModelExecutionException(
                'Unable to retrieve the count value from the query result'
            );
        }

        return (int) $result[0][self::COUNT_COLUMN_ALIAS];
    }
}
