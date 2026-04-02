<?php

declare(strict_types=1);

namespace App\Interface\Api\Transformer\Seckill;

use App\Infrastructure\Model\Seckill\SeckillSession;

final class SeckillSessionTransformer
{
    /**
     * @param array<int, SeckillSession> $sessions
     * @return array<int, array<string, mixed>>
     */
    public function transformList(array $sessions): array
    {
        return array_values(array_map(fn (SeckillSession $session): array => $this->transformItem($session), $sessions));
    }

    /**
     * @return array<string, mixed>
     */
    public function transformItem(SeckillSession $session): array
    {
        $statusTag = $session->getDynamicStatus()->value;
        $remainingMs = max(0, ((int) $session->end_time?->getTimestamp() - time()) * 1000);

        return [
            'id' => (int) $session->id,
            'activityId' => (int) $session->activity_id,
            'time' => $session->start_time?->format('H:i') ?: '--:--',
            'startTime' => $session->start_time?->toDateTimeString(),
            'endTime' => $session->end_time?->toDateTimeString(),
            'status' => match ($statusTag) {
                'active' => 'ongoing',
                'pending' => 'upcoming',
                default => 'ended',
            },
            'statusTag' => $statusTag,
            'remainingTime' => in_array($statusTag, ['active', 'pending'], true) ? $remainingMs : 0,
            'productsCount' => (int) ($session->products_count ?? 0),
        ];
    }
}
