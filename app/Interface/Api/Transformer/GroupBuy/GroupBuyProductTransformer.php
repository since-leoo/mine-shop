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

namespace App\Interface\Api\Transformer\GroupBuy;

use App\Infrastructure\Model\GroupBuy\GroupBuy;
use App\Interface\Api\Transformer\ProductTransformer;

final class GroupBuyProductTransformer
{
    public function __construct(
        private readonly ProductTransformer $productTransformer
    ) {}

    /** @param array{product: array, groupBuy: GroupBuy} $data */
    public function transformDetail(array $data): array
    {
        $product = $data['product'];
        /** @var GroupBuy $groupBuy */
        $groupBuy = $data['groupBuy'];
        $base = $this->productTransformer->transformDetail($product);

        $targetSkuId = (string) $groupBuy->sku_id;
        $filteredSkuList = array_values(array_filter(
            $base['skuList'],
            static fn (array $sku) => $sku['skuId'] === $targetSkuId
        ));
        $filteredSpecList = $this->filterSpecList($base['specList'], $filteredSkuList);

        $groupBuyStock = max(0, (int) $groupBuy->total_quantity - (int) $groupBuy->sold_quantity);
        $groupPrice = (int) $groupBuy->group_price;
        $originalPrice = (int) $groupBuy->original_price;

        foreach ($filteredSkuList as &$sku) {
            $sku['priceInfo'] = [
                ['priceType' => 1, 'price' => $groupPrice],
                ['priceType' => 2, 'price' => $originalPrice],
            ];
            $sku['stockInfo']['stockQuantity'] = $groupBuyStock;
        }
        unset($sku);

        return array_merge($base, [
            'minSalePrice' => $groupPrice, 'maxSalePrice' => $groupPrice,
            'maxLinePrice' => $originalPrice,
            'skuList' => $filteredSkuList, 'specList' => $filteredSpecList,
            'spuStockQuantity' => $groupBuyStock,
            'available' => $groupBuyStock > 0 ? 1 : 0,
            'activityType' => 'group_buy',
            'activityInfo' => [
                'activityId' => (int) $groupBuy->id,
                'skuId' => $targetSkuId,
                'groupPrice' => $groupPrice, 'originalPrice' => $originalPrice,
                'stock' => $groupBuyStock,
                'soldQuantity' => (int) $groupBuy->sold_quantity,
                'minPeople' => (int) $groupBuy->min_people,
                'maxPeople' => (int) $groupBuy->max_people,
                'groupTimeLimit' => (int) $groupBuy->group_time_limit,
                'groupCount' => (int) $groupBuy->group_count,
                'successGroupCount' => (int) $groupBuy->success_group_count,
                'startTime' => $groupBuy->start_time?->toDateTimeString(),
                'endTime' => $groupBuy->end_time?->toDateTimeString(),
                'status' => (string) $groupBuy->status,
                'images' => $groupBuy->images ?? [],
            ],
        ]);
    }

    private function filterSpecList(array $specList, array $skuList): array
    {
        if ($skuList === []) {
            return [];
        }
        $usedValueIds = [];
        foreach ($skuList as $sku) {
            foreach ($sku['specInfo'] ?? [] as $spec) {
                $usedValueIds[$spec['specValueId']] = true;
            }
        }
        return array_values(array_filter(array_map(static function (array $spec) use ($usedValueIds) {
            $spec['specValueList'] = array_values(array_filter(
                $spec['specValueList'],
                static fn (array $sv) => isset($usedValueIds[$sv['specValueId']])
            ));
            return $spec['specValueList'] !== [] ? $spec : null;
        }, $specList)));
    }
}
