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

use Nano\Database\Select;
use Nano\Model\Entity;
use Nano\Model\Exception\InvalidEntityException;
use Nano\Model\Exception\ModelExecutionException;

/**
 * Helper class for retrieving a single entity.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class SelectOneBuilder extends QueryBuilder
{
    /**
     * @var string
     */
    private $primaryKeyValue;

    /**
     * Initialize the SelectOneBuilder.
     *
     * @param string $entityClass The class name of the entity to be retrieved.
     * @param string $primaryKey The primary key value.
     *
     * @throws InvalidEntityException for an invalid entity definition.
     */
    public function __construct(string $entityClass, string $primaryKey)
    {
        $this->primaryKeyValue = $primaryKey;

        parent::__construct($entityClass);
    }

    /**
     * @inheritDoc
     */
    protected function initializeQuery(): Select
    {
        $query = parent::initializeQuery();
        $query->where(
            't_0.' . $this->metadata->getPrimaryKey(),
            '=',
            $this->primaryKeyValue
        );
        return $query;
    }

    /**
     * Execute the query and return the entity if found.
     *
     * @return Entity|null Return an instance of searched entity or NULL if not found.
     *
     * @throws ModelExecutionException if an error occur during query execution.
     */
    public function execute(): ?Entity
    {
        $this->showDeleted();
        $result   = $this->executeQuery();
        $entities = $this->resultToEntities($result);
        return $entities[0] ?? null;
    }
}
