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

use Nano\Database\Exception\QueryExecutionException;
use Nano\Database\Select;
use Nano\Model\Exception\InvalidEntityException;
use Nano\Model\Exception\ModelExecutionException;
use Nano\Model\Mapper\Mapper;
use Nano\Model\Metadata\EntityMetadata;
use Nano\Model\Metadata\MetadataCollector;
use Nano\Model\Metadata\Relation;

/**
 * Abstract Query Builder.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
abstract class QueryBuilder
{
    /**
     * @var EntityMetadata
     */
    protected $metadata;

    /**
     * @var Select
     */
    protected $query;

    /**
     * @var int
     */
    private $iteration = 0;

    /**
     * @var Relation[]
     */
    private $relationList = [];

    /**
     * @var bool
     */
    private $showDeleted = false;

    /**
     * Initialize the QueryBuilder.
     *
     * @param string $entityClass The class name of the starting entity to be retrieved.
     *
     * @throws InvalidEntityException for an invalid entity definition.
     */
    public function __construct(string $entityClass)
    {
        $this->metadata = MetadataCollector::get($entityClass);
        $this->query    = $this->initializeQuery();
    }

    /**
     * Create the base query to execute.
     *
     * @return Select Returns the query to execute.
     */
    protected function initializeQuery(): Select
    {
        $query = new Select($this->metadata->getTable(), 't_' . $this->iteration);

        $columns = [];
        foreach ($this->metadata->getColumns() as $column) {
            $columns["{$column}_{$this->iteration}"] = "t_{$this->iteration}.{$column}";
        }
        $query->select($columns);

        return $this->createRelations($query, $this->metadata);
    }

    /**
     * Add joins and columns to the query recursively for each relation.
     *
     * @param Select $query The query object that has to be created.
     * @param EntityMetadata $metadata The current entity reference.
     * @return Select Returns the query to execute.
     */
    protected function createRelations(Select $query, EntityMetadata $metadata): Select
    {
        $parentIteration = $this->iteration;
        foreach ($metadata->getRelations() as $relation) {

            if ($relation->isLazy()) {
                if ($relation->isOneToOne()) {
                    $query->select(
                        "t_{$this->iteration}.{$relation->getForeignKey()}",
                        "{$relation->getForeignKey()}_{$this->iteration}"
                    );
                }
                continue;
            }
            $this->relationList[++$this->iteration] = $relation;

            $bindingEntity = $relation->getBindingEntity();
            // Not show soft deleted entities.
            if ($bindingEntity->hasSoftDeletion()) {
                $query->where(
                    "t_{$this->iteration}." . EntityMetadata::COLUMN_DELETED,
                    'IS',
                    null
                );
            }
            // Add the proper join clause to the query.
            if ($relation->isOneToOne()) {
                $query->join(
                    $bindingEntity->getTable(),
                    "t_{$this->iteration}",
                    "t_{$parentIteration}.{$relation->getForeignKey()}",
                    $relation->getBindingKey()
                );

            } else if ($relation->isOneToMany()) {
                $query->join(
                    $bindingEntity->getTable(),
                    "t_{$this->iteration}",
                    "t_{$parentIteration}.{$relation->getForeignKey()}",
                    $relation->getBindingKey()
                );

            } else {
                // $relation is ManyToMany.
                $query->join(
                    $relation->getJunctionTable(),
                    "t_j_{$this->iteration}",
                    "t_{$parentIteration}.{$metadata->getPrimaryKey()}",
                    $relation->getForeignKey()
                );
                $query->join(
                    $bindingEntity->getTable(),
                    "t_{$this->iteration}",
                    "t_j_{$this->iteration}.{$relation->getBindingKey()}",
                    $bindingEntity->getPrimaryKey()
                );
            }

            $columns = [];
            foreach ($bindingEntity->getColumns() as $column) {
                $columns["{$column}_{$this->iteration}"] = "t_{$this->iteration}.{$column}";
            }
            $query->select($columns);

            if ($bindingEntity->hasRelationsForQueryBuilding()) {
                // Recursion call for relations that has to be considered
                // during query building.
                $query = $this->createRelations($query, $bindingEntity);
            }
        }

        return $query;
    }

    /**
     * Add soft deleted entities in the result.
     *
     * @param bool $show [optional] Whether to show soft deleted entities in
     *     the result; default: TRUE.
     * @return static Returns self reference for method chaining.
     */
    public function showDeleted(bool $show = true): self
    {
        $this->showDeleted = $show;
        return $this;
    }

    /**
     * Execute the query and return the result.
     *
     * @return array Returns the reference to the result set.
     *
     * @throws ModelExecutionException if an error occur during query execution.
     */
    protected function executeQuery(): array
    {
        if ($this->metadata->hasSoftDeletion() && !$this->showDeleted) {
            $this->query->where('t_0.' . EntityMetadata::COLUMN_DELETED, 'IS', null);
        }

        $connection = ConnectionCollector::getConnection();
        try {
            return $connection->executeSelect($this->query);

        } catch (QueryExecutionException $exception) {
            throw new ModelExecutionException(
                sprintf('Error during entities retrieving: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Perform mapping from database result to entities.
     *
     * @param array $result The reference to the result set.
     * @return array Returns the list of mapped entities.
     */
    protected function resultToEntities(array $result): array
    {
        return (new Mapper($this->metadata, $this->relationList))
            ->mapToEntities($result);
    }
}