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

namespace App\Interface\Api\Transformer\Seckill;

use App\Infrastructure\Model\Seckill\SeckillProduct;
use App\Infrastructure\Model\Seckill\SeckillSession;
use App\Interface\Api\Transformer\ProductTransformer;

final class SeckillProductTransformer
{
    public function __construct(private readonly ProductTransformer $productTransformer) {}

    public function transformDetail(array $data): array
    {
        $product = $data['product'];
        /** @var SeckillProduct $seckillProduct */
        $seckillProduct = $data['seckillProduct'];
        /** @var SeckillSession $session */
        $session = $data['session'];

        $base = $this->productTransformer->transformDetail($product);
        $targetSkuId = (string) $seckillProduct->product_sku_id;
        $filteredSkuList = array_values(array_filter($base['skuList'], static fn (array $sku) => $sku['skuId'] === $targetSkuId));
        $filteredSpecList = $this->filterSpecList($base['specList'], $filteredSkuList);

        $seckillStock = max(0, (int) $seckillProduct->quantity - (int) $seckillProduct->sold_quantity);
        $seckillPrice = (int) $seckillProduct->seckill_price;
        $originalPrice = (int) $seckillProduct->original_price;

        foreach ($filteredSkuList as &$sku) {
            $sku['priceInfo'] = [['priceType' => 1, 'price' => $seckillPrice], ['priceType' => 2, 'price' => $originalPrice]];
            $sku['stockInfo']['stockQuantity'] = $seckillStock;
        }
        unset($sku);

        return array_merge($base, [
            'minSalePrice' => $seckillPrice, 'maxSalePrice' => $seckillPrice, 'maxLinePrice' => $originalPrice,
            'skuList' => $filteredSkuList, 'specList' => $filteredSpecList,
            'spuStockQuantity' => $seckillStock, 'available' => $seckillStock > 0 ? 1 : 0,
            'activityType' => 'seckill',
            'activityInfo' => [
                'activityId' => (int) $seckillProduct->activity_id, 'sessionId' => (int) $seckillProduct->session_id,
                'skuId' => $targetSkuId, 'seckillPrice' => $seckillPrice, 'originalPrice' => $originalPrice,
                'stock' => $seckillStock, 'soldQuantity' => (int) $seckillProduct->sold_quantity,
                'maxQuantityPerUser' => (int) ($seckillProduct->max_quantity_per_user ?: $session->max_quantity_per_user),
                'endTime' => $session->end_time?->toDateTimeString(), 'startTime' => $session->start_time?->toDateTimeString(),
                'status' => $session->getDynamicStatus()->value,
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
            $spec['specValueList'] = array_values(array_filter($spec['specValueList'], static fn (array $sv) => isset($usedValueIds[$sv['specValueId']])));
            return $spec['specValueList'] !== [] ? $spec : null;
        }, $specList)));
    }
}
