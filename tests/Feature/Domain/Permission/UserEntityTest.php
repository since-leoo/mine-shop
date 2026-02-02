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

namespace HyperfTests\Feature\Domain\Permission;

use App\Domain\Auth\Enum\Type;
use App\Domain\Permission\Entity\UserEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class UserEntityTest extends TestCase
{
    public function testEnsureCanPersistRequiresUsernameAndPasswordOnCreate(): void
    {
        $entity = new UserEntity();
        $entity->setNickname('测试用户');

        $this->expectException(\DomainException::class);
        $entity->ensureCanPersist(true);
    }

    public function testChangeStatusAndUserType(): void
    {
        $entity = (new UserEntity())
            ->setUsername('mineadmin')
            ->setPassword('123456')
            ->setNickname('管理员')
            ->setUserType(Type::SYSTEM);

        $entity->ensureCanPersist(true);

        self::assertSame('mineadmin', $entity->getUsername());
    }
}
