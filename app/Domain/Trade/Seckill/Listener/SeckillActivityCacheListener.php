<?php

declare(strict_types=1);

namespace App\Domain\Trade\Seckill\Listener;

use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use App\Domain\Trade\Seckill\Event\SeckillActivityCreatedEvent;
use App\Domain\Trade\Seckill\Event\SeckillActivityDeletedEvent;
use App\Domain\Trade\Seckill\Event\SeckillActivityEnabledEvent;
use App\Domain\Trade\Seckill\Event\SeckillActivityStatusChangedEvent;
use App\Domain\Trade\Seckill\Event\SeckillActivityUpdatedEvent;
use App\Domain\Trade\Seckill\Service\SeckillCacheService;

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
