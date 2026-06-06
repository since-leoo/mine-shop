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

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Domain\Trade\Order\Repository\OrderRepository;
use Carbon\Carbon;
use Hyperf\Crontab\Annotation\Crontab;
use Psr\Log\LoggerInterface;

#[Crontab(
    name: 'order-auto-confirm',
    rule: '0 * * * *',
    callback: 'execute',
    memo: '订单自动确认收货',
    enable: true
)]
class OrderAutoConfirmCrontab
{
    private const BATCH_LIMIT = 200;

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly DomainMallSettingService $mallSettingService,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(): void
    {
        $autoConfirmDays = $this->mallSettingService->order()->autoConfirmDays();
        if ($autoConfirmDays <= 0) {
            return;
        }

        $orders = $this->orderRepository->findAutoConfirmableOrders(
            Carbon::now()->subDays($autoConfirmDays),
            self::BATCH_LIMIT,
        );

        if ($orders->isEmpty()) {
            return;
        }

        $confirmed = 0;
        foreach ($orders as $order) {
            try {
                $entity = $this->makeCompletableEntity($order->id);
                $entity->complete();
                $this->orderRepository->complete($entity);
                ++$confirmed;
            } catch (\Throwable $e) {
                $this->logger->error('[OrderAutoConfirm] 确认收货失败', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('[OrderAutoConfirm] 本轮确认收货订单', ['count' => $confirmed]);
    }

    private function makeCompletableEntity(int $orderId): OrderEntity
    {
        $entity = new OrderEntity();
        $entity->setId($orderId);
        $entity->setStatus(OrderStatus::SHIPPED->value);

        return $entity;
    }
}
