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

        $shipEntity = new OrderShipEntity();
        $shipEntity->setOrderId($input->getOrderId());
        $shipEntity->setPackages($input->getPackages());

        $orderEntity->setShipEntity($shipEntity);
        $orderEntity->ship();

        $this->orderService->ship($orderEntity);

        event(new OrderShippedEvent(
            $orderEntity,
            $shipEntity,
            $input->getOperatorId(),
            $input->getOperatorName(),
        ));

        return $this->orderService->findDetail($orderEntity->getId()) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    #[Transactional]
    public function cancel(OrderCancelInput $input): array
    {
        $orderEntity = $this->orderService->getEntity($input->getOrderId());

        $orderEntity->cancel();

        $this->orderService->cancel($orderEntity);

        event(new OrderCancelledEvent(
            $orderEntity,
            $input->getReason(),
            $input->getOperatorId(),
            $input->getOperatorName(),
        ));

        return $this->orderService->findDetail($orderEntity->getId()) ?? [];
    }
}
