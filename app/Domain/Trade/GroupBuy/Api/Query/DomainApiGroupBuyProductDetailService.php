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

namespace App\Domain\Trade\GroupBuy\Api\Query;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyRepository;
use App\Infrastructure\Model\GroupBuy\GroupBuy;

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
