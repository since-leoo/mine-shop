<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Mapper;

use Plugin\Since\Seckill\Domain\Entity\SeckillProductEntity;
use Plugin\Since\Seckill\Infrastructure\Model\SeckillProduct;

final class SeckillProductMapper
{
    public static function fromModel(SeckillProduct $model): SeckillProductEntity
    {
        return SeckillProductEntity::reconstitute(
            id: $model->id, activityId: $model->activity_id, sessionId: $model->session_id,
            productId: $model->product_id, productSkuId: $model->product_sku_id,
            originalPrice: (int) $model->original_price, seckillPrice: (int) $model->seckill_price,
            quantity: $model->quantity, soldQuantity: $model->sold_quantity,
            maxQuantityPerUser: $model->max_quantity_per_user, sortOrder: $model->sort_order,
            isEnabled: $model->is_enabled, createdAt: $model->created_at, updatedAt: $model->updated_at
        );
    }

    public static function getNewEntity(): SeckillProductEntity
    {
        return new SeckillProductEntity();
    }
}
