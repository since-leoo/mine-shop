<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\GroupBuy\Entity;

use App\Domain\Trade\GroupBuy\Entity\GroupBuyEntity;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class GroupBuyEntityTest extends TestCase
{
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
    }

    public function testBasicProperties(): void
    {
        $entity = $this->makeEntity();
        $this->assertSame(1, $entity->getId());
        $this->assertSame('团购活动', $entity->getTitle());
        $this->assertSame(100, $entity->getProductId());
        $this->assertSame(10000, $entity->getOriginalPrice());
        $this->assertSame(8000, $entity->getGroupPrice());
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
        $this->assertSame('active', $entity->getStatus());
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
        $this->assertSame('ended', $entity->getStatus());
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
        $this->assertSame(10, $entity->getSoldQuantity());
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
        $this->assertSame('sold_out', $entity->getStatus());
    }

    public function testIncreaseGroupCount(): void
    {
        $entity = $this->makeEntity();
        $entity->increaseGroupCount();
        $this->assertSame(1, $entity->getGroupCount());
    }

    public function testIncreaseSuccessGroupCount(): void
    {
        $entity = $this->makeEntity();
        $entity->increaseSuccessGroupCount();
        $this->assertSame(1, $entity->getSuccessGroupCount());
    }

    public function testCanJoin(): void
    {
        $entity = $this->makeEntity('active', true);
        $this->assertTrue($entity->canJoin());
    }

    public function testCanJoinDisabled(): void
    {
        $entity = $this->makeEntity('active', false);
        $this->assertFalse($entity->canJoin());
    }

    public function testCanJoinNotActive(): void
    {
        $entity = $this->makeEntity('pending', true);
        $this->assertFalse($entity->canJoin());
    }

    public function testCanJoinSoldOut(): void
    {
        $entity = $this->makeEntity('active', true);
        $entity->setSoldQuantity(100);
        $this->assertFalse($entity->canJoin());
    }

    public function testEnable(): void
    {
        $entity = $this->makeEntity('pending', false);
        $entity->setTotalQuantity(100);
        $entity->enable();
        $this->assertTrue($entity->getIsEnabled());
    }

    public function testDisable(): void
    {
        $entity = $this->makeEntity('active', true);
        $entity->disable();
        $this->assertFalse($entity->getIsEnabled());
    }

    public function testToArray(): void
    {
        $entity = $this->makeEntity();
        $arr = $entity->toArray();
        $this->assertSame('团购活动', $arr['title']);
        $this->assertSame(10000, $arr['original_price']);
        $this->assertSame(8000, $arr['group_price']);
    }
}
