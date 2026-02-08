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

namespace App\Domain\Marketing\Seckill\Job;

use App\Domain\Marketing\Seckill\Enum\SeckillStatus;
use App\Domain\Marketing\Seckill\Service\DomainSeckillActivityService;
use App\Domain\Marketing\Seckill\Service\DomainSeckillSessionService;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Psr\Log\LoggerInterface;

/**
 * 秒杀场次延迟启动 Job.
 *
 * 在精确的 start_time 执行，将场次状态从 pending → active。
 * 幂等设计：状态非 pending 时跳过，不抛异常。
 * 联动检查：场次激活后，检查父活动是否需要从 pending → active。
 */
class SeckillSessionStartJob extends Job
{
    /**
     * 最大尝试次数.
     */
    public int $maxAttempts = 3;

    public function __construct(
        protected int $sessionId,
        protected int $activityId
    ) {}

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        $sessionService = $container->get(DomainSeckillSessionService::class);
        $activityService = $container->get(DomainSeckillActivityService::class);
        $cacheService = $container->get(\App\Domain\Marketing\Seckill\Service\SeckillCacheService::class);
        $logger = $container->get(LoggerInterface::class);

        try {
            // 1. 获取场次，检查状态是否仍为 pending（幂等设计）
            $session = $sessionService->repository->findById($this->sessionId);
            if (! $session) {
                $logger->warning('SeckillSessionStartJob: 场次不存在，跳过', [
                    'session_id' => $this->sessionId,
                    'activity_id' => $this->activityId,
                ]);
                return;
            }

            if ($session->status !== SeckillStatus::PENDING->value) {
                $logger->info('SeckillSessionStartJob: 场次状态非 pending，跳过', [
                    'session_id' => $this->sessionId,
                    'activity_id' => $this->activityId,
                    'current_status' => $session->status,
                ]);
                return;
            }

            // 2. 调用领域服务启动场次
            $sessionService->start($this->sessionId);

            // 3. 预热场次缓存（商品库存、场次信息）
            $cacheService->warmSession($this->sessionId);

            $logger->info('SeckillSessionStartJob: 场次已激活并预热缓存', [
                'session_id' => $this->sessionId,
                'activity_id' => $this->activityId,
            ]);

            // 3. 联动检查：父活动是否需要从 pending → active
            $activity = $activityService->repository->findById($this->activityId);
            if ($activity
                && $activity->status === SeckillStatus::PENDING->value
                && $activity->is_enabled
            ) {
                $activityService->start($this->activityId);

                $logger->info('SeckillSessionStartJob: 父活动已联动激活', [
                    'session_id' => $this->sessionId,
                    'activity_id' => $this->activityId,
                ]);
            }
        } catch (\Throwable $e) {
            $logger->error('SeckillSessionStartJob: 执行失败', [
                'session_id' => $this->sessionId,
                'activity_id' => $this->activityId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
