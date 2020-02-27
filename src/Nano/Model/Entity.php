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
use Nano\Database\Facade\Types;
use Nano\Database\Update;
use Nano\Model\Exception\InvalidEntityException;
use Nano\Model\Exception\InvalidValueException;
use Nano\Model\Exception\ModelExecutionException;
use Nano\Model\Exception\NotDefinedPropertyException;
use Nano\Model\Metadata\EntityMetadata;
use Nano\Model\Metadata\MetadataCollector;
use Nano\Model\Metadata\Relation;
use Nano\Model\QueryBuilder\ConnectionCollector;
use Nano\Model\QueryBuilder\CountBuilder;
use Nano\Model\QueryBuilder\EntitySaver;
use Nano\Model\QueryBuilder\LazyLoader;
use Nano\Model\QueryBuilder\SelectAllBuilder;
use Nano\Model\QueryBuilder\SelectOneBuilder;

/**
 * Implements functionalities for create, read, update and delete entities.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
abstract class Entity
{
    /**
     * Represents the BOOL data type.
     */
    public const TYPE_BOOL = 'bool';
    /**
     * Represents the DATE data type.
     */
    public const TYPE_DATE = 'date';
    /**
     * Represents the DATETIME data type.
     */
    public const TYPE_DATETIME = 'datetime';
    /**
     * Represents the FLOAT data type.
     */
    public const TYPE_FLOAT = 'float';
    /**
     * Represents a JSON encoded string.
     */
    public const TYPE_JSON = 'json';
    /**
     * Represents the INT data type.
     */
    public const TYPE_INT = 'int';
    /**
     * Represents the STRING data type.
     */
    public const TYPE_STRING = 'string';
    /**
     * Represents the TIME data type.
     */
    public const TYPE_TIME = 'time';

    /**
     * The table in the database that refers to this entity.
     *
     * @var string
     */
    public static $table = '';

    /**
     * The primary key column name; default: `'id'`.
     *
     * Only one primary key is supported.
     * Example of SQL definition:
     * <code>INT UNSIGNED PRIMARY KEY AUTO_INCREMENT</code>
     *
     * @var string
     */
    public static $primaryKey = 'id';

    /**
     * The column list definition; default: `[]`.
     *
     * Each item of the list MUST be in the form `$column => $type` where:
     *  - `$column` is the column name composed only by alphanumeric or
     *   underscore characters;
     *  - `$type` is the data type using the `Entity::TYPE_*` constants.
     *
     * In this list MUST NOT be inserted:
     *  - primary key column,
     *  - relation columns, used as reference to an another entity,
     *  - `'created'` and `'updated'` datetime columns, used for entity history,
     *  - `'deleted'` datetime column, used for soft deletion.
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
     * The relations definition; default: `[]`.
     *
     * Every item of this array represent a relation between current entity and
     * a specified binding entity.
     * Each relation SHOULD be identified uniquely by its index.
     *
     * A relation is represented by an array with the following items:
     *  - `name` is the name of the property associated to this relation;
     *  - `type` is the type if the relation using the `Relation::ONE_TO_ONE`,
     *   `Relation::ONE_TO_MANY` or `Relation::MANY_TO_MANY` constants;
     *  - `entity` is the class name of the binding entity;
     *  - `foreignKey` [optional] is the column name if the foreign key, by
     *   default, is the value of `name` option;
     *  - `bindingKey` [optional] is the column name of the binding key,
     *   default is `'id'`;
     *  - `junctionTable` [required for ManyToMany relations] is the name of
     *   the junction table.
     *  - `loading` [optional] is the type of loading using `Relation::EAGER`
     *   or `Relation::LAZY` constants; default is `Relation::EAGER` for
     *   OneToOne relations, `Relation::LAZY` otherwise.
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
     * Enable datetime functionality; default: `true`.
     *
     * For this feature to work, the `updated` and `created` columns of the
     * `DATETIME` type must be defined in the entity table.
     *
     * Example of SQL definition:
     * <code>
     * updated DATETIME NOT `null`
     * created DATETIME NOT `null`
     * </code>
     *
     * @var bool
     */
    public static $datetime = true;

    /**
     * Enable soft deletion functionality; default: `true`.
     *
     * For this feature to work, the `deleted` column of the `DATETIME` type
     * must be defined in the entity table.
     *
     * Example of SQL definition:
     * <code>deleted DATETIME DEFAULT `null`</code>
     *
     * @var bool
     */
    public static $softDeletion = true;

    /**
     * Disable automatic setters and entity saving; default: `false`.
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
     * The primary key value or `null` if the entity is not persisted.
     *
     * @var string|null
     */
    private $id = null;

    /**
     * The persisted data of the entity.
     *
     * @var array
     */
    private $data = [];

    /**
     * Updates to be committed in the next call of <code>save()</code> method.
     *
     * @var array
     */
    private $updatedData = [];

    /**
     * The list of booleans indicating the execution of properties' lazy loading.
     *
     * @var bool[]
     */
    private $lazyLoad = [];

    /**
     * Get a list of entities with optional filters and options.
     *
     * @return SelectAllBuilder Returns the helper for query building.
     */
    public static function all(): SelectAllBuilder
    {
        return new SelectAllBuilder(static::class);
    }

    /**
     * Get a single entity using the value of its primary key.
     *
     * @param string $primaryKey The primary key value.
     * @return static|null Returns an instance of searched entity or `null` if
     *   not found.
     *
     * @throws ModelExecutionException if an error occurs during the execution
     *   of the query.
     */
    public static function get(string $primaryKey): ?self
    {
        $retriever = new SelectOneBuilder(static::class, $primaryKey);
        return $retriever->execute();
    }

    /**
     * Get the number of entities that respect particular conditions.
     *
     * @return CountBuilder Returns the helper for query building.
     */
    public static function count(): CountBuilder
    {
        return new CountBuilder(static::class);
    }

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
     * Returns the primary key value or `null` if the entity is not persisted.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * This method can be overwritten in order to execute
     * some code before the entity creation.
     */
    protected function beforeCreation() {}

    /**
     * This method can be overwritten in order to execute
     * some code after the entity creation.
     */
    protected function afterCreation() {}

    /**
     * This method can be overwritten in order to execute
     * some code before the entity update.
     */
    protected function beforeUpdate() {}

    /**
     * This method can be overwritten in order to execute
     * some code after the entity update.
     */
    protected function afterUpdate() {}

    /**
     * Update or insert the entity in database.
     *
     * @throws ModelExecutionException if an error occurs during the execution
     *   of the query.
     */
    public function save()
    {
        $saver = new EntitySaver(
            $metadata = $this->getMetadata(),
            $this->data,
            $this->updatedData
        );

        $isNew = $this->isNew();
        if (!$isNew && !$this->isModified()) {
            return;
        }

        $isNew ?
            $this->beforeCreation() :
            $this->beforeUpdate();

        $query = $isNew ? $saver->createInsertQuery()
            : $saver->createUpdateQuery($this->id);
        $saver->execute($query);

        if ($isNew) {
            $newPrimaryKey = $saver->getNewPrimaryKey();
            $this->id      = $newPrimaryKey;
            $this->updatedData[$metadata->getPrimaryKey()] = $newPrimaryKey;
        }

        $this->data        = array_merge($this->data, $this->updatedData);
        $this->updatedData = [];

        $isNew ?
            $this->afterCreation() :
            $this->afterUpdate();
    }

    /**
     * This method can be overwritten in order to execute
     * some code before the entity deletion.
     */
    protected function beforeDeletion() {}

    /**
     * This method can be overwritten in order to execute
     * some code after the entity deletion.
     */
    protected function afterDeletion() {}

    /**
     * Delete the entity.
     *
     * @param bool $hardDelete [optional] Force hard deletion; default: `false`.
     *
     * @throws ModelExecutionException if this entity is not persisted in database.
     * @throws ModelExecutionException if an error occur during query execution.
     */
    public function delete(bool $hardDelete = false)
    {
        if ($this->isNew()) {
            throw new ModelExecutionException('You cannot delete a non-persisted entity');
        }

        $this->beforeDeletion();

        $metadata = $this->getMetadata();
        if (!$hardDelete && $metadata->hasSoftDeletion()) {
            $query = (new Update($metadata->getTable()))->addValue(
                EntityMetadata::COLUMN_DELETED,
                date('Y-m-d H:i:s'),
                Types::DATETIME
            );
        } else {
            $query = new Delete($metadata->getTable());
        }
        $query->where($metadata->getPrimaryKey(), '=', $this->id, Types::STRING);
        try {
            ConnectionCollector::getConnection()->executeUpdate($query);

        } catch (QueryExecutionException $exception) {
            throw new ModelExecutionException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        $this->afterDeletion();
    }

    /**
     * This method can be overwritten in order to execute
     * some code before the entity restoration.
     */
    protected function beforeRestore() {}

    /**
     * This method can be overwritten in order to execute
     * some code after the entity restoration.
     */
    protected function afterRestore() {}

    /**
     * Restore the entity from soft deletion.
     *
     * @throws ModelExecutionException if this entity is not soft deleted.
     * @throws ModelExecutionException if an error occur during query execution.
     */
    public function restore()
    {
        if (!$this->isDeleted()) {
            throw new ModelExecutionException('You cannot restore a non-deleted entity');
        }

        $this->beforeRestore();

        try {
            $query = (new Update($this->getMetadata()->getTable()))
                ->addValue(EntityMetadata::COLUMN_DELETED, null, Types::NULL)
                ->where($this->getMetadata()->getPrimaryKey(), '=', $this->id, Types::STRING);
            ConnectionCollector::getConnection()->executeUpdate($query);

        } catch (QueryExecutionException $exception) {
            throw new ModelExecutionException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        $this->afterRestore();
    }

    /**
     * Get the value of an entity property.
     *
     * Note that this method does not return-by-reference, so the value
     * provided is a copy of the actual value. In case of an array, the
     * operation `$entity->array[] = $new_item;` produces a warning
     * and doesn't work. You should retrieve the array, modify it and then
     * re-assign the value to the property:
     * ```
     * $array = $entity->array;
     * $array[] = $new_item;
     * $entity->array = $array;
     * ```
     *
     * @param string $name The name of the property.
     * @return mixed Returns the value of the property.
     *
     * @throws NotDefinedPropertyException if the property is not defined or is not set.
     * @throws ModelExecutionException if an error occur during query execution.
     */
    public function __get(string $name)
    {
        // Check for the execution of a lazy loading.
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
     * Set the value of an entity property.
     *
     * @param string $name The name of the property.
     * @param mixed $value The new value of the property.
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
     * Determine if an entity property is set and is not `null`.
     *
     * @param string $name The name of the property.
     * @return bool Returns `true` if the property is set and is not `null`, `false` otherwise.
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