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

namespace HyperfTests\Unit\Domain\Trade\GroupBuy\Entity;

use App\Domain\Trade\GroupBuy\Entity\GroupBuyEntity;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class GroupBuyEntityTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
    }

    public function testBasicProperties(): void
    {
        $entity = $this->makeEntity();
        self::assertSame(1, $entity->getId());
        self::assertSame('团购活动', $entity->getTitle());
        self::assertSame(100, $entity->getProductId());
        self::assertSame(10000, $entity->getOriginalPrice());
        self::assertSame(8000, $entity->getGroupPrice());
    }

    public function testEmptyTitleThrows(): void
    {
        $entity = new GroupBuyEntity();
        $this->expectException(\DomainException::class);
        $entity->setTitle('');
    }

    public function testStart(): void
    {
        $entity = $this->makeEntity('pending');
        $entity->start();
        self::assertSame('active', $entity->getStatus());
    }

    public function testStartNotPendingThrows(): void
    {
        $entity = $this->makeEntity('active');
        $this->expectException(\DomainException::class);
        $entity->start();
    }

    public function testEnd(): void
    {
        $entity = $this->makeEntity('active');
        $entity->end();
        self::assertSame('ended', $entity->getStatus());
    }

    public function testEndAlreadyEndedThrows(): void
    {
        $entity = $this->makeEntity('ended');
        $this->expectException(\DomainException::class);
        $entity->end();
    }

    public function testIncreaseSoldQuantity(): void
    {
        $entity = $this->makeEntity('active');
        $entity->increaseSoldQuantity(10);
        self::assertSame(10, $entity->getSoldQuantity());
    }

    public function testIncreaseSoldQuantityZeroThrows(): void
    {
        $entity = $this->makeEntity('active');
        $this->expectException(\DomainException::class);
        $entity->increaseSoldQuantity(0);
    }

    public function testIncreaseSoldQuantityExceedsStockThrows(): void
    {
        $entity = $this->makeEntity('active');
        $this->expectException(\DomainException::class);
        $entity->increaseSoldQuantity(101);
    }

    public function testIncreaseSoldQuantitySoldOut(): void
    {
        $entity = $this->makeEntity('active');
        $entity->increaseSoldQuantity(100);
        self::assertSame('sold_out', $entity->getStatus());
    }

    public function testIncreaseGroupCount(): void
    {
        $entity = $this->makeEntity();
        $entity->increaseGroupCount();
        self::assertSame(1, $entity->getGroupCount());
    }

    public function testIncreaseSuccessGroupCount(): void
    {
        $entity = $this->makeEntity();
        $entity->increaseSuccessGroupCount();
        self::assertSame(1, $entity->getSuccessGroupCount());
    }

    public function testCanJoin(): void
    {
        $entity = $this->makeEntity('active', true);
        self::assertTrue($entity->canJoin());
    }

    public function testCanJoinDisabled(): void
    {
        $entity = $this->makeEntity('active', false);
        self::assertFalse($entity->canJoin());
    }

    public function testCanJoinNotActive(): void
    {
        $entity = $this->makeEntity('pending', true);
        self::assertFalse($entity->canJoin());
    }

    public function testCanJoinSoldOut(): void
    {
        $entity = $this->makeEntity('active', true);
        $entity->setSoldQuantity(100);
        self::assertFalse($entity->canJoin());
    }

    public function testEnable(): void
    {
        $entity = $this->makeEntity('pending', false);
        $entity->setTotalQuantity(100);
        $entity->enable();
        self::assertTrue($entity->getIsEnabled());
    }

    public function testDisable(): void
    {
        $entity = $this->makeEntity('active', true);
        $entity->disable();
        self::assertFalse($entity->getIsEnabled());
    }

    public function testToArray(): void
    {
        $entity = $this->makeEntity();
        $arr = $entity->toArray();
        self::assertSame('团购活动', $arr['title']);
        self::assertSame(10000, $arr['original_price']);
        self::assertSame(8000, $arr['group_price']);
    }

    private function makeEntity(string $status = 'pending', bool $enabled = true): GroupBuyEntity
    {
        Carbon::setTestNow(Carbon::parse('2026-03-05 12:00:00'));
        $entity = new GroupBuyEntity();
        $entity->setId(1);
        $entity->setTitle('团购活动');
        $entity->setProductId(100);
        $entity->setSkuId(200);
        $entity->setOriginalPrice(10000);
        $entity->setGroupPrice(8000);
        $entity->setMinPeople(2);
        $entity->setMaxPeople(10);
        $entity->setStartTime('2026-03-01 00:00:00');
        $entity->setEndTime('2026-03-10 00:00:00');
        $entity->setStatus($status);
        $entity->setTotalQuantity(100);
        $entity->setSoldQuantity(0);
        $entity->setIsEnabled($enabled);
        return $entity;
    }
}
