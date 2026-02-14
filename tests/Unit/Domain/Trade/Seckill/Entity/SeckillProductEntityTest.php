<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Seckill\Entity;

use App\Domain\Trade\Seckill\Entity\SeckillProductEntity;
use PHPUnit\Framework\TestCase;

class SeckillProductEntityTest extends TestCase
{
    private function makeEntity(int $quantity = 100, int $sold = 0, bool $enabled = true): SeckillProductEntity
    {
        return SeckillProductEntity::reconstitute(
            1, 1, 1, 100, 200, 10000, 8000, $quantity, $sold, 3, 0, $enabled
        );
    }

    public function testReconstitute(): void
    {
        $entity = $this->makeEntity();
        $this->assertSame(1, $entity->getId());
        $this->assertSame(1, $entity->getActivityId());
        $this->assertSame(100, $entity->getProductId());
        $this->assertSame(200, $entity->getProductSkuId());
        $this->assertSame(10000, $entity->getPrice()->getOriginalPrice());
        $this->assertSame(8000, $entity->getPrice()->getSeckillPrice());
        $this->assertSame(100, $entity->getStock()->getQuantity());
        $this->assertSame(3, $entity->getMaxQuantityPerUser());
    }

    public function testCanSell(): void
    {
        $entity = $this->makeEntity(100, 90);
        $this->assertTrue($entity->canSell(10));
        $this->assertFalse($entity->canSell(11));
    }

    public function testCanSellDisabled(): void
    {
        $entity = $this->makeEntity(100, 0, false);
        $this->assertFalse($entity->canSell(1));
    }

    public function testCanSellSoldOut(): void
    {
        $entity = $this->makeEntity(100, 100);
        $this->assertFalse($entity->canSell(1));
    }

    public function testCanUserPurchase(): void
    {
        $entity = $this->makeEntity(); // maxQuantityPerUser = 3
        $this->assertTrue($entity->canUserPurchase(2, 0));
        $this->assertTrue($entity->canUserPurchase(1, 2));
        $this->assertFalse($entity->canUserPurchase(2, 2));
    }

    public function testSell(): void
    {
        $entity = $this->makeEntity(100, 0);
        $entity->sell(10);
        $this->assertSame(10, $entity->getStock()->getSoldQuantity());
    }

    public function testSellSoldOutDisables(): void
    {
        $entity = $this->makeEntity(10, 0);
        $entity->sell(10);
        $this->assertFalse($entity->isEnabled());
    }

    public function testEnableDisable(): void
    {
        $entity = $this->makeEntity();
        $entity->disable();
        $this->assertFalse($entity->isEnabled());
        $entity->enable();
        $this->assertTrue($entity->isEnabled());
    }

    public function testToArray(): void
    {
        $entity = $this->makeEntity();
        $arr = $entity->toArray();
        $this->assertSame(1, $arr['activity_id']);
        $this->assertSame(10000, $arr['original_price']);
        $this->assertSame(8000, $arr['seckill_price']);
        $this->assertSame(100, $arr['quantity']);
    }
}
