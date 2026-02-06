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

namespace App\Application\Mapper;

use App\Domain\Seckill\Entity\SeckillProductEntity;

/**
 * 秒杀商品组装器.
 */
final class SeckillProductAssembler
{
    /**
     * 从请求数据创建商品实体.
     *
     * @param array<string, mixed> $payload
     */
    public static function toCreateEntity(array $payload): SeckillProductEntity
    {
        return new SeckillProductEntity(
            id: 0,
            activityId: isset($payload['activity_id']) ? (int) $payload['activity_id'] : null,
            sessionId: isset($payload['session_id']) ? (int) $payload['session_id'] : null,
            productId: isset($payload['product_id']) ? (int) $payload['product_id'] : null,
            productSkuId: isset($payload['product_sku_id']) ? (int) $payload['product_sku_id'] : null,
            originalPrice: isset($payload['original_price']) ? (float) $payload['original_price'] : null,
            seckillPrice: isset($payload['seckill_price']) ? (float) $payload['seckill_price'] : null,
            quantity: isset($payload['quantity']) ? (int) $payload['quantity'] : 0,
            soldQuantity: 0,
            maxQuantityPerUser: isset($payload['max_quantity_per_user']) ? (int) $payload['max_quantity_per_user'] : 1,
            sortOrder: isset($payload['sort_order']) ? (int) $payload['sort_order'] : 0,
            isEnabled: ! isset($payload['is_enabled']) || $payload['is_enabled']
        );
    }

    /**
     * 从请求数据创建更新实体.
     *
     * @param array<string, mixed> $payload
     */
    public static function toUpdateEntity(int $id, array $payload): SeckillProductEntity
    {
        return new SeckillProductEntity(
            id: $id,
            activityId: isset($payload['activity_id']) ? (int) $payload['activity_id'] : null,
            sessionId: isset($payload['session_id']) ? (int) $payload['session_id'] : null,
            productId: isset($payload['product_id']) ? (int) $payload['product_id'] : null,
            productSkuId: isset($payload['product_sku_id']) ? (int) $payload['product_sku_id'] : null,
            originalPrice: isset($payload['original_price']) ? (float) $payload['original_price'] : null,
            seckillPrice: isset($payload['seckill_price']) ? (float) $payload['seckill_price'] : null,
            quantity: isset($payload['quantity']) ? (int) $payload['quantity'] : null,
            soldQuantity: isset($payload['sold_quantity']) ? (int) $payload['sold_quantity'] : null,
            maxQuantityPerUser: isset($payload['max_quantity_per_user']) ? (int) $payload['max_quantity_per_user'] : null,
            sortOrder: isset($payload['sort_order']) ? (int) $payload['sort_order'] : null,
            isEnabled: isset($payload['is_enabled']) ? (bool) $payload['is_enabled'] : null
        );
    }

    /**
     * 从批量数据创建商品实体数组.
     *
     * @param array<int, array<string, mixed>> $items
     * @return SeckillProductEntity[]
     */
    public static function toBatchCreateEntities(int $activityId, int $sessionId, array $items): array
    {
        $entities = [];
        foreach ($items as $item) {
            $item['activity_id'] = $activityId;
            $item['session_id'] = $sessionId;
            $entities[] = self::toCreateEntity($item);
        }
        return $entities;
    }
}
