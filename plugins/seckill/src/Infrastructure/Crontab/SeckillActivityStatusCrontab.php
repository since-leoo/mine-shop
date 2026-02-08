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

namespace Plugin\Since\Seckill\Infrastructure\Crontab;

use Carbon\Carbon;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Crontab\Annotation\Crontab;
use Plugin\Since\Seckill\Domain\Enum\SeckillStatus;
use Plugin\Since\Seckill\Domain\Job\SeckillSessionStartJob;
use Plugin\Since\Seckill\Domain\Repository\SeckillActivityRepository;
use Plugin\Since\Seckill\Domain\Repository\SeckillSessionRepository;
use Plugin\Since\Seckill\Domain\Service\DomainSeckillActivityService;
use Plugin\Since\Seckill\Domain\Service\DomainSeckillSessionService;
use Plugin\Since\Seckill\Domain\Service\SeckillCacheService;
use Psr\Log\LoggerInterface;

#[Crontab(name: 'seckill-activity-status', rule: '* *\/10 * * * *', callback: 'execute', memo: '秒杀活动状态自动推进', enable: true)]
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

    private function processPendingSessions(): void
    {
        $sessions = $this->sessionRepository->findPendingSessionsWithinMinutes(30);
        $now = Carbon::now();
        foreach ($sessions as $session) {
            try {
                if (\in_array($session->status, [SeckillStatus::CANCELLED->value, SeckillStatus::SOLD_OUT->value], true)) {
                    continue;
                }
                $startTime = Carbon::parse($session->start_time);
                if ($startTime->lte($now)) {
                    $this->sessionService->start($session->id);
                    $this->cacheService->warmSession($session->id);
                    $this->logger->info('[SeckillActivityStatus] 场次兜底激活并预热缓存', ['id' => $session->id]);
                } else {
                    $delaySeconds = (int) $startTime->diffInSeconds($now);
                    $this->driverFactory->get('default')->push(new SeckillSessionStartJob($session->id, $session->activity_id), $delaySeconds);
                    $this->logger->info('[SeckillActivityStatus] 场次延迟 Job 已推送', ['id' => $session->id, 'delay_seconds' => $delaySeconds]);
                }
            } catch (\Throwable $e) {
                $this->logger->error('[SeckillActivityStatus] 处理待开始场次失败', ['id' => $session->id, 'error' => $e->getMessage()]);
            }
        }
    }

    private function processExpiredSessions(): void
    {
        foreach ($this->sessionRepository->findActiveExpiredSessions() as $session) {
            try {
                if (\in_array($session->status, [SeckillStatus::CANCELLED->value, SeckillStatus::SOLD_OUT->value], true)) {
                    continue;
                }
                $this->sessionService->end($session->id);
                $this->logger->info('[SeckillActivityStatus] 场次已结束', ['id' => $session->id]);
            } catch (\Throwable $e) {
                $this->logger->error('[SeckillActivityStatus] 处理过期场次失败', ['id' => $session->id, 'error' => $e->getMessage()]);
            }
        }
    }

    private function processActivityStart(): void
    {
        foreach ($this->activityRepository->findPendingEnabledActivities() as $activity) {
            try {
                if (\in_array($activity->status, [SeckillStatus::CANCELLED->value, SeckillStatus::SOLD_OUT->value], true)) {
                    continue;
                }
                $hasActive = false;
                foreach ($this->sessionRepository->findByActivityId($activity->id) as $session) {
                    if ($session->status === SeckillStatus::ACTIVE->value) {
                        $hasActive = true;
                        break;
                    }
                }
                if ($hasActive) {
                    $this->activityService->start($activity->id);
                    $this->logger->info('[SeckillActivityStatus] 活动联动激活', ['id' => $activity->id]);
                }
            } catch (\Throwable $e) {
                $this->logger->error('[SeckillActivityStatus] 处理活动联动激活失败', ['id' => $activity->id, 'error' => $e->getMessage()]);
            }
        }
    }

    private function processActivityEnd(): void
    {
        foreach ($this->activityRepository->findActiveActivities() as $activity) {
            try {
                if (\in_array($activity->status, [SeckillStatus::CANCELLED->value, SeckillStatus::SOLD_OUT->value], true)) {
                    continue;
                }
                $sessions = $this->sessionRepository->findByActivityId($activity->id);
                if (empty($sessions)) {
                    continue;
                }
                $allFinished = true;
                foreach ($sessions as $session) {
                    if (! \in_array($session->status, [SeckillStatus::ENDED->value, SeckillStatus::CANCELLED->value], true)) {
                        $allFinished = false;
                        break;
                    }
                }
                if ($allFinished) {
                    $this->activityService->end($activity->id);
                    $this->logger->info('[SeckillActivityStatus] 活动联动结束', ['id' => $activity->id]);
                }
            } catch (\Throwable $e) {
                $this->logger->error('[SeckillActivityStatus] 处理活动联动结束失败', ['id' => $activity->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
