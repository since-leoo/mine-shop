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

namespace App\Domain\Order\Service;

use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Event\OrderCreatedEvent;
use App\Domain\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Order\Repository\OrderRepository;
use Hyperf\DbConnection\Db;
use Psr\EventDispatcher\EventDispatcherInterface;

final class OrderService
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly OrderTypeStrategyFactory $strategyFactory,
        private readonly OrderStockService $stockService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * 获取订单.
     */
    public function getEntityById(int $id): OrderEntity
    {
        $orderEntity = $this->repository->getEntityById($id);

        if (! $orderEntity) {
            throw new \RuntimeException('订单不存在');
        }

        return $orderEntity;
    }

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
     * 预览订单.
     */
    public function preview(OrderEntity $command): OrderEntity
    {
        $strategy = $this->strategyFactory->make($command->getOrderType());
        $strategy->validate($command);
        return $strategy->buildDraft($command);
    }

    /**
     * 提交订单.
     *
     * @throws \Throwable
     */
    public function submit(OrderEntity $orderEntity): OrderEntity
    {
        // 获取订单策略
        $strategy = $this->strategyFactory->make($orderEntity->getOrderType());
        // 验证
        $strategy->validate($orderEntity);
        // 获取订单商品
        $items = array_map(static function ($item) {return $item->toArray(); }, $orderEntity->getItems());
        // 锁定库存
        $locks = $this->stockService->acquireLocks($items);
        try {
            // 库存扣除（LUA）
            $this->stockService->reserve($items);

            try {
                $this->repository->save($strategy->buildDraft($orderEntity));
                $strategy->postCreate($orderEntity);
                $this->eventDispatcher->dispatch(new OrderCreatedEvent($orderEntity));
                return $orderEntity;
            } catch (\Throwable $throwable) {
                $this->stockService->rollback($items);
                throw $throwable;
            }
        } finally {
            $this->stockService->releaseLocks($locks);
        }
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
