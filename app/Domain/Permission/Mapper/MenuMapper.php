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

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Entity\MenuEntity;
use App\Infrastructure\Model\Permission\Menu;

/**
 * MenuMapper 类用于在 Menu 模型和 MenuEntity 实体之间进行转换。
 */
class MenuMapper
{
    /**
     * 将 Menu 模型对象转换为 MenuEntity 实体对象。
     *
     * @param Menu $model 需要转换的 Menu 模型对象
     * @return MenuEntity 转换后的 MenuEntity 实体对象
     */
    public static function fromModel(Menu $model): MenuEntity
    {
        $entity = new MenuEntity();

        $entity->setId($model->id);
        $entity->setParentId($model->parent_id ?? 0);
        $entity->setName($model->name);
        $entity->setPath($model->path);
        $entity->setComponent($model->component);
        $entity->setRedirect($model->redirect);
        $entity->setStatus(Status::tryFrom($model->status) ?? Status::Normal);
        $entity->setSort($model->sort ?? 0);
        $entity->setRemark($model->remark);
        $entity->setMeta($model->meta ?? []);
        $entity->setCreatedBy($model->created_by ?? 0);
        $entity->setUpdatedBy($model->updated_by ?? 0);

        return $entity;
    }

    /**
     * 创建并返回一个新的 MenuEntity 实例。
     *
     * @return MenuEntity 新创建的 MenuEntity 实例
     */
    public static function getNewEntity(): MenuEntity
    {
        return new MenuEntity();
    }
}
