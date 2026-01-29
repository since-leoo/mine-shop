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

namespace App\Domain\GroupBuy\Service;

use App\Domain\GroupBuy\Entity\GroupBuyEntity;
use App\Domain\GroupBuy\Repository\GroupBuyRepository;
use App\Infrastructure\Model\GroupBuy\GroupBuy;

/**
 * 团购活动领域服务.
 */
final class GroupBuyService
{
    public function __construct(
        private readonly GroupBuyRepository $repository
    ) {}

    /**
     * 分页查询团购活动.
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->repository->page($filters, $page, $pageSize);
    }

    /**
     * 根据ID查找团购活动.
     */
    public function findById(int $id): ?GroupBuy
    {
        return $this->repository->findById($id);
    }

    /**
     * 创建团购活动.
     */
    public function create(GroupBuyEntity $entity): GroupBuy
    {
        return $this->repository->createFromEntity($entity);
    }

    /**
     * 更新团购活动.
     */
    public function update(GroupBuyEntity $entity): bool
    {
        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 删除团购活动.
     */
    public function delete(int $id): bool
    {
        $groupBuy = $this->repository->findById($id);
        if (! $groupBuy) {
            throw new \InvalidArgumentException('团购活动不存在');
        }

        // 检查是否有进行中的团或已支付的订单
        if ($groupBuy->status === 'active' && $groupBuy->sold_quantity > 0) {
            throw new \RuntimeException('活动进行中且已有销量，无法删除');
        }

        return $this->repository->deleteById($id) > 0;
    }

    /**
     * 切换活动状态.
     */
    public function toggleStatus(int $id): bool
    {
        $groupBuy = $this->repository->findById($id);
        if (! $groupBuy) {
            throw new \InvalidArgumentException('团购活动不存在');
        }

        $entity = new GroupBuyEntity();
        $entity->setId($id);
        $entity->setIsEnabled(! $groupBuy->is_enabled);

        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 获取统计数据.
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }
}
