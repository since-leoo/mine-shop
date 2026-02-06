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

namespace App\Application\Commad;

use App\Domain\Order\Entity\OrderCancelEntity;
use App\Domain\Order\Entity\OrderShipEntity;
use App\Domain\Order\Event\OrderCancelledEvent;
use App\Domain\Order\Event\OrderShippedEvent;
use App\Domain\Order\Service\OrderService;
use Hyperf\DbConnection\Annotation\Transactional;

final class OrderCommandService
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * @return array<string, mixed>
     */
    #[Transactional]
    public function ship(OrderShipEntity $orderShipEntity): array
    {
        $orderEntity = $this->orderService->getEntity($orderShipEntity->getOrderId());

        $orderEntity->setShipEntity($orderShipEntity);
        $orderEntity->ship();

        $this->orderService->ship($orderEntity);

        event(new OrderShippedEvent($orderEntity, $orderShipEntity));

        return $this->orderService->findDetail($orderEntity->getId()) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    #[Transactional]
    public function cancel(OrderCancelEntity $orderCancelEntity): array
    {
        $orderEntity = $this->orderService->getEntity($orderCancelEntity->getOrderId());

        $orderEntity->cancel();

        $this->orderService->cancel($orderEntity);

        event(new OrderCancelledEvent($orderEntity, $orderCancelEntity));

        return $this->orderService->findDetail($orderEntity->getId()) ?? [];
    }
}
