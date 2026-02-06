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

namespace App\Domain\Product\Trait;

use App\Domain\Product\Entity\ProductAttributeEntity;
use App\Domain\Product\Entity\ProductSkuEntity;
use App\Infrastructure\Model\Product\Product;
use Hyperf\Collection\Collection;

trait ProductEntityTrait
{
    public function getDeleteSkuIds(?Product $product): array
    {
        if ($product === null) {
            return [];
        }

        // 使用已加载的关联关系
        $product->loadMissing('skus');
        $skus = $product->getRelation('skus');

        if ($skus instanceof Collection) {
            $originalIds = $skus->pluck('id')->map(static fn ($id) => (int) $id)->toArray();
        } else {
            $originalIds = [];
        }

        $newIds = $this->extractIds($this->getSkus());

        return array_values(array_diff($originalIds, $newIds));
    }

    public function getDeleteAttributeIds(?Product $product): array
    {
        if ($product === null) {
            return [];
        }

        // 使用 getRelation() 避免与 JSON 字段 attributes 冲突
        $product->loadMissing('attributes');
        $attributes = $product->getRelation('attributes');

        if ($attributes instanceof Collection) {
            $originalIds = $attributes->pluck('id')->map(static fn ($id) => (int) $id)->toArray();
        } else {
            $originalIds = [];
        }

        $newIds = $this->extractIds($this->getAttributes());

        return array_values(array_diff($originalIds, $newIds));
    }

    /**
     * @param null|array<int, mixed> $items
     * @return int[]
     */
    private function extractIds(?array $items): array
    {
        if ($items === null) {
            return [];
        }

        $ids = [];
        foreach ($items as $item) {
            $id = null;
            if ($item instanceof ProductSkuEntity || $item instanceof ProductAttributeEntity) {
                $id = $item->getId();
            } elseif (\is_array($item) && isset($item['id'])) {
                $id = $item['id'];
            }

            if ($id !== null) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }
}
