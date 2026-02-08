<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Mapper;

use Plugin\Since\Seckill\Domain\Entity\SeckillActivityEntity;
use Plugin\Since\Seckill\Infrastructure\Model\SeckillActivity;

final class SeckillActivityMapper
{
    public static function fromModel(SeckillActivity $model): SeckillActivityEntity
    {
        return SeckillActivityEntity::reconstitute(
            id: $model->id, title: $model->title, description: $model->description,
            status: $model->status, isEnabled: $model->is_enabled, rulesData: $model->rules,
            remark: $model->remark, createdAt: $model->created_at, updatedAt: $model->updated_at
        );
    }

    public static function getNewEntity(): SeckillActivityEntity
    {
        return new SeckillActivityEntity();
    }
}
