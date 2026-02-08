<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Domain\Api\Query;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use Plugin\Since\GroupBuy\Infrastructure\Model\GroupBuy;
use Plugin\Since\GroupBuy\Domain\Repository\GroupBuyRepository;

final class DomainApiGroupBuyProductDetailService
{
    public function __construct(
        private readonly ProductSnapshotInterface $productSnapshotService,
        private readonly GroupBuyRepository $groupBuyRepository
    ) {}

    /** @return null|array{product: array, groupBuy: GroupBuy} */
    public function getDetail(int $activityId, int $spuId): ?array
    {
        $groupBuy = $this->groupBuyRepository->findById($activityId);
        if (! $groupBuy || ! $groupBuy->is_enabled) {
            return null;
        }
        if ((int) $groupBuy->product_id !== $spuId) {
            return null;
        }
        $product = $this->productSnapshotService->getProduct($spuId, ['skus', 'attributes', 'gallery']);
        if (! $product) {
            return null;
        }
        return ['product' => $product, 'groupBuy' => $groupBuy];
    }
}
