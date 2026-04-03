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

    /**
     * @param array{list: array, statusTag: string, time: int, banner: string} $data
     * @return array{list: array, statusTag: string, time: int, banner: string, navTabs: array<int, array{key: string, label: string, count: int}>}
     */
    public function transformPromotionList(array $data): array
    {
        $list = $data['list'] ?? [];
        $sceneBuckets = [];

        foreach ($list as $item) {
            $minPeople = max(2, (int) ($item['minPeople'] ?? 0));
            $tabKey = sprintf('people_%d', $minPeople);
            if (! isset($sceneBuckets[$tabKey])) {
                $sceneBuckets[$tabKey] = [
                    'key' => $tabKey,
                    'label' => sprintf('%d人快团', $minPeople),
                    'count' => 0,
                    'sort' => $minPeople,
                ];
            }
            ++$sceneBuckets[$tabKey]['count'];
        }

        usort($sceneBuckets, static fn (array $left, array $right) => $left['sort'] <=> $right['sort']);
        $sceneTabs = array_values(array_map(static fn (array $item): array => [
            'key' => $item['key'],
            'label' => $item['label'],
            'count' => $item['count'],
        ], $sceneBuckets));

        $navTabs = array_merge([[
            'key' => 'direct_join',
            'label' => '可直接参团',
            'count' => count($list),
        ]], $sceneTabs);

        return [
            'list' => $list,
            'statusTag' => (string) ($data['statusTag'] ?? 'expired'),
            'time' => (int) ($data['time'] ?? 0),
            'banner' => (string) ($data['banner'] ?? ''),
            'navTabs' => $navTabs,
        ];
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
