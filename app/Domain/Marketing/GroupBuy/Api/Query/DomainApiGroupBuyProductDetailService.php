<?php

declare(strict_types=1);

namespace App\Domain\Marketing\GroupBuy\Api\Query;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Marketing\GroupBuy\Repository\GroupBuyRepository;
use App\Infrastructure\Model\GroupBuy\GroupBuy;

/**
 * 拼团商品详情领域查询服务.
 *
 * 组合商品快照 + 拼团活动数据，返回拼团视角的商品详情.
 */
final class DomainApiGroupBuyProductDetailService
{
    public function __construct(
        private readonly ProductSnapshotInterface $productSnapshotService,
        private readonly GroupBuyRepository $groupBuyRepository
    ) {}

    /**
     * 获取拼团商品详情.
     *
     * @return null|array{product: array, groupBuy: GroupBuy}
     */
    public function getDetail(int $activityId, int $spuId): ?array
    {
        // 1. 查找拼团活动
        $groupBuy = $this->groupBuyRepository->findById($activityId);
        if (! $groupBuy || ! $groupBuy->is_enabled) {
            return null;
        }

        // 2. 校验活动关联的商品是否匹配
        if ((int) $groupBuy->product_id !== $spuId) {
            return null;
        }

        // 3. 获取商品基础数据
        $product = $this->productSnapshotService->getProduct($spuId, ['skus', 'attributes', 'gallery']);
        if (! $product) {
            return null;
        }

        return [
            'product' => $product,
            'groupBuy' => $groupBuy,
        ];
    }
}
