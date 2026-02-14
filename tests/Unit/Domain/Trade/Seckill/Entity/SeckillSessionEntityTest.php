<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Seckill\Entity;

use App\Domain\Trade\Seckill\Entity\SeckillSessionEntity;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SeckillSessionEntityTest extends TestCase
{
    private function makeEntity(string $status = 'pending', bool $enabled = true, int $sold = 0): SeckillSessionEntity
    {
        return SeckillSessionEntity::reconstitute(
            1, 1,
            Carbon::now()->subHour()->toDateTimeString(),
            Carbon::now()->addHour()->toDateTimeString(),
            $status, 3, 100, $sold, 0, $enabled, null, null
        );
    }

    public function testReconstitute(): void
    {
        $entity = $this->makeEntity();
        $this->assertSame(1, $entity->getId());
        $this->assertSame(1, $entity->getActivityId());
        $this->assertSame(SeckillStatus::PENDING, $entity->getStatus());
        $this->assertTrue($entity->isEnabled());
    }

    public function testCanPurchase(): void
    {
        $entity = $this->makeEntity('pending', true, 0);
        // pending + enabled + not sold out + active period
        $this->assertTrue($entity->canPurchase());
    }

    public function testCanPurchaseDisabled(): void
    {
        $entity = $this->makeEntity('pending', false);
        $this->assertFalse($entity->canPurchase());
    }

    public function testCanPurchaseSoldOut(): void
    {
        $entity = $this->makeEntity('pending', true, 100);
        $this->assertFalse($entity->canPurchase());
    }

    public function testCanBeEdited(): void
    {
        $pending = $this->makeEntity('pending');
        $this->assertTrue($pending->canBeEdited());

        $active = $this->makeEntity('active');
        $this->assertFalse($active->canBeEdited());
    }

    public function testCanBeDeleted(): void
    {
        $pending = $this->makeEntity('pending', true, 0);
        $this->assertTrue($pending->canBeDeleted());

        $active = $this->makeEntity('active');
        $this->assertFalse($active->canBeDeleted());
    }

    public function testStart(): void
    {
        $entity = $this->makeEntity('pending', true);
        $entity->start();
        $this->assertSame(SeckillStatus::ACTIVE, $entity->getStatus());
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
        $this->assertSame(SeckillStatus::ENDED, $entity->getStatus());
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
        $this->assertSame(SeckillStatus::SOLD_OUT, $entity->getStatus());
    }

    public function testSell(): void
    {
        $entity = $this->makeEntity('active', true, 0);
        $entity->sell(10);
        $this->assertSame(10, $entity->getStock()->getSoldQuantity());
    }

    public function testSellSoldOutChangesStatus(): void
    {
        $entity = $this->makeEntity('active', true, 90);
        $entity->sell(10);
        $this->assertSame(SeckillStatus::SOLD_OUT, $entity->getStatus());
    }

    public function testToggleEnabled(): void
    {
        $entity = $this->makeEntity('pending', true);
        $entity->toggleEnabled();
        $this->assertFalse($entity->isEnabled());
    }

    public function testToArray(): void
    {
        $entity = $this->makeEntity();
        $arr = $entity->toArray();
        $this->assertSame(1, $arr['activity_id']);
        $this->assertSame('pending', $arr['status']);
        $this->assertArrayHasKey('start_time', $arr);
        $this->assertArrayHasKey('end_time', $arr);
    }
}
