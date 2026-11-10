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

namespace HyperfTests\Unit\Distribution;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use PHPUnit\Framework\TestCase;
use Plugin\Distribution\Domain\Entity\CommissionAccountEntity;
use Plugin\Distribution\Domain\Repository\DistributionRepository;
use Plugin\Distribution\Domain\Service\DomainCommissionService;

/**
 * @internal
 * @coversNothing
 */
final class CommissionReplayTest extends TestCase
{
    public function testRefundAndCompletionBeforePaymentDeliveryAreRecoveredWithoutDuplicateMoney(): void
    {
        $state = (object) [
            'refund' => 500,
            'commissions' => [],
            'ledger' => [],
            'account' => [
                'member_id' => 1, 'pending_amount' => 0, 'available_amount' => 0,
                'frozen_amount' => 0, 'withdrawn_amount' => 0, 'debt_amount' => 0,
            ],
            'snapshot' => [
                'buyer_id' => 2, 'member_id' => 1, 'paid_at' => null, 'completed_at' => '2026-01-02 12:00:00',
                'snapshot' => [
                    'commission_mode' => 'first_order', 'settlement_days' => 0,
                    'lines' => [['product_id' => 9, 'sku_id' => 99, 'base_amount' => 1000, 'amount' => 101]],
                ],
            ],
        ];
        $repository = $this->createMock(DistributionRepository::class);
        $repository->method('snapshot')->willReturnCallback(static fn () => $state->snapshot);
        $repository->method('member')->willReturn(['id' => 2, 'status' => 'active']);
        $repository->method('order')->willReturn([
            'id' => 11, 'member_id' => 2, 'pay_time' => '2026-01-01 12:00:00',
            'status' => 'completed', 'pay_amount' => 1000,
        ]);
        $repository->method('claimFirst')->willReturn(true);
        $repository->method('items')->willReturn([['id' => 21, 'product_id' => 9, 'sku_id' => 99]]);
        $repository->method('account')->willReturnCallback(static fn () => $state->account);
        $repository->method('commissionsForOrder')->willReturnCallback(static fn () => array_values($state->commissions));
        $repository->method('hasOpenAfterSale')->willReturn(false);
        $repository->method('totalRefund')->willReturnCallback(static fn () => $state->refund);
        $repository->method('refundedGoods')->willReturnCallback(static fn () => $state->refund);
        $repository->method('now')->willReturn('2026-01-03 12:00:00');
        $repository->method('createCommission')->willReturnCallback(static function (array $row) use ($state): int {
            $id = 100 + \count($state->commissions);
            $state->commissions[$id] = $row + ['id' => $id, 'refunded_amount' => 0, 'status' => 'pending'];
            return $id;
        });
        $repository->method('markPaid')->willReturnCallback(static function () use ($state): void {
            $state->snapshot['paid_at'] = '2026-01-03 12:00:00';
        });
        $repository->method('saveAccount')->willReturnCallback(static function (CommissionAccountEntity $account, string $key, string $type, int $amount) use ($state): void {
            self::assertArrayNotHasKey($key, $state->ledger, 'A replay must not attempt the same financial entry twice');
            $state->account = $account->state();
            $state->ledger[$key] = ['type' => $type, 'amount' => $amount];
        });
        $repository->method('updateCommission')->willReturnCallback(static function (int $id, array $changes) use ($state): void {
            $state->commissions[$id] = array_replace($state->commissions[$id], $changes);
        });
        $service = new DomainCommissionService($repository, $this->createMock(DomainMallSettingService::class));

        // External state is already refunded/completed, while paid outbox delivery was delayed.
        $service->refund(11);
        $service->settle(11);
        self::assertSame([], $state->ledger);
        $service->paid(11);
        $service->paid(11);
        $service->refund(11);
        self::assertSame(50, $state->account['pending_amount']);
        self::assertSame(51, $state->commissions[100]['refunded_amount']);
        $service->settle(11);
        $service->settle(11);
        self::assertSame(0, $state->account['pending_amount']);
        self::assertSame(50, $state->account['available_amount']);
        self::assertSame([101, -51, 50], array_column($state->ledger, 'amount'));

        // A later final refund removes the rounding remainder, once only.
        $state->refund = 1000;
        $service->refund(11);
        $service->refund(11);
        self::assertSame(101, $state->commissions[100]['refunded_amount']);
        self::assertSame(0, $state->account['available_amount']);
        self::assertSame(0, $state->account['debt_amount']);
        self::assertSame([101, -51, 50, -50], array_column($state->ledger, 'amount'));
    }
}
