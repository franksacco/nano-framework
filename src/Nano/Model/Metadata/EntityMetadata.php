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

use Nano\Model\Entity;
use Nano\Model\Exception\InvalidEntityException;
use Nano\Model\Exception\NotDefinedPropertyException;
use ReflectionClass;
use ReflectionException;

/**
 * Adapter class to retrieve information about an entity definition.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class EntityMetadata
{
    use ParseValuesTrait;

    /**
     * Date and time format.
     */
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const DATE_FORMAT     = 'Y-m-d';
    const TIME_FORMAT     = 'H:i:s';

    /**
     * Column name for datetime and soft deletion functionality.
     */
    const COLUMN_UPDATED = 'updated';
    const COLUMN_CREATED = 'created';
    const COLUMN_DELETED = 'deleted';

    /**
     * Available column types.
     */
    private const AVAILABLE_COLUMN_TYPES = [
        Entity::TYPE_BOOL,
        Entity::TYPE_DATE,
        Entity::TYPE_DATETIME,
        Entity::TYPE_FLOAT,
        Entity::TYPE_JSON,
        Entity::TYPE_INT,
        Entity::TYPE_STRING,
        Entity::TYPE_TIME
    ];

    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $primaryKey;

    /**
     * @var array
     */
    private $columns;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var Relation[]
     */
    private $relations;

    /**
     * @var bool
     */
    private $datetime;

    /**
     * @var bool
     */
    private $softDeletion;

    /**
     * @var bool
     */
    private $readOnly;

    /**
     * Parse entity definition and create entity metadata.
     *
     * @param string $entityClass The entity class name.
     *
     * @throws InvalidEntityException for an invalid entity definition.
     */
    public function __construct(string $entityClass)
    {
        try {
            $this->reflection = new ReflectionClass($entityClass);
        } catch (ReflectionException $e) {
            throw InvalidEntityException::forNonExistingClass($entityClass);
        }

        $this->table        = (string) $this->reflection->getStaticPropertyValue('table');
        $this->primaryKey   = (string) $this->reflection->getStaticPropertyValue('primaryKey');
        $this->datetime     = (bool) $this->reflection->getStaticPropertyValue('datetime');
        $this->softDeletion = (bool) $this->reflection->getStaticPropertyValue('softDeletion');
        $this->columns      = $this->parseColumns();
        $this->readOnly     = (bool) $this->reflection->getStaticPropertyValue('readOnly');
    }

    /**
     * Parse columns definition.
     *
     * @return array
     *
     * @throws InvalidEntityException for an invalid columns definition.
     */
    private function parseColumns(): array
    {
        $columns = $this->reflection->getStaticPropertyValue('columns');
        if (! is_array($columns)) {
            throw InvalidEntityException::forNonArrayColumnList(
                $columns,
                $this->reflection->getName()
            );
        }

        foreach ($columns as $column => $type) {
            if (! in_array($type, self::AVAILABLE_COLUMN_TYPES)) {
                throw InvalidEntityException::forInvalidColumnType(
                    $type,
                    $column,
                    $this->reflection->getName()
                );
            }
        }

        $columns += [$this->primaryKey => Entity::TYPE_STRING];
        if ($this->datetime) {
            $columns[self::COLUMN_UPDATED] = Entity::TYPE_DATETIME;
            $columns[self::COLUMN_CREATED] = Entity::TYPE_DATETIME;
        }

        if ($this->softDeletion) {
            $columns[self::COLUMN_DELETED] = Entity::TYPE_DATETIME;
        }

        return $columns;
    }

    /**
     * Parse relations definition.
     *
     * @throws InvalidEntityException for an invalid relation definition.
     */
    public function parseRelations()
    {
        $parser = new RelationsParser($this->reflection);
        $this->relations = $parser->parse();
        foreach ($this->relations as $relation) {
            if ($relation->isOneToMany()) {
                $parser->checkReverseRelation($relation);
            }
        }
        $this->properties = $this->buildProperties($this->columns, $this->relations);
    }

    /**
     * @param array $columns
     * @param Relation[] $relations
     * @return array
     */
    private function buildProperties(array $columns, array $relations): array
    {
        foreach ($relations as $relation) {
            $columns[$relation->getName()] = $relation;
        }

        return $columns;
    }

    /**
     * Get the table that refers to this entity.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the primary key column name.
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Get the complete list of columns names.
     *
     * @return string[]
     */
    public function getColumns(): array
    {
        return array_keys($this->columns);
    }

    /**
     * Determine the type of a property.
     *
     * @param string $name The property name.
     * @return string|Relation Returns the property type.
     *
     * @throws NotDefinedPropertyException if the property is not defined.
     */
    public function getPropertyType(string $name)
    {
        if (!isset($this->properties[$name])) {
            throw new NotDefinedPropertyException(sprintf(
                'The property "%s" is not defined in entity "%s"',
                $name,
                $this->reflection->getName()
            ));
        }
        return $this->properties[$name];
    }

    /**
     * Get the list of relations.
     *
     * @return Relation[]
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * Determine if the entity has at least one relation to be considered during query building.
     *
     * @return bool Returns `true` if entity has at least one relation with eager
     *     loading or a OneToOne relation with Lazy loading, `false` otherwise.
     */
    public function hasRelationsForQueryBuilding(): bool
    {
        foreach ($this->relations as $relation) {
            if ($relation->isEager() || ($relation->isOneToOne() && $relation->isLazy())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if entity has datetime functionality enabled.
     *
     * @return bool
     */
    public function hasDatetime(): bool
    {
        return $this->datetime;
    }

    /**
     * Check if entity has soft deletion functionality enabled.
     *
     * @return bool
     */
    public function hasSoftDeletion(): bool
    {
        return $this->softDeletion;
    }

    /**
     * Determine if entity is read-only.
     *
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * Returns the class name of the entity.
     *
     * @return string
     */
    public function getClassName(): string
    {
        return $this->reflection->getName();
    }

    /**
     * Create an entity instance starting from data given.
     *
     * @param array $data [optional] The data passed to the entity.
     * @return Entity Returns the created entity instance.
     */
    public function newInstance(array $data = []): Entity
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->reflection->newInstance($data);
    }
}