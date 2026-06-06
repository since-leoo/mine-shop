<?php

declare(strict_types=1);

namespace App\Domain\Member\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Infrastructure\Model\Member\MemberWalletTransaction;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Stringable\Str;

final class DomainMemberPointsExpireService
{
    private const SOURCE = 'points_expire';

    public function __construct(
        private readonly DomainMemberWalletService $walletService,
        private readonly DomainMallSettingService $mallSettingService,
        private readonly MemberWalletTransactionRepository $transactionRepository,
    ) {}

    /**
     * @return array{members:int,points:int,transactions:int}
     */
    public function expireDuePoints(?Carbon $now = null): array
    {
        $expireMonths = $this->mallSettingService->member()->pointsExpireMonths();
        if ($expireMonths <= 0) {
            return ['members' => 0, 'points' => 0, 'transactions' => 0];
        }

        $cutoff = ($now ?? Carbon::now())->copy()->subMonthsNoOverflow($expireMonths);
        $sources = $this->transactionRepository->findExpirablePointGrantTransactions($cutoff);
        if ($sources->isEmpty()) {
            return ['members' => 0, 'points' => 0, 'transactions' => 0];
        }

        $sourceIds = $sources->map(static fn (MemberWalletTransaction $transaction): int => (int) $transaction->id)->all();
        $processedSourceIds = $this->transactionRepository->findExpiredPointSourceTransactionIds($sourceIds);
        $remainingSources = $this->buildRemainingSources($sources, $processedSourceIds);

        if ($remainingSources === []) {
            return ['members' => 0, 'points' => 0, 'transactions' => 0];
        }

        return $this->deductRemainingSources($remainingSources);
    }

    /**
     * @param Collection<int, MemberWalletTransaction> $sources
     * @param array<int, int> $processedSourceIds
     * @return array<int, array<int, array{transaction:MemberWalletTransaction,remaining:int}>>
     */
    private function buildRemainingSources(Collection $sources, array $processedSourceIds): array
    {
        $remainingSources = [];
        $processed = array_flip($processedSourceIds);

        foreach ($sources as $source) {
            $sourceId = (int) $source->id;
            if (isset($processed[$sourceId]) || (int) $source->amount <= 0) {
                continue;
            }

            $remainingSources[(int) $source->member_id][] = [
                'transaction' => $source,
                'remaining' => (int) $source->amount,
            ];
        }

        return $remainingSources;
    }

    /**
     * @param array<int, array<int, array{transaction:MemberWalletTransaction,remaining:int}>> $remainingSources
     * @return array{members:int,points:int,transactions:int}
     */
    private function deductRemainingSources(array $remainingSources): array
    {
        $memberCount = 0;
        $expiredPoints = 0;
        $transactionCount = 0;

        foreach ($remainingSources as $memberId => $memberSources) {
            $wallet = $this->walletService->getEntity($memberId, 'points');
            $available = $wallet->getBalance();
            $deductedForMember = false;
            foreach ($memberSources as $memberSource) {
                $balanceBefore = $wallet->getBalance();
                $amount = min($memberSource['remaining'], $available);
                $available -= $amount;

                if ($amount <= 0) {
                    $this->createExpireTransaction(
                        source: $memberSource['transaction'],
                        walletId: $wallet->getId(),
                        amount: 0,
                        balanceBefore: $balanceBefore,
                        balanceAfter: $balanceBefore,
                    );
                    ++$transactionCount;
                    continue;
                }

                $actualDeduction = $wallet->deductSafe($amount, self::SOURCE, 'points expired');
                $this->createExpireTransaction(
                    source: $memberSource['transaction'],
                    walletId: $wallet->getId(),
                    amount: $actualDeduction,
                    balanceBefore: $wallet->getBeforeBalance(),
                    balanceAfter: $wallet->getAfterBalance(),
                );
                ++$transactionCount;

                $expiredPoints += $actualDeduction;
                $deductedForMember = $deductedForMember || $actualDeduction > 0;
            }

            if ($deductedForMember) {
                $this->walletService->saveEntity($wallet);
                ++$memberCount;
            }
        }

        return ['members' => $memberCount, 'points' => $expiredPoints, 'transactions' => $transactionCount];
    }

    private function createExpireTransaction(
        MemberWalletTransaction $source,
        ?int $walletId,
        int $amount,
        int $balanceBefore,
        int $balanceAfter
    ): void {
        $this->transactionRepository->create([
            'wallet_id' => $walletId,
            'member_id' => (int) $source->member_id,
            'wallet_type' => 'points',
            'transaction_no' => Str::upper(Str::random(24)),
            'type' => 'adjust_out',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'source' => self::SOURCE,
            'related_type' => 'wallet_transaction',
            'related_id' => (int) $source->id,
            'description' => 'points expired',
            'remark' => 'points expired',
            'operator_type' => 'system',
            'operator_id' => null,
            'operator_name' => null,
        ]);
    }
}
