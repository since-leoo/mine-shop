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

namespace App\Domain\Trade\Order\Listener;

use App\Domain\Member\Api\Command\DomainApiMemberCartCommandService;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use App\Domain\Trade\Order\Event\OrderCreatedEvent;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
final class OrderCreatedListener implements ListenerInterface
{
    public function __construct(
        private readonly DomainApiMemberCartCommandService $cartCommandService,
    ) {}

    public function listen(): array
    {
        return [OrderCreatedEvent::class];
    }

    public function process(object $event): void
    {
        if (! $event instanceof OrderCreatedEvent) {
            return;
        }

        $order = $event->order;

        $this->removeCartItem($order);
    }

    private function removeCartItem(OrderEntity $order): void
    {
        if (! $order->getExtra('from_cart', false)) {
            return;
        }

        foreach ($order->getItems() as $item) {
            if (! $item instanceof OrderItemEntity) {
                continue;
            }

            $skuId = $item->getSkuId();
            if ($skuId > 0) {
                $this->cartCommandService->removeItem($order->getMemberId(), (int) $skuId);
            }
        }
    }
}
