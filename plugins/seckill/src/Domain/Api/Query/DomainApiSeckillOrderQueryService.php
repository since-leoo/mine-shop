<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Api\Query;

use Plugin\Since\Seckill\Domain\Repository\SeckillActivityRepository;
use Plugin\Since\Seckill\Domain\Repository\SeckillOrderRepository;
use Plugin\Since\Seckill\Infrastructure\Model\SeckillOrder;

final class DomainApiSeckillOrderQueryService
{
    public function __construct(
        private readonly SeckillActivityRepository $activityRepository,
        private readonly SeckillOrderRepository $orderRepository,
    ) {}

    /**
     * 以秒杀活动为维度的汇总列表（分页）.
     *
     * @return array{list: array, total: int}
     */
    public function activitySummaryPage(array $filters, int $page, int $pageSize): array
    {
        $paginated = $this->activityRepository->handleSearch(
            $this->activityRepository->getQuery(),
            $filters
        )->paginate(perPage: $pageSize, page: $page);

        $activities = collect($paginated->items());
        $activityIds = $activities->pluck('id')->toArray();

        if (empty($activityIds)) {
            return ['list' => [], 'total' => $paginated->total()];
        }

        // 批量统计每个活动的秒杀订单数和总金额
        $orderStats = SeckillOrder::whereIn('activity_id', $activityIds)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('activity_id, count(*) as buyer_count, sum(total_amount) as total_amount')
            ->groupBy('activity_id')
            ->get()
            ->keyBy('activity_id');

        $list = $activities->map(static function ($activity) use ($orderStats) {
            $stats = $orderStats->get($activity->id);
            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'status' => $activity->status,
                'is_enabled' => $activity->is_enabled,
                'sessions_count' => $activity->sessions_count ?? 0,
                'buyer_count' => (int) ($stats?->buyer_count ?? 0),
                'total_amount' => (int) ($stats?->total_amount ?? 0),
                'created_at' => $activity->created_at?->toDateTimeString(),
            ];
        })->toArray();

        return ['list' => $list, 'total' => $paginated->total()];
    }

    /**
     * 某个秒杀活动下的秒杀订单列表（分页）.
     *
     * @return array{list: array, total: int}
     */
    public function ordersByActivity(int $activityId, array $filters, int $page, int $pageSize): array
    {
        $filters['activity_id'] = $activityId;

        $query = $this->orderRepository->handleSearch(
            $this->orderRepository->getQuery(),
            $filters
        )->with([
                'member' => static fn ($q) => $q->select('id', 'nickname', 'phone'),
                'order' => static fn ($q) => $q->select('id', 'order_no', 'status', 'pay_amount', 'pay_status', 'created_at')->with('address'),
                'session',
            ]);

        $paginated = $query->paginate(perPage: $pageSize, page: $page);

        $list = collect($paginated->items())->map(static fn (SeckillOrder $row) => [
            'id' => $row->id,
            'member_nickname' => $row->member?->nickname ?? '',
            'member_phone' => $row->order?->address?->phone ?? $row->member?->phone ?? '',
            'session_time' => $row->session
                ? ($row->session->start_time?->format('H:i') . '-' . $row->session->end_time?->format('H:i'))
                : '',
            'quantity' => $row->quantity,
            'original_price' => $row->original_price,
            'seckill_price' => $row->seckill_price,
            'total_amount' => $row->total_amount,
            'status' => $row->status,
            'seckill_time' => $row->seckill_time?->toDateTimeString(),
            'order_no' => $row->order?->order_no ?? '',
            'order_status' => $row->order?->status ?? '',
            'pay_amount' => $row->order?->pay_amount ?? 0,
        ])->toArray();

        return ['list' => $list, 'total' => $paginated->total()];
    }
}
