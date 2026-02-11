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

namespace Plugin\Since\GroupBuy\Application\Admin;

use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Plugin\Since\GroupBuy\Domain\Contract\GroupBuyCreateInput;
use Plugin\Since\GroupBuy\Domain\Contract\GroupBuyUpdateInput;
use Plugin\Since\GroupBuy\Domain\Service\DomainGroupBuyService;
use Plugin\Since\GroupBuy\Domain\Service\GroupBuyCacheService;

final class AppGroupBuyCommandService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
        private readonly AppGroupBuyQueryService $queryService,
        private readonly GroupBuyCacheService $cacheService,
    ) {}

    public function create(GroupBuyCreateInput $input): bool
    {
        $result = Db::transaction(fn () => $this->groupBuyService->create($input));

        // 如果活动开始时间已过且已启用，立即激活并预热缓存
        if ($result && $input->getIsEnabled()) {
            $this->activateIfStarted($input->getStartTime());
        }

        return $result;
    }

    public function update(GroupBuyUpdateInput $input): bool
    {
        $result = Db::transaction(fn () => $this->groupBuyService->update($input));

        // 更新后检查：如果活动仍是 pending 且开始时间已过，立即激活
        if ($result && $input->getIsEnabled()) {
            $this->activateIfStarted($input->getStartTime(), $input->getId());
        }

        return $result;
    }

    public function delete(int $id): bool
    {
        $groupBuy = $this->queryService->find($id);
        $groupBuy || throw new \InvalidArgumentException('团购活动不存在');
        return Db::transaction(fn () => $this->groupBuyService->delete($id));
    }

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
                $latest = $this->groupBuyService->repository->getQuery()
                    ->orderByDesc('id')->first();
                if (! $latest || $latest->status !== 'pending') {
                    return;
                }
                $id = (int) $latest->id;
            }

            $model = $this->groupBuyService->repository->findById($id);
            if (! $model || $model->status !== 'pending') {
                return;
            }

            $this->groupBuyService->start($id);
            $this->cacheService->warmStock($id);
        } catch (\Throwable) {
            // 激活失败不影响主流程，定时任务会兜底
        }
    }
}
