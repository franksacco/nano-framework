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

namespace Nano\Model\Exception;

use Nano\Model\Entity;
use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown when an invalid property of an entity is found.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class InvalidEntityException extends \UnexpectedValueException implements NanoExceptionInterface
{
    /**
     * @param string $entityClass
     * @return InvalidEntityException
     */
    public static function forNonExistingClass(string $entityClass): self
    {
        return new self(sprintf(
            'Entity class "%s" does not exist',
            $entityClass
        ));
    }

    /**
     * @param string $entityClass
     * @return InvalidEntityException
     */
    public static function forNoEntityInheritance(string $entityClass): self
    {
        return new self(sprintf(
            'Entity class "%s" must extends "%s" class',
            $entityClass,
            Entity::class
        ));
    }

    /**
     * @param mixed $columns
     * @param string $entityClass
     * @return InvalidEntityException
     */
    public static function forNonArrayColumnList($columns, string $entityClass): self
    {
        return new static(sprintf(
            'Column definition list must be an array, "%s" given in "%s" entity',
            gettype($columns),
            $entityClass
        ));
    }

    /**
     * @param string $type
     * @param string $column
     * @param string $entityClass
     * @return InvalidEntityException
     */
    public static function forInvalidColumnType($type, $column, string $entityClass): self
    {
        return new static(sprintf(
            'Invalid type "%s" for column "%s" in "%s" entity',
            (string) $type,
            (string) $column,
            $entityClass
        ));
    }

    /**
     * @param mixed $relations
     * @param string $entityClass
     * @return InvalidEntityException
     */
    public static function forNonArrayRelationList($relations, string $entityClass): self
    {
        return new static(sprintf(
            'Relation definition list must be an array, "%s" given in "%s" entity',
            gettype($relations),
            $entityClass
        ));
    }

    /**
     * @param string $relation
     * @param string $entityClass
     * @return InvalidEntityException
     */
    public static function forInvalidRelationDefinition(string $relation, string $entityClass): self
    {
        return new static(sprintf(
            'Invalid definition for "%s" relation defined in "%s" entity',
            $relation,
            $entityClass
        ));
    }

    /**
     * @param string $entityClass
     * @return InvalidEntityException
     */
    public static function forInfiniteLoop(string $entityClass): self
    {
        return new static(sprintf(
            'Infinite loop caused by relations definition detected in entity "%s"',
            $entityClass
        ));
    }

    /**
     * @param string $relation
     * @param string $entityClass
     * @return InvalidEntityException
     */
    public static function forInvalidLoadingType(string $relation, string $entityClass): self
    {
        return new static(sprintf(
            'Invalid relation loading type for "%s" relation defined in "%s" entity',
            $relation,
            $entityClass
        ));
    }

    /**
     * @param string $relation
     * @param string $entityClass
     * @return InvalidEntityException
     */
    public static function forInvalidRelationType(string $relation, string $entityClass): self
    {
        return new static(sprintf(
            'Invalid relation type for "%s" relation defined in "%s" entity',
            $relation,
            $entityClass
        ));
    }

    /**
     * @param string $relation
     * @param string $entityClass
     * @return InvalidEntityException
     */
    public static function forMissingJunctionTable(string $relation, string $entityClass): self
    {
        return new static(sprintf(
            'Missing junction table for ManyToMany relation "%s" in "%s" entity',
            $relation,
            $entityClass
        ));
    }

    /**
     * @param string $relation
     * @param string $entityClass
     * @return InvalidEntityException
     */
    public static function forMissingDoubleSidedRelation(string $relation, string $entityClass): self
    {
        return new static(sprintf(
            'Missing double sided definition for OneToMany relation "%s" in "%s" entity',
            $relation,
            $entityClass
        ));
    }
}
