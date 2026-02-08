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

namespace App\Domain\Marketing\GroupBuy\Job;

use App\Domain\Marketing\GroupBuy\Enum\GroupBuyStatus;
use App\Domain\Marketing\GroupBuy\Service\DomainGroupBuyService;
use App\Domain\Marketing\GroupBuy\Service\GroupBuyCacheService;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Psr\Log\LoggerInterface;

/**
 * 拼团活动延迟启动 Job.
 *
 * 在精确的 start_time 执行，将活动状态从 pending → active。
 * 幂等设计：状态非 pending 时跳过，不抛异常。
 */
class GroupBuyStartJob extends Job
{
    /**
     * 最大尝试次数.
     */
    public int $maxAttempts = 3;

    public function __construct(
        protected int $groupBuyId
    ) {}

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        $groupBuyService = $container->get(DomainGroupBuyService::class);
        $logger = $container->get(LoggerInterface::class);

        try {
            // 1. 获取活动，检查状态是否仍为 pending（幂等设计）
            $groupBuy = $groupBuyService->repository->findById($this->groupBuyId);
            if (! $groupBuy) {
                $logger->warning('GroupBuyStartJob: 活动不存在，跳过', [
                    'group_buy_id' => $this->groupBuyId,
                ]);
                return;
            }

            if ($groupBuy->status !== GroupBuyStatus::PENDING->value) {
                $logger->info('GroupBuyStartJob: 活动状态非 pending，跳过', [
                    'group_buy_id' => $this->groupBuyId,
                    'current_status' => $groupBuy->status,
                ]);
                return;
            }

            // 2. 调用领域服务启动活动
            $groupBuyService->start($this->groupBuyId);

            // 3. 预热库存到 Redis（供 Lua 原子扣减）
            $container->get(GroupBuyCacheService::class)->warmStock($this->groupBuyId);

            $logger->info('GroupBuyStartJob: 活动已激活', [
                'group_buy_id' => $this->groupBuyId,
            ]);
        } catch (\Throwable $e) {
            $logger->error('GroupBuyStartJob: 执行失败', [
                'group_buy_id' => $this->groupBuyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
