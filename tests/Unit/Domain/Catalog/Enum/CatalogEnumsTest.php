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

namespace HyperfTests\Unit\Domain\Catalog\Enum;

use App\Domain\Catalog\Brand\Enum\BrandStatus;
use App\Domain\Catalog\Category\Enum\CategoryStatus;
use App\Domain\Catalog\Product\Enum\ProductStatus;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class CatalogEnumsTest extends TestCase
{
    public function testBrandStatus(): void
    {
        self::assertSame('active', BrandStatus::ACTIVE->value);
        self::assertSame('inactive', BrandStatus::INACTIVE->value);
        self::assertSame('启用', BrandStatus::ACTIVE->getText());
        self::assertSame('禁用', BrandStatus::INACTIVE->getText());
    }

    public function testBrandStatusOptions(): void
    {
        $options = BrandStatus::getOptions();
        self::assertCount(2, $options);
    }

    public function testCategoryStatus(): void
    {
        self::assertSame('active', CategoryStatus::ACTIVE->value);
        self::assertSame('inactive', CategoryStatus::INACTIVE->value);
    }

    public function testProductStatus(): void
    {
        $values = ProductStatus::values();
        self::assertContains('draft', $values);
        self::assertContains('active', $values);
        self::assertContains('inactive', $values);
        self::assertContains('sold_out', $values);
    }

    public function testProductStatusMutableValues(): void
    {
        $mutable = ProductStatus::mutableValues();
        self::assertContains('draft', $mutable);
        self::assertContains('active', $mutable);
        self::assertNotContains('sold_out', $mutable);
    }
}
