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

namespace App\Application\Api\Review;

use App\Domain\Trade\Review\Api\Query\DomainApiReviewQueryService;

final class AppApiReviewQueryService
{
    public function __construct(
        private readonly DomainApiReviewQueryService $reviewQueryService,
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function listByProduct(int $productId, array $filters, int $page, int $pageSize): array
    {
        return $this->reviewQueryService->listByProduct($productId, $filters, $page, $pageSize);
    }

    /**
     * @return array<string, int>
     */
    public function getProductStats(int $productId): array
    {
        return $this->reviewQueryService->getProductStats($productId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getProductSummary(int $productId, int $limit): array
    {
        return $this->reviewQueryService->getProductSummary($productId, $limit);
    }
}
