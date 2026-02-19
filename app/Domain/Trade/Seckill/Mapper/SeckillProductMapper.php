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

namespace App\Domain\Trade\Seckill\Mapper;

use App\Domain\Trade\Seckill\Contract\SeckillProductInput;
use App\Domain\Trade\Seckill\Entity\SeckillProductEntity;
use App\Infrastructure\Model\Seckill\SeckillProduct;

/**
 * 秒杀商品 Mapper.
 *
 * 负责实体与模型/DTO 之间的转换。
 */
final class SeckillProductMapper
{
    /**
     * 从持久化模型重建实体.
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
     * 从 DTO 创建新实体.
     */
    public static function fromDto(SeckillProductInput $dto): SeckillProductEntity
    {
        $entity = new SeckillProductEntity();
        $entity->create($dto);
        return $entity;
    }

    /**
     * 获取空实体.
     *
     * @deprecated 使用 fromInput 代替
     */
    public static function getNewEntity(): SeckillProductEntity
    {
        return new SeckillProductEntity();
    }
}
