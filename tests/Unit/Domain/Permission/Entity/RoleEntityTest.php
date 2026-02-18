<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace HyperfTests\Unit\Domain\Permission\Entity;

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Entity\RoleEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class RoleEntityTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $role = $this->makeRole();
        self::assertSame(1, $role->getId());
        self::assertSame('管理员', $role->getName());
        self::assertSame('admin', $role->getCode());
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
        self::assertTrue($role->isSuperAdmin());

        $role2 = $this->makeRole('admin');
        self::assertFalse($role2->isSuperAdmin());
    }

    public function testCanGrantPermission(): void
    {
        $role = $this->makeRole('admin', Status::Normal);
        self::assertTrue($role->canGrantPermission());

        $disabled = $this->makeRole('admin', Status::DISABLE);
        self::assertFalse($disabled->canGrantPermission());

        $superAdmin = $this->makeRole('SuperAdmin', Status::Normal);
        self::assertFalse($superAdmin->canGrantPermission());
    }

    public function testGrantPermissions(): void
    {
        $role = $this->makeRole();
        $result = $role->grantPermissions([1, 2, 3]);
        self::assertTrue($result->success);
        self::assertSame([1, 2, 3], $result->menuIds);
    }

    public function testGrantPermissionsEmpty(): void
    {
        $role = $this->makeRole();
        $result = $role->grantPermissions([]);
        self::assertTrue($result->success);
        self::assertTrue($result->shouldDetach);
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
        self::assertSame(0, $role->getSort());
    }

    private function makeRole(string $code = 'admin', Status $status = Status::Normal): RoleEntity
    {
        $entity = new RoleEntity();
        $entity->setId(1)->setName('管理员')->setCode($code)->setStatus($status);
        return $entity;
    }
}
