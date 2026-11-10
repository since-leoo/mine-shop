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
use Plugin\Distribution\Domain\Mapper\WithdrawalMapper;
use Plugin\Distribution\Domain\ValueObject\DistributionPolicyVo;
use Plugin\Distribution\Interface\Dto\WithdrawalDto;
use Plugin\Distribution\Interface\Dto\WithdrawalReviewDto;

/**
 * @internal
 * @coversNothing
 */
final class WithdrawalLifecycleTest extends TestCase
{
    public function testCreationFreezesTheGrossAmountWhileFeeIsRoundedDownInCents(): void
    {
        $policy = DistributionPolicyVo::validate(['withdrawal_min_amount' => 100, 'withdrawal_fee_bps' => 125]);
        $entity = WithdrawalMapper::getNewEntity();
        $entity->create(new WithdrawalDto(['amount' => 1001, 'account_name' => '张三', 'account_no' => '62220001', 'request_key' => 'request-12']), 7, $policy);
        $state = $entity->state();
        self::assertSame(1001, $state['amount']);
        self::assertSame(12, $state['fee_amount']);
        self::assertSame(989, $state['net_amount']);
        self::assertSame($state['amount'], $state['fee_amount'] + $state['net_amount']);
        self::assertSame('pending', $state['status']);
        self::assertSame('request-12', $state['request_key']);
        self::assertSame($state, $entity->toArray());
    }

    public function testBelowMinimumIsRejectedWithoutCreatingDirtyState(): void
    {
        $entity = WithdrawalMapper::getNewEntity();
        try {
            $entity->create(new WithdrawalDto(['amount' => 99, 'account_name' => '张三', 'account_no' => '62220001']), 7, DistributionPolicyVo::validate(['withdrawal_min_amount' => 100]));
            self::fail('Below-minimum withdrawal must not be created');
        } catch (\DomainException) {
            self::assertSame([], $entity->state());
            self::assertSame([], $entity->toArray());
        }
    }

    public function testRestoringAndApprovingDoesNotRewritePayoutIdentityOrAmount(): void
    {
        $entity = WithdrawalMapper::fromModel($this->persisted('pending'));
        self::assertSame([], $entity->toArray());
        $entity->review(new WithdrawalReviewDto(['action' => 'approve', 'remark' => '已核对收款资料']), 88);
        self::assertSame(['status' => 'approved', 'remark' => '已核对收款资料', 'operator_id' => 88], $entity->toArray());
        self::assertSame(1001, $entity->state()['amount']);
        self::assertSame('62220001', $entity->state()['account_no']);
    }

    public function testApprovalAndPaymentAreSeparateAndPaymentRequiresReceipt(): void
    {
        $entity = WithdrawalMapper::fromModel($this->persisted('approved'));
        try {
            $entity->review(new WithdrawalReviewDto(['action' => 'pay', 'payment_no' => '   ']), 88);
            self::fail('A payout cannot be marked paid without a receipt');
        } catch (\DomainException) {
            self::assertSame([], $entity->toArray());
            self::assertSame('approved', $entity->state()['status']);
        }
        $entity->review(new WithdrawalReviewDto(['action' => 'pay', 'payment_no' => 'BANK-12', 'remark' => '银行已转账']), 88);
        self::assertSame('paid', $entity->state()['status']);
        self::assertSame('BANK-12', $entity->state()['payment_no']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $entity->state()['paid_at']);
        self::assertArrayNotHasKey('amount', $entity->toArray());
    }

    public function testRejectAndFailRequireAnAuditableReasonAndAllowCancellingApprovedFunds(): void
    {
        foreach (['reject' => 'rejected', 'fail' => 'failed'] as $action => $status) {
            $entity = WithdrawalMapper::fromModel($this->persisted('approved'));
            try {
                $entity->review(new WithdrawalReviewDto(['action' => $action, 'remark' => '']), 88);
                self::fail('Returning frozen funds needs an auditable reason');
            } catch (\DomainException) {
                self::assertSame([], $entity->toArray());
            }
            $entity->review(new WithdrawalReviewDto(['action' => $action, 'remark' => '退款后账户需重新核对']), 88);
            self::assertSame($status, $entity->state()['status']);
            self::assertNull($entity->state()['paid_at']);
        }
    }

    public function testTerminalWithdrawalsCannotBePaidOrReleasedTwice(): void
    {
        foreach (['paid', 'failed', 'rejected'] as $status) {
            foreach (['approve', 'reject', 'pay', 'fail'] as $action) {
                $row = $this->persisted($status);
                $entity = WithdrawalMapper::fromModel($row);
                try {
                    $entity->review(new WithdrawalReviewDto(['action' => $action, 'remark' => 'duplicate', 'payment_no' => 'BANK-12']), 88);
                    self::fail($status . ' must not transition again via ' . $action);
                } catch (\DomainException) {
                    self::assertSame($row, $entity->state());
                    self::assertSame([], $entity->toArray());
                }
            }
        }
    }

    public function testPendingApplicationCannotSkipApprovalOrPretendToHaveFailedPayment(): void
    {
        foreach (['pay', 'fail', 'unknown'] as $action) {
            $entity = WithdrawalMapper::fromModel($this->persisted('pending'));
            try {
                $entity->review(new WithdrawalReviewDto(['action' => $action, 'remark' => 'reason', 'payment_no' => 'BANK-12']), 88);
                self::fail('Pending application must go through review');
            } catch (\DomainException) {
                self::assertSame([], $entity->toArray());
            }
        }
    }

    private function persisted(string $status): array
    {
        return [
            'id' => 12, 'member_id' => 7, 'withdrawal_no' => 'WD12', 'request_key' => 'request-12',
            'amount' => 1001, 'fee_amount' => 12, 'net_amount' => 989, 'channel' => 'manual',
            'account_name' => '张三', 'account_no' => '62220001', 'status' => $status,
            'remark' => '', 'payment_no' => null, 'paid_at' => null, 'created_at' => '2026-10-16 10:00:00',
        ];
    }
}
