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

namespace Plugin\ProductReview\Interface\Adapter;

use App\Domain\Infrastructure\Plugin\Contract\ProductReviewCommandInterface;
use App\Domain\Infrastructure\Plugin\Contract\ProductReviewQueryInterface;
use App\Domain\Infrastructure\Plugin\Contract\ReviewStatisticsInterface;
use App\Domain\Trade\Review\Contract\ReviewInput;
use Plugin\ProductReview\Application\Api\Review\AppApiReviewCommandService;
use Plugin\ProductReview\Application\Api\Review\AppApiReviewQueryService;
use Plugin\ProductReview\Domain\Trade\Review\Repository\ReviewRepository;
use Plugin\ProductReview\Interface\Api\Transformer\ReviewTransformer;

final class ProductReviewGateway implements ProductReviewCommandInterface, ProductReviewQueryInterface, ReviewStatisticsInterface
{
    public function __construct(
        private readonly AppApiReviewCommandService $commands,
        private readonly AppApiReviewQueryService $queries,
        private readonly ReviewRepository $repository,
        private readonly ReviewTransformer $transformer,
    ) {}

    public function enabled(): bool
    {
        return true;
    }

    public function create(int $memberId, ReviewInput $input): int
    {
        return $this->commands->create($memberId, $input);
    }

    public function listByProduct(int $productId, array $filters, int $page, int $pageSize): array
    {
        return $this->transformer->transformListResult($this->queries->listByProduct($productId, $filters, $page, $pageSize)) + ['enabled' => true];
    }

    public function getProductStats(int $productId): array
    {
        return $this->queries->getProductStats($productId) + ['enabled' => true];
    }

    public function getProductSummary(int $productId, int $limit): array
    {
        return $this->transformer->transformSummaryResult($this->queries->getProductSummary($productId, $limit)) + ['enabled' => true];
    }

    public function reviewedOrderItemIds(array $ids): array
    {
        return $this->repository->reviewedOrderItemIds($ids);
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatistics() + ['enabled' => true];
    }
}
