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
use Nano\Database\Query;
use Nano\Model\Entity;
use Nano\Model\Exception\InvalidValueException;
use Nano\Model\Exception\ModelExecutionException;

/**
 * Helper class for retrieving a list of entity.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class SelectAllBuilder extends QueryBuilder
{
    use FilterTrait;

    /**
     * Add a sorting rule for result-set.
     *
     * @param string $column The name of the column. The string can contains
     *   only alphanumeric or underscore characters. In addition, it is
     *   possible to prepend a table name or alias to the column adding a dot
     *   between them.
     * @param string $order [optional] The sort order using Query::SORT_*
     *   constants; default: Query::SORT_ASC.
     * @return static Returns self reference for method chaining.
     *
     * @throws InvalidValueException if column name or order is not valid.
     */
    public function orderBy(string $column, string $order = Query::SORT_ASC): self
    {
        try {
            $this->query->orderBy($column, $order);

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
     * Specify the number of rows to return and to skip.
     *
     * `$limit = 0` means no limit,
     * `$offset = 0` means no offset.
     *
     * @param int $limit The limit value.
     * @param int $offset [optional] The offset value; default: 0.
     * @return static Returns self reference for method chaining.
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->query->limit($limit, $offset);
        return $this;
    }

    /**
     * Execute the query and return the list of entities.
     *
     * @return Entity[] Returns the list of searched entities.
     *
     * @throws ModelExecutionException if an error occur during query execution.
     */
    public function execute(): array
    {
        $result = $this->executeQuery();
        return $this->resultToEntities($result);
    }
}