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

namespace HyperfTests\Unit\SupplyChain;

use PHPUnit\Framework\TestCase;
use Plugin\SupplyChain\Domain\Mapper\SupplyAfterSaleMapper;
use Plugin\SupplyChain\Domain\ValueObject\AfterSaleSubmissionVo;

/**
 * @internal @coversNothing
 * @coversNothing
 */
final class AfterSaleLifecycleTest extends TestCase
{
    public function testPartialRefundCannotConsumeWholeProcurementLineBudget(): void
    {
        $submission = $this->submission(['quantity' => 1, 'amount_cents' => 101]);
        $this->expectException(\DomainException::class);
        $submission->assertLimits(['quantity' => 2, 'apply_amount' => 1000], $this->line(), [], 20);
    }

    public function testLastRemainingQuantityCanConsumeRoundingResidue(): void
    {
        $submission = $this->submission(['quantity' => 1, 'amount_cents' => 101]);
        $submission->assertLimits(['quantity' => 1, 'apply_amount' => 101], $this->line(), ['quantity' => 1, 'amount_cents' => 100], 20);
        self::assertSame(101, $submission->toCommand()->amountCents);
    }

    public function testCustomerBudgetIncludesFreightAndTechnicalFee(): void
    {
        $submission = $this->submission(['quantity' => 2, 'amount_cents' => 200, 'freight_cents' => 20, 'technical_service_fee_cents' => 10]);
        $this->expectException(\DomainException::class);
        $submission->assertLimits(['quantity' => 2, 'apply_amount' => 229], $this->line(), [], 20);
    }

    public function testUnknownRefundEnumIsRejectedInsteadOfGuessed(): void
    {
        $this->expectException(\DomainException::class);
        $this->submission()->assertTypes([1 => '退货退款']);
    }

    public function testMapperIsCleanAndUnknownCreateCannotBeResubmitted(): void
    {
        $entity = SupplyAfterSaleMapper::getNewEntity();
        $entity->create(1, 2, 3, ['status' => 'pending_review']);
        $entity = SupplyAfterSaleMapper::fromModel($entity->snapshot());
        self::assertSame([], $entity->toArray());
        self::assertSame(1, $entity->queueSubmission($this->submission(), $this->line(), [0 => '仅退款']));
        $entity->begin('after_sale_create', 1);
        $entity->failed('after_sale_create', 'timeout', true, false);
        self::assertSame('unknown', $entity->snapshot()['status']);
        $this->expectException(\DomainException::class);
        $entity->queueSubmission($this->submission(), $this->line(), [0 => '仅退款']);
    }

    public function testQueryByItemRecordsCandidateWithoutClaimingAnOlderRefund(): void
    {
        $entity = SupplyAfterSaleMapper::getNewEntity();
        $entity->create(1, 2, 3, []);
        $entity->queueSubmission($this->submission(), $this->line(), [0 => '仅退款']);
        $entity->begin('after_sale_create', 1);
        $entity->failed('after_sale_create', 'timeout', true, false);
        $entity->succeeded('after_sale_query', ['external_after_sale_id' => 'old-remote-id']);
        self::assertNull($entity->snapshot()['external_after_sale_id']);
        self::assertSame('unknown', $entity->snapshot()['status']);
        self::assertSame('old-remote-id', $entity->snapshot()['snapshot']['candidate_external_after_sale_id']);
        self::assertFalse($entity->queueAction('after_sale_cancel'));
    }

    public function testCustomerCancellationBeforeDispatchPreventsSubmission(): void
    {
        $entity = SupplyAfterSaleMapper::getNewEntity();
        $entity->create(1, 2, 3, []);
        $entity->queueSubmission($this->submission(), $this->line(), [0 => '仅退款']);
        $entity->customerChanged(['status' => 'closed'], 'cancel');
        self::assertSame('closed', $entity->snapshot()['status']);
        $this->expectException(\DomainException::class);
        $entity->begin('after_sale_create', 1);
    }

    public function testInFlightReturnCannotBeOverwrittenByCancellation(): void
    {
        $entity = SupplyAfterSaleMapper::getNewEntity();
        $entity->create(1, 2, 3, []);
        $entity->queueSubmission($this->submission(), $this->line(), [0 => '仅退款']);
        $entity->begin('after_sale_create', 1);
        $entity->succeeded('after_sale_create', 'provider-1');
        self::assertTrue($entity->queueAction('after_sale_return', ['tracking_no' => 'SF1']));
        $entity->begin('after_sale_return', 1);
        $entity->customerChanged(['status' => 'closed'], 'cancel');
        self::assertFalse($entity->queueAction('after_sale_cancel'));
        $entity->succeeded('after_sale_return', null);
        self::assertTrue($entity->queueAction('after_sale_cancel'));
        self::assertSame('cancel_queued', $entity->snapshot()['status']);
    }

    private function submission(array $overrides = []): AfterSaleSubmissionVo
    {
        return new AfterSaleSubmissionVo(array_replace(['external_order_item_id' => '33', 'merchant_order_no' => 'SC1',
            'quantity' => 1, 'amount_cents' => 100, 'freight_cents' => 0, 'technical_service_fee_cents' => 0,
            'reason_type' => 1, 'reason' => '申请退款', 'received_status' => 0, 'refund_type' => 0, 'refund_way' => 1], $overrides));
    }

    private function line(): array
    {
        return ['quantity' => 2, 'amount_cents' => 201, 'technical_service_fee_cents' => 10];
    }
}
