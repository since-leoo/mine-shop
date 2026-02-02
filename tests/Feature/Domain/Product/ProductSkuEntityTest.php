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

use App\Domain\Product\Entity\ProductSkuEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ProductSkuEntityTest extends TestCase
{
    public function testIncreaseAndDecreaseStock(): void
    {
        $sku = new ProductSkuEntity();
        $sku->setSkuName('默认');
        $sku->setSalePrice(99.0);
        $sku->setStock(5);

        $sku->increaseStock(3);
        self::assertSame(8, $sku->getStock());

        $sku->decreaseStock(2);
        self::assertSame(6, $sku->getStock());
    }

    public function testEnsureStockAvailableThrowsWhenInsufficient(): void
    {
        $sku = new ProductSkuEntity();
        $sku->setSkuName('默认');
        $sku->setSalePrice(10.0);
        $sku->setStock(1);

        $this->expectException(\DomainException::class);
        $sku->ensureStockAvailable(2);
    }
}
