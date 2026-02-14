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

use App\Infrastructure\Abstract\IService;
use App\Domain\Trade\GroupBuy\Contract\GroupBuyCreateInput;
use App\Domain\Trade\GroupBuy\Contract\GroupBuyUpdateInput;
use App\Domain\Trade\GroupBuy\Entity\GroupBuyEntity;
use App\Domain\Trade\GroupBuy\Mapper\GroupBuyMapper;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyRepository;
use App\Infrastructure\Model\GroupBuy\GroupBuy;

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

    public function create(GroupBuyCreateInput $dto): bool
    {
        $entity = GroupBuyMapper::getNewEntity();
        $entity->create($dto);
        $groupBuy = $this->repository->create($entity->toArray());
        $entity->setId((int) $groupBuy->id);
        return (bool) $groupBuy;
    }

    public function update(GroupBuyUpdateInput $dto): bool
    {
        $entity = $this->getEntity($dto->getId());
        $entity->update($dto);
        return $this->repository->updateById($dto->getId(), $entity->toArray());
    }

    public function delete(int $id): bool
    {
        $groupBuy = $this->repository->findById($id);
        if (! $groupBuy) {
            throw new \RuntimeException('团购活动不存在');
        }
        if ($groupBuy->status === 'active' && $groupBuy->sold_quantity > 0) {
            throw new \DomainException('活动进行中且已有销量，无法删除');
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
