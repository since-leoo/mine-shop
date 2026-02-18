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

use App\Domain\Trade\Seckill\Entity\SeckillActivityEntity;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class SeckillActivityEntityTest extends TestCase
{
    public function testReconstitute(): void
    {
        $entity = SeckillActivityEntity::reconstitute(
            1,
            '秒杀活动',
            '描述',
            'pending',
            true,
            null,
            '备注'
        );
        self::assertSame(1, $entity->getId());
        self::assertSame('秒杀活动', $entity->getTitle());
        self::assertSame('描述', $entity->getDescription());
        self::assertSame(SeckillStatus::PENDING, $entity->getStatus());
        self::assertTrue($entity->isEnabled());
        self::assertSame('备注', $entity->getRemark());
    }

    public function testToggleEnabled(): void
    {
        $entity = SeckillActivityEntity::reconstitute(1, 'Test', null, 'pending', true, null, null);
        $entity->toggleEnabled();
        self::assertFalse($entity->isEnabled());
        $entity->toggleEnabled();
        self::assertTrue($entity->isEnabled());
    }

    public function testCanBeEnabled(): void
    {
        $pending = SeckillActivityEntity::reconstitute(1, 'T', null, 'pending', true, null, null);
        self::assertTrue($pending->canBeEnabled());

        $cancelled = SeckillActivityEntity::reconstitute(2, 'T', null, 'cancelled', false, null, null);
        self::assertFalse($cancelled->canBeEnabled());

        $ended = SeckillActivityEntity::reconstitute(3, 'T', null, 'ended', false, null, null);
        self::assertFalse($ended->canBeEnabled());
    }

    public function testCanBeEdited(): void
    {
        $pending = SeckillActivityEntity::reconstitute(1, 'T', null, 'pending', true, null, null);
        self::assertTrue($pending->canBeEdited());

        $active = SeckillActivityEntity::reconstitute(2, 'T', null, 'active', true, null, null);
        self::assertFalse($active->canBeEdited());
    }

    public function testCanBeDeleted(): void
    {
        $pending = SeckillActivityEntity::reconstitute(1, 'T', null, 'pending', true, null, null);
        self::assertTrue($pending->canBeDeleted());

        $active = SeckillActivityEntity::reconstitute(2, 'T', null, 'active', true, null, null);
        self::assertFalse($active->canBeDeleted());
    }

    public function testStart(): void
    {
        $entity = SeckillActivityEntity::reconstitute(1, 'T', null, 'pending', true, null, null);
        $entity->start();
        self::assertSame(SeckillStatus::ACTIVE, $entity->getStatus());
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
        self::assertSame(SeckillStatus::ENDED, $entity->getStatus());
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
        self::assertSame(SeckillStatus::CANCELLED, $entity->getStatus());
        self::assertFalse($entity->isEnabled());
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
        self::assertSame('活动', $arr['title']);
        self::assertSame('pending', $arr['status']);
        self::assertTrue($arr['is_enabled']);
    }
}
