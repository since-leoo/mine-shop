<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Permission\Entity;

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Entity\RoleEntity;
use PHPUnit\Framework\TestCase;

class RoleEntityTest extends TestCase
{
    private function makeRole(string $code = 'admin', Status $status = Status::Normal): RoleEntity
    {
        $entity = new RoleEntity();
        $entity->setId(1)->setName('管理员')->setCode($code)->setStatus($status);
        return $entity;
    }

    public function testBasicProperties(): void
    {
        $role = $this->makeRole();
        $this->assertSame(1, $role->getId());
        $this->assertSame('管理员', $role->getName());
        $this->assertSame('admin', $role->getCode());
    }

    public function testEmptyNameThrows(): void
    {
        $this->expectException(\DomainException::class);
        (new RoleEntity())->setName('');
    }

    public function testEmptyCodeThrows(): void
    {
        $this->expectException(\DomainException::class);
        (new RoleEntity())->setCode('');
    }

    public function testIsSuperAdmin(): void
    {
        $role = $this->makeRole('SuperAdmin');
        $this->assertTrue($role->isSuperAdmin());

        $role2 = $this->makeRole('admin');
        $this->assertFalse($role2->isSuperAdmin());
    }

    public function testCanGrantPermission(): void
    {
        $role = $this->makeRole('admin', Status::Normal);
        $this->assertTrue($role->canGrantPermission());

        $disabled = $this->makeRole('admin', Status::DISABLE);
        $this->assertFalse($disabled->canGrantPermission());

        $superAdmin = $this->makeRole('SuperAdmin', Status::Normal);
        $this->assertFalse($superAdmin->canGrantPermission());
    }

    public function testGrantPermissions(): void
    {
        $role = $this->makeRole();
        $result = $role->grantPermissions([1, 2, 3]);
        $this->assertTrue($result->success);
        $this->assertSame([1, 2, 3], $result->menuIds);
    }

    public function testGrantPermissionsEmpty(): void
    {
        $role = $this->makeRole();
        $result = $role->grantPermissions([]);
        $this->assertTrue($result->success);
        $this->assertTrue($result->shouldDetach);
    }

    public function testGrantPermissionsDisabledThrows(): void
    {
        $role = $this->makeRole('admin', Status::DISABLE);
        $this->expectException(\DomainException::class);
        $role->grantPermissions([1]);
    }

    public function testGrantPermissionsSuperAdminThrows(): void
    {
        $role = $this->makeRole('admin', Status::Normal);
        $this->expectException(\DomainException::class);
        $role->grantPermissions([1], true);
    }

    public function testSortNonNegative(): void
    {
        $role = $this->makeRole();
        $role->setSort(-10);
        $this->assertSame(0, $role->getSort());
    }
}
