<?php

declare(strict_types=1);

namespace App\Interface\Api\Transformer;

use App\Infrastructure\Model\GroupBuy\GroupBuy;

/**
 * 拼团商品详情 Transformer.
 *
 * 基于 ProductTransformer 的商品基础数据，叠加拼团活动信息.
 * 价格固定显示拼团价，SKU 只返回参与拼团的那个.
 */
final class GroupBuyProductTransformer
{
    public function __construct(
        private readonly ProductTransformer $productTransformer
    ) {}

    /**
     * @param array{product: array, groupBuy: GroupBuy} $data
     * @return array<string, mixed>
     */
    public function transformDetail(array $data): array
    {
        $product = $data['product'];
        /** @var GroupBuy $groupBuy */
        $groupBuy = $data['groupBuy'];

        // 获取普通商品详情作为基础
        $base = $this->productTransformer->transformDetail($product);

        // 找到拼团对应的 SKU，只保留这一个
        $targetSkuId = (string) $groupBuy->sku_id;
        $filteredSkuList = array_values(array_filter(
            $base['skuList'],
            static fn (array $sku) => $sku['skuId'] === $targetSkuId
        ));

        // 过滤 specList
        $filteredSpecList = $this->filterSpecList($base['specList'], $filteredSkuList);

        // 拼团库存
        $groupBuyStock = max(0, (int) $groupBuy->total_quantity - (int) $groupBuy->sold_quantity);

        // 覆盖拼团 SKU 的价格为拼团价
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
            // 价格固定为拼团价
            'minSalePrice' => $groupPrice,
            'maxSalePrice' => $groupPrice,
            'maxLinePrice' => $originalPrice,
            // 只返回拼团 SKU
            'skuList' => $filteredSkuList,
            'specList' => $filteredSpecList,
            'spuStockQuantity' => $groupBuyStock,
            'available' => $groupBuyStock > 0 ? 1 : 0,
            // 拼团专属信息
            'activityType' => 'group_buy',
            'activityInfo' => [
                'activityId' => (int) $groupBuy->id,
                'skuId' => $targetSkuId,
                'groupPrice' => $groupPrice,
                'originalPrice' => $originalPrice,
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

    /**
     * 根据拼团 SKU 过滤 specList，只保留相关规格值.
     */
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
