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

namespace Plugin\Since\GroupBuy\Infrastructure\Crontab;

use Carbon\Carbon;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Crontab\Annotation\Crontab;
use Plugin\Since\GroupBuy\Domain\Enum\GroupBuyStatus;
use Plugin\Since\GroupBuy\Domain\Job\GroupBuyStartJob;
use Plugin\Since\GroupBuy\Domain\Repository\GroupBuyRepository;
use Plugin\Since\GroupBuy\Domain\Service\DomainGroupBuyService;
use Plugin\Since\GroupBuy\Domain\Service\GroupBuyCacheService;
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

    private function processPendingActivities(): void
    {
        $activities = $this->repository->findPendingActivitiesWithinMinutes(30);
        $now = Carbon::now();

        foreach ($activities as $activity) {
            try {
                if (\in_array($activity->status, [GroupBuyStatus::CANCELLED->value, GroupBuyStatus::SOLD_OUT->value], true)) {
                    continue;
                }
                if (! $activity->is_enabled) {
                    continue;
                }
                $startTime = Carbon::parse($activity->start_time);
                if ($startTime->lte($now)) {
                    $this->groupBuyService->start($activity->id);
                    $this->groupBuyCacheService->warmStock($activity->id);
                    $this->logger->info('[GroupBuyActivityStatus] 活动兜底激活', [
                        'type' => 'group_buy', 'id' => $activity->id,
                    ]);
                } else {
                    $delaySeconds = (int) $startTime->diffInSeconds($now);
                    $this->driverFactory->get('default')->push(new GroupBuyStartJob($activity->id), $delaySeconds);
                    $this->logger->info('[GroupBuyActivityStatus] 活动延迟 Job 已推送', [
                        'type' => 'group_buy', 'id' => $activity->id, 'delay_seconds' => $delaySeconds,
                    ]);
                }
            } catch (\Throwable $e) {
                $this->logger->error('[GroupBuyActivityStatus] 处理待开始活动失败', [
                    'type' => 'group_buy', 'id' => $activity->id, 'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function processExpiredActivities(): void
    {
        $activities = $this->repository->findActiveExpiredActivities();
        foreach ($activities as $activity) {
            try {
                if (\in_array($activity->status, [GroupBuyStatus::CANCELLED->value, GroupBuyStatus::SOLD_OUT->value], true)) {
                    continue;
                }
                $this->groupBuyService->end($activity->id);
                $this->groupBuyCacheService->evictStock($activity->id);
                $this->logger->info('[GroupBuyActivityStatus] 活动已结束', [
                    'type' => 'group_buy', 'id' => $activity->id,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('[GroupBuyActivityStatus] 处理过期活动失败', [
                    'type' => 'group_buy', 'id' => $activity->id, 'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
