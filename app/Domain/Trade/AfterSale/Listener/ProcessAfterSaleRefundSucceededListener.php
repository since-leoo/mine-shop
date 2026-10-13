<?php

declare(strict_types=1);

namespace App\Domain\Trade\AfterSale\Listener;

use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Trade\AfterSale\Event\AfterSaleRefundSucceeded;
use App\Domain\Trade\Shared\Contract\TradeWebhookInterface;
use Hyperf\Event\Contract\ListenerInterface;

/** 财务结算已在回调事务完成，本监听器仅承担可重试的衍生通知。 */
final class ProcessAfterSaleRefundSucceededListener implements ListenerInterface
{
    public function __construct(
        private readonly MemberRepository $members,
        private readonly TradeWebhookInterface $webhooks,
    ) {}

    public function listen(): array
    {
        return [AfterSaleRefundSucceeded::class];
    }

    public function process(object $event): void
    {
        if (! $event instanceof AfterSaleRefundSucceeded) {
            return;
        }
        $this->members->syncOrderStats($event->memberId);
        $this->webhooks->dispatch('after_sale.refund_succeeded', [
            'after_sale_id' => $event->afterSaleId, 'order_id' => $event->orderId,
            'member_id' => $event->memberId, 'refund_amount' => $event->refundAmount,
            'payment_id' => $event->paymentId, 'payment_no' => $event->paymentNo,
            'payment_method' => $event->paymentMethod, 'refund_no' => $event->refundNo,
            'operator_id' => $event->operatorId, 'operator_name' => $event->operatorName,
        ]);
    }
}
