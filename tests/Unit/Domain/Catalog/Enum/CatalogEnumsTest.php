<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Catalog\Enum;

use App\Domain\Catalog\Brand\Enum\BrandStatus;
use App\Domain\Catalog\Category\Enum\CategoryStatus;
use App\Domain\Catalog\Product\Enum\ProductStatus;
use PHPUnit\Framework\TestCase;

class CatalogEnumsTest extends TestCase
{
    public function testBrandStatus(): void
    {
        $this->assertSame('active', BrandStatus::ACTIVE->value);
        $this->assertSame('inactive', BrandStatus::INACTIVE->value);
        $this->assertSame('启用', BrandStatus::ACTIVE->getText());
        $this->assertSame('禁用', BrandStatus::INACTIVE->getText());
    }

    public function testBrandStatusOptions(): void
    {
        $options = BrandStatus::getOptions();
        $this->assertCount(2, $options);
    }

    public function testCategoryStatus(): void
    {
        $this->assertSame('active', CategoryStatus::ACTIVE->value);
        $this->assertSame('inactive', CategoryStatus::INACTIVE->value);
    }

    public function testProductStatus(): void
    {
        $values = ProductStatus::values();
        $this->assertContains('draft', $values);
        $this->assertContains('active', $values);
        $this->assertContains('inactive', $values);
        $this->assertContains('sold_out', $values);
    }

    public function testProductStatusMutableValues(): void
    {
        $mutable = ProductStatus::mutableValues();
        $this->assertContains('draft', $mutable);
        $this->assertContains('active', $mutable);
        $this->assertNotContains('sold_out', $mutable);
    }
}
