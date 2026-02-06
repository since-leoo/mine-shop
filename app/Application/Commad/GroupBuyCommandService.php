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

namespace App\Application\Commad;

use App\Application\Query\GroupBuyQueryService;
use App\Domain\GroupBuy\Entity\GroupBuyEntity;
use App\Domain\GroupBuy\Service\GroupBuyService;
use App\Infrastructure\Model\GroupBuy\GroupBuy;

/**
 * 团购活动命令服务：处理所有写操作.
 */
final class GroupBuyCommandService
{
    public function __construct(
        private readonly GroupBuyService $groupBuyService,
        private readonly GroupBuyQueryService $queryService
    ) {}

    /**
     * 创建团购活动.
     */
    public function create(GroupBuyEntity $entity): GroupBuy
    {
        return $this->groupBuyService->create($entity);
    }

    /**
     * 更新团购活动.
     */
    public function update(GroupBuyEntity $entity): bool
    {
        $groupBuy = $this->queryService->find($entity->getId());
        $groupBuy || throw new \InvalidArgumentException('团购活动不存在');

        return $this->groupBuyService->update($entity);
    }

    /**
     * 删除团购活动.
     */
    public function delete(int $id): bool
    {
        $groupBuy = $this->queryService->find($id);
        $groupBuy || throw new \InvalidArgumentException('团购活动不存在');

        return $this->groupBuyService->delete($id);
    }

    /**
     * 切换活动状态.
     */
    public function toggleStatus(int $id): bool
    {
        $groupBuy = $this->queryService->find($id);
        $groupBuy || throw new \InvalidArgumentException('团购活动不存在');

        return $this->groupBuyService->toggleStatus($id);
    }
}
