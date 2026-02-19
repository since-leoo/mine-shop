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

use App\Domain\Trade\Seckill\Contract\SeckillSessionInput;
use App\Domain\Trade\Seckill\Entity\SeckillSessionEntity;
use App\Infrastructure\Model\Seckill\SeckillSession;

/**
 * 秒杀场次 Mapper.
 *
 * 负责实体与模型/DTO 之间的转换。
 */
final class SeckillSessionMapper
{
    /**
     * 从持久化模型重建实体.
     */
    public static function fromModel(SeckillSession $model): SeckillSessionEntity
    {
        return SeckillSessionEntity::reconstitute(
            id: $model->id,
            activityId: $model->activity_id,
            startTime: $model->start_time->toDateTimeString(),
            endTime: $model->end_time->toDateTimeString(),
            status: $model->status,
            maxQuantityPerUser: $model->max_quantity_per_user,
            totalQuantity: $model->total_quantity,
            soldQuantity: $model->sold_quantity,
            sortOrder: $model->sort_order,
            isEnabled: $model->is_enabled,
            rulesData: $model->rules,
            remark: $model->remark,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }

    /**
     * 从 DTO 创建新实体.
     */
    public static function fromDto(SeckillSessionInput $dto): SeckillSessionEntity
    {
        $entity = new SeckillSessionEntity();
        $entity->create($dto);
        return $entity;
    }

    /**
     * 获取空实体.
     *
     * @deprecated 使用 fromInput 代替
     */
    public static function getNewEntity(): SeckillSessionEntity
    {
        return new SeckillSessionEntity();
    }
}
