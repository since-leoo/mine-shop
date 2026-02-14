<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\MemberSetting;
use App\Domain\Member\Entity\MemberWalletEntity;
use App\Domain\Member\Event\MemberBalanceAdjusted;
use App\Domain\Member\Repository\MemberLevelRepository;
use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Domain\Member\Service\DomainMemberPointsService;
use App\Domain\Member\Service\DomainMemberWalletService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Model\Member\MemberLevel;
use PHPUnit\Framework\TestCase;

class DomainMemberPointsServiceTest extends TestCase
{
    private DomainMemberWalletService $walletService;
    private DomainMallSettingService $mallSettingService;
    private MemberRepository $memberRepository;
    private MemberLevelRepository $levelRepository;
    private MemberWalletTransactionRepository $transactionRepository;
    private DomainMemberPointsService $service;

    protected function setUp(): void
    {
        $this->walletService = $this->createMock(DomainMemberWalletService::class);
        $this->mallSettingService = $this->createMock(DomainMallSettingService::class);
        $this->memberRepository = $this->createMock(MemberRepository::class);
        $this->levelRepository = $this->createMock(MemberLevelRepository::class);
        $this->transactionRepository = $this->createMock(MemberWalletTransactionRepository::class);

        $this->service = new DomainMemberPointsService(
            $this->walletService,
            $this->mallSettingService,
            $this->memberRepository,
            $this->levelRepository,
            $this->transactionRepository,
        );
    }

    private function makeMemberSetting(int $registerPoints = 100, int $pointsRatio = 100): MemberSetting
    {
        return new MemberSetting(
            enableGrowth: true,
            registerPoints: $registerPoints,
            signInReward: 5,
            inviteReward: 50,
            pointsExpireMonths: 24,
            vipLevels: [],
            defaultLevel: 1,
            pointsRatio: $pointsRatio,
        );
    }

