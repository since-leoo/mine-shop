<?php

declare(strict_types=1);

namespace App\Application\Api\Order;

use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;
use App\Domain\Trade\Order\Contract\OrderSubmissionStoreInterface;
use App\Domain\Trade\Order\Service\DomainOrderCancellationService;
use App\Domain\Trade\Order\Service\DomainOrderStockService;
use App\Domain\Trade\Seckill\Repository\SeckillOrderRepository;
use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;

final class AppOrderCancellationService
{
    public function __construct(
        private readonly DomainOrderCancellationService $cancellation,
        private readonly OrderSubmissionStoreInterface $submissions,
        private readonly TransactionManagerInterface $transactions,
        private readonly TradeOutboxInterface $outbox,
        private readonly SeckillOrderRepository $seckillOrders,
        private readonly GroupBuyOrderRepository $groupOrders,
    ) {}

    public function cancel(string $orderNo, ?int $memberId = null, bool $expiredOnly = false, string $reason = '', int $operatorId = 0, string $operatorName = 'system'): void
    {
        $this->transactions->run(function () use ($orderNo, $memberId, $expiredOnly, $reason, $operatorId, $operatorName): void {
            $order = $this->cancellation->cancel($orderNo, $memberId, $expiredOnly);
            if ($order === null) {
                return;
            }
            $submission = $this->submissions->find($orderNo);
            $activityId = match ($order->getOrderType()) {
                'seckill' => $this->seckillOrders->findByOrderId($order->getId())?->session_id ?? 0,
                'group_buy' => $this->groupOrders->findByOrderId($order->getId())?->group_buy_id ?? 0,
                default => 0,
            };
            $this->outbox->append('release:' . $orderNo, 'stock.release', [
                'trade_no' => $orderNo,
                'items' => array_map(static fn ($item) => $item->toArray(), $order->getItems()),
                'stock_key' => $submission['payload']['stock_key'] ?? DomainOrderStockService::resolveStockKey($order->getOrderType(), (int) $activityId),
                'legacy' => $submission === null || ($submission['payload']['legacy'] ?? false),
            ]);
            $this->outbox->append('cancelled:' . $orderNo, 'order.cancelled', [
                'order_no' => $orderNo, 'reason' => $reason, 'operator_id' => $operatorId, 'operator_name' => $operatorName,
            ]);
        });
    }
}
