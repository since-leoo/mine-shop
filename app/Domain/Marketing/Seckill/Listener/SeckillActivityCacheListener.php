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
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * 秒杀活动缓存监听器.
 */
#[Listener]
final class SeckillActivityCacheListener implements ListenerInterface
{
    private const CACHE_PREFIX = 'seckill:activity:';

    private const CACHE_LIST_KEY = 'seckill:activity:list';

    private const CACHE_STATISTICS_KEY = 'seckill:activity:statistics';

    private const CACHE_TTL = 3600; // 1小时

    public function __construct(
        private readonly ContainerInterface $container
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
        $cache = $this->container->get(CacheInterface::class);

        match (true) {
            $event instanceof SeckillActivityCreatedEvent => $this->handleCreated($cache, $event),
            $event instanceof SeckillActivityUpdatedEvent => $this->handleUpdated($cache, $event),
            $event instanceof SeckillActivityDeletedEvent => $this->handleDeleted($cache, $event),
            $event instanceof SeckillActivityEnabledEvent => $this->handleEnabled($cache, $event),
            $event instanceof SeckillActivityStatusChangedEvent => $this->handleStatusChanged($cache, $event),
            default => null,
        };
    }

    private function handleCreated(CacheInterface $cache, SeckillActivityCreatedEvent $event): void
    {
        // 清除列表缓存
        $cache->delete(self::CACHE_LIST_KEY);
        // 清除统计缓存
        $cache->delete(self::CACHE_STATISTICS_KEY);

        // 缓存新创建的活动
        $cacheKey = self::CACHE_PREFIX . $event->getActivityId();
        $cache->set($cacheKey, $event->getActivity()->toArray(), self::CACHE_TTL);
    }

    private function handleUpdated(CacheInterface $cache, SeckillActivityUpdatedEvent $event): void
    {
        $activityId = $event->getActivityId();

        // 删除活动详情缓存
        $cache->delete(self::CACHE_PREFIX . $activityId);

        // 如果标题或状态变更，清除列表缓存
        if ($event->hasFieldChanged('title') || $event->hasFieldChanged('status') || $event->hasFieldChanged('is_enabled')) {
            $cache->delete(self::CACHE_LIST_KEY);
        }

        // 如果状态变更，清除统计缓存
        if ($event->hasFieldChanged('status') || $event->hasFieldChanged('is_enabled')) {
            $cache->delete(self::CACHE_STATISTICS_KEY);
        }
    }

    private function handleDeleted(CacheInterface $cache, SeckillActivityDeletedEvent $event): void
    {
        // 删除活动详情缓存
        $cache->delete(self::CACHE_PREFIX . $event->getActivityId());
        // 清除列表缓存
        $cache->delete(self::CACHE_LIST_KEY);
        // 清除统计缓存
        $cache->delete(self::CACHE_STATISTICS_KEY);
    }

    private function handleEnabled(CacheInterface $cache, SeckillActivityEnabledEvent $event): void
    {
        // 删除活动详情缓存
        $cache->delete(self::CACHE_PREFIX . $event->getActivityId());
        // 清除列表缓存
        $cache->delete(self::CACHE_LIST_KEY);
        // 清除统计缓存
        $cache->delete(self::CACHE_STATISTICS_KEY);
    }

    private function handleStatusChanged(CacheInterface $cache, SeckillActivityStatusChangedEvent $event): void
    {
        // 删除活动详情缓存
        $cache->delete(self::CACHE_PREFIX . $event->getActivityId());
        // 清除列表缓存
        $cache->delete(self::CACHE_LIST_KEY);
        // 清除统计缓存
        $cache->delete(self::CACHE_STATISTICS_KEY);
    }
}
