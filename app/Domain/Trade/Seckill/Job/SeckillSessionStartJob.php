<?php

declare(strict_types=1);

namespace App\Domain\Trade\Seckill\Job;

use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;
use App\Domain\Trade\Seckill\Service\DomainSeckillActivityService;
use App\Domain\Trade\Seckill\Service\DomainSeckillSessionService;
use App\Domain\Trade\Seckill\Service\SeckillCacheService;
use Psr\Log\LoggerInterface;

class SeckillSessionStartJob extends Job
{
    public int $maxAttempts = 3;

    public function __construct(protected int $sessionId, protected int $activityId) {}

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        $sessionService = $container->get(DomainSeckillSessionService::class);
        $activityService = $container->get(DomainSeckillActivityService::class);
        $cacheService = $container->get(SeckillCacheService::class);
        $logger = $container->get(LoggerInterface::class);

        try {
            $session = $sessionService->repository->findById($this->sessionId);
            if (! $session) {
                $logger->warning('SeckillSessionStartJob: 场次不存在，跳过', ['session_id' => $this->sessionId]);
                return;
            }
            if ($session->status !== SeckillStatus::PENDING->value) {
                $logger->info('SeckillSessionStartJob: 场次状态非 pending，跳过', ['session_id' => $this->sessionId, 'current_status' => $session->status]);
                return;
            }

            $sessionService->start($this->sessionId);
            $cacheService->warmSession($this->sessionId);
            $logger->info('SeckillSessionStartJob: 场次已激活并预热缓存', ['session_id' => $this->sessionId, 'activity_id' => $this->activityId]);

            $activity = $activityService->repository->findById($this->activityId);
            if ($activity && $activity->status === SeckillStatus::PENDING->value && $activity->is_enabled) {
                $activityService->start($this->activityId);
                $logger->info('SeckillSessionStartJob: 父活动已联动激活', ['activity_id' => $this->activityId]);
            }
        } catch (\Throwable $e) {
            $logger->error('SeckillSessionStartJob: 执行失败', ['session_id' => $this->sessionId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
