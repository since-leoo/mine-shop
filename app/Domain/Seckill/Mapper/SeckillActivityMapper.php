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

namespace App\Domain\Seckill\Mapper;

use App\Domain\Seckill\Entity\SeckillActivityEntity;
use App\Infrastructure\Model\Seckill\SeckillActivity;

/**
 * 秒杀活动映射器.
 */
final class SeckillActivityMapper
{
    /**
     * 从Model转换为Entity.
     */
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

    /**
     * 获取新实体.
     */
    public static function getNewEntity(): SeckillActivityEntity
    {
        return new SeckillActivityEntity();
    }
}
