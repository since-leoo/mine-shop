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

namespace App\Domain\Marketing\Seckill\Listener;

use App\Domain\Marketing\Seckill\Event\SeckillActivityCreatedEvent;
use App\Domain\Marketing\Seckill\Event\SeckillActivityDeletedEvent;
use App\Domain\Marketing\Seckill\Event\SeckillActivityEnabledEvent;
use App\Domain\Marketing\Seckill\Event\SeckillActivityStatusChangedEvent;
use App\Domain\Marketing\Seckill\Event\SeckillActivityUpdatedEvent;
use App\Domain\Marketing\Seckill\Service\SeckillCacheService;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

/**
 * 秒杀活动缓存监听器.
 *
 * 后台管理变更活动/场次时，通过 SeckillCacheService 刷新或清除缓存.
 */
#[Listener]
final class SeckillActivityCacheListener implements ListenerInterface
{
    public function __construct(
        private readonly SeckillCacheService $cacheService
    ) {}

    public function listen(): array
    {
        return [
            SeckillActivityCreatedEvent::class,
            SeckillActivityUpdatedEvent::class,
            SeckillActivityDeletedEvent::class,
            SeckillActivityEnabledEvent::class,
            SeckillActivityStatusChangedEvent::class,
        ];
    }

    public function process(object $event): void
    {
        match (true) {
            $event instanceof SeckillActivityCreatedEvent => $this->cacheService->warmActivity($event->getActivityId()),
            $event instanceof SeckillActivityUpdatedEvent => $this->cacheService->evictActivity($event->getActivityId()),
            $event instanceof SeckillActivityDeletedEvent => $this->cacheService->evictActivity($event->getActivityId()),
            $event instanceof SeckillActivityEnabledEvent => $this->cacheService->evictActivity($event->getActivityId()),
            $event instanceof SeckillActivityStatusChangedEvent => $this->cacheService->evictActivity($event->getActivityId()),
            default => null,
        };
    }
}
