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

namespace Nano\Model\Metadata;

/**
 * Representation of a relation between two entities.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Relation
{
    /**
     * Represents a OneToOne relation.
     */
    const ONE_TO_ONE = 'OneToOne';
    /**
     * Represents a OneToMany relation.
     */
    const ONE_TO_MANY = 'OneToMany';
    /**
     * Represents a ManyToMany relation.
     */
    const MANY_TO_MANY = 'ManyToMany';

    /**
     * Represents a relation with eager loading.
     */
    const EAGER = 'eager';
    /**
     * Represents a relation with lazy loading.
     */
    const LAZY = 'lazy';

    /**
     * The relation option list.
     *
     * The array contains the following items:
     *  - 'name': property name associated to this relation;
     *  - 'type': type of relation (Relation::ONE_TO_ONE, Relation::ONE_TO_MANY
     *   or Relation::MANY_TO_MANY);
     *  - 'loading': type of loading (Relation::EAGER or Relation::LAZY);
     *  - 'entity': binding entity reference;
     *  - 'foreignKey': foreign key column name;
     *  - 'bindingKey': referenced column name;
     *  - 'junctionTable': junction table used in ManyToMany relations.
     *
     * @var array
     */
    private $options;

    /**
     * Create a relation representation.
     *
     * @param array $options The relation option list.
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Get the property name associated to this relation.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->options['name'];
    }

    /**
     * Determine if the relation is of type OneToOne.
     *
     * @return bool
     */
    public function isOneToOne(): bool
    {
        return $this->options['type'] === self::ONE_TO_ONE;
    }

    /**
     * Determine if the relation is of type OneToMany.
     *
     * @return bool
     */
    public function isOneToMany(): bool
    {
        return $this->options['type'] === self::ONE_TO_MANY;
    }

    /**
     * Determine if the relation is of type ManyToMany.
     *
     * @return bool
     */
    public function isManyToMany(): bool
    {
        return $this->options['type'] === self::MANY_TO_MANY;
    }

    /**
     * Determine if the relation has an eager loading.
     *
     * @return bool
     */
    public function isEager(): bool
    {
        return $this->options['loading'] === self::EAGER;
    }

    /**
     * Determine if the relation has a lazy loading.
     *
     * @return bool
     */
    public function isLazy(): bool
    {
        return $this->options['loading'] === self::LAZY;
    }

    /**
     * Get the column name of the foreign key.
     *
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->options['foreignKey'];
    }

    /**
     * Get the column name of the referenced key.
     *
     * @return string
     */
    public function getBindingKey(): string
    {
        return $this->options['bindingKey'];
    }

    /**
     * Get the junction table name used in many-to-many relations.
     *
     * If relation is not ManyToMany, an empty string is returned.
     *
     * @return string
     */
    public function getJunctionTable(): string
    {
        return $this->options['junctionTable'] ?? '';
    }

    /**
     * Get metadata of binding entity.
     *
     * @return EntityMetadata
     */
    public function getBindingEntity(): EntityMetadata
    {
        if (is_string($this->options['entity'])) {
            $this->options['entity'] = MetadataCollector::get($this->options['entity']);
        }
        return $this->options['entity'];
    }

    /**
     * Get reverse relation for a OneToMany relation.
     *
     * @param string $className The class name of the entity that owns the OneToMany relation.
     * @return Relation|null Return the reverse relation, or NULL if it is not defined.
     */
    public function getReverseRelation(string $className): ?Relation
    {
        if ($this->isOneToMany()) {
            foreach ($this->getBindingEntity()->getRelations() as $relation) {
                if ($relation->isOneToOne() &&
                    $className === $relation->getBindingEntity()->getClassName() &&
                    $this->options['foreignKey'] === $relation->getBindingKey() &&
                    $this->options['bindingKey'] === $relation->getForeignKey()
                ) {
                    return $relation;
                }
            }
        }
        return null;
    }
}
