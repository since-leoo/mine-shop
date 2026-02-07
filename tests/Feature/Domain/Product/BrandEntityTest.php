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

use App\Domain\Catalog\Brand\Entity\BrandEntity;
use App\Domain\Catalog\Brand\Enum\BrandStatus;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class BrandEntityTest extends TestCase
{
    public function testRenameAndSort(): void
    {
        $entity = new BrandEntity();
        $entity->rename('Apple')->applySort(-5);

        self::assertSame('Apple', $entity->getName());
        self::assertSame(0, $entity->getSort());
    }

    public function testChangeStatusAndActivation(): void
    {
        $entity = new BrandEntity();
        $entity->rename('Nike');
        $entity->deactivate();

        self::assertFalse($entity->isActive());

        $entity->activate();
        self::assertSame(BrandStatus::ACTIVE->value, $entity->getStatus());
    }

    public function testEnsureCanPersistRequiresNameOnCreate(): void
    {
        $entity = new BrandEntity();
        $this->expectException(\DomainException::class);
        $entity->ensureCanPersist(true);
    }
}
