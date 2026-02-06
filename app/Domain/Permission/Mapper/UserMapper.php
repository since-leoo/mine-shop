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

use App\Domain\Permission\Entity\UserEntity;
use App\Infrastructure\Model\Permission\User;

/**
 * UserMapper 类用于在 User 模型和 UserEntity 实体之间进行转换。
 */
class UserMapper
{
    /**
     * 将 User 模型对象转换为 UserEntity 实体对象。
     *
     * @param User $model 需要转换的 User 模型对象
     * @return UserEntity 转换后的 UserEntity 实体对象
     */
    public static function fromModel(User $model): UserEntity
    {
        // 创建一个新的 UserEntity 实例
        $entity = new UserEntity();

        // 将模型中的属性逐个映射到实体对象中
        $entity->setId($model->id);
        $entity->setUsername($model->username);
        $entity->setAvatar($model->avatar ?? '');
        $entity->setEmail($model->email ?? '');
        $entity->setBackendSetting($model->backend_setting ?? []);
        $entity->setRemark($model->remark ?? '');
        $entity->setNickname($model->nickname ?? '');
        $entity->setPassword($model->password);
        $entity->setCreatedBy($model->created_by ?? 1);
        $entity->setPhone($model->phone ?? '');
        $entity->setSigned($model->signed ?? '');
        $entity->setUpdatedBy($model->updated_by ?? 1);
        $entity->setUserType($model->user_type);
        $entity->setStatus($model->status);

        // 返回填充完成的实体对象
        return $entity;
    }

    /**
     * 创建并返回一个新的 UserEntity 实例。
     *
     * @return UserEntity 新创建的 UserEntity 实例
     */
    public static function getNewEntity(): UserEntity
    {
        return new UserEntity();
    }
}
