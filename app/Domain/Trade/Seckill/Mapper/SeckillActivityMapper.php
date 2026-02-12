<?php

declare(strict_types=1);

namespace App\Domain\Trade\Seckill\Mapper;

use App\Domain\Trade\Seckill\Entity\SeckillActivityEntity;
use App\Infrastructure\Model\Seckill\SeckillActivity;

final class SeckillActivityMapper
{
    public static function fromModel(SeckillActivity $model): SeckillActivityEntity
    {
        return SeckillActivityEntity::reconstitute(
            id: $model->id,
            title: $model->title,
            description: $model->description,
            status: $model->status,
            isEnabled: $model->is_enabled,
            rulesData: $model->rules,
            remark: $model->remark,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }

    public static function getNewEntity(): SeckillActivityEntity
    {
        return new SeckillActivityEntity();
    }
}
