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

use PHPUnit\Framework\TestCase;
use Plugin\Distribution\Domain\Entity\CommissionAccountEntity;
use Plugin\Distribution\Domain\Mapper\CommissionAccountMapper;

/**
 * @internal
 * @coversNothing
 */
final class CommissionAccountTest extends TestCase
{
    public function testHydrationDoesNotDirtyBalancesOrPersistUnrelatedColumns(): void
    {
        $account = $this->account(['member_id' => '7', 'pending_amount' => '120', 'updated_at' => '2026-10-16', 'id' => 42]);
        self::assertSame([], $account->toArray());
        self::assertSame(7, $account->state()['member_id']);
        self::assertSame(120, $account->state()['pending_amount']);
        self::assertArrayNotHasKey('updated_at', $account->state());
        $account->pending(30);
        self::assertSame(['pending_amount' => 150], $account->toArray());
    }

    public function testWithdrawnRefundDebtIsOffsetBeforeNewCommissionBecomesWithdrawable(): void
    {
        $account = $this->account();
        $account->pending(1001);
        self::assertSame(1001, $this->net($account));
        $account->settle(1001);
        $account->freeze(800);
        $account->pay(800);
        self::assertSame(1001, $this->net($account));
        $account->reverse(500, true);
        self::assertSame(0, $account->state()['available_amount']);
        self::assertSame(299, $account->state()['debt_amount']);
        self::assertSame(501, $this->net($account));
        $account = CommissionAccountMapper::fromModel($account->state());
        self::assertSame([], $account->toArray());
        $account->pending(500);
        $account->settle(500);
        self::assertSame(0, $account->state()['debt_amount']);
        self::assertSame(201, $account->state()['available_amount']);
        self::assertSame(800, $account->state()['withdrawn_amount']);
        self::assertSame(1001, $this->net($account));
    }

    public function testPendingRefundDoesNotTouchAvailableMoney(): void
    {
        $account = $this->account(['pending_amount' => 100, 'available_amount' => 200]);
        $account->reverse(30, false);
        self::assertSame(70, $account->state()['pending_amount']);
        self::assertSame(200, $account->state()['available_amount']);
        $account->settle(70);
        self::assertSame(270, $account->state()['available_amount']);
        self::assertSame(0, $account->state()['pending_amount']);
    }

    public function testRefundDuringWithdrawalFreezeBlocksPaymentAndReleasedFundsRepayDebt(): void
    {
        $account = $this->account(['available_amount' => 1000]);
        $account->freeze(1000);
        $account->reverse(400, true);
        self::assertSame(600, $this->net($account));
        $before = $account->state();
        try {
            $account->pay(1000);
            self::fail('Debt must block payment of frozen money');
        } catch (\DomainException) {
            self::assertSame($before, $account->state());
        }
        $account->release(1000);
        self::assertSame(600, $account->state()['available_amount']);
        self::assertSame(0, $account->state()['debt_amount']);
        self::assertSame(0, $account->state()['frozen_amount']);
        $account->freeze(600);
        $account->pay(600);
        self::assertSame(600, $account->state()['withdrawn_amount']);
        self::assertSame(600, $this->net($account));
    }

    public function testInsufficientFundsNeverPartiallyMutateTheAccount(): void
    {
        foreach (['settle', 'freeze', 'release', 'pay'] as $method) {
            $account = $this->account(['pending_amount' => 10, 'available_amount' => 10, 'frozen_amount' => 10]);
            $before = $account->state();
            try {
                $account->{$method}(11);
                self::fail($method . ' must reject insufficient balance');
            } catch (\DomainException) {
                self::assertSame($before, $account->state());
                self::assertSame([], $account->toArray());
            }
        }
    }

    public function testNegativeAmountsCannotIncreaseWithdrawableFunds(): void
    {
        foreach (['pending', 'settle', 'freeze', 'release', 'pay'] as $method) {
            $account = $this->account();
            try {
                $account->{$method}(-1);
                self::fail($method . ' must reject a negative amount');
            } catch (\DomainException) {
                self::assertSame([], $account->toArray());
            }
        }
    }

    private function account(array $balances = []): CommissionAccountEntity
    {
        return CommissionAccountMapper::fromModel($balances + [
            'member_id' => 7, 'pending_amount' => 0, 'available_amount' => 0,
            'frozen_amount' => 0, 'withdrawn_amount' => 0, 'debt_amount' => 0,
        ]);
    }

    private function net(CommissionAccountEntity $account): int
    {
        $s = $account->state();
        return $s['pending_amount'] + $s['available_amount'] + $s['frozen_amount'] + $s['withdrawn_amount'] - $s['debt_amount'];
    }
}
