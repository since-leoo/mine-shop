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

namespace App\Infrastructure\Crontab;

use App\Domain\Marketing\GroupBuy\Enum\GroupBuyStatus;
use App\Domain\Marketing\GroupBuy\Job\GroupBuyStartJob;
use App\Domain\Marketing\GroupBuy\Repository\GroupBuyRepository;
use App\Domain\Marketing\GroupBuy\Service\DomainGroupBuyService;
use App\Domain\Marketing\GroupBuy\Service\GroupBuyCacheService;
use Carbon\Carbon;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Crontab\Annotation\Crontab;
use Psr\Log\LoggerInterface;

#[Crontab(
    name: 'group-buy-activity-status',
    rule: '*/10 * * * *',
    callback: 'execute',
    memo: '拼团活动状态自动推进',
    enable: true
)]
class GroupBuyActivityStatusCrontab
{
    public function __construct(
        private readonly GroupBuyRepository $repository,
        private readonly DomainGroupBuyService $groupBuyService,
        private readonly GroupBuyCacheService $groupBuyCacheService,
        private readonly DriverFactory $driverFactory,
        private readonly LoggerInterface $logger
    ) {}

    public function execute(): void
    {
        $this->processPendingActivities();
        $this->processExpiredActivities();
    }

    /**
     * 步骤1：处理待开始的拼团活动.
     *
     * - start_time <= now：直接调用 groupBuyService->start() 兜底
     * - start_time > now AND start_time <= now+30min：推送 GroupBuyStartJob 延迟到 start_time
     */
    private function processPendingActivities(): void
    {
        $activities = $this->repository->findPendingActivitiesWithinMinutes(30);
        $now = Carbon::now();

        foreach ($activities as $activity) {
            try {
                // 跳过 cancelled/sold_out 状态或 is_enabled=false 的记录
                if (\in_array($activity->status, [GroupBuyStatus::CANCELLED->value, GroupBuyStatus::SOLD_OUT->value], true)) {
                    continue;
                }

                if (! $activity->is_enabled) {
                    continue;
                }

                $startTime = Carbon::parse($activity->start_time);

                if ($startTime->lte($now)) {
                    // 兜底：已过开始时间，直接激活
                    $oldStatus = $activity->status;
                    $this->groupBuyService->start($activity->id);
                    $this->groupBuyCacheService->warmStock($activity->id);

                    $this->logger->info('[GroupBuyActivityStatus] 活动兜底激活', [
                        'type' => 'group_buy',
                        'id' => $activity->id,
                        'old_status' => $oldStatus,
                        'new_status' => GroupBuyStatus::ACTIVE->value,
                    ]);
                } else {
                    // 延迟队列：推送 Job 到精确的 start_time
                    $delaySeconds = (int) $startTime->diffInSeconds($now);
                    $job = new GroupBuyStartJob($activity->id);
                    $this->driverFactory->get('default')->push($job, $delaySeconds);

                    $this->logger->info('[GroupBuyActivityStatus] 活动延迟 Job 已推送', [
                        'type' => 'group_buy',
                        'id' => $activity->id,
                        'delay_seconds' => $delaySeconds,
                        'start_time' => $startTime->toDateTimeString(),
                    ]);
                }
            } catch (\Throwable $e) {
                $this->logger->error('[GroupBuyActivityStatus] 处理待开始活动失败', [
                    'type' => 'group_buy',
                    'id' => $activity->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * 步骤2：处理已过期的 active 拼团活动 → 结束.
     */
    private function processExpiredActivities(): void
    {
        $activities = $this->repository->findActiveExpiredActivities();

        foreach ($activities as $activity) {
            try {
                // 跳过 cancelled/sold_out 状态的记录
                if (\in_array($activity->status, [GroupBuyStatus::CANCELLED->value, GroupBuyStatus::SOLD_OUT->value], true)) {
                    continue;
                }

                $oldStatus = $activity->status;
                $this->groupBuyService->end($activity->id);
                $this->groupBuyCacheService->evictStock($activity->id);

                $this->logger->info('[GroupBuyActivityStatus] 活动已结束', [
                    'type' => 'group_buy',
                    'id' => $activity->id,
                    'old_status' => $oldStatus,
                    'new_status' => GroupBuyStatus::ENDED->value,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('[GroupBuyActivityStatus] 处理过期活动失败', [
                    'type' => 'group_buy',
                    'id' => $activity->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
