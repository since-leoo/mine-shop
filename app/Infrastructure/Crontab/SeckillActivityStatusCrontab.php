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

use App\Domain\Marketing\Seckill\Enum\SeckillStatus;
use App\Domain\Marketing\Seckill\Job\SeckillSessionStartJob;
use App\Domain\Marketing\Seckill\Repository\SeckillActivityRepository;
use App\Domain\Marketing\Seckill\Repository\SeckillSessionRepository;
use App\Domain\Marketing\Seckill\Service\DomainSeckillActivityService;
use App\Domain\Marketing\Seckill\Service\DomainSeckillSessionService;
use App\Domain\Marketing\Seckill\Service\SeckillCacheService;
use Carbon\Carbon;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Crontab\Annotation\Crontab;
use Psr\Log\LoggerInterface;

#[Crontab(
    name: 'seckill-activity-status',
    rule: '* *\/10 * * * *',
    callback: 'execute',
    memo: '秒杀活动状态自动推进',
    enable: true
)]
class SeckillActivityStatusCrontab
{
    public function __construct(
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly SeckillActivityRepository $activityRepository,
        private readonly DomainSeckillActivityService $activityService,
        private readonly DomainSeckillSessionService $sessionService,
        private readonly SeckillCacheService $cacheService,
        private readonly DriverFactory $driverFactory,
        private readonly LoggerInterface $logger
    ) {}

    public function execute(): void
    {
        $this->processPendingSessions();
        $this->processExpiredSessions();
        $this->processActivityStart();
        $this->processActivityEnd();
    }

    /**
     * 步骤1：处理待开始的场次.
     *
     * - start_time <= now：直接调用 sessionService->start() 兜底
     * - start_time > now AND start_time <= now+30min：推送 SeckillSessionStartJob 延迟到 start_time
     */
    private function processPendingSessions(): void
    {
        $sessions = $this->sessionRepository->findPendingSessionsWithinMinutes(30);
        $now = Carbon::now();

        foreach ($sessions as $session) {
            try {
                // 跳过 cancelled/sold_out 状态
                if (\in_array($session->status, [SeckillStatus::CANCELLED->value, SeckillStatus::SOLD_OUT->value], true)) {
                    continue;
                }

                $startTime = Carbon::parse($session->start_time);

                if ($startTime->lte($now)) {
                    // 兜底：已过开始时间，直接激活
                    $oldStatus = $session->status;
                    $this->sessionService->start($session->id);
                    $this->cacheService->warmSession($session->id);

                    $this->logger->info('[SeckillActivityStatus] 场次兜底激活并预热缓存', [
                        'type' => 'seckill_session',
                        'id' => $session->id,
                        'activity_id' => $session->activity_id,
                        'old_status' => $oldStatus,
                        'new_status' => SeckillStatus::ACTIVE->value,
                    ]);
                } else {
                    // 延迟队列：推送 Job 到精确的 start_time
                    $delaySeconds = (int) $startTime->diffInSeconds($now);
                    $job = new SeckillSessionStartJob($session->id, $session->activity_id);
                    $this->driverFactory->get('default')->push($job, $delaySeconds);

                    $this->logger->info('[SeckillActivityStatus] 场次延迟 Job 已推送', [
                        'type' => 'seckill_session',
                        'id' => $session->id,
                        'activity_id' => $session->activity_id,
                        'delay_seconds' => $delaySeconds,
                        'start_time' => $startTime->toDateTimeString(),
                    ]);
                }
            } catch (\Throwable $e) {
                $this->logger->error('[SeckillActivityStatus] 处理待开始场次失败', [
                    'type' => 'seckill_session',
                    'id' => $session->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * 步骤2：处理已过期的 active 场次 → 结束.
     */
    private function processExpiredSessions(): void
    {
        $sessions = $this->sessionRepository->findActiveExpiredSessions();

        foreach ($sessions as $session) {
            try {
                // 跳过 cancelled/sold_out 状态
                if (\in_array($session->status, [SeckillStatus::CANCELLED->value, SeckillStatus::SOLD_OUT->value], true)) {
                    continue;
                }

                $oldStatus = $session->status;
                $this->sessionService->end($session->id);

                $this->logger->info('[SeckillActivityStatus] 场次已结束', [
                    'type' => 'seckill_session',
                    'id' => $session->id,
                    'activity_id' => $session->activity_id,
                    'old_status' => $oldStatus,
                    'new_status' => SeckillStatus::ENDED->value,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('[SeckillActivityStatus] 处理过期场次失败', [
                    'type' => 'seckill_session',
                    'id' => $session->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * 步骤3：处理活动联动激活.
     *
     * 查询 pending+enabled 的活动，检查是否有 active 场次 → 激活活动
     */
    private function processActivityStart(): void
    {
        $activities = $this->activityRepository->findPendingEnabledActivities();

        foreach ($activities as $activity) {
            try {
                // 跳过 cancelled/sold_out 状态
                if (\in_array($activity->status, [SeckillStatus::CANCELLED->value, SeckillStatus::SOLD_OUT->value], true)) {
                    continue;
                }

                // 检查是否有 active 场次
                $sessions = $this->sessionRepository->findByActivityId($activity->id);
                $hasActiveSession = false;

                foreach ($sessions as $session) {
                    if ($session->status === SeckillStatus::ACTIVE->value) {
                        $hasActiveSession = true;
                        break;
                    }
                }

                if ($hasActiveSession) {
                    $oldStatus = $activity->status;
                    $this->activityService->start($activity->id);

                    $this->logger->info('[SeckillActivityStatus] 活动联动激活', [
                        'type' => 'seckill_activity',
                        'id' => $activity->id,
                        'old_status' => $oldStatus,
                        'new_status' => SeckillStatus::ACTIVE->value,
                    ]);
                }
            } catch (\Throwable $e) {
                $this->logger->error('[SeckillActivityStatus] 处理活动联动激活失败', [
                    'type' => 'seckill_activity',
                    'id' => $activity->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * 步骤4：处理活动联动结束.
     *
     * 查询 active 的活动，检查是否所有场次都 ended/cancelled → 结束活动
     */
    private function processActivityEnd(): void
    {
        $activities = $this->activityRepository->findActiveActivities();

        foreach ($activities as $activity) {
            try {
                // 跳过 cancelled/sold_out 状态
                if (\in_array($activity->status, [SeckillStatus::CANCELLED->value, SeckillStatus::SOLD_OUT->value], true)) {
                    continue;
                }

                $sessions = $this->sessionRepository->findByActivityId($activity->id);

                // 如果没有场次，跳过
                if (empty($sessions)) {
                    continue;
                }

                // 检查是否所有场次都已 ended 或 cancelled
                $allFinished = true;
                foreach ($sessions as $session) {
                    if (! \in_array($session->status, [SeckillStatus::ENDED->value, SeckillStatus::CANCELLED->value], true)) {
                        $allFinished = false;
                        break;
                    }
                }

                if ($allFinished) {
                    $oldStatus = $activity->status;
                    $this->activityService->end($activity->id);

                    $this->logger->info('[SeckillActivityStatus] 活动联动结束', [
                        'type' => 'seckill_activity',
                        'id' => $activity->id,
                        'old_status' => $oldStatus,
                        'new_status' => SeckillStatus::ENDED->value,
                    ]);
                }
            } catch (\Throwable $e) {
                $this->logger->error('[SeckillActivityStatus] 处理活动联动结束失败', [
                    'type' => 'seckill_activity',
                    'id' => $activity->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
