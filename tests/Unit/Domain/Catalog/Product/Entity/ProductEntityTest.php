<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Catalog\Product\Entity;

use App\Domain\Catalog\Product\Entity\ProductEntity;
use App\Domain\Catalog\Product\Entity\ProductSkuEntity;
use PHPUnit\Framework\TestCase;

class ProductEntityTest extends TestCase
{
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

    public function testBasicProperties(): void
    {
        $entity = $this->makeEntity();
        $this->assertSame(1, $entity->getId());
        $this->assertSame('测试商品', $entity->getName());
        $this->assertSame(10, $entity->getCategoryId());
        $this->assertSame(5, $entity->getBrandId());
        $this->assertSame('draft', $entity->getStatus());
    }

    public function testStatusTransitionDraftToActive(): void
    {
        $entity = $this->makeEntity();
        $entity->activate();
        $this->assertSame('active', $entity->getStatus());
    }

    public function testStatusTransitionActiveToInactive(): void
    {
        $entity = $this->makeEntity();
        $entity->activate();
        $entity->deactivate();
        $this->assertSame('inactive', $entity->getStatus());
    }

    public function testStatusTransitionInactiveToActive(): void
    {
        $entity = $this->makeEntity();
        $entity->activate();
        $entity->deactivate();
        $entity->activate();
        $this->assertSame('active', $entity->getStatus());
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
        $this->assertSame(1000, $entity->getMinPrice());
        $this->assertSame(2000, $entity->getMaxPrice());
    }

    public function testSyncPriceRangeNoSkus(): void
    {
        $entity = $this->makeEntity();
        $entity->setSkus([]);
        $entity->syncPriceRange();
        $this->assertSame(0, $entity->getMinPrice());
        $this->assertSame(0, $entity->getMaxPrice());
    }

    public function testAddAndRemoveSku(): void
    {
        $entity = $this->makeEntity();
        $sku = new ProductSkuEntity();
        $sku->setId(100);
        $sku->setSalePrice(1000);
        $entity->addSku($sku);
        $this->assertCount(1, $entity->getSkus());

        $entity->removeSkuById(100);
        $this->assertCount(0, $entity->getSkus());
    }

    public function testSortNonNegative(): void
    {
        $entity = $this->makeEntity();
        $entity->applySort(-5);
        $this->assertSame(0, $entity->getSort());
    }

    public function testFreightSettings(): void
    {
        $entity = $this->makeEntity();
        $entity->setFreightType('flat');
        $entity->setFlatFreightAmount(800);
        $entity->setShippingTemplateId(3);
        $this->assertSame('flat', $entity->getFreightType());
        $this->assertSame(800, $entity->getFlatFreightAmount());
        $this->assertSame(3, $entity->getShippingTemplateId());
    }

    public function testFlags(): void
    {
        $entity = $this->makeEntity();
        $entity->setIsRecommend(true);
        $entity->setIsHot(true);
        $entity->setIsNew(false);
        $this->assertTrue($entity->getIsRecommend());
        $this->assertTrue($entity->getIsHot());
        $this->assertFalse($entity->getIsNew());
    }

    public function testToArray(): void
    {
        $entity = $this->makeEntity();
        $arr = $entity->toArray();
        $this->assertSame('测试商品', $arr['name']);
        $this->assertSame(10, $arr['category_id']);
        $this->assertSame('draft', $arr['status']);
    }
}
