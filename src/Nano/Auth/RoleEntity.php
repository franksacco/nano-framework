<?php
/**
 * Nano Framework
 *
 * @package   Nano
 * @author    Francesco Saccani <saccani.francesco@gmail.com>
 * @copyright Copyright (c) 2020 Francesco Saccani
 * @version   1.0
 */

declare(strict_types=1);

namespace Nano\Auth;

use DateTimeImmutable;
use Nano\Auth\Exception\InvalidRoleException;
use Nano\Model\Entity;
use Nano\Model\EntityCollection;
use Nano\Model\Metadata\Relation;

/**
 * Implementation of a role based on database entity.
 *
 * Example of table definition in a MySQL database:
 * <code>
 * CREATE TABLE `roles` (
 *   `id` int(10) unsigned NOT `null` AUTO_INCREMENT,
 *   `name` tinytext NOT `null`,
 *   `permissions` text NOT `null`,
 *   `updated` datetime NOT `null` DEFAULT CURRENT_TIMESTAMP,
 *   `created` datetime NOT `null` DEFAULT CURRENT_TIMESTAMP,
 *   `deleted` datetime DEFAULT `null`,
 *   PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 * </code>
 * Example of table definition for roles hierarchy:
 * <code>
 * CREATE TABLE `roles_hierarchy` (
 *   `role_id` int(10) unsigned NOT `null`,
 *   `parent_id` int(10) unsigned NOT `null`,
 *   UNIQUE KEY `role_id` (`role_id`,`parent_id`),
 *   KEY `parent_id` (`parent_id`),
 *   CONSTRAINT `role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
 *   CONSTRAINT `parent_id` FOREIGN KEY (`parent_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 * </code>
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 *
 * @property string $id The unique identifier of the role.
 * @property string $name The name of the role.
 * @property string[] $permissions The list of permission granted to this role.
 * @property RoleEntity[]|EntityCollection $parents The list of parent roles.
 * @property DateTimeImmutable $updated The datetime of the last update.
 * @property DateTimeImmutable $created The datetime of the creation.
 * @property DateTimeImmutable|null $deleted The datetime of the deletion.
 */
class RoleEntity extends Entity implements RoleInterface
{
    public static $table = 'roles';

    public static $columns = [
        'name'        => Entity::TYPE_STRING,
        'permissions' => Entity::TYPE_JSON
    ];

    public static $relations = [
        'role_hierarchy' => [
            'name'          => 'parents',
            'type'          => Relation::MANY_TO_MANY,
            'entity'        => self::class,
            'foreignKey'    => 'role_id',
            'bindingKey'    => 'parent_id',
            'junctionTable' => 'roles_hierarchy',
            // To prevent infinite loops during query building.
            'loading'       => Relation::LAZY
        ]
    ];

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return parent::getId() ?? '';
    }

    /**
     * @inheritDoc
     */
    public function hasPermission(string $permission): bool
    {
        if (in_array(strtolower($permission), $this->__get('permissions'))) {
            return true;
        }

        foreach ($this->__get('parents')->toArray() as $parent) {
            if ($parent->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a permission to the role.
     *
     * You need to call {@see RoleEntity::save()} for changes to take effect.
     *
     * @param string $name The name of the permission.
     */
    public function addPermission(string $name)
    {
        $name = strtolower($name);
        $permissions = $this->__get('permissions');

        if (! in_array($name, $permissions)) {
            $permissions[] = $name;
            $this->__set('permissions', $permissions);
        }
    }

    /**
     * Remove a permission from the role.
     *
     * You need to call {@see RoleEntity::save()} for changes to take effect.
     *
     * @param string $name The name of the permission.
     */
    public function removePermission(string $name)
    {
        $name = strtolower($name);
        $permissions = $this->__get('permissions');
        $key = array_search($name, $permissions);

        if ($name !== false) {
            unset($permissions[$key]);
            $this->__set('permissions', $permissions);
        }
    }

    /**
     * Check if the given role is a parent of this role.
     *
     * @param RoleEntity $role
     * @return bool
     */
    protected function hasParent(RoleEntity $role): bool
    {
        $parents = $this->__get('parents')->toArray();

        foreach ($parents as $parent) {
            if ($parent->getId() == $role->getId()) {
                return true;
            }
        }
        foreach ($parents as $parent) {
            if ($parent->hasParent($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidRoleException if role is not a {@see RoleEntity}.
     * @throws InvalidRoleException if the role creates a recursive hierarchy.
     */
    public function addParent(RoleInterface $parent)
    {
        if (! $parent instanceof RoleEntity) {
            throw new InvalidRoleException(sprintf(
                "Parent role must be a %s instance",
                RoleEntity::class
            ));
        }

        if ($parent->hasParent($this)) {
            throw new InvalidRoleException('Parent role creates a recursive hierarchy');
        }

        $this->__get('parents')->add($parent);
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidRoleException if role is not a {@see RoleEntity}.
     */
    public function removeParent(RoleInterface $parent)
    {
        if (! $parent instanceof RoleEntity) {
            throw new InvalidRoleException(sprintf(
                "Parent role must be a %s instance",
                RoleEntity::class
            ));
        }

        $this->__get('parents')->remove($parent);
    }

    /**
     * @inheritDoc
     */
    public function getParents(): array
    {
        return $this->__get('parents')->toArray();
    }
}