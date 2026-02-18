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

namespace HyperfTests\Unit\Domain\Trade\Seckill\Entity;

use App\Domain\Trade\Seckill\Entity\SeckillProductEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class SeckillProductEntityTest extends TestCase
{
    public function testReconstitute(): void
    {
        $entity = $this->makeEntity();
        self::assertSame(1, $entity->getId());
        self::assertSame(1, $entity->getActivityId());
        self::assertSame(100, $entity->getProductId());
        self::assertSame(200, $entity->getProductSkuId());
        self::assertSame(10000, $entity->getPrice()->getOriginalPrice());
        self::assertSame(8000, $entity->getPrice()->getSeckillPrice());
        self::assertSame(100, $entity->getStock()->getQuantity());
        self::assertSame(3, $entity->getMaxQuantityPerUser());
    }

    public function testCanSell(): void
    {
        $entity = $this->makeEntity(100, 90);
        self::assertTrue($entity->canSell(10));
        self::assertFalse($entity->canSell(11));
    }

    public function testCanSellDisabled(): void
    {
        $entity = $this->makeEntity(100, 0, false);
        self::assertFalse($entity->canSell(1));
    }

    public function testCanSellSoldOut(): void
    {
        $entity = $this->makeEntity(100, 100);
        self::assertFalse($entity->canSell(1));
    }

    public function testCanUserPurchase(): void
    {
        $entity = $this->makeEntity(); // maxQuantityPerUser = 3
        self::assertTrue($entity->canUserPurchase(2, 0));
        self::assertTrue($entity->canUserPurchase(1, 2));
        self::assertFalse($entity->canUserPurchase(2, 2));
    }

    public function testSell(): void
    {
        $entity = $this->makeEntity(100, 0);
        $entity->sell(10);
        self::assertSame(10, $entity->getStock()->getSoldQuantity());
    }

    public function testSellSoldOutDisables(): void
    {
        $entity = $this->makeEntity(10, 0);
        $entity->sell(10);
        self::assertFalse($entity->isEnabled());
    }

    public function testEnableDisable(): void
    {
        $entity = $this->makeEntity();
        $entity->disable();
        self::assertFalse($entity->isEnabled());
        $entity->enable();
        self::assertTrue($entity->isEnabled());
    }

    public function testToArray(): void
    {
        $entity = $this->makeEntity();
        $arr = $entity->toArray();
        self::assertSame(1, $arr['activity_id']);
        self::assertSame(10000, $arr['original_price']);
        self::assertSame(8000, $arr['seckill_price']);
        self::assertSame(100, $arr['quantity']);
    }

    private function makeEntity(int $quantity = 100, int $sold = 0, bool $enabled = true): SeckillProductEntity
    {
        return SeckillProductEntity::reconstitute(
            1,
            1,
            1,
            100,
            200,
            10000,
            8000,
            $quantity,
            $sold,
            3,
            0,
            $enabled
        );
    }
}
