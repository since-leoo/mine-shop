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

namespace App\Infrastructure\Crontab;

use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Infrastructure\Model\Order\Order;
use Hyperf\Crontab\Annotation\Crontab;
use Psr\Log\LoggerInterface;

/**
 * 订单超时自动关闭.
 *
 * 每分钟扫描 status=pending 且 expire_time <= now 的订单，
 * 逐笔取消并回滚 Redis 库存。
 * expire_time 由系统配置 mall.order.auto_close_minutes 决定。
 */
#[Crontab(
    name: 'order-auto-close',
    rule: '* * * * *',
    callback: 'execute',
    memo: '订单超时自动关闭',
    enable: true
)]
class OrderAutoCloseCrontab
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly \App\Application\Api\Order\AppOrderCancellationService $cancellation,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(): void
    {
        $orders = $this->orderRepository->findExpiredPendingOrders();

        if ($orders->isEmpty()) {
            return;
        }

        $closed = 0;
        foreach ($orders as $order) {
            try {
                $this->closeOrder($order);
                ++$closed;
            } catch (\Throwable $e) {
                $this->logger->error('[OrderAutoClose] 关闭订单失败', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('[OrderAutoClose] 本轮关闭订单', ['count' => $closed]);
    }

    private function closeOrder(Order $order): void
    {
        $this->cancellation->cancel((string) $order->order_no, expiredOnly: true, reason: '支付超时');
    }
}
