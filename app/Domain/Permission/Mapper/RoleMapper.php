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

namespace App\Domain\Permission\Mapper;

use App\Domain\Permission\Entity\RoleEntity;
use App\Infrastructure\Model\Permission\Role;

/**
 * RoleMapper 类用于在 Role 模型和 RoleEntity 实体之间进行转换。
 */
class RoleMapper
{
    /**
     * 将 Role 模型对象转换为 RoleEntity 实体对象。
     *
     * @param Role $model 需要转换的 Role 模型对象
     * @return RoleEntity 转换后的 RoleEntity 实体对象
     */
    public static function fromModel(Role $model): RoleEntity
    {
        $entity = new RoleEntity();

        $entity->setId($model->id);
        $entity->setName($model->name);
        $entity->setCode($model->code);
        $entity->setStatus($model->status);
        $entity->setSort($model->sort ?? 0);
        $entity->setRemark($model->remark ?? null);
        $entity->setCreatedBy($model->created_by ?? 0);
        $entity->setUpdatedBy($model->updated_by ?? 0);

        return $entity;
    }

    /**
     * 创建并返回一个新的 RoleEntity 实例。
     *
     * @return RoleEntity 新创建的 RoleEntity 实例
     */
    public static function getNewEntity(): RoleEntity
    {
        return new RoleEntity();
    }
}
