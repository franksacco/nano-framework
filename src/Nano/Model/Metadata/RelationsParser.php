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
use ReflectionClass;
use ReflectionException;

/**
 * Implements logic for parsing relations.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class RelationsParser
{
    /**
     * Available relation type.
     */
    private const AVAILABLE_RELATION_TYPE = [
        Relation::ONE_TO_ONE,
        Relation::ONE_TO_MANY,
        Relation::MANY_TO_MANY
    ];

    /**
     * Available relation loading types.
     */
    private const AVAILABLE_LOADING_TYPE = [
        Relation::EAGER,
        Relation::LAZY
    ];

    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * @var array
     */
    private $relations;

    /**
     * @var string[]
     */
    private static $processedNames = [];

    /**
     * @param ReflectionClass $reflection
     *
     * @throws InvalidEntityException for an invalid relations definition.
     */
    public function __construct(ReflectionClass $reflection)
    {
        $this->reflection = $reflection;
        $this->relations  = $reflection->getStaticPropertyValue('relations');

        if (! is_array($this->relations)) {
            throw InvalidEntityException::forNonArrayRelationList(
                $this->relations,
                $reflection->getName()
            );
        }
    }

    /**
     * Parse relations definition.
     *
     * Each item of output array has following information/keys:
     *  - 'name': property name associated to this relation;
     *  - 'type': type of relation (Relation::ONE_TO_ONE, Relation::ONE_TO_MANY
     *   or Relation::MANY_TO_MANY);
     *  - 'loading': type of loading (Relation::EAGER or Relation::LAZY);
     *  - 'entity': binding entity reference;
     *  - 'foreignKey': foreign key column name;
     *  - 'bindingKey': referenced column name;
     *  - 'junctionTable': junction table used in ManyToMany relations.
     *
     * @return array Returns the list of options for each relation.
     *
     * @throws InvalidEntityException for an invalid relation definition.
     */
    public function parse(): array
    {
        $result = [];

        foreach ($this->relations as $name => $relation) {
            $processed = [];

            $name = (string) $name;
            if (!is_array($relation) || !isset($relation['name']) || !isset($relation['entity'])) {
                throw InvalidEntityException::forInvalidRelationDefinition(
                    $name, $this->reflection->getName()
                );
            }
            $processed['name'] = (string) $relation['name'];

            $processed['type'] = $relation['type'] ?? Relation::ONE_TO_ONE;
            if (! in_array($processed['type'], self::AVAILABLE_RELATION_TYPE)) {
                throw InvalidEntityException::forInvalidRelationType(
                    $name, $this->reflection->getName()
                );
            }

            $processed['loading'] = $relation['loading'] ??
                ($processed['type'] === Relation::ONE_TO_ONE ? Relation::EAGER : Relation::LAZY);
            if (! in_array($processed['loading'], self::AVAILABLE_LOADING_TYPE)) {
                throw InvalidEntityException::forInvalidLoadingType(
                    $name, $this->reflection->getName()
                );
            }

            if ($processed['loading'] === Relation::EAGER) {
                $this->checkInfiniteLoop($name);
            }

            $this->checkEntityClass((string) $relation['entity']);
            $processed['entity'] = $relation['entity'];

            $processed['foreignKey'] = (string) ($relation['foreignKey'] ?? $processed['name']);
            $processed['bindingKey'] = (string) ($relation['bindingKey'] ?? 'id');

            if ($processed['type'] === Relation::MANY_TO_MANY) {
                if (! isset($relation['junctionTable'])) {
                    throw InvalidEntityException::forMissingJunctionTable(
                        $name, $this->reflection->getName()
                    );
                }
                $processed['junctionTable'] = (string) $relation['junctionTable'];
            }

            $result[ $processed['name'] ] = new Relation($processed);
        }

        return $result;
    }

    /**
     * Check for an infinite loop of eager relations.
     *
     * @param string $name The relation name.
     *
     * @throws InvalidEntityException if an infinite loop is identified.
     */
    private function checkInfiniteLoop(string $name)
    {
        if (in_array($name, static::$processedNames)) {
            throw InvalidEntityException::forInfiniteLoop(
                $this->reflection->getName()
            );
        }
        static::$processedNames[] = $name;
    }

    /**
     * Check for a valid entity class name and get a reference of it.
     *
     * @param string $class The class name to analyze.
     *
     * @throws InvalidEntityException for an invalid entity class name.
     */
    private function checkEntityClass(string $class)
    {
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw InvalidEntityException::forNonExistingClass($class);
        }
        if (! $reflection->isSubclassOf(Entity::class)) {
            throw InvalidEntityException::forNoEntityInheritance($class);
        }
    }

    /**
     * Check reverse definition for a OneToMany relation.
     *
     * @param Relation $relation The OneToMany relation.
     *
     * @throws InvalidEntityException for a missing reverse definition.
     */
    public function checkReverseRelation(Relation $relation)
    {
        if ($relation->getReverseRelation($this->reflection->getName()) === null) {
            throw InvalidEntityException::forMissingDoubleSidedRelation(
                $relation->getName(),
                $this->reflection->getName()
            );
        }
    }
}
