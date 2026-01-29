<?php

declare(strict_types=1);

namespace App\Domain\Order\Service;

use App\Domain\Order\Entity\OrderDraftEntity;
use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Entity\OrderSubmitCommand;
use App\Domain\Order\Event\OrderCreatedEvent;
use App\Domain\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Order\Repository\OrderRepository;
use Hyperf\DbConnection\Db;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class OrderService
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly OrderTypeStrategyFactory $strategyFactory,
        private readonly OrderStockService $stockService,
        private readonly OrderService $orderService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * @param array<string, mixed> $filters
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->repository->page($filters, $page, $pageSize);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function stats(array $filters): array
    {
        return $this->repository->stats($filters);
    }

    public function findDetail(int $id): ?array
    {
        return $this->repository->findDetail($id);
    }

    /**
     * 预览订单
     *
     * @param OrderSubmitCommand $command
     * @return OrderDraftEntity
     */
    public function preview(OrderSubmitCommand $command): OrderDraftEntity
    {
        $strategy = $this->strategyFactory->make($command->getOrderType());
        $strategy->validate($command);
        return $strategy->buildDraft($command);
    }

    /**
     * 提交订单
     *
     * @param OrderSubmitCommand $command
     * @return OrderEntity
     * @throws Throwable
     */
    public function submit(OrderSubmitCommand $command): OrderEntity
    {
        $strategy = $this->strategyFactory->make($command->getOrderType());
        $strategy->validate($command);

        $locks = $this->stockService->acquireLocks($command->getItems());
        try {
            $this->stockService->reserve($command->getItems());

            try {
                $draft = $strategy->buildDraft($command);
                $order = $this->orderService->createFromDraft($draft);
                $strategy->postCreate($order, $draft);
                $this->eventDispatcher->dispatch(new OrderCreatedEvent($order));
                return $order;
            } catch (Throwable $throwable) {
                $this->stockService->rollback($command->getItems());
                throw $throwable;
            }
        } finally {
            $this->stockService->releaseLocks($locks);
        }
    }

    public function createFromDraft(OrderDraftEntity $draft): OrderEntity
    {
        return Db::transaction(function () use ($draft) {
            return $this->repository->createFromDraft($draft);
        });
    }

    public function ship(OrderEntity $entity): OrderEntity
    {
        Db::transaction(function () use ($entity) {
            $this->repository->ship($entity);
        });

        return $entity;
    }

    public function cancel(OrderEntity $entity): OrderEntity
    {
        Db::transaction(function () use ($entity) {
            $this->repository->cancel($entity);
        });

        return $entity;
    }
}
