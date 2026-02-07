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

namespace App\Domain\Marketing\Seckill\Mapper;

use App\Domain\Marketing\Seckill\Entity\SeckillProductEntity;
use App\Infrastructure\Model\Seckill\SeckillProduct;

/**
 * 秒杀商品映射器.
 */
final class SeckillProductMapper
{
    /**
     * 从Model转换为Entity.
     */
    public static function fromModel(SeckillProduct $model): SeckillProductEntity
    {
        return SeckillProductEntity::reconstitute(
            id: $model->id,
            activityId: $model->activity_id,
            sessionId: $model->session_id,
            productId: $model->product_id,
            productSkuId: $model->product_sku_id,
            originalPrice: (int) $model->original_price,
            seckillPrice: (int) $model->seckill_price,
            quantity: $model->quantity,
            soldQuantity: $model->sold_quantity,
            maxQuantityPerUser: $model->max_quantity_per_user,
            sortOrder: $model->sort_order,
            isEnabled: $model->is_enabled,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }

    /**
     * 获取新实体.
     */
    public static function getNewEntity(): SeckillProductEntity
    {
        return new SeckillProductEntity();
    }
}