    private function makeMemberMock(int $id, ?int $levelId = null): Member
    {
        $member = $this->getMockBuilder(Member::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $attrs = ['id' => $id, 'level_id' => $levelId, 'level' => 'VIP1'];
        $member->method('getAttribute')->willReturnCallback(fn (string $key) => $attrs[$key] ?? null);
        return $member;
    }

    private function makeLevelMock(int $id, float $pointRate): MemberLevel
    {
        $level = $this->getMockBuilder(MemberLevel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $attrs = ['id' => $id, 'point_rate' => $pointRate];
        $level->method('getAttribute')->willReturnCallback(fn (string $key) => $attrs[$key] ?? null);
        return $level;
    }

    private function makeWalletEntity(int $memberId, int $balance = 0): MemberWalletEntity
    {
        $entity = new MemberWalletEntity();
        $entity->setMemberId($memberId);
        $entity->setType('points');
        $entity->setBalance($balance);
        $entity->setId(1);
        return $entity;
    }

    // --- calculatePurchasePoints ---

    public function testCalculatePurchasePointsBasicFormula(): void
    {
        $this->mallSettingService->method('member')->willReturn($this->makeMemberSetting(100, 100));
        $this->assertSame(10000, $this->service->calculatePurchasePoints(10000, 1.0));
    }

    public function testCalculatePurchasePointsWithHigherPointRate(): void
    {
        $this->mallSettingService->method('member')->willReturn($this->makeMemberSetting(100, 100));
        $this->assertSame(15000, $this->service->calculatePurchasePoints(10000, 1.5));
    }

    public function testCalculatePurchasePointsFloorsDown(): void
    {
        $this->mallSettingService->method('member')->willReturn($this->makeMemberSetting(100, 100));
        $this->assertSame(999, $this->service->calculatePurchasePoints(999, 1.0));
    }

    public function testCalculatePurchasePointsZeroAmount(): void
    {
        $this->mallSettingService->method('member')->willReturn($this->makeMemberSetting(100, 100));
        $this->assertSame(0, $this->service->calculatePurchasePoints(0, 1.0));
    }

    // --- grantRegisterPoints ---

    public function testGrantRegisterPointsReturnsEvent(): void
    {
        $this->mallSettingService->method('member')->willReturn($this->makeMemberSetting(100));
        $this->memberRepository->method('findById')->with(1)->willReturn($this->makeMemberMock(1));
        $this->transactionRepository->method('existsByMemberAndSource')->willReturn(false);

        $walletEntity = $this->makeWalletEntity(1, 0);
        $this->walletService->expects($this->once())->method('getEntity')->with(1, 'points')->willReturn($walletEntity);
        $this->walletService->expects($this->once())->method('saveEntity');

        $event = $this->service->grantRegisterPoints(1);

        $this->assertInstanceOf(MemberBalanceAdjusted::class, $event);
        $this->assertSame(1, $event->memberId);
        $this->assertSame('points', $event->walletType);
        $this->assertSame(100, (int) $event->changeAmount);
        $this->assertSame('register', $event->source);
    }

    public function testGrantRegisterPointsReturnsNullWhenConfigIsZero(): void
    {
        $this->mallSettingService->method('member')->willReturn($this->makeMemberSetting(0));
        $this->walletService->expects($this->never())->method('getEntity');
        $this->assertNull($this->service->grantRegisterPoints(1));
    }

    public function testGrantRegisterPointsReturnsNullWhenAlreadyGranted(): void
    {
        $this->mallSettingService->method('member')->willReturn($this->makeMemberSetting(100));
        $this->memberRepository->method('findById')->willReturn($this->makeMemberMock(1));
        $this->transactionRepository->method('existsByMemberAndSource')->willReturn(true);

        $this->walletService->expects($this->never())->method('getEntity');
        $this->assertNull($this->service->grantRegisterPoints(1));
    }

    public function testGrantRegisterPointsThrowsWhenMemberNotFound(): void
    {
        $this->mallSettingService->method('member')->willReturn($this->makeMemberSetting(100));
        $this->memberRepository->method('findById')->willReturn(null);

        $this->expectException(BusinessException::class);
        $this->service->grantRegisterPoints(999);
    }

    // --- grantPurchasePoints ---

    public function testGrantPurchasePointsReturnsEventWithLevel(): void
    {
        $member = $this->makeMemberMock(1, 2);
        $level = $this->makeLevelMock(2, 1.5);

        $this->memberRepository->method('findById')->with(1)->willReturn($member);
        $this->levelRepository->method('findById')->with(2)->willReturn($level);
        $this->mallSettingService->method('member')->willReturn($this->makeMemberSetting(100, 100));

        $walletEntity = $this->makeWalletEntity(1, 500);
        $this->walletService->expects($this->once())->method('getEntity')->with(1, 'points')->willReturn($walletEntity);
        $this->walletService->expects($this->once())->method('saveEntity');

        $event = $this->service->grantPurchasePoints(1, 10000, 'ORD001');

        $this->assertInstanceOf(MemberBalanceAdjusted::class, $event);
        $this->assertSame(15000, (int) $event->changeAmount);
        $this->assertSame('purchase_reward', $event->source);
    }

    public function testGrantPurchasePointsReturnsNullWhenResultIsZero(): void
    {
        $member = $this->makeMemberMock(1, null);
        $this->memberRepository->method('findById')->willReturn($member);
        $this->mallSettingService->method('member')->willReturn($this->makeMemberSetting(100, 0));

        $this->walletService->expects($this->never())->method('getEntity');
        $this->assertNull($this->service->grantPurchasePoints(1, 10000, 'ORD003'));
    }

    public function testGrantPurchasePointsThrowsWhenMemberNotFound(): void
    {
        $this->memberRepository->method('findById')->willReturn(null);
        $this->expectException(BusinessException::class);
        $this->service->grantPurchasePoints(999, 10000, 'ORD004');
    }

    // --- deductPurchasePoints ---

    public function testDeductPurchasePointsReturnsEventForFullDeduction(): void
    {
        $this->memberRepository->method('findById')->with(1)->willReturn($this->makeMemberMock(1));

        $walletEntity = $this->makeWalletEntity(1, 500);
        $this->walletService->expects($this->once())->method('getEntity')->with(1, 'points')->willReturn($walletEntity);
        $this->walletService->expects($this->once())->method('saveEntity');

        $event = $this->service->deductPurchasePoints(1, 200, 'ORD005');

        $this->assertInstanceOf(MemberBalanceAdjusted::class, $event);
        $this->assertSame(-200, (int) $event->changeAmount);
        $this->assertSame('purchase_refund', $event->source);
    }

    public function testDeductPurchasePointsCapsAtBalance(): void
    {
        $this->memberRepository->method('findById')->willReturn($this->makeMemberMock(1));

        $walletEntity = $this->makeWalletEntity(1, 50);
        $this->walletService->expects($this->once())->method('getEntity')->willReturn($walletEntity);
        $this->walletService->expects($this->once())->method('saveEntity');

        $event = $this->service->deductPurchasePoints(1, 200, 'ORD006');

        $this->assertSame(-50, (int) $event->changeAmount);
    }

    public function testDeductPurchasePointsReturnsNullWhenBalanceIsZero(): void
    {
        $this->memberRepository->method('findById')->willReturn($this->makeMemberMock(1));

        $walletEntity = $this->makeWalletEntity(1, 0);
        $this->walletService->method('getEntity')->willReturn($walletEntity);
        $this->walletService->expects($this->never())->method('saveEntity');

        $this->assertNull($this->service->deductPurchasePoints(1, 100, 'ORD007'));
    }

    public function testDeductPurchasePointsReturnsNullWhenAmountIsZero(): void
    {
        $this->memberRepository->expects($this->never())->method('findById');
        $this->assertNull($this->service->deductPurchasePoints(1, 0, 'ORD008'));
    }

    public function testDeductPurchasePointsThrowsWhenMemberNotFound(): void
    {
        $this->memberRepository->method('findById')->willReturn(null);
        $this->expectException(BusinessException::class);
        $this->service->deductPurchasePoints(999, 100, 'ORD009');
    }
}
