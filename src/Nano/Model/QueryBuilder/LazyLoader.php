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

use Nano\Model\Entity;
use Nano\Model\EntityCollection;
use Nano\Model\Exception\ModelExecutionException;
use Nano\Model\Metadata\Relation;

/**
 * Helper class for retrieving relations that use lazy loading.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class LazyLoader
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
     * @param Entity $entity The entity instance.
     * @param Relation $relation The relation that has lazy loading.
     */
    public function __construct(Entity $entity, Relation $relation)
    {
        $this->entity   = $entity;
        $this->relation = $relation;
    }

    /**
     * Execute the lazy loading.
     *
     * @param string $primaryKey The primary key value for OneToOne relations.
     * @return Entity|EntityCollection|null
     *
     * @throws ModelExecutionException if an error occur during query execution.
     */
    public function execute($primaryKey)
    {
        if ($this->relation->isOneToOne()) {
            return $this->loadOneToOne($primaryKey);

        } elseif ($this->relation->isOneToMany()) {
            return $this->loadOneToMany();

        } else {
            return $this->loadManyToMany();
        }
    }

    /**
     * Load a OneToOne relation.
     *
     * @param string $primaryKey The primary key value for OneToOne relations.
     * @return Entity|null Returns the searched entity instance or
     *   NULL if not found.
     *
     * @throws ModelExecutionException if an error occur during query execution.
     */
    private function loadOneToOne(string $primaryKey): ?Entity
    {
        /** @var Entity $bindingEntity */
        $bindingEntity = $this->relation->getBindingEntity()->getClassName();
        return $bindingEntity::get($primaryKey);
    }

    /**
     * Load a OneToMany relation.
     *
     * @return EntityCollection Returns the entity collection referred to the
     *   relation.
     *
     * @throws ModelExecutionException if an error occur during query execution.
     */
    private function loadOneToMany(): EntityCollection
    {
        /** @var Entity $bindingEntity */
        $bindingEntity = $this->relation->getBindingEntity()->getClassName();
        $result = $bindingEntity::all()
            ->where('t_0.' . $this->relation->getBindingKey(), '=', $this->entity->getId())
            ->execute();
        return new EntityCollection($this->entity, $this->relation, $result);
    }

    /**
     * Load a ManyToMany relation.
     *
     * @return EntityCollection Returns the entity collection referred to the
     *   relation.
     *
     * @throws ModelExecutionException if an error occur during query execution.
     */
    private function loadManyToMany(): EntityCollection
    {
        $builder = new SelectManyToManyBuilder($this->entity, $this->relation);
        return new EntityCollection($this->entity, $this->relation, $builder->execute());
    }
}
