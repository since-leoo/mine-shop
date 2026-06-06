<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\MemberSetting;
use App\Domain\Member\Entity\MemberWalletEntity;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Domain\Member\Service\DomainMemberPointsExpireService;
use App\Domain\Member\Service\DomainMemberWalletService;
use App\Infrastructure\Model\Member\MemberWalletTransaction;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DomainMemberPointsExpireServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
    }

    public function testExpireDuePointsSkipsWhenConfigDisabled(): void
    {
        $transactionRepository = $this->createMock(MemberWalletTransactionRepository::class);
        $transactionRepository->expects(self::never())->method('findExpirablePointGrantTransactions');

        $service = new DomainMemberPointsExpireService(
            $this->createMock(DomainMemberWalletService::class),
            $this->makeSettings(0),
            $transactionRepository,
        );

        self::assertSame(['members' => 0, 'points' => 0, 'transactions' => 0], $service->expireDuePoints());
    }

    public function testExpireDuePointsAggregatesDueSourceTransactionsAndRecordsEachSource(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-06 10:00:00'));

        $sourceA = $this->makeTransaction(11, 1, 100, 'register', '2024-05-01 00:00:00');
        $sourceB = $this->makeTransaction(12, 1, 50, 'purchase_reward', '2024-05-02 00:00:00');

        $wallet = $this->makeWallet(1, 200);
        $walletService = $this->createMock(DomainMemberWalletService::class);
        $walletService->expects(self::once())->method('getEntity')->with(1, 'points')->willReturn($wallet);
        $walletService->expects(self::once())->method('saveEntity')->with($wallet);

        $transactionRepository = $this->createMock(MemberWalletTransactionRepository::class);
        $transactionRepository
            ->expects(self::once())
            ->method('findExpirablePointGrantTransactions')
            ->with(self::callback(static fn (Carbon $cutoff): bool => $cutoff->equalTo(Carbon::parse('2024-06-06 10:00:00'))))
            ->willReturn(new Collection([$sourceA, $sourceB]));
        $transactionRepository
            ->method('findExpiredPointSourceTransactionIds')
            ->with([11, 12])
            ->willReturn([]);

        $created = [];
        $transactionRepository
            ->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(static function (array $payload) use (&$created): MemberWalletTransaction {
                $created[] = $payload;
                return self::makeEmptyTransaction();
            });

        $service = new DomainMemberPointsExpireService(
            $walletService,
            $this->makeSettings(24),
            $transactionRepository,
        );

        self::assertSame(['members' => 1, 'points' => 150, 'transactions' => 2], $service->expireDuePoints());
        self::assertSame(50, $wallet->getBalance());
        self::assertSame([11, 12], array_column($created, 'related_id'));
        self::assertSame(['adjust_out', 'adjust_out'], array_column($created, 'type'));
        self::assertSame([100, 50], array_column($created, 'amount'));
        self::assertSame([200, 100], array_column($created, 'balance_before'));
        self::assertSame([100, 50], array_column($created, 'balance_after'));
        self::assertSame(['points_expire', 'points_expire'], array_column($created, 'source'));
        self::assertSame(['wallet_transaction', 'wallet_transaction'], array_column($created, 'related_type'));
    }

    public function testExpireDuePointsIsIdempotentForAlreadyExpiredSourceAmounts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-06 10:00:00'));

        $sourceA = $this->makeTransaction(11, 1, 100, 'register', '2024-05-01 00:00:00');
        $sourceB = $this->makeTransaction(12, 1, 50, 'purchase_reward', '2024-05-02 00:00:00');

        $wallet = $this->makeWallet(1, 200);
        $walletService = $this->createMock(DomainMemberWalletService::class);
        $walletService->expects(self::once())->method('getEntity')->with(1, 'points')->willReturn($wallet);

        $transactionRepository = $this->createMock(MemberWalletTransactionRepository::class);
        $transactionRepository
            ->method('findExpirablePointGrantTransactions')
            ->willReturn(new Collection([$sourceA, $sourceB]));
        $transactionRepository
            ->method('findExpiredPointSourceTransactionIds')
            ->with([11, 12])
            ->willReturn([11]);

        $created = [];
        $transactionRepository
            ->expects(self::once())
            ->method('create')
            ->willReturnCallback(static function (array $payload) use (&$created): MemberWalletTransaction {
                $created[] = $payload;
                return self::makeEmptyTransaction();
            });

        $service = new DomainMemberPointsExpireService(
            $walletService,
            $this->makeSettings(24),
            $transactionRepository,
        );

        self::assertSame(['members' => 1, 'points' => 50, 'transactions' => 1], $service->expireDuePoints());
        self::assertSame(150, $wallet->getBalance());
        self::assertSame(12, $created[0]['related_id']);
        self::assertSame('adjust_out', $created[0]['type']);
        self::assertSame(50, $created[0]['amount']);
    }

    public function testExpireDuePointsCapsDeductionAtCurrentBalance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-06 10:00:00'));

        $sourceA = $this->makeTransaction(11, 1, 100, 'register', '2024-05-01 00:00:00');
        $sourceB = $this->makeTransaction(12, 1, 50, 'purchase_reward', '2024-05-02 00:00:00');

        $wallet = $this->makeWallet(1, 80);
        $walletService = $this->createMock(DomainMemberWalletService::class);
        $walletService->expects(self::once())->method('getEntity')->with(1, 'points')->willReturn($wallet);
        $walletService->expects(self::once())->method('saveEntity')->with($wallet);

        $transactionRepository = $this->createMock(MemberWalletTransactionRepository::class);
        $transactionRepository
            ->method('findExpirablePointGrantTransactions')
            ->willReturn(new Collection([$sourceA, $sourceB]));
        $transactionRepository
            ->method('findExpiredPointSourceTransactionIds')
            ->with([11, 12])
            ->willReturn([]);

        $created = [];
        $transactionRepository
            ->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(static function (array $payload) use (&$created): MemberWalletTransaction {
                $created[] = $payload;
                return self::makeEmptyTransaction();
            });

        $service = new DomainMemberPointsExpireService(
            $walletService,
            $this->makeSettings(24),
            $transactionRepository,
        );

        self::assertSame(['members' => 1, 'points' => 80, 'transactions' => 2], $service->expireDuePoints());
        self::assertSame(0, $wallet->getBalance());
        self::assertSame([80, 0], array_column($created, 'amount'));
        self::assertSame([80, 0], array_column($created, 'balance_before'));
        self::assertSame([0, 0], array_column($created, 'balance_after'));
    }

    public function testExpireDuePointsDoesNotChasePreviouslyAttemptedSourceAfterBalanceReplenished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-06 10:00:00'));

        $source = $this->makeTransaction(11, 1, 100, 'register', '2024-05-01 00:00:00');

        $walletService = $this->createMock(DomainMemberWalletService::class);
        $walletService->expects(self::never())->method('getEntity');

        $transactionRepository = $this->createMock(MemberWalletTransactionRepository::class);
        $transactionRepository
            ->method('findExpirablePointGrantTransactions')
            ->willReturn(new Collection([$source]));
        $transactionRepository
            ->method('findExpiredPointSourceTransactionIds')
            ->with([11])
            ->willReturn([11]);
        $transactionRepository->expects(self::never())->method('create');

        $service = new DomainMemberPointsExpireService(
            $walletService,
            $this->makeSettings(24),
            $transactionRepository,
        );

        self::assertSame(['members' => 0, 'points' => 0, 'transactions' => 0], $service->expireDuePoints());
    }

    private function makeSettings(int $pointsExpireMonths): DomainMallSettingService
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('member')->willReturn(new MemberSetting(
            enableGrowth: true,
            registerPoints: 100,
            signInReward: 5,
            inviteReward: 50,
            pointsExpireMonths: $pointsExpireMonths,
            vipLevels: [],
            defaultLevel: 1,
            pointsRatio: 100,
        ));

        return $settings;
    }

    private function makeWallet(int $memberId, int $balance): MemberWalletEntity
    {
        $wallet = new MemberWalletEntity();
        $wallet->setId(100 + $memberId);
        $wallet->setMemberId($memberId);
        $wallet->setType('points');
        $wallet->setBalance($balance);

        return $wallet;
    }

    private function makeTransaction(
        int $id,
        int $memberId,
        int $amount,
        string $source,
        string $createdAt
    ): MemberWalletTransaction {
        $transaction = self::makeEmptyTransaction();
        $transaction->setRawAttributes([
            'id' => $id,
            'wallet_id' => 100 + $memberId,
            'member_id' => $memberId,
            'wallet_type' => 'points',
            'transaction_no' => 'TXN' . $id,
            'type' => 'adjust_in',
            'amount' => $amount,
            'balance_before' => 0,
            'balance_after' => $amount,
            'source' => $source,
            'related_type' => null,
            'related_id' => null,
            'description' => '',
            'remark' => '',
            'operator_type' => 'system',
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ], true);

        return $transaction;
    }

    private static function makeEmptyTransaction(): MemberWalletTransaction
    {
        return new class extends MemberWalletTransaction {
            public function __construct() {}
        };
    }
}
