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

namespace HyperfTests\Unit\Domain\Catalog\Product\Entity;

use App\Domain\Catalog\Product\Entity\ProductEntity;
use App\Domain\Catalog\Product\Entity\ProductSkuEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ProductEntityTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $entity = $this->makeEntity();
        self::assertSame(1, $entity->getId());
        self::assertSame('测试商品', $entity->getName());
        self::assertSame(10, $entity->getCategoryId());
        self::assertSame(5, $entity->getBrandId());
        self::assertSame('draft', $entity->getStatus());
    }

    public function testStatusTransitionDraftToActive(): void
    {
        $entity = $this->makeEntity();
        $entity->activate();
        self::assertSame('active', $entity->getStatus());
    }

    public function testStatusTransitionActiveToInactive(): void
    {
        $entity = $this->makeEntity();
        $entity->activate();
        $entity->deactivate();
        self::assertSame('inactive', $entity->getStatus());
    }

    public function testStatusTransitionInactiveToActive(): void
    {
        $entity = $this->makeEntity();
        $entity->activate();
        $entity->deactivate();
        $entity->activate();
        self::assertSame('active', $entity->getStatus());
    }

    public function testInvalidStatusTransitionThrows(): void
    {
        $entity = $this->makeEntity(); // draft
        $this->expectException(\Throwable::class);
        $entity->markSoldOut(); // draft -> sold_out not allowed
    }

    public function testSyncPriceRange(): void
    {
        $entity = $this->makeEntity();
        $sku1 = new ProductSkuEntity();
        $sku1->setSalePrice(1000);
        $sku2 = new ProductSkuEntity();
        $sku2->setSalePrice(2000);
        $entity->setSkus([$sku1, $sku2]);
        $entity->syncPriceRange();
        self::assertSame(1000, $entity->getMinPrice());
        self::assertSame(2000, $entity->getMaxPrice());
    }

    public function testSyncPriceRangeNoSkus(): void
    {
        $entity = $this->makeEntity();
        $entity->setSkus([]);
        $entity->syncPriceRange();
        self::assertSame(0, $entity->getMinPrice());
        self::assertSame(0, $entity->getMaxPrice());
    }

    public function testAddAndRemoveSku(): void
    {
        $entity = $this->makeEntity();
        $sku = new ProductSkuEntity();
        $sku->setId(100);
        $sku->setSalePrice(1000);
        $entity->addSku($sku);
        self::assertCount(1, $entity->getSkus());

        $entity->removeSkuById(100);
        self::assertCount(0, $entity->getSkus());
    }

    public function testSortNonNegative(): void
    {
        $entity = $this->makeEntity();
        $entity->applySort(-5);
        self::assertSame(0, $entity->getSort());
    }

    public function testFreightSettings(): void
    {
        $entity = $this->makeEntity();
        $entity->setFreightType('flat');
        $entity->setFlatFreightAmount(800);
        $entity->setShippingTemplateId(3);
        self::assertSame('flat', $entity->getFreightType());
        self::assertSame(800, $entity->getFlatFreightAmount());
        self::assertSame(3, $entity->getShippingTemplateId());
    }

    public function testFlags(): void
    {
        $entity = $this->makeEntity();
        $entity->setIsRecommend(true);
        $entity->setIsHot(true);
        $entity->setIsNew(false);
        self::assertTrue($entity->getIsRecommend());
        self::assertTrue($entity->getIsHot());
        self::assertFalse($entity->getIsNew());
    }

    public function testToArray(): void
    {
        $entity = $this->makeEntity();
        $arr = $entity->toArray();
        self::assertSame('测试商品', $arr['name']);
        self::assertSame(10, $arr['category_id']);
        self::assertSame('draft', $arr['status']);
    }

    private function makeEntity(): ProductEntity
    {
        $entity = new ProductEntity();
        $entity->setId(1);
        $entity->setName('测试商品');
        $entity->setCategoryId(10);
        $entity->setBrandId(5);
        $entity->setStatus('draft');
        return $entity;
    }
}
