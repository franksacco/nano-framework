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

/**
 * Interface for a role in a RBAC system.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
interface RoleInterface
{
    /**
     * Get the identifier of the role.
     *
     * @return string Returns the identifier of the role.
     */
    public function getId(): string;

    /**
     * Check if the given permission is granted for this role.
     *
     * A permission is granted if it is associated to this role or to any of
     * its parent roles.
     *
     * @param string $permission The name of the permission.
     * @return bool Returns TRUE if the permission is granted, FALSE otherwise.
     */
    public function hasPermission(string $permission): bool;

    /**
     * Add a permission to the role.
     *
     * @param string $name The name of the permission.
     */
    public function addPermission(string $name);

    /**
     * Remove a permission from the role.
     *
     * @param string $name The name of the permission.
     */
    public function removePermission(string $name);

    /**
     * Get the list of parent roles.
     *
     * @return RoleInterface[] Returns the list of parent roles.
     */
    public function getParents(): array;

    /**
     * Add a parent role.
     *
     * @param RoleInterface $parent The parent role to add.
     */
    public function addParent(RoleInterface $parent);

    /**
     * Remove a parent role.
     *
     * @param RoleInterface $parent The parent role to remove.
     */
    public function removeParent(RoleInterface $parent);
}