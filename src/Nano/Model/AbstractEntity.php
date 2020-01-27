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

use Nano\Model\Exception\InvalidValueException;
use Nano\Model\Exception\InvalidEntityException;
use Nano\Model\Exception\ModelExecutionException;
use Nano\Model\Exception\NotDefinedPropertyException;
use Nano\Model\Metadata\EntityMetadata;
use Nano\Model\Metadata\MetadataCollector;
use Nano\Model\Metadata\Relation;
use Nano\Model\QueryBuilder\LazyLoader;

/**
 * Implements basic functionality for get or set property from an entity.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
abstract class AbstractEntity
{
    /**
     * The table that refers to this entity.
     *
     * @var string
     */
    public static $table = '';

    /**
     * The primary key column name.
     *
     * Only one primary key is supported.
     * Example of SQL definition:
     * <code>INT UNSIGNED PRIMARY KEY AUTO_INCREMENT</code>
     *
     * @var string
     */
    public static $primaryKey = 'id';

    /**
     * The column list definition.
     *
     * Each item of the list MUST be in the form `column` => `type` where:
     *  - `column` is the column name composed only by alphanumeric or
     *   underscore characters;
     *  - `type` is the data type using the Entity::TYPE_* constants.
     *
     * In this list MUST NOT be inserted:
     *  - primary key column;
     *  - relation columns, used as reference to an another entity;
     *  - `created` and `updated` datetime columns, used for entity history;
     *  - `deleted` datetime column, used for soft deletion.
     *
     * Example:
     * <code>
     * $columns = [
     *     'username'  => Entity::TYPE_STRING,
     *     'password'  => Entity::TYPE_STRING,
     *     'lastLogin' => Entity::TYPE_DATETIME
     * ]
     * </code>
     *
     * @var array
     */
    public static $columns = [];

    /**
     * The relations definition.
     *
     * Every item of this array represent a relation between current entity and
     * a specified binding entity.
     * Each relation SHOULD be identified uniquely by its index.
     *
     * A relation is represented by an array with the following items:
     *  - 'name' is the name of the property associated to this relation;
     *  - 'type' is the type if the relation using the Relation::ONE_TO_ONE,
     *    Relation::ONE_TO_MANY or  Relation::MANY_TO_MANY constants;
     *  - 'entity' is the class name of the binding entity;
     *  - 'loading' [optional] is the type of loading using Relation::EAGER or
     *   Relation::LAZY constants; default is Relation::EAGER for OneToOne
     *   relations, Relation::LAZY otherwise;
     *  - 'foreignKey' [optional] is the column name if the foreign key, by
     *   default, is the value of `name` option;
     *  - 'bindingKey' [optional] is the column name of the binding key,
     *   default is "id";
     *  - 'junctionTable' [required for ManyToMany relations] is the name of
     *   the junction table.
     *
     * In OneToMany relations, `bindingKey` is referred to a column in the
     * external table while `foreignKey` is referred to the table associated
     * to this entity.
     * In ManyToMany relations, it is supposed that the junction table has two
     * columns and their names are the values of `foreignKey` and `bindingKey`
     * options respectively.
     * The first column is referred to the entity table, and the latter one is
     * referred to the external table.
     *
     * Example of definition:
     * <code>
     * $relations = [
     *     'user_role' => [
     *         'name'       => 'role',
     *         'type'       => Relation::RELATION_ONE_TO_ONE,
     *         'entity'     => UserRole::class,
     *         'foreignKey' => 'roleId',
     *         'bindingKey' => 'id'
     *     ]
     * ];
     * </code>
     *
     * @var array
     */
    public static $relations = [];

    /**
     * Enable datetime functionality.
     *
     * For this feature to work, the `updated` and `created` columns of the
     * DATETIME type must be defined in the entity table.
     *
     * Example of SQL definition:
     * <code>
     * updated DATETIME NOT NULL
     * created DATETIME NOT NULL
     * </code>
     *
     * @var bool
     */
    public static $datetime = true;

    /**
     * Enable soft deletion functionality.
     *
     * For this feature to work, the `deleted` column of the DATETIME type must
     * be defined in the entity table.
     *
     * Example of SQL definition:
     * <code>deleted DATETIME DEFAULT NULL</code>
     *
     * @var bool
     */
    public static $softDeletion = true;

    /**
     * Disable automatic setters and entity saving.
     *
     * @var bool
     */
    public static $readOnly = false;

    /**
     * The entity metadata.
     *
     * @var EntityMetadata
     */
    private $metadata;

    /**
     * The primary key value or NULL if the entity is not persisted.
     *
     * @var string|null
     */
    protected $id = null;

    /**
     * The persisted data of the entity.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Updates to be committed in the next call of <code>save()</code> method.
     *
     * @var array
     */
    protected $updatedData = [];

    /**
     * The list of booleans indicating the execution of properties' lazy loading.
     *
     * @var bool[]
     */
    private $lazyLoad = [];

    /**
     * Create an instance of this entity.
     *
     * @param array $data [optional] The list of values for entity properties.
     *
     * @throws NotDefinedPropertyException if one of the properties given is not defined.
     * @throws InvalidEntityException for an invalid entity definition.
     * @throws InvalidValueException if one of the properties given has an invalid value.
     */
    public function __construct(array $data = [])
    {
        $metadata = $this->getMetadata();

        if (isset($data[$metadata->getPrimaryKey()])) {
            $this->id = (string) $data[$metadata->getPrimaryKey()];
        }
        foreach ($data as $property => $value) {
            $this->data[$property] = $metadata->parseValue($this, $property, $value);
        }

        foreach ($metadata->getRelations() as $relation) {
            // initialize lazy loading indicators
            if ($relation->isLazy()) {
                $this->lazyLoad[$relation->getName()] = false;
            }
            // initialize empty collections for *ToMany relations
            if (!$relation->isOneToOne() && !isset($this->data[$relation->getName()])) {
                $this->data[$relation->getName()] = new EntityCollection($this, $relation);
            }
        }
    }

    /**
     * Get metadata for this entity.
     *
     * @return EntityMetadata Returns the entity metadata.
     *
     * @throws InvalidEntityException for an invalid entity definition.
     */
    public function getMetadata(): EntityMetadata
    {
        if ($this->metadata === null) {
            $this->metadata = MetadataCollector::get(static::class);
        }
        return $this->metadata;
    }

    /**
     * Determines if this entity is new or is persisted in database.
     *
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->id === null;
    }

    /**
     * Determines if this entity has some updates that are not persisted.
     *
     * @return bool
     */
    public function isModified(): bool
    {
        return $this->updatedData !== [];
    }

    /**
     * Determines if this entity was soft deleted.
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return isset($this->data[EntityMetadata::COLUMN_DELETED]);
    }

    /**
     * Returns the primary key value or NULL if the entity is not persisted.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Get the value of an entity property.
     *
     * @param string $name The property name.
     * @return mixed Returns reference to the requested property.
     *
     * @throws NotDefinedPropertyException if the property is not defined or is not set.
     * @throws ModelExecutionException if an error occur during query execution.
     */
    public function __get(string $name)
    {
        // check for execution of a lazy loading
        $type = $this->getMetadata()->getPropertyType($name);
        if (!$this->isNew() && $type instanceof Relation
            && $type->isLazy() && !$this->lazyLoad[$name])
        {
            $loader = new LazyLoader($this, $type);

            $this->data[$name]     = $loader->execute($this->data[$name]);
            $this->lazyLoad[$name] = true;
        }

        if (array_key_exists($name, $this->updatedData)) {
            return $this->updatedData[$name];
        }
        return $this->data[$name] ?? null;
    }

    /**
     * Set the value for an entity property.
     *
     * @param string $name The property name.
     * @param mixed $value The property value.
     *
     * @throws NotDefinedPropertyException if the property is not defined.
     * @throws InvalidValueException if value is invalid or entity is read-only.
     */
    public function __set(string $name, $value)
    {
        $metadata = $this->getMetadata();
        if ($metadata->isReadOnly()) {
            throw new InvalidValueException(sprintf(
                'Properties of "%s" entity are read-only',
                static::class
            ));
        }
        $this->updatedData[$name] = $metadata->parseValue($this, $name, $value);
    }

    /**
     * Determine if an entity property is set and is not NULL.
     *
     * @param string $name The property name.
     * @return bool Returns TRUE if the property is set and is not NULL, FALSE otherwise.
     */
    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    /**
     * Magic method called by {@see var_dump()} when dumping
     * an object to get the properties that should be shown.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return $this->updatedData + $this->data;
    }
}
