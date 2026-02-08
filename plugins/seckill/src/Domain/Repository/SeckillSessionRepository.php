<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Repository;

use App\Infrastructure\Abstract\IRepository;
use Plugin\Since\Seckill\Domain\Entity\SeckillSessionEntity;
use Plugin\Since\Seckill\Domain\Enum\SeckillStatus;
use Plugin\Since\Seckill\Infrastructure\Model\SeckillSession;
use Carbon\Carbon;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<SeckillSession>
 */
final class SeckillSessionRepository extends IRepository
{
    public function __construct(protected readonly SeckillSession $model) {}

    public function createFromEntity(SeckillSessionEntity $entity): SeckillSession
    {
        $session = SeckillSession::create($entity->toArray());
        $entity->setId((int) $session->id);
        return $session;
    }

    public function updateFromEntity(SeckillSessionEntity $entity): bool
    {
        $session = SeckillSession::find($entity->getId());
        return $session && $session->update($entity->toArray());
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['activity_id']), static fn (Builder $q) => $q->where('activity_id', $params['activity_id']))
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['is_enabled']), static fn (Builder $q) => $q->where('is_enabled', (bool) $params['is_enabled']))
            ->with('activity')->withCount('products')
            ->orderBy('sort_order')->orderBy('start_time', 'desc');
    }

    public function findByActivityId(int $activityId): array
    {
        return SeckillSession::where('activity_id', $activityId)->withCount('products')->orderBy('sort_order')->orderBy('start_time')->get()->all();
    }

    public function countByActivityId(int $activityId): int
    {
        return SeckillSession::where('activity_id', $activityId)->count();
    }

    public function updateQuantityStats(int $sessionId): void
    {
        $session = SeckillSession::find($sessionId);
        if (!$session) { return; }
        $products = $session->products()->where('is_enabled', true)->get();
        $session->update(['total_quantity' => $products->sum('quantity'), 'sold_quantity' => $products->sum('sold_quantity')]);
    }

    public function findPendingSessionsWithinMinutes(int $minutes): array
    {
        return SeckillSession::where('status', SeckillStatus::PENDING->value)->where('is_enabled', true)
            ->where('start_time', '<=', Carbon::now()->addMinutes($minutes))->get()->all();
    }

    public function findActiveExpiredSessions(): array
    {
        return SeckillSession::where('status', SeckillStatus::ACTIVE->value)->where('end_time', '<', Carbon::now())->get()->all();
    }

    public function findNearestEnabledActiveOrPending(int $activityId): ?SeckillSession
    {
        return SeckillSession::where('activity_id', $activityId)->where('is_enabled', true)
            ->whereIn('status', [SeckillStatus::ACTIVE->value, SeckillStatus::PENDING->value])
            ->orderBy('start_time')->first();
    }
}
