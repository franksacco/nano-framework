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

use Nano\Auth\Exception\InvalidRoleException;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function testGetId()
    {
        $id = 'test';
        $role = new Role($id);

        $this->assertSame($id, $role->getId());
    }

    public function testPermission()
    {
        $role = new Role('test');
        $role->addPermission('something');

        $this->assertTrue($role->hasPermission('something'));
        $this->assertFalse($role->hasPermission('something_else'));

        $role->removePermission('something');

        $this->assertFalse($role->hasPermission('something'));
    }

    public function testParent()
    {
        $role = new Role('test');
        $parent = new Role('parent');
        $role->addParent($parent);

        $this->assertSame([$parent], $role->getParents());

        $role->removeParent($parent);

        $this->assertSame([], $role->getParents());
    }

    public function testParentPermission()
    {
        $role = new Role('test');
        $parent = new Role('parent');
        $parent->addPermission('something');
        $role->addParent($parent);

        $this->assertTrue($role->hasPermission('something'));
    }

    public function testInvalidRole()
    {
        $role = new Role('test');
        $invalid = $this->createMock(RoleInterface::class);

        $this->expectException(InvalidRoleException::class);
        $this->expectExceptionMessage('Parent role must be a Nano\Auth\Role instance');

        $role->addParent($invalid);
    }

    public function testRecursiveHierarchy()
    {
        $role = new Role('test');
        $parent1 = new Role('parent1');
        $parent2 = new Role('parent2');
        $role->addParent($parent1);
        $parent1->addParent($parent2);

        $this->expectException(InvalidRoleException::class);
        $this->expectExceptionMessage('Parent role creates a recursive hierarchy');

        $parent2->addParent($role);
    }
}