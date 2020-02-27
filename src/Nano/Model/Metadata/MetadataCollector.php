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

use Nano\Model\Exception\InvalidEntityException;

/**
 * Collect all entity metadata in this class to avoid multiple parsing for the same entity.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class MetadataCollector
{
    /**
     * @var EntityMetadata[]
     */
    private static $entities = [];

    /**
     * Avoid class instantiation.
     */
    private function __construct() {}

    /**
     * Get metadata for the given entity.
     *
     * @param string $entityClass The entity class name.
     * @return EntityMetadata Returns the entity metadata.
     *
     * @throws InvalidEntityException for an invalid entity definition.
     */
    public static function get(string $entityClass): EntityMetadata
    {
        if (! isset(self::$entities[$entityClass])) {
            $metadata = new EntityMetadata($entityClass);
            self::$entities[$entityClass] = $metadata;

            $metadata->parseRelations();
        }
        return self::$entities[$entityClass];
    }
}