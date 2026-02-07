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

use App\Domain\Trade\Order\Event\OrderCancelledEvent;
use App\Domain\Trade\Order\Event\OrderShippedEvent;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use Hyperf\Event\Contract\ListenerInterface;
use Plugin\Since\SystemMessage\Facade\SystemMessage;

final class OrderStatusNotifyListener implements ListenerInterface
{
    public function __construct(private readonly DomainMallSettingService $mallSettingService) {}

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
        $packages = $event->command->getPackages();
        $firstPackage = $packages[0] ?? null;
        $content = $firstPackage
            ? \sprintf('您的订单已由 %s 发货，快递单号 %s。', $firstPackage->getShippingCompany(), $firstPackage->getShippingNo())
            : '您的订单已发货。';

        $this->notify(
            $event->order->getMemberId(),
            \sprintf('订单 %s 已发货', $event->order->getOrderNo()),
            $content
        );
    }

    private function handleCancelled(OrderCancelledEvent $event): void
    {
        $reason = $event->command->getReason() ?: '管理员取消订单';
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
