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

namespace App\Infrastructure\Plugin;

use App\Domain\Infrastructure\Plugin\Contract\ProductReviewCommandInterface;
use App\Domain\Infrastructure\Plugin\Contract\ProductReviewQueryInterface;
use App\Domain\Infrastructure\Plugin\Contract\ReviewStatisticsInterface;
use App\Domain\Trade\Review\Contract\ReviewInput;

final class NullProductReview implements ProductReviewQueryInterface, ProductReviewCommandInterface, ReviewStatisticsInterface
{
    public function enabled(): bool
    {
        return false;
    }

    public function create(int $memberId, ReviewInput $input): int
    {
        throw new \DomainException('商品评价功能未启用');
    }

    public function listByProduct(int $productId, array $filters, int $page, int $pageSize): array
    {
        return ['total' => 0, 'list' => [], 'page' => $page, 'page_size' => $pageSize, 'enabled' => false];
    }

    public function getProductStats(int $productId): array
    {
        return ['total' => 0, 'good' => 0, 'medium' => 0, 'bad' => 0, 'with_images' => 0, 'enabled' => false];
    }

    public function getProductSummary(int $productId, int $limit): array
    {
        return ['total' => 0, 'list' => [], 'enabled' => false];
    }

    public function reviewedOrderItemIds(array $ids): array
    {
        return [];
    }

    public function getStatistics(): array
    {
        return ['today_reviews' => 0, 'pending_reviews' => 0, 'total_reviews' => 0, 'average_rating' => 0, 'enabled' => false];
    }
}
