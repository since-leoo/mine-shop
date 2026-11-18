<?php

declare(strict_types=1);

namespace App\Domain\Trade\GroupBuy\Service;

use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Contract\OrderSubmissionStoreInterface;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;

final class DomainGroupBuyRefundService
{
    public function __construct(
        private readonly DomainOrderService $orders,
        private readonly OrderPaymentRepository $payments,
        private readonly OrderPaymentRefundRepository $refunds,
        private readonly OrderSubmissionStoreInterface $submissions,
        private readonly GroupBuyOrderRepository $groups,
    ) {}

    /** 已付款则持久化原路退款；未付款返回订单号，由应用层走现有取消流程。 */
    public function prepare(int $orderId): ?string
    {
        $order = $this->orders->getLockedEntityById($orderId);
        if ($order->getPayStatus() === 'pending') {
            return $order->getOrderNo();
        }
        if ($order->getPayStatus() === 'refunded') {
            return null;
        }
        $payment = $this->payments->findPaidByOrderIdForUpdate($orderId);
        $refundNo = 'GBFAIL' . $orderId;
        if ($this->refunds->findByRefundNo($refundNo) !== null) {
            return null;
        }
        if ($payment === null || ! in_array($payment->payment_method, ['balance', 'wechat'], true)) {
            throw new \DomainException('未成团订单需人工核实线下退款: ' . $order->getOrderNo());
        }
        $amount = (int) $payment->paid_amount - (int) $payment->refund_amount;
        if ($amount <= 0 || $this->refunds->outstandingAmount((int) $payment->id) > 0) {
            throw new \DomainException('订单已有其他退款申请，等待退款完成后重试');
        }
        $group = $this->groups->findByOrderId($orderId);
        $submission = $this->submissions->find($order->getOrderNo());
        $this->refunds->create([
            'refund_no' => $refundNo, 'payment_id' => $payment->id, 'payment_no' => $payment->payment_no,
            'order_id' => $orderId, 'order_no' => $order->getOrderNo(), 'member_id' => $order->getMemberId(),
            'refund_amount' => $amount, 'refund_reason' => '拼团超时未成团', 'status' => 'requested',
            'operator_type' => 'system',
            'extra_data' => ['after_sale_id' => 0, 'group_buy_failed' => true, 'payment_method' => $payment->payment_method,
                'paid_amount' => (int) $payment->paid_amount,
                'stock_release' => ['trade_no' => $order->getOrderNo(),
                    'items' => array_map(static fn ($item) => $item->toArray(), $order->getItems()),
                    'stock_key' => $submission['payload']['stock_key'] ?? 'groupbuy:stock:' . $group->group_buy_id,
                    'legacy' => $submission === null || ($submission['payload']['legacy'] ?? false)]],
        ]);
        return null;
    }
}
