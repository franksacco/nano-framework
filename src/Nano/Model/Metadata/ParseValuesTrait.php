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

use DateTimeImmutable;
use Nano\Model\Entity;
use Nano\Model\EntityCollection;
use Nano\Model\Exception\InvalidValueException;
use Nano\Model\Exception\NotDefinedPropertyException;

/**
 * Trait implementing logic to parse a property value.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
trait ParseValuesTrait
{
    /**
     * Parse and cast a value for an entity property.
     *
     * @param Entity $entity The entity which the property is
     *   associated.
     * @param string $property The property name.
     * @param mixed $value The value.
     * @return mixed Returns the parsed value for the property.
     *
     * @throws NotDefinedPropertyException if the property is not defined.
     * @throws InvalidValueException for an invalid value.
     */
    public function parseValue(Entity $entity, string $property, $value)
    {
        if ($value === null) {
            return null;
        }

        $type = $this->getPropertyType($property);
        if ($type instanceof Relation) {
            return $this->parseValueOfRelation($entity, $type, $value);
        }

        switch ($type) {
            case Entity::TYPE_BOOL:
                return (bool) $value;

            case Entity::TYPE_DATE:
            case Entity::TYPE_DATETIME:
            case Entity::TYPE_TIME:
                if ($value instanceof DateTimeImmutable) {
                    return $value;
                }
                $result = DateTimeImmutable::createFromFormat('!' .
                    $type === Entity::TYPE_DATE ? self::DATE_FORMAT :
                        ($type === Entity::TYPE_TIME ? self::TIME_FORMAT : self::DATETIME_FORMAT),
                    (string) $value
                );
                if ($result === false) {
                    throw new InvalidValueException(sprintf(
                        'Invalid %s value for property "%s" of entity "%s"',
                        $type, $property, static::class
                    ));
                }
                return $result;

            case Entity::TYPE_FLOAT:
                return (float) $value;

            case Entity::TYPE_INT:
                return (int) $value;

            case Entity::TYPE_JSON:
                if (is_string($value)) {
                    $result = json_decode($value);
                } else {
                    $result = json_encode($value) === false ? null : $value;
                }
                if ($result === null) {
                    throw new InvalidValueException(sprintf(
                        'Invalid json value for property "%s" of entity "%s": %s',
                        $property, static::class, json_last_error_msg()
                    ));
                }
                return $result;

            case Entity::TYPE_STRING:
            default:
                return (string) $value;
        }
    }

    /**
     * Parse the value for a relation property.
     *
     * @param Entity $entity The entity which the relation is
     *   associated.
     * @param Relation $relation The relation.
     * @param mixed $value The value.
     * @return mixed
     *
     * @throws InvalidValueException for an invalid value.
     */
    private function parseValueOfRelation(Entity $entity, Relation $relation, $value)
    {
        if ($relation->isOneToOne()) {
            $bindingEntity = $relation->getBindingEntity()->newInstance();

            if ($relation->isLazy() && is_numeric($value)) {
                $value = (int) $value;

            } elseif (! $value instanceof $bindingEntity) {
                throw new InvalidValueException(sprintf(
                    'Invalid value for a OneToOne relation: "%s" object expected, "%s" given',
                    get_class($bindingEntity),
                    gettype($value)
                ));
            }

        } else {
            // relation is OneToMany or ManyToMany
            if (! is_array($value)) {
                throw new InvalidValueException(sprintf(
                    'Invalid value for a *ToMany relation: array expected, "%s" given',
                    gettype($value)
                ));
            }

            $value = new EntityCollection($entity, $relation, $value);
        }

        return $value;
    }
}
