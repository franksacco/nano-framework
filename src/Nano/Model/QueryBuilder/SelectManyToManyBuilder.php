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
use Nano\Model\Metadata\Relation;

/**
 * Helper class to execute a lazy loading of a ManyToMany relation.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class SelectManyToManyBuilder extends QueryBuilder
{
    /**
     * @var Entity
     */
    private $entity;

    /**
     * @var Relation
     */
    private $relation;

    /**
     * Initialize the SelectManyToManyBuilder.
     *
     * @param Entity $entity The entity that owns the relation.
     * @param Relation $relation The ManyToMany relation.
     *
     * @throws InvalidEntityException for an invalid entity definition.
     */
    public function __construct(Entity $entity, Relation $relation)
    {
        $this->entity   = $entity;
        $this->relation = $relation;

        parent::__construct($relation->getBindingEntity()->getClassName());
    }

    /**
     * @inheritDoc
     */
    protected function initializeQuery(): Select
    {
        $query = parent::initializeQuery();
        $query->join(
            $this->relation->getJunctionTable(),
            't_j_0',
            't_0.' . $this->metadata->getPrimaryKey(),
            $this->relation->getBindingKey()
        );
        $query->where(
            't_j_0.' . $this->relation->getForeignKey(),
            '=',
            $this->entity->getId()
        );
        return $query;
    }

    /**
     * Execute the query and return the list of entities.
     *
     * @return Entity[] Return the list of searched entities.
     *
     * @throws ModelExecutionException if an error occur during query execution.
     */
    public function execute(): array
    {
        $result = $this->executeQuery();
        return $this->resultToEntities($result);
    }
}