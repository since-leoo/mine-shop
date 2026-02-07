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

namespace App\Domain\Order\Listener;

use App\Domain\Order\Event\OrderCreatedEvent;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;

#[Listener]
final class OrderCreatedListener implements ListenerInterface
{
    public function __construct(private readonly LoggerFactory $loggerFactory) {}

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
        $this->loggerFactory->get('order')->info('订单创建', [
            'order_no' => $order->getOrderNo(),
            'member_id' => $order->getMemberId(),
            'pay_amount' => $order->getPayAmount(),
        ]);

        // TODO: 发送通知、同步外部系统
    }
}
