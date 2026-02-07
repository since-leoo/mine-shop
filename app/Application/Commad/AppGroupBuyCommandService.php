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

use App\Application\Query\AppGroupBuyQueryService;
use App\Domain\GroupBuy\Contract\GroupBuyCreateInput;
use App\Domain\GroupBuy\Contract\GroupBuyUpdateInput;
use App\Domain\GroupBuy\Service\DomainGroupBuyService;
use Hyperf\DbConnection\Db;

/**
 * 团购活动命令服务：处理所有写操作.
 */
final class AppGroupBuyCommandService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
        private readonly AppGroupBuyQueryService $queryService,
    ) {}

    /**
     * 创建团购活动.
     */
    public function create(GroupBuyCreateInput $input): bool
    {
        // 1. 事务管理
        return Db::transaction(fn () => $this->groupBuyService->create($input));
        // 2. 领域事件发布（如果需要）
        // event(new GroupBuyCreated($groupBuy));
    }

    /**
     * 更新团购活动.
     */
    public function update(GroupBuyUpdateInput $input): bool
    {
        return Db::transaction(fn () => $this->groupBuyService->update($input));
    }

    /**
     * 删除团购活动.
     */
    public function delete(int $id): bool
    {
        $groupBuy = $this->queryService->find($id);
        $groupBuy || throw new \InvalidArgumentException('团购活动不存在');

        // 1. 事务管理
        return Db::transaction(fn () => $this->groupBuyService->delete($id));
    }

    /**
     * 切换活动状态.
     */
    public function toggleStatus(int $id): bool
    {
        $groupBuy = $this->queryService->find($id);
        $groupBuy || throw new \InvalidArgumentException('团购活动不存在');

        // 1. 事务管理
        return Db::transaction(fn () => $this->groupBuyService->toggleStatus($id));
    }
}
