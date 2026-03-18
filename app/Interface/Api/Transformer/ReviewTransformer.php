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

namespace App\Interface\Api\Transformer;

use App\Infrastructure\Model\Review\Review;

final class ReviewTransformer
{
    /**
     * @param array{total: int, list: iterable<Review>, page: int, page_size: int} $result
     * @return array{total: int, list: array<int, array<string, mixed>>, page: int, page_size: int}
     */
    public function transformListResult(array $result): array
    {
        $list = [];
        foreach ($result['list'] as $review) {
            $list[] = $this->transform($review);
        }

        return [
            'total' => (int) $result['total'],
            'list' => $list,
            'page' => (int) $result['page'],
            'page_size' => (int) $result['page_size'],
        ];
    }

    /**
     * @param array{total: int, list: iterable<Review>} $result
     * @return array{total: int, list: array<int, array<string, mixed>>}
     */
    public function transformSummaryResult(array $result): array
    {
        $list = [];
        foreach ($result['list'] as $review) {
            $list[] = $this->transform($review);
        }

        return [
            'total' => (int) $result['total'],
            'list' => $list,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(Review $review): array
    {
        $nickname = (string) ($review->member?->nickname ?? '????');
        $avatar = (string) ($review->member?->avatar ?? '');

        if ((bool) $review->is_anonymous) {
            $nickname = self::desensitizeNickname($nickname);
            $avatar = '';
        }

        return [
            'id' => (int) $review->id,
            'rating' => (int) $review->rating,
            'content' => (string) $review->content,
            'images' => $review->images ?? [],
            'is_anonymous' => (bool) $review->is_anonymous,
            'nickname' => $nickname,
            'avatar' => $avatar,
            'sku_name' => (string) ($review->orderItem?->sku_name ?? ''),
            'admin_reply' => $review->admin_reply,
            'reply_time' => $review->reply_time?->toDateTimeString(),
            'created_at' => $review->created_at?->toDateTimeString(),
        ];
    }

    /**
     * æµç§°è±æå¤çã
     */
    public static function desensitizeNickname(string $nickname): string
    {
        $length = mb_strlen($nickname);

        if ($length <= 0) {
            return '????';
        }

        if ($length === 1) {
            return $nickname . '***';
        }

        return mb_substr($nickname, 0, 1) . '***' . mb_substr($nickname, -1, 1);
    }
}
