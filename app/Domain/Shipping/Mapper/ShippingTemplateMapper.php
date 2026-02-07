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

namespace App\Domain\Shipping\Mapper;

use App\Domain\Shipping\Entity\ShippingTemplateEntity;
use App\Infrastructure\Model\Shipping\ShippingTemplate;

/**
 * 运费模板映射器：Model ↔ Entity 转换.
 */
final class ShippingTemplateMapper
{
    /**
     * 从 Model 转换为 Entity.
     */
    public static function fromModel(ShippingTemplate $model): ShippingTemplateEntity
    {
        $entity = new ShippingTemplateEntity();

        $entity->setId((int) $model->id);
        $entity->setName($model->name);
        $entity->setChargeType($model->charge_type);
        $entity->setRules($model->rules);
        $entity->setFreeRules($model->free_rules);
        $entity->setIsDefault($model->is_default);
        $entity->setStatus($model->status);

        return $entity;
    }

    /**
     * 将 Entity 转换为持久化数组.
     *
     * @return array<string, mixed>
     */
    public static function toArray(ShippingTemplateEntity $entity): array
    {
        return $entity->toArray();
    }

    /**
     * 获取新实体.
     */
    public static function getNewEntity(): ShippingTemplateEntity
    {
        return new ShippingTemplateEntity();
    }
}
