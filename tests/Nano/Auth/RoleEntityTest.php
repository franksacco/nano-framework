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
use Nano\Model\EntityCollection;
use PHPUnit\Framework\TestCase;

class RoleEntityTest extends TestCase
{
    public function testGetId()
    {
        $id = 'test';
        $role = new RoleEntity([
            'id' => $id
        ]);

        $this->assertSame($id, $role->getId());
    }

    public function testPermission()
    {
        $parentsCollector = $this->createMock(EntityCollection::class);
        $parentsCollector->expects($this->exactly(2))
            ->method('toArray')
            ->willReturn([]);

        $role = $this->getMockBuilder(RoleEntity::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['addPermission', 'hasPermission', 'removePermission'])
            ->getMock();
        $role->expects($this->exactly(7))
            ->method('__get')
            ->withConsecutive(
                ['permissions'],
                ['permissions'],
                ['permissions'],
                ['parents'],
                ['permissions'],
                ['permissions'],
                ['parents']
            )
            ->willReturnOnConsecutiveCalls(
                [],
                ['something'],
                ['something'],
                $parentsCollector,
                ['something'],
                [],
                $parentsCollector
            );
        $role->expects($this->exactly(2))
            ->method('__set')
            ->withConsecutive(
                ['permissions', ['something']],
                ['permissions', []]
            );

        $role->addPermission('something');

        $this->assertTrue($role->hasPermission('something'));
        $this->assertFalse($role->hasPermission('something_else'));

        $role->removePermission('something');

        $this->assertFalse($role->hasPermission('something'));
    }

    public function testParent()
    {
        $parent = new RoleEntity([
            'name' => 'parent'
        ]);

        $parentsCollector = $this->createMock(EntityCollection::class);
        $parentsCollector->expects($this->once())
            ->method('add')
            ->with($parent);
        $parentsCollector->expects($this->once())
            ->method('remove')
            ->with($parent);
        $parentsCollector->expects($this->exactly(2))
            ->method('toArray')
            ->willReturnOnConsecutiveCalls([$parent], []);

        $role = $this->getMockBuilder(RoleEntity::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['addParent', 'removeParent', 'getParents'])
            ->getMock();
        $role->expects($this->exactly(4))
            ->method('__get')
            ->with('parents')
            ->willReturn($parentsCollector);

        $role->addParent($parent);
        $this->assertSame([$parent], $role->getParents());

        $role->removeParent($parent);
        $this->assertSame([], $role->getParents());
    }

    public function testParentPermission()
    {
        $parent = new RoleEntity([
            'permissions' => '[]'
        ]);
        $parent->addPermission('something');

        $parentsCollector = $this->createMock(EntityCollection::class);
        $parentsCollector->expects($this->once())
            ->method('add')
            ->with($parent);
        $parentsCollector->expects($this->once())
            ->method('toArray')
            ->willReturn([$parent]);

        $role = $this->getMockBuilder(RoleEntity::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['addParent', 'hasPermission'])
            ->getMock();
        $role->expects($this->exactly(3))
            ->method('__get')
            ->withConsecutive(
                ['parents'],
                ['permissions'],
                ['parents']
            )
            ->willReturnOnConsecutiveCalls(
                $parentsCollector,
                [],
                $parentsCollector
            );

        $role->addParent($parent);

        $this->assertTrue($role->hasPermission('something'));
    }

    public function testInvalidRoleInAddParent()
    {
        $role = $this->getMockBuilder(RoleEntity::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['addParent'])
            ->getMock();
        $invalid = $this->createMock(RoleInterface::class);

        $this->expectException(InvalidRoleException::class);
        $this->expectExceptionMessage('Parent role must be a Nano\Auth\RoleEntity instance');

        $role->addParent($invalid);
    }

    public function testInvalidRoleInRemoveParent()
    {
        $role = $this->getMockBuilder(RoleEntity::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['removeParent'])
            ->getMock();
        $invalid = $this->createMock(RoleInterface::class);

        $this->expectException(InvalidRoleException::class);
        $this->expectExceptionMessage('Parent role must be a Nano\Auth\RoleEntity instance');

        $role->removeParent($invalid);
    }

    public function testRecursiveHierarchy()
    {
        $role = $this->getMockBuilder(RoleEntity::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['hasParent', 'addParent'])
            ->getMock();
        $parent1 = $this->getMockBuilder(RoleEntity::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['hasParent', 'addParent'])
            ->getMock();
        $parent2 = $this->getMockBuilder(RoleEntity::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['hasParent', 'addParent'])
            ->getMock();

        $parentsCollector2 = $this->createMock(EntityCollection::class);
        $parentsCollector2->expects($this->exactly(2))
            ->method('toArray')
            ->willReturn([]);
        $parent2->expects($this->exactly(2))
            ->method('__get')
            ->with('parents')
            ->willReturn($parentsCollector2);
        $parent2->expects($this->exactly(4))
            ->method('getId')
            ->willReturn('parent2');

        $parentsCollector1 = $this->createMock(EntityCollection::class);
        $parentsCollector1->expects($this->exactly(2))
            ->method('toArray')
            ->willReturn([$parent2]);
        $parentsCollector1->expects($this->once())
            ->method('add')
            ->with($parent1);
        $parent1->expects($this->exactly(3))
            ->method('__get')
            ->with('parents')
            ->willReturn($parentsCollector1);
        $parent1->expects($this->exactly(1))
            ->method('getId')
            ->willReturn('parent1');

        $parentsCollector0 = $this->createMock(EntityCollection::class);
        $parentsCollector0->expects($this->once())
            ->method('add')
            ->with($parent1);
        $parentsCollector0->expects($this->once())
            ->method('toArray')
            ->willReturn([$parent1]);
        $role->expects($this->exactly(2))
            ->method('__get')
            ->with('parents')
            ->willReturn($parentsCollector0);
        $role->expects($this->exactly(1))
            ->method('getId')
            ->willReturn('role');

        $role->addParent($parent1);
        $parent1->addParent($parent2);

        $this->expectException(InvalidRoleException::class);
        $this->expectExceptionMessage('Parent role creates a recursive hierarchy');

        $parent2->addParent($role);
    }
}