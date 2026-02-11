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

namespace App\Interface\Api\Transformer;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;

final class CartTransformer
{
    public function __construct(private readonly DomainMallSettingService $mallSettingService) {}

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function transform(array $items, int $memberId): array
    {
        $storeTemplate = $this->buildStoreTemplate();
        $storeGoods = [];
        $invalidGoods = [];

        $goodsList = [];
        $shortageList = [];
        $lastJoinTime = null;
        $totalPrice = 0;

        foreach ($items as $item) {
            $sku = \is_array($item['sku'] ?? null) ? $item['sku'] : null;
            $product = \is_array($item['product'] ?? null) ? $item['product'] : null;
            if (! $sku || ! $product) {
                $invalidGoods[] = $this->formatInvalidGoods($item, $memberId);
                continue;
            }

            $goods = $this->formatGoods($item, $product, $sku, $memberId);
            $lastJoinTime ??= $goods['joinCartTime'];

            if ($this->isSaleable($product, $sku)) {
                if ((int) ($sku['stock'] ?? 0) > 0) {
                    $goodsList[] = $goods;
                    $totalPrice += (int) $goods['price'] * (int) $goods['quantity'];
                } else {
                    $shortageList[] = $goods;
                }
            } else {
                $invalidGoods[] = $goods;
            }
        }

        if ($goodsList !== [] || $shortageList !== []) {
            $store = $storeTemplate;
            $store['promotionGoodsList'][0]['goodsPromotionList'] = $goodsList;
            $store['promotionGoodsList'][0]['lastJoinTime'] = $lastJoinTime;
            $store['shortageGoodsList'] = $shortageList;
            $store['totalDiscountSalePrice'] = (string) $totalPrice;
            $storeGoods[] = $store;
        }

        return [
            'isNotEmpty' => $storeGoods !== [] || $invalidGoods !== [],
            'storeGoods' => $storeGoods,
            'invalidGoodItems' => array_values($invalidGoods),
        ];
    }

    private function buildStoreTemplate(): array
    {
        return [
            'storeId' => '1',
            'storeName' => $this->mallSettingService->basic()->mallName(),
            'storeStatus' => 1,
            'totalDiscountSalePrice' => '0',
            'promotionGoodsList' => [
                [
                    'title' => '默认优惠',
                    'promotionCode' => 'DEFAULT',
                    'promotionSubCode' => 'NONE',
                    'promotionId' => null,
                    'tagText' => [],
                    'promotionStatus' => 1,
                    'tag' => '',
                    'description' => '',
                    'doorSillRemain' => null,
                    'isNeedAddOnShop' => 0,
                    'goodsPromotionList' => [],
                    'lastJoinTime' => null,
                ],
            ],
            'shortageGoodsList' => [],
        ];
    }

    private function formatGoods(array $item, array $product, array $sku, int $memberId): array
    {
        $image = $sku['image'] ?? $product['main_image'] ?? null;
        $tags = $this->resolveTags($product);
        $skuStock = (int) ($sku['stock'] ?? 0);

        return [
            'cartId' => (string) ($sku['id'] ?? ''),
            'uid' => (string) $memberId,
            'saasId' => 'mine-mall',
            'storeId' => '1',
            'storeName' => $this->mallSettingService->basic()->mallName(),
            'spuId' => (string) ($product['id'] ?? ''),
            'skuId' => (string) ($sku['id'] ?? ''),
            'isSelected' => 1,
            'thumb' => $image,
            'title' => (string) ($product['name'] ?? ''),
            'primaryImage' => $image,
            'quantity' => (int) ($item['quantity'] ?? 0),
            'stockStatus' => $skuStock > 0,
            'stockQuantity' => $skuStock,
            'price' => $this->toCentString($sku['sale_price'] ?? 0),
            'originPrice' => $this->toCentString($sku['market_price'] ?? $sku['sale_price'] ?? 0),
            'tagPrice' => null,
            'titlePrefixTags' => $tags,
            'roomId' => null,
            'specInfo' => $this->formatSpecInfo($sku['spec_values'] ?? []),
            'joinCartTime' => $item['created_at'] ?? null,
            'available' => $this->isSaleable($product, $sku) ? 1 : 0,
            'putOnSale' => ((string) ($product['status'] ?? '')) === Product::STATUS_ACTIVE ? 1 : 0,
            'etitle' => null,
        ];
    }

    private function formatInvalidGoods(array $item, int $memberId): array
    {
        return [
            'cartId' => (string) ($item['sku_id'] ?? ''),
            'storeId' => '1',
            'spuId' => '',
            'skuId' => '',
            'isSelected' => 1,
            'title' => '商品已失效',
            'quantity' => (int) ($item['quantity'] ?? 0),
            'price' => '0',
            'originPrice' => '0',
            'stockStatus' => false,
            'stockQuantity' => 0,
            'available' => 0,
            'putOnSale' => 0,
            'specInfo' => [],
            'thumb' => null,
            'primaryImage' => null,
            'joinCartTime' => $item['created_at'] ?? null,
            'uid' => (string) $memberId,
        ];
    }

    /**
     * @return array<int, array{specTitle:string,specValue:string}>
     */
    private function formatSpecInfo(mixed $values): array
    {
        if (! \is_array($values)) {
            return [];
        }

        $result = [];
        foreach ($values as $value) {
            if (\is_array($value)) {
                $result[] = [
                    'specTitle' => (string) ($value['title'] ?? $value['specTitle'] ?? $value['name'] ?? ''),
                    'specValue' => (string) ($value['value'] ?? $value['specValue'] ?? ''),
                ];
            } elseif (\is_string($value) && $value !== '') {
                $parts = preg_split('/[:：]/', $value);
                $result[] = [
                    'specTitle' => (string) ($parts[0] ?? ''),
                    'specValue' => (string) ($parts[1] ?? $value),
                ];
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function resolveTags(array $product): array
    {
        $tags = [];
        if (! empty($product['is_new'])) {
            $tags[] = ['text' => '新品'];
        }
        if (! empty($product['is_hot'])) {
            $tags[] = ['text' => '热卖'];
        }
        if (! empty($product['is_recommend'])) {
            $tags[] = ['text' => '推荐'];
        }
        return $tags;
    }

    /**
     * 确保金额为字符串（分）。数据库已存储为分，无需转换.
     */
    private function toCentString(mixed $price): string
    {
        if ($price === null) {
            return '0';
        }

        return (string) (int) $price;
    }

    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $sku
     */
    private function isSaleable(array $product, array $sku): bool
    {
        return ((string) ($product['status'] ?? '')) === Product::STATUS_ACTIVE
            && ((string) ($sku['status'] ?? '')) === ProductSku::STATUS_ACTIVE;
    }
}
