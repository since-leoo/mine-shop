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

namespace App\Application\Seckill\Assembler;

use App\Domain\Seckill\Entity\SeckillSessionEntity;

/**
 * 秒杀场次组装器.
 */
final class SeckillSessionAssembler
{
    /**
     * 从请求数据创建场次实体.
     *
     * @param array<string, mixed> $payload
     */
    public static function toCreateEntity(array $payload): SeckillSessionEntity
    {
        return new SeckillSessionEntity(
            id: 0,
            activityId: isset($payload['activity_id']) ? (int) $payload['activity_id'] : null,
            startTime: $payload['start_time'] ?? null,
            endTime: $payload['end_time'] ?? null,
            status: $payload['status'] ?? 'pending',
            maxQuantityPerUser: isset($payload['max_quantity_per_user']) ? (int) $payload['max_quantity_per_user'] : 1,
            totalQuantity: isset($payload['total_quantity']) ? (int) $payload['total_quantity'] : 0,
            soldQuantity: 0,
            sortOrder: isset($payload['sort_order']) ? (int) $payload['sort_order'] : 0,
            isEnabled: isset($payload['is_enabled']) ? (bool) $payload['is_enabled'] : true,
            rules: $payload['rules'] ?? null,
            remark: $payload['remark'] ?? null
        );
    }

    /**
     * 从请求数据创建更新实体.
     *
     * @param array<string, mixed> $payload
     */
    public static function toUpdateEntity(int $id, array $payload): SeckillSessionEntity
    {
        return new SeckillSessionEntity(
            id: $id,
            activityId: isset($payload['activity_id']) ? (int) $payload['activity_id'] : null,
            startTime: $payload['start_time'] ?? null,
            endTime: $payload['end_time'] ?? null,
            status: $payload['status'] ?? null,
            maxQuantityPerUser: isset($payload['max_quantity_per_user']) ? (int) $payload['max_quantity_per_user'] : null,
            totalQuantity: isset($payload['total_quantity']) ? (int) $payload['total_quantity'] : null,
            soldQuantity: isset($payload['sold_quantity']) ? (int) $payload['sold_quantity'] : null,
            sortOrder: isset($payload['sort_order']) ? (int) $payload['sort_order'] : null,
            isEnabled: isset($payload['is_enabled']) ? (bool) $payload['is_enabled'] : null,
            rules: $payload['rules'] ?? null,
            remark: $payload['remark'] ?? null
        );
    }
}
