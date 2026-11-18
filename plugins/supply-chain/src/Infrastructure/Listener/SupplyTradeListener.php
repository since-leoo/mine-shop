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

namespace Plugin\SupplyChain\Infrastructure\Listener;

use App\Domain\Trade\AfterSale\Event\AfterSaleChangedIntegrationEvent;
use App\Domain\Trade\AfterSale\Event\AfterSaleRefundSucceeded;
use App\Domain\Trade\Order\Event\OrderCancelledIntegrationEvent;
use App\Domain\Trade\Order\Event\OrderCompletedEvent;
use App\Domain\Trade\Order\Event\OrderPaidIntegrationEvent;
use Hyperf\Event\Contract\ListenerInterface;
use Plugin\SupplyChain\Application\Commad\AppSupplyTradeService;

final class SupplyTradeListener implements ListenerInterface
{
    public function __construct(private readonly AppSupplyTradeService $trade) {}

    public function listen(): array
    {
        return [OrderPaidIntegrationEvent::class, OrderCancelledIntegrationEvent::class, OrderCompletedEvent::class, AfterSaleChangedIntegrationEvent::class, AfterSaleRefundSucceeded::class];
    }

    public function process(object $event): void
    {
        if ($event instanceof OrderPaidIntegrationEvent) {
            $this->trade->paid($event->orderId);
        } elseif ($event instanceof OrderCancelledIntegrationEvent) {
            $this->trade->notify($event->orderNo, 'order_cancel');
        } elseif ($event instanceof OrderCompletedEvent) {
            $this->trade->notify($event->orderNo, 'order_complete');
        } elseif ($event instanceof AfterSaleChangedIntegrationEvent) {
            $this->trade->afterSale($event->afterSaleId, $event->action);
        } elseif ($event instanceof AfterSaleRefundSucceeded && $event->afterSaleId > 0) {
            $this->trade->afterSale($event->afterSaleId, 'refund_succeeded');
        }
    }
}
