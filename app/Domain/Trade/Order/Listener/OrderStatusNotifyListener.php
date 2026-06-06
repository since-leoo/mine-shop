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

use App\Domain\Infrastructure\SystemMessage\Facade\SystemMessage;
use App\Domain\Infrastructure\SystemMessage\Service\OutboundWebhookDispatcher;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\Order\Event\OrderCancelledEvent;
use App\Domain\Trade\Order\Event\OrderShippedEvent;
use Hyperf\Event\Contract\ListenerInterface;

final class OrderStatusNotifyListener implements ListenerInterface
{
    public function __construct(
        private readonly DomainMallSettingService $mallSettingService,
        private readonly OutboundWebhookDispatcher $webhookDispatcher
    ) {}

    public function listen(): array
    {
        return [
            OrderShippedEvent::class,
            OrderCancelledEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof OrderShippedEvent) {
            $this->handleShipped($event);
            return;
        }

        if ($event instanceof OrderCancelledEvent) {
            $this->handleCancelled($event);
        }
    }

    private function handleShipped(OrderShippedEvent $event): void
    {
        $packages = $event->shipment->getPackages();
        $firstPackage = $packages[0] ?? null;
        $content = $firstPackage
            ? \sprintf('您的订单已由 %s 发货，快递单号 %s。', $firstPackage->getShippingCompany(), $firstPackage->getShippingNo())
            : '您的订单已发货。';

        $this->webhookDispatcher->dispatch('order.shipped', [
            'order_no' => $event->order->getOrderNo(),
            'member_id' => $event->order->getMemberId(),
            'pay_amount' => $event->order->getPayAmount(),
            'shipping_company' => $firstPackage?->getShippingCompany(),
            'shipping_no' => $firstPackage?->getShippingNo(),
            'operator_id' => $event->operatorId,
            'operator_name' => $event->operatorName,
        ]);

        $this->notify(
            $event->order->getMemberId(),
            \sprintf('订单 %s 已发货', $event->order->getOrderNo()),
            $content
        );
    }

    private function handleCancelled(OrderCancelledEvent $event): void
    {
        $reason = $event->reason ?: '管理员取消订单';
        $this->webhookDispatcher->dispatch('order.cancelled', [
            'order_no' => $event->order->getOrderNo(),
            'member_id' => $event->order->getMemberId(),
            'pay_amount' => $event->order->getPayAmount(),
            'reason' => $reason,
            'operator_id' => $event->operatorId,
            'operator_name' => $event->operatorName,
        ]);

        $this->notify(
            $event->order->getMemberId(),
            \sprintf('订单 %s 已取消', $event->order->getOrderNo()),
            \sprintf('取消原因：%s', $reason)
        );
    }

    private function notify(int $memberId, string $title, string $content): void
    {
        if ($memberId <= 0 || ! $this->mallSettingService->integration()->isChannelEnabled('system')) {
            return;
        }

        SystemMessage::notify($title, $content);
    }
}
