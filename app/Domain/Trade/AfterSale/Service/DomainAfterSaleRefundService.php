<?php

declare(strict_types=1);

namespace App\Domain\Trade\AfterSale\Service;

use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Infrastructure\Model\Order\OrderPaymentRefund;

/** 准备可重试的退款申请，不进行网络调用；应用层负责事务。 */
class DomainAfterSaleRefundService
{
    public function __construct(
        private readonly DomainAfterSaleService $afterSales,
        private readonly DomainOrderService $orders,
        private readonly OrderPaymentRepository $payments,
        private readonly OrderPaymentRefundRepository $refunds,
    ) {}

    /** 审核与自动退款申请同事务保存；外部请求在事务提交后发起。 */
    public function approve(\App\Domain\Trade\AfterSale\Contract\AfterSaleReviewInput $input, bool $autoRefund): ?OrderPaymentRefund
    {
        $snapshot = $this->afterSales->getEntity($input->getId());
        $order = $this->orders->getEntity($snapshot->getOrderId());
        $this->orders->getLockedEntity($order->getOrderNo());
        $afterSale = $this->afterSales->getLockedEntity($input->getId());
        if ($input->getApprovedRefundAmount() !== null) {
            if ($input->getApprovedRefundAmount() < 0 || $input->getApprovedRefundAmount() > $afterSale->getApplyAmount()) {
                throw new \DomainException('审核退款金额不能超过本次申请的可退金额');
            }
            $afterSale->setRefundAmount($input->getApprovedRefundAmount());
        }
        $afterSale->approve();
        $this->afterSales->saveEntity($afterSale);
        return $autoRefund && $afterSale->getStatus() === 'waiting_refund'
            ? $this->prepare($input->getId(), $input->getOperatorId(), $input->getOperatorName())
            : null;
    }

    public function prepare(int $afterSaleId, int $operatorId, string $operatorName): OrderPaymentRefund
    {
        $snapshot = $this->afterSales->getEntity($afterSaleId);
        $order = $this->orders->getEntity($snapshot->getOrderId());
        $this->orders->getLockedEntity($order->getOrderNo());
        $payment = $this->payments->findPaidByOrderIdForUpdate($order->getId());
        $afterSale = $this->afterSales->getLockedEntity($afterSaleId);
        // 申请未确定失败前始终沿用原退款号，防止网络超时后重试生成第二笔退款。
        $existing = $this->refunds->findLatestByAfterSaleId($afterSaleId);
        if ($existing !== null && $existing->status !== 'failed') {
            return $existing;
        }
        if ($payment?->payment_method === 'manual') {
            throw new \DomainException('线下支付订单不支持原路自动退款，请先联系财务处理线下退款');
        }
        if ($payment === null || ! in_array($payment->payment_method, ['balance', 'wechat'], true)) {
            throw new \DomainException('没有可退款的支付记录');
        }
        $amount = $afterSale->getRefundAmount();
        if ($amount > $afterSale->getApplyAmount()) { throw new \DomainException('退款金额超过本次售后申请上限'); }
        $available = (int) $payment->paid_amount - (int) $payment->refund_amount - $this->refunds->outstandingAmount((int) $payment->id);
        if ($amount <= 0 || $amount > $available) {
            throw new \DomainException('退款金额超过可退余额');
        }
        $afterSale->markRefunding();
        $this->afterSales->saveEntity($afterSale);
        return $this->refunds->create([
            'refund_no' => 'REF' . bin2hex(random_bytes(16)),
            'payment_id' => $payment->id, 'payment_no' => $payment->payment_no,
            'order_id' => $payment->order_id, 'order_no' => $payment->order_no,
            'member_id' => $payment->member_id, 'refund_amount' => $amount,
            'refund_reason' => $afterSale->getReason(), 'status' => 'requested',
            'operator_type' => 'admin', 'operator_id' => $operatorId, 'operator_name' => $operatorName,
            'extra_data' => ['after_sale_id' => $afterSaleId, 'payment_method' => $payment->payment_method, 'paid_amount' => (int) $payment->paid_amount],
        ]);
    }
}
