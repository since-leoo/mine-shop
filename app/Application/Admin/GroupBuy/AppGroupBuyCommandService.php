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

namespace App\Application\Admin\GroupBuy;

use App\Domain\Trade\GroupBuy\Contract\GroupBuyCreateInput;
use App\Domain\Trade\GroupBuy\Contract\GroupBuyUpdateInput;
use App\Domain\Trade\GroupBuy\Mapper\GroupBuyMapper;
use App\Domain\Trade\GroupBuy\Service\DomainGroupBuyService;
use App\Domain\Trade\GroupBuy\Service\GroupBuyCacheService;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;

/**
 * 团购活动应用层命令服务.
 *
 * 负责协调领域服务，处理 DTO 到实体的转换。
 */
final class AppGroupBuyCommandService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
        private readonly AppGroupBuyQueryService $queryService,
        private readonly GroupBuyCacheService $cacheService,
    ) {}

    /**
     * 创建团购活动.
     *
     * @param GroupBuyCreateInput $input 创建输入 DTO
     * @return bool 是否创建成功
     */
    public function create(GroupBuyCreateInput $input): bool
    {
        // 使用 Mapper 将 DTO 转换为实体
        $entity = GroupBuyMapper::fromDto($input);

        $result = Db::transaction(fn () => (bool) $this->groupBuyService->create($entity));

        // 如果活动开始时间已过且已启用，立即激活并预热缓存
        if ($result && $input->getIsEnabled()) {
            $this->activateIfStarted($input->getStartTime());
        }

        return $result;
    }

    /**
     * 更新团购活动.
     *
     * @param GroupBuyUpdateInput $input 更新输入 DTO
     * @return bool 是否更新成功
     */
    public function update(GroupBuyUpdateInput $input): bool
    {
        // 从数据库获取实体并更新
        $entity = $this->groupBuyService->getEntity($input->getId());
        $entity->update($input);

        $result = Db::transaction(fn () => $this->groupBuyService->update($entity));

        // 更新后检查：如果活动仍是 pending 且开始时间已过，立即激活
        if ($result && $input->getIsEnabled()) {
            $this->activateIfStarted($input->getStartTime(), $input->getId());
        }

        return $result;
    }

    /**
     * 删除团购活动.
     *
     * @param int $id 活动 ID
     * @return bool 是否删除成功
     */
    public function delete(int $id): bool
    {
        $groupBuy = $this->queryService->find($id);
        $groupBuy || throw new \InvalidArgumentException('团购活动不存在');
        return Db::transaction(fn () => $this->groupBuyService->delete($id));
    }

    /**
     * 切换活动启用状态.
     *
     * @param int $id 活动 ID
     * @return bool 是否操作成功
     */
    public function toggleStatus(int $id): bool
    {
        $groupBuy = $this->queryService->find($id);
        $groupBuy || throw new \InvalidArgumentException('团购活动不存在');
        $result = Db::transaction(fn () => $this->groupBuyService->toggleStatus($id));

        // 启用时，如果活动开始时间已过且状态为 pending，立即激活
        if ($result && ! $groupBuy->is_enabled) {
            // was disabled, now enabled
            $this->activateIfStarted($groupBuy->start_time, $id);
        }

        return $result;
    }

    /**
     * 如果开始时间已过，立即激活活动并预热库存缓存.
     * $id 为 null 时表示刚创建的活动，通过查询最新一条获取.
     */
    private function activateIfStarted(string $startTime, ?int $id = null): void
    {
        try {
            if (Carbon::parse($startTime)->gt(Carbon::now())) {
                return;
            }

            // 刚创建的活动需要查出 ID
            if ($id === null) {
                $id = $this->groupBuyService->findLatestPendingId();
                if ($id === null) {
                    return;
                }
            }

            if (! $this->groupBuyService->isPending($id)) {
                return;
            }

            $this->groupBuyService->start($id);
            $this->cacheService->warmStock($id);
        } catch (\Throwable) {
            // 激活失败不影响主流程，定时任务会兜底
        }
    }
}
