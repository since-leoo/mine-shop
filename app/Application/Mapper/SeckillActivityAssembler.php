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

use App\Domain\Seckill\Entity\SeckillActivityEntity;

/**
 * 秒杀活动组装器.
 */
final class SeckillActivityAssembler
{
    /**
     * 从请求数据创建活动实体.
     *
     * @param array<string, mixed> $payload
     */
    public static function toCreateEntity(array $payload): SeckillActivityEntity
    {
        return new SeckillActivityEntity(
            id: 0,
            title: $payload['title'] ?? null,
            description: $payload['description'] ?? null,
            status: $payload['status'] ?? 'pending',
            isEnabled: ! isset($payload['is_enabled']) || (bool) $payload['is_enabled'],
            rules: $payload['rules'] ?? null,
            remark: $payload['remark'] ?? null
        );
    }

    /**
     * 从请求数据创建更新实体.
     *
     * @param array<string, mixed> $payload
     */
    public static function toUpdateEntity(int $id, array $payload): SeckillActivityEntity
    {
        return new SeckillActivityEntity(
            id: $id,
            title: $payload['title'] ?? null,
            description: $payload['description'] ?? null,
            status: $payload['status'] ?? null,
            isEnabled: isset($payload['is_enabled']) ? (bool) $payload['is_enabled'] : null,
            rules: $payload['rules'] ?? null,
            remark: $payload['remark'] ?? null
        );
    }
}
