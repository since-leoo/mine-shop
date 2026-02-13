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
use Hyperf\Database\Model\Builder;

/**
 * 评价仓储.
 *
 * @extends IRepository<Review>
 */
final class ReviewRepository extends IRepository
{
    public function __construct(protected readonly Review $model) {}

    /**
     * 根据实体创建评价记录.
     */
    public function createFromEntity(ReviewEntity $entity): Review
    {
        $review = Review::create($entity->toArray());
        $entity->setId((int) $review->id);
        return $review;
    }

    /**
     * 根据实体更新评价记录.
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
     * 根据订单项ID查找评价.
     */
    public function findByOrderItemId(int $orderItemId): ?Review
    {
        return Review::where('order_item_id', $orderItemId)->first();
    }

    /**
     * 判断订单项是否已评价.
     */
    public function existsByOrderItemId(int $orderItemId): bool
    {
        return Review::where('order_item_id', $orderItemId)->exists();
    }

    /**
     * 获取商品评价统计（仅统计已通过的评价）.
     *
     * @return array{total: int, good: int, medium: int, bad: int, with_images: int}
     */
    public function getProductStats(int $productId): array
    {
        $query = Review::where('product_id', $productId)->where('status', 'approved');

        return [
            'total' => (clone $query)->count(),
            'good' => (clone $query)->whereBetween('rating', [4, 5])->count(),
            'medium' => (clone $query)->where('rating', 3)->count(),
            'bad' => (clone $query)->whereBetween('rating', [1, 2])->count(),
            'with_images' => (clone $query)->whereNotNull('images')->count(),
        ];
    }

    /**
     * 处理评价搜索查询条件.
     */
    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['rating']), static fn (Builder $q) => $q->where('rating', $params['rating']))
            ->when(isset($params['product_id']), static fn (Builder $q) => $q->where('product_id', $params['product_id']))
            ->when(isset($params['member_id']), static fn (Builder $q) => $q->where('member_id', $params['member_id']))
            ->when(isset($params['start_date']), static fn (Builder $q) => $q->where('created_at', '>=', $params['start_date']))
            ->when(isset($params['end_date']), static fn (Builder $q) => $q->where('created_at', '<=', $params['end_date']))
            ->orderByDesc('id');
    }

    /**
     * 获取评价统计数据（仪表盘用）.
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
}
