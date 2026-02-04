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
use App\Domain\Order\Enum\OrderStatus;
use App\Domain\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Order\Repository\OrderRepository;
use App\Domain\SystemSetting\Service\MallSettingService;

final class OrderService
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly OrderTypeStrategyFactory $strategyFactory,
        private readonly OrderStockService $stockService,
        private readonly MallSettingService $mallSettingService,
    ) {}

    /**
     * 更新订单.
     */
    public function update(OrderEntity $entity)
    {
        return $this->repository->updateById($entity->getId(), $entity->toArray());
    }

    /**
     * 获取订单.
     */
    public function getEntityById(int $id = 0, string $orderNo = ''): OrderEntity
    {
        if ($id) {
            $orderEntity = $this->repository->getEntityById($id);
        } else {
            $orderEntity = $this->repository->getEntityByOrderNo($orderNo);
        }

        if (! $orderEntity || $orderEntity->getMemberId() !== memberId()) {
            throw new \RuntimeException('订单不存在');
        }

        if ($orderEntity->getPayStatus() === OrderStatus::PAID->value) {
            throw new \RuntimeException('订单已支付');
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
    public function preview(OrderEntity $orderEntity): OrderEntity
    {
        $orderEntity->guardPreorderAllowed($this->mallSettingService->product()->allowPreorder());
        $strategy = $this->strategyFactory->make($orderEntity->getOrderType());
        $strategy->validate($orderEntity);
        return $strategy->buildDraft($orderEntity);
    }

    /**
     * 提交订单.
     *
     * @throws \Throwable
     */
    public function submit(OrderEntity $orderEntity): OrderEntity
    {
        $orderEntity->guardPreorderAllowed($this->mallSettingService->product()->allowPreorder());
        $orderEntity->applySubmissionPolicy($this->mallSettingService->order());
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
                $orderEntity = $this->repository->save($strategy->buildDraft($orderEntity));
                $strategy->postCreate($orderEntity);
            } catch (\Throwable $throwable) {
                $this->stockService->rollback($items);
                throw $throwable;
            }
        } finally {
            $this->stockService->releaseLocks($locks);
        }

        return $orderEntity;
    }

    public function ship(OrderEntity $entity): OrderEntity
    {
        $entity->ensureShippable($this->mallSettingService->shipping());
        $this->repository->ship($entity);

        return $entity;
    }

    public function cancel(OrderEntity $entity): OrderEntity
    {
        $this->repository->cancel($entity);

        return $entity;
    }

    public function countByMemberAndStatuses(int $memberId): array
    {
        return $this->repository->countByMemberAndStatuses($memberId);
    }
}
