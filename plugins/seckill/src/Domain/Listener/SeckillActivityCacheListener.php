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

namespace Plugin\Since\Seckill\Domain\Listener;

use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Plugin\Since\Seckill\Domain\Event\SeckillActivityCreatedEvent;
use Plugin\Since\Seckill\Domain\Event\SeckillActivityDeletedEvent;
use Plugin\Since\Seckill\Domain\Event\SeckillActivityEnabledEvent;
use Plugin\Since\Seckill\Domain\Event\SeckillActivityStatusChangedEvent;
use Plugin\Since\Seckill\Domain\Event\SeckillActivityUpdatedEvent;
use Plugin\Since\Seckill\Domain\Service\SeckillCacheService;

#[Listener]
final class SeckillActivityCacheListener implements ListenerInterface
{
    public function __construct(private readonly SeckillCacheService $cacheService) {}

    public function listen(): array
    {
        return [
            SeckillActivityCreatedEvent::class, SeckillActivityUpdatedEvent::class,
            SeckillActivityDeletedEvent::class, SeckillActivityEnabledEvent::class,
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
