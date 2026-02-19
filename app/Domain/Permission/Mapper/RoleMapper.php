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

use App\Domain\Permission\Contract\Role\RoleInput;
use App\Domain\Permission\Entity\RoleEntity;
use App\Infrastructure\Model\Permission\Role;

/**
 * 角色 Mapper.
 *
 * 负责实体与模型/DTO 之间的转换。
 */
class RoleMapper
{
    /**
     * 从持久化模型重建实体.
     *
     * @param Role $model 数据库模型
     * @return RoleEntity 角色实体
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
     * 从 DTO 创建新实体.
     *
     * @param RoleInput $dto 角色输入 DTO
     * @return RoleEntity 角色实体
     */
    public static function fromDto(RoleInput $dto): RoleEntity
    {
        $entity = new RoleEntity();
        $entity->setName($dto->getName());
        $entity->setCode($dto->getCode());
        $entity->setStatus($dto->getStatus());
        $entity->setSort($dto->getSort());
        $entity->setRemark($dto->getRemark());
        $entity->setCreatedBy($dto->getCreatedBy());
        return $entity;
    }

    /**
     * 获取新实体.
     *
     * @deprecated 使用 fromDto 代替
     */
    public static function getNewEntity(): RoleEntity
    {
        return new RoleEntity();
    }
}
