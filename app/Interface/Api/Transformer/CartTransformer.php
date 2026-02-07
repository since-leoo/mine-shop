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
            $lastJoinTime ??= $goods['join_cart_time'];

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
            $store['promotion_goods_list'][0]['goods_list'] = $goodsList;
            $store['promotion_goods_list'][0]['last_join_time'] = $lastJoinTime;
            $store['shortage_goods_list'] = $shortageList;
            $store['total_discount_sale_price'] = (string) $totalPrice;
            $storeGoods[] = $store;
        }

        return [
            'is_not_empty' => $storeGoods !== [] || $invalidGoods !== [],
            'store_goods' => $storeGoods,
            'invalid_items' => array_values($invalidGoods),
        ];
    }

    private function buildStoreTemplate(): array
    {
        return [
            'store_id' => '1',
            'store_name' => $this->mallSettingService->basic()->mallName(),
            'store_status' => 1,
            'total_discount_sale_price' => '0',
            'promotion_goods_list' => [
                [
                    'title' => '默认优惠',
                    'promotion_code' => 'DEFAULT',
                    'promotion_sub_code' => 'NONE',
                    'promotion_id' => null,
                    'tag_text' => [],
                    'promotion_status' => 1,
                    'tag' => '',
                    'description' => '',
                    'door_sill_remain' => null,
                    'is_need_add_on_shop' => 0,
                    'goods_list' => [],
                    'last_join_time' => null,
                ],
            ],
            'shortage_goods_list' => [],
        ];
    }

    private function formatGoods(array $item, array $product, array $sku, int $memberId): array
    {
        $image = $sku['image'] ?? $product['main_image'] ?? null;
        $tags = $this->resolveTags($product);
        $skuStock = (int) ($sku['stock'] ?? 0);

        return [
            'cart_id' => (string) ($sku['id'] ?? ''),
            'uid' => (string) $memberId,
            'saas_id' => 'mine-mall',
            'store_id' => '1',
            'store_name' => $this->mallSettingService->basic()->mallName(),
            'spu_id' => (string) ($product['id'] ?? ''),
            'sku_id' => (string) ($sku['id'] ?? ''),
            'thumb' => $image,
            'title' => (string) ($product['name'] ?? ''),
            'primary_image' => $image,
            'quantity' => (int) ($item['quantity'] ?? 0),
            'stock_status' => $skuStock > 0,
            'stock_quantity' => $skuStock,
            'price' => $this->toCentString($sku['sale_price'] ?? 0),
            'origin_price' => $this->toCentString($sku['market_price'] ?? $sku['sale_price'] ?? 0),
            'tag_price' => null,
            'title_prefix_tags' => $tags,
            'room_id' => null,
            'spec_info' => $this->formatSpecInfo($sku['spec_values'] ?? []),
            'join_cart_time' => $item['created_at'] ?? null,
            'available' => $this->isSaleable($product, $sku) ? 1 : 0,
            'put_on_sale' => ((string) ($product['status'] ?? '')) === Product::STATUS_ACTIVE ? 1 : 0,
            'etitle' => null,
        ];
    }

    private function formatInvalidGoods(array $item, int $memberId): array
    {
        return [
            'cart_id' => (string) ($item['sku_id'] ?? ''),
            'store_id' => '1',
            'spu_id' => '',
            'sku_id' => '',
            'title' => '商品已失效',
            'quantity' => (int) ($item['quantity'] ?? 0),
            'price' => '0',
            'origin_price' => '0',
            'stock_status' => false,
            'stock_quantity' => 0,
            'available' => 0,
            'put_on_sale' => 0,
            'spec_info' => [],
            'thumb' => null,
            'primary_image' => null,
            'join_cart_time' => $item['created_at'] ?? null,
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
