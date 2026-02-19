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

namespace App\Domain\Trade\GroupBuy\Service;

use App\Domain\Trade\GroupBuy\Entity\GroupBuyEntity;
use App\Domain\Trade\GroupBuy\Mapper\GroupBuyMapper;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\GroupBuy\GroupBuy;

/**
 * 团购活动领域服务.
 *
 * 负责团购活动的核心业务逻辑，只接受实体对象。
 * DTO 到实体的转换由应用层负责。
 */
final class DomainGroupBuyService extends IService
{
    public function __construct(
        public readonly GroupBuyRepository $repository
    ) {}

    /**
     * 查找最新的 pending 状态活动 ID.
     */
    public function findLatestPendingId(): ?int
    {
        $latest = $this->repository->getQuery()
            ->orderByDesc('id')->first();
        if (! $latest || $latest->status !== 'pending') {
            return null;
        }
        return (int) $latest->id;
    }

    /**
     * 判断指定活动是否为 pending 状态.
     */
    public function isPending(int $id): bool
    {
        $model = $this->repository->findById($id);
        return $model && $model->status === 'pending';
    }

    /**
     * 创建团购活动.
     *
     * @param GroupBuyEntity $entity 团购实体
     * @return GroupBuy 创建的模型
     */
    public function create(GroupBuyEntity $entity): GroupBuy
    {
        $groupBuy = $this->repository->create($entity->toArray());
        $entity->setId((int) $groupBuy->id);
        return $groupBuy;
    }

    /**
     * 更新团购活动.
     *
     * @param GroupBuyEntity $entity 更新后的实体
     * @return bool 是否更新成功
     * @throws \DomainException 活动不允许编辑时抛出
     */
    public function update(GroupBuyEntity $entity): bool
    {
        // 检查是否允许编辑
        if (! $entity->canBeEdited()) {
            throw new \DomainException('当前活动状态不允许编辑（活动已开始或即将开始）');
        }

        return $this->repository->updateById($entity->getId(), $entity->toArray());
    }

    public function delete(int $id): bool
    {
        $groupBuy = $this->repository->findById($id);
        if (! $groupBuy) {
            throw new \RuntimeException('团购活动不存在');
        }

        $entity = GroupBuyMapper::fromModel($groupBuy);
        if (! $entity->canBeDeleted()) {
            throw new \DomainException('当前活动状态不允许删除（活动进行中有销量或即将开始）');
        }

        return $this->repository->deleteById($id) > 0;
    }

    public function toggleStatus(int $id): bool
    {
        $entity = $this->getEntity($id);
        $entity->getIsEnabled() ? $entity->disable() : $entity->enable();
        return $this->repository->updateById($id, $entity->toArray());
    }

    public function start(int $id): bool
    {
        $entity = $this->getEntity($id);
        $entity->start();
        return $this->repository->updateById($id, $entity->toArray());
    }

    public function end(int $id): bool
    {
        $entity = $this->getEntity($id);
        $entity->end();
        return $this->repository->updateById($id, $entity->toArray());
    }

    public function getEntity(int $id): GroupBuyEntity
    {
        /** @var null|GroupBuy $model */
        $model = $this->repository->findById($id);
        if (! $model) {
            throw new \RuntimeException("团购活动不存在: ID={$id}");
        }
        return GroupBuyMapper::fromModel($model);
    }

    public function increaseSoldQuantity(int $id, int $quantity): bool
    {
        $entity = $this->getEntity($id);
        $entity->increaseSoldQuantity($quantity);
        return $this->repository->updateById($id, $entity->toArray());
    }

    public function increaseGroupCount(int $id): bool
    {
        $entity = $this->getEntity($id);
        $entity->increaseGroupCount();
        return $this->repository->updateById($id, $entity->toArray());
    }

    public function increaseSuccessGroupCount(int $id): bool
    {
        $entity = $this->getEntity($id);
        $entity->increaseSuccessGroupCount();
        return $this->repository->updateById($id, $entity->toArray());
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }
}
