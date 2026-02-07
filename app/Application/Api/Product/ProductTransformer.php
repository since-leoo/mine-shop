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

namespace App\Application\Api\Product;

use Hyperf\Stringable\Str;

final class ProductTransformer
{
    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    public function transformListItem(array $product): array
    {
        $sold = (int) ($product['real_sales'] ?? 0) + (int) ($product['virtual_sales'] ?? 0);

        return [
            'spuId' => (string) ($product['id'] ?? 0),
            'title' => (string) ($product['name'] ?? ''),
            'subTitle' => (string) ($product['sub_title'] ?? ''),
            'thumb' => $product['main_image'] ?? null,
            'price' => $this->toCent($product['min_price'] ?? 0),
            'originPrice' => $this->toCent($product['max_price'] ?? 0),
            'tags' => $this->resolveTags($product),
            'soldNum' => $sold,
            'isRecommend' => (bool) ($product['is_recommend'] ?? false),
            'isHot' => (bool) ($product['is_hot'] ?? false),
            'status' => $product['status'] ?? 'draft',
        ];
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    public function transformDetail(array $product): array
    {
        $skuList = $this->formatSkuList($product['skus'] ?? []);
        $specList = $this->buildSpecList($product['skus'] ?? []);
        $stockQuantity = array_sum(array_map(static fn ($sku) => (int) ($sku['stock'] ?? 0), $product['skus'] ?? []));

        return [
            'spuId' => (string) ($product['id'] ?? 0),
            'title' => (string) ($product['name'] ?? ''),
            'subTitle' => (string) ($product['sub_title'] ?? ''),
            'primaryImage' => $product['main_image'] ?? null,
            'images' => $this->normalizeImages($product),
            'desc' => $this->normalizeDescription($product),
            'detailContent' => $product['detail_content'] ?? '',
            'soldNum' => (int) ($product['real_sales'] ?? 0) + (int) ($product['virtual_sales'] ?? 0),
            'minSalePrice' => $this->toCent($product['min_price'] ?? 0),
            'maxSalePrice' => $this->toCent($product['max_price'] ?? 0),
            'maxLinePrice' => $this->toCent($product['max_price'] ?? 0),
            'skuList' => $skuList,
            'specList' => $specList,
            'spuStockQuantity' => $stockQuantity,
            'isPutOnSale' => ($product['status'] ?? '') === 'active' ? 1 : 0,
            'available' => ($product['status'] ?? '') === 'active' && $stockQuantity > 0 ? 1 : 0,
            'limitInfo' => [],
            'spuTagList' => $this->resolveTags($product),
            'storeId' => '1',
            'storeName' => '官方旗舰店',
            'attributes' => $this->formatAttributes($product['attributes'] ?? []),
        ];
    }

    /**
     * @param array<int, mixed> $skus
     * @return array<int, array<string, mixed>>
     */
    private function buildSpecList(array $skus): array
    {
        $specMap = [];
        foreach ($skus as $sku) {
            $values = $this->normalizeSpecValues($sku['spec_values'] ?? []);
            foreach ($values as $index => $value) {
                $specId = \sprintf('spec_%d', $index + 1);
                $specMap[$specId] ??= [
                    'specId' => $specId,
                    'title' => $value['title'],
                    'specValueList' => [],
                ];
                $specMap[$specId]['specValueList'][$value['value_id']] = [
                    'specValueId' => $value['value_id'],
                    'specValue' => $value['value'],
                    'image' => $value['image'],
                ];
            }
        }

        return array_values(array_map(static function (array $spec): array {
            $spec['specValueList'] = array_values($spec['specValueList']);
            return $spec;
        }, $specMap));
    }

    /**
     * @param array<int, mixed> $skus
     * @return array<int, array<string, mixed>>
     */
    private function formatSkuList(array $skus): array
    {
        return array_map(function (array $sku): array {
            $specValues = $this->normalizeSpecValues($sku['spec_values'] ?? []);
            return [
                'skuId' => (string) ($sku['id'] ?? $sku['sku_id'] ?? ''),
                'skuImage' => $sku['image'] ?? null,
                'specInfo' => array_map(static function (array $spec): array {
                    return [
                        'specId' => $spec['spec_id'],
                        'specTitle' => $spec['title'],
                        'specValueId' => $spec['value_id'],
                        'specValue' => $spec['value'],
                    ];
                }, $specValues),
                'priceInfo' => [
                    ['priceType' => 1, 'price' => $this->toCent($sku['sale_price'] ?? 0)],
                    ['priceType' => 2, 'price' => $this->toCent($sku['market_price'] ?? $sku['sale_price'] ?? 0)],
                ],
                'stockInfo' => [
                    'stockQuantity' => (int) ($sku['stock'] ?? 0),
                    'safeStockQuantity' => (int) ($sku['warning_stock'] ?? 0),
                    'soldQuantity' => (int) ($sku['sold_quantity'] ?? 0),
                ],
                'weight' => [
                    'value' => (float) ($sku['weight'] ?? 0),
                    'unit' => 'KG',
                ],
            ];
        }, $skus);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function formatAttributes(array $attributes): array
    {
        return array_values(array_map(static function ($attribute): array {
            return [
                'name' => (string) ($attribute['attribute_name'] ?? ''),
                'value' => (string) ($attribute['value'] ?? ''),
            ];
        }, $attributes));
    }

    /**
     * @param array<string, mixed> $product
     * @return string[]
     */
    private function resolveTags(array $product): array
    {
        $tags = [];
        if (! empty($product['is_recommend'])) {
            $tags[] = '精选';
        }
        if (! empty($product['is_hot'])) {
            $tags[] = '热卖';
        }
        if (! empty($product['is_new'])) {
            $tags[] = '上新';
        }
        return $tags;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeImages(array $product): array
    {
        $images = $product['gallery_images'] ?? [];
        if (! \is_array($images) || $images === []) {
            $images = $product['gallery'] ?? [];
        }
        $images = \is_array($images) ? $images : [];
        $primary = $product['main_image'] ?? null;
        if ($primary && ! \in_array($primary, $images, true)) {
            array_unshift($images, $primary);
        }

        return array_values(array_filter($images, static fn ($url) => \is_string($url) && $url !== ''));
    }

    /**
     * @return array<int, string>
     */
    private function normalizeDescription(array $product): array
    {
        if (! empty($product['gallery_images']) && \is_array($product['gallery_images'])) {
            return $product['gallery_images'];
        }

        if (! empty($product['detail_content']) && \is_string($product['detail_content'])) {
            preg_match_all('/<img[^>]+src=["\\\']([^"\\\']+)["\\\']/i', $product['detail_content'], $matches);
            if (! empty($matches[1])) {
                return array_values(array_unique($matches[1]));
            }
        }

        return $product['gallery'] ?? [];
    }

    /**
     * @return array<int, array{spec_id: string, title: string, value_id: string, value: string, image: null|string}>
     */
    private function normalizeSpecValues(mixed $values): array
    {
        if (! \is_array($values)) {
            if (\is_string($values) && $values !== '') {
                $values = [$values];
            } else {
                return [];
            }
        }

        $normalized = [];
        foreach (array_values($values) as $index => $value) {
            $title = \sprintf('规格%d', $index + 1);
            $realValue = $value;
            $image = null;

            if (\is_array($value)) {
                $title = (string) ($value['name'] ?? $value['title'] ?? $title);
                $realValue = $value['value'] ?? $value['spec_value'] ?? '';
                $image = $value['image'] ?? null;
            } elseif (\is_string($value) && Str::contains($value, [':', '：'])) {
                [$maybeTitle, $maybeValue] = preg_split('/[:：]/', $value, 2);
                if ($maybeValue !== null) {
                    $title = trim($maybeTitle);
                    $realValue = trim($maybeValue);
                }
            }

            $realValue = (string) $realValue;
            $specId = \sprintf('spec_%d', $index + 1);
            $valueId = \sprintf('%s_%s', $specId, mb_substr(md5($realValue), 0, 8));

            $normalized[] = [
                'spec_id' => $specId,
                'title' => $title !== '' ? $title : \sprintf('规格%d', $index + 1),
                'value_id' => $valueId,
                'value' => $realValue,
                'image' => $image,
            ];
        }

        return $normalized;
    }

    /**
     * 确保金额为整数（分）。数据库已存储为分，无需转换.
     */
    private function toCent(mixed $price): int
    {
        if (\is_string($price)) {
            return (int) $price;
        }

        if (! \is_int($price) && ! \is_float($price)) {
            return 0;
        }

        return (int) $price;
    }
}
