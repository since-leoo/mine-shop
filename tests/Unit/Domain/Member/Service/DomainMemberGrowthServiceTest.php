<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Service;

use App\Domain\Member\Event\MemberGrowthChanged;
use App\Domain\Member\Repository\MemberGrowthLogRepository;
use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Member\Service\DomainMemberGrowthService;
use App\Domain\Member\Service\DomainMemberLevelService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Model\Member\MemberLevel;
use PHPUnit\Framework\TestCase;

class DomainMemberGrowthServiceTest extends TestCase
{
    private MemberRepository $memberRepository;
    private MemberGrowthLogRepository $growthLogRepository;
    private DomainMemberLevelService $levelService;
    private DomainMemberGrowthService $service;

    protected function setUp(): void
    {
        $this->memberRepository = $this->createMock(MemberRepository::class);
        $this->growthLogRepository = $this->createMock(MemberGrowthLogRepository::class);
        $this->levelService = $this->createMock(DomainMemberLevelService::class);

        $this->service = new DomainMemberGrowthService(
            $this->memberRepository,
            $this->growthLogRepository,
            $this->levelService,
        );
    }

    private function makeMemberMock(int $id, int $growthValue, ?int $levelId = null, string $level = 'VIP1'): Member
    {
        $member = $this->getMockBuilder(Member::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $attrs = ['id' => $id, 'growth_value' => $growthValue, 'level_id' => $levelId, 'level' => $level];
        $member->method('getAttribute')->willReturnCallback(fn (string $key) => $attrs[$key] ?? null);
        return $member;
    }

    private function makeLevelMock(int $id, string $name, int $levelNumber): MemberLevel
    {
        $level = $this->getMockBuilder(MemberLevel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $attrs = ['id' => $id, 'name' => $name, 'level' => $levelNumber];
        $level->method('getAttribute')->willReturnCallback(fn (string $key) => $attrs[$key] ?? null);
        return $level;
    }

    // --- addGrowthValue ---

    public function testAddGrowthValueReturnsEventWithCorrectData(): void
    {
        $member = $this->makeMemberMock(1, 100);
        $this->memberRepository->method('findById')->with(1)->willReturn($member);
        $this->memberRepository->expects($this->once())->method('updateById')->with(1, ['growth_value' => 150]);
        $this->growthLogRepository->expects($this->once())->method('create')
            ->with($this->callback(fn (array $d) => $d['before_value'] === 100 && $d['after_value'] === 150 && $d['change_amount'] === 50));

        $event = $this->service->addGrowthValue(1, 50, 'order_payment', 'test');

        $this->assertInstanceOf(MemberGrowthChanged::class, $event);
        $this->assertSame(1, $event->memberId);
        $this->assertSame(100, $event->beforeValue);
        $this->assertSame(150, $event->afterValue);
        $this->assertSame(50, $event->changeAmount);
    }

    public function testAddGrowthValueReturnsNullWhenAmountIsZero(): void
    {
        $this->memberRepository->expects($this->never())->method('findById');
        $this->assertNull($this->service->addGrowthValue(1, 0, 'order_payment'));
    }

    public function testAddGrowthValueReturnsNullWhenAmountIsNegative(): void
    {
        $this->memberRepository->expects($this->never())->method('findById');
        $this->assertNull($this->service->addGrowthValue(1, -10, 'order_payment'));
    }

    public function testAddGrowthValueThrowsWhenMemberNotFound(): void
    {
        $this->memberRepository->method('findById')->willReturn(null);
        $this->expectException(BusinessException::class);
        $this->service->addGrowthValue(999, 50, 'order_payment');
    }

    // --- deductGrowthValue ---

    public function testDeductGrowthValueReturnsEventWithCorrectData(): void
    {
        $member = $this->makeMemberMock(1, 200);
        $this->memberRepository->method('findById')->willReturn($member);
        $this->memberRepository->expects($this->once())->method('updateById')->with(1, ['growth_value' => 150]);
        $this->growthLogRepository->expects($this->once())->method('create')
            ->with($this->callback(fn (array $d) => $d['before_value'] === 200 && $d['after_value'] === 150 && $d['change_amount'] === -50));

        $event = $this->service->deductGrowthValue(1, 50, 'order_refund');

        $this->assertInstanceOf(MemberGrowthChanged::class, $event);
        $this->assertSame(-50, $event->changeAmount);
        $this->assertSame(150, $event->afterValue);
    }

    public function testDeductGrowthValueFloorsAtZero(): void
    {
        $member = $this->makeMemberMock(1, 30);
        $this->memberRepository->method('findById')->willReturn($member);
        $this->memberRepository->expects($this->once())->method('updateById')->with(1, ['growth_value' => 0]);

        $event = $this->service->deductGrowthValue(1, 100, 'order_refund');

        $this->assertSame(0, $event->afterValue);
        $this->assertSame(-30, $event->changeAmount);
    }

    public function testDeductGrowthValueReturnsNullWhenCurrentValueIsZero(): void
    {
        $member = $this->makeMemberMock(1, 0);
        $this->memberRepository->method('findById')->willReturn($member);
        $this->memberRepository->expects($this->never())->method('updateById');

        $this->assertNull($this->service->deductGrowthValue(1, 50, 'order_refund'));
    }

    public function testDeductGrowthValueReturnsNullWhenAmountIsZero(): void
    {
        $this->memberRepository->expects($this->never())->method('findById');
        $this->assertNull($this->service->deductGrowthValue(1, 0, 'order_refund'));
    }

    public function testDeductGrowthValueThrowsWhenMemberNotFound(): void
    {
        $this->memberRepository->method('findById')->willReturn(null);
        $this->expectException(BusinessException::class);
        $this->service->deductGrowthValue(999, 50, 'order_refund');
    }

    // --- recalculateLevel ---

    public function testRecalculateLevelUpdatesWhenLevelChanges(): void
    {
        $member = $this->makeMemberMock(1, 500, 1, 'VIP1');
        $newLevel = $this->makeLevelMock(2, 'VIP2', 2);

        $this->memberRepository->method('findById')->willReturn($member);
        $this->levelService->method('matchLevelByGrowthValue')->with(500)->willReturn($newLevel);
        $this->memberRepository->expects($this->once())->method('updateById')->with(1, ['level' => 'VIP2', 'level_id' => 2]);

        $this->service->recalculateLevel(1);
    }

    public function testRecalculateLevelSkipsWhenLevelUnchanged(): void
    {
        $member = $this->makeMemberMock(1, 500, 1, 'VIP1');
        $sameLevel = $this->makeLevelMock(1, 'VIP1', 1);

        $this->memberRepository->method('findById')->willReturn($member);
        $this->levelService->method('matchLevelByGrowthValue')->willReturn($sameLevel);
        $this->memberRepository->expects($this->never())->method('updateById');

        $this->service->recalculateLevel(1);
    }

    public function testRecalculateLevelThrowsWhenMemberNotFound(): void
    {
        $this->memberRepository->method('findById')->willReturn(null);
        $this->expectException(BusinessException::class);
        $this->service->recalculateLevel(999);
    }
}
