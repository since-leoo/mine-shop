<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Permission\Entity;

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Entity\UserEntity;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    private function makeUser(Status $status = Status::Normal): UserEntity
    {
        $entity = new UserEntity();
        $entity->setId(1)->setUsername('admin')->setNickname('管理员')->setStatus($status);
        return $entity;
    }

    public function testBasicProperties(): void
    {
        $user = $this->makeUser();
        $this->assertSame(1, $user->getId());
        $this->assertSame('admin', $user->getUsername());
        $this->assertSame('管理员', $user->getNickname());
        $this->assertSame(Status::Normal, $user->getStatus());
    }

    public function testActivateDisable(): void
    {
        $user = $this->makeUser();
        $user->disable();
        $this->assertSame(Status::DISABLE, $user->getStatus());
        $user->activate();
        $this->assertSame(Status::Normal, $user->getStatus());
    }

    public function testVerifyPassword(): void
    {
        $user = $this->makeUser();
        $user->setPassword(password_hash('secret123', PASSWORD_BCRYPT));
        $this->assertTrue($user->verifyPassword('secret123'));
        $this->assertFalse($user->verifyPassword('wrong'));
    }

    public function testGrantRoles(): void
    {
        $user = $this->makeUser();
        $result = $user->grantRoles([1, 2, 3]);
        $this->assertTrue($result->success);
        $this->assertSame([1, 2, 3], $result->roleIds);
    }

    public function testGrantRolesEmpty(): void
    {
        $user = $this->makeUser();
        $result = $user->grantRoles([]);
        $this->assertTrue($result->success);
        $this->assertTrue($result->shouldSync);
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
        $this->assertTrue($user->canGrantRoles());

        $disabled = $this->makeUser(Status::DISABLE);
        $this->assertFalse($disabled->canGrantRoles());
    }

    public function testResetPassword(): void
    {
        $user = $this->makeUser();
        $result = $user->resetPasswordWithValidation('newpass');
        $this->assertTrue($result->success);
        $this->assertTrue($result->needsSave);
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
        $this->assertFalse($user->shouldSyncDepartments());
        $user->setDepartmentIds([1, 2]);
        $this->assertTrue($user->shouldSyncDepartments());
        $this->assertSame([1, 2], $user->getDepartmentIds());
    }

    public function testPositionSync(): void
    {
        $user = $this->makeUser();
        $this->assertFalse($user->shouldSyncPositions());
        $user->setPositionIds([3, 4]);
        $this->assertTrue($user->shouldSyncPositions());
    }
}
