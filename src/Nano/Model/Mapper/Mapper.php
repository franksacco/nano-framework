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

namespace Nano\Model\Mapper;

use Nano\Model\Entity;
use Nano\Model\Metadata\EntityMetadata;
use Nano\Model\Metadata\Relation;

/**
 * Entity mapper.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Mapper
{
    /**
     * @var EntityMetadata[]
     */
    private $metadataList = [];

    /**
     * @var array
     */
    private $temporaryData = [];

    /**
     * @var array
     */
    private $associations = [];

    /**
     * Initialize the entity mapper.
     *
     * @param EntityMetadata $metadata The metadata of the main entity.
     * @param Relation[] $relationList The relations list of the main entity.
     */
    public function __construct(EntityMetadata $metadata, array $relationList)
    {
        $this->metadataList[] = $metadata;
        foreach ($relationList as $iteration => $relation) {
            $this->metadataList[$iteration] = $relation->getBindingEntity();
        }

        $this->temporaryData = array_fill(0, count($this->metadataList), []);
        $this->associations  = array_fill(1, count($this->metadataList) - 1, []);
    }

    /**
     * Map database result-set to entities.
     *
     * @param array $data The result-set from database.
     * @return Entity[] Returns a list of entities.
     */
    public function mapToEntities(array $data): array
    {
        $this->createTemporaryData($data);
        return $this->hydrateEntities();
    }

    /**
     * Iterate throw database result-set in order to collect associations and data for entities.
     *
     * @param array $data The result-set from database.
     */
    private function createTemporaryData(array $data)
    {
        foreach ($data as $row) {

            foreach ($this->metadataList as $iteration => $metadata) {
                $primaryKey = $row["{$metadata->getPrimaryKey()}_{$iteration}"];
                if ($primaryKey !== null) {

                    // Collect associations between entities.
                    $i = $iteration + 1;
                    foreach ($metadata->getRelations() as $relation) {
                        if ($relation->isEager()) {
                            $secondaryKey = $row[$relation->getBindingEntity()->getPrimaryKey() . '_' . $i];
                            if ($secondaryKey !== null) {
                                if (! isset($this->associations[$i][$primaryKey])) {
                                    $this->associations[$i][$primaryKey] = [$secondaryKey];
                                } else {
                                    $this->associations[$i][$primaryKey][] = $secondaryKey;
                                }
                            }
                            $i++;
                        }
                    }

                    // Collect data for entities.
                    if (! isset($this->temporaryData[$iteration][$primaryKey])) {
                        $temp = [];
                        foreach ($metadata->getColumns() as $column) {
                            $temp[$column] = $row["{$column}_{$iteration}"];
                        }
                        foreach ($metadata->getRelations() as $relation) {
                            if ($relation->isOneToOne() && $relation->isLazy()) {
                                $temp[$relation->getName()] = $row["{$relation->getForeignKey()}_{$iteration}"];
                            }
                        }
                        $this->temporaryData[$iteration][$primaryKey] = $temp;
                    }

                }
            }

        }
    }

    /**
     * Iterate throw the mapped data in order to create entities.
     *
     * @param int $iteration The iteration level.
     * @param array $primaryKeys The list of primary keys of associated entities.
     * @return Entity[] Returns the list of entities.
     */
    private function hydrateEntities(int $iteration = 0, array $primaryKeys = null): array
    {
        $result = [];

        $metadata = $this->metadataList[$iteration];
        foreach ($this->temporaryData[$iteration] as $primaryKey => $entityData) {

            if ($primaryKeys === null || in_array($primaryKey, $primaryKeys)) {
                $i = $iteration + 1;
                foreach ($metadata->getRelations() as $relation) {

                    if ($relation->isEager()) {
                        $associations    = $this->associations[$i][$primaryKey] ?? [];
                        $bindingEntities = $this->hydrateEntities($i, $associations);
                        if ($relation->isOneToOne()) {
                            $bindingEntities = reset($bindingEntities) ?: null;
                        }

                        $entityData[$relation->getName()] = $bindingEntities;
                        $i++;
                    }

                }
                $result[] = $this->hydrateEntity($metadata, $entityData);
            }

        }

        return $result;
    }

    /**
     * Create a new instance of an entity with given data.
     *
     * @param EntityMetadata $metadata The entity metadata.
     * @param array $data The data for entity creation.
     * @return Entity Returns the instantiated entity.
     */
    private function hydrateEntity(EntityMetadata $metadata, array $data): Entity
    {
        return $metadata->newInstance($data);
    }
}
