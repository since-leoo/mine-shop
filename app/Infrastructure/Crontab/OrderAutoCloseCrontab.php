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

use App\Domain\Trade\Order\Mapper\OrderMapper;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Domain\Trade\Order\Service\DomainOrderStockService;
use App\Infrastructure\Model\Order\Order;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\DbConnection\Db;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;
use App\Domain\Trade\Seckill\Repository\SeckillOrderRepository;
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
        private readonly SeckillOrderRepository $seckillOrderRepository,
        private readonly GroupBuyOrderRepository $groupBuyOrderRepository,
        private readonly DomainOrderStockService $stockService,
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
        // 构建 Entity 并取消
        $entity = OrderMapper::getNewEntity();
        $entity->setId($order->id);
        $entity->cancel();

        Db::transaction(fn () => $this->orderRepository->cancel($entity));

        // 回滚 Redis 库存
        $items = $order->items->map(static fn ($item) => [
            'sku_id' => $item->sku_id,
            'quantity' => $item->quantity,
        ])->toArray();

        $stockHashKey = $this->resolveStockHashKey($order);
        $this->stockService->rollback($items, $stockHashKey);
    }

    private function resolveStockHashKey(Order $order): string
    {
        if ($order->order_type === 'seckill') {
            $seckillOrder = $this->seckillOrderRepository->findByOrderId($order->id);
            return DomainOrderStockService::resolveStockKey('seckill', $seckillOrder?->session_id ?? 0);
        }

        if ($order->order_type === 'group_buy') {
            $groupBuyOrder = $this->groupBuyOrderRepository->findByOrderId($order->id);
            return DomainOrderStockService::resolveStockKey('group_buy', $groupBuyOrder?->group_buy_id ?? 0);
        }

        return DomainOrderStockService::resolveStockKey('normal');
    }
}
