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

use Nano\Database\Exception\InvalidArgumentException;
use Nano\Database\Exception\QueryExecutionException;
use Nano\Database\Facade\Types;
use Nano\Database\Facade\UpdateQueryInterface;
use Nano\Database\Insert;
use Nano\Database\Update;
use Nano\Model\Entity;
use Nano\Model\Exception\InvalidValueException;
use Nano\Model\Exception\ModelExecutionException;
use Nano\Model\Metadata\EntityMetadata;

/**
 * Helper class for insert or update an entity and associated relations.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class EntitySaver
{
    /**
     * @var EntityMetadata
     */
    private $metadata;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $updatedData;

    /**
     * Initialize the entity saver.
     *
     * @param EntityMetadata $metadata The entity metadata.
     * @param array $data The associative array of entity data.
     * @param array $updatedData The associative of entity updated data.
     */
    public function __construct(EntityMetadata $metadata, array $data, array $updatedData)
    {
        $this->metadata    = $metadata;
        $this->data        = array_merge($data, $updatedData);
        $this->updatedData = $updatedData;
    }

    /**
     * Convert data from complex format to a database-compatible format.
     *
     * @param array $data The array of complex data.
     * @return array Returns the data in the database-compatible format:
     *   `column` => [`value`, `type`].
     */
    private function convertData(array $data): array
    {
        // Convert relations.
        foreach ($this->metadata->getRelations() as $relation) {

            if (array_key_exists($relation->getName(), $data)) {
                $value = $data[$relation->getName()];
                unset($data[$relation->getName()]);

                if ($value === null) {
                    $data[$relation->getForeignKey()] = [null, Types::NULL];

                } elseif ($relation->isOneToOne()) {
                    /** @var Entity $value */
                    if ($value->isNew()) {
                        $value->save();
                    }
                    $data[$relation->getForeignKey()] = [
                        $value->__get($relation->getBindingEntity()->getPrimaryKey()),
                        Types::STRING
                    ];
                }
            }
        }

        // Convert native data types.
        foreach ($this->metadata->getColumns() as $column) {
            if (! isset($data[$column])) {
                continue;
            }

            $value = $data[$column];
            switch ($type = $this->metadata->getPropertyType($column)) {
                case Entity::TYPE_BOOL:
                    $data[$column] = [$value, Types::BOOL];
                    break;

                case Entity::TYPE_FLOAT:
                    $data[$column] = [$value, Types::FLOAT];
                    break;
                case Entity::TYPE_INT:
                    $data[$column] = [$value, Types::INT];
                    break;

                case Entity::TYPE_DATE:
                case Entity::TYPE_DATETIME:
                case Entity::TYPE_TIME:
                    /** @var \DateTimeImmutable $value */
                    $format = $type === Entity::TYPE_DATE ? EntityMetadata::DATE_FORMAT :
                        ($type === Entity::TYPE_TIME ? EntityMetadata::TIME_FORMAT :
                            EntityMetadata::DATETIME_FORMAT);
                    $data[$column] = [$value->format($format), Types::DATETIME];
                    break;

                case Entity::TYPE_JSON:
                    $data[$column] = [json_encode($value), Types::JSON];
                    break;

                case Entity::TYPE_STRING:
                default:
                    $data[$column] = [$value, Types::STRING];
            }
        }

        return $data;
    }

    /**
     * Create the query to insert new entity in database.
     *
     * @return Insert Returns the insert query instance.
     *
     * @throws InvalidValueException if the data is not valid.
     */
    public function createInsertQuery(): Insert
    {
        $data = $this->convertData($this->data);

        if ($this->metadata->hasDatetime()) {
            $data[EntityMetadata::COLUMN_CREATED] = date('Y-m-d H:i:s');
            $data[EntityMetadata::COLUMN_UPDATED] = date('Y-m-d H:i:s');
        }

        try {
            return (new Insert($this->metadata->getTable()))
                ->addValues($data);

        } catch (InvalidArgumentException $exception) {
            throw new InvalidValueException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Create the query to update entity in database.
     *
     * @param string $id The primary key of the entity to update.
     * @return Update Returns the update query instance.
     *
     * @throws InvalidValueException if the data is not valid.
     */
    public function createUpdateQuery(string $id): Update
    {
        $data = $this->convertData($this->updatedData);

        if ($this->metadata->hasDatetime()) {
            $data[EntityMetadata::COLUMN_UPDATED] = date('Y-m-d H:i:s');
        }

        try {
            return (new Update($this->metadata->getTable()))
                ->addValues($data)
                ->where($this->metadata->getPrimaryKey(), '=', $id);

        } catch (InvalidArgumentException $exception) {
            throw new InvalidValueException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Execute the query.
     *
     * @param UpdateQueryInterface $query The query to be executed.
     *
     * @throws ModelExecutionException if an error occur during query execution.
     */
    public function execute(UpdateQueryInterface $query)
    {
        $connection = ConnectionCollector::getConnection();
        try {
            $connection->executeUpdate($query);

        } catch (QueryExecutionException $exception) {
            throw new ModelExecutionException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Retrieve the last inserted id from database.
     *
     * @return string
     */
    public function getNewPrimaryKey(): string
    {
        return ConnectionCollector::getConnection()->getLastInsertId();
    }
}
