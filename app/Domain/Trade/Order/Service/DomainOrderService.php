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

namespace App\Domain\Trade\Order\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Mapper\OrderMapper;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Order\Order;

/**
 * 订单领域服务（后台管理端）.
 *
 * API 端专属操作（preview、submit、cancel、confirmReceipt）已迁移至
 * Domain\Order\Api\Command\OrderCommandApiService.
 */
final class DomainOrderService extends IService
{
    public function __construct(
        public readonly OrderRepository $repository,
        private readonly DomainMallSettingService $mallSettingService,
    ) {}

    /**
     * 更新订单.
     */
    public function update(OrderEntity $entity): bool
    {
        return $this->repository->updateById($entity->getId(), $entity->toArray());
    }

    /**
     * 获取订单实体（后台管理端，不校验会员归属）.
     */
    public function getEntity(int $id = 0, string $orderNo = ''): OrderEntity
    {
        /** @var null|Order $order */
        $order = $id ? $this->repository->findById($id) : $this->repository->findByOrderNo($orderNo);

        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }

        return OrderMapper::fromModel($order);
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
     * 发货.
     */
    public function ship(OrderEntity $entity): OrderEntity
    {
        $entity->ensureShippable($this->mallSettingService->shipping());
        $this->repository->ship($entity);

        return $entity;
    }

    /**
     * 取消订单（后台和 API 共用）.
     */
    public function cancel(OrderEntity $entity): OrderEntity
    {
        $this->repository->cancel($entity);

        return $entity;
    }

    /**
     * 会员各状态订单数量.
     */
    public function countByMemberAndStatuses(int $memberId): array
    {
        return $this->repository->countByMemberAndStatuses($memberId);
    }
}
