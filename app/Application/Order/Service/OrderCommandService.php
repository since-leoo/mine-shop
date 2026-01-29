<?php

declare(strict_types=1);

namespace App\Application\Order\Service;

use App\Domain\Order\Entity\OrderCancelEntity;
use App\Domain\Order\Entity\OrderShipEntity;
use App\Domain\Order\Entity\OrderSubmitCommand;
use App\Domain\Order\Event\OrderCancelledEvent;
use App\Domain\Order\Event\OrderShippedEvent;
use App\Domain\Order\Service\OrderService;
use App\Domain\Order\Service\OrderSubmissionService;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

final class OrderCommandService
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}


    /**
     * @param OrderSubmitCommand $command
     * @return array<string, mixed>
     * @throws \Throwable
     */
    public function submit(OrderSubmitCommand $command): array
    {
        $order = $this->orderService->submit($command);
        return $this->orderService->findDetail($order->getId()) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(OrderSubmitCommand $command): array
    {
        $draft = $this->orderService->preview($command);
        return $draft->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function ship(OrderShipEntity $orderShipEntity): array
    {
        $orderEntity = $this->orderService->findEntityById($orderShipEntity->getOrderId());
        if (! $orderEntity) {
            throw new RuntimeException('订单不存在');
        }

        $orderEntity->setShipEntity($orderShipEntity);
        $orderEntity->ship();
        
        $this->orderService->ship($orderEntity);

        // 发送事件
        $this->eventDispatcher->dispatch(new OrderShippedEvent($orderEntity, $orderShipEntity));

        return $this->orderService->findDetail($orderEntity->getId()) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(OrderCancelEntity $orderCancelEntity): array
    {
        $orderEntity = $this->orderService->findEntityById($orderCancelEntity->getOrderId());
        if (! $orderEntity) {
            throw new RuntimeException('订单不存在');
        }
        $orderEntity->cancel();

        $this->orderService->cancel($orderEntity);

        $this->eventDispatcher->dispatch(new OrderCancelledEvent($orderEntity, $orderCancelEntity));

        return $this->orderService->findDetail($orderEntity->getId()) ?? [];
    }
}
