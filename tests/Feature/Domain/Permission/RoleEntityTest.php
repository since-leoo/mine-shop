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

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Entity\RoleEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class RoleEntityTest extends TestCase
{
    public function testEnsureCanPersistRequiresNameAndCodeOnCreate(): void
    {
        $entity = new RoleEntity();
        $this->expectException(\DomainException::class);
        $entity->ensureCanPersist(true);
    }

    public function testRenameAndAssignCode(): void
    {
        $entity = (new RoleEntity())
            ->rename('管理员')
            ->assignCode('admin')
            ->changeStatus(Status::Normal)
            ->applySort(-10);

        self::assertSame('管理员', $entity->getName());
        self::assertSame('admin', $entity->getCode());
        self::assertSame(0, $entity->getSort());
    }
}
