<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

interface ProductReviewQueryInterface
{
    public function enabled(): bool;
    public function listByProduct(int $productId, array $filters, int $page, int $pageSize): array;
    public function getProductStats(int $productId): array;
    public function getProductSummary(int $productId, int $limit): array;
    /** @return int[] */
    public function reviewedOrderItemIds(array $ids): array;
}
