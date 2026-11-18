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

namespace App\Domain\Trade\GroupBuy\Listener;

use App\Domain\Member\Event\OrderPaidForMember;
use App\Application\Admin\GroupBuy\AppGroupBuyLifecycleService;
use Hyperf\Event\Contract\ListenerInterface;

/**
 * 订单支付成功 → 同步更新团购订单状态并检查成团.
 */
final class GroupBuyOrderPaidListener implements ListenerInterface
{
    public function __construct(
        private readonly AppGroupBuyLifecycleService $lifecycleService,
    ) {}

    public function listen(): array
    {
        return [OrderPaidForMember::class, \App\Domain\Trade\Order\Event\OrderCancelledEvent::class];
    }

    public function process(object $event): void
    {
        if ($event instanceof \App\Domain\Trade\Order\Event\OrderCancelledEvent) {
            if ($event->order->getOrderType() === 'group_buy') {
                $this->lifecycleService->onOrderCancelled($event->order->getId());
            }
            return;
        }
        if (! $event instanceof OrderPaidForMember || $event->orderId === 0) {
            return;
        }

        $this->lifecycleService->onOrderPaid($event->orderId);
    }
}
