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

use App\Domain\Trade\Seckill\Entity\SeckillSessionEntity;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class SeckillSessionEntityTest extends TestCase
{
    public function testReconstitute(): void
    {
        $entity = $this->makeEntity();
        self::assertSame(1, $entity->getId());
        self::assertSame(1, $entity->getActivityId());
        self::assertSame(SeckillStatus::PENDING, $entity->getStatus());
        self::assertTrue($entity->isEnabled());
    }

    public function testCanPurchase(): void
    {
        $entity = $this->makeEntity('pending', true, 0);
        // pending + enabled + not sold out + active period
        self::assertTrue($entity->canPurchase());
    }

    public function testCanPurchaseDisabled(): void
    {
        $entity = $this->makeEntity('pending', false);
        self::assertFalse($entity->canPurchase());
    }

    public function testCanPurchaseSoldOut(): void
    {
        $entity = $this->makeEntity('pending', true, 100);
        self::assertFalse($entity->canPurchase());
    }

    public function testCanBeEdited(): void
    {
        $pending = $this->makeEntity('pending');
        self::assertTrue($pending->canBeEdited());

        $active = $this->makeEntity('active');
        self::assertFalse($active->canBeEdited());
    }

    public function testCanBeDeleted(): void
    {
        $pending = $this->makeEntity('pending', true, 0);
        self::assertTrue($pending->canBeDeleted());

        $active = $this->makeEntity('active');
        self::assertFalse($active->canBeDeleted());
    }

    public function testStart(): void
    {
        $entity = $this->makeEntity('pending', true);
        $entity->start();
        self::assertSame(SeckillStatus::ACTIVE, $entity->getStatus());
    }

    public function testStartNotPendingThrows(): void
    {
        $entity = $this->makeEntity('active');
        $this->expectException(\DomainException::class);
        $entity->start();
    }

    public function testStartDisabledThrows(): void
    {
        $entity = $this->makeEntity('pending', false);
        $this->expectException(\DomainException::class);
        $entity->start();
    }

    public function testEnd(): void
    {
        $entity = $this->makeEntity('active');
        $entity->end();
        self::assertSame(SeckillStatus::ENDED, $entity->getStatus());
    }

    public function testEndAlreadyEndedThrows(): void
    {
        $entity = $this->makeEntity('ended');
        $this->expectException(\DomainException::class);
        $entity->end();
    }

    public function testSoldOut(): void
    {
        $entity = $this->makeEntity('active');
        $entity->soldOut();
        self::assertSame(SeckillStatus::SOLD_OUT, $entity->getStatus());
    }

    public function testSell(): void
    {
        $entity = $this->makeEntity('active', true, 0);
        $entity->sell(10);
        self::assertSame(10, $entity->getStock()->getSoldQuantity());
    }

    public function testSellSoldOutChangesStatus(): void
    {
        $entity = $this->makeEntity('active', true, 90);
        $entity->sell(10);
        self::assertSame(SeckillStatus::SOLD_OUT, $entity->getStatus());
    }

    public function testToggleEnabled(): void
    {
        $entity = $this->makeEntity('pending', true);
        $entity->toggleEnabled();
        self::assertFalse($entity->isEnabled());
    }

    public function testToArray(): void
    {
        $entity = $this->makeEntity();
        $arr = $entity->toArray();
        self::assertSame(1, $arr['activity_id']);
        self::assertSame('pending', $arr['status']);
        self::assertArrayHasKey('start_time', $arr);
        self::assertArrayHasKey('end_time', $arr);
    }

    private function makeEntity(string $status = 'pending', bool $enabled = true, int $sold = 0): SeckillSessionEntity
    {
        return SeckillSessionEntity::reconstitute(
            1,
            1,
            Carbon::now()->subHour()->toDateTimeString(),
            Carbon::now()->addHour()->toDateTimeString(),
            $status,
            3,
            100,
            $sold,
            0,
            $enabled,
            null,
            null
        );
    }
}
