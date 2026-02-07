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

use App\Domain\GroupBuy\Contract\GroupBuyCreateInput;
use App\Domain\GroupBuy\Contract\GroupBuyUpdateInput;
use App\Domain\GroupBuy\Entity\GroupBuyEntity;
use App\Domain\GroupBuy\Mapper\GroupBuyMapper;
use App\Domain\GroupBuy\Repository\GroupBuyRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\GroupBuy\GroupBuy;

/**
 * 团购活动领域服务.
 */
final class GroupBuyService extends IService
{
    public function __construct(
        public readonly GroupBuyRepository $repository
    ) {}

    /**
     * 创建团购活动.
     */
    public function create(GroupBuyCreateInput $dto): bool
    {
        $entity = GroupBuyMapper::getNewEntity();
        $entity->create($dto);
        $groupBuy = $this->repository->create($entity->toArray());
        $entity->setId((int) $groupBuy->id);

        return (bool) $groupBuy;
    }

    /**
     * 更新团购活动.
     */
    public function update(GroupBuyUpdateInput $dto): bool
    {
        $entity = $this->getEntity($dto->getId());
        $entity->update($dto);
        return $this->repository->updateById($dto->getId(), $entity->toArray());
    }

    /**
     * 删除团购活动.
     */
    public function delete(int $id): bool
    {
        $groupBuy = $this->repository->findById($id);
        if (! $groupBuy) {
            throw new \RuntimeException('团购活动不存在');
        }

        // 检查是否有进行中的团或已支付的订单
        if ($groupBuy->status === 'active' && $groupBuy->sold_quantity > 0) {
            throw new \DomainException('活动进行中且已有销量，无法删除');
        }

        return $this->repository->deleteById($id) > 0;
    }

    /**
     * 切换活动状态.
     */
    public function toggleStatus(int $id): bool
    {
        // 1. 获取实体
        $entity = $this->getEntity($id);

        // 2. 调用实体行为方法
        if ($entity->getIsEnabled()) {
            $entity->disable();
        } else {
            $entity->enable();
        }

        // 3. 持久化修改
        return $this->repository->updateById($id, $entity->toArray());
    }

    /**
     * 获取团购活动实体.
     *
     * 通过 ID 获取 Model，然后通过 Mapper 转换为 Entity.
     * 用于需要调用实体行为方法的场景.
     *
     * @param int $id 团购活动ID
     * @return GroupBuyEntity 团购活动实体对象
     * @throws \RuntimeException 当团购活动不存在时
     */
    public function getEntity(int $id): GroupBuyEntity
    {
        /** @var null|GroupBuy $model */
        $model = $this->repository->findById($id);

        if (! $model) {
            throw new \RuntimeException("团购活动不存在: ID={$id}");
        }

        return GroupBuyMapper::fromModel($model);
    }

    /**
     * 增加销量.
     */
    public function increaseSoldQuantity(int $id, int $quantity): bool
    {
        // 1. 获取实体
        $entity = $this->getEntity($id);

        // 2. 调用实体行为方法
        $entity->increaseSoldQuantity($quantity);

        // 3. 持久化修改
        return $this->repository->updateById($id, $entity->toArray());
    }

    /**
     * 增加成团数.
     */
    public function increaseGroupCount(int $id): bool
    {
        // 1. 获取实体
        $entity = $this->getEntity($id);

        // 2. 调用实体行为方法
        $entity->increaseGroupCount();

        // 3. 持久化修改
        return $this->repository->updateById($id, $entity->toArray());
    }

    /**
     * 增加成功成团数.
     */
    public function increaseSuccessGroupCount(int $id): bool
    {
        // 1. 获取实体
        $entity = $this->getEntity($id);

        // 2. 调用实体行为方法
        $entity->increaseSuccessGroupCount();

        // 3. 持久化修改
        return $this->repository->updateById($id, $entity->toArray());
    }

    /**
     * 获取统计数据.
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }
}
