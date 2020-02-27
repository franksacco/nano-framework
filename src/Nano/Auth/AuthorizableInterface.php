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

namespace Nano\Auth;

/**
 * Interface implemented by entities that can be authorized.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
interface AuthorizableInterface
{
    /**
     * Determine if the given permission is granted for this entity.
     *
     * @param string $permission The name of the permission to check.
     * @return bool Returns `true` if the permission is granted, `false` otherwise.
     */
    public function can(string $permission): bool;

    /**
     * Add a role to this entity.
     *
     * @param RoleInterface $role The role to add.
     */
    public function addRole(RoleInterface $role);

    /**
     * Remove a role from this entity.
     *
     * @param RoleInterface $role The role to remove.
     */
    public function removeRole(RoleInterface $role);

    /**
     * Get the list of roles assigned to this entity.
     *
     * @return RoleInterface[] Returns the list of roles.
     */
    public function getRoles(): array;
}