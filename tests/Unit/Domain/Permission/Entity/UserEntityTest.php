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
use App\Domain\Permission\Entity\UserEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class UserEntityTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $user = $this->makeUser();
        self::assertSame(1, $user->getId());
        self::assertSame('admin', $user->getUsername());
        self::assertSame('管理员', $user->getNickname());
        self::assertSame(Status::Normal, $user->getStatus());
    }

    public function testActivateDisable(): void
    {
        $user = $this->makeUser();
        $user->disable();
        self::assertSame(Status::DISABLE, $user->getStatus());
        $user->activate();
        self::assertSame(Status::Normal, $user->getStatus());
    }

    public function testVerifyPassword(): void
    {
        $user = $this->makeUser();
        $user->setPassword(password_hash('secret123', \PASSWORD_BCRYPT));
        self::assertTrue($user->verifyPassword('secret123'));
        self::assertFalse($user->verifyPassword('wrong'));
    }

    public function testGrantRoles(): void
    {
        $user = $this->makeUser();
        $result = $user->grantRoles([1, 2, 3]);
        self::assertTrue($result->success);
        self::assertSame([1, 2, 3], $result->roleIds);
    }

    public function testGrantRolesEmpty(): void
    {
        $user = $this->makeUser();
        $result = $user->grantRoles([]);
        self::assertTrue($result->success);
        self::assertTrue($result->shouldSync);
    }

    public function testGrantRolesDisabledThrows(): void
    {
        $user = $this->makeUser(Status::DISABLE);
        $this->expectException(\DomainException::class);
        $user->grantRoles([1]);
    }

    public function testCanGrantRoles(): void
    {
        $user = $this->makeUser(Status::Normal);
        self::assertTrue($user->canGrantRoles());

        $disabled = $this->makeUser(Status::DISABLE);
        self::assertFalse($disabled->canGrantRoles());
    }

    public function testResetPassword(): void
    {
        $user = $this->makeUser();
        $result = $user->resetPasswordWithValidation('newpass');
        self::assertTrue($result->success);
        self::assertTrue($result->needsSave);
    }

    public function testResetPasswordDisabledThrows(): void
    {
        $user = $this->makeUser(Status::DISABLE);
        $this->expectException(\DomainException::class);
        $user->resetPasswordWithValidation();
    }

    public function testDepartmentSync(): void
    {
        $user = $this->makeUser();
        self::assertFalse($user->shouldSyncDepartments());
        $user->setDepartmentIds([1, 2]);
        self::assertTrue($user->shouldSyncDepartments());
        self::assertSame([1, 2], $user->getDepartmentIds());
    }

    public function testPositionSync(): void
    {
        $user = $this->makeUser();
        self::assertFalse($user->shouldSyncPositions());
        $user->setPositionIds([3, 4]);
        self::assertTrue($user->shouldSyncPositions());
    }

    private function makeUser(Status $status = Status::Normal): UserEntity
    {
        $entity = new UserEntity();
        $entity->setId(1)->setUsername('admin')->setNickname('管理员')->setStatus($status);
        return $entity;
    }
}
