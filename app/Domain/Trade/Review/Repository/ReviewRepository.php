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

namespace App\Domain\Trade\Review\Repository;

use App\Domain\Trade\Review\Entity\ReviewEntity;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Review\Review;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

/**
 * 评价仓储。
 *
 * @extends IRepository<Review>
 */
final class ReviewRepository extends IRepository
{
    public function __construct(protected readonly Review $model) {}

    /**
     * 根据实体创建评价记录。
     */
    public function createFromEntity(ReviewEntity $entity): Review
    {
        $review = Review::create($entity->toArray());
        $entity->setId((int) $review->id);

        return $review;
    }

    /**
     * 根据实体更新评价记录。
     */
    public function updateFromEntity(ReviewEntity $entity): bool
    {
        $review = Review::find($entity->getId());
        if ($review === null) {
            return false;
        }

        return $review->update($entity->toArray());
    }

    /**
     * 统一补充后台列表需要的展示字段。
     */
    public function handleItems(Collection $items): Collection
    {
        return $items->map(function (Review $review): array {
            $data = $review->toArray();
            $orderItem = $this->getLoadedRelation($review, 'orderItem');
            $member = $this->getLoadedRelation($review, 'member');
            $order = $this->getLoadedRelation($review, 'order');

            if ($order === null && is_object($orderItem) && method_exists($orderItem, 'relationLoaded') && $orderItem->relationLoaded('order') && method_exists($orderItem, 'getRelation')) {
                $order = $orderItem->getRelation('order');
            }

            $data['product_name'] = (string) ((is_object($orderItem) && isset($orderItem->product_name)) ? $orderItem->product_name : '');
            $data['member_nickname'] = (string) ((is_object($member) && isset($member->nickname)) ? $member->nickname : '');
            $data['order_no'] = (string) ((is_object($order) && isset($order->order_no)) ? $order->order_no : '');

            return $data;
        });
    }

    /**
     * 分页查询商品已通过的评价列表。
     *
     * @param array{rating_level?: string, has_images?: bool} $filters
     */
    public function getApprovedProductReviews(int $productId, array $filters, int $page, int $pageSize): Collection
    {
        return $this->buildApprovedProductQuery($productId, $filters)
            ->orderByDesc('created_at')
            ->offset(max($page - 1, 0) * $pageSize)
            ->limit($pageSize)
            ->get();
    }

    /**
     * 统计商品已通过的评价总数。
     *
     * @param array{rating_level?: string, has_images?: bool} $filters
     */
    public function countApprovedProductReviews(int $productId, array $filters = []): int
    {
        return $this->buildApprovedProductQuery($productId, $filters)->count();
    }

    /**
     * 获取商品评价摘要列表。
     */
    public function getApprovedProductSummary(int $productId, int $limit): Collection
    {
        return $this->buildApprovedProductQuery($productId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * 按 ID 查询评价详情，并补充商品与用户关联。
     */
    public function findById(int $id): ?object
    {
        return $this->getQuery()
            ->with(['member', 'orderItem.order', 'order'])
            ->whereKey($id)
            ->first();
    }

    /**
     * 根据订单项 ID 查找评价。
     */
    public function findByOrderItemId(int $orderItemId): ?Review
    {
        return Review::where('order_item_id', $orderItemId)->first();
    }

    /**
     * 判断订单项是否已评价。
     */
    public function existsByOrderItemId(int $orderItemId): bool
    {
        return Review::where('order_item_id', $orderItemId)->exists();
    }

    /**
     * 获取商品评价统计（仅统计已通过的评价）。
     *
     * @return array{total: int, good: int, medium: int, bad: int, with_images: int}
     */
    public function getProductStats(int $productId): array
    {
        $query = $this->buildApprovedProductQuery($productId);

        return [
            'total' => (clone $query)->count(),
            'good' => (clone $query)->whereBetween('rating', [4, 5])->count(),
            'medium' => (clone $query)->where('rating', 3)->count(),
            'bad' => (clone $query)->whereBetween('rating', [1, 2])->count(),
            'with_images' => (clone $query)->whereNotNull('images')->count(),
        ];
    }

    /**
     * 处理评价搜索条件。
     */
    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->with(['member', 'orderItem.order', 'order'])
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['rating']), static fn (Builder $q) => $q->where('rating', $params['rating']))
            ->when(isset($params['product_id']), static fn (Builder $q) => $q->where('product_id', $params['product_id']))
            ->when(isset($params['member_id']), static fn (Builder $q) => $q->where('member_id', $params['member_id']))
            ->when(isset($params['start_date']), static fn (Builder $q) => $q->where('created_at', '>=', $params['start_date']))
            ->when(isset($params['end_date']), static fn (Builder $q) => $q->where('created_at', '<=', $params['end_date']))
            ->orderByDesc('id');
    }

    /**
     * 获取评价统计数据（仪表盘用）。
     *
     * @return array{today_reviews: int, pending_reviews: int, total_reviews: int, average_rating: float}
     */
    public function getStatistics(): array
    {
        $today = Carbon::today();

        return [
            'today_reviews' => Review::whereDate('created_at', $today)->count(),
            'pending_reviews' => Review::where('status', 'pending')->count(),
            'total_reviews' => Review::count(),
            'average_rating' => round((float) Review::avg('rating'), 1),
        ];
    }

    /**
     * 安全读取已加载关联，避免在列表转换阶段触发懒加载。
     */
    private function getLoadedRelation(Review $review, string $relation): ?object
    {
        if (! method_exists($review, 'relationLoaded') || ! $review->relationLoaded($relation)) {
            return null;
        }

        if (! method_exists($review, 'getRelation')) {
            return null;
        }

        $loaded = $review->getRelation($relation);

        return is_object($loaded) ? $loaded : null;
    }

    /**
     * 构建商品已通过评价查询。
     *
     * @param array{rating_level?: string, has_images?: bool} $filters
     */
    private function buildApprovedProductQuery(int $productId, array $filters = []): Builder
    {
        $query = Review::query()
            ->with([
                'member:id,nickname,avatar',
                'orderItem:id,order_id,product_id,sku_id,product_name,sku_name,product_image',
            ])
            ->where('product_id', $productId)
            ->where('status', 'approved');

        if (isset($filters['rating_level'])) {
            $query = match ($filters['rating_level']) {
                'good' => $query->whereBetween('rating', [4, 5]),
                'medium' => $query->where('rating', 3),
                'bad' => $query->whereBetween('rating', [1, 2]),
                default => $query,
            };
        }

        if (! empty($filters['has_images'])) {
            $query->whereNotNull('images');
        }

        return $query;
    }
}
