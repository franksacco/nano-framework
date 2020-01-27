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

use Nano\Auth\Exception\InvalidRoleException;

/**
 * Basic implementation of a role.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Role implements RoleInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * The list of parent roles.
     *
     * @var Role[]
     */
    protected $parents = [];

    /**
     * The list of permissions granted to this role.
     *
     * @var string[]
     */
    protected $permissions = [];

    /**
     * Initialize the role.
     *
     * @param string $id The name or identifier of the role.
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function hasPermission(string $permission): bool
    {
        if (in_array(strtolower($permission), $this->permissions)) {
            return true;
        }

        foreach ($this->parents as $parent) {
            if ($parent->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function addPermission(string $name)
    {
        $name = strtolower($name);
        if (! in_array($name, $this->permissions)) {
            $this->permissions[] = $name;
        }
    }

    /**
     * @inheritDoc
     */
    public function removePermission(string $name)
    {
        $name = strtolower($name);
        $key = array_search($name, $this->permissions);
        if ($name !== false) {
             unset($this->permissions[$key]);
        }
    }

    /**
     * Check if the given role is a parent of this role.
     *
     * @param Role $role
     * @return bool
     */
    protected function hasParent(Role $role): bool
    {
        if (isset($this->parents[$role->getId()])) {
            return true;
        }

        foreach ($this->parents as $parent) {
            if ($parent->hasParent($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidRoleException if role is not a {@see Role}.
     * @throws InvalidRoleException if the role creates a recursive hierarchy.
     */
    public function addParent(RoleInterface $parent)
    {
        if (! $parent instanceof Role) {
            throw new InvalidRoleException(sprintf(
                'Parent role must be a %s instance',
                Role::class
            ));
        }

        if ($parent->hasParent($this)) {
            throw new InvalidRoleException('Parent role creates a recursive hierarchy');
        }

        $parentId = $parent->getId();
        if (! isset($this->parents[$parentId])) {
            $this->parents[$parentId] = $parent;
        }
    }

    /**
     * @inheritDoc
     */
    public function removeParent(RoleInterface $parent)
    {
        $parentId = $parent->getId();
        if (isset($this->parents[$parentId])) {
            unset($this->parents[$parentId]);
        }
    }

    /**
     * @inheritDoc
     */
    public function getParents(): array
    {
        return array_values($this->parents);
    }
}
