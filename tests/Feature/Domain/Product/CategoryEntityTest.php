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

namespace HyperfTests\Feature\Domain\Product;

use App\Domain\Product\Entity\CategoryEntity;
use App\Domain\Product\Enum\CategoryStatus;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class CategoryEntityTest extends TestCase
{
    public function testMoveAndSort(): void
    {
        $entity = (new CategoryEntity())
            ->rename('数码产品')
            ->moveToParent(1, 2)
            ->applySort(-10);

        self::assertSame(1, $entity->getParentId());
        self::assertSame(2, $entity->getLevel());
        self::assertSame(0, $entity->getSort());
    }

    public function testChangeStatus(): void
    {
        $entity = (new CategoryEntity())->rename('服饰');
        $entity->deactivate();
        self::assertSame(CategoryStatus::INACTIVE->value, $entity->getStatus());
    }

    public function testEnsureCanPersistRequiresNameOnCreate(): void
    {
        $entity = new CategoryEntity();
        $this->expectException(\DomainException::class);
        $entity->ensureCanPersist(true);
    }
}
