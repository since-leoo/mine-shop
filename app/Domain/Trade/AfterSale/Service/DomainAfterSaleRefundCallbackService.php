<?php

declare(strict_types=1);

namespace App\Domain\Trade\AfterSale\Service;

use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;

class DomainAfterSaleRefundCallbackService
{
    public function __construct(
        private readonly OrderPaymentRefundRepository $refunds,
        private readonly OrderPaymentRepository $payments,
        private readonly DomainOrderService $orders,
        private readonly DomainAfterSaleService $afterSales,
        private readonly DomainRefundSettlementService $settlement,
        private readonly TradeOutboxInterface $outbox,
    ) {}

    /** 应用层事务覆盖本方法、账目与 outbox；失败不会留下提前写入的 success。 */
    public function handleWechatRefundCallback(array $payload): void
    {
        $this->complete($payload, 'wechat');
    }

    public function complete(array $payload, string $method): void
    {
        $refundNo = (string) ($payload['out_refund_no'] ?? '');
        $snapshot = $this->refunds->findByRefundNo($refundNo);
        if ($snapshot === null) {
            throw new \RuntimeException('退款记录不存在');
        }
        $this->orders->getLockedEntity($snapshot->order_no);
        $payment = $this->payments->findPaidByOrderIdForUpdate((int) $snapshot->order_id);
        $refund = $this->refunds->findForUpdate($refundNo);
        if ($refund === null || $payment === null || $payment->payment_method !== $method) {
            throw new \DomainException('退款支付记录不匹配');
        }
        if (($payload['out_trade_no'] ?? '') !== $refund->order_no
            || ($payload['amount']['refund'] ?? null) !== (int) $refund->refund_amount
            || ($payload['amount']['total'] ?? null) !== (int) $payment->paid_amount) {
            throw new \DomainException('退款通知订单或金额不匹配');
        }
        if ($refund->status === 'success') {
            return;
        }
        $status = strtoupper((string) ($payload['refund_status'] ?? $payload['status'] ?? ''));
        if ($status === 'SUCCESS') {
            $this->settlement->settle($refund, $method);
            $this->refunds->updateByRefundNo($refundNo, [
                'status' => 'success', 'third_party_refund_no' => $payload['refund_id'] ?? null,
                'third_party_response' => $payload, 'processed_at' => date('Y-m-d H:i:s'),
            ]);
            // 晚到支付从未发放购买奖励，因此不发送售后奖励回退事件。
            if ((int) ($refund->extra_data['after_sale_id'] ?? 0) === 0) {
                return;
            }
            $this->outbox->append('refunded:' . $refundNo, 'refund.succeeded', [
                'after_sale_id' => (int) $refund->extra_data['after_sale_id'], 'order_id' => (int) $refund->order_id,
                'member_id' => (int) $refund->member_id, 'refund_amount' => (int) $refund->refund_amount,
                'payment_id' => (int) $refund->payment_id, 'payment_no' => $refund->payment_no,
                'payment_method' => $method, 'refund_no' => $refundNo,
                'operator_id' => (int) $refund->operator_id, 'operator_name' => (string) $refund->operator_name,
            ]);
        } elseif ($status === 'CLOSED') {
            $this->refunds->updateByRefundNo($refundNo, ['status' => 'failed', 'third_party_response' => $payload]);
            if ((int) ($refund->extra_data['after_sale_id'] ?? 0) === 0) {
                return;
            }
            $afterSale = $this->afterSales->getLockedEntity((int) $refund->extra_data['after_sale_id']);
            if ($afterSale->getStatus() === 'refunding') {
                $afterSale->markRefundFailed();
                $this->afterSales->saveEntity($afterSale);
            }
        } elseif ($status === 'ABNORMAL') {
            $this->refunds->updateByRefundNo($refundNo, ['status' => 'unknown', 'third_party_response' => $payload]);
        } elseif ($status === 'PROCESSING') {
            $this->refunds->updateByRefundNo($refundNo, ['status' => 'refunding', 'third_party_response' => $payload]);
        } else {
            throw new \DomainException('未知退款状态，保留待确认');
        }
    }
}
