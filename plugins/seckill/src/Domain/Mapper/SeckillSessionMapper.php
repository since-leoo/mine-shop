<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Mapper;

use Plugin\Since\Seckill\Domain\Entity\SeckillSessionEntity;
use Plugin\Since\Seckill\Infrastructure\Model\SeckillSession;

final class SeckillSessionMapper
{
    public static function fromModel(SeckillSession $model): SeckillSessionEntity
    {
        return SeckillSessionEntity::reconstitute(
            id: $model->id, activityId: $model->activity_id,
            startTime: $model->start_time->toDateTimeString(), endTime: $model->end_time->toDateTimeString(),
            status: $model->status, maxQuantityPerUser: $model->max_quantity_per_user,
            totalQuantity: $model->total_quantity, soldQuantity: $model->sold_quantity,
            sortOrder: $model->sort_order, isEnabled: $model->is_enabled,
            rulesData: $model->rules, remark: $model->remark,
            createdAt: $model->created_at, updatedAt: $model->updated_at
        );
    }

    public static function getNewEntity(): SeckillSessionEntity
    {
        return new SeckillSessionEntity();
    }
}
