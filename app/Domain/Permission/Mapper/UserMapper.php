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

use App\Domain\Permission\Contract\User\UserInput;
use App\Domain\Permission\Entity\UserEntity;
use App\Infrastructure\Model\Permission\User;

/**
 * 用户 Mapper.
 *
 * 负责实体与模型/DTO 之间的转换。
 */
class UserMapper
{
    /**
     * 从持久化模型重建实体.
     *
     * @param User $model 数据库模型
     * @return UserEntity 用户实体
     */
    public static function fromModel(User $model): UserEntity
    {
        $entity = new UserEntity();

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

        return $entity;
    }

    /**
     * 从 DTO 创建新实体.
     *
     * @param UserInput $dto 用户输入 DTO
     * @return UserEntity 用户实体
     */
    public static function fromDto(UserInput $dto): UserEntity
    {
        $entity = new UserEntity();
        $entity->create($dto);
        return $entity;
    }

    /**
     * 获取新实体.
     *
     * @deprecated 使用 fromDto 代替
     */
    public static function getNewEntity(): UserEntity
    {
        return new UserEntity();
    }
}
