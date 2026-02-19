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

use App\Domain\Trade\Seckill\Contract\SeckillActivityInput;
use App\Domain\Trade\Seckill\Entity\SeckillActivityEntity;
use App\Infrastructure\Model\Seckill\SeckillActivity;

/**
 * 秒杀活动 Mapper.
 *
 * 负责实体与模型/DTO 之间的转换。
 */
final class SeckillActivityMapper
{
    /**
     * 从持久化模型重建实体.
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
     * 从 DTO 创建新实体.
     */
    public static function fromDto(SeckillActivityInput $dto): SeckillActivityEntity
    {
        $entity = new SeckillActivityEntity();
        $entity->create($dto);
        return $entity;
    }

    /**
     * 获取空实体.
     *
     * @deprecated 使用 fromInput 代替
     */
    public static function getNewEntity(): SeckillActivityEntity
    {
        return new SeckillActivityEntity();
    }
}
