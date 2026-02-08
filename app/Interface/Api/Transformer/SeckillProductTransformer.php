<?php

declare(strict_types=1);

namespace App\Interface\Api\Transformer;

use App\Infrastructure\Model\Seckill\SeckillProduct;
use App\Infrastructure\Model\Seckill\SeckillSession;

/**
 * 秒杀商品详情 Transformer.
 *
 * 基于 ProductTransformer 的商品基础数据，叠加秒杀活动信息.
 * 价格固定显示秒杀价，SKU 只返回参与秒杀的那个.
 */
final class SeckillProductTransformer
{
    public function __construct(
        private readonly ProductTransformer $productTransformer
    ) {}

    /**
     * @param array{product: array, seckillProduct: SeckillProduct, session: SeckillSession} $data
     * @return array<string, mixed>
     */
    public function transformDetail(array $data): array
    {
        $product = $data['product'];
        /** @var SeckillProduct $seckillProduct */
        $seckillProduct = $data['seckillProduct'];
        /** @var SeckillSession $session */
        $session = $data['session'];

        // 获取普通商品详情作为基础
        $base = $this->productTransformer->transformDetail($product);

        // 找到秒杀对应的 SKU，只保留这一个
        $targetSkuId = (string) $seckillProduct->product_sku_id;
        $filteredSkuList = array_values(array_filter(
            $base['skuList'],
            static fn (array $sku) => $sku['skuId'] === $targetSkuId
        ));

        // 过滤 specList：只保留秒杀 SKU 涉及的规格值
        $filteredSpecList = $this->filterSpecList($base['specList'], $filteredSkuList);

        // 秒杀库存
        $seckillStock = max(0, (int) $seckillProduct->quantity - (int) $seckillProduct->sold_quantity);

        // 覆盖秒杀 SKU 的价格为秒杀价
        $seckillPrice = (int) $seckillProduct->seckill_price;
        $originalPrice = (int) $seckillProduct->original_price;
        foreach ($filteredSkuList as &$sku) {
            $sku['priceInfo'] = [
                ['priceType' => 1, 'price' => $seckillPrice],
                ['priceType' => 2, 'price' => $originalPrice],
            ];
            $sku['stockInfo']['stockQuantity'] = $seckillStock;
        }
        unset($sku);

        return array_merge($base, [
            // 价格固定为秒杀价
            'minSalePrice' => $seckillPrice,
            'maxSalePrice' => $seckillPrice,
            'maxLinePrice' => $originalPrice,
            // 只返回秒杀 SKU
            'skuList' => $filteredSkuList,
            'specList' => $filteredSpecList,
            'spuStockQuantity' => $seckillStock,
            'available' => $seckillStock > 0 ? 1 : 0,
            // 秒杀专属信息
            'activityType' => 'seckill',
            'activityInfo' => [
                'activityId' => (int) $seckillProduct->activity_id,
                'sessionId' => (int) $seckillProduct->session_id,
                'skuId' => $targetSkuId,
                'seckillPrice' => $seckillPrice,
                'originalPrice' => $originalPrice,
                'stock' => $seckillStock,
                'soldQuantity' => (int) $seckillProduct->sold_quantity,
                'maxQuantityPerUser' => (int) ($seckillProduct->max_quantity_per_user ?: $session->max_quantity_per_user),
                'endTime' => $session->end_time?->toDateTimeString(),
                'startTime' => $session->start_time?->toDateTimeString(),
                'status' => $session->getDynamicStatus()->value,
            ],
        ]);
    }

    /**
     * 根据秒杀 SKU 过滤 specList，只保留相关规格值.
     */
    private function filterSpecList(array $specList, array $skuList): array
    {
        if ($skuList === []) {
            return [];
        }

        // 收集秒杀 SKU 涉及的 specValueId
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
