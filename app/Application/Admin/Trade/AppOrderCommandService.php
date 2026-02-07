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

namespace App\Application\Admin\Trade;

use App\Domain\Trade\Order\Contract\OrderCancelInput;
use App\Domain\Trade\Order\Contract\OrderShipInput;
use App\Domain\Trade\Order\Entity\OrderCancelEntity;
use App\Domain\Trade\Order\Entity\OrderShipEntity;
use App\Domain\Trade\Order\Event\OrderCancelledEvent;
use App\Domain\Trade\Order\Event\OrderShippedEvent;
use App\Domain\Trade\Order\Service\DomainOrderService;
use Hyperf\DbConnection\Annotation\Transactional;

final class AppOrderCommandService
{
    public function __construct(private readonly DomainOrderService $orderService) {}

    /**
     * @return array<string, mixed>
     */
    #[Transactional]
    public function ship(OrderShipInput $input): array
    {
        $orderEntity = $this->orderService->getEntity($input->getOrderId());

        // 将 DTO 转换为 Entity
        $orderShipEntity = new OrderShipEntity();
        $orderShipEntity->setOrderId($input->getOrderId());
        $orderShipEntity->setOperatorId($input->getOperatorId());
        $orderShipEntity->setOperatorName($input->getOperatorName());
        $orderShipEntity->setPackages($input->getPackages());

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
    public function cancel(OrderCancelInput $input): array
    {
        $orderEntity = $this->orderService->getEntity($input->getOrderId());

        // 将 DTO 转换为 Entity
        $orderCancelEntity = new OrderCancelEntity();
        $orderCancelEntity->setOrderId($input->getOrderId());
        $orderCancelEntity->setReason($input->getReason());
        $orderCancelEntity->setOperatorId($input->getOperatorId());
        $orderCancelEntity->setOperatorName($input->getOperatorName());

        $orderEntity->cancel();

        $this->orderService->cancel($orderEntity);

        event(new OrderCancelledEvent($orderEntity, $orderCancelEntity));

        return $this->orderService->findDetail($orderEntity->getId()) ?? [];
    }
}
