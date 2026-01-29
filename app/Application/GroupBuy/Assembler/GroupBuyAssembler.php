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

namespace App\Application\GroupBuy\Assembler;

use App\Domain\GroupBuy\Entity\GroupBuyEntity;

/**
 * 团购活动组装器.
 */
final class GroupBuyAssembler
{
    /**
     * 从请求数据创建团购实体.
     *
     * @param array<string, mixed> $payload
     */
    public static function toCreateEntity(array $payload): GroupBuyEntity
    {
        return new GroupBuyEntity(
            id: 0,
            title: $payload['title'] ?? null,
            description: $payload['description'] ?? null,
            productId: isset($payload['product_id']) ? (int) $payload['product_id'] : null,
            skuId: isset($payload['sku_id']) ? (int) $payload['sku_id'] : null,
            originalPrice: isset($payload['original_price']) ? (float) $payload['original_price'] : null,
            groupPrice: isset($payload['group_price']) ? (float) $payload['group_price'] : null,
            minPeople: isset($payload['min_people']) ? (int) $payload['min_people'] : 2,
            maxPeople: isset($payload['max_people']) ? (int) $payload['max_people'] : 10,
            startTime: $payload['start_time'] ?? null,
            endTime: $payload['end_time'] ?? null,
            groupTimeLimit: isset($payload['group_time_limit']) ? (int) $payload['group_time_limit'] : 24,
            status: $payload['status'] ?? 'pending',
            totalQuantity: isset($payload['total_quantity']) ? (int) $payload['total_quantity'] : 0,
            soldQuantity: 0,
            groupCount: 0,
            successGroupCount: 0,
            sortOrder: isset($payload['sort_order']) ? (int) $payload['sort_order'] : 0,
            isEnabled: isset($payload['is_enabled']) ? (bool) $payload['is_enabled'] : true,
            rules: $payload['rules'] ?? null,
            images: $payload['images'] ?? null,
            remark: $payload['remark'] ?? null
        );
    }

    /**
     * 从请求数据创建更新实体.
     *
     * @param array<string, mixed> $payload
     */
    public static function toUpdateEntity(int $id, array $payload): GroupBuyEntity
    {
        return new GroupBuyEntity(
            id: $id,
            title: $payload['title'] ?? null,
            description: $payload['description'] ?? null,
            productId: isset($payload['product_id']) ? (int) $payload['product_id'] : null,
            skuId: isset($payload['sku_id']) ? (int) $payload['sku_id'] : null,
            originalPrice: isset($payload['original_price']) ? (float) $payload['original_price'] : null,
            groupPrice: isset($payload['group_price']) ? (float) $payload['group_price'] : null,
            minPeople: isset($payload['min_people']) ? (int) $payload['min_people'] : null,
            maxPeople: isset($payload['max_people']) ? (int) $payload['max_people'] : null,
            startTime: $payload['start_time'] ?? null,
            endTime: $payload['end_time'] ?? null,
            groupTimeLimit: isset($payload['group_time_limit']) ? (int) $payload['group_time_limit'] : null,
            status: $payload['status'] ?? null,
            totalQuantity: isset($payload['total_quantity']) ? (int) $payload['total_quantity'] : null,
            soldQuantity: null,
            groupCount: null,
            successGroupCount: null,
            sortOrder: isset($payload['sort_order']) ? (int) $payload['sort_order'] : null,
            isEnabled: isset($payload['is_enabled']) ? (bool) $payload['is_enabled'] : null,
            rules: $payload['rules'] ?? null,
            images: $payload['images'] ?? null,
            remark: $payload['remark'] ?? null
        );
    }
}
