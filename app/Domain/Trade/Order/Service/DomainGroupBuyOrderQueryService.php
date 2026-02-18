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

namespace App\Domain\Trade\Order\Service;

use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyRepository;
use App\Infrastructure\Model\GroupBuy\GroupBuyOrder;
use App\Infrastructure\Model\Order\OrderItem;
use Hyperf\DbConnection\Db;

final class DomainGroupBuyOrderQueryService
{
    public function __construct(
        private readonly GroupBuyRepository $groupBuyRepository,
        private readonly GroupBuyOrderRepository $orderRepository,
    ) {}

    /**
     * 以拼团活动为维度的汇总列表（分页）.
     *
     * @return array{list: array, total: int}
     */
    public function activitySummaryPage(array $filters, int $page, int $pageSize): array
    {
        $paginated = $this->groupBuyRepository->handleSearch(
            $this->groupBuyRepository->getQuery(),
            $filters
        )->paginate(perPage: $pageSize, page: $page);

        $activities = collect($paginated->items());
        $activityIds = $activities->pluck('id')->toArray();

        if (empty($activityIds)) {
            return ['list' => [], 'total' => $paginated->total()];
        }

        $groupStats = GroupBuyOrder::whereIn('group_buy_id', $activityIds)
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->selectRaw('group_buy_id, count(*) as buyer_count, sum(total_amount) as total_amount')
            ->groupBy('group_buy_id')
            ->get()
            ->keyBy('group_buy_id');

        $skuIds = $activities->pluck('sku_id')->unique()->toArray();
        $originalCounts = $this->countOriginalBuyers($skuIds, $activities);

        $list = $activities->map(static function ($activity) use ($groupStats, $originalCounts) {
            $stats = $groupStats->get($activity->id);
            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'product_name' => $activity->product?->name ?? '',
                'product_image' => $activity->product?->main_image ?? '',
                'status' => $activity->status,
                'start_time' => $activity->start_time?->toDateTimeString(),
                'end_time' => $activity->end_time?->toDateTimeString(),
                'min_people' => $activity->min_people,
                'group_count' => $activity->group_count,
                'success_group_count' => $activity->success_group_count,
                'group_buyer_count' => (int) ($stats?->buyer_count ?? 0),
                'original_buyer_count' => $originalCounts[$activity->id] ?? 0,
                'group_buyer_amount' => (int) ($stats?->total_amount ?? 0),
                'total_quantity' => $activity->total_quantity,
                'sold_quantity' => $activity->sold_quantity,
            ];
        })->toArray();

        return ['list' => $list, 'total' => $paginated->total()];
    }

    /**
     * 某个拼团活动下的拼团订单列表（分页）.
     *
     * @return array{list: array, total: int}
     */
    public function ordersByActivity(int $activityId, array $filters, int $page, int $pageSize): array
    {
        $filters['group_buy_id'] = $activityId;

        $query = $this->orderRepository->handleSearch(
            $this->orderRepository->getQuery(),
            $filters
        )->with([
            'member' => static fn ($q) => $q->select('id', 'nickname', 'phone'),
            'order' => static fn ($q) => $q->select('id', 'order_no', 'status', 'pay_amount', 'pay_status', 'created_at')->with('address'),
        ]);

        $paginated = $query->paginate(perPage: $pageSize, page: $page);

        $list = collect($paginated->items())->map(static fn (GroupBuyOrder $row) => [
            'id' => $row->id,
            'group_no' => $row->group_no,
            'is_leader' => $row->is_leader,
            'member_nickname' => $row->member?->nickname ?? '',
            'member_phone' => $row->order?->address?->phone ?? $row->member?->phone ?? '',
            'quantity' => $row->quantity,
            'original_price' => $row->original_price,
            'group_price' => $row->group_price,
            'total_amount' => $row->total_amount,
            'status' => $row->status,
            'join_time' => $row->join_time?->toDateTimeString(),
            'expire_time' => $row->expire_time?->toDateTimeString(),
            'order_no' => $row->order?->order_no ?? '',
            'order_status' => $row->order?->status ?? '',
            'pay_amount' => $row->order?->pay_amount ?? 0,
        ])->toArray();

        return ['list' => $list, 'total' => $paginated->total()];
    }

    private function countOriginalBuyers(array $skuIds, $activities): array
    {
        if (empty($skuIds)) {
            return [];
        }

        $skuToActivity = [];
        foreach ($activities as $a) {
            $skuToActivity[$a->sku_id] = $a->id;
        }

        $rows = OrderItem::whereIn('sku_id', $skuIds)
            ->whereHas('order', static function ($q) {
                $q->where('order_type', 'group_buy')
                    ->where('status', '!=', 'cancelled')
                    ->whereNotExists(static function ($sub) {
                        $sub->select(Db::raw(1))
                            ->from('group_buy_orders')
                            ->whereColumn('group_buy_orders.order_id', 'orders.id');
                    });
            })
            ->selectRaw('sku_id, COUNT(DISTINCT order_id) as cnt')
            ->groupBy('sku_id')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $aid = $skuToActivity[$row->sku_id] ?? null;
            if ($aid) {
                $result[$aid] = ($result[$aid] ?? 0) + (int) $row->cnt;
            }
        }

        return $result;
    }
}
