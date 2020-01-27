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

namespace Nano\Model;

use Nano\Database\Delete;
use Nano\Database\Exception\QueryExecutionException;
use Nano\Database\Insert;
use Nano\Model\Exception\InvalidValueException;
use Nano\Model\Exception\ModelExecutionException;
use Nano\Model\Metadata\Relation;
use Nano\Model\QueryBuilder\ConnectionCollector;

/**
 * A collection of entities.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class EntityCollection implements \Iterator, \Countable
{
    /**
     * The entity which this collection is referred.
     *
     * @var Entity
     */
    private $entity;

    /**
     * The relation which this collection is referred.
     *
     * @var Relation
     */
    private $relation;

    /**
     * The list of entities of this collection.
     *
     * @var Entity[]
     */
    private $entities;

    /**
     * @var int
     */
    private $position = 0;

    /**
     * Initialize a collection of entities.
     *
     * @param Entity $entity The entity which this collection is referred.
     * @param Relation $relation The relation which this collection is referred.
     * @param Entity[] $entities the initial list of persisted entities.
     *
     * @throws InvalidValueException for an invalid value.
     */
    public function __construct(Entity $entity, Relation $relation, array $entities = [])
    {
        $this->entity   = $entity;
        $this->relation = $relation;

        $this->checkEntities($entities);
        $this->entities = $entities;
    }

    /**
     * Check the validity of an entity list.
     *
     * @param Entity[] $entities The entity list to check.
     *
     * @throws InvalidValueException for an invalid value.
     */
    private function checkEntities(array $entities)
    {
        $className = $this->relation->getBindingEntity()->getClassName();

        array_map(function ($entity) use ($className) {
            if (!is_object($entity) || get_class($entity) !== $className) {
                throw new InvalidValueException(sprintf(
                    'Invalid value in a EntityCollection: "%s" expected, "%s" given',
                    $className,
                    is_object($entity) ? get_class($entity) : gettype($entity)
                ));
            }
        }, $entities);
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->entities[$this->position] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        return isset($this->entities[$this->position]);
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Return the number of entities for this collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->entities);
    }

    /**
     * Add an entity to the relation.
     *
     * @param Entity $entity The entity to add.
     *
     * @throws InvalidValueException for an invalid entity object.
     * @throws ModelExecutionException if an error occur during query execution.
     */
    public function add(Entity $entity)
    {
        $this->checkEntities([$entity]);
        try {
            if ($this->relation->isOneToMany()) {
                $reverseRelation = $this->relation->getReverseRelation(get_class($this->entity));
                $entity->__set($reverseRelation->getName(), $this->entity);
                $entity->save();

            } else {
                // Be sure that the two entities in the ManyToMany relation
                // has been persisted in the database.
                $entity->save();
                $this->entity->save();

                $query = (new Insert($this->relation->getJunctionTable()))
                    ->addValues([
                        $this->relation->getForeignKey() => $this->entity->getId(),
                        $this->relation->getBindingKey() => $entity->getId()
                    ]);
                $connection = ConnectionCollector::getConnection();
                $connection->executeUpdate($query);
            }

        } catch (QueryExecutionException $exception) {
            throw new ModelExecutionException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        $this->entities[] = $entity;
    }

    /**
     * Remove an entity from the relation.
     *
     * If the relation is of type OneToMany, the method {@see Entity::delete()}
     * of the provided entity is called. Otherwise, will be deleted only the
     * entry in the junction table.
     *
     * @param Entity $entity The entity to remove.
     *
     * @throws ModelExecutionException if an error occur during query execution.
     */
    public function remove(Entity $entity)
    {
        $key = array_search($entity, $this->entities, true);
        if ($key !== false) {

            try {
                if ($this->relation->isOneToMany()) {
                    $entity->delete();

                } else {
                    $query = (new Delete($this->relation->getJunctionTable()))
                        ->where($this->relation->getBindingKey(), '=', $entity->getId());
                    $connection = ConnectionCollector::getConnection();
                    $connection->executeUpdate($query);
                }

            } catch (QueryExecutionException $exception) {
                throw new ModelExecutionException(
                    $exception->getMessage(),
                    $exception->getCode(),
                    $exception
                );
            }

            unset($this->entities[$key]);
        }
    }

    /**
     * Set the entities of the collection.
     *
     * @param Entity[] $entities The entity list.
     *
     * @throws InvalidValueException for an invalid value.
     */
    public function set(array $entities)
    {
        $this->checkEntities($entities);

        $compareFunc = function (Entity $e1, Entity $e2): int {
            return intval($e1->getId()) - intval($e2->getId());
        };
        $toAdd    = array_udiff($entities, $this->entities, $compareFunc);
        $toRemove = array_udiff($this->entities, $entities, $compareFunc);

        foreach ($toRemove as $e) {
            $this->remove($e);
        }

        foreach ($toAdd as $e) {
            $this->add($e);
        }
    }

    /**
     * Get the list of entities of this collection.
     *
     * @return Entity[] Returns the list of entities.
     */
    public function toArray(): array
    {
        return $this->entities;
    }

    /**
     * Magic method called by {@see var_dump()} when dumping
     * an object to get the properties that should be shown.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return $this->toArray();
    }
}
