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

namespace App\Domain\Trade\Review\Api\Query;

use App\Domain\Trade\Review\Repository\ReviewRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Review\Review;

/**
 * 小程序评价查询服务.
 */
final class DomainApiReviewQueryService extends IService
{
    public function __construct(
        private readonly ReviewRepository $repository,
    ) {}

    /**
     * 获取商品评价列表（仅返回 approved，支持筛选，按时间倒序）.
     *
     * @param array{rating_level?: string, has_images?: bool} $filters
     * @return array{total: int, list: array, page: int, page_size: int}
     */
    public function listByProduct(int $productId, array $filters, int $page, int $pageSize): array
    {
        $query = Review::with('member:id,nickname,avatar')
            ->where('product_id', $productId)
            ->where('status', 'approved');

        // 按评分等级筛选：good(4-5), medium(3), bad(1-2)
        if (isset($filters['rating_level'])) {
            $query = match ($filters['rating_level']) {
                'good' => $query->whereBetween('rating', [4, 5]),
                'medium' => $query->where('rating', 3),
                'bad' => $query->whereBetween('rating', [1, 2]),
                default => $query,
            };
        }

        // 按是否有图筛选
        if (isset($filters['has_images']) && $filters['has_images']) {
            $query->whereNotNull('images');
        }

        $total = $query->count();

        $reviews = $query->orderByDesc('created_at')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        $list = $reviews->map(fn (Review $review) => $this->formatReview($review))->toArray();

        return [
            'total' => $total,
            'list' => $list,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取商品评价统计（好评/中评/差评/有图数）.
     *
     * @return array{total: int, good: int, medium: int, bad: int, with_images: int}
     */
    public function getProductStats(int $productId): array
    {
        return $this->repository->getProductStats($productId);
    }

    /**
     * 获取商品评价摘要（商品详情页用）.
     *
     * @return array{total: int, list: array}
     */
    public function getProductSummary(int $productId, int $limit = 3): array
    {
        $total = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->count();

        $reviews = Review::with('member:id,nickname,avatar')
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $list = $reviews->map(fn (Review $review) => $this->formatReview($review))->toArray();

        return [
            'total' => $total,
            'list' => $list,
        ];
    }

    /**
     * 格式化评价数据，处理匿名昵称脱敏.
     */
    private function formatReview(Review $review): array
    {
        $nickname = $review->member?->nickname ?? '匿名用户';
        $avatar = $review->member?->avatar ?? '';

        if ($review->is_anonymous) {
            $nickname = self::desensitizeNickname($nickname);
        }

        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'content' => $review->content,
            'images' => $review->images,
            'is_anonymous' => $review->is_anonymous,
            'nickname' => $nickname,
            'avatar' => $review->is_anonymous ? '' : $avatar,
            'admin_reply' => $review->admin_reply,
            'reply_time' => $review->reply_time?->toDateTimeString(),
            'created_at' => $review->created_at?->toDateTimeString(),
        ];
    }

    /**
     * 昵称脱敏处理.
     *
     * 规则：保留首尾字符，中间用 *** 替代
     * 例如："张三丰" → "张***丰"，"张三" → "张***三"，单字符 "张" → "张***"
     */
    public static function desensitizeNickname(string $nickname): string
    {
        $len = mb_strlen($nickname);

        if ($len <= 0) {
            return '匿名用户';
        }

        if ($len === 1) {
            return $nickname . '***';
        }

        $first = mb_substr($nickname, 0, 1);
        $last = mb_substr($nickname, -1, 1);

        return $first . '***' . $last;
    }
}
