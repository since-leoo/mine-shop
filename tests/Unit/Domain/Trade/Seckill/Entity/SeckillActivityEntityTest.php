<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Seckill\Entity;

use App\Domain\Trade\Seckill\Entity\SeckillActivityEntity;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;
use PHPUnit\Framework\TestCase;

class SeckillActivityEntityTest extends TestCase
{
    public function testReconstitute(): void
    {
        $entity = SeckillActivityEntity::reconstitute(
            1, '秒杀活动', '描述', 'pending', true, null, '备注'
        );
        $this->assertSame(1, $entity->getId());
        $this->assertSame('秒杀活动', $entity->getTitle());
        $this->assertSame('描述', $entity->getDescription());
        $this->assertSame(SeckillStatus::PENDING, $entity->getStatus());
        $this->assertTrue($entity->isEnabled());
        $this->assertSame('备注', $entity->getRemark());
    }

    public function testToggleEnabled(): void
    {
        $entity = SeckillActivityEntity::reconstitute(1, 'Test', null, 'pending', true, null, null);
        $entity->toggleEnabled();
        $this->assertFalse($entity->isEnabled());
        $entity->toggleEnabled();
        $this->assertTrue($entity->isEnabled());
    }

    public function testCanBeEnabled(): void
    {
        $pending = SeckillActivityEntity::reconstitute(1, 'T', null, 'pending', true, null, null);
        $this->assertTrue($pending->canBeEnabled());

        $cancelled = SeckillActivityEntity::reconstitute(2, 'T', null, 'cancelled', false, null, null);
        $this->assertFalse($cancelled->canBeEnabled());

        $ended = SeckillActivityEntity::reconstitute(3, 'T', null, 'ended', false, null, null);
        $this->assertFalse($ended->canBeEnabled());
    }

    public function testCanBeEdited(): void
    {
        $pending = SeckillActivityEntity::reconstitute(1, 'T', null, 'pending', true, null, null);
        $this->assertTrue($pending->canBeEdited());

        $active = SeckillActivityEntity::reconstitute(2, 'T', null, 'active', true, null, null);
        $this->assertFalse($active->canBeEdited());
    }

    public function testCanBeDeleted(): void
    {
        $pending = SeckillActivityEntity::reconstitute(1, 'T', null, 'pending', true, null, null);
        $this->assertTrue($pending->canBeDeleted());

        $active = SeckillActivityEntity::reconstitute(2, 'T', null, 'active', true, null, null);
        $this->assertFalse($active->canBeDeleted());
    }

    public function testStart(): void
    {
        $entity = SeckillActivityEntity::reconstitute(1, 'T', null, 'pending', true, null, null);
        $entity->start();
        $this->assertSame(SeckillStatus::ACTIVE, $entity->getStatus());
    }

    public function testStartNotPendingThrows(): void
    {
        $entity = SeckillActivityEntity::reconstitute(1, 'T', null, 'active', true, null, null);
        $this->expectException(\DomainException::class);
        $entity->start();
    }

    public function testStartDisabledThrows(): void
    {
        $entity = SeckillActivityEntity::reconstitute(1, 'T', null, 'pending', false, null, null);
        $this->expectException(\DomainException::class);
        $entity->start();
    }

    public function testEnd(): void
    {
        $entity = SeckillActivityEntity::reconstitute(1, 'T', null, 'active', true, null, null);
        $entity->end();
        $this->assertSame(SeckillStatus::ENDED, $entity->getStatus());
    }

    public function testEndAlreadyEndedThrows(): void
    {
        $entity = SeckillActivityEntity::reconstitute(1, 'T', null, 'ended', false, null, null);
        $this->expectException(\DomainException::class);
        $entity->end();
    }

    public function testCancel(): void
    {
        $entity = SeckillActivityEntity::reconstitute(1, 'T', null, 'pending', true, null, null);
        $entity->cancel();
        $this->assertSame(SeckillStatus::CANCELLED, $entity->getStatus());
        $this->assertFalse($entity->isEnabled());
    }

    public function testCancelEndedThrows(): void
    {
        $entity = SeckillActivityEntity::reconstitute(1, 'T', null, 'ended', false, null, null);
        $this->expectException(\DomainException::class);
        $entity->cancel();
    }

    public function testToArray(): void
    {
        $entity = SeckillActivityEntity::reconstitute(1, '活动', '描述', 'pending', true, null, null);
        $arr = $entity->toArray();
        $this->assertSame('活动', $arr['title']);
        $this->assertSame('pending', $arr['status']);
        $this->assertTrue($arr['is_enabled']);
    }
}
